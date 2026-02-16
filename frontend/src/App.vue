<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from './store/auth';
import api from './services/api';
import echo from './services/echo';
import { LogOut, User as UserIcon, MessageSquare, Search, Settings, CheckCircle, X, Info, Bell, Briefcase } from 'lucide-vue-next';
import valoraLogo from './assets/v-logo.png';
import Footer from './components/Footer.vue';
import BottomNav from './components/BottomNav.vue';
import LanguageSwitcher from './components/LanguageSwitcher.vue';

const auth = useAuthStore();
const router = useRouter();
const unreadCount = ref(0);
const reqCount = ref(0);
const isScrolled = ref(false);
const notifications = ref([]);
const unreadNotifCount = ref(0);
const showNotifDropdown = ref(false);
const route = useRoute();

const fetchNotifications = async () => {
  if (!auth.isAuthenticated) return;
  try {
    const response = await api.get('/api/notifications');
    notifications.value = response.data.notifications.map(n => ({
        id: n.id,
        type: n.data.type || 'system',
        title: n.data.title || 'Notification',
        desc: n.data.desc || n.data.content || '',
        time: n.created_at, // We will format this in template or via a helper
        unread: !n.read_at,
        original: n
    }));
    unreadNotifCount.value = response.data.unread_count;
  } catch (error) {
    console.error('Error fetching notifications:', error);
  }
};

