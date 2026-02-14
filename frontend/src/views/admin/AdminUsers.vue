<script setup>
import { ref, onMounted, watch } from 'vue';
import api from '../../services/api';
import { 
  Search, 
  UserPlus, 
  Edit, 
  Trash2, 
  CheckCircle, 
  XCircle, 
  User, 
  Loader2,
  Filter,
  MoreHorizontal,
  Shield,
  Briefcase
} from 'lucide-vue-next';

const users = ref([]);
const pagination = ref({ current_page: 1, last_page: 1 });
const loading = ref(true);
const roles = ref([]);

const filters = ref({
  search: '',
  role: ''
});

const showUserModal = ref(false);
const isEditing = ref(false);
const userForm = ref({
  id: null,
  name: '',
  email: '',
  password: '',
  role_id: null
});
const formLoading = ref(false);

const stats = ref({
    total: 0,
    providers: 0,
    clients: 0,
    admins: 0
});

const getRoleClass = (role) => {
  switch (role) {
    case 'admin': return 'bg-red-50 text-red-600 border-red-100/50 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20';
    case 'provider': return 'bg-purple-50 text-purple-600 border-purple-100/50 dark:bg-purple-500/10 dark:text-purple-400 dark:border-purple-500/20';
    case 'client': return 'bg-blue-50 text-blue-600 border-blue-100/50 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20';
    default: return 'bg-slate-50 text-slate-600 border-slate-100/50 dark:bg-slate-700/50 dark:text-slate-400 dark:border-slate-600/20';
  }
};

const fetchRoles = async () => {
  try {
    const res = await api.get('/api/admin/roles');
    roles.value = res.data.roles;
  } catch (err) {
    console.error('Erreur roles:', err);
  }
};

const fetchUsers = async (page = 1) => {
  loading.value = true;
  try {
    const res = await api.get('/api/admin/users', {
      params: { 
        page, 
        search: filters.value.search, 
        role: filters.value.role 
      }
    });
    users.value = res.data.data;
    pagination.value = {
      current_page: res.data.current_page,
      last_page: res.data.last_page
    };
    
    // update simple stats from current view (approximate or fetch real stats if needed)
    // For now, let's just fetch global stats separately if needed, but we can rely on what we have.
  } catch (err) {
    console.error('Erreur users:', err);
  } finally {
    loading.value = false;
  }
};

const openCreateModal = () => {
  isEditing.value = false;
  userForm.value = { name: '', email: '', password: '', role_id: roles.value.find(r => r.slug === 'client')?.id || null };
  showUserModal.value = true;
};

const openEditModal = (user) => {
  isEditing.value = true;
  userForm.value = { 
    id: user.id, 
    name: user.name, 
    email: user.email, 
    role_id: user.role_id 
  };
  showUserModal.value = true;
};

const saveUser = async () => {
  formLoading.value = true;
  try {
    if (isEditing.value) {
      await api.put(`/api/admin/users/${userForm.value.id}`, userForm.value);
    } else {
      await api.post('/api/admin/users', userForm.value);
    }
    showUserModal.value = false;
    fetchUsers(pagination.value.current_page);
  } catch (err) {
    alert(err.response?.data?.message || 'Erreur lors de l\'enregistrement');
  } finally {
    formLoading.value = false;
  }
};

const toggleUserStatus = async (user) => {
  try {
    const res = await api.post(`/api/admin/users/${user.id}/toggle-status`);
    user.is_active = res.data.user.is_active;
  } catch (err) {
    alert(err.response?.data?.message || 'Action impossible');
  }
};

const deleteUser = async (id) => {
  if (!confirm('Supprimer cet utilisateur définitivement ?')) return;
  try {
    await api.delete(`/api/admin/users/${id}`);
    fetchUsers(pagination.value.current_page);
  } catch (err) {
    alert(err.response?.data?.message || 'Erreur lors de la suppression');
  }
};

let searchTimeout;
watch(() => filters.value.search, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => fetchUsers(1), 500);
});

watch(() => filters.value.role, () => {
  fetchUsers(1);
});

