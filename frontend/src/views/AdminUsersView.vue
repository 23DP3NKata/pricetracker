<template>
  <v-container class="py-8">
    <div class="d-flex align-center justify-space-between mb-6 ga-3 flex-wrap">
      <h1 class="text-h4 font-weight-bold">{{ $t('adminUsers.title') }}</h1>
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
            :label="$t('adminUsers.searchNameEmail')"
            variant="outlined"
            density="comfortable"
            clearable
            @keyup.enter="loadUsers(1)"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-select
            v-model="filters.status"
            :label="$t('adminUsers.status')"
            variant="outlined"
            density="comfortable"
            :items="statusOptions"
            clearable
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-select
            v-model="filters.role"
            :label="$t('adminUsers.role')"
            variant="outlined"
            density="comfortable"
            :items="roleOptions"
            clearable
          />
        </v-col>
        <v-col cols="12" md="2" class="d-flex align-center">
          <v-btn color="primary" block rounded="xl" :loading="loading" @click="loadUsers(1)">{{ $t('adminCommon.apply') }}</v-btn>
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
                {{ $t('adminUsers.id') }}
                <v-icon end size="16">{{ sortIcon('id') }}</v-icon>
              </v-btn>
            </th>
            <th>
              <v-btn variant="text" size="small" class="px-0" @click="toggleSort('name')">
                {{ $t('adminUsers.user') }}
                <v-icon end size="16">{{ sortIcon('name') }}</v-icon>
              </v-btn>
            </th>
            <th>
              <v-btn variant="text" size="small" class="px-0" @click="toggleSort('role')">
                {{ $t('adminUsers.role') }}
                <v-icon end size="16">{{ sortIcon('role') }}</v-icon>
              </v-btn>
            </th>
            <th>
              <v-btn variant="text" size="small" class="px-0" @click="toggleSort('status')">
                {{ $t('adminUsers.status') }}
                <v-icon end size="16">{{ sortIcon('status') }}</v-icon>
              </v-btn>
            </th>
            <th>
              <v-btn variant="text" size="small" class="px-0" @click="toggleSort('monthly_limit')">
                {{ $t('adminUsers.monthlyLimit') }}
                <v-icon end size="16">{{ sortIcon('monthly_limit') }}</v-icon>
              </v-btn>
            </th>
            <th>
              <v-btn variant="text" size="small" class="px-0" @click="toggleSort('checks_used')">
                {{ $t('adminUsers.checksUsed') }}
                <v-icon end size="16">{{ sortIcon('checks_used') }}</v-icon>
              </v-btn>
            </th>
            <th class="text-right">{{ $t('adminUsers.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in users" :key="u.id">
            <td>{{ u.id }}</td>
            <td>
              <div class="font-weight-medium">{{ u.name }}</div>
              <div class="text-caption text-medium-emphasis">{{ u.email }}</div>
            </td>
            <td>
              <v-chip size="small" :color="u.role === 'admin' ? 'primary' : 'default'">{{ roleLabel(u.role) }}</v-chip>
            </td>
            <td>
              <v-chip size="small" :color="u.status === 'active' ? 'success' : 'warning'">{{ statusLabel(u.status) }}</v-chip>
            </td>
            <td>{{ u.monthly_limit }}</td>
            <td>{{ u.checks_used }}</td>
            <td class="text-right">
              <v-menu location="bottom end">
                <template #activator="{ props }">
                  <v-btn icon="mdi-dots-vertical" variant="text" v-bind="props" />
                </template>
                <v-list density="compact">
                  <v-list-item @click="openStatusDialog(u)">
                    <v-list-item-title>{{ u.status === 'active' ? $t('adminUsers.blockUser') : $t('adminUsers.unblockUser') }}</v-list-item-title>
                  </v-list-item>
                  <v-list-item @click="toggleRole(u)">
                    <v-list-item-title>{{ u.role === 'admin' ? $t('adminUsers.setAsUser') : $t('adminUsers.setAsAdmin') }}</v-list-item-title>
                  </v-list-item>
                  <v-list-item @click="openLimitDialog(u)">
                    <v-list-item-title>{{ $t('adminUsers.changeMonthlyLimit') }}</v-list-item-title>
                  </v-list-item>
                </v-list>
              </v-menu>
            </td>
          </tr>
          <tr v-if="!loading && users.length === 0">
            <td colspan="7" class="text-center py-8 text-medium-emphasis">{{ $t('adminUsers.noUsersFound') }}</td>
          </tr>
        </tbody>
      </v-table>

      <div class="d-flex justify-center py-4" v-if="pagination.last_page > 1">
        <v-pagination
          v-model="pagination.current_page"
          :length="pagination.last_page"
          @update:model-value="loadUsers"
        />
      </div>
    </v-card>

    <v-dialog v-model="limitDialog.open" max-width="420">
      <v-card rounded="xl" class="pa-5">
        <h3 class="text-h6 mb-4">{{ $t('adminUsers.setMonthlyLimit') }}</h3>
        <div class="text-body-2 mb-4">{{ limitDialog.user?.name }} ({{ limitDialog.user?.email }})</div>
        <v-text-field
          v-model.number="limitDialog.value"
          type="number"
          min="0"
          variant="outlined"
          :label="$t('adminUsers.monthlyLimit')"
        />
        <div class="d-flex justify-end ga-2 mt-2">
          <v-btn variant="text" @click="limitDialog.open = false">{{ $t('adminCommon.cancel') }}</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveLimit">{{ $t('adminCommon.save') }}</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <v-dialog v-model="statusDialog.open" max-width="460">
      <v-card rounded="xl" class="pa-5">
        <h3 class="text-h6 mb-4">{{ statusDialog.nextStatus === 'blocked' ? $t('adminUsers.blockUser') : $t('adminUsers.unblockUser') }}</h3>
        <div class="text-body-2 mb-4">{{ statusDialog.user?.name }} ({{ statusDialog.user?.email }})</div>
        <v-textarea
          v-model="statusDialog.reason"
          :label="$t('adminCommon.reason')"
          variant="outlined"
          rows="3"
          auto-grow
          :placeholder="$t('adminUsers.reasonPlaceholder')"
        />
        <div class="d-flex justify-end ga-2 mt-2">
          <v-btn variant="text" @click="statusDialog.open = false">{{ $t('adminCommon.cancel') }}</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveStatus">{{ $t('adminCommon.save') }}</v-btn>
        </div>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { onMounted, reactive, ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { getAdminUsers, updateAdminUserLimit, updateAdminUserRole, updateAdminUserStatus } from '@/api'

const loading = ref(false)
const saving = ref(false)
const users = ref([])
const route = useRoute()
const { t } = useI18n()

function isTabActive(name) {
  return route.name === name
}

const filters = reactive({
  search: '',
  status: null,
  role: null,
  sort_by: 'id',
  sort_dir: 'desc',
})

const statusOptions = computed(() => [
  { title: t('adminUsers.active'), value: 'active' },
  { title: t('adminUsers.blocked'), value: 'blocked' },
])

const roleOptions = computed(() => [
  { title: t('adminUsers.userRole'), value: 'user' },
  { title: t('adminUsers.adminRole'), value: 'admin' },
])

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
})

const limitDialog = reactive({
  open: false,
  user: null,
  value: 0,
})

const statusDialog = reactive({
  open: false,
  user: null,
  nextStatus: 'active',
  reason: '',
})

function toggleSort(field) {
  if (filters.sort_by === field) {
    filters.sort_dir = filters.sort_dir === 'asc' ? 'desc' : 'asc'
  } else {
    filters.sort_by = field
    filters.sort_dir = 'asc'
  }
  loadUsers(1)
}

function sortIcon(field) {
  if (filters.sort_by !== field) return 'mdi-swap-vertical'
  return filters.sort_dir === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down'
}

function roleLabel(role) {
  return role === 'admin' ? t('adminUsers.adminRole') : t('adminUsers.userRole')
}

function statusLabel(status) {
  return status === 'blocked' ? t('adminUsers.blocked') : t('adminUsers.active')
}

async function loadUsers(page = 1) {
  loading.value = true
  try {
    const { data } = await getAdminUsers({
      page,
      search: filters.search || undefined,
      status: filters.status || undefined,
      role: filters.role || undefined,
      sort_by: filters.sort_by,
      sort_dir: filters.sort_dir,
    })

    users.value = data.data
    pagination.current_page = data.current_page
    pagination.last_page = data.last_page
    pagination.total = data.total
  } finally {
    loading.value = false
  }
}

function openStatusDialog(user) {
  statusDialog.user = user
  statusDialog.nextStatus = user.status === 'active' ? 'blocked' : 'active'
  statusDialog.reason = ''
  statusDialog.open = true
}

async function saveStatus() {
  if (!statusDialog.user) return

  saving.value = true
  try {
    await updateAdminUserStatus(statusDialog.user.id, {
      status: statusDialog.nextStatus,
      reason: statusDialog.reason || undefined,
    })
    statusDialog.open = false
    await loadUsers(pagination.current_page)
  } finally {
    saving.value = false
  }
}

async function toggleRole(user) {
  saving.value = true
  try {
    const role = user.role === 'admin' ? 'user' : 'admin'
    await updateAdminUserRole(user.id, { role })
    await loadUsers(pagination.current_page)
  } finally {
    saving.value = false
  }
}

function openLimitDialog(user) {
  limitDialog.user = user
  limitDialog.value = Number(user.monthly_limit) || 0
  limitDialog.open = true
}

async function saveLimit() {
  if (!limitDialog.user) return

  saving.value = true
  try {
    await updateAdminUserLimit(limitDialog.user.id, { monthly_limit: Math.max(0, Number(limitDialog.value) || 0) })
    limitDialog.open = false
    await loadUsers(pagination.current_page)
  } finally {
    saving.value = false
  }
}

onMounted(() => loadUsers(1))
</script>
