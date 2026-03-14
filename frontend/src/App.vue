<template>
  <v-app>
    <!-- nav -->
    <v-app-bar :elevation="scrolled ? 2 : 0" :class="{ scrolled }" height="70" flat>
      <v-container class="d-flex align-center">
        <div class="logo" @click="$router.push('/')">
          <v-icon color="primary" size="28">mdi-chart-line-variant</v-icon>
          <span>PriceTracker</span>
        </div>

        <v-spacer />

        <!-- Desktop nav links -->
        <div class="nav d-none d-md-flex">
          <template v-if="!auth.isAuthenticated">
            <v-btn to="/" variant="text" rounded>Home</v-btn>
            <v-btn to="/about" variant="text" rounded>About</v-btn>
          </template>
          <template v-else>
            <v-btn to="/dashboard" variant="text" rounded>Dashboard</v-btn>
            <v-btn to="/products" variant="text" rounded>Products</v-btn>
            <v-btn v-if="auth.isAdmin" to="/admin/dashboard" variant="text" rounded>Admin</v-btn>
          </template>
        </div>

        <!-- Desktop right-side actions -->
        <div class="d-none d-md-flex align-center ml-4 ga-2">
          <template v-if="auth.isAuthenticated">
            <v-menu v-model="notificationsMenu" location="bottom end" offset="12" @update:model-value="handleNotificationsMenu">
              <template #activator="{ props }">
                <v-btn icon variant="text" v-bind="props" class="notification-btn">
                  <v-badge
                    v-if="notificationsStore.unreadCount > 0"
                    :content="notificationsStore.unreadCount"
                    color="error"
                  >
                    <v-icon size="22">mdi-bell-outline</v-icon>
                  </v-badge>
                  <v-icon v-else size="22">mdi-bell-outline</v-icon>
                </v-btn>
              </template>

              <v-card min-width="340" rounded="xl" elevation="3">
                <v-card-title class="text-subtitle-1">Notifications</v-card-title>

                <v-divider />

                <v-list density="compact" class="py-1">
                  <template v-if="recentNotifications.length > 0">
                    <v-list-item
                      v-for="n in recentNotifications"
                      :key="n.id"
                      rounded="lg"
                      :class="{ 'notification-unread': !n.is_read }"
                      @click="openNotification(n)"
                    >
                      <v-list-item-title class="text-body-2">{{ notificationText(n) }}</v-list-item-title>
                      <v-list-item-subtitle class="text-caption">{{ notificationDate(n.created_at) }}</v-list-item-subtitle>
                    </v-list-item>
                  </template>
                  <v-list-item v-else>
                    <v-list-item-title class="text-body-2 text-medium-emphasis">No recent notifications</v-list-item-title>
                  </v-list-item>
                </v-list>

                <v-divider />

                <v-card-actions>
                  <v-btn to="/notifications" variant="text" block @click="notificationsMenu = false">View all</v-btn>
                </v-card-actions>
              </v-card>
            </v-menu>

            <!-- Add product -->
            <v-btn color="primary" rounded="xl" prepend-icon="mdi-plus" @click="openAddProduct">Add product</v-btn>

            <!-- User menu -->
            <v-menu location="bottom end" offset="12">
              <template #activator="{ props }">
                <v-btn icon v-bind="props" variant="text">
                  <v-avatar size="32" color="primary">
                    <span class="text-white text-caption">{{ auth.user?.name?.charAt(0)?.toUpperCase() || '?' }}</span>
                  </v-avatar>
                </v-btn>
              </template>

              <v-card min-width="260" rounded="xl" elevation="3">
                <v-list density="compact" class="py-2">
                  <v-list-item to="/settings" rounded="lg" prepend-icon="mdi-cog-outline" title="Settings" />

                  <v-list-item rounded="lg" prepend-icon="mdi-translate" @click="toggleLanguage">
                    <v-list-item-title>Language</v-list-item-title>
                    <template #append>
                      <span class="text-caption text-medium-emphasis">{{ currentLanguageLabel }}</span>
                    </template>
                  </v-list-item>

                  <v-list-item
                    rounded="lg"
                    :prepend-icon="isDark ? 'mdi-weather-sunny' : 'mdi-weather-night'"
                    :title="isDark ? 'Light theme' : 'Dark theme'"
                    @click="toggleTheme"
                  />
                </v-list>

                <v-divider />

                <v-list density="compact" class="py-2">
                  <v-list-item
                    rounded="lg"
                    prepend-icon="mdi-logout"
                    title="Logout"
                    class="text-error"
                    @click="handleLogout"
                  />
                </v-list>
              </v-card>
            </v-menu>
          </template>

          <template v-else>
            <v-btn to="/login" variant="text" rounded>Sign In</v-btn>
              <v-btn to="/register" color="primary" rounded>Get Started</v-btn>

            <!-- Guest user menu -->
            <v-menu location="bottom end" offset="12">
              <template #activator="{ props }">
                <v-btn icon v-bind="props" variant="text">
                  <v-icon size="30">mdi-account-circle-outline</v-icon>
                </v-btn>
              </template>

              <v-card min-width="260" rounded="xl" elevation="3">
                <v-list density="compact" class="py-2">
                  <v-list-item rounded="lg" prepend-icon="mdi-translate" @click="toggleLanguage">
                    <v-list-item-title>Language</v-list-item-title>
                    <template #append>
                      <span class="text-caption text-medium-emphasis">{{ currentLanguageLabel }}</span>
                    </template>
                  </v-list-item>

                  <v-list-item
                    rounded="lg"
                    :prepend-icon="isDark ? 'mdi-weather-sunny' : 'mdi-weather-night'"
                    :title="isDark ? 'Light theme' : 'Dark theme'"
                    @click="toggleTheme"
                  />
                </v-list>

                <v-divider />

                <v-list density="compact" class="py-2">
                  <v-list-item
                    rounded="lg"
                    prepend-icon="mdi-login"
                    title="Sign In"
                    to="/login"
                  />
                </v-list>
              </v-card>
            </v-menu>
          </template>
        </div>

        <v-btn icon="mdi-menu" variant="text" class="d-md-none ml-2" @click="drawer = true" />
      </v-container>
    </v-app-bar>

    <!-- Mobile drawer -->
    <v-navigation-drawer v-model="drawer" temporary location="right" width="280">
      <template v-if="auth.isAuthenticated">
        <!-- User header -->
        <div class="user-menu-header px-4 py-4">
          <div class="d-flex align-center ga-3">
              <v-icon size="40" color="primary">mdi-account-circle-outline</v-icon>
            <div>
              <div class="text-subtitle-2 font-weight-bold">{{ auth.user?.name }}</div>
              <div class="text-caption text-medium-emphasis">{{ auth.user?.email }}</div>
            </div>
          </div>
        </div>
        <v-divider />
        <v-list class="pa-2">
          <v-list-item to="/dashboard" rounded prepend-icon="mdi-view-dashboard-outline" title="Dashboard" />
          <v-list-item to="/products" rounded prepend-icon="mdi-tag-outline" title="Products" />
          <v-list-item to="/notifications" rounded prepend-icon="mdi-bell-outline" title="Notifications">
            <template v-if="notificationsStore.unreadCount > 0" #append>
              <v-chip size="x-small" color="error">{{ notificationsStore.unreadCount }}</v-chip>
            </template>
          </v-list-item>
          <template v-if="auth.isAdmin">
            <v-list-item to="/admin/dashboard" rounded prepend-icon="mdi-shield-account" title="Admin dashboard" />
            <v-list-item to="/admin/users" rounded prepend-icon="mdi-account-group-outline" title="Admin users" />
            <v-list-item to="/admin/products" rounded prepend-icon="mdi-package-variant-closed" title="Admin products" />
            <v-list-item to="/admin/logs" rounded prepend-icon="mdi-text-box-search-outline" title="Admin logs" />
            <v-list-item to="/admin/actions" rounded prepend-icon="mdi-history" title="Admin actions" />
          </template>
          <v-divider class="my-2" />
          <v-list-item rounded prepend-icon="mdi-plus" title="Add product" base-color="primary" @click="openAddProduct" />
          <v-divider class="my-2" />
          <v-list-item
            rounded
            :prepend-icon="isDark ? 'mdi-weather-sunny' : 'mdi-weather-night'"
            :title="isDark ? 'Light theme' : 'Dark theme'"
            @click="toggleTheme"
          />
          <v-list-item rounded prepend-icon="mdi-translate" title="Language" @click="toggleLanguage">
            <template #append>
              <span class="text-caption text-medium-emphasis">{{ currentLanguageLabel }}</span>
            </template>
          </v-list-item>
          <v-list-item to="/settings" rounded prepend-icon="mdi-cog-outline" title="Settings" />
          <v-divider class="my-2" />
          <v-list-item rounded prepend-icon="mdi-logout" title="Logout" class="text-error" @click="handleLogout" />
        </v-list>
      </template>

      <template v-else>
        <v-list class="pa-4">
          <v-list-item to="/" rounded>Home</v-list-item>
          <v-list-item to="/about" rounded>About</v-list-item>
          <v-divider class="my-3" />
          <v-list-item to="/login" rounded>Sign In</v-list-item>
          <v-btn to="/register" color="primary" block rounded class="mt-2">Get Started</v-btn>
        </v-list>
      </template>
    </v-navigation-drawer>

    <!-- main content -->
    <v-main>
      <router-view />
    </v-main>

    <v-dialog v-model="showAddDialog" max-width="520">
      <v-card rounded="xl" class="pa-6">
        <h2 class="text-h6 font-weight-bold mb-4">Add product</h2>

        <v-alert
          v-if="addError"
          type="error"
          variant="tonal"
          rounded="lg"
          class="mb-4"
          closable
          @click:close="addError = null"
        >
          {{ addError }}
        </v-alert>

        <v-form ref="addFormRef" @submit.prevent="handleAddProduct">
          <v-text-field
            v-model="addForm.url"
            label="Product URL"
            variant="outlined"
            rounded="lg"
            prepend-inner-icon="mdi-link-variant"
            placeholder="https://www.amazon.com/dp/..."
            :rules="urlRules"
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

          <div class="d-flex ga-2 justify-end mt-2">
            <v-btn variant="text" rounded="xl" :disabled="addLoading" @click="closeAddProduct">Cancel</v-btn>
            <v-btn type="submit" color="primary" rounded="xl" :loading="addLoading" prepend-icon="mdi-plus">Track</v-btn>
          </div>
        </v-form>
      </v-card>
    </v-dialog>

    <!-- footer -->
    <footer class="footer">
      <v-container>
        <div class="footer-content">
          <div class="logo">
            <v-icon color="primary" size="24">mdi-chart-line-variant</v-icon>
            <span>PriceTracker</span>
          </div>

          <div class="footer-links">
            <router-link to="/">Home</router-link>
            <router-link to="/about">About</router-link>
            <router-link to="#">Terms</router-link>
          </div>

          <div class="socials">
            <v-btn icon="mdi-github" variant="text" href="https://github.com/23DP3NKata" target="_blank"/>
          </div>
        </div>

        <v-divider class="my-4" />

        <p class="copyright">© {{ new Date().getFullYear() }} PriceTracker. All rights reserved.</p>
      </v-container>
    </footer>

    <v-snackbar v-model="showLanguageNotice" timeout="2200" location="bottom right">
      Language switcher is a placeholder for now.
    </v-snackbar>
  </v-app>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useTheme } from 'vuetify'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'
