<script setup>
import { ref, onMounted, watch } from 'vue';
import api from '../../services/api';
import { 
    Search, 
    MessageCircle, 
    Clock, 
    ChevronLeft, 
    ChevronRight, 
    User,
    MoreHorizontal
} from 'lucide-vue-next';

// State
const conversations = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const page = ref(1);
const totalPages = ref(1);
const currentMeta = ref(null);

// Actions
const fetchConversations = async () => {
    loading.value = true;
    try {
        const response = await api.get('/api/admin/conversations', {
            params: {
                page: page.value,
                search: searchQuery.value
            }
        });
        conversations.value = response.data.data;
        currentMeta.value = response.data;
        totalPages.value = response.data.last_page;
    } catch (error) {
        console.error('Error fetching conversations:', error);
    } finally {
        loading.value = false;
    }
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleString(undefined, {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Watchers
let searchTimeout;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        page.value = 1;
        fetchConversations();
    }, 300);
});

watch(page, () => {
    fetchConversations();
});

onMounted(() => {
    fetchConversations();
});
</script>

<template>
    <div class="flex flex-col gap-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Conversations</h1>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mt-1">
                    Suivez les échanges entre utilisateurs sur la plateforme.
                </p>
            </div>

            <!-- Search -->
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Search class="h-4 w-4 text-slate-400 group-focus-within:text-blue-500 transition-colors" />
                </div>
                <input 
                    v-model="searchQuery" 
                    type="text" 
                    placeholder="Rechercher un utilisateur..." 
                    class="pl-10 pr-4 py-2.5 bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-white/10 rounded-xl text-sm font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 focus:outline-none transition-all w-64 shadow-sm"
                >
            </div>
        </div>

        <!-- Content -->
        <div class="bg-white dark:bg-[#1E293B] rounded-[24px] border border-slate-200 dark:border-white/5 shadow-sm overflow-hidden">
            
            <!-- Loading -->
            <div v-if="loading" class="p-8 space-y-4">
                <div v-for="i in 5" :key="i" class="h-20 bg-slate-50 dark:bg-slate-800/50 rounded-2xl animate-pulse"></div>
            </div>

            <!-- Empty State -->
            <div v-else-if="conversations.length === 0" class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-[#0F172A] flex items-center justify-center mb-4">
                    <MessageCircle class="w-8 h-8 text-slate-400" />
                </div>
                <p class="text-slate-900 dark:text-white font-bold">Aucune conversation trouvée</p>
                <p class="text-xs text-slate-500 mt-1">Essayez de modifier votre recherche.</p>
            </div>

            <!-- List -->
            <div v-else class="divide-y divide-slate-100 dark:divide-white/5">
                <div 
                    v-for="conv in conversations" 
                    :key="conv.id"
                    class="p-6 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors group cursor-pointer"
                >
                    <div class="flex items-start justify-between">
                        <!-- Participants -->
                        <div class="flex items-center gap-4">
                            <!-- User 1 -->
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-white/10 flex items-center justify-center overflow-hidden border border-slate-200 dark:border-white/10">
                                    <img v-if="conv.sender?.photo" :src="conv.sender.photo" class="w-full h-full object-cover">
                                    <span v-else class="font-bold text-slate-500 dark:text-slate-400">{{ conv.sender?.name?.charAt(0) }}</span>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ conv.sender?.name }}</p>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400 uppercase tracking-wider">{{ conv.sender?.role || 'User' }}</span>
                                </div>
                            </div>

                            <div class="px-4 text-slate-300 dark:text-slate-600">
                                <MoreHorizontal class="w-5 h-5" />
                            </div>

                            <!-- User 2 -->
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-white/10 flex items-center justify-center overflow-hidden border border-slate-200 dark:border-white/10">
                                    <img v-if="conv.receiver?.photo" :src="conv.receiver.photo" class="w-full h-full object-cover">
                                    <span v-else class="font-bold text-slate-500 dark:text-slate-400">{{ conv.receiver?.name?.charAt(0) }}</span>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ conv.receiver?.name }}</p>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400 uppercase tracking-wider">{{ conv.receiver?.role || 'User' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Time -->
                        <div class="flex flex-col items-end">
                            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 mb-1">
                                <Clock class="w-3.5 h-3.5" />
                                {{ formatDate(conv.created_at) }}
                            </div>
                        </div>
                    </div>

                    <!-- Last Message Snippet -->
                    <div class="mt-4 pl-14 pr-4">
                        <div class="bg-slate-50 dark:bg-[#0F172A] rounded-xl p-4 border border-slate-100 dark:border-white/5 relative">
                            <div class="absolute inset-y-0 left-0 w-1 bg-slate-200 dark:bg-slate-700 rounded-l-xl"></div>
                            <p class="text-sm text-slate-600 dark:text-slate-300 line-clamp-2 font-medium">
                                <span class="text-xs font-black text-slate-400 uppercase mr-2 tracking-wider">{{ conv.sender_id === conv.sender.id ? conv.sender.name : conv.receiver.name }} :</span>
                                {{ conv.content || (conv.attachment_type ? '[Fichier ' + conv.attachment_type + ']' : '') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="p-6 border-t border-slate-100 dark:border-white/5 flex items-center justify-center gap-4">
                <button 
                    @click="page--" 
                    :disabled="page === 1"
                    class="p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-white/5 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                    <ChevronLeft class="w-5 h-5 text-slate-600 dark:text-slate-400" />
                </button>
                <span class="text-sm font-bold text-slate-600 dark:text-slate-400">Page {{ page }} sur {{ totalPages }}</span>
                <button 
                    @click="page++" 
                    :disabled="page === totalPages"
                    class="p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-white/5 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                    <ChevronRight class="w-5 h-5 text-slate-600 dark:text-slate-400" />
                </button>
            </div>
        </div>
    </div>
</template>
