<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../services/api';
import { 
  Lock, Key, Loader2, CheckCircle, AlertCircle
} from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();

const form = ref({
  email: route.query.email || '',
  token: route.query.token || '',
  password: '',
  password_confirmation: ''
});

const loading = ref(false);
const success = ref(false);
const error = ref('');

const handleSubmit = async () => {
  loading.value = true;
  error.value = '';
  try {
    const response = await api.post('/api/password/reset', form.value);
    success.value = true;
    setTimeout(() => {
      router.push('/login');
    }, 3000);
  } catch (err) {
    error.value = err.response?.data?.message || 'Lien invalide ou expiré';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  if (!form.value.token) {
    error.value = 'Jeton de réinitialisation manquant.';
  }
});
</script>

<template>
  <div class="min-h-[80vh] flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full bg-white rounded-[40px] shadow-2xl shadow-blue-50 p-10 relative overflow-hidden">
      <div class="text-center mb-10">
        <div class="w-20 h-20 bg-gray-900 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-xl shadow-gray-200">
          <Key class="w-10 h-10 text-white" />
        </div>
        <h2 class="text-3xl font-black text-gray-900">Nouveau mot de passe</h2>
        <p class="text-gray-500 mt-2">Définissez vos nouveaux identifiants de connexion.</p>
      </div>

      <div v-if="success" class="text-center space-y-6 animate-in fade-in slide-in-from-bottom duration-500">
        <div class="p-6 bg-green-50 rounded-[32px] border border-green-100">
          <CheckCircle class="w-12 h-12 text-green-500 mx-auto mb-4" />
          <h3 class="text-xl font-bold text-green-900 mb-2">Réinitialisé !</h3>
          <p class="text-sm text-green-700">Votre mot de passe a été mis à jour. Redirection vers la page de connexion...</p>
        </div>
      </div>

      <form v-else @submit.prevent="handleSubmit" class="space-y-6">
        <div v-if="error" class="p-4 bg-red-50 text-red-700 rounded-2xl flex items-center mb-6">
          <AlertCircle class="w-5 h-5 mr-3" />
          <span class="text-sm font-bold">{{ error }}</span>
        </div>

        <div class="space-y-2">
          <label class="block text-xs font-black text-gray-400 uppercase tracking-widest ml-1">Adresse Email</label>
          <input 
            v-model="form.email"
            type="email" 
            readonly
            class="w-full px-5 py-4 bg-gray-100 border-none rounded-2xl text-sm text-gray-400 outline-none"
          >
        </div>

        <div class="space-y-2">
          <label class="block text-xs font-black text-gray-400 uppercase tracking-widest ml-1">Nouveau mot de passe</label>
          <div class="relative">
            <Lock class="absolute left-5 top-4.5 w-5 h-5 text-gray-400" />
            <input 
              v-model="form.password"
              type="password" 
              required
              class="w-full pl-14 pr-5 py-4 bg-gray-50 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 transition"
            >
          </div>
        </div>

        <div class="space-y-2">
          <label class="block text-xs font-black text-gray-400 uppercase tracking-widest ml-1">Confirmer le mot de passe</label>
          <div class="relative">
            <Lock class="absolute left-5 top-4.5 w-5 h-5 text-gray-400" />
            <input 
              v-model="form.password_confirmation"
              type="password" 
              required
              class="w-full pl-14 pr-5 py-4 bg-gray-50 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 transition"
            >
          </div>
        </div>

        <button 
          type="submit"
          :disabled="loading || !form.token"
          class="w-full bg-gray-900 text-white py-5 rounded-2xl font-bold hover:bg-black shadow-xl shadow-gray-100 transition active:scale-[0.98] flex items-center justify-center space-x-3 disabled:opacity-50"
        >
          <Loader2 v-if="loading" class="w-6 h-6 animate-spin" />
          <span>Réinitialiser</span>
        </button>
      </form>
    </div>
  </div>
</template>
