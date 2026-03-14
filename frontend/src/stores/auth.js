import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { getCsrfCookie, login as apiLogin, register as apiRegister, logout as apiLogout, getUser } from '@/api'
import { useNotificationsStore } from '@/stores/notifications'
import { useProductsStore } from '@/stores/products'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const initialCheckDone = ref(false)

  function clearSessionStores() {
    useProductsStore().reset()
    useNotificationsStore().reset()
  }

  const isAuthenticated = computed(() => !!user.value)
  const isAdmin = computed(() => user.value?.role === 'admin')
  const emailVerified = computed(() => !!user.value?.email_verified_at)

  async function fetchUser() {
    try {
      const { data } = await getUser()
      user.value = data
    } catch {
      user.value = null
      clearSessionStores()
    } finally {
      initialCheckDone.value = true
    }
  }

  async function login(credentials) {
    loading.value = true
    error.value = null
    clearSessionStores()
    try {
      await getCsrfCookie()
      await apiLogin(credentials)
      await fetchUser()
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка входа'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function register(data) {
    loading.value = true
    error.value = null
    clearSessionStores()
    try {
      await getCsrfCookie()
      await apiRegister(data)
      await fetchUser()
    } catch (e) {
      error.value = e.response?.data?.message || 'Ошибка регистрации'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await apiLogout()
    } finally {
      user.value = null
      clearSessionStores()
    }
  }

  return { user, loading, error, initialCheckDone, isAuthenticated, isAdmin, emailVerified, fetchUser, login, register, logout }
})
