<script setup>
import { ref, onMounted, watch } from 'vue';
import api from '../../services/api';
import { 
  ShieldAlert, 
  AlertTriangle, 
  User, 
  Search, 
  CheckCircle, 
  XCircle, 
  Paperclip,
  ArrowRight,
  Clock,
  Ban,
  Filter,
  MoreVertical,
  ChevronDown
} from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

// --- State ---
const reports = ref([]);
const selectedReport = ref(null);
const loading = ref(false);
const loadingMore = ref(false);
const page = ref(1);
const hasMore = ref(true);
const searchQuery = ref('');
const activeFilter = ref('all'); // all, Haute, Moyenne, Basse

// --- Constants ---
const filters = [
  { label: 'Tous', value: 'all' },
  { label: 'Haute', value: 'high', color: 'bg-red-500' },
  { label: 'Moyenne', value: 'medium', color: 'bg-amber-500' },
  { label: 'Basse', value: 'low', color: 'bg-blue-500' },
];

const priorityConfig = {
    'high': { color: 'text-red-500', bg: 'bg-red-50 dark:bg-red-500/10', border: 'border-red-100 dark:border-red-500/20', icon: ShieldAlert },
    'medium': { color: 'text-amber-500', bg: 'bg-amber-50 dark:bg-amber-500/10', border: 'border-amber-100 dark:border-amber-500/20', icon: AlertTriangle },
    'low': { color: 'text-blue-500', bg: 'bg-blue-50 dark:bg-blue-500/10', border: 'border-blue-100 dark:border-blue-500/20', icon: CheckCircle }, // Using CheckCircle as generic info icon
};

// --- Actions ---
const fetchReports = async (reset = false) => {
    if (reset) {
        page.value = 1;
        reports.value = [];
        hasMore.value = true;
        loading.value = true;
    } else {
        loadingMore.value = true;
    }

    try {
        const response = await api.get('/api/admin/complaints', {
            params: {
                page: page.value,
                search: searchQuery.value,
                priority: activeFilter.value === 'all' ? null : activeFilter.value,
                per_page: 8
            }
        });

        const newReports = response.data.data;
        reports.value = [...reports.value, ...newReports];
        hasMore.value = response.data.next_page_url !== null;
        
        // Auto-select first report on initial load if none selected
        if (reset && newReports.length > 0 && !selectedReport.value) {
            selectedReport.value = newReports[0];
        } else if (reports.value.length === 0) {
            selectedReport.value = null;
        }

    } catch (error) {
        console.error('Error loading reports:', error);
    } finally {
        loading.value = false;
        loadingMore.value = false;
    }
};

const handleLoadMore = () => {
    if (hasMore.value && !loadingMore.value) {
        page.value++;
        fetchReports();
    }
};

const handleAction = async (action) => {
    if (!selectedReport.value) return;
    
    // In a real app, use a nice Modal here. For V2 MVP, we use native confirm but style the result.
    if (!confirm(`Confirmer l'action : ${action.toUpperCase()} ?`)) return;

    try {
        await api.post(`/api/admin/complaints/${selectedReport.value.id}/action`, { action });
        
        // Optimistic update
        reports.value = reports.value.filter(r => r.id !== selectedReport.value.id);
        
        if (reports.value.length > 0) {
            selectedReport.value = reports.value[0];
        } else {
            selectedReport.value = null;
            fetchReports(true); // Refresh if empty
        }

    } catch (error) {
        console.error('Action failed:', error);
        alert('Erreur lors de l\'action.');
    }
};

// --- Watchers ---
let searchTimeout;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => fetchReports(true), 300);
});

watch(activeFilter, () => {
    fetchReports(true);
});

