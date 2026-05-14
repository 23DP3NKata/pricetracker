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

    <v-alert
      v-if="verificationFlash.show"
      :type="verificationFlash.type"
      variant="tonal"
      rounded="lg"
      class="mb-4"
      closable
      @click:close="verificationFlash.show = false"
    >
      {{ verificationFlash.message }}
    </v-alert>

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

      <v-card rounded="lg" class="list-shell crypto-table" v-if="displayAssets.length">
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
            <button type="button" class="head-cell text-right sortable-head d-none d-sm-inline-flex" :class="{ 'head-active': sortBy === 'change24h' }" @click="toggleSort('change24h')">
              {{ $t('dashboard.change24h') }}
              <v-icon size="14" class="sort-icon">{{ sortIcon('change24h') }}</v-icon>
            </button>
            <div class="head-cell d-sm-none">{{ $t('dashboard.action') }}</div>
            <button type="button" class="head-cell text-right sortable-head d-none d-sm-block" :class="{ 'head-active': sortBy === 'change7d' }" @click="toggleSort('change7d')">
              {{ $t('dashboard.change7d') }}
              <v-icon size="14" class="sort-icon">{{ sortIcon('change7d') }}</v-icon>
            </button>
            <div class="head-cell d-none d-sm-block">{{ $t('dashboard.last7Days') }}</div>
            <div class="head-cell d-none d-sm-block">{{ $t('dashboard.action') }}</div>
          </div>

          <div
            v-for="asset in displayAssets"
            :key="asset.id"
            class="list-row"
          >
            <div class="rank-col">
              <span class="rank-badge">{{ asset._rank }}</span>
            </div>

            <router-link :to="`/products/${asset.id}`" class="asset-link">
              <div class="asset-col">
                <v-avatar :size="xs ? 24 : 34" color="grey-lighten-4" class="mr-2">
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

            <div class="change-col text-right d-none d-sm-block">
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

            <div class="action-col d-none d-sm-flex">
              <v-btn
                size="small"
                rounded="lg"
                variant="outlined"
                :color="emailVerified ? 'primary' : 'warning'"
                class="track-btn text-wrap"
                height="auto"
                style="min-height: 36px; padding-top: 6px; padding-bottom: 6px;"
                prepend-icon="mdi-bell-plus"
                :disabled="false"
                @click="openTrackDialog(asset)"
              >
                <span v-html="$t('dashboard.addAlert')"></span>
              </v-btn>
            </div>

            <div class="mobile-track-action d-flex d-sm-none justify-end">
              <v-btn
                size="small"
                rounded="lg"
                variant="outlined"
                :color="emailVerified ? 'primary' : 'warning'"
                class="track-btn text-wrap"
                height="auto"
                style="min-height: 36px; padding-top: 6px; padding-bottom: 6px;"
                prepend-icon="mdi-bell-plus"
                :disabled="false"
                @click.stop="openTrackDialog(asset)"
              >
                <span v-html="$t('dashboard.addAlert')"></span>
              </v-btn>
            </div>
          </div>
    </v-card>

    <div v-if="displayAssets.length" class="crypto-cards">
      <div
        v-for="asset in displayAssets"
        :key="asset.id"
        class="crypto-card"
      >
        <div class="crypto-card-main" @click="goToCoin(asset.id)">
          <div class="coin-left">
            <v-avatar size="36" color="grey-lighten-4" class="coin-avatar">
              <v-img v-if="asset.image_url" :src="asset.image_url" :alt="asset.symbol" />
              <span v-else class="text-caption font-weight-bold">{{ asset.symbol?.slice(0, 1) }}</span>
            </v-avatar>

            <div class="coin-meta">
              <div class="coin-line">
                <span class="coin-symbol">{{ asset.symbol }}</span>
                <span class="coin-name">{{ asset.title }}</span>
              </div>
            </div>
          </div>

          <div class="coin-right">
            <div class="coin-price-row">
              <div class="coin-price">{{ formatPrice(asset.current_price, asset.currency) }}</div>
              <v-btn
                icon="mdi-bell-outline"
                size="small"
                variant="text"
                class="notify-icon-btn"
                :color="emailVerified ? 'primary' : 'warning'"
                :aria-label="$t('dashboard.addAlert')"
                :title="$t('dashboard.addAlert')"
                @click.stop="openTrackDialog(asset)"
              />
            </div>
            <div :class="['coin-change', percentClass(asset._change24h)]">
              {{ formatPercent(asset._change24h) }}
              <span class="coin-period">24h</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="assets.length" class="coingecko-attribution">
      <span class="coingecko-source">{{ $t('dashboard.sourceLabel') }}</span>
      <a
        href="https://www.coingecko.com/"
        target="_blank"
        rel="noopener noreferrer"
        :aria-label="$t('dashboard.sourceAria')"
      >
        <img :src="coingeckoLogoSrc" alt="CoinGecko" class="coingecko-logo" />
      </a>
    </div>

    <TrackingSetupDialog
      v-model="showTrackDialog"
      :asset="selectedAsset"
      @tracked="handleTracked"
    />
  </v-container>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useDisplay, useTheme } from 'vuetify'
