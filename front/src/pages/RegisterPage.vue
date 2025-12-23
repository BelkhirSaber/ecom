<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useAuthStore } from '@/stores/auth';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';

const { t } = useI18n();
const router = useRouter();
const toast = useToast();
const authStore = useAuthStore();

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
});

const loading = ref(false);

const submit = async () => {
  loading.value = true;
  try {
    await authStore.register(form.value);
    toast.add({ 
      severity: 'success', 
      summary: t('auth.register.success'), 
      detail: t('auth.register.welcome'), 
      life: 3000 
    });
    router.push('/');
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: t('auth.register.error'),
      detail: error.response?.data?.message || t('auth.register.failed'),
      life: 4000
    });
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <section class="register grid justify-content-center">
    <div class="col-12 md:col-6 xl:col-4">
      <div class="surface-card border-round-2xl shadow-2 p-4 md:p-5 flex flex-column gap-4">
        <div class="flex flex-column gap-2 text-center">
          <span class="pi pi-user-plus text-3xl text-primary-500" />
          <h1 class="m-0 text-2xl font-semibold">{{ t('auth.register.title') }}</h1>
          <p class="m-0 text-color-secondary">{{ t('auth.register.subtitle') }}</p>
        </div>

        <form class="flex flex-column gap-3" @submit.prevent="submit">
          <label class="flex flex-column gap-2">
            <span class="font-medium">{{ t('auth.register.name') }}</span>
            <InputText v-model="form.name" :placeholder="t('auth.register.namePlaceholder')" required autofocus />
          </label>

          <label class="flex flex-column gap-2">
            <span class="font-medium">{{ t('auth.register.email') }}</span>
            <InputText v-model="form.email" type="email" :placeholder="t('auth.register.emailPlaceholder')" required />
          </label>

          <label class="flex flex-column gap-2">
            <span class="font-medium">{{ t('auth.register.password') }}</span>
            <Password
              v-model="form.password"
              toggleMask
              :input-props="{ placeholder: t('auth.register.passwordPlaceholder') }"
              required
            />
          </label>

          <label class="flex flex-column gap-2">
            <span class="font-medium">{{ t('auth.register.confirmPassword') }}</span>
            <Password
              v-model="form.password_confirmation"
              toggleMask
              :feedback="false"
              :input-props="{ placeholder: t('auth.register.passwordPlaceholder') }"
              required
            />
          </label>

          <Button :label="t('auth.register.submit')" icon="pi pi-user-plus" type="submit" :loading="loading" />
        </form>

        <div class="border-top-1 surface-border pt-3 text-sm text-center">
          {{ t('auth.register.hasAccount') }} <Button :label="t('auth.register.loginLink')" link class="p-0" @click="router.push('/login')" />
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.register {
  padding: 3rem 0 4rem;
}
</style>
