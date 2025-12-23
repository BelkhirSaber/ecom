<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useAuthStore } from '@/stores/auth';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';

const { t } = useI18n();
const router = useRouter();
const toast = useToast();
const authStore = useAuthStore();

const form = ref({
  email: '',
  password: '',
  remember: false
});

const loading = ref(false);

const submit = async () => {
  loading.value = true;
  try {
    await authStore.login({
      email: form.value.email,
      password: form.value.password
    });
    toast.add({ severity: 'success', summary: t('auth.login.success'), detail: t('auth.login.welcome'), life: 3000 });
    router.push('/');
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: t('auth.login.error'),
      detail: error.response?.data?.message || t('auth.login.invalidCredentials'),
      life: 4000
    });
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <section class="login grid justify-content-center">
    <div class="col-12 md:col-6 xl:col-4">
      <div class="surface-card border-round-2xl shadow-2 p-4 md:p-5 flex flex-column gap-4">
        <div class="flex flex-column gap-2 text-center">
          <span class="pi pi-user text-3xl text-primary-500" />
          <h1 class="m-0 text-2xl font-semibold">{{ t('auth.login.title') }}</h1>
          <p class="m-0 text-color-secondary">{{ t('auth.login.subtitle') }}</p>
        </div>

        <form class="flex flex-column gap-3" @submit.prevent="submit">
          <label class="flex flex-column gap-2">
            <span class="font-medium">{{ t('auth.login.email') }}</span>
            <InputText v-model="form.email" type="email" :placeholder="t('auth.login.emailPlaceholder')" required autofocus />
          </label>

          <label class="flex flex-column gap-2">
            <span class="font-medium">{{ t('auth.login.password') }}</span>
            <Password
              v-model="form.password"
              toggleMask
              :feedback="false"
              :input-props="{ placeholder: t('auth.login.passwordPlaceholder') }"
              required
            />
          </label>

          <div class="flex align-items-center justify-content-between">
            <div class="flex align-items-center gap-2">
              <Checkbox v-model="form.remember" binary input-id="remember" />
              <label for="remember" class="text-sm">{{ t('auth.login.remember') }}</label>
            </div>
            <Button :label="t('auth.login.forgotPassword')" link class="p-0" type="button" />
          </div>

          <Button :label="t('auth.login.submit')" icon="pi pi-sign-in" type="submit" :loading="loading" />
        </form>

        <div class="border-top-1 surface-border pt-3 text-sm text-center">
          {{ t('auth.login.noAccount') }} <Button :label="t('auth.login.createAccount')" link class="p-0" @click="router.push('/register')" />
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.login {
  padding: 3rem 0 4rem;
}
</style>
