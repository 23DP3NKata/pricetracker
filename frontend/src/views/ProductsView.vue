<template>
  <v-container class="py-8">
    <div class="d-flex align-center justify-space-between mb-6">
      <h1 class="text-h4 font-weight-bold">{{ $t('productsPage.title') }}</h1>
      <v-btn color="primary" rounded="xl" prepend-icon="mdi-plus" @click="showAddDialog = true">
        {{ $t('productsPage.addProduct') }}
      </v-btn>
    </div>

    <v-progress-linear v-if="store.loading" indeterminate color="primary" class="mb-4" />

    <v-card rounded="xl" class="pa-4 mb-4">
      <v-row>
        <v-col cols="12" sm="6" md="4">
          <v-select
            v-model="sort.by"
            :items="sortByOptions"
            :label="$t('productsPage.sortBy')"
            variant="outlined"
            density="comfortable"
            @update:model-value="loadProducts(1)"
          />
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-select
            v-model="sort.dir"
            :items="sortDirOptions"
            :label="$t('productsPage.direction')"
            variant="outlined"
            density="comfortable"
            @update:model-value="loadProducts(1)"
          />
        </v-col>
      </v-row>
    </v-card>

    <!-- Empty state -->
    <v-card v-if="!store.loading && store.products.length === 0" rounded="xl" class="pa-8 text-center">
      <v-icon size="64" color="primary" class="mb-4">mdi-package-variant-plus</v-icon>
      <h3 class="text-h6 mb-2">{{ $t('productsPage.noProductsYet') }}</h3>
      <p class="text-medium-emphasis mb-4">{{ $t('productsPage.startTrackingFirst') }}</p>
      <v-btn color="primary" rounded="xl" prepend-icon="mdi-plus" @click="showAddDialog = true">{{ $t('productsPage.addProduct') }}</v-btn>
    </v-card>

    <!-- Products list -->
    <v-row v-else>
      <v-col v-for="product in store.products" :key="product.id" cols="12" sm="6" md="4" class="d-flex">
        <v-card rounded="xl" class="product-card" :to="`/products/${product.id}`">
          <v-img
            v-if="product.image_url"
            :src="product.image_url"
            height="140"
            cover
            class="bg-grey-lighten-3"
          />
          <v-sheet
            v-else
            height="140"
            class="d-flex align-center justify-center bg-grey-lighten-4"
          >
            <v-icon size="36" color="medium-emphasis">mdi-image-outline</v-icon>
          </v-sheet>
          <v-card-text class="pa-4">
            <div class="d-flex justify-space-between align-start mb-2">
              <h3 class="text-subtitle-1 font-weight-bold" style="line-height: 1.3;">{{ product.title }}</h3>
              <v-chip
                :color="product.pivot?.is_active ? 'success' : 'grey'"
                size="small"
                variant="tonal"
                class="status-chip"
              >
                {{ product.pivot?.is_active ? $t('productsPage.active') : $t('productsPage.paused') }}
              </v-chip>
            </div>

            <div class="text-h5 font-weight-bold primary--text mb-2">
              {{ formatPrice(product.current_price) }}
            </div>

            <div class="text-caption text-medium-emphasis">
              <v-icon size="14">mdi-store</v-icon> {{ product.store_name || $t('productsPage.unknown') }}
            </div>
            <div class="text-caption text-medium-emphasis">
              <v-icon size="14">mdi-clock-outline</v-icon> {{ $t('productsPage.every') }} {{ formatInterval(product.pivot?.check_interval) }}
            </div>
            <div class="text-caption text-medium-emphasis">
              <v-icon size="14">mdi-timer-sand</v-icon> {{ $t('productsPage.sortNextCheck') }}: {{ formatDateTime(product.pivot?.next_check_at) }}
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Pagination -->
    <div v-if="store.pagination.last_page > 1" class="d-flex justify-center mt-6">
      <v-pagination
        v-model="currentPage"
        :length="store.pagination.last_page"
        rounded="circle"
        @update:model-value="loadProducts"
      />
    </div>

    <!-- Add Product Dialog -->
    <v-dialog v-model="showAddDialog" max-width="500">
      <v-card rounded="xl" class="pa-6">
        <h2 class="text-h6 font-weight-bold mb-4">{{ $t('productsPage.dialogTitle') }}</h2>

        <v-alert v-if="addError" type="error" variant="tonal" rounded="lg" class="mb-4" closable @click:close="addError = null">
          {{ addError }}
        </v-alert>

        <v-form @submit.prevent="handleAdd" ref="addFormRef">
          <v-text-field
            v-model="addForm.url"
            :label="$t('productsPage.productUrl')"
            variant="outlined"
            rounded="lg"
            prepend-inner-icon="mdi-link"
            :placeholder="$t('form.urlPlaceholder')"
            :error="addUrlErrors().length > 0"
            :error-messages="addUrlErrors()"
            :disabled="addLoading"
          />

          <v-select
            v-model="addForm.checkInterval"
            :label="$t('productsPage.checkFrequency')"
            variant="outlined"
            rounded="lg"
            prepend-inner-icon="mdi-timer-outline"
            :items="checkIntervalOptions"
            item-title="label"
            item-value="value"
            :disabled="addLoading"
          />

          <div class="text-caption text-medium-emphasis mb-4">
            <v-icon size="14">mdi-information-outline</v-icon>
            {{ $t('productsPage.supportedStores') }}
          </div>

          <div class="d-flex ga-2 justify-end mt-2">
            <v-btn variant="text" rounded="xl" @click="closeAddDialog" :disabled="addLoading">{{ $t('productsPage.cancel') }}</v-btn>
            <v-btn type="submit" color="primary" rounded="xl" :loading="addLoading">
              <v-icon start>mdi-magnify</v-icon> {{ $t('productsPage.fetchTrack') }}
            </v-btn>
          </div>
        </v-form>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useProductsStore } from '@/stores/products'

