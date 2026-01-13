<template>
  <div class="category-management">
    <div class="category-header">
      <h2>Gestion des Catégories de Services</h2>
      <button class="btn-primary" @click="openCreateModal">
        + Nouvelle Catégorie
      </button>
    </div>

    <!-- Search and Filter -->
    <div class="category-filters">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Rechercher une catégorie..."
        class="search-input"
      />
      <select v-model="sortBy" class="sort-select">
        <option value="alphabetical">Ordre alphabétique</option>
        <option value="popular">Popularité (nombre d'offres)</option>
      </select>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="loading">
      Chargement des catégories...
    </div>

    <!-- Categories Table -->
    <div v-else-if="filteredCategories.length > 0" class="categories-table">
      <table>
        <thead>
          <tr>
            <th>Nom</th>
            <th>Description</th>
            <th>Icône</th>
            <th>Offres</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="category in filteredCategories" :key="category.id">
            <td class="category-name">
              <span v-if="category.icon" class="icon">{{ category.icon }}</span>
              {{ category.name }}
            </td>
            <td class="category-description">
              {{ category.description || '-' }}
            </td>
            <td class="category-icon">
              {{ category.icon || '-' }}
            </td>
            <td class="offer-count">
              {{ category.service_offers_count || 0 }}
            </td>
            <td class="actions">
              <button
                class="btn-edit"
                @click="openEditModal(category)"
                title="Modifier"
              >
                ✏️
              </button>
              <button
                class="btn-delete"
                @click="confirmDelete(category)"
                :disabled="category.service_offers_count > 0"
                :title="
                  category.service_offers_count > 0
                    ? 'Impossible de supprimer (offres existantes)'
                    : 'Supprimer'
                "
              >
                🗑️
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <p>Aucune catégorie trouvée. Créez votre première catégorie!</p>
    </div>

    <!-- Error Message -->
    <div v-if="errorMessage" class="alert-error">
      {{ errorMessage }}
    </div>

    <!-- Success Message -->
    <div v-if="successMessage" class="alert-success">
      {{ successMessage }}
    </div>

    <!-- Create/Edit Modal -->
    <CategoryModal
      v-if="showModal"
      :category="editingCategory"
      @save="saveCategory"
      @close="closeModal"
    />

    <!-- Delete Confirmation Modal -->
    <DeleteConfirmModal
      v-if="showDeleteConfirm"
      :item-name="categoryToDelete.name"
      @confirm="deleteCategory"
      @cancel="cancelDelete"
    />
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import categoryService from '../services/categoryService'
import CategoryModal from './CategoryModal.vue'
import DeleteConfirmModal from './DeleteConfirmModal.vue'

export default {
  name: 'CategoryManagement',
  components: {
    CategoryModal,
    DeleteConfirmModal
  },
  setup() {
    const categories = ref([])
    const searchQuery = ref('')
    const sortBy = ref('alphabetical')
    const isLoading = ref(false)
    const showModal = ref(false)
    const editingCategory = ref(null)
    const showDeleteConfirm = ref(false)
    const categoryToDelete = ref(null)
    const errorMessage = ref('')
    const successMessage = ref('')

    // Filtered categories based on search and sort
    const filteredCategories = computed(() => {
      let filtered = [...categories.value]

      // Apply search filter
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(
          cat =>
            cat.name.toLowerCase().includes(query) ||
            (cat.description && cat.description.toLowerCase().includes(query))
        )
      }

      // Apply sorting
      if (sortBy.value === 'popular') {
        filtered.sort(
          (a, b) => (b.service_offers_count || 0) - (a.service_offers_count || 0)
        )
      } else {
        filtered.sort((a, b) => a.name.localeCompare(b.name))
      }

      return filtered
    })

    // Fetch all categories
    const fetchCategories = async () => {
      isLoading.value = true
      errorMessage.value = ''
      try {
        const response = await categoryService.getCategories({
          search: searchQuery.value,
          sort: sortBy.value
        })
        categories.value = response.data
      } catch (error) {
        errorMessage.value =
          error.response?.data?.message ||
          'Erreur lors du chargement des catégories'
        console.error('Erreur:', error)
      } finally {
        isLoading.value = false
      }
    }

    // Open create modal
    const openCreateModal = () => {
      editingCategory.value = null
      showModal.value = true
    }

    // Open edit modal
    const openEditModal = category => {
      editingCategory.value = { ...category }
      showModal.value = true
    }

    // Save category (create or update)
    const saveCategory = async categoryData => {
      try {
        if (editingCategory.value && editingCategory.value.id) {
          // Update existing category
          const response = await categoryService.updateCategory(
            editingCategory.value.id,
            categoryData
          )
          successMessage.value = response.data.message
          // Update in local array
          const index = categories.value.findIndex(
            c => c.id === editingCategory.value.id
          )
          if (index !== -1) {
            categories.value[index] = response.data.category
          }
        } else {
          // Create new category
          const response = await categoryService.createCategory(categoryData)
          successMessage.value = response.data.message
          categories.value.push(response.data.category)
        }
        closeModal()
        setTimeout(() => {
          successMessage.value = ''
        }, 3000)
      } catch (error) {
        errorMessage.value =
          error.response?.data?.message ||
          'Erreur lors de la sauvegarde de la catégorie'
        console.error('Erreur:', error)
      }
    }

    // Close modal
    const closeModal = () => {
      showModal.value = false
      editingCategory.value = null
    }

    // Confirm delete
    const confirmDelete = category => {
      categoryToDelete.value = category
      showDeleteConfirm.value = true
    }

    // Delete category
    const deleteCategory = async () => {
      try {
        const response = await categoryService.deleteCategory(
          categoryToDelete.value.id
        )
        successMessage.value = response.data.message
        categories.value = categories.value.filter(
          c => c.id !== categoryToDelete.value.id
        )
        cancelDelete()
        setTimeout(() => {
          successMessage.value = ''
        }, 3000)
      } catch (error) {
        errorMessage.value =
          error.response?.data?.message ||
          'Erreur lors de la suppression de la catégorie'
        console.error('Erreur:', error)
        cancelDelete()
      }
    }

    // Cancel delete
    const cancelDelete = () => {
      showDeleteConfirm.value = false
      categoryToDelete.value = null
    }

    // Load categories on mount
    onMounted(() => {
      fetchCategories()
    })

    return {
      categories,
      filteredCategories,
      searchQuery,
      sortBy,
      isLoading,
      showModal,
      editingCategory,
      showDeleteConfirm,
      categoryToDelete,
      errorMessage,
      successMessage,
      openCreateModal,
      openEditModal,
      saveCategory,
      closeModal,
      confirmDelete,
      deleteCategory,
      cancelDelete,
      fetchCategories
    }
  }
}
</script>