import { useProductsStore } from '@/stores/products'

const theme = useTheme()
const router = useRouter()
const auth = useAuthStore()
const notificationsStore = useNotificationsStore()
const productsStore = useProductsStore()
const drawer = ref(false)
const scrolled = ref(false)
const language = ref(localStorage.getItem('pt-language') || 'en')
const showLanguageNotice = ref(false)
const notificationsMenu = ref(false)
const showAddDialog = ref(false)
const addFormRef = ref(null)
const addLoading = ref(false)
const addError = ref(null)
const checkIntervalOptions = [
  { label: 'Every 30 minutes', value: 30 },
  { label: 'Every 1 hour', value: 60 },
  { label: 'Every 6 hours', value: 360 },
  { label: 'Every 12 hours', value: 720 },
  { label: 'Every 24 hours', value: 1440 },
]
const addForm = reactive({
  url: '',
  targetPrice: '',
  checkInterval: 1440,
})
const urlRules = [
  v => !!v || 'Product URL is required',
  v => /^https?:\/\/.+/.test(v) || 'URL must start with http:// or https://',
]

const isDark = computed(() => theme.global.current.value.dark)
const currentLanguageLabel = computed(() => language.value === 'lv' ? 'Latvian' : 'English')
const recentNotifications = computed(() => notificationsStore.notifications.slice(0, 5))

