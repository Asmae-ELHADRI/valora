import { createI18n } from 'vue-i18n';
import en from './locales/en.json';
import fr from './locales/fr.json';
import ar from './locales/ar.json';
import darija from './locales/darija.json';

const savedLocale = localStorage.getItem('user-locale') || 'fr';

const i18n = createI18n({
  legacy: false, // Use Composition API
  locale: savedLocale, // Default locale
  fallbackLocale: 'en',
  messages: {
    en,
    fr,
    ar,
    darija
  }
});

export default i18n;
