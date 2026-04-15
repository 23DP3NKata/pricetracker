<template>
  <section class="auth-page">
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>

    <v-container class="auth-shell">
      <div class="auth-grid">
        <v-card rounded="xl" class="auth-card pa-6 pa-md-8">
          <div class="form-head mb-5">
            <v-avatar color="primary" variant="tonal" size="48">
              <v-icon>mdi-lock-check-outline</v-icon>
            </v-avatar>
            <div>
              <h2>{{ $t('authRecovery.setNewPassword') }}</h2>
              <p class="text-medium-emphasis">{{ $t('authRecovery.enterNewPassword') }}</p>
            </div>
          </div>

          <v-alert v-if="successMsg" type="success" variant="tonal" rounded="lg" class="mb-4">
            {{ successMsg }}
            <div class="mt-2">
              <router-link to="/login" class="text-success font-weight-medium auth-link">{{ $t('authRecovery.goToSignIn') }}</router-link>
            </div>
          </v-alert>

          <v-alert v-if="errorMsg" type="error" variant="tonal" rounded="lg" class="mb-4" closable @click:close="errorMsg = null">
            {{ errorMsg }}
          </v-alert>

          <v-form v-if="!successMsg" @submit.prevent="handleSubmit" ref="formRef">
            <v-text-field
              v-model="form.email"
              :label="$t('auth.email')"
              type="email"
              variant="outlined"
              rounded="lg"
              prepend-inner-icon="mdi-email-outline"
              :rules="[v => !!v || $t('auth.required'), v => /.+@.+\..+/.test(v) || $t('auth.invalidEmail')]"
            />

            <v-text-field
              v-model="form.password"
              :label="$t('settings.newPassword')"
              :type="showPassword ? 'text' : 'password'"
              variant="outlined"
              rounded="lg"
              prepend-inner-icon="mdi-lock-outline"
              :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
              @click:append-inner="showPassword = !showPassword"
              :rules="[v => !!v || $t('auth.required'), v => v.length >= 8 || $t('auth.min8Chars')]"
            />

            <v-text-field
              v-model="form.password_confirmation"
              :label="$t('settings.confirmNewPassword')"
              :type="showPassword ? 'text' : 'password'"
              variant="outlined"
              rounded="lg"
              prepend-inner-icon="mdi-lock-check-outline"
              :rules="[v => !!v || $t('auth.required'), v => v === form.password || $t('settings.passwordsNoMatch')]"
            />

            <v-btn
              type="submit"
              color="primary"
              size="large"
              rounded="xl"
              block
              :loading="loading"
              class="submit-btn"
            >
              {{ $t('authRecovery.resetPasswordBtn') }}
            </v-btn>
          </v-form>

          <div class="text-center mt-4 text-body-2">
            <router-link to="/login" class="text-primary font-weight-medium auth-link">{{ $t('authRecovery.backToSignIn') }}</router-link>
          </div>
        </v-card>
      </div>
    </v-container>
  </section>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { resetPassword } from '@/api'

const route = useRoute()
const { t } = useI18n()
const formRef = ref(null)
const showPassword = ref(false)
const loading = ref(false)
const successMsg = ref(null)
const errorMsg = ref(null)

const form = reactive({
  token: '',
  email: '',
  password: '',
  password_confirmation: '',
})

onMounted(() => {
  form.token = route.params.token || ''
  const rawEmail = route.query.email
  if (Array.isArray(rawEmail)) {
    form.email = rawEmail[0] || ''
  } else {
    form.email = rawEmail || ''
  }
})

async function handleSubmit() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  loading.value = true
  errorMsg.value = null
  try {
    const { data } = await resetPassword(form)
    successMsg.value = data.status || t('authRecovery.resetSuccessFallback')
  } catch (e) {
    let message = e.response?.data?.message
    if (!message) {
      message = e.response?.data?.errors?.email?.[0]
    }
    if (!message) {
      message = e.response?.data?.errors?.password?.[0]
    }
    if (!message) {
      message = t('authRecovery.failedReset')
    }

    errorMsg.value = message
  } finally {
    loading.value = false
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

.submit-btn {
  margin-top: 10px;
  height: 46px;
  text-transform: none;
  letter-spacing: 0.2px;
  font-weight: 700;
}

.auth-link {
  text-decoration: none;
}

@media (max-width: 960px) {
  .auth-card {
    padding: 22px !important;
  }
}
</style>
