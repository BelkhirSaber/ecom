<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter, RouterLink, RouterView } from 'vue-router';
import { useI18n } from 'vue-i18n';
import ScrollTop from 'primevue/scrolltop';
import Toast from 'primevue/toast';
import { LOCALE_STORAGE_KEY } from './i18n';
import { useAuthStore } from '@/stores/auth';
import { useCartStore } from '@/stores/cart';
import { initPixels } from '@/plugins/pixels';

const router = useRouter();
const route = useRoute();

const { t, locale } = useI18n();

const languages = [
  { code: 'fr', label: 'FR', native: 'Français', dir: 'ltr' },
  { code: 'en', label: 'EN', native: 'English', dir: 'ltr' },
  { code: 'ar', label: 'AR', native: 'العربية', dir: 'rtl' }
];

const selectedCategory = ref('all');
const selectedLanguage = ref(languages.find((l) => l.code === locale.value) ?? languages[0]);
const searchQuery = ref('');
const marketingConsent = ref(typeof window !== 'undefined' ? localStorage.getItem('marketing_consent') || 'pending' : 'pending');

const authStore = useAuthStore();
const cartStore = useCartStore();

const isLoginRoute = computed(() => route.name === 'login');
const isAuthenticated = computed(() => authStore.isAuthenticated);
const cartCount = computed(() => cartStore.itemCount);
const showConsentBanner = computed(() => marketingConsent.value === 'pending');

const utilityLinks = computed(() => t('nav.utilityLinks'));
const utilityNavLinks = computed(() => [
  { label: utilityLinks.value?.[0] ?? 'Best Sellers', to: { name: 'catalogue', query: { sort: 'top' } } },
  { label: utilityLinks.value?.[1] ?? 'Gift Ideas', to: { name: 'catalogue', query: { tag: 'gift' } } },
  { label: utilityLinks.value?.[2] ?? 'New Releases', to: { name: 'catalogue', query: { sort: 'new' } } },
  { label: utilityLinks.value?.[3] ?? 'Today’s Deals', to: { name: 'catalogue', query: { promotion: 'daily' } } },
  { label: utilityLinks.value?.[4] ?? 'Customer Service', to: { name: 'cms-page', params: { slug: 'customer-service' } } }
]);
const categoryOptions = computed(() => t('nav.categoryOptions'));
const authActionLabel = computed(() => {
  if (isAuthenticated.value) {
    return t('nav.actions.account');
  }
  if (isLoginRoute.value) {
    return t('nav.actions.backToShop');
  }
  return t('nav.actions.signIn');
});

const goToAuth = () => {
  if (isAuthenticated.value) {
    router.push({ name: 'account' });
    return;
  }
  if (isLoginRoute.value) {
    router.push({ name: 'home' });
  } else {
    router.push({ name: 'login' });
  }
};

const goToCart = () => router.push({ name: 'cart' });

const submitSearch = () => {
  if (!searchQuery.value.trim()) return;
  router.push({ name: 'catalogue', query: { q: searchQuery.value, category: selectedCategory.value } });
};

const acceptMarketing = () => {
  marketingConsent.value = 'granted';
};

const declineMarketing = () => {
  marketingConsent.value = 'declined';
};

const handleLogout = async () => {
  await authStore.logout();
  cartStore.clearCart();
  router.push({ name: 'home' });
};

watch(
  () => locale.value,
  (current) => {
    if (typeof window !== 'undefined') {
      localStorage.setItem(LOCALE_STORAGE_KEY, current);
      document.documentElement.setAttribute('dir', current === 'ar' ? 'rtl' : 'ltr');
      document.documentElement.setAttribute('lang', current);
    }

    const langMatch = languages.find((lang) => lang.code === current);
    if (langMatch && selectedLanguage.value.code !== langMatch.code) {
      selectedLanguage.value = langMatch;
    }
  },
  { immediate: true }
);

watch(
  selectedLanguage,
  (lang, prev) => {
    if (lang?.code && lang.code !== prev?.code) {
      locale.value = lang.code;
    }
  },
  { immediate: true }
);

watch(
  marketingConsent,
  (value, previous) => {
    if (typeof window !== 'undefined' && value !== previous) {
      localStorage.setItem('marketing_consent', value);
    }
    if (value === 'granted') {
      initPixels();
    }
  },
  { immediate: true }
);

watch(
  () => authStore.isAuthenticated,
  (value) => {
    if (value) {
      cartStore.syncWithBackend();
    }
  }
);

onMounted(async () => {
  if (authStore.token && !authStore.user) {
    try {
      await authStore.fetchUser();
      await cartStore.syncWithBackend();
    } catch (error) {
      console.warn('Failed to fetch authenticated user', error?.response?.data ?? error);
    }
  }

  if (marketingConsent.value === 'granted') {
    initPixels();
  }
});
</script>

