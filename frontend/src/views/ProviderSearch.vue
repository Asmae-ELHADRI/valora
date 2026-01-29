<script setup>
import { ref, onMounted, watch } from 'vue';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import { 
  Search, Filter, MapPin, Star, Briefcase, 
  ChevronRight, X, Loader2, Info, User,
  MessageSquare, GraduationCap, Award, Send, CheckCircle, AlertCircle,
  ShieldAlert, Ban
} from 'lucide-vue-next';
import { useRouter } from 'vue-router';

const auth = useAuthStore();
const router = useRouter();
const providers = ref([]);
const categories = ref([]);
const loading = ref(false);
const selectedProvider = ref(null);
const showFilters = ref(false);
const showInviteModal = ref(false);
const clientOffers = ref([]);
const selectedOfferId = ref('');
const invitationMessage = ref('');
const inviting = ref(false);

const showReportModal = ref(false);
const reportForm = ref({
  reason: '',
  description: ''
});
const reporting = ref(false);

const showBlockModal = ref(false);
const blocking = ref(false);

const filters = ref({
  keyword: '',
  category_id: '',
  location: '',
  min_rating: '',
  badge_level: '',
  availability_date: '',
  sort_by: 'newest'
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
    min_rating: '',
    badge_level: '',
    availability_date: '',
    sort_by: 'newest'
  };
};

const startConversation = (providerId) => {
  router.push(`/messages?userId=${providerId}`);
};

const fetchClientOffers = async () => {
  try {
    const response = await api.get('/api/offers/my-offers');
    clientOffers.value = response.data.filter(o => o.status === 'open');
  } catch (err) {
    console.error('Erreur chargement offres:', err);
  }
};

const openInviteModal = () => {
  fetchClientOffers();
  showInviteModal.value = true;
};

const submitInvitation = async () => {
  if (!selectedOfferId.value) return;
  
  inviting.value = true;
  try {
    await api.post('/api/invite', {
      service_offer_id: selectedOfferId.value,
      provider_id: selectedProvider.value.id,
      message: invitationMessage.value
    });
    alert('Invitation envoyée !');
    showInviteModal.value = false;
    selectedOfferId.value = '';
    invitationMessage.value = '';
  } catch (err) {
    alert(err.response?.data?.message || 'Erreur lors de l\'envoi');
  } finally {
    inviting.value = false;
  }
};

const submitReport = async () => {
  if (!reportForm.value.reason) return;
  reporting.value = true;
  try {
    await api.post('/api/report', {
      reported_id: selectedProvider.value.id,
      reason: `${reportForm.value.reason}: ${reportForm.value.description}`
    });
    alert('Signalement envoyé. Merci de nous aider à garder VALORA sûr.');
    showReportModal.value = false;
    reportForm.value = { reason: '', description: '' };
  } catch (err) {
    alert(err.response?.data?.message || 'Erreur lors du signalement');
  } finally {
    reporting.value = false;
  }
};

