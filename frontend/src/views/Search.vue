<script setup>
import { ref, onMounted, watch } from 'vue';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import { 
  Search, Filter, MapPin, Briefcase, Calendar, 
  ChevronRight, Euro, Clock, X, Loader2, Info, Check
} from 'lucide-vue-next';

const auth = useAuthStore();
const offers = ref([]);
const categories = ref([]);
const loading = ref(false);
const selectedOffer = ref(null);
const showFilters = ref(false);

const filters = ref({
  keyword: '',
  category_id: '',
  location: '',
  min_budget: '',
  max_budget: '',
  desired_date: ''
});

const applyMessage = ref('');
const applying = ref(false);
const applySuccess = ref(false);

const submitApplication = async () => {
  if (!selectedOffer.value) return;
  applying.value = true;
  try {
    await api.post('/api/apply', {
      service_offer_id: selectedOffer.value.id,
      message: applyMessage.value
    });
    applySuccess.value = true;
    setTimeout(() => {
      closeOffer();
    }, 2000);
  } catch (err) {
    alert(err.response?.data?.message || 'Erreur lors de la candidature');
  } finally {
    applying.value = false;
  }
};

const fetchOffers = async () => {
  loading.value = true;
  try {
    const params = {};
    Object.keys(filters.value).forEach(key => {
      if (filters.value[key]) params[key] = filters.value[key];
    });
    
    const response = await api.get('/api/offers', { params });
    offers.value = response.data.data;
  } catch (err) {
    console.error('Erreur lors du chargement des offres:', err);
  } finally {
    loading.value = false;
  }
};

const fetchCategories = async () => {
  try {
    const response = await api.get('/api/offers/categories');
    categories.value = response.data;
  } catch (err) {
    console.error('Erreur lors du chargement des catégories:', err);
  }
};

onMounted(() => {
  fetchOffers();
  fetchCategories();
});

// Debounce search
let timeout = null;
watch(filters, () => {
  clearTimeout(timeout);
  timeout = setTimeout(() => {
    fetchOffers();
  }, 500);
}, { deep: true });

const openOffer = (offer) => {
  selectedOffer.value = offer;
  applyMessage.value = '';
  applySuccess.value = false;
};

const closeOffer = () => {
  selectedOffer.value = null;
  applyMessage.value = '';
  applySuccess.value = false;
};

