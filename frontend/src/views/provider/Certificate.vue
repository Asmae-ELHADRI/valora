<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../services/api';
import { Download, ArrowLeft, ShieldCheck, Award } from 'lucide-vue-next';
import html2pdf from 'html2pdf.js';

const router = useRouter();
const loading = ref(true);
const generating = ref(false);
const certificateData = ref(null);
const error = ref(null);

const fetchCertificate = async () => {
    try {
        const response = await api.get('/api/provider/certificate');
        certificateData.value = response.data;
    } catch (err) {
        console.error('Certificate Error:', err);
        error.value = err.response?.data?.message || 'Impossible de charger le certificat.';
    } finally {
        loading.value = false;
    }
};

const downloadPDF = async () => {
    if (!certificateData.value) return;
    generating.value = true;

    try {
        const element = document.getElementById('certificate-content');
        const opt = {
            margin: 0,
            filename: `Certificat_Valora_${certificateData.value.certificate_id}.pdf`,
            image: { type: 'jpeg', quality: 1 },
            html2pdf: { scale: 2, useCORS: true, logging: true },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
        };

        await html2pdf().set(opt).from(element).save();
    } catch (err) {
        console.error('PDF Generation Error:', err);
        alert('Erreur lors de la génération du PDF.');
    } finally {
        generating.value = false;
    }
};

