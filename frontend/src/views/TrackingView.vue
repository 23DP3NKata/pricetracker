<template>
  <v-container class="py-8 tracking-view">
    <div class="d-flex flex-wrap align-center justify-space-between ga-3 mb-4">
      <div>
        <h1 class="text-h4 font-weight-bold mb-1">{{ $t('tracking.title') }}</h1>
        <p class="text-medium-emphasis ma-0">{{ $t('tracking.subtitle') }}</p>
      </div>
      <v-btn color="primary" variant="tonal" rounded="xl" prepend-icon="mdi-refresh" :loading="loading" @click="reloadAll">
        {{ $t('tracking.refresh') }}
      </v-btn>
    </div>

    <v-card rounded="xl" class="pa-4 mb-5">
      <div class="d-flex flex-wrap align-center justify-space-between ga-2 mb-2">
        <div class="text-subtitle-1 font-weight-medium">{{ $t('tracking.monthlyUsage') }}</div>
        <div class="text-body-2">
          <strong>{{ checksUsed }}</strong> / {{ monthlyLimit }}
        </div>
      </div>

      <v-progress-linear
        :model-value="usagePercent"
        :color="usagePercent >= 90 ? 'error' : usagePercent >= 70 ? 'warning' : 'primary'"
        rounded
        height="10"
      />

      <div class="d-flex flex-wrap ga-2 mt-3 text-caption text-medium-emphasis">
        <v-chip size="small" variant="tonal" color="info">{{ $t('tracking.pending') }}: {{ pendingCount }}</v-chip>
        <v-chip size="small" variant="tonal" color="success">{{ $t('tracking.completed') }}: {{ completedCount }}</v-chip>
        <v-chip size="small" variant="tonal" color="grey">{{ $t('tracking.inactive') }}: {{ inactiveCount }}</v-chip>
      </div>
    </v-card>

    <v-alert v-if="error" type="error" variant="tonal" rounded="lg" class="mb-4" closable @click:close="error = null">
      {{ error }}
    </v-alert>

    <div v-if="activeRows.length" class="mb-5">
      <div class="section-header">
        <h2 class="text-subtitle-1 font-weight-bold mb-0">{{ $t('tracking.activeSection') }}</h2>
        <v-chip size="small" color="info" variant="tonal">{{ activeRows.length }}</v-chip>
      </div>

      <v-card rounded="xl" class="list-shell">
      <div class="list-head d-none d-md-grid">
        <div>{{ $t('tracking.asset') }}</div>
        <div>{{ $t('tracking.currentPrice') }}</div>
        <div>{{ $t('tracking.target') }}</div>
        <div>{{ $t('tracking.condition') }}</div>
        <div>{{ $t('tracking.status') }}</div>
        <div>{{ $t('tracking.actions') }}</div>
      </div>

      <div v-for="item in activeRows" :key="item.id" class="list-row">
        <div class="asset-col">
          <v-avatar size="30" color="grey-lighten-4" class="mr-2">
            <v-img v-if="item.image_url" :src="item.image_url" :alt="item.symbol" />
            <span v-else class="text-caption font-weight-bold">{{ item.symbol?.slice(0, 1) }}</span>
          </v-avatar>
          <div>
            <div class="font-weight-bold">{{ item.symbol }}</div>
            <div class="text-caption text-medium-emphasis">{{ item.title }}</div>
          </div>
        </div>

        <div>
          <div class="price-main">{{ formatPrice(item.current_price, item.currency) }}</div>
          <div class="price-sub text-medium-emphasis">{{ formatPriceHint(item.current_price) }}</div>
        </div>

        <div class="target-col">
          <v-text-field
            v-model.number="item.target_price"
            type="number"
            min="0"
            step="0.00000001"
            density="compact"
            variant="outlined"
            hide-details
            class="target-input"
            prepend-inner-icon="mdi-target"
          />
        </div>

        <div class="condition-col">
          <v-select
            v-model="item.notify_when"
            :items="notifyWhenOptions"
            item-title="text"
            item-value="value"
            density="compact"
            variant="outlined"
            hide-details
            class="condition-select"
          />
        </div>

        <div>
          <v-chip size="small" variant="tonal" :color="statusColor(item)">
            {{ statusLabel(item) }}
          </v-chip>
          <div class="text-caption text-medium-emphasis mt-1" v-if="item.last_notified_at">
            {{ $t('tracking.completedAt') }}: {{ formatDate(item.last_notified_at) }}
          </div>
        </div>

        <div class="d-flex ga-2 justify-end actions-col">
          <v-btn size="small" color="primary" variant="elevated" rounded="lg" class="save-btn" :loading="item._saving" @click="saveItem(item)">
            {{ $t('tracking.save') }}
          </v-btn>
          <v-btn size="small" color="error" variant="outlined" rounded="lg" class="remove-btn" :loading="item._deleting" @click="removeItem(item)">
            {{ $t('tracking.remove') }}
          </v-btn>
        </div>
      </div>
      </v-card>
    </div>

    <div v-if="completedRows.length" class="mb-5">
      <div class="section-header">
        <h2 class="text-subtitle-1 font-weight-bold mb-0">{{ $t('tracking.completedSection') }}</h2>
        <v-chip size="small" color="success" variant="tonal">{{ completedRows.length }}</v-chip>
      </div>

      <v-card rounded="xl" class="list-shell completed-shell">
        <div class="list-head d-none d-md-grid">
          <div>{{ $t('tracking.asset') }}</div>
          <div>{{ $t('tracking.currentPrice') }}</div>
          <div>{{ $t('tracking.condition') }}</div>
          <div>{{ $t('tracking.status') }}</div>
        </div>

        <div v-for="item in completedRows" :key="item.id" class="list-row list-row--completed">
          <div class="asset-col">
            <v-avatar size="30" color="grey-lighten-4" class="mr-2">
              <v-img v-if="item.image_url" :src="item.image_url" :alt="item.symbol" />
              <span v-else class="text-caption font-weight-bold">{{ item.symbol?.slice(0, 1) }}</span>
            </v-avatar>
            <div>
              <div class="font-weight-bold">{{ item.symbol }}</div>
              <div class="text-caption text-medium-emphasis">{{ item.title }}</div>
            </div>
          </div>

          <div>
            <div class="price-main">{{ formatPrice(item.current_price, item.currency) }}</div>
            <div class="price-sub text-medium-emphasis">{{ formatPriceHint(item.current_price) }}</div>
          </div>

          <div>
            <v-chip size="small" variant="tonal" color="primary">
              {{ item.notify_when === 'above' ? $t('tracking.conditionAbove') : $t('tracking.conditionBelow') }}
            </v-chip>
          </div>

          <div>
            <v-chip size="small" variant="tonal" :color="statusColor(item)">
              {{ statusLabel(item) }}
            </v-chip>
            <div class="text-caption text-medium-emphasis mt-1" v-if="item.last_notified_at">
              {{ $t('tracking.completedAt') }}: {{ formatDate(item.last_notified_at) }}
            </div>
          </div>

        </div>
      </v-card>
    </div>

    <v-card v-if="!loading && !rows.length" rounded="xl" class="pa-8 text-center">
      <v-icon size="54" color="primary" class="mb-2">mdi-crosshairs-question</v-icon>
      <div class="text-h6 mb-1">{{ $t('tracking.emptyTitle') }}</div>
      <p class="text-medium-emphasis mb-0">{{ $t('tracking.emptySubtitle') }}</p>
    </v-card>
  </v-container>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { getDashboard, getTrackingRules, updateTrackingRule, deleteTrackingRule } from '@/api'

