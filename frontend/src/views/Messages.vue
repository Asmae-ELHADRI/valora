<script setup>
import { ref, onMounted, nextTick, computed, onUnmounted } from 'vue';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import echo from '../services/echo';
import { useRoute } from 'vue-router';
import { Send, User, MessageCircle, MoreVertical, Search, CheckCheck, ChevronLeft, CheckCircle, Paperclip, X, FileText, Loader2, Play, Mic, Ban, Trash2, StopCircle, Pause } from 'lucide-vue-next';

const auth = useAuthStore();
const route = useRoute();
const conversations = ref([]);
const activeConversation = ref(null);
const messages = ref([]);
const newMessage = ref('');
const loading = ref(true);
const isSending = ref(false);
const messagesContainer = ref(null);
const showSidebar = ref(true);
const fileInput = ref(null);
const selectedFile = ref(null);
const previewUrl = ref(null);
const secureUrls = ref({});

// Audio Recording State
const isRecording = ref(false);
const mediaRecorder = ref(null);
const audioChunks = ref([]);
const recordingDuration = ref(0);
const recordingTimer = ref(null);
const audioBlob = ref(null);
const audioPreviewUrl = ref(null);

// Audio Player State
const playingAudioId = ref(null);
const audioElements = ref({}); // Map of audio elements refs
const onlineUsers = ref([]);
const currentTime = ref(Date.now());

const isOnline = (userId) => {
    return onlineUsers.value.some(u => u.id === userId);
};

const fetchSecureUrl = async (message) => {
    if (!message.attachment_path || message.id.toString().startsWith('temp')) return;
    try {
        const response = await api.get(`/api/messages/attachments/${message.id}`, { responseType: 'blob' });
        const url = URL.createObjectURL(response.data);
        secureUrls.value[message.id] = url;
    } catch (err) {
        console.error(`Error fetching secure attachment for message ${message.id}:`, err);
    }
};

// Toast Notification State
const showToast = ref(false);
const toastMessage = ref('');
const toastType = ref('success'); // 'success' or 'error'

const showSuccessToast = (message) => {
    toastType.value = 'success';
    toastMessage.value = message;
    showToast.value = true;
    setTimeout(() => {
        showToast.value = false;
    }, 3000);
};

const showReportModal = ref(false);
const reportForm = ref({
    reason: 'Comportement inapproprié',
    description: ''
});
const reportLoading = ref(false);
const showMenu = ref(false);

const openReportModal = () => {
    showMenu.value = false;
    showReportModal.value = true;
};

const submitReport = async () => {
    if (!activeConversation.value) return;
    reportLoading.value = true;
    try {
        await api.post('/api/report', {
            reported_id: activeConversation.value.user.id,
            reason: reportForm.value.reason,
            description: reportForm.value.description
        });
        showSuccessToast('Utilisateur signalé et bloqué.');
        showReportModal.value = false;
        reportForm.value.description = '';
        if (activeConversation.value) {
            activeConversation.value.user.has_blocked = true;
        }
    } catch (err) {
        showErrorToast(err.response?.data?.message || 'Erreur lors du signalement.');
    } finally {
        reportLoading.value = false;
    }
};

const showErrorToast = (message) => {
    toastType.value = 'error';
    toastMessage.value = message;
    showToast.value = true;
    setTimeout(() => {
        showToast.value = false;
    }, 4000);
};

const showInfoToast = (message) => {
    toastType.value = 'info';
    toastMessage.value = message;
    showToast.value = true;
    setTimeout(() => {
        showToast.value = false;
    }, 5000);
};

