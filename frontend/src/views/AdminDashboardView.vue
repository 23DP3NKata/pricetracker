<template>
  <v-container class="py-8">
    <div class="d-flex align-center justify-space-between mb-6 ga-3 flex-wrap">
      <h1 class="text-h4 font-weight-bold">{{ $t('adminDashboard.title') }}</h1>
      <div class="d-flex ga-2 flex-wrap">
        <v-btn to="/admin/dashboard" rounded="xl" prepend-icon="mdi-shield-account" :variant="isTabActive('admin-dashboard') ? 'flat' : 'tonal'" :color="isTabActive('admin-dashboard') ? 'primary' : undefined">{{ $t('adminCommon.dashboard') }}</v-btn>
        <v-btn to="/admin/users" rounded="xl" prepend-icon="mdi-account-group-outline" :variant="isTabActive('admin-users') ? 'flat' : 'tonal'" :color="isTabActive('admin-users') ? 'primary' : undefined">{{ $t('adminCommon.users') }}</v-btn>
        <v-btn to="/admin/products" rounded="xl" prepend-icon="mdi-package-variant-closed" :variant="isTabActive('admin-products') ? 'flat' : 'tonal'" :color="isTabActive('admin-products') ? 'primary' : undefined">{{ $t('adminCommon.products') }}</v-btn>
        <v-btn to="/admin/logs" rounded="xl" prepend-icon="mdi-text-box-search-outline" :variant="isTabActive('admin-logs') ? 'flat' : 'tonal'" :color="isTabActive('admin-logs') ? 'primary' : undefined">{{ $t('adminCommon.logs') }}</v-btn>
        <v-btn to="/admin/actions" rounded="xl" prepend-icon="mdi-history" :variant="isTabActive('admin-actions') ? 'flat' : 'tonal'" :color="isTabActive('admin-actions') ? 'primary' : undefined">{{ $t('adminCommon.actions') }}</v-btn>
      </div>
    </div>

    <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-4" />

    <v-row v-if="stats">
      <v-col cols="12" md="3" sm="6">
        <v-card rounded="xl" class="pa-4">
          <div class="text-caption text-medium-emphasis">{{ $t('adminDashboard.usersTotal') }}</div>
          <div class="text-h5 font-weight-bold">{{ stats.users_total }}</div>
          <div class="text-caption">{{ $t('adminDashboard.admins') }}: {{ stats.admins_total }}</div>
        </v-card>
      </v-col>
      <v-col cols="12" md="3" sm="6">
        <v-card rounded="xl" class="pa-4">
          <div class="text-caption text-medium-emphasis">{{ $t('adminDashboard.usersStatus') }}</div>
          <div class="text-h5 font-weight-bold">{{ stats.active_users }}</div>
          <div class="text-caption">{{ $t('adminDashboard.blocked') }}: {{ stats.blocked_users }}</div>
        </v-card>
      </v-col>
      <v-col cols="12" md="3" sm="6">
        <v-card rounded="xl" class="pa-4">
          <div class="text-caption text-medium-emphasis">{{ $t('adminDashboard.products') }}</div>
          <div class="text-h5 font-weight-bold">{{ stats.products_total }}</div>
          <div class="text-caption">{{ $t('adminDashboard.active') }}: {{ stats.products_active }} | {{ $t('adminDashboard.hidden') }}: {{ stats.products_hidden }}</div>
        </v-card>
      </v-col>
      <v-col cols="12" md="3" sm="6">
        <v-card rounded="xl" class="pa-4">
          <div class="text-caption text-medium-emphasis">{{ $t('adminDashboard.trackingLinks') }}</div>
          <div class="text-h5 font-weight-bold">{{ stats.active_tracking_links }}</div>
          <div class="text-caption">{{ $t('adminDashboard.deletedProducts') }}: {{ stats.products_deleted }}</div>
        </v-card>
      </v-col>

      <v-col cols="12" md="4">
        <v-card rounded="xl" class="pa-4">
          <div class="text-caption text-medium-emphasis">{{ $t('adminDashboard.logs24h') }}</div>
          <div class="text-h5 font-weight-bold">{{ stats.logs_24h }}</div>
          <div class="text-caption text-error">{{ $t('adminDashboard.errorsCritical') }}: {{ stats.errors_24h }}</div>
        </v-card>
      </v-col>
      <v-col cols="12" md="4">
        <v-card rounded="xl" class="pa-4">
          <div class="text-caption text-medium-emphasis">{{ $t('adminDashboard.actions7d') }}</div>
          <div class="text-h5 font-weight-bold">{{ stats.actions_7d }}</div>
        </v-card>
      </v-col>
      <v-col cols="12" md="4">
        <v-card rounded="xl" class="pa-4">
          <div class="text-caption text-medium-emphasis">{{ $t('adminDashboard.notifications24h') }}</div>
          <div class="text-h5 font-weight-bold">{{ stats.notifications_24h }}</div>
        </v-card>
      </v-col>

      <v-col cols="12" md="6">
        <v-card rounded="xl" class="pa-4">
          <div class="d-flex justify-space-between align-center mb-3">
            <div class="text-caption text-medium-emphasis">{{ $t('adminDashboard.addProductAvailability') }}</div>
            <v-chip :color="addProductEnabled ? 'success' : 'warning'" variant="tonal" size="small">
              {{ addProductEnabled ? $t('adminDashboard.enabled') : $t('adminDashboard.disabled') }}
            </v-chip>
          </div>
          <div class="text-body-2 mb-4">{{ $t('adminDashboard.addProductAvailabilityHint') }}</div>
          <v-btn
            :color="addProductEnabled ? 'warning' : 'success'"
            variant="flat"
            rounded="xl"
            :loading="savingToggle"
            @click="openToggleDialog"
          >
            {{ addProductEnabled ? $t('adminDashboard.disableAddProduct') : $t('adminDashboard.enableAddProduct') }}
          </v-btn>
        </v-card>
      </v-col>
    </v-row>

    <v-dialog v-model="toggleDialog" max-width="520">
      <v-card rounded="xl" class="pa-6">
        <h3 class="text-h6 font-weight-bold mb-2">{{ $t('adminDashboard.confirmAction') }}</h3>
        <p class="text-body-2 mb-4">
          {{ addProductEnabled ? $t('adminDashboard.confirmDisableAddProduct') : $t('adminDashboard.confirmEnableAddProduct') }}
        </p>

        <v-textarea
          v-model="toggleReason"
          :label="$t('adminDashboard.reasonOptional')"
          rows="3"
          auto-grow
          variant="outlined"
          rounded="lg"
          class="mb-4"
        />

        <div class="d-flex justify-end ga-2">
          <v-btn variant="text" rounded="xl" @click="toggleDialog = false">{{ $t('adminCommon.cancel') }}</v-btn>
          <v-btn
            :color="addProductEnabled ? 'warning' : 'success'"
            rounded="xl"
            :loading="savingToggle"
            @click="confirmToggle"
          >
            {{ $t('adminDashboard.confirm') }}
          </v-btn>
        </div>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { getAdminDashboard, updateAdminAddProductSetting } from '@/api'

const loading = ref(false)
const stats = ref(null)
const addProductEnabled = ref(true)
const toggleDialog = ref(false)
const toggleReason = ref('')
const savingToggle = ref(false)
const route = useRoute()

function isTabActive(name) {
  return route.name === name
}

async function loadStats() {
  loading.value = true
  try {
    const { data } = await getAdminDashboard()
    stats.value = data
    addProductEnabled.value = !!data?.add_product_enabled
  } finally {
    loading.value = false
  }
}

function openToggleDialog() {
  toggleReason.value = ''
  toggleDialog.value = true
}

async function confirmToggle() {
  savingToggle.value = true
  try {
    const { data } = await updateAdminAddProductSetting({
      enabled: !addProductEnabled.value,
      reason: toggleReason.value?.trim() || null,
    })
    addProductEnabled.value = !!data?.add_product_enabled
    toggleDialog.value = false
    await loadStats()
  } finally {
    savingToggle.value = false
  }
}

onMounted(loadStats)
</script>
