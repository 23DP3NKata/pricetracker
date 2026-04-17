<template>
  <v-container class="py-8 tracking-view">
    <div class="d-flex flex-wrap align-center justify-space-between ga-3 mb-4">
      <div>
        <h1 class="text-h4 font-weight-bold mb-1">{{ $t('tracking.title') }}</h1>
        <p class="text-medium-emphasis ma-0">{{ $t('tracking.subtitle') }}</p>
      </div>
      <v-btn color="primary" variant="text" rounded="xl" prepend-icon="mdi-refresh" :loading="loading" @click="reloadAll">
        {{ $t('tracking.refresh') }}
      </v-btn>
    </div>

    <div class="usage-block mb-5">
      <div class="usage-head">
        <div class="text-subtitle-1 font-weight-bold">{{ $t('tracking.monthlyUsage') }}</div>
        <div class="text-body-2">
          <span class="font-weight-bold">{{ checksUsed }}</span> / {{ monthlyLimit }}
        </div>
      </div>

      <v-progress-linear
        :model-value="usagePercent"
        :color="usageColor()"
        height="8"
      />

      <div class="usage-sub text-caption text-medium-emphasis">
        <span>{{ $t('tracking.activeSection') }}: {{ pendingCount }}</span>
        <span>{{ $t('tracking.completed') }}: {{ completedCount }}</span>
      </div>
    </div>

    <div class="filters-inline mb-5">
      <v-text-field
        v-model="searchQuery"
        :placeholder="$t('tracking.searchPlaceholder')"
        variant="plain"
        density="compact"
        hide-details
        class="filter-field filter-search"
      />

      <v-select
        v-model="statusFilter"
        :items="statusFilterOptions"
        item-title="text"
        item-value="value"
        variant="plain"
        density="compact"
        placeholder="Status"
        persistent-placeholder
        hide-details
        class="filter-field"
      />

      <v-select
        v-model="conditionFilter"
        :items="conditionFilterOptions"
        item-title="text"
        item-value="value"
        variant="plain"
        density="compact"
        placeholder="Condition"
        persistent-placeholder
        hide-details
        class="filter-field"
      />

      <v-select
        v-model="sortBy"
        :items="sortOptions"
        item-title="text"
        item-value="value"
        variant="plain"
        density="compact"
        placeholder="Sort by"
        persistent-placeholder
        hide-details
        class="filter-field"
      />

      <v-btn
        variant="text"
        rounded
        class="reset-filters-btn"
        @click="resetFilters"
      >
        {{ $t('tracking.resetFilters') }}
      </v-btn>
    </div>

    <v-alert v-if="error" type="error" variant="tonal" rounded="lg" class="mb-4" closable @click:close="error = null">
      {{ error }}
    </v-alert>

    <v-alert v-if="successMsg" type="success" variant="tonal" rounded="lg" class="mb-4" closable @click:close="successMsg = null">
      {{ successMsg }}
    </v-alert>

    <v-alert v-if="!loading && rows.length && !filteredRows.length" type="info" variant="tonal" rounded="lg" class="mb-4">
      {{ $t('tracking.noMatches') }}
    </v-alert>

    <div v-if="activeRows.length" class="mb-5">
      <div class="section-header">
        <h2 class="text-subtitle-1 font-weight-bold mb-0 section-title">{{ $t('tracking.activeSection') }}</h2>
        <span class="section-counter">{{ activeRows.length }}</span>
      </div>

      <div class="list-shell">
        <div class="table-scroll">
          <div class="list-head">
            <div class="head-index">#</div>
            <div class="head-coin">Coin</div>
            <div>{{ $t('tracking.currentPrice') }}</div>
            <div>{{ $t('tracking.target') }}</div>
            <div>{{ $t('tracking.condition') }}</div>
            <div class="status-head">{{ $t('tracking.status') }}</div>
            <div class="head-actions">{{ $t('tracking.actions') }}</div>
          </div>

          <div v-for="(item, index) in activeRows" :key="item.id" class="list-row">
            <div class="index-col">{{ index + 1 }}</div>

            <router-link :to="`/products/${item.product_id}`" class="asset-link coin-cell">
              <div class="asset-col">
                <v-avatar size="30" color="grey-lighten-4">
                  <v-img v-if="item.image_url" :src="item.image_url" :alt="item.symbol" />
                  <span v-else class="text-caption font-weight-bold">{{ item.symbol?.slice(0, 1) }}</span>
                </v-avatar>
                <div>
                  <div class="asset-symbol">{{ item.symbol }}</div>
                  <div class="asset-title text-medium-emphasis">{{ item.title }}</div>
                </div>
              </div>
            </router-link>

            <div class="price-col">
              <div class="price-main">{{ formatPrice(item.current_price, item.currency) }}</div>
              <div class="price-sub text-medium-emphasis">{{ formatPriceHint(item.current_price) }}</div>
            </div>

            <div class="target-col">
              <v-text-field
                v-model="item.target_price"
                @update:model-value="(value) => normalizeItemTargetInput(item, value)"
                type="text"
                inputmode="decimal"
                maxlength="18"
                min="0"
                step="0.01"
                density="compact"
                variant="outlined"
                hide-details
                class="modern-input"
                rounded="lg"
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
                class="modern-input"
                rounded="lg"
              />
            </div>

            <div class="status-cell">
              <span class="status-text">{{ statusLabel(item) }}</span>
              <div class="text-caption text-medium-emphasis mt-1" v-if="item.last_notified_at">
                {{ $t('tracking.completedAt') }}: {{ formatDate(item.last_notified_at) }}
              </div>
            </div>

            <div class="actions-col">
              <v-tooltip :text="$t('tracking.save')" location="top">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    size="x-small"
                    variant="plain"
                    class="icon-action-btn"
                    :loading="item._saving"
                    icon="mdi-content-save-outline"
                    :aria-label="$t('tracking.save')"
                    @click="saveItem(item)"
                  />
                </template>
              </v-tooltip>

              <v-tooltip :text="$t('tracking.remove')" location="top">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    size="x-small"
                    variant="plain"
                    class="icon-action-btn"
                    :loading="item._deleting"
                    icon="mdi-trash-can-outline"
                    :aria-label="$t('tracking.remove')"
                    @click="removeItem(item)"
                  />
                </template>
              </v-tooltip>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="completedRows.length" class="mb-5">
      <div class="section-header">
        <h2 class="text-subtitle-1 font-weight-bold mb-0 section-title">{{ $t('tracking.completedSection') }}</h2>
        <span class="section-counter">{{ completedRows.length }}</span>
      </div>

      <div class="list-shell">
        <div class="table-scroll">
          <div class="list-head">
            <div class="head-index">#</div>
            <div class="head-coin">Coin</div>
            <div>{{ $t('tracking.currentPrice') }}</div>
            <div>{{ $t('tracking.target') }}</div>
            <div>{{ $t('tracking.condition') }}</div>
            <div class="status-head">{{ $t('tracking.status') }}</div>
            <div class="head-actions">{{ $t('tracking.actions') }}</div>
          </div>

          <div v-for="(item, index) in completedRows" :key="item.id" class="list-row list-row--completed">
            <div class="index-col">{{ index + 1 }}</div>

            <router-link :to="`/products/${item.product_id}`" class="asset-link coin-cell">
              <div class="asset-col">
                <v-avatar size="30" color="grey-lighten-4">
                  <v-img v-if="item.image_url" :src="item.image_url" :alt="item.symbol" />
                  <span v-else class="text-caption font-weight-bold">{{ item.symbol?.slice(0, 1) }}</span>
                </v-avatar>
                <div>
                  <div class="asset-symbol">{{ item.symbol }}</div>
                  <div class="asset-title text-medium-emphasis">{{ item.title }}</div>
                </div>
              </div>
            </router-link>

            <div class="price-col">
              <div class="price-main">{{ formatPrice(item.current_price, item.currency) }}</div>
              <div class="price-sub text-medium-emphasis">{{ formatPriceHint(item.current_price) }}</div>
            </div>

            <div class="target-col text-body-2 text-medium-emphasis">
              {{ formatPrice(item.target_price, item.currency) }}
            </div>

            <div class="condition-col text-body-2 text-medium-emphasis">
              {{ conditionLabel(item.notify_when) }}
            </div>

            <div class="status-cell">
              <span class="status-text">{{ statusLabel(item) }}</span>
              <div class="text-caption text-medium-emphasis mt-1" v-if="item.last_notified_at">
                {{ $t('tracking.completedAt') }}: {{ formatDate(item.last_notified_at) }}
              </div>
            </div>

            <div class="actions-col">
              <v-tooltip :text="$t('tracking.remove')" location="top">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    size="x-small"
                    variant="plain"
                    class="icon-action-btn"
                    :loading="item._deleting"
                    icon="mdi-trash-can-outline"
                    :aria-label="$t('tracking.remove')"
                    @click="removeItem(item)"
                  />
                </template>
              </v-tooltip>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="!loading && !rows.length" class="empty-state pa-8 text-center">
      <v-icon size="54" color="primary" class="mb-2">mdi-crosshairs-question</v-icon>
      <div class="text-h6 mb-1">{{ $t('tracking.emptyTitle') }}</div>
      <p class="text-medium-emphasis mb-0">{{ $t('tracking.emptySubtitle') }}</p>
    </div>
  </v-container>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { getDashboard, getTrackingRules, updateTrackingRule, deleteTrackingRule } from '@/api'
