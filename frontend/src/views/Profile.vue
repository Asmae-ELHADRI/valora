<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import { 
  User, Mail, Phone, MapPin, Briefcase, GraduationCap, 
  Calendar, Eye, EyeOff, Camera, Check, Loader2, Save, Star,
  Lock, AlertTriangle, Trash2
} from 'lucide-vue-next';
import { useRouter } from 'vue-router';

const auth = useAuthStore();
const router = useRouter();
const loading = ref(false);
const saving = ref(false);
const message = ref({ type: '', text: '' });
const activeTab = ref('profile'); // profile, security
const categories = ref([]);

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
  address: '',
  skills: '',
  description: '',
  experience: '',
  diplomas: '',
  category_id: '',
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
const reviewsData = ref({ reviews: [], average_rating: 0, total_reviews: 0 });

const fetchReviews = async () => {
  if (auth.user?.role !== 'provider') return;
  try {
    const response = await api.get('/api/my-reviews');
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
      auth.user ? Promise.resolve() : auth.fetchUser(),
      fetchReviews()
    ]);
    categories.value = catRes.data;
    const user = auth.user;
    form.value = {
      first_name: user.first_name || '',
      last_name: user.last_name || '',
      phone: user.phone || '',
      address: user.address || '',
      skills: user.prestataire?.skills || '',
      description: user.prestataire?.description || '',
      experience: user.prestataire?.experience || '',
      diplomas: user.prestataire?.diplomas || '',
      category_id: user.prestataire?.category_id || '',
      type: user.client?.type || 'individual',
      availabilities: user.prestataire?.availabilities || form.value.availabilities
    };
    isVisible.value = user.prestataire?.is_visible ?? true;
    
    // Photo handling
    const photoPath = user.role === 'provider' ? user.prestataire?.photo : user.client?.photo;
    if (photoPath) {
      photoPreview.value = `http://localhost:8000/storage/${photoPath}`;
    }
  } catch (err) {
    message.value = { type: 'error', text: 'Impossible de charger le profil' };
  } finally {
    loading.value = false;
  }
});

const handlePhotoChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    photoFile.value = file;
    photoPreview.value = URL.createObjectURL(file);
  }
};

