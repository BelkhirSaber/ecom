import { createI18n } from 'vue-i18n';
import messages from './messages';

export const LOCALE_STORAGE_KEY = 'eflyer_locale';

const getInitialLocale = () => {
  if (typeof window !== 'undefined') {
    return localStorage.getItem(LOCALE_STORAGE_KEY) ?? 'fr';
  }

  return 'fr';
};

const i18n = createI18n({
  legacy: false,
  globalInjection: true,
  locale: getInitialLocale(),
  fallbackLocale: 'fr',
  messages
});

export default i18n;
