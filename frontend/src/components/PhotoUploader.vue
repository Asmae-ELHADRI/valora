<script setup>
import { ref, onMounted } from 'vue';
import { Camera, Loader2, Upload } from 'lucide-vue-next';
import api from '../services/api';

const props = defineProps({
  currentPhoto: {
    type: String,
    default: null
  }
});

const emit = defineEmits(['photo-updated']);

const photoPreview = ref(null);
const fileInput = ref(null);
const uploading = ref(false);
const error = ref(null);

const triggerFileInput = () => {
  fileInput.value.click();
};

const handleFileChange = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  // Basic validation
  if (!file.type.startsWith('image/')) {
    error.value = 'Le fichier doit être une image';
    return;
  }
  if (file.size > 2 * 1024 * 1024) { // 2MB
    error.value = 'L\'image ne doit pas dépasser 2Mo';
    return;
  }

  // Preview
  const reader = new FileReader();
  reader.onload = (e) => {
    photoPreview.value = e.target.result;
  };
  reader.readAsDataURL(file);

  // Upload
  await uploadPhoto(file);
};

const uploadPhoto = async (file) => {
  uploading.value = true;
  error.value = null;
  
  const formData = new FormData();
  formData.append('photo', file);

  try {
    const response = await api.post('/api/provider/photo', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    // Assuming response contains the new URL
    emit('photo-updated', response.data.photo_url);
    // Force refresh or just use local preview
  } catch (err) {
    console.error(err);
    error.value = 'Erreur lors du téléchargement de la photo';
    // Revert preview if failed
    photoPreview.value = null;
  } finally {
    uploading.value = false;
  }
};
</script>

<template>
  <div class="flex flex-col items-center space-y-4">
    <div class="relative group cursor-pointer" @click="triggerFileInput">
      <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-white shadow-lg ring-2 ring-gray-100 bg-gray-50 flex items-center justify-center">
        <img 
          v-if="photoPreview || currentPhoto" 
          :src="photoPreview || currentPhoto" 
          class="w-full h-full object-cover"
          alt="Profile"
        />
        <div v-else class="text-gray-300">
           <Camera class="w-12 h-12" />
        </div>
        
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black/30 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
          <Upload class="w-8 h-8 text-white" />
        </div>
      </div>
      
      <div v-if="uploading" class="absolute inset-0 flex items-center justify-center bg-white/80 rounded-full">
        <Loader2 class="w-8 h-8 text-blue-600 animate-spin" />
      </div>
    </div>

    <div class="text-center">
      <button 
        type="button" 
        @click="triggerFileInput"
        class="text-sm font-semibold text-blue-600 hover:text-blue-700"
      >
        Modifier la photo
      </button>
      <p class="text-xs text-gray-400 mt-1">JPG, PNG max 2Mo</p>
      <p v-if="error" class="text-xs text-red-500 mt-1">{{ error }}</p>
    </div>

    <input 
      ref="fileInput"
      type="file" 
      accept="image/*" 
      class="hidden" 
      @change="handleFileChange"
    />
  </div>
</template>
