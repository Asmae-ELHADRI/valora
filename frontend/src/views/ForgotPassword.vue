<script setup>
import { ref } from 'vue';
import api from '../services/api';
import { Mail, ArrowLeft, Loader2, CheckCircle } from 'lucide-vue-next';

const email = ref('');
const loading = ref(false);
const message = ref({ type: '', text: '' });
const submitted = ref(false);

const handleSubmit = async () => {
  loading.value = true;
  message.value = { type: '', text: '' };
  try {
    const response = await api.post('/api/password/forgot', { email: email.value });
    message.value = { type: 'success', text: response.data.message };
    submitted.value = true;
  } catch (err) {
    message.value = { type: 'error', text: err.response?.data?.message || 'Une erreur est survenue' };
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="min-h-screen flex items-center justify-center px-4 py-12 bg-gray-50">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl shadow-blue-100/50 border border-gray-100 p-8">
      <div class="text-center mb-8">
        <div class="bg-blue-50 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <Mail class="w-8 h-8 text-blue-600" />
        </div>
        <h2 class="text-2xl font-bold text-gray-900">Mot de passe oublié ?</h2>
        <p class="text-gray-500 mt-2">Entrez votre email pour recevoir un lien de réinitialisation.</p>
      </div>

      <div v-if="submitted" class="text-center space-y-6">
        <div class="bg-green-50 p-4 rounded-2xl flex items-center space-x-3 text-green-700 font-medium">
          <CheckCircle class="w-5 h-5 flex-shrink-0" />
          <p>{{ message.text }}</p>
        </div>
        <router-link to="/login" class="flex items-center justify-center space-x-2 text-blue-600 font-bold hover:underline">
          <ArrowLeft class="w-4 h-4" />
          <span>Retour à la connexion</span>
        </router-link>
      </div>

      <form v-else @submit.prevent="handleSubmit" class="space-y-6">
        <div v-if="message.text" :class="message.type === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'" class="p-4 rounded-2xl text-sm font-medium">
          {{ message.text }}
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Email</label>
          <div class="relative">
            <Mail class="absolute left-4 top-3.5 w-5 h-5 text-gray-400" />
            <input 
              v-model="email" 
              type="email" 
              required 
              placeholder="votre@email.com"
              class="w-full pl-12 pr-4 py-3.5 rounded-2xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition"
            >
          </div>
        </div>

        <button 
          type="submit" 
          :disabled="loading"
          class="w-full bg-blue-600 text-white py-4 rounded-2xl font-bold text-lg hover:bg-blue-700 shadow-lg shadow-blue-200 transition active:scale-[0.98] disabled:opacity-50 flex items-center justify-center space-x-2"
        >
          <Loader2 v-if="loading" class="w-5 h-5 animate-spin" />
          <span>Envoyer le lien</span>
        </button>

        <router-link to="/login" class="flex items-center justify-center space-x-2 text-gray-500 font-medium hover:text-blue-600 transition pt-2">
          <ArrowLeft class="w-4 h-4" />
          <span>Retour à la connexion</span>
        </router-link>
      </form>
    </div>
  </div>
</template>
