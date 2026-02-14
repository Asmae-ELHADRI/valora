<script setup>
import { 
  ArrowRight, 
  MapPin, 
  CircleDollarSign,
  UserPlus,
  Zap,
  Briefcase,
  Search
} from 'lucide-vue-next';
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import valoraLogo from '../assets/v-logo.png';
import LanguageSwitcher from '../components/LanguageSwitcher.vue';

const auth = useAuthStore();
const router = useRouter();

const artisans = ref([]);

const fetchArtisans = async () => {
  try {
    const response = await api.get('/api/provider?limit=4&sort_by=newest');
    if (response.data.data) {
      artisans.value = response.data.data.map(user => ({
        id: user.id,
        name: user.name,
        category: user.prestataire?.categories?.[0]?.slug || 'general',
        categoryDisplay: user.prestataire?.categories?.[0]?.name || 'Prestataire',
        logo: user.prestataire?.photo_url || `https://ui-avatars.com/api/?name=${user.name}&background=random`,
        rating: user.prestataire?.rating || '5.0',
        city: user.prestataire?.city || 'Maroc',
        description: user.prestataire?.description || 'Professionnel qualifié sur Valora.'
      }));
    }
  } catch (err) {
    console.error('Erreur chargement artisans:', err);
  }
};

const offers = ref([
  { id: 1, title: 'Rénovation Cuisine', category: 'btp', city: 'Casablanca', salary: '2500 MAD' },
  { id: 2, title: 'Installation Électrique', category: 'electricite', city: 'Rabat', salary: '1200 MAD' },
  { id: 4, title: 'Peinture Appartement', category: 'peinture', city: 'Tanger', salary: '3000 MAD' },
  { id: 6, title: 'Nettoyage de Printemps', category: 'nettoyage', city: 'Agadir', salary: '600 MAD' },
]);

const filters = ref({
  category: '',
  city: '',
  salary: ''
});

const isVisible = ref(false);

const filteredOffers = computed(() => {
  return offers.value.filter(offer => {
    const matchCategory = !filters.value.category || offer.category === filters.value.category;
    const matchCity = !filters.value.city || offer.city.toLowerCase().includes(filters.value.city.toLowerCase());
    const matchSalary = !filters.value.salary || offer.salary.includes(filters.value.salary);
    return matchCategory && matchCity && matchSalary;
  });
});

const fetchLatestOffers = async () => {
  try {
    const response = await api.get('/api/offers');
    if (response.data.data) {
      offers.value = response.data.data.slice(0, 3).map(o => ({
        id: o.id,
        title: o.title,
        category: o.category?.name || 'Service',
        city: o.location || 'Maroc',
        salary: o.budget + ' MAD'
      }));
    }
  } catch (err) {
    console.error('Erreur lors du chargement des offres:', err);
  }
};


const handleApply = (offerId) => {
  if (!auth.isAuthenticated) {
    router.push('/login');
  } else {
    // If logged in, go to search page with this offer selected or just search
    router.push({ name: 'Search', query: { id: offerId } });
  }
};

const getCategoryKey = (name) => {
  if (!name) return 'service';
  return name.toLowerCase()
    .normalize("NFD").replace(/[\u0300-\u036f]/g, "") // remove accents
    .replace(/ & /g, '_')
    .replace(/ /g, '_')
    .replace(/[^a-z0-9_]/g, '');
};

onMounted(() => {
  isVisible.value = true;
  fetchLatestOffers();
  fetchArtisans();
});
</script>

