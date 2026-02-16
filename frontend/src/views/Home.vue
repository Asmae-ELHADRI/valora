<script setup>
import { 
  ArrowRight,
  MapPin,
  CircleDollarSign,
  UserPlus,
  Zap,
  Briefcase,
  Search,
  CheckCircle,
  MessageSquare,
  LayoutGrid,
  Star
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

  // Scroll Reveal Observer
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('reveal-visible');
      }
    });
  }, observerOptions);

  document.querySelectorAll('.reveal-on-scroll').forEach(el => {
    observer.observe(el);
  });
});
</script>

<template>
  <div class="min-h-screen bg-white font-sans text-slate-900 selection:bg-[#facc15] selection:text-slate-900 overflow-x-hidden">
    
    <!-- HERO SECTION -->
    <header class="relative min-h-[90vh] flex items-center justify-center overflow-hidden bg-white text-slate-900 reveal-on-scroll">
      <!-- Background Image with Overlay -->
      <div class="absolute inset-0 z-0 overflow-hidden">
         <img src="https://images.unsplash.com/photo-1581578731117-104f8a74695e?q=80&w=2070&auto=format&fit=crop" class="w-full h-full object-cover opacity-20 scale-110 animate-slow-zoom" alt="Background">
         <div class="absolute inset-0 bg-gradient-to-b from-white/90 via-white/70 to-white"></div>
         <!-- Dynamic Radial Glow -->
         <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[150%] h-[150%] bg-[radial-gradient(circle,rgba(250,204,21,0.08)_0%,transparent_50%)] animate-slow-spin-slow z-[-1]"></div>
      </div>

      <!-- Animated Blobs -->
      <div class="absolute top-[10%] left-[10%] w-96 h-96 bg-[#facc15]/5 rounded-full blur-[100px] animate-pulse-slow"></div>
      <div class="absolute bottom-[10%] right-[10%] w-96 h-96 bg-[#2563eb]/5 rounded-full blur-[100px] animate-pulse-slow delay-1000"></div>

      <div class="relative z-10 max-w-7xl mx-auto px-6 text-center pt-20 flex flex-col items-center">
         <!-- Large Central Logo Area -->
         <div class="mb-12 animate-in fade-in zoom-in duration-1000">
            <div class="relative w-64 h-64 md:w-80 md:h-80 mx-auto flex items-center justify-center animate-float">
                <!-- Circular background/border -->
                <div class="absolute inset-0 rounded-full border-8 border-slate-100/50 bg-white shadow-2xl scale-105"></div>
                <!-- Logo Image -->
                <img :src="valoraLogo" alt="Valora Illustration" class="w-full h-full object-contain relative z-10 p-8 drop-shadow-2xl rounded-full">
            </div>
         </div>
         
         <!-- Slogan -->
         <h1 class="text-3xl sm:text-4xl md:text-6xl font-black text-[#1A2B4C] mb-12 animate-in fade-in slide-in-from-bottom-6 duration-1000 delay-200">
            "Valorisez <span class="text-[#facc15] italic font-playfair lowercase">l'effort</span> qui révèle l'humain"
         </h1>
      </div>
    </header>

    <!-- Section Offres d'emploi (Services) -->
    <section class="py-32 px-6 bg-white relative reveal-on-scroll">
      <div class="max-w-7xl mx-auto space-y-20">
        <div class="text-center space-y-6">
          <h2 class="text-4xl md:text-6xl font-black tracking-tighter">
            Services <span class="text-[#facc15] italic font-playfair lowercase">& opportunités</span>
          </h2>
          <div class="flex justify-center">
            <div class="w-24 h-1.5 bg-[#facc15] rounded-full"></div>
          </div>
        </div>

        <!-- Advanced Filters -->
        <div class="bg-white p-8 rounded-[3rem] shadow-2xl shadow-indigo-900/5 grid grid-cols-1 md:grid-cols-3 gap-8 items-end border border-slate-50">
          <div class="space-y-3">
            <label class="text-xs font-black text-[#1A2B4C]/40 uppercase tracking-widest ml-1">{{ $t('home.jobs.filter_category') }}</label>
            <div class="relative group">
              <select v-model="filters.category" class="w-full bg-white border-none rounded-2xl px-6 py-4 focus:ring-2 focus:ring-[#F4C430] outline-none transition-all appearance-none font-bold">
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
              <input v-model="filters.city" type="text" placeholder="Casablanca" class="w-full bg-white border-none rounded-2xl px-6 py-4 focus:ring-2 focus:ring-[#F4C430] outline-none transition-all font-bold placeholder:opacity-20">
              <MapPin class="absolute right-6 top-1/2 -translate-y-1/2 w-4 h-4 opacity-30" />
            </div>
          </div>
          <div class="space-y-3">
            <label class="text-xs font-black text-[#1A2B4C]/40 uppercase tracking-widest ml-1">{{ $t('home.jobs.filter_salary') }}</label>
            <div class="relative">
              <input v-model="filters.salary" type="text" placeholder="1000 MAD" class="w-full bg-white border-none rounded-2xl px-6 py-4 focus:ring-2 focus:ring-[#F4C430] outline-none transition-all font-bold placeholder:opacity-20">
              <CircleDollarSign class="absolute right-6 top-1/2 -translate-y-1/2 w-4 h-4 opacity-30" />
            </div>
          </div>
        </div>

        <!-- Offers Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
          <div 
            v-for="offer in filteredOffers" 
            :key="offer.id"
            class="group bg-white p-10 rounded-[3rem] shadow-sm hover:shadow-[0_30px_60px_rgba(0,0,0,0.08)] hover:-translate-y-3 transition-all duration-700 border border-slate-50 flex flex-col justify-between active:scale-95"
          >
            <div class="space-y-8">
              <div class="flex justify-between items-center">
                <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center transition-all duration-500 group-hover:bg-[#facc15] group-hover:rotate-6">
                  <Briefcase class="w-8 h-8 text-[#1A2B4C] transition-colors" />
                </div>
                <div class="bg-slate-50 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest text-slate-400 group-hover:bg-[#1A2B4C] group-hover:text-white transition-all duration-500">
                  {{ $t('categories.' + getCategoryKey(offer.category)) }}
                </div>
              </div>
              <div class="space-y-3">
                <h3 class="text-2xl font-black tracking-tight leading-tight transition-colors group-hover:text-[#1A2B4C]">{{ offer.title }}</h3>
                <div class="flex flex-wrap items-center gap-4 text-slate-400 font-bold text-sm">
                  <span class="flex items-center gap-1.5 bg-slate-50/50 px-3 py-1 rounded-lg group-hover:bg-slate-50 transition-colors">
                    <MapPin class="w-3.5 h-3.5" />
                    {{ offer.city }}
                  </span>
                  <span class="flex items-center gap-1.5 bg-[#facc15]/10 text-[#8B5E3C] px-3 py-1 rounded-lg group-hover:bg-[#facc15]/20 transition-colors">
                    <CircleDollarSign class="w-3.5 h-3.5" />
                    {{ offer.salary }}
                  </span>
                </div>
              </div>
            </div>
            
            <button 
              @click="handleApply(offer.id)"
              class="w-full mt-10 py-5 bg-slate-50 text-[#1A2B4C] rounded-2xl font-black group-hover:bg-[#facc15] group-hover:shadow-[0_15px_30px_rgba(250,204,21,0.3)] transition-all duration-500 flex items-center justify-center gap-3 overflow-hidden relative"
            >
              <span class="relative z-10">{{ $t('home.jobs.apply_btn') }}</span>
              <ArrowRight class="w-4 h-4 relative z-10 opacity-0 -translate-x-4 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-500" />
              <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/20 to-white/0 -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- Section Artisans -->
    <section class="py-32 px-6 bg-white relative overflow-hidden">
      <!-- Background numbers decoration -->
      <div class="absolute top-0 right-[-5%] text-[20vw] font-black text-slate-50 opacity-10 select-none">VALORA</div>
      
      <div class="max-w-7xl mx-auto space-y-20 relative z-10">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-8">
          <div class="space-y-4">
            <span class="bg-[#facc15]/20 text-[#1A2B4C] px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest">{{ $t('home.artisans.community') }}</span>
            <h2 class="text-4xl md:text-6xl font-black tracking-tighter">
              Nos <span class="text-[#facc15] italic font-playfair lowercase">Artisans</span>
            </h2>
          </div>
          <p class="text-slate-500 max-w-sm font-medium leading-relaxed">
            {{ $t('home.artisans.description') }}
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div 
            v-for="artisan in artisans" 
            :key="artisan.id"
            class="group bg-white p-6 rounded-[2rem] hover:shadow-[0_20px_50px_rgba(0,0,0,0.1)] transition-all duration-700 border border-slate-100 flex flex-col relative overflow-hidden active:scale-95"
          >
            <!-- Hover Glow Effect -->
            <div class="absolute -inset-2 bg-gradient-to-r from-transparent via-[#facc15]/10 to-transparent opacity-0 group-hover:opacity-100 group-hover:translate-x-full transition-all duration-1000 pointer-events-none"></div>
            
            <div class="flex items-start justify-between mb-4 relative z-10">
                <div class="relative">
                    <div class="w-20 h-20 rounded-2xl overflow-hidden shadow-md group-hover:scale-110 group-hover:rotate-2 transition-transform duration-700 bg-slate-100">
                        <img :src="artisan.logo" :alt="artisan.name" class="w-full h-full object-cover">
                    </div>
                    <div class="absolute -bottom-2 -right-2 bg-[#facc15] text-[#1A2B4C] text-[10px] font-black px-2 py-1 rounded-lg shadow-lg flex items-center gap-1 group-hover:scale-110 transition-transform">
                        <Star class="w-3 h-3 fill-current" />
                        {{ artisan.rating }}
                    </div>
                </div>
                <div class="backdrop-blur-md bg-slate-50/50 px-3 py-1 rounded-full border border-slate-100 group-hover:bg-[#1A2B4C] group-hover:text-white transition-all duration-500">
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 group-hover:text-white transition-colors">{{ artisan.categoryDisplay }}</span>
                </div>
            </div>
            <div class="relative z-10 mt-auto">
                <h3 class="text-xl font-bold text-[#1A2B4C] mb-1 group-hover:translate-x-1 transition-transform duration-500">{{ artisan.name }}</h3>
                <div class="flex items-center gap-2 text-slate-400 text-xs font-medium mb-3 group-hover:translate-x-1 transition-transform duration-700">
                    <MapPin class="w-3.5 h-3.5" />
                    {{ artisan.city }}
                </div>
                
                <p class="text-slate-500 text-sm line-clamp-2 mb-4 leading-relaxed">
                    {{ artisan.description }}
                </p>
                <button class="w-full py-3.5 rounded-xl bg-slate-50 text-[#1A2B4C] font-black text-[10px] uppercase tracking-widest hover:bg-[#facc15] transition-all duration-500 shadow-sm hover:shadow-lg hover:-translate-y-1">
                    {{ $t('common.view_profile') || 'Voir Profil' }}
                </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section "Comment ça marche ?" -->
    <section class="py-32 px-6 bg-white relative reveal-on-scroll">
      <div class="max-w-7xl mx-auto">
        <div class="text-center space-y-4 mb-20">
          <span class="text-[#facc15] font-black uppercase tracking-widest text-[10px]">{{ $t('home.how_it_works.subtitle') }}</span>
          <h2 class="text-4xl md:text-6xl font-black tracking-tighter">
            Comment ça <span class="text-[#facc15] italic font-playfair lowercase">marche ?</span>
          </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-12 relative">
          <!-- Connector Line (Desktop) -->
          <div class="hidden md:block absolute top-[45px] left-[15%] right-[15%] h-0.5 bg-gradient-to-r from-transparent via-slate-100 to-transparent z-0"></div>

          <div class="relative z-10 text-center space-y-6 group">
            <div class="w-24 h-24 bg-white border-2 border-slate-100 rounded-full flex items-center justify-center mx-auto shadow-xl group-hover:border-[#facc15] transition-colors duration-500">
                <Search class="w-10 h-10 text-slate-900" />
                <div class="absolute -top-2 -right-2 w-8 h-8 bg-slate-900 text-white rounded-full flex items-center justify-center font-black text-sm">1</div>
            </div>
            <h3 class="text-2xl font-black">{{ $t('home.how_it_works.step1_title') }}</h3>
            <p class="text-slate-500 font-medium">{{ $t('home.how_it_works.step1_desc') }}</p>
          </div>

          <div class="relative z-10 text-center space-y-6 group">
            <div class="w-24 h-24 bg-white border-2 border-[#facc15] rounded-full flex items-center justify-center mx-auto shadow-xl shadow-[#facc15]/20 group-hover:scale-110 transition-transform duration-500">
                <UserPlus class="w-10 h-10 text-slate-900" />
                <div class="absolute -top-2 -right-2 w-8 h-8 bg-[#facc15] text-slate-900 rounded-full flex items-center justify-center font-black text-sm">2</div>
            </div>
            <h3 class="text-2xl font-black">{{ $t('home.how_it_works.step2_title') }}</h3>
            <p class="text-slate-500 font-medium">{{ $t('home.how_it_works.step2_desc') }}</p>
          </div>

          <div class="relative z-10 text-center space-y-6 group">
            <div class="w-24 h-24 bg-white border-2 border-slate-100 rounded-full flex items-center justify-center mx-auto shadow-xl group-hover:border-[#facc15] transition-colors duration-500">
                <CheckCircle class="w-10 h-10 text-slate-900" />
                <div class="absolute -top-2 -right-2 w-8 h-8 bg-slate-900 text-white rounded-full flex items-center justify-center font-black text-sm">3</div>
            </div>
            <h3 class="text-2xl font-black">{{ $t('home.how_it_works.step3_title') }}</h3>
            <p class="text-slate-500 font-medium">{{ $t('home.how_it_works.step3_desc') }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Section Catégories -->
    <section class="py-32 px-6 bg-white reveal-on-scroll">
      <div class="max-w-7xl mx-auto space-y-16">
        <div class="flex flex-col md:flex-row justify-between items-end gap-8">
          <div class="space-y-4">
            <span class="text-[#facc15] font-black uppercase tracking-widest text-xs">{{ $t('home.categories_section.subtitle') }}</span>
            <h2 class="text-4xl md:text-6xl font-black tracking-tighter">
              Catégories <span class="text-[#facc15] italic font-playfair lowercase">populaires</span>
            </h2>
          </div>
          <button class="px-8 py-3 bg-slate-900 text-white rounded-xl font-black text-xs uppercase tracking-widest hover:bg-[#facc15] hover:text-slate-900 transition-all">
            {{ $t('home.categories_section.view_all') }}
          </button>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
          <div v-for="cat in ['plomberie', 'electricite', 'btp', 'jardinage', 'peinture', 'nettoyage']" :key="cat" 
               class="group p-8 bg-white border border-slate-100 rounded-[2.5rem] text-center space-y-4 hover:shadow-[0_20px_40px_rgba(0,0,0,0.06)] hover:-translate-y-2 transition-all duration-700 cursor-pointer active:scale-95">
            <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mx-auto group-hover:bg-[#facc15] group-hover:rotate-6 transition-all duration-500 shadow-sm group-hover:shadow-[#facc15]/40 group-hover:shadow-lg">
              <LayoutGrid class="w-8 h-8 text-slate-900" />
            </div>
            <span class="block font-black text-[10px] uppercase tracking-[0.2em] text-slate-400 group-hover:text-[#1A2B4C] transition-colors">{{ $t('categories.' + cat) }}</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Section Témoignages -->
    <section class="py-32 px-6 bg-white relative overflow-hidden reveal-on-scroll">
      <div class="max-w-7xl mx-auto relative z-10">
        <div class="text-center space-y-4 mb-20">
          <span class="text-[#facc15] font-black uppercase tracking-widest text-[10px]">{{ $t('home.testimonials.subtitle') }}</span>
          <h2 class="text-4xl md:text-6xl font-black tracking-tighter">
            Ce qu'ils disent <span class="text-[#facc15] italic font-playfair lowercase">de nous</span>
          </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
          <!-- Testimonial 1 -->
          <div class="bg-white p-12 rounded-[3.5rem] shadow-sm border border-slate-50 relative group hover:shadow-2xl transition-all duration-500">
            <div class="flex items-center gap-6 mb-8">
               <img src="https://i.pravatar.cc/150?u=sarah" class="w-20 h-20 rounded-2xl object-cover shadow-lg" alt="Sarah L.">
               <div>
                 <h4 class="text-xl font-black">Sarah L.</h4>
                 <span class="bg-[#facc15] px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest">{{ $t('home.testimonials.role1') }}</span>
               </div>
               <MessageSquare class="w-12 h-12 text-slate-100 absolute top-12 right-12 group-hover:text-[#facc15]/20 transition-colors" />
            </div>
            <div class="flex gap-1 mb-6">
               <Star v-for="i in 5" :key="i" class="w-4 h-4 text-[#facc15] fill-current" />
            </div>
            <p class="text-xl text-slate-800 font-medium leading-relaxed italic">
              "{{ $t('home.testimonials.text1') }}"
            </p>
          </div>

          <!-- Testimonial 2 -->
          <div class="bg-white p-12 rounded-[3.5rem] shadow-sm border border-slate-50 relative group hover:shadow-2xl transition-all duration-500">
            <div class="flex items-center gap-6 mb-8">
               <img src="https://i.pravatar.cc/150?u=marc" class="w-20 h-20 rounded-2xl object-cover shadow-lg" alt="Marc D.">
               <div>
                 <h4 class="text-xl font-black">Marc D.</h4>
                 <span class="bg-[#facc15] px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest">{{ $t('home.testimonials.role2') }}</span>
               </div>
               <MessageSquare class="w-12 h-12 text-slate-100 absolute top-12 right-12 group-hover:text-[#facc15]/20 transition-colors" />
            </div>
            <div class="flex gap-1 mb-6">
               <Star v-for="i in 5" :key="i" class="w-4 h-4 text-[#facc15] fill-current" />
            </div>
            <p class="text-xl text-slate-800 font-medium leading-relaxed italic">
              "{{ $t('home.testimonials.text2') }}"
            </p>
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

/* Premium Animations */
@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-20px); }
}

@keyframes slow-spin-slow {
  from { transform: translate(-50%, -50%) rotate(0deg); }
  to { transform: translate(-50%, -50%) rotate(360deg); }
}

@keyframes pulse-slow {
  0%, 100% { opacity: 0.3; transform: scale(1); }
  50% { opacity: 0.6; transform: scale(1.1); }
}

@keyframes slow-zoom {
  0% { transform: scale(1.1); }
  100% { transform: scale(1.3); }
}

.animate-float {
  animation: float 6s ease-in-out infinite;
}

.animate-slow-spin-slow {
  animation: slow-spin-slow 20s linear infinite;
}

.animate-pulse-slow {
  animation: pulse-slow 8s ease-in-out infinite;
}

.animate-slow-zoom {
  animation: slow-zoom 30s linear infinite alternate;
}

/* Scroll Reveal Classes */
.reveal-on-scroll {
  opacity: 0;
  transform: translateY(30px);
  transition: all 1s cubic-bezier(0.2, 0.8, 0.2, 1);
}

.reveal-visible {
  opacity: 1;
  transform: translateY(0);
}

select {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%231A2B4C'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
  background-size: 1.5rem;
  background-position: right 1.5rem center;
  background-repeat: no-repeat;
}
</style>
