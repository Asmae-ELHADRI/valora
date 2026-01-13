<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import { 
  Lock, ShieldCheck, AlertTriangle, Trash2, 
  Loader2, Key, ShieldAlert, CheckCircle, Info
} from 'lucide-vue-next';
import { useRouter } from 'vue-router';

const auth = useAuthStore();
const router = useRouter();

const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: ''
});

const changingPassword = ref(false);
const showDeleteConfirm = ref(false);
const deleting = ref(false);
const deleteConfirmText = ref('');
const successMessage = ref('');
const errorMessage = ref('');

const handleChangePassword = async () => {
  changingPassword.value = true;
  successMessage.value = '';
  errorMessage.value = '';
  
  try {
    const response = await api.post('/api/password/update', passwordForm.value);
    successMessage.value = response.data.message;
    passwordForm.value = {
      current_password: '',
      password: '',
      password_confirmation: ''
    };
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Erreur lors de la mise à jour';
  } finally {
    changingPassword.value = false;
  }
};

const handleDeleteAccount = async () => {
  if (deleteConfirmText.value !== 'SUPPRIMER') return;
  
  deleting.value = true;
  try {
    await api.delete('/api/account/delete');
    auth.logout();
    router.push('/');
    alert('Votre compte a été supprimé définitivement.');
  } catch (err) {
    alert('Erreur lors de la suppression du compte.');
  } finally {
    deleting.value = false;
  }
};
</script>

