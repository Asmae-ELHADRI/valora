<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../services/api';
import { ArrowLeft, Save, Trash, Loader2, MapPin, Calendar, Briefcase, Info, DollarSign } from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const isEditing = ref(false);
const loading = ref(false);
const saving = ref(false);
const categories = ref([]);

const offer = ref({
    title: '',
    description: '',
    category_id: '',
    nature_of_need: '',
    requirements: '',
    estimated_duration: '',
    material_required: '',
    budget: '',
    location: '',
    desired_date: '',
    status: 'open'
});

const fetchCategories = async () => {
    try {
        const response = await api.get('/api/offers/categories');
        categories.value = response.data;
    } catch (err) {
        console.error('Erreur chargement catégories', err);
    }
};

const fetchOffer = async (id) => {
    loading.value = true;
    try {
        const response = await api.get(`/api/offers/${id}`);
        // Ensure date is formatted for input
        const d = new Date(response.data.desired_date);
        const dateStr = d.toISOString().split('T')[0];
        
        offer.value = {
            ...response.data,
            desired_date: dateStr
        };
    } catch (err) {
        console.error(err);
        alert('Erreur chargement offre');
        router.push('/dashboard-client');
    } finally {
        loading.value = false;
    }
};

const handleSubmit = async () => {
    saving.value = true;
    try {
        if (isEditing.value) {
            await api.put(`/api/offers/${route.params.id}`, offer.value);
            alert('Offre mise à jour');
        } else {
            await api.post('/api/offers', offer.value);
            alert('Offre publiée avec succès');
        }
        router.push('/dashboard-client');
    } catch (err) {
        console.error(err);
        alert(err.response?.data?.message || 'Erreur lors de l\'enregistrement');
    } finally {
        saving.value = false;
    }
};

const deleteOffer = async () => {
    if(!confirm('Supprimer cette offre ?')) return;
    try {
        await api.delete(`/api/offers/${route.params.id}`);
        router.push('/dashboard-client');
    } catch (err) {
        alert('Erreur suppression');
    }
};

onMounted(async () => {
    await fetchCategories();
    if (route.params.id) {
        isEditing.value = true;
        await fetchOffer(route.params.id);
    } else {
        // Set default date to today + 7
        const today = new Date();
        today.setDate(today.getDate() + 7);
        offer.value.desired_date = today.toISOString().split('T')[0];
    }
});
</script>

