<script setup>
import { ref, onMounted } from 'vue';
import ClientProfile from './ClientProfile.vue';
import { useAuthStore } from '../store/auth';
import { 
  Plus, Briefcase, Clock, CheckCircle, Edit, Trash2, 
  MapPin, Calendar, XCircle, DollarSign, User, Info, MessageSquare, Star, Trash, Loader2,
  ShieldCheck, Award, Search
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
    clientReviews.value = reviewsRes.data;

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
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-24">
    <!-- Header: Bonjour + Avatar -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-black text-slate-900">Bonjour, {{ auth.user?.first_name || auth.user?.name }}</h1>
            <div class="flex items-center space-x-2 mt-1">
                <span class="flex items-center text-[10px] font-black text-slate-400 bg-slate-50 px-2 py-0.5 rounded-full border border-slate-200 uppercase tracking-widest">
                    <span class="w-1.5 h-1.5 bg-slate-400 rounded-full mr-1.5"></span>
                    Client
                </span>
                <span class="text-[10px] text-slate-400 font-medium ml-2">Bienvenue sur votre espace</span>
            </div>
        </div>
        <div class="w-12 h-12 rounded-full bg-white shadow-xl flex items-center justify-center border-4 border-white overflow-hidden group hover:scale-110 transition-transform duration-500">
             <div v-if="auth.user?.photo" class="w-full h-full">
                <img :src="auth.user.photo" class="w-full h-full object-cover">
             </div>
             <div v-else class="w-full h-full flex items-center justify-center text-slate-300">
                <User class="w-6 h-6" />
            </div>
        </div>
    </div>

    <!-- Client Banner (Dark) -->
    <div class="bg-[#1e293b] rounded-4xl p-6 text-white shadow-xl relative overflow-hidden mb-8">
        <div class="relative z-10">
            <h2 class="text-xl font-black mb-2">Besoin d'un service ?</h2>
            <p class="text-slate-300 text-xs mb-6 font-medium leading-relaxed max-w-[90%]">Trouvez facilement le prestataire idéal pour vos projets ou publiez une annonce gratuitement.</p>
            
            <div class="flex space-x-3">
                <router-link to="/search" class="flex-1 bg-white/10 backdrop-blur-sm border border-white/20 text-white py-3 rounded-xl font-black text-xs hover:bg-white/20 transition-colors flex items-center justify-center">
                    <Search class="w-4 h-4 mr-2" />
                    Trouver
                </router-link>
                <router-link to="/post-offer" class="flex-1 bg-premium-yellow text-slate-900 py-3 rounded-xl font-black text-xs hover:bg-yellow-400 transition-colors shadow-lg shadow-yellow-500/20 flex items-center justify-center">
                    <Plus class="w-4 h-4 mr-2" />
                    Publier
                </router-link>
            </div>
        </div>
        <!-- Decor -->
         <div class="absolute top-0 right-0 w-32 h-32 bg-slate-800 rounded-full blur-[60px] opacity-50"></div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-3 gap-3 mb-8">
        <div class="bg-white p-4 rounded-4xl border border-slate-100 shadow-sm flex flex-col items-center text-center">
            <div class="w-8 h-8 rounded-full bg-green-50 flex items-center justify-center mb-2">
                <CheckCircle class="w-4 h-4 text-green-600" />
            </div>
            <span class="text-[9px] text-slate-400 font-bold leading-tight mb-1">Ouvertes</span>
            <span class="text-xl font-black text-slate-900">{{ stats.open }}</span>
        </div>
        <div class="bg-white p-4 rounded-4xl border border-slate-100 shadow-sm flex flex-col items-center text-center">
            <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center mb-2">
                <Clock class="w-4 h-4 text-blue-600" />
            </div>
            <span class="text-[9px] text-slate-400 font-bold leading-tight mb-1">En cours</span>
            <span class="text-xl font-black text-slate-900">{{ stats.in_progress }}</span>
        </div>
        <div class="bg-white p-4 rounded-4xl border border-slate-100 shadow-sm flex flex-col items-center text-center">
            <div class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center mb-2">
                <CheckCircle class="w-4 h-4 text-purple-600" />
            </div>
            <span class="text-[9px] text-slate-400 font-bold leading-tight mb-1">Terminées</span>
            <span class="text-xl font-black text-slate-900">{{ stats.completed }}</span>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="flex space-x-6 border-b border-gray-100 mb-8 overflow-x-auto scrollbar-hide pb-1">
      <button @click="activeTab = 'offers'" :class="activeTab === 'offers' ? 'text-slate-900 border-premium-yellow' : 'text-slate-400 border-transparent'" class="pb-3 border-b-2 font-bold text-sm transition-colors whitespace-nowrap">Mes Annonces</button>
      <button @click="activeTab = 'requests'" :class="activeTab === 'requests' ? 'text-slate-900 border-premium-yellow' : 'text-slate-400 border-transparent'" class="pb-3 border-b-2 font-bold text-sm transition-colors whitespace-nowrap">Candidatures reçues</button>
      <button @click="activeTab = 'reviews'" :class="activeTab === 'reviews' ? 'text-slate-900 border-premium-yellow' : 'text-slate-400 border-transparent'" class="pb-3 border-b-2 font-bold text-sm transition-colors whitespace-nowrap">Mes Avis</button>
      <button @click="activeTab = 'profile'" :class="activeTab === 'profile' ? 'text-slate-900 border-premium-yellow' : 'text-slate-400 border-transparent'" class="pb-3 border-b-2 font-bold text-sm transition-colors whitespace-nowrap">Mon Profil</button>
    </div>

    <!-- TAB: OFFERS -->
    <div v-show="activeTab === 'offers'">
       <div v-if="loading" class="flex justify-center py-10">
         <Loader2 class="w-8 h-8 text-premium-brown animate-spin" />
       </div>

       <div v-else-if="offers.length === 0" class="text-center py-10 bg-white rounded-4xl border border-dashed border-slate-200">
         <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
           <Briefcase class="w-8 h-8 text-slate-300" />
         </div>
         <h3 class="text-slate-900 font-bold mb-2">Aucune annonce</h3>
         <p class="text-slate-400 text-xs mb-4">Vous n'avez pas encore publié d'annonce.</p>
         <router-link to="/post-offer" class="text-premium-yellow text-xs font-black uppercase tracking-wider hover:underline">Publier une annonce</router-link>
       </div>

       <div v-else class="space-y-4">
          <div v-for="offer in offers" :key="offer.id" class="bg-white p-5 rounded-4xl border border-slate-100 shadow-sm relative overflow-hidden group">
              <!-- Status Badge -->
              <div class="absolute top-5 right-5">
                 <span v-if="offer.status === 'open'" class="bg-green-100 text-green-700 text-[10px] font-black px-2 py-1 rounded uppercase tracking-wider">Ouverte</span>
                 <span v-else-if="offer.status === 'in_progress'" class="bg-blue-100 text-blue-700 text-[10px] font-black px-2 py-1 rounded uppercase tracking-wider">En cours</span>
                 <span v-else class="bg-gray-100 text-gray-500 text-[10px] font-black px-2 py-1 rounded uppercase tracking-wider">Terminée</span>
              </div>

              <h3 class="font-bold text-slate-900 pr-20">{{ offer.title }}</h3>
              <p class="text-[10px] text-slate-400 font-black uppercase tracking-wider mt-1 mb-4">{{ offer.budget }} € • {{ offer.category?.name }}</p>

              <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-50">
                  <div class="flex space-x-2">
                      <button v-if="offer.status === 'open'" @click="updateOfferStatus(offer.id, 'closed')" class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                         <XCircle class="w-4 h-4" />
                      </button>
                      <button @click="deleteOffer(offer.id)" class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center text-red-400 hover:text-red-600 transition">
                         <Trash2 class="w-4 h-4" />
                      </button>
                  </div>
                  <router-link :to="`/edit-offer/${offer.id}`" class="bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-slate-800 transition">
                      Voir détails
                  </router-link>
              </div>
          </div>
       </div>
    </div>

    <!-- TAB: REQUESTS (Candidatures reçues / Invitations envoyées) -->
    <div v-show="activeTab === 'requests'">
       <div v-if="requests.length === 0" class="text-center py-10 bg-white rounded-4xl border border-dashed border-slate-200">
         <p class="text-slate-400 text-xs font-medium">Aucune candidature reçue pour le moment.</p>
       </div>

       <div v-else class="space-y-4">
          <div v-for="req in requests" :key="req.id" class="bg-white p-5 rounded-4xl border-2 border-slate-100 shadow-sm hover:border-premium-yellow/50 transition-colors">
              <div class="flex justify-between items-start mb-3">
                 <div>
                    <h4 class="font-bold text-slate-900 text-sm">{{ req.service_offer?.title }}</h4>
                    <p class="text-[10px] text-slate-400 mt-1">Candidat : <span class="text-slate-600 font-bold">{{ req.provider?.name || req.user?.name }}</span></p>
                 </div>
                 <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center">
                    <User class="w-4 h-4 text-slate-500" />
                 </div>
              </div>

              <!-- Message Preview -->
              <div class="bg-slate-50 p-3 rounded-xl mb-4">
                  <p class="text-xs text-slate-500 italic line-clamp-2">"{{ req.message || 'Je suis intéressé par votre offre...' }}"</p>
              </div>

              <!-- Status & Actions -->
              <div class="flex justify-between items-center">
                  <span v-if="req.status === 'pending'" class="text-[9px] bg-yellow-50 text-yellow-700 px-2 py-1 rounded font-black uppercase tracking-wider">En attente</span>
                  <span v-else-if="req.status === 'accepted'" class="text-[9px] bg-green-50 text-green-700 px-2 py-1 rounded font-black uppercase tracking-wider">Acceptée</span>
                  <span v-else class="text-[9px] bg-gray-50 text-gray-500 px-2 py-1 rounded font-black uppercase tracking-wider">{{ req.status }}</span>

                  <div class="flex space-x-2" v-if="req.status === 'pending'">
                      <button @click="updateRequestStatus(req.id, 'accepted')" class="bg-green-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-lg shadow-green-100">Accepter</button>
                      <button @click="updateRequestStatus(req.id, 'rejected')" class="bg-gray-100 text-gray-500 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-red-50 hover:text-red-600 transition">Refuser</button>
                  </div>
                  <div v-else-if="req.status === 'accepted' || req.status === 'completed'" class="flex space-x-2">
                       <button @click="$router.push(`/mission/${req.id}`)" class="bg-slate-900 text-white px-3 py-1.5 rounded-lg text-xs font-bold">Gérer Mission</button>
                       <button v-if="req.status === 'accepted'" @click="updateRequestStatus(req.id, 'completed')" class="border border-green-200 text-green-600 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-green-50">Terminer</button>
                       <button v-if="req.status === 'completed' && !req.client_review" @click="openReviewModal(req)" class="bg-premium-yellow text-slate-900 px-3 py-1.5 rounded-lg text-xs font-black shadow-lg shadow-yellow-200">Noter</button>
                  </div>
              </div>
          </div>
       </div>
    </div>

    <!-- TAB: REVIEWS (Avis laissés) -->
    <div v-show="activeTab === 'reviews'">
        <div v-if="clientReviews.length === 0" class="text-center py-10 bg-white rounded-4xl border border-dashed border-slate-200">
             <p class="text-slate-400 text-xs">Vous n'avez pas encore laissé d'avis.</p>
        </div>
        <div v-else class="space-y-4">
            <div v-for="review in clientReviews" :key="review.id" class="bg-white p-5 rounded-4xl shadow-sm border border-slate-100">
                <div class="flex justify-between mb-2">
                    <span class="font-bold text-slate-900 text-sm">Pour : {{ review.provider?.name }}</span>
                    <div class="flex text-yellow-400">
                         <Star v-for="i in 5" :key="i" :class="i <= review.rating ? 'fill-current' : 'text-slate-200'" class="w-3 h-3" />
                    </div>
                </div>
                <p class="text-xs text-slate-500 italic mb-3">"{{ review.comment }}"</p>
                <div class="flex justify-end space-x-2">
                    <button @click="openReviewModal(null, review)" class="text-[10px] font-black uppercase text-blue-600 bg-blue-50 px-2 py-1 rounded hover:bg-blue-100 transition">Modifier</button>
                    <!-- Delete logic if needed -->
                </div>
            </div>
        </div>
    </div>

    <!-- TAB: PROFILE -->
    <div v-show="activeTab === 'profile'">
        <ClientProfile />
    </div>

    <!-- Modal Review -->
    <div v-if="showReviewModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-100 flex items-center justify-center p-4">
        <div class="bg-white rounded-[2.5rem] p-8 w-full max-w-md shadow-2xl relative">
            <button @click="showReviewModal = false" class="absolute top-6 right-6 text-slate-300 hover:text-slate-500 transition">
                <XCircle class="w-6 h-6" />
            </button>
            
            <h3 class="text-xl font-black text-slate-900 mb-6 text-center">{{ isEditingReview ? 'Modifier votre avis' : 'Noter le prestataire' }}</h3>
            
            <div class="flex justify-center space-x-2 mb-8">
                <button v-for="star in 5" :key="star" @click="reviewForm.rating = star" class="focus:outline-none transition-transform hover:scale-110">
                    <Star class="w-10 h-10" :class="star <= reviewForm.rating ? 'fill-yellow-400 text-yellow-400' : 'text-slate-200'" />
                </button>
            </div>

            <textarea v-model="reviewForm.comment" rows="4" class="w-full bg-slate-50 border border-slate-100 rounded-2xl p-4 mb-6 outline-none focus:ring-2 focus:ring-premium-yellow/50 text-sm" placeholder="Partagez votre expérience..."></textarea>

            <button @click="submitReview" :disabled="submittingReview" class="w-full bg-premium-yellow text-slate-900 font-black py-4 rounded-xl shadow-lg shadow-yellow-200 hover:bg-yellow-400 transition-colors disabled:opacity-50">
                {{ submittingReview ? 'Envoi...' : (isEditingReview ? 'Mettre à jour' : 'Envoyer l\'avis') }}
            </button>
        </div>
    </div>
  </div>
</template>
