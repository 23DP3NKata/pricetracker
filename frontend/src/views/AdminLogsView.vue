<template>
  <v-container class="py-8">
    <div class="d-flex align-center justify-space-between mb-6 ga-3 flex-wrap">
      <h1 class="text-h4 font-weight-bold">{{ $t('adminLogs.title') }}</h1>
      <div class="d-flex ga-2 flex-wrap">
        <v-btn to="/admin/dashboard" rounded="xl" prepend-icon="mdi-shield-account" :variant="isTabActive('admin-dashboard') ? 'flat' : 'tonal'" :color="isTabActive('admin-dashboard') ? 'primary' : undefined">{{ $t('adminCommon.dashboard') }}</v-btn>
        <v-btn to="/admin/users" rounded="xl" prepend-icon="mdi-account-group-outline" :variant="isTabActive('admin-users') ? 'flat' : 'tonal'" :color="isTabActive('admin-users') ? 'primary' : undefined">{{ $t('adminCommon.users') }}</v-btn>
        <v-btn to="/admin/products" rounded="xl" prepend-icon="mdi-package-variant-closed" :variant="isTabActive('admin-products') ? 'flat' : 'tonal'" :color="isTabActive('admin-products') ? 'primary' : undefined">{{ $t('adminCommon.products') }}</v-btn>
        <v-btn to="/admin/logs" rounded="xl" prepend-icon="mdi-text-box-search-outline" :variant="isTabActive('admin-logs') ? 'flat' : 'tonal'" :color="isTabActive('admin-logs') ? 'primary' : undefined">{{ $t('adminCommon.logs') }}</v-btn>
        <v-btn to="/admin/actions" rounded="xl" prepend-icon="mdi-history" :variant="isTabActive('admin-actions') ? 'flat' : 'tonal'" :color="isTabActive('admin-actions') ? 'primary' : undefined">{{ $t('adminCommon.actions') }}</v-btn>
      </div>
    </div>

    <v-card rounded="xl" class="pa-4 mb-4">
      <v-row>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="filters.search"
            :label="$t('adminLogs.searchMessage')"
            variant="outlined"
            clearable
            @keyup.enter="loadLogs(1)"
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-select v-model="filters.level" :label="$t('adminLogs.level')" variant="outlined" :items="levelOptions" clearable />
        </v-col>
        <v-col cols="12" md="2">
          <v-select v-model="filters.category" :label="$t('adminLogs.category')" variant="outlined" :items="categoryOptions" clearable />
        </v-col>
        <v-col cols="12" md="2">
          <v-text-field v-model="filters.from" type="date" :label="$t('adminCommon.from')" variant="outlined" />
        </v-col>
        <v-col cols="12" md="2">
          <v-text-field v-model="filters.to" type="date" :label="$t('adminCommon.to')" variant="outlined" />
        </v-col>
        <v-col cols="12" md="1" class="d-flex align-center">
          <v-btn color="primary" block rounded="xl" :loading="loading" @click="loadLogs(1)">{{ $t('adminCommon.apply') }}</v-btn>
        </v-col>
        <v-col cols="12" md="12" class="d-flex justify-end">
          <v-btn color="secondary" variant="tonal" rounded="xl" prepend-icon="mdi-download" :loading="exporting" @click="downloadCsv">
            {{ $t('adminCommon.exportCsv') }}
          </v-btn>
        </v-col>
      </v-row>
    </v-card>

    <v-card rounded="xl">
      <v-progress-linear v-if="loading" indeterminate color="primary" />

      <v-table>
        <thead>
          <tr>
            <th>
              <v-btn variant="text" size="small" class="px-0" @click="toggleSort('created_at')">
                {{ $t('adminLogs.time') }}
                <v-icon end size="16">{{ sortIcon('created_at') }}</v-icon>
              </v-btn>
            </th>
            <th>
              <v-btn variant="text" size="small" class="px-0" @click="toggleSort('level')">
                {{ $t('adminLogs.level') }}
                <v-icon end size="16">{{ sortIcon('level') }}</v-icon>
              </v-btn>
            </th>
            <th>
              <v-btn variant="text" size="small" class="px-0" @click="toggleSort('category')">
                {{ $t('adminLogs.category') }}
                <v-icon end size="16">{{ sortIcon('category') }}</v-icon>
              </v-btn>
            </th>
            <th>{{ $t('adminLogs.message') }}</th>
            <th>{{ $t('adminLogs.user') }}</th>
            <th>{{ $t('adminLogs.product') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in logs" :key="log.id">
            <td>{{ formatDate(log.created_at) }}</td>
            <td>
              <v-chip size="small" :color="levelColor(log.level)">{{ log.level }}</v-chip>
            </td>
            <td>{{ log.category }}</td>
            <td class="log-message">{{ log.message }}</td>
            <td>{{ log.user?.email || '-' }}</td>
            <td>
              <router-link
                v-if="log.product?.id"
                :to="{ path: '/admin/products', query: { product_id: String(log.product.id) } }"
                class="text-primary text-decoration-none"
              >
                {{ log.product.title || ('Product #' + log.product.id) }}
              </router-link>
              <span v-else>-</span>
            </td>
          </tr>
          <tr v-if="!loading && logs.length === 0">
            <td colspan="6" class="text-center py-8 text-medium-emphasis">{{ $t('adminLogs.noLogsFound') }}</td>
          </tr>
        </tbody>
      </v-table>

      <div class="d-flex justify-center py-4" v-if="pagination.last_page > 1">
        <v-pagination
          v-model="pagination.current_page"
          :length="pagination.last_page"
          @update:model-value="loadLogs"
        />
      </div>
    </v-card>
  </v-container>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { exportAdminLogs, getAdminLogs } from '@/api'

const loading = ref(false)
const exporting = ref(false)
const logs = ref([])
const route = useRoute()

function isTabActive(name) {
  return route.name === name
}

const filters = reactive({
  search: '',
  level: null,
  category: null,
  from: '',
  to: '',
  sort_by: 'created_at',
  sort_dir: 'desc',
})

const levelOptions = ['info', 'warning', 'error', 'critical']
const categoryOptions = ['scraper', 'price_check', 'auth', 'email', 'database', 'api', 'system', 'admin']

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
})

