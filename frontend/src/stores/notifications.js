import { defineStore } from 'pinia'
import { ref } from 'vue'
import {
  getNotifications as apiGetNotifications,
  getUnreadCount as apiGetUnreadCount,
  markNotificationRead as apiMarkRead,
  markAllNotificationsRead as apiMarkAllRead,
} from '@/api'

export const useNotificationsStore = defineStore('notifications', () => {
  const notifications = ref([])
  const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
  const unreadCount = ref(0)
  const loading = ref(false)
  let _pollingInFlight = false

  function reset() {
    notifications.value = []
    pagination.value = { current_page: 1, last_page: 1, total: 0 }
    unreadCount.value = 0
    loading.value = false
    _pollingInFlight = false
  }

  async function fetchNotifications(page = 1) {
    loading.value = true
    try {
      const { data } = await apiGetNotifications(page)
      notifications.value = data.data
      pagination.value = {
        current_page: data.current_page,
        last_page: data.last_page,
        total: data.total,
      }
    } finally {
      loading.value = false
    }
  }

  async function fetchUnreadCount() {
    if (_pollingInFlight) return
    _pollingInFlight = true
    try {
      const { data } = await apiGetUnreadCount()
      unreadCount.value = data.unread_count
    } finally {
      _pollingInFlight = false
    }
  }

  async function markRead(id) {
    await apiMarkRead(id)
    const n = notifications.value.find(n => n.id === id)
    if (n) n.is_read = true
    unreadCount.value = Math.max(0, unreadCount.value - 1)
  }

  async function markAllRead() {
    await apiMarkAllRead()
    notifications.value.forEach(n => n.is_read = true)
    unreadCount.value = 0
  }

  return {
    notifications, pagination, unreadCount, loading,
    fetchNotifications, fetchUnreadCount, markRead, markAllRead, reset,
  }
})