<template>
  <div class="app-shell">
    <Toast position="top-right" />
    <ScrollTop target="window" :threshold="400" icon="pi pi-chevron-up" />

    <header class="app-header">
      <div class="utility-bar">
        <div class="utility-links">
          <RouterLink
            v-for="link in utilityNavLinks"
            :key="link.label"
            :to="link.to"
            class="utility-link"
          >
            <span>{{ link.label }}</span>
          </RouterLink>
        </div>
        <div class="utility-actions">
          <button class="text-link" @click="router.push({ name: 'catalogue' })">{{ t('nav.actions.wishlist') }}</button>
          <span aria-hidden="true">|</span>
          <button class="text-link" @click="goToCart">
            {{ t('nav.actions.cart') }}
            <span v-if="cartCount > 0" class="cart-count-badge">{{ cartCount }}</span>
          </button>
          <span aria-hidden="true">|</span>
          <template v-if="isAuthenticated">
            <button class="text-link" @click="goToAuth">{{ t('nav.actions.account') }}</button>
            <span aria-hidden="true">|</span>
            <button class="text-link" @click="handleLogout">{{ t('nav.actions.logout') }}</button>
          </template>
          <button v-else class="text-link" @click="goToAuth">
            {{ authActionLabel }}
          </button>
        </div>
      </div>

      <div class="main-nav">
        <div class="brand">
          <button class="icon-button" aria-label="Open navigation">
            <span class="pi pi-bars" />
          </button>
          <RouterLink to="/" class="logo">Eflyer</RouterLink>
        </div>
        <div class="search-stack">
          <select v-model="selectedCategory" class="category-select" aria-label="Select category">
            <option value="all">
              {{ t('nav.categoryPlaceholder') }}
            </option>
            <option
              v-for="category in categoryOptions"
              :key="category"
              :value="category"
            >
              {{ category }}
            </option>
          </select>
          <form class="search-field" @submit.prevent="submitSearch">
            <input
              v-model="searchQuery"
              type="search"
              :placeholder="t('nav.searchPlaceholder')"
              aria-label="Search products"
            />
            <button type="submit">
              <span class="pi pi-search" />
            </button>
          </form>
        </div>
        <div class="main-actions">
          <div class="lang-picker">
            <span class="pi pi-globe" aria-hidden="true" />
            <select v-model="selectedLanguage" aria-label="Language selector">
              <option
                v-for="lang in languages"
                :key="lang.code"
                :value="lang"
              >
                {{ lang.native }}
              </option>
            </select>
          </div>
          <button class="pill-button" @click="goToCart">
            <span class="pi pi-shopping-cart" aria-hidden="true" />
            {{ t('nav.actions.cart') }}
            <span v-if="cartCount > 0" class="cart-badge">{{ cartCount }}</span>
          </button>
        </div>
      </div>
    </header>

    <transition name="slide-up">
      <div v-if="showConsentBanner" class="marketing-banner" role="dialog" aria-live="polite">
        <div class="banner-content">
          <h2>{{ t('marketing.consent.title') }}</h2>
          <p>{{ t('marketing.consent.description') }}</p>
        </div>
        <div class="banner-actions">
          <button class="banner-button primary" @click="acceptMarketing">
            {{ t('marketing.consent.accept') }}
          </button>
          <button class="banner-button" @click="declineMarketing">
            {{ t('marketing.consent.decline') }}
          </button>
        </div>
      </div>
    </transition>

    <main class="app-main">
      <RouterView v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </RouterView>
    </main>

    <footer class="app-footer">
      <div class="footer-content">
        <p v-html="t('footer.text', { year: new Date().getFullYear() })" />
        <div class="footer-links">
          <RouterLink to="/catalogue">{{ t('footer.links.catalogue') }}</RouterLink>
          <RouterLink to="/login">{{ t('footer.links.client') }}</RouterLink>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
:global(body) {
  background: #fdfaf5;
}