<template>
  <div class="min-h-screen bg-[#FAF9F6] text-[#1A2B4C] font-sans selection:bg-[#F4C430] selection:text-[#1A2B4C] overflow-x-hidden">
    
    <!-- Header / Hero Section -->
    <header class="relative h-screen flex flex-col items-center justify-center px-4 overflow-hidden">
      <!-- Decorative Background Elements -->
      <div class="absolute inset-0 z-0 opacity-40">
        <div class="absolute top-[10%] left-[5%] w-[400px] h-[400px] bg-[#F4C430]/10 rounded-full blur-[100px]"></div>
        <div class="absolute bottom-[20%] right-[10%] w-[500px] h-[500px] bg-[#8B5E3C]/5 rounded-full blur-[120px]"></div>
      </div>


      <!-- Centerpiece Section -->
      <div class="relative z-10 flex flex-col items-center justify-center w-full max-w-4xl pt-20">
        <!-- The Orbit Container (Central Circle) -->
        <div class="relative w-[300px] h-36 md:w-[500px] md:h-72 flex items-center justify-center">
          <div 
            class="relative z-20 w-36 h-36 md:w-72 md:h-72 bg-slate-200/20 backdrop-blur-md rounded-full flex items-center justify-center shadow-inner border border-slate-200/30 p-0 transition-all duration-1000 transform group"
            :class="isVisible ? 'scale-100 opacity-100' : 'scale-50 opacity-0'"
          >
            <div class="absolute inset-0 bg-gradient-to-tr from-[#F4C430]/10 to-transparent rounded-full animate-pulse group-hover:scale-110 transition-transform duration-700"></div>
            <div class="relative flex flex-col items-center w-full h-full">
              <img :src="valoraLogo" alt="Valora" class="w-full h-full object-contain transform scale-150 group-hover:rotate-12 transition-transform duration-500 drop-shadow-[0_0_20px_rgba(250,204,21,0.2)]">
            </div>
          </div>
        </div>

        <!-- Slogan (Now Directly Below Logo) -->
        <div class="mt-4 text-center px-3 transition-all duration-1000 delay-500 transform" :class="isVisible ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0'">
          <h2 class="text-2xl md:text-5xl font-black text-[#1A2B4C] leading-tight">
            "{{ $t('home.slogan_part1') }} 
            <span class="text-[#8B5E3C] italic font-playfair lowercase">{{ $t('home.slogan_highlight') }}</span> 
            {{ $t('home.slogan_part2') }}"
          </h2>
        </div>
      </div>


      
    </header>

    <!-- Section Artisans -->
    <section class="py-32 px-4 bg-white relative overflow-hidden">
      <!-- Background numbers decoration -->
      <div class="absolute top-0 right-[-5%] text-[20vw] font-black text-slate-50 opacity-10 select-none">VALORA</div>
      
      <div class="max-w-7xl mx-auto space-y-20 relative z-10">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-8">
          <div class="space-y-4">
            <span class="bg-[#F4C430]/20 text-[#1A2B4C] px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest">{{ $t('home.artisans.community') }}</span>
            <h2 class="text-4xl md:text-6xl font-black tracking-tighter">{{ $t('home.artisans.title') }}</h2>
          </div>
          <p class="text-slate-500 max-w-sm font-medium leading-relaxed">
            {{ $t('home.artisans.description') }}
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div 
            v-for="artisan in artisans" 
            :key="artisan.id"
            class="group bg-white p-6 rounded-[2rem] hover:shadow-xl transition-all duration-500 border border-slate-100 flex flex-col relative overflow-hidden"
          >
            <!-- Hover Gradient Background -->
            <div class="absolute inset-0 bg-gradient-to-b from-transparent via-[#1A2B4C]/5 to-[#1A2B4C]/90 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

            <div class="flex items-start justify-between mb-4 relative z-10">
                <div class="relative">
                    <div class="w-20 h-20 rounded-2xl overflow-hidden shadow-md group-hover:scale-105 transition-transform duration-500 bg-slate-100">
                        <img :src="artisan.logo" :alt="artisan.name" class="w-full h-full object-cover">
                    </div>
                    <div class="absolute -bottom-2 -right-2 bg-[#F4C430] text-[#1A2B4C] text-[10px] font-black px-2 py-1 rounded-lg shadow-sm flex items-center gap-1">
                        <Star class="w-3 h-3 fill-current" />
                        {{ artisan.rating }}
                    </div>
                </div>
                <div class="bg-slate-50 px-3 py-1 rounded-full border border-slate-100 group-hover:bg-white/20 group-hover:text-white group-hover:border-white/20 transition-colors">
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 group-hover:text-white">{{ artisan.categoryDisplay }}</span>
                </div>
            </div>
            <div class="relative z-10 mt-auto">
                <h3 class="text-xl font-bold text-[#1A2B4C] group-hover:text-white transition-colors mb-1">{{ artisan.name }}</h3>
                <div class="flex items-center gap-2 text-slate-400 group-hover:text-slate-300 text-xs font-medium mb-3">
                    <MapPin class="w-3.5 h-3.5" />
                    {{ artisan.city }}
                </div>
                
                <p class="text-slate-500 text-sm line-clamp-2 mb-4 group-hover:text-slate-200 transition-colors">
                    {{ artisan.description }}
                </p>

                <button class="w-full py-3 rounded-xl bg-[#FAF9F6] text-[#1A2B4C] font-bold text-xs uppercase tracking-widest hover:bg-[#F4C430] transition-colors shadow-sm group-hover:shadow-lg">
                    Voir Profil
                </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section Offres d'emploi -->
    <section class="py-32 px-4 bg-[#FAF9F6] relative">
      <div class="max-w-7xl mx-auto space-y-20">
        <div class="text-center space-y-6">
          <h2 class="text-4xl md:text-6xl font-black tracking-tighter">{{ $t('home.jobs.title') }}</h2>
          <div class="flex justify-center">
            <div class="w-24 h-1.5 bg-[#F4C430] rounded-full"></div>
          </div>
        </div>

        <!-- Advanced Filters -->
        <div class="bg-white p-8 rounded-[3rem] shadow-2xl shadow-indigo-900/5 grid grid-cols-1 md:grid-cols-3 gap-8 items-end border border-slate-50">
          <div class="space-y-3">
            <label class="text-xs font-black text-[#1A2B4C]/40 uppercase tracking-widest ml-1">{{ $t('home.jobs.filter_category') }}</label>
            <div class="relative group">
              <select v-model="filters.category" class="w-full bg-[#FAF9F6] border-none rounded-2xl px-6 py-4 focus:ring-2 focus:ring-[#F4C430] outline-none transition-all appearance-none font-bold">
                <option value="">{{ $t('home.jobs.all_categories') }}</option>
                <option value="btp">{{ $t('categories.btp') }}</option>
                <option value="electricite">{{ $t('categories.electricite') }}</option>
                <option value="jardinage">{{ $t('categories.jardinage') }}</option>
                <option value="plomberie">{{ $t('categories.plomberie') }}</option>
              </select>
              <div class="absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none opacity-30">
                <Search class="w-4 h-4" />
              </div>
            </div>
          </div>
          <div class="space-y-3">
            <label class="text-xs font-black text-[#1A2B4C]/40 uppercase tracking-widest ml-1">{{ $t('home.jobs.filter_city') }}</label>
            <div class="relative">
              <input v-model="filters.city" type="text" placeholder="Casablanca" class="w-full bg-[#FAF9F6] border-none rounded-2xl px-6 py-4 focus:ring-2 focus:ring-[#F4C430] outline-none transition-all font-bold placeholder:opacity-20">
              <MapPin class="absolute right-6 top-1/2 -translate-y-1/2 w-4 h-4 opacity-30" />
            </div>
          </div>
          <div class="space-y-3">
            <label class="text-xs font-black text-[#1A2B4C]/40 uppercase tracking-widest ml-1">{{ $t('home.jobs.filter_salary') }}</label>
            <div class="relative">
              <input v-model="filters.salary" type="text" placeholder="1000 MAD" class="w-full bg-[#FAF9F6] border-none rounded-2xl px-6 py-4 focus:ring-2 focus:ring-[#F4C430] outline-none transition-all font-bold placeholder:opacity-20">
              <CircleDollarSign class="absolute right-6 top-1/2 -translate-y-1/2 w-4 h-4 opacity-30" />
            </div>
          </div>
        </div>

        <!-- Offers Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
          <div 
            v-for="offer in filteredOffers" 
            :key="offer.id"
            class="group bg-white p-10 rounded-[3rem] shadow-sm hover:shadow-2xl hover:-translate-y-3 transition-all duration-500 border border-slate-50 flex flex-col justify-between"
          >
            <div class="space-y-8">
              <div class="flex justify-between items-center">
                <div class="w-16 h-16 bg-[#1A2B4C]/5 rounded-2xl flex items-center justify-center transition-colors group-hover:bg-[#F4C430]/10">
                  <Briefcase class="w-8 h-8 text-[#1A2B4C]" />
                </div>
                <div class="bg-[#FAF9F6] px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest text-slate-400 group-hover:bg-[#1A2B4C] group-hover:text-white transition-all">
                  {{ $t('categories.' + getCategoryKey(offer.category)) }}
                </div>
              </div>
              <div class="space-y-3">
                <h3 class="text-2xl font-black tracking-tight leading-tight transition-colors">{{ offer.title }}</h3>
                <div class="flex flex-wrap items-center gap-4 text-slate-400 font-bold text-sm">
                  <span class="flex items-center gap-1.5 bg-[#FAF9F6] px-3 py-1 rounded-lg">
                    <MapPin class="w-3.5 h-3.5" />
                    {{ offer.city }}
                  </span>
                  <span class="flex items-center gap-1.5 bg-[#F4C430]/10 text-[#8B5E3C] px-3 py-1 rounded-lg">
                    <CircleDollarSign class="w-3.5 h-3.5" />
                    {{ offer.salary }}
                  </span>
                </div>
              </div>
            </div>
            
            <button 
              @click="handleApply(offer.id)"
              class="w-full mt-10 py-5 bg-[#FAF9F6] text-[#1A2B4C] rounded-2xl font-black group-hover:bg-[#F4C430] group-hover:shadow-lg group-hover:shadow-[#F4C430]/20 transition-all flex items-center justify-center gap-3"
            >
              {{ $t('home.jobs.apply_btn') }}
              <ArrowRight class="w-4 h-4 opacity-0 -translate-x-4 group-hover:opacity-100 group-hover:translate-x-0 transition-all" />
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- Single Persistent CTA at the Bottom -->
    <div class="fixed bottom-10 left-1/2 -translate-x-1/2 z-50">
      <router-link to="/login" class="flex items-center gap-4 bg-[#1A2B4C] text-white pl-8 pr-12 py-5 rounded-full font-black shadow-[0_20px_40px_rgba(26,43,76,0.3)] hover:scale-105 active:scale-95 transition-all group overflow-hidden border-2 border-white/20">
        <div class="relative w-6 h-6">
          <UserPlus class="w-6 h-6 relative z-10" />
          <div class="absolute inset-0 bg-[#F4C430] rounded-full scale-[2] opacity-0 blur-md group-hover:opacity-30 group-hover:animate-pulse transition-all"></div>
        </div>
        <span class="uppercase tracking-widest text-xs whitespace-nowrap">{{ $t('home.jobs.register_apply') }}</span>
        <div class="absolute right-0 top-0 h-full w-2 bg-[#F4C430]"></div>
      </router-link>
    </div>

  </div>
</template>

<style scoped>
.font-playfair {
  font-family: 'Playfair Display', serif;
}

@keyframes reveal {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Custom transitions */
.transition-slow {
  transition: all 1.2s cubic-bezier(0.4, 0, 0.2, 1);
}

select {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%231A2B4C'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
  background-size: 1.5rem;
  background-position: right 1.5rem center;
  background-repeat: no-repeat;
}
</style>
