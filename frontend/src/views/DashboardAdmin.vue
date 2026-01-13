<script setup>
import { ref, onMounted, watch } from 'vue';
import { useAuthStore } from '../store/auth';
import api from '../services/api';
import { 
  Users, Briefcase, Flag, BarChart3, Search, 
  UserPlus, MoreHorizontal, Edit, Trash2, 
  CheckCircle, XCircle, Shield, ShieldAlert,
  Loader2, ArrowRight, User, Mail, ShieldCheck,
  AlertTriangle, Filter, LayoutGrid, List as ListIcon
} from 'lucide-vue-next';

const auth = useAuthStore();
const adminStats = ref({
  users_count: 0,
  providers_count: 0,
  clients_count: 0,
  offers_count: 0,
  reports_count: 0
});

const users = ref([]);
const complaints = ref([]);
const roles = ref([]);
const allPermissions = ref([]);
const pagination = ref({ current_page: 1, last_page: 1 });
const complaintsPagination = ref({ current_page: 1, last_page: 1 });
const loading = ref(true);
const reportsLoading = ref(false);
const rolesLoading = ref(false);
const statsLoading = ref(true);
const activeTab = ref('users'); // users, reports, roles

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

const fetchStats = async () => {
  statsLoading.value = true;
  try {
    const res = await api.get('/api/admin/stats');
    adminStats.value = res.data;
  } catch (err) {
    console.error('Erreur stats:', err);
  } finally {
    statsLoading.value = false;
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
  } catch (err) {
    console.error('Erreur users:', err);
  } finally {
    loading.value = false;
  }
};

const fetchComplaints = async (page = 1) => {
  reportsLoading.value = true;
  try {
    const res = await api.get('/api/admin/complaints', {
      params: { page }
    });
    complaints.value = res.data.data;
    complaintsPagination.value = {
      current_page: res.data.current_page,
      last_page: res.data.last_page
    };
  } catch (err) {
    console.error('Erreur complaints:', err);
    } finally {
    reportsLoading.value = false;
  }
};

const fetchRoles = async () => {
  rolesLoading.value = true;
  try {
    const res = await api.get('/api/admin/roles');
    roles.value = res.data.roles;
    allPermissions.value = res.data.permissions;
  } catch (err) {
    console.error('Erreur roles:', err);
  } finally {
    rolesLoading.value = false;
  }
};

const updateRolePermissions = async (role) => {
  try {
    const permissionIds = role.permissions.map(p => p.id);
    await api.put(`/api/admin/roles/${role.id}/permissions`, { permissions: permissionIds });
    alert('Permissions mises à jour avec succès');
  } catch (err) {
    alert('Erreur lors de la mise à jour des permissions');
  }
};

const togglePermission = (role, permission) => {
  const index = role.permissions.findIndex(p => p.id === permission.id);
  if (index === -1) {
    role.permissions.push(permission);
  } else {
    role.permissions.splice(index, 1);
  }
};

const hasPermission = (role, permission) => {
  return role.permissions.some(p => p.id === permission.id);
};

const updateComplaintStatus = async (complaint, status) => {
  try {
    await api.post(`/api/admin/complaints/${complaint.id}/status`, { status });
    complaint.status = status;
    fetchStats();
  } catch (err) {
    alert('Erreur lors de la mise à jour');
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
    fetchStats();
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
    fetchStats();
  } catch (err) {
    alert(err.response?.data?.message || 'Erreur lors de la suppression');
  }
};

watch([() => filters.value.role], () => {
  fetchUsers(1);
});

let searchTimeout;
watch(() => filters.value.search, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => fetchUsers(1), 500);
});

onMounted(() => {
  fetchStats();
  fetchUsers();
  fetchComplaints();
  fetchRoles();
});

