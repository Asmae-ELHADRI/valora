<script setup>
import { ref, onMounted } from 'vue';
import api from '../../services/api';
import { 
  Award, 
  Settings, 
  Plus, 
  Edit3, 
  Trash2, 
  CheckCircle2, 
  AlertOctagon,
  ShieldCheck,
  Zap,
  Star,
  X,
  User,
  MoreHorizontal,
  Search,
  Trophy,
  Crown,
  Sparkles
} from 'lucide-vue-next';

// --- State ---
const grades = ref([]);
const attributions = ref([]);
const loading = ref(true);
const showCreateModal = ref(false);
const editingGrade = ref(null);

const form = ref({
    name: '',
    slug: '',
    missions_threshold: 5,
    rating_threshold: 4.0,
    seniority_threshold_months: 1,
    color: 'text-amber-500',
    bg_color: 'bg-amber-500/10'
});

// --- Constants ---
// Special styling for specific grade names to ensure the "wow" effect on standard grades
const getGradeVisuals = (name) => {
    const lower = name.toLowerCase();
    if (lower.includes('or') || lower.includes('gold')) return {
        gradient: 'from-[#FFD700] via-[#FDB931] to-[#D4AF37]',
        text: 'text-amber-400',
        glow: 'shadow-amber-500/20',
        icon: Crown,
        border: 'border-amber-500/30'
    };
    if (lower.includes('argent') || lower.includes('silver')) return {
        gradient: 'from-[#E0E0E0] via-[#F5F5F5] to-[#BDBDBD]',
        text: 'text-slate-300',
        glow: 'shadow-slate-400/20',
        icon: ShieldCheck,
        border: 'border-slate-400/30'
    };
    if (lower.includes('bronze')) return {
        gradient: 'from-[#CD7F32] via-[#E6AA68] to-[#8B4513]',
        text: 'text-orange-400',
        glow: 'shadow-orange-500/20',
        icon: Award,
        border: 'border-orange-500/30'
    };
    return {
        gradient: 'from-blue-400 via-indigo-500 to-purple-600',
        text: 'text-indigo-400',
        glow: 'shadow-indigo-500/20',
        icon: Star,
        border: 'border-indigo-500/30'
    };
};

const colors = [
    { name: 'Amber', text: 'text-amber-400', bg: 'bg-amber-500/10' },
    { name: 'Slate', text: 'text-slate-400', bg: 'bg-slate-400/10' }, 
    { name: 'Blue', text: 'text-blue-400', bg: 'bg-blue-500/10' },
    { name: 'Emerald', text: 'text-emerald-400', bg: 'bg-emerald-500/10' },
    { name: 'Purple', text: 'text-purple-400', bg: 'bg-purple-500/10' },
    { name: 'Rose', text: 'text-rose-400', bg: 'bg-rose-500/10' },
];

// --- API ---
const fetchData = async () => {
    loading.value = true;
    try {
        const [gradesRes, attrRes] = await Promise.all([
            api.get('/api/admin/grades'),
            api.get('/api/admin/attributions')
        ]);
        grades.value = gradesRes.data;
        attributions.value = attrRes.data;
    } catch (error) {
        console.error('Error fetching data:', error);
    } finally {
        loading.value = false;
    }
};

const openModal = (grade = null) => {
    if (grade) {
        editingGrade.value = grade;
        form.value = { ...grade };
    } else {
        editingGrade.value = null;
        form.value = {
            name: '',
            slug: '',
            missions_threshold: 5,
            rating_threshold: 4.0,
            seniority_threshold_months: 1,
            color: 'text-slate-400',
            bg_color: 'bg-slate-400/10'
        };
    }
    showCreateModal.value = true;
};

const showAttributionModal = ref(false);
const selectedAttribution = ref(null);
const newGradeId = ref(null);

const saveGrade = async () => {
    try {
        form.value.slug = form.value.name.toLowerCase().replace(/\s+/g, '-');
        if (editingGrade.value) {
            await api.put(`/api/admin/grades/${editingGrade.value.id}`, form.value);
        } else {
            await api.post('/api/admin/grades', form.value);
        }
        showCreateModal.value = false;
        fetchData();
    } catch (error) {
        console.error('Error saving grade:', error);
    }
};

