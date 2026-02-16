<script setup>
import { ref, onMounted, watch } from 'vue';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import { useRouter } from 'vue-router';
import { 
  Search, Filter, MapPin, Briefcase, Calendar, 
  ChevronRight, Euro, Clock, X, Loader2, Info, Check, ShieldAlert, MessageCircle
} from 'lucide-vue-next';
import ReportModal from '../components/ReportModal.vue';

const auth = useAuthStore();
const router = useRouter();
const offers = ref([]);
const categories = ref([]);
const loading = ref(false);
const loadingMore = ref(false);
const selectedOffer = ref(null);
const showFilters = ref(false);
const currentPage = ref(1);
const lastPage = ref(1);

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

const showReportModal = ref(false);

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

const contactClient = () => {
    if (!selectedOffer.value || !selectedOffer.value.user) return;
    router.push({
        path: '/messages',
        query: {
            user: selectedOffer.value.user.id,
            related_type: 'service_request', // or 'offer'
            related_id: selectedOffer.value.id
        }
    });
};

const fetchOffers = async (loadMore = false) => {
  if (loadMore) {
    loadingMore.value = true;
  } else {
    loading.value = true;
    currentPage.value = 1; // Reset to page 1 on new filter/search
  }

  try {
    const params = { page: currentPage.value };
    Object.keys(filters.value).forEach(key => {
      if (filters.value[key]) params[key] = filters.value[key];
    });
    
    const response = await api.get('/api/offers', { params });
    
    if (loadMore) {
      offers.value = [...offers.value, ...response.data.data];
    } else {
      offers.value = response.data.data;
    }
    
    lastPage.value = response.data.last_page;
  } catch (err) {
    console.error('Erreur lors du chargement des offres:', err);
  } finally {
    loading.value = false;
    loadingMore.value = false;
  }
};

