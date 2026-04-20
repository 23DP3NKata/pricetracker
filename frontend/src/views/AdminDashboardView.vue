<template>
  <v-container class="py-8 admin-dashboard-view">
    <div class="d-flex align-center justify-space-between mb-6 ga-3 flex-wrap">
      <h1 class="text-h4 font-weight-bold page-title">{{ $t('adminDashboard.title') }}</h1>
      <div class="d-flex ga-2 flex-wrap">
        <v-btn to="/admin/dashboard" rounded="xl" prepend-icon="mdi-shield-account" :variant="tabVariant('admin-dashboard')" :color="tabColor('admin-dashboard')">{{ $t('adminCommon.dashboard') }}</v-btn>
        <v-btn to="/admin/users" rounded="xl" prepend-icon="mdi-account-group-outline" :variant="tabVariant('admin-users')" :color="tabColor('admin-users')">{{ $t('adminCommon.users') }}</v-btn>
        <v-btn to="/admin/products" rounded="xl" prepend-icon="mdi-package-variant-closed" :variant="tabVariant('admin-products')" :color="tabColor('admin-products')">{{ $t('adminCommon.products') }}</v-btn>
        <v-btn to="/admin/logs" rounded="xl" prepend-icon="mdi-text-box-search-outline" :variant="tabVariant('admin-logs')" :color="tabColor('admin-logs')">{{ $t('adminCommon.logs') }}</v-btn>
        <v-btn to="/admin/actions" rounded="xl" prepend-icon="mdi-history" :variant="tabVariant('admin-actions')" :color="tabColor('admin-actions')">{{ $t('adminCommon.actions') }}</v-btn>
      </div>
    </div>

    <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-4" />

    <template v-if="stats">
      <v-card rounded="xl" class="hero-card pa-4 mb-6">
        <div class="hero-head-row mb-3">
          <div class="hero-head-inline">
            <span class="text-subtitle-1 font-weight-bold">{{ $t('adminDashboard.requestsAllTimeShort') }}</span>
          </div>
        </div>

        <div class="requests-actions-row">
          <div class="request-chips-row">
            <v-chip variant="flat" rounded="lg" class="request-chip request-chip--soft">
              {{ $t('adminDashboard.requestsDay') }}: <span class="request-value">{{ formatCount(stats.requests_day) }}</span>
            </v-chip>
            <v-chip variant="flat" rounded="lg" class="request-chip request-chip--soft">
              {{ $t('adminDashboard.requestsMonth') }}: <span class="request-value">{{ formatCount(stats.requests_month) }}</span>
            </v-chip>
            <v-chip variant="flat" rounded="lg" class="request-chip request-chip--soft">
              {{ $t('adminDashboard.requestsAllTime') }}: <span class="request-value">{{ formatCount(stats.requests_all_time) }}</span>
            </v-chip>
          </div>

          <div class="admin-actions-row">
            <v-btn
              class="admin-action-btn"
              rounded="lg"
              prepend-icon="mdi-refresh"
              variant="text"
              :loading="refreshingAll"
              :disabled="switchingAll"
              @click="refreshAllPrices"
            >
              {{ $t('adminDashboard.forceRefreshAllPricesShort') }}
            </v-btn>
            <v-btn
              class="admin-action-btn"
              rounded="lg"
              :prepend-icon="stopStartButtonIcon"
              variant="text"
              :loading="switchingAll"
              :disabled="refreshingAll"
              @click="toggleAllProducts"
            >
              {{ $t(stopStartButtonLabel) }}
            </v-btn>
          </div>
        </div>
      </v-card>

      <div class="section-label mb-2">{{ $t('adminCommon.users') }}</div>
      <v-row class="mb-2">
        <v-col cols="12" md="6" sm="6">
          <v-card rounded="xl" class="metric-card pa-4 h-100">
            <div class="card-top">
              <span class="metric-label">{{ $t('adminDashboard.usersTotal') }}</span>
            </div>
            <div class="metric-value">{{ formatCount(stats.users_total) }}</div>
            <div class="metric-sub">
              {{ $t('adminDashboard.admins') }}: {{ formatCount(stats.admins_total) }}
              | {{ $t('adminDashboard.active') }}: {{ formatCount(stats.active_users) }}
              | {{ $t('adminDashboard.blocked') }}: {{ formatCount(stats.blocked_users) }}
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" md="6" sm="6">
          <v-card rounded="xl" class="metric-card pa-4 h-100">
            <div class="card-top">
              <span class="metric-label">{{ $t('adminDashboard.trackingLinks') }}</span>
            </div>
            <div class="metric-value">{{ formatCount(stats.active_tracking_links) }}</div>
            <div class="metric-sub">{{ $t('adminDashboard.products') }}: {{ formatCount(stats.products_total) }}</div>
          </v-card>
        </v-col>
      </v-row>

      <div class="section-label mb-2">{{ $t('adminCommon.products') }}</div>
      <v-row class="mb-2">
        <v-col cols="12" md="6" sm="6">
          <v-card rounded="xl" class="metric-card pa-4 h-100">
            <div class="card-top">
              <span class="metric-label">{{ $t('adminDashboard.products') }}</span>
            </div>
            <div class="metric-value">{{ formatCount(stats.products_total) }}</div>
            <div class="metric-sub">
              {{ $t('adminDashboard.active') }}: {{ formatCount(stats.products_active) }}
              | {{ $t('adminDashboard.hidden') }}: {{ formatCount(stats.products_hidden) }}
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" md="6" sm="6">
          <v-card rounded="xl" class="metric-card pa-4 h-100">
            <div class="card-top">
              <span class="metric-label">{{ $t('adminDashboard.logs24h') }}</span>
            </div>
            <div class="metric-value">{{ formatCount(stats.logs_24h) }}</div>
            <div class="metric-sub text-error">{{ $t('adminDashboard.errorsCritical') }}: {{ formatCount(stats.errors_24h) }}</div>
          </v-card>
        </v-col>
      </v-row>

      <div class="section-label mb-2">{{ $t('adminCommon.actions') }}</div>
      <v-row>
        <v-col cols="12" md="6" sm="6">
          <v-card rounded="xl" class="metric-card pa-4 h-100">
            <div class="card-top">
              <span class="metric-label">{{ $t('adminDashboard.actions7d') }}</span>
            </div>
            <div class="metric-value">{{ formatCount(stats.actions_7d) }}</div>
          </v-card>
        </v-col>
        <v-col cols="12" md="6" sm="6">
          <v-card rounded="xl" class="metric-card pa-4 h-100">
            <div class="card-top">
              <span class="metric-label">{{ $t('adminDashboard.notifications24h') }}</span>
            </div>
            <div class="metric-value">{{ formatCount(stats.notifications_24h) }}</div>
          </v-card>
        </v-col>
      </v-row>
    </template>
  </v-container>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import {
  getAdminDashboard,
  refreshAllAdminProductPrices,
  startAllAdminProductUpdates,
  stopAllAdminProductUpdates,
} from '@/api'

