<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';
import { 
    Tag, Plus, Edit, Trash2, Search, Loader2, X, Save 
} from 'lucide-vue-next';

const categories = ref([]);
const loading = ref(false);
const showModal = ref(false);
const isEditing = ref(false);
const saving = ref(false);

const form = ref({
    id: null,
    name: '',
    description: '',
    icon: '',
    image: ''
});

const filter = ref('');

const fetchCategories = async () => {
    loading.value = true;
    try {
        const response = await api.get('/api/admin/categories', {
            params: { search: filter.value }
        });
        categories.value = response.data;
    } catch (err) {
        console.error('Erreur categories', err);
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    isEditing.value = false;
    form.value = { name: '', description: '', icon: '', image: '' };
    showModal.value = true;
};

const openEditModal = (cat) => {
    isEditing.value = true;
    form.value = { ...cat };
    showModal.value = true;
};

const saveCategory = async () => {
    saving.value = true;
    try {
        if (isEditing.value) {
            await api.put(`/api/admin/categories/${form.value.id}`, form.value);
        } else {
            await api.post('/api/admin/categories', form.value);
        }
        showModal.value = false;
        fetchCategories();
    } catch (err) {
        alert(err.response?.data?.message || 'Erreur lors de l\'enregistrement');
    } finally {
        saving.value = false;
    }
};

const deleteCategory = async (id) => {
    if(!confirm('Supprimer cette catégorie ?')) return;
    try {
        await api.delete(`/api/admin/categories/${id}`);
        fetchCategories();
    } catch (err) {
        alert(err.response?.data?.message || 'Erreur lors de la suppression');
    }
};

onMounted(fetchCategories);
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col md:flex-row gap-4 items-center justify-between bg-white p-4 rounded-3xl border border-gray-100 shadow-sm">
            <div class="relative flex-grow max-w-md w-full">
                <Search class="absolute left-5 top-4 w-5 h-5 text-gray-400" />
                <input 
                    v-model="filter"
                    @input="fetchCategories"
                    type="text" 
                    placeholder="Rechercher une catégorie..."
                    class="w-full pl-14 pr-6 py-4 bg-gray-50 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 transition"
                >
            </div>
            <button @click="openCreateModal" class="bg-blue-600 text-white px-6 py-4 rounded-2xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-100 flex items-center space-x-2">
                <Plus class="w-5 h-5" />
                <span>Nouvelle Catégorie</span>
            </button>
        </div>

        <div class="bg-white rounded-[40px] border border-gray-100 shadow-sm overflow-hidden">
             <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-8 py-5 text-xs font-black text-gray-400 uppercase tracking-widest">Nom</th>
                            <th class="px-8 py-5 text-xs font-black text-gray-400 uppercase tracking-widest">Description</th>
                            <th class="px-8 py-5 text-xs font-black text-gray-400 uppercase tracking-widest">Offres liées</th>
                            <th class="px-8 py-5 text-right text-xs font-black text-gray-400 uppercase tracking-widest">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <template v-if="loading">
                            <tr v-for="i in 3" :key="'loading-' + i" class="animate-pulse">
                                <td colspan="4" class="px-8 py-8"><div class="h-4 bg-gray-100 rounded w-full"></div></td>
                            </tr>
                        </template>
                        <template v-else>
                            <tr v-for="cat in categories" :key="cat.id" class="hover:bg-gray-50/30 transition">
                                <td class="px-8 py-6">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-blue-50 rounded-lg overflow-hidden flex items-center justify-center text-blue-600 font-bold border border-gray-100">
                                            <img v-if="cat.image" :src="cat.image" class="w-full h-full object-cover" />
                                            <Tag v-else class="w-4 h-4" />
                                        </div>
                                        <span class="font-bold text-gray-900">{{ cat.name }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-sm text-gray-500 max-w-xs truncate">{{ cat.description }}</td>
                                <td class="px-8 py-6">
                                    <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-bold">{{ cat.service_offers_count }} offres</span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                         <button @click="openEditModal(cat)" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"><Edit class="w-4 h-4" /></button>
                                         <button @click="deleteCategory(cat.id)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"><Trash2 class="w-4 h-4" /></button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
             </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showModal = false"></div>
            <div class="relative bg-white w-full max-w-lg rounded-[40px] shadow-2xl p-10 animate-in fade-in zoom-in duration-300">
                <button @click="showModal = false" class="absolute top-6 right-6 p-2 rounded-2xl bg-gray-100 text-gray-500 hover:bg-red-50 hover:text-red-600 transition">
                    <X class="w-6 h-6" />
                </button>
                
                <h3 class="text-2xl font-black text-gray-900 mb-8">{{ isEditing ? 'Modifier la catégorie' : 'Nouvelle catégorie' }}</h3>
                
                <form @submit.prevent="saveCategory" class="space-y-6">
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Nom</label>
                        <input v-model="form.name" type="text" required class="w-full px-5 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none transition font-medium">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Description</label>
                        <textarea v-model="form.description" rows="3" class="w-full px-5 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none transition font-medium"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Image URL</label>
                        <input v-model="form.image" type="text" placeholder="https://example.com/image.jpg" class="w-full px-5 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none transition font-medium">
                        <div v-if="form.image" class="mt-4 w-full h-32 rounded-2xl overflow-hidden border border-gray-100">
                            <img :src="form.image" class="w-full h-full object-cover">
                        </div>
                    </div>

                    <button 
                        type="submit" 
                        :disabled="saving"
                        class="w-full bg-blue-600 text-white py-4 rounded-2xl font-bold hover:bg-blue-700 shadow-xl shadow-blue-100 transition active:scale-[0.98] flex items-center justify-center space-x-2 disabled:opacity-50"
                    >
                        <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
                        <Save v-else class="w-5 h-5" />
                        <span>{{ isEditing ? 'Mettre à jour' : 'Créer' }}</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
