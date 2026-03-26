<template>
  <v-app>
    <!-- nav -->
    <v-app-bar :elevation="scrolled ? 2 : 0" :class="{ scrolled }" height="70" flat>
      <v-container class="d-flex align-center">
        <div class="logo" @click="handleLogoClick">
          <img :src="siteLogoSrc" alt="PriceTracker logo" class="logo-mark" />
          <span>PriceTracker</span>
        </div>

        <v-spacer />

        <!-- Desktop nav links -->
        <div class="nav d-none d-md-flex">
          <template v-if="!auth.isAuthenticated">
            <v-btn to="/" variant="text" rounded>{{ $t('nav.home') }}</v-btn>
            <v-btn to="/about" variant="text" rounded>{{ $t('nav.about') }}</v-btn>
          </template>
          <template v-else>
            <v-btn to="/dashboard" variant="text" rounded>{{ $t('nav.dashboard') }}</v-btn>
            <v-btn to="/products" variant="text" rounded>{{ $t('nav.products') }}</v-btn>
            <v-btn v-if="auth.isAdmin" to="/admin/dashboard" variant="text" rounded>{{ $t('nav.admin') }}</v-btn>
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

              <v-card min-width="300" max-width="300" rounded="xl" elevation="3">
                <v-card-title class="text-subtitle-1">{{ $t('notifications.title') }}</v-card-title>

                <v-divider />

                <v-list density="compact" class="py-1">
                  <template v-if="recentNotifications.length > 0">
                    <v-list-item
                      v-for="n in recentNotifications"
                      :key="n.id"
                      rounded="lg"
                      :class="{ 'notification-unread': !n.is_read }"
                      :prepend-icon="isTrackingStoppedNotification(n) ? 'mdi-alert-circle-outline' : undefined"
                      @click="openNotification(n)"
                    >
                      <v-list-item-title class="text-body-2" :class="{ 'text-warning': isTrackingStoppedNotification(n) }">{{ notificationText(n) }}</v-list-item-title>
                      <v-list-item-subtitle class="text-caption">{{ notificationDate(n.created_at) }}</v-list-item-subtitle>
                    </v-list-item>
                  </template>
                  <v-list-item v-else>
                    <v-list-item-title class="text-body-2 text-medium-emphasis">{{ $t('notifications.noRecent') }}</v-list-item-title>
                  </v-list-item>
                </v-list>

                <v-divider />

                <v-card-actions>
                  <v-btn to="/notifications" variant="text" block @click="notificationsMenu = false">{{ $t('notifications.all') }}</v-btn>
                </v-card-actions>
              </v-card>
            </v-menu>

            <!-- Add product -->
            <v-btn color="primary" rounded="xl" prepend-icon="mdi-plus" @click="openAddProduct">{{ $t('nav.addProduct') }}</v-btn>

            <!-- User menu -->
            <v-menu location="bottom end" offset="12" :close-on-content-click="false">
              <template #activator="{ props }">
                <v-btn icon v-bind="props" variant="text">
                  <v-avatar size="32" color="primary">
                    <span class="text-white text-caption">{{ auth.user?.name?.charAt(0)?.toUpperCase() || '?' }}</span>
                  </v-avatar>
                </v-btn>
              </template>

              <v-card min-width="260" rounded="xl" elevation="3">
                <v-list density="compact" class="py-2">
                  <v-list-item to="/settings" rounded="lg" prepend-icon="mdi-cog-outline" :title="$t('nav.settings')" />

                  <v-menu v-model="languageMenuUser" location="end" offset="8">
                    <template #activator="{ props }">
                      <v-list-item
                        v-bind="props"
                        rounded="lg"
                        prepend-icon="mdi-translate"
                        :title="$t('nav.language')"
                        :subtitle="currentLanguageLabel"
                      />
                    </template>

                    <v-card min-width="220" rounded="lg" elevation="2">
                      <v-list density="compact" class="py-1">
                        <v-list-item :active="i18n.locale.value === 'en'" :title="i18n.t('ux.languageEnglish')" @click="setLanguage('en')" />
                        <v-list-item :active="i18n.locale.value === 'lv'" :title="i18n.t('ux.languageLatvian')" @click="setLanguage('lv')" />
                        <v-list-item :active="i18n.locale.value === 'ru'" :title="i18n.t('ux.languageRussian')" @click="setLanguage('ru')" />
                      </v-list>
                    </v-card>
                  </v-menu>

                  <v-list-item
                    rounded="lg"
                    :prepend-icon="isDark ? 'mdi-weather-sunny' : 'mdi-weather-night'"
                    :title="isDark ? $t('ux.themeLight') : $t('ux.themeDark')"
                    @click="toggleTheme"
                  />
                </v-list>

                <v-divider />

                <v-list density="compact" class="py-2">
                  <v-list-item
                    rounded="lg"
                    prepend-icon="mdi-logout"
                    :title="$t('nav.logout')"
                    class="text-error"
                    @click="handleLogout"
                  />
                </v-list>
              </v-card>
            </v-menu>
          </template>

          <template v-else>
            <v-btn to="/login" variant="text" rounded>{{ $t('nav.signIn') }}</v-btn>
              <v-btn to="/register" color="primary" rounded>{{ $t('nav.getStarted') }}</v-btn>

            <!-- Guest user menu -->
            <v-menu location="bottom end" offset="12" :close-on-content-click="false">
              <template #activator="{ props }">
                <v-btn icon v-bind="props" variant="text">
                  <v-icon size="30">mdi-account-circle-outline</v-icon>
                </v-btn>
              </template>

              <v-card min-width="260" rounded="xl" elevation="3">
                <v-list density="compact" class="py-2">
                  <v-menu v-model="languageMenuGuest" location="end" offset="8">
                    <template #activator="{ props }">
                      <v-list-item
                        v-bind="props"
                        rounded="lg"
                        prepend-icon="mdi-translate"
                        :title="$t('nav.language')"
                        :subtitle="currentLanguageLabel"
                      />
                    </template>

                    <v-card min-width="220" rounded="lg" elevation="2">
                      <v-list density="compact" class="py-1">
                        <v-list-item :active="i18n.locale.value === 'en'" :title="i18n.t('ux.languageEnglish')" @click="setLanguage('en')" />
                        <v-list-item :active="i18n.locale.value === 'lv'" :title="i18n.t('ux.languageLatvian')" @click="setLanguage('lv')" />
                        <v-list-item :active="i18n.locale.value === 'ru'" :title="i18n.t('ux.languageRussian')" @click="setLanguage('ru')" />
                      </v-list>
                    </v-card>
                  </v-menu>

                  <v-list-item
                    rounded="lg"
                    :prepend-icon="isDark ? 'mdi-weather-sunny' : 'mdi-weather-night'"
                    :title="isDark ? $t('ux.themeLight') : $t('ux.themeDark')"
                    @click="toggleTheme"
                  />
                </v-list>

                <v-divider />

                <v-list density="compact" class="py-2">
                  <v-list-item
                    rounded="lg"
                    prepend-icon="mdi-login"
                    :title="$t('nav.signIn')"
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
          <v-list-item to="/dashboard" rounded prepend-icon="mdi-view-dashboard-outline" :title="$t('nav.dashboard')" />
          <v-list-item to="/products" rounded prepend-icon="mdi-tag-outline" :title="$t('nav.products')" />
          <v-list-item to="/notifications" rounded prepend-icon="mdi-bell-outline" :title="$t('notificationsPage.title')">
            <template v-if="notificationsStore.unreadCount > 0" #append>
              <v-chip size="x-small" color="error">{{ notificationsStore.unreadCount }}</v-chip>
            </template>
          </v-list-item>
          <template v-if="auth.isAdmin">
            <v-list-item to="/admin/dashboard" rounded prepend-icon="mdi-shield-account" :title="$t('nav.admin') + ': ' + $t('adminCommon.dashboard')" />
            <v-list-item to="/admin/users" rounded prepend-icon="mdi-account-group-outline" :title="$t('nav.admin') + ': ' + $t('adminCommon.users')" />
            <v-list-item to="/admin/products" rounded prepend-icon="mdi-package-variant-closed" :title="$t('nav.admin') + ': ' + $t('adminCommon.products')" />
            <v-list-item to="/admin/logs" rounded prepend-icon="mdi-text-box-search-outline" :title="$t('nav.admin') + ': ' + $t('adminCommon.logs')" />
            <v-list-item to="/admin/actions" rounded prepend-icon="mdi-history" :title="$t('nav.admin') + ': ' + $t('adminCommon.actions')" />
          </template>
          <v-divider class="my-2" />
          <v-list-item rounded prepend-icon="mdi-plus" :title="$t('nav.addProduct')" base-color="primary" @click="openAddProduct" />
          <v-divider class="my-2" />
          <v-list-item
            rounded
            :prepend-icon="isDark ? 'mdi-weather-sunny' : 'mdi-weather-night'"
            :title="isDark ? $t('ux.themeLight') : $t('ux.themeDark')"
            @click="toggleTheme"
          />
          <v-menu v-model="languageMenuMobile" location="bottom" offset="8">
            <template #activator="{ props }">
              <v-list-item
                v-bind="props"
                rounded
                prepend-icon="mdi-translate"
                :title="$t('nav.language')"
                :subtitle="currentLanguageLabel"
              />
            </template>

            <v-card min-width="220" rounded="lg" elevation="2">
              <v-list density="compact" class="py-1">
                <v-list-item :active="i18n.locale.value === 'en'" :title="i18n.t('ux.languageEnglish')" @click="setLanguage('en')" />
                <v-list-item :active="i18n.locale.value === 'lv'" :title="i18n.t('ux.languageLatvian')" @click="setLanguage('lv')" />
                <v-list-item :active="i18n.locale.value === 'ru'" :title="i18n.t('ux.languageRussian')" @click="setLanguage('ru')" />
              </v-list>
            </v-card>
          </v-menu>
          <v-list-item to="/settings" rounded prepend-icon="mdi-cog-outline" :title="$t('nav.settings')" />
          <v-divider class="my-2" />
          <v-list-item rounded prepend-icon="mdi-logout" :title="$t('nav.logout')" class="text-error" @click="handleLogout" />
        </v-list>
      </template>

      <template v-else>
        <v-list class="pa-4">
          <v-list-item to="/" rounded>{{ $t('nav.home') }}</v-list-item>
          <v-list-item to="/about" rounded>{{ $t('nav.about') }}</v-list-item>
          <v-divider class="my-3" />
          <v-list-item to="/login" rounded>{{ $t('nav.signIn') }}</v-list-item>
          <v-btn to="/register" color="primary" block rounded class="mt-2">{{ $t('nav.getStarted') }}</v-btn>
        </v-list>
      </template>
    </v-navigation-drawer>

    <!-- main content -->
    <v-main>
      <router-view />
    </v-main>

    <v-dialog v-model="showAddDialog" max-width="520">
      <v-card rounded="xl" class="pa-6">
        <h2 class="text-h6 font-weight-bold mb-4">{{ $t('nav.addProduct') }}</h2>

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
            :label="$t('form.url')"
            variant="outlined"
            rounded="lg"
            prepend-inner-icon="mdi-link-variant"
            :placeholder="$t('form.urlPlaceholder')"
            :error="addUrlErrors().length > 0"
            :error-messages="addUrlErrors()"
            :disabled="addLoading"
          />

          <v-select
            v-model="addForm.checkInterval"
            :label="$t('form.checkFrequency')"
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
            {{ $t('productsPage.supportedStores') }}
          </div>

          <div class="d-flex ga-2 justify-end mt-2">
            <v-btn variant="text" rounded="xl" :disabled="addLoading" @click="closeAddProduct">{{ $t('form.cancel') }}</v-btn>
            <v-btn type="submit" color="primary" rounded="xl" :loading="addLoading" prepend-icon="mdi-plus">{{ $t('form.track') }}</v-btn>
          </div>
        </v-form>
      </v-card>
    </v-dialog>

    <!-- footer -->
    <footer class="footer">
      <v-container>
        <div class="footer-content">
          <div class="logo">
            <img :src="siteLogoSrc" alt="PriceTracker logo" class="logo-mark logo-mark--sm" />
            <span>PriceTracker</span>
          </div>

          <div class="footer-links">
            <router-link to="/">{{ $t('nav.home') }}</router-link>
            <router-link to="/about">{{ $t('nav.about') }}</router-link>
            <router-link to="/terms">{{ $t('footer.terms') }}</router-link>
            <router-link to="/privacy">{{ $t('footer.privacy') }}</router-link>
            <router-link to="/disclaimer">{{ $t('footer.disclaimer') }}</router-link>
          </div>
        </div>

        <v-divider class="my-4" />

        <p class="copyright">© {{ new Date().getFullYear() }} PriceTracker. {{ $t('footer.copyright') }}</p>
      </v-container>
    </footer>

    <v-snackbar v-model="showLanguageNotice" timeout="2200" location="bottom right">
      {{ $t('messages.languageSwitcher') }}
    </v-snackbar>
  </v-app>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'
