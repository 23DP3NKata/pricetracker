<template>
  <v-container class="py-8 py-md-10 dashboard-crypto">
    <div class="d-flex flex-wrap align-end justify-space-between mb-4 ga-3">
      <div>
        <p class="text-medium-emphasis ma-0">
          {{ $t('dashboard.topAssetsSubtitle') }}
        </p>
        <p v-if="lastUpdatedLabel" class="text-caption text-medium-emphasis ma-0 mt-1">
          {{ $t('dashboard.lastUpdated') }}: {{ lastUpdatedLabel }}
        </p>
      </div>
      <v-btn
        variant="text"
        rounded
        prepend-icon="mdi-refresh"
        :loading="loading"
        class="dashboard-nav-btn"
        @click="loadTopAssets"
      >
        <span class="d-none d-sm-inline">{{ $t('dashboard.refresh') }}</span>
      </v-btn>
    </div>

    <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-5" />

        <v-alert
          v-if="error"
          type="error"
          variant="tonal"
          rounded="lg"
          class="mb-6"
          closable
          @click:close="error = null"
        >
          {{ error }}
        </v-alert>

        <v-alert
          v-else-if="!loading && assets.length === 0"
          type="info"
          variant="tonal"
          rounded="lg"
          class="mb-6"
          icon="mdi-information-outline"
        >
          {{ $t('dashboard.emptyTopAssetsHint') }}
        </v-alert>

      <v-card rounded="lg" class="list-shell" v-if="assets.length">
          <div class="list-head">
            <button type="button" class="head-cell sortable-head" :class="{ 'head-active': sortBy === 'rank' }" @click="toggleSort('rank')">
              #
              <v-icon size="14" class="sort-icon">{{ sortIcon('rank') }}</v-icon>
            </button>
            <button type="button" class="head-cell sortable-head" :class="{ 'head-active': sortBy === 'coin' }" @click="toggleSort('coin')">
              {{ $t('dashboard.coin') }}
              <v-icon size="14" class="sort-icon">{{ sortIcon('coin') }}</v-icon>
            </button>
            <button type="button" class="head-cell text-right sortable-head" :class="{ 'head-active': sortBy === 'price' }" @click="toggleSort('price')">
              {{ $t('dashboard.price') }}
              <v-icon size="14" class="sort-icon">{{ sortIcon('price') }}</v-icon>
            </button>
            <button type="button" class="head-cell text-right sortable-head col-1h d-none d-sm-block" :class="{ 'head-active': sortBy === 'change1h' }" @click="toggleSort('change1h')">
              {{ $t('dashboard.change1h') }}
              <v-icon size="14" class="sort-icon">{{ sortIcon('change1h') }}</v-icon>
            </button>
            <button type="button" class="head-cell text-right sortable-head" :class="{ 'head-active': sortBy === 'change24h' }" @click="toggleSort('change24h')">
              {{ $t('dashboard.change24h') }}
              <v-icon size="14" class="sort-icon">{{ sortIcon('change24h') }}</v-icon>
            </button>
            <button type="button" class="head-cell text-right sortable-head d-none d-sm-block" :class="{ 'head-active': sortBy === 'change7d' }" @click="toggleSort('change7d')">
              {{ $t('dashboard.change7d') }}
              <v-icon size="14" class="sort-icon">{{ sortIcon('change7d') }}</v-icon>
            </button>
            <div class="head-cell d-none d-sm-block">{{ $t('dashboard.last7Days') }}</div>
          </div>

          <div
            v-for="asset in sortedAssets"
            :key="asset.id"
            class="list-row"
          >
            <div class="rank-col">
              <span class="rank-badge">{{ asset._rank }}</span>
            </div>

            <router-link :to="`/products/${asset.id}`" class="asset-link">
              <div class="asset-col">
                <v-avatar :size="xs ? 26 : 34" color="grey-lighten-4" class="mr-2">
                  <v-img v-if="asset.image_url" :src="asset.image_url" :alt="asset.symbol" />
                  <span v-else class="text-caption font-weight-bold">{{ asset.symbol?.slice(0, 1) }}</span>
                </v-avatar>
                <div>
                  <div class="coin-line font-weight-bold">
                    <span class="coin-name">{{ asset.title }}</span>
                    <span class="coin-symbol text-medium-emphasis">{{ asset.symbol }}</span>
                  </div>
                </div>
              </div>
            </router-link>

            <div class="price-col text-right">
              <div class="price-main">{{ formatPrice(asset.current_price, asset.currency) }}</div>
            </div>

            <div class="change-col text-right col-1h d-none d-sm-block">
              <span :class="['change-text', percentClass(asset._change1h)]">
                {{ formatTrendPercent(asset._change1h) }}
              </span>
            </div>

            <div class="change-col text-right">
              <span :class="['change-text', percentClass(asset._change24h)]">
                {{ formatTrendPercent(asset._change24h) }}
              </span>
            </div>

            <div class="change-col text-right d-none d-sm-block">
              <span :class="['change-text', percentClass(asset._change7d)]">
                {{ formatTrendPercent(asset._change7d) }}
              </span>
            </div>

            <div class="chart-col d-none d-sm-block">
              <svg viewBox="0 0 220 64" preserveAspectRatio="none" class="sparkline" role="img" :aria-label="t('dashboard.chartAria', { symbol: asset.symbol })">
                <line x1="0" y1="32" x2="220" y2="32" class="sparkline-baseline" />
                <polyline
                  :points="sparklinePoints(asset.history)"
                  fill="none"
                  :stroke="sparklineStroke(asset._change24h)"
                  stroke-width="2.6"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <circle
                  v-if="sparklineLastPoint(asset.history)"
                  :cx="sparklineLastPoint(asset.history).x"
                  :cy="sparklineLastPoint(asset.history).y"
                  r="2.8"
                  :fill="sparklineStroke(asset._change24h)"
                />
              </svg>
            </div>
          </div>
    </v-card>

    <div v-if="assets.length" class="coingecko-attribution">
      <span class="coingecko-source">Source:</span>
      <a
        href="https://www.coingecko.com/"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Source: CoinGecko"
      >
        <img :src="coingeckoLogoSrc" alt="CoinGecko" class="coingecko-logo" />
      </a>
    </div>

    <v-dialog v-model="showTrackDialog" max-width="560">
      <v-card rounded="xl" class="pa-6">
        <div class="tracking-setup-head mb-4">
          <v-avatar color="primary" variant="tonal" size="42">
            <v-icon>mdi-tune-variant</v-icon>
          </v-avatar>
          <div>
            <h2 class="text-h5 font-weight-bold mb-1">{{ $t('dashboard.trackingSetupTitle') }}</h2>
            <p class="text-body-2 text-medium-emphasis ma-0">{{ $t('dashboard.trackingSetupSubtitle') }}</p>
          </div>
        </div>

        <v-alert
          v-if="trackError"
          type="error"
          variant="tonal"
          rounded="lg"
          class="mb-4"
          closable
          @click:close="trackError = null"
        >
          {{ $t(trackError) }}
        </v-alert>

        <div v-if="selectedAsset" class="tracking-asset-meta mb-4">
          <div class="text-body-1 font-weight-bold">
            {{ selectedAsset.title }}
            <span class="text-medium-emphasis">({{ selectedAsset.symbol }})</span>
          </div>
          <div class="text-body-2 text-medium-emphasis">
            {{ $t('dashboard.currentMarketPrice') }}:
            <span class="font-weight-bold text-high-emphasis">{{ formatPrice(selectedAsset.current_price, selectedAsset.currency) }}</span>
          </div>
        </div>

        <v-form @submit.prevent="submitTracking">
          <v-text-field
            v-model="trackForm.targetPrice"
            @update:model-value="normalizeTrackTargetInput"
            @keydown="preventPriceInputKeydown"
            @paste="handlePricePaste"
            :label="$t('dashboard.targetPrice')"
            type="text"
            inputmode="decimal"
            maxlength="18"
            counter="18"
            min="0"
            step="0.01"
            rounded="lg"
            variant="outlined"
            prepend-inner-icon="mdi-target"
          />

          <div class="quick-adjust mb-4">
            <div class="text-caption text-medium-emphasis mb-2">{{ $t('dashboard.quickAdjust') }}</div>
            <div class="d-flex flex-wrap ga-2">
              <v-btn
                v-for="percent in quickAdjustPercents"
                :key="percent"
                size="small"
                variant="tonal"
                rounded
                class="quick-adjust-btn"
                @click="applyTargetPercent(percent)"
              >
                {{ percentLabel(percent) }}
              </v-btn>
              <v-btn
                size="small"
                variant="text"
                rounded
                class="quick-adjust-btn"
                @click="setTargetToCurrent"
              >
                {{ $t('dashboard.useCurrentPrice') }}
              </v-btn>
            </div>
            <div class="text-caption text-medium-emphasis mt-2">{{ $t('dashboard.onlyNumbersHint') }}</div>
          </div>

          <v-select
            v-model="trackForm.notifyWhen"
            :items="notifyWhenOptions"
            item-title="text"
            item-value="value"
            :label="$t('dashboard.notifyCondition')"
            rounded="lg"
            variant="outlined"
          />

          <div class="d-flex justify-end ga-2 mt-2">
            <v-btn variant="text" rounded="xl" :disabled="trackLoading" @click="showTrackDialog = false">{{ $t('form.cancel') }}</v-btn>
            <v-btn type="submit" color="primary" rounded="xl" :loading="trackLoading" :disabled="trackLoading" prepend-icon="mdi-bell-ring-outline">
              {{ $t('dashboard.saveTracking') }}
            </v-btn>
          </div>
        </v-form>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useDisplay, useTheme } from 'vuetify'