.app-shell {
  min-height: 100vh;
  display: grid;
  grid-template-rows: auto 1fr auto;
  background: linear-gradient(180deg, #fff0d6 0%, #ffffff 160px);
}

.app-header {
  position: sticky;
  top: 0;
  z-index: 20;
  background: transparent;
  box-shadow: 0 8px 30px rgba(29, 29, 29, 0.06);
}

.utility-bar {
  background: #111111;
  color: #fef3d7;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.25rem 4vw;
  gap: 1rem;
  font-size: 0.85rem;
}

.utility-links {
  display: flex;
  flex-wrap: wrap;
  gap: 1.5rem;
}

.utility-link {
  position: relative;
  text-decoration: none;
  color: inherit;
  font: inherit;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  font-weight: 600;
  padding-bottom: 0.25rem;
  transition: color var(--transition-base);
}

.utility-link::after {
  content: '';
  position: absolute;
  left: 0;
  bottom: 0;
  width: 100%;
  height: 2px;
  background: linear-gradient(90deg, #ff8f1f, #ffb400);
  transform: scaleX(0);
  transform-origin: left;
  transition: transform var(--transition-base);
}

.utility-link:hover,
.utility-link:focus-visible {
  color: #ffb400;
}

.utility-link:hover::after,
.utility-link:focus-visible::after,
.utility-link.router-link-active::after {
  transform: scaleX(1);
}

.utility-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.text-link {
  background: none;
  border: none;
  color: inherit;
  cursor: pointer;
  font: inherit;
}

.cart-count-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.25rem;
  height: 1.25rem;
  margin-left: 0.5rem;
  padding: 0 0.35rem;
  border-radius: 999px;
  background: #ffb400;
  color: #111;
  font-size: 0.75rem;
  font-weight: 700;
}

.main-nav {
  background: #fff5e3;
  padding: 1rem 4vw;
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 1.5rem;
  align-items: center;
}

.brand {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.logo {
  font-size: clamp(1.5rem, 2vw, 2rem);
  font-weight: 700;
  color: #ff9b05;
  text-decoration: none;
  letter-spacing: 0.28em;
}

.icon-button {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  border: none;
  background: #ffb400;
  color: #111;
  cursor: pointer;
  display: grid;
  place-items: center;
  box-shadow: 0 10px 20px rgba(255, 180, 0, 0.35);
}

.search-stack {
  display: grid;
  grid-template-columns: 180px 1fr;
  background: #fff;
  border-radius: 999px;
  overflow: hidden;
  box-shadow: 0 20px 30px rgba(17, 17, 17, 0.08);
}

.category-select {
  border: none;
  padding: 0 1.5rem;
  background: linear-gradient(135deg, rgba(17, 17, 17, 0.9), rgba(17, 17, 17, 0.8));
  color: #fff;
  font-weight: 600;
  letter-spacing: 0.04em;
  appearance: none;
  border-right: 1px solid rgba(255, 255, 255, 0.1);
}

.search-field {
  display: grid;
  grid-template-columns: 1fr auto;
  align-items: center;
  background: linear-gradient(135deg, #ffffff, #fff7eb);
}

.search-field input {
  border: none;
  padding: 0.95rem 1.25rem;
  font-size: 1rem;
  outline: none;
  color: #6c5a3d;
  font-weight: 500;
}

.search-field input::placeholder {
  color: #b19570;
}

.search-field button {
  border: none;
  background: #ff7a18;
  color: #fff;
  height: 100%;
  width: 56px;
  cursor: pointer;
  display: grid;
  place-items: center;
}

.main-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.lang-picker {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.65rem 1rem;
  border-radius: 999px;
  background: #fff;
  box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.08);
}

.lang-picker select {
  border: none;
  background: transparent;
  font-weight: 600;
  cursor: pointer;
  color: #3b2b1a;
}

.pill-button {
  border: none;
  background: #111;
  color: #fff;
  border-radius: 999px;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.35rem;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.cart-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.25rem;
  height: 1.25rem;
  margin-left: 0.5rem;
  padding: 0 0.35rem;
  border-radius: 999px;
  background: #ffb400;
  color: #111;
  font-size: 0.75rem;
  font-weight: 700;
}

.pill-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 15px 25px rgba(0, 0, 0, 0.2);
}

.app-main {
  padding: 2rem 4vw 4rem;
}

.app-footer {
  background: #0e0e0e;
  color: #f7f2e9;
  padding: 2rem 4vw;
}

.footer-content {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 1rem;
  font-size: 0.9rem;
}

.footer-links {
  display: flex;
  gap: 1.25rem;
}

.footer-links a {
  color: inherit;
  text-decoration: none;
  font-weight: 600;
}

.marketing-banner {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  left: 1.5rem;
  display: flex;
  flex-wrap: wrap;
  gap: 1.5rem;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem 2rem;
  border-radius: 20px;
  background: rgba(17, 17, 17, 0.94);
  color: #fff1d6;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
  z-index: 30;
}

.banner-content h2 {
  margin: 0 0 0.5rem;
  font-size: 1.25rem;
  font-weight: 700;
}

.banner-content p {
  margin: 0;
  max-width: 32rem;
  font-size: 0.95rem;
  line-height: 1.6;
}

.banner-actions {
  display: flex;
  gap: 0.75rem;
}

.banner-button {
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 999px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s ease, opacity 0.2s ease;
}

.banner-button.primary {
  background: linear-gradient(90deg, #ff8f1f, #ffb400);
  color: #1b0f00;
}

.banner-button:not(.primary) {
  background: rgba(255, 255, 255, 0.15);
  color: #fff1d6;
}

.banner-button:hover {
  transform: translateY(-2px);
  opacity: 0.9;
}

:global(.p-button) {
  background: linear-gradient(90deg, #ff8f1f, #ffb400);
  border: none;
  color: #1b0f00;
  font-weight: 700;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

:global(.p-button:hover) {
  transform: translateY(-2px);
  box-shadow: 0 12px 24px rgba(255, 143, 31, 0.35);
}

:global(.p-button:focus-visible) {
  box-shadow: 0 0 0 3px rgba(255, 143, 31, 0.35);
  outline: none;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.25s ease, opacity 0.25s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(20px);
  opacity: 0;
}

@media (max-width: 960px) {
  .utility-bar {
    flex-direction: column;
    align-items: flex-start;
  }

  .main-nav {
    grid-template-columns: 1fr;
  }

  .search-stack {
    grid-template-columns: 1fr;
    border-radius: 20px;
  }

  .category-select {
    border-radius: 20px 20px 0 0;
  }
}
</style>
