<template>
  <v-container class="py-8">
    <div class="d-flex align-center justify-space-between mb-6 ga-3 flex-wrap">
      <h1 class="text-h4 font-weight-bold">{{ $t('adminActions.title') }}</h1>
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
        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.search"
            :label="$t('adminActions.searchReason')"
            variant="outlined"
            clearable
            @keyup.enter="loadActions(1)"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-select v-model="filters.action_type" :label="$t('adminActions.actionType')" variant="outlined" :items="actionTypes" clearable />
        </v-col>
        <v-col cols="12" md="2">
          <v-text-field v-model="filters.from" type="date" :label="$t('adminCommon.from')" variant="outlined" />
        </v-col>
        <v-col cols="12" md="2">
          <v-text-field v-model="filters.to" type="date" :label="$t('adminCommon.to')" variant="outlined" />
        </v-col>
        <v-col cols="12" md="1" class="d-flex align-center">
          <v-btn color="primary" block rounded="xl" :loading="loading" @click="loadActions(1)">{{ $t('adminCommon.apply') }}</v-btn>
        </v-col>
        <v-col cols="12" class="d-flex justify-end">
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
                {{ $t('adminActions.time') }}
                <v-icon end size="16">{{ sortIcon('created_at') }}</v-icon>
              </v-btn>
            </th>
            <th>{{ $t('adminActions.admin') }}</th>
            <th>
              <v-btn variant="text" size="small" class="px-0" @click="toggleSort('action_type')">
                {{ $t('adminActions.action') }}
                <v-icon end size="16">{{ sortIcon('action_type') }}</v-icon>
              </v-btn>
            </th>
            <th>{{ $t('adminActions.targetUser') }}</th>
            <th>{{ $t('adminActions.targetProduct') }}</th>
            <th>{{ $t('adminActions.reason') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="action in actions" :key="action.id">
            <td>{{ formatDate(action.created_at) }}</td>
            <td>{{ action.admin?.email || '-' }}</td>
            <td>
              <v-chip size="small" color="primary" variant="tonal">{{ actionTypeLabel(action.action_type) }}</v-chip>
            </td>
            <td>{{ action.target_user?.email || '-' }}</td>
            <td>
              <router-link
                v-if="action.target_product?.id"
                :to="{ path: '/admin/products', query: { product_id: String(action.target_product.id) } }"
                class="text-primary text-decoration-none"
              >
                {{ action.target_product.title || ('Product #' + action.target_product.id) }}
              </router-link>
              <span v-else>-</span>
            </td>
            <td>{{ action.reason || '-' }}</td>
          </tr>
          <tr v-if="!loading && actions.length === 0">
            <td colspan="6" class="text-center py-8 text-medium-emphasis">{{ $t('adminActions.noAdminActionsYet') }}</td>
          </tr>
        </tbody>
      </v-table>

      <div class="d-flex justify-center py-4" v-if="pagination.last_page > 1">
        <v-pagination
          v-model="pagination.current_page"
          :length="pagination.last_page"
          @update:model-value="loadActions"
        />
      </div>
    </v-card>
  </v-container>
</template>

<script setup>
import { onMounted, reactive, ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { exportAdminActions, getAdminActions } from '@/api'

const loading = ref(false)
const exporting = ref(false)
const actions = ref([])
const route = useRoute()
const { t } = useI18n()

function isTabActive(name) {
  return route.name === name
}

const actionTypes = computed(() => [
  { title: t('adminActions.block_user'), value: 'block_user' },
  { title: t('adminActions.unblock_user'), value: 'unblock_user' },
  { title: t('adminActions.delete_user'), value: 'delete_user' },
  { title: t('adminActions.restore_user'), value: 'restore_user' },
  { title: t('adminActions.hide_product'), value: 'hide_product' },
  { title: t('adminActions.delete_product'), value: 'delete_product' },
  { title: t('adminActions.restore_product'), value: 'restore_product' },
  { title: t('adminActions.promote_user'), value: 'promote_user' },
  { title: t('adminActions.demote_user'), value: 'demote_user' },
  { title: t('adminActions.change_user_limit'), value: 'change_user_limit' },
  { title: t('adminActions.change_user_role'), value: 'change_user_role' },
])

const filters = reactive({
  search: '',
  action_type: null,
  from: '',
  to: '',
  sort_by: 'created_at',
  sort_dir: 'desc',
})

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
})

function formatDate(value) {
  if (!value) return '-'
  return new Date(value).toLocaleString(undefined, { timeZone: 'UTC' })
}

function toggleSort(field) {
  if (filters.sort_by === field) {
    filters.sort_dir = filters.sort_dir === 'asc' ? 'desc' : 'asc'
  } else {
    filters.sort_by = field
    filters.sort_dir = 'asc'
  }
  loadActions(1)
}

function sortIcon(field) {
  if (filters.sort_by !== field) return 'mdi-swap-vertical'
  return filters.sort_dir === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down'
}

function actionTypeLabel(type) {
  const key = `adminActions.${type}`
  const translated = t(key)
  return translated === key ? type : translated
}

async function loadActions(page = 1) {
  loading.value = true
  try {
    const { data } = await getAdminActions({
      page,
      search: filters.search || undefined,
      action_type: filters.action_type || undefined,
      from: filters.from || undefined,
      to: filters.to || undefined,
      sort_by: filters.sort_by,
      sort_dir: filters.sort_dir,
    })
    actions.value = data.data
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
    const { data } = await exportAdminActions({
      search: filters.search || undefined,
      action_type: filters.action_type || undefined,
      from: filters.from || undefined,
      to: filters.to || undefined,
      sort_by: filters.sort_by,
      sort_dir: filters.sort_dir,
    })

    const url = URL.createObjectURL(new Blob([data], { type: 'text/csv;charset=utf-8;' }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `admin-actions-${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(url)
  } finally {
    exporting.value = false
  }
}

onMounted(() => loadActions(1))
</script>