import { formatCurrencyPrice, formatDecimalPrice, roundToTwo, sanitizePriceInput, toPriceInput } from '@/utils/price'

const { t } = useI18n()

const loading = ref(false)
const error = ref(null)
const successMsg = ref(null)
const rows = ref([])
const checksUsed = ref(0)
const monthlyLimit = ref(0)
const searchQuery = ref('')
const conditionFilter = ref('all')
const statusFilter = ref('all')
const sortBy = ref('symbolAsc')

const notifyWhenOptions = computed(() => [
  { text: t('tracking.conditionBelow'), value: 'below' },
  { text: t('tracking.conditionAbove'), value: 'above' },
])

const conditionFilterOptions = computed(() => [
  { text: t('tracking.conditionAll'), value: 'all' },
  { text: t('tracking.conditionBelow'), value: 'below' },
  { text: t('tracking.conditionAbove'), value: 'above' },
])

const statusFilterOptions = computed(() => [
  { text: t('tracking.statusAll'), value: 'all' },
  { text: t('tracking.activeSection'), value: 'active' },
  { text: t('tracking.completedSection'), value: 'completed' },
])

const sortOptions = computed(() => [
  { text: t('tracking.sortSymbolAsc'), value: 'symbolAsc' },
  { text: t('tracking.sortSymbolDesc'), value: 'symbolDesc' },
  { text: t('tracking.sortCurrentPriceDesc'), value: 'currentPriceDesc' },
  { text: t('tracking.sortCurrentPriceAsc'), value: 'currentPriceAsc' },
  { text: t('tracking.sortTargetPriceDesc'), value: 'targetPriceDesc' },
  { text: t('tracking.sortTargetPriceAsc'), value: 'targetPriceAsc' },
])

