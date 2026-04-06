<template>
  <section class="auth-page">
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>

    <v-container class="auth-shell">
      <div class="auth-grid">
        <v-card rounded="xl" class="auth-card pa-6 pa-md-8">
          <div class="form-head mb-5">
            <v-avatar color="primary" variant="tonal" size="48">
              <v-icon>mdi-account-plus-outline</v-icon>
            </v-avatar>
            <div>
              <h2>{{ $t('auth.createAccountBtn') }}</h2>
              <p class="text-medium-emphasis">{{ $t('auth.startTrackingToday') }}</p>
            </div>
          </div>

          <v-alert v-if="auth.error" type="error" variant="tonal" rounded="lg" class="mb-4" closable @click:close="auth.error = null">
            {{ auth.error }}
          </v-alert>

          <v-alert v-if="errors" type="error" variant="tonal" rounded="lg" class="mb-4" closable @click:close="errors = null">
            <ul class="error-list">
              <template v-for="(msgs, field) in errors" :key="field">
                <li v-for="msg in msgs" :key="msg">{{ msg }}</li>
              </template>
            </ul>
          </v-alert>

          <v-form ref="formRef" @submit.prevent="handleRegister">
            <v-text-field
              v-model="form.name"
              :label="$t('auth.name')"
              variant="outlined"
              rounded="lg"
              prepend-inner-icon="mdi-account-outline"
              class="mb-2"
              :rules="[v => !!v || $t('auth.required'), v => v.length >= 2 || $t('auth.min2Chars')]"
            />

            <v-text-field
              v-model="form.email"
              :label="$t('auth.email')"
              type="email"
              variant="outlined"
              rounded="lg"
              prepend-inner-icon="mdi-email-outline"
              class="mb-2"
              :rules="[v => !!v || $t('auth.required'), v => /.+@.+\..+/.test(v) || $t('auth.invalidEmail')]"
            />

            <v-text-field
              v-model="form.password"
              :label="$t('auth.password')"
              :type="showPassword ? 'text' : 'password'"
              variant="outlined"
              rounded="lg"
              prepend-inner-icon="mdi-lock-outline"
              :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
              @click:append-inner="showPassword = !showPassword"
              class="mb-2"
              :rules="[v => !!v || $t('auth.required'), v => v.length >= 8 || $t('auth.min8Chars')]"
            />

            <v-text-field
              v-model="form.password_confirmation"
              :label="$t('auth.confirmPassword')"
              :type="showPassword ? 'text' : 'password'"
              variant="outlined"
              rounded="lg"
              prepend-inner-icon="mdi-lock-check-outline"
              :rules="[v => !!v || $t('auth.required'), v => v === form.password || $t('auth.passwordsDontMatch')]"
            />

            <v-btn
              type="submit"
              color="primary"
              size="large"
              rounded="xl"
              block
              :loading="auth.loading"
              class="submit-btn"
            >
              {{ $t('auth.createAccountBtn') }}
            </v-btn>
          </v-form>

          <div class="text-center mt-4 text-body-2">
            <span class="text-medium-emphasis">{{ $t('auth.alreadyHaveAccount') }}</span>
            <router-link to="/login" class="ml-1 text-primary font-weight-medium auth-link">{{ $t('auth.signIn') }}</router-link>
          </div>
        </v-card>
      </div>
    </v-container>
  </section>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const formRef = ref(null)
const showPassword = ref(false)
const errors = ref(null)

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

async function handleRegister() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  errors.value = null
  try {
    await auth.register(form)
    router.push('/verify-email')
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors
    }
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
    radial-gradient(500px 240px at -8% 90%, rgba(var(--v-theme-success), 0.1), transparent 70%),
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
  background: rgba(var(--v-theme-success), 0.2);
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

.error-list {
  margin: 0;
  padding-left: 18px;
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
