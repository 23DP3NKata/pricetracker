import axios from 'axios'

function resolveApiBaseUrl() {
  const envUrl = import.meta.env.VITE_API_URL
  if (envUrl && String(envUrl).trim() !== '') {
    return String(envUrl).trim()
  }

  if (typeof window !== 'undefined' && window.location?.origin) {
    const origin = window.location.origin

    if (/^https?:\/\/localhost:(5173|5174)$/i.test(origin)) {
      return 'http://localhost:8000'
    }

    return origin
  }

  return 'http://localhost:8000'
}

const api = axios.create({
  baseURL: resolveApiBaseUrl(),
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

export function getCsrfCookie() {
  return api.get('/sanctum/csrf-cookie')
}

// Auth
export function register(data) {
  return api.post('/api/register', data)
}

export function login(data) {
  return api.post('/api/login', data)
}

export function logout() {
  return api.post('/api/logout')
}

export function getUser() {
  return api.get('/api/user')
}

export function forgotPassword(email) {
  return api.post('/api/forgot-password', { email })
}

export function resetPassword(data) {
  return api.post('/api/reset-password', data)
}

export function changePassword(data) {
  return api.put('/api/password', data)
}

// User Settings
export function getUserProfile() {
  return api.get('/api/user/profile')
}

export function updateUserName(data) {
  return api.put('/api/user/name', data)
}

export function updateUserEmail(data) {
  return api.put('/api/user/email', data)
}

export function updateUserPassword(data) {
  return api.put('/api/user/password', data)
}

export function deleteUserAccount(data) {
  return api.delete('/api/user', { data })
}

// Dashboard
export function getDashboard() {
  return api.get('/api/dashboard')
}

// Market feed
export function getTopAssets(limit = 10) {
  return api.get('/api/market/top-assets', { params: { limit } })
}

// Products
export function getProducts(params = {}) {
  return api.get('/api/products', { params })
}

export function getProduct(id) {
  return api.get(`/api/products/${id}`)
}

export function trackAsset(data) {
  return api.post('/api/assets', data)
}

export function updateProduct(id, data) {
  return api.put(`/api/products/${id}`, data)
}

export function deleteProduct(id) {
  return api.delete(`/api/products/${id}`)
}

export function getTrackingRules(params = {}) {
  return api.get('/api/tracking-rules', { params })
}

export function updateTrackingRule(id, data) {
  return api.put(`/api/tracking-rules/${id}`, data)
}

export function deleteTrackingRule(id) {
  return api.delete(`/api/tracking-rules/${id}`)
}

// Price History
export function getPriceHistory(productId, days = 30) {
  let params = {}
  if (days !== null) {
    params = { days }
  }

  return api.get(`/api/products/${productId}/prices`, { params })
}

export function getCurrentAssetPrice(productId) {
  return api.get(`/api/assets/${productId}/current-price`)
}

export function updateAssetAlerts(productId, data) {
  return api.patch(`/api/assets/${productId}/alerts`, data)
}

// Notifications
export function getNotifications(page = 1) {
  return api.get('/api/notifications', { params: { page } })
}

export function getUnreadCount() {
  return api.get('/api/notifications/unread-count')
}

export function markNotificationRead(id) {
  return api.post(`/api/notifications/${id}/read`)
}

export function markAllNotificationsRead() {
  return api.post('/api/notifications/read-all')
}

// Email Verification
export function resendVerification() {
  return api.post('/api/email/verification-notification')
}

// Admin - Users
export function getAdminDashboard() {
  return api.get('/api/admin/dashboard')
}

export function getAdminUsers(params = {}) {
  return api.get('/api/admin/users', { params })
}

export function updateAdminUserStatus(userId, data) {
  return api.patch(`/api/admin/users/${userId}/status`, data)
}

export function updateAdminUserLimit(userId, data) {
  return api.patch(`/api/admin/users/${userId}/limit`, data)
}

export function updateAdminUserRole(userId, data) {
  return api.patch(`/api/admin/users/${userId}/role`, data)
}

// Admin - Logs / Actions
export function getAdminLogs(params = {}) {
  return api.get('/api/admin/logs', { params })
}

export function exportAdminLogs(params = {}) {
  return api.get('/api/admin/logs/export', {
    params,
    responseType: 'blob',
  })
}

export function getAdminActions(params = {}) {
  return api.get('/api/admin/actions', { params })
}

export function exportAdminActions(params = {}) {
  return api.get('/api/admin/actions/export', {
    params,
    responseType: 'blob',
  })
}

// Admin - Products
export function getAdminProducts(params = {}) {
  return api.get('/api/admin/products', { params })
}

export function updateAdminProductStatus(productId, data) {
  return api.patch(`/api/admin/products/${productId}/status`, data)
}

export function refreshAdminProductPrice(productId, data = {}) {
  return api.post(`/api/admin/products/${productId}/refresh-price`, data)
}

export function refreshAllAdminProductPrices(data = {}) {
  return api.post('/api/admin/products/refresh-prices', data)
}

export default api
