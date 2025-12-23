<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';
import { getMockImage } from '@/data/mockProducts';

const { t } = useI18n();

import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Skeleton from 'primevue/skeleton';

const toast = useToast();

const loading = ref(true);
const products = ref([]);
const searchTerm = ref('');
const selectedCategory = ref(null);
const stockFilter = ref(null);

const stockOptions = computed(() => [
  { label: t('catalogue.allCategories'), value: null },
  { label: t('catalogue.inStock'), value: 'in_stock' },
  { label: t('catalogue.outOfStock'), value: 'out_of_stock' }
]);

const categories = ref([]);
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8001/api/v1',
  headers: { Accept: 'application/json' }
});

const filteredProducts = computed(() => {
  return products.value.filter((product) => {
    const matchesSearch = product.name.toLowerCase().includes(searchTerm.value.toLowerCase());
    const matchesCategory = selectedCategory.value ? product.category?.id === selectedCategory.value : true;
    const matchesStock = stockFilter.value ? product.stock?.status === stockFilter.value : true;
    return matchesSearch && matchesCategory && matchesStock;
  });
});

const fetchCatalogue = async () => {
  loading.value = true;
  try {
    const [productsResponse, categoriesResponse] = await Promise.all([
      api.get('/products', {
        params: {
          with_variants: true,
          only_active: true,
          per_page: 30
        }
      }),
      api.get('/categories', {
        params: {
          only_active: true,
          per_page: 50,
          only_roots: false
        }
      })
    ]);

    products.value = productsResponse.data.data ?? [];
    categories.value = (categoriesResponse.data.data ?? []).map((cat) => ({
      label: cat.name,
      value: cat.id
    }));
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: t('catalogue.errorSummary'),
      detail: error.response?.data?.message ?? t('catalogue.errorDetail'),
      life: 5000
    });
  } finally {
    loading.value = false;
  }
};

onMounted(fetchCatalogue);
</script>

<template>
  <section class="catalog flex flex-column gap-5">
    <header class="surface-card border-round-xl shadow-1 p-4 flex flex-column gap-4">
      <div class="flex flex-column gap-2">
        <h1 class="text-4xl font-bold mb-4">{{ t('catalogue.title') }}</h1>
        <p class="m-0 text-color-secondary">
          {{ t('catalogue.description') }}
        </p>
      </div>
      <div class="grid gap-3">
        <div class="col-12 md:col-4">
          <span class="p-input-icon-left w-full">
            <i class="pi pi-search" />
            <InputText
              v-model="searchTerm"
              :placeholder="t('catalogue.search') + '...'"
              class="w-full"
            />
          </span>
        </div>
        <div class="col-12 md:col-4">
          <Dropdown
            v-model="selectedCategory"
            :options="categories"
            option-label="label"
            option-value="value"
            :placeholder="t('catalogue.allCategories')"
            class="w-full"
            showClear
          />
        </div>
        <div class="col-12 md:col-4">
          <Dropdown
            v-model="stockFilter"
            :options="stockOptions"
            option-label="label"
            option-value="value"
            :placeholder="t('catalogue.allStock')"
            class="w-full"
            showClear
          />
        </div>
      </div>
    </header>

    <template v-if="loading">
      <div class="grid">
        <div class="col-12 md:col-6 xl:col-4" v-for="n in 6" :key="`skeleton-${n}`">
          <Card class="border-round-xl shadow-1">
            <template #content>
              <Skeleton height="200px" class="mb-3 border-round-lg" />
              <Skeleton width="60%" class="mb-2" />
              <Skeleton width="40%" class="mb-2" />
              <Skeleton height="2rem" width="100%" class="border-round-lg" />
            </template>
          </Card>
        </div>
      </div>
    </template>
    <template v-else>
      <div v-if="filteredProducts.length" class="grid">
        <div
          class="col-12 md:col-6 xl:col-4"
          v-for="(product, index) in filteredProducts"
          :key="product.id"
        >
          <Card class="border-round-2xl shadow-2 h-full flex flex-column catalog-card surface-card">
            <template #header>
              <div class="relative">
                <img
                  :src="product.media?.cover || getMockImage(index)"
                  :alt="product.name"
                  class="product-image"
                />
                <Tag
                  :value="product.type === 'digital' ? t('catalogue.digital') : t('catalogue.physical')"
                  :severity="product.type === 'digital' ? 'info' : 'warning'"
                  rounded
                  class="absolute top-2 left-2 product-type-tag"
                />
              </div>
            </template>
            <template #title>
              <div class="flex align-items-center justify-content-between">
                <h3 class="m-0 text-xl font-semibold">{{ product.name }}</h3>
                <span class="text-lg font-semibold text-primary">
                  {{ product.pricing?.price }} {{ product.pricing?.currency }}
                </span>
              </div>
            </template>
            <template #content>
              <p class="text-color-secondary line-height-3">
                {{ product.meta?.description ?? t('catalogue.noDescription') }}
              </p>
              <div class="flex flex-wrap gap-2">
                <Tag
                  v-for="variant in product.variants ?? []"
                  :key="variant.id"
                  :value="variant.name"
                  severity="secondary"
                  class="surface-100 text-color-secondary"
                />
              </div>
            </template>
            <template #footer>
              <Button
                :label="t('catalogue.viewDetails')"
                icon="pi pi-eye"
                severity="warning"
                class="w-full"
                @click="$router.push({ name: 'product', params: { id: product.id } })"
              />
            </template>
          </Card>
        </div>
      </div>

      <div class="surface-card border-round-xl shadow-1 p-5 text-center flex flex-column gap-3 empty-state" v-else>
        <span class="pi pi-inbox text-3xl text-color-secondary" />
        <h3 class="m-0">Aucun produit ne correspond à votre recherche.</h3>
        <p class="m-0 text-color-secondary">Modifiez vos filtres ou réinitialisez la recherche.</p>
        <Button
          label="Réinitialiser"
          icon="pi pi-refresh"
          text
          @click="() => {
            searchTerm.value = '';
            selectedCategory.value = null;
            stockFilter.value = null;
          }"
        />
      </div>
    </template>
  </section>
</template>

<style scoped>
.catalog {
  padding-bottom: 4rem;
}

.product-image {
  width: 100%;
  height: 220px;
  object-fit: cover;
  border-radius: 1.25rem 1.25rem 0 0;
}

.catalog-card {
  border: 1px solid var(--surface-border);
  background: var(--surface-card);
  transition: transform var(--transition-fast), box-shadow var(--transition-fast);
}

.catalog-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 18px 30px -12px rgba(0, 0, 0, 0.18);
}

.product-type-tag {
  background: rgba(255, 184, 0, 0.16) !important;
  color: #9a6b00 !important;
  border: none !important;
}

.empty-state {
  border: 1px dashed var(--surface-border);
  background: #fffaf1;
}
</style>
