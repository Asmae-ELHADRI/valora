<script setup>
import { ref, onMounted, nextTick, computed } from 'vue';
import { useAuthStore } from '../../store/auth';
import api from '../../services/api';
import { Send, MessageSquare, Search, Loader2, User, Clock } from 'lucide-vue-next';

const auth = useAuthStore();
const conversations = ref([]);
const activeConversation = ref(null);
const messages = ref([]);
const newMessage = ref('');
const loading = ref(true);
const isSending = ref(false);
const messagesContainer = ref(null);
const searchQuery = ref('');

const filteredConversations = computed(() => {
    if (!searchQuery.value) return conversations.value;
    
    const query = searchQuery.value.toLowerCase();
    return conversations.value.filter(conv => 
        conv.other_user?.name?.toLowerCase().includes(query) ||
        conv.other_user?.email?.toLowerCase().includes(query) ||
        conv.last_message?.toLowerCase().includes(query)
    );
});

const fetchConversations = async () => {
    try {
        loading.value = true;
        const response = await api.get('/api/admin/my-conversations');
        conversations.value = response.data.data || [];
    } catch (error) {
        console.error('Error fetching conversations:', error);
    } finally {
        loading.value = false;
    }
};

const fetchMessages = async (conversationId) => {
    try {
        const response = await api.get(`/api/admin/my-conversations/${conversationId}`);
        messages.value = response.data.data.messages || [];
        activeConversation.value = response.data.data.conversation;
        
        await nextTick();
        scrollToBottom();
        
        // Mark as read
        await api.post(`/api/admin/my-conversations/${conversationId}/read`);
        
        // Update unread count in conversation list
        const conv = conversations.value.find(c => c.id === conversationId);
        if (conv) conv.unread_count = 0;
    } catch (error) {
        console.error('Error fetching messages:', error);
    }
};

const selectConversation = (conversation) => {
    activeConversation.value = conversation;
    fetchMessages(conversation.id);
};

const sendMessage = async () => {
    if (!newMessage.value.trim() || !activeConversation.value || isSending.value) return;

    const messageContent = newMessage.value.trim();
    newMessage.value = '';
    isSending.value = true;

    try {
        const response = await api.post(`/api/admin/my-conversations/${activeConversation.value.id}/messages`, {
            content: messageContent
        });

        messages.value.push(response.data.data);
        await nextTick();
        scrollToBottom();

        // Update last message in sidebar
        const conv = conversations.value.find(c => c.id === activeConversation.value.id);
        if (conv) {
            conv.last_message = messageContent;
            conv.last_message_time = new Date();
        }
    } catch (error) {
        console.error('Error sending message:', error);
        newMessage.value = messageContent; // Restore message on error
    } finally {
        isSending.value = false;
    }
};