const loadMoreOffers = () => {
    if (currentPage.value < lastPage.value) {
        currentPage.value++;
        fetchOffers(true);
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
    <div class="mb-10 text-center md:text-left">
      <h1 class="text-4xl font-black text-slate-900 tracking-tight">{{ $t('search.title') }}</h1>
      <p class="text-slate-500 mt-2 font-medium text-lg">{{ $t('search.subtitle') }}</p>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white p-4 rounded-4xl border border-gray-100 shadow-xl shadow-slate-200/40 mb-12 space-y-4 md:space-y-0 md:flex md:items-center md:space-x-4">
      <div class="relative grow">
        <Search class="absolute left-6 top-4 w-5 h-5 text-slate-400" />
        <input 
          v-model="filters.keyword"
          type="text" 
          :placeholder="$t('search.keyword_placeholder')" 
          class="w-full pl-14 pr-6 py-4 rounded-2xl border-none bg-slate-50 focus:bg-white focus:ring-4 focus:ring-slate-100 outline-none transition text-base font-medium placeholder:text-slate-400"
        >
      </div>
      
      <div class="flex items-center space-x-3">
        <button 
          @click="showFilters = !showFilters"
          :class="showFilters ? 'bg-slate-900 text-white shadow-lg shadow-slate-900/20' : 'bg-gray-100 text-slate-600 hover:bg-gray-200'"
          class="px-6 py-4 rounded-2xl transition-all flex items-center space-x-2 text-sm font-bold active:scale-95"
        >
          <Filter class="w-5 h-5" />
          <span class="hidden sm:inline">{{ $t('search.filters_btn') }}</span>
        </button>
        <button 
          v-if="Object.values(filters).some(v => v)"
          @click="resetFilters"
          class="px-4 py-4 text-xs font-black text-slate-400 uppercase tracking-wider hover:text-red-500 transition border border-transparent hover:bg-red-50 hover:border-red-100 rounded-2xl"
        >
          {{ $t('search.clear_btn') }}
        </button>
      </div>
    </div>

    <!-- Expanded Filters -->
    <div v-if="showFilters" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-12 p-8 bg-white rounded-[2.5rem] border border-gray-100 shadow-lg shadow-slate-100 animate-in slide-in-from-top-4 duration-300">
      <div class="lg:col-span-2">
        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3 pl-2">{{ $t('search.category_label') }}</label>
        <div class="relative">
             <select v-model="filters.category_id" class="w-full px-5 py-3.5 rounded-2xl border border-gray-200 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-slate-100 outline-none text-sm appearance-none font-bold text-slate-700 transition-all cursor-pointer">
              <option value="">{{ $t('search.all_categories') }}</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
            <ChevronRight class="absolute right-5 top-4 w-4 h-4 text-slate-400 rotate-90 pointer-events-none" />
        </div>
      </div>
      <div>
        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3 pl-2">{{ $t('search.location_label') }}</label>
        <div class="relative">
            <MapPin class="absolute left-4 top-3.5 w-4 h-4 text-slate-400" />
            <input v-model="filters.location" type="text" :placeholder="$t('search.location_placeholder')" class="w-full pl-10 pr-4 py-3.5 rounded-2xl border border-gray-200 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-slate-100 outline-none text-sm font-bold text-slate-700 transition-all">
        </div>
      </div>
      <div>
        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3 pl-2">{{ $t('search.budget_min') }}</label>
        <div class="relative">
            <Euro class="absolute left-4 top-3.5 w-4 h-4 text-slate-400" />
            <input v-model="filters.min_budget" type="number" placeholder="50" class="w-full pl-10 pr-4 py-3.5 rounded-2xl border border-gray-200 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-slate-100 outline-none text-sm font-bold text-slate-700 transition-all">
        </div>
      </div>
      <div>
        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3 pl-2">{{ $t('search.date_label') }}</label>
        <input v-model="filters.desired_date" type="date" class="w-full px-4 py-3.5 rounded-2xl border border-gray-200 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-slate-100 outline-none text-sm font-bold text-slate-700 transition-all uppercase">
      </div>
    </div>

    <!-- Results -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <Loader2 class="w-12 h-12 text-slate-900 animate-spin" />
    </div>

    <div v-else-if="offers.length === 0" class="text-center py-24 bg-white rounded-[3rem] border border-dashed border-gray-200">
      <div class="bg-slate-50 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-8 shadow-inner">
        <Search class="w-10 h-10 text-slate-300" />
      </div>
      <h3 class="text-2xl font-black text-slate-900 mb-2">{{ $t('search.no_offers') }}</h3>
      <p class="text-slate-500 font-medium">{{ $t('search.no_offers_desc') }}</p>
      <button @click="resetFilters" class="mt-8 text-slate-900 font-black hover:underline uppercase tracking-wide text-sm">{{ $t('search.view_all') }}</button>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <div 
        v-for="offer in offers" 
        :key="offer.id"
        @click="openOffer(offer)"
        class="bg-white p-7 rounded-[2.5rem] border border-gray-100 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 cursor-pointer group flex flex-col h-full"
      >
        <div class="flex justify-between items-start mb-6">
          <div class="w-14 h-14 bg-slate-50 rounded-2xl overflow-hidden flex items-center justify-center text-slate-400 group-hover:bg-slate-900 group-hover:text-white transition-all duration-300 shadow-sm border border-gray-100">
             <img v-if="offer.category && offer.category.image" :src="offer.category.image" class="w-full h-full object-cover" />
             <Briefcase v-else class="w-6 h-6" />
          </div>
          <span class="px-4 py-2 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-slate-200 group-hover:bg-slate-800 transition-colors">
            {{ offer.budget }} DH
          </span>
        </div>
        
        <h3 class="text-xl font-bold text-slate-900 mb-3 line-clamp-2 group-hover:text-amber-600 transition-colors leading-tight">{{ offer.title }}</h3>
        <p class="text-sm text-slate-500 line-clamp-3 mb-8 font-medium leading-relaxed">{{ offer.description }}</p>
        
        <div class="mt-auto space-y-4">
            <div class="h-px bg-slate-50"></div>
            <div class="flex items-center justify-between text-xs font-bold text-slate-400 uppercase tracking-wider">
                <div class="flex items-center">
                    <MapPin class="w-4 h-4 mr-2" />
                    {{ offer.location || 'Maroc' }}
                </div>
                <div class="flex items-center">
                    <Calendar class="w-4 h-4 mr-2" />
                    {{ formatDate(offer.created_at) }}
                </div>
            </div>
            <div class="flex items-center text-xs font-bold text-slate-500 bg-slate-50 p-3 rounded-xl">
                <Clock class="w-4 h-4 mr-2 text-slate-400" />
                {{ $t('search.published_by') || 'Publié par' }} <span class="text-slate-900 ml-1">{{ offer.user?.name }}</span>
            </div>
        </div>
      </div>
    </div>

    <!-- Load More Button -->
    <div v-if="offers.length > 0 && currentPage < lastPage" class="mt-12 text-center">
        <button 
            @click="loadMoreOffers" 
            :disabled="loadingMore"
            class="px-8 py-4 bg-white border border-slate-200 text-slate-900 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-50 hover:border-slate-300 transition-all shadow-sm active:scale-95 disabled:opacity-50 flex items-center justify-center mx-auto gap-2"
        >
            <Loader2 v-if="loadingMore" class="w-4 h-4 animate-spin" />
            <span>{{ loadingMore ? 'Chargement...' : 'Voir plus d\'offres' }}</span>
        </button>
    </div>

    <!-- Offer Detail Modal -->
    <div v-if="selectedOffer" class="fixed inset-0 z-100 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity" @click="closeOffer"></div>
      <div class="relative bg-white w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-[3rem] shadow-2xl animate-in fade-in zoom-in duration-300">
        <button @click="closeOffer" class="absolute top-8 right-8 p-3 rounded-full bg-slate-50 text-slate-400 hover:bg-slate-100 hover:text-slate-900 transition z-10">
          <X class="w-6 h-6" />
        </button>
        
        <div class="p-10 md:p-14">
          <div class="flex items-center justify-between mb-8">
            <div class="flex items-center space-x-3 text-slate-900">
               <span class="px-4 py-1.5 bg-slate-100 rounded-full text-[10px] font-black uppercase tracking-widest border border-slate-200">{{ selectedOffer.category.name }}</span>
            </div>
            <div v-if="selectedOffer.category && selectedOffer.category.image" class="w-16 h-16 rounded-2xl overflow-hidden border border-gray-100 shadow-sm">
              <img :src="selectedOffer.category.image" class="w-full h-full object-cover" />
            </div>
          </div>
          <h2 class="text-3xl md:text-4xl font-black text-slate-900 mb-8 leading-tight">{{ selectedOffer.title }}</h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-10">
            <div class="bg-gray-50 p-6 rounded-3xl border border-gray-100">
              <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">{{ $t('search.proposed_budget') }}</p>
              <p class="text-2xl font-black text-slate-900">{{ selectedOffer.budget }} DH</p>
            </div>
            <div class="bg-gray-50 p-6 rounded-3xl border border-gray-100">
              <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">{{ $t('search.desired_date') }}</p>
              <p class="text-xl font-bold text-slate-900">{{ formatDate(selectedOffer.desired_date) }}</p>
            </div>
            <div v-if="selectedOffer.nature_of_need" class="bg-amber-50 p-6 rounded-3xl border border-amber-100">
              <p class="text-[10px] font-black text-amber-500 uppercase tracking-widest mb-2">{{ $t('search.nature_of_need') }}</p>
              <p class="text-lg font-bold text-amber-900">{{ selectedOffer.nature_of_need }}</p>
            </div>
            <div v-if="selectedOffer.estimated_duration" class="bg-slate-900 text-white p-6 rounded-3xl relative overflow-hidden">
                <div class="absolute -right-4 -top-4 w-16 h-16 bg-slate-800 rounded-full blur-xl"></div>
              <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 relative z-10">{{ $t('search.estimated_duration') }}</p>
              <p class="text-lg font-bold text-white relative z-10">{{ selectedOffer.estimated_duration }}</p>
            </div>
          </div>

          <div class="space-y-8">
            <div>
              <h4 class="font-black text-slate-900 mb-4 flex items-center text-lg uppercase tracking-tight">
                <Info class="w-5 h-5 mr-3 text-slate-400" />
                {{ $t('search.description_title') }}
              </h4>
              <p class="text-slate-600 leading-relaxed font-medium text-lg">{{ selectedOffer.description }}</p>
            </div>

            <div v-if="selectedOffer.material_required" class="bg-amber-50 p-8 rounded-4xl border border-amber-100">
              <h4 class="font-bold text-amber-900 mb-3 flex items-center uppercase tracking-wide text-xs">
                <Briefcase class="w-4 h-4 mr-2" />
                {{ $t('search.material_required') }}
              </h4>
              <p class="text-base text-amber-800 font-medium">{{ selectedOffer.material_required }}</p>
            </div>

            <div v-if="selectedOffer.requirements" class="bg-emerald-50 p-8 rounded-4xl border border-emerald-100">
              <h4 class="font-bold text-emerald-900 mb-3 flex items-center uppercase tracking-wide text-xs">
                <Check class="w-4 h-4 mr-2" />
                {{ $t('search.requirements') }}
              </h4>
              <p class="text-base text-emerald-800 font-medium italic">"{{ selectedOffer.requirements }}"</p>
            </div>

            <!-- Apply Form -->
            <div v-if="!applySuccess" class="pt-8 border-t border-gray-100">
              <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-4 pl-2">{{ $t('search.apply_msg_label') }}</label>
              <textarea 
                v-model="applyMessage"
                rows="4" 
                :placeholder="$t('search.apply_msg_placeholder')"
                class="w-full px-6 py-5 rounded-3xl border border-gray-200 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-slate-100 outline-none transition font-medium text-slate-700"
              ></textarea>
            </div>

            <div v-else class="pt-8 border-t border-gray-100 text-center py-4">
              <div class="bg-green-50 text-green-700 p-6 rounded-3xl flex items-center justify-center space-x-3 border border-green-100">
                <Check class="w-6 h-6" />
                <span class="font-bold text-lg">{{ $t('search.apply_success') }}</span>
              </div>
            </div>
            
            <div class="flex items-center space-x-8 pt-8 border-t border-gray-100">
              <div class="flex items-center text-xs font-bold text-slate-400 uppercase tracking-wider">
                <MapPin class="w-4 h-4 mr-2 text-slate-900" />
                {{ selectedOffer.location }}
              </div>
              <div class="flex items-center text-xs font-bold text-slate-400 uppercase tracking-wider">
                <Briefcase class="w-4 h-4 mr-2 text-slate-900" />
                Client: {{ selectedOffer.user.name }}
              </div>
            </div>
          </div>

            <div v-if="!applySuccess" class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-12">
               <button 
                  @click="contactClient"
                  class="py-5 rounded-2xl font-black bg-blue-50 text-blue-600 hover:bg-blue-100 shadow-xl shadow-blue-100 transition flex items-center justify-center space-x-3 active:scale-[0.98]"
                >
                  <MessageCircle class="w-5 h-5" />
                  <span>Discuter</span>
                </button>
                <button 
                  @click="submitApplication"
                  :disabled="applying"
                  class="bg-slate-900 text-white py-5 rounded-2xl font-black hover:bg-slate-800 shadow-xl shadow-slate-200 transition disabled:opacity-50 flex items-center justify-center space-x-3 active:scale-[0.98]"
                >
                  <Loader2 v-if="applying" class="w-5 h-5 animate-spin" />
                  <span>{{ applying ? $t('search.sending') : $t('search.apply_btn') }}</span>
                </button>
            </div>
            
            <div class="flex gap-4 mt-6 justify-center">
                 <button @click="showReportModal = true" class="text-xs font-black text-red-400 hover:text-red-500 uppercase tracking-widest flex items-center gap-2 transition">
                   <ShieldAlert class="w-4 h-4" />
                   Signaler l'offre
                 </button>
                 <button @click="closeOffer" class="text-xs font-black text-slate-400 hover:text-slate-600 uppercase tracking-widest transition">
                   Fermer
                 </button>
            </div>

        </div>
      </div>
    </div>

    <!-- Report Modal -->
    <ReportModal 
        :isOpen="showReportModal"
        :userId="selectedOffer?.user?.id"
        :userName="selectedOffer?.user?.name"
        @close="showReportModal = false"
        @success="showReportModal = false"
    />
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
