<template>
  <v-container class="py-8">
    <v-btn variant="text" rounded="xl" prepend-icon="mdi-arrow-left" class="mb-4" @click="$router.push('/dashboard')">
      {{ $t('productDetail.backToProducts') }}
    </v-btn>

    <v-progress-linear v-if="store.loading" indeterminate color="primary" class="mb-4" />

    <template v-if="product">
      <!-- Product info -->
      <v-card rounded="xl" class="pa-6 mb-6">
        <div class="d-flex align-start justify-space-between flex-wrap ga-4">
          <div class="d-flex align-center flex-wrap ga-3">
            <v-avatar size="44" color="grey-lighten-4">
              <v-img v-if="product.image_url" :src="product.image_url" :alt="product.symbol" />
              <v-icon v-else size="22">mdi-currency-btc</v-icon>
            </v-avatar>
            <div class="d-flex align-center flex-wrap ga-2">
              <h1 class="text-h5 font-weight-bold mb-0">{{ product.title }}</h1>
              <span class="text-medium-emphasis text-subtitle-2">{{ (product.symbol || 'N/A').toUpperCase() }}</span>
            </div>
          </div>
          <div class="text-right">
            <div class="text-h4 font-weight-bold">{{ formatPrice(product.current_price) }}</div>
            <div class="text-body-2 mt-1" :class="changeTextClass(product.price_change_24h)">
              {{ changeArrow(product.price_change_24h) }} {{ formatPercent(product.price_change_24h) }} 24h
            </div>
          </div>
        </div>

        <div class="d-flex align-center justify-space-between flex-wrap ga-3 mt-3">
          <a
            v-if="productUrl"
            :href="productUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="text-caption text-medium-emphasis"
          >
            ↗ {{ $t('product.openPage') }}
          </a>
          <div class="d-flex flex-column ga-2" :class="smAndDown ? 'align-start' : 'align-end'">
            <div class="d-flex align-center flex-wrap ga-2">
              <v-chip :color="product.status === 'active' ? 'success' : 'grey'" size="small" variant="tonal">
                {{ statusLabel(product.status) }}
              </v-chip>
              <v-chip :color="trendColor(product.trend)" size="small" variant="tonal">
                {{ trendLabel(product.trend) }}
              </v-chip>
            </div>
            <v-btn
              variant="outlined"
              color="primary"
              size="default"
              prepend-icon="mdi-bell-plus-outline"
              :disabled="!emailVerified"
              @click="openTrackDialog"
            >
              {{ $t('product.trackBtn') }}
            </v-btn>
          </div>
        </div>
      </v-card>

      <!-- Price History -->
      <v-card rounded="xl" class="pa-6">
        <div class="d-flex align-center flex-wrap ga-3 mb-4">
          <h3 class="text-h6 font-weight-bold">{{ $t('productDetail.priceHistory') }}</h3>
          <v-btn-toggle v-model="historyDays" mandatory rounded="xl" density="compact" variant="outlined">
            <v-btn :value="7" size="small">7d</v-btn>
            <v-btn :value="30" size="small">30d</v-btn>
            <v-btn :value="90" size="small">90d</v-btn>
            <v-btn :value="-1" size="small">{{ $t('productDetail.allTime') }}</v-btn>
          </v-btn-toggle>
        </div>

        <!-- Stats -->
        <v-row v-if="historyStats" class="mb-4">
          <v-col cols="6" sm="3">
            <v-card variant="flat" rounded="lg" class="pa-3">
              <div class="d-flex align-center ga-2">
                <v-icon size="20" color="success">mdi-arrow-down-bold</v-icon>
                <div class="text-caption text-medium-emphasis">{{ $t('productDetail.min') }}</div>
              </div>
              <div class="text-subtitle-1 font-weight-bold text-success mt-1">{{ formatPrice(historyStats.min) }}</div>
            </v-card>
          </v-col>
          <v-col cols="6" sm="3">
            <v-card variant="flat" rounded="lg" class="pa-3">
              <div class="d-flex align-center ga-2">
                <v-icon size="20" color="error">mdi-arrow-up-bold</v-icon>
                <div class="text-caption text-medium-emphasis">{{ $t('productDetail.max') }}</div>
              </div>
              <div class="text-subtitle-1 font-weight-bold text-error mt-1">{{ formatPrice(historyStats.max) }}</div>
            </v-card>
          </v-col>
          <v-col cols="6" sm="3">
            <v-card variant="flat" rounded="lg" class="pa-3">
              <div class="d-flex align-center ga-2">
                <v-icon size="20">mdi-sigma</v-icon>
                <div class="text-caption text-medium-emphasis">{{ $t('productDetail.average') }}</div>
              </div>
              <div class="text-subtitle-1 font-weight-bold mt-1">{{ formatPrice(historyStats.avg) }}</div>
            </v-card>
          </v-col>
          <v-col cols="6" sm="3">
            <v-card variant="flat" rounded="lg" class="pa-3">
              <div class="d-flex align-center ga-2">
                <v-icon size="20">mdi-chart-scatter-plot</v-icon>
                <div class="text-caption text-medium-emphasis">{{ $t('productDetail.dataPoints') }}</div>
              </div>
              <div class="text-subtitle-1 font-weight-bold mt-1">{{ historyStats.data_points }}</div>
            </v-card>
          </v-col>
        </v-row>

        <div class="mb-4 chart-wrap">
          <div class="chart-scroll">
            <div class="chart-inner" :style="{ height: '280px' }">
              <Line :data="chartData" :options="chartOptions" />
            </div>
          </div>
        </div>

        <!-- Price table -->
        <template v-if="historyData.length">
          <div class="d-sm-none">
            <div
              v-for="item in historyRows"
              :key="item.id"
              class="d-flex justify-space-between align-center py-2 mobile-history-row"
            >
              <span class="text-caption text-medium-emphasis">{{ formatDate(item.checked_at) }}</span>
              <div class="text-right">
                <div class="text-body-2 font-weight-500">{{ formatPrice(item.price) }}</div>
                <div class="text-caption" :class="item.change >= 0 ? 'text-success' : 'text-error'">
                  {{ item.change >= 0 ? '▲' : '▼' }} {{ formatChange(item.change) }}
                </div>
              </div>
            </div>
          </div>

          <div class="d-none d-sm-block">
            <v-table density="compact" class="history-table">
              <thead>
                <tr>
                  <th>{{ $t('productDetail.date') }}</th>
                  <th class="text-right">{{ $t('productDetail.price') }}</th>
                  <th class="text-right">{{ $t('productDetail.change') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(entry, i) in historyData" :key="`${entry.checked_at}-${entry.price}-${i}`">
                  <td>{{ formatDate(entry.checked_at) }}</td>
                  <td class="text-right font-weight-medium">{{ formatPrice(entry.price) }}</td>
                  <td class="text-right">
                    <template v-if="i < historyData.length - 1">
                      <span :class="historyChangeClass(entry.price, historyData[i + 1].price)">
                        {{ historyChangeArrow(entry.price, historyData[i + 1].price) }}
                        {{ historyChangeAmount(entry.price, historyData[i + 1].price) }}
                        ({{ historyChangePercent(entry.price, historyData[i + 1].price) }})
                      </span>
                    </template>
                  </td>
                </tr>
              </tbody>
            </v-table>
          </div>

          <div v-if="!smAndDown" class="d-flex justify-center align-center flex-wrap ga-2 mt-4">
            <v-btn variant="text" rounded="xl" size="small" :disabled="loadingPage || historyPage <= 1" @click="goToPage(1)">
              «
            </v-btn>
            <v-btn variant="text" rounded="xl" size="small" :disabled="loadingPage || historyPage <= 1" @click="goToPage(historyPage - 1)">
              ‹
            </v-btn>
            <div class="d-flex align-center ga-2 text-body-2 text-medium-emphasis">
              <span>{{ $t('productDetail.pageIndicator', { current: historyPage, last: totalPages }) }}</span>
              <input
                class="page-input"
                type="number"
                :value="historyPage"
                min="1"
                :max="totalPages"
                @change="goToPage(Number($event.target.value))"
              />
            </div>
            <v-btn variant="text" rounded="xl" size="small" :disabled="loadingPage || historyPage >= totalPages" @click="goToPage(historyPage + 1)">
              ›
            </v-btn>
            <v-btn variant="text" rounded="xl" size="small" :disabled="loadingPage || historyPage >= totalPages" @click="goToPage(totalPages)">
              »
            </v-btn>
          </div>
        </template>

        <div v-else class="text-center text-medium-emphasis pa-4">
          {{ $t('productDetail.noPriceDataYet') }}
        </div>
      </v-card>
    </template>

    <TrackingSetupDialog
      v-model="showTrackDialog"
      :asset="product"
      @tracked="handleTrackCreated"
    />

  </v-container>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useDisplay } from 'vuetify'
import TrackingSetupDialog from '@/components/TrackingSetupDialog.vue'
import { useProductsStore } from '@/stores/products'
import { useAuthStore } from '@/stores/auth'
import { getPriceHistory } from '@/api'
import { formatCurrencyPrice } from '@/utils/price'
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
const { t } = useI18n()
const { smAndDown } = useDisplay()
const store = useProductsStore()
const auth = useAuthStore()

const product = ref(null)
const showTrackDialog = ref(false)
const historyDays = ref(30)
const historyData = ref([])
const chartHistoryData = ref([])
const historyStats = ref(null)
const historyPage = ref(1)
const loadingPage = ref(false)
const historyPagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
})
const totalPages = computed(() => Math.max(1, Number(historyPagination.value.last_page) || 1))
const emailVerified = computed(() => auth.emailVerified)
const productUrl = computed(() => {
  const url = (product.value?.product_page_url || '').trim()

  if (!url) return ''
  if (/^https?:\/\//i.test(url)) return url

  return `https://${url.replace(/^\/+/, '')}`
})
const historyRows = computed(() =>
  historyData.value.map((entry, i) => ({
    id: `${entry.checked_at}-${entry.price}-${i}`,
    checked_at: entry.checked_at,
    price: entry.price,
    change: i < historyData.value.length - 1 ? priceDiff(entry.price, historyData.value[i + 1].price) : 0,
  }))
)

function openTrackDialog() {
  showTrackDialog.value = true
}

async function handleTrackCreated() {
  await loadProduct()
}

function statusLabel(status) {
  if (status === 'active') return t('productDetail.active')
  return t('productDetail.paused')
}

function trendLabel(trend) {
  if (trend === 'up') return t('productDetail.trendUp')
  if (trend === 'down') return t('productDetail.trendDown')
  return t('productDetail.trendFlat')
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

function changeTextClass(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return 'text-medium-emphasis'
  }

  if (Number(value) > 0) return 'text-success'
  if (Number(value) < 0) return 'text-error'
  return 'text-medium-emphasis'
}

function formatPrice(price) {
  if (price === null || price === undefined || Number.isNaN(Number(price))) return t('productDetail.noData')
  return formatCurrencyPrice(price, product.value?.currency || 'USD')
}

function formatChange(value) {
  const currency = (product.value?.currency || 'USD').toUpperCase()
  if (!Number.isFinite(Number(value))) {
    return formatCurrencyPrice(0, currency)
  }

  return formatCurrencyPrice(Math.abs(Number(value)), currency)
}

function changeArrow(value) {
  const numeric = Number(value)
  if (!Number.isFinite(numeric)) return '•'
  if (numeric > 0) return '▲'
  if (numeric < 0) return '▼'
  return '•'
}
function historyChangeArrow(current, previous) {
  const diff = priceDiff(current, previous)
  if (diff > 0) return '▲'
  if (diff < 0) return '▼'
  return '•'
}

function historyChangeAmount(current, previous) {
  const currency = (product.value?.currency || 'USD').toUpperCase()
  const diff = Number(current) - Number(previous)

  if (!Number.isFinite(diff)) {
    return formatCurrencyPrice(0, currency)
  }

  const sign = diff > 0 ? '+' : diff < 0 ? '-' : ''
  const formatted = formatCurrencyPrice(Math.abs(diff), currency)
  return `${sign}${formatted}`
}

function historyChangePercent(current, previous) {
  const diff = priceDiff(current, previous)
  const prevNum = Number(previous)

  if (!Number.isFinite(diff) || !Number.isFinite(prevNum) || prevNum === 0) {
    return formatPercent(0)
  }

  return formatPercent((diff / prevNum) * 100)
}

function historyChangeClass(current, previous) {
  const diff = priceDiff(current, previous)
  if (diff > 0) return 'text-success'
  if (diff < 0) return 'text-error'
  return 'text-medium-emphasis'
}

function formatDate(dateStr) {
  const hasTimezone = /[zZ]$|[+-]\d{2}:\d{2}$/.test(dateStr)
  let normalized = dateStr
  if (!hasTimezone) {
    normalized = `${dateStr.replace(' ', 'T')}Z`
  }

  const date = new Date(normalized)
  if (Number.isNaN(date.getTime())) return t('productDetail.noData')

  return date.toLocaleString()
}

function formatChartAxisDate(dateStr) {
  const timestamp = parseHistoryDate(dateStr)
  if (!Number.isFinite(timestamp)) return ''

  const date = new Date(timestamp)
  if (smAndDown.value) {
    return new Intl.DateTimeFormat(undefined, {
      month: 'short',
      day: 'numeric',
    }).format(date)
  }

  return new Intl.DateTimeFormat(undefined, {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

function parseHistoryDate(dateStr) {
  if (!dateStr) return Number.NEGATIVE_INFINITY

  const hasTimezone = /[zZ]$|[+-]\d{2}:\d{2}$/.test(dateStr)
  let normalized = dateStr
  if (!hasTimezone) {
    normalized = `${dateStr.replace(' ', 'T')}Z`
  }

  const date = new Date(normalized)
  if (Number.isNaN(date.getTime())) {
    return Number.NEGATIVE_INFINITY
  }

  return date.getTime()
}

const chartRows = computed(() => {
  const asc = [...chartHistoryData.value].sort((a, b) => parseHistoryDate(a.checked_at) - parseHistoryDate(b.checked_at))
  const maxPoints = 200

  if (asc.length <= maxPoints) return asc

  const bucketCount = Math.ceil(maxPoints / 2)
  const bucketSize = Math.ceil(asc.length / bucketCount)
  const sampled = []

  for (let start = 0; start < asc.length; start += bucketSize) {
    const end = Math.min(start + bucketSize, asc.length)

    let minIndex = start
    let maxIndex = start
    let minPrice = Number(asc[start]?.price)
    let maxPrice = Number(asc[start]?.price)

    if (!Number.isFinite(minPrice)) minPrice = Number.POSITIVE_INFINITY
    if (!Number.isFinite(maxPrice)) maxPrice = Number.NEGATIVE_INFINITY

    for (let i = start; i < end; i += 1) {
      const price = Number(asc[i]?.price)
      if (!Number.isFinite(price)) continue

      if (price < minPrice) {
        minPrice = price
        minIndex = i
      }

      if (price > maxPrice) {
        maxPrice = price
        maxIndex = i
      }
    }

    if (minIndex === maxIndex) {
      sampled.push(asc[minIndex])
      continue
    }

    if (minIndex < maxIndex) {
      sampled.push(asc[minIndex], asc[maxIndex])
      continue
    }

    sampled.push(asc[maxIndex], asc[minIndex])
  }

  const thinned = []
  let prev = null

  for (const row of sampled) {
    if (row !== prev) {
      thinned.push(row)
      prev = row
    }
  }

  const first = asc[0]
  const last = asc[asc.length - 1]

  if (thinned[0] !== first) {
    thinned.unshift(first)
  }

  if (thinned[thinned.length - 1] !== last) {
    thinned.push(last)
  }

  if (thinned.length <= maxPoints) return thinned

  const step = Math.ceil(thinned.length / maxPoints)
  const compact = thinned.filter((_, i) => i % step === 0)

  if (compact[compact.length - 1] !== last) {
    compact.push(last)
  }

  return compact
})

const chartData = computed(() => {
  const labels = []
  const points = []

  for (const entry of chartRows.value) {
    labels.push(formatChartAxisDate(entry.checked_at))
    points.push(Number(entry.price))
  }

  return {
    labels,
    datasets: [
      {
        label: 'Price',
        data: points,
        borderColor: '#1976d2',
        backgroundColor: 'rgba(25, 118, 210, 0.15)',
        borderWidth: 2,
        pointRadius: points.length < 10 ? 5 : points.length > 100 ? 0 : 3,
        pointHoverRadius: 5,
        pointHitRadius: 16,
        tension: 0.25,
        fill: true,
      },
    ],
  }
})

const chartOptions = computed(() => {
  const prices = []

  for (const entry of chartRows.value) {
    const price = Number(entry.price)
    if (Number.isFinite(price)) {
      prices.push(price)
    }
  }

  let yMin
  let yMax

  if (prices.length) {
    const minVal = Math.min(...prices)
    const maxVal = Math.max(...prices)
    const pad = (maxVal - minVal) * 0.1 || 0.01

    if (minVal > 0) {
      yMin = Math.max(0, minVal - pad)
    } else {
      yMin = minVal - pad
    }

    yMax = maxVal + pad
  }

  return {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false,
      },
      tooltip: {
        displayColors: false,
        callbacks: {
          title(context) {
            const first = context?.[0]
            if (!first) return ''

            const row = chartRows.value[first.dataIndex]
            if (!row) return ''
            return formatDate(row.checked_at)
          },
          label(context) {
            const currency = (product.value?.currency || 'USD').toUpperCase()
            const y = Number(context.parsed.y)

            return `${new Intl.NumberFormat(undefined, {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }).format(y)} ${currency}`
          },
        },
      },
    },
    scales: {
      x: {
        grid: {
          display: false,
        },
        ticks: {
          maxRotation: 0,
          autoSkip: true,
          maxTicksLimit: 8,
        },
      },
      y: {
        min: yMin,
        max: yMax,
        ticks: {
          maxTicksLimit: smAndDown.value ? 5 : 7,
          callback(value) {
            return new Intl.NumberFormat(undefined, {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }).format(Number(value))
          },
        },
      },
    },
  }
})

function priceDiff(current, previous) {
  return Number(current) - Number(previous)
}

async function loadProduct() {
  await store.fetchProduct(route.params.id)
  product.value = store.currentProduct
}

async function loadHistory() {
  try {
    const days = historyDays.value === -1 ? null : historyDays.value

    const { data } = await getPriceHistory(route.params.id, days, historyPage.value)
    historyData.value = data.history || []
    chartHistoryData.value = data.chart_history || data.history || []
    historyStats.value = data.stats || null
    historyPagination.value = {
      current_page: data?.pagination?.current_page || 1,
      last_page: data?.pagination?.last_page || 1,
      per_page: data?.pagination?.per_page || 10,
      total: data?.pagination?.total || historyData.value.length,
    }
    historyPage.value = historyPagination.value.current_page
  } catch {
    historyData.value = []
    chartHistoryData.value = []
    historyStats.value = null
    historyPagination.value = {
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
    }
  }
}

async function goToPage(p) {
  if (loadingPage.value) return

  const nextPage = Number(p)
  if (!Number.isFinite(nextPage)) return

  const clampedPage = Math.min(totalPages.value, Math.max(1, Math.trunc(nextPage)))
  if (clampedPage === historyPage.value) return

  loadingPage.value = true
  historyPage.value = clampedPage

  try {
    await loadHistory()
  } finally {
    loadingPage.value = false
  }
}

watch(historyDays, async () => {
  historyPage.value = 1
  await loadHistory()
})

watch(
  () => route.params.id,
  async () => {
    historyPage.value = 1
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
  width: 100%;
}

.chart-scroll {
  width: 100%;
  overflow: hidden;
}

.chart-inner {
  width: 100%;
  height: 280px;
}

.page-input {
  width: 48px;
  text-align: center;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.2);
  border-radius: 8px;
  padding: 4px 6px;
  font: inherit;
  color: inherit;
  background: transparent;
}

.page-input:focus {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 1px;
}

.mobile-history-row {
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.history-table tbody tr {
  background-color: transparent;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.12);
}

.history-table tbody tr:last-child {
  border-bottom: 0;
}
</style>
