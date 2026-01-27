<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import { Search, FileText, Star, TrendingUp, Clock, CheckCircle, XCircle, MapPin, Loader2, User, Settings, LogOut, Power, MessageCircle } from 'lucide-vue-next';
import PhotoUploader from '../components/PhotoUploader.vue';
import AvailabilityScheduler from '../components/AvailabilityScheduler.vue';

const auth = useAuthStore();
const applications = ref([]);
const reviewsData = ref({ reviews: [], average_rating: 0, total_reviews: 0 });
const categories = ref([]);
const loading = ref(true);
const activeTab = ref('overview');
const saving = ref(false);

// Form Data
const profileForm = ref({
  first_name: '',
  last_name: '',
  phone: '',
  address: '',
  skills: '',
  description: '',
  experience: '',
  diplomas: '',
  category_ids: [],
  availabilities: {}
});

const visibility = ref(true);

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

const fetchCategories = async () => {
  try {
    const response = await api.get('/api/offers/categories');
    categories.value = response.data;
  } catch (err) {
    console.error('Erreur chargement catégories:', err);
  }
};

const initProfileForm = () => {
  const user = auth.user;
  if (user) {
    const userCategoryIds = user.prestataire?.categories?.map(c => c.id) || [];
    if (userCategoryIds.length === 0 && user.prestataire?.category_id) {
        userCategoryIds.push(user.prestataire.category_id);
    }

    profileForm.value = {
      first_name: user.first_name || '',
      last_name: user.last_name || '',
      phone: user.phone || '',
      address: user.address || '',
      skills: user.prestataire?.skills || '',
      description: user.prestataire?.description || '',
      experience: user.prestataire?.experience || '',
      diplomas: user.prestataire?.diplomas || '',
      category_ids: userCategoryIds,
      availabilities: user.prestataire?.availabilities || {},
    };
    visibility.value = user.prestataire?.is_visible ?? true;
  }
};

onMounted(async () => {
  await Promise.all([fetchApplications(), fetchReviews(), fetchCategories()]);
  // Ensure we have the latest user data including provider details
  if (auth.token) {
      await auth.fetchUser();
  }
  initProfileForm();
  loading.value = false;
});

// Watch for user changes to keep form in sync if reloaded
watch(() => auth.user, () => {
  initProfileForm();
}, { deep: true });

const saveProfile = async () => {
  saving.value = true;
  try {
    const response = await api.post('/api/provider/profile', profileForm.value);
    auth.user = response.data.user; // Update store
    
    // Save availability
    await api.post('/api/provider/availability', { availabilities: profileForm.value.availabilities });
    
    alert('Profil mis à jour avec succès');
  } catch (err) {
    console.error(err);
    alert('Erreur lors de la mise à jour du profil');
  } finally {
    saving.value = false;
  }
};

const handlePhotoUpdate = (newUrl) => {
    auth.fetchUser();
};

const handleAvailabilityUpdate = (newAvailability) => {
    profileForm.value.availabilities = newAvailability;
};

const toggleVisibility = async () => {
  try {
    const response = await api.post('/api/provider/visibility');
    visibility.value = response.data.is_visible;
    if (auth.user && auth.user.prestataire) {
        auth.user.prestataire.is_visible = visibility.value;
    }
  } catch (err) {
    console.error(err);
    alert('Erreur lors du changement de visibilité');
  }
};

const deleteAccount = async () => {
    if (!confirm('Êtes-vous sûr de vouloir supprimer définitivement votre compte ? Cette action est irréversible.')) {
        return;
    }
    
    try {
        await api.delete('/api/account/delete');
        auth.logout();
        window.location.href = '/'; // Force redirect
    } catch (err) {
        console.error(err);
        alert('Erreur lors de la suppression du compte');
    }
};

// Existing Stats & Helpers
const profileCompletion = computed(() => {
    const fields = [
        auth.user?.first_name,
        auth.user?.last_name,
        auth.user?.phone,
        auth.user?.address,
        auth.user?.prestataire?.photo,
        auth.user?.prestataire?.description,
        auth.user?.prestataire?.skills,
        auth.user?.prestataire?.categories?.length > 0
    ];
    const completedFields = fields.filter(f => f && (Array.isArray(f) ? f.length > 0 : true));
    return Math.round((completedFields.length / fields.length) * 100);
});

const activeMissions = computed(() => {
  return applications.value.filter(a => ['accepted', 'completed'].includes(a.status));
});

