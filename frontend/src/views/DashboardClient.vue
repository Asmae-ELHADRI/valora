<script setup>
import { ref, onMounted } from 'vue';
import ClientProfile from './ClientProfile.vue';
import { useAuthStore } from '../store/auth';
import { 
  Plus, Briefcase, Clock, CheckCircle, Edit, Trash2, 
  MapPin, Calendar, XCircle, DollarSign, User, Info, MessageSquare, Star, Trash, Loader2
} from 'lucide-vue-next';
import api from '../services/api';

const auth = useAuthStore();
const offers = ref([]);
const requests = ref([]);
const clientReviews = ref([]);
const loading = ref(true);
const activeTab = ref('offers'); // offers, requests, reviews

const showReviewModal = ref(false);
const reviewingReq = ref(null);
const reviewForm = ref({
  rating: 5,
  comment: ''
});
const submittingReview = ref(false);
const isEditingReview = ref(false);
const editingReviewId = ref(null);

const stats = ref({
  open: 0,
  in_progress: 0,
  completed: 0
});

const fetchDashboardData = async () => {
  loading.value = true;
  try {
    const [offersRes, requestsRes, reviewsRes] = await Promise.all([
      api.get('/api/offers/my-offers'),
      api.get('/api/my-requests'),
      api.get('/api/reviews/client')
    ]);
    
    offers.value = offersRes.data.data;
    requests.value = requestsRes.data.data;
    clientReviews.value = reviewsRes.data; // reviews might not be paginated yet, check if needed
    
    // Calculate stats
    stats.value = offers.value.reduce((acc, offer) => {
      if (offer.status === 'open') acc.open++;
      if (offer.status === 'in_progress') acc.in_progress++;
      if (offer.status === 'completed') acc.completed++;
      return acc;
    }, { open: 0, in_progress: 0, completed: 0 });
  } catch (err) {
    console.error('Erreur chargement:', err);
  } finally {
    loading.value = false;
  }
};

const deleteOffer = async (id) => {
  if (!confirm('Êtes-vous sûr de vouloir supprimer cette offre ?')) return;
  try {
    await api.delete(`/api/offers/${id}`);
    await fetchDashboardData();
  } catch (err) {
    alert('Erreur lors de la suppression');
  }
};

const updateOfferStatus = async (id, status) => {
  try {
    await api.put(`/api/offers/${id}`, { status });
    await fetchDashboardData();
  } catch (err) {
    alert('Erreur lors de la mise à jour du statut');
  }
};

const updateRequestStatus = async (id, status) => {
  let confirmMsg = 'Êtes-vous sûr ?';
  if (status === 'accepted') confirmMsg = 'Accepter cette candidature et démarrer la mission ?';
  if (status === 'rejected') confirmMsg = 'Refuser cette candidature ?';
  if (status === 'cancelled') confirmMsg = 'Annuler cette demande ?';
  if (status === 'completed') confirmMsg = 'Marquer cette mission comme terminée ?';
  
  if (!confirm(confirmMsg)) return;

  try {
    await api.post(`/api/service-requests/${id}/status`, { status });
    await fetchDashboardData();
  } catch (err) {
    alert('Erreur lors de la mise à jour');
  }
};

const openReviewModal = (req, existingReview = null) => {
  reviewingReq.value = req;
  if (existingReview) {
    isEditingReview.value = true;
    editingReviewId.value = existingReview.id;
    reviewForm.value = {
      rating: existingReview.rating,
      comment: existingReview.comment
    };
  } else {
    isEditingReview.value = false;
    editingReviewId.value = null;
    reviewForm.value = {
      rating: 5,
      comment: ''
    };
  }
  showReviewModal.value = true;
};

const submitReview = async () => {
  submittingReview.value = true;
  try {
    if (isEditingReview.value) {
      await api.put(`/api/reviews/${editingReviewId.value}`, reviewForm.value);
    } else {
      await api.post('/api/reviews', {
        ...reviewForm.value,
        user_id: reviewingReq.value.provider_id,
        service_offer_id: reviewingReq.value.service_offer_id
      });
    }
    showReviewModal.value = false;
    await fetchDashboardData();
  } catch (err) {
    alert(err.response?.data?.message || 'Erreur lors de l\'enregistrement');
  } finally {
    submittingReview.value = false;
  }
};

