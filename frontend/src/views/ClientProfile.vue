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
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-900 mb-6">Logo / Photo</h3>
                <PhotoUploader 
                    :current-photo="auth.user?.client?.photo_url" 
                    upload-url="/api/client/photo"
                    @photo-updated="handlePhotoUpdate" 
                />
            </div>
        </div>

        <!-- Info -->
        <div class="md:col-span-2">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-900 mb-6">Informations personnelles</h3>
                <form @submit.prevent="updateProfile" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prénom</label>
                            <input v-model="profileForm.first_name" type="text" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                            <input v-model="profileForm.last_name" type="text" class="w-full px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type de compte</label>
                        <div class="flex space-x-4">
                            <label class="flex items-center space-x-2 cursor-pointer bg-gray-50 px-4 py-2 rounded-xl border border-gray-200 hover:bg-gray-100" :class="{'ring-2 ring-blue-500 border-blue-500': profileForm.type === 'individual'}">
                                <input type="radio" v-model="profileForm.type" value="individual" class="hidden">
                                <User class="w-4 h-4 text-gray-500" />
                                <span class="font-medium text-sm">Particulier</span>
                            </label>
                            <label class="flex items-center space-x-2 cursor-pointer bg-gray-50 px-4 py-2 rounded-xl border border-gray-200 hover:bg-gray-100" :class="{'ring-2 ring-blue-500 border-blue-500': profileForm.type === 'company'}">
                                <input type="radio" v-model="profileForm.type" value="company" class="hidden">
                                <Briefcase class="w-4 h-4 text-gray-500" />
                                <span class="font-medium text-sm">Entreprise</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                             <div class="relative">
                                <Phone class="w-4 h-4 text-gray-400 absolute left-3 top-3.5" />
                                <input v-model="profileForm.phone" type="text" class="w-full pl-10 pr-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                            <div class="relative">
                                <MapPin class="w-4 h-4 text-gray-400 absolute left-3 top-3.5" />
                                <input v-model="profileForm.address" type="text" class="w-full pl-10 pr-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button 
                            type="submit"
                            :disabled="saving"
                            class="bg-blue-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-blue-700 transition flex items-center space-x-2 disabled:opacity-50"
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