<template>
    <div class="max-w-3xl mx-auto px-4 py-8">
        <button @click="$router.push('/dashboard-client')" class="flex items-center text-gray-500 hover:text-gray-900 mb-6 font-medium transition">
            <ArrowLeft class="w-5 h-5 mr-2" /> Retour au tableau de bord
        </button>

        <div class="bg-white rounded-[40px] shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-10 border-b border-gray-50 bg-gray-50/50">
                <h1 class="text-3xl font-black text-gray-900">{{ isEditing ? 'Modifier l\'offre' : 'Publier une nouvelle offre' }}</h1>
                <p class="text-gray-500 mt-2">Détaillez votre besoin pour trouver le meilleur prestataire.</p>
            </div>

            <div v-if="loading" class="p-20 flex justify-center">
                <Loader2 class="w-10 h-10 text-blue-600 animate-spin" />
            </div>

            <form v-else @submit.prevent="handleSubmit" class="p-10 space-y-8">
                <div>
                    <label class="flex items-center text-sm font-bold text-gray-900 mb-2">
                        <Info class="w-4 h-4 mr-2 text-blue-600" /> Titre de la mission
                    </label>
                    <input 
                        v-model="offer.title" 
                        type="text" 
                        required
                        placeholder="Ex: Rénovation complète de salle de bain"
                        class="w-full px-5 py-3 rounded-2xl border border-gray-200 focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition font-medium text-lg"
                    >
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="flex items-center text-sm font-bold text-gray-900 mb-2">
                            <Briefcase class="w-4 h-4 mr-2 text-blue-600" /> Catégorie
                        </label>
                        <select 
                            v-model="offer.category_id" 
                            required
                            class="w-full px-5 py-3 rounded-2xl border border-gray-200 focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition bg-white"
                        >
                            <option value="" disabled>Choisir une catégorie</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="flex items-center text-sm font-bold text-gray-900 mb-2">
                            <Info class="w-4 h-4 mr-2 text-blue-600" /> Nature du besoin
                        </label>
                        <input 
                            v-model="offer.nature_of_need" 
                            type="text" 
                            placeholder="Ex: Urgence, Rénovation, Entretien..."
                            class="w-full px-5 py-3 rounded-2xl border border-gray-200 focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition"
                        >
                    </div>
                </div>

                <div>
                     <label class="flex items-center text-sm font-bold text-gray-900 mb-2">
                        <Info class="w-4 h-4 mr-2 text-blue-600" /> Description détaillée
                    </label>
                    <textarea 
                        v-model="offer.description" 
                        rows="4"
                        required
                        placeholder="Décrivez précisément ce que vous attendez..."
                        class="w-full px-5 py-3 rounded-2xl border border-gray-200 focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition"
                    ></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="flex items-center text-sm font-bold text-gray-900 mb-2">
                            <Clock class="w-4 h-4 mr-2 text-blue-600" /> Durée estimée
                        </label>
                        <input 
                            v-model="offer.estimated_duration" 
                            type="text" 
                            placeholder="Ex: 2 jours, 3 heures..."
                            class="w-full px-5 py-3 rounded-2xl border border-gray-200 focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition"
                        >
                    </div>

                    <div>
                        <label class="flex items-center text-sm font-bold text-gray-900 mb-2">
                            <DollarSign class="w-4 h-4 mr-2 text-blue-600" /> Budget estimé (€)
                        </label>
                        <input 
                            v-model="offer.budget" 
                            type="number" 
                            min="0"
                            required
                            placeholder="500"
                            class="w-full px-5 py-3 rounded-2xl border border-gray-200 focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition"
                        >
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="flex items-center text-sm font-bold text-gray-900 mb-2">
                            <Briefcase class="w-4 h-4 mr-2 text-blue-600" /> Matériel requis
                        </label>
                        <textarea 
                            v-model="offer.material_required" 
                            rows="3"
                            placeholder="Ex: Échelles, pinceaux, outillage spécifique..."
                            class="w-full px-5 py-3 rounded-2xl border border-gray-200 focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition"
                        ></textarea>
                    </div>
                    <div>
                        <label class="flex items-center text-sm font-bold text-gray-900 mb-2">
                            <Info class="w-4 h-4 mr-2 text-blue-600" /> Critères / Pré-requis
                        </label>
                        <textarea 
                            v-model="offer.requirements" 
                            rows="3"
                            placeholder="Ex: Expérience de 5 ans demandée, certificat spécifique..."
                            class="w-full px-5 py-3 rounded-2xl border border-gray-200 focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition"
                        ></textarea>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                     <div>
                        <label class="flex items-center text-sm font-bold text-gray-900 mb-2">
                            <MapPin class="w-4 h-4 mr-2 text-blue-600" /> Ville / Code Postal
                        </label>
                        <input 
                            v-model="offer.location" 
                            type="text" 
                            required
                            placeholder="Paris 75001"
                            class="w-full px-5 py-3 rounded-2xl border border-gray-200 focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition"
                        >
                    </div>

                    <div>
                        <label class="flex items-center text-sm font-bold text-gray-900 mb-2">
                            <Calendar class="w-4 h-4 mr-2 text-blue-600" /> Date souhaitée
                        </label>
                        <input 
                            v-model="offer.desired_date" 
                            type="date" 
                            required
                            class="w-full px-5 py-3 rounded-2xl border border-gray-200 focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition"
                        >
                    </div>
                </div>

                <div class="flex items-center justify-between pt-6 border-t border-gray-50">
                    <button 
                        v-if="isEditing" 
                        type="button" 
                        @click="deleteOffer"
                        class="text-red-500 font-bold hover:bg-red-50 px-4 py-2 rounded-xl transition flex items-center"
                    >
                        <Trash class="w-5 h-5 mr-2" /> Supprimer
                    </button>
                    <div v-else></div>

                    <button 
                        type="submit" 
                        :disabled="saving"
                        class="bg-blue-600 text-white px-8 py-4 rounded-2xl font-bold hover:bg-blue-700 shadow-xl shadow-blue-200 transition active:scale-[0.98] flex items-center space-x-2 disabled:opacity-50"
                    >
                        <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
                        <Save v-else class="w-5 h-5" />
                        <span>{{ isEditing ? 'Mettre à jour' : 'Publier l\'offre' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
