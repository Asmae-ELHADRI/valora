<template>
  <div class="space-y-8 animate-in fade-in duration-500">
    <!-- Header with Weekly Summary -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6 bg-slate-900 p-6 rounded-[2rem] text-white shadow-2xl relative overflow-hidden group border border-white/5">
        <div class="relative z-10 flex items-center space-x-4">
            <div class="p-3 bg-premium-yellow/10 rounded-2xl border border-premium-yellow/20 group-hover:rotate-12 transition-transform duration-300">
                <Clock class="w-6 h-6 text-premium-yellow" />
            </div>
            <div>
                <h3 class="text-lg font-black tracking-tight">Planning Hebdo</h3>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Gérez vos horaires</p>
            </div>
        </div>
        
        <div class="relative z-10 bg-white/5 backdrop-blur-md px-5 py-2.5 rounded-2xl border border-white/10 flex items-center space-x-4">
            <div class="text-right">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Capacité</p>
                <p class="text-xl font-black text-premium-yellow tracking-tighter">{{ totalWeeklyHours }}h</p>
            </div>
            <div class="w-8 h-8 rounded-full border-2 border-premium-yellow/20 flex items-center justify-center">
                <div class="w-4 h-4 rounded-full border-[3px] border-premium-yellow animate-pulse"></div>
            </div>
        </div>

        <!-- Decor -->
        <div class="absolute top-0 right-0 w-48 h-48 bg-premium-yellow/5 rounded-full blur-3xl -mr-24 -mt-24"></div>
    </div>

    <!-- Grid of Days -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="day in days" :key="day.key" 
        class="group relative bg-white rounded-[2rem] p-6 border transition-all duration-200 hover:shadow-lg flex flex-col justify-between overflow-hidden cursor-pointer h-full"
        :class="availability[day.key].active ? 'border-premium-yellow ring-1 ring-premium-yellow shadow-md' : 'border-slate-100 bg-slate-50/30 hover:bg-white'"
        @click="toggleDay(day.key)"
      >
        <!-- Header: Day & Toggle -->
        <div class="relative z-10 flex justify-between items-start mb-6">
            <div class="space-y-1">
                <div class="flex items-center space-x-2">
                    <Calendar v-if="availability[day.key].active" class="w-3.5 h-3.5 text-premium-brown" />
                    <h4 class="font-black text-xs uppercase tracking-[0.15em] transition-colors" :class="availability[day.key].active ? 'text-slate-900' : 'text-slate-400'">
                        {{ day.label }}
                    </h4>
                </div>
                <div v-if="availability[day.key].active" class="flex items-center space-x-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/50"></div>
                    <span class="text-[9px] font-black text-emerald-600 uppercase tracking-widest">Ouvert</span>
                </div>
                <div v-else class="flex items-center space-x-2 opacity-50">
                    <div class="w-1.5 h-1.5 rounded-full bg-slate-300"></div>
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Fermé</span>
                </div>
            </div>

            <!-- Custom Toggle -->
            <button 
                type="button"
                class="relative inline-flex h-6 w-10 items-center rounded-full transition-colors duration-200 focus:outline-none"
                :class="availability[day.key].active ? 'bg-premium-yellow' : 'bg-slate-200'"
            >
                <span class="sr-only">Toggle day</span>
                <span 
                    :class="availability[day.key].active ? 'translate-x-5' : 'translate-x-1'"
                    class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200 shadow-sm"
                />
            </button>
        </div>

        <!-- Time Inputs -->
        <div v-if="availability[day.key].active" class="relative z-10 space-y-4 animate-in slide-in-from-bottom-1 duration-200" @click.stop>
            <div class="flex items-center gap-2">
                <div class="relative grow">
                    <input 
                        type="time" 
                        v-model="availability[day.key].start"
                        @change="emitChange"
                        class="w-full px-2 py-2.5 bg-slate-50 rounded-xl border border-slate-200 text-[11px] font-black text-slate-900 outline-none focus:bg-white focus:border-premium-yellow transition-all text-center"
                    >
                    <span class="absolute -top-2 left-2 text-[8px] font-bold text-slate-400 bg-white px-1">DE</span>
                </div>
                <span class="text-slate-300 font-bold">-</span>
                <div class="relative grow">
                     <input 
                        type="time" 
                        v-model="availability[day.key].end"
                        @change="emitChange"
                        class="w-full px-2 py-2.5 bg-slate-50 rounded-xl border border-slate-200 text-[11px] font-black text-slate-900 outline-none focus:bg-white focus:border-premium-yellow transition-all text-center"
                    >
                    <span class="absolute -top-2 left-2 text-[8px] font-bold text-slate-400 bg-white px-1">À</span>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="grow flex items-center justify-center py-4 opacity-30">
            <Clock class="w-8 h-8 text-slate-300" />
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
    // Default structure incase props are empty
    acc[day.key] = { active: false, start: '09:00', end: '18:00' };
    return acc;
}, {}));

// One-way sync from props to local state (Initial Load Only)
const syncAvailability = () => {
  if (!props.initialAvailability) return;
  
  days.forEach(day => {
    if (props.initialAvailability[day.key]) {
      // JSON Parse safety if needed, but usually it's an object here
      const val = props.initialAvailability[day.key];
      availability.value[day.key] = { 
          active: !!val.active, 
          start: val.start || '09:00', 
          end: val.end || '18:00' 
      };
    }
  });
};

onMounted(() => {
  syncAvailability();
});

// Watch only if external prop changes significantly (Deep check could be added)
// For now, we trust initial load. If we want to support external updates not triggered by us:
watch(() => props.initialAvailability, (newVal) => {
    // Basic check to avoid circular loop if we just emitted it
    // Implementation: trust local state as source of truth during editing
    // Only sync if local is empty/default and prop has data (First load scenario)
}, { deep: true, immediate: true }); 

const toggleDay = (key) => {
    availability.value[key].active = !availability.value[key].active;
    emitChange();
};

const emitChange = () => {
    // Emit a clean copy
    const cleanData = {};
    days.forEach(day => {
        cleanData[day.key] = { ...availability.value[day.key] };
    });
  emit('update', cleanData);
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
