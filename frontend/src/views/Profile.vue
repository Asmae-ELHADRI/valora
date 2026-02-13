<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import { 
  User, Mail, Phone, MapPin, Briefcase, GraduationCap, 
  Calendar, Eye, EyeOff, Camera, Check, Loader2, Save, Star,
  Lock, AlertTriangle, Trash2, Search, Pencil, X, Plus, Info, CheckCircle, ShieldCheck,
  ChevronLeft, ChevronRight, Award, ChevronDown
} from 'lucide-vue-next';
import { useRouter } from 'vue-router';

const auth = useAuthStore();
const router = useRouter();
const loading = ref(false);
const saving = ref(false);
const message = ref({ type: '', text: '' });
const activeTab = ref('profile'); // profile, security
const categories = ref([]);
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
    return form.value.category_ids.length;
});

const selectedCategoryNames = computed(() => {
    return categories.value
        .filter(cat => form.value.category_ids.includes(cat.id))
        .map(cat => cat.name);
});

const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: ''
});

const updatePassword = async () => {
  saving.value = true;
  message.value = { type: '', text: '' };
  try {
    const response = await api.post('/api/password/update', passwordForm.value);
    message.value = { type: 'success', text: response.data.message };
    passwordForm.value = { current_password: '', password: '', password_confirmation: '' };
  } catch (err) {
    message.value = { type: 'error', text: err.response?.data?.message || 'Erreur lors de la mise à jour' };
  } finally {
    saving.value = false;
  }
};

const deleteAccount = async () => {
  if (!confirm('Êtes-vous sûr de vouloir supprimer votre compte définitivement ? Cette action est irréversible.')) return;
  
  try {
    await api.delete('/api/account/delete');
    await auth.logout();
    router.push('/');
  } catch (err) {
    alert('Erreur lors de la suppression du compte');
  }
};

const form = ref({
  first_name: '',
  last_name: '',
  phone: '',
  city: '',
  address: '',
  birth_date: '',
  cin: '',
  skills: '',
  description: '',
  experience: '',
  experience_level: 'debutant',
  experiences_list: [],
  diplomas: '',
  category_ids: [],
  type: 'individual', // individual, company
  availabilities: {
    monday: false,
    tuesday: false,
    wednesday: false,
    thursday: false,
    friday: false,
    saturday: false,
    sunday: false
  }
});

const isVisible = ref(true);
const photoPreview = ref(null);
const photoFile = ref(null);
const newExperienceInput = ref('');
const editingIndex = ref(null);
const editingInput = ref('');
const hasDiploma = ref(false);
const reviewsData = ref({ reviews: [], average_rating: 0, total_reviews: 0 });
const currentStep = ref(1);

const nextStep = () => {
    if (currentStep.value < 3) currentStep.value++;
};

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

const fetchReviews = async () => {
  if (auth.user?.role !== 'provider') return;
  try {
    const response = await api.get('/api/reviews/provider');
    reviewsData.value = response.data;
  } catch (err) {
    console.error('Erreur chargement avis:', err);
  }
};