import { getTopAssets, trackAsset } from '@/api'
import { formatCurrencyPrice, formatDecimalPrice, roundToTwo, sanitizePriceInput, toPriceInput } from '@/utils/price'

const { t } = useI18n()
const { xs } = useDisplay()
const theme = useTheme()

const loading = ref(false)
const error = ref(null)
const assets = ref([])
const sortBy = ref('rank')
const sortDir = ref('asc')
const lastUpdatedAt = ref(null)

const showTrackDialog = ref(false)
const selectedAsset = ref(null)
const trackLoading = ref(false)
const trackError = ref(null)

const trackForm = ref({
  targetPrice: '',
  notifyWhen: 'below',
})

const notifyWhenOptions = computed(() => [
  { text: t('dashboard.conditionDropToTarget'), value: 'below' },
  { text: t('dashboard.conditionRiseToTarget'), value: 'above' },
])

const quickAdjustPercents = computed(() => {
  if (trackForm.value.notifyWhen === 'below') {
    return [-1, -2, -5, -10, -15]
  }

  return [1, 2, 5, 10, 15]
})

const coingeckoLogoSrc = computed(() => {
  if (theme.global.current.value.dark) {
    return '/branding/CGAPI-Lockup-1.svg'
  }

  return '/branding/CGAPI-Lockup.svg'
})

