<template>
  <v-container class="py-8 py-md-10 dashboard-crypto">
    <div class="d-flex flex-wrap align-end justify-space-between mb-4 ga-3">
      <div>
        <h1 class="text-h4 text-md-h3 font-weight-bold mb-2">{{ $t('dashboard.title') }}</h1>
        <p class="text-medium-emphasis ma-0">
          {{ $t('dashboard.topAssetsSubtitle') }}
        </p>
        <p v-if="lastUpdatedLabel" class="text-caption text-medium-emphasis ma-0 mt-1">
          {{ $t('dashboard.lastUpdated') }}: {{ lastUpdatedLabel }}
        </p>
      </div>
      <v-btn variant="outlined" rounded="xl" prepend-icon="mdi-refresh" :loading="loading" @click="loadTopAssets">
        {{ $t('dashboard.refresh') }}
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

      <v-card rounded="xl" class="list-shell" v-if="assets.length">
          <div class="list-head d-none d-md-grid">
            <div>#</div>
            <div>{{ $t('dashboard.asset') }}</div>
            <div class="text-right">{{ $t('dashboard.lastPrice') }}</div>
            <div class="text-right">{{ $t('dashboard.change24h') }}</div>
            <div>{{ $t('dashboard.miniChart') }}</div>
            <div class="text-right">{{ $t('dashboard.action') }}</div>
          </div>

          <div
            v-for="(asset, index) in assets"
            :key="asset.id"
            class="list-row"
          >
            <div class="rank-col">
              <span class="rank-badge">{{ index + 1 }}</span>
            </div>

            <router-link :to="`/products/${asset.id}`" class="asset-link">
              <div class="asset-col">
                <v-avatar size="34" color="grey-lighten-4" class="mr-2">
                  <v-img v-if="asset.image_url" :src="asset.image_url" :alt="asset.symbol" />
                  <span v-else class="text-caption font-weight-bold">{{ asset.symbol?.slice(0, 1) }}</span>
                </v-avatar>
                <div>
                  <div class="text-subtitle-2 font-weight-bold">{{ asset.symbol }}</div>
                  <div class="text-caption text-medium-emphasis">{{ asset.title }}</div>
                </div>
              </div>
            </router-link>

            <div class="price-col text-md-right">
              <div class="price-main">{{ formatPrice(asset.current_price, asset.currency) }}</div>
              <div class="price-sub text-medium-emphasis">{{ formatPriceUsdHint(asset.current_price) }}</div>
            </div>

            <div class="change-col text-md-right">
              <v-chip size="small" variant="tonal" :color="trendColor(asset.trend)">
                {{ formatPercent(asset.price_change_24h) }}
              </v-chip>
            </div>

            <div class="chart-col">
              <div class="sparkline-wrap">
                <svg viewBox="0 0 220 64" preserveAspectRatio="none" class="sparkline" role="img" :aria-label="t('dashboard.chartAria', { symbol: asset.symbol })">
                  <polyline
                    :points="sparklinePoints(asset.history)"
                    fill="none"
                    :stroke="sparklineStroke(asset.price_change_24h)"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>
              </div>
            </div>

            <div class="action-col text-md-right">
              <v-btn
                size="small"
                rounded="xl"
                :variant="asset.is_tracked ? 'outlined' : 'tonal'"
                color="primary"
                :prepend-icon="asset.is_tracked ? 'mdi-check-circle-outline' : 'mdi-crosshairs-gps'"
                @click="openTrackDialog(asset)"
              >
                {{ asset.is_tracked ? $t('dashboard.tracked') : $t('dashboard.track') }}
              </v-btn>
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

    <v-dialog v-model="showTrackDialog" max-width="520">
      <v-card rounded="xl" class="pa-6">
        <h2 class="text-h6 font-weight-bold mb-4">{{ $t('dashboard.trackingSetupTitle') }}</h2>

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

        <div v-if="selectedAsset" class="text-body-2 mb-4">
          <span class="font-weight-bold">{{ selectedAsset.title }}</span>
          <span class="text-medium-emphasis"> ({{ selectedAsset.symbol }})</span>
        </div>

        <v-form @submit.prevent="submitTracking">
          <v-text-field
            v-model="trackForm.targetPrice"
            @update:model-value="normalizeTrackTargetInput"
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
import { useTheme } from 'vuetify'
import { getTopAssets, trackAsset } from '@/api'
import { formatCurrencyPrice, formatDecimalPrice, roundToTwo, sanitizePriceInput, toPriceInput } from '@/utils/price'

