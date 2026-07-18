<script setup lang="ts">
import { ref, computed } from 'vue'

interface ApiStatus {
  status: string
  maintenance: boolean
  payments_maintenance: boolean
}

const props = defineProps<{
  apiStatus: ApiStatus | null
  apiError: boolean
}>()

const backendOk = computed(() =>
  !props.apiError && props.apiStatus?.status === 'ok'
)

const stats = ref([
  { label: 'Clientes activos',    value: '—',  delta: '',      color: 'purple' },
  { label: 'Proyectos en curso',  value: '—',  delta: '',      color: 'blue'   },
  { label: 'Cotizaciones abiert.', value: '—', delta: '',      color: 'amber'  },
  { label: 'Facturación (MXN)',   value: '—',  delta: '',      color: 'green'  },
])

const recentActivity = ref([
  { type: 'info',    text: 'Backend API conectado y funcionando',   time: 'ahora' },
  { type: 'warning', text: 'Pagos en mantenimiento (Stripe fase 2)', time: 'sistema' },
  { type: 'info',    text: 'Base de datos SQLite lista',             time: 'inicio' },
])
</script>

<template>
  <div class="dashboard">
    <!-- Header -->
    <header class="dashboard-header">
      <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-sub">Bienvenido a CometaX — plataforma de gestión de consultoría</p>
      </div>
      <div class="header-actions">
        <div class="api-pill" :class="backendOk ? 'api-pill--ok' : 'api-pill--err'">
          <span class="api-dot" />
          {{ backendOk ? 'API conectada' : 'API sin conexión' }}
        </div>
        <button class="btn-primary">+ Nuevo proyecto</button>
      </div>
    </header>

    <!-- API status detail card -->
    <div v-if="apiStatus" class="status-card">
      <div class="status-card-title">Estado del sistema</div>
      <div class="status-grid">
        <div class="status-item">
          <span class="status-label">Plataforma</span>
          <span class="status-val" :class="apiStatus.maintenance ? 'val--warn' : 'val--ok'">
            {{ apiStatus.maintenance ? 'Mantenimiento' : 'Operativa' }}
          </span>
        </div>
        <div class="status-item">
          <span class="status-label">Pagos / Stripe</span>
          <span class="status-val" :class="apiStatus.payments_maintenance ? 'val--warn' : 'val--ok'">
            {{ apiStatus.payments_maintenance ? 'En desarrollo' : 'Activo' }}
          </span>
        </div>
        <div class="status-item">
          <span class="status-label">Base de datos</span>
          <span class="status-val val--ok">SQLite ✓</span>
        </div>
        <div class="status-item">
          <span class="status-label">Endpoint</span>
          <span class="status-val val--ok">/api/v1/status ✓</span>
        </div>
      </div>
    </div>

    <!-- KPI cards -->
    <section class="stats-grid">
      <div v-for="stat in stats" :key="stat.label" class="stat-card" :class="`stat-card--${stat.color}`">
        <div class="stat-label">{{ stat.label }}</div>
        <div class="stat-value">{{ stat.value }}</div>
        <div class="stat-note">Pendiente de datos reales</div>
      </div>
    </section>

    <!-- Two-column area -->
    <div class="content-grid">
      <!-- Activity feed -->
      <div class="card">
        <h2 class="card-title">Actividad reciente</h2>
        <ul class="activity-list">
          <li v-for="(ev, i) in recentActivity" :key="i" class="activity-item">
            <span class="activity-dot" :class="`dot--${ev.type}`" />
            <span class="activity-text">{{ ev.text }}</span>
            <span class="activity-time">{{ ev.time }}</span>
          </li>
        </ul>
      </div>

      <!-- Quick actions -->
      <div class="card">
        <h2 class="card-title">Acciones rápidas</h2>
        <div class="quick-actions">
          <button class="qa-btn">
            <span class="qa-icon">◉</span>
            <span>Nuevo cliente</span>
          </button>
          <button class="qa-btn">
            <span class="qa-icon">◈</span>
            <span>Nuevo proyecto</span>
          </button>
          <button class="qa-btn">
            <span class="qa-icon">◇</span>
            <span>Crear cotización</span>
          </button>
          <button class="qa-btn">
            <span class="qa-icon">◆</span>
            <span>Emitir factura</span>
          </button>
          <button class="qa-btn">
            <span class="qa-icon">◎</span>
            <span>Agregar consultor</span>
          </button>
          <button class="qa-btn">
            <span class="qa-icon">▦</span>
            <span>Ver reportes</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