const usagePercent = computed(() => {
  if (!monthlyLimit.value) return 0
  return Math.min(100, Math.round((checksUsed.value / monthlyLimit.value) * 100))
})

function usageColor() {
  if (usagePercent.value >= 90) return 'error'
  if (usagePercent.value >= 70) return 'warning'
  return 'primary'
}

function conditionLabel(value) {
  if (value === 'above') return t('tracking.conditionAbove')
  return t('tracking.conditionBelow')
}

const filteredRows = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  const list = []

  for (const item of rows.value) {
    if (query) {
      const symbol = String(item.symbol || '').toLowerCase()
      const title = String(item.title || '').toLowerCase()
      if (!symbol.includes(query) && !title.includes(query)) {
        continue
      }
    }

    if (conditionFilter.value !== 'all' && item.notify_when !== conditionFilter.value) {
      continue
    }

    if (statusFilter.value === 'active' && (!item.is_active || item.last_notified_at)) {
      continue
    }

    if (statusFilter.value === 'completed' && !item.last_notified_at) {
      continue
    }

    list.push(item)
  }

  const numberValue = (value) => {
    const n = Number(value)
    return Number.isNaN(n) ? 0 : n
  }

  if (sortBy.value === 'symbolDesc') {
    list.sort((a, b) => String(b.symbol || '').localeCompare(String(a.symbol || '')))
  } else if (sortBy.value === 'currentPriceDesc') {
    list.sort((a, b) => numberValue(b.current_price) - numberValue(a.current_price))
  } else if (sortBy.value === 'currentPriceAsc') {
    list.sort((a, b) => numberValue(a.current_price) - numberValue(b.current_price))
  } else if (sortBy.value === 'targetPriceDesc') {
    list.sort((a, b) => numberValue(b.target_price) - numberValue(a.target_price))
  } else if (sortBy.value === 'targetPriceAsc') {
    list.sort((a, b) => numberValue(a.target_price) - numberValue(b.target_price))
  } else {
    list.sort((a, b) => String(a.symbol || '').localeCompare(String(b.symbol || '')))
  }

  return list
})

