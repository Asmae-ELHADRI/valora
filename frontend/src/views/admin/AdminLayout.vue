<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../store/auth';
import { 
  LayoutDashboard, 
  ShieldAlert, 
  Award, 
  Users, 
  Settings, 
  LogOut, 
  Search, 
  Bell, 
  Sun, 
  Moon,
  MessageCircle,
  ChevronRight,
  Menu
} from 'lucide-vue-next';
import LanguageSwitcher from '../../components/LanguageSwitcher.vue';
import { useThemeStore } from '../../store/theme';

const { t, locale } = useI18n();
const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const themeStore = useThemeStore();

const isActive = (path) => route.path === path;

const menuItems = computed(() => [
  { name: t('admin.dashboard', 'Tableau de bord'), path: '/admin/dashboard', icon: LayoutDashboard },
  { name: t('admin.users', 'Utilisateurs'), path: '/admin/users', icon: Users },
  { name: t('nav.conversations', 'Conversations'), path: '/admin/conversations', icon: MessageCircle, arrow: true },
  { name: t('admin.reports', 'Signalements'), path: '/admin/moderation', icon: ShieldAlert },
  { name: t('admin.grades', 'Grades'), path: '/admin/governance', icon: Award },
  { name: t('admin.settings', 'Paramètres'), path: '/admin/settings', icon: Settings },
]);

const logout = async () => {
    await auth.logout();
    router.push('/login');
};

// RTL Support
const isRTL = computed(() => locale.value === 'ar');
watch(isRTL, (newVal) => {
  document.documentElement.dir = newVal ? 'rtl' : 'ltr';
}, { immediate: true });

</script>

