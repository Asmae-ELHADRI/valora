<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h3>{{ editingCategory ? 'Modifier la catégorie' : 'Créer une catégorie' }}</h3>
        <button class="btn-close" @click="$emit('close')">×</button>
      </div>

      <form @submit.prevent="submitForm">
        <div class="form-group">
          <label for="name">Nom de la catégorie *</label>
          <input
            id="name"
            v-model="formData.name"
            type="text"
            placeholder="ex. Ménage, Plomberie, Soutien scolaire"
            class="form-input"
            required
            @blur="validateName"
          />
          <span v-if="errors.name" class="error-text">{{ errors.name }}</span>
        </div>

        <div class="form-group">
          <label for="description">Description</label>
          <textarea
            id="description"
            v-model="formData.description"
            placeholder="Décrivez brièvement cette catégorie..."
            class="form-textarea"
            rows="4"
          />
        </div>

        <div class="form-group">
          <label for="icon">Icône (emoji)</label>
          <input
            id="icon"
            v-model="formData.icon"
            type="text"
            placeholder="ex. 🧹 🔧 📚"
            class="form-input"
            maxlength="2"
          />
          <small>Entrez un emoji unique (1-2 caractères)</small>
          <div v-if="formData.icon" class="icon-preview">
            Aperçu: <span class="preview-icon">{{ formData.icon }}</span>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-secondary" @click="$emit('close')">
            Annuler
          </button>
          <button type="submit" class="btn-primary" :disabled="isSubmitting">
            {{ isSubmitting ? 'Enregistrement...' : 'Enregistrer' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref, watch } from 'vue'

export default {
  name: 'CategoryModal',
  props: {
    category: {
      type: Object,
      default: null
    }
  },
  emits: ['save', 'close'],
  setup(props, { emit }) {
    const formData = ref({
      name: '',
      description: '',
      icon: ''
    })
    const errors = ref({})
    const isSubmitting = ref(false)

    // Initialize form with existing category data
    watch(
      () => props.category,
      category => {
        if (category) {
          formData.value = {
            name: category.name,
            description: category.description || '',
            icon: category.icon || ''
          }
        } else {
          formData.value = {
            name: '',
            description: '',
            icon: ''
          }
        }
        errors.value = {}
      },
      { immediate: true }
    )

    const validateName = () => {
      if (!formData.value.name.trim()) {
        errors.value.name = 'Le nom est requis'
      } else if (formData.value.name.length < 2) {
        errors.value.name = 'Le nom doit contenir au moins 2 caractères'
      } else if (formData.value.name.length > 255) {
        errors.value.name = 'Le nom ne peut pas dépasser 255 caractères'
      } else {
        errors.value.name = ''
      }
    }

    const submitForm = () => {
      errors.value = {}

      // Validate name
      if (!formData.value.name.trim()) {
        errors.value.name = 'Le nom est requis'
      } else if (formData.value.name.length < 2) {
        errors.value.name = 'Le nom doit contenir au moins 2 caractères'
      } else if (formData.value.name.length > 255) {
        errors.value.name = 'Le nom ne peut pas dépasser 255 caractères'
      }

      if (Object.keys(errors.value).length > 0) {
        return
      }

      isSubmitting.value = true
      emit('save', {
        name: formData.value.name.trim(),
        description: formData.value.description.trim() || null,
        icon: formData.value.icon.trim() || null
      })
      setTimeout(() => {
        isSubmitting.value = false
      }, 1000)
    }

    return {
      formData,
      errors,
      isSubmitting,
      validateName,
      submitForm
    }
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #eee;
}

.modal-header h3 {
  margin: 0;
  color: #333;
  font-size: 1.3rem;
}

.btn-close {
  background: none;
  border: none;
  font-size: 2rem;
  cursor: pointer;
  color: #666;
  padding: 0;
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-close:hover {
  color: #333;
}

form {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

label {
  display: block;
  margin-bottom: 0.5rem;
  color: #333;
  font-weight: 500;
  font-size: 0.95rem;
}

.form-input,
.form-textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
  font-family: inherit;
  box-sizing: border-box;
  transition: border-color 0.3s;
}

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: #4caf50;
  box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.1);
}

.form-textarea {
  resize: vertical;
  min-height: 100px;
}

small {
  display: block;
  margin-top: 0.25rem;
  color: #666;
  font-size: 0.85rem;
}

.icon-preview {
  display: block;
  margin-top: 0.75rem;
  padding: 0.75rem;
  background-color: #f5f5f5;
  border-radius: 4px;
  text-align: center;
}

.preview-icon {
  font-size: 2rem;
  margin-left: 0.5rem;
}

.error-text {
  display: block;
  margin-top: 0.25rem;
  color: #f44336;
  font-size: 0.85rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1.5rem;
  border-top: 1px solid #eee;
  background-color: #f9f9f9;
}

.btn-primary,
.btn-secondary {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
  font-weight: 500;
  transition: background-color 0.3s;
}

.btn-primary {
  background-color: #4caf50;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #45a049;
}

.btn-primary:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

.btn-secondary {
  background-color: #f0f0f0;
  color: #333;
}

.btn-secondary:hover {
  background-color: #e0e0e0;
}

@media (max-width: 600px) {
  .modal-content {
    width: 95%;
    max-height: 95vh;
  }

  .modal-header {
    padding: 1rem;
  }

  form {
    padding: 1rem;
  }

  .modal-footer {
    padding: 1rem;
  }

  .btn-primary,
  .btn-secondary {
    flex: 1;
  }
}
</style>
