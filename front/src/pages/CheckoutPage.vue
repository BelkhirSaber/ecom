<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';
import { useCartStore } from '@/stores/cart';
import { useAuthStore } from '@/stores/auth';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import RadioButton from 'primevue/radiobutton';

const router = useRouter();
const { t } = useI18n();
const toast = useToast();
const cartStore = useCartStore();
const authStore = useAuthStore();

const loading = ref(false);
const step = ref(1);

const shippingForm = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  address: '',
  city: '',
  postal_code: '',
  country: 'MA'
});

const paymentMethod = ref('cod');
const couponCode = ref('');

const countries = [
  { label: 'Maroc', value: 'MA' },
  { label: 'France', value: 'FR' },
  { label: 'Belgique', value: 'BE' }
];

async function submitOrder() {
  loading.value = true;
  try {
    const response = await authStore.api.post('/orders', {
      shipping_address: shippingForm.value,
      payment_method: paymentMethod.value,
      coupon_code: couponCode.value || null,
      items: cartStore.items.map(item => ({
        product_id: item.product.id,
        variant_id: item.variant?.id,
        quantity: item.quantity
      }))
    });

    toast.add({
      severity: 'success',
      summary: t('checkout.success'),
      detail: t('checkout.orderPlaced'),
      life: 5000
    });

    cartStore.clearCart();
    router.push(`/account/orders/${response.data.data.id}`);
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: t('checkout.error'),
      detail: error.response?.data?.message || t('checkout.failed'),
      life: 4000
    });
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <section class="checkout-page">
    <div class="grid">
      <div class="col-12 lg:col-8">
        <div class="surface-card border-round-xl shadow-1 p-4 md:p-5">
          <h1 class="text-3xl font-bold mb-4">{{ t('checkout.title') }}</h1>

          <form @submit.prevent="submitOrder" class="flex flex-column gap-4">
            <div class="grid">
              <div class="col-12 md:col-6">
                <label class="flex flex-column gap-2">
                  <span class="font-semibold">{{ t('checkout.firstName') }}</span>
                  <InputText v-model="shippingForm.first_name" required />
                </label>
              </div>
              <div class="col-12 md:col-6">
                <label class="flex flex-column gap-2">
                  <span class="font-semibold">{{ t('checkout.lastName') }}</span>
                  <InputText v-model="shippingForm.last_name" required />
                </label>
              </div>
            </div>

            <label class="flex flex-column gap-2">
              <span class="font-semibold">{{ t('checkout.email') }}</span>
              <InputText v-model="shippingForm.email" type="email" required />
            </label>

            <label class="flex flex-column gap-2">
              <span class="font-semibold">{{ t('checkout.phone') }}</span>
              <InputText v-model="shippingForm.phone" required />
            </label>

            <label class="flex flex-column gap-2">
              <span class="font-semibold">{{ t('checkout.address') }}</span>
              <Textarea v-model="shippingForm.address" rows="3" required />
            </label>

            <div class="grid">
              <div class="col-12 md:col-6">
                <label class="flex flex-column gap-2">
                  <span class="font-semibold">{{ t('checkout.city') }}</span>
                  <InputText v-model="shippingForm.city" required />
                </label>
              </div>
              <div class="col-12 md:col-6">
                <label class="flex flex-column gap-2">
                  <span class="font-semibold">{{ t('checkout.postalCode') }}</span>
                  <InputText v-model="shippingForm.postal_code" required />
                </label>
              </div>
            </div>

            <label class="flex flex-column gap-2">
              <span class="font-semibold">{{ t('checkout.country') }}</span>
              <Dropdown v-model="shippingForm.country" :options="countries" optionLabel="label" optionValue="value" />
            </label>

            <div class="border-top-1 surface-border pt-4">
              <h3 class="text-xl font-semibold mb-3">{{ t('checkout.paymentMethod') }}</h3>
              <div class="flex flex-column gap-3">
                <div class="flex align-items-center gap-2">
                  <RadioButton v-model="paymentMethod" inputId="cod" value="cod" />
                  <label for="cod">{{ t('checkout.cashOnDelivery') }}</label>
                </div>
              </div>
            </div>

            <Button 
              type="submit" 
              :label="t('checkout.placeOrder')" 
              icon="pi pi-check" 
              :loading="loading"
              class="p-button-primary"
            />
          </form>
        </div>
      </div>

      <div class="col-12 lg:col-4">
        <div class="surface-card border-round-xl shadow-1 p-4 sticky top-0">
          <h3 class="text-xl font-semibold mb-3">{{ t('checkout.orderSummary') }}</h3>
          
          <div class="flex flex-column gap-3 mb-4">
            <div v-for="item in cartStore.items" :key="item.id" class="flex justify-content-between">
              <span class="text-sm">{{ item.quantity }}× {{ item.product.name }}</span>
              <span class="font-semibold">{{ (item.product.price * item.quantity).toFixed(2) }}</span>
            </div>
          </div>

          <div class="border-top-1 surface-border pt-3">
            <div class="flex justify-content-between mb-2">
              <span>{{ t('cart.subtotal') }}</span>
              <span class="font-semibold">{{ cartStore.totalAmount.toFixed(2) }} USD</span>
            </div>
            <div class="flex justify-content-between text-xl font-bold text-primary">
              <span>{{ t('cart.total') }}</span>
              <span>{{ cartStore.totalAmount.toFixed(2) }} USD</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.checkout-page {
  padding: 2rem 0 4rem;
}
</style>
