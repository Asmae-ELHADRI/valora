<script setup>
import { ref, onMounted, watch, computed, nextTick } from 'vue';
import api from '../../services/api';
import { 
    Search, 
    MessageCircle, 
    Clock, 
    ChevronLeft, 
    ChevronRight, 
    User,
    MoreHorizontal,
    ArrowLeft,
    Shield,
    Trash2,
    CheckCircle2,
    Calendar,
    Send
} from 'lucide-vue-next';

// State
const conversations = ref([]);
const loading = ref(false);
const messagesLoading = ref(false);
const searchQuery = ref('');
const page = ref(1);
const totalPages = ref(1);
const selectedConversation = ref(null);
const messages = ref([]);
const messagesPage = ref(1);
const hasMoreMessages = ref(false);
const chatContainer = ref(null);

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
        totalPages.value = response.data.last_page;
    } catch (error) {
        console.error('Error fetching conversations:', error);
    } finally {
        loading.value = false;
    }
};

const selectConversation = async (conv) => {
    selectedConversation.value = conv;
    messages.value = [];
    messagesPage.value = 1;
    await fetchMessages();
    scrollToBottom();
};

const fetchMessages = async (loadMore = false) => {
    if (!selectedConversation.value) return;
    messagesLoading.value = true;
    try {
        const response = await api.get(`/api/admin/conversations/${selectedConversation.value.sender_id}/${selectedConversation.value.receiver_id}`, {
            params: { page: messagesPage.value }
        });
        
        const newMessages = response.data.data.reverse();
        if (loadMore) {
            messages.value = [...newMessages, ...messages.value];
        } else {
            messages.value = newMessages;
        }
        hasMoreMessages.value = response.data.current_page < response.data.last_page;
    } catch (error) {
        console.error('Error fetching messages:', error);
    } finally {
        messagesLoading.value = false;
    }
};

const scrollToBottom = () => {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
        }
    });
};

const formatTime = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString();
};

const isToday = (date) => {
    const today = new Date();
    const d = new Date(date);
    return d.toDateString() === today.toDateString();
};

const getGroupedMessages = computed(() => {
    const groups = [];
    let currentGroup = null;

    messages.value.forEach(msg => {
        const date = new Date(msg.created_at).toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });

        if (!currentGroup || currentGroup.date !== date) {
            currentGroup = { date, messages: [] };
            groups.push(currentGroup);
        }
        currentGroup.messages.push(msg);
    });

    return groups;
});

// Watchers
let searchTimeout;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        page.value = 1;
        fetchConversations();
    }, 300);
});

watch(page, fetchConversations);

onMounted(fetchConversations);
</script>

