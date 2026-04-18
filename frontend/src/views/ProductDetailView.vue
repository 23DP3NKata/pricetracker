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
          <div class="chart-scroll">
            <div class="chart-inner" :style="{ height: '280px' }">
              <Line :data="chartData" :options="chartOptions" />
            </div>
          </div>
        </div>

        <!-- Price table -->
        <v-table v-if="historyData.length" density="compact">
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
                  <v-chip
                    :color="historyChangeColor(entry.price, historyData[i + 1].price)"
                    size="x-small"
                    variant="tonal"
                  >
                    {{ formatPriceDiff(entry.price, historyData[i + 1].price) }} {{ (product?.currency || 'USD').toUpperCase() }}
                  </v-chip>
                </template>
              </td>
            </tr>
          </tbody>
        </v-table>

        <div v-if="historyData.length" class="d-flex justify-center align-center ga-3 mt-3">
          <v-btn variant="text" rounded="xl" :disabled="historyPage <= 1" @click="historyPage -= 1">
            ← Prev
          </v-btn>
          <div class="text-body-2 text-medium-emphasis">
            {{ $t('productDetail.pageIndicator', { current: historyPagination.current_page, last: historyPagination.last_page }) }}
          </div>
          <v-btn
            variant="text"
            rounded="xl"
            :disabled="historyPage >= historyPagination.last_page"
            @click="historyPage += 1"
          >
            Next →
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
import { useDisplay } from 'vuetify'
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
const { smAndDown } = useDisplay()
const store = useProductsStore()

const product = ref(null)
const historyDays = ref(30)
const historyData = ref([])
const chartHistoryData = ref([])
const historyStats = ref(null)
const historyPage = ref(1)
const historyPagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
})

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
  if (diff > 0) return 'success'
  if (diff < 0) return 'error'
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
        pointRadius: points.length > 100 ? 0 : 3,
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

watch(historyDays, () => {
  if (historyPage.value !== 1) {
    historyPage.value = 1
    return
  }

  loadHistory()
})

watch(historyPage, () => loadHistory())

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
</style>