const blockUser = async () => {
  blocking.value = true;
  try {
    await api.post('/api/block', {
      blocked_id: selectedProvider.value.id
    });
    alert('Utilisateur bloqué.');
    showBlockModal.value = false;
    selectedProvider.value = null; // Close profile
    fetchProviders(); // Refresh list to hide blocked user
  } catch (err) {
    alert(err.response?.data?.message || 'Erreur lors du blocage');
  } finally {
    blocking.value = false;
  }
};
const getBadgeClass = (level) => {
    switch (level) {
        case 'Expert': return 'bg-purple-600 text-white shadow-lg shadow-purple-200';
        case 'Confirmé': return 'bg-blue-600 text-white shadow-lg shadow-blue-200';
        default: return 'bg-gray-500 text-white shadow-lg shadow-gray-200';
    }
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
    <div v-if="showFilters" class="mb-8 p-8 bg-gray-50 rounded-[2.5rem] border border-gray-100 shadow-inner">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div>
          <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Catégorie</label>
          <select v-model="filters.category_id" class="w-full px-5 py-3.5 rounded-2xl border-none bg-white shadow-sm focus:ring-2 focus:ring-blue-500 outline-none text-sm font-bold text-gray-700 appearance-none">
            <option value="">Toutes les catégories</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Expérience (Badge)</label>
          <select v-model="filters.badge_level" class="w-full px-5 py-3.5 rounded-2xl border-none bg-white shadow-sm focus:ring-2 focus:ring-blue-500 outline-none text-sm font-bold text-gray-700 appearance-none">
            <option value="">Tous les niveaux</option>
            <option value="Expert">Expert (300+ pts)</option>
            <option value="Confirmé">Confirmé (100+ pts)</option>
            <option value="Débutant">Débutant</option>
          </select>
        </div>
        <div>
          <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Disponible le</label>
          <input v-model="filters.availability_date" type="date" class="w-full px-5 py-3.5 rounded-2xl border-none bg-white shadow-sm focus:ring-2 focus:ring-blue-500 outline-none text-sm font-bold text-gray-700">
        </div>
        <div>
          <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Localisation</label>
          <input v-model="filters.location" type="text" placeholder="Ville..." class="w-full px-5 py-3.5 rounded-2xl border-none bg-white shadow-sm focus:ring-2 focus:ring-blue-500 outline-none text-sm font-bold text-gray-700">
        </div>
      </div>

      <!-- Sorting Bar -->
      <div class="flex flex-wrap items-center gap-4 pt-6 border-t border-gray-200/50">
        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Trier par :</span>
        <div class="flex p-1 bg-white rounded-xl shadow-sm">
          <button 
            v-for="opt in [{k:'newest', l:'Nouveaux'}, {k:'rating', l:'Meilleures Notes'}, {k:'pro_score', l:'Pro Score'}]" 
            :key="opt.k"
            @click="filters.sort_by = opt.k"
            :class="filters.sort_by === opt.k ? 'bg-blue-600 text-white shadow-md shadow-blue-100' : 'text-gray-500 hover:text-gray-900'"
            class="px-4 py-1.5 rounded-lg text-xs font-black transition-all"
          >
            {{ opt.l }}
          </button>
        </div>
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
        
        <h3 class="text-lg font-black text-gray-900 leading-tight group-hover:text-blue-600 transition">{{ provider.name }}</h3>
        <span 
            v-if="provider.prestataire?.badge_level" 
            :class="getBadgeClass(provider.prestataire.badge_level)"
            class="mt-1 px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-widest leading-none"
        >
            {{ provider.prestataire.badge_level }}
        </span>

        <div class="flex flex-wrap justify-center gap-1 mt-4 mb-4">
          <span 
            v-for="cat in provider.prestataire?.categories" 
            :key="cat.id"
            class="text-[10px] font-bold text-blue-600 uppercase tracking-widest bg-blue-50 px-2 py-0.5 rounded-lg"
          >
            {{ cat.name }}
          </span>
          <span v-if="!provider.prestataire?.categories?.length" class="text-[10px] font-bold text-gray-400 uppercase tracking-widest bg-gray-50 px-2 py-0.5 rounded-lg">
            Prestataire
          </span>
        </div>
        
        <p class="text-sm text-gray-500 line-clamp-2 mb-6 h-10">{{ provider.prestataire?.description || 'Pas de description disponible.' }}</p>
        
        <div class="flex items-center justify-between w-full mt-auto pt-6 border-t border-gray-50">
          <div class="flex items-center text-xs text-gray-400">
            <MapPin class="w-4 h-4 mr-1.5" />
            {{ provider.address || 'Localisation non précisée' }}
          </div>
          <button 
            @click.stop="startConversation(provider.id)"
            class="p-2.5 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white transition shadow-sm"
            title="Contacter"
          >
            <MessageSquare class="w-5 h-5" />
          </button>
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
                  <div class="flex flex-wrap justify-center md:justify-start gap-2">
                    <span 
                      v-for="cat in selectedProvider.prestataire?.categories" 
                      :key="cat.id"
                      class="px-4 py-1.5 bg-blue-600 text-white rounded-xl text-[10px] font-bold uppercase tracking-widest shadow-lg shadow-blue-200"
                    >
                      {{ cat.name }}
                    </span>
                  </div>
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

              <div>
                <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4 flex items-center">
                  <Clock class="w-4 h-4 mr-2 text-blue-600" />
                  Disponibilités
                </h4>
                <div class="grid grid-cols-2 gap-2">
                   <div v-for="(day, key) in { monday: 'Lun', tuesday: 'Mar', wednesday: 'Mer', thursday: 'Jeu', friday: 'Ven', saturday: 'Sam', sunday: 'Dim' }" :key="key" 
                        class="p-2 rounded-xl text-[10px] font-bold border flex flex-col items-center"
                        :class="selectedProvider.prestataire?.availabilities?.[key]?.active ? 'bg-green-50 border-green-100 text-green-700' : 'bg-gray-50 border-gray-100 text-gray-400 opacity-50'">
                        <span>{{ day }}</span>
                        <span v-if="selectedProvider.prestataire?.availabilities?.[key]?.active" class="mt-1 text-[9px]">
                            {{ selectedProvider.prestataire?.availabilities[key].start }} - {{ selectedProvider.prestataire?.availabilities[key].end }}
                        </span>
                        <span v-else class="mt-1">Off</span>
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

        <div class="p-10 bg-gray-50 border-t border-gray-100 flex flex-wrap gap-4">
          <button 
            v-if="auth.isClient"
            @click="openInviteModal"
            class="flex-grow bg-blue-600 text-white py-5 px-8 rounded-3xl font-bold hover:bg-blue-700 shadow-xl shadow-blue-100 transition active:scale-[0.98] flex items-center justify-center space-x-3"
          >
            <Briefcase class="w-6 h-6" />
            <span>Proposer une mission</span>
          </button>
          
          <button 
            @click="startConversation(selectedProvider.id)"
            class="flex-grow bg-white text-blue-600 border-2 border-blue-50 py-5 px-8 rounded-3xl font-bold hover:bg-blue-50 transition active:scale-[0.98] flex items-center justify-center space-x-3"
          >
            <MessageSquare class="w-6 h-6" />
            <span>Contacter</span>
          </button>
          
          <div class="flex gap-2">
            <button 
              @click="showReportModal = true"
              class="p-5 bg-white text-orange-500 border border-orange-100 rounded-3xl hover:bg-orange-50 transition"
              title="Signaler"
            >
              <ShieldAlert class="w-6 h-6" />
            </button>
            <button 
              @click="showBlockModal = true"
              class="p-5 bg-white text-red-500 border border-red-100 rounded-3xl hover:bg-red-50 transition"
              title="Bloquer"
            >
              <Ban class="w-6 h-6" />
            </button>
          </div>
          
          <button @click="closeProvider" class="px-10 py-5 bg-white text-gray-700 border border-gray-200 rounded-3xl font-bold hover:bg-gray-100 transition">
            Fermer
          </button>
        </div>
      </div>
    </div>

    <!-- Invitation Modal -->
    <div v-if="showInviteModal" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showInviteModal = false"></div>
      <div class="relative bg-white w-full max-w-lg rounded-[40px] shadow-2xl p-10 animate-in fade-in zoom-in duration-300">
        <button @click="showInviteModal = false" class="absolute top-6 right-6 p-2 rounded-2xl bg-gray-100 text-gray-500 hover:bg-red-50 hover:text-red-600 transition">
          <X class="w-6 h-6" />
        </button>

        <div class="text-center mb-8">
          <div class="w-20 h-20 bg-blue-50 rounded-3xl flex items-center justify-center mx-auto mb-6 text-blue-600">
            <Send class="w-10 h-10" />
          </div>
          <h3 class="text-2xl font-black text-gray-900">Proposer une mission</h3>
          <p class="text-gray-500 mt-2">Invitez {{ selectedProvider?.name }} à travailler sur l'un de vos projets.</p>
        </div>

        <div class="space-y-6">
          <div v-if="clientOffers.length > 0">
            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Sélectionnez une offre</label>
            <select v-model="selectedOfferId" class="w-full px-5 py-4 rounded-2xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition appearance-none font-bold text-gray-700">
              <option value="" disabled>Choisir parmi vos offres ouvertes</option>
              <option v-for="offer in clientOffers" :key="offer.id" :value="offer.id">{{ offer.title }}</option>
            </select>
          </div>
          <div v-else class="p-6 bg-yellow-50 rounded-2xl border border-yellow-100 flex items-start space-x-3">
            <AlertCircle class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" />
            <p class="text-sm text-yellow-700 font-medium">Vous n'avez aucune offre ouverte. Veuillez <router-link to="/post-offer" class="underline font-bold">en créer une</router-link> avant d'inviter un prestataire.</p>
          </div>

          <div v-if="clientOffers.length > 0">
            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Message (Optionnel)</label>
            <textarea 
              v-model="invitationMessage"
              rows="4" 
              placeholder="Bonjour, j'aimerais vous proposer ce projet car votre profil correspond parfaitement..."
              class="w-full px-5 py-4 rounded-2xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition font-medium text-gray-700"
            ></textarea>
          </div>

          <button 
            v-if="clientOffers.length > 0"
            @click="submitInvitation"
            :disabled="!selectedOfferId || inviting"
            class="w-full bg-blue-600 text-white py-5 rounded-3xl font-bold hover:bg-blue-700 shadow-xl shadow-blue-100 transition active:scale-[0.98] flex items-center justify-center space-x-3 disabled:opacity-50 disabled:grayscale"
          >
            <Loader2 v-if="inviting" class="w-6 h-6 animate-spin" />
            <Send v-else class="w-6 h-6" />
            <span>Envoyer l'invitation</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Report Modal -->
    <div v-if="showReportModal" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showReportModal = false"></div>
      <div class="relative bg-white w-full max-w-lg rounded-[40px] shadow-2xl p-10 animate-in fade-in zoom-in duration-300">
        <div class="text-center mb-8">
          <div class="w-16 h-16 bg-orange-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-orange-600">
            <ShieldAlert class="w-8 h-8" />
          </div>
          <h3 class="text-2xl font-black text-gray-900">Signaler un problème</h3>
          <p class="text-sm text-gray-500 mt-2">Votre signalement est anonyme et sera traité par notre équipe de modération.</p>
        </div>
        <div class="space-y-6">
          <div>
            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Raison du signalement</label>
            <select v-model="reportForm.reason" class="w-full px-5 py-4 rounded-2xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none font-bold text-gray-700">
              <option value="" disabled>Pourquoi signalez-vous ce profil ?</option>
              <option value="scam">Arnaque ou fraude</option>
              <option value="harassment">Harcèlement ou propos offensants</option>
              <option value="unprofessional">Comportement non professionnel</option>
              <option value="other">Autre</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Description complémentaire</label>
            <textarea v-model="reportForm.description" rows="3" class="w-full px-5 py-4 rounded-2xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none font-medium text-gray-700"></textarea>
          </div>
          <div class="flex gap-4">
            <button @click="showReportModal = false" class="flex-grow py-4 rounded-2xl font-bold border border-gray-200 text-gray-500 hover:bg-gray-50 transition">Annuler</button>
            <button @click="submitReport" :disabled="reporting || !reportForm.reason" class="flex-grow py-4 rounded-2xl font-bold bg-orange-600 text-white hover:bg-orange-700 disabled:opacity-50 transition">Envoyer</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Block Modal -->
    <div v-if="showBlockModal" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showBlockModal = false"></div>
      <div class="relative bg-white w-full max-w-sm rounded-[40px] shadow-2xl p-10 animate-in fade-in zoom-in duration-300 text-center">
        <div class="w-16 h-16 bg-red-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-red-600">
          <Ban class="w-8 h-8" />
        </div>
        <h3 class="text-2xl font-black text-gray-900">Bloquer {{ selectedProvider?.name }} ?</h3>
        <p class="text-sm text-gray-500 mt-4 mb-8">Vous ne pourrez plus échanger de messages ni collaborer avec cette personne. Cette action est réversible.</p>
        <div class="space-y-3">
          <button @click="blockUser" :disabled="blocking" class="w-full py-4 rounded-2xl font-bold bg-red-600 text-white hover:bg-red-700 disabled:opacity-50 transition">Bloquer l'utilisateur</button>
          <button @click="showBlockModal = false" class="w-full py-4 rounded-2xl font-bold text-gray-500 hover:bg-gray-50 transition">Annuler</button>
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