const markNotifRead = async (id) => {
    try {
        await api.post(`/api/notifications/${id}/read`);
        const notif = notifications.value.find(n => n.id === id);
        if (notif && notif.unread) {
            notif.unread = false;
            unreadNotifCount.value--;
        }
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
};

const markAllNotifsRead = async () => {
    try {
        await api.post('/api/notifications/read-all');
        notifications.value.forEach(n => n.unread = false);
        unreadNotifCount.value = 0;
    } catch (error) {
        console.error('Error marking all notifications as read:', error);
    }
};

const formatRelativeTime = (date) => {
    if (!date) return '';
    const d = typeof date === 'string' ? new Date(date) : date;
    const now = new Date();
    const diff = Math.floor((now - d) / 1000); // seconds

    if (diff < 60) return "À l'instant";
    if (diff < 3600) return `Il y a ${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `Il y a ${Math.floor(diff / 3600)} h`;
    return d.toLocaleDateString();
};

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
  if (auth.isAuthenticated) {
    fetchUnreadCount();
    fetchNotifications();
  }
  window.addEventListener('scroll', handleScroll);

  if (auth.user) {
    echo.private(`chat.${auth.user.id}`)
      .listen('MessageSent', (e) => {
        const msg = e.message;
        if (msg.sender_id !== auth.user.id) {
          // If we're not already on the messages page for this conversation
          if (route.path !== '/messages') {
              unreadCount.value++;
              showGlobalToast(`Nouveau message de ${msg.sender?.name || 'Contact'}`, 'info');
          }
        }
      });

    // Listen for real-time notifications
    echo.private(`App.Models.User.${auth.user.id}`)
      .notification((notification) => {
        // Add new notification to the top
        notifications.value.unshift({
            id: notification.id,
            type: notification.type || 'system',
            title: notification.title || 'Notification',
            desc: notification.desc || notification.content || '',
            time: 'À l\'instant',
            unread: true,
            original: notification
        });
        unreadNotifCount.value++;
        
        // Show a little toast too? Why not.
        showGlobalToast(`🔔 ${notification.title || 'Nouvelle notification'}`, 'info');
      });
  }

  // Handle click outside for notification dropdown
  window.addEventListener('click', (e) => {
    const btn = document.querySelector('.notif-btn-trigger');
    const dropdown = document.querySelector('.notif-dropdown-menu');
    if (showNotifDropdown.value && btn && !btn.contains(e.target) && dropdown && !dropdown.contains(e.target)) {
        showNotifDropdown.value = false;
    }
  });
});

onUnmounted(() => {
  if (countInterval) clearTimeout(countInterval);
  window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Premium Navbar -->
    <nav v-if="!route.path.startsWith('/admin')"
         :class="[isScrolled ? 'navbar-glacier' : 'navbar-top']"
         class="navbar-premium sticky top-0 z-50 transition-all duration-700">
      <!-- Top Gradient Overlay (Only at top) -->
      <div class="navbar-gradient-top" :class="{ 'opacity-100': !isScrolled, 'opacity-0': isScrolled }"></div>
      
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
              <LanguageSwitcher />
            </template>
            <template v-else>
              <!-- Messages Icon -->
              <router-link to="/messages" class="nav-icon-btn">
                <MessageSquare class="w-5 h-5" />
                <span v-if="unreadCount > 0" class="notification-badge bg-gradient-to-br from-red-500 to-red-600">
                  {{ unreadCount }}
                </span>
              </router-link>
              
              <!-- Notifications Icon -->
              <div class="relative">
                <button @click="showNotifDropdown = !showNotifDropdown" class="nav-icon-btn notif-btn-trigger">
                    <Bell class="w-5 h-5" />
                    <span v-if="unreadNotifCount > 0" class="notif-badge-new"></span>
                </button>
                
                <!-- Notification Dropdown -->
                <Transition name="dropdown">
                    <div v-if="showNotifDropdown" class="absolute right-0 mt-4 w-80 bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden z-50 notif-dropdown-menu">
                        <div class="p-5 border-b border-slate-50 flex items-center justify-between bg-slate-50/50">
                            <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-900">Notifications</h4>
                            <button @click="markAllNotifsRead" class="text-[8px] font-bold text-slate-400 uppercase tracking-widest hover:text-premium-brown transition-colors">Tout marquer</button>
                        </div>
                        <div class="max-h-96 overflow-y-auto divide-y divide-slate-50 premium-scrollbar">
                            <div v-for="notif in notifications" :key="notif.id" @click="markNotifRead(notif.id)" class="p-4 hover:bg-slate-50 transition-colors cursor-pointer group relative">
                                <div class="flex space-x-3 relative z-10">
                                    <div :class="notif.unread ? 'bg-yellow-100 text-yellow-700' : 'bg-slate-100 text-slate-400'" class="w-9 h-9 rounded-xl shrink-0 flex items-center justify-center transition-transform group-hover:scale-110">
                                        <MessageSquare v-if="notif.type === 'message' || notif.type === 'App\\Notifications\\NewMessageNotification'" class="w-4 h-4" />
                                        <Briefcase v-else-if="notif.type === 'mission' || notif.type === 'App\\Notifications\\ServiceRequestNotification'" class="w-4 h-4" />
                                        <Bell v-else class="w-4 h-4" />
                                    </div>
                                    <div class="grow min-w-0">
                                        <div class="flex justify-between items-start">
                                            <p class="text-[11px] font-black text-slate-900 truncate tracking-tight">{{ notif.title }}</p>
                                            <span class="text-[8px] font-bold text-slate-400 shrink-0 ml-2">{{ formatRelativeTime(notif.time) }}</span>
                                        </div>
                                        <p class="text-[10px] text-slate-500 line-clamp-2 mt-0.5 leading-relaxed font-medium">{{ notif.desc }}</p>
                                    </div>
                                </div>
                                <div v-if="notif.unread" class="absolute left-1 top-1/2 -translate-y-1/2 w-1 h-8 bg-yellow-400 rounded-full opacity-50"></div>
                            </div>
                        </div>
                        <div v-if="notifications.length === 0" class="p-10 text-center">
                            <Bell class="w-8 h-8 text-slate-200 mx-auto mb-3" />
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Aucune notification</p>
                        </div>
                        <div class="p-4 bg-slate-50/50 text-center border-t border-slate-50">
                             <button class="text-[9px] font-black text-slate-400 uppercase tracking-widest hover:text-slate-900 transition-colors">Voir l'historique complet</button>
                        </div>
                    </div>
                </Transition>
              </div>

              <!-- Dashboard Icon -->
              <router-link :to="auth.isProvider ? '/dashboard-provider' : (auth.isClient ? '/dashboard-client' : (auth.isAdmin ? '/admin/dashboard' : '/dashboard'))" class="nav-icon-btn">
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

              <LanguageSwitcher />
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
    <Footer v-if="!['/login', '/register'].includes($route.path) && !$route.path.startsWith('/admin')" />

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

.navbar-top {
  background: transparent;
  box-shadow: none;
}

.navbar-top .nav-link, 
.navbar-top .nav-btn-secondary, 
.navbar-top .nav-icon-btn {
  color: #ffffff;
}

.navbar-glacier {
  background: rgba(255, 255, 255, 0.65);
  backdrop-filter: blur(20px) saturate(180%);
  -webkit-backdrop-filter: blur(20px) saturate(180%);
  border-bottom: 1px solid rgba(255, 255, 255, 0.4);
  box-shadow: 
    0 4px 30px rgba(0, 0, 0, 0.03),
    inset 0 0 20px rgba(255, 255, 255, 0.5);
}

.navbar-glacier .nav-link,
.navbar-glacier .nav-btn-secondary,
.navbar-glacier .nav-icon-btn {
  color: #0f172a;
}

/* Shiny Glacier Effect Overlay */
.navbar-glacier::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(
    110deg,
    transparent 20%,
    rgba(255, 255, 255, 0.4) 48%,
    rgba(255, 255, 255, 0.5) 50%,
    rgba(255, 255, 255, 0.4) 52%,
    transparent 80%
  );
  background-size: 200% 100%;
  animation: shine-glacier 8s infinite linear;
  pointer-events: none;
  z-index: -1;
}

@keyframes shine-glacier {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

/* Gradient Background for the Top State */
.navbar-gradient-top {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(180deg, 
    rgba(15, 23, 42, 0.8) 0%,
    rgba(15, 23, 42, 0.4) 50%,
    transparent 100%
  );
  z-index: -1;
  transition: opacity 0.7s ease;
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

.nav-link:hover {
  color: #facc15;
  background: rgba(255, 255, 255, 0.1);
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

.nav-btn-secondary:hover {
  background: rgba(255, 255, 255, 0.2);
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

.navbar-top .nav-icon-btn {
  background: rgba(255, 255, 255, 0.1);
  border: 1.5px solid rgba(255, 255, 255, 0.2);
}

.navbar-glacier .nav-icon-btn {
  background: rgba(255, 255, 255, 0.15);
  border: 1.5px solid rgba(255, 255, 255, 0.3);
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

/* Dropdown Animations */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.dropdown-enter-from {
  opacity: 0;
  transform: translateY(15px) scale(0.95);
}

.dropdown-leave-to {
  opacity: 0;
  transform: translateY(10px) scale(0.98);
}

.notif-badge-new {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 6px;
    height: 6px;
    background: #facc15;
    border-radius: 50%;
    box-shadow: 0 0 10px rgba(250, 204, 21, 0.8);
    animation: pulse-yellow 2s infinite;
}

@keyframes pulse-yellow {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.5); opacity: 0.5; }
    100% { transform: scale(1); opacity: 1; }
}

.premium-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.premium-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.premium-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
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