import { useTheme } from 'vuetify'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'
import { useProductsStore } from '@/stores/products'

const theme = useTheme()
const router = useRouter()
const i18n = useI18n()
const auth = useAuthStore()
const notificationsStore = useNotificationsStore()
const productsStore = useProductsStore()
const drawer = ref(false)
const scrolled = ref(false)
const showLanguageNotice = ref(false)
const notificationsMenu = ref(false)
const showAddDialog = ref(false)
const addFormRef = ref(null)
const addLoading = ref(false)
const addError = ref(null)
const addSubmitted = ref(false)
const languageMenuUser = ref(false)
const languageMenuGuest = ref(false)
const languageMenuMobile = ref(false)
const checkIntervalOptions = computed(() => [
  { label: i18n.t('form.every30min'), value: 30 },
  { label: i18n.t('form.every1h'), value: 60 },
  { label: i18n.t('form.every6h'), value: 360 },
  { label: i18n.t('form.every12h'), value: 720 },
  { label: i18n.t('form.every24h'), value: 1440 },
  { label: i18n.t('form.every3d'), value: 4320 },
  { label: i18n.t('form.every7d'), value: 10080 },
  { label: i18n.t('form.every14d'), value: 20160 },
])
const addForm = reactive({
  url: '',
  targetPrice: '',
  checkInterval: 1440,
})

