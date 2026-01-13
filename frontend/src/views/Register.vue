<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import { User, Mail, Lock, Briefcase, Users, Loader2 } from 'lucide-vue-next';

const auth = useAuthStore();
const router = useRouter();
const name = ref('');
const email = ref('');
const password = ref('');
const password_confirmation = ref('');
const role = ref('client');
const loading = ref(false);
const error = ref('');

const handleRegister = async () => {
  if (password.value !== password_confirmation.value) {
    error.value = 'Les mots de passe ne correspondent pas';
    return;
  }

  loading.value = true;
  error.value = '';
  try {
    await auth.register({
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: password_confirmation.value,
      role: role.value
    });
    router.push('/dashboard');
  } catch (err) {
    error.value = err.response?.data?.message || 'Une erreur est survenue';
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="max-w-2xl mx-auto mt-12 p-8 bg-white rounded-2xl shadow-xl border border-gray-100">
    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Créer un compte</h1>
      <p class="text-gray-500 mt-2">Rejoignez la plateforme VALORA</p>
    </div>

    <form @submit.prevent="handleRegister" class="space-y-6">
      <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">
        {{ error }}
      </div>

      <!-- Role Selection -->
      <div class="grid grid-cols-2 gap-4">
        <button 
          type="button"
          @click="role = 'client'"
          :class="role === 'client' ? 'border-blue-600 bg-blue-50 text-blue-600' : 'border-gray-200 text-gray-500'"
          class="flex flex-col items-center p-4 border-2 rounded-xl transition hover:border-blue-300"
        >
          <Users class="w-8 h-8 mb-2" />
          <span class="font-medium">Je suis un Client</span>
        </button>
        <button 
          type="button"
          @click="role = 'provider'"
          :class="role === 'provider' ? 'border-blue-600 bg-blue-50 text-blue-600' : 'border-gray-200 text-gray-500'"
          class="flex flex-col items-center p-4 border-2 rounded-xl transition hover:border-blue-300"
        >
          <Briefcase class="w-8 h-8 mb-2" />
          <span class="font-medium">Je suis Prestataire</span>
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Nom complet</label>
          <div class="relative">
            <User class="absolute left-3 top-3 w-5 h-5 text-gray-400" />
            <input 
              v-model="name" 
              type="text" 
              required
              class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
              placeholder="Jean Dupont"
            >
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
          <div class="relative">
            <Mail class="absolute left-3 top-3 w-5 h-5 text-gray-400" />
            <input 
              v-model="email" 
              type="email" 
              required
              class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
              placeholder="votre@email.com"
            >
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Mot de passe</label>
          <div class="relative">
            <Lock class="absolute left-3 top-3 w-5 h-5 text-gray-400" />
            <input 
              v-model="password" 
              type="password" 
              required
              class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
              placeholder="••••••••"
            >
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Confirmer le mot de passe</label>
          <div class="relative">
            <Lock class="absolute left-3 top-3 w-5 h-5 text-gray-400" />
            <input 
              v-model="password_confirmation" 
              type="password" 
              required
              class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
              placeholder="••••••••"
            >
          </div>
        </div>
      </div>

      <button 
        type="submit" 
        :disabled="loading"
        class="w-full bg-blue-600 text-white py-4 rounded-xl font-semibold hover:bg-blue-700 transition flex items-center justify-center space-x-2 disabled:opacity-50"
      >
        <Loader2 v-if="loading" class="w-5 h-5 animate-spin" />
        <span>{{ loading ? 'Création du compte...' : 'Créer mon compte' }}</span>
      </button>
    </form>

    <div class="mt-8 text-center text-sm text-gray-500">
      Déjà inscrit ? 
      <router-link to="/login" class="text-blue-600 font-semibold hover:underline">Se connecter</router-link>
    </div>
  </div>
</template>
