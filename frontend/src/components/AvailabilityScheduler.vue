<template>
  <div class="space-y-8 animate-in fade-in duration-700">
    <!-- Header with Weekly Summary -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6 bg-slate-900 p-8 rounded-4xl text-white shadow-2xl relative overflow-hidden group border border-white/5">
        <div class="relative z-10 flex items-center space-x-4">
            <div class="p-3 bg-premium-yellow/10 rounded-2xl border border-premium-yellow/20 group-hover:rotate-12 transition-transform duration-500">
                <Clock class="w-6 h-6 text-premium-yellow" />
            </div>
            <div>
                <h3 class="text-xl font-black tracking-tight">Gestionnaire Hebdo</h3>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Optimisez votre planning</p>
            </div>
        </div>
        
        <div class="relative z-10 bg-white/5 backdrop-blur-md px-6 py-3 rounded-2xl border border-white/10 flex items-center space-x-4">
            <div class="text-right">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Capacité hebdomadaire</p>
                <p class="text-2xl font-black text-premium-yellow tracking-tighter">{{ totalWeeklyHours }}<span class="text-xs ml-1 text-slate-400 italic">heures</span></p>
            </div>
            <div class="w-10 h-10 rounded-full border-2 border-premium-yellow/20 flex items-center justify-center">
                <div class="w-6 h-6 rounded-full border-4 border-premium-yellow animate-pulse"></div>
            </div>
        </div>

        <!-- Decor -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-premium-yellow/5 rounded-full blur-3xl -mr-32 -mt-32"></div>
    </div>

    <!-- Grid of Days -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <div v-for="day in days" :key="day.key" 
        class="group relative bg-white rounded-[2.5rem] p-8 border border-slate-100 transition-all duration-500 hover:shadow-2xl hover:shadow-slate-200/50 flex flex-col justify-between min-h-[220px] overflow-hidden"
        :class="availability[day.key].active ? 'ring-2 ring-premium-yellow shadow-xl' : 'bg-slate-50/30 opacity-80'"
      >
        <!-- Background Accent for Active Days -->
        <div v-if="availability[day.key].active" class="absolute -top-10 -right-10 w-24 h-24 bg-premium-yellow/5 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>

        <div class="relative z-10 flex justify-between items-start mb-8">
            <div class="space-y-2">
                <div class="flex items-center space-x-2">
                    <Calendar v-if="availability[day.key].active" class="w-3.5 h-3.5 text-premium-brown" />
                    <h4 class="font-black text-xs uppercase tracking-[0.2em] transition-colors" :class="availability[day.key].active ? 'text-slate-900' : 'text-slate-400'">
                        {{ day.label }}
                    </h4>
                </div>
                <div v-if="availability[day.key].active" class="flex items-center space-x-2 animate-in fade-in slide-in-from-left-2 duration-500">
                    <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shadow-sm shadow-emerald-500/50"></div>
                    <span class="text-[9px] font-black text-emerald-600 uppercase tracking-widest">Disponible</span>
                </div>
                <div v-else class="flex items-center space-x-2 opacity-50">
                    <div class="w-1.5 h-1.5 rounded-full bg-slate-200"></div>
                    <span class="text-[9px] font-black text-slate-300 uppercase tracking-widest">Fermé</span>
                </div>
            </div>

            <!-- Custom Toggle -->
            <button 
                @click="availability[day.key].active = !availability[day.key].active; emitChange()"
                class="relative inline-flex h-7 w-12 items-center rounded-full transition-all duration-500 focus:outline-none ring-offset-2 focus:ring-2 focus:ring-premium-yellow"
                :class="availability[day.key].active ? 'bg-premium-yellow shadow-lg shadow-yellow-500/20' : 'bg-slate-200'"
            >
                <span class="sr-only">Toggle day active</span>
                <span 
                    :class="availability[day.key].active ? 'translate-x-6' : 'translate-x-1'"
                    class="inline-block h-5 w-5 transform rounded-full bg-white transition-transform duration-500 shadow-xl"
                />
            </button>
        </div>

        <!-- Time Controls -->
        <div v-if="availability[day.key].active" class="relative z-10 space-y-6 animate-in slide-in-from-bottom-2 duration-500">
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <label class="text-[8px] font-black text-slate-400 uppercase tracking-widest ml-2">De</label>
                    <input 
                        type="text" 
                        v-model="availability[day.key].start"
                        placeholder="00:00"
                        @input="emitChange"
                        class="w-full px-3 py-3 bg-slate-50 rounded-2xl border border-slate-100 text-[11px] font-black text-slate-900 outline-none focus:bg-white focus:ring-4 focus:ring-premium-yellow/10 focus:border-premium-yellow transition-all text-center"
                    >
                </div>
                <div class="space-y-1.5">
                    <label class="text-[8px] font-black text-slate-400 uppercase tracking-widest ml-2">Au</label>
                    <input 
                        type="text" 
                        v-model="availability[day.key].end"
                        placeholder="00:00"
                        @input="emitChange"
                        class="w-full px-3 py-3 bg-slate-50 rounded-2xl border border-slate-100 text-[11px] font-black text-slate-900 outline-none focus:bg-white focus:ring-4 focus:ring-premium-yellow/10 focus:border-premium-yellow transition-all text-center"
                    >
                </div>
            </div>
        </div>

        <!-- Disabled State Empty -->
        <div v-else class="grow flex items-center justify-center">
            <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-200 group-hover:rotate-12 transition-transform duration-500">
                <Clock class="w-6 h-6 opacity-30" />
            </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { Clock, Check, XCircle, Calendar } from 'lucide-vue-next';

const props = defineProps({
  initialAvailability: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['update']);

const days = [
  { key: 'monday', label: 'Lundi' },
  { key: 'tuesday', label: 'Mardi' },
  { key: 'wednesday', label: 'Mercredi' },
  { key: 'thursday', label: 'Jeudi' },
  { key: 'friday', label: 'Vendredi' },
  { key: 'saturday', label: 'Samedi' },
  { key: 'sunday', label: 'Dimanche' }
];

// Local state
const availability = ref(days.reduce((acc, day) => {
    const initial = props.initialAvailability?.[day.key];
    acc[day.key] = initial ? { ...initial } : {
        active: false,
        start: '00:00',
        end: '00:00'
    };
    return acc;
}, {}));

const syncAvailability = () => {
  days.forEach(day => {
    if (props.initialAvailability && props.initialAvailability[day.key]) {
      availability.value[day.key] = { ...props.initialAvailability[day.key] };
    }
  });
};

onMounted(() => {
  syncAvailability();
});

watch(() => props.initialAvailability, () => {
  syncAvailability();
}, { deep: true });

const emitChange = () => {
  emit('update', availability.value);
};

const totalWeeklyHours = computed(() => {
    let total = 0;
    Object.values(availability.value).forEach(day => {
        if (day.active && day.start && day.end) {
            const [startH, startM] = day.start.split(':').map(Number);
            const [endH, endM] = day.end.split(':').map(Number);
            const diff = (endH + endM/60) - (startH + startM/60);
            if (diff > 0) total += diff;
        }
    });
    return Math.round(total * 10) / 10;
});
</script>