function addUrlErrors() {
  if (!addSubmitted.value) return []

  const url = addForm.url?.trim() || ''
  if (!url) return [`${i18n.t('form.url')} ${i18n.t('form.required')}`]
  if (!/^https?:\/\/.+/.test(url)) return [i18n.t('form.invalidUrl')]
  return []
}

const isDark = computed(() => theme.global.current.value.dark)
const currentLanguageLabel = computed(() => {
  if (i18n.locale.value === 'lv') return i18n.t('ux.languageLatvian')
  if (i18n.locale.value === 'ru') return i18n.t('ux.languageRussian')
  return i18n.t('ux.languageEnglish')
})
const siteLogoSrc = computed(() => (isDark.value ? '/wombat.png' : '/wombat-blue.png'))
const recentNotifications = computed(() => notificationsStore.notifications.slice(0, 5))

function handleLogoClick() {
  router.push(auth.isAuthenticated ? '/dashboard' : '/')
}

function notificationText(notification) {
  return notification?.message || notification?.title || i18n.t('ux.notificationFallback')
}

function isTrackingStoppedNotification(notification) {
  const msg = (notification?.message || '').toLowerCase()
  return msg.includes('tracking stopped')
}

function notificationDate(value) {
  if (!value) return ''
  return new Date(value).toLocaleString(undefined, { timeZone: 'UTC' })
}

