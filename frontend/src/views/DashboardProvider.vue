<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useAuthStore } from '../store/auth';
import { useRouter } from 'vue-router';
import api from '../services/api';
import { Search, FileText, Star, TrendingUp, Clock, CheckCircle, Check, XCircle, MapPin, Loader2, User, Settings, LogOut, Power, MessageCircle, ShieldCheck, Award, Plus, ArrowRight, ChevronDown, Eye, EyeOff, Briefcase, Calendar, LifeBuoy, Smartphone, Mail, Fingerprint, X, PenTool, ChevronLeft, ChevronRight, Zap, Trophy, Coins, Users } from 'lucide-vue-next';
import PhotoUploader from '../components/PhotoUploader.vue';
import AvailabilityScheduler from '../components/AvailabilityScheduler.vue';

const auth = useAuthStore();
const router = useRouter();
const applications = ref([]);
const reviewsData = ref({ reviews: [], average_rating: 0, total_reviews: 0 });
const categories = ref([]);
const loading = ref(true);
const activeTab = ref('overview');
const saving = ref(false);
const success = ref(null);
const error = ref(null);
const cvMode = ref(false); // Toggle between Edit and CV View
const categorySearch = ref('');
const currentStep = ref(1);

const nextStep = () => {
    if (currentStep.value < 3) currentStep.value++;
};

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

const moroccanCities = [
    'Agadir', 'Al Hoceima', 'Béni Mellal', 'Berkane', 'Berrechid', 'Casablanca', 
    'Chefchaouen', 'Dakhla', 'El Jadida', 'Errachidia', 'Essaouira', 'Fès', 
    'Fquih Ben Salah', 'Guelmim', 'Ifrane', 'Kénitra', 'Khemisset', 'Khénifra', 
    'Khouribga', 'Ksar El Kebir', 'Larache', 'Marrakech', 'Meknès', 'Mohammédia', 
    'Nador', 'Ouarzazate', 'Oujda', 'Rabat', 'Safi', 'Saidía', 'Salé', 'Settat', 
    'Sidi Ifni', 'Sidi Kacem', 'Sidi Slimane', 'Tanger', 'Tan-Tan', 'Taroudant', 
    'Taza', 'Tétouan', 'Tiznit'
];

const showCityDropdown = ref(false);
const cityQuery = ref('');
const filteredCities = computed(() => {
    if (!cityQuery.value) return moroccanCities;
    return moroccanCities.filter(city => 
        city.toLowerCase().includes(cityQuery.value.toLowerCase())
    );
});

const selectCity = (city) => {
    profileForm.value.city = city;
    showCityDropdown.value = false;
    cityQuery.value = '';
};

// Fermer le dropdown au clic extérieur
onMounted(() => {
    window.addEventListener('click', (e) => {
        const dropdown = document.getElementById('city-dropdown-container');
        if (dropdown && !dropdown.contains(e.target)) {
            showCityDropdown.value = false;
        }
    });
});

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
    return profileForm.value.category_ids?.length || 0;
});

const hasDiploma = ref('');

watch(hasDiploma, (val) => {
    if (val === 'Non') {
        profileForm.value.diplomas = 'Non';
    } else if (val === 'Oui' && profileForm.value.diplomas === 'Non') {
        profileForm.value.diplomas = '';
    }
});

// Form Data
const profileForm = ref({
  first_name: '',
  last_name: '',
  phone: '',
  birth_date: '',
  cin: '',
  address: '',
  city: '',
  skills: '',
  experience: '',
  diplomas: '',
  hourly_rate: '',
  category_ids: [],
  is_visible: true,
  availabilities: {},
  description: [] // Detailed experiences list
});

const addDetailedExperience = () => {
    profileForm.value.description.push('');
};

const removeDetailedExperience = (index) => {
    profileForm.value.description.splice(index, 1);
};

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
      birth_date: user.prestataire?.birth_date ? user.prestataire.birth_date.substring(0, 10) : '',
      cin: user.prestataire?.cin || '',
      address: user.address || '',
      city: user.prestataire?.city || '',
      skills: user.prestataire?.skills || '',
      experience: user.prestataire?.experience || '',
      diplomas: user.prestataire?.diplomas || '',
      hourly_rate: user.prestataire?.hourly_rate || '',
      category_ids: userCategoryIds,
      availabilities: user.prestataire?.availabilities || {},
      description: user.prestataire?.description ? user.prestataire.description.split('|||') : []
    };
    
    // Initialize hasDiploma state
    if (user.prestataire?.diplomas === 'Non') {
        hasDiploma.value = 'Non';
    } else if (user.prestataire?.diplomas) {
        hasDiploma.value = 'Oui';
    } else {
        hasDiploma.value = '';
    }

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
  
  // Auto-switch to CV mode if profile is sufficiently completed
  if (profileCompletion.value >= 80) {
      cvMode.value = true;
  }
  
  loading.value = false;
});

// Watch for user changes to keep form in sync if reloaded
watch(() => auth.user, () => {
  initProfileForm();
}, { deep: true });

const saveProfile = async () => {
  saving.value = true;
  success.value = null;
  error.value = null;
  try {
    const dataToSend = {
        ...profileForm.value,
        description: profileForm.value.description.filter(e => e.trim() !== '').join('|||')
    };
    const response = await api.post('/api/provider/profile', dataToSend);
    auth.user = response.data.user; // Update store
    
    // Save availability
    await api.post('/api/provider/availability', { availabilities: profileForm.value.availabilities });
    
    success.value = "Profil mis à jour avec succès !";
    setTimeout(() => { success.value = null; }, 4000);
    
    // Switch to CV View automatically
    cvMode.value = true;
    currentStep.value = 1;
  } catch (err) {
    console.error(err);
    error.value = "Erreur lors de la mise à jour du profil.";
    setTimeout(() => { error.value = null; }, 4000);
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
        profileForm.value.first_name,
        profileForm.value.last_name,
        profileForm.value.phone,
        profileForm.value.address,
        profileForm.value.birth_date,
        profileForm.value.cin,
        profileForm.value.city,
        profileForm.value.hourly_rate,
        auth.user?.prestataire?.photo_url,
        profileForm.value.skills,
        profileForm.value.category_ids?.length > 0
    ];
    const completedFields = fields.filter(f => f && (Array.isArray(f) ? f.length > 0 : true));
    return Math.round((completedFields.length / fields.length) * 100);
});

const currentBadge = computed(() => {
    const count = auth.user?.prestataire?.missions_count || 0;
    if (count >= 20) {
        return { 
            name: 'Premium', color: 'text-purple-600', bg: 'bg-purple-50', border: 'border-purple-200', icon: Zap 
        };
    } else if (count >= 5) {
        return { 
            name: 'Or', color: 'text-yellow-600', bg: 'bg-yellow-50', border: 'border-yellow-200', icon: Star 
        };
    } else {
        return { 
            name: 'Bronze', color: 'text-amber-700', bg: 'bg-amber-50', border: 'border-amber-200', icon: ShieldCheck 
        };
    }
});

const currentCategoryNames = computed(() => {
    return auth.user?.prestataire?.categories?.map(c => c.name).join(', ') || 'Artisan';
});

