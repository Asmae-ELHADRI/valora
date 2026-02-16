<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../services/api';
import { 
    ArrowLeft, 
    Save, 
    Trash, 
    Loader2, 
    MapPin, 
    Calendar, 
    Briefcase, 
    Info, 
    DollarSign, 
    Clock,
    Tag,
    FileText,
    LayoutGrid,
    CheckCircle2,
    Plus,
    X,
    Shield
} from 'lucide-vue-next';

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
    const usedIds = new Set();

    for (const [groupName, categoryNames] of Object.entries(groups)) {
        const groupCategories = categories.value.filter(cat => 
            categoryNames.includes(cat.name)
        );
        if (groupCategories.length > 0) {
            result.push({
                name: groupName,
                categories: groupCategories
            });
            groupCategories.forEach(cat => usedIds.add(cat.id));
        }
    }

    // Ajouter les catégories non classées dans "Autres"
    const otherCategories = categories.value.filter(cat => !usedIds.has(cat.id));
    if (otherCategories.length > 0) {
        result.push({
            name: 'Autres',
            categories: otherCategories
        });
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
            // alert('Offre mise à jour'); // Cleaner than standard alert
        } else {
            await api.post('/api/offers', offer.value);
            // alert('Offre publiée avec succès');
        }
        router.push('/dashboard-client');
    } catch (err) {
        console.error(err);
        // Toast logic should ideally be here if available
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
    <div class="min-h-screen bg-slate-50/50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto">
            <!-- Navigation -->
            <button 
                @click="$router.push('/dashboard-client')" 
                class="group flex items-center text-slate-400 hover:text-premium-blue mb-10 transition-all duration-300 active:scale-95"
            >
                <div class="p-2 mr-3 rounded-xl bg-white shadow-sm border border-slate-100 group-hover:border-premium-blue/30 group-hover:text-premium-blue transition-all">
                    <ArrowLeft class="w-5 h-5" />
                </div>
                <span class="font-black text-xs uppercase tracking-widest">{{ $t('post_offer.back_dashboard') }}</span>
            </button>

            <!-- Main Header Card -->
            <div class="mb-8 relative overflow-hidden rounded-[2.5rem] bg-premium-blue p-8 md:p-12 text-white shadow-2xl shadow-premium-blue/20 animate-in fade-in slide-in-from-top-4 duration-700">
                <!-- Decorative Elements -->
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-premium-yellow/20 rounded-full blur-3xl"></div>
                
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <span class="px-3 py-1 bg-white/10 backdrop-blur-md rounded-full text-[10px] font-black uppercase tracking-widest border border-white/20">
                                {{ isEditing ? 'Mode Édition' : 'Nouvelle Mission' }}
                            </span>
                        </div>
                        <h1 class="text-3xl md:text-5xl font-black tracking-tight mb-2">
                            {{ isEditing ? $t('post_offer.title_edit') : $t('post_offer.title_new') }}
                        </h1>
                        <p class="text-blue-100/70 font-medium max-w-lg">
                            Détaillez votre besoin pour trouver le prestataire idéal sur Valorra.
                        </p>
                    </div>
                    
                    <div class="hidden md:flex items-center gap-4">
                        <div class="text-right">
                            <p class="text-premium-yellow font-black text-sm uppercase tracking-wider mb-1">Garantie Valorra</p>
                            <p class="text-[10px] text-blue-100/50 font-bold uppercase tracking-widest leading-tight">Paiement sécurisé<br>Prestataires vérifiés</p>
                        </div>
                        <div class="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center">
                            <Shield class="w-8 h-8 text-premium-yellow" />
                        </div>
                    </div>
                </div>
            </div>

            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Section 1: Basic Info -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Essential Card -->
                        <div class="bg-white rounded-4xl shadow-sm hover:shadow-xl hover:shadow-slate-200/40 transition-all duration-500 animate-in fade-in slide-in-from-left-4 delay-100">
                            <div class="flex items-center gap-3 mb-8">
                                <div class="p-3 rounded-2xl bg-blue-50 text-premium-blue">
                                    <FileText class="w-6 h-6" />
                                </div>
                                <h2 class="text-xl font-black text-premium-blue uppercase tracking-tight">Informations de base</h2>
                            </div>

                            <div class="space-y-6">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-2">
                                        {{ $t('post_offer.mission_title') }}
                                    </label>
                                    <input 
                                        v-model="offer.title" 
                                        type="text" 
                                        required
                                        :placeholder="$t('post_offer.title_placeholder')"
                                        class="premium-input text-xl"
                                    >
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-2">
                                            {{ $t('post_offer.category_label') }}
                                        </label>
                                        <div class="relative group">
                                            <select 
                                                v-model="offer.category_id" 
                                                required
                                                class="premium-select"
                                            >
                                                <option value="" disabled>{{ $t('post_offer.select_category') }}</option>
                                                <optgroup v-for="group in categoryGroups" :key="group.name" :label="group.name">
                                                    <option v-for="cat in group.categories" :key="cat.id" :value="cat.id">
                                                        {{ cat.name }}
                                                    </option>
                                                </optgroup>
                                            </select>
                                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400 transition-colors">
                                                <LayoutGrid class="w-4 h-4" />
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-2">
                                            {{ $t('post_offer.nature_label') }}
                                        </label>
                                        <input 
                                            v-model="offer.nature_of_need" 
                                            type="text" 
                                            :placeholder="$t('post_offer.nature_placeholder')"
                                            class="premium-input"
                                        >
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-2">
                                        {{ $t('post_offer.desc_label') }}
                                    </label>
                                    <textarea 
                                        v-model="offer.description" 
                                        rows="6"
                                        required
                                        :placeholder="$t('post_offer.desc_placeholder')"
                                        class="premium-textarea"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Technical Card -->
                        <div class="bg-white rounded-4xl shadow-sm hover:shadow-xl hover:shadow-slate-200/40 transition-all duration-500 animate-in fade-in slide-in-from-left-4 delay-200">
                            <div class="flex items-center gap-3 mb-8">
                                <div class="p-3 rounded-2xl bg-amber-50 text-premium-brown">
                                    <Briefcase class="w-6 h-6" />
                                </div>
                                <h2 class="text-xl font-black text-premium-blue uppercase tracking-tight">Aspects Techniques</h2>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-2">
                                        Matériel Requis
                                    </label>
                                    <textarea 
                                        v-model="offer.material_required" 
                                        rows="4"
                                        placeholder="Ex: Échelle, outillage spécifique..."
                                        class="premium-textarea"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-2">
                                        Exigences Particulières
                                    </label>
                                    <textarea 
                                        v-model="offer.requirements" 
                                        rows="4"
                                        placeholder="Ex: Diplômes, années d'expérience..."
                                        class="premium-textarea"
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar Cards -->
                    <div class="space-y-6">
                        <!-- Budget & Timing Card -->
                        <div class="bg-white rounded-4xl border border-slate-200/50 p-8 shadow-sm hover:shadow-xl hover:shadow-slate-200/40 transition-all duration-500 animate-in fade-in slide-in-from-right-4 delay-150">
                            <div class="flex items-center gap-3 mb-8 text-premium-blue">
                                <div class="p-3 rounded-2xl bg-slate-900 text-white">
                                    <Tag class="w-6 h-6" />
                                </div>
                                <h2 class="text-lg font-black uppercase tracking-tight">Planning & Prix</h2>
                            </div>

                            <div class="space-y-6">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-2">
                                        {{ $t('post_offer.budget_label') }}
                                    </label>
                                    <div class="relative">
                                        <div class="absolute left-5 top-1/2 -translate-y-1/2 font-black text-premium-brown">€</div>
                                        <input 
                                            v-model="offer.budget" 
                                            type="number" 
                                            min="0"
                                            required
                                            placeholder="0.00"
                                            class="premium-input pl-12 font-black"
                                        >
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-2">
                                        {{ $t('post_offer.date_label') }}
                                    </label>
                                    <div class="relative">
                                        <input 
                                            v-model="offer.desired_date" 
                                            type="date" 
                                            required
                                            class="premium-input uppercase"
                                        >
                                        <Calendar class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-2">
                                        {{ $t('post_offer.duration_label') }}
                                    </label>
                                    <div class="relative">
                                        <input 
                                            v-model="offer.estimated_duration" 
                                            type="text" 
                                            placeholder="Ex: 2 jours"
                                            class="premium-input pl-11"
                                        >
                                        <Clock class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Location Card -->
                        <div class="bg-white rounded-4xl border border-slate-200/50 p-8 shadow-sm hover:shadow-xl hover:shadow-slate-200/40 transition-all duration-500 animate-in fade-in slide-in-from-right-4 delay-250">
                            <div class="flex items-center gap-3 mb-8 text-premium-blue">
                                <div class="p-3 rounded-2xl bg-slate-900 text-white">
                                    <MapPin class="w-6 h-6" />
                                </div>
                                <h2 class="text-lg font-black uppercase tracking-tight">Localisation</h2>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-2">
                                    {{ $t('post_offer.location_label') }}
                                </label>
                                <input 
                                    v-model="offer.location" 
                                    type="text" 
                                    required
                                    placeholder="Ville ou adresse"
                                    class="premium-input pr-10"
                                >
                            </div>
                            
                            <div class="mt-4 p-4 bg-slate-50 rounded-2xl border border-slate-100 flex items-center gap-3">
                                <Info class="w-4 h-4 text-premium-blue shrink-0" />
                                <p class="text-[10px] text-slate-500 font-bold uppercase leading-tight">L'adresse exacte sera partagée uniquement après acceptation.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-6 pt-10 px-8 animate-in fade-in slide-in-from-bottom-4 delay-300">
                    <button 
                        v-if="isEditing" 
                        type="button" 
                        @click="deleteOffer"
                        class="flex items-center gap-2 text-red-500 font-black text-[10px] uppercase tracking-[0.2em] hover:bg-red-50 px-6 py-4 rounded-2xl transition-all group overflow-hidden"
                    >
                        <Trash class="w-4 h-4 group-hover:shake" />
                        <span>Supprimer la mission</span>
                    </button>
                    <div v-else></div>

                    <button 
                        type="submit" 
                        :disabled="saving"
                        class="w-full sm:w-auto min-w-[280px] bg-premium-yellow text-premium-blue px-10 py-5 rounded-2xl font-black text-[12px] uppercase tracking-[0.3em] shadow-[0_15px_30px_-10px_rgba(250,204,21,0.5)] hover:shadow-[0_20px_40px_-5px_rgba(250,204,21,0.6)] hover:-translate-y-1 transition-all duration-300 active:scale-95 disabled:opacity-50 disabled:translate-y-0 flex items-center justify-center gap-3"
                    >
                        <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
                        <template v-else>
                            <Save class="w-5 h-5" />
                            <span>{{ isEditing ? 'Mettre à jour la mission' : 'Publier la mission' }}</span>
                        </template>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.premium-input {
    width: 100%;
    padding: 1rem 1.25rem;
    border-radius: 1.25rem;
    border: 2px solid #F1F5F9;
    background-color: #F8FAFC;
    color: #1e293b;
    font-weight: 700;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    outline: none;
}

.premium-input:focus {
    border-color: #2563eb;
    background-color: #ffffff;
    box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.1);
}

.premium-select {
    width: 100%;
    padding: 1rem 1.25rem;
    padding-right: 3rem;
    border-radius: 1.25rem;
    border: 2px solid #F1F5F9;
    background-color: #F8FAFC;
    color: #1e293b;
    font-weight: 700;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    outline: none;
    appearance: none;
}

.premium-select:focus {
    border-color: #2563eb;
    background-color: #ffffff;
}

.premium-textarea {
    width: 100%;
    padding: 1.25rem;
    border-radius: 1.5rem;
    border: 2px solid #F1F5F9;
    background-color: #F8FAFC;
    color: #1e293b;
    font-weight: 500;
    line-height: 1.6;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    outline: none;
    resize: none;
}

.premium-textarea:focus {
    border-color: #2563eb;
    background-color: #ffffff;
    box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.1);
}

.shadow-premium {
    box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.05);
}

@keyframes shake {
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(-10deg); }
    75% { transform: rotate(10deg); }
}

.group:hover .shake {
    animation: shake 0.3s ease-in-out infinite;
}

/* Custom Scrollbar for form if needed */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.2);
    border-radius: 10px;
}

::placeholder {
    color: #cbd5e1;
    opacity: 1;
}
</style>
