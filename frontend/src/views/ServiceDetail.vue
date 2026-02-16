<script setup>
import { useRoute } from 'vue-router';
import { computed, onMounted } from 'vue';
import { services } from '../data/services.js';
import { CheckCircle, ArrowRight, Star } from 'lucide-vue-next';

const route = useRoute();

const service = computed(() => {
    return services.find(s => s.slug === route.params.slug);
});

onMounted(() => {
    window.scrollTo(0, 0);
});
</script>

<template>
  <div v-if="service" class="min-h-screen bg-slate-50 font-sans text-slate-900 selection:bg-[#facc15] selection:text-slate-900">
    
    <!-- Hero Section -->
    <header class="relative h-[50vh] md:h-[60vh] flex items-center justify-center overflow-hidden bg-[#0f172a] text-white">
      <div class="absolute inset-0 z-0">
         <img :src="service.image" class="w-full h-full object-cover opacity-30" :alt="service.title">
         <div class="absolute inset-0 bg-gradient-to-t from-[#0f172a] via-[#0f172a]/40 to-transparent"></div>
      </div>

      <div class="relative z-10 max-w-4xl mx-auto px-6 text-center space-y-6 pt-20">
         <div :class="`inline-flex items-center justify-center w-16 h-16 rounded-2xl ${service.bg} ${service.color} mb-4 animate-in fade-in zoom-in duration-700`">
            <component :is="service.icon" class="w-8 h-8" />
         </div>
         
         <h1 class="text-3xl md:text-6xl font-black tracking-tight leading-tight animate-in fade-in slide-in-from-bottom-4 duration-1000">
            {{ service.title }}
         </h1>
         
         <p class="text-base md:text-xl text-slate-300 max-w-2xl mx-auto font-medium leading-relaxed animate-in fade-in slide-in-from-bottom-4 duration-1000 delay-200">
            {{ service.shortDesc }}
         </p>
      </div>
    </header>

    <!-- Content Section -->
    <section class="py-12 md:py-24 px-4 md:px-6 -mt-16 md:-mt-20 relative z-20">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white p-6 md:p-10 rounded-[2rem] md:rounded-[3rem] shadow-xl shadow-slate-200/50">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                    <!-- Main Description -->
                    <div class="md:col-span-2 space-y-8">
                        <h2 class="text-3xl font-black text-[#0f172a]">À propos du service</h2>
                        <p class="text-slate-600 leading-relaxed text-lg">
                            {{ service.description }}
                        </p>

                        <div class="space-y-6 pt-6 border-t border-slate-100">
                            <h3 class="text-xl font-bold text-[#0f172a]">Ce qui est inclus :</h3>
                            <ul class="space-y-4">
                                <li v-for="(feature, index) in service.features" :key="index" class="flex items-start gap-3 group">
                                    <CheckCircle class="w-6 h-6 text-[#facc15] shrink-0 group-hover:scale-110 transition-transform" />
                                    <span class="text-slate-700 font-medium">{{ feature }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Sidebar CTA -->
                    <div class="space-y-6">
                        <div class="bg-slate-50 p-8 rounded-[2rem] border border-slate-100 sticky top-32">
                            <h3 class="text-xl font-black text-[#0f172a] mb-4">Intéressé ?</h3>
                            <p class="text-sm text-slate-500 mb-6">Trouvez dès maintenant un expert qualifié pour ce service.</p>
                            
                            <a href="/search" class="w-full py-4 bg-[#facc15] text-[#0f172a] rounded-xl font-black uppercase tracking-widest hover:bg-[#fbbf24] transition-all flex items-center justify-center gap-2 mb-3 shadow-lg shadow-yellow-400/20">
                                Trouver un artisan
                                <ArrowRight class="w-4 h-4" />
                            </a>
                             <a href="/post-offer" class="w-full py-4 bg-white border border-slate-200 text-[#0f172a] rounded-xl font-black uppercase tracking-widest hover:bg-slate-50 transition-all flex items-center justify-center gap-2">
                                Publier une offre
                            </a>

                            <div class="mt-8 pt-8 border-t border-slate-200 text-center">
                                <div class="flex justify-center -space-x-2 mb-3">
                                    <img src="https://i.pravatar.cc/100?img=1" class="w-8 h-8 rounded-full border-2 border-white">
                                    <img src="https://i.pravatar.cc/100?img=2" class="w-8 h-8 rounded-full border-2 border-white">
                                    <img src="https://i.pravatar.cc/100?img=3" class="w-8 h-8 rounded-full border-2 border-white">
                                </div>
                                <p class="text-xs font-bold text-slate-400">Plus de 500+ experts disponibles</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

  </div>
  <div v-else class="min-h-screen flex items-center justify-center bg-slate-50">
      <div class="text-center">
          <h1 class="text-4xl font-black text-slate-300">Service introuvable</h1>
          <a href="/" class="text-[#facc15] font-bold mt-4 inline-block hover:underline">Retour à l'accueil</a>
      </div>
  </div>
</template>