function notificationText(notification) {
  return notification?.message || notification?.title || 'Notification'
}

function notificationDate(value) {
  if (!value) return ''
  return new Date(value).toLocaleString(undefined, { timeZone: 'UTC' })
}

async function handleNotificationsMenu(isOpen) {
  if (!isOpen || !auth.isAuthenticated) return
  if (notificationsStore.notifications.length === 0) {
    await notificationsStore.fetchNotifications(1)
  }
}

async function openNotification(notification) {
  if (!notification) return

  if (!notification.is_read) {
    try {
      await notificationsStore.markRead(notification.id)
    } catch (_) {
      // Keep navigation even if mark-read request fails.
    }
  }

  notificationsMenu.value = false

  if (notification.product_id) {
    router.push({ name: 'product-detail', params: { id: notification.product_id } })
    return
  }

  router.push({ name: 'notifications' })
}

function openAddProduct() {
  showAddDialog.value = true
  drawer.value = false
}

function closeAddProduct() {
  showAddDialog.value = false
  addError.value = null
  addForm.url = ''
  addForm.targetPrice = ''
  addForm.checkInterval = 1440
  addFormRef.value?.resetValidation()
}

async function handleAddProduct() {
  const { valid } = await addFormRef.value.validate()
  if (!valid) return

  addLoading.value = true
  addError.value = null
  try {
    const result = await productsStore.addProduct({
      url: addForm.url,
      check_interval: addForm.checkInterval,
    })
    closeAddProduct()
    if (result?.product?.id) {
      router.push({ name: 'product-detail', params: { id: result.product.id } })
    }
  } catch (e) {
    addError.value = e.response?.data?.message || 'Failed to add product'
  } finally {
    addLoading.value = false
  }
}