const deleteReview = async (id) => {
  if (!confirm('Supprimer cette évaluation ?')) return;
  try {
    await api.delete(`/api/reviews/${id}`);
    await fetchDashboardData();
  } catch (err) {
    alert('Erreur lors de la suppression');
  }
};

const canEditReview = (createdAt) => {
  const createdDate = new Date(createdAt);
  const now = new Date();
  const diffHours = (now - createdDate) / (1000 * 60 * 60);
  return diffHours < 24;
};

const getStatusLabel = (status) => {
  const labels = {
    open: 'Ouverte',
    in_progress: 'En cours',
    completed: 'Terminée',
    cancelled: 'Annulée'
  };
  return labels[status] || status;
};

const getStatusClass = (status) => {
  const classes = {
    open: 'bg-green-50 text-green-700 border-green-100',
    in_progress: 'bg-blue-50 text-blue-700 border-blue-100',
    completed: 'bg-gray-50 text-gray-700 border-gray-100',
    cancelled: 'bg-red-50 text-red-700 border-red-100'
  };
  return classes[status] || 'bg-gray-50 text-gray-700 border-gray-100';
};

const getReqStatusLabel = (status) => {
  const labels = {
    pending: 'En attente',
    accepted: 'Acceptée',
    rejected: 'Refusée',
    completed: 'Terminée',
    cancelled: 'Annulée'
  };
  return labels[status] || status;
};

const getReqStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-50 text-yellow-700 border-yellow-100',
    accepted: 'bg-blue-50 text-blue-700 border-blue-100',
    rejected: 'bg-red-50 text-red-700 border-red-100',
    completed: 'bg-green-50 text-green-700 border-green-100',
    cancelled: 'bg-gray-50 text-gray-700 border-gray-100'
  };
  return classes[status] || 'bg-gray-50 text-gray-700 border-gray-100';
};

const showProviderProfileModal = ref(false);
const selectedProviderData = ref(null);
const loadingProfile = ref(false);

const openProviderProfile = async (providerId) => {
  loadingProfile.value = true;
  showProviderProfileModal.value = true;
  try {
    const response = await api.get(`/api/provider/${providerId}`);
    selectedProviderData.value = response.data;
  }
 catch (err) {
    console.error('Erreur chargement profil:', err);
    alert('Impossible de charger le profil du prestataire');
    showProviderProfileModal.value = false;
  } finally {
    loadingProfile.value = false;
  }
};

const getBadgeClass = (level) => {
    switch (level) {
        case 'Expert': return 'bg-purple-600 text-white shadow-purple-100';
        case 'Confirmé': return 'bg-blue-600 text-white shadow-blue-100';
        default: return 'bg-gray-500 text-white shadow-gray-100';
    }
};