const sortedAssets = computed(() => {
  const source = Array.isArray(assets.value) ? assets.value : []
  const list = []

  for (let i = 0; i < source.length; i += 1) {
    const asset = source[i]
    list.push({
      ...asset,
      _rank: i + 1,
      _change1h: historyChangePercent(asset.history, 1),
      _change24h: historyChangePercent(asset.history, 24),
      _change7d: historyChangePercent(asset.history, 24 * 7),
    })
  }

  const dir = sortDir.value === 'asc' ? 1 : -1

  list.sort((a, b) => {
    if (sortBy.value === 'rank') {
      return (a._rank - b._rank) * dir
    }

    if (sortBy.value === 'coin') {
      const aName = String(a.title || a.symbol || '').toLowerCase()
      const bName = String(b.title || b.symbol || '').toLowerCase()
      if (aName < bName) return -1 * dir
      if (aName > bName) return 1 * dir
      return 0
    }

    if (sortBy.value === 'price') {
      return ((Number(a.current_price) || 0) - (Number(b.current_price) || 0)) * dir
    }

    if (sortBy.value === 'change1h') {
      return ((Number(a._change1h) || 0) - (Number(b._change1h) || 0)) * dir
    }

    if (sortBy.value === 'change24h') {
      return ((Number(a._change24h) || 0) - (Number(b._change24h) || 0)) * dir
    }

    if (sortBy.value === 'change7d') {
      return ((Number(a._change7d) || 0) - (Number(b._change7d) || 0)) * dir
    }

    return 0
  })

  return list
})

const lastUpdatedLabel = computed(() => {
  if (!lastUpdatedAt.value) return ''

  const date = new Date(lastUpdatedAt.value)
  if (Number.isNaN(date.getTime())) return ''
  return date.toLocaleString()
})