const userFullName = computed(() => {
  return `${profileForm.value.first_name} ${profileForm.value.last_name}`.trim() || auth.user?.name;
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

const orderedDays = computed(() => {
    const dayKeys = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    const labels = {
        monday: 'Lundi', tuesday: 'Mardi', wednesday: 'Mercredi', thursday: 'Jeudi',
        friday: 'Vendredi', saturday: 'Samedi', sunday: 'Dimanche'
    };
    
    return dayKeys.map(key => ({
        key,
        label: labels[key],
        ...(profileForm.value.availabilities[key] || { active: false, start: '00:00', end: '00:00' })
    }));
});
</script>

<template>
  <div :class="cvMode ? 'max-w-[1500px]' : 'max-w-7xl'" class="mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-32 font-sans selection:bg-premium-yellow selection:text-slate-900 overflow-x-hidden transition-all duration-700">
    <!-- Modern Notifications -->
    <Transition name="fade">
        <div v-if="success || error" class="fixed top-24 left-1/2 -translate-x-1/2 z-50 w-full max-w-md px-6 pointer-events-none">
            <div :class="success ? 'bg-emerald-600 shadow-emerald-500/20' : 'bg-red-600 shadow-red-500/20'" class="p-4 rounded-4xl text-white shadow-2xl backdrop-blur-md flex items-center space-x-4 animate-in slide-in-from-top-10 duration-500 pointer-events-auto">
                <div class="w-10 h-10 rounded-2xl bg-white/20 flex items-center justify-center shrink-0">
                    <CheckCircle v-if="success" class="w-6 h-6" />
                    <XCircle v-else class="w-6 h-6" />
                </div>
                <p class="font-black text-xs uppercase tracking-widest leading-tight grow">{{ success || error }}</p>
                <button @click="success = null; error = null" class="w-8 h-8 rounded-xl hover:bg-white/10 transition-colors flex items-center justify-center">
                    <X class="w-4 h-4" />
                </button>
            </div>
        </div>
    </Transition>

    <!-- Header: Premium Greetings -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
        <div class="space-y-2">
            <div class="inline-flex items-center space-x-2 bg-premium-yellow/10 px-4 py-1.5 rounded-full border border-premium-yellow/20">
                <div class="w-1.5 h-1.5 bg-premium-yellow rounded-full animate-pulse"></div>
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-premium-brown">Espace Professionnel</span>
            </div>
            <h1 class="text-4xl md:text-6xl font-black text-slate-900 tracking-tighter">
                Bonjour, <span class="text-premium-brown">{{ auth.user?.first_name || auth.user?.name }}</span>
            </h1>
            <div class="flex items-center space-x-3">
                <div v-if="visibility" class="flex items-center px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full border border-emerald-100 text-[10px] font-black uppercase tracking-widest">
                    <CheckCircle class="w-3 h-3 mr-1.5" />
                    Profil en ligne
                </div>
                <div v-else class="flex items-center px-3 py-1 bg-slate-50 text-slate-400 rounded-full border border-slate-200 text-[10px] font-black uppercase tracking-widest">
                    <XCircle class="w-3 h-3 mr-1.5" />
                    Profil masqué
                </div>
                <div v-if="nextLevel" class="text-[10px] text-slate-400 font-bold">
                    {{ nextLevel.remaining }} pts pour le badge <span class="text-slate-900">{{ nextLevel.name }}</span>
                </div>
            </div>
        </div>
        
        <div class="relative group cursor-pointer" @click="activeTab = 'profile'">
            <div class="w-20 h-20 md:w-24 md:h-24 rounded-[2.5rem] bg-white shadow-2xl border-4 border-white overflow-hidden transition-transform duration-500 group-hover:scale-105 group-hover:rotate-3">
                <img 
                   v-if="auth.user?.prestataire?.photo_url" 
                   :src="auth.user.prestataire.photo_url" 
                   class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-slate-200 bg-slate-50">
                    <User class="w-10 h-10" />
                </div>
            </div>
            <div class="absolute -bottom-2 -right-2 bg-premium-yellow p-2.5 rounded-2xl shadow-xl transform rotate-12 group-hover:rotate-0 transition-all border-4 border-white">
                <Settings class="w-4 h-4 text-slate-900" />
            </div>
        </div>
    </div>

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
        
        <!-- Sidebar: Stats & Progress -->
        <div class="lg:col-span-4 space-y-8 sticky top-28">
            <!-- Profile Completion Card (Wizard style) -->
            <div class="bg-slate-900 rounded-[3rem] p-8 text-white shadow-2xl relative overflow-hidden group">
                <div class="relative z-10 space-y-8">
                    <div class="space-y-2">
                        <h3 class="text-2xl font-black tracking-tight">Optimisez votre vitrine</h3>
                        <p class="text-slate-400 text-xs font-medium leading-relaxed">
                            Complétez votre profil pour apparaître en tête des résultats de recherche.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="flex justify-between items-end">
                            <div class="space-y-1">
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-premium-yellow opacity-80">Score de complétion</span>
                                <div class="text-[8px] text-slate-500 font-bold uppercase tracking-widest">Profil Public</div>
                            </div>
                            <div class="flex items-baseline space-x-1">
                                <span class="text-4xl font-black text-white tracking-tighter">{{ profileCompletion }}</span>
                                <span class="text-sm font-black text-premium-yellow">%</span>
                            </div>
                        </div>
                        <div class="h-4 bg-white/5 rounded-2xl overflow-hidden p-1 border border-white/5 shadow-inner">
                            <div 
                                class="h-full bg-linear-to-r from-premium-yellow via-yellow-400 to-yellow-500 rounded-xl shadow-[0_0_20px_rgba(250,204,21,0.3)] transition-all duration-1000 relative"
                                :style="{ width: profileCompletion + '%' }"
                            >
                                <div class="absolute inset-0 bg-linear-to-b from-white/20 to-transparent"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Decor -->
                <div class="absolute -top-20 -right-20 w-48 h-48 bg-premium-yellow/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-10 left-4 opacity-5 pointer-events-none">
                    <TrendingUp class="w-32 h-32" />
                </div>
            </div>

            <!-- Pro Score Card -->
            <div class="bg-white rounded-[3rem] p-8 shadow-sm border border-slate-100 relative group overflow-hidden">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="font-black text-slate-900 text-sm uppercase tracking-widest">Reputation</h3>
                    <Award class="w-5 h-5 text-premium-brown" />
                </div>

                <div class="flex items-center gap-8 mb-8">
                    <div class="relative w-28 h-28 flex items-center justify-center">
                        <svg class="w-full h-full transform -rotate-90">
                            <circle cx="56" cy="56" r="50" stroke="#f1f5f9" stroke-width="8" fill="transparent" />
                            <circle 
                                cx="56" cy="56" r="50" stroke="#0f172a" stroke-width="10" fill="transparent" 
                                :stroke-dasharray="314" 
                                :stroke-dashoffset="314 - (Math.min(auth.user?.prestataire?.pro_score || 0, 300) / 300) * 314"
                                stroke-linecap="round"
                                class="transition-all duration-1000"
                            />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-3xl font-black text-slate-900 tracking-tighter">{{ auth.user?.prestataire?.pro_score || 0 }}</span>
                            <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest text-center leading-none">Pro<br>Score</span>
                        </div>
                    </div>
                    
                    <div class="space-y-4 flex-1">
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 group-hover:border-premium-yellow transition-colors">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Missions</span>
                                <span class="text-xs font-black text-slate-900">+{{ (auth.user?.prestataire?.completed_missions_count || 0) * 10 }}</span>
                            </div>
                            <div class="h-1 bg-slate-200 rounded-full">
                                <div class="h-full bg-slate-900 rounded-full" :style="{ width: '60%' }"></div>
                            </div>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 group-hover:border-premium-yellow transition-colors">
                             <div class="flex justify-between items-center mb-1">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Qualité</span>
                                <span class="text-xs font-black text-slate-900">+{{ Math.round((auth.user?.prestataire?.rating || 0) * 20) }}</span>
                            </div>
                            <div class="h-1 bg-slate-200 rounded-full">
                                <div class="h-full bg-premium-yellow rounded-full" :style="{ width: '80%' }"></div>
                            </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Support Card -->
    <div class="bg-indigo-600 p-8 rounded-[3rem] shadow-xl shadow-indigo-200 relative overflow-hidden group/support flex flex-col justify-between">
        <div class="relative z-10 space-y-4">
            <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center">
                <LifeBuoy class="w-6 h-6 text-white" />
            </div>
            <div class="space-y-1">
                <h4 class="text-white font-black text-lg">{{ $t('common.support_title') }}</h4>
                <p class="text-indigo-100 text-[10px] font-medium leading-relaxed">{{ $t('common.support_desc') }}</p>
            </div>
            <router-link to="/messages?userId=19" class="inline-flex items-center space-x-2 bg-white text-indigo-600 px-6 py-3 rounded-xl font-bold text-[10px] uppercase tracking-widest hover:bg-premium-yellow hover:text-slate-900 transition-all shadow-lg active:scale-95">
                <span>{{ $t('common.contact_admin') }}</span>
                <ArrowRight class="w-4 h-4" />
            </router-link>
        </div>
        <!-- Decor -->
        <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-white/10 rounded-full blur-2xl group-hover/support:scale-150 transition-transform duration-700"></div>
    </div>
</div>
        <!-- Main Content Area -->
        <div class="lg:col-span-8">

            <!-- Navigation Tabs -->
            <div class="flex items-center space-x-10 border-b border-slate-100 mb-10 pb-0.5 overflow-x-auto scrollbar-hide">
                <button 
                  v-for="tab in [
                    { id: 'overview', label: 'Vue d\'ensemble' },
                    { id: 'profile', label: 'Mon Profil & Vitrine' },
                    { id: 'settings', label: 'Paramètres & Sécurité' }
                  ]"
                  :key="tab.id"
                  @click="activeTab = tab.id"
                  class="pb-5 relative group transition-all"
                  :class="activeTab === tab.id ? 'text-slate-900' : 'text-slate-400 hover:text-slate-600'"
                >
                    <span class="text-sm font-black uppercase tracking-[0.2em]">{{ tab.label }}</span>
                    <div class="absolute bottom-0 left-0 w-full h-1 bg-slate-900 rounded-full transition-all scale-x-0 group-hover:scale-x-50" :class="{ 'scale-x-100': activeTab === tab.id }"></div>
                    <div v-if="tab.id === 'overview' && pendingApplications.length > 0" class="absolute -top-1 -right-4 w-4 h-4 bg-premium-yellow rounded-full flex items-center justify-center text-[8px] font-black text-slate-900 border-2 border-white animate-bounce-slow">
                        {{ pendingApplications.length }}
                    </div>
                </button>
            </div>

            <!-- Content: Overview -->
            <div v-if="activeTab === 'overview'" class="space-y-10 animate-in fade-in slide-in-from-bottom-5 duration-700">
                <!-- Certification Status (If certified) -->
                <div v-if="auth.user?.prestataire?.is_certified" class="bg-linear-to-br from-premium-blue to-slate-800 rounded-[3rem] p-10 text-white shadow-2xl relative overflow-hidden group">
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-8">
                        <div class="flex items-center space-x-8">
                            <div class="w-20 h-20 bg-premium-yellow rounded-3xl flex items-center justify-center shadow-2xl shadow-yellow-500/20 transform group-hover:rotate-6 transition-transform">
                                <ShieldCheck class="w-12 h-12 text-premium-blue" />
                            </div>
                            <div class="space-y-2">
                                <h2 class="text-3xl font-black tracking-tighter">Artisan Certifié <span class="text-premium-yellow">Valora</span></h2>
                                <p class="text-slate-400 text-xs font-black uppercase tracking-[0.2em] italic flex items-center">
                                    <CheckCircle class="w-3.5 h-3.5 mr-2 text-premium-yellow" />
                                    Identité & Compétences Validées
                                </p>
                            </div>
                        </div>
                        <router-link to="/certificate" class="bg-white text-slate-900 px-10 py-5 rounded-[2rem] font-black text-xs uppercase tracking-widest hover:bg-premium-yellow hover:scale-105 transition-all shadow-2xl active:scale-95 text-center">
                            Voir mon certificat
                        </router-link>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div v-for="stat in [
                        { icon: FileText, label: 'Applications', value: stats.total, color: 'text-purple-600', bg: 'bg-purple-50' },
                        { icon: Star, label: 'Note Moyenne', value: reviewsData.average_rating, color: 'text-amber-600', bg: 'bg-amber-50', sub: reviewsData.total_reviews + ' avis' },
                        { icon: TrendingUp, label: 'Réussites', value: stats.completed, color: 'text-blue-600', bg: 'bg-blue-50' }
                    ]" :key="stat.label" class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-sm hover:shadow-xl transition-all duration-500 group">
                        <div class="flex flex-col items-center text-center space-y-4">
                            <div class="w-14 h-14 rounded-3xl flex items-center justify-center transition-transform group-hover:scale-110" :class="stat.bg">
                                <component :is="stat.icon" class="w-7 h-7" :class="stat.color" />
                            </div>
                            <div>
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ stat.label }}</h4>
                                <div class="text-3xl font-black text-slate-900 tracking-tighter">{{ stat.value }}</div>
                                <p v-if="stat.sub" class="text-[9px] text-slate-400 font-bold mt-1 uppercase tracking-widest">{{ stat.sub }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Missions -->
                <div class="space-y-6">
                    <div class="flex items-center justify-between px-4">
                        <h3 class="text-xl font-black text-slate-900 tracking-tight">Missions Actives</h3>
                        <router-link to="/search" class="text-[10px] font-black text-premium-brown uppercase tracking-widest hover:text-slate-900 transition-colors">Explorer plus <ArrowRight class="inline w-3 h-3 ml-1" /></router-link>
                    </div>

                    <div v-if="activeMissions.length === 0" class="bg-white rounded-[3rem] p-16 text-center border-2 border-dashed border-slate-100 group hover:border-premium-yellow transition-colors cursor-pointer" @click="$router.push('/search')">
                        <div class="max-w-xs mx-auto space-y-6">
                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto transition-transform group-hover:scale-110 group-hover:bg-premium-yellow/10">
                                <Search class="w-8 h-8 text-slate-300 group-hover:text-premium-yellow" />
                            </div>
                            <p class="text-slate-400 text-sm font-medium">Vous n'avez aucune mission en cours. Commencez à postuler pour booster votre activité !</p>
                            <span class="inline-block bg-slate-900 text-white px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-xl">Rechercher des missions</span>
                        </div>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div v-for="mission in activeMissions" :key="mission.id" class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group">
                            <div class="space-y-6">
                                <div class="flex justify-between items-start">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center group-hover:bg-premium-yellow/20 transition-colors">
                                            <Briefcase class="w-6 h-6 text-blue-600 group-hover:text-slate-900" />
                                        </div>
                                        <div>
                                            <h4 class="font-black text-slate-900 text-sm leading-tight group-hover:text-premium-brown transition-colors">{{ mission.service_offer?.title }}</h4>
                                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">{{ mission.service_offer?.user?.name }}</p>
                                        </div>
                                    </div>
                                    <div class="bg-emerald-50 text-emerald-600 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest">Active</div>
                                </div>
                                
                                <div class="bg-slate-50 p-4 rounded-2xl space-y-3">
                                    <div class="flex justify-between items-center text-[10px] font-bold text-slate-500">
                                        <span>Progression</span>
                                        <span class="text-slate-900">En cours</span>
                                    </div>
                                    <div class="h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-linear-to-r from-blue-500 to-blue-400 rounded-full w-2/3 shadow-sm"></div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-1.5 bg-slate-50 px-3 py-1.5 rounded-xl text-[10px] font-black text-slate-500">
                                        <MapPin class="w-3 h-3" />
                                        <span>{{ mission.service_offer?.city || 'Maroc' }}</span>
                                    </div>
                                    <button class="bg-slate-900 text-white px-6 py-3 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-premium-yellow hover:text-slate-900 transition-all shadow-lg active:scale-95">
                                        Détails
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Feedback -->
                <div class="bg-slate-900 rounded-[3.5rem] p-10 text-white relative overflow-hidden group shadow-2xl shadow-slate-900/40">
                    <div class="relative z-10 flex flex-col md:flex-row gap-10">
                        <div class="md:w-1/3 space-y-4">
                            <h3 class="text-2xl font-black tracking-tight">Derniers Avis</h3>
                            <div class="flex items-center space-x-2">
                                <div class="flex text-premium-yellow">
                                    <Star v-for="i in 5" :key="i" class="w-5 h-5 fill-current" />
                                </div>
                                <span class="text-3xl font-black">{{ reviewsData.average_rating }}</span>
                            </div>
                            <p class="text-slate-400 text-xs font-medium leading-relaxed">
                                Votre réputation est votre meilleur atout. Continuez à fournir un service d'excellence.
                            </p>
                        </div>

                        <div class="flex-1">
                            <div v-if="reviewsData.reviews.length > 0" class="space-y-6">
                                <div class="bg-white/5 backdrop-blur-md p-8 rounded-[2.5rem] border border-white/10 relative">
                                    <p class="text-lg italic text-slate-200 font-medium leading-relaxed mb-8">
                                        "{{ reviewsData.reviews[0].comment }}"
                                    </p>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-premium-yellow font-black text-sm">
                                                {{ reviewsData.reviews[0].reviewer?.name.substring(0,2).toUpperCase() }}
                                            </div>
                                            <div>
                                                <h5 class="text-sm font-black">{{ reviewsData.reviews[0].reviewer?.name }}</h5>
                                                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest italic">Client vérifié</p>
                                            </div>
                                        </div>
                                        <div class="bg-white/10 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-400">
                                            {{ formatDate(reviewsData.reviews[0].created_at) }}
                                        </div>
                                    </div>
                                    <MessageCircle class="absolute top-6 right-8 w-12 h-12 text-white/5" />
                                </div>
                            </div>
                            <div v-else class="h-full flex items-center justify-center border-2 border-dashed border-white/10 rounded-[2.5rem] py-12">
                                <p class="text-slate-500 font-black uppercase tracking-widest text-[10px]">Aucun avis pour le moment</p>
                            </div>
                        </div>
                    </div>
                    <!-- Decor -->
                     <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-premium-yellow/5 rounded-full blur-3xl"></div>
                </div>
            </div>
    
    <!-- Other Tabs (Profile, Settings) kept simple for now or reused from previous structure if needed -->
    <!-- ... same as before but wrapped in v-show ... -->



            <!-- TAB: PROFILE -->
            <div v-show="activeTab === 'profile'" class="space-y-10 animate-in fade-in slide-in-from-bottom-5 duration-700">
                <!-- CV / Edit Toggle -->
                <div class="flex items-center justify-between bg-white p-4 rounded-3xl border border-slate-100 shadow-sm">
                    <div class="flex items-center space-x-4 pl-4">
                        <div class="p-2 bg-premium-yellow/10 rounded-xl">
                            <Eye v-if="cvMode" class="w-5 h-5 text-premium-brown" />
                            <PenTool v-else class="w-5 h-5 text-premium-brown" />
                        </div>
                        <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest">
                            {{ cvMode ? 'Aperçu de votre CV' : 'Modifier ma vitrine' }}
                        </h3>
                    </div>
                    <button 
                        @click="cvMode = !cvMode"
                        class="flex items-center space-x-3 px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all active:scale-95"
                        :class="cvMode ? 'bg-slate-900 text-white shadow-xl shadow-slate-900/20' : 'bg-premium-yellow text-slate-900 shadow-xl shadow-yellow-500/10 hover:bg-premium-brown hover:text-white'"
                    >
                        <span>{{ cvMode ? 'Mode Édition' : 'Aperçu CV' }}</span>
                        <ArrowRight v-if="!cvMode" class="w-4 h-4" />
                        <Settings v-else class="w-4 h-4" />
                    </button>
                </div>

                <!-- CV MODE VIEW (Refined Premium Design) -->
                <div v-if="cvMode" class="space-y-10 xl:space-y-6 xl:scale-[0.98] xl:origin-top animate-in fade-in zoom-in-95 duration-500 pb-20 transition-all duration-700">
                    
                    <!-- Hero CV Card -->
                    <div class="bg-white rounded-[4rem] shadow-2xl shadow-slate-200/50 border border-slate-100 overflow-hidden relative">
                        <!-- Cover with Actions -->
                        <div class="h-60 xl:h-48 bg-slate-900 relative overflow-hidden transition-all duration-700">
                            <div class="absolute inset-0 bg-linear-to-r from-slate-900 via-slate-800 to-slate-900"></div>
                            <div class="absolute top-0 right-0 w-96 h-96 bg-premium-yellow/10 rounded-full blur-3xl -mr-20 -mt-20"></div>
                            <div class="absolute bottom-0 left-0 w-64 h-64 bg-premium-brown/20 rounded-full blur-3xl -ml-10 -mb-10"></div>
                            
                            <!-- Quick Management Actions -->
                            <div class="absolute top-8 right-10 flex items-center space-x-4 z-20">
                                <button @click="cvMode = false" class="bg-white/10 hover:bg-white/20 backdrop-blur-md text-white px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest border border-white/10 transition-all flex items-center space-x-2 group">
                                    <PenTool class="w-4 h-4 text-premium-yellow group-hover:rotate-12 transition-transform" />
                                    <span>Modifier</span>
                                </button>
                                <button @click="deleteAccount" class="bg-red-500/20 hover:bg-red-500/40 backdrop-blur-md text-red-100 px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest border border-red-500/20 transition-all flex items-center space-x-2 group">
                                    <XCircle class="w-4 h-4 group-hover:scale-110 transition-transform" />
                                    <span>Supprimer</span>
                                </button>
                            </div>

                            <!-- Decorative Badge Section -->
                            <div class="absolute bottom-6 left-10 z-20 hidden md:block">
                                <div class="px-4 py-2 bg-white/5 backdrop-blur-xl rounded-2xl border border-white/10 flex items-center space-x-3">
                                    <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                                    <span class="text-[9px] font-black text-white uppercase tracking-widest">Compte Vérifié & Actif</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="px-10 md:px-20 pb-16 xl:pb-10">
                            <div class="flex flex-col md:flex-row gap-12 items-start -mt-24 relative z-10">
                                <!-- Avatar Orb -->
                                <div class="relative group/avatar">
                                    <div class="w-48 h-48 xl:w-40 xl:h-40 rounded-[3.5rem] xl:rounded-[3rem] bg-slate-50 border-[10px] xl:border-[8px] border-white shadow-2xl overflow-hidden flex items-center justify-center relative z-10 transform group-hover/avatar:scale-105 transition-all duration-500">
                                        <img v-if="auth.user?.prestataire?.photo_url" :src="auth.user.prestataire.photo_url" class="w-full h-full object-cover">
                                        <User v-else class="w-20 h-20 text-slate-200" />
                                    </div>
                                    <div class="absolute -bottom-4 -right-4 p-3 rounded-2xl shadow-2xl border-4 border-white z-20 flex items-center justify-center ring-8 ring-white/30" :class="currentBadge.bg" :title="`Badge ${currentBadge.name}`">
                                        <component :is="currentBadge.icon" class="w-8 h-8" :class="currentBadge.color" />
                                    </div>
                                </div>
                                
                                <div class="md:pt-28 xl:pt-20 space-y-4 grow">
                                    <div class="space-y-2">
                                        <div class="flex flex-col md:flex-row md:items-center gap-3 md:gap-6">
                                            <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-900 tracking-tighter">{{ userFullName }}</h2>
                                            <div class="flex items-center">
                                                <div class="px-4 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-[0.2em] border shadow-sm backdrop-blur-md" :class="[currentBadge.bg, currentBadge.border, currentBadge.color]">
                                                    {{ currentBadge.name }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap gap-x-6 gap-y-3 items-center text-sm font-bold text-slate-500">
                                            <span class="flex items-center text-premium-brown bg-premium-yellow/5 px-3 py-1 rounded-lg border border-premium-yellow/10">
                                                <Briefcase class="w-4 h-4 mr-2" />
                                                {{ currentCategoryNames }}
                                            </span>
                                            <div class="flex items-center space-x-2">
                                                <MapPin class="w-4 h-4 text-slate-400" />
                                                <span>{{ profileForm.city || 'Maroc' }}</span>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <Star class="w-4 h-4 text-yellow-400 fill-current" />
                                                <span class="text-slate-900">4.9</span>
                                                <span class="text-[10px] text-slate-400 font-medium">Note moyenne</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-wrap gap-2.5 pt-4">
                                        <div v-for="skill in (profileForm.skills ? profileForm.skills.split(',') : [])" :key="skill" class="group/skill bg-slate-50 px-5 py-3 rounded-2xl text-[10px] font-bold uppercase tracking-widest text-slate-700 border border-slate-100 hover:bg-white hover:shadow-xl hover:shadow-premium-yellow/5 hover:border-premium-yellow/20 hover:-translate-y-1 transition-all duration-300 flex items-center space-x-2.5">
                                            <div class="w-1.5 h-1.5 rounded-full bg-premium-yellow group-hover/skill:scale-150 transition-transform shadow-sm shadow-premium-yellow/50"></div>
                                            <span>{{ skill.trim() }}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="md:pt-28 xl:pt-20 shrink-0 flex flex-col items-end gap-5">
                                    <div class="bg-slate-50/50 p-6 rounded-[2.5rem] border border-slate-100 shadow-sm space-y-4 min-w-[200px] hover:shadow-xl transition-all duration-500 group/rate">
                                        <div class="text-right space-y-1">
                                            <div class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 group-hover/rate:text-premium-brown transition-colors">Taux Horaire</div>
                                            <div class="flex items-baseline justify-end space-x-1">
                                                <span class="text-4xl font-black text-slate-900 font-mono tracking-tighter">{{ profileForm.hourly_rate }}</span>
                                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">DH/h</span>
                                            </div>
                                        </div>
                                        
                                        <div class="h-px bg-slate-100 w-full"></div>

                                        <div class="flex items-center justify-end space-x-3">
                                            <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse shadow-[0_0_10px_rgba(16,185,129,0.5)]"></div>
                                            <span class="text-[9px] font-black uppercase tracking-[0.15em] text-emerald-600">Disponibilité Validée</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Main View Content Grid (Fully Responsive Optimization) -->
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 xl:gap-20 mt-16 xl:mt-10">
                                <!-- Left Section: BIO & EXP -->
                                <div class="lg:col-span-7 xl:col-span-8 space-y-16 xl:space-y-10">
                                    <!-- Biographie Section -->
                                    <div class="space-y-6">
                                        <h4 class="text-xs font-black text-slate-900 uppercase tracking-[0.3em] flex items-center">
                                            <Fingerprint class="w-5 h-5 mr-3 text-premium-brown" />
                                            Parcours & Valeurs
                                        </h4>
                                        <p class="text-slate-600 font-medium leading-[2] text-lg max-w-3xl whitespace-pre-line">
                                            {{ profileForm.skills || "Aucune biographie détaillée n'a été configurée pour le moment." }}
                                        </p>
                                    </div>

                                    <!-- Expériences Progressions -->
                                    <div class="space-y-10">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-xs font-black text-slate-900 uppercase tracking-[0.3em] flex items-center">
                                                <div class="w-10 h-10 bg-premium-yellow/10 rounded-xl flex items-center justify-center mr-4">
                                                    <Trophy class="w-5 h-5 text-premium-brown" />
                                                </div>
                                                Expérience Professionnelle
                                            </h4>
                                            <span class="bg-slate-50 text-slate-400 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border border-slate-100">{{ profileForm.experience }}</span>
                                        </div>
                                        
                                        <div class="space-y-8 pl-6 border-l-2 border-slate-100 relative ml-5">
                                            <div v-for="(exp, idx) in profileForm.description" :key="idx" class="relative group">
                                                <!-- Pulse Point on Timeline -->
                                                <div class="absolute -left-[35px] top-8 w-5 h-5 rounded-full bg-white border-4 border-slate-200 transition-all group-hover:border-premium-yellow group-hover:scale-125 z-10 flex items-center justify-center">
                                                    <div class="w-1.5 h-1.5 rounded-full bg-slate-400 group-hover:bg-premium-yellow shadow-sm"></div>
                                                </div>

                                                 <div class="p-6 sm:p-8 xl:p-6 bg-white rounded-[2.5rem] border border-slate-100 group-hover:shadow-2xl group-hover:shadow-slate-200/50 group-hover:border-premium-yellow/20 transition-all duration-500 relative overflow-hidden">
                                                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                                                        <div class="space-y-1">
                                                            <div class="flex items-center space-x-3">
                                                                <span class="text-[9px] font-black text-premium-brown uppercase tracking-[0.2em] bg-premium-yellow/10 px-2 py-0.5 rounded-md">Réalisation {{ idx + 1 }}</span>
                                                            </div>
                                                            <div class="text-xl font-black text-slate-900 tracking-tight leading-tight group-hover:text-premium-brown transition-colors">
                                                                {{ exp.split('@')[0].trim() }}
                                                            </div>
                                                        </div>
                                                        <div v-if="exp.includes('@')" class="shrink-0">
                                                            <div class="bg-slate-900 text-white px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-slate-900/10 flex items-center space-x-2">
                                                                <Calendar class="w-3 h-3 text-premium-yellow" />
                                                                <span>{{ exp.split('@')[1].trim() }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <p class="text-slate-500 font-medium leading-relaxed text-sm">
                                                        {{ exp.split('@').length > 2 ? exp.split('@')[2].trim() : exp.split('@')[0].trim() }}
                                                    </p>

                                                    <!-- Background Glow -->
                                                    <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-premium-yellow/5 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                                                </div>
                                            </div>
                                            <div v-if="profileForm.description.length === 0" class="p-16 bg-slate-50/50 rounded-[3rem] text-slate-400 font-medium text-sm border-2 border-dashed border-slate-100 text-center flex flex-col items-center space-y-6 group cursor-pointer hover:border-premium-yellow/20 transition-all" @click="activeTab = 'profile'; cvMode = false; currentStep = 2">
                                                <div class="w-20 h-20 bg-white rounded-3xl flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                                                    <Plus class="w-10 h-10 text-slate-200" />
                                                </div>
                                                <div class="space-y-2">
                                                    <p class="font-black text-slate-900 uppercase tracking-widest text-[10px]">Aucune réalisation listée</p>
                                                    <p class="text-xs">Décrivez vos missions passées pour rassurer vos futurs clients.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                                
                                <!-- Right Section: Details & Availabilities -->
                                <div class="lg:col-span-5 xl:col-span-4 space-y-12 xl:space-y-8">
                                    <!-- Diplômes Grid-style (Moved for balance) -->
                                    <div class="space-y-6">
                                        <h4 class="text-xs font-black text-slate-900 uppercase tracking-[0.3em] flex items-center">
                                            <Award class="w-5 h-5 mr-3 text-premium-brown" />
                                            Garanties & Certifications
                                        </h4>
                                        <div class="bg-slate-900 p-8 xl:p-6 rounded-[3rem] border border-white/5 flex flex-col gap-6 xl:gap-4 shadow-2xl relative overflow-hidden group">
                                            <div class="flex items-center space-x-4 relative z-10">
                                                <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center">
                                                    <ShieldCheck class="w-6 h-6 text-premium-yellow" />
                                                </div>
                                                <div>
                                                    <div class="text-xs font-black text-white uppercase tracking-widest">{{ profileForm.diplomas || 'Non spécifié' }}</div>
                                                    <div class="text-[8px] font-black text-premium-yellow uppercase tracking-widest mt-1">Artisan Vérifié</div>
                                                </div>
                                            </div>
                                            <p class="text-[10px] text-slate-400 leading-relaxed font-medium relative z-10">
                                                Certification officielle garantissant l'expertise et le savoir-faire professionnel pour chaque intervention.
                                            </p>
                                            <!-- Decor -->
                                            <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-premium-yellow/5 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                                        </div>
                                    </div>
                                    <!-- Contact Info Card -->
                                    <div class="space-y-6">
                                        <h4 class="text-xs font-black text-slate-900 uppercase tracking-[0.3em]">Coordonnées Privées</h4>
                                        <div class="bg-white p-8 xl:p-6 rounded-[3rem] shadow-xl border border-slate-100 space-y-8 xl:space-y-5 relative overflow-hidden group">
                                            <div class="flex items-center space-x-5 group/item">
                                                <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center border border-slate-100 group-hover/item:border-premium-yellow/30 group-hover/item:bg-white transition-all duration-300">
                                                    <Smartphone class="w-5 h-5 text-premium-brown group-hover/item:scale-110 transition-transform" />
                                                </div>
                                                <div class="space-y-0.5">
                                                    <div class="text-[9px] uppercase tracking-widest text-slate-400 font-black">Téléphone</div>
                                                    <div class="text-sm font-black text-slate-900">{{ profileForm.phone || 'Non configuré' }}</div>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-5 group/item">
                                                <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center border border-slate-100 group-hover/item:border-premium-yellow/30 group-hover/item:bg-white transition-all duration-300">
                                                    <Mail class="w-5 h-5 text-premium-brown group-hover/item:scale-110 transition-transform" />
                                                </div>
                                                <div class="space-y-0.5 whitespace-nowrap overflow-hidden pr-4">
                                                    <div class="text-[9px] uppercase tracking-widest text-slate-400 font-black">Email Professionnel</div>
                                                    <div class="text-sm font-black text-slate-900 truncate">{{ auth.user?.email }}</div>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-5 group/item">
                                                <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center border border-slate-100 group-hover/item:border-premium-yellow/30 group-hover/item:bg-white transition-all duration-300">
                                                    <Fingerprint class="w-5 h-5 text-premium-brown group-hover/item:scale-110 transition-transform" />
                                                </div>
                                                <div class="space-y-0.5">
                                                    <div class="text-[9px] uppercase tracking-widest text-slate-400 font-black">N° Identité (CIN)</div>
                                                    <div class="text-sm font-black text-slate-900">{{ profileForm.cin || 'Non spécifié' }}</div>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-5 group/item">
                                                <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center border border-slate-100 group-hover/item:border-premium-yellow/30 group-hover/item:bg-white transition-all duration-300">
                                                    <MapPin class="w-5 h-5 text-premium-brown group-hover/item:scale-110 transition-transform" />
                                                </div>
                                                <div class="space-y-0.5">
                                                    <div class="text-[9px] uppercase tracking-widest text-slate-400 font-black">Adresse Résidentielle</div>
                                                    <div class="text-sm font-black text-slate-900 leading-tight">{{ profileForm.address || 'Non spécifiée' }}</div>
                                                </div>
                                            </div>
                                            
                                            <!-- Subtle Decor -->
                                            <div class="absolute -bottom-12 -right-12 w-32 h-32 bg-premium-yellow/5 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                                        </div>
                                    </div>

                                    <!-- Availabilities Grid -->
                                    <div class="space-y-6">
                                        <h4 class="text-xs font-black text-slate-900 uppercase tracking-[0.3em] flex items-center">
                                            <Clock class="w-5 h-5 mr-3 text-premium-brown" />
                                            Semaine de Travail
                                        </h4>
                                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-3">
                                            <div v-for="day in orderedDays" :key="day.key" 
                                                 class="p-5 xl:p-4 rounded-[2rem] border transition-all flex items-center justify-between group/day hover:-translate-y-0.5 duration-300"
                                                 :class="day.active ? 'bg-slate-900 border-slate-900 shadow-xl shadow-slate-900/10' : 'bg-slate-50 border-slate-100 opacity-60'"
                                            >
                                                <div class="flex items-center space-x-4">
                                                    <div class="w-10 h-10 rounded-2xl flex items-center justify-center transition-all duration-500" :class="day.active ? 'bg-premium-yellow text-slate-900 rotate-3 shadow-lg shadow-yellow-500/20' : 'bg-white text-slate-300'">
                                                        <Calendar class="w-5 h-5" />
                                                    </div>
                                                    <div>
                                                        <span class="block text-[10px] font-black uppercase tracking-widest leading-none mb-1.5" :class="day.active ? 'text-premium-yellow' : 'text-slate-400'">{{ day.label }}</span>
                                                        <div class="text-[11px] font-black tracking-tight" :class="day.active ? 'text-white' : 'text-slate-400'">
                                                            {{ day.active ? (day.start === '00:00' && day.end === '00:00' ? 'Sur demande' : `De ${day.start} à ${day.end}`) : 'Fermé' }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-if="day.active" class="flex items-center">
                                                    <div class="w-1.5 h-1.5 rounded-full bg-premium-yellow shadow-sm shadow-premium-yellow/50 animate-pulse"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Quick Stats Column -->
                                    <div class="p-8 bg-premium-yellow rounded-[3rem] shadow-2xl shadow-yellow-500/10 flex flex-col gap-6 relative overflow-hidden group">
                                         <div class="relative z-10 flex items-center justify-between">
                                             <div>
                                                 <div class="text-4xl font-black text-slate-900">{{ auth.user?.prestataire?.missions_count || 0 }}</div>
                                                 <div class="text-[10px] font-black uppercase tracking-widest text-slate-900/50">Missions Réussies</div>
                                             </div>
                                             <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center">
                                                 <TrendingUp class="w-8 h-8 text-slate-900" />
                                             </div>
                                         </div>
                                         <div class="relative z-10 p-4 bg-black/5 rounded-2xl border border-black/5">
                                             <p class="text-[9px] font-bold text-slate-900 leading-relaxed italic">
                                                 "Votre engagement et la qualité de vos services font monter votre score de pro."
                                             </p>
                                         </div>
                                         <!-- Decor -->
                                         <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-white/10 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- EDIT MODE WIZARD -->
                <div v-else class="space-y-10 animate-in fade-in duration-700">
                    <!-- Progress Indicator -->
                    <div class="max-w-3xl mx-auto space-y-4 mb-12">
                        <div class="flex items-end justify-between px-2">
                            <div class="space-y-1">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Étape {{ currentStep }} sur 3</p>
                                <h3 class="text-xl font-black text-slate-900 tracking-tight">
                                    {{ currentStep === 1 ? 'Identité Visuelle' : (currentStep === 2 ? 'Expertise & Expérience' : 'Vos Disponibilités') }}
                                </h3>
                            </div>
                            <span class="text-lg font-black text-premium-yellow">{{ Math.round((currentStep / 3) * 100) }}%</span>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden p-0.5">
                            <div 
                                class="h-full bg-premium-yellow rounded-full transition-all duration-700 ease-out shadow-lg"
                                :style="{ width: `${(currentStep / 3) * 100}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- STEP 1: Identité Visuelle -->
                    <div v-if="currentStep === 1" class="max-w-5xl mx-auto animate-in fade-in slide-in-from-bottom-4 duration-500">
                        <div class="bg-white p-12 rounded-[3.5rem] shadow-xl border border-slate-100 relative overflow-hidden">
                            <!-- Background Decor -->
                            <div class="absolute top-0 right-0 w-64 h-64 bg-premium-yellow/5 rounded-full blur-3xl -mr-32 -mt-32"></div>
                            
                            <div class="relative z-10 flex flex-col lg:flex-row gap-16">
                                <!-- Photo & Brand Side -->
                                <div class="lg:w-1/3 flex flex-col items-center space-y-8">
                                    <div class="relative group/photo">
                                        <div class="absolute -inset-4 bg-linear-to-tr from-premium-yellow/20 via-transparent to-premium-brown/20 rounded-[4rem] blur-2xl opacity-0 group-hover/photo:opacity-100 transition-opacity duration-700"></div>
                                        <PhotoUploader 
                                            :current-photo="auth.user?.prestataire?.photo_url" 
                                            @photo-updated="handlePhotoUpdate" 
                                            class="relative z-10"
                                        />
                                    </div>
                                    <div class="bg-slate-50 p-8 rounded-[2.5rem] border border-slate-100 space-y-4 w-full">
                                        <div class="flex items-center space-x-3 text-premium-brown">
                                            <ShieldCheck class="w-5 h-5" />
                                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-900">Conseil Pro</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 leading-relaxed font-medium">
                                            Un portrait soigné et professionnel multiplie par **3** vos chances d'être contacté par de nouveaux clients.
                                        </p>
                                    </div>
                                </div>

                                <!-- Form Fields Side -->
                                <div class="flex-1 space-y-10">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-1.5 h-10 bg-premium-yellow rounded-full"></div>
                                            <div>
                                                <h3 class="text-2xl font-black text-slate-900 tracking-tight">Identité Visuelle</h3>
                                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Étape cruciale de votre profil</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                                        <!-- Row 1: Identity -->
                                        <div class="space-y-2 group">
                                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 group-focus-within:text-premium-brown transition-colors">Prénom</label>
                                            <div class="relative">
                                                <User class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-premium-yellow transition-colors" />
                                                <input v-model="profileForm.first_name" type="text" class="w-full pl-14 pr-6 py-4 rounded-2xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-8 focus:ring-premium-yellow/5 focus:border-premium-yellow outline-none transition-all font-black text-slate-900 shadow-sm text-sm">
                                            </div>
                                        </div>
                                        <div class="space-y-2 group">
                                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 group-focus-within:text-premium-brown transition-colors">Nom</label>
                                            <div class="relative">
                                                <Fingerprint class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-premium-yellow transition-colors" />
                                                <input v-model="profileForm.last_name" type="text" class="w-full pl-14 pr-6 py-4 rounded-2xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-8 focus:ring-premium-yellow/5 focus:border-premium-yellow outline-none transition-all font-black text-slate-900 shadow-sm text-sm">
                                            </div>
                                        </div>

                                        <!-- Row 2: Location & Birth -->
                                        <div class="space-y-2 group">
                                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Ville de résidence</label>
                                            <div class="relative" id="city-dropdown-container">
                                                <!-- Custom Select Trigger -->
                                                <div 
                                                    @click="showCityDropdown = !showCityDropdown"
                                                    class="w-full pl-14 pr-6 py-4 rounded-2xl border border-slate-100 bg-slate-50 focus-within:bg-white focus-within:ring-8 focus-within:ring-premium-yellow/5 focus-within:border-premium-yellow outline-none transition-all font-black text-slate-900 shadow-sm text-sm cursor-pointer flex items-center justify-between"
                                                >
                                                    <MapPin class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-premium-yellow transition-colors" />
                                                    <span :class="profileForm.city ? 'text-slate-900' : 'text-slate-400'">
                                                        {{ profileForm.city || 'Sélectionnez votre ville' }}
                                                    </span>
                                                    <ChevronDown 
                                                        class="w-4 h-4 text-slate-400 transition-transform duration-300"
                                                        :class="{ 'rotate-180': showCityDropdown }"
                                                    />
                                                </div>

                                                <!-- Custom Dropdown Menu -->
                                                <div 
                                                    v-if="showCityDropdown"
                                                    class="absolute top-full left-0 right-0 mt-2 bg-white rounded-3xl shadow-2xl border border-slate-100 z-[100] overflow-hidden animate-in fade-in slide-in-from-top-2 duration-300"
                                                >
                                                    <div class="p-4 border-b border-slate-50">
                                                        <div class="relative">
                                                            <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
                                                            <input 
                                                                v-model="cityQuery"
                                                                type="text"
                                                                placeholder="Rechercher une ville..."
                                                                class="w-full pl-10 pr-4 py-3 bg-slate-50 rounded-xl text-xs font-bold text-slate-900 outline-none border border-transparent focus:border-premium-yellow/30 transition-all"
                                                                @click.stop
                                                            >
                                                        </div>
                                                    </div>
                                                    <div class="max-h-60 overflow-y-auto custom-scrollbar">
                                                        <div 
                                                            v-for="city in filteredCities" 
                                                            :key="city"
                                                            @click="selectCity(city)"
                                                            class="px-6 py-3.5 text-sm font-bold text-slate-600 hover:text-premium-brown hover:bg-premium-yellow/5 cursor-pointer transition-colors flex items-center justify-between group/item"
                                                        >
                                                            {{ city }}
                                                            <Check v-if="profileForm.city === city" class="w-4 h-4 text-premium-yellow" />
                                                        </div>
                                                        <div v-if="filteredCities.length === 0" class="px-6 py-8 text-center">
                                                            <p class="text-xs font-bold text-slate-400">Aucune ville trouvée</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="space-y-2 group">
                                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Date de naissance</label>
                                            <div class="relative">
                                                <Calendar class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-premium-yellow transition-colors" />
                                                <input v-model="profileForm.birth_date" type="date" class="w-full pl-14 pr-6 py-4 rounded-2xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-8 focus:ring-premium-yellow/5 focus:border-premium-yellow outline-none transition-all font-black text-slate-900 shadow-sm text-sm">
                                            </div>
                                        </div>

                                        <!-- Row 3: ID & Phone -->
                                        <div class="space-y-2 group">
                                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">CIN</label>
                                            <div class="relative">
                                                <Award class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-premium-yellow transition-colors" />
                                                <input v-model="profileForm.cin" type="text" class="w-full pl-14 pr-6 py-4 rounded-2xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-8 focus:ring-premium-yellow/5 focus:border-premium-yellow outline-none transition-all font-black text-slate-900 shadow-sm text-sm" placeholder="Ex: AB123456">
                                            </div>
                                        </div>
                                        <div class="space-y-2 group">
                                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Téléphone</label>
                                            <div class="relative">
                                                <Smartphone class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-premium-yellow transition-colors" />
                                                <input v-model="profileForm.phone" type="text" class="w-full pl-14 pr-6 py-4 rounded-2xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-8 focus:ring-premium-yellow/5 focus:border-premium-yellow outline-none transition-all font-black text-slate-900 shadow-sm text-sm">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Email (Locked) -->
                                    <div class="pt-6 border-t border-slate-50">
                                        <div class="flex items-center space-x-4 bg-slate-50/50 p-6 rounded-3xl border border-slate-100">
                                            <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center shadow-sm text-slate-300">
                                                <Mail class="w-6 h-6" />
                                            </div>
                                            <div class="grow">
                                                <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Compte vérifié</p>
                                                <p class="text-sm font-black text-slate-700">{{ auth.user?.email }}</p>
                                            </div>
                                            <div class="p-2 bg-emerald-50 text-emerald-500 rounded-xl">
                                                <CheckCircle class="w-5 h-5" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: Expertise & Expérience -->
                    <div v-if="currentStep === 2" class="animate-in fade-in slide-in-from-bottom-4 duration-500">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Categories Card -->
                            <div class="bg-white p-10 rounded-[3rem] shadow-xl border border-slate-100 flex flex-col h-[700px]">
                                <div class="flex items-center justify-between mb-8">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 bg-premium-yellow/10 rounded-2xl flex items-center justify-center border border-premium-yellow/20">
                                            <Briefcase class="w-6 h-6 text-premium-brown" />
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-black text-slate-900 tracking-tight">Expertise Pro</h3>
                                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mt-1">Choisissez vos spécialités</p>
                                        </div>
                                    </div>
                                    <div v-if="selectedCategoriesCount > 0" class="flex flex-col items-end">
                                        <span class="text-[10px] font-black bg-slate-900 text-white px-4 py-2 rounded-xl uppercase tracking-widest shadow-lg shadow-slate-900/10">{{ selectedCategoriesCount }} service{{ selectedCategoriesCount > 1 ? 's' : '' }}</span>
                                    </div>
                                </div>

                                <div class="relative group mb-6">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <Search class="h-4 w-4 text-slate-300 group-focus-within:text-premium-yellow transition-colors" />
                                    </div>
                                    <input 
                                        v-model="categorySearch" 
                                        type="text" 
                                        placeholder="Que savez-vous faire ?"
                                        class="w-full pl-12 pr-6 py-4 rounded-2xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-8 focus:ring-premium-yellow/5 focus:border-premium-yellow outline-none transition-all font-bold text-xs shadow-inner"
                                    >
                                </div>

                                <div class="flex-1 overflow-y-auto pr-2 space-y-8 scrollbar-hide py-2">
                                    <div v-for="group in filteredCategoryGroups" :key="group.name" class="space-y-4">
                                        <div class="flex items-center space-x-3 sticky top-0 bg-white/90 backdrop-blur-sm z-10 py-2">
                                            <div class="h-px bg-slate-100 grow"></div>
                                            <h4 class="text-[9px] font-black text-slate-400 uppercase tracking-[0.3em] px-3">{{ group.name }}</h4>
                                            <div class="h-px bg-slate-100 grow"></div>
                                        </div>
                                        <div class="grid grid-cols-1 gap-2.5">
                                            <label 
                                                v-for="cat in group.categories" 
                                                :key="cat.id"
                                                class="flex items-center space-x-4 p-4 rounded-2xl border transition-all cursor-pointer group/cat relative overflow-hidden"
                                                :class="profileForm.category_ids.includes(cat.id) ? 'bg-premium-yellow/5 border-premium-yellow/30 shadow-sm' : 'bg-slate-50 border-transparent hover:bg-slate-100 hover:border-slate-200'"
                                            >
                                                <div class="relative z-10 flex items-center justify-center w-5 h-5 rounded-md border-2 transition-all"
                                                     :class="profileForm.category_ids.includes(cat.id) ? 'bg-premium-yellow border-premium-yellow scale-110 shadow-lg shadow-yellow-500/20' : 'bg-white border-slate-200 group-hover/cat:border-slate-300'"
                                                >
                                                    <Check v-if="profileForm.category_ids.includes(cat.id)" class="w-3.5 h-3.5 text-slate-900" />
                                                </div>
                                                <input type="checkbox" :value="cat.id" v-model="profileForm.category_ids" class="hidden">
                                                <span class="relative z-10 text-[11px] font-bold text-slate-700 transition-colors group-hover/cat:text-slate-900">{{ cat.name }}</span>
                                                
                                                <!-- Subtle bg glow on hover -->
                                                <div class="absolute right-0 top-0 w-32 h-full bg-linear-to-l from-white/20 to-transparent translate-x-32 group-hover/cat:translate-x-0 transition-transform duration-500"></div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Experience & Bio Card -->
                            <div class="bg-white p-10 rounded-[3rem] shadow-xl border border-slate-100 space-y-10 flex flex-col h-[700px]">
                                <div class="flex items-center space-x-4">
                                    <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center border border-amber-100 shadow-sm">
                                        <Trophy class="w-6 h-6 text-amber-500" />
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-black text-slate-900 tracking-tight">Parcours & Bio</h3>
                                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mt-1">Racontez votre histoire</p>
                                    </div>
                                </div>

                                <div class="space-y-8 flex-1 overflow-y-auto pr-2 scrollbar-hide">
                                    <!-- Years of Experience Selector -->
                                    <div class="space-y-4">
                                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Expérience globale</label>
                                        <div class="grid grid-cols-2 gap-3">
                                            <button 
                                                v-for="opt in [
                                                    { value: 'Débutant', label: 'Débutant' },
                                                    { value: 'Entre 1 an et 2 ans', label: '1 - 2 ans' },
                                                    { value: '2 à 4 ans', label: '2 - 4 ans' },
                                                    { value: 'Plus de 4 ans', label: 'Plus de 4 ans' }
                                                ]"
                                                :key="opt.value"
                                                @click="profileForm.experience = opt.value"
                                                class="px-4 py-5 rounded-[1.5rem] font-black text-[10px] uppercase tracking-wider transition-all relative overflow-hidden group/opt flex items-center justify-center text-center"
                                                :class="profileForm.experience === opt.value ? 'bg-slate-900 text-white shadow-2xl scale-[1.02] z-10' : 'bg-slate-50 text-slate-500 border border-slate-100 hover:bg-white hover:shadow-lg'"
                                            >
                                                <span class="relative z-10">{{ opt.label }}</span>
                                                <div v-if="profileForm.experience === opt.value" class="absolute inset-0 bg-linear-to-br from-white/10 to-transparent"></div>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Notable Experiences List -->
                                    <div class="space-y-5 pt-4">
                                        <div class="flex items-center justify-between px-2">
                                            <div class="space-y-1">
                                                <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Expériences marquantes</label>
                                                <p class="text-[8px] text-slate-400 font-medium">Ajoutez vos références clés</p>
                                            </div>
                                            <button @click="addDetailedExperience" class="w-10 h-10 bg-slate-900 text-white rounded-2xl hover:bg-premium-yellow hover:text-slate-900 transition-all shadow-xl flex items-center justify-center active:scale-90">
                                                <Plus class="w-5 h-5" />
                                            </button>
                                        </div>
                                        <div class="space-y-3">
                                            <div v-for="(exp, index) in profileForm.description" :key="index" class="relative group/item animate-in slide-in-from-right-2 duration-300">
                                                <div class="absolute left-4 top-1/2 -translate-y-1/2 w-1.5 h-1.5 rounded-full bg-premium-yellow"></div>
                                                <input 
                                                    v-model="profileForm.description[index]" 
                                                    type="text" 
                                                    placeholder="Ex: Rénovation Maison Casablanca..." 
                                                    class="w-full pl-10 pr-12 py-4 rounded-2xl border border-slate-100 bg-slate-50/50 focus:bg-white focus:ring-4 focus:ring-premium-yellow/10 focus:border-premium-yellow outline-none transition-all text-xs font-black text-slate-800 shadow-sm"
                                                >
                                                <button @click="removeDetailedExperience(index)" class="absolute right-3 top-1/2 -translate-y-1/2 p-2 text-slate-300 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all opacity-0 group-hover/item:opacity-100">
                                                    <X class="w-4 h-4" />
                                                </button>
                                            </div>
                                            <div v-if="profileForm.description.length === 0" class="py-12 bg-slate-50/50 rounded-[2rem] border-2 border-dashed border-slate-100 flex flex-col items-center justify-center space-y-3 group cursor-pointer hover:border-premium-yellow/30 transition-colors" @click="addDetailedExperience">
                                                <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center shadow-sm text-slate-300 group-hover:scale-110 transition-transform">
                                                    <Zap class="w-6 h-6" />
                                                </div>
                                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Aucune expérience ajoutée</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bio Textarea -->
                                    <div class="space-y-4 pt-4">
                                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Bio Professionnelle</label>
                                        <div class="relative group">
                                            <textarea 
                                                v-model="profileForm.skills" 
                                                rows="4" 
                                                placeholder="Partagez votre passion et votre rigueur..." 
                                                class="w-full px-8 py-6 rounded-[2rem] border border-slate-100 bg-slate-50 focus:bg-white focus:ring-8 focus:ring-premium-yellow/5 focus:border-premium-yellow outline-none transition-all font-bold text-xs resize-none shadow-inner leading-relaxed"
                                            ></textarea>
                                            <div class="absolute bottom-4 right-6 text-[8px] font-black text-slate-300 uppercase tracking-widest">Conseil: Soyez précis</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: Disponibilités -->
                    <div v-if="currentStep === 3" class="max-w-4xl mx-auto animate-in fade-in slide-in-from-bottom-4 duration-500">
                        <div class="bg-white p-10 rounded-4xl shadow-sm border border-slate-100 space-y-10">
                            <div class="flex items-center space-x-4">
                                <div class="w-1.5 h-8 bg-premium-yellow rounded-full"></div>
                                <h3 class="text-xl font-black text-slate-900 tracking-tight">Vos Disponibilités</h3>
                            </div>

                            <AvailabilityScheduler 
                                :initial-availability="profileForm.availabilities"
                                @update="handleAvailabilityUpdate"
                            />
                        </div>
                    </div>

                    <!-- WIZARD NAVIGATION -->
                    <div class="max-w-4xl mx-auto flex items-center justify-between pt-10 border-t border-slate-100">
                        <button 
                            v-if="currentStep > 1"
                            @click="prevStep" 
                            class="flex items-center space-x-3 px-8 py-4 rounded-3xl border-2 border-slate-100 text-slate-900 font-black text-xs uppercase tracking-widest transition-all hover:border-slate-900 active:scale-95"
                        >
                            <ChevronLeft class="w-5 h-5" />
                            <span>Précédent</span>
                        </button>
                        <div v-else></div>

                        <div class="flex items-center space-x-4">
                            <button 
                                v-if="currentStep < 3"
                                @click="nextStep" 
                                class="flex items-center space-x-3 bg-premium-yellow text-slate-900 px-10 py-4 rounded-3xl font-black text-xs uppercase tracking-widest transition-all hover:bg-yellow-400 hover:shadow-xl active:scale-95"
                            >
                                <span>Suivant</span>
                                <ChevronRight class="w-5 h-5" />
                            </button>
                            <button 
                                v-else
                                @click="saveProfile" 
                                :disabled="saving"
                                class="flex items-center space-x-3 bg-premium-yellow text-slate-900 px-10 py-4 rounded-3xl font-black text-xs uppercase tracking-widest transition-all hover:bg-yellow-400 hover:shadow-xl active:scale-95 disabled:opacity-50"
                            >
                                <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
                                <Award v-else class="w-5 h-5" />
                                <span>{{ saving ? 'Enregistrement...' : 'Terminer' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: SETTINGS -->
            <div v-show="activeTab === 'settings'" class="space-y-8 animate-in fade-in slide-in-from-bottom-5 duration-700 max-w-2xl mx-auto">
                <div class="bg-white p-10 rounded-[3rem] shadow-sm border border-slate-100 space-y-8">
                    <div class="flex items-center space-x-4">
                        <div class="w-1.5 h-8 bg-blue-500 rounded-full"></div>
                        <h3 class="text-xl font-black text-slate-900 tracking-tight">Sécurité & Visibilité</h3>
                    </div>

                    <div class="flex items-center justify-between p-6 bg-slate-50 rounded-3xl border border-slate-100">
                        <div class="space-y-1">
                            <h4 class="text-sm font-black text-slate-900">Visibilité globale</h4>
                            <p class="text-[10px] text-slate-500 font-medium">Si désactivé, vous ne recevrez plus de propositions.</p>
                        </div>
                        <button 
                            @click="toggleVisibility"
                            :class="visibility ? 'bg-slate-900 text-white shadow-xl shadow-slate-900/20' : 'bg-white text-slate-300 border border-slate-100'"
                            class="px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all active:scale-95 flex items-center space-x-2"
                        >
                            <Power class="w-3.5 h-3.5" />
                            <span>{{ visibility ? 'Publié' : 'Brouillon' }}</span>
                        </button>
                    </div>

                    <div class="p-6 bg-red-50 rounded-3xl border border-red-100 space-y-6">
                        <div class="space-y-1">
                            <h4 class="text-sm font-black text-red-900">Zone de danger</h4>
                            <p class="text-[10px] text-red-600/60 font-medium">La suppression de votre compte est irréversible et effacera tout votre historique.</p>
                        </div>
                        <button @click="deleteAccount" class="w-full py-4 text-center border-2 border-red-200 text-red-600 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-red-600 hover:text-white hover:border-red-600 transition-all active:scale-95">
                            Supprimer mon compte définitivement
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
