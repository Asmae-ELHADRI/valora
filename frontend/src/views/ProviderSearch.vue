<script setup>
import { ref, onMounted, watch } from 'vue';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import { 
  Search, Filter, MapPin, Star, Briefcase, 
  ChevronRight, X, Loader2, Info, User,
  MessageSquare, GraduationCap, Award
} from 'lucide-vue-next';
import { useRouter } from 'vue-router';

const auth = useAuthStore();
const router = useRouter();
const providers = ref([]);
const categories = ref([]);
const loading = ref(false);
const selectedProvider = ref(null);
const showFilters = ref(false);

const filters = ref({
  keyword: '',
  category_id: '',
  location: '',
  min_rating: ''
});

const fetchProviders = async () => {
  loading.value = true;
  try {
    const params = {};
    Object.keys(filters.value).forEach(key => {
      if (filters.value[key]) params[key] = filters.value[key];
    });
    
    const response = await api.get('/api/provider', { params });
    providers.value = response.data.data;
  } catch (err) {
    console.error('Erreur lors du chargement des prestataires:', err);
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
  fetchProviders();
  fetchCategories();
});

// Debounce search
let timeout = null;
watch(filters, () => {
  clearTimeout(timeout);
  timeout = setTimeout(() => {
    fetchProviders();
  }, 500);
}, { deep: true });

const openProvider = async (provider) => {
  try {
    const response = await api.get(`/api/provider/${provider.id}`);
    selectedProvider.value = response.data;
  } catch (err) {
    console.error('Erreur lors du chargement du profil:', err);
  }
};

const closeProvider = () => {
  selectedProvider.value = null;
};

const resetFilters = () => {
  filters.value = {
    keyword: '',
    category_id: '',
    location: '',
    min_rating: ''
  };
};

