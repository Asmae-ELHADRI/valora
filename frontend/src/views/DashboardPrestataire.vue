<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import { 
  User, Mail, Phone, MapPin, Briefcase, Calendar, Clock, 
  Camera, CheckCircle, AlertCircle, Loader2, Edit3, Save, X, Fingerprint, Coins, Plus,
  FileText, Star, TrendingUp, ShieldCheck, Award, Search, ArrowRight
} from 'lucide-vue-next';
import PhotoUploader from '../components/PhotoUploader.vue';

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

import { MOROCCAN_CITIES } from '../constants/cities';
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
  availabilities: {
    startDate: '',
    days: []
  }
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
      
      // Parse availability
      let avail = user.prestataire?.availabilities;
      
      if (typeof avail === 'string') {
          try { 
              avail = JSON.parse(avail); 
          } catch (e) { 
              console.warn('Failed to parse availabilities JSON', e);
          }
      }
      
      // Normalize: Handle null (from DB or JSON.parse), non-objects, or arrays
      if (!avail || typeof avail !== 'object') {
          avail = { startDate: '', days: [] };
      } else if (Array.isArray(avail)) {
          avail = { startDate: '', days: avail };
      }
      
      // Ensure structure properties
      if (!avail.days) avail.days = [];
      if (!avail.startDate) avail.startDate = '';

      profileData.value = {
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
        category_id: user.prestataire?.category_id || null,
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
        // Add
        profileData.value.availabilities.days.push(day);
    } else {
        // Remove
        profileData.value.availabilities.days.splice(index, 1);
    }
};

const nextStep = () => {
    if (currentStep.value === 1) {
        if (!profileData.value.first_name || !profileData.value.last_name || !profileData.value.city) {
            // Simple validation could be better
            // alert('Veuillez remplir les informations obligatoires');
            // Allow proceed for now to not block, but ideally show validation errors
        }
    }
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
    
    // Send data. Adjust availabilities to be sent as is (array/object handled by backend cast?)
    // The backend casts 'availabilities' => 'array', so sending a JSON object is fine.
    
    const payload = {
      ...profileData.value,
      category_ids: profileData.value.category_id ? [profileData.value.category_id] : []
    };

    const response = await api[method]('/api/provider/profile', payload);
    
    success.value = mode.value === 'create' ? "Profil créé avec succès !" : "Profil mis à jour !";
    await fetchProfile();
    
    if (mode.value === 'create') {
        mode.value = 'view';
        activeTab.value = 'profile'; // Show the new CV
    } else {
        mode.value = 'view';
        activeTab.value = 'profile';
    }
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
      availabilities: { startDate: '', days: [] }
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

onMounted(() => {
  fetchProfile();
  fetchCategories();
});

const currentCategoryName = computed(() => {
  const cat = categories.value.find(c => c.id === profileData.value.category_id);
  return cat ? cat.name : 'Non définie';
});

const handlePhotoUpdate = (newUrl) => {
    auth.fetchUser();
};

const currentPhotoUrl = computed(() => {
    return auth.user?.prestataire?.photo_url;
});

const userFullName = computed(() => {
  return `${profileData.value.first_name} ${profileData.value.last_name}`.trim() || auth.user?.name;
});

// --- Dashboard Computed Properties ---
const stats = computed(() => {
  return {
    total: applications.value.length,
    accepted: applications.value.filter(a => a.status === 'accepted').length,
    completed: applications.value.filter(a => a.status === 'completed').length,
  };
});

const activeMissions = computed(() => {
  return applications.value.filter(a => ['accepted', 'completed'].includes(a.status));
});

const pendingApplications = computed(() => {
  return applications.value.filter(a => a.status === 'pending');
});

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  });
};
</script>

