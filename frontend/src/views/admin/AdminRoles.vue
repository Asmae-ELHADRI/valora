<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../../services/api';
import { 
    Shield, Plus, Edit, Trash2, Check, X, Lock, Users, AlertTriangle, Loader2 
} from 'lucide-vue-next';

const roles = ref([]);
const permissions = ref([]);
const groupedPermissions = ref({});
const loading = ref(true);
const saving = ref(false);

const showModal = ref(false);
const isEditing = ref(false);
const currentRole = ref({
    name: '',
    slug: '',
    permissions: []
});

const formError = ref(null);

const fetchRoles = async () => {
    loading.value = true;
    try {
        const [rolesRes, permsRes] = await Promise.all([
            api.get('/api/admin/roles'),
            api.get('/api/admin/permissions')
        ]);
        roles.value = rolesRes.data.roles;
        permissions.value = permsRes.data.permissions;
        groupedPermissions.value = permsRes.data.grouped;
    } catch (err) {
        console.error("Error fetching RBAC:", err);
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    isEditing.value = false;
    currentRole.value = { name: '', slug: '', permissions: [] };
    formError.value = null;
    showModal.value = true;
};

const openEditModal = (role) => {
    isEditing.value = true;
    currentRole.value = { 
        id: role.id,
        name: role.name, 
        slug: role.slug, 
        permissions: role.permissions.map(p => p.id) 
    };
    formError.value = null;
    showModal.value = true;
};

const saveRole = async () => {
    if (!currentRole.value.name) return;
    
    saving.value = true;
    formError.value = null;

    try {
        if (isEditing.value) {
            await api.put(`/api/admin/roles/${currentRole.value.id}`, currentRole.value);
        } else {
            // Slug generation if not provided (simple logic)
            if (!currentRole.value.slug) {
                currentRole.value.slug = currentRole.value.name.toLowerCase().replace(/ /g, '-');
            }
            await api.post('/api/admin/roles', currentRole.value);
        }
        await fetchRoles();
        showModal.value = false;
    } catch (err) {
        console.error(err);
        formError.value = err.response?.data?.message || "Erreur lors de l'enregistrement.";
    } finally {
        saving.value = false;
    }
};

const deleteRole = async (roleId) => {
    if (!confirm("Êtes-vous sûr de vouloir supprimer ce rôle ?")) return;
    try {
        await api.delete(`/api/admin/roles/${roleId}`);
        await fetchRoles();
    } catch (err) {
        alert(err.response?.data?.message || "Erreur lors de la suppression");
    }
};

const togglePermission = (permId) => {
    const minter = new Set(currentRole.value.permissions);
    if (minter.has(permId)) {
        minter.delete(permId);
    } else {
        minter.add(permId);
    }
    currentRole.value.permissions = Array.from(minter);
};

const selectAllGroup = (groupName) => {
    const groupPerms = groupedPermissions.value[groupName].map(p => p.id);
    const minter = new Set(currentRole.value.permissions);
    
    // Check if all already selected, then deserialize
    const allSelected = groupPerms.every(id => minter.has(id));
    
    if (allSelected) {
        groupPerms.forEach(id => minter.delete(id));
    } else {
        groupPerms.forEach(id => minter.add(id));
    }
    currentRole.value.permissions = Array.from(minter);
};

onMounted(() => {
    fetchRoles();
});
</script>

<template>
    <div class="space-y-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                    <Shield class="w-8 h-8 text-premium-yellow" />
                    Rôles & Permissions
                </h1>
                <p class="text-slate-500 font-medium mt-1">Gérez les accès et les privilèges des utilisateurs.</p>
            </div>
            <button 
                @click="openCreateModal"
                class="px-5 py-3 bg-premium-yellow text-slate-900 rounded-xl font-bold uppercase tracking-widest text-xs hover:bg-premium-brown hover:text-white transition-all shadow-lg shadow-yellow-500/20 flex items-center gap-2"
            >
                <Plus class="w-4 h-4" /> Nouveau Rôle
            </button>
        </div>

        <!-- Roles Grid -->
        <div v-if="loading" class="flex justify-center py-20">
            <Loader2 class="w-10 h-10 animate-spin text-slate-300" />
        </div>
        
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="role in roles" :key="role.id" class="group bg-white rounded-3xl p-6 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 border border-slate-100 relative overflow-hidden">
                <!-- Header -->
                <div class="flex justify-between items-start mb-6 z-10 relative">
                    <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center">
                        <Lock class="w-6 h-6 text-slate-400 group-hover:text-premium-yellow transition-colors" />
                    </div>
                    <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                         <button 
                            @click="openEditModal(role)"
                            class="p-2 hover:bg-blue-50 text-blue-500 rounded-lg transition-colors"
                            title="Modifier"
                        >
                            <Edit class="w-4 h-4" />
                        </button>
                        <button 
                            v-if="!['admin','provider','client'].includes(role.slug)"
                            @click="deleteRole(role.id)"
                            class="p-2 hover:bg-red-50 text-red-500 rounded-lg transition-colors"
                            title="Supprimer"
                        >
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <div class="mb-6 relative z-10">
                    <h3 class="text-xl font-black text-slate-900 tracking-tight mb-1">{{ role.name }}</h3>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest bg-slate-50 px-2 py-1 rounded-md">{{ role.slug }}</span>
                </div>

                <!-- Permissions Preview -->
                <div class="space-y-3 relative z-10">
                     <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Permissions ({{ role.permissions.length }})</p>
                     <div class="flex flex-wrap gap-1.5">
                         <span v-for="perm in role.permissions.slice(0, 5)" :key="perm.id" class="px-2 py-1 rounded bg-slate-50 border border-slate-100 text-[10px] font-bold text-slate-600">
                             {{ perm.name }}
                         </span>
                         <span v-if="role.permissions.length > 5" class="px-2 py-1 text-[10px] font-bold text-slate-400">
                             +{{ role.permissions.length - 5 }} autres
                         </span>
                         <span v-if="role.permissions.length === 0" class="text-xs text-slate-300 italic">Aucune permission</span>
                     </div>
                </div>

                <!-- Decoration -->
                 <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-premium-yellow/5 rounded-full blur-2xl group-hover:bg-premium-yellow/10 transition-colors"></div>
            </div>
        </div>

        <!-- Setup Modal -->
        <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
             <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="showModal = false"></div>
             
             <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col relative animate-in zoom-in-95 duration-300">
                 <!-- Header -->
                 <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                     <h2 class="text-2xl font-black text-slate-900">
                         {{ isEditing ? 'Modifier le Rôle' : 'Créer un Rôle' }}
                     </h2>
                     <button @click="showModal = false" class="p-2 hover:bg-slate-100 rounded-full transition-colors">
                         <X class="w-6 h-6 text-slate-400" />
                     </button>
                 </div>

                 <!-- Body : Scrollable -->
                 <div class="p-8 overflow-y-auto custom-scrollbar flex-1 space-y-8">
                     <!-- Basic Info -->
                     <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                         <div class="space-y-2">
                             <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Nom du Rôle</label>
                             <input v-model="currentRole.name" type="text" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-premium-yellow focus:ring-4 focus:ring-premium-yellow/10 outline-none font-bold text-slate-900 transition-all" placeholder="Ex: Modérateur">
                         </div>
                         <div class="space-y-2">
                             <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Slug (Identifiant unique)</label>
                             <input v-model="currentRole.slug" type="text" :disabled="isEditing" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 font-bold text-slate-600" placeholder="Auto-généré si vide">
                         </div>
                     </div>

                     <!-- Permissions Matrix -->
                     <div>
                         <h3 class="text-lg font-black text-slate-900 mb-6 flex items-center gap-2">
                             <Lock class="w-5 h-5 text-premium-brown" /> Permissions
                         </h3>
                         
                         <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                             <div v-for="(perms, group) in groupedPermissions" :key="group" class="bg-slate-50 rounded-2xl p-5 border border-slate-100">
                                 <div class="flex justify-between items-center mb-4 pb-2 border-b border-slate-200/60">
                                     <h4 class="font-black text-slate-700 capitalize">{{ group }}</h4>
                                     <button @click="selectAllGroup(group)" class="text-[10px] font-bold text-premium-yellow hover:text-premium-brown uppercase tracking-wider">
                                         Tout cocher
                                     </button>
                                 </div>
                                 <div class="space-y-3">
                                     <label v-for="perm in perms" :key="perm.id" class="flex items-start gap-3 cursor-pointer group/check">
                                         <div 
                                            class="w-5 h-5 rounded border-2 flex items-center justify-center transition-all mt-0.5 shrink-0"
                                            :class="currentRole.permissions.includes(perm.id) ? 'bg-premium-yellow border-premium-yellow' : 'border-slate-300 bg-white group-hover/check:border-premium-yellow'"
                                         >
                                             <Check v-if="currentRole.permissions.includes(perm.id)" class="w-3.5 h-3.5 text-slate-900" />
                                         </div>
                                         <input type="checkbox" class="hidden" :checked="currentRole.permissions.includes(perm.id)" @change="togglePermission(perm.id)">
                                         <span class="text-sm font-medium text-slate-600 group-hover/check:text-slate-900 transition-colors select-none leading-tight">
                                             {{ perm.name }}
                                         </span>
                                     </label>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>

                 <!-- Footer -->
                 <div class="p-6 border-t border-slate-100 bg-slate-50 flex justify-between items-center z-10">
                     <p v-if="formError" class="text-xs font-bold text-red-500 pl-2">{{ formError }}</p>
                     <div v-else></div>
                     <div class="flex gap-4">
                         <button @click="showModal = false" class="px-6 py-3 rounded-xl font-bold text-slate-500 hover:bg-white hover:text-slate-900 transition-colors">
                             Annuler
                         </button>
                         <button 
                            @click="saveRole" 
                            :disabled="saving"
                            class="px-8 py-3 bg-slate-900 text-white rounded-xl font-bold uppercase tracking-widest hover:bg-slate-800 transition-all shadow-lg flex items-center gap-2"
                         >
                            <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
                            <span>{{ isEditing ? 'Enregistrer' : 'Créer' }}</span>
                         </button>
                     </div>
                 </div>
             </div>
        </div>
    </div>
</template>
