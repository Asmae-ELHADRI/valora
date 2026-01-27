<script setup>
import { ref, onMounted, nextTick, computed } from 'vue';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import { useRoute } from 'vue-router';
import { Send, User, MessageCircle, MoreVertical, Search, CheckCheck, ChevronLeft } from 'lucide-vue-next';

const auth = useAuthStore();
const route = useRoute();
const conversations = ref([]);
const activeConversation = ref(null);
const messages = ref([]);
const newMessage = ref('');
const loading = ref(true);
const messagesContainer = ref(null);
const showSidebar = ref(true);

const fetchConversations = async () => {
    try {
        const response = await api.get('/api/messages');
        conversations.value = response.data.map(c => ({
            ...c,
            last_message_date: c.last_message ? new Date(c.last_message.created_at) : new Date(0)
        }));
        
        if (route.query.user) {
            const userId = parseInt(route.query.user);
            const existingConv = conversations.value.find(c => c.user.id === userId);
            if (existingConv) {
                selectConversation(existingConv);
            }
        }
    } catch (err) {
        console.error('Error fetching conversations:', err);
    } finally {
        loading.value = false;
    }
};

const fetchMessages = async (userId) => {
    try {
        const response = await api.get(`/api/messages/${userId}`);
        messages.value = response.data.data.reverse();
        scrollToBottom();
    } catch (err) {
        console.error('Error fetching messages:', err);
    }
};

const getBadgeClass = (level) => {
    switch (level) {
        case 'Expert': return 'bg-purple-600 text-white shadow-lg shadow-purple-200';
        case 'Confirmé': return 'bg-blue-600 text-white shadow-lg shadow-blue-200';
        default: return 'bg-gray-500 text-white shadow-lg shadow-gray-200';
    }
};

const selectConversation = async (conv) => {
    activeConversation.value = conv;
    conv.unread_count = 0; 
    if (window.innerWidth < 768) showSidebar.value = false;
    await fetchMessages(conv.user.id);
};

const sendMessage = async () => {
    if (!newMessage.value.trim() || !activeConversation.value) return;

    const tempMsg = {
        id: 'temp-' + Date.now(),
        content: newMessage.value,
        sender_id: auth.user.id,
        receiver_id: activeConversation.value.user.id,
        created_at: new Date().toISOString(),
        pending: true
    };
    
    messages.value.push(tempMsg);
    newMessage.value = '';
    scrollToBottom();

    try {
        const response = await api.post('/api/messages', {
            receiver_id: activeConversation.value.user.id,
            content: tempMsg.content
        });
        
        const index = messages.value.findIndex(m => m.id === tempMsg.id);
        if (index !== -1) {
            messages.value[index] = response.data;
        }
        
        activeConversation.value.last_message = response.data;
        activeConversation.value.last_message_date = new Date(response.data.created_at);
        conversations.value.sort((a, b) => b.last_message_date - a.last_message_date);
        
    } catch (err) {
        console.error('Error sending message:', err);
    }
};

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