const fetchConversations = async () => {
    try {
        const response = await api.get('/api/messages');
        conversations.value = response.data.map(c => ({
            ...c,
            last_message_date: c.last_message ? new Date(c.last_message.created_at) : new Date(0)
        }));

        if (route.query.user || route.query.userId) {
            const userId = parseInt(route.query.user || route.query.userId);
            const existingConv = conversations.value.find(c => c.user.id === userId);
            if (existingConv) {
                selectConversation(existingConv);
            } else {
                // Fetch basic info and start new conversation
                try {
                    const userRes = await api.get(`/api/users/${userId}/basic`);
                    const newUser = userRes.data;
                    const newConv = {
                        user: newUser,
                        last_message: null,
                        unread_count: 0
                    };
                    activeConversation.value = newConv;
                    if (window.innerWidth < 768) showSidebar.value = false;
                    messages.value = []; // Empty chat
                } catch (e) {
                    console.error('Error fetching user for new chat', e);
                }
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
        messages.value.forEach(msg => {
            if (msg.attachment_path) fetchSecureUrl(msg);
        });
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

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        if (file.size > 20 * 1024 * 1024) {
            alert('Le fichier est trop volumineux (Max 20Mo)');
            return;
        }
        selectedFile.value = file;
        
        // Clear audio if file is selected
        if (audioBlob.value) cancelRecording();

        if (file.type.startsWith('image/')) {
            previewUrl.value = URL.createObjectURL(file);
        } else {
            previewUrl.value = null; // Can add generic file icon preview later
        }
    }
};

const clearFile = () => {
    selectedFile.value = null;
    previewUrl.value = null;
    if (fileInput.value) fileInput.value.value = '';
};

// Audio Functions
const startRecording = async () => {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder.value = new MediaRecorder(stream);
        audioChunks.value = [];
        
        mediaRecorder.value.ondataavailable = (event) => {
            audioChunks.value.push(event.data);
        };
        
        mediaRecorder.value.onstop = () => {
            audioBlob.value = new Blob(audioChunks.value, { type: 'audio/webm' });
            audioPreviewUrl.value = URL.createObjectURL(audioBlob.value);
            
            // Stop all tracks to release microphone
            stream.getTracks().forEach(track => track.stop());
        };
        
        mediaRecorder.value.start();
        isRecording.value = true;
        recordingDuration.value = 0;
        
        recordingTimer.value = setInterval(() => {
            recordingDuration.value++;
        }, 1000);
        
    } catch (err) {
        console.error('Error accessing microphone:', err);
        alert('Impossible d\'accéder au microphone. Veuillez vérifier vos permissions.');
    }
};

const stopRecording = () => {
    if (mediaRecorder.value && isRecording.value) {
        mediaRecorder.value.stop();
        isRecording.value = false;
        clearInterval(recordingTimer.value);
    }
};

const cancelRecording = () => {
    if (mediaRecorder.value && isRecording.value) {
        mediaRecorder.value.stop(); // Will trigger onstop but we clear data after
        // We need to wait a tick for onstop to fire if we want to be clean, 
        // but generally just resetting values is enough if we don't use the blob.
    }
    
    isRecording.value = false;
    audioBlob.value = null;
    audioPreviewUrl.value = null;
    audioChunks.value = [];
    recordingDuration.value = 0;
    isRecording.value = false;
    audioBlob.value = null;
    audioPreviewUrl.value = null;
    audioChunks.value = [];
    recordingDuration.value = 0;
    if (recordingTimer.value) clearInterval(recordingTimer.value);
};

const toggleAudio = (msgId) => {
    const audio = audioElements.value[msgId];
    if (!audio) return;

    if (playingAudioId.value && playingAudioId.value !== msgId) {
        // Stop currently playing
        const data = messages.value.find(m => m.id === playingAudioId.value);
        if (data) data.isPlaying = false;
        if (audioElements.value[playingAudioId.value]) {
            audioElements.value[playingAudioId.value].pause();
            audioElements.value[playingAudioId.value].currentTime = 0;
        }
    }

    const msg = messages.value.find(m => m.id === msgId);
    if (!msg) return;

    if (audio.paused) {
        audio.play();
        msg.isPlaying = true;
        playingAudioId.value = msgId;
    } else {
        audio.pause();
        msg.isPlaying = false;
        playingAudioId.value = null;
    }
};

const onAudioEnded = (msgId) => {
    const msg = messages.value.find(m => m.id === msgId);
    if (msg) msg.isPlaying = false;
    playingAudioId.value = null;
};

const onTimeUpdate = (msgId) => {
    const audio = audioElements.value[msgId];
    const msg = messages.value.find(m => m.id === msgId);
    if (audio && msg) {
        msg.progress = (audio.currentTime / audio.duration) * 100 || 0;
        msg.currentTime = audio.currentTime;
        msg.duration = audio.duration;
    }
};

const onLoadedMetadata = (msgId) => {
    const audio = audioElements.value[msgId];
    const msg = messages.value.find(m => m.id === msgId);
    if (audio && msg) {
        msg.duration = audio.duration;
    }
};

const seekAudio = (msgId, event) => {
    const audio = audioElements.value[msgId];
    if (!audio) return;
    const percent = event.target.value;
    audio.currentTime = (percent / 100) * audio.duration;
};

const formatAudioTime = (seconds) => {
    if (!seconds) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const formatDuration = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const sendMessage = async () => {
    if ((!newMessage.value.trim() && !selectedFile.value && !audioBlob.value) || !activeConversation.value) return;

    const tempMsg = {
        id: 'temp-' + Date.now(),
        content: newMessage.value,
        sender_id: auth.user.id,
        receiver_id: activeConversation.value.user.id,
        created_at: new Date().toISOString(),
        pending: true,
        attachment_path: previewUrl.value || audioPreviewUrl.value, // Local preview
        attachment_type: selectedFile.value ? 'image' : (audioBlob.value ? 'audio' : null)
    };
    
    messages.value.push(tempMsg);
    scrollToBottom();

    const formData = new FormData();
    formData.append('receiver_id', activeConversation.value.user.id);
    formData.append('content', newMessage.value);
    if (selectedFile.value) {
        formData.append('image', selectedFile.value);
    } else if (audioBlob.value) {
        formData.append('attachment', audioBlob.value, 'voice-message.webm');
    }

    // Reset input immediately
    const sentContent = newMessage.value;
    const sentFile = selectedFile.value;
    
    newMessage.value = '';
    
    // Clear attachments but keep temp ref if needed (though we just reset)
    clearFile();
    
    // Reset Audio
    audioBlob.value = null;
    audioPreviewUrl.value = null;
    audioChunks.value = [];
    
    isSending.value = true;

    try {
        const response = await api.post('/api/messages', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        
        const index = messages.value.findIndex(m => m.id === tempMsg.id);
        if (index !== -1) {
            messages.value[index] = response.data;
        }
        
        activeConversation.value.last_message = response.data;
        activeConversation.value.last_message_date = new Date(response.data.created_at);
        conversations.value.sort((a, b) => b.last_message_date - a.last_message_date);
        
        showSuccessToast('Message envoyé !');
    } catch (err) {
        console.error('Error sending message:', err);
        const index = messages.value.findIndex(m => m.id === tempMsg.id);
        if (index !== -1) {
            messages.value[index].error = true;
            messages.value[index].pending = false;
        }
        newMessage.value = sentContent;
        showErrorToast('Erreur lors de l\'envoi du message.');
    } finally {
        isSending.value = false;
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

const formatLastSeen = (date) => {
    if (!date) return 'Jamais vu';
    const now = currentTime.value;
    const seenDate = new Date(date);
    const seen = seenDate.getTime();
    const diffInMinutes = Math.floor((now - seen) / 60000);
    
    if (diffInMinutes < 1) return 'Dernière activité à l\'instant';
    if (diffInMinutes < 60) return `Dernière activité il y a ${diffInMinutes} minutes`;
    
    const day = seenDate.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long' });
    const today = new Date(now).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long' });
    const time = seenDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    
    if (day === today) return `Vu à ${time}`;
    return `Vu le ${day} à ${time}`;
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

const isBlocked = computed(() => {
    if (!activeConversation.value) return false;
    return activeConversation.value.user.has_blocked || activeConversation.value.user.is_blocked_by;
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
    
    // Refresh "Last seen" relative times every minute
    const timer = setInterval(() => {
        currentTime.value = Date.now();
    }, 60000);

    onUnmounted(() => {
        clearInterval(timer);
    });
    
    if (auth.user) {
        echo.join('online')
            .here((users) => {
                onlineUsers.value = users;
            })
            .joining((user) => {
                if (!onlineUsers.value.some(u => u.id === user.id)) {
                    onlineUsers.value.push(user);
                }
            })
            .leaving((user) => {
                onlineUsers.value = onlineUsers.value.filter(u => u.id !== user.id);
            });

        // Subscribe to private chat channel
        echo.private(`chat.${auth.user.id}`)
            .listen('MessageSent', (e) => {
                const newMsg = e.message;
                
                if (newMsg.sender_id !== auth.user.id) {
                    showInfoToast(`${newMsg.sender?.name || 'Contact'} : ${newMsg.content || 'Nouveau fichier reçu'}`);
                }

                // If it's for the active conversation
                if (activeConversation.value && (
                    newMsg.sender_id === activeConversation.value.user.id || 
                    (newMsg.sender_id === auth.user.id && newMsg.receiver_id === activeConversation.value.user.id)
                )) {
                    // Avoid duplicates (already added by sender's AJAX response)
                    const exists = messages.value.some(m => m.id === newMsg.id || (m.id.toString().startsWith('temp') && m.content === newMsg.content));
                    if (!exists) {
                        messages.value.push(newMsg);
                        if (newMsg.attachment_path) fetchSecureUrl(newMsg);
                        scrollToBottom();
                        
                        // Mark as read immediately if it's the active conversation
                        if (newMsg.sender_id === activeConversation.value.user.id) {
                            api.post(`/api/messages/${newMsg.sender_id}/read`).catch(e => console.error('Error marking as read', e));
                        }
                    }
                }

                // Update conversations list summary
                const otherUserId = newMsg.sender_id === auth.user.id ? newMsg.receiver_id : newMsg.sender_id;
                const conv = conversations.value.find(c => c.user.id === otherUserId);
                if (conv) {
                    conv.last_message = newMsg;
                    conv.last_message_date = new Date(newMsg.created_at);
                    if (newMsg.sender_id !== auth.user.id && (!activeConversation.value || activeConversation.value.user.id !== newMsg.sender_id)) {
                         conv.unread_count = (conv.unread_count || 0) + 1;
                    }
                    conversations.value.sort((a, b) => b.last_message_date - a.last_message_date);
                } else {
                    fetchConversations();
                }
            });
    }
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
                            <div class="flex items-center space-x-2">
                                <h3 class="font-bold text-gray-900 group-hover:text-blue-600 transition">{{ conv.user.name }}</h3>
                                <div class="flex items-center" v-if="isOnline(conv.user.id)">
                                    <span class="w-2 h-2 bg-green-500 rounded-full border border-white shadow-sm animate-pulse"></span>
                                    <span class="text-[8px] font-black text-green-500 uppercase ml-1 tracking-tighter">En ligne</span>
                                </div>
                                <span v-else class="w-2 h-2 bg-gray-300 rounded-full border border-white"></span>
                            </div>
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
            class="flex-grow bg-white border border-gray-100 rounded-[2.5rem] shadow-sm flex flex-col overflow-hidden relative"
            :class="{ 'hidden md:flex': showSidebar, 'flex': !showSidebar }"
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
                              <p v-if="isOnline(activeConversation.user.id)" class="text-[10px] font-bold text-green-500 mt-1 uppercase tracking-widest flex items-center">
                                 <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1 animate-pulse"></span> En ligne
                              </p>
                              <p v-else class="text-[10px] font-bold text-gray-400 mt-1 uppercase tracking-widest flex items-center">
                                 <span class="w-1.5 h-1.5 bg-gray-300 rounded-full mr-1"></span> {{ formatLastSeen(activeConversation.user.last_seen_at) }}
                              </p>
                          </div>
                     </div>
                    
                    <div class="relative">
                        <button @click="showMenu = !showMenu" class="p-3 rounded-2xl text-gray-400 hover:bg-gray-50 transition">
                            <MoreVertical class="w-5 h-5" />
                        </button>
                        <!-- Dropdown Menu -->
                        <div v-if="showMenu" class="absolute right-0 top-full mt-2 w-48 bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden z-50">
                            <button @click="openReportModal" class="w-full text-left px-5 py-3 hover:bg-red-50 text-red-500 font-bold text-xs uppercase tracking-wider flex items-center transition-colors">
                                <Ban class="w-4 h-4 mr-2" />
                                Signaler
                            </button>
                        </div>
                    </div>
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
                                <div v-if="msg.attachment_path" class="mb-3 rounded-xl overflow-hidden min-w-[200px]">
                                    <!-- Image Rendering -->
                                    <template v-if="msg.attachment_type === 'image' || (msg.id.toString().startsWith('temp') && msg.attachment_path && !msg.attachment_path.includes('video'))">
                                        <img :src="msg.id.toString().startsWith('temp') ? msg.attachment_path : secureUrls[msg.id]" 
                                             class="w-full h-auto object-cover max-h-60 cursor-pointer hover:opacity-90 transition" 
                                             alt="Attachment">
                                        <div v-if="!msg.id.toString().startsWith('temp') && !secureUrls[msg.id]" class="p-4 bg-gray-50 flex items-center justify-center">
                                            <Loader2 class="w-5 h-5 animate-spin text-blue-600" />
                                        </div>
                                    </template>
                                    
                                    <!-- Video Rendering -->
                                    <template v-else-if="msg.attachment_type === 'video'">
                                        <video v-if="secureUrls[msg.id]" controls class="w-full h-auto max-h-60">
                                            <source :src="secureUrls[msg.id]" type="video/mp4">
                                            Votre navigateur ne supporte pas la lecture de vidéos.
                                        </video>
                                        <div v-else class="p-4 bg-gray-50 flex items-center justify-center">
                                            <Loader2 class="w-5 h-5 animate-spin text-blue-600" />
                                        </div>
                                    </template>

                                    <!-- Audio Rendering -->
                                    <template v-else-if="msg.attachment_type === 'audio'">
                                        <div class="p-4 bg-opacity-10 rounded-2xl min-w-[260px] flex items-center gap-4 border"
                                             :class="msg.sender_id === auth.user.id 
                                                ? 'bg-blue-800 border-blue-500/30' 
                                                : 'bg-gray-100 border-gray-200'"
                                        >
                                            <button @click="toggleAudio(msg.id)" 
                                                class="w-12 h-12 rounded-full flex items-center justify-center transition-all duration-300 shadow-md transform hover:scale-105"
                                                :class="msg.sender_id === auth.user.id ? 'bg-white text-blue-600' : 'bg-blue-600 text-white'"
                                            >
                                                <Pause v-if="msg.isPlaying" class="w-5 h-5 fill-current" />
                                                <Play v-else class="w-5 h-5 fill-current ml-1" />
                                            </button>
                                            
                                            <div class="flex-grow space-y-2">
                                                 <input 
                                                    type="range" 
                                                    min="0" 
                                                    max="100" 
                                                    :value="msg.progress || 0" 
                                                    @input="seekAudio(msg.id, $event)"
                                                    class="w-full h-1.5 rounded-full appearance-none cursor-pointer"
                                                    :style="{ 
                                                        background: `linear-gradient(to right, ${msg.sender_id === auth.user.id ? 'white' : '#2563eb'} 0%, ${msg.sender_id === auth.user.id ? 'white' : '#2563eb'} ${msg.progress || 0}%, ${msg.sender_id === auth.user.id ? 'rgba(255,255,255,0.3)' : '#e2e8f0'} ${msg.progress || 0}%, ${msg.sender_id === auth.user.id ? 'rgba(255,255,255,0.3)' : '#e2e8f0'} 100%)` 
                                                    }"
                                                />
                                                <div class="flex justify-between text-[10px] font-bold uppercase tracking-wider"
                                                     :class="msg.sender_id === auth.user.id ? 'text-blue-100' : 'text-gray-400'"
                                                >
                                                    <span>{{ formatAudioTime(msg.currentTime) }}</span>
                                                    <span>{{ formatAudioTime(msg.duration) }}</span>
                                                </div>
                                            </div>

                                            <audio 
                                                :ref="el => { if(el) audioElements[msg.id] = el }"
                                                class="hidden"
                                                @ended="onAudioEnded(msg.id)"
                                                @timeupdate="onTimeUpdate(msg.id)"
                                                @loadedmetadata="onLoadedMetadata(msg.id)"
                                            >
                                                <source :src="msg.id.toString().startsWith('temp') ? msg.attachment_path : secureUrls[msg.id]" type="audio/webm">
                                            </audio>
                                        </div>
                                    </template>
                                    <!-- Generic File Rendering -->
                                    <template v-else-if="!msg.id.toString().startsWith('temp')">
                                        <div class="p-4 bg-gray-50/50 rounded-xl flex items-center space-x-3 border border-gray-100">
                                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm">
                                                <FileText class="w-5 h-5 text-blue-600" />
                                            </div>
                                            <div class="flex-grow min-w-0">
                                                <p class="text-xs font-bold text-gray-700 truncate">{{ msg.attachment_path.split('/').pop() }}</p>
                                                <p class="text-[10px] text-gray-400 uppercase font-black">{{ msg.attachment_type || 'Fichier' }}</p>
                                            </div>
                                            <a v-if="secureUrls[msg.id]" :href="secureUrls[msg.id]" 
                                               :download="msg.attachment_path.split('/').pop()"
                                               class="p-2 bg-white rounded-lg text-blue-600 hover:bg-blue-50 transition border border-gray-100">
                                                <Play class="w-4 h-4" />
                                            </a>
                                            <div v-else class="p-2">
                                                <Loader2 class="w-4 h-4 animate-spin text-blue-600" />
                                            </div>
                                        </div>
                                    </template>
                                    
                                    <!-- Temp Generic File Preview -->
                                    <template v-else>
                                         <div class="p-4 bg-gray-50/50 rounded-xl flex items-center space-x-3">
                                            <FileText class="w-5 h-5 text-gray-400" />
                                            <span class="text-xs font-bold text-gray-500">Chargement du fichier...</span>
                                         </div>
                                    </template>
                                </div>
                                <p v-if="msg.content" class="text-sm font-medium leading-relaxed">{{ msg.content }}</p>
                                <div 
                                    :class="msg.sender_id === auth.user.id ? 'text-blue-100' : 'text-gray-400'"
                                    class="text-[9px] font-bold mt-2 flex items-center justify-end space-x-1 uppercase tracking-tighter"
                                >
                                    <span v-if="msg.error" class="text-red-300 mr-2 font-black">Échec de l'envoi</span>
                                    <span>{{ formatTime(msg.created_at) }}</span>
                                    <CheckCheck v-if="msg.sender_id === auth.user.id && !msg.pending && !msg.error" class="w-3 h-3" />
                                    <Loader2 v-if="msg.pending" class="w-3 h-3 animate-spin" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="p-6 bg-white border-t border-gray-100">
                    <div v-if="isBlocked" class="p-4 bg-gray-50 rounded-2xl text-center border border-gray-100">
                        <p class="text-gray-500 font-bold text-sm flex items-center justify-center">
                            <Ban class="w-5 h-5 mr-2 text-red-500" />
                            Discussion bloquée. Vous ne pouvez plus échanger avec cet utilisateur.
                        </p>
                    </div>
                    <div v-else>
                        <div v-if="selectedFile" class="mb-4 relative inline-block">
                        <div class="relative rounded-2xl overflow-hidden h-24 w-24 border-2 border-blue-100 shadow-xl shadow-blue-50">
                            <img v-if="previewUrl" :src="previewUrl" class="w-full h-full object-cover">
                            <div v-else class="w-full h-full bg-blue-50 flex flex-col items-center justify-center p-2 text-center">
                                <FileText class="w-8 h-8 text-blue-400 mb-1" />
                                <span class="text-[8px] font-black text-blue-600 uppercase truncate w-full">{{ selectedFile.name }}</span>
                            </div>
                            <button @click="clearFile" class="absolute top-1 right-1 bg-white/90 text-red-500 rounded-full p-1 hover:bg-red-500 hover:text-white transition shadow-sm">
                                <X class="w-3 h-3" />
                            </button>
                        </div>
                    </div>
                    <form @submit.prevent="sendMessage" class="flex items-center space-x-3">
                         <!-- Audio Recording Mode -->
                        <div v-if="isRecording" class="flex-grow flex items-center px-2 py-2 rounded-2xl relative overflow-hidden transition-all duration-300">
                             <!-- Glassmorphism Background -->
                            <div class="absolute inset-0 bg-red-500/10 backdrop-blur-md border border-red-500/20 rounded-2xl z-0"></div>
                            
                            <!-- Animated Waveform Background -->
                            <div class="absolute inset-0 flex items-center justify-center gap-1 opacity-20 z-0 px-20">
                                <div class="w-1 h-3 bg-red-500 rounded-full animate-wave" style="animation-duration: 0.5s"></div>
                                <div class="w-1 h-5 bg-red-500 rounded-full animate-wave" style="animation-duration: 0.7s"></div>
                                <div class="w-1 h-8 bg-red-500 rounded-full animate-wave" style="animation-duration: 0.4s"></div>
                                <div class="w-1 h-4 bg-red-500 rounded-full animate-wave" style="animation-duration: 0.6s"></div>
                                <div class="w-1 h-6 bg-red-500 rounded-full animate-wave" style="animation-duration: 0.8s"></div>
                                <div class="w-1 h-3 bg-red-500 rounded-full animate-wave" style="animation-duration: 0.5s"></div>
                            </div>

                            <div class="relative z-10 flex items-center w-full pl-4">
                                <span class="w-3 h-3 bg-red-500 rounded-full mr-3 animate-pulse shadow-[0_0_10px_rgba(239,68,68,0.6)]"></span>
                                <span class="font-bold font-mono text-red-600 mr-4 tracking-widest text-lg">{{ formatDuration(recordingDuration) }}</span>
                                <span class="text-xs font-bold text-red-500 uppercase tracking-widest mr-auto">Enregistrement...</span>
                                
                                <button @click.prevent="cancelRecording" type="button" class="p-3 hover:bg-white/50 text-red-400 hover:text-red-500 rounded-xl mr-2 transition backdrop-blur-sm" title="Annuler">
                                    <Trash2 class="w-6 h-6" />
                                </button>
                                <button @click.prevent="stopRecording" type="button" class="p-3 bg-red-500 text-white rounded-xl hover:bg-red-600 transition shadow-lg shadow-red-200 transform active:scale-95" title="Terminer">
                                    <Send class="w-6 h-6" />
                                </button>
                            </div>
                        </div>

                        <!-- Audio Preview Mode -->
                        <div v-else-if="audioBlob" class="flex-grow flex items-center px-4 py-3 rounded-2xl border border-blue-100 bg-blue-50">
                             <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center mr-3 shadow-sm">
                                <Mic class="w-5 h-5 text-blue-500" />
                             </div>
                             <audio controls :src="audioPreviewUrl" class="flex-grow h-8 mr-4 custom-audio"></audio>
                             <button @click="cancelRecording" type="button" class="p-2 text-gray-400 hover:text-red-500 transition">
                                <X class="w-5 h-5" />
                             </button>
                        </div>

                        <!-- Normal Input Mode -->

                        <template v-else>
                            <button 
                                type="button"
                                @click="fileInput.click()"
                                class="p-4 bg-gray-50 text-gray-400 rounded-2xl hover:bg-gray-100 hover:text-gray-600 transition"
                            >
                                <Paperclip class="w-6 h-6" />
                            </button>
                            <input 
                                ref="fileInput"
                                type="file" 
                                accept="image/*,video/*,application/pdf,.doc,.docx" 
                                class="hidden"
                                @change="handleFileSelect"
                            >
                            <input 
                                v-model="newMessage"
                                type="text" 
                                placeholder="Tapez un message..." 
                                class="flex-grow px-6 py-4 rounded-2xl border border-gray-100 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition font-medium text-sm"
                            >
                            
                            <!-- Record Button (Visible if no text) -->
                            <button 
                                v-if="!newMessage.trim() && !selectedFile"
                                type="button"
                                @click="startRecording"
                                class="p-4 bg-gray-50 text-gray-500 rounded-2xl hover:bg-gray-100 hover:text-gray-900 transition"
                            >
                                <Mic class="w-6 h-6" />
                            </button>
                        </template>

                        <button 
                            type="submit" 
                            :disabled="(!newMessage.trim() && !selectedFile && !audioBlob) || isSending || isRecording"
                            class="p-4 bg-blue-600 text-white rounded-2xl hover:bg-blue-700 transition disabled:opacity-50 disabled:grayscale shadow-xl shadow-blue-100 active:scale-95 flex items-center justify-center min-w-[56px]"
                        >
                            <Loader2 v-if="isSending" class="w-6 h-6 animate-spin" />
                            <Send v-else class="w-6 h-6" />
                        </button>
                    </form>
                    </div>
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

            <!-- Report Modal -->
        <div v-if="showReportModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showReportModal = false"></div>
            <div class="relative bg-white w-full max-w-md rounded-[32px] shadow-2xl p-8 border border-slate-100 animate-in fade-in zoom-in duration-300">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-black text-slate-900 flex items-center">
                        <Ban class="w-6 h-6 mr-3 text-red-500" />
                        Signaler
                    </h3>
                    <button @click="showReportModal = false" class="p-2 hover:bg-slate-50 rounded-full text-slate-400 hover:text-slate-600 transition">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">Motif du signalement</label>
                        <select v-model="reportForm.reason" class="w-full px-4 py-3 bg-slate-50 rounded-xl border border-slate-200 focus:ring-2 focus:ring-red-500/20 outline-none text-slate-900 font-bold text-sm appearance-none">
                            <option>Comportement inapproprié</option>
                            <option>Spam ou fraude</option>
                            <option>Harcèlement</option>
                            <option>Autre problème</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">Description (Optionnel)</label>
                        <textarea v-model="reportForm.description" rows="4" placeholder="Décrivez le problème..." class="w-full px-4 py-3 bg-slate-50 rounded-xl border border-slate-200 focus:ring-2 focus:ring-red-500/20 outline-none text-slate-900 font-medium text-sm resize-none"></textarea>
                    </div>
                </div>

                <div class="flex space-x-4 mt-8">
                    <button @click="showReportModal = false" class="flex-1 py-4 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition">Annuler</button>
                    <button @click="submitReport" :disabled="reportLoading" class="flex-1 py-4 bg-red-500 text-white font-bold rounded-xl hover:bg-red-600 transition shadow-lg shadow-red-500/30 flex justify-center items-center">
                        <Loader2 v-if="reportLoading" class="w-5 h-5 animate-spin mr-2" />
                        Signaler
                    </button>
                </div>
            </div>
        </div>

    <Transition name="toast">
        <div v-if="showToast" 
             :class="{
                'shadow-green-100 border-green-100': toastType === 'success',
                'shadow-red-100 border-red-100': toastType === 'error',
                'shadow-blue-100 border-blue-100': toastType === 'info'
             }"
             class="fixed top-6 left-1/2 transform -translate-x-1/2 z-[200] flex items-center bg-white px-6 py-4 rounded-2xl shadow-2xl border min-w-[300px] max-w-[90vw]"
        >
            <div :class="{
                'bg-green-100': toastType === 'success',
                'bg-red-100': toastType === 'error',
                'bg-blue-100': toastType === 'info'
            }"
                 class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center mr-4">
                <CheckCircle v-if="toastType === 'success'" class="w-6 h-6 text-green-600" />
                <X v-else-if="toastType === 'error'" class="w-6 h-6 text-red-600" />
                <MessageSquare v-else class="w-6 h-6 text-blue-600" />
            </div>
            <div class="min-w-0">
                <h4 class="font-black text-gray-900 text-sm">
                    {{ toastType === 'success' ? 'Succès !' : (toastType === 'error' ? 'Erreur' : 'Nouveau message') }}
                </h4>
                <p class="text-xs text-gray-500 font-medium truncate">{{ toastMessage }}</p>
            </div>
        </div>
    </Transition>
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

@keyframes wave {
    0%, 100% { transform: scaleY(0.5); opacity: 0.5; }
    50% { transform: scaleY(1.2); opacity: 1; }
}
.animate-wave {
    animation-name: wave;
    animation-iteration-count: infinite;
    animation-timing-function: ease-in-out;
}

/* Range input styling */
input[type=range]::-webkit-slider-thumb {
  -webkit-appearance: none;
  height: 12px;
  width: 12px;
  border-radius: 50%;
  background: white;
  margin-top: -3px; /* You need to specify a margin in Chrome, but in Firefox and IE it is automatic */
  box-shadow: 0 1px 3px rgba(0,0,0,0.3);
  cursor: pointer;
}

/* Toast Animations */
.toast-enter-active,
.toast-leave-active {
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translate(-50%, -20px);
}
</style>
