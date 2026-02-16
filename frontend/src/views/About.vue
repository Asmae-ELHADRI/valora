<script setup>
import { CheckCircle, Users, Award, Briefcase, Target, Heart } from 'lucide-vue-next';

const stats = [
    { value: '15k+', label: 'Utilisateurs Actifs' },
    { value: '500+', label: 'Artisans Vérifiés' },
    { value: '98%', label: 'Satisfaction Client' },
    { value: '24/7', label: 'Support Dédié' },
];

const values = [
    { icon: CheckCircle, title: 'Confiance', desc: 'Chaque artisan est rigoureusement vérifié pour garantir votre sécurité.' },
    { icon: Award, title: 'Qualité', desc: 'Nous ne retenons que les meilleurs talents pour vos projets.' },
    { icon: Heart, title: 'Passion', desc: 'Nous aimons ce que nous faisons et cela se voit dans nos résultats.' },
];
</script>

<template>
  <div class="min-h-screen bg-slate-50 font-sans text-slate-900 selection:bg-[#facc15] selection:text-slate-900">
    
    <!-- Hero Section -->
    <header class="relative py-32 bg-[#0f172a] text-white overflow-hidden">
        <!-- Decor -->
        <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-[#facc15]/5 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-[#2563eb]/5 rounded-full blur-[100px]"></div>

        <div class="max-w-7xl mx-auto px-6 relative z-10 text-center space-y-8">
            <span class="text-[#facc15] font-black text-xs uppercase tracking-widest bg-white/5 px-4 py-2 rounded-full border border-white/10">À Propos de Valora</span>
            <h1 class="text-5xl md:text-7xl font-black tracking-tight leading-tight">
                Connecter l'excellence <br /> 
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#facc15] to-[#f59e0b]">au besoin.</span>
            </h1>
            <p class="text-slate-400 text-lg max-w-2xl mx-auto leading-relaxed">
                Valora est née d'une mission simple : simplifier la recherche de professionnels de confiance au Maroc tout en valorisant le savoir-faire local.
            </p>
        </div>
    </header>

    <!-- Mission & Vision -->
    <section class="py-24 px-6 relative z-10 -mt-20">
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-12">
            <div class="bg-white p-10 rounded-[3rem] shadow-xl shadow-slate-200/50 flex flex-col justify-center space-y-6">
                <div class="w-16 h-16 bg-[#facc15]/10 rounded-2xl flex items-center justify-center text-[#facc15]">
                    <Target class="w-8 h-8" />
                </div>
                <h2 class="text-3xl font-black text-[#0f172a]">Notre Mission</h2>
                <p class="text-slate-600 leading-relaxed">
                    Nous nous engageons à transformer le secteur des services à domicile en apportant transparence, sécurité et rapidité. Nous voulons que chaque foyer puisse trouver l'aide dont il a besoin en quelques clics, sans stress.
                </p>
            </div>
             <div class="bg-[#0f172a] p-10 rounded-[3rem] shadow-xl shadow-slate-900/50 flex flex-col justify-center space-y-6 text-white">
                <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center text-[#facc15]">
                    <Users class="w-8 h-8" />
                </div>
                <h2 class="text-3xl font-black">Notre Vision</h2>
                <p class="text-slate-400 leading-relaxed">
                    Devenir la référence incontournable en Afrique pour la mise en relation entre particuliers et professionnels, en créant un écosystème où le mérite et la qualité priment.
                </p>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="py-24 px-6 bg-white">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div v-for="stat in stats" :key="stat.label" class="text-center space-y-2">
                    <h3 class="text-5xl font-black text-[#0f172a]">{{ stat.value }}</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ stat.label }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Values -->
    <section class="py-24 px-6 bg-slate-50">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                 <h2 class="text-4xl font-black text-[#0f172a] mb-4">Nos Valeurs</h2>
                 <p class="text-slate-500 max-w-xl mx-auto">Ce qui nous guide au quotidien pour vous offrir la meilleure expérience possible.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div v-for="val in values" :key="val.title" class="bg-white p-8 rounded-[2rem] border border-slate-100 hover:shadow-xl transition-all duration-300 text-center">
                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-[#0f172a] mx-auto mb-6">
                        <component :is="val.icon" class="w-8 h-8" />
                    </div>
                    <h3 class="text-xl font-bold text-[#0f172a] mb-3">{{ val.title }}</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">{{ val.desc }}</p>
                </div>
            </div>
        </div>
    </section>

  </div>
</template>
