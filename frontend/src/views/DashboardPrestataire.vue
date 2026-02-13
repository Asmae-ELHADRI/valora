<script setup>
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import { 
  User, Mail, Phone, MapPin, Briefcase, Calendar, Clock, 
  Camera, CheckCircle, AlertCircle, Loader2, Edit3, Save, X, Fingerprint, Coins,
  ChevronDown, ArrowRight, Award, LifeBuoy, Star, ShieldCheck, Zap, 
  Trophy, TrendingUp, Users, Check, ExternalLink
} from 'lucide-vue-next';
import PhotoUploader from '../components/PhotoUploader.vue';

const auth = useAuthStore();
const loading = ref(true);
const saving = ref(false);
const error = ref(null);
const success = ref(null);

// mode = 'view' | 'create' | 'edit'
const mode = ref('view');
const profileExists = ref(false);
const categories = ref([]);

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
    date: '',
    time: ''
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

const fetchProfile = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.get('/api/provider/profile');
    if (response.data.exists) {
      profileExists.value = true;
      const user = response.data.user;
      
      // Extract category names if available as relation, or fetch them if only IDs
      const userCategories = user.prestataire?.categories || [];
      
      profileData.value = {
        missions_count: user.prestataire?.missions_count || 0,
        first_name: user.first_name || '',
        last_name: user.last_name || '',
        phone: user.phone || '',
        birth_date: user.prestataire?.birth_date ? user.prestataire.birth_date.substring(0, 10) : '',
        diplomas: user.prestataire?.diplomas || '',
        cin: user.prestataire?.cin || '',
        address: user.address || '',
        city: user.prestataire?.city || '',
        description: user.prestataire?.description || '',
        skills: user.prestataire?.skills || '',
        experience: user.prestataire?.experience || '',
        hourly_rate: user.prestataire?.hourly_rate || '',
        category_ids: userCategories.map(c => c.id), // Store IDs
        categories: userCategories, // Store full objects for display
        availabilities: user.prestataire?.availabilities || { 
            monday: false, tuesday: false, wednesday: false, thursday: false, friday: false, saturday: false, sunday: false,
            date: '', time: '' 
        }
      };
      mode.value = 'view';
    } else {
      profileExists.value = false;
      mode.value = 'view';
    }
  } catch (err) {
    error.value = "Impossible de charger le profil.";
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const handleSaveProfile = async () => {
  saving.value = true;
  error.value = null;
  success.value = null;
  try {
    const method = mode.value === 'create' ? 'post' : 'put';
    // Use category_ids for saving
    const response = await api[method]('/api/provider/profile', {
      ...profileData.value,
      category_ids: profileData.value.category_ids
    });
    
    await api.post('/api/provider/availability', { 
        availabilities: profileData.value.availabilities 
    });

    success.value = mode.value === 'create' ? "Profil créé avec succès !" : "Profil mis à jour !";
    await fetchProfile();
  } catch (err) {
    error.value = "Erreur lors de l'enregistrement.";
    console.error(err);
  } finally {
    saving.value = false;
  }
};

const startCreate = () => {
  mode.value = 'create';
  profileData.value = { ...profileData.value, category_ids: [] }; 
};

// ... existing code ...

const currentCategoryNames = computed(() => {
    // If we have full category objects loaded
    if (profileData.value.categories && profileData.value.categories.length > 0) {
        return profileData.value.categories.map(c => c.name).join(', ');
    }
    // Fallback: find names from global categories list using IDs
    if (profileData.value.category_ids && profileData.value.category_ids.length > 0) {
        return categories.value
            .filter(c => profileData.value.category_ids.includes(c.id))
            .map(c => c.name)
            .join(', ');
    }
    return 'Non définie';
});

// ... existing code ...



const startEdit = () => {
  mode.value = 'edit';
};

const cancelEdit = () => {
  if (profileExists.value) {
    mode.value = 'view';
    fetchProfile(); // Reset data
  } else {
    mode.value = 'view';
  }
};

onMounted(() => {
  fetchProfile();
  fetchCategories();
});

const validateAndSubmit = () => {
    const d = profileData.value;
    const requiredFields = [
        { key: 'first_name', label: 'Prénom' },
        { key: 'last_name', label: 'Nom' },
        { key: 'phone', label: 'Téléphone' },
        { key: 'city', label: 'Ville' },
        { key: 'address', label: 'Adresse' },
        { key: 'birth_date', label: 'Date de naissance' },
        { key: 'cin', label: 'CIN' },
        { key: 'description', label: 'Biographie' },
        { key: 'category_id', label: 'Domaine d\'exercice' },
        { key: 'experience', label: 'Années d\'expérience' },
        { key: 'hourly_rate', label: 'Taux horaire' }
    ];

    const missing = requiredFields.filter(f => !d[f.key]);
    
    if (missing.length > 0) {
        error.value = `Veuillez remplir les champs obligatoires : ${missing.map(f => f.label).join(', ')}`;
        // Scroll to top to see error
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }

    handleSaveProfile();
};

// ...



const handlePhotoUpdate = (newUrl) => {
    // Profil image is usually cached or we can force reload user data
    auth.fetchUser();
};

const currentPhotoUrl = computed(() => {
    return auth.user?.prestataire?.photo_url;
});

const userFullName = computed(() => {
  return `${profileData.value.first_name} ${profileData.value.last_name}`.trim() || auth.user?.name;
});

const currentBadge = computed(() => {
    // Logic: Bronze (< 5 missions) -> Gold (5-19 missions) -> Premium (20+ missions)
    const count = profileData.value.missions_count || 0;
    
    if (count >= 20) {
        return { 
            name: 'Premium', 
            color: 'text-purple-600', 
            bg: 'bg-purple-50', 
            border: 'border-purple-200',
            icon: Zap 
        };
    } else if (count >= 5) {
        return { 
            name: 'Or', 
            color: 'text-yellow-600', 
            bg: 'bg-yellow-50', 
            border: 'border-yellow-200',
            icon: Star 
        };
    } else {
        return { 
            name: 'Bronze', 
            color: 'text-amber-700', 
            bg: 'bg-amber-50', 
            border: 'border-amber-200',
            icon: ShieldCheck 
        };
    }
});
</script>

<template>

  <div class="min-h-screen bg-[#F8FAFC] font-outfit pb-20">
    <!-- Decorative Background Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-[10%] -right-[10%] w-[50%] h-[50%] bg-premium-yellow/5 rounded-full blur-[120px]"></div>
        <div class="absolute top-[20%] -left-[5%] w-[40%] h-[40%] bg-slate-900/5 rounded-full blur-[100px]"></div>
        <div class="absolute bottom-[10%] right-[10%] w-[30%] h-[30%] bg-premium-brown/5 rounded-full blur-[80px]"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 space-y-10 relative">
      <!-- Success Message -->
      <div v-if="success" class="bg-premium-yellow text-slate-900 px-8 py-4 rounded-2xl font-black text-sm flex items-center shadow-2xl animate-in zoom-in-50 duration-500">
          <CheckCircle class="w-5 h-5 mr-3" />
          <span>{{ success }}</span>
      </div>

      <!-- Error Message -->
      <div v-if="error" class="bg-red-500 text-white px-8 py-4 rounded-2xl font-black text-sm flex items-center shadow-2xl animate-in zoom-in-50 duration-500">
          <AlertCircle class="w-5 h-5 mr-3" />
          <span>{{ error }}</span>
      </div>

      <!-- State: Loading -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-32 bg-white rounded-[3rem] border border-slate-100 shadow-sm relative overflow-hidden">
        <Loader2 class="w-20 h-20 text-slate-900 animate-spin mb-6" />
        <p class="text-slate-400 font-black uppercase tracking-[0.3em] text-[10px]">Chargement de votre univers...</p>
        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-slate-50/30 to-transparent animate-pulse"></div>
      </div>

      <!-- State: No Profile & Mode View -->
      <div v-else-if="!profileExists && mode === 'view'" class="bg-white rounded-[4rem] p-16 md:p-24 text-center border border-slate-100 shadow-2xl relative overflow-hidden group">
        <div class="relative z-10 space-y-10">
          <div class="relative inline-block">
              <div class="w-32 h-32 bg-slate-50 rounded-[3rem] flex items-center justify-center mx-auto transform group-hover:rotate-12 transition-transform duration-700">
                <Fingerprint class="w-16 h-16 text-slate-900" />
              </div>
              <div class="absolute -top-4 -right-4 w-12 h-12 bg-premium-yellow rounded-2xl flex items-center justify-center shadow-xl shadow-premium-yellow/20 animate-bounce">
                  <Award class="w-6 h-6 text-slate-900" />
              </div>
          </div>

          <div class="space-y-4">
              <h2 class="text-3xl md:text-4xl font-black text-slate-900 tracking-tight">Identité non configurée</h2>
              <p class="text-slate-500 max-w-lg mx-auto text-lg font-medium leading-relaxed">
                Pour commencer à recevoir des offres et être visible par les clients, vous devez configurer votre vitrine professionnelle.
              </p>
          </div>

          <button @click="startCreate" class="group bg-slate-900 text-white px-12 py-6 rounded-[2.5rem] font-black text-xs uppercase tracking-[0.3em] shadow-2xl shadow-slate-900/30 hover:bg-premium-yellow hover:text-slate-900 transition-all active:scale-95 flex items-center mx-auto space-x-4">
            <span>Créer mon profil maintenant</span>
            <ArrowRight class="w-5 h-5 group-hover:translate-x-2 transition-transform" />
          </button>
        </div>
        
        <!-- Background Accents -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-slate-50 rounded-full -translate-y-1/2 translate-x-1/2"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-premium-yellow/5 rounded-full translate-y-1/2 -translate-x-1/2 blur-3xl"></div>
      </div>


      <!-- Main Content Grid -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
        
        <!-- Left/Top: Profile Summary Card (View Mode - Premium Redesign) -->
        <div v-if="mode === 'view' && profileExists" class="lg:col-span-12 space-y-8 animate-in slide-in-from-bottom-5 duration-700">
          
          <!-- Hero Profile Card -->
          <div class="bg-white rounded-[3rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden relative">
            
            <!-- Header matching Profile.vue Step 1 -->
            <div class="flex items-center space-x-3 p-10 pb-0 relative z-20">
                <div class="p-3 bg-premium-yellow/10 rounded-2xl">
                    <User class="w-6 h-6 text-premium-brown" />
                </div>
                <h3 class="font-black text-2xl text-slate-900 tracking-tight">Profil & Identité</h3>
            </div>

            <!-- Cover Background -->
            <div class="h-48 bg-slate-900 relative overflow-hidden mt-6">
                <div class="absolute inset-0 bg-gradient-to-r from-slate-900 to-slate-800"></div>
                <div class="absolute top-0 right-0 w-96 h-96 bg-premium-yellow/10 rounded-full blur-3xl -mr-20 -mt-20"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-premium-brown/20 rounded-full blur-3xl -ml-10 -mb-10"></div>
                
                <div class="absolute top-8 right-8 flex gap-3">
                     <button @click="startEdit" class="bg-white/10 backdrop-blur-md text-white px-6 py-2.5 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-white hover:text-slate-900 transition-all border border-white/10 flex items-center gap-2 group">
                        <Edit3 class="w-4 h-4 group-hover:-rotate-12 transition-transform" />
                        <span>Modifier</span>
                    </button>
                </div>
            </div>

            <div class="px-10 md:px-14 pb-10">
                <div class="flex flex-col md:flex-row gap-8 items-start relative -mt-20">
                    <!-- Avatar Orb -->
                     <div class="relative">
                        <div class="w-40 h-40 rounded-[2.5rem] bg-slate-50 border-4 border-white shadow-2xl overflow-hidden flex items-center justify-center relative z-10">
                            <img v-if="currentPhotoUrl" :src="currentPhotoUrl" class="w-full h-full object-cover">
                            <User v-else class="w-16 h-16 text-slate-300" />
                        </div>
                        <div class="absolute -bottom-3 -right-3 p-2.5 rounded-xl shadow-lg border-4 border-white z-20 flex items-center justify-center transition-all duration-500" :class="currentBadge.bg" :title="`Badge ${currentBadge.name}`">
                            <component :is="currentBadge.icon" class="w-6 h-6" :class="currentBadge.color" />
                        </div>
                     </div>

                     <!-- Name & badges -->
                     <div class="md:pt-24 space-y-2 grow">
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-4xl font-black text-slate-900 tracking-tight">{{ userFullName }}</h2>
                            <div class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border" :class="[currentBadge.bg, currentBadge.border, currentBadge.color]">
                                {{ currentBadge.name }}
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-4 text-sm font-medium text-slate-500">
                            <span class="flex items-center text-slate-900 font-bold">
                                <Briefcase class="w-4 h-4 mr-1.5 text-premium-brown" />
                                {{ currentCategoryNames }}
                            </span>
                            <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                            <span class="flex items-center">
                                <MapPin class="w-4 h-4 mr-1.5 text-slate-400" />
                                {{ profileData.city || 'Non localisé' }}
                            </span>
                        </div>
                     </div>

                     <!-- Stats (Right aligned) -->
                     <div class="md:pt-24 flex gap-4 hidden md:flex">
                        <div class="text-center px-6 py-3 bg-slate-50 rounded-2xl border border-slate-100">
                            <div class="text-2xl font-black text-slate-900">{{ profileData.missions_count }}</div>
                            <div class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Missions</div>
                        </div>
                         <div class="text-center px-6 py-3 bg-slate-50 rounded-2xl border border-slate-100">
                            <div class="text-2xl font-black text-slate-900 flex items-center justify-center gap-1">{{ parseFloat(profileData.rating || 4.9).toFixed(1) }} <Star class="w-4 h-4 text-yellow-400 fill-current" /></div>
                            <div class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Note</div>
                        </div>
                     </div>
                </div>

                <!-- Main Grid Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 mt-12">
                     <!-- Left Sidebar -->
                     <div class="space-y-8">
                         <!-- About -->
                        <div class="space-y-4">
                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest flex items-center">
                                <User class="w-4 h-4 mr-2" /> À propos
                            </h4>
                             <p class="text-slate-600 leading-relaxed font-medium text-sm">
                                {{ profileData.description || "Aucune description pour le moment." }}
                             </p>
                        </div>

                        <!-- Skills -->
                        <div class="space-y-4">
                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest flex items-center">
                                <Zap class="w-4 h-4 mr-2" /> Compétences
                            </h4>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="skill in (profileData.skills ? profileData.skills.split(',') : [])" :key="skill" class="px-3 py-1.5 bg-slate-50 border border-slate-100 rounded-lg text-[10px] font-black uppercase tracking-wider text-slate-700">
                                    {{ skill.trim() }}
                                </span>
                                <span v-if="!profileData.skills" class="text-sm text-slate-400 italic">Non spécifié</span>
                            </div>
                        </div>

                        <!-- Contact / Info -->
                        <div class="p-6 bg-slate-900 rounded-3xl text-white space-y-6 relative overflow-hidden">
                             <div class="relative z-10 space-y-4">
                                 <div>
                                     <div class="text-[10px] uppercase tracking-widest text-slate-400 mb-1">Email</div>
                                     <div class="font-bold truncate">{{ auth.user?.email }}</div>
                                 </div>
                                 <div>
                                     <div class="text-[10px] uppercase tracking-widest text-slate-400 mb-1">Téléphone</div>
                                     <div class="font-bold">{{ profileData.phone }}</div>
                                 </div>
                                 <div>
                                     <div class="text-[10px] uppercase tracking-widest text-slate-400 mb-1">Ville</div>
                                     <div class="font-bold">{{ profileData.city || 'Non renseignée' }}</div>
                                 </div>
                                 <div>
                                     <div class="text-[10px] uppercase tracking-widest text-slate-400 mb-1">Adresse</div>
                                     <div class="font-bold">{{ profileData.address || 'Non renseignée' }}</div>
                                 </div>
                                 <div class="grid grid-cols-2 gap-4">
                                     <div>
                                         <div class="text-[10px] uppercase tracking-widest text-slate-400 mb-1">D. Naissance</div>
                                         <div class="font-bold">{{ profileData.birth_date || '-' }}</div>
                                     </div>
                                     <div>
                                         <div class="text-[10px] uppercase tracking-widest text-slate-400 mb-1">CIN</div>
                                         <div class="font-bold">{{ profileData.cin || '-' }}</div>
                                     </div>
                                 </div>
                                 <div>
                                     <div class="text-[10px] uppercase tracking-widest text-slate-400 mb-1">Type d'emploi</div>
                                     <div class="flex items-center gap-2 text-premium-yellow font-bold">
                                         <Clock class="w-4 h-4" />
                                         {{ profileData.availabilities.time || 'Non définie' }}
                                     </div>
                                 </div>
                             </div>
                             <!-- Decor -->
                             <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full blur-2xl -mr-16 -mt-16"></div>
                        </div>
                     </div>

                     <!-- Right Content -->
                     <div class="lg:col-span-2 space-y-10">
                        
                        <!-- Experience & Diplomas -->
                        <div class="bg-slate-50 rounded-[2.5rem] p-8 space-y-8 border border-slate-100">
                             <!-- Experience -->
                             <div>
                                <h3 class="font-black text-xl text-slate-900 mb-6 flex items-center">
                                    <Trophy class="w-5 h-5 mr-3 text-premium-brown" />
                                    Expériences
                                </h3>
                                <div class="pl-4 border-l-2 border-slate-200 space-y-8">
                                    <!-- Parse experience string into list if possible, otherwise show plain text -->
                                    <div class="relative">
                                        <div class="absolute -left-[21px] top-1.5 w-3 h-3 rounded-full bg-slate-900 ring-4 ring-white"></div>
                                        <div class="space-y-1">
                                            <div class="font-bold text-slate-900">Parcours Professionnel</div>
                                            <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-line">{{ profileData.experience || 'Non renseigné' }}</p>
                                        </div>
                                    </div>
                                </div>
                             </div>

                             <!-- Diplomas -->
                             <div v-if="profileData.diplomas">
                                <h3 class="font-black text-xl text-slate-900 mb-6 flex items-center pt-8 border-t border-slate-200">
                                    <Award class="w-5 h-5 mr-3 text-premium-brown" />
                                    Certifications & Diplômes
                                </h3>
                                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-start gap-4">
                                    <div class="p-3 bg-premium-yellow/10 rounded-xl">
                                        <Award class="w-6 h-6 text-premium-brown" />
                                    </div>
                                    <p class="text-sm text-slate-700 font-medium whitespace-pre-line leading-relaxed mt-1">
                                        {{ profileData.diplomas }}
                                    </p>
                                </div>
                             </div>
                        </div>

                        <!-- Availability Calendar Preview (Weekly) -->
                         <div>
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="font-black text-xl text-slate-900 flex items-center">
                                    <Calendar class="w-5 h-5 mr-3 text-premium-brown" />
                                    Semaine Type
                                </h3>
                            </div>
                            <div class="grid grid-cols-7 gap-2">
                                <div v-for="(status, day) in profileData.availabilities" :key="day" 
                                     v-show="['monday','tuesday','wednesday','thursday','friday','saturday','sunday'].includes(day)"
                                     class="flex flex-col items-center gap-2 p-3 rounded-2xl border"
                                     :class="status ? 'bg-slate-900 border-slate-900 text-white shadow-lg' : 'bg-white border-slate-100 text-slate-300'"
                                >
                                    <span class="text-[10px] font-black uppercase tracking-widest">{{ day.substring(0,3) }}</span>
                                    <div class="w-2 h-2 rounded-full" :class="status ? 'bg-premium-yellow' : 'bg-slate-200'"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Reviews Preview -->
                        <div class="pt-10 border-t border-slate-100">
                             <h3 class="font-black text-xl text-slate-900 mb-6 flex items-center">
                                <Star class="w-5 h-5 mr-3 text-premium-brown" />
                                Avis Clients
                            </h3>
                            <div class="text-center py-10 bg-slate-50/50 rounded-3xl border border-dashed border-slate-200">
                                <p class="text-slate-400 text-sm font-medium">Les avis apparaîtront ici une fois que vous aurez réalisé des missions.</p>
                            </div>
                        </div>

                     </div>
                </div>
            </div>
          </div>
        </div>

        <!-- Right / Full: Form (Create/Edit Mode) -->
        <div v-if="mode === 'create' || mode === 'edit'" class="lg:col-span-12 space-y-10 animate-in fade-in slide-in-from-right-5 duration-700">
          
          <!-- Form Integrated -->
          <div class="bg-white rounded-[4rem] p-10 md:p-20 shadow-2xl border border-slate-100 overflow-hidden relative group">
            <div class="relative z-10">
              <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-16">
                <div class="space-y-2">
                    <h2 class="text-3xl font-black text-slate-900 tracking-tight">
                    {{ mode === 'create' ? 'Configuration Identity' : 'Mise à jour Profil' }}
                    </h2>
                    <p class="text-slate-400 font-medium">Remplissez les informations ci-dessous pour parfaire votre vitrine.</p>
                </div>
                <button @click="cancelEdit" class="self-start md:self-auto p-4 bg-slate-50 hover:bg-red-50 hover:text-red-500 rounded-2xl transition-all group/close">
                  <X class="w-6 h-6 transform group-hover/close:rotate-90 transition-transform" />
                </button>
              </div>

              <form @submit.prevent="handleSaveProfile" class="space-y-16">
                
                <!-- Section: Identity Photo -->
                <div class="flex flex-col items-center justify-center py-12 bg-slate-50/50 rounded-[3rem] border-2 border-dashed border-slate-100 relative group/photo">
                  <div class="absolute inset-0 bg-gradient-to-b from-transparent to-white/50 opacity-0 group-hover/photo:opacity-100 transition-opacity"></div>
                  <div class="relative z-10 flex flex-col items-center">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-8">Portrait Professionnel</h3>
                    <PhotoUploader 
                        :current-photo="currentPhotoUrl" 
                        @photo-updated="handlePhotoUpdate" 
                    />
                    <p class="mt-6 text-[10px] font-bold text-slate-400 italic">Format recommandé: 800x800px</p>
                  </div>
                </div>

                <!-- Section: Personal Details -->
                <div class="space-y-10">
                  <div class="flex items-center space-x-4">
                      <div class="w-10 h-10 bg-premium-blue/5 rounded-xl flex items-center justify-center">
                          <User class="w-5 h-5 text-premium-blue" />
                      </div>
                      <h3 class="text-lg font-black text-slate-900 uppercase tracking-widest">Informations Identitaires</h3>
                  </div>
                  
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-3">
                      <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">Prénom d'usage</label>
                      <input v-model="profileData.first_name" type="text" required class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-4xl focus:bg-white focus:ring-8 focus:ring-premium-blue/5 focus:border-premium-blue outline-none transition-all font-black text-slate-700">
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">Nom de famille</label>
                        <input v-model="profileData.last_name" type="text" required class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-4xl focus:bg-white focus:ring-8 focus:ring-premium-blue/5 focus:border-premium-blue outline-none transition-all font-black text-slate-700">
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">Date de Naissance</label>
                        <input v-model="profileData.birth_date" type="date" required class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-4xl focus:bg-white focus:ring-8 focus:ring-premium-blue/5 focus:border-premium-blue outline-none transition-all font-black text-slate-700">
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">N° Carte d'identité (CIN)</label>
                        <input v-model="profileData.cin" type="text" required class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-4xl focus:bg-white focus:ring-8 focus:ring-premium-blue/5 focus:border-premium-blue outline-none transition-all font-black text-slate-700">
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">Contact Téléphonique</label>
                        <input v-model="profileData.phone" type="text" required class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-4xl focus:bg-white focus:ring-8 focus:ring-premium-blue/5 focus:border-premium-blue outline-none transition-all font-black text-slate-700">
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">Ville de Résidence</label>
                        <input v-model="profileData.city" type="text" required class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-4xl focus:bg-white focus:ring-8 focus:ring-premium-blue/5 focus:border-premium-blue outline-none transition-all font-black text-slate-700">
                    </div>
                    <div class="space-y-3 md:col-span-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">Adresse Géographique</label>
                        <input v-model="profileData.address" type="text" required class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-4xl focus:bg-white focus:ring-8 focus:ring-premium-blue/5 focus:border-premium-blue outline-none transition-all font-black text-slate-700">
                    </div>
                  </div>
                  
                  <div class="space-y-3">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">Biographie & Présentation</label>
                    <textarea v-model="profileData.description" rows="5" required placeholder="Décrivez votre parcours, vos valeurs et votre approche professionnelle..." class="w-full px-8 py-6 bg-slate-50 border border-slate-100 rounded-[2.5rem] focus:bg-white focus:ring-8 focus:ring-premium-blue/5 focus:border-premium-blue outline-none transition-all font-medium text-slate-700 resize-none leading-relaxed"></textarea>
                  </div>
                </div>

                <!-- Section: Professional Context -->
                <div class="space-y-10">
                  <div class="flex items-center space-x-4">
                      <div class="w-10 h-10 bg-premium-brown/5 rounded-xl flex items-center justify-center">
                          <Briefcase class="w-5 h-5 text-premium-brown" />
                      </div>
                      <h3 class="text-lg font-black text-slate-900 uppercase tracking-widest">Expertise Métier</h3>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-3">
                      <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">Domaine d'exercice</label>
                      <div class="relative">
                        <select v-model="profileData.category_id" required class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-4xl focus:bg-white focus:ring-8 focus:ring-premium-blue/5 focus:border-premium-blue outline-none transition-all font-black text-slate-700 appearance-none">
                            <option :value="null" disabled>Choisir votre secteur</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                        <ChevronDown class="absolute right-6 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 pointer-events-none" />
                      </div>
                    </div>
                    <div class="space-y-3">
                      <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">Niveau d'expérience (Années)</label>
                      <input v-model="profileData.experience" type="number" required class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-4xl focus:bg-white focus:ring-8 focus:ring-premium-blue/5 focus:border-premium-blue outline-none transition-all font-black text-slate-700">
                    </div>
                    <div class="space-y-3">
                      <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">Prêt salarial (MAD/H)</label>
                      <div class="relative group/input">
                        <div class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within/input:text-premium-yellow transition-colors font-black">
                            <Coins class="w-6 h-6" />
                        </div>
                        <input v-model="profileData.hourly_rate" type="number" step="0.01" required class="w-full pl-16 pr-8 py-5 bg-slate-50 border border-slate-100 rounded-4xl focus:bg-white focus:ring-8 focus:ring-premium-blue/5 focus:border-premium-blue outline-none transition-all font-black text-slate-700">
                      </div>
                    </div>
                  </div>

                  <div class="space-y-3">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">Compétences Clés (Mots-clés séparés par des virgules)</label>
                    <input v-model="profileData.skills" type="text" placeholder="Ex: Force de proposition, Plomberie, Lecture de plans..." class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-4xl focus:bg-white focus:ring-8 focus:ring-premium-blue/5 focus:border-premium-blue outline-none transition-all font-black text-slate-700">
                  </div>
                </div>

                <!-- Section: Availability -->
                <div class="space-y-10">
                  <div class="flex items-center space-x-4">
                      <div class="w-10 h-10 bg-amber-500/5 rounded-xl flex items-center justify-center">
                          <Clock class="w-5 h-5 text-amber-500" />
                      </div>
                      <h3 class="text-lg font-black text-slate-900 uppercase tracking-widest">Planification Immédiate</h3>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-3">
                      <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">Date de prise d'effet</label>
                      <input v-model="profileData.availabilities.date" type="date" class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-4xl focus:bg-white focus:ring-8 focus:ring-premium-blue/5 focus:border-premium-blue outline-none transition-all font-black text-slate-700">
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">Type d'emploi</label>
                        <div class="relative">
                            <select v-model="profileData.availabilities.time" class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-4xl focus:bg-white focus:ring-8 focus:ring-premium-blue/5 focus:border-premium-blue outline-none transition-all font-black text-slate-700 appearance-none">
                                <option value="" disabled>Sélectionner un type</option>
                                <option value="Temps plein">Temps plein</option>
                                <option value="Temps partiel">Temps partiel (Demi-temps)</option>
                                <option value="Ponctuel">Ponctuel / À la mission</option>
                                <option value="Soir & Weekend">Soir & Weekend</option>
                            </select>
                            <ChevronDown class="absolute right-6 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 pointer-events-none" />
                        </div>
                    </div>
                  </div>
                </div>

                <!-- Final Actions -->
                <div class="flex flex-col md:flex-row items-center justify-end gap-6 pt-10 border-t border-slate-50">
                  <button type="button" @click="cancelEdit" class="px-12 py-5 rounded-4xl font-black text-[10px] uppercase tracking-[0.3em] text-slate-400 hover:text-red-500 transition-colors">
                    Abandonner
                  </button>
                  <button type="button" @click="validateAndSubmit" :disabled="saving" class="group/btn bg-slate-900 text-white px-14 py-6 rounded-[2.5rem] font-black text-[10px] uppercase tracking-[0.3em] shadow-2xl shadow-slate-900/40 hover:bg-premium-yellow hover:text-slate-900 hover:scale-105 active:scale-95 transition-all flex items-center space-x-4">
                    <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
                    <Save v-else class="w-5 h-5 group-hover/btn:rotate-12 transition-transform" />
                    <span>{{ mode === 'create' ? 'Finaliser l\'inscription' : 'Confirmer mes nouveaux paramètres' }}</span>
                  </button>
                </div>

              </form>
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