function formatPrice(price, currency = 'USD') {
  return formatCurrencyPrice(price, currency)
}

function formatPriceUsdHint(price) {
  if (roundToTwo(price) === null) return ' '
  return `≈ ${formatDecimalPrice(price)} USD`
}

function normalizeTrackTargetInput(value) {
  trackForm.value.targetPrice = sanitizePriceInput(value, { maxLength: 18, decimals: 2 })
}

function preventPriceInputKeydown(event) {
  const { key, ctrlKey, metaKey } = event

  if (ctrlKey || metaKey) return

  const allowedControlKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End']
  if (allowedControlKeys.includes(key)) return

  const isDigit = /^\d$/.test(key)
  const isDecimalSeparator = key === '.' || key === ','

  if (isDigit) return

  if (isDecimalSeparator) {
    const current = String(trackForm.value.targetPrice || '')
    if (!current.includes('.') && !current.includes(',')) return
  }

  event.preventDefault()
}

function handlePricePaste(event) {
  const pasted = event.clipboardData?.getData('text') || ''
  const sanitized = sanitizePriceInput(pasted, { maxLength: 18, decimals: 2 })

  event.preventDefault()
  trackForm.value.targetPrice = sanitized
}

function percentLabel(percent) {
  return `${percent > 0 ? '+' : ''}${percent}%`
}

function applyTargetPercent(percent) {
  if (!selectedAsset.value) return

  const current = Number(selectedAsset.value.current_price)
  if (Number.isNaN(current) || current <= 0) return

  const adjusted = roundToTwo(current * (1 + percent / 100))
  if (adjusted === null || adjusted <= 0) return

  trackForm.value.targetPrice = toPriceInput(adjusted)
}

function setTargetToCurrent() {
  if (!selectedAsset.value) return
  trackForm.value.targetPrice = toPriceInput(selectedAsset.value.current_price)
}

function formatPercent(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return 'N/A'
  }

  const num = Number(value)
  const sign = num > 0 ? '+' : ''
  return `${sign}${num.toFixed(2)}%`
}

function formatTrendPercent(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return 'N/A'
  }

  const num = Number(value)
  if (num > 0) return `▲ ${num.toFixed(2)}%`
  if (num < 0) return `▼ ${Math.abs(num).toFixed(2)}%`
  return `• ${num.toFixed(2)}%`
}

function percentClass(value) {
  const num = Number(value)
  if (Number.isNaN(num)) return 'text-medium-emphasis'
  if (num > 0) return 'text-success'
  if (num < 0) return 'text-error'
  return 'text-medium-emphasis'
}

function toggleSort(column) {
  if (sortBy.value === column) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    return
  }

  sortBy.value = column
  sortDir.value = column === 'coin' ? 'asc' : 'desc'
}

function sortIcon(column) {
  if (sortBy.value !== column) return 'mdi-unfold-more-horizontal'
  return sortDir.value === 'asc' ? 'mdi-chevron-up' : 'mdi-chevron-down'
}

function historyChangePercent(history, hours) {
  const rows = Array.isArray(history) ? history : []
  if (rows.length < 2) return null

  const clean = []
  for (let i = 0; i < rows.length; i += 1) {
    const row = rows[i]
    const price = Number(row?.price)
    const time = new Date(row?.checked_at || '').getTime()

    if (!Number.isNaN(price) && price > 0 && !Number.isNaN(time)) {
      clean.push({ price, time })
    }
  }

  if (clean.length < 2) return null

  const last = clean[clean.length - 1]
  const needTime = last.time - (hours * 60 * 60 * 1000)

  let base = clean[0]
  for (let i = 0; i < clean.length; i += 1) {
    if (clean[i].time <= needTime) {
      base = clean[i]
    } else {
      break
    }
  }

  if (!base || base.price <= 0) return null
  return ((last.price - base.price) / base.price) * 100
}

function sparklineStroke(change) {
  if (change === null || change === undefined || Number.isNaN(Number(change))) return '#9ca3af'
  return Number(change) >= 0 ? '#16a34a' : '#dc2626'
}

