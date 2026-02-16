<script setup>
import { ref } from 'vue';
import { Mail, Phone, MapPin, Send, CheckCircle, AlertCircle } from 'lucide-vue-next';
import api from '../services/api';

const form = ref({
    name: '',
    email: '',
    subject: '',
    message: ''
});

const status = ref({
    loading: false,
    success: false,
    error: null
});

const submitForm = async () => {
    status.value.loading = true;
    status.value.error = null;
    status.value.success = false;

    // Simulate API call or real call if backend supports it
    try {
        // await api.post('/contact', form.value); // Uncomment if backend exists
        await new Promise(resolve => setTimeout(resolve, 1500)); // Mock delay
        status.value.success = true;
        form.value = { name: '', email: '', subject: '', message: '' };
    } catch (err) {
        status.value.error = "Une erreur est survenue. Veuillez réessayer.";
    } finally {
        status.value.loading = false;
    }
};
</script>

<template>
  <div class="min-h-screen bg-slate-50 font-sans text-slate-900 selection:bg-[#facc15] selection:text-slate-900">
    
    <!-- Hero Section -->
    <header class="py-24 bg-[#0f172a] text-white text-center px-6 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-full bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-5"></div>
        <div class="relative z-10 max-w-3xl mx-auto space-y-6">
            <h1 class="text-4xl md:text-6xl font-black tracking-tight">Contactez-nous</h1>
            <p class="text-slate-400 text-lg">Une question ? Un projet ? Notre équipe est à votre écoute.</p>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-6 py-16 -mt-10 relative z-20">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            
            <!-- Contact Info -->
            <div class="space-y-8">
                <div class="bg-white p-8 rounded-[2rem] shadow-xl shadow-slate-200/50 space-y-8">
                    <h3 class="text-2xl font-black text-[#0f172a]">Nos Coordonnées</h3>
                    
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-[#facc15]/10 rounded-xl flex items-center justify-center text-[#facc15] shrink-0">
                            <MapPin class="w-6 h-6" />
                        </div>
                        <div>
                            <h4 class="font-bold text-[#0f172a]">Bureau Principal</h4>
                            <p class="text-slate-500 text-sm mt-1">123 Boulevard Mohamed VI, Casablanca, Maroc</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-[#2563eb]/10 rounded-xl flex items-center justify-center text-[#2563eb] shrink-0">
                            <Mail class="w-6 h-6" />
                        </div>
                        <div>
                            <h4 class="font-bold text-[#0f172a]">Email</h4>
                            <p class="text-slate-500 text-sm mt-1">contact@valora.ma</p>
                            <p class="text-slate-500 text-sm">support@valora.ma</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-[#10b981]/10 rounded-xl flex items-center justify-center text-[#10b981] shrink-0">
                            <Phone class="w-6 h-6" />
                        </div>
                        <div>
                            <h4 class="font-bold text-[#0f172a]">Téléphone</h4>
                            <p class="text-slate-500 text-sm mt-1">+212 5 22 00 00 00</p>
                            <p class="text-slate-400 text-xs mt-1">Lun - Ven, 9h - 18h</p>
                        </div>
                    </div>
                </div>

                <div class="bg-[#0f172a] p-8 rounded-[2rem] shadow-xl text-white relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-[#facc15] rounded-full blur-[80px] opacity-20"></div>
                    <h3 class="text-xl font-bold mb-4">Besoin d'aide immédiate ?</h3>
                    <p class="text-slate-400 text-sm mb-6">Consultez notre FAQ pour trouver des réponses rapides aux questions fréquentes.</p>
                    <button class="w-full py-3 bg-white/10 hover:bg-white/20 rounded-xl font-bold text-xs uppercase tracking-widest transition-colors backdrop-blur-sm border border-white/10">
                        Voir la FAQ
                    </button>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="bg-white p-10 rounded-[2.5rem] shadow-2xl shadow-slate-200/50">
                <h3 class="text-2xl font-black text-[#0f172a] mb-8">Envoyer un message</h3>
                
                <form @submit.prevent="submitForm" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Nom complet</label>
                            <input v-model="form.name" type="text" required class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#facc15] outline-none font-medium transition-all" placeholder="Votre nom">
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Email</label>
                            <input v-model="form.email" type="email" required class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#facc15] outline-none font-medium transition-all" placeholder="votre@email.com">
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                         <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Sujet</label>
                         <input v-model="form.subject" type="text" required class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#facc15] outline-none font-medium transition-all" placeholder="Ex: Devenir partenaire">
                    </div>

                    <div class="space-y-2">
                         <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Message</label>
                         <textarea v-model="form.message" rows="5" required class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#facc15] outline-none font-medium transition-all" placeholder="Comment pouvons-nous vous aider ?"></textarea>
                    </div>

                    <div v-if="status.success" class="flex items-center gap-2 text-green-600 bg-green-50 p-4 rounded-xl text-sm font-bold">
                        <CheckCircle class="w-5 h-5" /> Message envoyé avec succès !
                    </div>

                    <div v-if="status.error" class="flex items-center gap-2 text-red-600 bg-red-50 p-4 rounded-xl text-sm font-bold">
                        <AlertCircle class="w-5 h-5" /> {{ status.error }}
                    </div>

                    <button :disabled="status.loading" type="submit" class="w-full py-4 bg-[#0f172a] text-white rounded-xl font-black uppercase tracking-widest hover:bg-[#334155] transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span v-if="status.loading">Envoi en cours...</span>
                        <span v-else class="flex items-center gap-2">Envoyer le message <Send class="w-4 h-4" /></span>
                    </button>
                </form>
            </div>

        </div>
    </div>

  </div>
</template>
