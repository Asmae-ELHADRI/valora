<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import { 
  User, Mail, Phone, MapPin, Briefcase, Calendar, Clock, 
  Camera, CheckCircle, AlertCircle, Loader2, Edit3, Save, X, Fingerprint, Coins,
  ChevronDown, ArrowRight, Award, LifeBuoy, Star, ShieldCheck, Zap, 
  Trophy, TrendingUp, Users, Check, ExternalLink, Plus, FileText, Search
} from 'lucide-vue-next';
import PhotoUploader from '../components/PhotoUploader.vue';
import { MOROCCAN_CITIES } from '../constants/cities';

const auth = useAuthStore();
const loading = ref(true);
const saving = ref(false);
const error = ref(null);
const success = ref(null);

// Dashboard State
const activeTab = ref('overview');
const applications = ref([]);
const reviewsData = ref({ reviews: [], average_rating: 0, total_reviews: 0 });

// mode = 'view' | 'create' | 'edit'
const mode = ref('view');
const profileExists = ref(false);
const categories = ref([]);

// Wizard State
const currentStep = ref(1);
const weekDays = [
  { label: 'Lun', value: 'lundi' },
  { label: 'Mar', value: 'mardi' },
  { label: 'Mer', value: 'mercredi' },
  { label: 'Jeu', value: 'jeudi' },
  { label: 'Ven', value: 'vendredi' },
  { label: 'Sam', value: 'samedi' },
  { label: 'Dim', value: 'dimanche' },
];

const citySearch = ref('');
const filteredCities = computed(() => {
    if (!citySearch.value) return [];
    const search = citySearch.value.toLowerCase();
    return MOROCCAN_CITIES.filter(city => city.toLowerCase().includes(search));
});

const selectCity = (city) => {
    profileData.value.city = city;
    citySearch.value = '';
};

const profileData = ref({
  first_name: '',
  last_name: '',
  phone: '',
  birth_date: '',
  cin: '',
  address: '',
  city: '',
  description: '',
  skills: '',
  experience: '',
  hourly_rate: '',
  category_id: null,
  category_ids: [],
  availabilities: {
    startDate: '',
    date: '',
    time: '',
    days: []
  },
  missions_count: 0
});

const fetchCategories = async () => {
  try {
    const response = await api.get('/api/offers/categories');
    categories.value = response.data;
  } catch (err) {
    console.error('Erreur catégories:', err);
  }
};

const fetchApplications = async () => {
  try {
    const response = await api.get('/api/my-applications');
    applications.value = response.data.data;
  } catch (err) {
    console.error('Erreur chargement candidatures:', err);
  }
};

const fetchReviews = async () => {
  try {
    const response = await api.get('/api/reviews/provider');
    reviewsData.value = response.data;
  } catch (err) {
    console.error('Erreur chargement avis:', err);
  }
};

const fetchProfile = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.get('/api/provider/profile');
    if (response.data.exists) {
      profileExists.value = true;
      const user = response.data.user;
      
      // Extract categories
      const userCategories = user.prestataire?.categories || [];
      
      // Parse availability
      let avail = user.prestataire?.availabilities;
      if (typeof avail === 'string') {
          try { 
              avail = JSON.parse(avail); 
          } catch (e) { 
              console.warn('Failed to parse availabilities JSON', e);
          }
      }
      
      if (!avail || typeof avail !== 'object') {
          avail = { startDate: '', days: [], date: '', time: '' };
      } else if (Array.isArray(avail)) {
          avail = { startDate: '', days: avail, date: '', time: '' };
      }
      
      if (!avail.days) avail.days = [];
      if (!avail.startDate) avail.startDate = '';

      profileData.value = {
        missions_count: user.prestataire?.missions_count || 0,
        first_name: user.first_name || '',
        last_name: user.last_name || '',
        phone: user.phone || '',
        birth_date: user.prestataire?.birth_date ? user.prestataire.birth_date.substring(0, 10) : '',
        cin: user.prestataire?.cin || '',
        address: user.address || '',
        city: user.prestataire?.city || '',
        description: user.prestataire?.description || '',
        skills: user.prestataire?.skills || '',
        experience: user.prestataire?.experience || '',
        hourly_rate: user.prestataire?.hourly_rate || '',
        category_id: user.prestataire?.category_id || (userCategories.length > 0 ? userCategories[0].id : null),
        category_ids: userCategories.map(c => c.id),
        categories: userCategories,
        availabilities: avail
      };
      
      if (mode.value !== 'edit' && mode.value !== 'create') {
          activeTab.value = 'overview';
          mode.value = 'view';
      }
      
      await Promise.all([fetchApplications(), fetchReviews()]);

    } else {
      profileExists.value = false;
      mode.value = 'view';
    }
  } catch (err) {
    error.value = "Impossible de charger le profil: " + (err.message || "Erreur inconnue");
    console.error("Profile Load Error:", err);
  } finally {
    loading.value = false;
  }
};

