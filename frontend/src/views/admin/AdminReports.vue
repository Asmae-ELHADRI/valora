<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../../services/api';
import { ShieldAlert, Eye, X, AlertTriangle, Ban, Trash2, CheckCircle } from 'lucide-vue-next';

const reports = ref([]);
const loading = ref(true);
const selectedReport = ref(null);
const showDetailModal = ref(false);
const actionModal = ref({ show: false, type: null, report: null });

// Form states
const actionForms = ref({
    warn: { warning_message: '', notes: '' },
    suspend: { suspension_days: 7, reason: '', notes: '' },
    delete: { confirmation: '', reason: '', notes: '' },
    ignore: { notes: '' }
});

const filter = ref('pending'); // pending, ignored, warned, suspended, deleted

const fetchReports = async () => {
    loading.value = true;
    try {
        const response = await api.get('/api/admin/reports', {
            params: { admin_action: filter.value }
        });
        reports.value = response.data.data;
    } catch (error) {
        console.error('Error fetching reports:', error);
    } finally {
        loading.value = false;
    }
};

const viewReport = (report) => {
    selectedReport.value = report;
    showDetailModal.value = true;
};

const openActionModal = (type, report) => {
    actionModal.value = { show: true, type, report };
    // Reset forms
    Object.keys(actionForms.value).forEach(key => {
        Object.keys(actionForms.value[key]).forEach(field => {
            actionForms.value[key][field] = '';
        });
    });
    if (type === 'suspend') actionForms.value.suspend.suspension_days = 7;
};

const closeActionModal = () => {
    actionModal.value = { show: false, type: null, report: null };
};

const ignoreReport = async () => {
    try {
        await api.post(`/api/admin/reports/${actionModal.value.report.id}/ignore`, actionForms.value.ignore);
        await fetchReports();
        closeActionModal();
        alert('Signalement ignoré');
    } catch (error) {
        alert('Erreur: ' + (error.response?.data?.message || 'Erreur inconnue'));
    }
};

const warnUser = async () => {
    try {
        await api.post(`/api/admin/reports/${actionModal.value.report.id}/warn`, actionForms.value.warn);
        await fetchReports();
        closeActionModal();
        alert('Utilisateur averti');
    } catch (error) {
        alert('Erreur: ' + (error.response?.data?.message || 'Erreur inconnue'));
    }
};

const suspendUser = async () => {
    try {
        await api.post(`/api/admin/reports/${actionModal.value.report.id}/suspend`, actionForms.value.suspend);
        await fetchReports();
        closeActionModal();
        alert('Compte suspendu');
    } catch (error) {
        alert('Erreur: ' + (error.response?.data?.message || 'Erreur inconnue'));
    }
};

const deleteAccount = async () => {
    if (actionForms.value.delete.confirmation !== 'DELETE_ACCOUNT') {
        alert('Veuillez taper DELETE_ACCOUNT pour confirmer');
        return;
    }
    try {
        await api.post(`/api/admin/reports/${actionModal.value.report.id}/delete-account`, actionForms.value.delete);
        await fetchReports();
        closeActionModal();
        alert('Compte supprimé');
    } catch (error) {
        alert('Erreur: ' + (error.response?.data?.message || 'Erreur inconnue'));
    }
};

const executeAction = () => {
    switch (actionModal.value.type) {
        case 'ignore': ignoreReport(); break;
        case 'warn': warnUser(); break;
        case 'suspend': suspendUser(); break;
        case 'delete': deleteAccount(); break;
    }
};

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800',
        ignored: 'bg-gray-100 text-gray-800',
        warned: 'bg-orange-100 text-orange-800',
        suspended: 'bg-red-100 text-red-800',
        deleted: 'bg-black text-white'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

onMounted(() => {
    fetchReports();
});
</script>

