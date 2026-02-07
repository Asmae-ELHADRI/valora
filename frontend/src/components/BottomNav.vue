<script setup>
import { LayoutGrid, Search, Plus, MessageSquare, User } from 'lucide-vue-next';
import { useRoute } from 'vue-router';

const route = useRoute();

const isActive = (path) => route.path === path;
</script>

<template>
  <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 px-6 py-4 flex justify-between items-center z-50 md:hidden">
    <router-link to="/dashboard-provider" class="p-2 transition-colors" :class="isActive('/dashboard-provider') ? 'text-slate-900' : 'text-slate-300 hover:text-slate-500'">
      <LayoutGrid class="w-6 h-6" :class="{'fill-current': isActive('/dashboard-provider')}" />
    </router-link>
    
    <router-link to="/search" class="p-2 transition-colors" :class="isActive('/search') ? 'text-slate-900' : 'text-slate-300 hover:text-slate-500'">
      <Search class="w-6 h-6" />
    </router-link>

    <div class="relative -top-8">
        <router-link to="/post-offer" class="bg-premium-yellow text-slate-900 rounded-full w-14 h-14 flex items-center justify-center shadow-lg shadow-yellow-200 hover:scale-105 transition-transform active:scale-95 border-4 border-white">
            <Plus class="w-7 h-7" />
        </router-link>
    </div>

    <router-link to="/messages" class="p-2 transition-colors" :class="isActive('/messages') ? 'text-slate-900' : 'text-slate-300 hover:text-slate-500'">
      <MessageSquare class="w-6 h-6" :class="{'fill-current': isActive('/messages')}" />
    </router-link>

    <router-link to="/profile" class="p-2 transition-colors" :class="isActive('/profile') ? 'text-slate-900' : 'text-slate-300 hover:text-slate-500'">
      <User class="w-6 h-6" :class="{'fill-current': isActive('/profile')}" />
    </router-link>
  </div>
</template>
