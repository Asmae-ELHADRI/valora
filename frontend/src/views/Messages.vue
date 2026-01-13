<script setup>
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import { 
  Send, User, Search as SearchIcon, 
  MoreVertical, Phone, Video, Info,
  Check, CheckCheck, Loader2, ArrowLeft, MessageSquare,
  ShieldAlert, Ban, MoreHorizontal, AlertTriangle
} from 'lucide-vue-next';

const auth = useAuthStore();
const conversations = ref([]);
const activeConversation = ref(null);
const messages = ref([]);
const newMessage = ref('');
const loadingConversations = ref(true);
const loadingMessages = ref(false);
const messageContainer = ref(null);

const showReportModal = ref(false);
const showBlockModal = ref(false);
const reportForm = ref({ reason: '', description: '' });
const reporting = ref(false);
const blocking = ref(false);
const showActions = ref(false);

let pollingInterval = null;

const fetchConversations = async (silent = false) => {
  if (!silent) loadingConversations.value = true;
  try {
    const response = await api.get('/api/messages');
    conversations.value = response.data;
  } catch (err) {
    console.error('Erreur chargement conversations:', err);
  } finally {
    if (!silent) loadingConversations.value = false;
  }
};

const fetchMessages = async (userId, silent = false) => {
  if (!silent) loadingMessages.value = true;
  try {
    const response = await api.get(`/api/messages/${userId}`);
    messages.value = response.data;
    if (!silent) await scrollToBottom();
  } catch (err) {
    console.error('Erreur chargement messages:', err);
  } finally {
    if (!silent) loadingMessages.value = false;
  }
};

const selectConversation = async (conv) => {
  activeConversation.value = conv;
  await fetchMessages(conv.user.id);
};

const sendMessage = async () => {
  if (!newMessage.value.trim() || !activeConversation.value) return;
  if (activeConversation.value.user.is_blocked_by || activeConversation.value.user.has_blocked) return;

  const content = newMessage.value;
  newMessage.value = '';

  try {
    const response = await api.post('/api/messages', {
      receiver_id: activeConversation.value.user.id,
      content: content
    });
    messages.value.push(response.data);
    await scrollToBottom();
    fetchConversations(true);
  } catch (err) {
    console.error('Erreur envoi message:', err);
    if (err.response?.status === 403) {
      alert('Action impossible : vous avez bloqué cet utilisateur ou vice versa.');
    }
  }
};

const submitReport = async () => {
  if (!reportForm.value.reason) return;
  reporting.value = true;
  try {
    await api.post('/api/report', {
      reported_id: activeConversation.value.user.id,
      reason: `${reportForm.value.reason}: ${reportForm.value.description}`
    });
    alert('Signalement envoyé.');
    showReportModal.value = false;
    reportForm.value = { reason: '', description: '' };
  } catch (err) {
    alert('Erreur lors du signalement');
  } finally {
    reporting.value = false;
  }
};

const toggleBlock = async () => {
  blocking.value = true;
  try {
    if (activeConversation.value.user.has_blocked) {
      await api.delete(`/api/block/${activeConversation.value.user.id}`);
      activeConversation.value.user.has_blocked = false;
    } else {
      await api.post('/api/block', { blocked_id: activeConversation.value.user.id });
      activeConversation.value.user.has_blocked = true;
    }
    showBlockModal.value = false;
    fetchConversations(true);
  } catch (err) {
    alert('Erreur lors de l\'action');
  } finally {
    blocking.value = false;
  }
};

const scrollToBottom = async () => {
  await nextTick();
  if (messageContainer.value) {
    messageContainer.value.scrollTop = messageContainer.value.scrollHeight;
  }
};

const formatDate = (date) => {
  const d = new Date(date);
  const now = new Date();
  if (d.toDateString() === now.toDateString()) {
    return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
  }
  return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
};

onMounted(() => {
  fetchConversations();
  pollingInterval = setInterval(() => {
    fetchConversations(true);
    if (activeConversation.value) {
      fetchMessages(activeConversation.value.user.id, true);
    }
  }, 5000);
});

onUnmounted(() => {
  clearInterval(pollingInterval);
});

const getInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase();
};
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 h-[calc(100vh-140px)]">
    <div class="bg-white rounded-[32px] shadow-sm border border-gray-100 overflow-hidden flex h-full">
      
      <!-- Conversations Sidebar -->
      <div 
        :class="activeConversation ? 'hidden md:flex' : 'flex'"
        class="w-full md:w-80 lg:w-96 border-r border-gray-100 flex-col"
      >
        <div class="p-6 border-b border-gray-100">
          <h2 class="text-xl font-bold text-gray-900">Messages</h2>
          <div class="mt-4 relative">
            <SearchIcon class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" />
            <input 
              type="text" 
              placeholder="Rechercher un client..." 
              class="w-full pl-10 pr-4 py-2 bg-gray-50 border-none rounded-xl text-sm focus:ring-2 focus:ring-blue-500 transition"
            >
          </div>
        </div>

        <div class="flex-grow overflow-y-auto">
          <div v-if="loadingConversations" class="p-8 flex justify-center">
            <Loader2 class="w-8 h-8 text-blue-600 animate-spin" />
          </div>
          
          <div v-else-if="conversations.length === 0" class="p-10 text-center">
            <p class="text-gray-400 text-sm">Aucune conversation</p>
          </div>

          <div v-else class="divide-y divide-gray-50">
            <button 
              v-for="conv in conversations" 
              :key="conv.user.id"
              @click="selectConversation(conv)"
              :class="activeConversation?.user.id === conv.user.id ? 'bg-blue-50' : 'hover:bg-gray-50'"
              class="w-full p-4 flex items-center space-x-4 transition text-left relative"
            >
              <div class="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center text-blue-700 font-bold shrink-0">
                {{ getInitials(conv.user.name) }}
              </div>
              <div class="flex-grow min-w-0">
                <div class="flex justify-between items-baseline mb-1">
                  <h3 class="font-bold text-gray-900 truncate">{{ conv.user.name }}</h3>
                  <span class="text-[10px] text-gray-400 font-medium">{{ formatDate(conv.last_message?.created_at) }}</span>
                </div>
                <p class="text-xs text-gray-500 truncate">{{ conv.last_message?.content || 'Démarrez la conversation' }}</p>
              </div>
              <div v-if="conv.unread_count > 0" class="absolute right-4 bottom-4 w-5 h-5 bg-blue-600 rounded-full flex items-center justify-center text-[10px] text-white font-bold">
                {{ conv.unread_count }}
              </div>
            </button>
          </div>
        </div>
      </div>

      <!-- Chat Area -->
      <div 
        :class="activeConversation ? 'flex' : 'hidden md:flex'"
        class="flex-grow flex-col bg-gray-50/30"
      >
        <template v-if="activeConversation">
          <!-- Chat Header -->
          <div class="bg-white p-4 md:p-6 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <button @click="activeConversation = null" class="md:hidden p-2 hover:bg-gray-100 rounded-xl">
                <ArrowLeft class="w-5 h-5 text-gray-600" />
              </button>
              <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center text-blue-700 font-bold">
                {{ getInitials(activeConversation.user.name) }}
              </div>
              <div>
                <h3 class="font-bold text-gray-900">{{ activeConversation.user.name }}</h3>
                <p class="text-[10px] text-green-500 font-bold uppercase tracking-wider">En ligne</p>
              </div>
            </div>
            <div class="flex items-center space-x-2 relative">
              <button @click="showActions = !showActions" class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition">
                <MoreHorizontal class="w-5 h-5" />
              </button>
              
              <div v-if="showActions" class="absolute top-full right-0 mt-2 w-48 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 z-20">
                <button @click="showReportModal = true; showActions = false" class="w-full px-4 py-2 text-left text-sm text-orange-600 hover:bg-orange-50 font-bold flex items-center">
                  <ShieldAlert class="w-4 h-4 mr-2" /> Signaler
                </button>
                <button @click="showBlockModal = true; showActions = false" class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 font-bold flex items-center">
                  <Ban class="w-4 h-4 mr-2" /> {{ activeConversation.user.has_blocked ? 'Débloquer' : 'Bloquer' }}
                </button>
              </div>
            </div>
          </div>

          <!-- Messages Container -->
          <div 
            ref="messageContainer"
            class="flex-grow overflow-y-auto p-6 space-y-4"
          >
            <div 
              v-for="msg in messages" 
              :key="msg.id"
              :class="msg.sender_id === auth.user.id ? 'justify-end' : 'justify-start'"
              class="flex"
            >
              <div 
                :class="msg.sender_id === auth.user.id ? 'bg-blue-600 text-white rounded-tr-none' : 'bg-white text-gray-900 rounded-tl-none border border-gray-100 shadow-sm'"
                class="max-w-[80%] md:max-w-[70%] p-4 rounded-3xl"
              >
                <p class="text-sm leading-relaxed">{{ msg.content }}</p>
                <div 
                  :class="msg.sender_id === auth.user.id ? 'text-blue-100' : 'text-gray-400'"
                  class="text-[10px] mt-2 flex items-center justify-end space-x-1"
                >
                  <span>{{ formatDate(msg.created_at) }}</span>
                  <template v-if="msg.sender_id === auth.user.id">
                    <CheckCheck v-if="msg.read_at" class="w-3 h-3" />
                    <Check v-else class="w-3 h-3" />
                  </template>
                </div>
              </div>
            </div>
          </div>

          <!-- Input Area -->
          <div v-if="!activeConversation.user.is_blocked_by && !activeConversation.user.has_blocked" class="p-4 md:p-6 bg-white border-t border-gray-100">
            <form @submit.prevent="sendMessage" class="flex items-center space-x-4">
              <div class="flex-grow relative">
                <input 
                  v-model="newMessage"
                  type="text" 
                  placeholder="Écrivez votre message ici..." 
                  class="w-full px-5 py-4 bg-gray-50 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition"
                >
              </div>
              <button 
                type="submit"
                :disabled="!newMessage.trim()"
                class="bg-blue-600 text-white p-4 rounded-2xl shadow-lg shadow-blue-100 hover:bg-blue-700 transition disabled:opacity-50"
              >
                <Send class="w-5 h-5" />
              </button>
            </form>
          </div>
          <div v-else class="p-6 bg-gray-100/50 border-t border-gray-100 text-center">
            <p class="text-sm font-bold text-gray-400 flex items-center justify-center">
              <Ban class="w-4 h-4 mr-2" />
              {{ activeConversation.user.has_blocked ? 'Vous avez bloqué cet utilisateur.' : 'Cet utilisateur vous a bloqué.' }}
            </p>
          </div>
        </template>

        <div v-else class="flex-grow flex flex-col items-center justify-center p-12 text-center">
          <div class="w-24 h-24 bg-blue-50 rounded-[32px] flex items-center justify-center text-blue-600 mb-6">
            <MessageSquare class="w-12 h-12" />
          </div>
          <h3 class="text-xl font-bold text-gray-900">Votre messagerie</h3>
          <p class="text-gray-500 mt-2 max-w-sm">Sélectionnez une conversation pour commencer à échanger avec vos clients et clarifier les détails de vos missions.</p>
        </div>
      </div>

    </div>

    <!-- Report Modal (Same as search) -->
    <div v-if="showReportModal" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showReportModal = false"></div>
      <div class="relative bg-white w-full max-w-lg rounded-[40px] shadow-2xl p-10 animate-in fade-in zoom-in duration-300">
        <div class="text-center mb-8">
          <div class="w-16 h-16 bg-orange-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-orange-600">
            <ShieldAlert class="w-8 h-8" />
          </div>
          <h3 class="text-2xl font-black text-gray-900">Signaler un problème</h3>
          <p class="text-sm text-gray-500 mt-2">Votre signalement sera examiné par nos modérateurs.</p>
        </div>
        <div class="space-y-6">
          <select v-model="reportForm.reason" class="w-full px-5 py-4 rounded-2xl border border-gray-100 bg-gray-50 outline-none font-bold text-gray-700">
            <option value="" disabled>Raison du signalement</option>
            <option value="scam">Arnaque</option>
            <option value="harassment">Harcèlement</option>
            <option value="unprofessional">Non professionnel</option>
            <option value="other">Autre</option>
          </select>
          <textarea v-model="reportForm.description" rows="3" placeholder="Détails (facultatif)" class="w-full px-5 py-4 rounded-2xl border border-gray-100 bg-gray-50 outline-none"></textarea>
          <div class="flex gap-4">
            <button @click="showReportModal = false" class="flex-grow py-4 rounded-2xl font-bold text-gray-500">Annuler</button>
            <button @click="submitReport" :disabled="reporting || !reportForm.reason" class="flex-grow py-4 rounded-2xl font-bold bg-orange-600 text-white">Signaler</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Block Modal -->
    <div v-if="showBlockModal" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showBlockModal = false"></div>
      <div class="relative bg-white w-full max-w-sm rounded-[40px] shadow-2xl p-10 animate-in fade-in zoom-in duration-300 text-center">
        <div class="w-16 h-16 bg-red-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-red-600">
          <Ban class="w-8 h-8" />
        </div>
        <h3 class="text-2xl font-black text-gray-900">{{ activeConversation.user.has_blocked ? 'Débloquer' : 'Bloquer' }} {{ activeConversation.user.name }} ?</h3>
        <p class="text-sm text-gray-500 mt-4 mb-8">
          {{ activeConversation.user.has_blocked ? 'Vous pourrez à nouveau échanger.' : 'Vous ne recevrez plus de messages de sa part.' }}
        </p>
        <div class="space-y-3">
          <button @click="toggleBlock" :disabled="blocking" :class="activeConversation.user.has_blocked ? 'bg-blue-600' : 'bg-red-600'" class="w-full py-4 rounded-2xl font-bold text-white hover:opacity-90 transition">
             {{ activeConversation.user.has_blocked ? 'Débloquer' : 'Bloquer' }}
          </button>
          <button @click="showBlockModal = false" class="w-full py-4 rounded-2xl font-bold text-gray-500">Annuler</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Custom scrollbar for better look */
::-webkit-scrollbar {
  width: 5px;
}
::-webkit-scrollbar-track {
  background: transparent;
}
::-webkit-scrollbar-thumb {
  background: #e5e7eb;
  border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
  background: #d1d5db;
}
</style>