const formatTime = (date) => {
    return new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const groupedMessages = computed(() => {
    const groups = [];
    let currentGroup = null;

    messages.value.forEach(msg => {
        const date = new Date(msg.created_at);
        const day = date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
        
        if (!currentGroup || currentGroup.date !== day) {
            currentGroup = { date: day, messages: [] };
            groups.push(currentGroup);
        }
        currentGroup.messages.push(msg);
    });

    return groups;
});

const formatDateHeader = (dateStr) => {
    const today = new Date().toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
    const yesterday = new Date(Date.now() - 86400000).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
    
    if (dateStr === today) return "Aujourd'hui";
    if (dateStr === yesterday) return "Hier";
    const parts = dateStr.split(' ');
    if (parts[2] === new Date().getFullYear().toString()) {
        return parts.slice(0, 2).join(' ');
    }
    return dateStr;
};

onMounted(() => {
    fetchConversations();
    setInterval(() => {
        if (activeConversation.value) {
            fetchMessages(activeConversation.value.user.id);
        }
        fetchConversations();
    }, 10000);
});
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 h-[calc(100vh-100px)] flex gap-6 overflow-hidden">
        <!-- Sidebar -->
        <div 
            v-show="showSidebar"
            class="w-full md:w-1/3 bg-white border border-gray-100 rounded-[2rem] shadow-sm flex flex-col overflow-hidden transition-all duration-300"
            :class="{ 'hidden md:flex': !showSidebar }"
        >
            <div class="p-6 border-b border-gray-50 bg-gray-50/50 flex justify-between items-center">
                <h2 class="text-xl font-black text-gray-900 flex items-center">
                    <MessageCircle class="w-6 h-6 mr-2 text-blue-600" />
                    Messages
                </h2>
            </div>
            
            <div class="flex-grow overflow-y-auto">
                <div v-if="loading" class="p-10 flex justify-center">
                     <Loader2 class="w-8 h-8 text-blue-600 animate-spin" />
                </div>
                <div v-else-if="conversations.length === 0" class="p-10 text-center">
                    <p class="text-gray-400 font-bold">Aucune conversation</p>
                </div>
                <div v-else class="divide-y divide-gray-50">
                    <div 
                        v-for="conv in conversations" 
                        :key="conv.user.id"
                        @click="selectConversation(conv)"
                        :class="activeConversation?.user.id === conv.user.id ? 'bg-blue-50/50 border-l-4 border-blue-600' : 'hover:bg-gray-50/30 border-l-4 border-transparent'"
                        class="p-5 cursor-pointer transition relative group"
                    >
                        <div class="flex justify-between items-start mb-1">
                            <h3 class="font-bold text-gray-900 group-hover:text-blue-600 transition">{{ conv.user.name }}</h3>
                            <span class="text-[10px] font-bold text-gray-300" v-if="conv.last_message">{{ formatTime(conv.last_message.created_at) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="text-xs text-gray-400 truncate w-4/5">
                                <span v-if="conv.last_message?.sender_id === auth.user.id">Vous: </span>
                                {{ conv.last_message?.content || 'Début de conversation' }}
                            </p>
                            <span v-if="conv.unread_count > 0" class="bg-red-500 text-white text-[10px] font-black px-2 py-0.5 rounded-full shadow-lg shadow-red-100">
                                {{ conv.unread_count }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat Window -->
        <div 
            v-show="!showSidebar || window?.innerWidth >= 768"
            class="flex-grow bg-white border border-gray-100 rounded-[2.5rem] shadow-sm flex flex-col overflow-hidden relative"
        >
            <template v-if="activeConversation">
                <!-- Chat Header -->
                <div class="p-5 border-b border-gray-100 bg-white flex justify-between items-center z-10 shadow-sm">
                     <div class="flex items-center space-x-4">
                          <button @click="showSidebar = true" class="md:hidden p-2 -ml-2 rounded-xl bg-gray-50 text-gray-500">
                              <ChevronLeft class="w-5 h-5" />
                          </button>
                          <div class="w-12 h-12 bg-gray-100 rounded-2xl flex items-center justify-center overflow-hidden border border-gray-50">
                              <img v-if="activeConversation.user.role === 'provider' && activeConversation.user.prestataire?.photo" :src="`http://localhost:8000/storage/${activeConversation.user.prestataire.photo}`" class="w-full h-full object-cover">
                              <div v-else class="w-full h-full flex items-center justify-center text-gray-300"><User class="w-6 h-6" /></div>
                          </div>
                          <div>
                              <div class="flex items-center space-x-2">
                                  <h3 class="font-black text-gray-900 leading-none">{{ activeConversation.user.name }}</h3>
                                  <span v-if="activeConversation.user.role === 'provider' && activeConversation.user.prestataire?.badge_level" 
                                        :class="getBadgeClass(activeConversation.user.prestataire.badge_level)"
                                        class="px-2 py-0.5 rounded-lg text-[8px] font-black uppercase tracking-widest"
                                  >
                                      {{ activeConversation.user.prestataire.badge_level }}
                                  </span>
                              </div>
                              <p class="text-[10px] font-bold text-green-500 mt-1 uppercase tracking-widest flex items-center">
                                 <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1 animate-pulse"></span> Connecté
                              </p>
                          </div>
                     </div>
                    
                    <button class="p-3 rounded-2xl text-gray-400 hover:bg-gray-50 transition">
                        <MoreVertical class="w-5 h-5" />
                    </button>
                </div>

                <!-- Messages Area with Grouping -->
                <div ref="messagesContainer" class="flex-grow overflow-y-auto p-8 space-y-8 bg-slate-50/50">
                    <div v-for="group in groupedMessages" :key="group.date" class="space-y-6">
                        <!-- Date Separator -->
                        <div class="flex justify-center">
                            <span class="px-4 py-1.5 bg-white rounded-full text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] shadow-sm border border-gray-100">
                                {{ formatDateHeader(group.date) }}
                            </span>
                        </div>

                        <!-- Messages in group -->
                        <div 
                            v-for="msg in group.messages" 
                            :key="msg.id"
                            :class="msg.sender_id === auth.user.id ? 'justify-end' : 'justify-start'"
                            class="flex"
                        >
                            <div 
                                :class="msg.sender_id === auth.user.id 
                                    ? 'bg-blue-600 text-white rounded-[20px] rounded-tr-none shadow-xl shadow-blue-100' 
                                    : 'bg-white text-gray-800 border border-gray-100 rounded-[20px] rounded-tl-none shadow-sm'"
                                class="max-w-[80%] px-5 py-4 relative group transition-all hover:scale-[1.01]"
                            >
                                <p class="text-sm font-medium leading-relaxed">{{ msg.content }}</p>
                                <div 
                                    :class="msg.sender_id === auth.user.id ? 'text-blue-100' : 'text-gray-400'"
                                    class="text-[9px] font-bold mt-2 flex items-center justify-end space-x-1 uppercase tracking-tighter"
                                >
                                    <span>{{ formatTime(msg.created_at) }}</span>
                                    <CheckCheck v-if="msg.sender_id === auth.user.id && !msg.pending" class="w-3 h-3" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="p-6 bg-white border-t border-gray-100">
                    <form @submit.prevent="sendMessage" class="flex items-center space-x-3">
                        <input 
                            v-model="newMessage"
                            type="text" 
                            placeholder="Tapez un message..." 
                            class="flex-grow px-6 py-4 rounded-2xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition font-medium text-sm"
                        >
                        <button 
                            type="submit" 
                            :disabled="!newMessage.trim()"
                            class="p-4 bg-blue-600 text-white rounded-2xl hover:bg-blue-700 transition disabled:opacity-50 disabled:grayscale shadow-xl shadow-blue-100 active:scale-95"
                        >
                            <Send class="w-6 h-6" />
                        </button>
                    </form>
                </div>
            </template>
            
            <div v-else class="flex-grow flex flex-col items-center justify-center text-gray-400 p-10 bg-slate-50/50">
                <div class="w-24 h-24 bg-white rounded-[2rem] shadow-sm border border-gray-100 flex items-center justify-center mb-6">
                    <MessageCircle class="w-10 h-10 text-blue-100" />
                </div>
                <h3 class="font-black text-gray-900 text-xl">Vos conversations</h3>
                <p class="text-gray-400 text-sm font-medium mt-2">Sélectionnez un contact pour discuter</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Scrollbar modern style */
::-webkit-scrollbar {
  width: 6px;
}
::-webkit-scrollbar-track {
  background: transparent;
}
::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
  background: #cbd5e1;
}
</style>
