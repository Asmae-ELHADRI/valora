<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../services/api';
import { ArrowLeft, Save, Trash, Loader2, MapPin, Calendar, Briefcase, Info, DollarSign, Clock } from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const isEditing = ref(false);
const loading = ref(false);
const saving = ref(false);
const categories = ref([]);

// Grouper les catégories par domaine
const categoryGroups = computed(() => {
    const groups = {
        'Construction & Rénovation': [
            'Plomberie', 'Électricité', 'Maçonnerie', 'Peinture & Décoration', 
            'Menuiserie', 'Carrelage', 'Chauffage & Climatisation', 'Isolation', 
            'Toiture & Couverture', 'Vitrerie'
        ],
        'Services à domicile': [
            'Ménage & Nettoyage', 'Jardinage & Paysagisme', 'Garde d\'enfants', 
            'Aide à domicile', 'Repassage', 'Cuisine à domicile'
        ],
        'Services professionnels': [
            'Informatique & Dépannage', 'Cours particuliers', 'Coaching & Formation', 
            'Traduction', 'Rédaction & Correction', 'Comptabilité', 'Conseil juridique', 
            'Marketing & Communication', 'Design graphique', 'Développement web'
        ],
        'Transport & Logistique': [
            'Déménagement', 'Transport de marchandises', 'Livraison', 'Coursier'
        ],
        'Événementiel': [
            'Traiteur', 'Photographie', 'Vidéographie', 'Animation', 
            'DJ & Musicien', 'Décoration événementielle'
        ],
        'Bien-être & Santé': [
            'Coiffure à domicile', 'Esthétique & Beauté', 'Massage', 
            'Fitness & Sport', 'Diététique'
        ],
        'Automobile': [
            'Mécanique auto', 'Carrosserie', 'Dépannage auto', 'Nettoyage auto'
        ],
        'Animaux': [
            'Toilettage', 'Garde d\'animaux', 'Promenade de chiens', 'Éducation canine'
        ]
    };

    const result = [];
    for (const [groupName, categoryNames] of Object.entries(groups)) {
        const groupCategories = categories.value.filter(cat => 
            categoryNames.includes(cat.name)
        );
        if (groupCategories.length > 0) {
            result.push({
                name: groupName,
                categories: groupCategories
            });
        }
    }
    return result;
});

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
    <div class="max-w-4xl mx-auto px-4 py-12">
        <button @click="$router.push('/dashboard-client')" class="flex items-center text-slate-400 hover:text-slate-900 mb-8 font-bold transition active:scale-95">
            <ArrowLeft class="w-5 h-5 mr-2" /> {{ $t('post_offer.back_dashboard') }}
        </button>

        <div class="bg-white rounded-[3rem] shadow-2xl shadow-slate-200/60 border border-gray-100 overflow-hidden relative">
            <div class="absolute top-0 left-0 w-full h-3 bg-linear-to-r from-premium-blue via-premium-brown to-premium-blue"></div>
            
            <div class="p-10 md:p-14 border-b border-gray-100 bg-slate-50/30">
                <div class="flex items-center space-x-4 mb-4">
                    <span class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center shadow-sm border border-gray-100">
                        <Briefcase v-if="!isEditing" class="w-7 h-7 text-premium-blue" />
                        <Save v-else class="w-7 h-7 text-premium-blue" />
                    </span>
                    <div>
                        <h1 class="text-3xl font-black text-premium-blue tracking-tight uppercase">{{ isEditing ? $t('post_offer.title_edit') : $t('post_offer.title_new') }}</h1>
                        <p class="text-premium-brown font-bold text-sm uppercase tracking-widest">{{ $t('post_offer.subtitle') }}</p>
                    </div>
                </div>
            </div>

            <div v-if="loading" class="p-24 flex justify-center">
                <Loader2 class="w-12 h-12 text-slate-900 animate-spin" />
            </div>

            <form v-else @submit.prevent="handleSubmit" class="p-10 md:p-14 space-y-10">
                <!-- Title Group -->
                <div class="bg-slate-50 p-8 rounded-4xl border border-gray-100/50">
                    <label class="flex items-center text-[10px] font-black text-premium-blue/40 uppercase tracking-[0.2em] mb-4 pl-2">
                        <Info class="w-4 h-4 mr-2" /> {{ $t('post_offer.mission_title') }}
                    </label>
                    <input 
                        v-model="offer.title" 
                        type="text" 
                        required
                        :placeholder="$t('post_offer.title_placeholder')"
                        class="w-full px-6 py-5 rounded-2xl border-none bg-white focus:ring-4 focus:ring-premium-yellow/20 outline-none transition-all font-bold text-2xl text-premium-blue placeholder:text-slate-300 shadow-sm"
                    >
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="flex items-center text-[10px] font-black text-premium-blue/40 uppercase tracking-[0.2em] mb-3 pl-2">
                            <Briefcase class="w-4 h-4 mr-2" /> {{ $t('post_offer.category_label') }}
                        </label>
                        <div class="relative group">
                            <select 
                                v-model="offer.category_id" 
                                required
                                class="w-full px-5 py-4 rounded-2xl border-2 border-slate-100 bg-white focus:border-premium-blue focus:ring-0 outline-none transition-all font-bold text-premium-blue appearance-none cursor-pointer"
                            >
                                <option value="" disabled>{{ $t('post_offer.select_category') }}</option>
                                <optgroup v-for="group in categoryGroups" :key="group.name" :label="group.name">
                                    <option v-for="cat in group.categories" :key="cat.id" :value="cat.id">
                                        {{ cat.name }}
                                    </option>
                                </optgroup>
                            </select>
                            <div class="absolute right-5 top-5 pointer-events-none text-slate-400 group-hover:text-premium-blue transition-colors">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="flex items-center text-[10px] font-black text-premium-blue/40 uppercase tracking-[0.2em] mb-3 pl-2">
                            <Info class="w-4 h-4 mr-2" /> {{ $t('post_offer.nature_label') }}
                        </label>
                        <input 
                            v-model="offer.nature_of_need" 
                            type="text" 
                            :placeholder="$t('post_offer.nature_placeholder')"
                            class="w-full px-5 py-4 rounded-2xl border-2 border-slate-100 bg-white focus:border-premium-blue focus:ring-0 outline-none transition-all font-bold text-premium-blue placeholder:text-slate-400"
                        >
                    </div>
                </div>

                <div>
                     <label class="flex items-center text-[10px] font-black text-premium-blue/40 uppercase tracking-[0.2em] mb-3 pl-2">
                        <Info class="w-4 h-4 mr-2" /> {{ $t('post_offer.desc_label') }}
                    </label>
                    <textarea 
                        v-model="offer.description" 
                        rows="5"
                        required
                        :placeholder="$t('post_offer.desc_placeholder')"
                        class="w-full px-6 py-5 rounded-3xl border-2 border-slate-100 bg-white focus:border-premium-blue focus:ring-0 outline-none transition-all font-medium text-premium-blue placeholder:text-slate-400 text-lg leading-relaxed"
                    ></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="flex items-center text-[10px] font-black text-premium-blue/40 uppercase tracking-[0.2em] mb-3 pl-2">
                            <Clock class="w-4 h-4 mr-2" /> {{ $t('post_offer.duration_label') }}
                        </label>
                        <input 
                            v-model="offer.estimated_duration" 
                            type="text" 
                            :placeholder="$t('post_offer.duration_placeholder')"
                            class="w-full px-5 py-4 rounded-2xl border-2 border-slate-100 bg-white focus:border-premium-blue focus:ring-0 outline-none transition-all font-bold text-premium-blue"
                        >
                    </div>

                    <div>
                        <label class="flex items-center text-[10px] font-black text-premium-blue/40 uppercase tracking-[0.2em] mb-3 pl-2">
                            <DollarSign class="w-4 h-4 mr-2" /> {{ $t('post_offer.budget_label') }}
                        </label>
                        <div class="relative">
                            <span class="absolute left-5 top-4 font-black text-premium-brown">€</span>
                            <input 
                                v-model="offer.budget" 
                                type="number" 
                                min="0"
                                required
                                placeholder="500"
                                class="w-full pl-10 pr-5 py-4 rounded-2xl border-2 border-slate-100 bg-white focus:border-premium-blue focus:ring-0 outline-none transition-all font-black text-premium-blue"
                            >
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="flex items-center text-[10px] font-black text-premium-blue/40 uppercase tracking-[0.2em] mb-3 pl-2">
                            <Briefcase class="w-4 h-4 mr-2" /> {{ $t('post_offer.material_label') }}
                        </label>
                        <textarea 
                            v-model="offer.material_required" 
                            rows="3"
                            :placeholder="$t('post_offer.material_placeholder')"
                            class="w-full px-5 py-4 rounded-2xl border-2 border-slate-100 bg-white focus:border-premium-blue focus:ring-0 outline-none transition-all font-medium text-premium-blue"
                        ></textarea>
                    </div>
                    <div>
                        <label class="flex items-center text-[10px] font-black text-premium-blue/40 uppercase tracking-[0.2em] mb-3 pl-2">
                            <Info class="w-4 h-4 mr-2" /> {{ $t('post_offer.req_label') }}
                        </label>
                        <textarea 
                            v-model="offer.requirements" 
                            rows="3"
                            :placeholder="$t('post_offer.req_placeholder')"
                            class="w-full px-5 py-4 rounded-2xl border-2 border-slate-100 bg-white focus:border-premium-blue focus:ring-0 outline-none transition-all font-medium text-premium-blue"
                        ></textarea>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                     <div>
                        <label class="flex items-center text-[10px] font-black text-premium-blue/40 uppercase tracking-[0.2em] mb-3 pl-2">
                            <MapPin class="w-4 h-4 mr-2" /> {{ $t('post_offer.location_label') }}
                        </label>
                        <input 
                            v-model="offer.location" 
                            type="text" 
                            required
                            :placeholder="$t('post_offer.location_placeholder')"
                            class="w-full px-5 py-4 rounded-2xl border-2 border-slate-100 bg-white focus:border-premium-blue focus:ring-0 outline-none transition-all font-bold text-premium-blue"
                        >
                    </div>

                    <div>
                        <label class="flex items-center text-[10px] font-black text-premium-blue/40 uppercase tracking-[0.2em] mb-3 pl-2">
                            <Calendar class="w-4 h-4 mr-2" /> {{ $t('post_offer.date_label') }}
                        </label>
                        <input 
                            v-model="offer.desired_date" 
                            type="date" 
                            required
                            class="w-full px-5 py-4 rounded-2xl border-2 border-slate-100 bg-white focus:border-premium-blue focus:ring-0 outline-none transition-all font-bold text-premium-blue uppercase"
                        >
                    </div>
                </div>

                <div class="flex items-center justify-between pt-12 border-t border-gray-100">
                    <button 
                        v-if="isEditing" 
                        type="button" 
                        @click="deleteOffer"
                        class="text-red-500 font-black hover:bg-red-50 px-6 py-4 rounded-2xl transition-all flex items-center text-xs uppercase tracking-widest active:scale-95"
                    >
                        <Trash class="w-5 h-5 mr-3" /> {{ $t('post_offer.delete_btn') }}
                    </button>
                    <div v-else></div>

                    <button 
                        type="submit" 
                        :disabled="saving"
                        class="bg-premium-yellow text-premium-blue px-12 py-5 rounded-2xl font-black hover:bg-yellow-400 shadow-[0_15px_30px_-5px_rgba(250,204,21,0.3)] hover:shadow-[0_20px_40px_-5px_rgba(250,204,21,0.4)] transition-all active:scale-[0.97] flex items-center space-x-3 disabled:opacity-50 text-xs uppercase tracking-[0.2em]"
                    >
                        <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
                        <Save v-else class="w-5 h-5" />
                        <span>{{ isEditing ? $t('post_offer.update_btn') : $t('post_offer.save_btn') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
