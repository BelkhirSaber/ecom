<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useCartStore } from '@/stores/cart';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputNumber from 'primevue/inputnumber';

const router = useRouter();
const { t } = useI18n();
const cartStore = useCartStore();

const formattedItems = computed(() => {
  return cartStore.items.map(item => ({
    ...item,
    displayName: item.variant 
      ? `${item.product.name} - ${item.variant.name}` 
      : item.product.name,
    unitPrice: item.variant?.price || item.product.price || 0,
    lineTotal: (item.variant?.price || item.product.price || 0) * item.quantity
  }));
});

function updateItemQuantity(item, newQuantity) {
  cartStore.updateQuantity(item.id, newQuantity);
}

function removeItem(item) {
  cartStore.removeItem(item.id);
}

function proceedToCheckout() {
  router.push('/checkout');
}
</script>

<template>
  <section class="cart-page">
    <div class="surface-card border-round-xl shadow-1 p-4 md:p-5">
      <h1 class="text-3xl font-bold mb-4">{{ t('cart.title') }}</h1>

      <template v-if="cartStore.items.length === 0">
        <div class="text-center py-8">
          <i class="pi pi-shopping-cart text-6xl text-color-secondary mb-4"></i>
          <h2 class="text-2xl font-semibold mb-3">{{ t('cart.empty') }}</h2>
          <Button 
            :label="t('cart.continueShopping')" 
            icon="pi pi-arrow-left" 
            @click="router.push('/catalogue')"
          />
        </div>
      </template>

      <template v-else>
        <DataTable :value="formattedItems" responsiveLayout="scroll">
          <Column :header="t('cart.item')" style="min-width: 250px">
            <template #body="{ data }">
              <div class="flex align-items-center gap-3">
                <img 
                  :src="data.product.media?.cover || 'https://via.placeholder.com/80'" 
                  :alt="data.displayName"
                  class="cart-item-image"
                />
                <div>
                  <div class="font-semibold">{{ data.displayName }}</div>
                  <div class="text-sm text-color-secondary">SKU: {{ data.product.sku }}</div>
                </div>
              </div>
            </template>
          </Column>

          <Column :header="t('cart.price')" style="min-width: 120px">
            <template #body="{ data }">
              <span class="text-price">{{ data.unitPrice }} {{ data.product.currency }}</span>
            </template>
          </Column>

          <Column :header="t('cart.quantity')" style="min-width: 150px">
            <template #body="{ data }">
              <InputNumber 
                :modelValue="data.quantity"
                @update:modelValue="(val) => updateItemQuantity(data, val)"
                :min="1"
                :max="99"
                showButtons
                buttonLayout="horizontal"
                size="small"
              />
            </template>
          </Column>

          <Column :header="t('cart.total')" style="min-width: 120px">
            <template #body="{ data }">
              <span class="font-bold text-price">{{ data.lineTotal }} {{ data.product.currency }}</span>
            </template>
          </Column>

          <Column style="min-width: 100px">
            <template #body="{ data }">
              <Button 
                icon="pi pi-trash" 
                severity="danger" 
                text 
                rounded
                @click="removeItem(data)"
              />
            </template>
          </Column>
        </DataTable>

        <div class="flex justify-content-end mt-5">
          <div class="surface-100 border-round-lg p-4" style="min-width: 300px">
            <div class="flex justify-content-between mb-3">
              <span class="font-semibold">{{ t('cart.subtotal') }}:</span>
              <span class="text-xl font-bold text-price">{{ cartStore.totalAmount.toFixed(2) }} USD</span>
            </div>
            <Button 
              :label="t('cart.checkout')" 
              icon="pi pi-arrow-right" 
              iconPos="right"
              class="w-full p-button-primary"
              @click="proceedToCheckout"
            />
          </div>
        </div>
      </template>
    </div>
  </section>
</template>

<style scoped>
.cart-page {
  padding: 2rem 0 4rem;
}

.cart-item-image {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 0.5rem;
}
</style>
