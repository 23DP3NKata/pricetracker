<template>
  <v-container class="py-8">
    <v-btn variant="text" rounded="xl" prepend-icon="mdi-arrow-left" class="mb-4" @click="$router.push('/products')">
      {{ $t('productDetail.backToProducts') }}
    </v-btn>

    <v-progress-linear v-if="store.loading" indeterminate color="primary" class="mb-4" />

    <template v-if="product">
      <!-- Product info -->
      <v-card rounded="xl" class="pa-6 mb-6">
        <div class="d-flex justify-space-between align-start flex-wrap ga-4">
          <div>
            <h1 class="text-h5 font-weight-bold mb-1">{{ product.title }}</h1>
            <div class="text-medium-emphasis mb-3">
              <v-icon size="16">mdi-store</v-icon> {{ product.store_name || $t('productDetail.unknown') }}
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
              {{ $t('productDetail.openProductPage') }}
            </v-btn>
          </div>
          <div class="text-right">
            <div class="text-h4 font-weight-bold">{{ formatPrice(product.current_price) }}</div>
            <v-chip :color="product.status === 'active' ? 'success' : 'grey'" size="small" variant="tonal" class="mt-1">
              {{ statusLabel(product.status) }}
            </v-chip>
          </div>
        </div>
      </v-card>

      <!-- Tracking settings -->
      <v-card rounded="xl" class="pa-6 mb-6">
        <h3 class="text-h6 font-weight-bold mb-4">{{ $t('productDetail.trackingSettings') }}</h3>
        <v-row align="center">
          <v-col cols="12" sm="4">
            <v-select
              v-model="trackingForm.check_interval"
              :items="intervalOptions"
              :label="$t('productDetail.checkInterval')"
              variant="outlined"
              rounded="lg"
              item-title="text"
              item-value="value"
            />
          </v-col>
          <v-col cols="12" sm="4">
            <v-switch
              v-model="trackingForm.is_active"
              :label="$t('productDetail.active')"
              color="primary"
              hide-details
            />
          </v-col>
          <v-col cols="12" sm="4" class="d-flex ga-2">
            <v-btn color="primary" rounded="xl" :loading="saving" @click="saveSettings">{{ $t('productDetail.save') }}</v-btn>
            <v-btn color="error" variant="tonal" rounded="xl" @click="confirmDelete = true">{{ $t('productDetail.delete') }}</v-btn>
          </v-col>
        </v-row>
        <div class="text-caption text-medium-emphasis mt-2">
          <v-icon size="14">mdi-timer-sand</v-icon>
          {{ $t('productsPage.sortNextCheck') }}: {{ formatDateOrFallback(product.tracking?.next_check_at) }}
        </div>
        <v-alert v-if="saveMsg" :type="saveMsg.type" variant="tonal" rounded="lg" class="mt-3" closable @click:close="saveMsg = null">
          {{ saveMsg.text }}
        </v-alert>
      </v-card>

      <!-- Price History -->
      <v-card rounded="xl" class="pa-6">
        <div class="d-flex justify-space-between align-center mb-4">
          <h3 class="text-h6 font-weight-bold">{{ $t('productDetail.priceHistory') }}</h3>
          <v-btn-toggle v-model="historyDays" mandatory rounded="xl" density="compact" variant="outlined">
            <v-btn :value="7" size="small">7d</v-btn>
            <v-btn :value="30" size="small">30d</v-btn>
            <v-btn :value="90" size="small">90d</v-btn>
          </v-btn-toggle>
        </div>

        <!-- Stats -->
        <v-row v-if="historyStats" class="mb-4">
          <v-col cols="6" sm="3">
            <div class="text-caption text-medium-emphasis">{{ $t('productDetail.min') }}</div>
            <div class="text-subtitle-1 font-weight-bold text-success">{{ formatPrice(historyStats.min) }}</div>
          </v-col>
          <v-col cols="6" sm="3">
            <div class="text-caption text-medium-emphasis">{{ $t('productDetail.max') }}</div>
            <div class="text-subtitle-1 font-weight-bold text-error">{{ formatPrice(historyStats.max) }}</div>
          </v-col>
          <v-col cols="6" sm="3">
            <div class="text-caption text-medium-emphasis">{{ $t('productDetail.average') }}</div>
            <div class="text-subtitle-1 font-weight-bold">{{ formatPrice(historyStats.avg) }}</div>
          </v-col>
          <v-col cols="6" sm="3">
            <div class="text-caption text-medium-emphasis">{{ $t('productDetail.dataPoints') }}</div>
            <div class="text-subtitle-1 font-weight-bold">{{ historyStats.data_points }}</div>
          </v-col>
        </v-row>

        <!-- Price table -->
        <v-table v-if="historyData.length" density="compact">
          <thead>
            <tr>
              <th>
                <button type="button" class="table-sort-btn" @click="setHistorySort('date')">
                  {{ $t('productDetail.date') }}
                  <v-icon v-if="historySortKey === 'date'" size="14">{{ historySortDir === 'desc' ? 'mdi-arrow-down' : 'mdi-arrow-up' }}</v-icon>
                </button>
              </th>
              <th class="text-right">{{ $t('productDetail.price') }}</th>
              <th class="text-right">
                <button type="button" class="table-sort-btn table-sort-btn-right" @click="setHistorySort('change')">
                  {{ $t('productDetail.change') }}
                  <v-icon v-if="historySortKey === 'change'" size="14">{{ historySortDir === 'desc' ? 'mdi-arrow-down' : 'mdi-arrow-up' }}</v-icon>
                </button>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(entry, i) in sortedHistoryData" :key="`${entry.checked_at}-${entry.price}-${i}`">
              <td>{{ formatDate(entry.checked_at) }}</td>
              <td class="text-right font-weight-medium">{{ formatPrice(entry.price) }}</td>
              <td class="text-right">
                <template v-if="i < sortedHistoryData.length - 1">
                  <v-chip
                    :color="priceDiff(entry.price, sortedHistoryData[i + 1].price) > 0 ? 'error' : priceDiff(entry.price, sortedHistoryData[i + 1].price) < 0 ? 'success' : 'grey'"
                    size="x-small"
                    variant="tonal"
                  >
                    {{ priceDiff(entry.price, sortedHistoryData[i + 1].price) > 0 ? '+' : '' }}{{ priceDiff(entry.price, sortedHistoryData[i + 1].price).toFixed(2) }} €
                  </v-chip>
                </template>
              </td>
            </tr>
          </tbody>
        </v-table>

        <div v-else class="text-center text-medium-emphasis pa-4">
          {{ $t('productDetail.noPriceDataYet') }}
        </div>
      </v-card>
    </template>

    <!-- Delete confirm -->
    <v-dialog v-model="confirmDelete" max-width="400">
      <v-card rounded="xl" class="pa-6">
        <h3 class="text-h6 font-weight-bold mb-2">{{ $t('productDetail.stopTrackingTitle') }}</h3>
        <p class="text-medium-emphasis mb-4">{{ $t('productDetail.stopTrackingText') }}</p>
        <div class="d-flex ga-2 justify-end">
          <v-btn variant="text" rounded="xl" @click="confirmDelete = false">{{ $t('productDetail.cancel') }}</v-btn>
          <v-btn color="error" rounded="xl" :loading="deleting" @click="handleDelete">{{ $t('productDetail.delete') }}</v-btn>
        </div>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, reactive, watch, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useProductsStore } from '@/stores/products'
