<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../store/auth';
import { Users, Briefcase, Flag, BarChart3 } from 'lucide-vue-next';

const auth = useAuthStore();
const stats = ref({
  users: 0,
  offers: 0,
  reports: 0
});

onMounted(async () => {
  // Fetch admin stats here
});
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-10">
      <h1 class="text-3xl font-bold text-gray-900">Console d'administration</h1>
      <p class="text-gray-500 mt-2">Vue d'ensemble de la plateforme VALORA</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-4">
          <div class="bg-blue-50 p-3 rounded-xl">
            <Users class="w-6 h-6 text-blue-600" />
          </div>
          <span class="text-green-600 text-xs font-bold bg-green-50 px-2 py-1 rounded">+12%</span>
        </div>
        <p class="text-sm text-gray-500 font-medium">Utilisateurs</p>
        <p class="text-3xl font-bold">1,248</p>
      </div>
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-4">
          <div class="bg-purple-50 p-3 rounded-xl">
            <Briefcase class="w-6 h-6 text-purple-600" />
          </div>
          <span class="text-green-600 text-xs font-bold bg-green-50 px-2 py-1 rounded">+5%</span>
        </div>
        <p class="text-sm text-gray-500 font-medium">Offres</p>
        <p class="text-3xl font-bold">856</p>
      </div>
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-4">
          <div class="bg-red-50 p-3 rounded-xl">
            <Flag class="w-6 h-6 text-red-600" />
          </div>
          <span class="text-red-600 text-xs font-bold bg-red-50 px-2 py-1 rounded">2 nouveaux</span>
        </div>
        <p class="text-sm text-gray-500 font-medium">Signalements</p>
        <p class="text-3xl font-bold">14</p>
      </div>
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-4">
          <div class="bg-orange-50 p-3 rounded-xl">
            <BarChart3 class="w-6 h-6 text-orange-600" />
          </div>
        </div>
        <p class="text-sm text-gray-500 font-medium">Chiffre d'affaires</p>
        <p class="text-3xl font-bold">12,450 €</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
          <h2 class="font-bold text-gray-900">Derniers utilisateurs inscrits</h2>
          <button class="text-blue-600 text-xs font-bold uppercase tracking-wider">Gérer tout</button>
        </div>
        <div class="divide-y divide-gray-100">
          <div v-for="i in 5" :key="i" class="px-6 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 rounded-full bg-gray-100"></div>
              <div>
                <p class="text-sm font-semibold">Utilisateur #{{ i }}</p>
                <p class="text-xs text-gray-500">Inscrit il y a 2h</p>
              </div>
            </div>
            <span class="px-2 py-1 text-[10px] font-bold rounded uppercase" :class="i % 2 === 0 ? 'bg-blue-50 text-blue-600' : 'bg-purple-50 text-purple-600'">
              {{ i % 2 === 0 ? 'Client' : 'Prestataire' }}
            </span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
          <h2 class="font-bold text-gray-900">Signalements récents</h2>
          <button class="text-blue-600 text-xs font-bold uppercase tracking-wider">Modérer</button>
        </div>
        <div class="p-10 text-center">
          <Flag class="w-8 h-8 text-gray-200 mx-auto mb-4" />
          <p class="text-gray-500 text-sm">Aucun signalement urgent à traiter.</p>
        </div>
      </div>
    </div>
  </div>
</template>
