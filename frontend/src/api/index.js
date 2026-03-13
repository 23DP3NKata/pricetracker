import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000',
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

// Dashboard
export function getDashboard() {
  return api.get('/api/dashboard')
}

// Products
export function getProducts(page = 1) {
  return api.get('/api/products', { params: { page } })
}

export function getProduct(id) {
  return api.get(`/api/products/${id}`)
}

export function addProduct(data) {
  return api.post('/api/products', data)
}

export function updateProduct(id, data) {
  return api.put(`/api/products/${id}`, data)
}

export function deleteProduct(id) {
  return api.delete(`/api/products/${id}`)
}

export function getSupportedStores() {
  return api.get('/api/products/supported-stores')
}

// Price History
export function getPriceHistory(productId, days = 30) {
  return api.get(`/api/products/${productId}/prices`, { params: { days } })
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

export default api
