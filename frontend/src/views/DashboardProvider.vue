<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../store/auth';
import { Search, FileText, Star, TrendingUp } from 'lucide-vue-next';

const auth = useAuthStore();
const applications = ref([]);
const loading = ref(true);

onMounted(async () => {
  // Fetch provider applications here
  loading.value = false;
});
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

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="bg-purple-50 p-3 rounded-xl">
          <FileText class="w-6 h-6 text-purple-600" />
        </div>
        <div>
          <p class="text-sm text-gray-500 font-medium">Candidatures</p>
          <p class="text-2xl font-bold">0</p>
        </div>
      </div>
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="bg-yellow-50 p-3 rounded-xl">
          <Star class="w-6 h-6 text-yellow-600" />
        </div>
        <div>
          <p class="text-sm text-gray-500 font-medium">Note moyenne</p>
          <p class="text-2xl font-bold">0.0</p>
        </div>
      </div>
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="bg-green-50 p-3 rounded-xl">
          <TrendingUp class="w-6 h-6 text-green-600" />
        </div>
        <div>
          <p class="text-sm text-gray-500 font-medium">Gains totaux</p>
          <p class="text-2xl font-bold">0 €</p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="font-bold text-gray-900">Vos candidatures récentes</h2>
      </div>
      <div class="p-10 text-center" v-if="applications.length === 0">
        <div class="bg-gray-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
          <FileText class="w-8 h-8 text-gray-300" />
        </div>
        <p class="text-gray-500">Vous n'avez pas encore postulé à des offres.</p>
        <router-link to="/search" class="mt-4 inline-block text-blue-600 font-semibold">Voir les offres disponibles</router-link>
      </div>
    </div>
  </div>
</template>
