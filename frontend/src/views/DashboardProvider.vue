<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import { Search, FileText, Star, TrendingUp, Clock, CheckCircle, XCircle, MapPin, Loader2, User, Settings, LogOut, Power, MessageCircle, ShieldCheck, Award } from 'lucide-vue-next';
import PhotoUploader from '../components/PhotoUploader.vue';
import AvailabilityScheduler from '../components/AvailabilityScheduler.vue';

const auth = useAuthStore();
const applications = ref([]);
const reviewsData = ref({ reviews: [], average_rating: 0, total_reviews: 0 });
const categories = ref([]);
const loading = ref(true);
const activeTab = ref('overview');
const saving = ref(false);
const categorySearch = ref('');

// Grouper les catégories par domaine
const categoryGroups = {
    'Construction & Rénovation': [
        'Plomberie', 'Électricité', 'Maçonnerie', 'Peinture & Décoration', 
        'Menuiserie', 'Carrelage', 'Chauffage & Climatisation', 'Isolation', 
        'Toiture & Couverture', 'Vitrerie'
    ],
    'Services à domicile': [
        'Ménage & Nettoyage', 'Jardinage & Paysagisme', 'Garde d\'enfants', 
        'Aide à domicile', 'Repassage', 'Cuisine à domicile'
    ],
    'Services professionnels': [
        'Informatique & Dépannage', 'Cours particuliers', 'Coaching & Formation', 
        'Traduction', 'Rédaction & Correction', 'Comptabilité', 'Conseil juridique', 
        'Marketing & Communication', 'Design graphique', 'Développement web'
    ],
    'Transport & Logistique': [
        'Déménagement', 'Transport de marchandises', 'Livraison', 'Coursier'
    ],
    'Événementiel': [
        'Traiteur', 'Photographie', 'Vidéographie', 'Animation', 
        'DJ & Musicien', 'Décoration événementielle'
    ],
    'Bien-être & Santé': [
        'Coiffure à domicile', 'Esthétique & Beauté', 'Massage', 
        'Fitness & Sport', 'Diététique'
    ],
    'Automobile': [
        'Mécanique auto', 'Carrosserie', 'Dépannage auto', 'Nettoyage auto'
    ],
    'Animaux': [
        'Toilettage', 'Garde d\'animaux', 'Promenade de chiens', 'Éducation canine'
    ]
};

const filteredCategoryGroups = computed(() => {
    const searchLower = categorySearch.value.toLowerCase();
    const result = [];
    
    for (const [groupName, categoryNames] of Object.entries(categoryGroups)) {
        const groupCategories = categories.value.filter(cat => 
            categoryNames.includes(cat.name) &&
            cat.name.toLowerCase().includes(searchLower)
        );
        if (groupCategories.length > 0) {
            result.push({
                name: groupName,
                categories: groupCategories
            });
        }
    }
    return result;
});

const selectedCategoriesCount = computed(() => {
    return profileForm.value.category_ids.length;
});

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
        case 'Expert': return 'bg-premium-brown text-white shadow-xl shadow-orange-900/20';
        case 'Confirmé': return 'bg-premium-blue text-white shadow-xl shadow-blue-900/20';
        default: return 'bg-gray-400 text-white';
    }
};