onMounted(() => {
  fetchRoles();
  fetchUsers();
});
</script>

<template>
  <div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Utilisateurs</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-2 font-bold text-sm">Gérez les comptes clients, prestataires et administrateurs.</p>
      </div>
      <button 
        @click="openCreateModal" 
        class="group relative bg-blue-600 hover:bg-blue-500 text-white px-6 py-4 rounded-2xl font-bold transition-all duration-300 shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 flex items-center space-x-3 overflow-hidden"
      >
        <div class="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></div>
        <UserPlus class="w-5 h-5 relative z-10" />
        <span class="relative z-10">Créer un utilisateur</span>
      </button>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white dark:bg-[#1E293B] p-4 rounded-[24px] border border-slate-200 dark:border-white/5 shadow-sm flex flex-col md:flex-row gap-4 mb-8">
        <div class="relative flex-grow">
            <Search class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
            <input 
                v-model="filters.search"
                type="text" 
                placeholder="Rechercher par nom ou email..."
                class="w-full pl-14 pr-6 py-4 bg-slate-50 dark:bg-[#0F172A] border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 transition-all"
            >
        </div>
        <div class="flex items-center space-x-3 min-w-[200px]">
            <div class="relative w-full">
                <Filter class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                <select 
                    v-model="filters.role"
                    class="w-full pl-12 pr-10 py-4 bg-slate-50 dark:bg-[#0F172A] border-none rounded-2xl text-sm font-bold text-slate-700 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none appearance-none cursor-pointer"
                >
                    <option value="">Tous les rôles</option>
                    <option value="provider">Prestataires</option>
                    <option value="client">Clients</option>
                    <option value="admin">Administrateurs</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white dark:bg-[#1E293B] rounded-[32px] border border-slate-200 dark:border-white/5 shadow-xl shadow-slate-200/50 dark:shadow-none overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-white/5 border-b border-slate-100 dark:border-white/5">
                        <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Utilisateur</th>
                        <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Rôle</th>
                        <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Statut</th>
                        <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Date</th>
                        <th class="px-8 py-6 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                    <tr v-if="loading" v-for="i in 5" :key="i" class="animate-pulse">
                        <td colspan="5" class="px-8 py-8"><div class="h-4 bg-slate-100 dark:bg-white/5 rounded w-full"></div></td>
                    </tr>
                    <tr v-else v-for="user in users" :key="user.id" class="hover:bg-slate-50/50 dark:hover:bg-white/5 transition-colors group">
                        <td class="px-8 py-5">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-[#0f172a] flex items-center justify-center text-slate-400 font-bold overflow-hidden border border-slate-200 dark:border-white/5 group-hover:scale-110 transition-transform duration-300">
                                    <img v-if="user.client?.photo || user.prestataire?.photo" :src="user.client?.photo || user.prestataire?.photo" class="w-full h-full object-cover">
                                    <span v-else class="text-xs">{{ user.name.charAt(0) }}</span>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white leading-tight">{{ user.name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ user.email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-5">
                            <span class="px-3 py-1.5 text-[10px] font-black uppercase tracking-wider rounded-lg border shadow-sm" :class="getRoleClass(user.role)">
                                {{ user.role }}
                            </span>
                        </td>
                        <td class="px-8 py-5">
                            <button 
                                @click="toggleUserStatus(user)"
                                class="flex items-center space-x-2 transition opacity-80 hover:opacity-100"
                                :class="user.is_active ? 'text-green-500' : 'text-red-400'"
                            >
                                <CheckCircle v-if="user.is_active" class="w-4 h-4" />
                                <XCircle v-else class="w-4 h-4" />
                                <span class="text-[10px] font-black uppercase">{{ user.is_active ? 'Actif' : 'Inactif' }}</span>
                            </button>
                        </td>
                        <td class="px-8 py-5 text-xs text-slate-500 dark:text-slate-400 font-bold">
                            {{ new Date(user.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                        </td>
                        <td class="px-8 py-5 text-right">
                            <div class="flex items-center justify-end space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="openEditModal(user)" class="p-2 text-slate-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-500/10 rounded-xl transition-colors">
                                    <Edit class="w-4 h-4" />
                                </button>
                                <button @click="deleteUser(user.id)" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-xl transition-colors">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!loading && users.length === 0">
                        <td colspan="5" class="py-20 text-center">
                            <div class="w-20 h-20 bg-slate-50 dark:bg-white/5 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300 dark:text-slate-600">
                                <Search class="w-8 h-8" />
                            </div>
                            <p class="text-slate-400 font-bold">Aucun utilisateur trouvé</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="px-8 py-6 border-t border-slate-100 dark:border-white/5 flex items-center justify-between bg-slate-50/30 dark:bg-white/5">
            <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest">Page {{ pagination.current_page }} sur {{ pagination.last_page }}</p>
            <div class="flex space-x-2">
                <button 
                    @click="fetchUsers(pagination.current_page - 1)" 
                    :disabled="pagination.current_page === 1"
                    class="px-4 py-2 bg-white dark:bg-[#0f172a] border border-slate-200 dark:border-white/10 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5 disabled:opacity-50 transition"
                >
                    Précédent
                </button>
                <button 
                    @click="fetchUsers(pagination.current_page + 1)" 
                    :disabled="pagination.current_page === pagination.last_page"
                    class="px-4 py-2 bg-white dark:bg-[#0f172a] border border-slate-200 dark:border-white/10 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5 disabled:opacity-50 transition"
                >
                    Suivant
                </button>
            </div>
        </div>
    </div>

    <!-- Modal (Create/Edit) -->
    <div v-if="showUserModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showUserModal = false"></div>
        <div class="relative bg-white dark:bg-[#1e293b] w-full max-w-lg rounded-[32px] shadow-2xl p-8 border border-slate-200 dark:border-white/10 animate-in fade-in zoom-in duration-300">
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-6">
                {{ isEditing ? 'Modifier' : 'Créer' }} un utilisateur
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase">Nom complet</label>
                    <input v-model="userForm.name" type="text" class="w-full px-4 py-3 bg-slate-50 dark:bg-[#0f172a] rounded-xl border border-slate-200 dark:border-white/10 focus:ring-2 focus:ring-blue-500 outline-none text-slate-900 dark:text-white font-bold text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase">Email</label>
                    <input v-model="userForm.email" type="email" class="w-full px-4 py-3 bg-slate-50 dark:bg-[#0f172a] rounded-xl border border-slate-200 dark:border-white/10 focus:ring-2 focus:ring-blue-500 outline-none text-slate-900 dark:text-white font-bold text-sm">
                </div>
                <div v-if="!isEditing">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase">Mot de passe</label>
                    <input v-model="userForm.password" type="password" class="w-full px-4 py-3 bg-slate-50 dark:bg-[#0f172a] rounded-xl border border-slate-200 dark:border-white/10 focus:ring-2 focus:ring-blue-500 outline-none text-slate-900 dark:text-white font-bold text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase">Rôle</label>
                    <select v-model="userForm.role_id" class="w-full px-4 py-3 bg-slate-50 dark:bg-[#0f172a] rounded-xl border border-slate-200 dark:border-white/10 focus:ring-2 focus:ring-blue-500 outline-none text-slate-900 dark:text-white font-bold text-sm appearance-none">
                        <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                    </select>
                </div>
            </div>

            <div class="flex space-x-4 mt-8">
                <button @click="showUserModal = false" class="flex-1 py-4 bg-slate-100 dark:bg-[#0f172a] text-slate-600 dark:text-slate-300 font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-white/5 transition">Annuler</button>
                <button @click="saveUser" :disabled="formLoading" class="flex-1 py-4 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-500 transition shadow-lg shadow-blue-500/30 flex justify-center items-center">
                    <Loader2 v-if="formLoading" class="w-5 h-5 animate-spin mr-2" />
                    {{ isEditing ? 'Enregistrer' : 'Créer' }}
                </button>
            </div>
        </div>
    </div>
  </div>
</template>
