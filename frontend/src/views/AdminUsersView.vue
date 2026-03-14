<template>
  <v-container class="py-8">
    <div class="d-flex align-center justify-space-between mb-6 ga-3 flex-wrap">
      <h1 class="text-h4 font-weight-bold">Admin: Users</h1>
      <v-btn to="/admin/logs" variant="tonal" rounded="xl" prepend-icon="mdi-text-box-search-outline">Logs</v-btn>
    </div>

    <v-card rounded="xl" class="pa-4 mb-4">
      <v-row>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="filters.search"
            label="Search by name or email"
            variant="outlined"
            density="comfortable"
            clearable
            @keyup.enter="loadUsers(1)"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-select
            v-model="filters.status"
            label="Status"
            variant="outlined"
            density="comfortable"
            :items="statusOptions"
            clearable
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-select
            v-model="filters.role"
            label="Role"
            variant="outlined"
            density="comfortable"
            :items="roleOptions"
            clearable
          />
        </v-col>
        <v-col cols="12" md="2" class="d-flex align-center">
          <v-btn color="primary" block rounded="xl" :loading="loading" @click="loadUsers(1)">Apply</v-btn>
        </v-col>
      </v-row>
    </v-card>

    <v-card rounded="xl">
      <v-progress-linear v-if="loading" indeterminate color="primary" />
      <v-table>
        <thead>
          <tr>
            <th>ID</th>
            <th>User</th>
            <th>Role</th>
            <th>Status</th>
            <th>Monthly limit</th>
            <th>Checks used</th>
            <th class="text-right">Actions</th>
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
              <v-chip size="small" :color="u.role === 'admin' ? 'primary' : 'default'">{{ u.role }}</v-chip>
            </td>
            <td>
              <v-chip size="small" :color="u.status === 'active' ? 'success' : 'warning'">{{ u.status }}</v-chip>
            </td>
            <td>{{ u.monthly_limit }}</td>
            <td>{{ u.checks_used }}</td>
            <td class="text-right">
              <v-menu location="bottom end">
                <template #activator="{ props }">
                  <v-btn icon="mdi-dots-vertical" variant="text" v-bind="props" />
                </template>
                <v-list density="compact">
                  <v-list-item @click="toggleStatus(u)">
                    <v-list-item-title>{{ u.status === 'active' ? 'Block user' : 'Unblock user' }}</v-list-item-title>
                  </v-list-item>
                  <v-list-item @click="toggleRole(u)">
                    <v-list-item-title>{{ u.role === 'admin' ? 'Set as user' : 'Set as admin' }}</v-list-item-title>
                  </v-list-item>
                  <v-list-item @click="openLimitDialog(u)">
                    <v-list-item-title>Change monthly limit</v-list-item-title>
                  </v-list-item>
                </v-list>
              </v-menu>
            </td>
          </tr>
          <tr v-if="!loading && users.length === 0">
            <td colspan="7" class="text-center py-8 text-medium-emphasis">No users found</td>
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
        <h3 class="text-h6 mb-4">Set monthly limit</h3>
        <div class="text-body-2 mb-4">{{ limitDialog.user?.name }} ({{ limitDialog.user?.email }})</div>
        <v-text-field
          v-model.number="limitDialog.value"
          type="number"
          min="0"
          variant="outlined"
          label="Monthly limit"
        />
        <div class="d-flex justify-end ga-2 mt-2">
          <v-btn variant="text" @click="limitDialog.open = false">Cancel</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveLimit">Save</v-btn>
        </div>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { getAdminUsers, updateAdminUserLimit, updateAdminUserRole, updateAdminUserStatus } from '@/api'

const loading = ref(false)
const saving = ref(false)
const users = ref([])

const filters = reactive({
  search: '',
  status: null,
  role: null,
})

const statusOptions = [
  { title: 'active', value: 'active' },
  { title: 'blocked', value: 'blocked' },
]

const roleOptions = [
  { title: 'user', value: 'user' },
  { title: 'admin', value: 'admin' },
]

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

async function loadUsers(page = 1) {
  loading.value = true
  try {
    const { data } = await getAdminUsers({
      page,
      search: filters.search || undefined,
      status: filters.status || undefined,
      role: filters.role || undefined,
    })

    users.value = data.data
    pagination.current_page = data.current_page
    pagination.last_page = data.last_page
    pagination.total = data.total
  } finally {
    loading.value = false
  }
}

async function toggleStatus(user) {
  saving.value = true
  try {
    const status = user.status === 'active' ? 'blocked' : 'active'
    await updateAdminUserStatus(user.id, { status })
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
