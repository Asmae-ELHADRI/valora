<script setup>
import { useI18n } from 'vue-i18n';
import { computed, watch } from 'vue';
import { Globe } from 'lucide-vue-next';

const { locale } = useI18n();

const availableLocales = {
  fr: { label: 'Français', dir: 'ltr' },
  ar: { label: 'العربية', dir: 'rtl' },
  darija: { label: 'الدارجة', dir: 'rtl' },
  en: { label: 'English', dir: 'ltr' }
};

const currentLocale = computed(() => availableLocales[locale.value]);

const switchLanguage = (newLocale) => {
  locale.value = newLocale;
  document.documentElement.setAttribute('dir', availableLocales[newLocale].dir);
  document.documentElement.setAttribute('lang', newLocale);
  localStorage.setItem('user-locale', newLocale);
};

// Initialize from local storage on mount (done implicitly if we set logic here)
// Actually better handle init in i18n.js or App.vue, but for now lets sync here
// Simple watcher to persist
watch(locale, (newVal) => {
  document.documentElement.setAttribute('dir', availableLocales[newVal].dir);
  localStorage.setItem('user-locale', newVal);
});
</script>

<template>
  <div class="relative group">
    <button class="nav-icon-btn flex items-center gap-2 px-3">
      <Globe class="w-5 h-5" />
      <span class="hidden sm:inline font-medium text-sm">{{ currentLocale.label }}</span>
    </button>
    
    <!-- Dropdown -->
    <div class="absolute right-0 mt-2 w-40 bg-white rounded-xl shadow-lg border border-gray-100 py-1 transition-all duration-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transform group-hover:translate-y-0 translate-y-2 z-50">
      <button 
        v-for="(config, code) in availableLocales" 
        :key="code"
        @click="switchLanguage(code)"
        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600 transition-colors flex items-center justify-between"
        :class="{'font-bold text-blue-600 bg-blue-50': locale === code}"
      >
        <span>{{ config.label }}</span>
        <span v-if="locale === code" class="w-2 h-2 rounded-full bg-blue-600"></span>
      </button>
    </div>
  </div>
</template>
