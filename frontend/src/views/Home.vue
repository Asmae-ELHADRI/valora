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
  Wrench,
  Hammer,
  Paintbrush,
  Sprout,
  SparklesIcon as Sparkles,
  Star
} from 'lucide-vue-next';
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import valoraLogo from '../assets/v-logo.png';
import LanguageSwitcher from '../components/LanguageSwitcher.vue';
import HeroParallax from '../components/HeroParallax.vue';

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
  
  // Setup Scroll Reveal Animations
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -100px 0px'
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
    
    <!-- HERO PARALLAX SECTION -->
    <HeroParallax 
      title="VALORA"
      subtitle="Valorisez l'effort qui révèle l'humain"
      ctaText="Découvrir nos services"
      ctaLink="/search"
      :sensitivity="-1"
    />

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

        <!-- Offers Grid with Premium Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
          <div 
            v-for="offer in filteredOffers" 
            :key="offer.id"
            class="group relative glass-card hover-lift hover-glow p-10 flex flex-col justify-between active:scale-95 overflow-hidden"
          >
            <!-- Gradient Border Effect -->
            <div class="absolute inset-0 bg-gradient-to-br from-amber-500/10 via-transparent to-amber-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none rounded-3xl"></div>
            
            <div class="space-y-8 relative z-10">
              <div class="flex justify-between items-center">
                <!-- Icon with Premium Hover -->
                <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center transition-all duration-500 group-hover:bg-gradient-to-br group-hover:from-amber-400 group-hover:to-amber-600 group-hover:rotate-6 group-hover:scale-110 group-hover:shadow-xl group-hover:shadow-amber-500/30">
                  <Briefcase class="w-8 h-8 text-[#1A2B4C] group-hover:text-white transition-colors" />
                </div>
                <!-- Category Badge -->
                <div class="bg-slate-50 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest text-slate-400 group-hover:bg-gradient-to-r group-hover:from-slate-800 group-hover:to-slate-900 group-hover:text-white transition-all duration-500">
                  {{ $t('categories.' + getCategoryKey(offer.category)) }}
                </div>
              </div>
              <div class="space-y-3">
                <!-- Title with Premium Gradient on Hover -->
                <h3 class="text-2xl font-black tracking-tight leading-tight transition-all duration-300 group-hover:text-gradient-gold">
                  {{ offer.title }}
                </h3>
                <!-- Meta Information -->
                <div class="flex flex-wrap items-center gap-4 text-slate-400 font-bold text-sm">
                  <span class="flex items-center gap-1.5 bg-slate-50/50 px-3 py-1 rounded-lg group-hover:bg-white group-hover:shadow-md transition-all">
                    <MapPin class="w-3.5 h-3.5 group-hover:text-amber-600 transition-colors" />
                    {{ offer.city }}
                  </span>
                  <span class="flex items-center gap-1.5 bg-[#facc15]/10 text-[#8B5E3C] px-3 py-1 rounded-lg group-hover:bg-gradient-to-r group-hover:from-amber-400 group-hover:to-amber-600 group-hover:text-white group-hover:shadow-md transition-all">
                    <CircleDollarSign class="w-3.5 h-3.5" />
                    {{ offer.salary }}
                  </span>
                </div>
              </div>
            </div>
            
            <!-- Premium Button with Shimmer -->
            <button 
              @click="handleApply(offer.id)"
              class="w-full mt-10 py-5 bg-slate-50 text-[#1A2B4C] rounded-2xl font-black group-hover:bg-gradient-to-r group-hover:from-amber-500 group-hover:to-amber-600 group-hover:text-white group-hover:shadow-2xl group-hover:shadow-amber-500/40 transition-all duration-500 flex items-center justify-center gap-3 overflow-hidden relative z-10"
            >
              <span class="relative z-10">{{ $t('home.jobs.apply_btn') }}</span>
              <ArrowRight class="w-4 h-4 relative z-10 opacity-0 -translate-x-4 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-500" />
              <!-- Shimmer Effect -->
              <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
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

        <!-- Artisans Premium Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div 
            v-for="artisan in artisans" 
            :key="artisan.id"
            class="group relative card-premium hover-lift hover-glow flex flex-col overflow-hidden"
          >
            <!-- Animated Gradient Border Glow -->
            <div class="absolute -inset-[1px] bg-gradient-to-br from-amber-500/20 via-transparent to-amber-500/20 opacity-0 group-hover:opacity-100 group-hover:animate-gradient-shift transition-opacity duration-500 pointer-events-none rounded-3xl"></div>
            
            <!-- Shimmer Sweep Effect -->
            <div class="absolute -inset-2 bg-gradient-to-r from-transparent via-amber-400/10 to-transparent opacity-0 group-hover:opacity-100 -translate-x-full group-hover:translate-x-full transition-all duration-1000 pointer-events-none"></div>
            
            <!-- Card Content -->
            <div class="flex items-start justify-between mb-4 relative z-10">
              <div class="relative">
                <!-- Avatar with Premium Hover -->
                <div class="w-20 h-20 rounded-2xl overflow-hidden shadow-md group-hover:scale-110 group-hover:rotate-3 group-hover:shadow-2xl group-hover:shadow-amber-500/20 transition-all duration-700 bg-slate-100">
                  <img :src="artisan.logo" :alt="artisan.name" class="w-full h-full object-cover">
                </div>
                <!-- Rating Badge with Glow -->
                <div class="absolute -bottom-2 -right-2 bg-gradient-to-br from-amber-400 to-amber-600 text-white text-[10px] font-black px-2 py-1 rounded-lg shadow-lg flex items-center gap-1 group-hover:scale-110 group-hover:shadow-xl group-hover:shadow-amber-500/40 transition-all">
                  <Star class="w-3 h-3 fill-current" />
                  {{ artisan.rating }}
                </div>
              </div>
              <!-- Category Badge -->
              <div class="glass-light px-3 py-1 rounded-full border border-white/30 group-hover:bg-gradient-to-r group-hover:from-slate-800 group-hover:to-slate-900 group-hover:text-white group-hover:border-slate-700 transition-all duration-500">
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-600 group-hover:text-white transition-colors">{{ artisan.categoryDisplay }}</span>
              </div>
            </div>
            
            <!-- Info Section -->
            <div class="relative z-10 mt-auto">
              <h3 class="text-xl font-bold text-[#1A2B4C] mb-1 group-hover:text-gradient-gold group-hover:translate-x-1 transition-all duration-500">{{ artisan.name }}</h3>
              <div class="flex items-center gap-2 text-slate-400 text-xs font-medium mb-3 group-hover:text-amber-600 group-hover:translate-x-1 transition-all duration-700">
                <MapPin class="w-3.5 h-3.5" />
                {{ artisan.city }}
              </div>
              
              <p class="text-slate-500 text-sm line-clamp-2 mb-4 leading-relaxed group-hover:text-slate-600">
                {{ artisan.description }}
              </p>
              
              <!-- Premium CTA Button -->
              <button class="w-full py-3.5 rounded-xl bg-slate-50 text-[#1A2B4C] font-black text-[10px] uppercase tracking-widest group-hover:bg-gradient-to-r group-hover:from-amber-500 group-hover:to-amber-600 group-hover:text-white group-hover:shadow-xl group-hover:shadow-amber-500/30 transition-all duration-500 relative overflow-hidden">
                <span class="relative z-10">{{ $t('common.view_profile') || 'Voir Profil' }}</span>
                <!-- Button Shimmer -->
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section "Comment ça marche ?" - Ultra Premium -->
    <section class="py-32 px-6 bg-gradient-to-b from-white via-slate-50/30 to-white relative reveal-on-scroll overflow-hidden">
      <!-- Background Decorative Elements -->
      <div class="absolute top-20 left-10 w-72 h-72 bg-amber-500/5 rounded-full blur-3xl"></div>
      <div class="absolute bottom-20 right-10 w-96 h-96 bg-blue-500/5 rounded-full blur-3xl"></div>
      
      <div class="max-w-7xl mx-auto relative z-10">
        <div class="text-center space-y-6 mb-24">
          <span class="inline-block text-gradient-gold font-black uppercase tracking-[0.3em] text-xs animate-fade-in-up px-6 py-2 bg-amber-50 rounded-full border border-amber-200">{{ $t('home.how_it_works.subtitle') }}</span>
          <h2 class="text-5xl md:text-7xl font-black tracking-tighter animate-fade-in-up">
            Comment ça <span class="text-gradient-gold italic font-playfair lowercase">marche ?</span>
          </h2>
          <p class="text-slate-600 text-lg max-w-2xl mx-auto font-medium">Rejoignez Valora en 3 étapes simples et commencez votre aventure professionnelle</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
          <!-- Animated Connector Lines -->
          <div class="hidden md:block absolute top-32 left-[16%] right-[16%] h-0.5 overflow-hidden">
            <div class="h-full bg-gradient-to-r from-transparent via-amber-500/30 to-transparent animate-shimmer"></div>
          </div>

          <!-- Step 1 - Enhanced Card -->
          <div class="relative group">
            <!-- Card Background with Gradient Border -->
            <div class="absolute -inset-1 bg-gradient-to-br from-blue-500/20 via-purple-500/10 to-transparent opacity-0 group-hover:opacity-100 rounded-3xl blur-xl transition-all duration-700"></div>
            
            <div class="relative card-premium p-10 space-y-6 hover-lift">
              <!-- Mega Number Background -->
              <div class="absolute top-8 right-8 text-[120px] font-black text-slate-100 leading-none select-none group-hover:text-blue-50 transition-colors">1</div>
              
              <!-- Icon Container -->
              <div class="relative w-28 h-28 mx-auto mb-6">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-400/20 to-blue-600/20 rounded-3xl blur-2xl opacity-0 group-hover:opacity-100 group-hover:animate-glow-pulse transition-opacity"></div>
                <div class="relative w-full h-full bg-gradient-to-br from-blue-50 to-blue-100 rounded-3xl flex items-center justify-center shadow-xl group-hover:shadow-2xl group-hover:shadow-blue-500/30 group-hover:scale-110 group-hover:rotate-6 transition-all duration-700">
                  <Search class="w-14 h-14 text-blue-600 group-hover:scale-110 transition-transform" />
                </div>
              </div>
              
              <!-- Content -->
              <div class="relative z-10 space-y-3">
                <h3 class="text-2xl md:text-3xl font-black text-slate-900 group-hover:text-blue-600 transition-colors">{{ $t('home.how_it_works.step1_title') }}</h3>
                <p class="text-slate-600 font-medium leading-relaxed text-base">{{ $t('home.how_it_works.step1_desc') }}</p>
              </div>
              
              <!-- Step Badge -->
              <div class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 rounded-full border border-blue-200 group-hover:bg-blue-600 group-hover:border-blue-600 transition-all">
                <span class="text-xs font-black uppercase tracking-wider text-blue-600 group-hover:text-white">Étape 1</span>
              </div>
            </div>
          </div>

          <!-- Step 2 - Featured Enhanced Card -->
          <div class="relative group md:-mt-4">
            <!-- Permanent Glow -->
            <div class="absolute -inset-2 bg-gradient-to-br from-amber-400/30 via-amber-500/20 to-amber-600/30 rounded-3xl blur-2xl animate-glow-pulse"></div>
            
            <div class="relative card-premium p-10 space-y-6 hover-lift border-2 border-amber-500/30">
              <!-- Mega Number Background -->
              <div class="absolute top-8 right-8 text-[120px] font-black text-amber-100 leading-none select-none">2</div>
              
              <!-- Icon Container - Featured -->
              <div class="relative w-32 h-32 mx-auto mb-6">
                <div class="absolute inset-0 bg-gradient-to-br from-amber-400/40 to-amber-600/40 rounded-3xl blur-2xl animate-glow-pulse"></div>
                <div class="relative w-full h-full bg-gradient-to-br from-amber-400 to-amber-600 rounded-3xl flex items-center justify-center shadow-2xl shadow-amber-500/40 group-hover:scale-110 group-hover:rotate-6 transition-all duration-700">
                  <UserPlus class="w-16 h-16 text-white group-hover:scale-110 transition-transform" />
                </div>
              </div>
              
              <!-- Content -->
              <div class="relative z-10 space-y-3">
                <h3 class="text-2xl md:text-3xl font-black text-gradient-gold">{{ $t('home.how_it_works.step2_title') }}</h3>
                <p class="text-slate-700 font-semibold leading-relaxed text-base">{{ $t('home.how_it_works.step2_desc') }}</p>
              </div>
              
              <!-- Step Badge - Featured -->
              <div class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 rounded-full shadow-lg shadow-amber-500/30">
                <span class="text-xs font-black uppercase tracking-wider text-white">⭐ Étape Clé</span>
              </div>
            </div>
          </div>

          <!-- Step 3 - Enhanced Card -->
          <div class="relative group">
            <!-- Card Background with Gradient Border -->
            <div class="absolute -inset-1 bg-gradient-to-br from-green-500/20 via-emerald-500/10 to-transparent opacity-0 group-hover:opacity-100 rounded-3xl blur-xl transition-all duration-700"></div>
            
            <div class="relative card-premium p-10 space-y-6 hover-lift">
              <!-- Mega Number Background -->
              <div class="absolute top-8 right-8 text-[120px] font-black text-slate-100 leading-none select-none group-hover:text-green-50 transition-colors">3</div>
              
              <!-- Icon Container -->
              <div class="relative w-28 h-28 mx-auto mb-6">
                <div class="absolute inset-0 bg-gradient-to-br from-green-400/20 to-green-600/20 rounded-3xl blur-2xl opacity-0 group-hover:opacity-100 group-hover:animate-glow-pulse transition-opacity"></div>
                <div class="relative w-full h-full bg-gradient-to-br from-green-50 to-green-100 rounded-3xl flex items-center justify-center shadow-xl group-hover:shadow-2xl group-hover:shadow-green-500/30 group-hover:scale-110 group-hover:rotate-6 transition-all duration-700">
                  <CheckCircle class="w-14 h-14 text-green-600 group-hover:scale-110 transition-transform" />
                </div>
              </div>
              
              <!-- Content -->
              <div class="relative z-10 space-y-3">
                <h3 class="text-2xl md:text-3xl font-black text-slate-900 group-hover:text-green-600 transition-colors">{{ $t('home.how_it_works.step3_title') }}</h3>
                <p class="text-slate-600 font-medium leading-relaxed text-base">{{ $t('home.how_it_works.step3_desc') }}</p>
              </div>
              
              <!-- Step Badge -->
              <div class="inline-flex items-center gap-2 px-4 py-2 bg-green-50 rounded-full border border-green-200 group-hover:bg-green-600 group-hover:border-green-600 transition-all">
                <span class="text-xs font-black uppercase tracking-wider text-green-600 group-hover:text-white">Étape 3</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section Catégories - Premium -->
    <section class="py-32 px-6 bg-white reveal-on-scroll">
      <div class="max-w-7xl mx-auto space-y-16">
        <div class="flex flex-col md:flex-row justify-between items-end gap-8">
          <div class="space-y-4">
            <span class="text-gradient-gold font-black uppercase tracking-widest text-xs animate-fade-in-up">{{ $t('home.categories_section.subtitle') }}</span>
            <h2 class="text-4xl md:text-6xl font-black tracking-tighter">
              Catégories <span class="text-gradient-gold italic font-playfair lowercase">populaires</span>
            </h2>
          </div>
          <!-- Premium CTA Button -->
          <button class="btn-premium">
            <span>{{ $t('home.categories_section.view_all') }}</span>
            <!-- Shimmer overlay -->
            <div class="absolute inset-0 shimmer"></div>
          </button>
        </div>

        <!-- Premium Category Grid with Specific Icons -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
          <!-- Plomberie -->
          <div class="group relative card-premium hover-lift hover-glow text-center space-y-4 cursor-pointer overflow-hidden">
            <div class="absolute -inset-[1px] bg-gradient-to-br from-blue-500/30 via-transparent to-blue-500/30 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none rounded-3xl"></div>
            <div class="relative z-10 w-16 h-16 glass-light rounded-2xl flex items-center justify-center mx-auto group-hover:bg-gradient-to-br group-hover:from-blue-400 group-hover:to-blue-600 group-hover:rotate-12 group-hover:scale-110 transition-all duration-500 shadow-sm group-hover:shadow-xl group-hover:shadow-blue-500/40">
              <Wrench class="w-8 h-8 text-blue-600 group-hover:text-white transition-colors" />
            </div>
            <span class="relative z-10 block font-black text-[10px] uppercase tracking-[0.2em] text-slate-400 group-hover:bg-gradient-to-r group-hover:from-blue-500 group-hover:via-blue-400 group-hover:to-blue-500 group-hover:bg-clip-text group-hover:text-transparent transition-all">{{ $t('categories.plomberie') }}</span>
          </div>

          <!-- Électricité -->
          <div class="group relative card-premium hover-lift hover-glow text-center space-y-4 cursor-pointer overflow-hidden">
            <div class="absolute -inset-[1px] bg-gradient-to-br from-yellow-500/30 via-transparent to-yellow-500/30 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none rounded-3xl"></div>
            <div class="relative z-10 w-16 h-16 glass-light rounded-2xl flex items-center justify-center mx-auto group-hover:bg-gradient-to-br group-hover:from-yellow-400 group-hover:to-yellow-600 group-hover:rotate-12 group-hover:scale-110 transition-all duration-500 shadow-sm group-hover:shadow-xl group-hover:shadow-yellow-500/40">
              <Zap class="w-8 h-8 text-yellow-600 group-hover:text-white transition-colors" />
            </div>
            <span class="relative z-10 block font-black text-[10px] uppercase tracking-[0.2em] text-slate-400 group-hover:bg-gradient-to-r group-hover:from-yellow-500 group-hover:via-yellow-400 group-hover:to-yellow-500 group-hover:bg-clip-text group-hover:text-transparent transition-all">{{ $t('categories.electricite') }}</span>
          </div>

          <!-- BTP -->
          <div class="group relative card-premium hover-lift hover-glow text-center space-y-4 cursor-pointer overflow-hidden">
            <div class="absolute -inset-[1px] bg-gradient-to-br from-orange-500/30 via-transparent to-orange-500/30 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none rounded-3xl"></div>
            <div class="relative z-10 w-16 h-16 glass-light rounded-2xl flex items-center justify-center mx-auto group-hover:bg-gradient-to-br group-hover:from-orange-400 group-hover:to-orange-600 group-hover:rotate-12 group-hover:scale-110 transition-all duration-500 shadow-sm group-hover:shadow-xl group-hover:shadow-orange-500/40">
              <Hammer class="w-8 h-8 text-orange-600 group-hover:text-white transition-colors" />
            </div>
            <span class="relative z-10 block font-black text-[10px] uppercase tracking-[0.2em] text-slate-400 group-hover:bg-gradient-to-r group-hover:from-orange-500 group-hover:via-orange-400 group-hover:to-orange-500 group-hover:bg-clip-text group-hover:text-transparent transition-all">{{ $t('categories.btp') }}</span>
          </div>

          <!-- Jardinage -->
          <div class="group relative card-premium hover-lift hover-glow text-center space-y-4 cursor-pointer overflow-hidden">
            <div class="absolute -inset-[1px] bg-gradient-to-br from-green-500/30 via-transparent to-green-500/30 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none rounded-3xl"></div>
            <div class="relative z-10 w-16 h-16 glass-light rounded-2xl flex items-center justify-center mx-auto group-hover:bg-gradient-to-br group-hover:from-green-400 group-hover:to-green-600 group-hover:rotate-12 group-hover:scale-110 transition-all duration-500 shadow-sm group-hover:shadow-xl group-hover:shadow-green-500/40">
              <Sprout class="w-8 h-8 text-green-600 group-hover:text-white transition-colors" />
            </div>
            <span class="relative z-10 block font-black text-[10px] uppercase tracking-[0.2em] text-slate-400 group-hover:bg-gradient-to-r group-hover:from-green-500 group-hover:via-green-400 group-hover:to-green-500 group-hover:bg-clip-text group-hover:text-transparent transition-all">{{ $t('categories.jardinage') }}</span>
          </div>

          <!-- Peinture -->
          <div class="group relative card-premium hover-lift hover-glow text-center space-y-4 cursor-pointer overflow-hidden">
            <div class="absolute -inset-[1px] bg-gradient-to-br from-purple-500/30 via-transparent to-purple-500/30 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none rounded-3xl"></div>
            <div class="relative z-10 w-16 h-16 glass-light rounded-2xl flex items-center justify-center mx-auto group-hover:bg-gradient-to-br group-hover:from-purple-400 group-hover:to-purple-600 group-hover:rotate-12 group-hover:scale-110 transition-all duration-500 shadow-sm group-hover:shadow-xl group-hover:shadow-purple-500/40">
              <Paintbrush class="w-8 h-8 text-purple-600 group-hover:text-white transition-colors" />
            </div>
            <span class="relative z-10 block font-black text-[10px] uppercase tracking-[0.2em] text-slate-400 group-hover:bg-gradient-to-r group-hover:from-purple-500 group-hover:via-purple-400 group-hover:to-purple-500 group-hover:bg-clip-text group-hover:text-transparent transition-all">{{ $t('categories.peinture') }}</span>
          </div>

          <!-- Nettoyage -->
          <div class="group relative card-premium hover-lift hover-glow text-center space-y-4 cursor-pointer overflow-hidden">
            <div class="absolute -inset-[1px] bg-gradient-to-br from-cyan-500/30 via-transparent to-cyan-500/30 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none rounded-3xl"></div>
            <div class="relative z-10 w-16 h-16 glass-light rounded-2xl flex items-center justify-center mx-auto group-hover:bg-gradient-to-br group-hover:from-cyan-400 group-hover:to-cyan-600 group-hover:rotate-12 group-hover:scale-110 transition-all duration-500 shadow-sm group-hover:shadow-xl group-hover:shadow-cyan-500/40">
              <Sparkles class="w-8 h-8 text-cyan-600 group-hover:text-white transition-colors" />
            </div>
            <span class="relative z-10 block font-black text-[10px] uppercase tracking-[0.2em] text-slate-400 group-hover:bg-gradient-to-r group-hover:from-cyan-500 group-hover:via-cyan-400 group-hover:to-cyan-500 group-hover:bg-clip-text group-hover:text-transparent transition-all">{{ $t('categories.nettoyage') }}</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Section Témoignages - Premium -->
    <section class="py-32 px-6 bg-white relative overflow-hidden reveal-on-scroll">
      <div class="max-w-7xl mx-auto relative z-10">
        <div class="text-center space-y-4 mb-20">
          <span class="text-gradient-gold font-black uppercase tracking-widest text-[10px] animate-fade-in-up">{{ $t('home.testimonials.subtitle') }}</span>
          <h2 class="text-4xl md:text-6xl font-black tracking-tighter">
            Ce qu'ils disent <span class="text-gradient-gold italic font-playfair lowercase">de nous</span>
          </h2>
        </div>

        <!-- Premium Testimonials Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
          <!-- Testimonial 1 - Premium -->
          <div class="relative card-premium hover-lift hover-glow p-12 overflow-hidden">
            <!-- Gradient Glow Border -->
            <div class="absolute -inset-[1px] bg-gradient-to-br from-amber-500/20 via-transparent to-amber-500/20 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none rounded-3xl"></div>
            
            <!-- Quote Icon Background -->
            <MessageSquare class="w-16 h-16 text-slate-50 absolute top-12 right-12 group-hover:text-amber-500/10 group-hover:scale-125 transition-all duration-500" />
            
            <!-- Header with Avatar -->
            <div class="flex items-center gap-6 mb-8 relative z-10">
              <!-- Premium Avatar with Glow -->
              <div class="relative">
                <div class="absolute -inset-1 bg-gradient-to-br from-amber-500/40 to-transparent rounded-2xl blur-md opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <img src="https://i.pravatar.cc/150?u=sarah" class="relative w-20 h-20 rounded-2xl object-cover shadow-lg group-hover:scale-105 group-hover:shadow-2xl group-hover:shadow-amber-500/20 transition-all duration-500" alt="Sarah L.">
              </div>
              <!-- Name and Badge -->
              <div>
                <h4 class="text-xl font-black mb-2">Sarah L.</h4>
                <span class="inline-block bg-gradient-to-r from-amber-400 to-amber-600 text-white px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest shadow-md">{{ $t('home.testimonials.role1') }}</span>
              </div>
            </div>
            
            <!-- Premium Star Rating -->
            <div class="flex gap-1 mb-6 relative z-10">
              <Star v-for="i in 5" :key="i" class="w-5 h-5 text-amber-500 fill-current group-hover:scale-110 transition-transform" :style="{transitionDelay: `${i * 50}ms`}" />
            </div>
            
            <!-- Quote Text -->
            <p class="text-xl text-slate-800 font-medium leading-relaxed italic relative z-10">
              "{{ $t('home.testimonials.text1') }}"
            </p>
          </div>

          <!-- Testimonial 2 - Premium -->
          <div class="relative card-premium hover-lift hover-glow p-12 overflow-hidden">
            <!-- Gradient Glow Border -->
            <div class="absolute -inset-[1px] bg-gradient-to-br from-amber-500/20 via-transparent to-amber-500/20 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none rounded-3xl"></div>
            
            <!-- Quote Icon Background -->
            <MessageSquare class="w-16 h-16 text-slate-50 absolute top-12 right-12 group-hover:text-amber-500/10 group-hover:scale-125 transition-all duration-500" />
            
            <!-- Header with Avatar -->
            <div class="flex items-center gap-6 mb-8 relative z-10">
              <!-- Premium Avatar with Glow -->
              <div class="relative">
                <div class="absolute -inset-1 bg-gradient-to-br from-amber-500/40 to-transparent rounded-2xl blur-md opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <img src="https://i.pravatar.cc/150?u=marc" class="relative w-20 h-20 rounded-2xl object-cover shadow-lg group-hover:scale-105 group-hover:shadow-2xl group-hover:shadow-amber-500/20 transition-all duration-500" alt="Marc D.">
              </div>
              <!-- Name and Badge -->
              <div>
                <h4 class="text-xl font-black mb-2">Marc D.</h4>
                <span class="inline-block bg-gradient-to-r from-amber-400 to-amber-600 text-white px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest shadow-md">{{ $t('home.testimonials.role2') }}</span>
              </div>
            </div>
            
            <!-- Premium Star Rating -->
            <div class="flex gap-1 mb-6 relative z-10">
              <Star v-for="i in 5" :key="i" class="w-5 h-5 text-amber-500 fill-current group-hover:scale-110 transition-transform" :style="{transitionDelay: `${i * 50}ms`}" />
            </div>
            
            <!-- Quote Text -->
            <p class="text-xl text-slate-800 font-medium leading-relaxed italic relative z-10">
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