// --- Formatting ---
const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString(undefined, { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
};

onMounted(() => {
    fetchReports(true);
});
</script>

<template>
    <div class="h-[calc(100vh-8rem)] flex flex-col gap-6">
        
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 flex-shrink-0">
            <div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Signalements</h1>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mt-1">Gérez les alertes et la sécurité de la plateforme.</p>
            </div>
            
            <div class="flex items-center gap-3">
                 <!-- Search -->
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <Search class="h-4 w-4 text-slate-400 group-focus-within:text-blue-500 transition-colors" />
                    </div>
                    <input 
                        v-model="searchQuery" 
                        type="text" 
                        placeholder="Rechercher un dossier..." 
                        class="pl-10 pr-4 py-2.5 bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-white/10 rounded-xl text-sm font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 focus:outline-none transition-all w-64 shadow-sm"
                    >
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex gap-2 overflow-x-auto pb-2 no-scrollbar flex-shrink-0">
             <button 
                v-for="filter in filters"
                :key="filter.value"
                @click="activeFilter = filter.value"
                class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-300 border flex items-center gap-2"
                :class="activeFilter === filter.value 
                    ? 'bg-slate-900 dark:bg-white text-white dark:text-slate-900 border-slate-900 dark:border-white shadow-lg transform scale-105' 
                    : 'bg-white dark:bg-[#1E293B] text-slate-500 dark:text-slate-400 border-slate-200 dark:border-white/5 hover:border-slate-300 dark:hover:border-white/10 hover:bg-slate-50 dark:hover:bg-white/5'"
             >
                <span v-if="filter.color" class="w-2 h-2 rounded-full" :class="filter.color"></span>
                {{ filter.label }}
             </button>
        </div>

        <!-- Main Content (2 Columns) -->
        <div class="flex-1 flex gap-8 overflow-hidden">
            
            <!-- LEFT: List -->
            <div class="w-full lg:w-[400px] xl:w-[450px] flex flex-col gap-4 overflow-y-auto pr-2 custom-scroll pb-20 relative">
                
                <!-- Loading State -->
                <div v-if="loading" class="space-y-4">
                    <div v-for="i in 4" :key="i" class="h-32 bg-white dark:bg-[#1E293B] rounded-[24px] animate-pulse"></div>
                </div>

                <!-- Empty State -->
                <div v-else-if="reports.length === 0" class="flex flex-col items-center justify-center py-20 text-center">
                    <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-[#1E293B] flex items-center justify-center mb-4">
                        <CheckCircle class="w-8 h-8 text-slate-400" />
                    </div>
                    <p class="text-slate-900 dark:text-white font-bold">Aucun signalement</p>
                    <p class="text-xs text-slate-500 mt-1">Tout semble calme pour le moment.</p>
                </div>

                <!-- Report Cards -->
                <div 
                    v-for="report in reports" 
                    :key="report.id"
                    @click="selectedReport = report"
                    class="group relative p-5 bg-white dark:bg-[#1E293B] rounded-[24px] border transition-all duration-300 cursor-pointer hover:scale-[1.02]"
                    :class="selectedReport?.id === report.id 
                        ? 'border-blue-500 ring-4 ring-blue-500/10 shadow-xl shadow-blue-500/10 z-10' 
                        : 'border-slate-100 dark:border-white/5 shadow-sm hover:shadow-md hover:border-slate-300 dark:hover:border-white/20'"
                >
                    <!-- Status Line -->
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center gap-2">
                             <span 
                                class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest border"
                                :class="`${priorityConfig[report.priority]?.bg || 'bg-slate-100'} ${priorityConfig[report.priority]?.color || 'text-slate-500'} ${priorityConfig[report.priority]?.border || 'border-slate-200'}`"
                             >
                                {{ report.priority }}
                             </span>
                             <span class="text-[10px] font-bold text-slate-400">{{ formatDate(report.created_at) }}</span>
                        </div>
                    </div>

                    <!-- Content -->
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm mb-1 line-clamp-1">{{ report.reason || 'Signalement' }}</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed line-clamp-2 mb-4">{{ report.description || 'Aucune description fournie.' }}</p>

                    <!-- User Footer -->
                    <div class="flex items-center gap-3 pt-3 border-t border-slate-50 dark:border-white/5">
                        <div class="flex items-center gap-2">
                             <div class="w-6 h-6 rounded-full bg-slate-100 dark:bg-white/10 flex items-center justify-center text-[10px] font-black text-slate-600 dark:text-slate-300">
                                {{ report.reported?.name?.charAt(0) || '?' }}
                             </div>
                             <span class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ report.reported?.name || 'Utilisateur inconnu' }}</span>
                        </div>
                        <ArrowRight class="w-3 h-3 text-slate-300" />
                        <span class="text-[10px] font-black text-xs text-red-500 bg-red-50 dark:bg-red-500/10 px-1.5 py-0.5 rounded">REPORTED</span>
                    </div>

                    <!-- Active Indicator -->
                    <div v-if="selectedReport?.id === report.id" class="absolute left-0 top-6 bottom-6 w-1 bg-blue-500 rounded-r-full"></div>
                </div>

                <!-- Load More -->
                <button 
                    v-if="hasMore && !loading" 
                    @click="handleLoadMore"
                    class="py-3 text-center text-xs font-black text-slate-400 hover:text-blue-500 uppercase tracking-widest transition-colors"
                >
                    {{ loadingMore ? 'Chargement...' : 'Charger plus' }}
                </button>
            </div>

            <!-- RIGHT: Detail View (Sticky) -->
            <div class="hidden lg:flex flex-1 flex-col bg-white dark:bg-[#1E293B] rounded-[32px] border border-slate-200 dark:border-white/5 shadow-2xl shadow-slate-200/50 dark:shadow-none overflow-hidden relative">
                
                <template v-if="selectedReport">
                    <!-- Background Decoration -->
                    <div class="absolute top-0 right-0 p-12 opacity-[0.02] pointer-events-none">
                        <ShieldAlert class="w-[600px] h-[600px] text-slate-900 dark:text-white" />
                    </div>

                    <!-- Scrollable Content -->
                    <div class="flex-1 overflow-y-auto p-8 relative z-10 no-scrollbar pb-32">
                        
                        <!-- Header -->
                        <div class="flex items-start justify-between mb-8">
                            <div class="flex items-center gap-5">
                                <div class="w-20 h-20 rounded-[20px] bg-slate-100 dark:bg-[#0F172A] flex items-center justify-center shadow-inner overflow-hidden border border-slate-200 dark:border-white/5">
                                    <img v-if="selectedReport.reported?.photo" :src="selectedReport.reported.photo" class="w-full h-full object-cover">
                                    <User v-else class="w-8 h-8 text-slate-400" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ selectedReport.reported?.name || 'Utilisateur' }}</h2>
                                        <Paperclip v-if="selectedReport.subject_type" class="w-4 h-4 text-slate-400" />
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-1 bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 rounded-lg text-[10px] font-black uppercase tracking-wider border border-slate-200 dark:border-white/10">
                                            ID #{{ selectedReport.reported?.id }}
                                        </span>
                                        <span class="px-2.5 py-1 bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-lg text-[10px] font-black uppercase tracking-wider border border-blue-100 dark:border-blue-500/20">
                                            Client
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Priority Badge Large -->
                             <div class="flex flex-col items-end">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Niveau d'alerte</span>
                                <div class="flex items-center gap-2 px-4 py-2 rounded-xl border"
                                    :class="`${priorityConfig[selectedReport.priority]?.bg} ${priorityConfig[selectedReport.priority]?.color} ${priorityConfig[selectedReport.priority]?.border}`">
                                    <component :is="priorityConfig[selectedReport.priority]?.icon" class="w-5 h-5" />
                                    <span class="font-black uppercase tracking-wider text-sm">{{ selectedReport.priority }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Description Box -->
                        <div class="bg-slate-50 dark:bg-[#0F172A] rounded-[24px] p-8 border border-slate-100 dark:border-white/5 mb-8 relative group">
                            <div class="absolute inset-y-0 left-0 w-1 bg-slate-300 dark:bg-slate-700 rounded-l-[24px]"></div>
                            <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-4 flex items-center gap-2">
                                <MessageSquare class="w-3 h-3" />
                                Motif du signalement
                            </h3>
                            <p class="text-xl font-medium text-slate-800 dark:text-slate-200 leading-relaxed font-serif italic">
                                "{{ selectedReport.description || selectedReport.reason }}"
                            </p>
                        </div>

                        <!-- Reporter Info -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-5 rounded-[20px] bg-slate-50 dark:bg-[#0F172A] border border-slate-100 dark:border-white/5">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Signalé par</p>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-white dark:bg-white/10 flex items-center justify-center text-xs font-black text-slate-700 dark:text-white shadow-sm">
                                        {{ selectedReport.reporter?.name?.charAt(0) }}
                                    </div>
                                    <span class="font-bold text-slate-900 dark:text-white">{{ selectedReport.reporter?.name }}</span>
                                </div>
                            </div>
                            <div class="p-5 rounded-[20px] bg-slate-50 dark:bg-[#0F172A] border border-slate-100 dark:border-white/5">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Date du rapport</p>
                                <div class="flex items-center gap-3">
                                    <Clock class="w-4 h-4 text-slate-400" />
                                    <span class="font-bold text-slate-900 dark:text-white">{{ formatDate(selectedReport.created_at) }}</span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Sticky Action Bar -->
                    <div class="absolute bottom-0 left-0 right-0 p-6 bg-white/90 dark:bg-[#1E293B]/90 backdrop-blur-xl border-t border-slate-100 dark:border-white/5 z-20">
                        <div class="flex items-center gap-4">
                            <button 
                                @click="handleAction('ignore')"
                                class="flex-1 py-4 rounded-2xl border border-slate-200 dark:border-white/10 text-slate-500 dark:text-slate-400 font-black text-xs uppercase hover:bg-slate-50 dark:hover:bg-white/5 hover:text-slate-900 dark:hover:text-white transition-all"
                            >
                                Ignorer
                            </button>
                            <button 
                                @click="handleAction('warn')"
                                class="flex-1 py-4 rounded-2xl bg-amber-500 hover:bg-amber-400 text-black font-black text-xs uppercase shadow-lg shadow-amber-500/20 transition-all transform active:scale-[0.98] flex items-center justify-center gap-2"
                            >
                                <AlertTriangle class="w-4 h-4" />
                                Avertir
                            </button>
                            <button 
                                @click="handleAction('ban')"
                                class="flex-1 py-4 rounded-2xl bg-red-600 hover:bg-red-500 text-white font-black text-xs uppercase shadow-lg shadow-red-600/30 transition-all transform active:scale-[0.98] flex items-center justify-center gap-2"
                            >
                                <Ban class="w-4 h-4" />
                                Bannir
                            </button>
                        </div>
                    </div>

                </template>

                <!-- No Selection State -->
                <div v-else class="flex-1 flex flex-col items-center justify-center p-12 text-center opacity-40">
                    <ShieldAlert class="w-32 h-32 text-slate-300 dark:text-slate-600 mb-8" />
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2">Détails du signalement</h3>
                    <p class="text-base font-medium text-slate-500 max-w-sm mx-auto">Veuillez sélectionner un dossier dans la liste de gauche pour afficher les informations complètes et prendre une décision.</p>
                </div>

            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scroll::-webkit-scrollbar {
  width: 6px;
}
.custom-scroll::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scroll::-webkit-scrollbar-thumb {
  background: #CBD5E1; 
  border-radius: 10px;
}
.dark .custom-scroll::-webkit-scrollbar-thumb {
    background: #334155;
}
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>