const completedRows = computed(() => {
  const list = []
  for (const row of filteredRows.value) {
    if (row.last_notified_at) {
      list.push(row)
    }
  }
  return list
})

const activeRows = computed(() => {
  const list = []
  for (const row of filteredRows.value) {
    if (row.is_active && !row.last_notified_at) {
      list.push(row)
    }
  }
  return list
})

const completedCount = computed(() => {
  let count = 0
  for (const row of rows.value) {
    if (row.last_notified_at) {
      count += 1
    }
  }
  return count
})

const pendingCount = computed(() => {
  let count = 0
  for (const row of rows.value) {
    if (row.is_active && !row.last_notified_at) {
      count += 1
    }
  }
  return count
})

function statusLabel(item) {
  if (item.last_notified_at) return t('tracking.completed')
  return t('tracking.active')
}

function formatPrice(price, currency = 'USD') {
  return formatCurrencyPrice(price, currency)
}

function formatPriceHint(price) {
  if (roundToTwo(price) === null) return ' '
  return `≈ ${formatDecimalPrice(price)} USD`
}

function normalizeItemTargetInput(item, value) {
  item.target_price = sanitizePriceInput(value, { maxLength: 18, decimals: 2 })
}

function formatDate(value) {
  if (!value) return '-'
  const date = new Date(String(value).replace(' ', 'T') + 'Z')
  if (Number.isNaN(date.getTime())) return '-'
  return date.toLocaleString()
}

function resetFilters() {
  searchQuery.value = ''
  conditionFilter.value = 'all'
  statusFilter.value = 'all'
  sortBy.value = 'symbolAsc'
}

async function loadUsage() {
  const { data } = await getDashboard()
  checksUsed.value = Number(data?.checks_used || 0)
  monthlyLimit.value = Number(data?.monthly_limit || 0)
}

async function loadTracking() {
  const { data } = await getTrackingRules({ per_page: 100 })
  const entries = Array.isArray(data?.data) ? data.data : []
  const list = []

  for (const entry of entries) {
    const product = entry.product || {}

    list.push({
      id: entry.id,
      product_id: entry.product_id,
      title: product.title || '-',
      symbol: product.symbol || '-',
      image_url: product.image_url || null,
      current_price: product.current_price,
      currency: product.currency || 'USD',
      target_price: toPriceInput(entry.target_price),
      notify_when: entry.notify_when || 'below',
      is_active: entry.is_active ?? true,
      next_check_at: entry.next_check_at || null,
      last_checked_at: entry.last_checked_at || null,
      last_notified_at: entry.last_notified_at || null,
      _saving: false,
      _deleting: false,
    })
  }

  rows.value = list
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
  successMsg.value = null
  const currentPrice = Number(item.current_price)
  const targetPrice = roundToTwo(item.target_price)

  if (
    item.notify_when === 'below'
    && targetPrice !== null
    && !Number.isNaN(currentPrice)
    && targetPrice > currentPrice
  ) {
    error.value = t('tracking.invalidBelowTarget')
    return
  }

  item._saving = true
  try {
    const normalizedTargetPrice = roundToTwo(item.target_price)

    await updateTrackingRule(item.id, {
      target_price: normalizedTargetPrice,
      notify_when: item.notify_when,
      is_active: !!item.is_active,
    })
    await reloadAll()
    successMsg.value = t('tracking.saved')
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

.usage-block {
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  padding-bottom: 12px;
}

.usage-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 10px;
}

.usage-sub {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-top: 8px;
}

.filters-inline {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.filter-field {
  min-width: 180px;
  flex: 1 1 180px;
}

.filter-search {
  flex: 1.4 1 260px;
}

.filter-field :deep(.v-field) {
  border-radius: 10px;
  background: rgba(var(--v-theme-on-surface), 0.04);
  padding-inline: 8px;
}

.filter-field :deep(.v-field__outline) {
  display: none;
}

.filter-field :deep(.v-field__input) {
  min-height: 34px;
  padding-top: 6px;
  padding-bottom: 6px;
}

.reset-filters-btn {
  text-transform: none;
  color: rgba(var(--v-theme-on-surface), 0.62);
  padding-inline: 8px;
  min-width: auto;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin: 0 2px 8px;
}

.list-shell {
  --tracking-grid: 40px minmax(200px, 1.5fr) minmax(150px, 1fr) minmax(150px, 1fr) minmax(150px, 1fr) minmax(120px, 0.8fr) minmax(100px, 0.8fr);
  border: 0;
  border-radius: 0;
  box-shadow: none;
  background: rgb(var(--v-theme-surface));
}

.table-scroll {
  overflow-x: auto;
}

.list-head,
.list-row {
  min-width: 1020px;
}

.list-head {
  display: grid;
  grid-template-columns: var(--tracking-grid);
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  font-size: 0.82rem;
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), 0.62);
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  background: rgba(var(--v-theme-on-surface), 0.02);
}

