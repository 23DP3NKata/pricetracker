<template>
  <v-container class="py-8">
    <div class="d-flex align-center justify-space-between mb-6 ga-3 flex-wrap">
      <h1 class="text-h4 font-weight-bold">Admin: Actions</h1>
      <div class="d-flex ga-2 flex-wrap">
        <v-btn to="/admin/dashboard" rounded="xl" prepend-icon="mdi-shield-account" :variant="isTabActive('admin-dashboard') ? 'flat' : 'tonal'" :color="isTabActive('admin-dashboard') ? 'primary' : undefined">Dashboard</v-btn>
        <v-btn to="/admin/users" rounded="xl" prepend-icon="mdi-account-group-outline" :variant="isTabActive('admin-users') ? 'flat' : 'tonal'" :color="isTabActive('admin-users') ? 'primary' : undefined">Users</v-btn>
        <v-btn to="/admin/products" rounded="xl" prepend-icon="mdi-package-variant-closed" :variant="isTabActive('admin-products') ? 'flat' : 'tonal'" :color="isTabActive('admin-products') ? 'primary' : undefined">Products</v-btn>
        <v-btn to="/admin/logs" rounded="xl" prepend-icon="mdi-text-box-search-outline" :variant="isTabActive('admin-logs') ? 'flat' : 'tonal'" :color="isTabActive('admin-logs') ? 'primary' : undefined">Logs</v-btn>
        <v-btn to="/admin/actions" rounded="xl" prepend-icon="mdi-history" :variant="isTabActive('admin-actions') ? 'flat' : 'tonal'" :color="isTabActive('admin-actions') ? 'primary' : undefined">Actions</v-btn>
      </div>
    </div>

    <v-card rounded="xl" class="pa-4 mb-4">
      <v-row>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.search"
            label="Search in reason"
            variant="outlined"
            clearable
            @keyup.enter="loadActions(1)"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-select v-model="filters.action_type" label="Action type" variant="outlined" :items="actionTypes" clearable />
        </v-col>
        <v-col cols="12" md="2">
          <v-text-field v-model="filters.from" type="date" label="From" variant="outlined" />
        </v-col>
        <v-col cols="12" md="2">
          <v-text-field v-model="filters.to" type="date" label="To" variant="outlined" />
        </v-col>
        <v-col cols="12" md="1" class="d-flex align-center">
          <v-btn color="primary" block rounded="xl" :loading="loading" @click="loadActions(1)">Apply</v-btn>
        </v-col>
        <v-col cols="12" class="d-flex justify-end">
          <v-btn color="secondary" variant="tonal" rounded="xl" prepend-icon="mdi-download" :loading="exporting" @click="downloadCsv">
            Export CSV
          </v-btn>
        </v-col>
      </v-row>
    </v-card>

    <v-card rounded="xl">
      <v-progress-linear v-if="loading" indeterminate color="primary" />

      <v-table>
        <thead>
          <tr>
            <th>Time</th>
            <th>Admin</th>
            <th>Action</th>
            <th>Target user</th>
            <th>Target product</th>
            <th>Reason</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="action in actions" :key="action.id">
            <td>{{ formatDate(action.created_at) }}</td>
            <td>{{ action.admin?.email || '-' }}</td>
            <td>
              <v-chip size="small" color="primary" variant="tonal">{{ action.action_type }}</v-chip>
            </td>
            <td>{{ action.target_user?.email || '-' }}</td>
            <td>{{ action.target_product?.title || '-' }}</td>
            <td>{{ action.reason || '-' }}</td>
          </tr>
          <tr v-if="!loading && actions.length === 0">
            <td colspan="6" class="text-center py-8 text-medium-emphasis">No admin actions yet</td>
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
import { onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { exportAdminActions, getAdminActions } from '@/api'

const loading = ref(false)
const exporting = ref(false)
const actions = ref([])
const route = useRoute()

function isTabActive(name) {
  return route.name === name
}

const actionTypes = [
  'block_user',
  'unblock_user',
  'delete_user',
  'restore_user',
  'hide_product',
  'delete_product',
  'restore_product',
  'promote_user',
  'demote_user',
  'change_user_limit',
  'change_user_role',
]

const filters = reactive({
  search: '',
  action_type: null,
  from: '',
  to: '',
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

async function loadActions(page = 1) {
  loading.value = true
  try {
    const { data } = await getAdminActions({
      page,
      search: filters.search || undefined,
      action_type: filters.action_type || undefined,
      from: filters.from || undefined,
      to: filters.to || undefined,
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
