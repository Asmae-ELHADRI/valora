<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import { Download, Printer, ArrowLeft, ShieldCheck, BadgeCheck } from 'lucide-vue-next';
import valoraLogo from '../assets/logo-valora.png';

const auth = useAuthStore();
const certificateData = ref(null);
const loading = ref(true);
const error = ref(null);

const fetchCertificate = async () => {
    try {
        const response = await api.get('/api/provider/certificate');
        certificateData.value = response.data;
    } catch (err) {
        error.value = err.response?.data?.message || "Erreur lors de la récupération du certificat";
    } finally {
        loading.value = false;
    }
};

const printCertificate = () => {
    window.print();
};

onMounted(() => {
    fetchCertificate();
});

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8 print:bg-white print:py-0 print:px-0">
        <!-- Dashboard Navigation -->
        <div class="max-w-4xl mx-auto mb-8 flex justify-between items-center print:hidden">
            <router-link to="/dashboard-provider" class="flex items-center text-slate-500 hover:text-premium-blue transition-colors font-bold uppercase text-xs tracking-widest">
                <ArrowLeft class="w-4 h-4 mr-2" />
                Retour au tableau de bord
            </router-link>
            
            <div class="flex space-x-4">
                <button @click="printCertificate" class="flex items-center bg-white border border-slate-200 px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest text-slate-700 hover:bg-slate-50 shadow-sm transition-all active:scale-95">
                    <Printer class="w-4 h-4 mr-2" />
                    Imprimer / PDF
                </button>
            </div>
        </div>

        <!-- Certificate Container -->
        <div v-if="loading" class="flex flex-col items-center justify-center py-32">
            <div class="w-16 h-16 border-4 border-premium-blue border-t-transparent rounded-full animate-spin"></div>
            <p class="mt-4 text-slate-400 font-bold uppercase text-xs tracking-widest animate-pulse">Génération du certificat...</p>
        </div>

        <div v-else-if="error" class="max-w-md mx-auto bg-white p-12 rounded-[3rem] shadow-2xl text-center">
            <div class="w-20 h-20 bg-red-50 text-red-500 rounded-3xl flex items-center justify-center mx-auto mb-6">
                <ShieldCheck class="w-10 h-10" />
            </div>
            <h3 class="text-2xl font-black text-slate-800 mb-2">Accès restreint</h3>
            <p class="text-slate-500 font-medium mb-8">{{ error }}</p>
            <router-link to="/dashboard-provider" class="inline-block bg-premium-blue text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-[0.2em] shadow-xl hover:scale-105 transition-all">
                Mon Tableau de Bord
            </router-link>
        </div>

        <div v-else class="certificate-wrapper max-w-[900px] mx-auto bg-white shadow-2xl rounded-sm overflow-hidden border-[16px] border-slate-100 relative print:shadow-none print:border-none print:m-0">
            <!-- Decorative Corners -->
            <div class="absolute top-0 left-0 w-32 h-32 border-t-4 border-l-4 border-premium-yellow/30 m-4"></div>
            <div class="absolute top-0 right-0 w-32 h-32 border-t-4 border-r-4 border-premium-yellow/30 m-4"></div>
            <div class="absolute bottom-0 left-0 w-32 h-32 border-b-4 border-l-4 border-premium-yellow/30 m-4"></div>
            <div class="absolute bottom-0 right-0 w-32 h-32 border-b-4 border-r-4 border-premium-yellow/30 m-4"></div>

            <!-- Pattern Background -->
            <div class="absolute inset-0 z-0 opacity-[0.03] pointer-events-none" style="background-image: radial-gradient(#0f172a 1px, transparent 1px); background-size: 20px 20px;"></div>

            <div class="certificate-content relative z-10 p-16 sm:p-24 flex flex-col items-center text-center">
                <!-- Branding -->
                <div class="flex flex-col items-center mb-12">
                    <img :src="valoraLogo" class="w-24 h-24 mb-4 object-contain" alt="Valora">
                    <h1 class="text-4xl font-black text-premium-blue tracking-tighter uppercase italic">VALORA<span class="text-premium-yellow">.</span></h1>
                    <div class="w-12 h-1 bg-premium-yellow mt-2 rounded-full"></div>
                </div>

                <!-- Main Text -->
                <div class="space-y-6 mb-16">
                    <h2 class="text-xs font-black uppercase tracking-[0.6em] text-premium-brown opacity-60">Certificat d'Excellence Professionnelle</h2>
                    <p class="text-slate-500 font-medium italic text-lg">Le présent document atteste que</p>
                    <h3 class="text-5xl font-black text-premium-blue tracking-tight py-4">{{ certificateData.prestataire.user.name }}</h3>
                    <div class="flex items-center justify-center space-x-3 text-premium-blue">
                        <div class="h-px w-12 bg-slate-200"></div>
                        <span class="font-black uppercase text-sm tracking-widest">{{ certificateData.prestataire.category.name }}</span>
                        <div class="h-px w-12 bg-slate-200"></div>
                    </div>
                </div>

                <div class="max-w-2xl mx-auto space-y-8 mb-20 text-slate-600 leading-relaxed font-medium">
                    <p>
                        A fait preuve d'un engagement exceptionnel et d'une qualité de service exemplaire sur la plateforme **Valora**, 
                        en réalisant avec succès plus de **10 missions professionnelles** vérifiées auprès de notre communauté.
                    </p>
                    <p>
                        Ce titre de **PRESTATAIRE CERTIFIÉ** est décerné en reconnaissance de son expertise, de sa fiabilité 
                        et de sa contribution positive à l'écosystème Valora.
                    </p>
                </div>

                <div class="grid grid-cols-2 w-full gap-20 pt-12 border-t border-slate-100">
                    <div class="flex flex-col items-center">
                        <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-4">Date de délivrance</span>
                        <span class="font-bold text-premium-blue">{{ formatDate(certificateData.certified_at) }}</span>
                    </div>
                    <div class="flex flex-col items-center">
                        <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-4">Cachet d'Authenticité</span>
                        <div class="relative">
                            <BadgeCheck class="w-16 h-16 text-premium-yellow fill-premium-yellow/10" />
                            <div class="absolute -top-2 -right-2 bg-premium-blue text-white text-[8px] font-black p-1 px-2 rounded-full border-2 border-white shadow-lg">VÉRIFIÉ</div>
                        </div>
                    </div>
                </div>

                <div class="mt-20">
                    <p class="text-[9px] font-black uppercase text-slate-300 tracking-[0.4em]">Identifiant Unique : {{ certificateData.certificate_id }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@1,900&display=swap');

.font-playfair {
    font-family: 'Playfair Display', serif;
}

@media print {
    @page {
        size: A4 landscape;
        margin: 0;
    }
    .min-h-screen {
        padding: 0 !important;
        background: white !important;
    }
    .certificate-wrapper {
        box-shadow: none !important;
        border: none !important;
        width: 100% !important;
        max-width: 100% !important;
    }
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
</style>
