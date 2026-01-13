<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../services/api';
import { 
  Plus, Save, ArrowLeft, Loader2, Calendar, MapPin, 
  DollarSign, Tag, Briefcase, AlertCircle 
} from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const loading = ref(false);
const saving = ref(false);
const categories = ref([]);
const offerId = route.params.id;
const isEditing = !!offerId;

const form = ref({
  category_id: '',
  title: '',
  description: '',
  requirements: '',
  estimated_duration: '',
  material_required: '',
  budget: '',
  location: '',
  desired_date: '',
});

onMounted(async () => {
  loading.value = true;
  try {
    const catRes = await api.get('/api/offers/categories');
    categories.value = catRes.data;

    if (isEditing) {
      const offRes = await api.get(`/api/offers/${offerId}`);
      const data = offRes.data;
      form.value = {
        category_id: data.category_id,
        title: data.title,
        description: data.description,
        requirements: data.requirements || '',
        estimated_duration: data.estimated_duration || '',
        material_required: data.material_required || '',
        budget: data.budget,
        location: data.location,
        desired_date: data.desired_date,
      };
    }
  } catch (err) {
    console.error('Erreur chargement:', err);
  } finally {
    loading.value = false;
  }
});

const handleSubmit = async () => {
  saving.value = true;
  try {
    if (isEditing) {
      await api.put(`/api/offers/${offerId}`, form.value);
    } else {
      await api.post('/api/offers', form.value);
    }
    router.push('/dashboard-client');
  } catch (err) {
    alert(err.response?.data?.message || 'Une erreur est survenue');
  } finally {
    saving.value = false;
  }
};
</script>

<template>
  <div class="max-w-4xl mx-auto px-4 py-12">
    <div class="flex items-center justify-between mb-8">
      <div class="flex items-center space-x-4">
        <button @click="router.back()" class="p-2 hover:bg-gray-100 rounded-full transition text-gray-500">
          <ArrowLeft class="w-6 h-6" />
        </button>
        <h1 class="text-3xl font-bold text-gray-900">
          {{ isEditing ? 'Modifier l\'offre' : 'Publier une offre' }}
        </h1>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center items-center h-64">
      <Loader2 class="w-12 h-12 text-blue-600 animate-spin" />
    </div>

    <form v-else @submit.prevent="handleSubmit" class="space-y-8">
      <!-- Section: Informations de base -->
      <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
        <div class="flex items-center space-x-2 text-blue-600">
          <Tag class="w-5 h-5" />
          <h3 class="font-bold uppercase tracking-wider text-sm">Informations de base</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="md:col-span-2">
            <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Titre de l'offre</label>
            <input 
              v-model="form.title" 
              type="text" 
              required
              placeholder="Ex: Recherche plombier pour fuite évier"
              class="w-full px-4 py-3.5 rounded-2xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition"
            >
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Catégorie</label>
            <select 
              v-model="form.category_id" 
              required
              class="w-full px-4 py-3.5 rounded-2xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition appearance-none"
            >
              <option value="" disabled>Choisir une catégorie</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Budget estimé (€)</label>
            <div class="relative">
              <DollarSign class="absolute left-4 top-3.5 w-5 h-5 text-gray-400" />
              <input 
                v-model="form.budget" 
                type="number" 
                placeholder="0.00"
                class="w-full pl-12 pr-4 py-3.5 rounded-2xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition"
              >
            </div>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Description du travail</label>
          <textarea 
            v-model="form.description" 
            rows="5" 
            required
            placeholder="Décrivez précisément votre besoin..."
            class="w-full px-4 py-3.5 rounded-2xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition"
          ></textarea>
        </div>
      </div>

      <!-- Section: Détails techniques -->
      <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
        <div class="flex items-center space-x-2 text-blue-600">
          <AlertCircle class="w-5 h-5" />
          <h3 class="font-bold uppercase tracking-wider text-sm">Informations complémentaires</h3>
        </div>

        <div class="space-y-6">
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Exigences particulières</label>
            <textarea 
              v-model="form.requirements" 
              rows="3" 
              placeholder="Ex: Expérience de 5 ans minimum, Certification RGE..."
              class="w-full px-4 py-3.5 rounded-2xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition"
            ></textarea>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Durée estimée</label>
              <input 
                v-model="form.estimated_duration" 
                type="text" 
                placeholder="Ex: 2 heures, 3 jours..."
                class="w-full px-4 py-3.5 rounded-2xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition"
              >
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Matériel requis</label>
              <input 
                v-model="form.material_required" 
                type="text" 
                placeholder="Ex: Échelle, OUTILS spécialisés..."
                class="w-full px-4 py-3.5 rounded-2xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition"
              >
            </div>
          </div>
        </div>
      </div>

      <!-- Section: Localisation et Date -->
      <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
        <div class="flex items-center space-x-2 text-blue-600">
          <MapPin class="w-5 h-5" />
          <h3 class="font-bold uppercase tracking-wider text-sm">Logistique</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Localisation</label>
            <div class="relative">
              <MapPin class="absolute left-4 top-3.5 w-5 h-5 text-gray-400" />
              <input 
                v-model="form.location" 
                type="text" 
                placeholder="Ville ou adresse"
                class="w-full pl-12 pr-4 py-3.5 rounded-2xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition"
              >
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Date souhaitée</label>
            <div class="relative">
              <Calendar class="absolute left-4 top-3.5 w-5 h-5 text-gray-400" />
              <input 
                v-model="form.desired_date" 
                type="date" 
                class="w-full pl-12 pr-4 py-3.5 rounded-2xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition"
              >
            </div>
          </div>
        </div>
      </div>

      <div class="flex justify-end pt-4">
        <button 
          type="submit" 
          :disabled="saving"
          class="bg-blue-600 text-white px-10 py-4 rounded-2xl font-bold text-lg hover:bg-blue-700 shadow-xl shadow-blue-200 transition active:scale-[0.98] disabled:opacity-50 flex items-center space-x-2"
        >
          <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
          <Save v-else class="w-5 h-5" />
          <span>{{ isEditing ? 'Enregistrer les modifications' : 'Publier l\'offre' }}</span>
        </button>
      </div>
    </form>
  </div>
</template>
