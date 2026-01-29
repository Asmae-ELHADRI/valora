<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from './store/auth';
import api from './services/api';
import echo from './services/echo';
import { LogOut, User as UserIcon, MessageSquare, Search, Settings, CheckCircle, X, Info } from 'lucide-vue-next';

const auth = useAuthStore();
const router = useRouter();
const unreadCount = ref(0);
const reqCount = ref(0);
let countInterval = null;

// Global Toast State
const toast = ref({
  show: false,
  message: '',
  type: 'info'
});

const showGlobalToast = (message, type = 'info') => {
  toast.value = { show: true, message, type };
  setTimeout(() => toast.value.show = false, 5000);
};

const fetchUnreadCount = async () => {
  if (!auth.isAuthenticated) return;
  
  try {
    // Sequential fetch to avoid session locking on PHP server
    try {
      const msgRes = await api.get('/api/messages/unread-count');
      unreadCount.value = msgRes.data.count;
    } catch (err) {
      console.error('Error fetching unread messages:', err.message, err.response?.status, err.response?.data);
    }

    try {
      const reqRes = await api.get('/api/requests/unread-count');
      reqCount.value = auth.isClient ? reqRes.data.candidatures_count : reqRes.data.invitations_count;
    } catch (err) {
      console.error('Error fetching request counts:', err.message, err.response?.status, err.response?.data);
    }
  } finally {
    // Schedule next poll
    if (countInterval) clearTimeout(countInterval);
    countInterval = setTimeout(fetchUnreadCount, 10000);
  }
};

const logout = async () => {
  if (countInterval) clearTimeout(countInterval);
  await auth.logout();
  router.push('/login');
};

onMounted(() => {
  fetchUnreadCount();

  if (auth.user) {
    echo.private(`chat.${auth.user.id}`)
      .listen('MessageSent', (e) => {
        // If not on messages page, show notification and increment count
        if (router.currentRoute.value.path !== '/messages') {
          unreadCount.value++;
          showGlobalToast(`${e.message.sender?.name || 'Nouveau message'} : ${e.message.content || 'Fichier reçu'}`, 'info');
        } else {
            // Unread count is handled by Messages.vue when on that page, 
            // but we might want to sync it here too if needed.
        }
      });
  }
});

onUnmounted(() => {
  if (countInterval) clearTimeout(countInterval);
});
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Navbar -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <router-link to="/" class="flex-shrink-0 flex items-center">
              <span class="text-2xl font-bold text-blue-600 tracking-tight">VALORA</span>
            </router-link>
            <div class="hidden sm:ml-8 sm:flex sm:space-x-8">
              <router-link to="/" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                Accueil
              </router-link>
              <template v-if="auth.isAuthenticated">
                <router-link v-if="auth.isProvider" to="/search" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                  Missions
                </router-link>
                <router-link v-if="auth.isClient" to="/providers" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                  Prestataires
                </router-link>
              </template>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <template v-if="!auth.isAuthenticated">
              <router-link to="/login" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">
                Connexion
              </router-link>
              <router-link to="/register" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                S'inscrire
              </router-link>
            </template>
            <template v-else>
              <router-link to="/messages" class="text-gray-500 hover:text-gray-700 p-2 relative">
                <MessageSquare class="w-5 h-5" />
                <span v-if="unreadCount > 0" class="absolute top-0 right-0 w-4 h-4 bg-red-500 text-white text-[10px] flex items-center justify-center rounded-full font-bold">
                  {{ unreadCount }}
                </span>
              </router-link>
              <router-link :to="auth.isProvider ? '/dashboard-provider' : (auth.isClient ? '/dashboard-client' : (auth.isAdmin ? '/dashboard-admin' : '/dashboard'))" class="text-gray-500 hover:text-gray-700 p-2 relative">
                <UserIcon class="w-5 h-5" />
                <span v-if="reqCount > 0" class="absolute top-0 right-0 w-4 h-4 bg-orange-500 text-white text-[10px] flex items-center justify-center rounded-full font-bold border-2 border-white">
                  {{ reqCount }}
                </span>
              </router-link>
              <router-link to="/security" class="text-gray-500 hover:text-gray-700 p-2">
                <Settings class="w-5 h-5" />
              </router-link>
              <button @click="logout" class="text-gray-500 hover:text-red-600 p-2">
                <LogOut class="w-5 h-5" />
              </button>
            </template>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow">
      <router-view></router-view>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-gray-500 text-sm">
        <p>© 2026 VALORA. « C’est dans l’effort que l’humain se révèle »</p>
      </div>
    </footer>

    <!-- Global Toast Notification -->
    <Transition name="toast">
      <div v-if="toast.show" 
           :class="{
              'shadow-green-100 border-green-100': toast.type === 'success',
              'shadow-red-100 border-red-100': toast.type === 'error',
              'shadow-blue-100 border-blue-100': toast.type === 'info'
           }"
           class="fixed bottom-6 right-6 z-[200] flex items-center bg-white px-6 py-4 rounded-2xl shadow-2xl border min-w-[300px] max-w-[90vw]"
      >
          <div :class="{
              'bg-green-100': toast.type === 'success',
              'bg-red-100': toast.type === 'error',
              'bg-blue-100': toast.type === 'info'
          }"
               class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center mr-4">
              <CheckCircle v-if="toast.type === 'success'" class="w-5 h-5 text-green-600" />
              <X v-else-if="toast.type === 'error'" class="w-5 h-5 text-red-600" />
              <MessageSquare v-else class="w-5 h-5 text-blue-600" />
          </div>
          <div class="min-w-0">
              <h4 class="font-black text-gray-900 text-sm">
                  {{ toast.type === 'success' ? 'Succès !' : (toast.type === 'error' ? 'Erreur' : 'Nouveau message') }}
              </h4>
              <p class="text-xs text-gray-500 font-medium truncate">{{ toast.message }}</p>
          </div>
          <button @click="toast.show = false" class="ml-auto pl-4 text-gray-300 hover:text-gray-500">
            <X class="w-4 h-4" />
          </button>
      </div>
    </Transition>
  </div>
</template>

<style>
.router-link-active {
  color: #2563eb !important;
}

/* Global Toast Animations */
.toast-enter-active,
.toast-leave-active {
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100px);
}
.toast-leave-to {
  opacity: 0;
  transform: translateY(20px);
}
</style>