onMounted(fetchDashboardData);
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div v-if="loading && offers.length === 0 && requests.length === 0" class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else>
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Tableau de bord Client</h1>
          <p class="text-gray-500 mt-2">Gérez vos offres et l'avancement de vos missions</p>
        </div>
        <router-link to="/post-offer" class="bg-blue-600 text-white px-8 py-4 rounded-2xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-100 transition flex items-center space-x-2 w-fit">
          <Plus class="w-5 h-5" />
          <span>Publier une offre</span>
        </router-link>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center space-x-4">
          <div class="bg-green-50 p-4 rounded-2xl text-green-600"><Briefcase class="w-6 h-6" /></div>
          <div><p class="text-xs font-bold text-gray-400 uppercase">Offres ouvertes</p><p class="text-2xl font-black text-gray-900">{{ stats.open }}</p></div>
        </div>
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center space-x-4">
          <div class="bg-blue-50 p-4 rounded-2xl text-blue-600"><Clock class="w-6 h-6" /></div>
          <div><p class="text-xs font-bold text-gray-400 uppercase">Missions en cours</p><p class="text-2xl font-black text-gray-900">{{ stats.in_progress }}</p></div>
        </div>
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center space-x-4">
          <div class="bg-gray-50 p-4 rounded-2xl text-gray-600"><CheckCircle class="w-6 h-6" /></div>
          <div><p class="text-xs font-bold text-gray-400 uppercase">Total terminées</p><p class="text-2xl font-black text-gray-900">{{ stats.completed }}</p></div>
        </div>
      </div>

      <!-- Tabs Navigation -->
      <div class="flex space-x-4 mb-8">
        <button @click="activeTab = 'offers'" :class="activeTab === 'offers' ? 'bg-blue-600 text-white shadow-lg' : 'bg-white text-gray-500 border border-gray-100 hover:bg-gray-50'" class="px-6 py-3 rounded-2xl font-bold transition flex items-center space-x-2">
          <Briefcase class="w-5 h-5 mx-1" /><span>Mes offres</span>
        </button>
        <button @click="activeTab = 'requests'" :class="activeTab === 'requests' ? 'bg-blue-600 text-white shadow-lg' : 'bg-white text-gray-500 border border-gray-100 hover:bg-gray-50'" class="px-6 py-3 rounded-2xl font-bold transition flex items-center space-x-2">
          <Clock class="w-5 h-5 mx-1" /><span>Demandes & Missions</span>
          <span v-if="requests.filter(r => r.status === 'pending').length > 0" class="bg-red-500 text-white text-[10px] w-5 h-5 rounded-full flex items-center justify-center ml-2">{{ requests.filter(r => r.status === 'pending').length }}</span>
        </button>
        <button @click="activeTab = 'reviews'" :class="activeTab === 'reviews' ? 'bg-blue-600 text-white shadow-lg' : 'bg-white text-gray-500 border border-gray-100 hover:bg-gray-50'" class="px-6 py-3 rounded-2xl font-bold transition flex items-center space-x-2">
          <Star class="w-5 h-5 mx-1" /><span>Mes avis</span>
        </button>
        <button @click="activeTab = 'profile'" :class="activeTab === 'profile' ? 'bg-blue-600 text-white shadow-lg' : 'bg-white text-gray-500 border border-gray-100 hover:bg-gray-50'" class="px-6 py-3 rounded-2xl font-bold transition flex items-center space-x-2">
          <User class="w-5 h-5 mx-1" /><span>Mon Profil</span>
        </button>
      </div>

      <!-- Content: Offers -->
      <div v-if="activeTab === 'offers'" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div v-if="offers.length > 0" class="divide-y divide-gray-50">
          <div v-for="offer in offers" :key="offer.id" class="p-8 hover:bg-gray-50/50 transition">
            <div class="flex flex-col md:flex-row justify-between gap-6">
              <div class="space-y-4 flex-grow">
                <div class="flex items-center space-x-3">
                  <span :class="getStatusClass(offer.status)" class="px-3 py-1 rounded-lg border text-[10px] font-black uppercase tracking-widest">{{ getStatusLabel(offer.status) }}</span>
                  <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-3 py-1 rounded-lg uppercase tracking-widest">{{ offer.category?.name }}</span>
                </div>
                <h3 class="text-xl font-bold text-gray-900">{{ offer.title }}</h3>
                <p class="text-gray-500 text-sm line-clamp-2">{{ offer.description }}</p>
                <div class="flex flex-wrap gap-4 text-xs text-gray-400 font-medium">
                  <div class="flex items-center"><MapPin class="w-4 h-4 mr-1" />{{ offer.location }}</div>
                  <div class="flex items-center"><Calendar class="w-4 h-4 mr-1" />{{ new Date(offer.desired_date).toLocaleDateString() }}</div>
                  <div class="flex items-center text-blue-600 font-bold"><DollarSign class="w-4 h-4 mr-0.5" />{{ offer.budget }}€</div>
                </div>
              </div>
              <div class="flex md:flex-col gap-2 shrink-0">
                <router-link :to="`/edit-offer/${offer.id}`" class="p-3 bg-white border border-gray-100 rounded-xl text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition shadow-sm"><Edit class="w-5 h-5" /></router-link>
                <button @click="deleteOffer(offer.id)" class="p-3 bg-white border border-gray-100 rounded-xl text-gray-400 hover:text-red-600 hover:bg-red-50 transition shadow-sm"><Trash2 class="w-5 h-5" /></button>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="p-20 text-center"><p class="text-gray-400 font-medium">Aucune offre publiée pour le moment.</p></div>
      </div>

      <!-- Content: Requests -->
      <div v-if="activeTab === 'requests'" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div v-if="requests.length > 0" class="divide-y divide-gray-50">
          <div v-for="req in requests" :key="req.id" class="p-8 hover:bg-gray-50/50 transition flex flex-col md:flex-row items-center gap-6">
            <div 
                class="w-16 h-16 rounded-2xl bg-gray-100 overflow-hidden border border-gray-50 shadow-inner shrink-0 cursor-pointer hover:ring-4 hover:ring-blue-100 transition"
                @click="openProviderProfile(req.provider_id)"
                title="Voir le profil complet"
            >
              <img v-if="req.provider?.prestataire?.photo" :src="`http://localhost:8000/storage/${req.provider.prestataire.photo}`" class="w-full h-full object-cover">
              <div v-else class="w-full h-full flex items-center justify-center text-gray-300"><User class="w-8 h-8" /></div>
            </div>
            <div class="flex-grow space-y-2">
              <div class="flex items-center space-x-3">
                <span :class="getReqStatusClass(req.status)" class="px-3 py-1 rounded-lg border text-[10px] font-black uppercase tracking-widest">{{ getReqStatusLabel(req.status) }}</span>
                <span v-if="req.created_by_id === auth.user.id" class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded-md uppercase">Invitation envoyée</span>
                <span v-else class="text-[10px] font-bold text-purple-600 bg-purple-50 px-2 py-1 rounded-md uppercase">Candidat</span>
              </div>
              <h3 class="text-lg font-bold text-gray-900">{{ req.service_offer?.title }} <span class="text-gray-400 font-normal">avec {{ req.provider?.name }}</span></h3>
              <p class="text-sm text-gray-500 italic line-clamp-1">"{{ req.message || 'Pas de message' }}"</p>
            </div>
            <div class="flex gap-2">
              <button 
                @click="openProviderProfile(req.provider_id)"
                class="p-3 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition border border-gray-100 shadow-sm" 
                title="Consulter le profil"
              >
                <User class="w-5 h-5" />
              </button>
              <router-link 
                :to="`/messages?userId=${req.provider?.id}`" 
                class="bg-white text-blue-600 border-2 border-blue-50 px-5 py-3 rounded-xl font-bold text-sm hover:bg-blue-50 transition flex items-center shadow-sm" 
                title="Discuter"
              >
                <MessageSquare class="w-4 h-4 mr-2" />
                <span>Discuter</span>
              </router-link>
              
              <template v-if="req.status === 'pending' && req.created_by_id !== auth.user.id">
                <button @click="updateRequestStatus(req.id, 'accepted')" class="bg-green-600 text-white px-5 py-3 rounded-xl font-bold text-sm hover:bg-green-700 transition flex items-center"><CheckCircle class="w-4 h-4 mr-2" /> Accepter</button>
                <button @click="updateRequestStatus(req.id, 'rejected')" class="bg-red-50 text-red-600 px-5 py-3 rounded-xl font-bold text-sm hover:bg-red-100 transition border border-red-100">Refuser</button>
              </template>
              <button v-if="req.status === 'accepted'" @click="updateRequestStatus(req.id, 'completed')" class="bg-blue-600 text-white px-5 py-3 rounded-xl font-bold text-sm hover:bg-blue-700 transition flex items-center"><CheckCircle class="w-4 h-4 mr-2" /> Terminée</button>
              
              <button 
                v-if="req.status === 'completed' && !clientReviews.find(r => r.service_offer_id === req.service_offer_id)" 
                @click="openReviewModal(req)" 
                class="bg-yellow-400 text-white px-5 py-3 rounded-xl font-bold text-sm hover:bg-yellow-500 transition flex items-center"
              >
                <Star class="w-4 h-4 mr-2 fill-current" /> Noter le prestataire
              </button>

              <button v-if="req.status === 'pending' || req.status === 'accepted'" @click="updateRequestStatus(req.id, 'cancelled')" class="p-3 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition border border-gray-100 shadow-sm" title="Annuler">
                <XCircle class="w-5 h-5" />
              </button>
            </div>
          </div>
        </div>
        <div v-else class="p-20 text-center"><p class="text-gray-400 font-medium">Aucune demande ou mission pour le moment.</p></div>
      </div>

      <!-- Content: Reviews -->
      <div v-if="activeTab === 'reviews'" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div v-if="clientReviews.length > 0" class="divide-y divide-gray-50">
          <div v-for="review in clientReviews" :key="review.id" class="p-8 hover:bg-gray-50/50 transition">
            <div class="flex justify-between items-start">
              <div class="space-y-3">
                <div class="flex items-center space-x-2">
                  <div class="flex text-yellow-400">
                    <Star v-for="i in 5" :key="i" :class="i <= review.rating ? 'fill-current' : 'text-gray-200'" class="w-4 h-4" />
                  </div>
                  <span class="text-xs text-gray-400">{{ new Date(review.created_at).toLocaleDateString() }}</span>
                </div>
                <h3 class="font-bold text-gray-900">Pour : {{ review.service_offer?.title }}</h3>
                <p class="text-sm text-gray-600 italic">"{{ review.comment }}"</p>
                <p class="text-[10px] font-bold text-blue-600 uppercase tracking-widest">Prestataire : {{ review.reviewed_user?.name }}</p>
              </div>
              <div class="flex gap-2" v-if="canEditReview(review.created_at)">
                <button @click="openReviewModal(null, review)" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"><Edit class="w-4 h-4" /></button>
                <button @click="deleteReview(review.id)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"><Trash class="w-4 h-4" /></button>
              </div>
              <div v-else class="h-8 flex items-center px-3 bg-gray-50 rounded-lg text-[10px] text-gray-400 font-bold uppercase">Figé</div>
            </div>
          </div>
        </div>
        <div v-else class="p-20 text-center"><p class="text-gray-400 font-medium">Vous n'avez pas encore laissé d'évaluations.</p></div>
      </div>
    </div>

    <!-- Review Modal -->
    <div v-if="showReviewModal" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showReviewModal = false"></div>
      <div class="relative bg-white w-full max-w-lg rounded-[40px] shadow-2xl p-10 animate-in fade-in zoom-in duration-300">
        <button @click="showReviewModal = false" class="absolute top-6 right-6 p-2 rounded-2xl bg-gray-100 text-gray-500 hover:bg-red-50 hover:text-red-600 transition">
          <XCircle class="w-6 h-6" />
        </button>

        <div class="text-center mb-8">
          <div class="w-20 h-20 bg-yellow-50 rounded-3xl flex items-center justify-center mx-auto mb-6 text-yellow-600">
            <Star class="w-10 h-10 fill-current" />
          </div>
          <h3 class="text-2xl font-black text-gray-900">{{ isEditingReview ? 'Modifier votre avis' : 'Évaluer la mission' }}</h3>
          <p class="text-gray-500 mt-2">Partagez votre expérience avec {{ reviewingReq?.provider?.name || 'le prestataire' }}</p>
        </div>

        <div class="space-y-6">
          <div>
            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest text-center mb-4">Votre note</label>
            <div class="flex justify-center space-x-3">
              <button 
                v-for="i in 5" 
                :key="i"
                @click="reviewForm.rating = i"
                class="transition transform active:scale-95"
              >
                <Star :class="i <= reviewForm.rating ? 'text-yellow-400 fill-current' : 'text-gray-200'" class="w-10 h-10" />
              </button>
            </div>
          </div>

          <div>
            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Votre commentaire</label>
            <textarea 
              v-model="reviewForm.comment"
              rows="4" 
              placeholder="Qu'est-ce qui s'est bien passé ? Qu'est-ce qui pourrait être amélioré ?"
              class="w-full px-5 py-4 rounded-2xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition font-medium text-gray-700"
            ></textarea>
          </div>

          <button 
            @click="submitReview"
            :disabled="submittingReview"
            class="w-full bg-blue-600 text-white py-5 rounded-3xl font-bold hover:bg-blue-700 shadow-xl shadow-blue-100 transition active:scale-[0.98] flex items-center justify-center space-x-3 disabled:opacity-50"
          >
            <Loader2 v-if="submittingReview" class="w-6 h-6 animate-spin" />
            <span>{{ isEditingReview ? 'Mettre à jour' : 'Enregistrer mon avis' }}</span>
          </button>
          <p v-if="!isEditingReview" class="text-[10px] text-center text-gray-400 font-medium">Vous pourrez modifier ou supprimer votre avis pendant 24 heures.</p>
        </div>
      </div>
    </div>
    <!-- Provider Profile Modal -->
    <div v-if="showProviderProfileModal" class="fixed inset-0 z-[130] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showProviderProfileModal = false"></div>
        <div class="relative bg-white w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-[40px] shadow-2xl animate-in fade-in zoom-in duration-300">
            <button @click="showProviderProfileModal = false" class="absolute top-6 right-6 p-2 rounded-2xl bg-gray-100 text-gray-500 hover:bg-red-50 hover:text-red-600 transition z-10">
                <XCircle class="w-6 h-6" />
            </button>

            <div v-if="loadingProfile" class="p-20 flex justify-center">
                <Loader2 class="w-10 h-10 text-blue-600 animate-spin" />
            </div>

            <div v-else-if="selectedProviderData" class="p-8">
                <div class="flex items-center space-x-6 mb-8">
                    <div class="w-24 h-24 rounded-3xl bg-gray-100 overflow-hidden border-4 border-white shadow-xl">
                        <img v-if="selectedProviderData.prestataire?.photo" :src="`http://localhost:8000/storage/${selectedProviderData.prestataire.photo}`" class="w-full h-full object-cover">
                        <div v-else class="w-full h-full flex items-center justify-center text-gray-300"><User class="w-12 h-12" /></div>
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-gray-900">{{ selectedProviderData.name }}</h2>
                        <div class="flex flex-wrap items-center gap-2 mt-3">
                             <!-- Hierarchical Badges -->
                             <template v-if="selectedProviderData.prestataire?.badges?.length > 0">
                                <div v-for="badge in selectedProviderData.prestataire.badges" :key="badge.id"
                                     :class="getBadgeClass(badge.name)" 
                                     class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest shadow-md flex items-center group/badge"
                                >
                                    <Award v-if="badge.name === 'Confirmé'" class="w-3 h-3 mr-1" />
                                    <ShieldCheck v-else-if="badge.name === 'Expert'" class="w-3 h-3 mr-1" />
                                    {{ badge.name }}
                                </div>
                             </template>
                             <div v-else class="px-3 py-1 bg-gray-100 text-gray-400 rounded-full text-[9px] font-black uppercase tracking-widest">
                                Débutant
                             </div>

                             <div v-if="selectedProviderData.prestataire?.rating > 0" class="flex items-center bg-yellow-50 text-yellow-700 px-3 py-1 rounded-full text-[10px] font-black ring-1 ring-yellow-100">
                                <Star class="w-3 h-3 fill-current mr-1 text-yellow-400" />
                                {{ parseFloat(selectedProviderData.prestataire.rating).toFixed(1) }}
                             </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <div class="space-y-6">
                        <div>
                            <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Compétences</h4>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="skill in (selectedProviderData.prestataire?.skills || '').split(',')" :key="skill" class="px-3 py-1 bg-gray-100 text-gray-600 rounded-lg text-xs font-bold">
                                    {{ skill.trim() }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Bio</h4>
                            <p class="text-sm text-gray-600 leading-relaxed">{{ selectedProviderData.prestataire?.description || 'Aucune description' }}</p>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <div>
                            <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Expérience</h4>
                            <div class="bg-blue-50/50 p-4 rounded-2xl text-sm text-gray-700 font-medium border border-blue-100">
                                {{ selectedProviderData.prestataire?.experience || 'Non renseigné' }}
                            </div>
                        </div>
                        <div>
                            <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Statistiques</h4>
                            <div class="bg-gray-50 p-4 rounded-2xl relative overflow-hidden group">
                                <div class="flex justify-between items-center mb-4">
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-black text-gray-400 uppercase leading-none mb-1">Pro Score</span>
                                        <span :class="getBadgeClass(selectedProviderData.prestataire?.badge_level)" class="text-xl font-black px-2 py-0.5 rounded-lg inline-block w-fit">{{ selectedProviderData.prestataire?.pro_score }}</span>
                                    </div>
                                    <div class="text-right">
                                        <div class="flex items-center text-xs font-bold text-gray-700">
                                            <Briefcase class="w-3 h-3 mr-1 text-blue-500" />
                                            {{ (selectedProviderData.prestataire?.completed_missions_count || 0) * 10 }} pts
                                        </div>
                                        <div class="flex items-center text-xs font-bold text-gray-700">
                                            <Star class="w-3 h-3 mr-1 text-purple-500" />
                                            {{ Math.round((selectedProviderData.prestataire?.rating || 0) * 20) }} pts
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-xs text-gray-500 font-medium">Missions réussies</span>
                                    <span class="font-bold text-gray-900">{{ selectedProviderData.prestataire?.completed_missions_count }}</span>
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-gray-500 font-medium">Avis déposés</span>
                                    <span class="font-bold text-gray-900">{{ selectedProviderData.received_reviews?.length || 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-6 border-t border-gray-100">
                    <button @click="showProviderProfileModal = false" class="px-8 py-3 bg-gray-100 text-gray-600 rounded-2xl font-bold hover:bg-gray-200 transition">
                        Fermer la vue détaillée
                    </button>
                </div>
            </div>
        </div>
    </div>
  </div>
</template>