async function handleNotificationsMenu(isOpen) {
  if (!isOpen || !auth.isAuthenticated) return
  await notificationsStore.fetchUnreadCount()
  await notificationsStore.fetchNotifications(1)
}

async function openNotification(notification) {
  if (!notification) return

  if (!notification.is_read) {
    try {
      await notificationsStore.markRead(notification.id)
    } catch (_) {

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
  addSubmitted.value = false
  addForm.url = ''
  addForm.targetPrice = ''
  addForm.checkInterval = 1440
}

async function handleAddProduct() {
  addSubmitted.value = true
  if (addUrlErrors().length) return

  addLoading.value = true
  addError.value = null
  try {
    const result = await productsStore.addProduct({
      url: addForm.url.trim(),
      check_interval: addForm.checkInterval,
    })
    closeAddProduct()
    if (result?.product?.id) {
      router.push({ name: 'product-detail', params: { id: result.product.id } })
    }
  } catch (e) {
    addError.value = e.response?.data?.message || i18n.t('productsPage.failedAdd')
  } finally {
    addLoading.value = false
  }
}

function toggleTheme() {
  const nextTheme = isDark.value ? 'light' : 'dark'
  theme.global.name.value = nextTheme
  localStorage.setItem('pt-theme', nextTheme)
}

function setLanguage(lang) {
  if (!['en', 'lv', 'ru'].includes(lang)) return
  const closeLanguageMenus = () => {
    languageMenuUser.value = false
    languageMenuGuest.value = false
    languageMenuMobile.value = false
  }

  if (i18n.locale.value === lang) {
    closeLanguageMenus()
    return
  }

  i18n.locale.value = lang
  localStorage.setItem('pt-language', lang)
  showLanguageNotice.value = true
  closeLanguageMenus()
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

  if (auth.isAuthenticated) {
    notificationsStore.fetchUnreadCount()
  }

  pollInterval = setInterval(() => {
    if (auth.isAuthenticated) notificationsStore.fetchUnreadCount()
  }, 30000)
})
onUnmounted(() => clearInterval(pollInterval))

watch(
  () => auth.isAuthenticated,
  (isAuthenticated) => {
    if (isAuthenticated) {
      notificationsStore.fetchUnreadCount()
      return
    }

    notificationsStore.reset()
    notificationsMenu.value = false
  }
)

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

.logo-mark {
  width: 34px;
  height: 34px;
  object-fit: contain;
}

.logo-mark--sm {
  width: 28px;
  height: 28px;
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
  justify-content: center;
  align-items: center;
  flex-wrap: wrap;
  gap: 1.5rem;
  flex-direction: column;
}

.footer-links {
  display: flex;
  gap: 1.5rem;
  justify-content: center;
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