const getRoleClass = (role) => {
  switch (role) {
    case 'admin': return 'bg-red-50 text-red-600 border-red-100';
    case 'provider': return 'bg-purple-50 text-purple-600 border-purple-100';
    case 'client': return 'bg-blue-50 text-blue-600 border-blue-100';
    default: return 'bg-gray-50 text-gray-600 border-gray-100';
  }
};
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
      <div>
        <h1 class="text-3xl font-black text-gray-900 tracking-tight">Console d'administration</h1>
        <p class="text-gray-500 mt-2 font-medium">Gestion globale de la plateforme VALORA</p>
      </div>
      <div class="flex items-center space-x-3">
        <button @click="openCreateModal" class="bg-gray-900 text-white px-6 py-4 rounded-2xl font-bold hover:bg-black transition shadow-xl shadow-gray-200 flex items-center space-x-2">
          <UserPlus class="w-5 h-5" />
          <span>Créer un utilisateur</span>
        </button>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
      <div class="bg-white p-8 rounded-[32px] shadow-sm border border-gray-100 group hover:border-blue-200 transition-all">
        <div class="flex items-center justify-between mb-6">
          <div class="bg-blue-50 p-4 rounded-2xl group-hover:scale-110 transition-transform">
            <Users class="w-6 h-6 text-blue-600" />
          </div>
          <div v-if="statsLoading" class="w-4 h-4 rounded-full border-2 border-blue-200 border-t-blue-600 animate-spin"></div>
        </div>
        <p class="text-sm text-gray-400 font-black uppercase tracking-widest">{{ adminStats.users_count }} Utilisateurs</p>
        <div class="mt-1 flex items-baseline space-x-2">
          <p class="text-4xl font-black text-gray-900">{{ adminStats.providers_count + adminStats.clients_count }}</p>
          <span class="text-xs font-bold text-gray-400">Total actifs</span>
        </div>
      </div>

      <div class="bg-white p-8 rounded-[32px] shadow-sm border border-gray-100 group hover:border-purple-200 transition-all">
        <div class="flex items-center justify-between mb-6">
          <div class="bg-purple-50 p-4 rounded-2xl group-hover:scale-110 transition-transform">
            <Briefcase class="w-6 h-6 text-purple-600" />
          </div>
        </div>
        <p class="text-sm text-gray-400 font-black uppercase tracking-widest">Offres en ligne</p>
        <p class="text-4xl font-black text-gray-900 mt-1">{{ adminStats.offers_count }}</p>
      </div>

      <div class="bg-white p-8 rounded-[32px] shadow-sm border border-gray-100 group hover:border-red-200 transition-all">
        <div class="flex items-center justify-between mb-6">
          <div class="bg-red-50 p-4 rounded-2xl group-hover:scale-110 transition-transform relative">
            <Flag class="w-6 h-6 text-red-600" />
            <span v-if="adminStats.reports_count > 0" class="absolute -top-1 -right-1 w-4 h-4 bg-red-600 rounded-full border-2 border-white"></span>
          </div>
        </div>
        <p class="text-sm text-gray-400 font-black uppercase tracking-widest">Signalements</p>
        <div class="mt-1 flex items-baseline space-x-2">
          <p class="text-4xl font-black text-gray-900">{{ adminStats.reports_count }}</p>
          <span v-if="adminStats.reports_count > 0" class="text-xs font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded">À traiter</span>
        </div>
      </div>

      <div class="bg-white p-8 rounded-[32px] shadow-sm border border-gray-100 group hover:border-orange-200 transition-all">
        <div class="flex items-center justify-between mb-6">
          <div class="bg-orange-50 p-4 rounded-2xl group-hover:scale-110 transition-transform">
            <BarChart3 class="w-6 h-6 text-orange-600" />
          </div>
        </div>
        <p class="text-sm text-gray-400 font-black uppercase tracking-widest">Répartition</p>
        <div class="mt-2 flex h-2 w-full bg-gray-100 rounded-full overflow-hidden">
          <div :style="{ width: (adminStats.providers_count / adminStats.users_count * 100) + '%' }" class="bg-purple-500"></div>
          <div :style="{ width: (adminStats.clients_count / adminStats.users_count * 100) + '%' }" class="bg-blue-500"></div>
        </div>
        <div class="mt-3 flex justify-between text-[10px] font-bold uppercase tracking-tighter">
          <span class="text-purple-600">{{ adminStats.providers_count }} Prestataires</span>
          <span class="text-blue-600">{{ adminStats.clients_count }} Clients</span>
        </div>
      </div>
    </div>

    <!-- Content Tabs -->
    <div class="mb-8 flex space-x-6 border-b border-gray-100">
      <button 
        @click="activeTab = 'users'"
        class="pb-4 px-2 text-sm font-black uppercase tracking-widest transition relative"
        :class="activeTab === 'users' ? 'text-gray-900' : 'text-gray-400 hover:text-gray-600'"
      >
        Utilisateurs
        <div v-if="activeTab === 'users'" class="absolute bottom-0 left-0 right-0 h-1 bg-gray-900 rounded-t-full"></div>
      </button>
      <button 
        @click="activeTab = 'roles'"
        class="pb-4 px-2 text-sm font-black uppercase tracking-widest transition relative"
        :class="activeTab === 'roles' ? 'text-gray-900' : 'text-gray-400 hover:text-gray-600'"
      >
        Rôles & Permissions
        <div v-if="activeTab === 'roles'" class="absolute bottom-0 left-0 right-0 h-1 bg-gray-900 rounded-t-full"></div>
      </button>
    </div>

    <!-- Users Management View -->
    <div v-if="activeTab === 'users'" class="space-y-6">
      <!-- Filters -->
      <div class="flex flex-col md:flex-row gap-4 items-center justify-between bg-white p-4 rounded-3xl border border-gray-100 shadow-sm">
        <div class="relative flex-grow max-w-md w-full">
          <Search class="absolute left-5 top-4 w-5 h-5 text-gray-400" />
          <input 
            v-model="filters.search"
            type="text" 
            placeholder="Rechercher par nom ou email..."
            class="w-full pl-14 pr-6 py-4 bg-gray-50 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 transition"
          >
        </div>
        <div class="flex items-center space-x-3 w-full md:w-auto">
          <select 
            v-model="filters.role"
            class="flex-grow md:flex-none px-6 py-4 bg-gray-50 border-none rounded-2xl text-sm font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 outline-none"
          >
            <option value="">Tous les rôles</option>
            <option value="provider">Prestataires</option>
            <option value="client">Clients</option>
            <option value="admin">Administrateurs</option>
          </select>
        </div>
      </div>

      <!-- Users Table -->
      <div class="bg-white rounded-[40px] border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-50/50">
                <th class="px-8 py-5 text-xs font-black text-gray-400 uppercase tracking-widest">Utilisateur</th>
                <th class="px-8 py-5 text-xs font-black text-gray-400 uppercase tracking-widest">Rôle</th>
                <th class="px-8 py-5 text-xs font-black text-gray-400 uppercase tracking-widest">Statut</th>
                <th class="px-8 py-5 text-xs font-black text-gray-400 uppercase tracking-widest">Date d'inscription</th>
                <th class="px-8 py-5 text-right text-xs font-black text-gray-400 uppercase tracking-widest">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-if="loading" v-for="i in 5" :key="i" class="animate-pulse">
                <td colspan="5" class="px-8 py-8"><div class="h-4 bg-gray-100 rounded w-full"></div></td>
              </tr>
              <tr v-else v-for="user in users" :key="user.id" class="hover:bg-gray-50/30 transition-colors">
                <td class="px-8 py-6">
                  <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center text-gray-400 font-bold overflow-hidden border border-gray-100">
                      <img v-if="user.client?.photo || user.prestataire?.photo" :src="user.client?.photo || user.prestataire?.photo" class="w-full h-full object-cover">
                      <User v-else class="w-6 h-6" />
                    </div>
                    <div>
                      <p class="text-base font-bold text-gray-900 leading-tight">{{ user.name }}</p>
                      <p class="text-xs text-gray-500 mt-1">{{ user.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-8 py-6">
                  <span class="px-3 py-1.5 text-[10px] font-black uppercase tracking-wider rounded-lg border border-transparent shadow-sm" :class="getRoleClass(user.role)">
                    {{ user.role }}
                  </span>
                </td>
                <td class="px-8 py-6">
                  <button 
                    @click="toggleUserStatus(user)"
                    class="flex items-center space-x-2 transition"
                    :class="user.is_active ? 'text-green-600' : 'text-red-400'"
                  >
                    <CheckCircle v-if="user.is_active" class="w-5 h-5" />
                    <XCircle v-else class="w-5 h-5" />
                    <span class="text-xs font-bold">{{ user.is_active ? 'Actif' : 'Inactif' }}</span>
                  </button>
                </td>
                <td class="px-8 py-6 text-sm text-gray-500 font-medium">
                  {{ new Date(user.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                </td>
                <td class="px-8 py-6 text-right">
                  <div class="flex items-center justify-end space-x-2">
                    <button @click="openEditModal(user)" class="p-2.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition">
                      <Edit class="w-5 h-5" />
                    </button>
                    <button @click="deleteUser(user.id)" class="p-2.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition">
                      <Trash2 class="w-5 h-5" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!loading && users.length === 0">
                <td colspan="5" class="py-20 text-center">
                  <div class="w-20 h-20 bg-gray-50 rounded-3xl flex items-center justify-center mx-auto mb-4 text-gray-300">
                    <Search class="w-10 h-10" />
                  </div>
                  <p class="text-gray-400 font-bold">Aucun utilisateur trouvé</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="px-8 py-6 bg-gray-50/30 border-t border-gray-50 flex items-center justify-between">
          <p class="text-xs text-gray-500 font-bold uppercase tracking-widest">Page {{ pagination.current_page }} sur {{ pagination.last_page }}</p>
          <div class="flex space-x-2">
            <button 
              @click="fetchUsers(pagination.current_page - 1)" 
              :disabled="pagination.current_page === 1"
              class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-50 disabled:opacity-50 transition"
            >
              Précédent
            </button>
            <button 
              @click="fetchUsers(pagination.current_page + 1)" 
              :disabled="pagination.current_page === pagination.last_page"
              class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-50 disabled:opacity-50 transition"
            >
              Suivant
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Reports View -->
    <div v-if="activeTab === 'reports'" class="space-y-6">
      <div class="bg-white rounded-[40px] border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-50/50">
                <th class="px-8 py-5 text-xs font-black text-gray-400 uppercase tracking-widest">Signaleur</th>
                <th class="px-8 py-5 text-xs font-black text-gray-400 uppercase tracking-widest">Signalé</th>
                <th class="px-8 py-5 text-xs font-black text-gray-400 uppercase tracking-widest">Raison</th>
                <th class="px-8 py-5 text-xs font-black text-gray-400 uppercase tracking-widest">Statut</th>
                <th class="px-8 py-5 text-right text-xs font-black text-gray-400 uppercase tracking-widest">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-if="reportsLoading" v-for="i in 3" :key="i" class="animate-pulse">
                <td colspan="5" class="px-8 py-8"><div class="h-4 bg-gray-100 rounded w-full"></div></td>
              </tr>
              <tr v-else v-for="report in complaints" :key="report.id" class="hover:bg-gray-50/30 transition-colors">
                <td class="px-8 py-6">
                  <p class="text-sm font-bold text-gray-900">{{ report.reporter?.name }}</p>
                  <p class="text-[10px] text-gray-400 font-bold uppercase">{{ report.reporter?.role }}</p>
                </td>
                <td class="px-8 py-6">
                  <p class="text-sm font-bold text-gray-900">{{ report.reported?.name }}</p>
                  <p class="text-[10px] text-gray-400 font-bold uppercase">{{ report.reported?.role }}</p>
                </td>
                <td class="px-8 py-6">
                  <div class="max-w-xs">
                    <p class="text-sm font-medium text-gray-700">{{ report.reason }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ new Date(report.created_at).toLocaleString() }}</p>
                  </div>
                </td>
                <td class="px-8 py-6">
                  <span 
                    class="px-2 py-1 text-[10px] font-black uppercase rounded-lg border shadow-sm"
                    :class="{
                      'bg-orange-50 text-orange-600 border-orange-100': report.status === 'pending',
                      'bg-green-50 text-green-600 border-green-100': report.status === 'resolved',
                      'bg-gray-50 text-gray-500 border-gray-100': report.status === 'ignored',
                    }"
                  >
                    {{ report.status }}
                  </span>
                </td>
                <td class="px-8 py-6 text-right">
                  <div v-if="report.status === 'pending'" class="flex items-center justify-end space-x-2">
                    <button @click="updateComplaintStatus(report, 'resolved')" class="text-xs font-bold text-green-600 hover:bg-green-50 px-3 py-2 rounded-xl border border-green-100 transition">Résoudre</button>
                    <button @click="updateComplaintStatus(report, 'ignored')" class="text-xs font-bold text-gray-400 hover:bg-gray-50 px-3 py-2 rounded-xl border border-gray-100 transition">Ignorer</button>
                  </div>
                </td>
              </tr>
              <tr v-if="!reportsLoading && complaints.length === 0">
                <td colspan="5" class="py-20 text-center">
                  <p class="text-gray-400 font-bold">Aucun signalement trouvé</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      </div>
    </div>
    <div v-if="activeTab === 'roles'" class="space-y-8">
      <div v-if="rolesLoading" class="flex items-center justify-center py-20">
        <Loader2 class="w-10 h-10 animate-spin text-gray-300" />
      </div>
      <div v-else v-for="role in roles" :key="role.id" class="bg-white rounded-[40px] border border-gray-100 shadow-sm overflow-hidden p-8">
        <div class="flex items-center justify-between mb-8">
          <div>
            <h3 class="text-2xl font-black text-gray-900 flex items-center space-x-3">
              <Shield class="w-6 h-6 text-blue-600" />
              <span>{{ role.name }}</span>
            </h3>
            <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mt-1">Slug: {{ role.slug }}</p>
          </div>
          <button 
            @click="updateRolePermissions(role)"
            class="bg-gray-900 text-white px-6 py-3 rounded-xl font-bold hover:bg-black transition text-sm"
          >
            Enregistrer les permissions
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div 
            v-for="permission in allPermissions" 
            :key="permission.id"
            @click="togglePermission(role, permission)"
            class="p-4 rounded-2xl border-2 cursor-pointer transition flex items-center justify-between group"
            :class="hasPermission(role, permission) ? 'bg-blue-50 border-blue-200' : 'bg-gray-50 border-transparent hover:border-gray-200'"
          >
            <div>
              <p class="text-sm font-bold" :class="hasPermission(role, permission) ? 'text-blue-900' : 'text-gray-700'">{{ permission.name }}</p>
              <p class="text-[10px] text-gray-400 font-medium">{{ permission.slug }}</p>
            </div>
            <div 
              class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition"
              :class="hasPermission(role, permission) ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-gray-200'"
            >
              <CheckCircle v-if="hasPermission(role, permission)" class="w-3 h-3" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- User Modal (Create/Edit) -->
    <div v-if="showUserModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showUserModal = false"></div>
      <div class="relative bg-white w-full max-w-lg rounded-[40px] shadow-2xl p-10 animate-in fade-in zoom-in duration-300">
        <div class="flex items-center justify-between mb-10">
          <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-gray-900 rounded-2xl flex items-center justify-center text-white">
              <UserPlus v-if="!isEditing" class="w-6 h-6" />
              <Edit v-else class="w-6 h-6" />
            </div>
            <div>
              <h3 class="text-2xl font-black text-gray-900">{{ isEditing ? 'Modifier' : 'Créer' }} un compte</h3>
              <p class="text-gray-500 text-sm font-medium">Renseignez les informations de l'utilisateur.</p>
            </div>
          </div>
        </div>

        <form @submit.prevent="saveUser" class="space-y-6">
          <div class="space-y-2">
            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest ml-1">Nom complet</label>
            <div class="relative">
              <User class="absolute left-5 top-4 w-5 h-5 text-gray-400" />
              <input v-model="userForm.name" type="text" required class="w-full pl-14 pr-6 py-4 bg-gray-50 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 transition">
            </div>
          </div>

          <div class="space-y-2">
            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest ml-1">Adresse Email</label>
            <div class="relative">
              <Mail class="absolute left-5 top-4 w-5 h-5 text-gray-400" />
              <input v-model="userForm.email" type="email" required class="w-full pl-14 pr-6 py-4 bg-gray-50 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 transition">
            </div>
          </div>

          <div v-if="!isEditing" class="space-y-2">
            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest ml-1">Mot de passe provisoire</label>
            <div class="relative">
              <ShieldCheck class="absolute left-5 top-4 w-5 h-5 text-gray-400" />
              <input v-model="userForm.password" type="password" required class="w-full pl-14 pr-6 py-4 bg-gray-50 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 transition">
            </div>
          </div>

          <div class="space-y-2">
            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest ml-1">Rôle</label>
            <div class="grid grid-cols-3 gap-3">
              <button 
                type="button" 
                v-for="role in roles" 
                :key="role.id"
                @click="userForm.role_id = role.id"
                class="py-3 px-2 rounded-xl text-[10px] font-black uppercase tracking-widest border-2 transition"
                :class="userForm.role_id === role.id ? 'bg-gray-900 border-gray-900 text-white' : 'bg-white border-gray-100 text-gray-400 hover:border-gray-200'"
              >
                {{ role.name }}
              </button>
            </div>
          </div>

          <div class="pt-6 flex gap-4">
            <button type="button" @click="showUserModal = false" class="flex-grow py-4 rounded-2xl font-bold text-gray-500 hover:bg-gray-50 transition">
              Annuler
            </button>
            <button type="submit" :disabled="formLoading" class="flex-grow py-4 rounded-2xl font-bold bg-blue-600 text-white hover:bg-blue-700 shadow-xl shadow-blue-100 transition active:scale-[0.98] flex items-center justify-center">
              <Loader2 v-if="formLoading" class="w-5 h-5 animate-spin mr-2" />
              <span>{{ isEditing ? 'Mettre à jour' : 'Créer le compte' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.divide-y > * + * {
  border-top-width: 1px;
}
</style>