const { t } = useI18n()
const theme = useTheme()

const loading = ref(false)
const error = ref(null)
const assets = ref([])
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
  { text: t('dashboard.conditionBelow'), value: 'below' },
  { text: t('dashboard.conditionAbove'), value: 'above' },
])

const coingeckoLogoSrc = computed(() => (
  theme.global.current.value.dark
    ? '/branding/CGAPI-Lockup-1.svg'
    : '/branding/CGAPI-Lockup.svg'
))

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

function formatPercent(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return 'N/A'
  }

  const num = Number(value)
  const sign = num > 0 ? '+' : ''
  return `${sign}${num.toFixed(2)}%`
}

function trendColor(trend) {
  if (trend === 'up') return 'success'
  if (trend === 'down') return 'error'
  return 'grey'
}

function sparklineStroke(change) {
  if (change === null || change === undefined || Number.isNaN(Number(change))) return '#9ca3af'
  return Number(change) >= 0 ? '#16a34a' : '#dc2626'
}

function sparklinePoints(history) {
  const rows = Array.isArray(history) ? history : []
  if (!rows.length) {
    return '0,32 220,32'
  }

  const values = rows.map((entry) => Number(entry.price)).filter((n) => !Number.isNaN(n))
  if (!values.length) {
    return '0,32 220,32'
  }

  const min = Math.min(...values)
  const max = Math.max(...values)
  const span = max - min || 1

  return values
    .map((price, index) => {
      const x = (index / Math.max(values.length - 1, 1)) * 220
      const y = 56 - ((price - min) / span) * 48
      return `${x.toFixed(2)},${y.toFixed(2)}`
    })
    .join(' ')
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

    const perAssetTimes = assets.value
      .map((asset) => asset?.last_updated_at || null)
      .filter(Boolean)
      .sort()

    lastUpdatedAt.value = perAssetTimes.length ? perAssetTimes[perAssetTimes.length - 1] : null
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
  border: 1px solid rgba(var(--v-theme-on-surface), 0.1);
  overflow: hidden;
}

.list-head {
  grid-template-columns: 72px minmax(210px, 1.6fr) minmax(160px, 1fr) minmax(120px, 0.7fr) minmax(160px, 1.1fr) minmax(160px, 0.9fr);
  gap: 12px;
  padding: 12px 18px;
  font-size: 0.82rem;
  font-weight: 700;
  color: rgba(var(--v-theme-on-surface), 0.62);
  text-transform: uppercase;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

.list-row {
  display: grid;
  grid-template-columns: 72px minmax(210px, 1.6fr) minmax(160px, 1fr) minmax(120px, 0.7fr) minmax(160px, 1.1fr) minmax(160px, 0.9fr);
  align-items: center;
  gap: 12px;
  padding: 14px 18px;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.06);
}

.list-row:last-child {
  border-bottom: 0;
}

.rank-badge {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  background: rgba(var(--v-theme-primary), 0.14);
  color: rgb(var(--v-theme-primary));
}

.asset-col {
  display: flex;
  align-items: center;
}

.asset-link {
  color: inherit;
  text-decoration: none;
  display: inline-flex;
  width: fit-content;
}

.asset-link:hover .text-subtitle-2,
.asset-link:hover .text-caption {
  color: rgb(var(--v-theme-primary));
}

.price-main {
  font-weight: 800;
  font-size: 1rem;
  letter-spacing: 0.01em;
}

.price-sub {
  font-size: 0.76rem;
  margin-top: 2px;
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
  height: 56px;
  border-radius: 12px;
  background: rgba(var(--v-theme-on-surface), 0.04);
  padding: 8px;
}

.sparkline {
  width: 100%;
  height: 100%;
  display: block;
}

@media (max-width: 959px) {
  .list-row {
    grid-template-columns: 52px 1fr;
    gap: 10px;
  }

  .price-col,
  .change-col,
  .chart-col,
  .action-col {
    grid-column: 2;
  }

  .action-col {
    margin-top: 4px;
  }

  .sparkline-wrap {
    height: 48px;
  }

  .coingecko-attribution {
    justify-content: flex-start;
  }
}
</style>
