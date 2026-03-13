<template>
  <v-container class="py-8">
    <h1 class="text-h4 font-weight-bold mb-6">Dashboard</h1>

    <!-- Email verification banner -->
    <v-alert
      v-if="!auth.emailVerified"
      type="warning"
      variant="tonal"
      rounded="lg"
      class="mb-6"
      prominent
    >
      <div class="d-flex align-center justify-space-between flex-wrap ga-3">
        <div>
          <strong>Email not verified.</strong> Your monthly limit is 5 checks.
          Verify your email to get 180.
        </div>
        <div class="d-flex ga-2">
          <v-btn
            v-if="!verifySent"
            color="warning"
            variant="flat"
            size="small"
            rounded="xl"
            :loading="verifyLoading"
            @click="handleResend"
          >
            Send Verification Link
          </v-btn>
          <v-chip v-else color="success" size="small" variant="tonal">Link sent!</v-chip>
        </div>
      </div>
    </v-alert>

    <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-4" />

    <div v-if="stats" class="mb-8">
      <!-- Stats cards -->
      <v-row>
        <v-col cols="12" sm="6" md="3">
          <v-card rounded="xl" class="pa-4">
            <div class="d-flex align-center ga-3">
              <v-avatar color="primary" variant="tonal" size="48">
                <v-icon>mdi-package-variant</v-icon>
              </v-avatar>
              <div>
                <div class="text-h5 font-weight-bold">{{ stats.total_products }}</div>
                <div class="text-medium-emphasis text-body-2">Products</div>
              </div>
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" md="3">
          <v-card rounded="xl" class="pa-4">
            <div class="d-flex align-center ga-3">
              <v-avatar color="error" variant="tonal" size="48">
                <v-icon>mdi-bell-ring</v-icon>
              </v-avatar>
              <div>
                <div class="text-h5 font-weight-bold">{{ stats.unread_notifications }}</div>
                <div class="text-medium-emphasis text-body-2">Unread</div>
              </div>
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" md="3">
          <v-card rounded="xl" class="pa-4">
            <div class="d-flex align-center ga-3">
              <v-avatar color="success" variant="tonal" size="48">
                <v-icon>mdi-arrow-down-bold</v-icon>
              </v-avatar>
              <div>
                <div class="text-h5 font-weight-bold">{{ stats.recent_drops }}</div>
                <div class="text-medium-emphasis text-body-2">Price Drops (7d)</div>
              </div>
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" md="3">
          <v-card rounded="xl" class="pa-4">
            <div class="d-flex align-center ga-3">
              <v-avatar color="warning" variant="tonal" size="48">
                <v-icon>mdi-arrow-up-bold</v-icon>
              </v-avatar>
              <div>
                <div class="text-h5 font-weight-bold">{{ stats.recent_increases }}</div>
                <div class="text-medium-emphasis text-body-2">Increases (7d)</div>
              </div>
            </div>
          </v-card>
        </v-col>
      </v-row>

      <!-- Monthly limit -->
      <v-card rounded="xl" class="pa-4 mt-4">
        <div class="d-flex justify-space-between align-center mb-2">
          <span class="font-weight-medium">Monthly Checks</span>
          <span class="text-medium-emphasis">{{ stats.checks_used }} / {{ stats.monthly_limit }}</span>
        </div>
        <v-progress-linear
          :model-value="(stats.checks_used / stats.monthly_limit) * 100"
          color="primary"
          rounded
          height="8"
        />
      </v-card>

      <!-- Top Drops -->
      <v-card v-if="stats.top_drops && stats.top_drops.length" rounded="xl" class="pa-4 mt-4">
        <h3 class="text-h6 font-weight-bold mb-3">
          <v-icon color="success" class="mr-1">mdi-trending-down</v-icon>
          Biggest Price Drops (7 days)
        </h3>
        <v-list>
          <v-list-item
            v-for="drop in stats.top_drops"
            :key="drop.product_id"
            :to="`/products/${drop.product_id}`"
            rounded="lg"
          >
            <v-list-item-title>{{ drop.title }}</v-list-item-title>
            <v-list-item-subtitle>
              {{ formatPrice(drop.old_price) }} → {{ formatPrice(drop.new_price) }}
            </v-list-item-subtitle>
            <template #append>
              <v-chip color="success" size="small" variant="tonal">
                -{{ formatPrice(drop.old_price - drop.new_price) }}
              </v-chip>
            </template>
          </v-list-item>
        </v-list>
      </v-card>

      <!-- Quick actions -->
      <v-row class="mt-4">
        <v-col cols="12" sm="6">
          <v-btn to="/products" color="primary" variant="tonal" block rounded="xl" size="large" prepend-icon="mdi-package-variant">
            My Products
          </v-btn>
        </v-col>
        <v-col cols="12" sm="6">
          <v-btn to="/notifications" color="secondary" variant="tonal" block rounded="xl" size="large" prepend-icon="mdi-bell">
            Notifications
          </v-btn>
        </v-col>
      </v-row>
    </div>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { getDashboard, resendVerification } from '@/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const stats = ref(null)
const loading = ref(true)
const verifyLoading = ref(false)
const verifySent = ref(false)

function formatPrice(price) {
  return Number(price).toFixed(2) + ' €'
}

async function handleResend() {
  verifyLoading.value = true
  try {
    await resendVerification()
    verifySent.value = true
  } finally {
    verifyLoading.value = false
  }
}

onMounted(async () => {
  try {
    const { data } = await getDashboard()
    stats.value = data
  } finally {
    loading.value = false
  }
})
</script>
