<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import { 
  User, Mail, Phone, MapPin, Briefcase, GraduationCap, 
  Calendar, Eye, EyeOff, Camera, Check, Loader2, Save 
} from 'lucide-vue-next';

const auth = useAuthStore();
const loading = ref(false);
const saving = ref(false);
const message = ref({ type: '', text: '' });

const form = ref({
  first_name: '',
  last_name: '',
  phone: '',
  address: '',
  skills: '',
  description: '',
  experience: '',
  diplomas: '',
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

onMounted(async () => {
  loading.value = true;
  try {
    if (!auth.user) await auth.fetchUser();
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
      availabilities: user.prestataire?.availabilities || form.value.availabilities
    };
    isVisible.value = user.prestataire?.is_visible ?? true;
    if (user.prestataire?.photo) {
      photoPreview.value = `http://localhost:8000/storage/${user.prestataire.photo}`;
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
  try {
    // Save profile info
    await api.post('/api/provider/profile', form.value);
    
    // Save photo if changed
    if (photoFile.value) {
      const formData = new FormData();
      formData.append('photo', photoFile.value);
      await api.post('/api/provider/photo', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
    }

    // Save availabilities
    await api.post('/api/provider/availability', { availabilities: form.value.availabilities });

    message.value = { type: 'success', text: 'Profil mis à jour avec succès !' };
    await auth.fetchUser();
  } catch (err) {
    message.value = { type: 'error', text: err.response?.data?.message || 'Erreur lors de la sauvegarde' };
  } finally {
    saving.value = false;
  }
};

const toggleVisibility = async () => {
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
            <p class="text-gray-500">{{ auth.user?.role === 'provider' ? 'Prestataire' : 'Client' }}</p>
          </div>
        </div>
        
        <button 
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
        <!-- Left Column: Navigation or Summary -->
        <div class="lg:col-span-1 space-y-6">
          <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
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
        </div>

        <!-- Right Column: Forms -->
        <div class="lg:col-span-2 space-y-8">
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
          <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
            <div class="flex items-center space-x-2 text-blue-600 mb-2">
              <Briefcase class="w-5 h-5" />
              <h3 class="font-bold uppercase tracking-wider text-sm">Profil Professionnel</h3>
            </div>
            <div class="space-y-6">
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
      </div>
    </div>
  </div>
</template>