const { t } = useI18n()

const loading = ref(false)
const error = ref(null)
const rows = ref([])
const checksUsed = ref(0)
const monthlyLimit = ref(0)

const notifyWhenOptions = computed(() => [
  { text: t('tracking.conditionBelow'), value: 'below' },
  { text: t('tracking.conditionAbove'), value: 'above' },
])

const usagePercent = computed(() => {
  if (!monthlyLimit.value) return 0
  return Math.min(100, Math.round((checksUsed.value / monthlyLimit.value) * 100))
})

const completedRows = computed(() => rows.value.filter((r) => !!r.last_notified_at))
const activeRows = computed(() => rows.value.filter((r) => !r.last_notified_at))

const completedCount = computed(() => rows.value.filter((r) => !!r.last_notified_at).length)
const inactiveCount = computed(() => rows.value.filter((r) => !r.is_active && !r.last_notified_at).length)
const pendingCount = computed(() => rows.value.filter((r) => r.is_active && !r.last_notified_at).length)

function statusLabel(item) {
  if (item.last_notified_at) return t('tracking.completed')
  if (!item.is_active) return t('tracking.inactive')
  return t('tracking.pending')
}

function statusColor(item) {
  if (item.last_notified_at) return 'success'
  if (!item.is_active) return 'grey'
  return 'info'
}

function formatPrice(price, currency = 'USD') {
  if (price === null || price === undefined || Number.isNaN(Number(price))) return 'N/A'

  const numeric = Number(price)
  const code = String(currency).toUpperCase()
  const fractionDigits = numeric >= 1000 ? 2 : numeric >= 1 ? 4 : 8

  return new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: code,
    minimumFractionDigits: 2,
    maximumFractionDigits: fractionDigits,
  }).format(numeric)
}

function formatPriceHint(price) {
  if (price === null || price === undefined || Number.isNaN(Number(price))) return ' '
  return `≈ ${Number(price).toFixed(8)} USD`
}

