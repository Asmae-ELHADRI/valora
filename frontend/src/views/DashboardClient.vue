<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../store/auth';
import { Plus, Briefcase, Clock, CheckCircle } from 'lucide-vue-next';

const auth = useAuthStore();
const offers = ref([]);
const loading = ref(true);

onMounted(async () => {
  // Fetch client offers here
  loading.value = false;
});
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex justify-between items-center mb-10">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Tableau de bord Client</h1>
        <p class="text-gray-500 mt-2">Gérez vos offres et vos demandes de services</p>
      </div>
      <router-link to="/post-offer" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-700 transition flex items-center space-x-2">
        <Plus class="w-5 h-5" />
        <span>Publier une offre</span>
      </router-link>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="bg-blue-50 p-3 rounded-xl">
          <Briefcase class="w-6 h-6 text-blue-600" />
        </div>
        <div>
          <p class="text-sm text-gray-500 font-medium">Offres actives</p>
          <p class="text-2xl font-bold">0</p>
        </div>
      </div>
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="bg-yellow-50 p-3 rounded-xl">
          <Clock class="w-6 h-6 text-yellow-600" />
        </div>
        <div>
          <p class="text-sm text-gray-500 font-medium">En attente</p>
          <p class="text-2xl font-bold">0</p>
        </div>
      </div>
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="bg-green-50 p-3 rounded-xl">
          <CheckCircle class="w-6 h-6 text-green-600" />
        </div>
        <div>
          <p class="text-sm text-gray-500 font-medium">Terminées</p>
          <p class="text-2xl font-bold">0</p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
        <h2 class="font-bold text-gray-900">Vos dernières publications</h2>
        <button class="text-blue-600 text-sm font-semibold hover:underline">Voir tout</button>
      </div>
      <div class="p-10 text-center" v-if="offers.length === 0">
        <div class="bg-gray-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
          <Briefcase class="w-8 h-8 text-gray-300" />
        </div>
        <p class="text-gray-500">Vous n'avez pas encore publié d'offres.</p>
        <button class="mt-4 text-blue-600 font-semibold">Commencer maintenant</button>
      </div>
    </div>
  </div>
</template>
