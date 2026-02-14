<script setup>
import { ref } from 'vue';
import api from '../../services/api';
import { 
  User,
  Bell,
  Key,
  Settings, 
  Lock, 
  Save,
  CheckCircle,
  AlertCircle
} from 'lucide-vue-next';

const form = ref({
    current_password: '',
    password: '',
    password_confirmation: ''
});

const loading = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

const updatePassword = async () => {
    loading.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    try {
        await api.post('/api/password/update', form.value);
        successMessage.value = 'Mot de passe mis à jour avec succès.';
        form.value = {
            current_password: '',
            password: '',
            password_confirmation: ''
        };
    } catch (error) {
        if (error.response && error.response.data && error.response.data.errors) {
            errorMessage.value = Object.values(error.response.data.errors).flat().join('\n');
        } else if (error.response && error.response.data && error.response.data.message) {
             errorMessage.value = error.response.data.message;
        } else {
            errorMessage.value = 'Une erreur est survenue.';
        }
    } finally {
        loading.value = false;
    }
};
</script>

<template>
  <div class="max-w-3xl mx-auto pb-20">
    
    <!-- Top Header -->
    <div class="mb-10 text-center">
        <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight mb-2">Paramètres</h1>
        <p class="text-base font-medium text-slate-500 dark:text-slate-400">
            Gérez la sécurité et les préférences de votre compte.
        </p>
    </div>

    <!-- Main Content -->
    <div class="space-y-8">
        
        <!-- Security Card -->
        <div class="bg-white dark:bg-[#1E293B] rounded-[32px] border border-slate-200 dark:border-white/5 shadow-xl shadow-slate-200/50 dark:shadow-none overflow-hidden relative">
            
            <!-- Deco Blob -->
            <div class="absolute top-0 right-0 -mr-20 -mt-20 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="p-8 md:p-10 border-b border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-white/5 relative z-10">
                <div class="flex items-center gap-6">
                    <div class="w-16 h-16 rounded-2xl bg-blue-600 text-white flex items-center justify-center shadow-lg shadow-blue-600/20 transform rotate-3">
                        <Lock class="w-8 h-8" />
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Sécurité</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 font-bold mt-1">Mise à jour du mot de passe</p>
                    </div>
                </div>
            </div>

            <div class="p-8 md:p-10 relative z-10">
                <form @submit.prevent="updatePassword" class="space-y-8">
                    
                    <!-- Alerts -->
                    <div v-if="successMessage" class="p-4 bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 rounded-2xl flex items-center gap-4 border border-emerald-100 dark:border-emerald-500/20 shadow-sm">
                        <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-500/20 flex items-center justify-center flex-shrink-0">
                            <CheckCircle class="w-4 h-4" />
                        </div>
                        <span class="text-sm font-bold">{{ successMessage }}</span>
                    </div>
                    
                    <div v-if="errorMessage" class="p-4 bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400 rounded-2xl flex items-start gap-4 border border-red-100 dark:border-red-500/20 shadow-sm">
                        <div class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-500/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <AlertCircle class="w-4 h-4" />
                        </div>
                        <span class="text-sm font-bold whitespace-pre-line">{{ errorMessage }}</span>
                    </div>

                    <!-- Current Password -->
                    <div class="space-y-4">
                        <div class="flex justify-between items-center px-1">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Mot de passe actuel</label>
                        </div>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                <Lock class="h-5 w-5 text-slate-300 group-focus-within:text-blue-500 transition-colors" />
                            </div>
                            <input 
                                type="password" 
                                v-model="form.current_password"
                                required
                                class="w-full pl-14 pr-4 py-4 bg-slate-50 dark:bg-[#0F172A] border border-slate-200 dark:border-white/10 rounded-2xl text-slate-900 dark:text-white text-sm font-bold placeholder-slate-400 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all shadow-inner"
                                placeholder="••••••••••••"
                            >
                        </div>
                    </div>

                    <div class="h-px bg-slate-100 dark:bg-white/5 my-8"></div>

                    <!-- New Passwords Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-3">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest px-1">Nouveau mot de passe</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                    <Key class="h-5 w-5 text-slate-300 group-focus-within:text-blue-500 transition-colors" />
                                </div>
                                <input 
                                    type="password" 
                                    v-model="form.password"
                                    required
                                    class="w-full pl-14 pr-4 py-4 bg-slate-50 dark:bg-[#0F172A] border border-slate-200 dark:border-white/10 rounded-2xl text-slate-900 dark:text-white text-sm font-bold placeholder-slate-400 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all shadow-inner"
                                    placeholder="Min. 8 caractères"
                                >
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <label class="text-xs font-black text-slate-500 uppercase tracking-widest px-1">Confirmation</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                    <CheckCircle class="h-5 w-5 text-slate-300 group-focus-within:text-emerald-500 transition-colors" />
                                </div>
                                <input 
                                    type="password" 
                                    v-model="form.password_confirmation"
                                    required
                                    class="w-full pl-14 pr-4 py-4 bg-slate-50 dark:bg-[#0F172A] border border-slate-200 dark:border-white/10 rounded-2xl text-slate-900 dark:text-white text-sm font-bold placeholder-slate-400 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all shadow-inner"
                                    placeholder="Répétez le mot de passe"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-6 flex items-center justify-end gap-4">
                        <button 
                            type="submit" 
                            :disabled="loading"
                            class="w-full md:w-auto flex items-center justify-center gap-3 px-10 py-4 bg-slate-900 dark:bg-blue-600 hover:bg-slate-800 dark:hover:bg-blue-500 text-white text-sm font-black rounded-2xl shadow-xl shadow-slate-900/20 dark:shadow-blue-600/20 transform hover:-translate-y-1 active:scale-95 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                        >
                            <span v-if="loading" class="animate-spin w-4 h-4 border-2 border-white/30 border-t-white rounded-full"></span>
                            <Save v-else class="w-4 h-4" />
                            <span>{{ loading ? 'Enregistrement...' : 'Mettre à jour' }}</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
  </div>
</template>