function sparklinePoints(history) {
  const rows = Array.isArray(history) ? history : []
  if (!rows.length) return '0,32 220,32'

  const nums = []
  for (let i = 0; i < rows.length; i += 1) {
    const p = Number(rows[i]?.price)
    if (!Number.isNaN(p)) nums.push(p)
  }

  if (!nums.length) return '0,32 220,32'

  const min = Math.min(...nums)
  const max = Math.max(...nums)
  const span = max - min || 1

  const out = []
  for (let i = 0; i < nums.length; i += 1) {
    const x = (i / Math.max(nums.length - 1, 1)) * 220
    const y = 56 - ((nums[i] - min) / span) * 48
    out.push(`${x.toFixed(2)},${y.toFixed(2)}`)
  }

  return out.join(' ')
}

function sparklineLastPoint(history) {
  const rows = Array.isArray(history) ? history : []
  if (!rows.length) return null

  const nums = []
  for (let i = 0; i < rows.length; i += 1) {
    const p = Number(rows[i]?.price)
    if (!Number.isNaN(p)) nums.push(p)
  }

  if (!nums.length) return null

  const min = Math.min(...nums)
  const max = Math.max(...nums)
  const span = max - min || 1

  const lastIndex = nums.length - 1
  const x = (lastIndex / Math.max(lastIndex, 1)) * 220
  const y = 56 - ((nums[lastIndex] - min) / span) * 48
  return { x, y }
}

async function loadTopAssets() {
  loading.value = true
  error.value = null

  try {
    const { data } = await getTopAssets(10)
    assets.value = Array.isArray(data?.data) ? data.data : []

    const fromMeta = data?.meta?.last_updated_at || null
    if (fromMeta) {
      lastUpdatedAt.value = fromMeta
      return
    }

    // Если сервер не дал meta.last_updated_at, берем самое свежее из списка.
    let latest = null
    let latestTime = 0

    for (const asset of assets.value) {
      const raw = asset?.last_updated_at
      if (!raw) continue

      const time = new Date(raw).getTime()
      if (Number.isNaN(time)) continue

      if (!latest || time > latestTime) {
        latest = raw
        latestTime = time
      }
    }

    lastUpdatedAt.value = latest
  } catch (e) {
    error.value = e.response?.data?.message || t('dashboard.failedLoadAssets')
  } finally {
    loading.value = false
  }
}

function openTrackDialog(asset) {
  selectedAsset.value = asset
  trackForm.value = {
    targetPrice: toPriceInput(asset.current_price),
    notifyWhen: 'below',
  }
  trackError.value = null
  showTrackDialog.value = true
}

async function submitTracking() {
  if (!selectedAsset.value || trackLoading.value) return

  const targetPrice = roundToTwo(trackForm.value.targetPrice)

  if (targetPrice === null || targetPrice <= 0) {
    trackError.value = t('dashboard.invalidTargetPrice')
    return
  }

  trackLoading.value = true
  trackError.value = null

  try {
    await trackAsset({
      symbol: selectedAsset.value.symbol,
      target_price: targetPrice,
      notify_when: trackForm.value.notifyWhen,
    })

    showTrackDialog.value = false
    await loadTopAssets()
  } catch (e) {
    trackError.value = e.response?.data?.message || t('dashboard.failedSaveTracking')
  } finally {
    trackLoading.value = false
  }
}

onMounted(() => {
  loadTopAssets()
})
</script>

<style scoped>
.dashboard-crypto {
  max-width: 1180px;
}

.list-shell {
  border: 0 !important;
  border-radius: 10px !important;
  box-shadow: none !important;
  background: rgb(var(--v-theme-surface)) !important;
  overflow: hidden;
}

.list-head {
  display: grid;
  grid-template-columns: 60px minmax(240px, 2fr) minmax(120px, 0.9fr) minmax(82px, 0.55fr) minmax(82px, 0.55fr) minmax(82px, 0.55fr) minmax(180px, 1.1fr);
  gap: 12px;
  padding: 10px 18px;
  font-size: 0.82rem;
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), 0.62);
  text-transform: none;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  position: sticky;
  top: 0;
  z-index: 5;
  background: rgba(var(--v-theme-on-surface), 0.02);
}

.list-head > *,
.list-row > * {
  justify-self: center;
  text-align: center;
}