const scrollToBottom = () => {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const formatTime = (date) => {
    if (!date) return '';
    const d = new Date(date);
    const now = new Date();
    const diffMs = now - d;
    const diffMins = Math.floor(diffMs / 60000);
    
    if (diffMins < 1) return 'À l\'instant';
    if (diffMins < 60) return `Il y a ${diffMins}m`;
    
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `Il y a ${diffHours}h`;
    
    const diffDays = Math.floor(diffHours / 24);
    if (diffDays < 7) return `Il y a ${diffDays}j`;
    
    return d.toLocaleDateString('fr-FR');
};

const getUserRoleBadge = (role) => {
    const badges = {
        client: { text: 'Client', class: 'bg-blue-100 text-blue-700' },
        provider: { text: 'Prestataire', class: 'bg-purple-100 text-purple-700' },
        admin: { text: 'Admin', class: 'bg-red-100 text-red-700' }
    };
    return badges[role] || { text: role, class: 'bg-gray-100 text-gray-700' };
};

onMounted(() => {
    fetchConversations();
});
</script>

<template>
  <div class="flex flex-col h-full bg-white dark:bg-[#0F172A]">
    <!-- Header -->
    <div class="px-8 py-6 border-b border-slate-200 dark:border-white/5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-black text-slate-900 dark:text-white">💬 Mes Messages</h1>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Conversations directes avec les utilisateurs</p>
        </div>
        <div class="flex items-center gap-2 text-sm">
          <div class="px-3 py-1 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 font-bold">
            {{ conversations.length }} conversation{{ conversations.length > 1 ? 's' : '' }}
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex overflow-hidden">
      <!-- Conversations Sidebar -->
      <div class="w-80 border-r border-slate-200 dark:border-white/5 flex flex-col bg-slate-50 dark:bg-[#1E293B]">
        <!-- Search -->
        <div class="p-4">
          <div class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher..."
              class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-[#0F172A] text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>

        <!-- Conversations List -->
        <div class="flex-1 overflow-y-auto">
          <div v-if="loading" class="flex items-center justify-center py-8">
            <Loader2 class="w-6 h-6 animate-spin text-blue-500" />
          </div>

          <div v-else-if="filteredConversations.length === 0" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
            <MessageSquare class="w-12 h-12 mx-auto mb-3 opacity-30" />
            <p class="text-sm">{{ searchQuery ? 'Aucun résultat' : 'Aucune conversation' }}</p>
          </div>

          <div v-else>
            <button
              v-for="conv in filteredConversations"
              :key="conv.id"
              @click="selectConversation(conv)"
              class="w-full px-4 py-3 flex items-start gap-3 hover:bg-white dark:hover:bg-[#0F172A] transition-colors border-l-4"
              :class="activeConversation?.id === conv.id 
                ? 'bg-white dark:bg-[#0F172A] border-blue-500' 
                : 'border-transparent'"
            >
              <!-- Avatar -->
              <div class="w-11 h-11 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center flex-shrink-0">
                <User class="w-5 h-5 text-slate-500 dark:text-slate-400" />
              </div>

              <!-- Info -->
              <div class="flex-1 min-w-0 text-left">
                <div class="flex items-center justify-between mb-1">
                  <p class="font-bold text-sm text-slate-900 dark:text-white truncate">
                    {{ conv.other_user?.name || 'Utilisateur' }}
                  </p>
                  <span class="text-xs text-slate-500 dark:text-slate-400 ml-2 flex-shrink-0">
                    {{ formatTime(conv.last_message_time) }}
                  </span>
                </div>

                <div class="flex items-center gap-2 mb-1">
                  <span 
                    class="text-xs px-2 py-0.5 rounded-full font-bold"
                    :class="getUserRoleBadge(conv.other_user?.role).class"
                  >
                    {{ getUserRoleBadge(conv.other_user?.role).text }}
                  </span>
                </div>

                <p class="text-xs text-slate-600 dark:text-slate-400 truncate">
                  {{ conv.last_message || 'Aucun message' }}
                </p>
              </div>

              <!-- Unread Badge -->
              <div v-if="conv.unread_count > 0" class="w-5 h-5 rounded-full bg-red-500 text-white text-xs flex items-center justify-center font-bold flex-shrink-0">
                {{ conv.unread_count }}
              </div>
            </button>
          </div>
        </div>
      </div>

      <!-- Chat Area -->
      <div class="flex-1 flex flex-col">
        <!-- No Conversation Selected -->
        <div v-if="!activeConversation" class="flex-1 flex items-center justify-center text-slate-400">
          <div class="text-center">
            <MessageSquare class="w-20 h-20 mx-auto mb-4 opacity-20" />
            <p class="text-lg font-bold">Sélectionnez une conversation</p>
            <p class="text-sm mt-1">Choisissez une conversation pour commencer</p>
          </div>
        </div>

        <!-- Active Conversation -->
        <template v-else>
          <!-- Chat Header -->
          <div class="px-6 py-4 border-b border-slate-200 dark:border-white/5 bg-white dark:bg-[#1E293B]">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center">
                <User class="w-5 h-5 text-slate-500 dark:text-slate-400" />
              </div>
              <div class="flex-1">
                <h3 class="font-black text-slate-900 dark:text-white">
                  {{ activeConversation.sender_id === auth.user.id ? activeConversation.receiver.name : activeConversation.sender.name }}
                </h3>
                <div class="flex items-center gap-2 mt-0.5">
                  <span 
                    class="text-xs px-2 py-0.5 rounded-full font-bold"
                    :class="getUserRoleBadge(activeConversation.sender_id === auth.user.id ? activeConversation.receiver.role : activeConversation.sender.role).class"
                  >
                    {{ getUserRoleBadge(activeConversation.sender_id === auth.user.id ? activeConversation.receiver.role : activeConversation.sender.role).text }}
                  </span>
                  <span class="text-xs text-slate-500 dark:text-slate-400">
                    {{ activeConversation.sender_id === auth.user.id ? activeConversation.receiver.email : activeConversation.sender.email }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Messages Area -->
          <div ref="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-4 bg-slate-50 dark:bg-[#0F172A]">
            <div v-if="messages.length === 0" class="flex items-center justify-center h-full text-slate-400">
              <div class="text-center">
                <MessageSquare class="w-12 h-12 mx-auto mb-2 opacity-20" />
                <p class="text-sm">Aucun message pour le moment</p>
              </div>
            </div>

            <div
              v-for="message in messages"
              :key="message.id"
              class="flex"
              :class="message.sender_id === auth.user.id ? 'justify-end' : 'justify-start'"
            >
              <div
                class="max-w-md px-4 py-3 rounded-2xl"
                :class="message.sender_id === auth.user.id 
                  ? 'bg-blue-600 text-white rounded-br-sm' 
                  : 'bg-white dark:bg-[#1E293B] text-slate-900 dark:text-white border border-slate-200 dark:border-white/10 rounded-bl-sm'"
              >
                <p class="text-sm whitespace-pre-wrap break-words">{{ message.content }}</p>
                <div class="flex items-center gap-1 mt-1">
                  <Clock class="w-3 h-3 opacity-60" />
                  <span class="text-xs opacity-60">{{ formatTime(message.created_at) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Input Area -->
          <div class="p-4 border-t border-slate-200 dark:border-white/5 bg-white dark:bg-[#1E293B]">
            <form @submit.prevent="sendMessage" class="flex items-end gap-3">
              <textarea
                v-model="newMessage"
                @keydown.enter.exact.prevent="sendMessage"
                placeholder="Écrire un message..."
                rows="1"
                class="flex-1 px-4 py-3 rounded-xl border border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-[#0F172A] text-slate-900 dark:text-white resize-none focus:ring-2 focus:ring-blue-500"
                :disabled="isSending"
              ></textarea>
              <button
                type="submit"
                :disabled="!newMessage.trim() || isSending"
                class="px-6 py-3 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
              >
                <Loader2 v-if="isSending" class="w-5 h-5 animate-spin" />
                <Send v-else class="w-5 h-5" />
              </button>
            </form>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>
