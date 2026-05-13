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

          <v-alert v-if="serverError" type="error" variant="tonal" rounded="lg" class="mb-4" closable @click:close="serverError = null">
            {{ serverError }}
          </v-alert>

          <v-form ref="formRef" @submit.prevent="handleRegister">
            <v-text-field
              v-model="form.name"
              :label="$t('auth.name')"
              variant="outlined"
              rounded="lg"
              prepend-inner-icon="mdi-account-outline"
              class="mb-2"
              @input="clearNameError"
              :error="submitted && nameError.messages.length > 0"
              :error-messages="submitted ? nameError.messages : []"
            />

            <v-text-field
              v-model="form.email"
              :label="$t('auth.email')"
              type="email"
              variant="outlined"
              rounded="lg"
              prepend-inner-icon="mdi-email-outline"
              class="mb-2"
              @input="clearEmailError"
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
              @click:append-inner="showPassword = !showPassword"
              @input="passwordTouched = true; clearPasswordError()"
              class="mb-2"
              :error="submitted && passwordError.messages.length > 0"
              :error-messages="submitted ? passwordError.messages : []"
            />

            <div v-if="passwordTouched" :class="['password-reqs', `password-reqs--${passwordStrength}`]">
              <div class="req-item" :class="{ 'req-item--met': passwordChecks.minLength }">
                <v-icon :class="passwordChecks.minLength ? 'req-ok' : 'req-fail'" size="small">{{ passwordChecks.minLength ? 'mdi-check-circle' : 'mdi-close-circle' }}</v-icon>
                <span>{{ $t('auth.password_requirements.min_length') }}</span>
              </div>
              <div class="req-item" :class="{ 'req-item--met': passwordChecks.lowercase }">
                <v-icon :class="passwordChecks.lowercase ? 'req-ok' : 'req-fail'" size="small">{{ passwordChecks.lowercase ? 'mdi-check-circle' : 'mdi-close-circle' }}</v-icon>
                <span>{{ $t('auth.password_requirements.lowercase') }}</span>
              </div>
              <div class="req-item" :class="{ 'req-item--met': passwordChecks.uppercase }">
                <v-icon :class="passwordChecks.uppercase ? 'req-ok' : 'req-fail'" size="small">{{ passwordChecks.uppercase ? 'mdi-check-circle' : 'mdi-close-circle' }}</v-icon>
                <span>{{ $t('auth.password_requirements.uppercase') }}</span>
              </div>
              <div class="req-item" :class="{ 'req-item--met': passwordChecks.numbers }">
                <v-icon :class="passwordChecks.numbers ? 'req-ok' : 'req-fail'" size="small">{{ passwordChecks.numbers ? 'mdi-check-circle' : 'mdi-close-circle' }}</v-icon>
                <span>{{ $t('auth.password_requirements.numbers') }}</span>
              </div>
              <div class="req-item" :class="{ 'req-item--met': passwordChecks.symbols }">
                <v-icon :class="passwordChecks.symbols ? 'req-ok' : 'req-fail'" size="small">{{ passwordChecks.symbols ? 'mdi-check-circle' : 'mdi-close-circle' }}</v-icon>
                <span>{{ $t('auth.password_requirements.symbols') }}</span>
              </div>
            </div>

            <v-text-field
              v-model="form.password_confirmation"
              :label="$t('auth.confirmPassword')"
              :type="showPassword ? 'text' : 'password'"
              variant="outlined"
              rounded="lg"
              prepend-inner-icon="mdi-lock-check-outline"
              @input="clearConfirmError"
              :error="submitted && confirmError.messages.length > 0"
              :error-messages="submitted ? confirmError.messages : []"
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
import { ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from 'vue-i18n'

const auth = useAuthStore()
const router = useRouter()
const { t } = useI18n()
const formRef = ref(null)
const showPassword = ref(false)
const serverError = ref(null)
const passwordTouched = ref(false)

const submitted = ref(false)
const nameError = ref({ show: false, messages: [] })
const emailError = ref({ show: false, messages: [] })
const passwordError = ref({ show: false, messages: [] })
const confirmError = ref({ show: false, messages: [] })

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const passwordChecks = computed(() => {
  const value = form.password || ''
  return {
    minLength: value.length >= 8,
    lowercase: /[a-z]/.test(value),
    uppercase: /[A-Z]/.test(value),
    numbers: /\d/.test(value),
    symbols: /[^A-Za-z0-9]/.test(value),
  }
})

const passwordStrength = computed(() => {
  if (!form.password) return 'empty'

  const metCount = Object.values(passwordChecks.value).filter(Boolean).length
  if (metCount <= 2) return 'weak'
  if (metCount <= 4) return 'medium'
  return 'strong'
})

const nameAllowedPattern = /^[\p{L}\p{N}]+$/u
const nameHasLetterPattern = /[\p{L}]/u
const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/

function clearNameError() {
  nameError.value.messages = []
  nameError.value.show = false
}

function clearEmailError() {
  emailError.value.messages = []
  emailError.value.show = false
}

function clearPasswordError() {
  passwordError.value.messages = []
  passwordError.value.show = false
}

function clearConfirmError() {
  confirmError.value.messages = []
  confirmError.value.show = false
}

function setFieldErrors({ nameMessages, emailMessages, passwordMessages, confirmMessages }) {
  nameError.value.messages = nameMessages
  nameError.value.show = nameMessages.length > 0

  emailError.value.messages = emailMessages
  emailError.value.show = emailMessages.length > 0

  passwordError.value.messages = passwordMessages
  passwordError.value.show = passwordMessages.length > 0

  confirmError.value.messages = confirmMessages
  confirmError.value.show = confirmMessages.length > 0
}

function validateName() {
  const messages = []
  if (!form.name) {
    messages.push(t('auth.required'))
  } else {
    if (form.name.length < 3) {
      messages.push(t('auth.min3Chars'))
    }

    if (!nameAllowedPattern.test(form.name)) {
      messages.push(t('auth.onlyLettersNumbers'))
    } else if (!nameHasLetterPattern.test(form.name)) {
      messages.push(t('auth.mustIncludeLetter'))
    }
  }
  return messages
}

function validateEmail() {
  const messages = []
  if (!form.email) {
    messages.push(t('auth.required'))
  } else if (!emailPattern.test(form.email)) {
    messages.push(t('auth.invalidEmail'))
  }
  return messages
}

function validatePassword() {
  const messages = []
  if (!form.password) {
    messages.push(t('auth.required'))
  } else {
    if (!passwordChecks.value.minLength) messages.push(t('auth.password_requirements.min_length'))
    if (!passwordChecks.value.lowercase) messages.push(t('auth.password_requirements.lowercase'))
    if (!passwordChecks.value.uppercase) messages.push(t('auth.password_requirements.uppercase'))
    if (!passwordChecks.value.numbers) messages.push(t('auth.password_requirements.numbers'))
    if (!passwordChecks.value.symbols) messages.push(t('auth.password_requirements.symbols'))
  }
  return messages
}

function validateConfirm() {
  const messages = []
  if (!form.password_confirmation) {
    messages.push(t('auth.required'))
  } else if (form.password_confirmation !== form.password) {
    messages.push(t('auth.passwordsDontMatch'))
  }
  return messages
}

async function handleRegister() {
  submitted.value = true
  serverError.value = null

  const nameMessages = validateName()
  const emailMessages = validateEmail()
  const passwordMessages = validatePassword()
  const confirmMessages = validateConfirm()

  setFieldErrors({
    nameMessages,
    emailMessages,
    passwordMessages,
    confirmMessages,
  })

  if (nameError.value.show || emailError.value.show || passwordError.value.show || confirmError.value.show) {
    return
  }

  try {
    await auth.register(form)
    router.push('/verify-email')
  } catch (e) {
    if (e.response?.status === 422 && e.response?.data?.errors) {
      applyServerErrors(e.response.data.errors)
      return
    }
    serverError.value = e.response?.data?.message || t('auth.registrationFailed')
  }
}

function applyServerErrors(serverErrors) {
  submitted.value = true

  const nameMessages = serverErrors.name ? validateName() : []
  if (serverErrors.name && nameMessages.length === 0) {
    nameMessages.push(t('auth.nameInvalid'))
  }

  const emailMessages = serverErrors.email ? validateEmail() : []
  if (serverErrors.email && emailMessages.length === 0) {
    const errorMsg = Array.isArray(serverErrors.email) ? serverErrors.email[0] : serverErrors.email
    const isTakenError = typeof errorMsg === 'string' && errorMsg.toLowerCase().includes('already')
    emailMessages.push(isTakenError ? t('auth.emailTaken') : t('auth.invalidEmail'))
  }

  const passwordMessages = serverErrors.password ? validatePassword() : []
  if (serverErrors.password && passwordMessages.length === 0) {
    passwordMessages.push(t('auth.validation.password'))
  }

  const confirmMessages = serverErrors.password_confirmation ? validateConfirm() : []

  setFieldErrors({
    nameMessages,
    emailMessages,
    passwordMessages,
    confirmMessages,
  })
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

.password-reqs {
  --req-color: rgba(var(--v-theme-on-surface), 0.45);
  margin: 2px 0 12px;
  padding: 8px 10px;
  border-radius: 12px;
  border-left: 3px solid var(--req-color);
  background: rgba(var(--v-theme-on-surface), 0.03);
  display: grid;
  gap: 4px;
  font-size: 0.85rem;
}

.password-reqs--weak {
  --req-color: rgb(var(--v-theme-error));
  background: rgba(var(--v-theme-error), 0.08);
}

.password-reqs--medium {
  --req-color: rgb(var(--v-theme-warning));
  background: rgba(var(--v-theme-warning), 0.1);
}

.password-reqs--strong {
  --req-color: rgb(var(--v-theme-success));
  background: rgba(var(--v-theme-success), 0.1);
}

.req-item {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--req-color);
}

.req-item--met {
  color: rgb(var(--v-theme-success));
}

.req-ok {
  color: #22c55e !important;
}

.req-fail {
  color: #ef4444 !important;
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
