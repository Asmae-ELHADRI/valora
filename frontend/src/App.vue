<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from './store/auth';
import api from './services/api';
import echo from './services/echo';
import { LogOut, User as UserIcon, MessageSquare, Search, Settings, CheckCircle, X, Info } from 'lucide-vue-next';
import valoraLogo from './assets/v-logo.png';
import BottomNav from './components/BottomNav.vue';

const auth = useAuthStore();
const router = useRouter();
const unreadCount = ref(0);
const reqCount = ref(0);
const isScrolled = ref(false);
let countInterval = null;

const handleScroll = () => {
  isScrolled.value = window.scrollY > 20;
};

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
  window.addEventListener('scroll', handleScroll);

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
  window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Premium Navbar -->
    <nav v-if="!['/login', '/register'].includes($route.path)" 
         :class="[isScrolled ? 'navbar-transparent' : 'navbar-scrolled']"
         class="navbar-premium sticky top-0 z-50 transition-all duration-500">
      <!-- Gradient Background -->
      <div class="navbar-gradient" :class="{ 'opacity-0': isScrolled, 'opacity-100': !isScrolled }"></div>
      
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="flex justify-between h-20">
          <div class="flex items-center">
            <!-- Logo with Premium Styling -->
            <router-link to="/" class="shrink-0 flex items-center group">
              <div class="logo-container">
                <img :src="valoraLogo" alt="VALORA Logo" class="logo-image" />
                <span class="text-3xl font-black tracking-tight logo-text logo-text-brown">
                  VALORA
                </span>
              </div>
            </router-link>
            
            <!-- Navigation Links -->
            <div class="hidden sm:ml-12 sm:flex sm:space-x-2">
              <router-link to="/" class="nav-link">
                <span>{{ $t('nav.home') }}</span>
                <div class="nav-link-underline"></div>
              </router-link>
              <template v-if="auth.isAuthenticated">
                <router-link v-if="auth.isProvider" to="/search" class="nav-link">
                  <span>{{ $t('nav.missions') }}</span>
                  <div class="nav-link-underline"></div>
                </router-link>
                <router-link v-if="auth.isClient" to="/providers" class="nav-link">
                  <span>{{ $t('nav.providers') }}</span>
                  <div class="nav-link-underline"></div>
                </router-link>
              </template>
            </div>
          </div>
          
          <!-- Right Side Actions -->
          <div class="flex items-center space-x-3">
            <template v-if="!auth.isAuthenticated">
              <router-link to="/login" class="nav-btn-secondary">
                {{ $t('nav.login') }}
              </router-link>
            </template>
            <template v-else>
              <!-- Messages Icon -->
              <router-link to="/messages" class="nav-icon-btn">
                <MessageSquare class="w-5 h-5" />
                <span v-if="unreadCount > 0" class="notification-badge bg-gradient-to-br from-red-500 to-red-600">
                  {{ unreadCount }}
                </span>
              </router-link>
              
              <!-- Dashboard Icon -->
              <router-link :to="auth.isProvider ? '/dashboard-provider' : (auth.isClient ? '/dashboard-client' : (auth.isAdmin ? '/dashboard-admin' : '/dashboard'))" class="nav-icon-btn">
                <UserIcon class="w-5 h-5" />
                <span v-if="reqCount > 0" class="notification-badge bg-gradient-to-br from-orange-500 to-orange-600">
                  {{ reqCount }}
                </span>
              </router-link>
              
              <!-- Settings Icon -->
              <router-link to="/security" class="nav-icon-btn">
                <Settings class="w-5 h-5" />
              </router-link>
              
              <!-- Logout Button -->
              <button @click="logout" class="nav-icon-btn logout-btn">
                <LogOut class="w-5 h-5" />
              </button>
            </template>
          </div>
        </div>
      </div>
      
    </nav>

    <!-- Main Content -->
    <main class="grow">
      <router-view></router-view>
    </main>

    <!-- Footer -->
    <footer v-if="!['/login', '/register'].includes($route.path)" class="bg-white border-t border-gray-200 py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-gray-500 text-sm">
        <p>© 2026 VALORA {{ $t('common.footer_quote') }}</p>
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
               class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center mr-4">
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

    <!-- Mobile Bottom Navigation -->
    <BottomNav v-if="auth.isAuthenticated" />
  </div>
</template>

<style>
/* Import Premium Google Font */
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&display=swap');

/* Premium Navbar Styles */
.navbar-premium {
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.navbar-transparent {
  background: transparent;
  box-shadow: none;
}

.navbar-scrolled {
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.3);
}