import { getPriceHistory } from '@/api'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const store = useProductsStore()

const product = ref(null)
const historyDays = ref(30)
const historyData = ref([])
const historyStats = ref(null)
const historySortKey = ref('date')
const historySortDir = ref('desc')
const saving = ref(false)
const saveMsg = ref(null)
const confirmDelete = ref(false)
const deleting = ref(false)

const trackingForm = reactive({
  check_interval: 1440,
  is_active: true,
})

const intervalOptions = computed(() => [
  { text: t('productDetail.every30min'), value: 30 },
  { text: t('productDetail.everyHour'), value: 60 },
  { text: t('productDetail.every6hours'), value: 360 },
  { text: t('productDetail.every12hours'), value: 720 },
  { text: t('productDetail.everyDay'), value: 1440 },
])

function statusLabel(status) {
  return status === 'active' ? t('productDetail.active') : t('productDetail.paused')
}

const productPageUrl = computed(() => {
  const url = (product.value?.product_page_url || product.value?.url || '').trim()

  if (!url) return ''
  if (/^https?:\/\//i.test(url)) return url

  return `https://${url.replace(/^\/+/, '')}`
})

function formatPrice(price) {
  if (price === null || price === undefined || Number.isNaN(Number(price))) {
    return t('productDetail.noData')
  }

  return Number(price).toFixed(2) + ' €'
}

function formatDate(dateStr) {
  // Backend may return UTC without timezone suffix ("YYYY-MM-DD HH:mm:ss").
  // Normalize to ISO UTC and then render in user local time.
  const hasTimezone = /[zZ]$|[+-]\d{2}:\d{2}$/.test(dateStr)
  const normalized = hasTimezone
    ? dateStr
    : `${dateStr.replace(' ', 'T')}Z`

  const date = new Date(normalized)
  if (Number.isNaN(date.getTime())) return t('productDetail.noData')

  return date.toLocaleString()
}

function parseHistoryDate(dateStr) {
  if (!dateStr) return Number.NEGATIVE_INFINITY

  const hasTimezone = /[zZ]$|[+-]\d{2}:\d{2}$/.test(dateStr)
  const normalized = hasTimezone
    ? dateStr
    : `${dateStr.replace(' ', 'T')}Z`

  const date = new Date(normalized)
  return Number.isNaN(date.getTime()) ? Number.NEGATIVE_INFINITY : date.getTime()
}

const sortedHistoryData = computed(() => {
  const rows = [...historyData.value]

  if (historySortKey.value === 'date') {
    rows.sort((a, b) => parseHistoryDate(a.checked_at) - parseHistoryDate(b.checked_at))
  }

  if (historySortKey.value === 'change') {
    // Compute change on chronological order first, then sort by absolute change.
    const byDateAsc = [...rows].sort((a, b) => parseHistoryDate(a.checked_at) - parseHistoryDate(b.checked_at))
    const withChange = byDateAsc.map((entry, index) => ({
      entry,
      change: index < byDateAsc.length - 1 ? priceDiff(entry.price, byDateAsc[index + 1].price) : null,
    }))

    withChange.sort((a, b) => {
      const aVal = a.change === null ? Number.NEGATIVE_INFINITY : Math.abs(a.change)
      const bVal = b.change === null ? Number.NEGATIVE_INFINITY : Math.abs(b.change)
      return aVal - bVal
    })

    rows.splice(0, rows.length, ...withChange.map((row) => row.entry))
  }

  if (historySortDir.value === 'desc') {
    rows.reverse()
  }

  return rows
})

function setHistorySort(key) {
  if (historySortKey.value === key) {
    historySortDir.value = historySortDir.value === 'desc' ? 'asc' : 'desc'
    return
  }

  historySortKey.value = key
  historySortDir.value = 'desc'
}

function formatDateOrFallback(dateStr) {
  if (!dateStr) return t('productDetail.noData')
  return formatDate(dateStr)
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
    await loadProduct()
    saveMsg.value = { type: 'success', text: t('productDetail.settingsSaved') }
  } catch {
    saveMsg.value = { type: 'error', text: t('productDetail.failedToSave') }
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

<style scoped>
.table-sort-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: transparent;
  border: 0;
  padding: 0;
  cursor: pointer;
  font: inherit;
  color: inherit;
}

.table-sort-btn-right {
  margin-left: auto;
}
</style>