function toggleTheme() {
  const nextTheme = isDark.value ? 'light' : 'dark'
  theme.global.name.value = nextTheme
  localStorage.setItem('pt-theme', nextTheme)
}

function toggleLanguage() {
  language.value = language.value === 'en' ? 'lv' : 'en'
  localStorage.setItem('pt-language', language.value)
  showLanguageNotice.value = true
}

async function handleLogout() {
  drawer.value = false
  await auth.logout()
  router.push('/')
}

// Poll unread count when authenticated
let pollInterval = null
onMounted(() => {
  const savedTheme = localStorage.getItem('pt-theme')
  if (savedTheme === 'light' || savedTheme === 'dark') {
    theme.global.name.value = savedTheme
  }

  pollInterval = setInterval(() => {
    if (auth.isAuthenticated) notificationsStore.fetchUnreadCount()
  }, 30000)
})
onUnmounted(() => clearInterval(pollInterval))

const handleScroll = () => scrolled.value = window.scrollY > 20
onMounted(() => window.addEventListener('scroll', handleScroll))
onUnmounted(() => window.removeEventListener('scroll', handleScroll))
</script>

<style scoped>
/* nav ber */
.v-app-bar {
  backdrop-filter: blur(10px);
  background: rgba(var(--v-theme-surface), 0.8) !important;
  transition: all 0.3s;
}

.v-app-bar.scrolled {
  background: rgba(var(--v-theme-surface), 0.95) !important;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08) !important;
}

.logo {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.logo span {
  font-size: 1.25rem;
  font-weight: 700;
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)), rgb(var(--v-theme-secondary)));
  background-clip: text;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.nav { gap: 0.5rem; }

.nav .v-btn, .v-app-bar .v-btn {
  text-transform: none;
  font-weight: 500;
}

.notification-btn {
  position: relative;
}

.notification-unread {
  background: rgba(var(--v-theme-primary), 0.06);
}

.user-menu-header {
  background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.08), rgba(var(--v-theme-secondary), 0.08));
}

/* footer */
.footer {
  background: rgba(var(--v-theme-surface), 1);
  border-top: 1px solid rgba(var(--v-theme-on-surface), 0.1);
  padding: 2rem 0 1rem;
  margin-top: 4rem;
}

.footer-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1.5rem;
}

.footer-links {
  display: flex;
  gap: 1.5rem;
}

.footer-links a {
  color: rgba(var(--v-theme-on-surface), 0.7);
  text-decoration: none;
  font-size: 0.875rem;
}

.footer-links a:hover {
  color: rgb(var(--v-theme-primary));
}

.socials {
  display: flex;
  gap: 0.25rem;
}

.copyright {
  text-align: center;
  color: rgba(var(--v-theme-on-surface), 0.5);
  font-size: 0.875rem;
  margin: 0;
}

@media (max-width: 768px) {
  .footer-content {
    flex-direction: column;
    text-align: center;
  }
  .footer-links {
    flex-wrap: wrap;
    justify-content: center;
  }
}
</style>