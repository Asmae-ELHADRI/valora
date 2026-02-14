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
    MoreHorizontal,
    FileText,
    Image as ImageIcon
} from 'lucide-vue-next';

// State
const conversations = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const page = ref(1);
const totalPages = ref(1);

const selectedConversation = ref(null);
const messages = ref([]);
const messagesLoading = ref(false);

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
        conversations.value = response.data.data || [];
        totalPages.value = response.data.last_page || 1;
    } catch (error) {
        console.error('Error fetching conversations:', error);
    } finally {
        loading.value = false;
    }
};

const selectConversation = async (conv) => {
    selectedConversation.value = conv;
    messagesLoading.value = true;
    messages.value = [];

    // Identify the two users involved
    const user1Id = conv.sender_id;
    const user2Id = conv.receiver_id;

    try {
        const response = await api.get('/api/admin/conversations/messages', {
            params: {
                user1_id: user1Id,
                user2_id: user2Id
            }
        });
        messages.value = response.data;
    } catch (error) {
        console.error('Error fetching messages:', error);
    } finally {
        messagesLoading.value = false;
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

const getParticipant = (conv, role) => {
    // Helper to try and identify client vs provider if possible, otherwise just return sender/receiver
    // In the conversation list item, we have sender and receiver.
    // If we want to be strict, we can check roles.
    if (conv.sender?.role === role) return conv.sender;
    if (conv.receiver?.role === role) return conv.receiver;
    return null; 
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
    <div class="h-[calc(100vh-100px)] flex flex-col gap-4">
        <!-- Header -->
        <div class="flex items-center justify-between shrink-0">
            <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Conversations</h1>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                    Suivez les échanges entre utilisateurs
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
                    placeholder="Rechercher..." 
                    class="pl-10 pr-4 py-2 bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-white/10 rounded-xl text-sm font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 focus:outline-none transition-all w-64 shadow-sm"
                >
            </div>
        </div>

        <!-- Main Content Area (Split View) -->
        <div class="flex-1 flex gap-6 overflow-hidden">
            
            <!-- Left Column: Conversation List -->
            <div class="w-1/3 min-w-[350px] flex flex-col bg-white dark:bg-[#1E293B] rounded-2xl border border-slate-200 dark:border-white/5 shadow-sm overflow-hidden">
                <!-- List container -->
                <div class="flex-1 overflow-y-auto p-2 space-y-2 custom-scrollbar">
                    <div v-if="loading" class="space-y-3 p-2">
                         <div v-for="i in 5" :key="i" class="h-20 bg-slate-50 dark:bg-slate-800/50 rounded-xl animate-pulse"></div>
                    </div>

                    <div v-else-if="conversations.length === 0" class="flex flex-col items-center justify-center h-full text-center p-6">
                        <MessageCircle class="w-12 h-12 text-slate-300 dark:text-slate-600 mb-3" />
                        <p class="text-slate-900 dark:text-white font-bold">Aucune conversation</p>
                    </div>

                    <div 
                        v-for="conv in conversations" 
                        :key="conv.id"
                        @click="selectConversation(conv)"
                        class="p-4 rounded-xl cursor-pointer transition-all duration-200 border border-transparent hover:border-slate-200 dark:hover:border-white/10"
                        :class="selectedConversation?.id === conv.id ? 'bg-blue-50 dark:bg-blue-500/10 border-blue-200 dark:border-blue-500/20' : 'hover:bg-slate-50 dark:hover:bg-white/5'"
                    >
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex -space-x-2">
                                <div class="w-8 h-8 rounded-full border-2 border-white dark:border-[#1E293B] bg-slate-200 flex items-center justify-center overflow-hidden">
                                     <img v-if="conv.sender?.photo" :src="conv.sender.photo" class="w-full h-full object-cover">
                                     <span v-else class="text-xs font-bold">{{ conv.sender?.name?.charAt(0) }}</span>
                                </div>
                                <div class="w-8 h-8 rounded-full border-2 border-white dark:border-[#1E293B] bg-slate-200 flex items-center justify-center overflow-hidden">
                                     <img v-if="conv.receiver?.photo" :src="conv.receiver.photo" class="w-full h-full object-cover">
                                     <span v-else class="text-xs font-bold">{{ conv.receiver?.name?.charAt(0) }}</span>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold text-slate-400">{{ formatDate(conv.created_at) }}</span>
                        </div>
                        
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-bold text-slate-900 dark:text-white">{{ conv.sender?.name }}</span>
                            <span class="text-xs text-slate-400">&</span>
                            <span class="text-xs font-bold text-slate-900 dark:text-white">{{ conv.receiver?.name }}</span>
                        </div>

                        <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1">
                            <span class="font-semibold text-slate-700 dark:text-slate-300">
                                {{ conv.sender_id === conv.sender.id ? conv.sender.name.split(' ')[0] : conv.receiver.name.split(' ')[0] }}:
                            </span> 
                            {{ conv.content || (conv.attachment_type ? '[Fichier]' : '') }}
                        </p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="totalPages > 1" class="p-3 border-t border-slate-100 dark:border-white/5 flex justify-center gap-2">
                    <button 
                        @click="page--" 
                        :disabled="page === 1"
                        class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/5 disabled:opacity-50"
                    >
                        <ChevronLeft class="w-4 h-4" />
                    </button>
                    <span class="text-xs font-bold self-center">Page {{ page }}</span>
                    <button 
                        @click="page++" 
                        :disabled="page === totalPages"
                        class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-white/5 disabled:opacity-50"
                    >
                        <ChevronRight class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- Right Column: Conversation Details -->
            <div class="flex-1 bg-white dark:bg-[#1E293B] rounded-2xl border border-slate-200 dark:border-white/5 shadow-sm overflow-hidden flex flex-col relative">
                
                <div v-if="!selectedConversation" class="h-full flex flex-col items-center justify-center text-center p-10">
                    <div class="w-20 h-20 rounded-full bg-slate-50 dark:bg-white/5 flex items-center justify-center mb-6">
                        <MessageCircle class="w-10 h-10 text-slate-300 dark:text-slate-600" />
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Sélectionnez une conversation</h3>
                    <p class="text-slate-500 dark:text-slate-400 max-w-xs mx-auto">
                        Cliquez sur une conversation dans la liste à gauche pour voir l'historique des messages.
                    </p>
                </div>

                <template v-else>
                    <!-- Header -->
                    <div class="p-4 border-b border-slate-100 dark:border-white/5 flex items-center justify-between shrink-0 bg-white/50 dark:bg-[#1E293B]/50 backdrop-blur-sm z-10">
                         <div class="flex items-center gap-4">
                            <!-- Participant 1 -->
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-slate-100 overflow-hidden">
                                    <img v-if="selectedConversation.sender?.photo" :src="selectedConversation.sender.photo" class="w-full h-full object-cover">
                                    <span v-else class="w-full h-full flex items-center justify-center text-xs font-bold">{{ selectedConversation.sender?.name?.charAt(0) }}</span>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-900 dark:text-white">{{ selectedConversation.sender?.name }}</p>
                                    <span class="text-[10px] uppercase text-slate-400 font-bold tracking-wider">{{ selectedConversation.sender?.role }}</span>
                                </div>
                            </div>
                            
                            <div class="h-8 w-px bg-slate-200 dark:bg-white/10"></div>

                            <!-- Participant 2 -->
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-slate-100 overflow-hidden">
                                     <img v-if="selectedConversation.receiver?.photo" :src="selectedConversation.receiver.photo" class="w-full h-full object-cover">
                                    <span v-else class="w-full h-full flex items-center justify-center text-xs font-bold">{{ selectedConversation.receiver?.name?.charAt(0) }}</span>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-900 dark:text-white">{{ selectedConversation.receiver?.name }}</p>
                                    <span class="text-[10px] uppercase text-slate-400 font-bold tracking-wider">{{ selectedConversation.receiver?.role }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="px-3 py-1 rounded-full bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 text-xs font-bold border border-amber-100 dark:border-amber-500/20">
                            Lecture Seule
                        </div>
                    </div>

                    <!-- Messages -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar bg-slate-50/50 dark:bg-[#0F172A]/50">
                        <div v-if="messagesLoading" class="flex justify-center p-10">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                        </div>
                        
                        <div v-else v-for="msg in messages" :key="msg.id" class="flex flex-col gap-1"
                            :class="msg.sender_id === selectedConversation.sender_id ? 'items-start' : 'items-end'"
                        >
                            <span class="text-[10px] font-bold text-slate-400 px-1">
                                {{ msg.sender_id === selectedConversation.sender_id ? selectedConversation.sender.name : selectedConversation.receiver.name }}
                            </span>
                            
                            <div 
                                class="max-w-[70%] p-3 rounded-2xl shadow-sm text-sm"
                                :class="msg.sender_id === selectedConversation.sender_id ? 'bg-white dark:bg-[#1E293B] text-slate-700 dark:text-slate-200 rounded-tl-sm' : 'bg-blue-500 text-white rounded-tr-sm'"
                            >
                                <p v-if="msg.content">{{ msg.content }}</p>
                                
                                <div v-if="msg.attachment_path" class="mt-2 flex items-center gap-2 text-xs opacity-90 p-2 bg-black/5 rounded-lg border border-black/5">
                                    <component :is="msg.attachment_type === 'image' ? ImageIcon : FileText" class="w-4 h-4" />
                                    <span>Pièce jointe</span>
                                </div>
                            </div>
                            
                            <span class="text-[10px] text-slate-400 px-1">{{ formatDate(msg.created_at) }}</span>
                        </div>
                    </div>
                </template>
            </div>

        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: rgba(156, 163, 175, 0.5);
  border-radius: 20px;
}
</style>
