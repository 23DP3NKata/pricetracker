<template>
  <v-container class="py-8">
    <v-btn variant="text" rounded="xl" prepend-icon="mdi-arrow-left" class="mb-4" @click="$router.push('/products')">
      Back to Products
    </v-btn>

    <v-progress-linear v-if="store.loading" indeterminate color="primary" class="mb-4" />

    <template v-if="product">
      <!-- Product info -->
      <v-card rounded="xl" class="pa-6 mb-6">
        <div class="d-flex justify-space-between align-start flex-wrap ga-4">
          <div>
            <h1 class="text-h5 font-weight-bold mb-1">{{ product.title }}</h1>
            <div class="text-medium-emphasis mb-3">
              <v-icon size="16">mdi-store</v-icon> {{ product.store_name || 'Unknown' }}
            </div>
            <v-btn
              :href="productPageUrl"
              target="_blank"
              rel="noopener noreferrer"
              variant="text"
              rounded="xl"
              prepend-icon="mdi-open-in-new"
              class="px-0"
              :disabled="!productPageUrl"
            >
              Open product page
            </v-btn>
          </div>
          <div class="text-right">
            <div class="text-h4 font-weight-bold">{{ formatPrice(product.current_price) }}</div>
            <v-chip :color="product.status === 'active' ? 'success' : 'grey'" size="small" variant="tonal" class="mt-1">
              {{ product.status }}
            </v-chip>
          </div>
        </div>
      </v-card>

      <!-- Tracking settings -->
      <v-card rounded="xl" class="pa-6 mb-6">
        <h3 class="text-h6 font-weight-bold mb-4">Tracking Settings</h3>
        <v-row align="center">
          <v-col cols="12" sm="4">
            <v-select
              v-model="trackingForm.check_interval"
              :items="intervalOptions"
              label="Check Interval"
              variant="outlined"
              rounded="lg"
              item-title="text"
              item-value="value"
            />
          </v-col>
          <v-col cols="12" sm="4">
            <v-switch
              v-model="trackingForm.is_active"
              label="Active"
              color="primary"
              hide-details
            />
          </v-col>
          <v-col cols="12" sm="4" class="d-flex ga-2">
            <v-btn color="primary" rounded="xl" :loading="saving" @click="saveSettings">Save</v-btn>
            <v-btn color="error" variant="tonal" rounded="xl" @click="confirmDelete = true">Delete</v-btn>
          </v-col>
        </v-row>
        <v-alert v-if="saveMsg" :type="saveMsg.type" variant="tonal" rounded="lg" class="mt-3" closable @click:close="saveMsg = null">
          {{ saveMsg.text }}
        </v-alert>
      </v-card>

      <!-- Price History -->
      <v-card rounded="xl" class="pa-6">
        <div class="d-flex justify-space-between align-center mb-4">
          <h3 class="text-h6 font-weight-bold">Price History</h3>
          <v-btn-toggle v-model="historyDays" mandatory rounded="xl" density="compact" variant="outlined">
            <v-btn :value="7" size="small">7d</v-btn>
            <v-btn :value="30" size="small">30d</v-btn>
            <v-btn :value="90" size="small">90d</v-btn>
          </v-btn-toggle>
        </div>

        <!-- Stats -->
        <v-row v-if="historyStats" class="mb-4">
          <v-col cols="6" sm="3">
            <div class="text-caption text-medium-emphasis">Min</div>
            <div class="text-subtitle-1 font-weight-bold text-success">{{ formatPrice(historyStats.min) }}</div>
          </v-col>
          <v-col cols="6" sm="3">
            <div class="text-caption text-medium-emphasis">Max</div>
            <div class="text-subtitle-1 font-weight-bold text-error">{{ formatPrice(historyStats.max) }}</div>
          </v-col>
          <v-col cols="6" sm="3">
            <div class="text-caption text-medium-emphasis">Average</div>
            <div class="text-subtitle-1 font-weight-bold">{{ formatPrice(historyStats.avg) }}</div>
          </v-col>
          <v-col cols="6" sm="3">
            <div class="text-caption text-medium-emphasis">Data Points</div>
            <div class="text-subtitle-1 font-weight-bold">{{ historyStats.data_points }}</div>
          </v-col>
        </v-row>

        <!-- Price table -->
        <v-table v-if="historyData.length" density="compact">
          <thead>
            <tr>
              <th>Date</th>
              <th class="text-right">Price</th>
              <th class="text-right">Change</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(entry, i) in historyData" :key="i">
              <td>{{ formatDate(entry.checked_at) }}</td>
              <td class="text-right font-weight-medium">{{ formatPrice(entry.price) }}</td>
              <td class="text-right">
                <template v-if="i < historyData.length - 1">
                  <v-chip
                    :color="priceDiff(entry.price, historyData[i + 1].price) > 0 ? 'error' : priceDiff(entry.price, historyData[i + 1].price) < 0 ? 'success' : 'grey'"
                    size="x-small"
                    variant="tonal"
                  >
                    {{ priceDiff(entry.price, historyData[i + 1].price) > 0 ? '+' : '' }}{{ priceDiff(entry.price, historyData[i + 1].price).toFixed(2) }} €
                  </v-chip>
                </template>
              </td>
            </tr>
          </tbody>
        </v-table>

        <div v-else class="text-center text-medium-emphasis pa-4">
          No price data yet. Prices will be recorded when checks run.
        </div>
      </v-card>
    </template>

    <!-- Delete confirm -->
    <v-dialog v-model="confirmDelete" max-width="400">
      <v-card rounded="xl" class="pa-6">
        <h3 class="text-h6 font-weight-bold mb-2">Stop tracking?</h3>
        <p class="text-medium-emphasis mb-4">This product will be removed from your tracking list.</p>
        <div class="d-flex ga-2 justify-end">
          <v-btn variant="text" rounded="xl" @click="confirmDelete = false">Cancel</v-btn>
          <v-btn color="error" rounded="xl" :loading="deleting" @click="handleDelete">Delete</v-btn>
        </div>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, reactive, watch, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProductsStore } from '@/stores/products'