function formatDate(value) {
  if (!value) return '-'
  return new Date(value).toLocaleString(undefined, { timeZone: 'UTC' })
}

function levelColor(level) {
  if (level === 'critical' || level === 'error') return 'error'
  if (level === 'warning') return 'warning'
  if (level === 'info') return 'info'
  return 'default'
}

function toggleSort(field) {
  if (filters.sort_by === field) {
    filters.sort_dir = filters.sort_dir === 'asc' ? 'desc' : 'asc'
  } else {
    filters.sort_by = field
    filters.sort_dir = 'asc'
  }
  loadLogs(1)
}

function sortIcon(field) {
  if (filters.sort_by !== field) return 'mdi-swap-vertical'
  return filters.sort_dir === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down'
}

async function loadLogs(page = 1) {
  loading.value = true
  try {
    const { data } = await getAdminLogs({
      page,
      search: filters.search || undefined,
      level: filters.level || undefined,
      category: filters.category || undefined,
      from: filters.from || undefined,
      to: filters.to || undefined,
      sort_by: filters.sort_by,
      sort_dir: filters.sort_dir,
    })

    logs.value = data.data
    pagination.current_page = data.current_page
    pagination.last_page = data.last_page
    pagination.total = data.total
  } finally {
    loading.value = false
  }
}

async function downloadCsv() {
  exporting.value = true
  try {
    const { data } = await exportAdminLogs({
      search: filters.search || undefined,
      level: filters.level || undefined,
      category: filters.category || undefined,
      from: filters.from || undefined,
      to: filters.to || undefined,
      sort_by: filters.sort_by,
      sort_dir: filters.sort_dir,
    })

    const url = URL.createObjectURL(new Blob([data], { type: 'text/csv;charset=utf-8;' }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `admin-logs-${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(url)
  } finally {
    exporting.value = false
  }
}

onMounted(() => loadLogs(1))
</script>

<style scoped>
.log-message {
  max-width: 560px;
  white-space: normal;
}
</style>
