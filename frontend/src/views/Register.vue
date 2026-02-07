<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

onMounted(() => {
    // Redirect to the new integrated Login/Register page
    router.replace('/login');
});
</script>

<template>
    <div class="min-h-screen bg-[#f6f7fa] flex items-center justify-center">
        <div class="animate-pulse flex flex-col items-center">
            <div class="w-16 h-16 bg-[#6FCF41] rounded-2xl mb-4"></div>
            <p class="text-white/60 font-medium tracking-widest">VALORA AUTH...</p>
        </div>
    </div>
</template>