const toggleDay = (day) => {
    if (!profileData.value.availabilities.days) {
        profileData.value.availabilities.days = [];
    }
    const index = profileData.value.availabilities.days.indexOf(day);
    if (index === -1) {
        profileData.value.availabilities.days.push(day);
    } else {
        profileData.value.availabilities.days.splice(index, 1);
    }
};

const nextStep = () => {
    currentStep.value++;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const prevStep = () => {
    currentStep.value--;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const handleSaveProfile = async () => {
  saving.value = true;
  error.value = null;
  success.value = null;
  
  if (!profileData.value.city && citySearch.value) {
      profileData.value.city = citySearch.value;
  }

  try {
    const method = mode.value === 'create' ? 'post' : 'put';
    
    // Normalize category_ids
    const category_ids = profileData.value.category_id ? [profileData.value.category_id] : 
                        (profileData.value.category_ids.length > 0 ? profileData.value.category_ids : []);

    const payload = {
      ...profileData.value,
      category_ids: category_ids
    };

    const response = await api[method]('/api/provider/profile', payload);
    
    success.value = mode.value === 'create' ? "Profil créé avec succès !" : "Profil mis à jour !";
    await fetchProfile();
    
    mode.value = 'view';
    activeTab.value = 'profile'; 
    currentStep.value = 1;

  } catch (err) {
    error.value = "Erreur lors de l'enregistrement. Vérifiez les champs.";
    console.error(err);
  } finally {
    saving.value = false;
  }
};

const startCreate = () => {
  mode.value = 'create';
  currentStep.value = 1;
  profileData.value = { 
      ...profileData.value,
      first_name: auth.user?.first_name || auth.user?.name?.split(' ')[0] || '',
      last_name: auth.user?.last_name || auth.user?.name?.split(' ').slice(1).join(' ') || '',
      phone: auth.user?.phone || '',
      address: auth.user?.address || '',
      availabilities: { startDate: '', days: [], date: '', time: '' }
  }; 
  activeTab.value = 'profile';
};

const startEdit = () => {
  mode.value = 'edit';
  currentStep.value = 1;
  activeTab.value = 'profile';
};

const cancelEdit = () => {
  if (profileExists.value) {
    mode.value = 'view';
    activeTab.value = 'profile'; 
    fetchProfile(); 
  } else {
    mode.value = 'view';
    activeTab.value = 'overview';
  }
  currentStep.value = 1;
};

const validateAndSubmit = () => {
    handleSaveProfile();
};

onMounted(() => {
  fetchProfile();
  fetchCategories();
});

// Computed
const stats = computed(() => ({
    total: applications.value.length,
    accepted: applications.value.filter(a => a.status === 'accepted').length,
    completed: applications.value.filter(a => a.status === 'completed').length,
    rating: reviewsData.value.average_rating || 0
}));

const activeMissions = computed(() => applications.value.filter(a => ['accepted', 'completed'].includes(a.status)));
const pendingApplications = computed(() => applications.value.filter(a => a.status === 'pending'));

const currentPhotoUrl = computed(() => auth.user?.prestataire?.photo_url);
const userFullName = computed(() => `${profileData.value.first_name} ${profileData.value.last_name}`.trim() || auth.user?.name);

const currentCategoryNames = computed(() => {
    if (profileData.value.categories?.length > 0) {
        return profileData.value.categories.map(c => c.name).join(', ');
    }
    if (profileData.value.category_id) {
        const cat = categories.value.find(c => c.id === profileData.value.category_id);
        return cat ? cat.name : 'Non définie';
    }
    return 'Non définie';
});

const currentBadge = computed(() => {
    const count = profileData.value.missions_count || 0;
    if (count >= 20) return { name: 'Premium', color: 'text-purple-600', bg: 'bg-purple-50', border: 'border-purple-200', icon: Zap };
    if (count >= 5) return { name: 'Or', color: 'text-yellow-600', bg: 'bg-yellow-50', border: 'border-yellow-200', icon: Star };
    return { name: 'Bronze', color: 'text-amber-700', bg: 'bg-amber-50', border: 'border-amber-200', icon: ShieldCheck };
});

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
};

const handlePhotoUpdate = () => auth.fetchUser();

</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] font-outfit pb-20">
    <!-- Decorative Background Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-[10%] -right-[10%] w-[50%] h-[50%] bg-blue-500/5 rounded-full blur-[120px]"></div>
        <div class="absolute top-[20%] -left-[5%] w-[40%] h-[40%] bg-slate-900/5 rounded-full blur-[100px]"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 space-y-10 relative">
      <!-- Success/Error Messages -->
      <Transition name="fade">
        <div v-if="success" class="bg-emerald-500 text-white px-8 py-4 rounded-2xl font-black text-sm flex items-center shadow-2xl">
          <CheckCircle class="w-5 h-5 mr-3" />
          <span>{{ success }}</span>
        </div>
      </Transition>
      <Transition name="fade">
        <div v-if="error" class="bg-red-500 text-white px-8 py-4 rounded-2xl font-black text-sm flex items-center shadow-2xl">
          <AlertCircle class="w-5 h-5 mr-3" />
          <span>{{ error }}</span>
        </div>
      </Transition>

      <!-- Loading State -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-32 bg-white rounded-[3rem] border border-slate-100 shadow-sm relative overflow-hidden">
        <Loader2 class="w-20 h-20 text-slate-900 animate-spin mb-6" />
        <p class="text-slate-400 font-black uppercase tracking-[0.3em] text-[10px]">Chargement de votre univers...</p>
      </div>

      <!-- Main Content -->
      <div v-else>
        <!-- Mode: View (Main Dashboard) -->
        <div v-if="mode === 'view'">
            <!-- Tabs Navigation -->
            <div class="flex space-x-6 border-b border-gray-100 mb-8 overflow-x-auto scrollbar-hide pb-1">
                <button @click="activeTab = 'overview'" :class="activeTab === 'overview' ? 'text-slate-900 border-yellow-400' : 'text-slate-400 border-transparent'" class="pb-3 border-b-2 font-bold text-sm transition-colors whitespace-nowrap px-2">Vue d'ensemble</button>
                <button @click="activeTab = 'profile'; if(!profileExists) startCreate()" :class="activeTab === 'profile' ? 'text-slate-900 border-yellow-400' : 'text-slate-400 border-transparent'" class="pb-3 border-b-2 font-bold text-sm transition-colors whitespace-nowrap px-2">Mon Profil CV</button>
            </div>

            <!-- TAB: OVERVIEW -->
            <div v-if="activeTab === 'overview'" class="space-y-8 animate-in fade-in duration-500">
                <!-- Stats Grid -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 flex flex-col items-center justify-center text-center group hover:border-blue-100 transition-all">
                        <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <Briefcase class="w-6 h-6 text-blue-600" />
                        </div>
                        <span class="text-3xl font-black text-slate-900 mb-1">{{ stats.total }}</span>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Candidatures</span>
                    </div>
                    <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 flex flex-col items-center justify-center text-center group hover:border-emerald-100 transition-all">
                        <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <CheckCircle class="w-6 h-6 text-emerald-600" />
                        </div>
                        <span class="text-3xl font-black text-slate-900 mb-1">{{ stats.accepted }}</span>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Acceptées</span>
                    </div>
                    <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 flex flex-col items-center justify-center text-center group hover:border-purple-100 transition-all">
                        <div class="w-12 h-12 bg-purple-50 rounded-2xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <Award class="w-6 h-6 text-purple-600" />
                        </div>
                        <span class="text-3xl font-black text-slate-900 mb-1">{{ stats.completed }}</span>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Réalisées</span>
                    </div>
                    <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 flex flex-col items-center justify-center text-center group hover:border-amber-100 transition-all">
                        <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <Star class="w-6 h-6 text-amber-500 fill-current" />
                        </div>
                        <span class="text-3xl font-black text-slate-900 mb-1">{{ stats.rating || 'N/A' }}</span>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Note Moyenne</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Missions List -->
                    <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100 h-full">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="font-black text-lg text-slate-900">Missions & Candidatures</h3>
                        </div>
                        <div v-if="!profileExists" class="text-center py-12 border-2 border-dashed border-amber-100 bg-amber-50/20 rounded-3xl">
                             <AlertCircle class="w-12 h-12 text-amber-400 mx-auto mb-4" />
                             <h4 class="text-slate-900 font-bold mb-2">Profil non configuré</h4>
                             <p class="text-slate-500 font-medium text-sm mb-6 max-w-xs mx-auto">Veuillez configurer votre profil pour postuler.</p>
                             <button @click="startEdit" class="w-full bg-slate-900 text-white px-8 py-4 rounded-4xl font-black text-[10px] uppercase tracking-widest hover:bg-blue-600 transition-all shadow-xl">Compléter mon profil</button>
                        </div>
                        <div v-else-if="applications.length === 0" class="text-center py-12 border-2 border-dashed border-slate-100 rounded-3xl">
                            <Briefcase class="w-12 h-12 text-slate-200 mx-auto mb-4" />
                            <p class="text-slate-400 font-medium text-sm">Aucune candidature pour le moment</p>
                            <router-link to="/search" class="inline-block mt-4 text-xs font-black text-blue-600 hover:underline">Rechercher des offres</router-link>
                        </div>
                        <div v-else class="space-y-4">
                            <div v-for="app in applications.slice(0, 5)" :key="app.id" class="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between group hover:bg-white hover:shadow-md transition-all">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs uppercase">
                                        {{ app.service_request?.title?.charAt(0) || 'M' }}
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-900 text-sm mb-0.5">{{ app.service_request?.title }}</h4>
                                        <span :class="{
                                            'bg-blue-100 text-blue-700': app.status === 'pending',
                                            'bg-emerald-100 text-emerald-700': app.status === 'accepted',
                                            'bg-purple-100 text-purple-700': app.status === 'completed',
                                            'bg-red-100 text-red-700': app.status === 'rejected'
                                        }" class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">{{ app.status }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                     <span class="block text-xs font-bold text-slate-900">{{ app.service_request?.budget }} MAD</span>
                                     <span class="text-[10px] text-slate-400 font-medium">{{ formatDate(app.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reviews List -->
                    <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100 h-full">
                        <h3 class="font-black text-lg text-slate-900 mb-6 font-outfit">Derniers Avis</h3>
                        <div v-if="reviewsData.reviews.length === 0" class="text-center py-12">
                           <p class="text-slate-400 font-medium text-sm">Aucun avis reçu pour le moment</p>
                        </div>
                        <div v-else class="space-y-4">
                            <div v-for="review in reviewsData.reviews.slice(0, 3)" :key="review.id" class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                                <div class="flex items-start gap-4">
                                     <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 font-bold text-xs">
                                         {{ review.reviewer?.name?.charAt(0) }}
                                     </div>
                                     <div class="grow">
                                         <div class="flex items-center justify-between mb-1">
                                             <span class="text-xs font-bold text-slate-900">{{ review.reviewer?.name }}</span>
                                             <div class="flex text-yellow-400">
                                                 <Star v-for="i in 5" :key="i" :class="i <= review.rating ? 'fill-current' : 'text-slate-200'" class="w-3 h-3" />
                                             </div>
                                        </div>
                                        <p class="text-xs text-slate-600 italic">"{{ review.comment }}"</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: PROFILE (CV View) -->
            <div v-if="activeTab === 'profile' && profileExists" class="space-y-8 animate-in fade-in duration-700">
                <div class="bg-white rounded-[3rem] shadow-xl overflow-hidden border border-slate-100">
                    <!-- Hero Banner -->
                    <div class="h-48 bg-slate-900 relative overflow-hidden">
                        <div class="absolute inset-0 bg-linear-to-r from-blue-900 to-slate-900"></div>
                        <div class="absolute top-8 right-8">
                             <button @click="startEdit" class="bg-white/10 backdrop-blur-md text-white px-6 py-2.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-white hover:text-slate-900 transition-all border border-white/10 flex items-center gap-2 group">
                                <Edit3 class="w-4 h-4" /> <span>Modifier mon CV</span>
                            </button>
                        </div>
                    </div>

                    <div class="px-10 pb-10">
                        <div class="flex flex-col md:flex-row gap-8 items-start -mt-20 relative">
                            <!-- Photo -->
                            <div class="relative">
                                <div class="w-40 h-40 rounded-[2.5rem] bg-slate-50 border-4 border-white shadow-2xl overflow-hidden flex items-center justify-center">
                                    <img v-if="currentPhotoUrl" :src="currentPhotoUrl" class="w-full h-full object-cover">
                                    <User v-else class="w-16 h-16 text-slate-300" />
                                </div>
                                <div class="absolute -bottom-3 -right-3 p-3 rounded-xl shadow-lg border-4 border-white bg-white" :title="`Badge ${currentBadge.name}`">
                                    <component :is="currentBadge.icon" class="w-6 h-6" :class="currentBadge.color" />
                                </div>
                            </div>
                            <!-- Title info -->
                            <div class="md:pt-24 grow">
                                <h1 class="text-4xl font-black text-slate-900 mb-2">{{ userFullName }}</h1>
                                <div class="flex flex-wrap items-center gap-6 text-sm font-medium text-slate-500">
                                    <span class="flex items-center text-blue-600 font-bold">
                                        <Briefcase class="w-4 h-4 mr-2" /> {{ currentCategoryNames }}
                                    </span>
                                    <span class="flex items-center">
                                        <MapPin class="w-4 h-4 mr-2 text-slate-400" /> {{ profileData.city || 'Maroc' }}
                                    </span>
                                    <span class="flex items-center font-bold text-slate-900">
                                        <Coins class="w-4 h-4 mr-2 text-amber-500" /> {{ profileData.hourly_rate }} MAD/h
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12 mt-12 pt-12 border-t border-slate-50">
                            <!-- Left: Basic Info -->
                            <div class="space-y-8">
                                <div class="space-y-4">
                                     <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Informations</h4>
                                     <ul class="space-y-4">
                                         <li class="flex items-center justify-between text-sm py-3 border-b border-slate-50">
                                             <span class="text-slate-500 font-medium">Membre depuis</span>
                                             <span class="font-bold text-slate-900">{{ formatDate(auth.user?.created_at) }}</span>
                                         </li>
                                         <li class="flex items-center justify-between text-sm py-3 border-b border-slate-50">
                                             <span class="text-slate-500 font-medium">CIN</span>
                                             <span class="font-bold text-slate-900">{{ profileData.cin }}</span>
                                         </li>
                                         <li class="flex items-center justify-between text-sm py-3 border-b border-slate-50">
                                             <span class="text-slate-500 font-medium">Expérience</span>
                                             <span class="font-bold text-slate-900">{{ profileData.experience }} ans</span>
                                         </li>
                                     </ul>
                                </div>
                                <div class="space-y-4">
                                     <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Compétences</h4>
                                     <div class="flex flex-wrap gap-2">
                                         <span v-for="skill in (profileData.skills ? profileData.skills.split(',') : [])" :key="skill" class="px-3 py-1.5 bg-slate-50 rounded-xl text-[10px] font-black uppercase text-slate-600 border border-slate-100">
                                             {{ skill.trim() }}
                                         </span>
                                     </div>
                                </div>
                            </div>
                            <!-- Right: Bio & Experience -->
                            <div class="lg:col-span-2 space-y-10">
                                <div class="space-y-4">
                                     <h4 class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em]">Biographie</h4>
                                     <p class="text-slate-600 leading-relaxed font-medium">{{ profileData.description || 'Aucune description fournie.' }}</p>
                                </div>
                                 <div class="h-4 bg-slate-200 rounded-4xl overflow-hidden p-1 border border-slate-100">
                                     <div class="h-full bg-linear-to-r from-blue-600 via-blue-500 to-blue-400 rounded-xl" :style="{ width: profileCompletion + '%' }"></div>
                                 </div>
                                <div class="space-y-6 pt-10 border-t border-slate-50">
                                     <h4 class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em]">Disponibilités hebdomadaires</h4>
                                     <div class="grid grid-cols-7 gap-3">
                                         <div v-for="day in weekDays" :key="day.value" 
                                              class="flex flex-col items-center gap-2 p-3 rounded-2xl border"
                                              :class="profileData.availabilities.days?.includes(day.value) ? 'bg-slate-900 border-slate-900 text-white shadow-lg' : 'bg-slate-50 border-transparent text-slate-300'"
                                         >
                                             <span class="text-[9px] font-black uppercase tracking-wider">{{ day.label }}</span>
                                             <div class="w-1.5 h-1.5 rounded-full" :class="profileData.availabilities.days?.includes(day.value) ? 'bg-yellow-400' : 'bg-slate-200'"></div>
                                         </div>
                                     </div>
                                     <p class="text-xs text-slate-400 font-medium">Prend effet le : {{ profileData.availabilities.startDate || 'Immédiatement' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: No Profile State -->
            <div v-else-if="activeTab === 'profile' && !profileExists" class="text-center py-24 bg-white rounded-[3rem] border border-slate-100 shadow-xl">
                 <div class="w-24 h-24 bg-slate-50 rounded-[2rem] flex items-center justify-center mx-auto mb-8">
                     <Fingerprint class="w-12 h-12 text-slate-300" />
                 </div>
                 <h2 class="text-2xl font-black text-slate-900 mb-4">Profil non créé</h2>
                 <p class="text-slate-500 mb-8 max-w-sm mx-auto font-medium">Configurez votre CV pour être visible par les clients et postuler aux missions.</p>
                 <button @click="startCreate" class="bg-slate-900 text-white px-10 py-5 rounded-4xl font-black text-xs uppercase tracking-widest hover:bg-yellow-400 hover:text-slate-900 transition-all">Créer mon profil</button>
            </div>
        </div>

        <!-- Mode: Create / Edit (Wizard) -->
        <div v-else class="max-w-4xl mx-auto py-10">
            <!-- Wizard Header -->
            <div class="mb-12">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-3xl font-black text-slate-900 tracking-tight">{{ mode === 'create' ? 'Bienvenue à bord' : 'Mettre à jour mon profil' }}</h2>
                    <span class="px-4 py-2 bg-slate-100 rounded-2xl text-xs font-black uppercase tracking-widest text-slate-500">Étape {{ currentStep }} / 3</span>
                </div>
                <!-- Progress Line -->
                <div class="flex gap-3 h-2">
                    <div :class="currentStep >= 1 ? 'bg-slate-900' : 'bg-slate-100'" class="flex-1 rounded-full transition-colors duration-500"></div>
                    <div :class="currentStep >= 2 ? 'bg-slate-900' : 'bg-slate-100'" class="flex-1 rounded-full transition-colors duration-500"></div>
                    <div :class="currentStep >= 3 ? 'bg-slate-900' : 'bg-slate-100'" class="flex-1 rounded-full transition-colors duration-500"></div>
                </div>
            </div>

            <div class="bg-white rounded-[3.5rem] p-10 md:p-16 shadow-2xl border border-slate-100 relative overflow-hidden">
                <!-- STEP 1: IDENTITY -->
                <div v-if="currentStep === 1" class="space-y-10 animate-in slide-in-from-right-10 duration-500">
                    <div class="flex flex-col items-center justify-center mb-4">
                        <PhotoUploader :current-photo="currentPhotoUrl" @photo-updated="handlePhotoUpdate" />
                        <p class="text-xs text-slate-400 mt-6 font-bold uppercase tracking-widest">Portrait Professionnel</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Prénom d'usage</label>
                            <input v-model="profileData.first_name" type="text" class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-[2rem] focus:bg-white focus:ring-8 focus:ring-blue-500/5 focus:border-blue-500 outline-none transition-all font-bold text-slate-700">
                        </div>
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Nom de famille</label>
                            <input v-model="profileData.last_name" type="text" class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-[2rem] focus:bg-white focus:ring-8 focus:ring-blue-500/5 focus:border-blue-500 outline-none transition-all font-bold text-slate-700">
                        </div>
                        <div class="space-y-3 relative">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Ville de résidence</label>
                            <div class="relative">
                                <input v-model="citySearch" type="text" :placeholder="profileData.city || 'Rechercher votre ville...'" class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-[2rem] focus:bg-white outline-none transition-all font-bold text-slate-700">
                                <MapPin class="absolute right-6 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300" />
                            </div>
                            <!-- Suggestions -->
                            <div v-if="filteredCities.length > 0" class="absolute z-50 w-full mt-2 bg-white border border-slate-100 rounded-3xl shadow-2xl max-h-48 overflow-y-auto overflow-x-hidden p-2">
                                <button v-for="city in filteredCities" :key="city" @click="selectCity(city)" type="button" class="w-full text-left px-6 py-4 text-sm hover:bg-slate-50 rounded-2xl font-bold text-slate-700 transition-colors">
                                    {{ city }}
                                </button>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Contact Téléphonique</label>
                            <input v-model="profileData.phone" type="tel" class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-[2rem] focus:bg-white outline-none transition-all font-bold text-slate-700">
                        </div>
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Date de naissance</label>
                            <input v-model="profileData.birth_date" type="date" class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-[2rem] focus:bg-white outline-none transition-all font-bold text-slate-700">
                        </div>
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">N° Carte d'identité (CIN)</label>
                            <input v-model="profileData.cin" type="text" class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-[2rem] focus:bg-white outline-none transition-all font-bold text-slate-700">
                        </div>
                    </div>
                </div>

                <!-- STEP 2: PROFESSIONAL -->
                <div v-if="currentStep === 2" class="space-y-10 animate-in slide-in-from-right-10 duration-500">
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Secteur d'activité principal</label>
                        <div class="relative">
                            <select v-model="profileData.category_id" class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-[2rem] focus:bg-white outline-none transition-all font-black text-slate-700 appearance-none">
                                <option :value="null" disabled>Sélectionner votre métier</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                            <ChevronDown class="absolute right-6 top-1/2 -translate-y-1/2 w-6 h-6 text-slate-400 pointer-events-none" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Expérience (Années)</label>
                            <input v-model="profileData.experience" type="number" class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-[2rem] focus:bg-white outline-none transition-all font-bold text-slate-700">
                        </div>
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Tarif Horaire SOUHAITÉ (MAD)</label>
                            <div class="relative">
                                <input v-model="profileData.hourly_rate" type="number" class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-[2rem] focus:bg-white outline-none transition-all font-bold text-slate-700 pl-16">
                                <Coins class="absolute left-6 top-1/2 -translate-y-1/2 w-6 h-6 text-slate-300" />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Présentation Professionnelle</label>
                        <textarea v-model="profileData.description" rows="5" class="w-full px-8 py-6 bg-slate-50 border border-slate-100 rounded-[2.5rem] focus:bg-white outline-none transition-all font-medium text-slate-700 resize-none leading-relaxed" placeholder="Décrivez votre parcours, vos points forts et les services que vous proposez..."></textarea>
                    </div>

                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Hard/Soft Skills (Séparés par virgules)</label>
                        <input v-model="profileData.skills" type="text" class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-[2rem] focus:bg-white outline-none transition-all font-bold text-slate-700" placeholder="Ex: Peinture, Menuiserie, Ponctualité...">
                    </div>
                </div>

                <!-- STEP 3: AVAILABILITY -->
                <div v-if="currentStep === 3" class="space-y-10 animate-in slide-in-from-right-10 duration-500">
                    <div class="space-y-8">
                        <div class="bg-blue-50/50 rounded-3xl p-8 border border-blue-100">
                            <h4 class="font-black text-blue-900 mb-6 flex items-center tracking-tight">
                                <Calendar class="w-6 h-6 mr-3" /> Agenda Hebdomadaire
                            </h4>
                            
                            <div class="space-y-8">
                                <div class="space-y-3">
                                    <label class="text-[10px] font-black text-blue-400 uppercase tracking-[0.2em] ml-2">Disponible à partir du</label>
                                    <input v-model="profileData.availabilities.startDate" type="date" class="w-full bg-white border-0 rounded-2xl px-6 py-4 shadow-sm font-bold text-slate-700 focus:ring-4 focus:ring-blue-100 outline-none transition-all">
                                </div>

                                <div class="space-y-4">
                                    <label class="text-[10px] font-black text-blue-400 uppercase tracking-[0.2em] ml-2">Jours d'intervention</label>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <button 
                                            v-for="day in weekDays" 
                                            :key="day.value"
                                            @click="toggleDay(day.value)"
                                            type="button"
                                            class="px-6 py-4 rounded-[1.5rem] font-black text-xs uppercase tracking-widest transition-all border-2"
                                            :class="profileData.availabilities.days?.includes(day.value) ? 'bg-slate-900 text-white border-slate-900 shadow-xl' : 'bg-white text-slate-400 border-slate-50 hover:border-slate-200'"
                                        >
                                            {{ day.label }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between mt-12 pt-10 border-t border-slate-50">
                    <button v-if="currentStep > 1" @click="prevStep" type="button" class="px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest text-slate-400 hover:text-slate-900 transition-colors">Retour</button>
                    <button v-else @click="cancelEdit" type="button" class="px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest text-slate-400 hover:text-red-500 transition-colors">Annuler</button>

                    <button v-if="currentStep < 3" @click="nextStep" type="button" class="bg-slate-900 text-white px-10 py-5 rounded-[2rem] font-black text-[10px] uppercase tracking-[0.3em] hover:bg-blue-600 transition-all shadow-2xl flex items-center space-x-3">
                        <span>Suivant</span>
                        <ArrowRight class="w-5 h-5" />
                    </button>
                    <button v-else @click="validateAndSubmit" :disabled="saving" class="bg-emerald-500 text-white px-12 py-5 rounded-[2rem] font-black text-[10px] uppercase tracking-[0.3em] hover:bg-emerald-600 transition-all shadow-2xl flex items-center space-x-3 disabled:opacity-50">
                        <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
                        <Save v-else class="w-5 h-5" />
                        <span>{{ mode === 'create' ? 'Terminer & Publier' : 'Mettre à jour' }}</span>
                    </button>
                </div>
            </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.font-outfit { font-family: 'Outfit', sans-serif; }
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }

.fade-enter-active, .fade-leave-active { transition: opacity 0.5s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-in { animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
</style>
