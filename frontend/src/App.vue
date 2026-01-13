<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from './store/auth';
import api from './services/api';
import { LogOut, User as UserIcon, MessageSquare, Search } from 'lucide-vue-next';

const auth = useAuthStore();
const router = useRouter();
const unreadCount = ref(0);
let countInterval = null;

const fetchUnreadCount = async () => {
  if (!auth.isAuthenticated) return;
  try {
    const response = await api.get('/api/messages/unread-count');
    unreadCount.value = response.data.count;
  } catch (err) {
    console.error('Erreur unread count:', err);
  }
};

const logout = async () => {
  await auth.logout();
  router.push('/login');
};

onMounted(() => {
  fetchUnreadCount();
  countInterval = setInterval(fetchUnreadCount, 10000);
});

onUnmounted(() => {
  clearInterval(countInterval);
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
              <router-link to="/profile" class="text-gray-500 hover:text-gray-700 p-2">
                <UserIcon class="w-5 h-5" />
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
  </div>
</template>

<style>
@reference "tailwindcss";

.router-link-active {
  @apply !text-blue-600;
}
</style>