<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-10">
      <h1 class="text-3xl font-black text-gray-900">Sécurité du compte</h1>
      <p class="text-gray-500 mt-2">Gérez la sécurité de votre accès et vos données personnelles.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <!-- Sidebar / Info -->
      <div class="space-y-6">
        <div class="bg-blue-600 rounded-[32px] p-8 text-white shadow-xl shadow-blue-100 relative overflow-hidden">
          <ShieldCheck class="w-12 h-12 mb-6" />
          <h3 class="text-xl font-bold mb-2">Votre sécurité est notre priorité</h3>
          <p class="text-blue-100 text-sm leading-relaxed">Nous utilisons des protocoles de sécurité avancés pour protéger vos informations personnelles.</p>
          <Lock class="absolute -bottom-4 -right-4 w-24 h-24 text-blue-500 opacity-20" />
        </div>

        <div class="bg-white rounded-[32px] p-6 border border-gray-100 shadow-sm space-y-4">
          <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest flex items-center">
            <Info class="w-4 h-4 mr-2 text-blue-600" />
            Conseils
          </h4>
          <ul class="space-y-3">
            <li class="text-sm text-gray-500 flex items-start">
              <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-1.5 mr-2 shrink-0"></span>
              Utilisez un mot de passe unique.
            </li>
            <li class="text-sm text-gray-500 flex items-start">
              <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-1.5 mr-2 shrink-0"></span>
              Minimum 8 caractères.
            </li>
            <li class="text-sm text-gray-500 flex items-start">
              <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-1.5 mr-2 shrink-0"></span>
              Mélangez lettres, chiffres et symboles.
            </li>
          </ul>
        </div>
      </div>

      <!-- Main Content -->
      <div class="md:col-span-2 space-y-8">
        <!-- Password Change Section -->
        <div class="bg-white rounded-[40px] p-10 border border-gray-100 shadow-sm relative overflow-hidden">
          <div class="flex items-center space-x-4 mb-8">
            <div class="w-12 h-12 bg-gray-50 rounded-2xl flex items-center justify-center text-gray-900">
              <Key class="w-6 h-6" />
            </div>
            <h2 class="text-2xl font-black text-gray-900">Changer de mot de passe</h2>
          </div>

          <form @submit.prevent="handleChangePassword" class="space-y-6">
            <div v-if="successMessage" class="p-4 bg-green-50 text-green-700 rounded-2xl flex items-center mb-6">
              <CheckCircle class="w-5 h-5 mr-3" />
              <span class="text-sm font-bold">{{ successMessage }}</span>
            </div>
            
            <div v-if="errorMessage" class="p-4 bg-red-50 text-red-700 rounded-2xl flex items-center mb-6">
              <ShieldAlert class="w-5 h-5 mr-3" />
              <span class="text-sm font-bold">{{ errorMessage }}</span>
            </div>

            <div class="space-y-2">
              <label class="block text-xs font-black text-gray-400 uppercase tracking-widest ml-1">Mot de passe actuel</label>
              <input 
                v-model="passwordForm.current_password"
                type="password" 
                required
                class="w-full px-5 py-4 bg-gray-50 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 transition"
              >
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest ml-1">Nouveau mot de passe</label>
                <input 
                  v-model="passwordForm.password"
                  type="password" 
                  required
                  class="w-full px-5 py-4 bg-gray-50 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 transition"
                >
              </div>
              <div class="space-y-2">
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest ml-1">Confirmer</label>
                <input 
                  v-model="passwordForm.password_confirmation"
                  type="password" 
                  required
                  class="w-full px-5 py-4 bg-gray-50 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 transition"
                >
              </div>
            </div>

            <button 
              type="submit"
              :disabled="changingPassword"
              class="bg-gray-900 text-white py-4 px-10 rounded-2xl font-bold hover:bg-black transition active:scale-[0.98] flex items-center justify-center space-x-3 disabled:opacity-50"
            >
              <Loader2 v-if="changingPassword" class="w-5 h-5 animate-spin" />
              <span>Mettre à jour le mot de passe</span>
            </button>
          </form>
        </div>

        <!-- Danger Zone -->
        <div class="bg-red-50 rounded-[40px] p-10 border border-red-100 relative overflow-hidden">
          <div class="flex items-center space-x-4 mb-4 text-red-600">
            <AlertTriangle class="w-8 h-8" />
            <h2 class="text-2xl font-black">Zone de danger</h2>
          </div>
          <p class="text-red-700 text-sm mb-8 leading-relaxed">
            La suppression de votre compte est définitive. Toutes vos données, missions et historiques seront supprimés sans possibilité de récupération.
          </p>

          <button 
            @click="showDeleteConfirm = true"
            class="bg-white text-red-600 px-8 py-4 rounded-2xl font-bold border border-red-200 hover:bg-red-600 hover:text-white transition duration-300"
          >
            Supprimer mon compte
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteConfirm" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showDeleteConfirm = false"></div>
      <div class="relative bg-white w-full max-w-lg rounded-[40px] shadow-2xl p-10 animate-in fade-in zoom-in duration-300 text-center">
        <div class="w-20 h-20 bg-red-100 rounded-3xl flex items-center justify-center mx-auto mb-8 text-red-600">
          <Trash2 class="w-10 h-10" />
        </div>
        <h3 class="text-2xl font-black text-gray-900 mb-4">Êtes-vous absolument sûr ?</h3>
        <p class="text-gray-500 mb-8 leading-relaxed">
          Pour confirmer, veuillez saisir <span class="font-black text-gray-900">"SUPPRIMER"</span> ci-dessous.
        </p>

        <input 
          v-model="deleteConfirmText"
          type="text" 
          placeholder="SUPPRIMER"
          class="w-full text-center px-5 py-4 bg-gray-50 border-2 border-red-100 rounded-2xl text-lg font-black focus:border-red-500 focus:ring-0 outline-none transition mb-8"
        >

        <div class="grid grid-cols-2 gap-4">
          <button 
            @click="showDeleteConfirm = false"
            class="py-4 rounded-2xl font-bold bg-gray-100 text-gray-700 hover:bg-gray-200 transition"
          >
            Annuler
          </button>
          <button 
            @click="handleDeleteAccount"
            :disabled="deleteConfirmText !== 'SUPPRIMER' || deleting"
            class="py-4 rounded-2xl font-bold bg-red-600 text-white hover:bg-red-700 transition disabled:opacity-50 flex items-center justify-center"
          >
            <Loader2 v-if="deleting" class="w-5 h-5 animate-spin mr-2" />
            Confirmer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
