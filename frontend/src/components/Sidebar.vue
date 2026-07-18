<script setup lang="ts">
import { ref } from 'vue'

const activeNav = ref('dashboard')

const navItems = [
  { id: 'dashboard',   label: 'Dashboard',    icon: '▦' },
  { id: 'clients',     label: 'Clientes',     icon: '◉' },
  { id: 'projects',    label: 'Proyectos',    icon: '◈' },
  { id: 'consultants', label: 'Consultores',  icon: '◎' },
  { id: 'quotes',      label: 'Cotizaciones', icon: '◇' },
  { id: 'invoices',    label: 'Facturas',     icon: '◆' },
  { id: 'reports',     label: 'Reportes',     icon: '◈' },
]
</script>

<template>
  <aside class="sidebar">
    <div class="sidebar-logo">
      <img src="../assets/FinalLogoCometax.svg" alt="CometaX" width="32" height="32" />
      <span>Cometa<strong>X</strong></span>
    </div>

    <nav class="sidebar-nav">
      <button
        v-for="item in navItems"
        :key="item.id"
        class="nav-item"
        :class="{ 'nav-item--active': activeNav === item.id }"
        @click="activeNav = item.id"
      >
        <span class="nav-icon">{{ item.icon }}</span>
        <span class="nav-label">{{ item.label }}</span>
        <span v-if="activeNav === item.id" class="nav-indicator" />
      </button>
    </nav>

    <div class="sidebar-footer">
      <div class="user-avatar">J</div>
      <div class="user-info">
        <span class="user-name">Jos5</span>
        <span class="user-role">Administrador</span>
      </div>
    </div>
  </aside>
</template>
