<script setup>
import { ArrowRight, Shield, Star, Zap, Users, Briefcase, Globe, Sparkles, TrendingUp } from 'lucide-vue-next';
</script>

<template>
  <div class="relative min-h-screen bg-[#fafaf9] overflow-hidden">
    <!-- Animated background elements -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
      <div class="absolute top-[-10%] left-[-5%] w-[500px] h-[500px] bg-premium-blue/5 rounded-full blur-[120px] animate-blob"></div>
      <div class="absolute bottom-[-10%] right-[-5%] w-[600px] h-[600px] bg-premium-brown/5 rounded-full blur-[120px] animate-blob animation-delay-2000"></div>
      <div class="absolute top-[20%] right-[10%] w-[300px] h-[300px] bg-premium-yellow/10 rounded-full blur-[100px] animate-blob animation-delay-4000"></div>
    </div>

    <!-- Hero Section -->
    <section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 px-4 sm:px-6 lg:px-8">
      <div class="max-w-7xl mx-auto">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
          <div class="text-left space-y-8 relative z-10">
            <div class="inline-flex items-center space-x-2 px-4 py-2 bg-white/60 backdrop-blur-md border border-white/40 rounded-full shadow-sm animate-fade-in-down">
              <Sparkles class="w-4 h-4 text-premium-yellow" />
              <span class="text-xs font-bold text-premium-blue uppercase tracking-widest">{{ $t('home.hero_badge', 'La nouvelle ère des services') }}</span>
            </div>
            
            <h1 class="text-6xl md:text-8xl font-black text-premium-blue tracking-tighter leading-[0.9] animate-fade-in">
              VALORA<span class="text-premium-yellow">.</span>
              <span class="block text-4xl md:text-5xl mt-4 font-playfair italic text-premium-brown lowercase tracking-normal">{{ $t('home.hero_subtitle') }}</span>
            </h1>

            <p class="text-xl text-slate-500 max-w-xl font-medium leading-relaxed animate-fade-in animation-delay-300">
              {{ $t('home.hero_desc') }}
            </p>

            <div class="flex flex-col sm:flex-row items-center gap-6 pt-4 animate-fade-in animation-delay-500">
              <router-link to="/register" class="group relative w-full sm:w-auto px-10 py-5 bg-premium-blue text-white rounded-2xl font-black shadow-2xl shadow-premium-blue/20 hover:scale-105 active:scale-95 transition-all duration-300 overflow-hidden text-center">
                <span class="relative z-10 flex items-center justify-center">
                  {{ $t('home.cta_start') }}
                  <ArrowRight class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" />
                </span>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
              </router-link>
              
              <router-link to="/search" class="w-full sm:w-auto px-10 py-5 bg-white/60 backdrop-blur-md border border-white/40 text-premium-blue rounded-2xl font-black hover:bg-white hover:shadow-xl transition-all duration-300 text-center">
                {{ $t('home.cta_explore') }}
              </router-link>
            </div>
          </div>

          <div class="relative flex justify-center lg:justify-end animate-fade-in animation-delay-700">
            <!-- Premium visual element -->
            <div class="relative w-full max-w-[500px] aspect-square">
              <div class="absolute inset-0 bg-gradient-to-br from-premium-blue to-premium-brown rounded-[4rem] rotate-6 opacity-10 blur-2xl"></div>
              <div class="relative h-full w-full bg-white/40 backdrop-blur-2xl border border-white/60 rounded-[4rem] shadow-2xl flex items-center justify-center overflow-hidden p-12 group hover:rotate-1 transition-transform duration-700">
                <div class="absolute top-0 right-0 p-8">
                  <Globe class="w-24 h-24 text-premium-blue/5 animate-pulse" />
                </div>
                <div class="space-y-6 relative z-10">
                   <div class="flex items-center space-x-4">
                     <div class="w-16 h-16 bg-premium-yellow rounded-2xl flex items-center justify-center shadow-lg shadow-premium-yellow/30">
                       <Zap class="w-8 h-8 text-premium-blue" />
                     </div>
                     <div>
                       <div class="text-[10px] font-black text-premium-blue/40 uppercase tracking-widest">Performance</div>
                       <div class="text-xl font-bold text-premium-blue">Plus de 12k missions</div>
                     </div>
                   </div>
                   <div class="h-px bg-premium-blue/5 w-full"></div>
                   <div class="flex items-center space-x-4">
                     <div class="w-16 h-16 bg-premium-blue rounded-2xl flex items-center justify-center shadow-lg shadow-premium-blue/30">
                       <Users class="w-8 h-8 text-white" />
                     </div>
                     <div>
                       <div class="text-[10px] font-black text-premium-blue/40 uppercase tracking-widest">Confiance</div>
                       <div class="text-xl font-bold text-premium-blue">5k+ Professionnels</div>
                     </div>
                   </div>
                </div>
                <!-- Decorative grid -->
                <div class="absolute bottom-[-20%] left-[-20%] w-[150%] h-[150%] grid-pattern opacity-10 pointer-events-none"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Stats Section -->
    <section class="py-24 bg-premium-blue relative overflow-hidden">
      <div class="absolute top-0 left-0 w-full h-full opacity-10 pointer-events-none">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-white/20 via-transparent to-transparent"></div>
      </div>
      
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-12 lg:gap-24">
          <div class="text-center group border-r border-white/5 last:border-none">
            <h4 class="text-5xl md:text-6xl font-black text-white mb-2 transition-transform group-hover:scale-110 duration-500">5k<span class="text-premium-yellow">+</span></h4>
            <p class="text-xs font-black text-white/40 uppercase tracking-[0.3em]">{{ $t('home.stats.providers') }}</p>
          </div>
          <div class="text-center group border-r border-white/5 last:border-none">
            <h4 class="text-5xl md:text-6xl font-black text-white mb-2 transition-transform group-hover:scale-110 duration-500">12k<span class="text-premium-yellow">+</span></h4>
            <p class="text-xs font-black text-white/40 uppercase tracking-[0.3em]">{{ $t('home.stats.missions') }}</p>
          </div>
          <div class="text-center group border-r border-white/5 last:border-none">
            <h4 class="text-5xl md:text-6xl font-black text-white mb-2 transition-transform group-hover:scale-110 duration-500">4.9<span class="text-premium-yellow">/5</span></h4>
            <p class="text-xs font-black text-white/40 uppercase tracking-[0.3em]">{{ $t('home.stats.rating') }}</p>
          </div>
          <div class="text-center group">
            <h4 class="text-5xl md:text-6xl font-black text-white mb-2 transition-transform group-hover:scale-110 duration-500">24<span class="text-premium-yellow">/7</span></h4>
            <p class="text-xs font-black text-white/40 uppercase tracking-[0.3em]">{{ $t('home.stats.support') }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="py-32 px-4 relative">
       <div class="max-w-7xl mx-auto">
          <div class="text-center max-w-3xl mx-auto mb-24">
            <div class="inline-block px-4 py-1.5 bg-premium-yellow/10 rounded-full text-[10px] font-black text-premium-brown uppercase tracking-widest mb-4">L'excellence au quotidien</div>
            <h2 class="text-4xl md:text-6xl font-black text-premium-blue tracking-tight leading-none mb-6">
              {{ $t('home.why_valora') }}
            </h2>
            <p class="text-slate-500 text-lg font-medium leading-relaxed">
              {{ $t('home.why_desc') }}
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Feature Cards with Glassmorphism -->
            <div class="group p-10 bg-white border border-slate-100 rounded-[3rem] shadow-sm hover:shadow-2xl hover:-translate-y-4 transition-all duration-500 flex flex-col items-center text-center">
               <div class="w-20 h-20 bg-blue-50 text-blue-600 rounded-3xl flex items-center justify-center mb-8 rotate-3 group-hover:rotate-12 group-hover:scale-110 transition-all duration-500">
                  <Shield class="w-10 h-10" />
               </div>
               <h3 class="text-2xl font-black text-premium-blue mb-4 tracking-tight">{{ $t('home.security_title') }}</h3>
               <p class="text-slate-500 font-medium leading-relaxed">{{ $t('home.security_desc') }}</p>
            </div>

            <div class="group p-10 bg-white border border-slate-100 rounded-[3rem] shadow-sm hover:shadow-2xl hover:-translate-y-4 transition-all duration-500 flex flex-col items-center text-center translate-y-8">
               <div class="w-20 h-20 bg-amber-50 text-amber-600 rounded-3xl flex items-center justify-center mb-8 -rotate-3 group-hover:-rotate-12 group-hover:scale-110 transition-all duration-500">
                  <Star class="w-10 h-10" />
               </div>
               <h3 class="text-2xl font-black text-premium-blue mb-4 tracking-tight">{{ $t('home.excellence_title') }}</h3>
               <p class="text-slate-500 font-medium leading-relaxed">{{ $t('home.excellence_desc') }}</p>
            </div>

            <div class="group p-10 bg-white border border-slate-100 rounded-[3rem] shadow-sm hover:shadow-2xl hover:-translate-y-4 transition-all duration-500 flex flex-col items-center text-center">
               <div class="w-20 h-20 bg-premium-blue text-white rounded-3xl flex items-center justify-center mb-8 rotate-6 group-hover:rotate-12 group-hover:scale-110 transition-all duration-500">
                  <Zap class="w-10 h-10" />
               </div>
               <h3 class="text-2xl font-black text-premium-blue mb-4 tracking-tight">{{ $t('home.simplicity_title') }}</h3>
               <p class="text-slate-500 font-medium leading-relaxed">{{ $t('home.simplicity_desc') }}</p>
            </div>
          </div>
       </div>
    </section>

    <!-- Bottom CTA -->
    <section class="pb-32 px-4 relative">
       <div class="max-w-7xl mx-auto relative group">
          <div class="absolute inset-0 bg-premium-brown rounded-[5rem] translate-y-4 translate-x-4 opacity-5 group-hover:translate-x-6 group-hover:translate-y-6 transition-transform duration-500"></div>
          <div class="relative bg-premium-blue-dark py-24 px-8 md:px-20 rounded-[5rem] overflow-hidden text-center">
              <div class="absolute -top-24 -right-24 w-96 h-96 bg-premium-yellow/10 rounded-full blur-[100px] animate-pulse"></div>
              <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-premium-brown/20 rounded-full blur-[100px]"></div>
              
              <div class="relative z-10 space-y-10 max-w-4xl mx-auto">
                <h2 class="text-5xl md:text-7xl font-black text-white tracking-tighter leading-[1]">
                  {{ $t('home.ready_title') }}
                </h2>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-6">
                   <router-link to="/register" class="w-full sm:w-auto px-12 py-6 bg-premium-yellow text-premium-blue rounded-3xl font-black hover:bg-yellow-400 hover:scale-105 active:scale-95 transition-all text-xs uppercase tracking-[0.2em]">
                     {{ $t('home.create_account') }}
                   </router-link>
                   <div class="text-white/40 font-black text-xs uppercase tracking-widest flex items-center">
                     <div class="w-8 h-px bg-white/20 mr-4"></div>
                     {{ $t('home.become_provider') }}
                     <div class="w-8 h-px bg-white/20 ml-4"></div>
                   </div>
                </div>
              </div>
          </div>
       </div>
    </section>
  </div>
</template>

<style scoped>
@keyframes blob {
  0% { transform: translate(0px, 0px) scale(1); }
  33% { transform: translate(30px, -50px) scale(1.1); }
  66% { transform: translate(-20px, 20px) scale(0.9); }
  100% { transform: translate(0px, 0px) scale(1); }
}

.animate-blob {
  animation: blob 7s infinite;
}

.animation-delay-2000 {
  animation-delay: 2s;
}

.animation-delay-4000 {
  animation-delay: 4s;
}

.animate-fade-in {
  animation: fadeIn 1s ease-out forwards;
}

.animate-fade-in-down {
  animation: fadeInDown 1s ease-out forwards;
}

.animation-delay-300 { animation-delay: 0.3s; }
.animation-delay-500 { animation-delay: 0.5s; }
.animation-delay-700 { animation-delay: 0.7s; }

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeInDown {
  from { opacity: 0; transform: translateY(-20px); }
  to { opacity: 1; transform: translateY(0); }
}

.grid-pattern {
  background-image: radial-gradient(circle, #0f172a 1px, transparent 1px);
  background-size: 30px 30px;
}

.bg-premium-blue {
  background-color: #0f172a;
}

.text-premium-blue {
  color: #0f172a;
}

.bg-premium-yellow {
  background-color: #facc15;
}

.text-premium-yellow {
  color: #facc15;
}

.text-premium-brown {
  color: #92400e;
}

.bg-premium-blue-dark {
  background-color: #020617;
}

.font-playfair {
  font-family: 'Playfair Display', serif;
}

/* Glassmorphism utility */
.backdrop-blur-2xl {
  backdrop-filter: blur(40px);
  -webkit-backdrop-filter: blur(40px);
}
</style>
