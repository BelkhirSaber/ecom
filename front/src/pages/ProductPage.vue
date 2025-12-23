<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';
import { useCartStore } from '@/stores/cart';
import { useAuthStore } from '@/stores/auth';
import { getMockImage } from '@/data/mockProducts';
import Button from 'primevue/button';
import Skeleton from 'primevue/skeleton';
import Tag from 'primevue/tag';
import InputNumber from 'primevue/inputnumber';
import Galleria from 'primevue/galleria';

const route = useRoute();
const router = useRouter();
const { t } = useI18n();
const toast = useToast();
const cartStore = useCartStore();
const authStore = useAuthStore();

const product = ref(null);
const loading = ref(true);
const selectedVariant = ref(null);
const quantity = ref(1);

const images = computed(() => {
  const mediaImages = product.value?.media?.images;
  if (mediaImages?.length) {
    return mediaImages.map((img) => ({
      itemImageSrc: img.url,
      thumbnailImageSrc: img.thumbnail || img.url,
      alt: product.value.name
    }));
  }

  const fallbackUrl = product.value?.media?.cover || getMockImage(Number(route.params.id) || 0);
  return [
    {
      itemImageSrc: fallbackUrl,
      thumbnailImageSrc: fallbackUrl,
      alt: product.value?.name || 'Product image'
    }
  ];
});

const currentPrice = computed(() => {
  if (selectedVariant.value?.price) {
    return selectedVariant.value.price;
  }
  return product.value?.price || 0;
});

const stockStatus = computed(() => {
  if (selectedVariant.value) {
    return selectedVariant.value.stock_status || 'out_of_stock';
  }
  return product.value?.stock_status || 'out_of_stock';
});

const isInStock = computed(() => stockStatus.value === 'in_stock');

async function fetchProduct() {
  loading.value = true;
  try {
    const response = await authStore.api.get(`/products/${route.params.id}`, {
      params: { with_variants: true }
    });
    product.value = response.data.data;
    
    if (product.value.variants?.length > 0) {
      selectedVariant.value = product.value.variants[0];
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: t('product.error'),
      detail: t('product.notFound'),
      life: 4000
    });
    router.push('/catalogue');
  } finally {
    loading.value = false;
  }
}

function addToCart() {
  if (!isInStock.value) {
    toast.add({
      severity: 'warn',
      summary: t('product.outOfStock'),
      detail: t('product.cannotAdd'),
      life: 3000
    });
    return;
  }

  cartStore.addItem(product.value, selectedVariant.value, quantity.value);
  
  toast.add({
    severity: 'success',
    summary: t('product.addedToCart'),
    detail: `${quantity.value} × ${product.value.name}`,
    life: 3000
  });

  quantity.value = 1;
}

onMounted(fetchProduct);
</script>

<template>
  <section class="product-page">
    <template v-if="loading">
      <div class="grid">
        <div class="col-12 md:col-6">
          <Skeleton height="400px" class="border-round-xl" />
        </div>
        <div class="col-12 md:col-6">
          <Skeleton width="60%" height="2rem" class="mb-3" />
          <Skeleton width="40%" height="1.5rem" class="mb-4" />
          <Skeleton width="100%" height="8rem" class="mb-4" />
          <Skeleton width="30%" height="3rem" />
        </div>
      </div>
    </template>

    <template v-else-if="product">
      <div class="grid">
        <div class="col-12 md:col-6">
          <Galleria 
            v-if="images.length > 0"
            :value="images" 
            :numVisible="5"
            containerStyle="max-width: 100%"
            :showThumbnails="images.length > 1"
            :showIndicators="images.length > 1"
          >
            <template #item="slotProps">
              <img :src="slotProps.item.itemImageSrc" :alt="slotProps.item.alt" class="product-main-image" />
            </template>
            <template #thumbnail="slotProps">
              <img :src="slotProps.item.thumbnailImageSrc" :alt="slotProps.item.alt" class="product-thumb-image" />
            </template>
          </Galleria>
          <div v-else class="product-placeholder-image">
            <i class="pi pi-image text-6xl text-color-secondary"></i>
          </div>
        </div>

        <div class="col-12 md:col-6">
          <div class="flex flex-column gap-4">
            <div>
              <h1 class="text-4xl font-bold m-0 mb-2">{{ product.name }}</h1>
              <p class="text-color-secondary text-lg m-0">{{ product.short_description }}</p>
            </div>

            <div class="flex align-items-center gap-3">
              <span class="product-card-price text-4xl">{{ currentPrice }} {{ product.currency }}</span>
              <Tag v-if="!isInStock" :value="t('product.outOfStock')" severity="danger" />
              <Tag v-else-if="stockStatus === 'preorder'" :value="t('product.preorder')" severity="warning" />
              <Tag v-else :value="t('product.inStock')" severity="success" />
            </div>

            <div v-if="product.variants?.length > 0" class="flex flex-column gap-2">
              <label class="font-semibold">{{ t('product.selectVariant') }}</label>
              <div class="flex flex-wrap gap-2">
                <Button
                  v-for="variant in product.variants"
                  :key="variant.id"
                  :label="variant.name"
                  :outlined="selectedVariant?.id !== variant.id"
                  @click="selectedVariant = variant"
                  size="small"
                />
              </div>
            </div>

            <div class="flex align-items-center gap-3">
              <label class="font-semibold">{{ t('product.quantity') }}</label>
              <InputNumber v-model="quantity" :min="1" :max="99" showButtons buttonLayout="horizontal" />
            </div>

            <div class="flex gap-2">
              <Button 
                :label="t('product.addToCart')" 
                icon="pi pi-shopping-cart" 
                class="flex-1"
                :disabled="!isInStock"
                @click="addToCart"
              />
              <Button icon="pi pi-heart" outlined severity="secondary" />
            </div>

            <div v-if="product.description" class="border-top-1 surface-border pt-4">
              <h3 class="text-xl font-semibold mb-3">{{ t('product.description') }}</h3>
              <div class="text-color-secondary line-height-3" v-html="product.description"></div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </section>
</template>

<style scoped>
.product-page {
  padding: 2rem 0 4rem;
}

.product-main-image {
  width: 100%;
  height: auto;
  max-height: 500px;
  object-fit: contain;
  border-radius: 1.5rem;
}

.product-thumb-image {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 0.5rem;
  cursor: pointer;
}

.product-placeholder-image {
  width: 100%;
  height: 400px;
  background: var(--surface-100);
  border-radius: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