.list-row {
  display: grid;
  grid-template-columns: 60px minmax(240px, 2fr) minmax(120px, 0.9fr) minmax(82px, 0.55fr) minmax(82px, 0.55fr) minmax(82px, 0.55fr) minmax(180px, 1.1fr);
  align-items: center;
  gap: 12px;
  padding: 14px 18px;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

.list-row:hover {
  background: rgba(var(--v-theme-on-surface), 0.03);
}

.list-row:last-child {
  border-bottom: 0;
}

.rank-badge {
  display: inline-block;
  font-weight: 700;
}

.asset-col {
  display: flex;
  align-items: center;
}

.asset-link {
  color: inherit;
  text-decoration: none;
  display: inline-flex;
  width: 100%;
  justify-content: center;
}

.asset-link:hover .coin-line,
.asset-link:hover .coin-symbol,
.asset-link:hover .text-caption {
  color: rgb(var(--v-theme-primary));
}

.coin-line {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
}

.price-main {
  font-weight: 700;
  font-size: 1rem;
  letter-spacing: 0.01em;
}

.price-sub {
  font-size: 0.76rem;
  margin-top: 2px;
}

.change-text {
  font-size: 0.9rem;
  font-weight: 600;
}

.coingecko-attribution {
  margin-top: 12px;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
}

.coingecko-source {
  font-size: 0.78rem;
  color: rgba(var(--v-theme-on-surface), 0.66);
}

.coingecko-logo {
  display: block;
  height: 20px;
  width: auto;
  opacity: 0.9;
  transition: opacity 0.2s ease;
}

.coingecko-logo:hover {
  opacity: 1;
}

.sparkline-wrap {
  height: 64px;
  border-radius: 12px;
  background: rgba(var(--v-theme-on-surface), 0.04);
  padding: 8px;
}

.sparkline {
  width: 100%;
  height: 64px;
  display: block;
}

.sparkline-baseline {
  stroke: rgba(148, 163, 184, 0.35);
  stroke-width: 1;
  stroke-dasharray: 3 3;
}

.head-cell {
  border: 0;
  background: transparent;
  color: inherit;
  padding: 0;
  text-align: center;
  font: inherit;
}

.sortable-head {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  cursor: pointer;
  user-select: none;
}

.sort-icon {
  opacity: 0.45;
}

.head-active {
  color: rgba(var(--v-theme-on-surface), 0.94);
  font-weight: 700;
}

.head-active .sort-icon {
  opacity: 0.9;
}

.dashboard-nav-btn {
  text-transform: none;
  font-weight: 500;
}

.tracking-setup-head {
  display: flex;
  align-items: center;
  gap: 12px;
}

.tracking-asset-meta {
  border-radius: 12px;
  padding: 10px 12px;
  background: rgba(var(--v-theme-on-surface), 0.035);
}

.quick-adjust-btn {
  text-transform: none;
}

.track-btn {
  min-width: 104px;
  height: 34px;
  transition: color 0.2s ease, background-color 0.2s ease;
}

.track-btn:hover {
  background: rgba(var(--v-theme-on-surface), 0.05) !important;
}

.track-btn--tracked {
  color: rgb(var(--v-theme-primary));
  font-weight: 600;
}

.track-btn--tracked:hover {
  background: rgba(var(--v-theme-primary), 0.1) !important;
}

@media (max-width: 959px) {
  .list-head {
    grid-template-columns: 52px minmax(180px, 1.5fr) minmax(100px, 0.9fr) minmax(80px, 0.65fr) minmax(80px, 0.65fr) minmax(130px, 1fr);
    gap: 8px;
    padding: 10px 12px;
    font-size: 0.78rem;
  }

  .list-row {
    grid-template-columns: 52px minmax(180px, 1.5fr) minmax(100px, 0.9fr) minmax(80px, 0.65fr) minmax(80px, 0.65fr) minmax(130px, 1fr);
    gap: 8px;
    padding: 12px;
  }

  .col-1h {
    display: none;
  }

  .sparkline {
    height: 56px;
  }

  .coingecko-attribution {
    justify-content: flex-start;
  }
}

@media (max-width: 599px) {
  .list-head {
    grid-template-columns: 32px 1fr auto auto;
    padding: 10px 12px;
    gap: 8px;
  }

  .list-row {
    grid-template-columns: 32px 1fr auto auto;
    padding: 10px 12px;
    gap: 8px;
  }

  .rank-badge {
    font-size: 0.78rem;
  }

  .coin-name {
    display: none;
  }
}
</style>
