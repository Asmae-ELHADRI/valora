<script setup>
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import { Search, FileText, Star, TrendingUp, Clock, CheckCircle, XCircle, MapPin, Loader2 } from 'lucide-vue-next';

const auth = useAuthStore();
const applications = ref([]);
const reviewsData = ref({ reviews: [], average_rating: 0, total_reviews: 0 });
const loading = ref(true);

const fetchApplications = async () => {
  try {
    const response = await api.get('/api/my-applications');
    applications.value = response.data;
  } catch (err) {
    console.error('Erreur chargement candidatures:', err);
  }
};

const fetchReviews = async () => {
  try {
    const response = await api.get('/api/my-reviews');
    reviewsData.value = response.data;
  } catch (err) {
    console.error('Erreur chargement avis:', err);
  }
};

onMounted(async () => {
  await Promise.all([fetchApplications(), fetchReviews()]);
  loading.value = false;
});

const stats = computed(() => {
  return {
    total: applications.value.length,
    accepted: applications.value.filter(a => a.status === 'accepted').length,
    completed: applications.value.filter(a => a.status === 'completed').length,
  };
});

const getStatusClass = (status) => {
  switch (status) {
    case 'pending': return 'bg-yellow-50 text-yellow-700 border-yellow-100';
    case 'accepted': return 'bg-green-50 text-green-700 border-green-100';
    case 'rejected': return 'bg-red-50 text-red-700 border-red-100';
    case 'completed': return 'bg-blue-50 text-blue-700 border-blue-100';
    default: return 'bg-gray-50 text-gray-700 border-gray-100';
  }
};

const getStatusLabel = (status) => {
  switch (status) {
    case 'pending': return 'En attente';
    case 'accepted': return 'Acceptée';
    case 'rejected': return 'Refusée';
    case 'completed': return 'Terminée';
    default: return status;
  }
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short'
  });
};

const updateStatus = async (id, status) => {
  try {
    await api.post(`/api/service-requests/${id}/status`, { status });
    await fetchApplications();
  } catch (err) {
    alert('Erreur lors de la mise à jour du statut');
  }
};
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex justify-between items-center mb-10">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Tableau de bord Prestataire</h1>
        <p class="text-gray-500 mt-2">Gérez vos candidatures et trouvez de nouveaux projets</p>
      </div>
      <router-link to="/search" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-700 transition flex items-center space-x-2">
        <Search class="w-5 h-5" />
        <span>Trouver du travail</span>
      </router-link>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="bg-purple-50 p-3 rounded-xl">
          <FileText class="w-6 h-6 text-purple-600" />
        </div>
        <div>
          <p class="text-sm text-gray-500 font-medium">Candidatures</p>
          <p class="text-2xl font-bold">{{ stats.total }}</p>
        </div>
      </div>
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="bg-yellow-50 p-3 rounded-xl">
          <Star class="w-6 h-6 text-yellow-600" />
        </div>
        <div>
          <p class="text-sm text-gray-500 font-medium">Note moyenne</p>
          <div class="flex items-baseline space-x-1">
            <p class="text-2xl font-bold">{{ reviewsData.average_rating }}</p>
            <p class="text-xs text-gray-400">({{ reviewsData.total_reviews }} avis)</p>
          </div>
        </div>
      </div>
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="bg-blue-50 p-3 rounded-xl">
          <TrendingUp class="w-6 h-6 text-blue-600" />
        </div>
        <div>
          <p class="text-sm text-gray-500 font-medium">Missions terminées</p>
          <p class="text-2xl font-bold">{{ stats.completed }}</p>
        </div>
      </div>
    </div>

    <!-- Applications List -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="font-bold text-gray-900">Vos candidatures récentes</h2>
      </div>

      <div v-if="loading" class="p-20 flex justify-center">
        <Loader2 class="w-10 h-10 text-blue-600 animate-spin" />
      </div>

      <div v-else-if="applications.length === 0" class="p-10 text-center">
        <div class="bg-gray-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
          <FileText class="w-8 h-8 text-gray-300" />
        </div>
        <p class="text-gray-500">Vous n'avez pas encore postulé à des offres.</p>
        <router-link to="/search" class="mt-4 inline-block text-blue-600 font-semibold">Voir les offres disponibles</router-link>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider">
            <tr>
              <th class="px-6 py-4">Projet / Client</th>
              <th class="px-6 py-4">Date Mission</th>
              <th class="px-6 py-4">Budget</th>
              <th class="px-6 py-4">Statut</th>
              <th class="px-6 py-4">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="app in applications" :key="app.id" class="hover:bg-gray-50 transition">
              <td class="px-6 py-4">
                <p class="font-bold text-gray-900">{{ app.service_offer?.title }}</p>
                <p class="text-xs text-gray-500">Client: {{ app.service_offer?.user?.name }}</p>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">
                {{ formatDate(app.service_offer?.desired_date) }}
              </td>
              <td class="px-6 py-4 text-sm font-bold text-gray-900">
                {{ app.service_offer?.budget }} €
              </td>
              <td class="px-6 py-4">
                <span :class="getStatusClass(app.status)" class="px-3 py-1 rounded-full text-[10px] font-bold uppercase border">
                  {{ getStatusLabel(app.status) }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center space-x-2">
                  <template v-if="app.status === 'pending' && app.created_by_id !== auth.user.id">
                    <button @click="updateStatus(app.id, 'accepted')" class="bg-green-600 text-white px-3 py-1 rounded-lg text-xs font-bold hover:bg-green-700 transition">Accepter</button>
                    <button @click="updateStatus(app.id, 'rejected')" class="bg-red-600 text-white px-3 py-1 rounded-lg text-xs font-bold hover:bg-red-700 transition">Refuser</button>
                  </template>
                  <template v-else-if="app.status === 'accepted'">
                    <button @click="updateStatus(app.id, 'completed')" class="text-blue-600 hover:text-blue-800 font-bold text-xs uppercase tracking-wider">Terminer</button>
                  </template>
                  <template v-else>
                    <button class="text-gray-400 hover:text-gray-600 font-semibold text-sm">Détails</button>
                  </template>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Reviews List -->
    <div class="mt-8 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
        <h2 class="font-bold text-gray-900">Derniers avis clients</h2>
        <div class="flex items-center text-yellow-400">
          <Star v-for="i in 5" :key="i" :class="i <= Math.round(reviewsData.average_rating) ? 'fill-current' : 'text-gray-200'" class="w-4 h-4" />
        </div>
      </div>

      <div v-if="loading" class="p-10 flex justify-center">
        <Loader2 class="w-8 h-8 text-blue-600 animate-spin" />
      </div>

      <div v-else-if="reviewsData.reviews.length === 0" class="p-10 text-center">
        <p class="text-gray-500">Aucun avis reçu pour le moment.</p>
      </div>

      <div v-else class="divide-y divide-gray-50">
        <div v-for="review in reviewsData.reviews" :key="review.id" class="p-6">
          <div class="flex justify-between mb-2">
            <h4 class="font-bold text-gray-900">{{ review.reviewer?.name }}</h4>
            <div class="flex text-yellow-400">
              <Star v-for="i in 5" :key="i" :class="i <= review.rating ? 'fill-current' : 'text-gray-200'" class="w-3 h-3" />
            </div>
          </div>
          <p class="text-sm text-gray-600 italic">"{{ review.comment }}"</p>
          <div class="mt-2 text-xs text-gray-400 flex items-center space-x-2">
             <span>Sur la mission: {{ review.service_offer?.title }}</span>
             <span>•</span>
             <span>{{ formatDate(review.created_at) }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
