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

      <!-- Price History -->
      <v-card rounded="xl" class="pa-6">
        <div class="d-flex justify-space-between align-center mb-4">
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
                  <v-icon v-if="historySortKey === 'date'" size="14">{{ sortArrowIcon() }}</v-icon>
                </button>
              </th>
              <th class="text-right">{{ $t('productDetail.price') }}</th>
              <th class="text-right">
                <button type="button" class="table-sort-btn table-sort-btn-right" @click="setHistorySort('change')">
                  {{ $t('productDetail.change') }}
                  <v-icon v-if="historySortKey === 'change'" size="14">{{ sortArrowIcon() }}</v-icon>
                </button>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(entry, i) in displayedHistoryData" :key="`${entry.checked_at}-${entry.price}-${i}`">
              <td>{{ formatDate(entry.checked_at) }}</td>
              <td class="text-right font-weight-medium">{{ formatPrice(entry.price) }}</td>
              <td class="text-right">
                <template v-if="i < displayedHistoryData.length - 1">
                  <v-chip
                    :color="historyChangeColor(entry.price, displayedHistoryData[i + 1].price)"
                    size="x-small"
                    variant="tonal"
                  >
                    {{ formatPriceDiff(entry.price, displayedHistoryData[i + 1].price) }} {{ (product?.currency || 'USD').toUpperCase() }}
                  </v-chip>
                </template>
              </td>
            </tr>
          </tbody>
        </v-table>

        <div v-if="hasMoreHistory" class="d-flex justify-center mt-3">
          <v-btn variant="text" rounded="xl" @click="showAllHistory = !showAllHistory">
            {{ showAllHistory ? $t('productDetail.showLessHistory') : $t('productDetail.showMoreHistory') }}
          </v-btn>
        </div>

        <div v-else class="text-center text-medium-emphasis pa-4">
          {{ $t('productDetail.noPriceDataYet') }}
        </div>
      </v-card>
    </template>

  </v-container>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useProductsStore } from '@/stores/products'
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
const store = useProductsStore()

const product = ref(null)
const historyDays = ref(30)
const historyData = ref([])
const historyStats = ref(null)
const historySortKey = ref('date')
const historySortDir = ref('desc')
const showAllHistory = ref(false)
const HISTORY_INITIAL_LIMIT = 10

function statusLabel(status) {
  if (status === 'active') return t('productDetail.active')
  return t('productDetail.paused')
}

function sortArrowIcon() {
  if (historySortDir.value === 'desc') return 'mdi-arrow-down'
  return 'mdi-arrow-up'
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

function changeColor(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return 'grey'
  }

  if (Number(value) > 0) return 'success'
  if (Number(value) < 0) return 'error'
  return 'grey'
}

function historyChangeColor(current, previous) {
  const diff = priceDiff(current, previous)
  if (diff > 0) return 'error'
  if (diff < 0) return 'success'
  return 'grey'
}

const productPageUrl = computed(() => {
  const url = (product.value?.product_page_url || '').trim()

  if (!url) return ''
  if (/^https?:\/\//i.test(url)) return url

  return `https://${url.replace(/^\/+/, '')}`
})

function formatPrice(price) {
  if (price === null || price === undefined || Number.isNaN(Number(price))) return t('productDetail.noData')
  return formatCurrencyPrice(price, product.value?.currency || 'USD')
}

function formatPriceDiff(current, previous) {
  const diff = Number(current) - Number(previous)
  if (Number.isNaN(diff)) return '0'

  const sign = diff > 0 ? '+' : ''
  const formatted = new Intl.NumberFormat(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(diff)

  return `${sign}${formatted}`
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

const sortedHistoryData = computed(() => {
  const rows = [...historyData.value]

  if (historySortKey.value === 'date') {
    rows.sort((a, b) => parseHistoryDate(a.checked_at) - parseHistoryDate(b.checked_at))
  }

  if (historySortKey.value === 'change') {
    const byDateAsc = [...rows].sort((a, b) => parseHistoryDate(a.checked_at) - parseHistoryDate(b.checked_at))
    const withChange = []

    for (let i = 0; i < byDateAsc.length; i += 1) {
      const entry = byDateAsc[i]
      let change = null
      if (i < byDateAsc.length - 1) {
        change = priceDiff(entry.price, byDateAsc[i + 1].price)
      }

      withChange.push({ entry, change })
    }

    withChange.sort((a, b) => {
      const aVal = a.change === null ? Number.NEGATIVE_INFINITY : Math.abs(a.change)
      const bVal = b.change === null ? Number.NEGATIVE_INFINITY : Math.abs(b.change)
      return aVal - bVal
    })

    const reordered = []
    for (const row of withChange) {
      reordered.push(row.entry)
    }
    rows.splice(0, rows.length, ...reordered)
  }

  if (historySortDir.value === 'desc') {
    rows.reverse()
  }

  return rows
})

const hasMoreHistory = computed(() => sortedHistoryData.value.length > HISTORY_INITIAL_LIMIT)

const displayedHistoryData = computed(() => {
  if (showAllHistory.value) return sortedHistoryData.value
  return sortedHistoryData.value.slice(0, HISTORY_INITIAL_LIMIT)
})

const chartData = computed(() => {
  const asc = [...historyData.value].sort((a, b) => parseHistoryDate(a.checked_at) - parseHistoryDate(b.checked_at))
  const labels = []
  const points = []

  for (const entry of asc) {
    labels.push(formatDate(entry.checked_at))
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
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          }).format(Number(value))
        },
      },
    },
  },
}))

function setHistorySort(key) {
  if (historySortKey.value === key) {
    if (historySortDir.value === 'desc') {
      historySortDir.value = 'asc'
    } else {
      historySortDir.value = 'desc'
    }
    return
  }

  historySortKey.value = key
  historySortDir.value = 'desc'
}

function priceDiff(current, previous) {
  return Number(current) - Number(previous)
}

async function loadProduct() {
  await store.fetchProduct(route.params.id)
  product.value = store.currentProduct
}

async function loadHistory() {
  try {
    let days = historyDays.value
    if (historyDays.value === -1) {
      days = null
    }

    const { data } = await getPriceHistory(route.params.id, days)
    historyData.value = data.history || []
    historyStats.value = data.stats || null
  } catch {
    historyData.value = []
    historyStats.value = null
  }
}

watch(historyDays, () => loadHistory())

watch(historyDays, () => {
  showAllHistory.value = false
})

watch(
  () => route.params.id,
  async () => {
    showAllHistory.value = false
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
