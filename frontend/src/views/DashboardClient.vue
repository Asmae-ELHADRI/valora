<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../store/auth';
import { Plus, Briefcase, Clock, CheckCircle, Edit, Trash2, MapPin, Calendar, MoreVertical, XCircle, AlertCircle, DollarSign } from 'lucide-vue-next';
import api from '../services/api';

const auth = useAuthStore();
const offers = ref([]);
const loading = ref(true);

const stats = ref({
  open: 0,
  in_progress: 0,
  completed: 0
});

const fetchOffers = async () => {
  loading.value = true;
  try {
    const response = await api.get('/api/offers/my-offers');
    offers.value = response.data;
    
    // Calculate stats
    stats.value = offers.value.reduce((acc, offer) => {
      if (offer.status === 'open') acc.open++;
      if (offer.status === 'in_progress') acc.in_progress++;
      if (offer.status === 'completed') acc.completed++;
      return acc;
    }, { open: 0, in_progress: 0, completed: 0 });
  } catch (err) {
    console.error('Erreur chargement offres:', err);
  } finally {
    loading.value = false;
  }
};

const deleteOffer = async (id) => {
  if (!confirm('Êtes-vous sûr de vouloir supprimer cette offre ?')) return;
  try {
    await api.delete(`/api/offers/${id}`);
    await fetchOffers();
  } catch (err) {
    alert('Erreur lors de la suppression');
  }
};

const updateOfferStatus = async (id, status) => {
  try {
    await api.put(`/api/offers/${id}`, { status });
    await fetchOffers();
  } catch (err) {
    alert('Erreur lors de la mise à jour du statut');
  }
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

onMounted(fetchOffers);
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div v-if="loading && offers.length === 0" class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else>
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Tableau de bord Client</h1>
          <p class="text-gray-500 mt-2">Gérez vos offres et vos demandes de services</p>
        </div>
        <router-link to="/post-offer" class="bg-blue-600 text-white px-8 py-4 rounded-2xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-100 transition flex items-center space-x-2 w-fit">
          <Plus class="w-5 h-5" />
          <span>Publier une offre</span>
        </router-link>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center space-x-4">
          <div class="bg-green-50 p-4 rounded-2xl">
            <Briefcase class="w-6 h-6 text-green-600" />
          </div>
          <div>
            <p class="text-sm text-gray-400 font-bold uppercase tracking-wider">Offres ouvertes</p>
            <p class="text-3xl font-black text-gray-900">{{ stats.open }}</p>
          </div>
        </div>
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center space-x-4">
          <div class="bg-blue-50 p-4 rounded-2xl">
            <Clock class="w-6 h-6 text-blue-600" />
          </div>
          <div>
            <p class="text-sm text-gray-400 font-bold uppercase tracking-wider">En cours</p>
            <p class="text-3xl font-black text-gray-900">{{ stats.in_progress }}</p>
          </div>
        </div>
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center space-x-4">
          <div class="bg-gray-50 p-4 rounded-2xl">
            <CheckCircle class="w-6 h-6 text-gray-600" />
          </div>
          <div>
            <p class="text-sm text-gray-400 font-bold uppercase tracking-wider">Terminées</p>
            <p class="text-3xl font-black text-gray-900">{{ stats.completed }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center">
          <h2 class="text-xl font-bold text-gray-900">Mes offres de service</h2>
          <div class="flex items-center space-x-2 text-sm text-gray-400">
            <span>{{ offers.length }} offre(s) au total</span>
          </div>
        </div>

        <div v-if="offers.length > 0" class="divide-y divide-gray-50">
          <div v-for="offer in offers" :key="offer.id" class="p-8 hover:bg-gray-50/50 transition relative group">
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
              <div class="flex-grow space-y-3">
                <div class="flex items-center space-x-3">
                  <span :class="getStatusClass(offer.status)" class="px-3 py-1 rounded-lg border text-xs font-bold uppercase tracking-wider">
                    {{ getStatusLabel(offer.status) }}
                  </span>
                  <span class="text-xs font-bold text-blue-600 uppercase tracking-widest bg-blue-50 px-3 py-1 rounded-lg">
                    {{ offer.category?.name }}
                  </span>
                </div>
                <h3 class="text-xl font-bold text-gray-900">{{ offer.title }}</h3>
                <p class="text-gray-600 line-clamp-2 text-sm max-w-2xl">{{ offer.description }}</p>
                <div class="flex flex-wrap gap-4 pt-2">
                  <div class="flex items-center text-sm text-gray-500 font-medium">
                    <MapPin class="w-4 h-4 mr-1.5 text-gray-400" />
                    {{ offer.location || 'Non précisé' }}
                  </div>
                  <div class="flex items-center text-sm text-gray-500 font-medium">
                    <Calendar class="w-4 h-4 mr-1.5 text-gray-400" />
                    {{ offer.desired_date ? new Date(offer.desired_date).toLocaleDateString() : 'Dès que possible' }}
                  </div>
                  <div class="flex items-center text-sm text-blue-600 font-bold">
                    <DollarSign class="w-4 h-4 mr-1" />
                    {{ offer.budget ? `${offer.budget}€` : 'À débattre' }}
                  </div>
                </div>
              </div>
              <div class="flex md:flex-col items-center gap-2">
                <button v-if="offer.status !== 'completed'" @click="updateOfferStatus(offer.id, 'completed')" class="p-3 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-xl transition shadow-sm bg-white border border-gray-100" title="Marquer comme terminée">
                  <CheckCircle class="w-5 h-5" />
                </button>
                <router-link :to="`/edit-offer/${offer.id}`" class="p-3 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition shadow-sm bg-white border border-gray-100" title="Modifier">
                  <Edit class="w-5 h-5" />
                </router-link>
                <button @click="deleteOffer(offer.id)" class="p-3 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition shadow-sm bg-white border border-gray-100" title="Supprimer">
                  <Trash2 class="w-5 h-5" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="p-20 text-center">
          <div class="bg-gray-50 w-24 h-24 rounded-3xl flex items-center justify-center mx-auto mb-6 transform -rotate-6">
            <Briefcase class="w-12 h-12 text-gray-300" />
          </div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">Aucune offre publiée</h3>
          <p class="text-gray-500 max-w-xs mx-auto mb-8">Vous n'avez pas encore publié d'offres de service. Commencez par publier votre premier besoin !</p>
          <router-link to="/post-offer" class="inline-flex items-center space-x-2 bg-blue-600 text-white px-8 py-4 rounded-2xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-100 transition">
            <Plus class="w-5 h-5" />
            <span>Publier ma première offre</span>
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>
