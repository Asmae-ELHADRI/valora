<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { Clock, Check } from 'lucide-vue-next';

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
        start: '09:00',
        end: '18:00'
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

<template>
  <div class="bg-white rounded-3xl border border-gray-100 p-8 shadow-sm">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
        <h3 class="font-bold text-gray-900 flex items-center">
            <Clock class="w-6 h-6 mr-2 text-blue-600" />
            Gestionnaire de disponibilités hebdo
        </h3>
        <div class="bg-blue-50 px-4 py-2 rounded-2xl border border-blue-100">
            <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest leading-none mb-1">Capacité totale</p>
            <p class="text-lg font-black text-blue-900 leading-none">{{ totalWeeklyHours }}h <span class="text-xs font-bold text-blue-400">/ semaine</span></p>
        </div>
    </div>

    <div class="space-y-3">
      <div v-for="day in days" :key="day.key" class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-4 rounded-2xl transition border border-transparent hover:border-gray-100 hover:bg-gray-50/50 gap-4">
        <div class="flex items-center space-x-4">
          <label class="relative inline-flex items-center cursor-pointer">
            <input 
              type="checkbox" 
              v-model="availability[day.key].active" 
              class="sr-only peer"
              @change="emitChange"
            >
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
          </label>
          <span class="font-bold text-sm tracking-tight" :class="availability[day.key].active ? 'text-gray-900' : 'text-gray-300'">
            {{ day.label }}
          </span>
        </div>

        <div class="flex items-center space-x-3" v-if="availability[day.key].active">
          <div class="relative">
            <input 
              type="time" 
              v-model="availability[day.key].start"
              @change="emitChange"
              class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
            >
          </div>
          <span class="text-gray-400">-</span>
          <div class="relative">
             <input 
              type="time" 
              v-model="availability[day.key].end"
              @change="emitChange"
              class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
            >
          </div>
        </div>
        <div v-else class="text-sm text-gray-400 italic">
          Indisponible
        </div>
      </div>
    </div>
  </div>
</template>