<template>
    <div class="h-[calc(100vh-140px)] flex flex-col">
        <!-- Main Layout -->
        <div class="flex flex-1 gap-6 min-h-0">
            <!-- Left Sidebar: Conversations List -->
            <div 
                class="w-full md:w-80 lg:w-96 flex flex-col bg-white dark:bg-[#1E293B] rounded-[32px] border border-slate-200 dark:border-white/5 shadow-premium overflow-hidden"
                :class="{ 'hidden md:flex': selectedConversation }"
            >
                <!-- Sidebar Header -->
                <div class="p-6 border-b border-slate-100 dark:border-white/5">
                    <div class="flex items-center justify-between mb-4">
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Messages</h1>
                        <Shield class="w-5 h-5 text-blue-500" />
                    </div>
                    
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Rechercher..."
                            class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-[#0F172A] border-none rounded-xl text-sm font-bold placeholder-slate-400 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all"
                        >
                    </div>
                </div>

                <!-- Conversation List -->
                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    <div v-if="loading" class="p-4 space-y-3">
                        <div v-for="i in 6" :key="i" class="h-16 bg-slate-50 dark:bg-slate-800 animate-pulse rounded-2xl"></div>
                    </div>

                    <div v-else-if="conversations.length === 0" class="flex flex-col items-center justify-center p-8 text-center text-slate-400">
                        <MessageCircle class="w-12 h-12 mb-2 opacity-20" />
                        <p class="text-sm font-bold">Aucune conversation</p>
                    </div>

                    <div v-else>
                        <button 
                            v-for="conv in conversations" 
                            :key="conv.id"
                            @click="selectConversation(conv)"
                            class="w-full p-4 flex items-center gap-4 hover:bg-slate-50 dark:hover:bg-white/5 transition-all relative border-b border-slate-50 dark:border-white/5 text-left"
                            :class="{ 'bg-blue-50/50 dark:bg-blue-500/5': selectedConversation?.id === conv.id }"
                        >
                            <!-- Selection Indicator -->
                            <div v-if="selectedConversation?.id === conv.id" class="absolute inset-y-0 left-0 w-1 bg-blue-500 rounded-r-full shadow-glow-blue"></div>

                            <div class="relative flex -space-x-4">
                                <!-- Participants Avatars -->
                                <div class="w-10 h-10 rounded-full border-2 border-white dark:border-[#1E293B] overflow-hidden bg-slate-100 flex-shrink-0 z-10">
                                    <img v-if="conv.sender?.photo" :src="conv.sender.photo" class="w-full h-full object-cover">
                                    <div v-else class="w-full h-full flex items-center justify-center bg-blue-500 text-white font-black text-xs">
                                        {{ conv.sender?.name?.charAt(0) }}
                                    </div>
                                </div>
                                <div class="w-10 h-10 rounded-full border-2 border-white dark:border-[#1E293B] overflow-hidden bg-slate-100 flex-shrink-0">
                                    <img v-if="conv.receiver?.photo" :src="conv.receiver.photo" class="w-full h-full object-cover">
                                    <div v-else class="w-full h-full flex items-center justify-center bg-slate-300 text-slate-600 font-black text-xs">
                                        {{ conv.receiver?.name?.charAt(0) }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start mb-0.5">
                                    <p class="text-sm font-black text-slate-900 dark:text-white truncate pr-2">
                                        {{ conv.sender?.name }} & {{ conv.receiver?.name }}
                                    </p>
                                    <span class="text-[10px] font-bold text-slate-400 whitespace-nowrap">
                                        {{ formatDate(conv.created_at) }}
                                    </span>
                                </div>
                                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 truncate opacity-70">
                                    {{ conv.content || 'Fichier joint' }}
                                </p>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Admin Pagination -->
                <div v-if="totalPages > 1" class="p-4 border-t border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-transparent flex items-center justify-between">
                    <button @click="page--" :disabled="page === 1" class="p-2 hover:bg-white dark:hover:bg-white/10 rounded-lg disabled:opacity-30 transition-all shadow-sm active:scale-95">
                        <ChevronLeft class="w-4 h-4" />
                    </button>
                    <span class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">Page {{ page }}/{{ totalPages }}</span>
                    <button @click="page++" :disabled="page === totalPages" class="p-2 hover:bg-white dark:hover:bg-white/10 rounded-lg disabled:opacity-30 transition-all shadow-sm active:scale-95">
                        <ChevronRight class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- Right Pane: Message Viewer -->
            <div 
                v-if="selectedConversation"
                class="flex-1 flex flex-col bg-white dark:bg-[#1E293B] rounded-[40px] border border-slate-200 dark:border-white/5 shadow-premium overflow-hidden"
            >
                <!-- Chat Header -->
                <div class="px-6 py-5 flex items-center justify-between border-b border-slate-50 dark:border-white/5 z-20">
                    <div class="flex items-center gap-4">
                        <button @click="selectedConversation = null" class="md:hidden p-2 -ml-2 hover:bg-slate-100 rounded-full dark:hover:bg-white/10">
                            <ArrowLeft class="w-5 h-5" />
                        </button>
                        
                        <div class="flex -space-x-3">
                            <div class="w-10 h-10 rounded-full border-2 border-white dark:border-[#1E293B] overflow-hidden bg-slate-100 shadow-sm relative z-10">
                                <img v-if="selectedConversation.sender?.photo" :src="selectedConversation.sender.photo" class="w-full h-full object-cover">
                                <div v-else class="w-full h-full flex items-center justify-center bg-blue-500 text-white font-black text-sm">
                                    {{ selectedConversation.sender?.name?.charAt(0) }}
                                </div>
                            </div>
                            <div class="w-10 h-10 rounded-full border-2 border-white dark:border-[#1E293B] overflow-hidden bg-slate-100 shadow-sm">
                                <img v-if="selectedConversation.receiver?.photo" :src="selectedConversation.receiver.photo" class="w-full h-full object-cover">
                                <div v-else class="w-full h-full flex items-center justify-center bg-slate-300 text-slate-600 font-black text-sm">
                                    {{ selectedConversation.receiver?.name?.charAt(0) }}
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="font-black text-slate-900 dark:text-white leading-tight">
                                {{ selectedConversation.sender?.name }} <span class="text-slate-300 mx-1">/</span> {{ selectedConversation.receiver?.name }}
                            </h3>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="flex items-center gap-1 text-[10px] font-black uppercase tracking-tighter text-blue-500">
                                    <Shield class="w-2.5 h-2.5" /> Monitoring Admin
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                         <button class="p-2.5 hover:bg-red-50 text-slate-400 hover:text-red-500 dark:hover:bg-red-500/10 rounded-2xl transition-all active:scale-90">
                            <Trash2 class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <!-- Messages Feed -->
                <div 
                    ref="chatContainer"
                    class="flex-1 overflow-y-auto p-6 scroll-smooth bg-slate-50/30 dark:bg-[#0F172A]/30 custom-scrollbar"
                >
                    <div v-if="messagesLoading && messages.length === 0" class="flex flex-col items-center justify-center h-full space-y-4">
                        <div class="w-10 h-10 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                        <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Chargement de l'historique...</p>
                    </div>

                    <div v-else class="space-y-8">
                        <div v-for="group in getGroupedMessages" :key="group.date" class="space-y-6">
                            <!-- Date Separator -->
                            <div class="flex justify-center sticky top-2 z-10">
                                <div class="px-4 py-1.5 bg-white/80 dark:bg-[#1E293B]/80 backdrop-blur-md rounded-full border border-slate-200 dark:border-white/10 shadow-sm">
                                    <span class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest whitespace-nowrap">
                                        {{ group.date }}
                                    </span>
                                </div>
                            </div>

                            <div 
                                v-for="msg in group.messages" 
                                :key="msg.id"
                                class="flex"
                                :class="msg.sender_id === selectedConversation.sender_id ? 'justify-start' : 'justify-end'"
                            >
                                <div 
                                    class="max-w-[85%] group relative"
                                    :class="msg.sender_id === selectedConversation.sender_id ? 'order-2' : 'order-1 text-right'"
                                >
                                    <!-- Sender Name (Admin Monitor Only) -->
                                    <span class="block text-[10px] font-black text-slate-400 uppercase mb-1 px-2 tracking-wider">
                                        {{ msg.sender_id === selectedConversation.sender_id ? selectedConversation.sender?.name : selectedConversation.receiver?.name }}
                                    </span>

                                    <!-- Bubble -->
                                    <div 
                                        class="p-4 shadow-sm"
                                        :class="[
                                            msg.sender_id === selectedConversation.sender_id 
                                                ? 'bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-white/10 rounded-2xl rounded-tl-none' 
                                                : 'bg-slate-900 dark:bg-blue-600 text-white rounded-2xl rounded-tr-none'
                                        ]"
                                    >
                                        <p class="text-sm font-medium leading-relaxed break-words">{{ msg.content }}</p>

                                        <!-- Attachments -->
                                        <div v-if="msg.attachment_path" class="mt-3">
                                            <div v-if="msg.attachment_type === 'image'" class="rounded-xl overflow-hidden shadow-premium">
                                                <img :src="`/api/admin/messages/attachments/${msg.id}`" class="w-full max-h-96 object-cover hover:scale-105 transition-transform cursor-zoom-in">
                                            </div>
                                            <div v-else class="flex items-center gap-3 p-3 bg-black/5 dark:bg-white/5 rounded-xl border border-white/10">
                                                <div class="w-10 h-10 rounded-lg bg-blue-500 flex items-center justify-center">
                                                    <Shield class="w-5 h-5 text-white" />
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-xs font-bold truncate">Document joint</p>
                                                    <p class="text-[10px] font-bold opacity-60 uppercase">{{ msg.attachment_type }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-end gap-1.5 mt-2 opacity-60">
                                            <span class="text-[10px] font-bold uppercase tracking-widest">{{ formatTime(msg.created_at) }}</span>
                                            <CheckCircle2 v-if="msg.read_at" class="w-3 h-3 text-blue-400" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer (Monitoring info) -->
                <div class="p-6 bg-slate-50 dark:bg-[#0F172A] border-t border-slate-100 dark:border-white/5">
                    <div class="flex items-center justify-between text-slate-400 text-xs font-bold uppercase tracking-widest">
                        <span class="flex items-center gap-2">
                             <Shield class="w-4 h-4 text-blue-500" /> Mode Surveillance Admin
                        </span>
                        <span>Total: {{ messages.length }} messages vus</span>
                    </div>
                </div>
            </div>

            <!-- Empty State / No selection -->
            <div 
                v-else
                class="hidden md:flex flex-1 flex-col items-center justify-center bg-white dark:bg-[#1E293B] rounded-[40px] border border-slate-200 dark:border-white/5 shadow-premium text-center p-12"
            >
                <div class="w-24 h-24 rounded-[32px] bg-slate-50 dark:bg-[#0F172A] flex items-center justify-center mb-6 shadow-sm border border-slate-100 dark:border-white/5">
                    <MessageCircle class="w-10 h-10 text-slate-300" />
                </div>
                <h2 class="text-2xl font-black text-slate-900 dark:text-white mb-2">Historique des Conversations</h2>
                <p class="text-slate-400 max-w-sm font-medium">Sélectionnez une discussion dans la liste de gauche pour consulter l'intégralité des échanges.</p>
                
                <div class="mt-8 grid grid-cols-2 gap-4 w-full max-w-md">
                    <div class="p-4 bg-blue-50/50 dark:bg-blue-500/5 rounded-2xl border border-blue-100/50 dark:border-blue-500/10 text-left">
                        <Shield class="w-5 h-5 text-blue-500 mb-2" />
                        <p class="text-xs font-bold dark:text-white">Sécurisé</p>
                        <p class="text-[10px] text-slate-500 mt-1 line-clamp-2">Tous les messages sont cryptés et surveillés pour votre sécurité.</p>
                    </div>
                    <div class="p-4 bg-amber-50/50 dark:bg-amber-500/5 rounded-2xl border border-amber-100/50 dark:border-amber-500/10 text-left">
                        <Calendar class="w-5 h-5 text-amber-500 mb-2" />
                        <p class="text-xs font-bold dark:text-white">Archivé</p>
                        <p class="text-[10px] text-slate-500 mt-1 line-clamp-2">L'historique complet est conservé pendant la durée légale.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.shadow-premium {
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 10px 15px -3px rgb(0 0 0 / 0.05);
}

.shadow-glow-blue {
    box-shadow: 2px 0 15px -3px rgba(59, 130, 246, 0.4);
}

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

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(148, 163, 184, 0.4);
}

/* Animations */
.message-enter-active, .message-leave-active {
  transition: all 0.3s ease;
}
.message-enter-from, .message-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