const pendingApplications = computed(() => {
  return applications.value.filter(a => a.status === 'pending');
});

const performanceData = computed(() => {
    // Mocking performance evolution for the visualization
    // In a real app, this might come from an analytics endpoint
    return [
        { label: 'Lun', value: 20 },
        { label: 'Mar', value: 45 },
        { label: 'Mer', value: 30 },
        { label: 'Jeu', value: 65 },
        { label: 'Ven', value: 50 },
        { label: 'Sam', value: 80 },
        { label: 'Dim', value: 95 },
    ];
});

const nextLevel = computed(() => {
    const score = auth.user?.prestataire?.pro_score || 0;
    if (score < 100) return { name: 'Confirmé', target: 100, remaining: 100 - score };
    if (score < 300) return { name: 'Expert', target: 300, remaining: 300 - score };
    return null;
});

const getBadgeClass = (level) => {
    switch (level) {
        case 'Expert': return 'bg-purple-600 text-white shadow-lg shadow-purple-200';
        case 'Confirmé': return 'bg-blue-600 text-white shadow-lg shadow-blue-200';
        default: return 'bg-gray-500 text-white shadow-lg shadow-gray-200';
    }
};

const getScoreColor = (score) => {
    if (score >= 300) return 'text-purple-600';
    if (score >= 100) return 'text-blue-600';
    return 'text-gray-600';
};

const stats = computed(() => {
  return {
    total: applications.value.length,
    accepted: applications.value.filter(a => a.status === 'accepted').length,
    completed: applications.value.filter(a => a.status === 'completed').length,
  };
});

const getStatusClass = (status) => {
  switch (status) {
    case 'pending': return 'bg-yellow-50 text-yellow-700 border-yellow-100';
    case 'accepted': return 'bg-green-50 text-green-700 border-green-100';
    case 'rejected': return 'bg-red-50 text-red-700 border-red-100';
    case 'completed': return 'bg-blue-50 text-blue-700 border-blue-100';
    default: return 'bg-gray-50 text-gray-700 border-gray-100';
  }
};

const getStatusLabel = (status) => {
  switch (status) {
    case 'pending': return 'En attente';
    case 'accepted': return 'Acceptée';
    case 'rejected': return 'Refusée';
    case 'completed': return 'Terminée';
    default: return status;
  }
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short'
  });
};