const resetFilters = () => {
  filters.value = {
    keyword: '',
    category_id: '',
    location: '',
    min_budget: '',
    max_budget: '',
    desired_date: ''
  };
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
};
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-10">
      <h1 class="text-3xl font-bold text-gray-900">Trouver une mission</h1>
      <p class="text-gray-500 mt-2">Explorez les opportunités qui correspondent à vos compétences.</p>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-sm mb-8 space-y-4 md:space-y-0 md:flex md:items-center md:space-x-4">
      <div class="relative flex-grow">
        <Search class="absolute left-4 top-3.5 w-5 h-5 text-gray-400" />
        <input 
          v-model="filters.keyword"
          type="text" 
          placeholder="Mots-clés (ex: Plomberie, Peinture...)" 
          class="w-full pl-12 pr-4 py-3.5 rounded-2xl border-none bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition text-sm"
        >
      </div>
      
      <div class="flex items-center space-x-2">
        <button 
          @click="showFilters = !showFilters"
          :class="showFilters ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
          class="p-3.5 rounded-2xl transition flex items-center space-x-2 text-sm font-semibold"
        >
          <Filter class="w-5 h-5" />
          <span class="hidden sm:inline">Filtres</span>
        </button>
        <button 
          v-if="Object.values(filters).some(v => v)"
          @click="resetFilters"
          class="text-xs font-bold text-gray-400 uppercase tracking-wider hover:text-red-500 transition"
        >
          Effacer
        </button>
      </div>
    </div>

    <!-- Expanded Filters -->
    <div v-if="showFilters" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8 p-6 bg-gray-50 rounded-3xl border border-gray-100 animate-in slide-in-from-top-4 duration-300">
      <div class="lg:col-span-2">
        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Catégorie</label>
        <div class="relative">
             <select v-model="filters.category_id" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white focus:ring-2 focus:ring-blue-500 outline-none text-sm appearance-none">
              <option value="">Toutes les catégories</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
            <ChevronRight class="absolute right-4 top-3.5 w-4 h-4 text-gray-400 rotate-90 pointer-events-none" />
        </div>
      </div>
      <div>
        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Localisation</label>
        <input v-model="filters.location" type="text" placeholder="Ville..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white focus:ring-2 focus:ring-blue-500 outline-none text-sm">
      </div>
      <div>
        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Budget Min (€)</label>
        <input v-model="filters.min_budget" type="number" placeholder="Ex: 50" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white focus:ring-2 focus:ring-blue-500 outline-none text-sm">
      </div>
      <div>
        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Date (au plus tôt)</label>
        <input v-model="filters.desired_date" type="date" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white focus:ring-2 focus:ring-blue-500 outline-none text-sm">
      </div>
    </div>

    <!-- Results -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <Loader2 class="w-12 h-12 text-blue-600 animate-spin" />
    </div>

    <div v-else-if="offers.length === 0" class="text-center py-20 bg-white rounded-[40px] border border-dashed border-gray-200">
      <div class="bg-gray-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
        <Search class="w-10 h-10 text-gray-300" />
      </div>
      <h3 class="text-xl font-bold text-gray-900">Aucune offre trouvée</h3>
      <p class="text-gray-500 mt-2">Essayez d'ajuster vos filtres ou vos mots-clés.</p>
      <button @click="resetFilters" class="mt-6 text-blue-600 font-bold hover:underline">Voir toutes les offres</button>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div 
        v-for="offer in offers" 
        :key="offer.id"
        @click="openOffer(offer)"
        class="bg-white p-6 rounded-[32px] border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition duration-300 cursor-pointer group"
      >
        <div class="flex justify-between items-start mb-6">
          <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition duration-300">
             <Briefcase class="w-6 h-6" />
          </div>
          <span class="px-3 py-1 bg-green-50 text-green-700 rounded-full text-[10px] font-bold uppercase tracking-wider">
            {{ offer.budget }} €
          </span>
        </div>
        
        <h3 class="text-lg font-bold text-gray-900 mb-2 truncate group-hover:text-blue-600 transition">{{ offer.title }}</h3>
        <p class="text-sm text-gray-500 line-clamp-2 mb-6 h-10">{{ offer.description }}</p>
        
        <div class="space-y-3 pt-6 border-t border-gray-50">
          <div class="flex items-center text-xs text-gray-500">
            <MapPin class="w-4 h-4 mr-2 text-gray-400" />
            {{ offer.location }}
          </div>
          <div class="flex items-center text-xs text-gray-500">
            <Calendar class="w-4 h-4 mr-2 text-gray-400" />
            {{ formatDate(offer.desired_date) }}
          </div>
          <div class="flex items-center text-xs text-gray-500">
            <Clock class="w-4 h-4 mr-2 text-gray-400" />
            Publié par {{ offer.user.name }}
          </div>
        </div>
      </div>
    </div>

    <!-- Offer Detail Modal -->
    <div v-if="selectedOffer" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeOffer"></div>
      <div class="relative bg-white w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-[40px] shadow-2xl animate-in fade-in zoom-in duration-300">
        <button @click="closeOffer" class="absolute top-6 right-6 p-2 rounded-2xl bg-gray-100 text-gray-500 hover:bg-red-50 hover:text-red-600 transition z-10">
          <X class="w-6 h-6" />
        </button>
        
        <div class="p-10">
          <div class="flex items-center space-x-3 text-blue-600 mb-4">
             <span class="px-3 py-1 bg-blue-50 rounded-full text-[10px] font-bold uppercase tracking-wider">{{ selectedOffer.category.name }}</span>
          </div>
          <h2 class="text-3xl font-extrabold text-gray-900 mb-6">{{ selectedOffer.title }}</h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-gray-50 p-4 rounded-2xl">
              <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Budget Proposé</p>
              <p class="text-xl font-bold text-gray-900">{{ selectedOffer.budget }} €</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-2xl">
              <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Date souhaitée</p>
              <p class="text-xl font-bold text-gray-900">{{ formatDate(selectedOffer.desired_date) }}</p>
            </div>
            <div v-if="selectedOffer.nature_of_need" class="bg-blue-50/50 p-4 rounded-2xl border border-blue-100">
              <p class="text-[10px] font-bold text-blue-600 uppercase tracking-widest mb-1">Nature du besoin</p>
              <p class="text-lg font-bold text-gray-900">{{ selectedOffer.nature_of_need }}</p>
            </div>
            <div v-if="selectedOffer.estimated_duration" class="bg-blue-50/50 p-4 rounded-2xl border border-blue-100">
              <p class="text-[10px] font-bold text-blue-600 uppercase tracking-widest mb-1">Durée estimée</p>
              <p class="text-lg font-bold text-gray-900">{{ selectedOffer.estimated_duration }}</p>
            </div>
          </div>

          <div class="space-y-6">
            <div>
              <h4 class="font-bold text-gray-900 mb-2 flex items-center">
                <Info class="w-4 h-4 mr-2 text-blue-600" />
                Description de la mission
              </h4>
              <p class="text-gray-600 leading-relaxed">{{ selectedOffer.description }}</p>
            </div>

            <div v-if="selectedOffer.material_required" class="bg-purple-50/50 p-6 rounded-2xl border border-purple-100">
              <h4 class="font-bold text-purple-900 mb-2 flex items-center">
                <Briefcase class="w-4 h-4 mr-2" />
                Matériel requis
              </h4>
              <p class="text-sm text-purple-800">{{ selectedOffer.material_required }}</p>
            </div>

            <div v-if="selectedOffer.requirements" class="bg-gray-50 p-6 rounded-2xl border border-gray-200">
              <h4 class="font-bold text-gray-900 mb-2 flex items-center">
                <Check class="w-4 h-4 mr-2 text-green-600" />
                Critères & Pré-requis
              </h4>
              <p class="text-sm text-gray-600 italic">{{ selectedOffer.requirements }}</p>
            </div>

            <!-- Apply Form -->
            <div v-if="!applySuccess" class="pt-6 border-t border-gray-100">
              <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Message d'accompagnement (facultatif)</label>
              <textarea 
                v-model="applyMessage"
                rows="3" 
                placeholder="Expliquez pourquoi vous êtes le meilleur candidat pour cette mission..."
                class="w-full px-4 py-3 rounded-2xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition text-sm"
              ></textarea>
            </div>

            <div v-else class="pt-6 border-t border-gray-100 text-center py-4">
              <div class="bg-green-50 text-green-700 p-4 rounded-2xl flex items-center justify-center space-x-2">
                <Check class="w-5 h-5" />
                <span class="font-bold">Candidature envoyée avec succès !</span>
              </div>
            </div>
            
            <div class="flex items-center space-x-6 pt-6 border-t border-gray-100">
              <div class="flex items-center text-sm text-gray-500">
                <MapPin class="w-5 h-5 mr-2 text-blue-600" />
                {{ selectedOffer.location }}
              </div>
              <div class="flex items-center text-sm text-gray-500">
                <Briefcase class="w-5 h-5 mr-2 text-blue-600" />
                Client: {{ selectedOffer.user.name }}
              </div>
            </div>
          </div>

          <div class="mt-10 flex space-x-4">
            <button 
              v-if="!applySuccess"
              @click="submitApplication"
              :disabled="applying"
              class="flex-grow bg-blue-600 text-white py-5 rounded-2xl font-bold hover:bg-blue-700 shadow-xl shadow-blue-100 transition disabled:opacity-50 flex items-center justify-center space-x-2"
            >
              <Loader2 v-if="applying" class="w-5 h-5 animate-spin" />
              <span>{{ applying ? 'Envoi...' : 'Postuler à cette offre' }}</span>
            </button>
            <button @click="closeOffer" class="px-8 py-5 bg-gray-100 text-gray-700 rounded-2xl font-bold hover:bg-gray-200 transition">
              Fermer
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