/* Gradient Background with Deeper Premium Palette */
.navbar-gradient {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(165deg, 
    rgba(15, 23, 42, 0.9) 0%,     /* Premium Blue */
    rgba(30, 41, 59, 0.8) 40%,    /* Slate 800 */
    rgba(69, 26, 3, 0.9) 100%     /* Deeper Brown */
  );
  z-index: 1;
  transition: opacity 0.5s ease;
}

/* Bottom Border Glow */
.navbar-border-glow {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, 
    transparent 0%,
    rgba(255, 255, 255, 0.5) 20%,
    rgba(255, 255, 255, 0.8) 50%,
    rgba(255, 255, 255, 0.5) 80%,
    transparent 100%
  );
  animation: glow-pulse 3s ease-in-out infinite;
}

@keyframes glow-pulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}


/* Navigation Links */
.nav-link {
  position: relative;
  display: inline-flex;
  align-items: center;
  padding: 0.75rem 1.25rem;
  font-size: 0.95rem;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.5s ease;
  border-radius: 12px;
  letter-spacing: 0.01em;
}

.navbar-transparent .nav-link {
  color: #0f172a;
}

.navbar-scrolled .nav-link {
  color: rgba(255, 255, 255, 0.9);
}

.nav-link:hover {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.15);
  transform: translateY(-1px);
}

/* Navigation Link Underline */
.nav-link-underline {
  position: absolute;
  bottom: 8px;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 2px;
  background: #facc15; /* Premium Yellow */
  border-radius: 2px;
  transition: width 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.nav-link:hover .nav-link-underline {
  width: 70%;
}

.router-link-active.nav-link {
  color: #facc15 !important;
  background: rgba(250, 204, 21, 0.1);
  font-weight: 800;
}

.router-link-active.nav-link .nav-link-underline {
  width: 70%;
  background: #facc15;
}

/* Primary Button (S'inscrire) */
.nav-btn-primary {
  display: inline-flex;
  align-items: center;
  padding: 0.65rem 1.5rem;
  font-size: 0.9rem;
  font-weight: 700;
  color: #1e3a8a;
  background: linear-gradient(135deg, #ffffff 0%, #fef3c7 100%);
  border-radius: 12px;
  text-decoration: none;
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
  letter-spacing: 0.02em;
}

.nav-btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(250, 204, 21, 0.3);
  background: #ffffff;
}

.nav-btn-primary:active {
  transform: translateY(0);
}

/* Secondary Button (Connexion) */
.nav-btn-secondary {
  display: inline-flex;
  align-items: center;
  padding: 0.65rem 1.5rem;
  font-size: 0.9rem;
  font-weight: 600;
  border-radius: 12px;
  text-decoration: none;
  transition: all 0.5s ease;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  letter-spacing: 0.01em;
}

.navbar-transparent .nav-btn-secondary {
  color: #0f172a;
  border: 1.5px solid rgba(15, 23, 42, 0.2);
  background: rgba(15, 23, 42, 0.05);
}

.navbar-scrolled .nav-btn-secondary {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.15);
  border: 1.5px solid rgba(255, 255, 255, 0.3);
}

.nav-btn-secondary:hover {
  background: rgba(255, 255, 255, 0.25);
  border-color: rgba(255, 255, 255, 0.5);
  transform: translateY(-1px);
}

/* Icon Buttons */
.nav-icon-btn {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.65rem;
  border-radius: 12px;
  transition: all 0.5s ease;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  cursor: pointer;
}

.navbar-transparent .nav-icon-btn {
  color: #0f172a;
  background: rgba(15, 23, 42, 0.05);
  border: 1.5px solid rgba(15, 23, 42, 0.1);
}

.navbar-scrolled .nav-icon-btn {
  color: rgba(255, 255, 255, 0.9);
  background: rgba(255, 255, 255, 0.1);
  border: 1.5px solid rgba(255, 255, 255, 0.2);
}

.nav-icon-btn:hover {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.2);
  border-color: rgba(255, 255, 255, 0.4);
  transform: translateY(-2px) scale(1.05);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.nav-icon-btn:active {
  transform: translateY(0) scale(1);
}

/* Logout Button Special Styling */
.logout-btn:hover {
  background: rgba(239, 68, 68, 0.2);
  border-color: rgba(239, 68, 68, 0.4);
  color: #fecaca;
}

/* Notification Badge */
.notification-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 800;
  color: #ffffff;
  border-radius: 9px;
  border: 2px solid rgba(37, 99, 235, 0.95);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
  animation: badge-pulse 2s ease-in-out infinite;
}

@keyframes badge-pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

/* Global Router Link Active State */
.router-link-active {
  color: inherit !important;
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

/* Responsive Adjustments */
@media (max-width: 640px) {
  .navbar-premium {
    padding: 0.5rem 0;
  }
  
  .logo-text {
    font-size: 1.5rem;
  }
  
  .nav-btn-primary,
  .nav-btn-secondary {
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
  }
}
</style>