const updateStatus = async (id, status) => {
  try {
    await api.post(`/api/service-requests/${id}/status`, { status });
    await fetchApplications();
  } catch (err) {
    alert('Erreur lors de la mise à jour du statut');
  }
};
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <!-- Profile Completion Banner -->
    <div v-if="profileCompletion < 100" class="mb-10 bg-gradient-to-r from-blue-600 to-indigo-700 rounded-3xl p-8 text-white shadow-xl relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="max-w-md">
                <h2 class="text-2xl font-bold mb-2">Valorisez votre profil !</h2>
                <p class="text-blue-100 mb-4">Un profil complet a 5 fois plus de chances d'attirer des clients. Ajoutez vos compétences, votre photo et vos catégories.</p>
                <div class="flex items-center space-x-4">
                    <div class="flex-1 bg-blue-900/40 rounded-full h-3">
                        <div :style="{ width: profileCompletion + '%' }" class="bg-white h-full rounded-full transition-all duration-1000"></div>
                    </div>
                    <span class="font-bold whitespace-nowrap">{{ profileCompletion }}% complété</span>
                </div>
            </div>
            <button @click="activeTab = 'profile'" class="bg-white text-blue-600 px-8 py-3 rounded-xl font-bold hover:bg-blue-50 transition shadow-lg">
                Compléter maintenant
            </button>
        </div>
        <!-- Decorative shapes -->
        <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-40 h-40 bg-blue-400/20 rounded-full blur-3xl"></div>
    </div>

    <!-- Header with Profile Summary -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
      <div class="flex items-center space-x-4">
         <div class="w-16 h-16 rounded-full overflow-hidden bg-gray-100 border-2 border-white shadow">
            <img 
               v-if="auth.user?.prestataire?.photo_url" 
               :src="auth.user.prestataire.photo_url" 
               alt="Profile"
               class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                <User class="w-8 h-8" />
            </div>
         </div>
         <div>
            <div class="flex items-center space-x-3 mb-1">
                <h1 class="text-3xl font-bold text-gray-900">Bonjour, {{ auth.user?.first_name || auth.user?.name }}</h1>
                <span 
                    v-if="auth.user?.prestataire?.badge_level" 
                    :class="getBadgeClass(auth.user.prestataire.badge_level)"
                    class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest"
                >
                    {{ auth.user.prestataire.badge_level }}
                </span>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
               <p v-if="visibility" class="flex items-center text-green-600 text-sm font-medium">
                 <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                 Profil visible
               </p>
               <p v-else class="flex items-center text-gray-400 text-sm font-medium">
                 <span class="w-2 h-2 bg-gray-300 rounded-full mr-2"></span>
                 Profil masqué
               </p>
               <p v-if="nextLevel" class="text-xs text-gray-400 font-medium bg-gray-50 px-2 py-0.5 rounded-lg">
                   Plus que <span class="text-blue-600 font-bold">{{ nextLevel.remaining }}</span> points pour le badge <span class="uppercase font-black">{{ nextLevel.name }}</span>
               </p>
            </div>
         </div>
      </div>
      
      <!-- Reputation Score Summary -->
      <div class="mb-10 p-6 bg-white/80 backdrop-blur-xl rounded-[40px] border border-white/20 shadow-xl shadow-blue-50/50 flex flex-col md:flex-row items-center justify-between gap-8">
          <div class="flex items-center space-x-6">
              <div class="relative w-24 h-24 flex items-center justify-center">
                  <svg class="w-full h-full transform -rotate-90">
                      <circle cx="48" cy="48" r="40" stroke="currentColor" stroke-width="8" fill="transparent" class="text-gray-100" />
                      <circle 
                          cx="48" cy="48" r="40" stroke="currentColor" stroke-width="8" fill="transparent" 
                          :stroke-dasharray="251.2" 
                          :stroke-dashoffset="251.2 - (Math.min(auth.user?.prestataire?.pro_score || 0, 300) / 300) * 251.2"
                          stroke-linecap="round"
                          :class="getScoreColor(auth.user?.prestataire?.pro_score)"
                          class="transition-all duration-1000"
                      />
                  </svg>
                  <div class="absolute inset-0 flex flex-col items-center justify-center">
                      <span class="text-2xl font-black text-gray-900 leading-none">{{ auth.user?.prestataire?.pro_score || 0 }}</span>
                      <span class="text-[8px] font-black text-gray-400 uppercase">Pro Score</span>
                  </div>
              </div>
              <div class="space-y-1">
                  <h3 class="font-bold text-gray-900">Votre Réputation</h3>
                  <p class="text-xs text-gray-500 max-w-xs">Votre Pro Score reflète votre fiabilité (missions) et la qualité de vos prestations (avis).</p>
              </div>
          </div>
          
          <div class="grid grid-cols-2 gap-4 w-full md:w-auto">
              <div class="bg-blue-50/50 px-6 py-4 rounded-3xl border border-blue-100">
                  <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-1">Missions</p>
                  <p class="text-xl font-black text-blue-900">+{{ (auth.user?.prestataire?.completed_missions_count || 0) * 10 }} pts</p>
              </div>
              <div class="bg-purple-50/50 px-6 py-4 rounded-3xl border border-purple-100">
                  <p class="text-[10px] font-black text-purple-600 uppercase tracking-widest mb-1">Qualité (⭐)</p>
                  <p class="text-xl font-black text-purple-900">+{{ Math.round((auth.user?.prestataire?.rating || 0) * 20) }} pts</p>
              </div>
          </div>
      </div>
      <router-link to="/search" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-700 transition flex items-center space-x-2">
        <Search class="w-5 h-5" />
        <span>Trouver du travail</span>
      </router-link>
    </div>

    <!-- Tabs Navigation -->
    <div class="flex space-x-2 border-b border-gray-200 mb-8 overflow-x-auto">
      <button 
        @click="activeTab = 'overview'"
        :class="activeTab === 'overview' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
        class="pb-4 px-4 font-medium border-b-2 transition whitespace-nowrap flex items-center space-x-2"
      >
        <TrendingUp class="w-4 h-4" />
        <span>Vue d'ensemble</span>
      </button>
      <button 
        @click="activeTab = 'profile'"
        :class="activeTab === 'profile' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
        class="pb-4 px-4 font-medium border-b-2 transition whitespace-nowrap flex items-center space-x-2"
      >
        <User class="w-4 h-4" />
        <span>Mon Profil</span>
      </button>
      <button 
        @click="activeTab = 'settings'"
        :class="activeTab === 'settings' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
        class="pb-4 px-4 font-medium border-b-2 transition whitespace-nowrap flex items-center space-x-2"
      >
        <Settings class="w-4 h-4" />
        <span>Paramètres</span>
      </button>
    </div>

    <!-- TAB: OVERVIEW -->
    <div v-show="activeTab === 'overview'">
        <!-- Stats & Performance -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
          <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div class="bg-white/80 backdrop-blur-xl p-6 rounded-3xl shadow-sm border border-white/20 flex flex-col justify-between">
                <div class="bg-purple-100/50 w-12 h-12 rounded-2xl flex items-center justify-center mb-4">
                  <FileText class="w-6 h-6 text-purple-600" />
                </div>
                <div>
                  <p class="text-sm text-gray-500 font-medium">Total Candidatures</p>
                  <p class="text-3xl font-black text-gray-900">{{ stats.total }}</p>
                </div>
            </div>
            <div class="bg-white/80 backdrop-blur-xl p-6 rounded-3xl shadow-sm border border-white/20 flex flex-col justify-between">
                <div class="bg-yellow-100/50 w-12 h-12 rounded-2xl flex items-center justify-center mb-4">
                  <Star class="w-6 h-6 text-yellow-600" />
                </div>
                <div>
                  <p class="text-sm text-gray-500 font-medium">Note moyenne</p>
                  <div class="flex items-baseline space-x-1">
                    <p class="text-3xl font-black text-gray-900">{{ reviewsData.average_rating }}</p>
                    <p class="text-xs text-gray-400 font-bold">({{ reviewsData.total_reviews }} avis)</p>
                  </div>
                </div>
            </div>
            <div class="bg-white/80 backdrop-blur-xl p-6 rounded-3xl shadow-sm border border-white/20 flex flex-col justify-between">
                <div class="bg-blue-100/50 w-12 h-12 rounded-2xl flex items-center justify-center mb-4">
                  <TrendingUp class="w-6 h-6 text-blue-600" />
                </div>
                <div>
                  <p class="text-sm text-gray-500 font-medium">Missions réussies</p>
                  <p class="text-3xl font-black text-gray-900">{{ stats.completed }}</p>
                </div>
            </div>
          </div>

          <!-- Performance Visualization -->
          <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 h-full">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-bold text-gray-900">Évolution Performance</h3>
                <span class="text-[10px] font-black uppercase text-blue-600 bg-blue-50 px-2 py-1 rounded-lg">7 derniers jours</span>
            </div>
            <div class="flex items-end justify-between h-32 gap-2">
                <div v-for="data in performanceData" :key="data.label" class="flex-1 flex flex-col items-center gap-2 group">
                    <div class="w-full bg-blue-50 rounded-lg relative overflow-hidden h-full">
                        <div 
                            class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-blue-600 to-blue-400 transition-all duration-1000 group-hover:from-blue-700 group-hover:to-blue-500" 
                            :style="{ height: data.value + '%' }"
                        ></div>
                    </div>
                    <span class="text-[10px] font-bold text-gray-400">{{ data.label }}</span>
                </div>
            </div>
          </div>
        </div>

        <!-- Ongoing Missions Section -->
        <div class="space-y-6 mb-12">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900 flex items-center tracking-tight">
                    <TrendingUp class="w-6 h-6 mr-2 text-blue-600" />
                    Missions en cours
                </h2>
                <span class="px-3 py-1 bg-blue-50 text-blue-700 text-xs rounded-full font-black border border-blue-100">{{ activeMissions.filter(m => m.status === 'accepted').length }}</span>
            </div>

            <div v-if="activeMissions.filter(m => m.status === 'accepted').length === 0" class="bg-white rounded-[2.5rem] p-12 text-center border border-dashed border-gray-200">
                <p class="text-gray-400 font-medium italic">Vous n'avez aucune mission active pour le moment.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="app in activeMissions.filter(m => m.status === 'accepted')" :key="app.id" class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                    <div class="flex justify-between items-start mb-4">
                        <span class="px-3 py-1 bg-green-50 text-green-700 border-green-100 rounded-full text-[10px] font-black uppercase border tracking-widest">
                            {{ getStatusLabel(app.status) }}
                        </span>
                        <span class="text-sm font-black text-gray-900 bg-gray-50 px-3 py-1 rounded-xl group-hover:bg-blue-600 group-hover:text-white transition-colors">{{ app.service_offer?.budget }} €</span>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-1 group-hover:text-blue-600 transition">{{ app.service_offer?.title }}</h3>
                    <p class="text-xs text-gray-500 mb-4 flex items-center font-medium">
                        <span class="w-1.5 h-1.5 bg-blue-500 rounded-full mr-2"></span>
                        Client: {{ app.service_offer?.user?.name }}
                    </p>
                    <div class="flex items-center justify-between pt-4 border-t border-gray-50 mt-auto">
                        <div class="flex items-center text-[10px] text-gray-400 font-black uppercase tracking-widest">
                            <Clock class="w-3 h-3 mr-1" />
                            {{ formatDate(app.service_offer?.desired_date) }}
                        </div>
                        <button @click="$router.push(`/messages?user=${app.created_by_id === auth.user.id ? app.service_offer.user_id : app.created_by_id}`)" class="bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white p-2.5 rounded-xl transition-all shadow-sm">
                            <MessageCircle class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Missions History Section (Completed) -->
        <div class="space-y-6 mb-10">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900 flex items-center tracking-tight text-gray-400">
                    <CheckCircle class="w-6 h-6 mr-2 text-gray-400" />
                    Historique des missions
                </h2>
                <span class="px-3 py-1 bg-gray-100 text-gray-600 text-xs rounded-full font-black border border-gray-200">{{ activeMissions.filter(m => m.status === 'completed').length }}</span>
            </div>

            <div v-if="activeMissions.filter(m => m.status === 'completed').length === 0" class="p-4 text-center">
                <p class="text-xs text-gray-300 font-black uppercase tracking-[0.2em]">Aucun historique archivé</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 opacity-80 hover:opacity-100 transition-opacity">
                <div v-for="app in activeMissions.filter(m => m.status === 'completed')" :key="app.id" class="bg-gray-50 p-6 rounded-[2rem] border border-gray-200 shadow-none grayscale hover:grayscale-0 hover:bg-white hover:border-blue-100 transition-all">
                    <div class="flex justify-between items-start mb-4">
                        <span class="px-3 py-1 bg-blue-50 text-blue-700 border-blue-100 rounded-full text-[10px] font-black uppercase border tracking-widest leading-none">
                            Terminée
                        </span>
                        <span class="text-xs font-black text-gray-400">{{ app.service_offer?.budget }} €</span>
                    </div>
                    <h3 class="font-bold text-gray-600 mb-1 line-through decoration-gray-300">{{ app.service_offer?.title }}</h3>
                    <p class="text-xs text-gray-400 flex items-center mb-4">
                        Client: {{ app.service_offer?.user?.name }}
                    </p>
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200 mt-auto">
                        <div class="text-[10px] text-gray-400 font-bold uppercase">
                            Archivé le {{ formatDate(app.updated_at) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Applications Section -->
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900 flex items-center">
                    <FileText class="w-6 h-6 mr-2 text-blue-600" />
                    Candidatures en attente
                </h2>
                <span class="px-4 py-1 bg-blue-100/50 text-blue-700 text-xs rounded-full font-black border border-blue-200">{{ pendingApplications.length }}</span>
            </div>

            <div v-if="pendingApplications.length === 0" class="bg-white rounded-[2.5rem] p-16 text-center border border-dashed border-gray-200">
                <p class="text-gray-400 font-medium">Vous n'avez aucune candidature en attente.</p>
                <router-link to="/search" class="text-blue-600 font-black text-sm mt-4 inline-flex items-center hover:translate-x-1 transition-transform">
                    <span>Trouver des missions</span>
                    <ChevronRight class="w-4 h-4 ml-1" />
                </router-link>
            </div>

            <!-- Card layout for mobile, Table for desktop -->
            <div v-else class="space-y-4">
                <!-- Mobile Cards -->
                <div class="grid grid-cols-1 gap-4 sm:hidden">
                    <div v-for="app in pendingApplications" :key="app.id" class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm">
                        <div class="flex justify-between items-start mb-3">
                            <h3 class="font-bold text-gray-900">{{ app.service_offer?.title }}</h3>
                            <span class="text-xs font-black text-blue-600">{{ app.service_offer?.budget }} €</span>
                        </div>
                        <div class="flex items-center text-xs text-gray-500 mb-4 space-x-4">
                            <span class="flex items-center"><User class="w-3 h-3 mr-1" /> {{ app.service_offer?.user?.name }}</span>
                            <span class="flex items-center"><Clock class="w-3 h-3 mr-1" /> {{ formatDate(app.service_offer?.desired_date) }}</span>
                        </div>
                        <div class="flex gap-2 pt-4 border-t border-gray-50">
                            <template v-if="app.created_by_id !== auth.user.id">
                                <button @click="updateStatus(app.id, 'accepted')" class="flex-1 bg-green-600 text-white py-2.5 rounded-xl text-xs font-bold shadow-lg shadow-green-100">Accepter</button>
                                <button @click="updateStatus(app.id, 'rejected')" class="flex-1 bg-gray-100 text-gray-600 py-2.5 rounded-xl text-xs font-bold">Refuser</button>
                            </template>
                            <div v-else class="w-full text-center py-2 bg-gray-50 rounded-xl">
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Attente client</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Desktop Table -->
                <div class="hidden sm:block bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] border-b border-gray-100">
                                <tr>
                                    <th class="px-8 py-6">Projet</th>
                                    <th class="px-8 py-6">Client</th>
                                    <th class="px-8 py-6">Date</th>
                                    <th class="px-8 py-6 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="app in pendingApplications" :key="app.id" class="hover:bg-blue-50/30 transition-colors group">
                                    <td class="px-8 py-6">
                                        <p class="font-bold text-gray-900 group-hover:text-blue-600 transition">{{ app.service_offer?.title }}</p>
                                        <p class="text-[10px] font-black text-blue-600 uppercase tracking-wider mt-1">{{ app.service_offer?.budget }} €</p>
                                    </td>
                                    <td class="px-8 py-6 text-sm font-bold text-gray-600">
                                        {{ app.service_offer?.user?.name }}
                                    </td>
                                    <td class="px-8 py-6 text-sm text-gray-500 font-bold">
                                        {{ formatDate(app.service_offer?.desired_date) }}
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <div v-if="app.created_by_id !== auth.user.id" class="flex justify-end space-x-2">
                                            <button @click="updateStatus(app.id, 'accepted')" class="bg-green-600 text-white px-5 py-2.5 rounded-2xl text-xs font-black hover:bg-green-700 shadow-xl shadow-green-100 transition active:scale-95">Accepter</button>
                                            <button @click="updateStatus(app.id, 'rejected')" class="bg-gray-100 text-gray-500 px-5 py-2.5 rounded-2xl text-xs font-black hover:bg-red-50 hover:text-red-600 transition active:scale-95">Refuser</button>
                                        </div>
                                        <div v-else>
                                            <span class="text-[10px] font-black text-gray-400 bg-gray-100 px-4 py-1.5 rounded-full uppercase tracking-widest border border-gray-200/50">En attente</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reviews List -->
        <div class="mt-8 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <h2 class="font-bold text-gray-900">Derniers avis clients</h2>
            <div class="flex items-center text-yellow-400">
            <Star v-for="i in 5" :key="i" :class="i <= Math.round(reviewsData.average_rating) ? 'fill-current' : 'text-gray-200'" class="w-4 h-4" />
            </div>
        </div>

        <div v-if="loading" class="p-10 flex justify-center">
            <Loader2 class="w-8 h-8 text-blue-600 animate-spin" />
        </div>

        <div v-else-if="reviewsData.reviews.length === 0" class="p-10 text-center">
            <p class="text-gray-500">Aucun avis reçu pour le moment.</p>
        </div>

        <div v-else class="divide-y divide-gray-50">
            <div v-for="review in reviewsData.reviews" :key="review.id" class="p-6">
            <div class="flex justify-between mb-2">
                <h4 class="font-bold text-gray-900">{{ review.reviewer?.name }}</h4>
                <div class="flex text-yellow-400">
                <Star v-for="i in 5" :key="i" :class="i <= review.rating ? 'fill-current' : 'text-gray-200'" class="w-3 h-3" />
                </div>
            </div>
            <p class="text-sm text-gray-600 italic">"{{ review.comment }}"</p>
            <div class="mt-2 text-xs text-gray-400 flex items-center space-x-2">
                <span>Sur la mission: {{ review.service_offer?.title }}</span>
                <span>•</span>
                <span>{{ formatDate(review.created_at) }}</span>
            </div>
            </div>
        </div>
        </div>
    </div>

    <!-- TAB: PROFILE -->
    <div v-show="activeTab === 'profile'" class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Left Col: Photo & Basic Info -->
        <div class="md:col-span-1 space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-900 mb-6">Photo de profil</h3>
                <PhotoUploader 
                    :current-photo="auth.user?.prestataire?.photo_url" 
                    @photo-updated="handlePhotoUpdate" 
                />
            </div>
            
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-900 mb-6">Coordonnées</h3>
                <div class="space-y-4">
                     <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prénom</label>
                        <input v-model="profileForm.first_name" type="text" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none">
                     </div>
                     <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                        <input v-model="profileForm.last_name" type="text" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none">
                     </div>
                     <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                        <input v-model="profileForm.phone" type="text" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none">
                     </div>
                     <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Adresse / Ville</label>
                        <input v-model="profileForm.address" type="text" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none">
                     </div>
                </div>
            </div>
        </div>

        <!-- Right Col: Professional Info & Availability -->
        <div class="md:col-span-2 space-y-6">
             <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-900 mb-6">Informations Professionnelles</h3>
                
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-4">Catégories de service</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label 
                                v-for="cat in categories" 
                                :key="cat.id"
                                :class="profileForm.category_ids.includes(cat.id) ? 'border-blue-600 bg-blue-50' : 'border-gray-100'"
                                class="flex items-center space-x-3 p-3 rounded-xl border-2 cursor-pointer transition"
                            >
                                <input 
                                    type="checkbox" 
                                    :value="cat.id" 
                                    v-model="profileForm.category_ids"
                                    class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                >
                                <span class="text-sm font-medium text-gray-700">{{ cat.name }}</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Présentation (Bio)</label>
                        <textarea v-model="profileForm.description" rows="4" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Décrivez votre activité, votre passion..."></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Compétences</label>
                        <input v-model="profileForm.skills" type="text" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Plomberie, Electricité, Peinture...">
                        <p class="text-xs text-gray-400 mt-1">Séparez vos compétences par des virgules</p>
                    </div>
                </div>
             </div>

             <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-900 mb-6">Expériences & Diplômes</h3>
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Expériences</label>
                        <textarea v-model="profileForm.experience" rows="3" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Vos expériences passées..."></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Diplômes / Certifications</label>
                        <textarea v-model="profileForm.diplomas" rows="3" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="CAP, BEP, Certifications..."></textarea>
                    </div>
                </div>
            </div>

            <AvailabilityScheduler 
                :initial-availability="profileForm.availabilities"
                @update="handleAvailabilityUpdate"
            />

            <div class="flex justify-end">
                <button 
                    @click="saveProfile"
                    :disabled="saving"
                    class="bg-blue-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-blue-700 transition flex items-center space-x-2 disabled:opacity-50"
                >
                    <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
                    <span>Enregistrer les modifications</span>
                </button>
            </div>
        </div>
    </div>

    <!-- TAB: SETTINGS -->
    <div v-show="activeTab === 'settings'" class="max-w-2xl mx-auto space-y-6">
         <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
             <div class="flex items-center justify-between">
                 <div>
                     <h3 class="font-bold text-gray-900">Visibilité du profil</h3>
                     <p class="text-sm text-gray-500 mt-1">Lorsque votre profil est masqué, vous n'apparaissez plus dans les résultats de recherche.</p>
                 </div>
                 <button 
                    @click="toggleVisibility"
                    :class="visibility ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'"
                    class="px-4 py-2 rounded-lg font-bold text-sm transition flex items-center space-x-2"
                 >
                    <Power class="w-4 h-4" />
                    <span>{{ visibility ? 'Visible' : 'Masqué' }}</span>
                 </button>
             </div>
         </div>

         <div class="bg-red-50 p-6 rounded-2xl shadow-sm border border-red-100">
             <h3 class="font-bold text-red-900 mb-2">Zone Danger</h3>
             <p class="text-sm text-red-700 mb-4">La suppression de votre compte est irréversible. Toutes vos données seront effacées.</p>
             <button @click="deleteAccount" class="border-2 border-red-200 text-red-600 hover:bg-red-100 px-4 py-2 rounded-lg font-bold text-sm transition">
                Supprimer mon compte
             </button>
         </div>
    </div>
  </div>
</template>
