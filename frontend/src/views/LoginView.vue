<template>
  <section class="auth-page">
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>

    <v-container class="auth-shell">
      <div class="auth-grid">
        <v-card rounded="xl" class="auth-card pa-6 pa-md-8">
          <div class="form-head mb-5">
            <v-avatar color="primary" variant="tonal" size="48">
              <v-icon>mdi-account-lock-outline</v-icon>
            </v-avatar>
            <div>
              <h2>{{ $t('auth.signIn') }}</h2>
              <p class="text-medium-emphasis">{{ $t('auth.signInAccount') }}</p>
            </div>
          </div>

          <v-alert v-if="auth.error" type="error" variant="tonal" rounded="lg" class="mb-4" closable @click:close="auth.error = null">
            {{ auth.error }}
          </v-alert>

          <v-form ref="formRef" @submit.prevent="handleLogin">
            <v-text-field
              v-model="form.email"
              :label="$t('auth.email')"
              type="email"
              variant="outlined"
              rounded="lg"
              prepend-inner-icon="mdi-email-outline"
              class="mb-2"
              autocomplete="email"
              name="email"
              :error="submitted && emailError.messages.length > 0"
              :error-messages="submitted ? emailError.messages : []"
            />

            <v-text-field
              v-model="form.password"
              :label="$t('auth.password')"
              :type="showPassword ? 'text' : 'password'"
              variant="outlined"
              rounded="lg"
              prepend-inner-icon="mdi-lock-outline"
              :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
              autocomplete="current-password"
              name="password"
              @click:append-inner="showPassword = !showPassword"
              :error="submitted && passwordError.messages.length > 0"
              :error-messages="submitted ? passwordError.messages : []"
            />

            <div class="d-flex align-center justify-space-between mb-4">
              <v-checkbox
                v-model="form.remember"
                :label="$t('auth.rememberMe')"
                density="compact"
                hide-details
                class="remember-checkbox"
              />
              <router-link to="/forgot-password" class="text-medium-emphasis text-body-2 auth-link">
                {{ $t('auth.forgotPassword') }}
              </router-link>
            </div>

            <v-btn
              type="submit"
              color="primary"
              size="large"
              rounded="xl"
              block
              :loading="auth.loading"
              class="submit-btn"
            >
              {{ $t('auth.signIn') }}
            </v-btn>
          </v-form>

          <div class="text-center mt-4 text-body-2">
            <span class="text-medium-emphasis">{{ $t('auth.dontHaveAccount') }}</span>
            <router-link to="/register" class="ml-1 text-primary font-weight-medium auth-link">{{ $t('auth.signUp') }}</router-link>
          </div>
        </v-card>
      </div>
    </v-container>
  </section>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from 'vue-i18n'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const { t } = useI18n()
const formRef = ref(null)
const showPassword = ref(false)

const submitted = ref(false)
const emailError = ref({ show: false, messages: [] })
const passwordError = ref({ show: false, messages: [] })

const form = reactive({
  email: '',
  password: '',
  remember: false,
})

onMounted(() => {
  setTimeout(() => {
    const emailInput = document.querySelector('input[type="email"]')
    const passInput = document.querySelector('input[type="password"]')
    if (emailInput?.value) form.email = emailInput.value
    if (passInput?.value) form.password = passInput.value
  }, 500)
})

function validateEmail() {
  const messages = []
  if (!form.email) {
    messages.push(t('auth.required'))
  } else if (!/.+@.+\..+/.test(form.email)) {
    messages.push(t('auth.invalidEmail'))
  }
  return messages
}

function validatePassword() {
  const messages = []
  if (!form.password) {
    messages.push(t('auth.required'))
  }
  return messages
}

function resolveSafeRedirect() {
  const rawRedirect = route.query.redirect
  let redirect = rawRedirect
  if (Array.isArray(rawRedirect)) {
    redirect = rawRedirect[0]
  }

  if (typeof redirect !== 'string' || !redirect.startsWith('/')) {
    return '/dashboard'
  }

  const resolved = router.resolve(redirect)
  if (!resolved?.name || resolved.name === 'not-found') {
    return '/dashboard'
  }

  return redirect
}

async function handleLogin() {
  submitted.value = true

  const emailMessages = validateEmail()
  const passwordMessages = validatePassword()

  emailError.value.messages = emailMessages
  emailError.value.show = emailMessages.length > 0

  passwordError.value.messages = passwordMessages
  passwordError.value.show = passwordMessages.length > 0

  if (emailError.value.show || passwordError.value.show) {
    return
  }

  try {
    await auth.login(form)
    router.push(resolveSafeRedirect())
  } catch {
    // error is handled in store
  }
}
</script>

<style scoped>
.auth-page {
  position: relative;
  min-height: calc(100vh - 64px);
  overflow: hidden;
  background:
    radial-gradient(700px 300px at 92% -8%, rgba(var(--v-theme-primary), 0.12), transparent 70%),
    radial-gradient(500px 240px at -8% 90%, rgba(var(--v-theme-secondary), 0.1), transparent 70%),
    rgb(var(--v-theme-background));
}

.bg-blob {
  position: absolute;
  border-radius: 999px;
  opacity: 0.35;
}

.blob-1 {
  width: 360px;
  height: 360px;
  right: -120px;
  top: -120px;
  background: rgba(var(--v-theme-primary), 0.25);
}

.blob-2 {
  width: 300px;
  height: 300px;
  left: -90px;
  bottom: -130px;
  background: rgba(var(--v-theme-secondary), 0.2);
}

.auth-shell {
  position: relative;
  z-index: 1;
  max-width: 760px;
  padding-top: 28px;
  padding-bottom: 28px;
}

.auth-grid {
  display: flex;
  justify-content: center;
}

.auth-card {
  width: 100%;
  max-width: 620px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  box-shadow: 0 18px 36px rgba(10, 20, 40, 0.08);
}

.form-head {
  display: flex;
  align-items: center;
  gap: 12px;
}

.form-head h2 {
  margin: 0;
  font-size: 1.55rem;
  line-height: 1.2;
}

.form-head p {
  margin: 2px 0 0;
}

.auth-link {
  text-decoration: none;
}

.submit-btn {
  height: 46px;
  text-transform: none;
  letter-spacing: 0.2px;
  font-weight: 700;
}

@media (max-width: 960px) {
  .auth-card {
    padding: 22px !important;
  }
}
</style>