<style scoped>
.category-management {
  padding: 2rem;
  background-color: #f5f5f5;
  min-height: 100vh;
}

.category-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.category-header h2 {
  margin: 0;
  color: #333;
  font-size: 1.8rem;
}

.btn-primary {
  padding: 0.75rem 1.5rem;
  background-color: #4caf50;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
  font-weight: 500;
  transition: background-color 0.3s;
}

.btn-primary:hover {
  background-color: #45a049;
}

.category-filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}

.search-input,
.sort-select {
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.search-input {
  flex: 1;
  min-width: 250px;
}

.sort-select {
  min-width: 200px;
}

.loading {
  text-align: center;
  padding: 2rem;
  color: #666;
  font-size: 1.1rem;
}

.categories-table {
  background-color: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

table {
  width: 100%;
  border-collapse: collapse;
}

th {
  background-color: #f9f9f9;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #333;
  border-bottom: 2px solid #ddd;
}

td {
  padding: 1rem;
  border-bottom: 1px solid #eee;
}

tr:hover {
  background-color: #f5f5f5;
}

.category-name {
  font-weight: 500;
  color: #333;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.icon {
  font-size: 1.5rem;
}

.category-description {
  color: #666;
  max-width: 300px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.category-icon {
  text-align: center;
  font-size: 1.5rem;
}

.offer-count {
  text-align: center;
  font-weight: 500;
  color: #666;
}

.actions {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
}

.btn-edit,
.btn-delete {
  padding: 0.5rem 0.75rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
  transition: background-color 0.3s;
}

.btn-edit {
  background-color: #2196f3;
  color: white;
}

.btn-edit:hover {
  background-color: #0b7dda;
}

.btn-delete {
  background-color: #f44336;
  color: white;
}

.btn-delete:hover:not(:disabled) {
  background-color: #da190b;
}

.btn-delete:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

.empty-state {
  background-color: white;
  border-radius: 8px;
  padding: 3rem;
  text-align: center;
  color: #666;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.alert-error {
  margin-top: 1rem;
  padding: 1rem;
  background-color: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
  border-radius: 4px;
}

.alert-success {
  margin-top: 1rem;
  padding: 1rem;
  background-color: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
  border-radius: 4px;
}

@media (max-width: 768px) {
  .category-header {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }

  .btn-primary {
    width: 100%;
  }

  .category-filters {
    flex-direction: column;
  }

  .search-input,
  .sort-select {
    width: 100%;
  }

  table {
    font-size: 0.9rem;
  }

  td,
  th {
    padding: 0.75rem;
  }

  .category-description {
    max-width: 150px;
  }

  .actions {
    flex-direction: column;
    gap: 0.25rem;
  }
}
</style>
