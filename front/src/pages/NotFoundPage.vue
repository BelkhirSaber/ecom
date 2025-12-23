<template>
  <section class="not-found surface-card border-round-2xl shadow-2 p-6 text-center flex flex-column gap-3 align-items-center">
    <span class="pi pi-compass text-5xl text-primary-500" aria-hidden="true" />
    <h1 class="m-0 text-3xl font-semibold">Page introuvable</h1>
    <p class="m-0 text-color-secondary">La page que vous recherchez n'existe plus ou a été déplacée.</p>
    <Button label="Retour à l'accueil" icon="pi pi-home" @click="$router.push({ name: 'home' })" />
  </section>
</template>

<script setup>
import Button from 'primevue/button';
</script>

<style scoped>
.not-found {
  margin: 4rem auto;
  max-width: 480px;
}
</style>
