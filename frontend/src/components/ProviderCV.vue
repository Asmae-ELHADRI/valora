<script setup>
import { computed } from 'vue';
import { 
  User, Mail, Phone, MapPin, Briefcase, Clock,
  ShieldCheck, Award, Download, CheckCircle,
  FileText
} from 'lucide-vue-next';
import html2pdf from 'html2pdf.js';

const props = defineProps({
  provider: {
    type: Object,
    required: true
  }
});

const prestataire = computed(() => props.provider?.prestataire || {});

const safeJsonParse = (value, fallback) => {
  if (value === null || value === undefined) return fallback;
  if (Array.isArray(value)) return value;
  if (typeof value === 'object') return value;
  if (typeof value === 'string') {
    try {
      const parsed = JSON.parse(value);
      return parsed === null ? fallback : parsed;
    } catch (e) {
      return fallback;
    }
  }
  return fallback;
};

const achievements = computed(() => safeJsonParse(prestataire.value.achievements, []));
const languages = computed(() => safeJsonParse(prestataire.value.languages, []));
const formations = computed(() => safeJsonParse(prestataire.value.formations, []));

const skillsList = computed(() => {
  if (!prestataire.value.skills) return [];
  return prestataire.value.skills.split(',').map(s => s.trim()).filter(s => s !== '');
});

const categoryNames = computed(() => {
  if (!prestataire.value.categories || prestataire.value.categories.length === 0) return 'Prestataire';
  return prestataire.value.categories.map(c => c.name).join(' • ');
});

