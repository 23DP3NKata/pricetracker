<template>
  <v-container class="py-8">
    <div class="d-flex align-center justify-space-between mb-6 ga-3 flex-wrap">
      <h1 class="text-h4 font-weight-bold">Admin: Actions</h1>
      <v-btn to="/admin/users" variant="tonal" rounded="xl" prepend-icon="mdi-account-group-outline">Users</v-btn>
    </div>

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
import { getAdminActions } from '@/api'

const loading = ref(false)
const actions = ref([])
const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
})

function formatDate(value) {
  if (!value) return '-'
  return new Date(value).toLocaleString()
}

async function loadActions(page = 1) {
  loading.value = true
  try {
    const { data } = await getAdminActions({ page })
    actions.value = data.data
    pagination.current_page = data.current_page
    pagination.last_page = data.last_page
    pagination.total = data.total
  } finally {
    loading.value = false
  }
}

onMounted(() => loadActions(1))
</script>
