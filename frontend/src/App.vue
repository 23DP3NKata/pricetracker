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

        <div class="nav d-none d-md-flex">
          <v-btn to="/" variant="text" rounded>Home</v-btn>
          <v-btn to="/about" variant="text" rounded>About</v-btn>
          <template v-if="auth.isAuthenticated">
            <v-btn to="/dashboard" variant="text" rounded>Dashboard</v-btn>
            <v-btn to="/products" variant="text" rounded>Products</v-btn>
            <v-btn to="/notifications" variant="text" rounded>
              Notifications
              <v-badge v-if="notificationsStore.unreadCount > 0"
                       :content="notificationsStore.unreadCount"
                       color="error" floating />
            </v-btn>
            <v-btn to="/settings" variant="text" rounded>Settings</v-btn>
          </template>
        </div>

        <div class="d-none d-md-flex ml-4 ga-2">
          <template v-if="auth.isAuthenticated">
            <v-btn variant="text" rounded @click="handleLogout">Logout</v-btn>
          </template>
          <template v-else>
            <v-btn to="/login" variant="text" rounded>Sign In</v-btn>
            <v-btn to="/register" color="primary" rounded>Get Started</v-btn>
          </template>
        </div>

        <v-btn :icon="isDark ? 'mdi-weather-sunny' : 'mdi-weather-night'" 
               variant="text" @click="toggleTheme" class="ml-2" />

        <v-btn icon="mdi-menu" variant="text" class="d-md-none ml-2" @click="drawer = true" />
      </v-container>
    </v-app-bar>

    <!-- burger menu -->
    <v-navigation-drawer v-model="drawer" temporary location="right" width="280">
      <v-list class="pa-4">
        <v-list-item to="/" rounded>Home</v-list-item>
        <v-list-item to="/about" rounded>About</v-list-item>
        <template v-if="auth.isAuthenticated">
          <v-list-item to="/dashboard" rounded>Dashboard</v-list-item>
          <v-list-item to="/products" rounded>Products</v-list-item>
          <v-list-item to="/notifications" rounded>Notifications</v-list-item>
          <v-list-item to="/settings" rounded>Settings</v-list-item>
          <v-divider class="my-3" />
          <v-list-item rounded @click="handleLogout">Logout</v-list-item>
        </template>
        <template v-else>
          <v-divider class="my-3" />
          <v-list-item to="/login" rounded>Sign In</v-list-item>
          <v-btn to="/register" color="primary" block rounded class="mt-2">Get Started</v-btn>
        </template>
      </v-list>
    </v-navigation-drawer>

    <!-- main content -->
    <v-main>
      <router-view />
    </v-main>

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
  </v-app>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useTheme } from 'vuetify'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'

const theme = useTheme()
const router = useRouter()
const auth = useAuthStore()
const notificationsStore = useNotificationsStore()
const drawer = ref(false)
const scrolled = ref(false)

const isDark = computed(() => theme.global.current.value.dark)
const toggleTheme = () => theme.global.name.value = isDark.value ? 'light' : 'dark'

async function handleLogout() {
  await auth.logout()
  router.push('/')
}

// Poll unread count when authenticated
let pollInterval = null
onMounted(() => {
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
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.nav { gap: 0.5rem; }

.nav .v-btn, .v-app-bar .v-btn {
  text-transform: none;
  font-weight: 500;
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