import { getTopAssets } from '@/api'
import TrackingSetupDialog from '@/components/TrackingSetupDialog.vue'
import { useAuthStore } from '@/stores/auth'
import { formatCurrencyPrice } from '@/utils/price'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { xs } = useDisplay()
const theme = useTheme()
const auth = useAuthStore()

const loading = ref(false)
const error = ref(null)
const assets = ref([])
const sortBy = ref('rank')
const sortDir = ref('asc')
const lastUpdatedAt = ref(null)
const verificationFlash = ref({
  show: false,
  type: 'success',
  message: '',
})

const showTrackDialog = ref(false)
const selectedAsset = ref(null)

const emailVerified = computed(() => auth.emailVerified)
const isAuthenticated = computed(() => auth.isAuthenticated)

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

const displayAssets = computed(() => uniqueById(sortedAssets.value))

const lastUpdatedLabel = computed(() => {
  if (!lastUpdatedAt.value) return ''

  const date = new Date(lastUpdatedAt.value)
  if (Number.isNaN(date.getTime())) return ''
  return date.toLocaleString()
})

function formatPrice(price, currency = 'USD') {
  return formatCurrencyPrice(price, currency)
}

function formatPercent(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return 'N/A'
  }

  const num = Number(value)
  const sign = num > 0 ? '+' : ''
  return `${sign}${num.toFixed(2)}%`
}

function uniqueById(list) {
  const seen = new Set()
  const result = []

  for (const item of Array.isArray(list) ? list : []) {
    const id = item?.id
    if (seen.has(id)) continue
    seen.add(id)
    result.push(item)
  }

  return result
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

    // If the server did not provide meta.last_updated_at, take the most recent timestamp from the list.
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
  if (!isAuthenticated.value) {
    router.push({ path: '/login', query: { redirect: route.fullPath } })
    return
  }

  if (!emailVerified.value) {
    router.push({ path: '/settings', query: { verify: '1' } })
    return
  }

  selectedAsset.value = asset
  showTrackDialog.value = true
}

function goToCoin(id) {
  if (!id) return
  router.push(`/products/${id}`)
}

async function handleTracked() {
  await loadTopAssets()
}

function applyVerificationFlash() {
  const status = String(route.query.status || '').toLowerCase()
  const verified = String(route.query.verified || '').toLowerCase()

  if (status === 'already_verified') {
    verificationFlash.value = {
      show: true,
      type: 'info',
      message: t('dashboard.alreadyVerifiedBanner'),
    }
  } else if (status === 'verified' || verified === '1' || verified === 'true') {
    verificationFlash.value = {
      show: true,
      type: 'success',
      message: t('dashboard.verificationSuccessBanner'),
    }
  }

  if (route.query.status !== undefined || route.query.verified !== undefined) {
    const nextQuery = { ...route.query }
    delete nextQuery.status
    delete nextQuery.verified
    router.replace({ query: nextQuery })
  }
}

onMounted(() => {
  applyVerificationFlash()
  loadTopAssets()
})
</script>