<template>
  <div class="min-h-screen bg-slate-50 font-outfit p-4 md:p-8">
    <div class="max-w-7xl mx-auto space-y-8">
      
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-black text-slate-900 tracking-tight">Bonjour, {{ auth.user?.first_name || auth.user?.name }}</h1>
          <p class="text-slate-500 font-medium">Gérez votre profil et vos activités sur Valora</p>
        </div>
        <div class="flex items-center gap-4">
            <div v-if="success" class="bg-emerald-50 text-emerald-700 px-6 py-3 rounded-2xl border border-emerald-100 flex items-center animate-fade-in shadow-sm">
                <CheckCircle class="w-5 h-5 mr-2" />
                <span class="text-sm font-bold">{{ success }}</span>
            </div>
            
            <!-- User Menu / Photo -->
            <div v-if="profileExists" class="w-12 h-12 rounded-full bg-white shadow-lg border-2 border-white overflow-hidden">
                 <img v-if="currentPhotoUrl" :src="currentPhotoUrl" class="w-full h-full object-cover">
                 <User v-else class="w-full h-full p-2 text-slate-300" />
            </div>
        </div>
      </div>

      <!-- Loading State -->
    <div v-if="loading" class="flex flex-col items-center justify-center min-h-[60vh]">
      <Loader2 class="w-12 h-12 text-premium-blue animate-spin mb-4" />
      <p class="text-slate-400 font-medium">Chargement de votre espace...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="flex flex-col items-center justify-center min-h-[60vh] text-center">
      <AlertCircle class="w-16 h-16 text-red-400 mb-6" />
      <h3 class="text-xl font-bold text-slate-800 mb-2">Une erreur est survenue</h3>
      <p class="text-slate-500 mb-8">{{ error }}</p>
      <button @click="fetchProfile" class="px-6 py-3 bg-slate-900 text-white rounded-xl font-bold hover:bg-slate-800 transition-colors">
        Réessayer
      </button>
    </div>

    <!-- Main Content -->
    <div v-else>
      <!-- Tabs Navigation -->
      <div v-if="mode === 'view'" class="flex space-x-6 border-b border-gray-100 mb-8 overflow-x-auto scrollbar-hide pb-1">
          <button @click="activeTab = 'overview'" :class="activeTab === 'overview' ? 'text-slate-900 border-yellow-400' : 'text-slate-400 border-transparent'" class="pb-3 border-b-2 font-bold text-sm transition-colors whitespace-nowrap px-2">Vue d'ensemble</button>
          <button @click="activeTab = 'profile'; if(!profileExists) startCreate()" :class="activeTab === 'profile' ? 'text-slate-900 border-yellow-400' : 'text-slate-400 border-transparent'" class="pb-3 border-b-2 font-bold text-sm transition-colors whitespace-nowrap px-2">Mon Profil CV</button>
      </div>

      <!-- TAB: OVERVIEW -->
      <div v-show="activeTab === 'overview' && mode === 'view'" class="animate-fade-in">
        <!-- Stats Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 flex flex-col items-center justify-center text-center group hover:border-blue-100 transition-colors">
                <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <Briefcase class="w-6 h-6 text-blue-600" />
                </div>
                <span class="text-3xl font-black text-slate-900 mb-1">{{ stats.total }}</span>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Candidatures</span>
            </div>
            
            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 flex flex-col items-center justify-center text-center group hover:border-emerald-100 transition-colors">
                <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <CheckCircle class="w-6 h-6 text-emerald-600" />
                </div>
                <span class="text-3xl font-black text-slate-900 mb-1">{{ stats.accepted }}</span>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Acceptées</span>
            </div>

            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 flex flex-col items-center justify-center text-center group hover:border-purple-100 transition-colors">
                <div class="w-12 h-12 bg-purple-50 rounded-2xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <Award class="w-6 h-6 text-purple-600" />
                </div>
                <span class="text-3xl font-black text-slate-900 mb-1">{{ stats.completed }}</span>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Réalisées</span>
            </div>

            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 flex flex-col items-center justify-center text-center group hover:border-amber-100 transition-colors">
                <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <Star class="w-6 h-6 text-amber-500" />
                </div>
                <span class="text-3xl font-black text-slate-900 mb-1">{{ reviewsData.average_rating || '-' }}</span>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Note Moyenne</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Active Missions / Pending -->
            <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100 h-full">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-black text-lg text-slate-900">Missions & Candidatures</h3>
                    <span class="text-xs font-bold text-slate-400 bg-slate-50 px-3 py-1 rounded-full">{{ activeMissions.length + pendingApplications.length }} Total</span>
                </div>

                <!-- NO PROFILE STATE CTA -->
                <div v-if="!profileExists" class="text-center py-12 border-2 border-dashed border-amber-100 bg-amber-50/20 rounded-3xl">
                    <AlertCircle class="w-12 h-12 text-amber-400 mx-auto mb-4" />
                    <h4 class="text-slate-900 font-bold mb-2">Profil non configuré</h4>
                    <p class="text-slate-500 font-medium text-sm mb-6 max-w-xs mx-auto">
                        Pour postuler aux offres et recevoir des missions, vous devez d'abord créer votre profil prestataire.
                    </p>
                    <button @click="startCreate" class="bg-slate-900 text-white px-6 py-3 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-premium-blue transition-colors shadow-lg shadow-slate-900/10">
                        Créer mon profil CV
                    </button>
                </div>

                <div v-else-if="activeMissions.length === 0 && pendingApplications.length === 0" class="text-center py-12 border-2 border-dashed border-slate-100 rounded-3xl">
                    <Briefcase class="w-12 h-12 text-slate-200 mx-auto mb-4" />
                    <p class="text-slate-400 font-medium text-sm">Aucune activité pour le moment</p>
                    <router-link to="/search" class="inline-block mt-4 text-xs font-black text-premium-blue hover:underline">RECHERCHER DES OFFRES</router-link>
                </div>

                <div v-else class="space-y-4">
                     <!-- List items code remains similar... -->
                     <div v-for="app in pendingApplications" :key="app.id" class="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between group hover:bg-white hover:shadow-md transition-all">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs">
                                {{ app.service_request?.title?.charAt(0) || 'M' }}
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 text-sm mb-0.5">{{ app.service_request?.title }}</h4>
                                <span class="bg-blue-100 text-blue-700 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">En attente</span>
                            </div>
                        </div>
                        <div class="text-right">
                             <span class="block text-xs font-bold text-slate-900">{{ app.service_request?.budget }} MAD</span>
                             <span class="text-[10px] text-slate-400 font-medium">{{ formatDate(app.created_at) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Reviews -->
            <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100 h-full">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-black text-lg text-slate-900">Derniers Avis</h3>
                    <div class="flex text-yellow-400 text-xs font-bold bg-yellow-50 px-2 py-1 rounded-lg">
                        <Star class="w-3 h-3 fill-current mr-1" />
                        {{ reviewsData.average_rating }}
                    </div>
                </div>

                <div v-if="reviewsData.reviews.length === 0" class="text-center py-12">
                   <p class="text-slate-400 font-medium text-sm">Aucun avis reçu pour le moment</p>
                </div>

                <div v-else class="space-y-4">
                    <div v-for="review in reviewsData.reviews.slice(0, 3)" :key="review.id" class="p-4 rounded-2xl bg-white border border-slate-50 shadow-sm">
                        <div class="flex items-start gap-3">
                             <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 font-bold text-xs">
                                 {{ review.reviewer?.name?.charAt(0) }}
                             </div>
                             <div>
                                 <div class="flex items-center justify-between w-full mb-1">
                                     <span class="text-xs font-bold text-slate-700">{{ review.reviewer?.name }}</span>
                                     <div class="flex text-yellow-400">
                                         <Star v-for="i in 5" :key="i" :class="i <= review.rating ? 'fill-current' : 'text-slate-200'" class="w-3 h-3" />
                                     </div>
                                </div>
                                <p class="text-xs text-slate-500 italic">"{{ review.comment }}"</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
      </div>

      <!-- TAB: CV PROFILE & WIZARD -->
      <div v-show="activeTab === 'profile' || mode !== 'view'" class="animate-fade-in">
        
        <!-- MODE: WIZARD (Create / Edit) -->
        <div v-if="mode === 'create' || mode === 'edit'" class="max-w-4xl mx-auto">
            <!-- Wizard Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-black text-slate-900">{{ mode === 'create' ? 'Création de votre Profil' : 'Édition du Profil' }}</h2>
                    <span class="text-xs font-bold text-slate-400 bg-slate-100 px-3 py-1 rounded-full">Étape {{ currentStep }} / 3</span>
                </div>
                <!-- Progress Bar -->
                <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden flex">
                    <div :class="currentStep >= 1 ? 'bg-slate-900' : 'bg-transparent'" class="h-full flex-1 transition-all duration-500 border-r border-white/20"></div>
                    <div :class="currentStep >= 2 ? 'bg-slate-900' : 'bg-transparent'" class="h-full flex-1 transition-all duration-500 border-r border-white/20"></div>
                    <div :class="currentStep >= 3 ? 'bg-slate-900' : 'bg-transparent'" class="h-full flex-1 transition-all duration-500"></div>
                </div>
                <div class="flex justify-between text-[10px] font-black uppercase tracking-widest text-slate-400 mt-2">
                    <span :class="currentStep >= 1 ? 'text-slate-900' : ''">Identité</span>
                    <span :class="currentStep >= 2 ? 'text-slate-900' : ''">Expertise</span>
                    <span :class="currentStep >= 3 ? 'text-slate-900' : ''">Disponibilité</span>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] p-8 md:p-12 shadow-2xl border border-slate-100 relative overflow-hidden">
                <!-- STEP 1: IDENTITY -->
                <div v-if="currentStep === 1" class="space-y-8 animate-fade-in">
                    <div class="flex flex-col items-center justify-center mb-8">
                        <PhotoUploader :current-photo="currentPhotoUrl" @photo-updated="handlePhotoUpdate" />
                        <p class="text-xs text-slate-400 mt-4 font-medium">Une photo professionnelle rassure les clients</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Prénom</label>
                            <input v-model="profileData.first_name" type="text" class="input-field" placeholder="Votre prénom">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Nom</label>
                            <input v-model="profileData.last_name" type="text" class="input-field" placeholder="Votre nom">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Téléphone</label>
                            <input v-model="profileData.phone" type="tel" class="input-field" placeholder="06...">
                        </div>
                         <!-- City Selection -->
                        <div class="space-y-2 relative">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Ville</label>
                            <div class="relative">
                                <input v-model="citySearch" type="text" :placeholder="profileData.city || 'Rechercher...'" class="input-field pl-10">
                                <MapPin class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            </div>
                            <!-- Suggestions -->
                            <div v-if="filteredCities.length > 0" class="absolute z-20 w-full mt-1 bg-white border border-slate-100 rounded-xl shadow-xl max-h-48 overflow-y-auto">
                                <button v-for="city in filteredCities" :key="city" @click="selectCity(city)" type="button" class="w-full text-left px-4 py-3 text-sm hover:bg-slate-50 font-medium">
                                    {{ city }}
                                </button>
                            </div>
                        </div>
                         <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Date de naissance</label>
                            <input v-model="profileData.birth_date" type="date" class="input-field">
                        </div>
                         <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">CIN</label>
                            <input v-model="profileData.cin" type="text" class="input-field">
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Bio / Présentation</label>
                        <textarea v-model="profileData.description" rows="4" class="input-field resize-none" placeholder="Décrivez votre parcours et vos services en quelques mots..."></textarea>
                    </div>
                </div>

                <!-- STEP 2: PROFESSIONAL -->
                <div v-if="currentStep === 2" class="space-y-8 animate-fade-in">
                     <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Catégorie Principale</label>
                        <select v-model="profileData.category_id" class="input-field appearance-none">
                            <option :value="null" disabled>Sélectionner votre métier</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                         <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Années d'expérience</label>
                            <input v-model="profileData.experience" type="number" class="input-field">
                        </div>
                         <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Tarif Horaire (MAD)</label>
                            <div class="relative">
                                <input v-model="profileData.hourly_rate" type="number" class="input-field pl-10">
                                <Coins class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Compétences Clés</label>
                        <input v-model="profileData.skills" type="text" class="input-field" placeholder="Ex: Peinture, Enduit, Pose parquet (séparés par virgules)">
                        <p class="text-[10px] text-slate-400 pl-4">Séparez les compétences par des virgules</p>
                    </div>
                </div>

                <!-- STEP 3: AVAILABILITY -->
                <div v-if="currentStep === 3" class="space-y-8 animate-fade-in">
                    <div class="bg-blue-50/50 rounded-2xl p-6 border border-blue-100">
                        <h4 class="font-bold text-blue-900 mb-4 flex items-center">
                            <Calendar class="w-5 h-5 mr-2" />
                            Configuration des disponibilités
                        </h4>
                        
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-1">Date de début de disponibilité</label>
                                <input v-model="profileData.availabilities.startDate" type="date" class="bg-white border-0 rounded-xl px-4 py-3 shadow-sm w-full font-bold text-slate-700 focus:ring-2 focus:ring-blue-200">
                            </div>

                            <div class="space-y-4">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-1">Jours de la semaine disponibles</label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <button 
                                        v-for="day in weekDays" 
                                        :key="day.value"
                                        @click="toggleDay(day.value)"
                                        type="button"
                                        class="px-4 py-3 rounded-xl text-sm font-bold transition-all border-2"
                                        :class="profileData.availabilities.days.includes(day.value) ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-500 border-slate-100 hover:border-slate-300'"
                                    >
                                        {{ day.label }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Wizard Actions -->
                <div class="flex items-center justify-between pt-8 border-t border-slate-100 mt-8">
                    <button v-if="currentStep > 1" @click="prevStep" type="button" class="px-6 py-3 rounded-xl font-bold text-slate-500 hover:bg-slate-50 transition-colors">
                        Retour
                    </button>
                    <button v-else @click="cancelEdit" type="button" class="px-6 py-3 rounded-xl font-bold text-slate-500 hover:bg-slate-50 transition-colors">
                        Annuler
                    </button>

                    <button v-if="currentStep < 3" @click="nextStep" type="button" class="bg-slate-900 text-white px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-premium-blue transition-colors flex items-center">
                        Suivant <ArrowRight class="w-4 h-4 ml-2" />
                    </button>
                    <button v-else @click="handleSaveProfile" :disabled="saving" class="bg-green-600 text-white px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-green-700 transition-colors flex items-center shadow-lg shadow-green-600/20">
                        <Loader2 v-if="saving" class="w-4 h-4 mr-2 animate-spin" />
                        {{ mode === 'create' ? 'Terminer & Publier' : 'Enregistrer' }}
                    </button>
                </div>
            </div>
        </div>

      <!-- Mode Vue: CV Premium -->
      <div v-if="mode === 'view'" class="space-y-8 animate-fade-in pb-12">
          
        <!-- Top Profile Card -->
        <div class="bg-white rounded-[2.5rem] overflow-hidden shadow-xl shadow-slate-200/50 border border-slate-100 relative group transition-all hover:shadow-2xl">
            <!-- Cover / Banner -->
            <div class="h-64 bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 relative overflow-hidden">
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10 mix-blend-overlay"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 to-transparent"></div>
                
                <!-- Floating Action Button -->
                <div class="absolute bottom-6 right-6 z-20">
                     <button @click="startEdit" class="bg-white/10 hover:bg-white/20 backdrop-blur-xl text-white px-6 py-3 rounded-2xl font-bold text-sm transition-all flex items-center gap-3 border border-white/20 shadow-lg group">
                        <Edit3 class="w-4 h-4 group-hover:rotate-12 transition-transform" />
                        <span>Modifier mon profil</span>
                    </button>
                </div>
            </div>

            <div class="px-8 md:px-12 pb-10">
                <div class="flex flex-col md:flex-row gap-8 relative">
                    <!-- Avatar Area -->
                    <div class="-mt-24 relative z-10 shrink-0">
                        <div class="w-40 h-40 md:w-48 md:h-48 rounded-[2rem] bg-white p-2 shadow-2xl rotate-3 group-hover:rotate-1 transition-transform duration-500 ease-out-back">
                            <img 
                                :src="currentPhotoUrl || 'https://ui-avatars.com/api/?name=' + userFullName + '&background=0F172A&color=fff'" 
                                class="w-full h-full object-cover rounded-[1.5rem] bg-slate-100 border border-slate-100"
                                alt="Profile"
                            />
                            <div class="absolute -bottom-3 -right-3 bg-yellow-400 text-slate-900 p-3 rounded-2xl shadow-lg border-4 border-white" v-if="profileData.verified || true">
                                <Award class="w-6 h-6" />
                            </div>
                        </div>
                    </div>

                    <!-- Header Info -->
                    <div class="pt-6 flex-1 min-w-0">
                        <div class="flex flex-col justify-end h-full">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="px-4 py-1.5 bg-blue-50 text-blue-700 text-xs font-black uppercase tracking-widest rounded-full border border-blue-100 shadow-sm">
                                    {{ currentCategoryName }}
                                </span>
                                <div class="flex items-center gap-1 bg-amber-50 text-amber-600 px-3 py-1.5 rounded-full text-xs font-bold border border-amber-100">
                                    <Star class="w-3.5 h-3.5 fill-current" />
                                    <span>{{ reviewsData.average_rating || 'Nouveau' }}</span>
                                </div>
                            </div>
                            
                            <h1 class="text-4xl md:text-5xl font-black text-slate-900 tracking-tight mb-4 truncate">{{ userFullName }}</h1>
                            
                            <div class="flex flex-wrap items-center gap-6 text-slate-500 font-medium">
                                <div class="flex items-center gap-2 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100">
                                    <MapPin class="w-4 h-4 text-slate-400" />
                                    {{ profileData.city || 'Maroc' }}
                                </div>
                                <div class="flex items-center gap-2 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100">
                                    <Briefcase class="w-4 h-4 text-slate-400" />
                                    {{ profileData.experience || '0' }} ans d'expérience
                                </div>
                                <a v-if="profileData.phone" :href="'tel:'+profileData.phone" class="flex items-center gap-2 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100 hover:bg-slate-100 transition-colors">
                                    <Phone class="w-4 h-4 text-slate-400" />
                                    <span class="text-slate-900">{{ profileData.phone }}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            
            <!-- Left Column: Details -->
            <div class="space-y-6 xl:col-span-1">
                
                <!-- Skills Card -->
                <div class="bg-white rounded-[2rem] p-8 shadow-lg shadow-slate-200/50 border border-slate-100 h-auto">
                    <h3 class="font-bold text-slate-900 mb-6 flex items-center gap-3 text-lg">
                        <div class="p-2.5 bg-blue-100 text-blue-600 rounded-xl">
                            <Star class="w-5 h-5" />
                        </div>
                        Compétences
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="skill in (profileData.skills ? profileData.skills.split(',') : [])" :key="skill" 
                              class="px-4 py-2 bg-slate-50 text-slate-700 text-sm font-bold rounded-xl border border-slate-200 hover:border-blue-300 hover:bg-blue-50 transition-all cursor-default">
                            {{ skill.trim() }}
                        </span>
                        <div v-if="!profileData.skills" class="w-full py-8 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200">
                            <p class="text-slate-400 text-sm font-medium">Ajoutez vos compétences</p>
                        </div>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="bg-white rounded-[2rem] p-8 shadow-lg shadow-slate-200/50 border border-slate-100">
                    <h3 class="font-bold text-slate-900 mb-6 flex items-center gap-3 text-lg">
                        <div class="p-2.5 bg-purple-100 text-purple-600 rounded-xl">
                            <Fingerprint class="w-5 h-5" />
                        </div>
                        Informations
                    </h3>
                    <ul class="space-y-5">
                        <li class="flex items-center justify-between">
                            <span class="text-slate-500 text-sm font-medium">Âge</span>
                            <span class="font-bold text-slate-900">{{ profileData.birth_date ? new Date().getFullYear() - new Date(profileData.birth_date).getFullYear() + ' ans' : '-' }}</span>
                        </li>
                        <li class="flex items-center justify-between">
                            <span class="text-slate-500 text-sm font-medium">Ville</span>
                            <span class="font-bold text-slate-900">{{ profileData.city || '-' }}</span>
                        </li>
                        <li class="flex items-center justify-between">
                            <span class="text-slate-500 text-sm font-medium">Membre depuis</span>
                            <span class="font-bold text-slate-900">{{ formatDate(auth.user?.created_at) }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Middle Column: Bio -->
            <div class="xl:col-span-2 space-y-6">
                <div class="bg-white rounded-[2rem] p-8 md:p-10 shadow-lg shadow-slate-200/50 border border-slate-100 h-full">
                    <h3 class="font-bold text-slate-900 mb-6 flex items-center gap-3 text-lg">
                        <div class="p-2.5 bg-yellow-100 text-yellow-600 rounded-xl">
                            <FileText class="w-5 h-5" />
                        </div>
                        À propos
                    </h3>
                    <div class="prose prose-slate prose-lg max-w-none">
                        <p v-if="profileData.description" class="leading-relaxed text-slate-600">
                            {{ profileData.description }}
                        </p>
                        <div v-else class="flex flex-col items-center justify-center py-12 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-100 text-center">
                            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4 text-slate-300">
                                <Edit3 class="w-8 h-8" />
                            </div>
                            <p class="text-slate-500 font-medium mb-2">Votre bio est vide</p>
                            <p class="text-slate-400 text-sm max-w-xs mx-auto mb-6">Rédigez une description captivante pour attirer plus de clients.</p>
                            <button @click="startEdit" class="text-blue-600 font-bold text-sm hover:underline">Modifier mon profil</button>
                        </div>
                    </div>
                    
                    <div class="mt-10 pt-8 border-t border-slate-100">
                        <h4 class="font-bold text-slate-900 mb-6 uppercase text-xs tracking-widest">Disponibilités & Tarifs</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                             <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-6 text-white relative overflow-hidden group">
                                <div class="absolute top-0 right-0 p-4 opacity-10 transform translate-x-1/2 -translate-y-1/2">
                                     <Coins class="w-32 h-32" />
                                </div>
                                <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-1">Tarif Horaire</p>
                                <div class="text-4xl font-black mb-1 group-hover:scale-105 transition-transform origin-left">
                                    {{ profileData.hourly_rate || '0' }} <span class="text-lg font-medium text-slate-400">MAD</span>
                                </div>
                            </div>
                            
                            <div class="bg-white border border-slate-200 rounded-2xl p-6 relative overflow-hidden">
                                <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-3">Jours Disponibles</p>
                                <div class="flex flex-wrap gap-2">
                                     <span v-if="!profileData.availabilities?.days?.length" class="text-slate-400 text-sm font-medium italic">Non spécifié</span>
                                     <span v-for="day in ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']" :key="day"
                                        :class="[
                                            'w-10 h-10 flex items-center justify-center rounded-lg text-xs font-bold border transition-all',
                                            (profileData.availabilities?.days || []).some(d => d.toLowerCase().startsWith(day.toLowerCase()))
                                                ? 'bg-emerald-500 text-white border-emerald-500 shadow-md shadow-emerald-200'
                                                : 'bg-slate-50 text-slate-300 border-transparent'
                                        ]">
                                        {{ day.slice(0, 1) }}
                                    </span>
                                </div>
                                <div class="mt-4 flex items-center gap-2 text-xs font-medium text-slate-500">
                                    <Clock class="w-3 h-3" />
                                    Début : {{ profileData.availabilities?.startDate ? formatDate(profileData.availabilities.startDate) : 'Immédiat' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

      </div>
    </div>

    </div>
  </div>
  </div>
</template>

<style scoped>
.font-outfit { font-family: 'Outfit', sans-serif; }

.animate-fade-in {
  animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes spin-slow {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.animate-spin-slow {
  animation: spin-slow 8s linear infinite;
}

.bg-premium-blue { background-color: #0f172a; }
.text-premium-blue { color: #0f172a; }
.bg-premium-yellow { background-color: #facc15; }
.text-premium-brown { color: #92400e; }
.bg-premium-bg { background-color: #FFF8F3; }
</style>
