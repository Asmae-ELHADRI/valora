<script setup>
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import { 
  User, Mail, Phone, MapPin, Briefcase, Calendar, Clock, 
  Camera, CheckCircle, AlertCircle, Loader2, Edit3, Save, X, Fingerprint, Coins, Plus
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
    date: '',
    time: ''
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

const fetchProfile = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.get('/api/provider/profile');
    if (response.data.exists) {
      profileExists.value = true;
      const user = response.data.user;
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
        availabilities: user.prestataire?.availabilities || { date: '', time: '' }
      };
      mode.value = 'view';
    } else {
      profileExists.value = false;
      mode.value = 'view'; // On reste en view pour afficher le message "aucun profil"
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
    const response = await api[method]('/api/provider/profile', {
      ...profileData.value,
      category_ids: [profileData.value.category_id]
    });
    
    // Si on a aussi des dispos séparées à sauvegarder
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
  profileData.value = { ...profileData.value }; // resetting form basically
};

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

const currentCategoryName = computed(() => {
  const cat = categories.value.find(c => c.id === profileData.value.category_id);
  return cat ? cat.name : 'Non définie';
});

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
</script>

<template>
  <div class="min-h-screen bg-slate-50 font-outfit p-4 md:p-8">
    <div class="max-w-5xl mx-auto space-y-8">
      
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-black text-slate-900 tracking-tight">Tableau de bord Prestataire</h1>
          <p class="text-slate-500 font-medium">Gérez votre profil et vos activités sur Valora</p>
        </div>
        <div v-if="success" class="bg-emerald-50 text-emerald-700 px-6 py-3 rounded-2xl border border-emerald-100 flex items-center animate-fade-in shadow-sm">
          <CheckCircle class="w-5 h-5 mr-2" />
          <span class="text-sm font-bold">{{ success }}</span>
        </div>
      </div>

      <!-- State: Loading -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-20 bg-white rounded-[2.5rem] border border-slate-100 shadow-sm">
        <Loader2 class="w-12 h-12 text-premium-blue animate-spin mb-4" />
        <p class="text-slate-400 font-bold uppercase tracking-widest text-xs">Chargement de votre univers...</p>
      </div>

      <!-- State: No Profile & Mode View -->
      <div v-else-if="!profileExists && mode === 'view'" class="bg-white rounded-[2.5rem] p-12 text-center border border-slate-100 shadow-xl overflow-hidden relative group">
        <div class="relative z-10">
          <div class="w-20 h-20 bg-amber-50 rounded-3xl flex items-center justify-center mx-auto mb-6 transform group-hover:scale-110 transition-transform">
            <AlertCircle class="w-10 h-10 text-amber-500" />
          </div>
          <h2 class="text-2xl font-black text-slate-900 mb-2">Votre profil n'est pas encore créé</h2>
          <p class="text-slate-500 max-w-md mx-auto mb-8 font-medium">
            Pour commencer à recevoir des offres et être visible par les clients, vous devez configurer votre vitrine professionnelle.
          </p>
          <button @click="startCreate" class="bg-premium-blue text-white px-10 py-4 rounded-2xl font-black text-sm uppercase tracking-[0.2em] shadow-2xl shadow-blue-900/20 hover:scale-105 active:scale-95 transition-all">
            Créer mon profil maintenant
          </button>
        </div>
        <div class="absolute -bottom-12 -right-12 w-48 h-48 bg-slate-50 rounded-full blur-3xl"></div>
      </div>

      <!-- Main Content Grid -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left: Profile Summary Card (View Mode) -->
        <div v-if="mode === 'view' && profileExists" class="lg:col-span-4 space-y-6 animate-fade-in">
          <div class="bg-white rounded-[2.5rem] p-8 shadow-xl border border-slate-100 relative overflow-hidden">
            <div class="relative z-10 flex flex-col items-center text-center">
              <div class="w-32 h-32 rounded-4xl bg-slate-100 border-4 border-white shadow-lg mb-6 overflow-hidden flex items-center justify-center text-slate-300">
                <img v-if="currentPhotoUrl" :src="currentPhotoUrl" class="w-full h-full object-cover">
                <User v-else class="w-12 h-12" />
              </div>
              
              <h3 class="text-xl font-black text-slate-900 mb-1">{{ userFullName }}</h3>
              <div class="inline-flex items-center px-3 py-1 bg-premium-blue/5 text-premium-blue rounded-full text-[10px] font-black uppercase tracking-wider mb-6">
                {{ currentCategoryName }}
              </div>

              <div class="w-full space-y-4 text-left">
                <div class="flex items-center text-sm text-slate-600">
                  <Briefcase class="w-4 h-4 mr-3 text-slate-400" />
                  <span class="font-medium">{{ profileData.experience }} ans d'expérience</span>
                </div>
                <div class="flex items-center text-sm text-slate-600">
                  <Clock class="w-4 h-4 mr-3 text-slate-400" />
                  <span class="font-medium">{{ profileData.hourly_rate }} MAD / heure</span>
                </div>
                <div class="flex items-center text-sm text-slate-600">
                  <MapPin class="w-4 h-4 mr-3 text-slate-400" />
                  <span class="font-medium">{{ profileData.address }}, {{ profileData.city }}</span>
                </div>
                <div class="flex items-center text-sm text-slate-600">
                  <Fingerprint class="w-4 h-4 mr-3 text-slate-400" />
                  <span class="font-medium">CIN: {{ profileData.cin }}</span>
                </div>
                <div class="flex items-center text-sm text-slate-600">
                  <User class="w-4 h-4 mr-3 text-slate-400" />
                  <span class="font-medium">Né(e) le: {{ profileData.birth_date }}</span>
                </div>
                <div class="flex items-center text-sm text-slate-600">
                  <Calendar class="w-4 h-4 mr-3 text-slate-400" />
                  <span class="font-medium">Dispo le: {{ profileData.availabilities.date || 'Non défini' }}</span>
                </div>
              </div>

              <button @click="startEdit" class="w-full mt-8 bg-slate-900 text-white py-4 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-premium-blue transition-colors flex items-center justify-center space-x-2">
                <Edit3 class="w-4 h-4" />
                <span>Modifier mon profil</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Right / Full: Form (Create/Edit Mode) -->
        <div :class="mode === 'view' ? 'lg:col-span-8' : 'lg:col-span-12'" class="space-y-8 animate-fade-in">
          
          <div v-if="mode === 'view'" class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100 min-h-[400px]">
             <h4 class="text-xs font-black text-slate-400 uppercase tracking-[0.3em] mb-6 flex items-center">
               <span class="w-8 h-px bg-premium-yellow mr-3"></span>
               À propos de moi
             </h4>
             <p class="text-slate-600 leading-relaxed font-medium mb-8">
               {{ profileData.description || "Aucune description fournie." }}
             </p>

             <h4 class="text-xs font-black text-slate-400 uppercase tracking-[0.3em] mb-6 flex items-center">
               <span class="w-8 h-px bg-premium-blue mr-3"></span>
               Mes Compétences
             </h4>
             <div class="flex flex-wrap gap-3">
               <span v-for="skill in profileData.skills.split(',')" :key="skill" class="px-4 py-2 bg-slate-50 border border-slate-100 rounded-xl text-xs font-bold text-slate-700">
                 {{ skill.trim() }}
               </span>
               <span v-if="!profileData.skills" class="text-slate-400 text-xs italic">Pas de compétences listées.</span>
             </div>
          </div>

          <!-- Form Integrated -->
          <div v-else class="bg-white rounded-[3rem] p-8 md:p-12 shadow-2xl border border-slate-100 overflow-hidden relative">
            <div class="relative z-10">
              <div class="flex items-center justify-between mb-10">
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">
                  {{ mode === 'create' ? 'Configuration de votre profil' : 'Édition de votre profil' }}
                </h2>
                <button @click="cancelEdit" class="p-2 hover:bg-slate-50 rounded-full transition-colors">
                  <X class="w-6 h-6 text-slate-400" />
                </button>
              </div>

              <form @submit.prevent="handleSaveProfile" class="space-y-10">
                
                <!-- Section: Logo/Photo -->
                <div class="flex flex-col items-center justify-center py-6 bg-slate-50/50 rounded-3xl border border-dashed border-slate-200">
                  <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Photo de profil</h3>
                  <PhotoUploader 
                    :current-photo="currentPhotoUrl" 
                    @photo-updated="handlePhotoUpdate" 
                  />
                </div>

                <!-- Section: Perso -->
                <div class="space-y-6">
                  <h3 class="text-sm font-black text-premium-blue uppercase tracking-widest flex items-center">
                    <User class="w-4 h-4 mr-2" />
                    Informations Personnelles
                  </h3>
                  
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                      <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Prénom</label>
                      <input v-model="profileData.first_name" type="text" required class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-premium-blue/5 outline-none transition-all font-bold text-slate-700">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Nom</label>
                        <input v-model="profileData.last_name" type="text" required class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-premium-blue/5 outline-none transition-all font-bold text-slate-700">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Date de naissance</label>
                        <input v-model="profileData.birth_date" type="date" required class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-premium-blue/5 outline-none transition-all font-bold text-slate-700">
                    </div>
                    <div class="space-y-2">
                  <!-- City Selection -->
                  <div class="relative">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2 pl-1">Ville</label>
                    <div class="flex gap-2">
                        <div class="flex-1 relative">
                            <MapPin class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input 
                                v-model="citySearch" 
                                type="text" 
                                :placeholder="profileData.city || 'Rechercher une ville...'"
                                class="w-full pl-11 pr-5 py-3.5 rounded-2xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 outline-none transition-all font-medium text-slate-900 placeholder:text-slate-400"
                            >
                        </div>
                    </div>
                    
                    <!-- City suggestions -->
                    <div v-if="filteredCities.length > 0" class="absolute z-10 w-full mt-2 bg-white border border-slate-100 rounded-2xl shadow-xl overflow-hidden animate-in fade-in slide-in-from-top-2 duration-200">
                        <button 
                            v-for="city in filteredCities" 
                            :key="city"
                            type="button"
                            @click="selectCity(city)"
                            class="w-full px-5 py-3 text-left text-sm font-medium hover:bg-slate-50 transition-colors flex items-center justify-between group"
                        >
                            <span>{{ city }}</span>
                            <Plus class="w-4 h-4 text-slate-300 group-hover:text-slate-900" />
                        </button>
                    </div>
                  </div>

                  <!-- Address Field -->
                  <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2 pl-1">{{ $t('provider_dashboard.profile.address') }}</label>
                    <input v-model="profileData.address" type="text" class="w-full px-5 py-3.5 rounded-2xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 outline-none transition-all font-medium text-slate-900 placeholder:text-slate-400">
                  </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">CIN</label>
                        <input v-model="profileData.cin" type="text" required class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-premium-blue/5 outline-none transition-all font-bold text-slate-700">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Téléphone</label>
                        <input v-model="profileData.phone" type="text" required class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-premium-blue/5 outline-none transition-all font-bold text-slate-700">
                    </div>
                        <input v-model="profileData.city" type="text" required class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-premium-blue/5 outline-none transition-all font-bold text-slate-700">
                    </div>
                  </div>
                  
                  <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Bio / Présentation</label>
                    <textarea v-model="profileData.description" rows="4" required class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-premium-blue/5 outline-none transition-all font-medium text-slate-700 resize-none"></textarea>
                  </div>
                </div>

                <div class="w-full h-px bg-slate-100 my-4"></div>

                <!-- Section: Pro -->
                <div class="space-y-6">
                  <h3 class="text-sm font-black text-premium-brown uppercase tracking-widest flex items-center">
                    <Briefcase class="w-4 h-4 mr-2" />
                    Informations Professionnelles
                  </h3>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                      <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Catégorie principal</label>
                      <select v-model="profileData.category_id" required class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-premium-blue/5 outline-none transition-all font-bold text-slate-700 appearance-none">
                        <option :value="null" disabled>Sélectionner une catégorie</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                      </select>
                    </div>
                    <div class="space-y-2">
                      <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Années d'expérience</label>
                      <input v-model="profileData.experience" type="number" required class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-premium-blue/5 outline-none transition-all font-bold text-slate-700">
                    </div>
                    <div class="space-y-2">
                      <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Tarif (Prestation salarial)</label>
                      <div class="relative">
                        <input v-model="profileData.hourly_rate" type="number" step="0.01" required class="w-full pl-12 pr-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-premium-blue/5 outline-none transition-all font-bold text-slate-700">
                        <Coins class="w-5 h-5 text-slate-300 absolute left-4 top-1/2 -translate-y-1/2" />
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-black text-slate-400">MAD / H</span>
                      </div>
                    </div>
                  </div>

                  <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Compétences (séparées par des virgules)</label>
                    <input v-model="profileData.skills" type="text" placeholder="Plomberie, Soudure, Évaluation de fuites..." class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-premium-blue/5 outline-none transition-all font-bold text-slate-700">
                  </div>
                </div>

                <div class="w-full h-px bg-slate-100 my-4"></div>

                <!-- Section: Dispo -->
                <div class="space-y-6">
                  <h3 class="text-sm font-black text-amber-600 uppercase tracking-widest flex items-center">
                    <Clock class="w-4 h-4 mr-2" />
                    Disponibilités immédiates
                  </h3>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                      <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Date</label>
                      <input v-model="profileData.availabilities.date" type="date" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-premium-blue/5 outline-none transition-all font-bold text-slate-700">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Heure</label>
                        <input v-model="profileData.availabilities.time" type="time" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-premium-blue/5 outline-none transition-all font-bold text-slate-700">
                    </div>
                  </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end space-x-4 pt-6">
                  <button type="button" @click="cancelEdit" class="px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-colors">
                    Annuler
                  </button>
                  <button type="submit" :disabled="saving" class="bg-premium-blue text-white px-10 py-4 rounded-2xl font-black text-xs uppercase tracking-[0.2em] shadow-2xl shadow-blue-900/20 hover:scale-[1.02] active:scale-95 transition-all flex items-center">
                    <Loader2 v-if="saving" class="w-5 h-5 mr-3 animate-spin" />
                    <span>{{ mode === 'create' ? 'Créer mon profil' : 'Sauvegarder les modifications' }}</span>
                  </button>
                </div>

              </form>
            </div>
            
            <!-- Decor -->
            <div class="absolute -top-24 -left-24 w-64 h-64 bg-premium-yellow/5 rounded-full blur-3xl"></div>
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
