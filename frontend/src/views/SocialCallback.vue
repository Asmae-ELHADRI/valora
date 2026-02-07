<script setup>
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../store/auth';
import { Loader2 } from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

onMounted(async () => {
    const code = route.query.code;
    const provider = route.query.provider;

    if (!code || !provider) {
        router.push('/login?error=Invalid auth callback parameters');
        return;
    }

    try {
        await auth.handleSocialCallback(provider, code);
        router.push('/dashboard');
    } catch (error) {
        console.error('Social auth failed:', error);
        router.push('/login?error=Social authentication failed');
    }
});
</script>

<template>
    <div class="min-h-screen bg-[#111827] flex items-center justify-center">
        <div class="text-center">
            <Loader2 class="w-12 h-12 text-[#D4A373] animate-spin mx-auto mb-4" />
            <h2 class="text-xl font-bold text-white">Authentification en cours...</h2>
            <p class="text-gray-400 mt-2">Veuillez patienter pendant que nous finalisons votre connexion.</p>
        </div>
    </div>
</template>
