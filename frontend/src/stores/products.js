import { defineStore } from 'pinia'
import { ref } from 'vue'
import {
  getProducts as apiGetProducts,
  getProduct as apiGetProduct,
  updateProduct as apiUpdateProduct,
  deleteProduct as apiDeleteProduct,
  getPriceHistory as apiGetPriceHistory,
} from '@/api'

export const useProductsStore = defineStore('products', () => {
  const products = ref([])
  const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
  const currentProduct = ref(null)
  const priceHistory = ref(null)
  const loading = ref(false)

  function reset() {
    products.value = []
    pagination.value = { current_page: 1, last_page: 1, total: 0 }
    currentProduct.value = null
    priceHistory.value = null
    loading.value = false
  }

  async function fetchProducts(params = {}) {
    let query = params
    if (typeof params === 'number') {
      query = { page: params }
    }

    loading.value = true
    try {
      const { data } = await apiGetProducts({
        page: query.page ?? 1,
        sort_by: query.sort_by,
        sort_dir: query.sort_dir,
        symbol: query.symbol,
      })
      products.value = data.data
      pagination.value = {
        current_page: data.current_page,
        last_page: data.last_page,
        total: data.total,
      }
    } finally {
      loading.value = false
    }
  }

  async function fetchProduct(id) {
    loading.value = true
    try {
      const { data } = await apiGetProduct(id)
      currentProduct.value = data
    } finally {
      loading.value = false
    }
  }

  async function updateProduct(id, productData) {
    const { data } = await apiUpdateProduct(id, productData)
    const idx = products.value.findIndex(p => p.id === id)
    if (idx !== -1) products.value[idx] = { ...products.value[idx], ...data.product }
    return data
  }

  async function removeProduct(id) {
    await apiDeleteProduct(id)

    const next = []
    for (const item of products.value) {
      if (item.id !== id) {
        next.push(item)
      }
    }

    products.value = next
  }

  async function fetchPriceHistory(productId, days = 30, page = 1) {
    const { data } = await apiGetPriceHistory(productId, days, page)
    priceHistory.value = data
    return data
  }

  return {
    products, pagination, currentProduct, priceHistory, loading,
    fetchProducts, fetchProduct, updateProduct, removeProduct, fetchPriceHistory, reset,
  }
})