const deleteGrade = async (id) => {
    if(!confirm('Supprimer ce grade ?')) return;
    try {
        await api.delete(`/api/admin/grades/${id}`);
        fetchData();
    } catch (error) {
        console.error('Error deleting:', error);
    }
};

const revokeAttribution = async (id) => {
    if(!confirm('Êtes-vous sûr de vouloir révoquer ce grade ? L\'utilisateur perdra ce statut.')) return;
    try {
        await api.post('/api/admin/grades/revoke', { attribution_id: id });
        fetchData();
    } catch (error) {
        console.error('Error revoking attribution:', error);
    }
};

const openEditAttribution = (attr) => {
    selectedAttribution.value = attr;
    newGradeId.value = attr.grade_id;
    showAttributionModal.value = true;
};

const saveAttribution = async () => {
    if (!selectedAttribution.value || !newGradeId.value) return;
    try {
        await api.post('/api/admin/grades/assign', {
            user_id: selectedAttribution.value.user_id,
            grade_id: newGradeId.value
        });
        showAttributionModal.value = false;
        fetchData();
    } catch (error) {
        console.error('Error assigning grade:', error);
    }
};

onMounted(() => {
    fetchData();
});
</script>

<template>
    <div class="space-y-16 pb-20">
        
        <!-- Premium Header with Backdrop Glow -->
        <div class="relative">
             <div class="absolute -top-20 -left-20 w-96 h-96 bg-blue-500/10 dark:bg-blue-500/20 rounded-full blur-[100px] pointer-events-none"></div>
             <div class="absolute -top-20 -right-20 w-96 h-96 bg-amber-500/10 rounded-full blur-[100px] pointer-events-none"></div>

            <div class="flex items-end justify-between relative z-10">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 bg-gradient-to-br from-amber-400 to-orange-600 rounded-xl shadow-lg shadow-amber-500/20">
                            <Trophy class="w-6 h-6 text-white" />
                        </div>
                        <span class="text-xs font-bold text-amber-600 dark:text-amber-500 tracking-wider uppercase">Système de Récompense</span>
                    </div>
                    <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight">Grades & Gouvernance</h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-2 max-w-xl text-lg font-medium leading-relaxed">
                        Gérez les paliers d'excellence de la communauté Valora.
                        <br><span class="text-slate-400 dark:text-slate-500 text-sm">Définissez les règles d'attribution automatique et suivez l'élite.</span>
                    </p>
                </div>
                <button 
                    @click="openModal()"
                    class="group relative px-6 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black text-xs uppercase tracking-widest rounded-2xl shadow-xl hover:scale-105 transition-all duration-300 overflow-hidden"
                >
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                    <div class="flex items-center gap-3 relative z-10">
                        <Plus class="w-4 h-4" />
                        <span>Nouveau Grade</span>
                    </div>
                </button>
            </div>
        </div>

        <!-- Grade Cards Showcase -->
        <section>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div 
                    v-for="grade in grades" 
                    :key="grade.id"
                    class="group relative h-full"
                >
                    <!-- Floating Edit Button -->
                    <button 
                        @click="openModal(grade)"
                        class="absolute top-4 right-4 z-20 p-2 text-slate-400 hover:text-slate-900 dark:text-slate-500 dark:hover:text-white bg-white/50 dark:bg-black/20 hover:bg-white dark:hover:bg-black/40 backdrop-blur-md rounded-xl transition-all opacity-0 group-hover:opacity-100 scale-90 group-hover:scale-100"
                    >
                        <Edit3 class="w-4 h-4" />
                    </button>

                    <!-- Card Body -->
                    <div 
                        class="relative h-full bg-white dark:bg-[#0F172A] rounded-[40px] p-1 overflow-hidden transition-transform duration-500 hover:-translate-y-2 hover:shadow-2xl shadow-lg border border-slate-100 dark:border-none"
                        :class="`hover:${getGradeVisuals(grade.name).glow}`"
                    >
                        <!-- Animated Border Gradient -->
                        <div class="absolute inset-0 opacity-20 group-hover:opacity-100 transition-opacity duration-500 bg-gradient-to-b from-slate-200 dark:from-white/10 to-transparent" :class="getGradeVisuals(grade.name).gradient"></div>
                        
                        <!-- Inner Card -->
                        <div class="relative h-full bg-white/90 dark:bg-[#0F172A]/90 backdrop-blur-xl rounded-[38px] p-8 border border-slate-100 dark:border-white/5 group-hover:border-slate-200 dark:group-hover:border-white/10 transition-colors flex flex-col items-center text-center">
                            
                            <!-- Icon / Badge -->
                            <div class="mb-6 relative">
                                <div class="absolute inset-0 blur-2xl opacity-20" :class="`bg-gradient-to-r ${getGradeVisuals(grade.name).gradient}`"></div>
                                <component :is="getGradeVisuals(grade.name).icon" class="w-16 h-16 relative z-10 drop-shadow-lg" :class="getGradeVisuals(grade.name).text" stroke-width="1.5" />
                                <div class="absolute -bottom-2 -right-2 bg-slate-100 dark:bg-slate-800 rounded-full p-1 border border-slate-200 dark:border-slate-700">
                                    <Sparkles class="w-4 h-4 text-slate-900 dark:text-white" />
                                </div>
                            </div>

                            <!-- Title -->
                            <h3 class="text-3xl font-black bg-clip-text text-transparent bg-gradient-to-br mb-2" :class="getGradeVisuals(grade.name).gradient">
                                {{ grade.name }}
                            </h3>

                            <div class="w-12 h-1 rounded-full opacity-30 mb-8" :class="`bg-gradient-to-r ${getGradeVisuals(grade.name).gradient}`"></div>

                            <!-- Metrics Grid -->
                            <div class="grid grid-cols-3 gap-3 w-full mt-auto">
                                <div class="bg-slate-50 dark:bg-white/5 rounded-2xl p-3 border border-slate-100 dark:border-white/5 hover:bg-slate-100 dark:hover:bg-white/10 transition-colors group/metric">
                                    <p class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1 group-hover/metric:text-slate-600 dark:group-hover/metric:text-slate-300 transition-colors">Missions</p>
                                    <p class="text-xl font-bold text-slate-900 dark:text-white">{{ grade.missions_threshold }}+</p>
                                </div>
                                <div class="bg-slate-50 dark:bg-white/5 rounded-2xl p-3 border border-slate-100 dark:border-white/5 hover:bg-slate-100 dark:hover:bg-white/10 transition-colors group/metric">
                                    <p class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1 group-hover/metric:text-slate-600 dark:group-hover/metric:text-slate-300 transition-colors">Note</p>
                                    <p class="text-xl font-bold text-slate-900 dark:text-white">{{ grade.rating_threshold }}</p>
                                </div>
                                <div class="bg-slate-50 dark:bg-white/5 rounded-2xl p-3 border border-slate-100 dark:border-white/5 hover:bg-slate-100 dark:hover:bg-white/10 transition-colors group/metric">
                                    <p class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1 group-hover/metric:text-slate-600 dark:group-hover/metric:text-slate-300 transition-colors">Ancienneté</p>
                                    <p class="text-xl font-bold text-slate-900 dark:text-white">{{ grade.seniority_threshold_months }}m</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                 <!-- Empty State with Wow effect -->
                 <div v-if="!loading && grades.length === 0" class="col-span-3 py-24 text-center border border-dashed border-slate-300 dark:border-slate-700 rounded-[40px] bg-slate-50/50 dark:bg-slate-900/50 backdrop-blur-sm relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-500/0 via-blue-500/5 to-blue-500/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                    <Award class="w-16 h-16 text-slate-400 dark:text-slate-600 mx-auto mb-4" />
                    <p class="text-slate-500 dark:text-slate-400 font-bold text-lg">Initialisez le système de gouvernance</p>
                    <p class="text-slate-400 dark:text-slate-600 text-sm mt-1">Créez votre premier grade pour commencer.</p>
                 </div>
            </div>
        </section>


    <!-- Attributions Feed (Premium List) -->
        <section class="max-w-5xl mx-auto">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                    <Zap class="w-6 h-6 text-yellow-500 fill-yellow-500" />
                    Attributions Récentes
                </h2>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Live Feed</span>
                </div>
            </div>

            <div class="space-y-4">
                <div 
                    v-for="(attr, index) in attributions" 
                    :key="attr.id"
                    class="group relative bg-white dark:bg-[#151F32] hover:bg-slate-50 dark:hover:bg-[#1E293B] border border-slate-200 dark:border-slate-800 rounded-[24px] p-5 transition-all duration-300 shadow-sm hover:shadow-md"
                    :style="`transition-delay: ${index * 50}ms`"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-5">
                            <!-- User Avatar with Grade Border -->
                            <div class="relative">
                                <div class="w-14 h-14 rounded-2xl p-0.5 bg-gradient-to-br" :class="getGradeVisuals(attr.grade).gradient">
                                    <div class="w-full h-full rounded-[14px] bg-white dark:bg-[#0F172A] flex items-center justify-center overflow-hidden">
                                         <img v-if="attr.user_photo" :src="attr.user_photo" class="w-full h-full object-cover">
                                         <span v-else class="text-lg font-bold text-slate-700 dark:text-white">{{ attr.user.charAt(0) }}</span>
                                    </div>
                                </div>
                                <div class="absolute -bottom-2 -right-2 bg-white dark:bg-[#0F172A] rounded-full p-1 border border-slate-100 dark:border-slate-800">
                                    <component :is="getGradeVisuals(attr.grade).icon" class="w-3 h-3" :class="getGradeVisuals(attr.grade).text" />
                                </div>
                            </div>

                            <div>
                                <h4 class="text-slate-900 dark:text-white font-bold text-lg leading-tight">{{ attr.user }}</h4>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/5 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                        {{ attr.type }}
                                    </span>
                                    <span class="text-slate-400 dark:text-slate-600 text-xs">•</span>
                                    <span class="text-slate-500 dark:text-slate-500 text-xs font-medium">{{ attr.date }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Right Actions -->
                        <div class="flex items-center gap-6">
                            <div class="text-right hidden sm:block">
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Grade Obtenu</p>
                                <div class="flex items-center justify-end gap-2">
                                     <component :is="getGradeVisuals(attr.grade).icon" class="w-4 h-4" :class="getGradeVisuals(attr.grade).text" />
                                     <span class="text-sm font-bold bg-clip-text text-transparent bg-gradient-to-r" :class="getGradeVisuals(attr.grade).gradient">
                                        {{ attr.grade }}
                                     </span>
                                </div>
                            </div>
                            
                            <div class="h-10 w-px bg-slate-100 dark:bg-white/5 mx-2 hidden sm:block"></div>

                            <button 
                                @click="openEditAttribution(attr)"
                                class="px-5 py-2.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-bold text-xs uppercase tracking-wider rounded-xl hover:bg-slate-800 dark:hover:bg-slate-200 transition-colors shadow-lg shadow-white/5 flex items-center gap-2"
                            >
                                <Edit3 class="w-3.5 h-3.5" />
                                Modifier
                            </button>
                            
                            <button 
                                @click="revokeAttribution(attr.id)"
                                class="p-2.5 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-xl transition-colors"
                                title="Révoquer l'attribution"
                            >
                                <Trash2 class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Edit/Create Grade Modal (Standardized Glass) -->
        <div v-if="showCreateModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40 dark:bg-[#000000]/80 backdrop-blur-md transition-opacity" @click="showCreateModal = false"></div>
            <div class="relative bg-white dark:bg-[#0F172A] w-full max-w-lg rounded-[40px] shadow-2xl overflow-hidden border border-slate-200 dark:border-white/10">
                
                <!-- Modal Header -->
                <div class="px-8 py-8 border-b border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-white/5">
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white">
                        {{ editingGrade ? 'Modifier le Grade' : 'Nouveau Grade' }}
                    </h3>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Définissez les critères d'excellence.</p>
                </div>
                
                <div class="p-8 space-y-6">
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Nom du Grade</label>
                        <input v-model="form.name" type="text" placeholder="Ex: Elite" class="w-full bg-slate-50 dark:bg-[#1E293B] border border-slate-200 dark:border-white/5 rounded-2xl px-5 py-4 font-bold text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-amber-500/50 transition-all">
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Missions</label>
                            <input v-model="form.missions_threshold" type="number" class="w-full bg-slate-50 dark:bg-[#1E293B] border border-slate-200 dark:border-white/5 rounded-2xl px-4 py-4 font-bold text-slate-900 dark:text-white text-center focus:outline-none focus:ring-2 focus:ring-amber-500/50 transition-all">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Note Min.</label>
                            <input v-model="form.rating_threshold" type="number" step="0.1" class="w-full bg-slate-50 dark:bg-[#1E293B] border border-slate-200 dark:border-white/5 rounded-2xl px-4 py-4 font-bold text-slate-900 dark:text-white text-center focus:outline-none focus:ring-2 focus:ring-amber-500/50 transition-all">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Ancienneté</label>
                            <input v-model="form.seniority_threshold_months" type="number" class="w-full bg-slate-50 dark:bg-[#1E293B] border border-slate-200 dark:border-white/5 rounded-2xl px-4 py-4 font-bold text-slate-900 dark:text-white text-center focus:outline-none focus:ring-2 focus:ring-amber-500/50 transition-all">
                        </div>
                    </div>

                    <!-- Color Picker -->
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-3">Couleur Signature</label>
                        <div class="flex gap-3">
                            <button 
                                v-for="c in colors" 
                                :key="c.name"
                                @click="form.color = c.text; form.bg_color = c.bg"
                                class="w-12 h-12 rounded-full border-2 flex items-center justify-center transition-all bg-slate-50 dark:bg-[#1E293B]"
                                :class="form.color === c.text ? 'border-slate-900 dark:border-white scale-110 shadow-lg' : 'border-transparent opacity-50 hover:opacity-100'"
                            >
                                <Star class="w-5 h-5" :class="c.text" fill="currentColor" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="px-8 pb-8 flex gap-4">
                    <button @click="showCreateModal = false" class="flex-1 py-4 rounded-2xl font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-white/5 transition-colors">Annuler</button>
                    <button @click="saveGrade" class="flex-1 py-4 rounded-2xl bg-amber-500 hover:bg-amber-400 text-black font-black uppercase tracking-wider shadow-lg shadow-amber-500/20 transition-transform active:scale-95">
                        {{ editingGrade ? 'Enregistrer' : 'Créer' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Edit Attribution Modal -->
        <div v-if="showAttributionModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40 dark:bg-[#000000]/80 backdrop-blur-md transition-opacity" @click="showAttributionModal = false"></div>
            <div class="relative bg-white dark:bg-[#0F172A] w-full max-w-md rounded-[32px] shadow-2xl overflow-hidden border border-slate-200 dark:border-white/10">
                
                <div class="px-8 py-8 border-b border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-white/5">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white">Modifier l'Attribution</h3>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Changez le grade de {{ selectedAttribution?.user }}</p>
                </div>
                
                <div class="p-8 space-y-4">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500">Nouveau Grade</label>
                        <div class="grid grid-cols-1 gap-2">
                            <button 
                                v-for="grade in grades" 
                                :key="grade.id"
                                @click="newGradeId = grade.id"
                                class="flex items-center gap-3 p-3 rounded-xl border transition-all"
                                :class="newGradeId === grade.id ? 'bg-amber-50 border-amber-500 ring-1 ring-amber-500' : 'bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 hover:border-slate-300'"
                            >
                                <component :is="getGradeVisuals(grade.name).icon" class="w-5 h-5" :class="getGradeVisuals(grade.name).text" />
                                <span class="font-bold text-sm" :class="newGradeId === grade.id ? 'text-slate-900' : 'text-slate-600 dark:text-slate-400'">{{ grade.name }}</span>
                                <CheckCircle2 v-if="newGradeId === grade.id" class="ml-auto w-4 h-4 text-amber-500" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="px-8 pb-8 flex gap-4">
                    <button @click="showAttributionModal = false" class="flex-1 py-4 rounded-xl font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-white/5 transition-colors">Annuler</button>
                    <button @click="saveAttribution" class="flex-1 py-4 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black uppercase tracking-wider shadow-lg transition-transform active:scale-95">
                        Confirmer
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
