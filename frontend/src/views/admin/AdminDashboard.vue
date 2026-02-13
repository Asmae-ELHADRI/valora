<script setup>
import { ref, onMounted, computed } from 'vue';
import { Line } from 'vue-chartjs';
import { 
  Chart as ChartJS, 
  Title, 
  Tooltip, 
  Legend, 
  LineElement, 
  CategoryScale, 
  LinearScale, 
  PointElement,
  Filler
} from 'chart.js';
import { 
  Users, 
  Banknote, 
  TrendingUp 
} from 'lucide-vue-next';
import api from '../../services/api';

ChartJS.register(Title, Tooltip, Legend, LineElement, CategoryScale, LinearScale, PointElement, Filler);

const stats = ref({
  users_count: 0,
  users_growth: 12, // Mock growth for now as backend doesn't provide it yet
  revenue: 0,
  revenue_growth: 18,
  new_users: 0,
  new_users_growth: 24,
  reports_count: 0,
  missions_count: 0
});

const chartData = ref({
  labels: [],
  datasets: []
});

const loading = ref(true);

// ... chart code ...



const fetchData = async () => {
    loading.value = true;
    try {
        // ... (stats fetch) ...
        const statsRes = await api.get('/api/admin/stats?period=month');
        const data = statsRes.data;
        
        stats.value = {
            users_count: data.users_count,
            users_growth: 12, 
            revenue: data.missions_count * 50, 
            revenue_growth: 15,
            new_users: data.chart_data.datasets[0].data.reduce((a, b) => a + b, 0),
            new_users_growth: 24,
            reports_count: data.reports_count,
            missions_count: data.missions_count
        };

        // ... (chart fetch) ...
        chartData.value = {
            labels: data.chart_data.labels,
            datasets: [{
                label: 'Nouveaux inscrits',
                data: data.chart_data.datasets[0].data,
                borderColor: '#3b82f6',
                backgroundColor: (context) => {
                    const ctx = context.chart.ctx;
                    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
                    gradient.addColorStop(0, 'rgba(59, 130, 246, 0.5)');
                    gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');
                    return gradient;
                },
                fill: true,
                tension: 0.4,
                pointRadius: 0
            }]
        };



    } catch (err) {
        console.error('Failed to fetch dashboard data:', err);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchData();
});
</script>

<template>
  <div class="space-y-8">
    <!-- Top Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Users Card -->
        <div class="bg-white dark:bg-[#1E293B] p-8 rounded-[32px] border border-slate-200 dark:border-white/5 relative overflow-hidden group hover:scale-[1.02] transition-all duration-300 shadow-xl shadow-slate-200/50 dark:shadow-none">
            <div class="absolute top-0 right-0 p-8 opacity-[0.03] group-hover:opacity-10 transition-opacity duration-500 transform scale-150">
                <Users class="w-32 h-32 text-blue-500" />
            </div>
            <div class="flex justify-between items-start mb-8 relative z-10">
                <div class="w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center border border-blue-100 dark:border-blue-500/20">
                    <Users class="w-7 h-7 text-blue-600 dark:text-blue-500" />
                </div>
                <div class="px-3 py-1 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-100 dark:border-emerald-500/20 rounded-full flex items-center gap-1">
                    <TrendingUp class="w-3 h-3 text-emerald-600 dark:text-emerald-400" />
                    <span class="text-xs font-black text-emerald-600 dark:text-emerald-400">+{{ stats.users_growth }}%</span>
                </div>
            </div>
            <div class="relative z-10">
                <p class="text-slate-500 dark:text-slate-400 text-[10px] font-black uppercase tracking-widest mb-2">{{ $t('admin.users') }}</p>
                <h3 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight">{{ stats.users_count?.toLocaleString() || 0 }}</h3>
            </div>
        </div>

        <!-- Revenue Card -->
        <div class="bg-white dark:bg-[#1E293B] p-8 rounded-[32px] border border-slate-200 dark:border-white/5 relative overflow-hidden group hover:scale-[1.02] transition-all duration-300 shadow-xl shadow-slate-200/50 dark:shadow-none">
             <div class="absolute top-0 right-0 p-8 opacity-[0.03] group-hover:opacity-10 transition-opacity duration-500 transform scale-150">
                <Banknote class="w-32 h-32 text-amber-500" />
            </div>
            <div class="flex justify-between items-start mb-8 relative z-10">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center border border-amber-100 dark:border-amber-500/20">
                    <Banknote class="w-7 h-7 text-amber-600 dark:text-amber-500" />
                </div>
                <div class="px-3 py-1 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-100 dark:border-emerald-500/20 rounded-full flex items-center gap-1">
                    <TrendingUp class="w-3 h-3 text-emerald-600 dark:text-emerald-400" />
                    <span class="text-xs font-black text-emerald-600 dark:text-emerald-400">+{{ stats.revenue_growth }}%</span>
                </div>
            </div>
            <div class="relative z-10">
                <p class="text-slate-500 dark:text-slate-400 text-[10px] font-black uppercase tracking-widest mb-2">{{ $t('admin.revenue') }}</p>
                <h3 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight">{{ (stats.revenue / 1000).toFixed(1) }}k <span class="text-xl text-slate-400 font-bold">MAD</span></h3>
            </div>
        </div>
    </div>

    <!-- Performance Graph -->
    <div class="bg-white dark:bg-[#1E293B] p-8 rounded-[32px] border border-slate-200 dark:border-white/5 shadow-xl shadow-slate-200/50 dark:shadow-none">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ $t('admin.performance') }}</h3>
                <div class="flex items-baseline space-x-3 mt-1">
                    <span class="text-4xl font-black text-slate-900 dark:text-white">{{ stats.new_users.toLocaleString() }}</span>
                    <span class="text-sm font-bold text-green-600 dark:text-green-400">+{{ stats.new_users_growth }}% {{ $t('admin.vs_prev_month') }}</span>
                </div>
                <p class="text-slate-500 dark:text-slate-400 text-xs mt-1">{{ $t('admin.new_users') }}</p>
            </div>
            <div class="px-4 py-2 bg-slate-100 dark:bg-[#0f172a] rounded-xl text-xs font-bold text-amber-600 dark:text-amber-500 border border-amber-500/20">
                {{ $t('admin.last_30_days') }}
            </div>
        </div>
        <div class="h-64 w-full">
            <Line :data="chartData" :options="chartOptions" />
        </div>
    </div>

  </div>
</template>