import { getPriceHistory } from '@/api'

const route = useRoute()
const router = useRouter()
const store = useProductsStore()

const product = ref(null)
const historyDays = ref(30)
const historyData = ref([])
const historyStats = ref(null)
const saving = ref(false)
const saveMsg = ref(null)
const confirmDelete = ref(false)
const deleting = ref(false)

const trackingForm = reactive({
  check_interval: 1440,
  is_active: true,
})

const intervalOptions = [
  { text: 'Every 30 min', value: 30 },
  { text: 'Every hour', value: 60 },
  { text: 'Every 6 hours', value: 360 },
  { text: 'Every 12 hours', value: 720 },
  { text: 'Every day', value: 1440 },
]

const productPageUrl = computed(() => {
  const url = (product.value?.product_page_url || product.value?.url || '').trim()

  if (!url) return ''
  if (/^https?:\/\//i.test(url)) return url

  return `https://${url.replace(/^\/+/, '')}`
})

function formatPrice(price) {
  if (price === null || price === undefined || Number.isNaN(Number(price))) {
    return 'No data'
  }

  return Number(price).toFixed(2) + ' €'
}

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleString()
}

function priceDiff(current, previous) {
  return Number(current) - Number(previous)
}

async function loadProduct() {
  await store.fetchProduct(route.params.id)
  product.value = store.currentProduct
  if (product.value?.tracking) {
    trackingForm.check_interval = product.value.tracking.check_interval || 1440
    trackingForm.is_active = product.value.tracking.is_active ?? true
  }
}

async function loadHistory() {
  try {
    const { data } = await getPriceHistory(route.params.id, historyDays.value)
    historyData.value = data.history || []
    historyStats.value = data.stats || null
  } catch {
    historyData.value = []
    historyStats.value = null
  }
}

async function saveSettings() {
  saving.value = true
  saveMsg.value = null
  try {
    await store.updateProduct(product.value.id, trackingForm)
    saveMsg.value = { type: 'success', text: 'Settings saved' }
  } catch {
    saveMsg.value = { type: 'error', text: 'Failed to save' }
  } finally {
    saving.value = false
  }
}

async function handleDelete() {
  deleting.value = true
  try {
    await store.removeProduct(product.value.id)
    router.push('/products')
  } finally {
    deleting.value = false
  }
}

watch(historyDays, () => loadHistory())

onMounted(async () => {
  await loadProduct()
  await loadHistory()
})
</script>
