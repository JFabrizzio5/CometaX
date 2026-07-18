<script setup lang="ts">
import { ref, onMounted } from 'vue'
import StatusBanner from './components/StatusBanner.vue'
import Sidebar from './components/Sidebar.vue'
import Dashboard from './components/Dashboard.vue'

interface ApiStatus {
  status: string
  maintenance: boolean
  payments_maintenance: boolean
}

const apiStatus = ref<ApiStatus | null>(null)
const apiError = ref(false)
const loading = ref(true)

onMounted(async () => {
  try {
    const res = await fetch('/api/v1/status')
    apiStatus.value = await res.json()
  } catch {
    apiError.value = true
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="app-shell">
    <!-- Loading splash -->
    <div v-if="loading" class="splash">
      <div class="splash-logo">
        <img src="./assets/FinalLogoCometax.svg" alt="CometaX" width="56" height="56" />
      </div>
      <div class="spinner" />
    </div>

    <!-- Main app -->
    <template v-else>
      <StatusBanner v-if="apiStatus" :status="apiStatus" :error="apiError" />
      <div class="layout">
        <Sidebar />
        <main class="main-content">
          <Dashboard :api-status="apiStatus" :api-error="apiError" />
        </main>
      </div>
    </template>
  </div>
</template>