<style scoped>
.dashboard-crypto {
  max-width: 1180px;
  overflow-x: hidden;
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
  grid-template-columns: 60px minmax(140px, 1.2fr) minmax(120px, 0.9fr) minmax(82px, 0.55fr) minmax(82px, 0.55fr) minmax(82px, 0.55fr) minmax(120px, 0.75fr) minmax(180px, 1.1fr);
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
  grid-template-columns: 60px minmax(140px, 1.2fr) minmax(120px, 0.9fr) minmax(82px, 0.55fr) minmax(82px, 0.55fr) minmax(82px, 0.55fr) minmax(120px, 0.75fr) minmax(180px, 1.1fr);
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
  justify-content: flex-start;
  width: min(100%, 160px);
  text-align: left;
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
  justify-content: flex-start;
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
  white-space: nowrap;
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

.action-col {
  justify-content: center;
}

.track-btn {
  min-width: 105px;
  width: auto;
  min-height: 34px;
  height: auto;
  font-weight: 600;
  text-transform: none;
  white-space: normal;
  transition: all 200ms cubic-bezier(0.4, 0, 0.2, 1);
}

.track-btn--card {
  min-width: 0;
}

.mobile-track-action {
  justify-self: end;
}

.crypto-cards {
  display: none;
}

.crypto-card-link {
  color: inherit;
  text-decoration: none;
}

.crypto-card {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: 10px;
  padding: 12px 16px;
  border-radius: 12px;
  background: var(--card-bg, rgb(var(--v-theme-surface)));
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
}

.crypto-card-main {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  color: inherit;
  text-decoration: none;
}

.notify-icon-btn {
  width: 32px;
  height: 32px;
  min-width: 32px;
  min-height: 32px;
  border-radius: 999px;
  flex: 0 0 auto;
}

.coin-left {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.coin-avatar {
  flex: 0 0 auto;
}

.coin-meta {
  min-width: 0;
}

.crypto-card .coin-line {
  display: flex;
  align-items: baseline;
  gap: 8px;
  min-width: 0;
}

.crypto-card .coin-symbol {
  font-size: 0.92rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  white-space: nowrap;
}

.crypto-card .coin-name {
  font-size: 0.82rem;
  color: rgba(var(--v-theme-on-surface), 0.7);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.coin-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  text-align: right;
  flex: 0 0 auto;
}

.coin-price-row {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
}

.coin-price {
  font-size: 0.98rem;
  font-weight: 700;
  letter-spacing: 0.01em;
}

.coin-change {
  margin-top: 2px;
  font-size: 0.8rem;
  font-weight: 600;
  white-space: nowrap;
}

.coin-period {
  margin-left: 4px;
  color: rgba(var(--v-theme-on-surface), 0.62);
  font-weight: 500;
}

@media (max-width: 959px) {
  .list-head {
    grid-template-columns: 52px minmax(180px, 1.5fr) minmax(100px, 0.9fr) minmax(80px, 0.65fr) minmax(80px, 0.65fr) minmax(120px, 0.8fr) minmax(130px, 1fr);
    gap: 8px;
    padding: 10px 12px;
    font-size: 0.78rem;
  }

  .list-row {
    grid-template-columns: 52px minmax(180px, 1.5fr) minmax(100px, 0.9fr) minmax(80px, 0.65fr) minmax(80px, 0.65fr) minmax(120px, 0.8fr) minmax(130px, 1fr);
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

@media (max-width: 768px) {
  .crypto-table {
    display: none;
  }

  .crypto-cards {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .crypto-card {
    padding: 12px 14px;
  }

  .crypto-card .coin-name {
    max-width: 44vw;
  }
}

@media (min-width: 769px) {
  .crypto-table {
    display: block;
  }

  .crypto-cards {
    display: none;
  }
}

@media (max-width: 599px) {
  .list-head {
    grid-template-columns: 28px minmax(80px, 1fr) minmax(90px, auto) minmax(70px, auto);
    padding: 10px 8px;
    gap: 8px;
  }

  .list-row {
    grid-template-columns: 28px minmax(80px, 1fr) minmax(90px, auto) minmax(70px, auto);
    padding: 10px 8px;
    gap: 8px;
  }

  .price-col {
    min-width: 90px;
  }

  .price-main {
    font-size: 0.95rem;
    font-weight: 600;
  }

  .list-row > .change-col:last-of-type,
  .list-head > .head-cell:last-of-type {
    min-width: 60px;
  }

  .coin-symbol {
    font-size: 0.9rem;
  }

  .rank-badge {
    font-size: 0.78rem;
  }

  .coin-name {
    display: none;
  }
}
</style>
