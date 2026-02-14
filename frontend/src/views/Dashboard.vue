<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../store/auth';

const auth = useAuthStore();
const router = useRouter();

onMounted(async () => {
  if (!auth.user) {
    try {
      await auth.fetchUser();
    } catch {
      router.push('/login');
      return;
    }
  }

  if (auth.isAdmin) {
    router.push({ name: 'AdminDashboard' });
  } else if (auth.user?.role === 'provider' || auth.user?.role === 'prestataire') {
    router.push({ name: 'DashboardPrestataire' });
  } else {
    router.push({ name: 'DashboardClient' });
  }
});
</script>

<template>
  <div class="flex items-center justify-center min-h-[60vh]">
    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
  </div>
</template>