function formatDate(value) {
  if (!value) return '-'
  const date = new Date(String(value).replace(' ', 'T') + 'Z')
  if (Number.isNaN(date.getTime())) return '-'
  return date.toLocaleString()
}

async function loadUsage() {
  const { data } = await getDashboard()
  checksUsed.value = Number(data?.checks_used || 0)
  monthlyLimit.value = Number(data?.monthly_limit || 0)
}

async function loadTracking() {
  const { data } = await getTrackingRules({ per_page: 100 })
  const entries = Array.isArray(data?.data) ? data.data : []

  rows.value = entries.map((entry) => {
    const product = entry.product || {}

    return {
      id: entry.id,
      product_id: entry.product_id,
      title: product.title || '-',
      symbol: product.symbol || '-',
      image_url: product.image_url || null,
      current_price: product.current_price,
      currency: product.currency || 'USD',
      target_price: entry.target_price ?? null,
      notify_when: entry.notify_when || 'below',
      is_active: entry.is_active ?? true,
      next_check_at: entry.next_check_at || null,
      last_checked_at: entry.last_checked_at || null,
      last_notified_at: entry.last_notified_at || null,
      _saving: false,
      _deleting: false,
    }
  })
}

async function reloadAll() {
  loading.value = true
  error.value = null
  try {
    await Promise.all([loadUsage(), loadTracking()])
  } catch (e) {
    error.value = e.response?.data?.message || t('tracking.failedLoad')
  } finally {
    loading.value = false
  }
}

async function saveItem(item) {
  const currentPrice = Number(item.current_price)
  const targetPrice = Number(item.target_price)

  if (
    item.notify_when === 'below'
    && !Number.isNaN(targetPrice)
    && !Number.isNaN(currentPrice)
    && targetPrice > currentPrice
  ) {
    error.value = t('tracking.invalidBelowTarget')
    return
  }

  item._saving = true
  try {
    await updateTrackingRule(item.id, {
      target_price: item.target_price === '' || item.target_price === null ? null : Number(item.target_price),
      notify_when: item.notify_when,
      is_active: !!item.is_active,
    })
    await reloadAll()
  } catch (e) {
    error.value = e.response?.data?.message || t('tracking.failedSave')
  } finally {
    item._saving = false
  }
}

async function removeItem(item) {
  item._deleting = true
  try {
    await deleteTrackingRule(item.id)
    await reloadAll()
  } catch (e) {
    error.value = e.response?.data?.message || t('tracking.failedRemove')
  } finally {
    item._deleting = false
  }
}

onMounted(() => {
  reloadAll()
})
</script>

<style scoped>
.tracking-view {
  max-width: 1240px;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin: 0 2px 10px;
}

.list-shell {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.1);
  overflow: hidden;
}

.completed-shell {
  border-color: rgba(var(--v-theme-success), 0.25);
}

.list-head {
  grid-template-columns: minmax(180px, 1.3fr) minmax(130px, 1fr) minmax(150px, 1fr) minmax(140px, 0.9fr) minmax(140px, 0.9fr) minmax(150px, 1fr);
  gap: 10px;
  padding: 12px 16px;
  font-size: 0.8rem;
  font-weight: 700;
  color: rgba(var(--v-theme-on-surface), 0.62);
  text-transform: uppercase;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

.list-row {
  display: grid;
  grid-template-columns: minmax(180px, 1.3fr) minmax(130px, 1fr) minmax(150px, 1fr) minmax(140px, 0.9fr) minmax(140px, 0.9fr) minmax(150px, 1fr);
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.06);
}

.completed-shell .list-head {
  grid-template-columns: minmax(200px, 1.6fr) minmax(170px, 1fr) minmax(160px, 1fr) minmax(180px, 1fr);
}

.completed-shell .list-row {
  grid-template-columns: minmax(200px, 1.6fr) minmax(170px, 1fr) minmax(160px, 1fr) minmax(180px, 1fr);
}

.list-row:last-child {
  border-bottom: 0;
}

.asset-col {
  display: flex;
  align-items: center;
}

.list-row--completed {
  background: rgba(var(--v-theme-success), 0.035);
}

.price-main {
  font-weight: 800;
  font-size: 0.98rem;
  letter-spacing: 0.01em;
}

.price-sub {
  font-size: 0.74rem;
  margin-top: 2px;
}

.target-input :deep(.v-field),
.condition-select :deep(.v-field) {
  border-radius: 12px;
  background: rgba(var(--v-theme-on-surface), 0.02);
}

.save-btn {
  min-width: 78px;
}

.remove-btn {
  min-width: 84px;
}

@media (max-width: 959px) {
  .list-row {
    grid-template-columns: 1fr;
    gap: 8px;
  }
}
</style>