const loading = ref(false)
const refreshingAll = ref(false)
const switchingAll = ref(false)
const stats = ref(null)
const route = useRoute()

const canStopAllProducts = computed(() => Number(stats.value?.products_active || 0) > 0)
const stopStartButtonLabel = computed(() => {
  if (canStopAllProducts.value) return 'adminDashboard.stopAllProductUpdates'
  return 'adminDashboard.startAllProductUpdates'
})
const stopStartButtonIcon = computed(() => {
  if (canStopAllProducts.value) return 'mdi-stop-circle-outline'
  return 'mdi-play-circle-outline'
})

function formatCount(value) {
  return new Intl.NumberFormat().format(Number(value || 0))
}

function isTabActive(name) {
  return route.name === name
}

function tabVariant(name) {
  if (isTabActive(name)) return 'flat'
  return 'tonal'
}

function tabColor(name) {
  if (isTabActive(name)) return 'primary'
  return undefined
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

async function refreshAllPrices() {
  refreshingAll.value = true
  try {
    await refreshAllAdminProductPrices()
    await loadStats()
  } finally {
    refreshingAll.value = false
  }
}

async function toggleAllProducts() {
  switchingAll.value = true
  try {
    if (canStopAllProducts.value) {
      await stopAllAdminProductUpdates()
    } else {
      await startAllAdminProductUpdates()
    }
    await loadStats()
  } finally {
    switchingAll.value = false
  }
}

onMounted(loadStats)
</script>

<style scoped>
.admin-dashboard-view {
  max-width: 1280px;
}

.page-title {
  letter-spacing: -0.015em;
}

.hero-card {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
  position: relative;
  z-index: 1;
}

.request-chip {
  color: rgba(var(--v-theme-on-surface), 0.8);
  font-weight: 400;
}

.request-chip--soft {
  background: rgba(var(--v-theme-on-surface), 0.06);
}

.request-value {
  margin-left: 4px;
  font-weight: 400;
}

.section-label {
  font-size: 0.76rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: rgba(var(--v-theme-on-surface), 0.56);
  font-weight: 700;
  position: relative;
  z-index: 2;
}

.metric-card {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
}

.card-top {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.metric-label {
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: rgba(var(--v-theme-on-surface), 0.56);
  font-weight: 700;
}

.metric-value {
  font-size: 1.9rem;
  line-height: 1.1;
  font-weight: 500;
  letter-spacing: -0.01em;
}

.metric-sub {
  margin-top: 4px;
  font-size: 0.82rem;
  color: rgba(var(--v-theme-on-surface), 0.72);
}

.hero-head-row {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.hero-head-inline {
  display: flex;
  align-items: baseline;
  gap: 10px;
  flex-wrap: wrap;
}

.requests-actions-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.request-chips-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.admin-actions-row {
  display: flex;
  gap: 8px;
  flex-wrap: nowrap;
  margin-left: auto;
  justify-content: flex-end;
}

.admin-action-btn {
  min-height: 36px;
  padding-inline: 10px;
  white-space: nowrap;
  text-align: left;
  line-height: 1.1;
  letter-spacing: 0.01em;
  text-transform: none;
  color: rgba(var(--v-theme-on-surface), 0.78);
}

.admin-action-btn:hover {
  background: rgba(var(--v-theme-on-surface), 0.05);
}

@media (max-width: 700px) {
  .requests-actions-row {
    align-items: stretch;
  }

  .request-chips-row {
    width: 100%;
  }

  .admin-actions-row {
    flex-direction: column;
    width: 100%;
    margin-left: 0;
  }

  .admin-action-btn {
    width: 100%;
    min-width: 0;
  }
}
</style>