const store = useProductsStore()
const { t } = useI18n()
const currentPage = ref(1)
const showAddDialog = ref(false)
const addFormRef = ref(null)
const addLoading = ref(false)
const addError = ref(null)
const addSubmitted = ref(false)

const addForm = reactive({
  url: '',
  checkInterval: 1440,
})

const sort = reactive({
  by: 'created_at',
  dir: 'desc',
})

const sortByOptions = computed(() => [
  { title: t('productsPage.sortAddedDate'), value: 'created_at' },
  { title: t('productsPage.sortTitle'), value: 'title' },
  { title: t('productsPage.sortCurrentPrice'), value: 'current_price' },
  { title: t('productsPage.sortStore'), value: 'store_name' },
  { title: t('productsPage.sortNextCheck'), value: 'next_check_at' },
  { title: t('productsPage.sortLastChecked'), value: 'last_checked_at' },
])

const sortDirOptions = computed(() => [
  { title: t('productsPage.sortDesc'), value: 'desc' },
  { title: t('productsPage.sortAsc'), value: 'asc' },
])

const checkIntervalOptions = computed(() => [
  { label: t('form.every30min'), value: 30 },
  { label: t('form.every1h'), value: 60 },
  { label: t('form.every6h'), value: 360 },
  { label: t('form.every12h'), value: 720 },
  { label: t('form.every24h'), value: 1440 },
])

function formatPrice(price) {
  return Number(price).toFixed(2) + ' €'
}

function formatInterval(minutes) {
  if (!minutes) return '24h'
  if (minutes < 60) return minutes + ' min'
  if (minutes < 1440) return Math.round(minutes / 60) + 'h'
  return Math.round(minutes / 1440) + 'd'
}

function formatDateTime(dateStr) {
  if (!dateStr) return t('productDetail.noData')

  // Backend may return UTC without timezone suffix ("YYYY-MM-DD HH:mm:ss").
  // Normalize to ISO UTC so local rendering is correct for the user.
  const hasTimezone = /[zZ]$|[+-]\d{2}:\d{2}$/.test(dateStr)
  const normalized = hasTimezone
    ? dateStr
    : `${dateStr.replace(' ', 'T')}Z`

  const date = new Date(normalized)
  if (Number.isNaN(date.getTime())) return t('productDetail.noData')

  return date.toLocaleString()
}

function addUrlErrors() {
  if (!addSubmitted.value) return []

  const url = addForm.url?.trim() || ''
  if (!url) return [t('productsPage.required')]
  if (!/^https?:\/\/.+/.test(url)) return [t('productsPage.validUrl')]
  return []
}

function closeAddDialog() {
  showAddDialog.value = false
  addError.value = null
  addSubmitted.value = false
  addForm.url = ''
  addForm.checkInterval = 1440
}

async function loadProducts(page = 1) {
  currentPage.value = page
  await store.fetchProducts({
    page,
    sort_by: sort.by,
    sort_dir: sort.dir,
  })
  currentPage.value = store.pagination.current_page
}

async function handleAdd() {
  addSubmitted.value = true
  if (addUrlErrors().length) return

  addLoading.value = true
  addError.value = null
  try {
    await store.addProduct({
      url: addForm.url.trim(),
      check_interval: addForm.checkInterval,
    })
    closeAddDialog()
    await loadProducts(currentPage.value)
  } catch (e) {
    addError.value = e.response?.data?.message || t('productsPage.failedAdd')
  } finally {
    addLoading.value = false
  }
}

onMounted(() => loadProducts(1))
</script>

<style scoped>
.product-card {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  transition: transform 0.2s, box-shadow 0.2s;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

.product-card :deep(.v-card-text) {
  flex: 1;
}

.status-chip {
  min-width: 66px;
  justify-content: center;
  white-space: nowrap;
  flex-shrink: 0;
}

.product-card h3 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.product-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}
</style>