const saveProfile = async () => {
  saving.value = true;
  message.value = { type: '', text: '' };
  const isProvider = auth.user.role === 'provider';
  const apiPrefix = isProvider ? '/api/provider' : '/api/client';
  
  try {
    // Save profile info
    await api.post(`${apiPrefix}/profile`, form.value);
    
    // Save photo if changed
    if (photoFile.value) {
      const formData = new FormData();
      formData.append('photo', photoFile.value);
      await api.post(`${apiPrefix}/photo`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
    }

    // Save provider specific data
    if (isProvider) {
      await api.post('/api/provider/availability', { availabilities: form.value.availabilities });
    }

    message.value = { type: 'success', text: 'Profil mis à jour avec succès !' };
    await auth.fetchUser();
  } catch (err) {
    message.value = { type: 'error', text: err.response?.data?.message || 'Erreur lors de la sauvegarde' };
  } finally {
    saving.value = false;
  }
};

const toggleVisibility = async () => {
  if (auth.user.role !== 'provider') return;
  try {
    const response = await api.post('/api/provider/visibility');
    isVisible.value = response.data.is_visible;
  } catch (err) {
    message.value = { type: 'error', text: 'Erreur lors du changement de visibilité' };
  }
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
  <div class="max-w-4xl mx-auto px-4 py-12">
    <div v-if="loading" class="flex justify-center items-center h-64">
      <Loader2 class="w-12 h-12 text-blue-600 animate-spin" />
    </div>

    <div v-else class="space-y-8">
      <!-- Header & Visibility -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
        <div class="flex items-center space-x-4">
          <div class="relative group">
            <div class="w-24 h-24 rounded-2xl bg-gray-100 overflow-hidden border-4 border-white shadow-md">
              <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover">
              <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                <User class="w-12 h-12" />
              </div>
            </div>
            <label class="absolute -bottom-2 -right-2 bg-blue-600 text-white p-2 rounded-xl shadow-lg cursor-pointer hover:bg-blue-700 transition">
              <Camera class="w-4 h-4" />
              <input type="file" @change="handlePhotoChange" class="hidden" accept="image/*">
            </label>
          </div>
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ auth.user?.name }}</h1>
            <div class="flex items-center space-x-2">
              <p class="text-gray-500">{{ auth.user?.role === 'provider' ? 'Prestataire' : 'Client' }}</p>
              <div v-if="reviewsData.total_reviews > 0" class="flex items-center text-yellow-500 text-sm font-bold">
                <Star class="w-4 h-4 fill-current mr-1" />
                {{ reviewsData.average_rating }}
              </div>
            </div>
          </div>
        </div>
        
        <button 
          v-if="auth.user?.role === 'provider'"
          @click="toggleVisibility"
          :class="isVisible ? 'bg-green-50 text-green-700 border-green-100' : 'bg-red-50 text-red-700 border-red-100'"
          class="flex items-center space-x-2 px-4 py-2 rounded-xl border font-medium transition"
        >
          <component :is="isVisible ? Eye : EyeOff" class="w-5 h-5" />
          <span>{{ isVisible ? 'Profil Visible' : 'Profil Masqué' }}</span>
        </button>
      </div>

      <!-- Success/Error Message -->
      <div v-if="message.text" :class="message.type === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'" class="p-4 rounded-2xl text-sm font-medium">
        {{ message.text }}
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column: Navigation -->
        <div class="lg:col-span-1 space-y-6">
          <div class="bg-white p-2 rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
            <button 
              @click="activeTab = 'profile'"
              :class="activeTab === 'profile' ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-500 hover:bg-gray-50'"
              class="w-full flex items-center space-x-3 px-6 py-4 rounded-2xl font-bold transition text-left"
            >
              <User class="w-5 h-5" />
              <span>Mon Profil</span>
            </button>
            <button 
              @click="activeTab = 'security'"
              :class="activeTab === 'security' ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-500 hover:bg-gray-50'"
              class="w-full flex items-center space-x-3 px-6 py-4 rounded-2xl font-bold transition text-left mt-1"
            >
              <Lock class="w-5 h-5" />
              <span>Sécurité</span>
            </button>
          </div>

          <div v-if="activeTab === 'profile' && auth.user?.role === 'provider'" class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
            <h3 class="font-bold text-gray-900 mb-4">Disponibilités</h3>
            <div class="space-y-3">
              <div v-for="day in days" :key="day.id" class="flex items-center justify-between">
                <span class="text-sm text-gray-600">{{ day.name }}</span>
                <button 
                  @click="form.availabilities[day.id] = !form.availabilities[day.id]"
                  :class="form.availabilities[day.id] ? 'bg-blue-600' : 'bg-gray-200'"
                  class="relative inline-flex h-6 w-11 items-center rounded-full transition"
                >
                  <span :class="form.availabilities[day.id] ? 'translate-x-6' : 'translate-x-1'" class="inline-block h-4 w-4 transform rounded-full bg-white transition" />
                </button>
              </div>
            </div>
          </div>

          <div v-if="activeTab === 'profile' && auth.user?.role === 'client'" class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
            <h3 class="font-bold text-gray-900 mb-4">Type de Client</h3>
            <div class="space-y-3">
              <label class="flex items-center space-x-3 p-3 rounded-xl border border-gray-100 cursor-pointer hover:bg-gray-50 transition" :class="form.type === 'individual' ? 'bg-blue-50 border-blue-200' : ''">
                <input type="radio" v-model="form.type" value="individual" class="text-blue-600 focus:ring-blue-500">
                <span class="text-sm font-medium">Particulier</span>
              </label>
              <label class="flex items-center space-x-3 p-3 rounded-xl border border-gray-100 cursor-pointer hover:bg-gray-50 transition" :class="form.type === 'company' ? 'bg-blue-50 border-blue-200' : ''">
                <input type="radio" v-model="form.type" value="company" class="text-blue-600 focus:ring-blue-500">
                <span class="text-sm font-medium">Entreprise / Pro</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Right Column: Content -->
        <div class="lg:col-span-2 space-y-8">
          <div v-if="activeTab === 'profile'" class="space-y-8">
          <!-- Personal Info -->
          <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
            <div class="flex items-center space-x-2 text-blue-600 mb-2">
              <User class="w-5 h-5" />
              <h3 class="font-bold uppercase tracking-wider text-sm">Informations Personnelles</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Prénom</label>
                <input v-model="form.first_name" type="text" class="w-full px-4 py-3 rounded-xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition">
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Nom</label>
                <input v-model="form.last_name" type="text" class="w-full px-4 py-3 rounded-xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition">
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Téléphone</label>
                <div class="relative">
                  <Phone class="absolute left-3 top-3.5 w-4 h-4 text-gray-400" />
                  <input v-model="form.phone" type="tel" class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Adresse</label>
                <div class="relative">
                  <MapPin class="absolute left-3 top-3.5 w-4 h-4 text-gray-400" />
                  <input v-model="form.address" type="text" class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>
              </div>
            </div>
          </div>

          <!-- Professional Info -->
          <div v-if="auth.user?.role === 'provider'" class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
            <div class="flex items-center space-x-2 text-blue-600 mb-2">
              <Briefcase class="w-5 h-5" />
              <h3 class="font-bold uppercase tracking-wider text-sm">Profil Professionnel</h3>
            </div>
            <div class="space-y-6">
              <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Catégorie de service</label>
                <select v-model="form.category_id" class="w-full px-4 py-3 rounded-xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition appearance-none">
                  <option value="" disabled>Choisir une catégorie</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Compétences (séparées par des virgules)</label>
                <input v-model="form.skills" type="text" placeholder="Plomberie, Électricité..." class="w-full px-4 py-3 rounded-xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition">
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Description</label>
                <textarea v-model="form.description" rows="4" class="w-full px-4 py-3 rounded-xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition"></textarea>
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Expériences</label>
                <textarea v-model="form.experience" rows="3" class="w-full px-4 py-3 rounded-xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition"></textarea>
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Diplômes</label>
                <textarea v-model="form.diplomas" rows="2" class="w-full px-4 py-3 rounded-xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition"></textarea>
              </div>
            </div>
          </div>

          <!-- Reviews List -->
          <div v-if="auth.user?.role === 'provider' && reviewsData.total_reviews > 0" class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
            <div class="flex items-center space-x-2 text-yellow-500 mb-2">
              <Star class="w-5 h-5 fill-current" />
              <h3 class="font-bold uppercase tracking-wider text-sm">Avis et évaluations</h3>
            </div>
            <div class="divide-y divide-gray-50">
              <div v-for="review in reviewsData.reviews" :key="review.id" class="py-4 first:pt-0 last:pb-0">
                <div class="flex justify-between items-center mb-1">
                  <span class="font-bold text-gray-900 text-sm">{{ review.reviewer?.name }}</span>
                  <div class="flex text-yellow-400">
                    <Star v-for="i in 5" :key="i" :class="i <= review.rating ? 'fill-current' : 'text-gray-200'" class="w-3 h-3" />
                  </div>
                </div>
                <p class="text-sm text-gray-600">{{ review.comment }}</p>
                <p class="text-[10px] text-gray-400 mt-1">{{ new Date(review.created_at).toLocaleDateString() }}</p>
              </div>
            </div>
          </div>

          <!-- Save Button -->
          <div class="flex justify-end">
            <button 
              @click="saveProfile" 
              :disabled="saving"
              class="flex items-center space-x-2 bg-blue-600 text-white px-8 py-4 rounded-2xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-100 transition disabled:opacity-50"
            >
              <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
              <Save v-else class="w-5 h-5" />
              <span>{{ saving ? 'Enregistrement...' : 'Enregistrer les modifications' }}</span>
            </button>
          </div>
        </div>

        <!-- Security Tab -->
          <div v-if="activeTab === 'security'" class="space-y-8">
            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
              <div class="flex items-center space-x-2 text-blue-600 mb-2">
                <Lock class="w-5 h-5" />
                <h3 class="font-bold uppercase tracking-wider text-sm">Changer le mot de passe</h3>
              </div>
              <div class="space-y-4">
                <div>
                  <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Mot de passe actuel</label>
                  <input v-model="passwordForm.current_password" type="password" class="w-full px-4 py-3 rounded-xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>
                <div>
                  <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Nouveau mot de passe</label>
                  <input v-model="passwordForm.password" type="password" class="w-full px-4 py-3 rounded-xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>
                <div>
                  <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Confirmer le nouveau mot de passe</label>
                  <input v-model="passwordForm.password_confirmation" type="password" class="w-full px-4 py-3 rounded-xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>
              </div>
              <div class="flex justify-end pt-4">
                <button 
                  @click="updatePassword" 
                  :disabled="saving"
                  class="bg-blue-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-blue-700 transition disabled:opacity-50 flex items-center space-x-2"
                >
                  <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
                  <span>Mettre à jour</span>
                </button>
              </div>
            </div>

            <div class="bg-red-50 p-8 rounded-3xl border border-red-100 space-y-4">
              <div class="flex items-center space-x-2 text-red-600">
                <AlertTriangle class="w-5 h-5" />
                <h3 class="font-bold uppercase tracking-wider text-sm">Zone de danger</h3>
              </div>
              <p class="text-sm text-red-600">La suppression de votre compte est définitive et supprimera toutes vos données (profil, offres, messages, etc.).</p>
              <button 
                @click="deleteAccount"
                class="flex items-center space-x-2 bg-red-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-red-700 transition"
              >
                <Trash2 class="w-4 h-4" />
                <span>Supprimer mon compte définitivement</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
