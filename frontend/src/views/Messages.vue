<script setup>
import { ref, onMounted, nextTick, computed, onUnmounted } from 'vue';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import echo from '../services/echo';
import { useRoute } from 'vue-router';
import { Send, User, MessageCircle, MessageSquare, MoreVertical, Search, CheckCheck, ChevronLeft, CheckCircle, Paperclip, X, FileText, Loader2, Play, Mic, Ban, Trash2, StopCircle, Pause, Flag, ShieldAlert } from 'lucide-vue-next';

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

// Reporting State
const showReportModal = ref(false);
const reportReason = ref('');
const isReporting = ref(false);

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

const deleteMessage = async (msgId) => {
    if (!confirm('Voulez-vous supprimer ce message ?')) return;
    try {
        await api.delete(`/api/messages/${msgId}`);
        messages.value = messages.value.filter(m => m.id !== msgId);
        
        // Sync conversation preview if it was the last message
        if (activeConversation.value?.last_message?.id === msgId) {
            const nextLast = messages.value[messages.value.length - 1] || null;
            activeConversation.value.last_message = nextLast;
            const conv = conversations.value.find(c => c.user.id === activeConversation.value.user.id);
            if (conv) {
                conv.last_message = nextLast;
                if (nextLast) conv.last_message_date = new Date(nextLast.created_at);
            }
        }
    } catch (err) {
        showErrorToast('Erreur lors de la suppression');
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
        // but generally just resetting values is enough if we don't use the column.
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
<div class="messages-page-wrapper bg-[#F8FAFC] min-h-screen">
    <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-8 h-[calc(100vh-40px)] flex gap-6 overflow-hidden">
        
        <!-- Sidebar - Left Pane -->
        <div 
            v-show="showSidebar"
            class="w-full md:w-[380px] bg-white rounded-[32px] shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex flex-col overflow-hidden transition-all duration-300 border border-slate-100"
            :class="{ 'hidden md:flex': !showSidebar }"
        >
            <div class="p-8 pb-4">
                <h2 class="text-[28px] font-black text-slate-900 flex items-center tracking-tight">
                    <MessageCircle class="w-8 h-8 mr-3 text-blue-600" />
                    Messages
                </h2>
            </div>
            
            <div class="grow overflow-y-auto px-4 pb-8">
                <div v-if="loading" class="p-10 flex justify-center">
                     <Loader2 class="w-8 h-8 text-blue-600 animate-spin" />
                </div>
                <div v-else-if="conversations.length === 0" class="p-10 text-center">
                    <p class="text-slate-400 font-bold">Aucune conversation</p>
                </div>
                <div v-else class="space-y-1 mt-4">
                    <div 
                        v-for="conv in conversations" 
                        :key="conv.user.id"
                        @click="selectConversation(conv)"
                        :class="activeConversation?.user.id === conv.user.id 
                            ? 'bg-white border-l-[3px] border-blue-600 shadow-[0_4px_20px_rgba(37,99,235,0.08)]' 
                            : 'hover:bg-slate-50 border-l-[3px] border-transparent'"
                        class="p-5 cursor-pointer transition-all duration-200 relative group rounded-r-2xl"
                    >
                        <div class="flex justify-between items-start mb-1">
                            <h3 class="font-bold text-[15px] text-slate-900 truncate pr-2">{{ conv.user.name }}</h3>
                            <span class="text-[11px] font-bold text-slate-400 whitespace-nowrap min-w-fit" v-if="conv.last_message">
                                {{ formatTime(conv.last_message.created_at) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center gap-2">
                            <p class="text-[13px] text-slate-400 truncate grow font-medium">
                                <span v-if="conv.last_message?.sender_id === auth.user?.id" class="text-slate-300">Vous: </span>
                                {{ conv.last_message?.content || (conv.last_message?.attachment_type ? '[Fichier]' : 'Début de conversation') }}
                            </p>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <span v-if="conv.unread_count > 0" class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                <span v-if="isOnline(conv.user.id)" class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat Window - Right Pane -->
        <div 
            class="grow bg-white rounded-[40px] shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex flex-col overflow-hidden relative border border-slate-100"
            :class="{ 'hidden md:flex': showSidebar, 'flex': !showSidebar }"
        >
            <template v-if="activeConversation">
                <!-- Chat Header -->
                <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center z-10">
                     <div class="flex items-center gap-4">
                          <button @click="showSidebar = true" class="md:hidden p-2 rounded-xl bg-slate-50 text-slate-500">
                              <ChevronLeft class="w-5 h-5" />
                          </button>
                          <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center overflow-hidden border border-slate-200/50">
                              <img v-if="activeConversation.user.role === 'provider' && activeConversation.user.prestataire?.photo" :src="`http://localhost:8000/storage/${activeConversation.user.prestataire.photo}`" class="w-full h-full object-cover">
                              <div v-else class="w-full h-full flex items-center justify-center text-slate-300 bg-slate-50"><User class="w-6 h-6" /></div>
                          </div>
                          <div>
                              <div class="flex items-center gap-2">
                                  <h3 class="font-bold text-slate-900 text-[17px] leading-tight">{{ activeConversation.user.name }}</h3>
                                  <span v-if="activeConversation.user.role === 'provider' && activeConversation.user.prestataire?.badge_level" 
                                        :class="getBadgeClass(activeConversation.user.prestataire.badge_level)"
                                        class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider"
                                  >
                                      {{ activeConversation.user.prestataire.badge_level }}
                                  </span>
                              </div>
                              <p class="text-[10px] font-black text-slate-400 mt-1 uppercase tracking-[0.1em]">
                                 {{ isOnline(activeConversation.user.id) ? 'EN LIGNE' : formatLastSeen(activeConversation.user.last_seen_at) }}
                              </p>
                          </div>
                     </div>
                    
                    <div class="relative">
                        <button @click="showMenu = !showMenu" class="p-2 rounded-xl text-slate-300 hover:text-slate-600 hover:bg-slate-50 transition-all">
                            <MoreVertical class="w-5 h-5" />
                        </button>
                        <!-- Dropdown Menu -->
                        <div v-if="showMenu" class="absolute right-0 top-full mt-2 w-48 bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden z-50">
                            <button @click="openReportModal" class="w-full text-left px-5 py-4 hover:bg-red-50 text-red-500 font-bold text-[11px] uppercase tracking-wider flex items-center transition-colors">
                                <Ban class="w-4 h-4 mr-3" />
                                Signaler l'utilisateur
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Messages Area -->
                <div ref="messagesContainer" class="grow overflow-y-auto p-10 space-y-10 scroll-smooth">
                    <div v-for="group in groupedMessages" :key="group.date" class="space-y-8">
                        <!-- Date Separator -->
                        <div class="flex justify-center">
                            <span class="px-6 py-2 bg-white rounded-full text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] shadow-[0_2px_10px_rgba(0,0,0,0.03)] border border-slate-100">
                                {{ formatDateHeader(group.date) }}
                            </span>
                        </div>

                        <!-- Messages -->
                        <div 
                            v-for="msg in group.messages" 
                            :key="msg.id"
                            :class="msg.sender_id === auth.user.id ? 'justify-end' : 'justify-start'"
                            class="flex"
                        >
                            <div 
                                :class="msg.sender_id === auth.user?.id 
                                    ? 'bg-blue-600 text-white rounded-[24px] rounded-tr-[4px]' 
                                    : 'bg-white text-slate-700 border border-slate-100 rounded-[24px] rounded-tl-[4px] shadow-[0_4px_15px_rgba(0,0,0,0.02)]'"
                                class="max-w-[70%] px-6 py-4 group transition-all"
                            >
                                <div v-if="msg.attachment_path" class="mb-4 rounded-xl overflow-hidden min-w-[240px]">
                                    <!-- Attachment rendering logic stays same but with refined styles -->
                                    <template v-if="msg.attachment_type === 'image' || (msg.id.toString().startsWith('temp') && msg.attachment_path && !msg.attachment_path.includes('video'))">
                                        <img :src="msg.id.toString().startsWith('temp') ? msg.attachment_path : secureUrls[msg.id]" 
                                             class="w-full h-auto object-cover max-h-80 cursor-pointer hover:opacity-95 transition" 
                                             alt="Attachment">
                                    </template>
                                    
                                    <template v-else-if="msg.attachment_type === 'audio'">
                                        <div class="p-4 rounded-2xl min-w-[280px] flex items-center gap-4 border"
                                             :class="msg.sender_id === auth.user?.id 
                                                ? 'bg-white/10 border-white/20' 
                                                : 'bg-slate-50 border-slate-100'"
                                        >
                                            <button @click="toggleAudio(msg.id)" 
                                                class="w-11 h-11 rounded-full flex items-center justify-center transition-all shadow-sm"
                                                :class="msg.sender_id === auth.user?.id ? 'bg-white text-blue-600' : 'bg-blue-600 text-white'"
                                            >
                                                <Pause v-if="msg.isPlaying" class="w-5 h-5 fill-current" />
                                                <Play v-else class="w-5 h-5 fill-current ml-1" />
                                            </button>
                                            
                                            <div class="grow space-y-2">
                                                 <input 
                                                    type="range" min="0" max="100" 
                                                    :value="msg.progress || 0" 
                                                    @input="seekAudio(msg.id, $event)"
                                                    class="w-full h-1 rounded-full appearance-none cursor-pointer"
                                                    :style="{ 
                                                        background: `linear-gradient(to right, ${msg.sender_id === auth.user?.id ? 'white' : '#2563eb'} 0%, ${msg.sender_id === auth.user?.id ? 'white' : '#2563eb'} ${msg.progress || 0}%, ${msg.sender_id === auth.user?.id ? 'rgba(255,255,255,0.2)' : '#E2E8F0'} ${msg.progress || 0}%, ${msg.sender_id === auth.user?.id ? 'rgba(255,255,255,0.2)' : '#E2E8F0'} 100%)` 
                                                    }"
                                                />
                                                <div class="flex justify-between text-[9px] font-black uppercase tracking-wider"
                                                     :class="msg.sender_id === auth.user?.id ? 'text-blue-100' : 'text-slate-400'"
                                                >
                                                    <span>{{ formatAudioTime(msg.currentTime) }}</span>
                                                    <span>{{ formatAudioTime(msg.duration) }}</span>
                                                </div>
                                            </div>
                                            <audio :ref="el => { if(el) audioElements[msg.id] = el }" class="hidden" 
                                                   @ended="onAudioEnded(msg.id)" @timeupdate="onTimeUpdate(msg.id)" @loadedmetadata="onLoadedMetadata(msg.id)">
                                                <source :src="msg.id.toString().startsWith('temp') ? msg.attachment_path : secureUrls[msg.id]" type="audio/webm">
                                            </audio>
                                        </div>
                                    </template>

                                    <template v-else-if="!msg.id.toString().startsWith('temp')">
                                        <div class="p-4 bg-slate-50 rounded-xl flex items-center gap-3 border border-slate-100">
                                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm">
                                                <FileText class="w-5 h-5 text-blue-600" />
                                            </div>
                                            <div class="grow min-w-0">
                                                <p class="text-[11px] font-bold text-slate-700 pr-4 truncate">{{ msg.attachment_path.split('/').pop() }}</p>
                                                <p class="text-[9px] text-slate-400 uppercase font-black tracking-widest">{{ msg.attachment_type || 'Fichier' }}</p>
                                            </div>
                                            <a :href="secureUrls[msg.id]" download class="p-2 text-blue-600 hover:bg-white rounded-lg transition-colors">
                                                <Play class="w-4 h-4" />
                                            </a>
                                        </div>
                                    </template>
                                </div>
                                <p v-if="msg.content" class="text-[14px] font-medium leading-relaxed">{{ msg.content }}</p>
                                <div 
                                    :class="msg.sender_id === auth.user?.id ? 'text-blue-200' : 'text-slate-400'"
                                    class="text-[10px] font-bold mt-2 flex items-center justify-end gap-2 uppercase tracking-tight"
                                >
                                    <button 
                                        v-if="msg.sender_id === auth.user?.id && !msg.pending" 
                                        @click="deleteMessage(msg.id)"
                                        class="mr-auto opacity-0 group-hover:opacity-100 transition-opacity hover:text-red-400"
                                    >
                                        <Trash2 class="w-3 h-3" />
                                    </button>
                                    <span>{{ formatTime(msg.created_at) }}</span>
                                    <CheckCheck v-if="msg.sender_id === auth.user?.id && !msg.pending && !msg.error" class="w-3.5 h-3.5" />
                                    <Loader2 v-if="msg.pending" class="w-3 h-3 animate-spin" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input Window -->
                <div class="p-8 bg-white border-t border-slate-100">
                    <div v-if="isBlocked" class="p-4 bg-slate-50 rounded-2xl text-center border border-slate-100">
                        <p class="text-slate-500 font-bold text-[13px] flex items-center justify-center">
                            <Ban class="w-4 h-4 mr-2 text-red-400" />
                            Discussion bloquée.
                        </p>
                    </div>
                    <div v-else>
                        <div v-if="selectedFile" class="mb-4 relative inline-block">
                            <div class="relative rounded-2xl overflow-hidden h-20 w-20 border border-slate-200 shadow-sm">
                                <img v-if="previewUrl" :src="previewUrl" class="w-full h-full object-cover">
                                <div v-else class="w-full h-full bg-slate-50 flex items-center justify-center"><FileText class="w-8 h-8 text-slate-300" /></div>
                                <button @click="clearFile" class="absolute top-1 right-1 bg-white/90 text-red-500 rounded-full p-1 shadow-sm transition-all hover:scale-110">
                                    <X class="w-3 h-3" />
                                </button>
                            </div>
                        </div>
                        <form @submit.prevent="sendMessage" class="flex items-center gap-4">
                            <!-- Input Wrapper matching design -->
                            <div class="grow flex items-center bg-[#F1F5F9] rounded-[24px] px-3 py-2 border border-transparent focus-within:border-slate-200 focus-within:bg-white transition-all group">
                                <button type="button" @click="fileInput.click()" class="p-3 text-slate-400 hover:text-slate-600 transition-colors">
                                    <Paperclip class="w-6 h-6" />
                                </button>
                                <input ref="fileInput" type="file" class="hidden" @change="handleFileSelect">
                                
                                <template v-if="isRecording">
                                    <div class="grow flex items-center px-4 animate-in fade-in slide-in-from-left-2">
                                        <span class="w-2 h-2 bg-red-500 rounded-full mr-3 animate-pulse"></span>
                                        <span class="font-bold text-red-500 font-mono pr-4">{{ formatDuration(recordingDuration) }}</span>
                                        <span class="text-xs font-black text-red-400 uppercase tracking-widest grow">Enregistrement...</span>
                                        <button @click.prevent="cancelRecording" class="p-2 text-slate-400 hover:text-red-500"><X class="w-5 h-5" /></button>
                                    </div>
                                </template>
                                <input v-else v-model="newMessage" type="text" placeholder="Tapez un message..." 
                                       class="grow bg-transparent border-none focus:ring-0 text-[15px] font-medium text-slate-700 px-2 placeholder-slate-400">
                                
                                <!-- Right actions in input -->
                                <div class="flex items-center gap-1">
                                    <button 
                                        v-if="!newMessage.trim() && !selectedFile && !isRecording"
                                        type="button" @click="startRecording"
                                        class="p-3 text-slate-400 hover:text-slate-600 transition-colors"
                                    >
                                        <Mic class="w-6 h-6" />
                                    </button>
                                    <template v-if="isRecording">
                                         <button @click.prevent="stopRecording" type="button" class="p-3 bg-red-500 text-white rounded-full hover:bg-red-600 shadow-md">
                                            <Send class="w-5 h-5" />
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <button 
                                type="submit" 
                                :disabled="(!newMessage.trim() && !selectedFile && !audioBlob) || isSending || isRecording"
                                class="w-[56px] h-[56px] bg-slate-400 text-white rounded-[20px] transition-all flex items-center justify-center shrink-0 hover:bg-slate-500 disabled:opacity-30 disabled:hover:bg-slate-400"
                                :class="{ 'bg-blue-600 hover:bg-blue-700 shadow-[0_8px_20px_rgba(37,99,235,0.25)]': newMessage.trim() || selectedFile || audioBlob }"
                            >
                                <Loader2 v-if="isSending" class="w-6 h-6 animate-spin" />
                                <Send v-else class="w-6 h-6 transform rotate-[-15deg] translate-x-[2px] translate-y-[-1px]" />
                            </button>
                        </form>
                    </div>
                </div>
            </template>
            
            <div v-else class="grow flex flex-col items-center justify-center text-slate-400 p-10 bg-slate-50/20">
                <div class="w-24 h-24 bg-white rounded-[32px] shadow-[0_8px_30px_rgba(0,0,0,0.03)] flex items-center justify-center mb-8 border border-slate-100">
                    <MessageCircle class="w-10 h-10 text-blue-100" />
                </div>
                <h3 class="font-black text-slate-900 text-2xl tracking-tight">Vos conversations</h3>
                <p class="text-slate-400 text-sm font-bold mt-3 uppercase tracking-widest">Choisissez un contact pour commencer</p>
            </div>
        </div>
    </div>

    <!-- Modals & Toasts stay mostly same but with visual polish -->
    <Transition name="fade">
        <div v-if="showReportModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px]" @click="showReportModal = false"></div>
            <div class="relative bg-white w-full max-w-md rounded-[40px] shadow-2xl p-10 border border-slate-100 animate-in zoom-in-95 duration-200">
                <h3 class="text-2xl font-black text-slate-900 mb-8 flex items-center">
                    <Ban class="w-7 h-7 mr-4 text-red-500" />
                    Signaler
                </h3>
                <div class="space-y-6">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 mb-2 uppercase tracking-[0.2em]">Motif</label>
                        <select v-model="reportForm.reason" class="w-full px-5 py-4 bg-slate-50 rounded-2xl border border-slate-100 focus:bg-white focus:ring-2 focus:ring-red-500/10 outline-none font-bold text-sm appearance-none">
                            <option>Comportement inapproprié</option>
                            <option>Spam ou fraude</option>
                            <option>Harcèlement</option>
                            <option>Autre problème</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 mb-2 uppercase tracking-[0.2em]">Détails</label>
                        <textarea v-model="reportForm.description" rows="4" class="w-full px-5 py-4 bg-slate-50 rounded-2xl border border-slate-100 focus:bg-white focus:ring-2 focus:ring-red-500/10 outline-none font-medium text-sm resize-none"></textarea>
                    </div>
                </div>
                <div class="flex gap-4 mt-10">
                    <button @click="showReportModal = false" class="flex-1 py-4 font-black text-[12px] uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-colors">Fermer</button>
                    <button @click="submitReport" :disabled="reportLoading" class="flex-1 py-4 bg-red-500 text-white font-black text-[12px] uppercase tracking-widest rounded-2xl shadow-lg shadow-red-500/20 hover:bg-red-600 transition-all">
                        {{ reportLoading ? '...' : 'Signaler' }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Toast Component -->
    <Transition name="toast">
        <div v-if="showToast" class="fixed bottom-10 left-10 z-[200] flex items-center bg-white/80 backdrop-blur-xl px-6 py-4 rounded-[24px] shadow-[0_10px_40px_rgba(0,0,0,0.1)] border border-white min-w-[320px]">
            <div :class="toastType === 'success' ? 'bg-green-500' : (toastType === 'error' ? 'bg-red-500' : 'bg-blue-600')"
                 class="w-10 h-10 rounded-2xl flex items-center justify-center mr-4 shadow-lg shrink-0">
                <CheckCircle v-if="toastType === 'success'" class="w-5 h-5 text-white" />
                <X v-else-if="toastType === 'error'" class="w-5 h-5 text-white" />
                <MessageSquare v-else class="w-5 h-5 text-white" />
            </div>
            <div class="min-w-0">
                <h4 class="font-black text-slate-900 text-[13px] uppercase tracking-wider mb-0.5">{{ toastType === 'success' ? 'Succès' : 'Info' }}</h4>
                <p class="text-xs text-slate-500 font-medium truncate">{{ toastMessage }}</p>
            </div>
        </div>
    </Transition>
</div>
</template>

<style scoped>
.messages-page-wrapper {
    font-family: 'Outfit', 'Inter', sans-serif;
}

/* Hide default scrollbar but keep functionality */
.overflow-y-auto {
    scrollbar-width: thin;
    scrollbar-color: #E2E8F0 transparent;
}

::-webkit-scrollbar {
    width: 4px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: #E2E8F0;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: #CBD5E1;
}

/* Range slider thumb refinement */
input[type=range]::-webkit-slider-thumb {
  -webkit-appearance: none;
  height: 12px;
  width: 12px;
  border-radius: 50%;
  background: white;
  margin-top: -4px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  cursor: pointer;
  border: 1px solid #E2E8F0;
}

/* Transitions */
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.toast-enter-active { animation: toast-in 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.toast-leave-active { animation: toast-in 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) reverse; }

@keyframes toast-in {
    from { transform: translateX(-40px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
</style>