const generatePDF = () => {
    const element = document.getElementById('cv-content-download');
    if (!element) return;

    const opt = {
        margin: 0,
        filename: `CV_${props.provider.name.replace(/\s+/g, '_')}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { 
            scale: 2, 
            useCORS: true,
            letterRendering: true,
            scrollX: 0,
            scrollY: 0
        },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    html2pdf().from(element).set(opt).save();
};
</script>

<template>
  <div class="flex flex-col items-center w-full space-y-6">
    <!-- Action Bar -->
    <div class="w-full max-w-[21cm] flex justify-end px-4 md:px-0 print:hidden">
      <button 
        @click="generatePDF" 
        class="flex items-center space-x-2 px-6 py-3 rounded-2xl bg-slate-900 text-white hover:bg-premium-brown transition-all shadow-xl active:scale-95 font-black text-xs uppercase tracking-widest group"
      >
        <Download class="w-4 h-4 group-hover:-translate-y-0.5 transition-transform" />
        <span>Télécharger PDF</span>
      </button>
    </div>

    <!-- CV Document -->
    <div id="cv-content-download" class="w-full max-w-[21cm] bg-white shadow-2xl relative overflow-hidden flex flex-col md:flex-row min-h-fit rounded-sm font-sans border border-slate-100">
      
      <!-- LEFT SIDEBAR -->
      <div class="w-full md:w-[35%] bg-slate-900 text-white relative p-8 flex flex-col shrink-0">
        <div class="absolute inset-0 bg-gradient-to-b from-slate-900 to-slate-950"></div>
        <div class="absolute top-0 left-0 w-64 h-64 bg-premium-yellow/5 rounded-full blur-[80px] -ml-20 -mt-20"></div>

        <div class="relative z-10 flex flex-col items-center mb-10 pt-4">
          <div class="w-40 h-40 rounded-full p-1.5 border border-white/10 relative">
            <div class="w-full h-full rounded-full overflow-hidden bg-slate-800 shadow-2xl relative">
              <img 
                v-if="prestataire.photo" 
                :src="`http://localhost:8000/storage/${prestataire.photo}`" 
                class="w-full h-full object-cover"
                crossorigin="anonymous"
              >
              <div v-else class="w-full h-full flex items-center justify-center text-slate-400 bg-slate-800">
                <User class="w-16 h-16" />
              </div>
            </div>
            <div v-if="prestataire.certified_at" class="absolute bottom-1 right-1 bg-emerald-500 text-white p-2 rounded-full border-[3px] border-slate-900 shadow-lg">
              <ShieldCheck class="w-4 h-4" />
            </div>
          </div>
        </div>

        <div class="space-y-10 relative z-10 flex-1">
          <!-- Contact -->
          <div>
            <h4 class="text-[10px] font-black text-premium-yellow uppercase tracking-[0.2em] mb-5 flex items-center gap-3">
              <span class="w-6 h-px bg-premium-yellow/50"></span> Contact
            </h4>
            <ul class="space-y-4">
              <li class="flex items-center gap-3">
                <div class="w-7 h-7 rounded-lg bg-white/5 flex items-center justify-center shrink-0">
                  <Phone class="w-3.5 h-3.5" />
                </div>
                <span class="text-[11px] font-medium text-slate-200">{{ props.provider.phone || 'N/A' }}</span>
              </li>
              <li class="flex items-center gap-3">
                <div class="w-7 h-7 rounded-lg bg-white/5 flex items-center justify-center shrink-0">
                  <Mail class="w-3.5 h-3.5" />
                </div>
                <span class="text-[11px] font-medium text-slate-200 truncate">{{ props.provider.email }}</span>
              </li>
              <li class="flex items-center gap-3">
                <div class="w-7 h-7 rounded-lg bg-white/5 flex items-center justify-center shrink-0">
                  <MapPin class="w-3.5 h-3.5" />
                </div>
                <span class="text-[11px] font-medium text-slate-200">{{ prestataire.city || props.provider.address || 'Maroc' }}</span>
              </li>
            </ul>
          </div>

          <!-- Services / Badge -->
          <div v-if="prestataire.categories?.length > 0">
            <h4 class="text-[10px] font-black text-premium-yellow uppercase tracking-[0.2em] mb-5 flex items-center gap-3">
              <span class="w-6 h-px bg-premium-yellow/50"></span> Services
            </h4>
            <div class="flex flex-wrap gap-2">
              <span 
                v-for="cat in prestataire.categories" 
                :key="cat.id" 
                class="px-3 py-1.5 bg-premium-yellow/10 border border-premium-yellow/20 rounded-md text-[10px] font-bold text-premium-yellow uppercase tracking-wide"
              >
                {{ cat.name }}
              </span>
            </div>
          </div>

          <!-- Languages -->
          <div v-if="languages.length > 0">
            <h4 class="text-[10px] font-black text-premium-yellow uppercase tracking-[0.2em] mb-5 flex items-center gap-3">
              <span class="w-6 h-px bg-premium-yellow/50"></span> Langues
            </h4>
            <ul class="space-y-3">
              <li v-for="(lang, idx) in languages" :key="idx" class="flex items-end justify-between border-b border-white/5 pb-1">
                <span class="text-xs font-bold text-slate-200">{{ lang.name }}</span>
                <span class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">{{ lang.level }}</span>
              </li>
            </ul>
          </div>

          <!-- Skills -->
          <div v-if="skillsList.length > 0">
            <h4 class="text-[10px] font-black text-premium-yellow uppercase tracking-[0.2em] mb-5 flex items-center gap-3">
              <span class="w-6 h-px bg-premium-yellow/50"></span> Compétences
            </h4>
            <div class="flex flex-wrap gap-2">
              <span 
                v-for="skill in skillsList" 
                :key="skill" 
                class="px-2.5 py-1 bg-white/5 border border-white/5 rounded text-[10px] font-bold text-slate-300 uppercase tracking-wider"
              >
                {{ skill }}
              </span>
            </div>
          </div>
        </div>
        
        <!-- Footer -->
        <div class="mt-auto pt-10 border-t border-white/5 text-center opacity-20">
          <span class="text-2xl font-black tracking-tighter text-white">VALORA</span>
        </div>
      </div>

      <!-- RIGHT CONTENT -->
      <div class="w-full md:w-[65%] bg-white p-10 flex flex-col relative">
        <!-- Header -->
        <div class="mb-10 pb-6 border-b border-slate-100">
          <div class="inline-flex items-center space-x-2 mb-2">
            <span class="w-8 h-0.5 bg-premium-yellow"></span>
            <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">{{ categoryNames }}</span>
          </div>
          <h1 class="text-4xl md:text-5xl font-black text-slate-900 uppercase tracking-tighter leading-none">
            {{ props.provider.first_name || props.provider.name.split(' ')[0] }}
            <span class="text-slate-400 block">{{ props.provider.last_name || props.provider.name.split(' ').slice(1).join(' ') }}</span>
          </h1>
        </div>

        <!-- Biography -->
        <div class="mb-10">
          <h3 class="text-xs font-black text-slate-900 uppercase tracking-widest mb-4 flex items-center gap-2">
            <User class="w-3.5 h-3.5 text-premium-yellow" /> Profil Pro
          </h3>
          <p class="text-sm text-slate-600 leading-relaxed font-medium">
            {{ prestataire.description || "Professionnel dévoué offrant des services de haute qualité." }}
          </p>
        </div>

        <!-- Experience -->
        <div class="mb-8 grow">
          <h3 class="text-xs font-black text-slate-900 uppercase tracking-widest mb-6 flex items-center gap-2">
            <Briefcase class="w-3.5 h-3.5 text-premium-yellow" /> Expérience
          </h3>
          
          <div v-if="achievements.length === 0" class="py-6 border border-dashed border-slate-200 rounded-lg text-center bg-slate-50 text-[10px] text-slate-400 font-bold uppercase tracking-wide">
             {{ prestataire.experience || 'Aucune expérience détaillée' }}
          </div>

          <div v-else class="space-y-0 relative pl-4">
            <div class="absolute left-[6px] top-1.5 bottom-4 w-px bg-slate-200"></div>
            <div v-for="(exp, idx) in achievements" :key="idx" class="relative pl-8 pb-8 last:pb-0">
              <div class="absolute -left-[5px] top-1.5 w-[11px] h-[11px] rounded-full bg-white border-[3px] border-slate-300"></div>
              <div>
                <h4 class="font-black text-slate-900 text-sm uppercase tracking-wide">{{ exp.split('@')[0].trim() }}</h4>
                <span v-if="exp.includes('@')" class="text-[9px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-sm uppercase tracking-wider">
                  {{ exp.split('@')[1]?.trim() }}
                </span>
                <p class="text-xs text-slate-500 font-medium leading-relaxed mt-1" v-if="exp.split('@').length > 2">
                  {{ exp.split('@')[2]?.trim() }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Education / Diplomas -->
        <div v-if="prestataire.diplomas && prestataire.diplomas !== 'Non'" class="mt-auto pt-6 border-t border-slate-100">
          <h3 class="text-xs font-black text-slate-900 uppercase tracking-widest mb-4 flex items-center gap-2">
            <Award class="w-3.5 h-3.5 text-premium-yellow" /> Certifications & Diplômes
          </h3>
          <div class="flex items-start gap-4 p-4 bg-slate-50 rounded-xl border border-slate-100/50">
            <div class="p-2 bg-white shadow-sm rounded-lg text-premium-brown shrink-0">
              <ShieldCheck class="w-4 h-4" />
            </div>
            <div>
              <p class="text-xs font-bold text-slate-700 leading-snug">{{ prestataire.diplomas }}</p>
              <div v-if="prestataire.certified_at" class="flex items-center gap-1 mt-1 text-[9px] font-black uppercase tracking-widest text-emerald-600">
                <CheckCircle class="w-3 h-3" /> <span>Vérifié par Valora</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
#cv-content-download {
  min-height: 29.7cm; /* A4 aspect ratio helper */
}

@media print {
  .print\:hidden {
    display: none !important;
  }
}
</style>
