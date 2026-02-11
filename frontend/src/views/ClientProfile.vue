<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import { User, Camera, Loader2, Save, MapPin, Phone, Briefcase } from 'lucide-vue-next';
import PhotoUploader from '../components/PhotoUploader.vue';

const auth = useAuthStore();
const loading = ref(false);
const saving = ref(false);

const profileForm = ref({
  first_name: '',
  last_name: '',
  phone: '',
  address: '',
  type: 'individual' // individual or company
});

const initForm = () => {
    const user = auth.user;
    if (user) {
        profileForm.value = {
            first_name: user.first_name || '',
            last_name: user.last_name || '',
            phone: user.phone || '',
            address: user.address || '',
            type: user.client?.type || 'individual'
        };
    }
};

const updateProfile = async () => {
    saving.value = true;
    try {
        const response = await api.post('/api/client/profile', profileForm.value);
        auth.user = response.data.user;
        alert('Profil mis à jour avec succès');
    } catch (err) {
        console.error(err);
        alert('Erreur lors de la mise à jour du profil');
    } finally {
        saving.value = false;
    }
};

const handlePhotoUpdate = async () => {
    await auth.fetchUser();
};

onMounted(initForm);
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Photo -->
        <div class="md:col-span-1 space-y-6">
            <div class="bg-white p-6 rounded-4xl shadow-sm border border-slate-100">
                <h3 class="font-bold text-slate-900 mb-6">Logo / Photo</h3>
                <PhotoUploader 
                    :current-photo="auth.user?.client?.photo_url" 
                    upload-url="/api/client/photo"
                    @photo-updated="handlePhotoUpdate" 
                />
            </div>
        </div>

        <!-- Info -->
        <div class="md:col-span-2">
            <div class="bg-white p-8 rounded-4xl shadow-sm border border-slate-100">
                <h3 class="font-bold text-slate-900 mb-8">Informations personnelles</h3>
                <form @submit.prevent="updateProfile" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2 pl-1">Prénom</label>
                            <input v-model="profileForm.first_name" type="text" class="w-full px-5 py-3.5 rounded-2xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 outline-none transition-all font-medium text-slate-900 placeholder:text-slate-400">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2 pl-1">Nom</label>
                            <input v-model="profileForm.last_name" type="text" class="w-full px-5 py-3.5 rounded-2xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 outline-none transition-all font-medium text-slate-900 placeholder:text-slate-400">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2 pl-1">Type de compte</label>
                        <div class="flex space-x-4">
                            <label class="flex items-center space-x-2 cursor-pointer bg-slate-50 px-5 py-3 rounded-2xl border border-slate-100 hover:bg-white transition-all" :class="{'ring-2 ring-premium-yellow border-premium-yellow bg-white': profileForm.type === 'individual'}">
                                <input type="radio" v-model="profileForm.type" value="individual" class="hidden">
                                <User class="w-4 h-4 text-slate-500" />
                                <span class="font-bold text-sm text-slate-700">Particulier</span>
                            </label>
                            <label class="flex items-center space-x-2 cursor-pointer bg-slate-50 px-5 py-3 rounded-2xl border border-slate-100 hover:bg-white transition-all" :class="{'ring-2 ring-premium-yellow border-premium-yellow bg-white': profileForm.type === 'company'}">
                                <input type="radio" v-model="profileForm.type" value="company" class="hidden">
                                <Briefcase class="w-4 h-4 text-slate-500" />
                                <span class="font-bold text-sm text-slate-700">Entreprise</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2 pl-1">Téléphone</label>
                             <div class="relative">
                                <Phone class="w-4 h-4 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2" />
                                <input v-model="profileForm.phone" type="text" class="w-full pl-12 pr-5 py-3.5 rounded-2xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 outline-none transition-all font-medium text-slate-900 placeholder:text-slate-400">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2 pl-1">Adresse</label>
                            <div class="relative">
                                <MapPin class="w-4 h-4 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2" />
                                <input v-model="profileForm.address" type="text" class="w-full pl-12 pr-5 py-3.5 rounded-2xl border border-slate-100 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 outline-none transition-all font-medium text-slate-900 placeholder:text-slate-400">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button 
                            type="submit"
                            :disabled="saving"
                            class="bg-premium-blue text-white px-10 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-xl shadow-blue-900/10 flex items-center space-x-3 disabled:opacity-50"
                        >
                            <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
                            <Save v-else class="w-5 h-5" />
                            <span>Enregistrer</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