<template>
  <div class="flex h-screen bg-[#F8FAFC] dark:bg-[#0F172A] font-sans transition-colors duration-500 overflow-hidden">
    
    <!-- Premium Sidebar -->
    <aside 
      class="w-[280px] h-full flex flex-col bg-white dark:bg-[#1E293B] border-r border-slate-200/60 dark:border-white/5 relative z-50 transition-all duration-300 shadow-2xl shadow-slate-200/50 dark:shadow-none"
    >
      
      <!-- Logo Section -->
      <div class="h-24 flex items-center px-8 border-b border-slate-100 dark:border-white/5 bg-slate-50/30 dark:bg-white/5 backdrop-blur-sm">
        <router-link to="/admin/dashboard" class="flex items-center gap-4 group w-full">
          <div class="relative w-11 h-11 flex-shrink-0">
            <div class="absolute inset-0 bg-blue-600 rounded-2xl blur-xl opacity-20 group-hover:opacity-40 transition-opacity duration-500"></div>
            <img src="/src/assets/v-logo.png" alt="Logo" class="relative w-full h-full object-contain drop-shadow-md">
          </div>
          <div class="flex flex-col">
            <h1 class="text-xl font-black tracking-tighter text-slate-900 dark:text-white leading-none">VALORA</h1>
            <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-[0.2em] mt-1">Admin Panel</span>
          </div>
        </router-link>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto py-8 px-4 space-y-2 no-scrollbar">
        <div class="px-4 mb-3">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest opacity-80 pl-1">Menu Principal</p>
        </div>

        <router-link 
          v-for="item in menuItems" 
          :key="item.path" 
          :to="item.path"
          class="group flex items-center justify-between px-5 py-4 rounded-[20px] transition-all duration-300 relative overflow-hidden"
          :class="isActive(item.path) 
            ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' 
            : 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/5 hover:text-slate-900 dark:hover:text-white'"
        >
          <!-- Active Indicator (Left Border) -->
          <div v-if="isActive(item.path)" class="absolute left-0 top-1/2 -translate-y-1/2 h-8 w-1 bg-white/20 rounded-r-full"></div>

          <div class="flex items-center gap-4 relative z-10">
            <component 
                :is="item.icon" 
                class="w-[22px] h-[22px] transition-transform duration-300 group-hover:scale-110"
                :class="isActive(item.path) ? 'text-white' : 'text-slate-400 group-hover:text-blue-500 dark:group-hover:text-blue-400'"
                stroke-width="2"
            />
            <span class="font-bold text-[14px] tracking-wide">{{ item.name }}</span>
          </div>
          
          <ChevronRight 
            v-if="item.arrow && !isActive(item.path)" 
            class="w-4 h-4 text-slate-300 dark:text-slate-600 opacity-0 group-hover:opacity-100 transition-all duration-300 -translate-x-2 group-hover:translate-x-0" 
            :class="{ 'rotate-180': isRTL }"
          />
        </router-link>
      </nav>

      <!-- Bottom Tools & Profile -->
      <div class="p-5 border-t border-slate-100 dark:border-white/5 bg-slate-50/80 dark:bg-[#0F172A]/80 backdrop-blur-md">
        
        <!-- Tools Row -->
        <div class="flex items-center justify-between mb-6 px-1">
             <LanguageSwitcher 
               class="origin-left scale-95" 
               :class="{ 'origin-right': isRTL }"
             />
             <button 
                @click="themeStore.toggleTheme" 
                class="w-10 h-10 flex items-center justify-center rounded-xl bg-white dark:bg-white/5 border border-slate-100 dark:border-white/5 text-slate-400 hover:text-amber-500 hover:border-amber-500/30 transition-all shadow-sm"
             >
                <Sun v-if="themeStore.theme === 'light'" class="w-5 h-5 transition-transform duration-500 hover:rotate-90" />
                <Moon v-else class="w-5 h-5 transition-transform duration-500 hover:-rotate-12" />
             </button>
        </div>

        <!-- User Profile Card -->
        <div class="flex items-center gap-4 p-4 rounded-[24px] bg-white dark:bg-[#1E293B] border border-slate-100 dark:border-white/5 shadow-lg shadow-slate-200/50 dark:shadow-none bg-gradient-to-br from-white to-slate-50 dark:from-[#1E293B] dark:to-[#0F172A] relative overflow-hidden group">
            <div class="absolute inset-0 bg-blue-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            
            <div class="w-11 h-11 rounded-full bg-slate-100 dark:bg-[#0F172A] flex items-center justify-center overflow-hidden border-2 border-slate-200 dark:border-white/10 flex-shrink-0 shadow-sm relative z-10">
                <img v-if="auth.user?.photo" :src="auth.user.photo" class="w-full h-full object-cover">
                <span v-else class="font-black text-slate-500 dark:text-slate-400 text-sm">{{ auth.user?.name?.charAt(0) || 'A' }}</span>
            </div>
            
            <div class="flex-1 min-w-0 relative z-10">
                <p class="text-[13px] font-black text-slate-900 dark:text-white truncate tracking-tight">{{ auth.user?.name || 'Admin' }}</p>
                <button @click="logout" class="text-[11px] font-bold text-red-500 hover:text-red-600 transition-colors text-left flex items-center gap-1.5 mt-0.5 group/logout">
                    <LogOut class="w-3 h-3 group-hover/logout:-translate-x-0.5 transition-transform" />
                    <span>Se déconnecter</span>
                </button>
            </div>
        </div>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 h-full overflow-y-auto relative scroll-smooth no-scrollbar bg-[#F8FAFC] dark:bg-[#0F172A]">
        <!-- Content Container -->
        <div class="max-w-[1600px] mx-auto p-8 lg:p-12 pb-32">
             <router-view v-slot="{ Component }">
                <transition 
                    enter-active-class="transition ease-out duration-500 delay-150" 
                    enter-from-class="opacity-0 translate-y-8 scale-95" 
                    enter-to-class="opacity-100 translate-y-0 scale-100" 
                    leave-active-class="transition ease-in duration-300" 
                    leave-from-class="opacity-100 translate-y-0" 
                    leave-to-class="opacity-0 -translate-y-8"
                    mode="out-in"
                >
                    <component :is="Component" />
                </transition>
            </router-view>
        </div>
    </main>
  </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
