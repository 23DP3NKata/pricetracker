<template>
  <v-container class="py-8">
    <div class="d-flex align-center justify-space-between mb-6 ga-3 flex-wrap">
      <h1 class="text-h4 font-weight-bold">Admin: Dashboard</h1>
      <div class="d-flex ga-2 flex-wrap">
        <v-btn to="/admin/dashboard" rounded="xl" prepend-icon="mdi-shield-account" :variant="isTabActive('admin-dashboard') ? 'flat' : 'tonal'" :color="isTabActive('admin-dashboard') ? 'primary' : undefined">Dashboard</v-btn>
        <v-btn to="/admin/users" rounded="xl" prepend-icon="mdi-account-group-outline" :variant="isTabActive('admin-users') ? 'flat' : 'tonal'" :color="isTabActive('admin-users') ? 'primary' : undefined">Users</v-btn>
        <v-btn to="/admin/products" rounded="xl" prepend-icon="mdi-package-variant-closed" :variant="isTabActive('admin-products') ? 'flat' : 'tonal'" :color="isTabActive('admin-products') ? 'primary' : undefined">Products</v-btn>
        <v-btn to="/admin/logs" rounded="xl" prepend-icon="mdi-text-box-search-outline" :variant="isTabActive('admin-logs') ? 'flat' : 'tonal'" :color="isTabActive('admin-logs') ? 'primary' : undefined">Logs</v-btn>
        <v-btn to="/admin/actions" rounded="xl" prepend-icon="mdi-history" :variant="isTabActive('admin-actions') ? 'flat' : 'tonal'" :color="isTabActive('admin-actions') ? 'primary' : undefined">Actions</v-btn>
      </div>
    </div>

    <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-4" />

    <v-row v-if="stats">
      <v-col cols="12" md="3" sm="6">
        <v-card rounded="xl" class="pa-4">
          <div class="text-caption text-medium-emphasis">Users total</div>
          <div class="text-h5 font-weight-bold">{{ stats.users_total }}</div>
          <div class="text-caption">Admins: {{ stats.admins_total }}</div>
        </v-card>
      </v-col>
      <v-col cols="12" md="3" sm="6">
        <v-card rounded="xl" class="pa-4">
          <div class="text-caption text-medium-emphasis">Users status</div>
          <div class="text-h5 font-weight-bold">{{ stats.active_users }}</div>
          <div class="text-caption">Blocked: {{ stats.blocked_users }}</div>
        </v-card>
      </v-col>
      <v-col cols="12" md="3" sm="6">
        <v-card rounded="xl" class="pa-4">
          <div class="text-caption text-medium-emphasis">Products</div>
          <div class="text-h5 font-weight-bold">{{ stats.products_total }}</div>
          <div class="text-caption">Active: {{ stats.products_active }} | Hidden: {{ stats.products_hidden }}</div>
        </v-card>
      </v-col>
      <v-col cols="12" md="3" sm="6">
        <v-card rounded="xl" class="pa-4">
          <div class="text-caption text-medium-emphasis">Tracking links</div>
          <div class="text-h5 font-weight-bold">{{ stats.active_tracking_links }}</div>
          <div class="text-caption">Deleted products: {{ stats.products_deleted }}</div>
        </v-card>
      </v-col>

      <v-col cols="12" md="4">
        <v-card rounded="xl" class="pa-4">
          <div class="text-caption text-medium-emphasis">Logs last 24h</div>
          <div class="text-h5 font-weight-bold">{{ stats.logs_24h }}</div>
          <div class="text-caption text-error">Errors/Critical: {{ stats.errors_24h }}</div>
        </v-card>
      </v-col>
      <v-col cols="12" md="4">
        <v-card rounded="xl" class="pa-4">
          <div class="text-caption text-medium-emphasis">Admin actions last 7d</div>
          <div class="text-h5 font-weight-bold">{{ stats.actions_7d }}</div>
        </v-card>
      </v-col>
      <v-col cols="12" md="4">
        <v-card rounded="xl" class="pa-4">
          <div class="text-caption text-medium-emphasis">Notifications last 24h</div>
          <div class="text-h5 font-weight-bold">{{ stats.notifications_24h }}</div>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { getAdminDashboard } from '@/api'

const loading = ref(false)
const stats = ref(null)
const route = useRoute()

function isTabActive(name) {
  return route.name === name
}

async function loadStats() {
  loading.value = true
  try {
    const { data } = await getAdminDashboard()
    stats.value = data
  } finally {
    loading.value = false
  }
}

onMounted(loadStats)
</script>
