<template>
  <v-container class="py-8">
    <div class="d-flex align-center justify-space-between mb-6">
      <h1 class="text-h4 font-weight-bold">{{ $t('notificationsPage.title') }}</h1>
      <v-btn
        v-if="store.notifications.length"
        variant="tonal"
        color="primary"
        rounded="xl"
        size="small"
        @click="store.markAllRead()"
      >
        {{ $t('notificationsPage.markAllRead') }}
      </v-btn>
    </div>

    <v-progress-linear v-if="store.loading" indeterminate color="primary" class="mb-4" />

    <!-- Empty state -->
    <v-card v-if="!store.loading && store.notifications.length === 0" rounded="xl" class="pa-8 text-center">
      <v-icon size="64" color="primary" class="mb-4">mdi-bell-check-outline</v-icon>
      <h3 class="text-h6 mb-2">{{ $t('notificationsPage.noNotifications') }}</h3>
      <p class="text-medium-emphasis">{{ $t('notificationsPage.alertsHere') }}</p>
    </v-card>

    <!-- Notifications list -->
    <div v-else class="notifications-wrap">
      <div class="d-flex flex-column ga-3">
        <v-card
          v-for="n in store.notifications"
          :key="n.id"
          rounded="xl"
          :class="{ 'notification-unread': !n.is_read }"
          class="notification-card"
          tabindex="0"
          role="button"
          @click="handleClick(n)"
          @keydown.enter.prevent="handleClick(n)"
          @keydown.space.prevent="handleClick(n)"
        >
          <v-card-text class="pa-4 d-flex align-center ga-4">
            <v-avatar
              :color="notificationColor(n)"
              variant="tonal"
              size="48"
            >
              <v-icon>{{ notificationIcon(n) }}</v-icon>
            </v-avatar>

            <div class="flex-grow-1">
              <div class="font-weight-bold">{{ notificationTitle(n) }}</div>
              <div class="text-body-2">
                {{ formatPrice(n.old_price) }} → {{ formatPrice(n.new_price) }}
                <v-chip
                  :color="notificationColor(n)"
                  size="x-small"
                  variant="tonal"
                  class="ml-1"
                >
                  {{ notificationChipText(n) }}
                </v-chip>
              </div>
              <div v-if="n.message" class="text-caption text-medium-emphasis mt-1">{{ n.message }}</div>
            </div>

            <div class="text-caption text-medium-emphasis text-no-wrap">
              {{ formatDate(n.created_at) }}
            </div>

            <v-icon v-if="!n.is_read" color="primary" size="12">mdi-circle</v-icon>
          </v-card-text>
        </v-card>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="store.pagination.last_page > 1" class="d-flex justify-center mt-6">
      <v-pagination
        v-model="currentPage"
        :length="store.pagination.last_page"
        rounded="circle"
        @update:model-value="store.fetchNotifications($event)"
      />
    </div>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useNotificationsStore } from '@/stores/notifications'

const store = useNotificationsStore()
const router = useRouter()
const { t } = useI18n()
const currentPage = ref(1)

function isTrackingStopped(notification) {
  const msg = (notification?.message || '').toLowerCase()
  return msg.includes('tracking stopped')
}

function notificationIcon(notification) {
  if (isTrackingStopped(notification)) return 'mdi-alert-circle-outline'

  if (Number(notification.new_price) < Number(notification.old_price)) {
    return 'mdi-arrow-down-bold'
  }

  return 'mdi-arrow-up-bold'
}

function notificationColor(notification) {
  if (isTrackingStopped(notification)) return 'warning'

  if (Number(notification.new_price) < Number(notification.old_price)) {
    return 'error'
  }

  return 'success'
}

function notificationTitle(notification) {
  const title = notification?.product?.title
  if (title) return title
  return t('notificationsPage.productFallback', { id: notification.product_id })
}

function notificationChipText(notification) {
  if (isTrackingStopped(notification)) return t('notificationsPage.trackingStopped')

  const diff = Number(notification.new_price) - Number(notification.old_price)
  const sign = diff >= 0 ? '+' : ''
  return `${sign}${diff.toFixed(2)} €`
}

function formatPrice(price) {
  return Number(price).toFixed(2) + ' €'
}

function formatDate(dateStr) {
  const d = new Date(dateStr)
  const now = new Date()
  const diff = now - d

  if (Number.isNaN(d.getTime())) return ''
  if (diff <= 0) return t('notificationsPage.justNow')
  if (diff < 3600000) return t('notificationsPage.minAgo', { count: Math.floor(diff / 60000) })
  if (diff < 86400000) return t('notificationsPage.hourAgo', { count: Math.floor(diff / 3600000) })
  return d.toLocaleDateString()
}

async function handleClick(n) {
  if (!n.is_read) await store.markRead(n.id)

  const productId = n?.product_id || n?.product?.id
  if (!productId) return

  router.push(`/products/${productId}`)
}

onMounted(() => store.fetchNotifications(1))
</script>

<style scoped>
.notification-card {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  cursor: pointer;
  transition: transform 0.2s;
}
.notifications-wrap {
  max-width: 920px;
  margin: 0 auto;
}
.notification-card:hover {
  transform: translateX(4px);
}
.notification-unread {
  border-left: 3px solid rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.03);
}
</style>