onMounted(async () => {
  loading.value = true;
  try {
    const [catRes] = await Promise.all([
      api.get('/api/offers/categories'),
      auth.user ? auth.fetchUser() : auth.fetchUser(),
      fetchReviews()
    ]);
    categories.value = catRes.data;
    const user = auth.user;
    
    // Extract category IDs from prestataire.categories
    const userCategoryIds = user.prestataire?.categories?.map(c => c.id) || [];
    // Fallback to old category_id if categories array is empty
    if (userCategoryIds.length === 0 && user.prestataire?.category_id) {
        userCategoryIds.push(user.prestataire.category_id);
    }

    form.value = {
      first_name: user.first_name || '',
      last_name: user.last_name || '',
      phone: user.phone || '',
      city: user.prestataire?.city || '',
      address: user.address || '',
      birth_date: user.prestataire?.birth_date ? user.prestataire.birth_date.substring(0, 10) : '',
      cin: user.prestataire?.cin || '',
      skills: user.prestataire?.skills || '',
      description: user.prestataire?.description || '',
      experience: user.prestataire?.experience || '',
      experience_level: 'debutant',
      experiences_list: [],
      diplomas: user.prestataire?.diplomas || '',
      category_ids: userCategoryIds,
      type: user.client?.type || 'individual',
      availabilities: user.prestataire?.availabilities || form.value.availabilities
    };

    hasDiploma.value = !!form.value.diplomas;

    // Parse experience if it follows our pattern
    if (form.value.experience) {
      const parts = form.value.experience.split('\n');
      const levelLine = parts.find(p => p.startsWith('Niveau: '));
      if (levelLine) {
        form.value.experience_level = levelLine.replace('Niveau: ', '').trim();
        const list = parts.filter(p => p.startsWith('- ')).map(p => p.replace('- ', '').trim());
        if (list.length > 0) form.value.experiences_list = list;
      } else {
        // Fallback for legacy plain text experience
        form.value.experiences_list = [form.value.experience];
      }
    }
    isVisible.value = user.prestataire?.is_visible ?? true;
    
    // Check if already completed
    if (user.prestataire?.is_completed && auth.user.role === 'provider') {
        router.push('/dashboard-prestataire');
        return;
    }

    // Photo handling
    const photoPath = user.role === 'provider' ? user.prestataire?.photo : user.client?.photo;
    if (photoPath) {
      photoPreview.value = photoPath.startsWith('http') ? photoPath : `http://localhost:8000/storage/${photoPath}`;
    }
  } catch (err) {
    message.value = { type: 'error', text: 'Impossible de charger le profil' };
  } finally {
    loading.value = false;
  }
});

watch(hasDiploma, (newVal) => {
    if (!newVal) {
        form.value.diplomas = '';
    }
});

const handlePhotoChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    photoFile.value = file;
    photoPreview.value = URL.createObjectURL(file);
  }
};

