<template>
  <v-container class="py-8">
    <div class="d-flex align-center justify-space-between mb-6 ga-3 flex-wrap">
      <h1 class="text-h4 font-weight-bold">{{ $t('adminProducts.title') }}</h1>
      <div class="d-flex ga-2 flex-wrap">
        <v-btn to="/admin/dashboard" rounded="xl" prepend-icon="mdi-shield-account" :variant="isTabActive('admin-dashboard') ? 'flat' : 'tonal'" :color="isTabActive('admin-dashboard') ? 'primary' : undefined">{{ $t('adminCommon.dashboard') }}</v-btn>
        <v-btn to="/admin/users" rounded="xl" prepend-icon="mdi-account-group-outline" :variant="isTabActive('admin-users') ? 'flat' : 'tonal'" :color="isTabActive('admin-users') ? 'primary' : undefined">{{ $t('adminCommon.users') }}</v-btn>
        <v-btn to="/admin/products" rounded="xl" prepend-icon="mdi-package-variant-closed" :variant="isTabActive('admin-products') ? 'flat' : 'tonal'" :color="isTabActive('admin-products') ? 'primary' : undefined">{{ $t('adminCommon.products') }}</v-btn>
        <v-btn to="/admin/logs" rounded="xl" prepend-icon="mdi-text-box-search-outline" :variant="isTabActive('admin-logs') ? 'flat' : 'tonal'" :color="isTabActive('admin-logs') ? 'primary' : undefined">{{ $t('adminCommon.logs') }}</v-btn>
        <v-btn to="/admin/actions" rounded="xl" prepend-icon="mdi-history" :variant="isTabActive('admin-actions') ? 'flat' : 'tonal'" :color="isTabActive('admin-actions') ? 'primary' : undefined">{{ $t('adminCommon.actions') }}</v-btn>
      </div>
    </div>

    <v-card v-if="activeProductId" rounded="xl" class="pa-3 mb-4" color="info" variant="tonal">
      <div class="d-flex align-center justify-space-between ga-3 flex-wrap">
        <div class="text-body-2">
          {{ $t('adminProducts.showingProductId') }} <strong>#{{ activeProductId }}</strong>
        </div>
        <v-btn size="small" variant="text" @click="clearProductIdFilter">{{ $t('adminProducts.clear') }}</v-btn>
      </div>
    </v-card>

    <v-card rounded="xl" class="pa-4 mb-4">
      <v-row>
        <v-col cols="12" md="5">
          <v-text-field
            v-model="filters.search"
            :label="$t('adminProducts.searchTitleUrl')"
            variant="outlined"
            clearable
            @keyup.enter="loadProducts(1)"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-select v-model="filters.status" :label="$t('adminProducts.status')" variant="outlined" :items="statusOptions" clearable />
        </v-col>
        <v-col cols="12" md="2">
          <v-text-field v-model="filters.store_name" :label="$t('adminProducts.store')" variant="outlined" clearable />
        </v-col>
        <v-col cols="12" md="2" class="d-flex align-center">
          <v-btn color="primary" block rounded="xl" :loading="loading" @click="loadProducts(1)">{{ $t('adminCommon.apply') }}</v-btn>
        </v-col>
      </v-row>
    </v-card>

    <v-card rounded="xl">
      <v-progress-linear v-if="loading" indeterminate color="primary" />
      <v-table>
        <thead>
          <tr>
            <th>
              <v-btn variant="text" size="small" class="px-0" @click="toggleSort('id')">
                {{ $t('adminProducts.id') }}
                <v-icon end size="16">{{ sortIcon('id') }}</v-icon>
              </v-btn>
            </th>
            <th>
              <v-btn variant="text" size="small" class="px-0" @click="toggleSort('title')">
                {{ $t('adminProducts.product') }}
                <v-icon end size="16">{{ sortIcon('title') }}</v-icon>
              </v-btn>
            </th>
            <th>
              <v-btn variant="text" size="small" class="px-0" @click="toggleSort('store_name')">
                {{ $t('adminProducts.store') }}
                <v-icon end size="16">{{ sortIcon('store_name') }}</v-icon>
              </v-btn>
            </th>
            <th>
              <v-btn variant="text" size="small" class="px-0" @click="toggleSort('status')">
                {{ $t('adminProducts.status') }}
                <v-icon end size="16">{{ sortIcon('status') }}</v-icon>
              </v-btn>
            </th>
            <th>
              <v-btn variant="text" size="small" class="px-0" @click="toggleSort('current_price')">
                {{ $t('adminProducts.price') }}
                <v-icon end size="16">{{ sortIcon('current_price') }}</v-icon>
              </v-btn>
            </th>
            <th>
              <v-btn variant="text" size="small" class="px-0" @click="toggleSort('tracking_count')">
                {{ $t('adminProducts.trackingCount') }}
                <v-icon end size="16">{{ sortIcon('tracking_count') }}</v-icon>
              </v-btn>
            </th>
            <th class="text-right">{{ $t('adminProducts.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in products" :key="p.id">
            <td>{{ p.id }}</td>
            <td>
              <div class="font-weight-medium">{{ p.title }}</div>
              <a :href="p.product_page_url || p.url" target="_blank" class="text-caption">{{ $t('adminProducts.openPage') }}</a>
            </td>
            <td>{{ p.store_name || '-' }}</td>
            <td>
              <v-chip size="small" :color="statusColor(p.status)">{{ statusLabel(p.status) }}</v-chip>
            </td>
            <td>{{ p.current_price !== null ? Number(p.current_price).toFixed(2) + ' ' + (p.currency || '') : '-' }}</td>
            <td>{{ p.tracking_count }}</td>
            <td class="text-right">
              <v-menu location="bottom end">
                <template #activator="{ props }">
                  <v-btn icon="mdi-dots-vertical" variant="text" v-bind="props" />
                </template>
                <v-list density="compact">
                  <v-list-item @click="setStatus(p, 'active')"><v-list-item-title>{{ $t('adminProducts.setActive') }}</v-list-item-title></v-list-item>
                  <v-list-item @click="setStatus(p, 'hidden')"><v-list-item-title>{{ $t('adminProducts.setHidden') }}</v-list-item-title></v-list-item>
                  <v-list-item @click="setStatus(p, 'deleted')"><v-list-item-title>{{ $t('adminProducts.setDeleted') }}</v-list-item-title></v-list-item>
                </v-list>
              </v-menu>
            </td>
          </tr>
          <tr v-if="!loading && products.length === 0">
            <td colspan="7" class="text-center py-8 text-medium-emphasis">{{ $t('adminProducts.noProductsFound') }}</td>
          </tr>
        </tbody>
      </v-table>

      <div class="d-flex justify-center py-4" v-if="pagination.last_page > 1">
        <v-pagination v-model="pagination.current_page" :length="pagination.last_page" @update:model-value="loadProducts" />
      </div>
    </v-card>
  </v-container>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { getAdminProducts, updateAdminProductStatus } from '@/api'

const loading = ref(false)
const saving = ref(false)
const products = ref([])
const route = useRoute()
const router = useRouter()
const { t } = useI18n()

function isTabActive(name) {
  return route.name === name
}

const filters = reactive({
  search: '',
  status: null,
  store_name: '',
  sort_by: 'id',
  sort_dir: 'desc',
})

const statusOptions = computed(() => [
  { title: t('adminProducts.active'), value: 'active' },
  { title: t('adminProducts.hidden'), value: 'hidden' },
  { title: t('adminProducts.deleted'), value: 'deleted' },
])

const activeProductId = computed(() => {
  const raw = route.query.product_id
  const value = Number(Array.isArray(raw) ? raw[0] : raw)
  return Number.isInteger(value) && value > 0 ? value : null
})

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
})

function statusColor(status) {
  if (status === 'active') return 'success'
  if (status === 'hidden') return 'warning'
  if (status === 'deleted') return 'error'
  return 'default'
}

function statusLabel(status) {
  if (status === 'active') return t('adminProducts.active')
  if (status === 'hidden') return t('adminProducts.hidden')
  if (status === 'deleted') return t('adminProducts.deleted')
  return status
}

function toggleSort(field) {
  if (filters.sort_by === field) {
    filters.sort_dir = filters.sort_dir === 'asc' ? 'desc' : 'asc'
  } else {
    filters.sort_by = field
    filters.sort_dir = 'asc'
  }
  loadProducts(1)
}

function sortIcon(field) {
  if (filters.sort_by !== field) return 'mdi-swap-vertical'
  return filters.sort_dir === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down'
}

async function clearProductIdFilter() {
  await router.push({ path: '/admin/products' })
  await loadProducts(1)
}

async function loadProducts(page = 1) {
  loading.value = true
  try {
    const { data } = await getAdminProducts({
      page,
      product_id: activeProductId.value || undefined,
      search: filters.search || undefined,
      status: filters.status || undefined,
      store_name: filters.store_name || undefined,
      sort_by: filters.sort_by,
      sort_dir: filters.sort_dir,
    })

    products.value = data.data
    pagination.current_page = data.current_page
    pagination.last_page = data.last_page
    pagination.total = data.total
  } finally {
    loading.value = false
  }
}

async function setStatus(product, status) {
  if (product.status === status) return
  saving.value = true
  try {
    await updateAdminProductStatus(product.id, { status })
    await loadProducts(pagination.current_page)
  } finally {
    saving.value = false
  }
}

onMounted(() => loadProducts(1))
</script>