<template>
<div class="p-8 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-black text-gray-900 flex items-center gap-3">
                    <ShieldAlert class="w-8 h-8 text-red-600" />
                    Modération des Signalements
                </h1>
                <p class="text-gray-500 mt-1">Gérer les signalements d'utilisateurs</p>
            </div>
            <select v-model="filter" @change="fetchReports" class="px-4 py-2 border rounded-lg">
                <option value="pending">En attente</option>
                <option value="ignored">Ignorés</option>
                <option value="warned">Avertis</option>
                <option value="suspended">Suspendus</option>
                <option value="deleted">Supprimés</option>
            </select>
        </div>

        <!-- Reports Table -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 uppercase">ID</th>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 uppercase">Signalé par</th>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 uppercase">Compte signalé</th>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 uppercase">Raison</th>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 uppercase">Statut</th>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 uppercase">Date</th>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">Chargement...</td>
                    </tr>
                    <tr v-else-if="reports.length === 0">
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">Aucun signalement</td>
                    </tr>
                    <tr v-for="report in reports" :key="report.id" class="border-b hover:bg-gray-50">
                        <td class="px-6 py-4 font-bold text-gray-900">#{{report.id}}</td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900">{{report.reporter.name}}</div>
                            <div class="text-xs text-gray-500">{{report.reporter.email}}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900">{{report.reported.name}}</div>
                            <div class="text-xs text-gray-500">{{report.reported.email}}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{report.reason?.substring(0, 50)}}...</td>
                        <td class="px-6 py-4">
                            <span :class="getStatusColor(report.admin_action)" class="px-3 py-1 rounded-full text-xs font-bold">
                                {{report.admin_action}}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{new Date(report.created_at).toLocaleDateString('fr-FR')}}</td>
                        <td class="px-6 py-4">
                            <div class="flex gap-2">
                                <button @click="viewReport(report)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg">
                                    <Eye class="w-4 h-4" />
                                </button>
                                <template v-if="report.admin_action === 'pending'">
                                    <button @click="openActionModal('ignore', report)" class="p-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                                        <X class="w-4 h-4" />
                                    </button>
                                    <button @click="openActionModal('warn', report)" class="p-2 text-orange-600 hover:bg-orange-50 rounded-lg">
                                        <AlertTriangle class="w-4 h-4" />
                                    </button>
                                    <button @click="openActionModal('suspend', report)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg">
                                        <Ban class="w-4 h-4" />
                                    </button>
                                    <button @click="openActionModal('delete', report)" class="p-2 text-black hover:bg-gray-100 rounded-lg">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </template>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Action Modal -->
    <div v-if="actionModal.show" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6">
            <h3 class="text-xl font-black mb-4">
                <span v-if="actionModal.type === 'ignore'">Ignorer le signalement</span>
                <span v-if="actionModal.type === 'warn'">Avertir l'utilisateur</span>
                <span v-if="actionModal.type === 'suspend'">Suspendre le compte</span>
                <span v-if="actionModal.type === 'delete'">Supprimer le compte</span>
            </h3>

            <!-- Ignore Form -->
            <div v-if="actionModal.type === 'ignore'" class="space-y-4">
                <textarea v-model="actionForms.ignore.notes" placeholder="Notes (optionnel)" class="w-full border rounded-lg px-4 py-2" rows="3"></textarea>
            </div>

            <!-- Warn Form -->
            <div v-if="actionModal.type === 'warn'" class="space-y-4">
                <textarea v-model="actionForms.warn.warning_message" placeholder="Message d'avertissement *" class="w-full border rounded-lg px-4 py-2" rows="3"></textarea>
                <textarea v-model="actionForms.warn.notes" placeholder="Notes internes (optionnel)" class="w-full border rounded-lg px-4 py-2" rows="2"></textarea>
            </div>

            <!-- Suspend Form -->
            <div v-if="actionModal.type === 'suspend'" class="space-y-4">
                <input type="number" v-model="actionForms.suspend.suspension_days" placeholder="Jours de suspension" class="w-full border rounded-lg px-4 py-2" />
                <textarea v-model="actionForms.suspend.reason" placeholder="Raison de la suspension *" class="w-full border rounded-lg px-4 py-2" rows="3"></textarea>
                <textarea v-model="actionForms.suspend.notes" placeholder="Notes internes (optionnel)" class="w-full border rounded-lg px-4 py-2" rows="2"></textarea>
            </div>

            <!-- Delete Form -->
            <div v-if="actionModal.type === 'delete'" class="space-y-4">
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-800 text-sm">
                    ⚠️ Cette action est IRRÉVERSIBLE. Le compte et toutes ses données seront supprimés.
                </div>
                <input type="text" v-model="actionForms.delete.confirmation" placeholder="Tapez DELETE_ACCOUNT pour confirmer" class="w-full border rounded-lg px-4 py-2" />
                <textarea v-model="actionForms.delete.reason" placeholder="Raison de la suppression *" class="w-full border rounded-lg px-4 py-2" rows="3"></textarea>
                <textarea v-model="actionForms.delete.notes" placeholder="Notes internes (optionnel)" class="w-full border rounded-lg px-4 py-2" rows="2"></textarea>
            </div>

            <div class="flex gap-3 mt-6">
                <button @click="closeActionModal" class="flex-1 px-4 py-2 border rounded-lg hover:bg-gray-50">Annuler</button>
                <button @click="executeAction" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Confirmer</button>
            </div>
        </div>
    </div>
</div>
</template>
