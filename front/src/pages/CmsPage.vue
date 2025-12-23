<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';
import { useAuthStore } from '@/stores/auth';
import Skeleton from 'primevue/skeleton';

const route = useRoute();
const router = useRouter();
const { t } = useI18n();
const toast = useToast();
const authStore = useAuthStore();

const page = ref(null);
const loading = ref(true);

async function fetchPage() {
  loading.value = true;
  try {
    const response = await authStore.api.get(`/pages/${route.params.slug}`);
    page.value = response.data.data;
    
    if (page.value.meta_title) {
      document.title = `${page.value.meta_title} · Ecom`;
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: t('cms.error'),
      detail: t('cms.pageNotFound'),
      life: 4000
    });
    router.push('/');
  } finally {
    loading.value = false;
  }
}

onMounted(fetchPage);
</script>

<template>
  <section class="cms-page">
    <div class="surface-card border-round-xl shadow-1 p-4 md:p-6">
      <template v-if="loading">
        <Skeleton width="60%" height="3rem" class="mb-4" />
        <Skeleton width="100%" height="1rem" class="mb-2" />
        <Skeleton width="100%" height="1rem" class="mb-2" />
        <Skeleton width="80%" height="1rem" class="mb-4" />
        <Skeleton width="100%" height="10rem" />
      </template>

      <template v-else-if="page">
        <article>
          <h1 class="text-4xl font-bold mb-4">{{ page.title }}</h1>
          
          <div class="cms-content text-color-secondary line-height-3" v-html="page.content"></div>
        </article>
      </template>
    </div>
  </section>
</template>

<style scoped>
.cms-page {
  padding: 2rem 0 4rem;
}

.cms-content {
  font-size: 1.125rem;
}

.cms-content :deep(h2) {
  font-size: 1.75rem;
  font-weight: 600;
  margin-top: 2rem;
  margin-bottom: 1rem;
}

.cms-content :deep(h3) {
  font-size: 1.5rem;
  font-weight: 600;
  margin-top: 1.5rem;
  margin-bottom: 0.75rem;
}

.cms-content :deep(p) {
  margin-bottom: 1rem;
}

.cms-content :deep(ul),
.cms-content :deep(ol) {
  margin-left: 2rem;
  margin-bottom: 1rem;
}

.cms-content :deep(a) {
  color: var(--primary-700);
  text-decoration: underline;
}

.cms-content :deep(a:hover) {
  color: var(--primary-800);
}
</style>
