<script setup>
import { ref } from 'vue';
import api from '../services/api';
import { X, AlertTriangle, CheckCircle, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    isOpen: Boolean,
    userId: Number,
    userName: String
});

const emit = defineEmits(['close', 'success']);

const reason = ref('Comportement inapproprié');
const description = ref('');
const loading = ref(false);
const error = ref('');
const success = ref(false);

const reasons = [
    'Comportement inapproprié',
    'Harcèlement ou intimidation',
    'Contenu indésirable / Spam',
    'Faux profil',
    'Arnaque ou fraude',
    'Autre'
];

const submitReport = async () => {
    if (!props.userId) return;
    loading.value = true;
    error.value = '';
    
    try {
        await api.post('/api/report', {
            reported_id: props.userId,
            reason: reason.value + (description.value ? `: ${description.value}` : '')
        });
        success.value = true;
        setTimeout(() => {
            emit('success');
            close();
        }, 2000);
    } catch (err) {
        error.value = err.response?.data?.message || 'Erreur lors du signalement.';
    } finally {
        loading.value = false;
    }
};

const close = () => {
    emit('close');
    // Reset state after transition
    setTimeout(() => {
        success.value = false;
        error.value = '';
        reason.value = 'Comportement inapproprié';
        description.value = '';
    }, 300);
};
</script>

<template>
    <Transition 
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="isOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div 
                class="bg-white rounded-[32px] w-full max-w-md shadow-2xl overflow-hidden animate-in zoom-in-95 duration-200"
                @click.stop
            >
                <!-- Header -->
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-red-100 rounded-xl text-red-600">
                            <AlertTriangle class="w-6 h-6" />
                        </div>
                        <h3 class="text-lg font-black text-slate-900">Signaler un problème</h3>
                    </div>
                    <button @click="close" class="p-2 rounded-xl hover:bg-slate-100 text-slate-400 transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Body -->
                <div class="p-8 space-y-6">
                    <div v-if="success" class="flex flex-col items-center justify-center py-8 text-center space-y-4 animate-in fade-in">
                        <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mb-2">
                             <CheckCircle class="w-8 h-8" />
                        </div>
                        <h4 class="text-xl font-black text-slate-900">Signalement envoyé</h4>
                        <p class="text-slate-500 font-medium">Nous allons examiner ce profil dans les plus brefs délais.</p>
                    </div>

                    <div v-else class="space-y-6">
                        <p class="text-sm text-slate-500 font-medium">
                            Vous signalez l'utilisateur <strong class="text-slate-900">{{ userName }}</strong>. Ce signalement restera anonyme.
                        </p>

                        <div class="space-y-3">
                            <label class="block text-xs font-black uppercase tracking-widest text-slate-400">Raison</label>
                            <div class="grid grid-cols-1 gap-2">
                                <button 
                                    v-for="r in reasons" 
                                    :key="r"
                                    @click="reason = r"
                                    class="text-left px-4 py-3 rounded-xl border-2 transition-all font-bold text-sm"
                                    :class="reason === r ? 'border-red-500 bg-red-50 text-red-700' : 'border-slate-100 text-slate-600 hover:border-slate-300'"
                                >
                                    {{ r }}
                                </button>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="block text-xs font-black uppercase tracking-widest text-slate-400">Détails (Optionnel)</label>
                            <textarea 
                                v-model="description" 
                                rows="3" 
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-slate-900 focus:ring-0 outline-none font-medium text-slate-900 resize-none text-sm"
                                placeholder="Fournissez plus de contexte..."
                            ></textarea>
                        </div>

                        <div v-if="error" class="p-4 rounded-xl bg-red-50 text-red-600 text-sm font-bold flex items-center gap-2">
                            <AlertTriangle class="w-4 h-4 shrink-0" />
                            {{ error }}
                        </div>

                        <div class="flex gap-4 pt-2">
                            <button @click="close" class="w-full py-4 rounded-xl border border-slate-200 text-slate-600 font-black text-[11px] uppercase tracking-widest hover:bg-slate-50 transition-colors">
                                Annuler
                            </button>
                            <button 
                                @click="submitReport" 
                                :disabled="loading"
                                class="w-full py-4 rounded-xl bg-red-600 text-white font-black text-[11px] uppercase tracking-widest hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-xl shadow-red-200"
                            >
                                <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
                                <span>{{ loading ? 'Envoi...' : 'Signaler' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