.list-row {
  display: grid;
  grid-template-columns: var(--tracking-grid);
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

.list-row:hover {
  background: rgba(var(--v-theme-on-surface), 0.02);
}

.list-row:last-child {
  border-bottom: 0;
}

.index-col {
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), 0.62);
  text-align: center;
  justify-self: center;
}

.section-counter {
  font-size: 0.82rem;
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), 0.62);
}

.section-title {
  letter-spacing: 0.01em;
}

.asset-col {
  display: flex;
  align-items: center;
  gap: 10px;
}

.asset-link {
  color: inherit;
  text-decoration: none;
  display: flex;
  width: 100%;
  justify-content: center;
  border-radius: 8px;
  padding: 2px 4px;
  margin: -2px -4px;
}

.coin-cell {
  align-self: center;
}

.price-col,
.target-col,
.condition-col {
  align-self: center;
}

.asset-link:hover .asset-symbol,
.asset-link:hover .asset-title {
  color: rgb(var(--v-theme-primary));
}

.asset-symbol {
  font-size: 1rem;
  line-height: 1.1;
  font-weight: 700;
  letter-spacing: 0.01em;
}

.asset-title {
  font-size: 0.82rem;
  margin-top: 2px;
}

.price-main {
  font-weight: 700;
  font-size: 0.95rem;
  letter-spacing: 0.01em;
}

.price-sub {
  font-size: 0.74rem;
  margin-top: 2px;
}

.status-cell {
  text-align: center;
  justify-self: center;
}

.status-head {
  text-align: center;
  justify-self: center;
}

.head-index {
  text-align: center;
  justify-self: center;
}

.head-coin {
  text-align: center;
  justify-self: center;
}

.head-actions {
  text-align: right;
  justify-self: stretch;
}

.status-text {
  display: inline-block;
  font-size: 0.82rem;
  color: rgba(var(--v-theme-on-surface), 0.62);
}

.actions-col {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
}

.icon-action-btn {
  color: rgba(var(--v-theme-on-surface), 0.62);
}

.icon-action-btn:hover {
  color: rgba(var(--v-theme-on-surface), 0.9);
  background: rgba(var(--v-theme-on-surface), 0.05);
}

.modern-input :deep(.v-field) {
  border-radius: 10px;
  background: rgba(var(--v-theme-on-surface), 0.03);
  box-shadow: none;
}

.modern-input :deep(.v-field__outline) {
  color: rgba(var(--v-theme-on-surface), 0.14);
}

.modern-input :deep(.v-field--focused) {
  background: rgba(var(--v-theme-on-surface), 0.04);
}

.list-row--completed {
  background: transparent;
}

.empty-state {
  border-top: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

.list-head {
  text-transform: none;
}

.list-head > *,
.list-row > * {
  align-self: center;
}


@media (max-width: 959px) {
  .filters-inline {
    gap: 8px;
  }

  .filter-field,
  .filter-search {
    flex: 1 1 100%;
    min-width: 100%;
  }

  .usage-sub {
    gap: 10px;
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
