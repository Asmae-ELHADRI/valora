<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../store/auth';
import { Lock, Mail, Loader2 } from 'lucide-vue-next';

const auth = useAuthStore();
const router = useRouter();

const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);

const handleLogin = async () => {
  loading.value = true;
  error.value = '';
  try {
    await auth.login({ email: email.value, password: password.value });
    router.push('/dashboard');
  } catch (err) {
    error.value = err.response?.data?.message || 'Une erreur est survenue';
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="max-w-md mx-auto mt-20 p-8 bg-white rounded-2xl shadow-xl border border-gray-100">
    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Bienvenue</h1>
      <p class="text-gray-500 mt-2">Connectez-vous pour continuer</p>
    </div>

    <form @submit.prevent="handleLogin" class="space-y-6">
      <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">
        {{ error }}
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
        <div class="flex justify-end mt-2">
          <router-link to="/forgot-password" class="text-xs text-blue-600 hover:underline">Mot de passe oublié ?</router-link>
        </div>
      </div>

      <button 
        type="submit" 
        :disabled="loading"
        class="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold hover:bg-blue-700 transition flex items-center justify-center space-x-2 disabled:opacity-50"
      >
        <Loader2 v-if="loading" class="w-5 h-5 animate-spin" />
        <span>{{ loading ? 'Connexion...' : 'Se connecter' }}</span>
      </button>
    </form>

    <div class="mt-8 text-center text-sm text-gray-500">
      Pas encore de compte ? 
      <router-link to="/register" class="text-blue-600 font-semibold hover:underline">S'inscrire</router-link>
    </div>
  </div>
</template>
