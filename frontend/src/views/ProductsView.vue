<template>
  <v-container class="py-8">
    <div class="d-flex align-center justify-space-between mb-6">
      <h1 class="text-h4 font-weight-bold">My Products</h1>
      <v-btn color="primary" rounded="xl" prepend-icon="mdi-plus" @click="showAddDialog = true">
        Add Product
      </v-btn>
    </div>

    <v-progress-linear v-if="store.loading" indeterminate color="primary" class="mb-4" />

    <v-card rounded="xl" class="pa-4 mb-4">
      <v-row>
        <v-col cols="12" sm="6" md="4">
          <v-select
            v-model="sort.by"
            :items="sortByOptions"
            label="Sort by"
            variant="outlined"
            density="comfortable"
            @update:model-value="loadProducts(1)"
          />
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-select
            v-model="sort.dir"
            :items="sortDirOptions"
            label="Direction"
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
      <h3 class="text-h6 mb-2">No products yet</h3>
      <p class="text-medium-emphasis mb-4">Start tracking your first product to get price alerts</p>
      <v-btn color="primary" rounded="xl" prepend-icon="mdi-plus" @click="showAddDialog = true">Add Product</v-btn>
    </v-card>

    <!-- Products list -->
    <v-row v-else>
      <v-col v-for="product in store.products" :key="product.id" cols="12" sm="6" md="4">
        <v-card rounded="xl" class="product-card" :to="`/products/${product.id}`">
          <v-img
            v-if="product.image_url"
            :src="product.image_url"
            height="140"
            cover
            class="bg-grey-lighten-3"
          />
          <v-card-text class="pa-4">
            <div class="d-flex justify-space-between align-start mb-2">
              <h3 class="text-subtitle-1 font-weight-bold" style="line-height: 1.3;">{{ product.title }}</h3>
              <v-chip
                :color="product.pivot?.is_active ? 'success' : 'grey'"
                size="x-small"
                variant="tonal"
              >
                {{ product.pivot?.is_active ? 'Active' : 'Paused' }}
              </v-chip>
            </div>

            <div class="text-h5 font-weight-bold primary--text mb-2">
              {{ formatPrice(product.current_price) }}
            </div>

            <div class="text-caption text-medium-emphasis">
              <v-icon size="14">mdi-store</v-icon> {{ product.store_name || 'Unknown' }}
            </div>
            <div class="text-caption text-medium-emphasis">
              <v-icon size="14">mdi-clock-outline</v-icon> Every {{ formatInterval(product.pivot?.check_interval) }}
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
        <h2 class="text-h6 font-weight-bold mb-4">Add Product</h2>

        <v-alert v-if="addError" type="error" variant="tonal" rounded="lg" class="mb-4" closable @click:close="addError = null">
          {{ addError }}
        </v-alert>

        <v-form @submit.prevent="handleAdd" ref="addFormRef">
          <v-text-field
            v-model="addForm.url"
            label="Product URL"
            variant="outlined"
            rounded="lg"
            prepend-inner-icon="mdi-link"
            placeholder="https://www.amazon.com/dp/..."
            :rules="[v => !!v || 'Required', v => /^https?:\/\/.+/.test(v) || 'Must be a valid URL']"
            :disabled="addLoading"
          />

          <v-select
            v-model="addForm.checkInterval"
            label="Check frequency"
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
            Supported stores: Amazon, eBay, rdveikals.lv, 1a.lv, 220.lv
          </div>

          <div class="d-flex ga-2 justify-end mt-2">
            <v-btn variant="text" rounded="xl" @click="showAddDialog = false" :disabled="addLoading">Cancel</v-btn>
            <v-btn type="submit" color="primary" rounded="xl" :loading="addLoading">
              <v-icon start>mdi-magnify</v-icon> Fetch &amp; Track
            </v-btn>
          </div>
        </v-form>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useProductsStore } from '@/stores/products'

const store = useProductsStore()
const currentPage = ref(1)
const showAddDialog = ref(false)
const addFormRef = ref(null)
const addLoading = ref(false)
const addError = ref(null)

const addForm = reactive({
  url: '',
  checkInterval: 1440,
})

const sort = reactive({
  by: 'created_at',
  dir: 'desc',
})

const sortByOptions = [
  { title: 'Added date', value: 'created_at' },
  { title: 'Title', value: 'title' },
  { title: 'Current price', value: 'current_price' },
  { title: 'Store', value: 'store_name' },
  { title: 'Next check', value: 'next_check_at' },
  { title: 'Last checked', value: 'last_checked_at' },
]

const sortDirOptions = [
  { title: 'Descending', value: 'desc' },
  { title: 'Ascending', value: 'asc' },
]

const checkIntervalOptions = [
  { label: 'Every 30 minutes', value: 30 },
  { label: 'Every 1 hour', value: 60 },
  { label: 'Every 6 hours', value: 360 },
  { label: 'Every 12 hours', value: 720 },
  { label: 'Every 24 hours', value: 1440 },
]

function formatPrice(price) {
  return Number(price).toFixed(2) + ' €'
}

function formatInterval(minutes) {
  if (!minutes) return '24h'
  if (minutes < 60) return minutes + ' min'
  if (minutes < 1440) return Math.round(minutes / 60) + 'h'
  return Math.round(minutes / 1440) + 'd'
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
  const { valid } = await addFormRef.value.validate()
  if (!valid) return

  addLoading.value = true
  addError.value = null
  try {
    await store.addProduct({
      url: addForm.url,
      check_interval: addForm.checkInterval,
    })
    showAddDialog.value = false
    addForm.url = ''
    addForm.checkInterval = 1440
    await loadProducts(currentPage.value)
  } catch (e) {
    addError.value = e.response?.data?.message || 'Failed to add product'
  } finally {
    addLoading.value = false
  }
}

onMounted(() => loadProducts(1))
</script>

<style scoped>
.product-card {
  transition: transform 0.2s, box-shadow 0.2s;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}
.product-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}
</style>