const startConversation = (providerId) => {
  router.push(`/messages?userId=${providerId}`);
};
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-10">
      <h1 class="text-3xl font-bold text-gray-900">Trouver un prestataire</h1>
      <p class="text-gray-500 mt-2">Découvrez les meilleurs talents pour vos projets.</p>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-sm mb-8 space-y-4 md:space-y-0 md:flex md:items-center md:space-x-4">
      <div class="relative flex-grow">
        <Search class="absolute left-4 top-3.5 w-5 h-5 text-gray-400" />
        <input 
          v-model="filters.keyword"
          type="text" 
          placeholder="Mots-clés (Compétence, métier, outil...)" 
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
    <div v-if="showFilters" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8 p-6 bg-gray-50 rounded-3xl border border-gray-100">
      <div>
        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Catégorie</label>
        <select v-model="filters.category_id" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white focus:ring-2 focus:ring-blue-500 outline-none text-sm">
          <option value="">Toutes les catégories</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Localisation</label>
        <input v-model="filters.location" type="text" placeholder="Ville ou Code Postal" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white focus:ring-2 focus:ring-blue-500 outline-none text-sm">
      </div>
      <div>
        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Note Minimale</label>
        <select v-model="filters.min_rating" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white focus:ring-2 focus:ring-blue-500 outline-none text-sm">
          <option value="">Toutes les notes</option>
          <option v-for="r in [4, 3, 2, 1]" :key="r" :value="r">{{ r }}+ étoiles</option>
        </select>
      </div>
    </div>

    <!-- Results -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <Loader2 class="w-12 h-12 text-blue-600 animate-spin" />
    </div>

    <div v-else-if="providers.length === 0" class="text-center py-20 bg-white rounded-[40px] border border-dashed border-gray-200">
      <div class="bg-gray-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
        <User class="w-10 h-10 text-gray-300" />
      </div>
      <h3 class="text-xl font-bold text-gray-900">Aucun prestataire trouvé</h3>
      <p class="text-gray-500 mt-2">Essayez d'ajuster vos filtres ou vos mots-clés.</p>
      <button @click="resetFilters" class="mt-6 text-blue-600 font-bold hover:underline">Voir tous les prestataires</button>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <div 
        v-for="provider in providers" 
        :key="provider.id"
        @click="openProvider(provider)"
        class="bg-white p-6 rounded-[32px] border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition duration-300 cursor-pointer group flex flex-col items-center text-center"
      >
        <div class="relative mb-4">
          <div class="w-24 h-24 rounded-[32px] bg-gray-100 overflow-hidden border-4 border-white shadow-md">
            <img v-if="provider.prestataire?.photo" :src="`http://localhost:8000/storage/${provider.prestataire.photo}`" class="w-full h-full object-cover">
            <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
              <User class="w-10 h-10" />
            </div>
          </div>
          <div v-if="provider.prestataire?.rating > 0" class="absolute -bottom-2 -right-2 bg-yellow-400 text-white text-[10px] font-black px-2 py-1 rounded-lg flex items-center shadow-lg">
            <Star class="w-3 h-3 fill-current mr-1" />
            {{ parseFloat(provider.prestataire.rating).toFixed(1) }}
          </div>
        </div>
        
        <h3 class="text-lg font-bold text-gray-900 mb-1 group-hover:text-blue-600 transition">{{ provider.name }}</h3>
        <p class="text-xs font-bold text-blue-600 uppercase tracking-widest mb-4 bg-blue-50 px-3 py-1 rounded-lg">
          {{ provider.prestataire?.category?.name || 'Prestataire' }}
        </p>
        
        <p class="text-sm text-gray-500 line-clamp-2 mb-6 h-10">{{ provider.prestataire?.description || 'Pas de description disponible.' }}</p>
        
        <div class="flex items-center text-xs text-gray-400 mt-auto">
          <MapPin class="w-4 h-4 mr-1.5" />
          {{ provider.address || 'Localisation non précisée' }}
        </div>
      </div>
    </div>

    <!-- Provider Detail Modal -->
    <div v-if="selectedProvider" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeProvider"></div>
      <div class="relative bg-white w-full max-w-3xl rounded-[40px] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300 max-h-[90vh] flex flex-col">
        <button @click="closeProvider" class="absolute top-6 right-6 p-2 rounded-2xl bg-gray-100 text-gray-500 hover:bg-red-50 hover:text-red-600 transition z-10">
          <X class="w-6 h-6" />
        </button>
        
        <div class="overflow-y-auto p-10">
          <div class="flex flex-col md:flex-row items-center md:items-start gap-8 mb-10">
            <div class="w-40 h-40 rounded-[40px] bg-gray-100 overflow-hidden border-8 border-gray-50 shadow-inner flex-shrink-0">
              <img v-if="selectedProvider.prestataire?.photo" :src="`http://localhost:8000/storage/${selectedProvider.prestataire.photo}`" class="w-full h-full object-cover">
              <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                <User class="w-16 h-16" />
              </div>
            </div>
            
            <div class="flex-grow text-center md:text-left space-y-4">
              <div>
                <h2 class="text-4xl font-black text-gray-900 leading-tight">{{ selectedProvider.name }}</h2>
                <div class="flex flex-wrap justify-center md:justify-start gap-2 mt-2">
                  <span class="px-4 py-1.5 bg-blue-600 text-white rounded-xl text-[10px] font-bold uppercase tracking-widest shadow-lg shadow-blue-200">
                    {{ selectedProvider.prestataire?.category?.name || 'Prestataire' }}
                  </span>
                  <div v-if="selectedProvider.prestataire?.rating > 0" class="flex items-center bg-yellow-50 text-yellow-700 px-3 py-1.5 rounded-xl text-xs font-bold ring-1 ring-yellow-200">
                    <Star class="w-4 h-4 fill-current mr-1.5 text-yellow-400" />
                    {{ parseFloat(selectedProvider.prestataire.rating).toFixed(1) }} / 5
                  </div>
                </div>
              </div>
              
              <div class="flex flex-wrap justify-center md:justify-start gap-6 text-sm text-gray-500 font-medium">
                <div class="flex items-center">
                  <MapPin class="w-5 h-5 mr-2 text-blue-600" />
                  {{ selectedProvider.address || 'Localisation non précisée' }}
                </div>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
            <div class="space-y-8">
              <div>
                <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4 flex items-center">
                  <Info class="w-4 h-4 mr-2 text-blue-600" />
                  A propos
                </h4>
                <p class="text-gray-600 leading-relaxed">{{ selectedProvider.prestataire?.description || 'Aucune description.' }}</p>
              </div>

              <div>
                <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4 flex items-center">
                  <Award class="w-4 h-4 mr-2 text-blue-600" />
                  Compétences
                </h4>
                <div class="flex flex-wrap gap-2">
                  <span v-for="skill in (selectedProvider.prestataire?.skills || '').split(',')" :key="skill" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl text-xs font-bold hover:bg-gray-200 transition">
                    {{ skill.trim() }}
                  </span>
                </div>
              </div>
            </div>

            <div class="space-y-8">
              <div>
                <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4 flex items-center">
                  <GraduationCap class="w-4 h-4 mr-2 text-blue-600" />
                  Expériences & Diplômes
                </h4>
                <div class="space-y-4">
                  <div class="bg-blue-50/50 p-4 rounded-2xl border border-blue-100">
                    <p class="text-xs font-bold text-blue-600 uppercase mb-1">Expérience</p>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ selectedProvider.prestataire?.experience || 'Non renseigné' }}</p>
                  </div>
                  <div class="bg-purple-50/50 p-4 rounded-2xl border border-purple-100">
                    <p class="text-xs font-bold text-purple-600 uppercase mb-1">Diplômes</p>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ selectedProvider.prestataire?.diplomas || 'Non renseigné' }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Reviews Section -->
          <div class="mt-12 pt-12 border-t border-gray-100">
            <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-8 flex items-center">
              <Star class="w-4 h-4 mr-2 text-yellow-400" />
              Avis des clients ({{ selectedProvider.received_reviews?.length || 0 }})
            </h4>
            
            <div v-if="selectedProvider.received_reviews?.length > 0" class="space-y-6">
              <div v-for="review in selectedProvider.received_reviews" :key="review.id" class="p-6 bg-gray-50 rounded-3xl border border-gray-100">
                <div class="flex justify-between items-start mb-4">
                  <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center font-bold text-blue-600 border border-gray-100">
                      {{ review.reviewer?.name?.charAt(0) }}
                    </div>
                    <div>
                      <p class="font-bold text-gray-900">{{ review.reviewer?.name }}</p>
                      <p class="text-[10px] text-gray-400">{{ new Date(review.created_at).toLocaleDateString() }}</p>
                    </div>
                  </div>
                  <div class="flex text-yellow-400">
                    <Star v-for="i in 5" :key="i" :class="i <= review.rating ? 'fill-current' : 'text-gray-200'" class="w-3 h-3" />
                  </div>
                </div>
                <p class="text-sm text-gray-600 leading-relaxed italic">"{{ review.comment }}"</p>
              </div>
            </div>
            <div v-else class="text-center py-10 text-gray-400 text-sm">
              Aucun avis pour le moment.
            </div>
          </div>
        </div>

        <div class="p-10 bg-gray-50 border-t border-gray-100 flex gap-4">
          <button 
            @click="startConversation(selectedProvider.id)"
            class="flex-grow bg-blue-600 text-white py-5 rounded-3xl font-bold hover:bg-blue-700 shadow-xl shadow-blue-100 transition active:scale-[0.98] flex items-center justify-center space-x-3"
          >
            <MessageSquare class="w-6 h-6" />
            <span>Contacter ce prestataire</span>
          </button>
          <button @click="closeProvider" class="px-10 py-5 bg-white text-gray-700 border border-gray-200 rounded-3xl font-bold hover:bg-gray-100 transition">
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