onMounted(() => {
    fetchCertificate();
});
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex flex-col items-center justify-center p-4 sm:p-8 font-serif">
        
        <!-- Controls -->
        <div class="w-full max-w-[297mm] flex items-center justify-between mb-8 print:hidden">
            <button @click="router.back()" class="flex items-center gap-2 text-slate-500 hover:text-slate-900 font-sans font-bold transition-colors">
                <ArrowLeft class="w-5 h-5" />
                <span>Retour</span>
            </button>
            
            <button 
                v-if="certificateData"
                @click="downloadPDF" 
                :disabled="generating"
                class="flex items-center gap-2 bg-slate-900 text-white px-6 py-3 rounded-xl font-sans font-bold hover:bg-slate-800 transition-colors shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <Download v-if="!generating" class="w-5 h-5" />
                <div v-else class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                <span>{{ generating ? 'Génération...' : 'Télécharger PDF' }}</span>
            </button>
        </div>

        <!-- Error State -->
        <div v-if="error" class="text-center max-w-md bg-white p-8 rounded-2xl shadow-xl border border-red-100">
            <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <ShieldCheck class="w-8 h-8 text-red-500" />
            </div>
            <h2 class="text-xl font-bold text-slate-900 mb-2 font-sans">Certificat Indisponible</h2>
            <p class="text-slate-500 mb-6 font-sans">{{ error }}</p>
            <button @click="router.push('/dashboard')" class="text-blue-600 font-bold hover:underline font-sans">
                Retour au tableau de bord
            </button>
        </div>

        <!-- Loading State -->
        <div v-else-if="loading" class="flex flex-col items-center justify-center space-y-4">
            <div class="w-12 h-12 border-4 border-slate-200 border-t-slate-900 rounded-full animate-spin"></div>
            <p class="text-slate-400 font-sans font-bold uppercase tracking-widest text-xs">Chargement...</p>
        </div>

        <!-- Certificate Content (A4 Landscape aspect ratio) -->
        <div v-else id="certificate-content" class="relative w-full max-w-[297mm] aspect-[297/210] bg-white text-slate-900 shadow-2xl overflow-hidden print:shadow-none print:m-0 print:w-[297mm] print:h-[210mm]">
            
            <!-- Ornate Border -->
            <div class="absolute inset-4 border-[3px] border-double border-[#C5A059] z-20 pointer-events-none"></div>
            <div class="absolute inset-6 border border-[#C5A059]/30 z-20 pointer-events-none"></div>

            <!-- Corners -->
            <div class="absolute top-4 left-4 w-16 h-16 border-t-[3px] border-l-[3px] border-[#C5A059] z-20"></div>
            <div class="absolute top-4 right-4 w-16 h-16 border-t-[3px] border-r-[3px] border-[#C5A059] z-20"></div>
            <div class="absolute bottom-4 left-4 w-16 h-16 border-b-[3px] border-l-[3px] border-[#C5A059] z-20"></div>
            <div class="absolute bottom-4 right-4 w-16 h-16 border-b-[3px] border-r-[3px] border-[#C5A059] z-20"></div>

            <!-- Background Pattern -->
            <div class="absolute inset-0 bg-[#FFFCF5] z-0">
                 <!-- Repeating Pattern SVG or similar could go here -->
                 <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(#C5A059 1px, transparent 1px); background-size: 20px 20px;"></div>
            </div>

            <!-- Watermark -->
            <div class="absolute inset-0 flex items-center justify-center z-0 opacity-[0.04] pointer-events-none">
                <Award class="w-[500px] h-[500px] text-[#C5A059]" />
            </div>

            <!-- Content Container -->
            <div class="relative z-10 h-full flex flex-col items-center justify-between py-20 px-24 text-center">
                
                <!-- Header -->
                <div class="space-y-6">
                    <div class="flex items-center justify-center gap-3 mb-2">
                        <div class="w-10 h-10 bg-[#C5A059] rounded-lg flex items-center justify-center text-white font-black font-sans text-xl">V</div>
                        <span class="text-2xl font-bold tracking-widest uppercase text-slate-900 font-sans">Valora</span>
                    </div>
                    
                    <h1 class="text-6xl font-serif text-[#C5A059] tracking-wide" style="font-family: 'Playfair Display', serif;">Certificat d'Excellence</h1>
                    
                    <p class="text-lg text-slate-500 uppercase tracking-[0.3em] font-sans font-medium">Ce document certifie que</p>
                </div>

                <!-- Main Name -->
                <div class="space-y-4">
                    <h2 class="text-5xl font-bold text-slate-900 font-serif italic">{{ certificateData.prestataire.user.name }}</h2>
                    <div class="w-64 h-px bg-[#C5A059] mx-auto"></div>
                    <p class="text-xl text-slate-600 font-sans">
                        a complété avec succès le processus de vérification et est reconnu comme
                    </p>
                    <div class="text-2xl font-bold text-[#C5A059] uppercase tracking-widest font-sans mt-2">
                        Prestataire {{ certificateData.prestataire.category?.name || 'Certifié' }}
                    </div>
                </div>

                <!-- Footer / Signatures -->
                <div class="w-full grid grid-cols-3 items-end mt-12">
                    <!-- Date -->
                    <div class="text-center space-y-2">
                        <p class="text-sm font-bold text-slate-400 uppercase tracking-widest font-sans">Certifié le</p>
                        <p class="text-lg font-serif text-slate-900 border-b border-slate-300 pb-1 inline-block min-w-[120px]">
                            {{ new Date(certificateData.certified_at).toLocaleDateString() }}
                        </p>
                    </div>

                    <!-- Seal -->
                    <div class="flex justify-center">
                        <div class="relative w-32 h-32">
                            <div class="absolute inset-0 border-4 border-[#C5A059] rounded-full opacity-30 animate-spin-slow" style="animation-duration: 20s;">
                                <svg viewBox="0 0 100 100" class="w-full h-full">
                                    <path id="curve" d="M 20, 50 a 30,30 0 1,1 60,0 a 30,30 0 1,1 -60,0" fill="transparent" />
                                    <text width="500">
                                        <textPath xlink:href="#curve" class="text-[10px] uppercase font-bold tracking-[0.2em] fill-[#C5A059]">
                                            • Valora Official Certification • Verified •
                                        </textPath>
                                    </text>
                                </svg>
                            </div>
                            <div class="absolute inset-2 bg-[#C5A059] rounded-full flex items-center justify-center text-white shadow-lg">
                                <ShieldCheck class="w-12 h-12" />
                            </div>
                        </div>
                    </div>

                    <!-- ID -->
                    <div class="text-center space-y-2">
                        <p class="text-sm font-bold text-slate-400 uppercase tracking-widest font-sans">ID Certificat</p>
                        <p class="text-sm font-mono font-bold text-slate-900 bg-slate-100 px-3 py-1 rounded inline-block">
                            {{ certificateData.certificate_id }}
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap');

.font-serif {
    font-family: 'Playfair Display', serif;
}
</style>
