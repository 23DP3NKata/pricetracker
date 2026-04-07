<template>
  <v-container class="py-8">
    <v-btn variant="text" rounded="xl" prepend-icon="mdi-arrow-left" class="mb-4" @click="$router.push('/dashboard')">
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
              <v-icon size="16">mdi-currency-btc</v-icon> {{ (product.symbol || 'N/A').toUpperCase() }}
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
            <div class="d-flex justify-end ga-2 mt-2 mb-1">
              <v-chip :color="trendColor(product.trend)" size="small" variant="tonal">
                {{ trendLabel(product.trend) }}
              </v-chip>
              <v-chip :color="changeColor(product.price_change_24h)" size="small" variant="tonal">
                24h: {{ formatPercent(product.price_change_24h) }}
              </v-chip>
            </div>
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
            <v-switch
              v-model="trackingForm.is_active"
              :label="$t('productDetail.active')"
              color="primary"
              hide-details
            />
          </v-col>
          <v-col cols="12" sm="4">
            <v-text-field
              v-model="trackingForm.target_price"
              label="Target price"
              variant="outlined"
              rounded="lg"
              type="number"
              min="0"
              step="0.00000001"
            />
          </v-col>
          <v-col cols="12" sm="4">
            <v-select
              v-model="trackingForm.notify_when"
              :items="alertDirectionOptions"
              label="Notify when"
              variant="outlined"
              rounded="lg"
              item-title="text"
              item-value="value"
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

        <div class="mb-4 chart-wrap">
          <Line :data="chartData" :options="chartOptions" />
        </div>

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
                    {{ formatPriceDiff(entry.price, sortedHistoryData[i + 1].price) }} {{ (product?.currency || 'USD').toUpperCase() }}
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
import { ref, reactive, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useProductsStore } from '@/stores/products'
import { getPriceHistory } from '@/api'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend)

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
  is_active: true,
  target_price: '',
  notify_when: 'below',
})

const alertDirectionOptions = [
  { text: 'At or below target', value: 'below' },
  { text: 'At or above target', value: 'above' },
]

function statusLabel(status) {
  return status === 'active' ? t('productDetail.active') : t('productDetail.paused')
}

function trendLabel(trend) {
  if (trend === 'up') return `📈 ${t('productDetail.trendUp')}`
  if (trend === 'down') return `📉 ${t('productDetail.trendDown')}`
  return `⏸ ${t('productDetail.trendFlat')}`
}

function trendColor(trend) {
  if (trend === 'up') return 'success'
  if (trend === 'down') return 'error'
  return 'grey'
}

function formatPercent(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return 'N/A'
  }

  const num = Number(value)
  const sign = num > 0 ? '+' : ''
  return `${sign}${num.toFixed(2)}%`
}

function changeColor(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return 'grey'
  }

  if (Number(value) > 0) return 'success'
  if (Number(value) < 0) return 'error'
  return 'grey'
}

const productPageUrl = computed(() => {
  const url = (product.value?.product_page_url || '').trim()

  if (!url) return ''
  if (/^https?:\/\//i.test(url)) return url

  return `https://${url.replace(/^\/+/, '')}`
})

function formatPrice(price) {
  if (price === null || price === undefined || Number.isNaN(Number(price))) {
    return t('productDetail.noData')
  }

  const currency = (product.value?.currency || 'USD').toUpperCase()
  const numeric = Number(price)
  const fractionDigits = numeric >= 1000 ? 2 : numeric >= 1 ? 4 : 8

  const formatted = new Intl.NumberFormat(undefined, {
    minimumFractionDigits: 0,
    maximumFractionDigits: fractionDigits,
  }).format(numeric)

  return `${formatted} ${currency}`
}

function formatPriceDiff(current, previous) {
  const diff = Number(current) - Number(previous)
  if (Number.isNaN(diff)) return '0'

  const sign = diff > 0 ? '+' : ''
  const formatted = new Intl.NumberFormat(undefined, {
    minimumFractionDigits: 0,
    maximumFractionDigits: 8,
  }).format(diff)

  return `${sign}${formatted}`
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

const chartData = computed(() => {
  const asc = [...historyData.value].sort((a, b) => parseHistoryDate(a.checked_at) - parseHistoryDate(b.checked_at))

  return {
    labels: asc.map((entry) => formatDate(entry.checked_at)),
    datasets: [
      {
        label: 'Price',
        data: asc.map((entry) => Number(entry.price)),
        borderColor: '#1976d2',
        backgroundColor: 'rgba(25, 118, 210, 0.15)',
        borderWidth: 2,
        pointRadius: 2,
        tension: 0.25,
        fill: true,
      },
    ],
  }
})

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false,
    },
    tooltip: {
      callbacks: {
        label(context) {
          const currency = (product.value?.currency || 'USD').toUpperCase()
          const y = Number(context.parsed.y)
          const fractionDigits = y >= 1000 ? 2 : y >= 1 ? 4 : 8

          return `${new Intl.NumberFormat(undefined, {
            minimumFractionDigits: 0,
            maximumFractionDigits: fractionDigits,
          }).format(y)} ${currency}`
        },
      },
    },
  },
  scales: {
    x: {
      ticks: {
        maxRotation: 0,
        autoSkip: true,
        maxTicksLimit: 6,
      },
    },
    y: {
      ticks: {
        callback(value) {
          return new Intl.NumberFormat(undefined, {
            minimumFractionDigits: 0,
            maximumFractionDigits: 8,
          }).format(Number(value))
        },
      },
    },
  },
}))

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
    trackingForm.is_active = product.value.tracking.is_active ?? true
    trackingForm.target_price = product.value.tracking.target_price ?? ''
    trackingForm.notify_when = product.value.tracking.notify_when || 'below'
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
    await store.updateProduct(product.value.id, {
      is_active: trackingForm.is_active,
      target_price: trackingForm.target_price === '' ? null : Number(trackingForm.target_price),
      notify_when: trackingForm.notify_when,
    })
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

watch(
  () => route.params.id,
  async () => {
    saveMsg.value = null
    await loadProduct()
    await loadHistory()
  },
  { immediate: true }
)
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

.chart-wrap {
  min-height: 280px;
}
</style>
