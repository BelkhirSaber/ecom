<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Skeleton from 'primevue/skeleton';

const router = useRouter();
const { t } = useI18n();
const authStore = useAuthStore();

const orders = ref([]);
const loading = ref(true);

async function fetchOrders() {
  loading.value = true;
  try {
    const response = await authStore.api.get('/orders');
    orders.value = response.data.data || [];
  } catch (error) {
    console.error('Failed to fetch orders:', error);
  } finally {
    loading.value = false;
  }
}

function getStatusSeverity(status) {
  const severityMap = {
    pending: 'warning',
    processing: 'info',
    shipped: 'info',
    delivered: 'success',
    cancelled: 'danger'
  };
  return severityMap[status] || 'secondary';
}

function handleLogout() {
  authStore.logout();
  router.push('/');
}

onMounted(fetchOrders);
</script>

<template>
  <section class="account-page">
    <div class="surface-card border-round-xl shadow-1 p-4 md:p-5">
      <div class="flex justify-content-between align-items-center mb-4">
        <h1 class="text-3xl font-bold m-0">{{ t('account.title') }}</h1>
        <Button 
          :label="t('account.logout')" 
          icon="pi pi-sign-out" 
          severity="secondary"
          outlined
          @click="handleLogout"
        />
      </div>

      <div class="mb-5">
        <h2 class="text-xl font-semibold mb-2">{{ t('account.welcome') }}</h2>
        <p class="text-color-secondary">{{ authStore.user?.email }}</p>
      </div>

      <div class="border-top-1 surface-border pt-4">
        <h3 class="text-2xl font-semibold mb-4">{{ t('account.myOrders') }}</h3>

        <template v-if="loading">
          <div class="flex flex-column gap-3">
            <Skeleton height="4rem" v-for="i in 3" :key="i" />
          </div>
        </template>

        <template v-else-if="orders.length === 0">
          <div class="text-center py-6">
            <i class="pi pi-shopping-bag text-6xl text-color-secondary mb-3"></i>
            <p class="text-xl text-color-secondary">{{ t('account.noOrders') }}</p>
            <Button 
              :label="t('account.startShopping')" 
              icon="pi pi-arrow-right"
              @click="router.push('/catalogue')"
            />
          </div>
        </template>

        <DataTable v-else :value="orders" responsiveLayout="scroll">
          <Column field="id" :header="t('account.orderId')" style="min-width: 100px">
            <template #body="{ data }">
              <span class="font-mono">#{{ data.id }}</span>
            </template>
          </Column>

          <Column field="created_at" :header="t('account.date')" style="min-width: 150px">
            <template #body="{ data }">
              {{ new Date(data.created_at).toLocaleDateString() }}
            </template>
          </Column>

          <Column field="total_amount" :header="t('account.total')" style="min-width: 120px">
            <template #body="{ data }">
              <span class="font-bold text-price">{{ data.total_amount }} {{ data.currency }}</span>
            </template>
          </Column>

          <Column field="status" :header="t('account.status')" style="min-width: 120px">
            <template #body="{ data }">
              <Tag :value="t(`account.status_${data.status}`)" :severity="getStatusSeverity(data.status)" />
            </template>
          </Column>

          <Column style="min-width: 100px">
            <template #body="{ data }">
              <Button 
                :label="t('account.viewDetails')" 
                icon="pi pi-eye" 
                text
                size="small"
                @click="router.push(`/account/orders/${data.id}`)"
              />
            </template>
          </Column>
        </DataTable>
      </div>
    </div>
  </section>
</template>

<style scoped>
.account-page {
  padding: 2rem 0 4rem;
}
</style>