const getScoreColor = (score) => {
    if (score >= 300) return 'text-premium-brown';
    if (score >= 100) return 'text-premium-blue';
    return 'text-gray-400';
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
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-24">
    <!-- Header: Bonjour + Profile Visibilty -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-black text-slate-900">Bonjour, {{ auth.user?.first_name || auth.user?.name }}</h1>
            <div class="flex items-center space-x-2 mt-1">
                <span v-if="visibility" class="flex items-center text-[10px] font-black text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100 uppercase tracking-widest">
                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-1.5 animate-pulse"></span>
                    Profil visible
                </span>
                 <span v-else class="flex items-center text-[10px] font-black text-slate-400 bg-slate-50 px-2 py-0.5 rounded-full border border-slate-200 uppercase tracking-widest">
                    <span class="w-1.5 h-1.5 bg-slate-400 rounded-full mr-1.5"></span>
                    Invisible
                </span>
                <span v-if="nextLevel" class="text-[10px] text-slate-400 font-medium ml-2">Plus que {{ nextLevel.remaining }} pts pour le badge <span class="font-black text-slate-600">{{ nextLevel.name }}</span></span>
            </div>
        </div>
        <div class="w-12 h-12 rounded-full bg-white shadow-xl flex items-center justify-center border-4 border-white overflow-hidden group hover:scale-110 transition-transform duration-500">
             <img 
               v-if="auth.user?.prestataire?.photo_url" 
               :src="auth.user.prestataire.photo_url" 
               class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-slate-300">
                <User class="w-6 h-6" />
            </div>
        </div>
    </div>

    <!-- Profile Completion Banner (Dark) -->
    <div class="bg-[#1e293b] rounded-[2rem] p-6 text-white shadow-xl relative overflow-hidden mb-8">
        <div class="relative z-10">
            <h2 class="text-xl font-black mb-2">{{ $t('provider_dashboard.banner.title') }}</h2>
            <p class="text-slate-300 text-xs mb-6 font-medium leading-relaxed max-w-[90%]">{{ $t('provider_dashboard.banner.desc') }}</p>
            
            <div class="flex items-center space-x-4 mb-6">
                <div class="flex-1 bg-slate-700/50 rounded-full h-2">
                    <div :style="{ width: profileCompletion + '%' }" class="bg-premium-yellow h-full rounded-full shadow-[0_0_10px_rgba(250,204,21,0.5)]"></div>
                </div>
                <span class="font-black text-premium-yellow text-sm">{{ profileCompletion }}%</span>
            </div>

            <button @click="activeTab = 'profile'" class="w-full bg-premium-yellow text-slate-900 py-3.5 rounded-xl font-black text-sm hover:bg-yellow-400 transition-colors shadow-lg shadow-yellow-500/20">
                {{ $t('provider_dashboard.banner.cta') }}
            </button>
        </div>
        <!-- Decor -->
         <div class="absolute top-0 right-0 w-32 h-32 bg-slate-800 rounded-full blur-[60px] opacity-50"></div>
    </div>

    <!-- Reputation Card -->
    <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 mb-8">
        <h3 class="font-bold text-slate-900 text-sm mb-6">{{ $t('provider_dashboard.reputation.title') }}</h3>
        
        <div class="flex items-center gap-6">
            <!-- Circular Progress -->
            <div class="relative w-24 h-24 shrink-0">
                <svg class="w-full h-full transform -rotate-90">
                    <circle cx="48" cy="48" r="40" stroke="#f1f5f9" stroke-width="8" fill="transparent" />
                    <circle 
                        cx="48" cy="48" r="40" stroke="#3b82f6" stroke-width="8" fill="transparent" 
                        :stroke-dasharray="251.2" 
                        :stroke-dashoffset="251.2 - (Math.min(auth.user?.prestataire?.pro_score || 0, 300) / 300) * 251.2"
                        stroke-linecap="round"
                    />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-2xl font-black text-slate-900">{{ auth.user?.prestataire?.pro_score || 0 }}</span>
                    <span class="text-[8px] font-black text-slate-400 uppercase">Pro Score</span>
                </div>
                <!-- Small dot indicator -->
                 <div class="absolute top-1 right-2 w-2 h-2 bg-blue-500 rounded-full border border-white"></div>
            </div>

            <div class="flex flex-col gap-3 w-full">
                 <div class="bg-[#FFF8F3] px-4 py-3 rounded-2xl border border-[#FFE8D6] flex justify-between items-center w-full">
                    <span class="text-[9px] font-black text-[#9A3412] uppercase tracking-widest">{{ $t('provider_dashboard.reputation.missions') }}</span>
                    <span class="text-sm font-black text-[#9A3412]">+{{ (auth.user?.prestataire?.completed_missions_count || 0) * 10 }} <span class="text-[9px]">pts</span></span>
                </div>
                <div class="bg-[#FFF9C4]/30 px-4 py-3 rounded-2xl border border-yellow-100 flex justify-between items-center w-full">
                    <span class="text-[9px] font-black text-yellow-700 uppercase tracking-widest">{{ $t('provider_dashboard.reputation.quality') }}</span>
                    <span class="text-sm font-black text-yellow-700">+{{ Math.round((auth.user?.prestataire?.rating || 0) * 20) }} <span class="text-[9px]">pts</span></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Tabs -->
    <div class="flex space-x-6 border-b border-gray-100 mb-8 overflow-x-auto scrollbar-hide pb-1">
      <button @click="activeTab = 'overview'" :class="activeTab === 'overview' ? 'text-slate-900 border-premium-yellow' : 'text-slate-400 border-transparent'" class="pb-3 border-b-2 font-bold text-sm transition-colors whitespace-nowrap">Vue d'ensemble</button>
      <button @click="activeTab = 'profile'" :class="activeTab === 'profile' ? 'text-slate-900 border-premium-yellow' : 'text-slate-400 border-transparent'" class="pb-3 border-b-2 font-bold text-sm transition-colors whitespace-nowrap">Mon Profil</button>
      <button @click="activeTab = 'settings'" :class="activeTab === 'settings' ? 'text-slate-900 border-premium-yellow' : 'text-slate-400 border-transparent'" class="pb-3 border-b-2 font-bold text-sm transition-colors whitespace-nowrap">Paramètres</button>
    </div>

    <div v-show="activeTab === 'overview'">
        <!-- Certification Card -->
        <div v-if="auth.user?.prestataire?.is_certified" class="bg-gradient-to-br from-premium-blue to-slate-800 rounded-[2rem] p-8 mb-8 text-white shadow-2xl relative overflow-hidden group">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-center space-x-6">
                    <div class="w-16 h-16 bg-premium-yellow rounded-2xl flex items-center justify-center shadow-lg shadow-yellow-500/20 transform group-hover:rotate-6 transition-transform">
                        <ShieldCheck class="w-10 h-10 text-premium-blue" />
                    </div>
                    <div>
                        <h2 class="text-2xl font-black tracking-tight">Prestataire Certifié <span class="text-premium-yellow">Valora</span></h2>
                        <p class="text-slate-300 text-xs font-medium uppercase tracking-[0.2em] mt-1 italic">Engagement & Excellence Vérifiés</p>
                    </div>
                </div>
                <router-link to="/certificate" class="bg-white text-premium-blue px-8 py-4 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-premium-yellow hover:scale-105 transition-all shadow-xl active:scale-95 text-center">
                    Voir mon certificat
                </router-link>
            </div>
            <!-- Decorative circle -->
            <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-premium-yellow/10 rounded-full blur-3xl"></div>
            <div class="absolute top-2 right-4 opacity-10">
                <Award class="w-32 h-32" />
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-3 gap-3 mb-10">
            <div class="bg-white p-4 rounded-[2rem] border border-slate-100 shadow-sm flex flex-col items-center text-center">
                <div class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center mb-2">
                    <FileText class="w-4 h-4 text-purple-600" />
                </div>
                <span class="text-[9px] text-slate-400 font-bold leading-tight mb-1">Total Candidatures</span>
                <span class="text-xl font-black text-slate-900">{{ stats.total }}</span>
            </div>
            <div class="bg-white p-4 rounded-[2rem] border border-slate-100 shadow-sm flex flex-col items-center text-center">
                <div class="w-8 h-8 rounded-full bg-yellow-50 flex items-center justify-center mb-2">
                    <Star class="w-4 h-4 text-yellow-600" />
                </div>
                <span class="text-[9px] text-slate-400 font-bold leading-tight mb-1">Note moyenne</span>
                 <div class="flex flex-col">
                    <span class="text-xl font-black text-slate-900">{{ reviewsData.average_rating }}</span>
                    <span class="text-[8px] text-slate-300 font-bold">({{ reviewsData.total_reviews }} avis)</span>
                 </div>
            </div>
            <div class="bg-white p-4 rounded-[2rem] border border-slate-100 shadow-sm flex flex-col items-center text-center">
                <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center mb-2">
                    <TrendingUp class="w-4 h-4 text-blue-600" />
                </div>
                <span class="text-[9px] text-slate-400 font-bold leading-tight mb-1">Missions réussies</span>
                <span class="text-xl font-black text-slate-900">{{ stats.completed }}</span>
            </div>
        </div>

        <!-- Missions en cours -->
        <div class="mb-10">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-black text-lg text-slate-900">Missions en cours</h3>
                <span v-if="activeMissions.length > 0" class="bg-blue-100 text-blue-700 text-[9px] px-2 py-0.5 rounded font-black uppercase tracking-wider">{{ activeMissions.length }} Active</span>
            </div>

            <div v-if="activeMissions.length === 0" class="bg-white rounded-[2rem] p-8 text-center border border-dashed border-slate-200">
                <p class="text-slate-400 text-sm font-medium">Aucune mission en cours</p>
                <router-link to="/search" class="text-premium-yellow text-xs font-black mt-2 inline-block">TROUVER UNE MISSION</router-link>
            </div>

            <div v-else class="space-y-4">
                <div v-for="mission in activeMissions" :key="mission.id" class="bg-white p-5 rounded-[2rem] border border-slate-100 shadow-sm">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center shrink-0">
                            <Briefcase class="w-6 h-6 text-blue-600" />
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm leading-tight mb-1">{{ mission.service_offer?.title }}</h4>
                            <p class="text-[10px] text-slate-400 font-medium">{{ mission.service_offer?.user?.name }}</p>
                        </div>
                    </div>
                    
                    <!-- Progress Bar Mockup -->
                    <div class="mb-4">
                        <div class="flex justify-between text-[10px] mb-1">
                            <span class="text-slate-400 font-medium">Progression</span>
                            <span class="font-black text-slate-900">75%</span>
                        </div>
                        <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-premium-yellow rounded-full w-3/4"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center text-[10px] bg-slate-50 px-3 py-1.5 rounded-lg text-slate-500 font-bold">
                            <Calendar class="w-3 h-3 mr-1.5 text-slate-400" />
                            {{ formatDate(mission.service_offer?.desired_date) }}
                        </div>
                        <button class="bg-premium-yellow text-slate-900 px-5 py-2 rounded-xl text-xs font-black hover:bg-yellow-400 transition" @click="$router.push(`/mission/${mission.id}`)">
                            Gérer
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Candidatures en attente (Yellow Border Style) -->
        <div class="mb-10">
            <h3 class="font-black text-lg text-slate-900 mb-4">Candidatures en attente</h3>
            
            <div v-if="pendingApplications.length === 0" class="text-center py-6">
                <p class="text-slate-400 text-xs">Aucune candidature en attente</p>
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div v-for="app in pendingApplications" :key="app.id" class="bg-white p-5 rounded-[2rem] border-2 border-premium-yellow/50 hover:border-premium-yellow transition shadow-sm">
                     <div class="flex justify-between items-start mb-2">
                        <h4 class="font-bold text-slate-900 text-sm w-2/3 truncate">{{ app.service_offer?.title }}</h4>
                        <!-- Hourglass placeholder icon for pending -->
                        <Loader2 class="w-4 h-4 text-premium-yellow animate-spin-slow" />
                     </div>
                     <p class="text-[10px] text-slate-400 mb-4">{{ app.service_offer?.user?.name }} • Il y a 2 jours</p>
                     
                     <div class="flex justify-between items-center">
                        <span class="text-[9px] font-black text-slate-400 bg-slate-50 px-2 py-1 rounded uppercase tracking-wider">En examen</span>
                        <div class="w-6 h-6 rounded-full bg-yellow-50 flex items-center justify-center text-yellow-600">
                           <Clock class="w-3 h-3" />
                        </div>
                     </div>
                </div>
            </div>
        </div>

        <!-- Historique (Green Check Style) -->
        <div class="mb-10">
            <h3 class="font-black text-lg text-slate-900 mb-4">Historique des missions</h3>
            
            <div class="space-y-3">
                 <div v-for="app in activeMissions.filter(m => m.status === 'completed')" :key="app.id" class="bg-white p-4 rounded-[2rem] shadow-sm border border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center shrink-0">
                            <CheckCircle class="w-5 h-5 text-green-500" />
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm leading-tight">{{ app.service_offer?.title }}</h4>
                            <p class="text-[10px] text-slate-400">{{ app.service_offer?.user?.name }} • {{ formatDate(app.updated_at) }}</p>
                        </div>
                    </div>
                    <span class="text-[8px] font-black bg-blue-50 text-blue-600 px-2 py-1 rounded uppercase">Terminé</span>
                 </div>
                 <!-- Fake history item if empty for visualization -->
                 <div v-if="activeMissions.filter(m => m.status === 'completed').length === 0" class="bg-white p-4 rounded-[2rem] shadow-sm border border-slate-100 flex items-center justify-between opacity-60">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center shrink-0">
                            <CheckCircle class="w-5 h-5 text-green-500" />
                        </div>
                        <div>
                             <h4 class="font-bold text-slate-900 text-sm leading-tight">Audit SEO Mobile</h4>
                             <p class="text-[10px] text-slate-400">Retail Group • Sept 2024</p>
                        </div>
                    </div>
                    <span class="text-[8px] font-black bg-blue-50 text-blue-600 px-2 py-1 rounded uppercase">Terminé</span>
                 </div>
            </div>
        </div>

        <!-- Derniers avis clients (Dark Card) -->
        <div class="bg-[#0f172a] rounded-[2rem] p-8 text-white shadow-xl">
             <h3 class="font-black text-lg mb-6">Derniers avis clients</h3>
             
             <div v-if="reviewsData.reviews.length > 0">
                 <div class="flex text-yellow-400 mb-3">
                     <Star v-for="i in 5" :key="i" :class="i <= reviewsData.reviews[0].rating ? 'fill-current' : 'text-slate-600'" class="w-4 h-4" />
                 </div>
                 <p class="text-sm text-slate-300 italic font-medium leading-relaxed mb-6">"{{ reviewsData.reviews[0].comment }}"</p>
                 <div class="flex items-center gap-3">
                     <div class="w-10 h-10 rounded-full bg-[#fae8ff] flex items-center justify-center text-[#86198f] font-black text-xs">
                         {{ reviewsData.reviews[0].reviewer?.name.substring(0,2).toUpperCase() }}
                     </div>
                     <div>
                         <p class="font-bold text-sm">{{ reviewsData.reviews[0].reviewer?.name }}</p>
                         <p class="text-[10px] text-slate-500 uppercase font-black">Client</p>
                     </div>
                 </div>
             </div>
             <div v-else>
                 <div class="flex text-yellow-400 mb-3">
                     <Star v-for="i in 5" :key="i" class="w-4 h-4 fill-current" />
                 </div>
                 <p class="text-sm text-slate-300 italic font-medium leading-relaxed mb-6">"Un travail d'une qualité exceptionnelle sur notre design system. Très réactif et force de proposition. Je recommande vivement !"</p>
                 <div class="flex items-center gap-3">
                     <div class="w-10 h-10 rounded-full bg-yellow-600/20 flex items-center justify-center text-yellow-500 font-black text-xs border border-yellow-600/30">
                         JD
                     </div>
                     <div>
                         <p class="font-bold text-sm">Jean Dupont</p>
                         <p class="text-[10px] text-slate-500 uppercase font-black">CEO @ TechCorp</p>
                     </div>
                 </div>
             </div>
        </div>
    </div>
    
    <!-- Other Tabs (Profile, Settings) kept simple for now or reused from previous structure if needed -->
    <!-- ... same as before but wrapped in v-show ... -->


    <!-- TAB: PROFILE -->
    <div v-show="activeTab === 'profile'" class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Left Col: Photo & Basic Info -->
        <div class="md:col-span-1 space-y-6">
            <!-- Photo Card -->
            <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100">
                <h3 class="font-bold text-slate-900 text-lg mb-8">{{ $t('provider_dashboard.profile.photo') }}</h3>
                <PhotoUploader 
                    :current-photo="auth.user?.prestataire?.photo_url" 
                    @photo-updated="handlePhotoUpdate" 
                />
            </div>
            
            <!-- Contact Info Card -->
            <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100">
                <h3 class="font-bold text-slate-900 text-lg mb-8">{{ $t('provider_dashboard.profile.contact_info') }}</h3>
                <div class="space-y-5">
                     <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2 pl-1">{{ $t('provider_dashboard.profile.firstname') }}</label>
                         <input v-model="profileForm.first_name" type="text" class="w-full px-5 py-3.5 rounded-2xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 outline-none transition-all font-medium text-slate-900 placeholder:text-slate-400">
                     </div>
                     <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2 pl-1">{{ $t('provider_dashboard.profile.lastname') }}</label>
                         <input v-model="profileForm.last_name" type="text" class="w-full px-5 py-3.5 rounded-2xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 outline-none transition-all font-medium text-slate-900 placeholder:text-slate-400">
                     </div>
                     <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2 pl-1">{{ $t('provider_dashboard.profile.phone') }}</label>
                         <input v-model="profileForm.phone" type="text" class="w-full px-5 py-3.5 rounded-2xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 outline-none transition-all font-medium text-slate-900 placeholder:text-slate-400">
                     </div>
                     <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2 pl-1">{{ $t('provider_dashboard.profile.address') }}</label>
                         <input v-model="profileForm.address" type="text" class="w-full px-5 py-3.5 rounded-2xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 outline-none transition-all font-medium text-slate-900 placeholder:text-slate-400">
                     </div>
                </div>
            </div>
        </div>

        <!-- Right Col: Professional Info & Availability -->
        <div class="md:col-span-2 space-y-6">
             <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-900 mb-6">{{ $t('provider_dashboard.profile.pro_info') }}</h3>
                
                <div class="space-y-6">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                          <label class="block text-sm font-medium text-gray-700">{{ $t('provider_dashboard.profile.categories') }}</label>
                          <span v-if="selectedCategoriesCount > 0" class="px-3 py-1 bg-premium-bg text-premium-brown text-xs rounded-full font-bold border border-premium-brown/20">
                            {{ selectedCategoriesCount }} sélectionnée{{ selectedCategoriesCount > 1 ? 's' : '' }}
                          </span>
                        </div>
                        
                        <!-- Search Bar -->
                        <div class="relative mb-4">
                          <Search class="absolute left-3 top-3.5 w-4 h-4 text-gray-400" />
                          <input 
                            v-model="categorySearch" 
                            type="text" 
                            :placeholder="$t('provider_dashboard.profile.search_category')"
                            class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-premium-brown/20 focus:border-premium-brown outline-none transition text-sm"
                          >
                        </div>

                        <!-- Grouped Categories -->
                        <div class="space-y-6 max-h-96 overflow-y-auto pr-2">
                          <div v-for="group in filteredCategoryGroups" :key="group.name" class="space-y-3">
                            <h4 class="text-xs font-black text-premium-brown uppercase tracking-wider sticky top-0 bg-white py-2">
                              {{ group.name }}
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                              <label 
                                v-for="cat in group.categories" 
                                :key="cat.id"
                                :class="profileForm.category_ids.includes(cat.id) ? 'border-premium-brown bg-premium-bg' : 'border-gray-50'"
                                class="flex items-center space-x-3 p-4 rounded-xl border-2 cursor-pointer transition-all hover:bg-premium-bg/50"
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
                          
                          <div v-if="filteredCategoryGroups.length === 0" class="text-center py-8">
                            <p class="text-gray-400 text-sm">{{ $t('provider_dashboard.profile.no_category') }}</p>
                          </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('provider_dashboard.profile.bio') }}</label>
                         <textarea v-model="profileForm.description" rows="4" class="w-full px-5 py-3 rounded-xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-premium-brown/20 focus:border-premium-brown outline-none transition-all" :placeholder="$t('provider_dashboard.profile.bio_placeholder')"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('provider_dashboard.profile.skills') }}</label>
                         <input v-model="profileForm.skills" type="text" class="w-full px-5 py-3 rounded-xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-premium-brown/20 focus:border-premium-brown outline-none transition-all" :placeholder="$t('provider_dashboard.profile.skills_placeholder')">
                        <p class="text-xs text-gray-400 mt-1">{{ $t('provider_dashboard.profile.skills_hint') }}</p>
                    </div>
                </div>
             </div>

             <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-900 mb-6">{{ $t('provider_dashboard.profile.experience_diplomas') }}</h3>
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('provider_dashboard.profile.experience') }}</label>
                         <textarea v-model="profileForm.experience" rows="3" class="w-full px-5 py-3 rounded-xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-premium-brown/20 focus:border-premium-brown outline-none transition-all" :placeholder="$t('provider_dashboard.profile.experience_placeholder')"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('provider_dashboard.profile.diplomas') }}</label>
                        <textarea v-model="profileForm.diplomas" rows="3" class="w-full px-5 py-3 rounded-xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-premium-brown/20 focus:border-premium-brown outline-none transition-all" :placeholder="$t('provider_dashboard.profile.diplomas_placeholder')"></textarea>
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
                    class="bg-premium-brown text-white px-10 py-4 rounded-2xl font-black hover:bg-orange-900 transition-all flex items-center space-x-3 shadow-xl disabled:opacity-50 active:scale-95"
                >
                    <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
                    <span>{{ saving ? $t('provider_dashboard.profile.saving') : $t('provider_dashboard.profile.save_changes') }}</span>
                </button>
            </div>
        </div>
    </div>

    <!-- TAB: SETTINGS -->
    <div v-show="activeTab === 'settings'" class="max-w-2xl mx-auto space-y-6">
         <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
             <div class="flex items-center justify-between">
                 <div>
                     <h3 class="font-bold text-gray-900">{{ $t('provider_dashboard.settings.profile_visibility') }}</h3>
                     <p class="text-sm text-gray-500 mt-1">{{ $t('provider_dashboard.settings.visibility_desc') }}</p>
                 </div>
                 <button 
                    @click="toggleVisibility"
                    :class="visibility ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'"
                    class="px-4 py-2 rounded-lg font-bold text-sm transition flex items-center space-x-2"
                 >
                    <Power class="w-4 h-4" />
                    <span>{{ visibility ? $t('provider_dashboard.settings.visible') : $t('provider_dashboard.settings.hidden') }}</span>
                 </button>
             </div>
         </div>

         <div class="bg-red-50 p-6 rounded-2xl shadow-sm border border-red-100">
             <h3 class="font-bold text-red-900 mb-2">{{ $t('provider_dashboard.settings.danger_zone') }}</h3>
             <p class="text-sm text-red-700 mb-4">{{ $t('provider_dashboard.settings.delete_desc') }}</p>
             <button @click="deleteAccount" class="border-2 border-red-200 text-red-600 hover:bg-red-100 px-4 py-2 rounded-lg font-bold text-sm transition">
                {{ $t('provider_dashboard.settings.delete_account') }}
             </button>
         </div>
    </div>
  </div>
</template>