const saveProfile = async (options = {}) => {
  const silent = options.silent || false;
  if (!silent) {
    saving.value = true;
    message.value = { type: '', text: '' };
  }
  
  const isProvider = auth.user.role === 'provider';
  const apiPrefix = isProvider ? '/api/provider' : '/api/client';
  
  try {
    // Serialize experience before saving
    if (isProvider) {
      const validExps = form.value.experiences_list.filter(e => e.trim());
      form.value.experience = `Niveau: ${form.value.experience_level}\n` + validExps.map(e => `- ${e}`).join('\n');
    }

    // Save profile info
    const payload = { ...form.value };
    if (options.is_completed) {
        payload.is_completed = true;
    }

    await api.post(`${apiPrefix}/profile`, payload);
    
    // Save photo if changed (only if not silent or if specifically asked, usually photo isn't auto-saved)
    if (photoFile.value && !silent) {
      const formData = new FormData();
      formData.append('photo', photoFile.value);
      await api.post(`${apiPrefix}/photo`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
    }

    if (isProvider) {
      await api.post('/api/provider/availability', { availabilities: form.value.availabilities });
    }

    if (!silent) {
       if (options.is_completed) {
         message.value = { type: 'success', text: 'Profil complété avec succès ! Redirection...' };
         setTimeout(() => {
            router.push('/dashboard-prestataire');
         }, 1500);
       } else {
         message.value = { type: 'success', text: 'Profil mis à jour avec succès !' };
       }
    }
    await auth.fetchUser();
  } catch (err) {
    if (!silent) {
      message.value = { type: 'error', text: err.response?.data?.message || 'Erreur lors de la sauvegarde' };
    }
  } finally {
    if (!silent) {
      saving.value = false;
    }
  }
};

// Initialisation logic and Watchers
watch(() => form.value.category_ids, (newVal, oldVal) => {
    // Only auto-save if the value actually changed and it's not the initial load
    if (JSON.stringify(newVal) !== JSON.stringify(oldVal) && auth.user?.role === 'provider') {
        saveProfile({ silent: true });
    }
}, { deep: true });

const toggleVisibility = async () => {
  if (auth.user.role !== 'provider') return;
  try {
    const response = await api.post('/api/provider/visibility');
    isVisible.value = response.data.is_visible;
  } catch (err) {
    message.value = { type: 'error', text: 'Erreur lors du changement de visibilité' };
  }
};

const addExperience = () => {
  if (newExperienceInput.value.trim()) {
    form.value.experiences_list.push(newExperienceInput.value.trim());
    newExperienceInput.value = '';
    saveProfile({ silent: true });
  }
};

const removeExperience = (index) => {
  form.value.experiences_list.splice(index, 1);
  saveProfile({ silent: true });
};

const startEditing = (index) => {
  editingIndex.value = index;
  editingInput.value = form.value.experiences_list[index];
};

const saveEdit = (index) => {
  if (editingInput.value.trim()) {
    form.value.experiences_list[index] = editingInput.value.trim();
    editingIndex.value = null;
    editingInput.value = '';
    saveProfile({ silent: true });
  }
};

const cancelEdit = () => {
  editingIndex.value = null;
  editingInput.value = '';
};

const days = [
  { id: 'monday', name: 'Lundi' },
  { id: 'tuesday', name: 'Mardi' },
  { id: 'wednesday', name: 'Mercredi' },
  { id: 'thursday', name: 'Jeudi' },
  { id: 'friday', name: 'Vendredi' },
  { id: 'saturday', name: 'Samedi' },
  { id: 'sunday', name: 'Dimanche' }
];
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-24">
    <div v-if="loading" class="flex justify-center items-center h-64">
      <Loader2 class="w-12 h-12 text-premium-yellow animate-spin" />
    </div>

    <div v-else class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
      <!-- Wizard Header Banner -->
      <div class="bg-slate-900 rounded-4xl p-8 md:p-10 text-white shadow-2xl relative overflow-hidden group border border-white/5 mb-8">
        <div class="relative z-10 flex flex-col items-center text-center space-y-6">
          <div class="bg-premium-yellow/10 p-4 rounded-3xl border border-premium-yellow/20 animate-pulse">
            <Award class="w-12 h-12 text-premium-yellow shadow-3xl" />
          </div>
          <div class="space-y-2">
            <h2 class="text-[10px] font-black uppercase tracking-[0.4em] text-premium-yellow">Rejoindre l'excellence</h2>
            <h1 class="text-3xl md:text-5xl font-black tracking-tighter">Votre Profil Professionnel</h1>
          </div>
        </div>
        <!-- Decorative Elements -->
        <div class="absolute top-0 right-0 w-96 h-96 bg-white/5 rounded-full blur-3xl -mr-48 -mt-48"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-premium-yellow/5 rounded-full blur-3xl -ml-32 -mb-32"></div>
      </div>

      <!-- Step Indicator & Progress Bar -->
      <div class="max-w-3xl mx-auto space-y-4 mb-12">
        <div class="flex items-end justify-between px-2">
          <div class="space-y-1">
             <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Étape {{ currentStep }} sur 3</p>
             <h3 class="text-xl font-black text-slate-900 tracking-tight">
                {{ currentStep === 1 ? 'Profil & Identité' : (currentStep === 2 ? 'Expertise & Documents' : 'Disponibilités') }}
             </h3>
          </div>
          <span class="text-lg font-black text-premium-brown">{{ Math.round((currentStep / 3) * 100) }}%</span>
        </div>
        <div class="h-3 bg-slate-100 rounded-full overflow-hidden border border-slate-50 shadow-inner p-0.5">
           <div 
             class="h-full bg-linear-to-r from-premium-brown to-premium-yellow rounded-full transition-all duration-700 ease-out shadow-lg"
             :style="{ width: `${(currentStep / 3) * 100}%` }"
           ></div>
        </div>
      </div>

      <!-- Success/Error Message -->
      <transition enter-active-class="transition duration-300 ease-out" enter-from-class="transform -translate-y-4 opacity-0" enter-to-class="transform translate-y-0 opacity-100">
        <div v-if="message.text" :class="message.type === 'success' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-red-500/10 text-red-400 border-red-500/20'" class="p-4 rounded-3xl border flex items-center space-x-3 text-sm font-black uppercase tracking-wider mb-8">
          <Check v-if="message.type === 'success'" class="w-5 h-5" />
          <AlertTriangle v-else class="w-5 h-5" />
          <span>{{ message.text }}</span>
        </div>
      </transition>

      <!-- Main Content Wizard Area -->
      <div class="max-w-4xl mx-auto space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
        
        <!-- STEP 1: Profil & Identité -->
        <div v-if="currentStep === 1" class="space-y-8">
            <div class="bg-white p-8 md:p-12 rounded-4xl shadow-xl shadow-slate-200/50 border border-slate-100 relative overflow-hidden">
                <div class="flex items-center space-x-3 mb-10 pb-6 border-b border-slate-50 relative z-10">
                    <div class="p-3 bg-premium-yellow/10 rounded-2xl">
                        <User class="w-6 h-6 text-premium-brown" />
                    </div>
                    <h3 class="font-black text-2xl text-slate-900 tracking-tight">Profil & Identité</h3>
                </div>

                <div class="flex flex-col md:flex-row gap-12 relative z-10">
                    <!-- Photo Column -->
                    <div class="flex flex-col items-center space-y-6">
                        <div class="relative group">
                            <div class="w-32 h-32 md:w-48 md:h-48 rounded-4xl bg-slate-50 overflow-hidden border-4 border-slate-50 shadow-2xl transform transition-transform duration-500 group-hover:scale-105">
                                <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover">
                                <div v-else class="w-full h-full flex items-center justify-center text-slate-200 bg-slate-50">
                                    <User class="w-20 h-20" />
                                </div>
                            </div>
                            <label class="absolute -bottom-4 -right-4 bg-slate-900 text-premium-yellow p-4 rounded-3xl shadow-2xl cursor-pointer hover:bg-slate-800 transform transition-all hover:scale-110 active:scale-95 z-20 border-4 border-white">
                                <Camera class="w-6 h-6 font-bold" />
                                <input type="file" @change="handlePhotoChange" class="hidden" accept="image/*">
                            </label>
                        </div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Format JPG, PNG (Max. 5Mo)</p>
                    </div>

                    <!-- Fields Column -->
                    <div class="grow grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-8">
                        <div class="group">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">Prénom</label>
                            <input v-model="form.first_name" type="text" placeholder="Prénom" class="w-full px-6 py-4 rounded-2xl border border-slate-100 bg-slate-50/50 focus:bg-white focus:ring-4 focus:ring-slate-900/5 focus:border-slate-900 outline-none transition-all duration-300 font-bold text-slate-900 shadow-sm">
                        </div>
                        <div class="group">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">Nom</label>
                            <input v-model="form.last_name" type="text" placeholder="Nom" class="w-full px-6 py-4 rounded-2xl border border-slate-100 bg-slate-50/50 focus:bg-white focus:ring-4 focus:ring-slate-900/5 focus:border-slate-900 outline-none transition-all duration-300 font-bold text-slate-900 shadow-sm">
                        </div>
                        <div class="group">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">Téléphone</label>
                            <div class="relative">
                                <Phone class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-slate-900 transition-colors" />
                                <input v-model="form.phone" type="tel" placeholder="06 00 00 00 00" class="w-full pl-14 pr-6 py-4 rounded-2xl border border-slate-100 bg-slate-50/50 focus:bg-white focus:ring-4 focus:ring-slate-900/5 focus:border-slate-900 outline-none transition-all duration-300 font-bold text-slate-900 shadow-sm">
                            </div>
                        </div>
                        <div class="group">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">Date de naissance</label>
                            <div class="relative">
                                <Calendar class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-slate-900 transition-colors" />
                                <input v-model="form.birth_date" type="date" class="w-full pl-14 pr-6 py-4 rounded-2xl border border-slate-100 bg-slate-50/50 focus:bg-white focus:ring-4 focus:ring-slate-900/5 focus:border-slate-900 outline-none transition-all duration-300 font-bold text-slate-900 shadow-sm">
                            </div>
                        </div>
                        <div class="group">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">CIN (Identité)</label>
                            <div class="relative">
                                <ShieldCheck class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-slate-900 transition-colors" />
                                <input v-model="form.cin" type="text" placeholder="AB123456" class="w-full pl-14 pr-6 py-4 rounded-2xl border border-slate-100 bg-slate-50/50 focus:bg-white focus:ring-4 focus:ring-slate-900/5 focus:border-slate-900 outline-none transition-all duration-300 font-bold text-slate-900 shadow-sm">
                            </div>
                        </div>
                        <div class="group">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">Ville</label>
                            <div class="relative">
                                <MapPin class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-slate-900 transition-colors" />
                                <input v-model="form.city" type="text" placeholder="Casablanca, Rabat..." class="w-full pl-14 pr-6 py-4 rounded-2xl border border-slate-100 bg-slate-50/50 focus:bg-white focus:ring-4 focus:ring-slate-900/5 focus:border-slate-900 outline-none transition-all duration-300 font-bold text-slate-900 shadow-sm">
                            </div>
                        </div>
                        <div class="group">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">Adresse</label>
                            <div class="relative">
                                <MapPin class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-slate-900 transition-colors" />
                                <input v-model="form.address" type="text" placeholder="Votre adresse" class="w-full pl-14 pr-6 py-4 rounded-2xl border border-slate-100 bg-slate-50/50 focus:bg-white focus:ring-4 focus:ring-slate-900/5 focus:border-slate-900 outline-none transition-all duration-300 font-bold text-slate-900 shadow-sm">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 2: Expertise & Documents -->
        <div v-if="currentStep === 2" class="space-y-8">
            <!-- Expertise Section -->
            <div class="bg-white p-8 md:p-12 rounded-4xl shadow-xl shadow-slate-200/50 border border-slate-100">
                <div class="flex items-center justify-between mb-10 pb-6 border-b border-slate-50">
                    <div class="flex items-center space-x-3">
                        <div class="p-3 bg-premium-yellow/10 rounded-2xl">
                            <Briefcase class="w-6 h-6 text-premium-brown" />
                        </div>
                        <h3 class="font-black text-2xl text-slate-900 tracking-tight">Domaines d'expertise</h3>
                    </div>
                    <div v-if="selectedCategoriesCount > 0" class="flex items-center space-x-2 bg-slate-900 text-white px-4 py-2 rounded-2xl animate-in zoom-in duration-300">
                        <CheckCircle class="w-4 h-4 text-premium-yellow" />
                        <span class="text-[10px] font-black uppercase tracking-widest">{{ selectedCategoriesCount }} service{{ selectedCategoriesCount > 1 ? 's' : '' }}</span>
                    </div>
                </div>

                <div class="space-y-10">
                    <!-- Categories Search & Grid -->
                    <div class="space-y-6">
                        <div class="relative group">
                            <Search class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-slate-900 transition-colors" />
                            <input 
                                v-model="categorySearch" 
                                type="text" 
                                placeholder="Rechercher une compétence..." 
                                class="w-full pl-14 pr-6 py-5 rounded-2xl border border-slate-100 bg-slate-50/50 focus:bg-white focus:ring-4 focus:ring-slate-900/5 focus:border-slate-900 outline-none transition-all duration-300 font-bold text-slate-900 shadow-sm"
                            >
                        </div>

                        <!-- Selected Categories Pills -->
                        <div v-if="selectedCategoryNames.length > 0" class="flex flex-wrap gap-2 animate-in fade-in slide-in-from-top-2 duration-500">
                            <div v-for="name in selectedCategoryNames" :key="name" class="flex items-center space-x-2 bg-slate-50 border border-slate-100 px-4 py-2 rounded-xl group hover:border-premium-yellow transition-all">
                                <span class="text-[10px] font-black uppercase tracking-wider text-slate-600 group-hover:text-slate-900">{{ name }}</span>
                                <button @click="form.category_ids = form.category_ids.filter(id => categories.find(c => c.name === name).id !== id)" class="text-slate-300 hover:text-red-500 transition-colors">
                                    <X class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        <div class="space-y-12 max-h-[400px] overflow-y-auto pr-2 scrollbar-hide py-2">
                             <div v-for="group in filteredCategoryGroups" :key="group.name" class="space-y-6">
                                <h4 class="text-xs font-black text-slate-400 uppercase tracking-[0.3em] flex items-center sticky top-0 bg-white/90 backdrop-blur-md py-2 z-10">
                                    <span class="w-10 h-0.5 bg-premium-yellow mr-3"></span>
                                    {{ group.name }}
                                </h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <label 
                                        v-for="cat in group.categories" 
                                        :key="cat.id"
                                        :class="form.category_ids.includes(cat.id) ? 'border-slate-900 bg-slate-900 text-white shadow-xl shadow-slate-900/20' : 'border-slate-100 bg-white hover:border-premium-yellow hover:bg-slate-50'"
                                        class="flex items-center space-x-4 p-4 rounded-2xl border-2 cursor-pointer transition-all duration-300 group relative"
                                    >
                                        <div class="shrink-0">
                                            <input type="checkbox" :value="cat.id" v-model="form.category_ids" class="hidden">
                                            <div class="w-5 h-5 rounded-md border-2 border-slate-200 flex items-center justify-center transition-all group-hover:border-premium-yellow" :class="form.category_ids.includes(cat.id) ? 'bg-premium-yellow border-premium-yellow' : 'bg-transparent'">
                                                <Check v-if="form.category_ids.includes(cat.id)" class="w-3 h-3 text-slate-900 font-black" />
                                            </div>
                                        </div>
                                        <span class="text-xs font-black uppercase tracking-wider truncate">{{ cat.name }}</span>
                                    </label>
                                </div>
                             </div>
                        </div>
                    </div>

                    <!-- Experience Block -->
                    <div class="space-y-10 pt-10 border-t border-slate-50">
                        <div class="space-y-4">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Combien d'années d'expérience ?</label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <button 
                                    v-for="opt in [
                                        { value: 'debutant', label: 'Débutant' },
                                        { value: '1-2ans', label: '1 - 2 ans' },
                                        { value: '2-4ans', label: '2 - 4 ans' },
                                        { value: 'plus4ans', label: '4 ans +' }
                                    ]"
                                    :key="opt.value"
                                    type="button"
                                    @click="form.experience_level = opt.value; saveProfile({ silent: true })"
                                    :class="form.experience_level === opt.value ? 'grid-rows-subgrid border-slate-400 text-premium-yellow shadow-xl shadow-slate-900/20 ring-4 ring-slate-900/5' : 'bg-slate-50/50 border-slate-100 text-slate-500 hover:border-premium-yellow hover:bg-white'"
                                    class="px-4 py-4 rounded-2xl border-2 font-black text-[10px] uppercase tracking-wider transition-all duration-300 transform active:scale-95 text-center flex items-center justify-center min-h-[64px]"
                                >
                                    {{ opt.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Add Experience Field -->
                        <div class="space-y-4">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Ajouter une expérience</label>
                            <div class="relative group">
                                <Plus class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-slate-900 transition-colors" />
                                <input 
                                    v-model="newExperienceInput" 
                                    @keydown.enter.prevent="addExperience"
                                    type="text" 
                                    placeholder="Détaillez vos expériences (ex: 5 ans en rénovation...)" 
                                    class="w-full pl-14 pr-32 py-5 rounded-2xl border border-slate-100 bg-slate-50/50 focus:bg-white focus:ring-4 focus:ring-slate-900/5 focus:border-slate-900 outline-none transition-all duration-300 font-bold text-slate-900 shadow-sm text-sm"
                                >
                                <button 
                                    @click="addExperience"
                                    :disabled="!newExperienceInput.trim()"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 px-6 py-2.5 rounded-xl bg-slate-900 text-white text-[10px] font-black uppercase tracking-widest hover:bg-slate-800 disabled:bg-slate-200 disabled:text-slate-400 transition-all shadow-lg"
                                >
                                    Ajouter
                                </button>
                            </div>
                        </div>

                        <!-- Timeline of Experiences -->
                        <div v-if="form.experiences_list.length > 0" class="space-y-6">
                            <div class="flex items-center space-x-3 pl-4">
                                <div class="w-1.5 h-1.5 rounded-full bg-premium-yellow"></div>
                                <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">Expériences enregistrées ci-dessous</h5>
                            </div>
                            
                            <div class="space-y-4">
                                <div v-for="(exp, index) in form.experiences_list" :key="index" class="group relative bg-slate-50/50 rounded-2xl p-6 border border-slate-100/50 hover:bg-white hover:border-premium-yellow hover:shadow-xl transition-all duration-500 animate-in slide-in-from-left-4">
                                    <div v-if="editingIndex === index" class="space-y-4">
                                        <textarea v-model="editingInput" class="w-full p-4 rounded-xl border-2 border-slate-900 bg-white focus:outline-none font-bold text-slate-900 shadow-lg text-sm" rows="3"></textarea>
                                        <div class="flex justify-end space-x-2">
                                            <button @click="cancelEdit" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-colors">Annuler</button>
                                            <button @click="saveEdit(index)" class="px-6 py-2 rounded-xl bg-slate-900 text-white text-[10px] font-black uppercase tracking-widest hover:bg-slate-800 transition-all shadow-lg">Enregistrer</button>
                                        </div>
                                    </div>
                                    <div v-else class="flex items-start justify-between space-x-6">
                                        <div class="flex items-start space-x-4">
                                            <div class="mt-1 w-2 h-2 rounded-full bg-premium-yellow shadow-sm shadow-premium-yellow/50"></div>
                                            <p class="text-sm font-bold text-slate-700 leading-relaxed">{{ exp }}</p>
                                        </div>
                                        <div class="flex items-center space-x-1 opacity-0 group-hover:opacity-100 transition-all duration-300">
                                            <button @click="startEditing(index)" class="p-2 rounded-xl text-slate-400 hover:text-slate-900 hover:bg-slate-100 transition-all">
                                                <Pencil class="w-4 h-4" />
                                            </button>
                                            <button @click="removeExperience(index)" class="p-2 rounded-xl text-slate-400 hover:text-red-500 hover:bg-red-50 transition-all">
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Bio Professionnelle</label>
                            <textarea v-model="form.description" @blur="saveProfile({ silent: true })" rows="3" placeholder="Parlez-nous de vos compétences..." class="w-full px-6 py-4 rounded-2xl border border-slate-100 bg-slate-50/50 focus:bg-white focus:ring-4 focus:ring-slate-900/5 focus:border-slate-900 outline-none transition-all duration-300 font-bold text-slate-900 resize-none shadow-sm"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents Section -->
            <div class="bg-white p-8 md:p-12 rounded-4xl shadow-xl shadow-slate-200/50 border border-slate-100">
                <div class="flex items-center space-x-3 mb-10 pb-6 border-b border-slate-50">
                    <div class="p-3 bg-premium-yellow/10 rounded-2xl">
                        <GraduationCap class="w-6 h-6 text-premium-brown" />
                    </div>
                    <h3 class="font-black text-2xl text-slate-900 tracking-tight">Vérification & Diplômes</h3>
                </div>

                <div class="space-y-8">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 p-8 bg-slate-50/50 rounded-4xl border border-slate-100 transition-hover duration-300 hover:bg-white hover:shadow-xl">
                        <div class="space-y-1">
                            <h4 class="text-sm font-black text-slate-900 uppercase tracking-wider">Avez-vous des diplômes ?</h4>
                            <p class="text-xs text-slate-400 font-medium">Les profils certifiés sont plus sollicités.</p>
                        </div>
                        <div class="flex bg-slate-100 p-1.5 rounded-2xl">
                             <button @click="hasDiploma = true" :class="hasDiploma ? 'bg-white text-slate-900 shadow-md' : 'text-slate-500 hover:text-slate-900'" class="px-8 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all">Oui</button>
                             <button @click="hasDiploma = false" :class="!hasDiploma ? 'bg-white text-slate-900 shadow-md' : 'text-slate-500 hover:text-slate-900'" class="px-8 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all">Non</button>
                        </div>
                    </div>

                    <div v-if="hasDiploma" class="animate-in fade-in slide-in-from-top-4 duration-500">
                        <div class="relative group">
                             <textarea v-model="form.diplomas" @blur="saveProfile({ silent: true })" rows="4" placeholder="Mentionnez vos diplômes ou certifications..." class="w-full px-8 py-6 rounded-4xl border border-slate-100 bg-white focus:ring-4 focus:ring-slate-900/5 focus:border-slate-900 outline-none transition-all duration-300 font-bold text-slate-900 resize-none shadow-sm"></textarea>
                             <div class="absolute bottom-6 right-6 p-3 bg-premium-yellow/10 rounded-2xl">
                                <Award class="w-5 h-5 text-premium-brown" />
                             </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 3: Disponibilités -->
        <div v-if="currentStep === 3" class="space-y-8">
            <div class="bg-white p-8 md:p-12 rounded-4xl shadow-xl shadow-slate-200/50 border border-slate-100">
                <div class="flex items-center space-x-3 mb-10 pb-6 border-b border-slate-50">
                    <div class="p-3 bg-premium-yellow/10 rounded-2xl">
                        <Calendar class="w-6 h-6 text-premium-brown" />
                    </div>
                    <h3 class="font-black text-2xl text-slate-900 tracking-tight">Vos Disponibilités</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="day in days" :key="day.id" 
                        class="p-6 rounded-4xl border-2 transition-all duration-500 flex flex-col items-center justify-center space-y-4 group"
                        :class="form.availabilities[day.id] ? 'bg-slate-500 border-slate-900 shadow-2xl scale-[1.02]' : 'bg-slate-300 border-slate-20 hover:border-slate-700 hover:bg-yellow-200'"
                    >
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-500" :class="form.availabilities[day.id] ? 'bg-premium-yellow text-slate-900 rotate-360' : 'bg-white text-slate-800 group-hover:text-slate-600 shadow-sm'">
                            <Calendar class="w-5 h-5" />
                        </div>
                        <div class="text-center">
                            <span class="block text-xs font-black uppercase tracking-widest mb-4" :class="form.availabilities[day.id] ? 'text-white' : 'text-slate-500'">{{ day.name }}</span>
                            <button 
                                @click="form.availabilities[day.id] = !form.availabilities[day.id]; saveProfile({ silent: true })"
                                :class="[
                                  form.availabilities[day.id] ? 'bg-premium-yellow ring-premium-yellow/20' : 'bg-slate-200 ring-transparent',
                                  'relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-300 ring-4'
                                ]"
                            >
                                <span :class="form.availabilities[day.id] ? 'translate-x-6' : 'translate-x-1'" class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-300 shadow-lg" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Provider Visibility Toggle Card -->
            <div v-if="auth.user?.role === 'provider'" class="bg-slate-900 p-8 rounded-4xl text-white flex flex-col md:flex-row items-center justify-between gap-8 border border-white/5 shadow-2xl overflow-hidden relative group">
                <div class="relative z-10 space-y-2">
                    <h4 class="font-black text-xl">Visibilité du profil</h4>
                    <p class="text-sm text-slate-400 font-medium">Contrôlez si les clients peuvent vous trouver dans la recherche.</p>
                </div>
                <button 
                    @click="toggleVisibility"
                    :class="isVisible ? 'bg-emerald-500 text-white shadow-emerald-500/20' : 'bg-slate-800 text-slate-400 shadow-black/20'"
                    class="relative z-10 flex items-center space-x-3 px-8 py-4 rounded-3xl border border-white/5 font-black text-xs uppercase tracking-widest transition-all hover:scale-105 active:scale-95 shadow-xl"
                >
                    <component :is="isVisible ? Eye : EyeOff" class="w-5 h-5" />
                    <span>{{ isVisible ? 'Profil Public' : 'Profil Privé' }}</span>
                </button>
                <!-- Decor -->
                <div class="absolute top-0 right-0 w-32 h-32 bg-premium-yellow/5 rounded-full blur-2xl -mr-16 -mt-16 group-hover:bg-premium-yellow/10 transition-colors"></div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-6 pt-12 border-t border-slate-100">
            <button 
                v-if="currentStep > 1"
                @click="prevStep" 
                class="w-full sm:w-auto flex items-center justify-center space-x-3 px-10 py-5 rounded-3xl border-2 border-slate-200 text-slate-900 font-black text-xs uppercase tracking-[0.2em] transition-all hover:bg-slate-50 hover:border-slate-900 active:scale-95 shadow-lg shadow-slate-200/20"
            >
                <ChevronLeft class="w-5 h-5" />
                <span>Précédent</span>
            </button>
            <div v-else class="hidden sm:block"></div>

            <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
                <button 
                    v-if="currentStep < 3"
                    @click="nextStep" 
                    class="w-full sm:w-auto flex items-center justify-center space-x-3 bg-premium-yellow text-slate-900 px-12 py-5 rounded-3xl font-black text-xs uppercase tracking-[0.2em] transition-all hover:bg-yellow-400 active:scale-95 shadow-2xl shadow-yellow-500/30"
                >
                    <span>Suivant</span>
                    <ChevronRight class="w-5 h-5" />
                </button>
                <button 
                    v-else
                    @click="saveProfile({ is_completed: true })" 
                    :disabled="saving"
                    class="w-full sm:w-auto flex items-center justify-center space-x-3 bg-premium-yellow text-slate-900 px-12 py-5 rounded-3xl font-black text-xs uppercase tracking-[0.2em] transition-all hover:bg-yellow-400 active:scale-95 shadow-2xl shadow-yellow-500/30 disabled:opacity-50"
                >
                    <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
                    <Save v-else class="w-5 h-5" />
                    <span>Terminer & Enregistrer</span>
                </button>
            </div>
        </div>

      </div>

    </div>
  </div>
</template>
