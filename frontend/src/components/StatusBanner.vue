<script setup lang="ts">
defineProps<{
  status: { maintenance: boolean; payments_maintenance: boolean; status: string } | null
  error: boolean
}>()
</script>

<template>
  <div v-if="error" class="banner banner--error">
    <span class="banner-dot" />
    No se pudo conectar con el backend API
  </div>
  <div v-else-if="status?.maintenance" class="banner banner--warning">
    <span class="banner-dot" />
    Modo mantenimiento activo — la plataforma está siendo actualizada
  </div>
  <div v-else-if="status?.payments_maintenance" class="banner banner--info">
    <span class="banner-dot" />
    Pagos en mantenimiento — Stripe / Cashier estará disponible pronto
  </div>
</template>
