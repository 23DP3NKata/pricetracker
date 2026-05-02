<template>
  <v-container class="auth-container" style="max-width: 480px;">
    <v-card rounded="xl" class="pa-6">
      <div class="text-center mb-6">
        <v-icon color="primary" size="48">mdi-lock-reset</v-icon>
        <h2 class="mt-2">{{ $t('authRecovery.forgotTitle') }}</h2>
        <p class="text-medium-emphasis">{{ $t('authRecovery.forgotSubtitle') }}</p>
      </div>

      <v-alert v-if="successMsg" type="success" variant="tonal" rounded="lg" class="mb-4">
        {{ successMsg }}
      </v-alert>

      <v-alert v-if="errorMsg" type="error" variant="tonal" rounded="lg" class="mb-4" closable @click:close="errorMsg = null">
        {{ errorMsg }}
      </v-alert>

      <v-form v-if="!successMsg" @submit.prevent="handleSubmit" ref="formRef">
        <v-text-field
          v-model="email"
          :label="$t('auth.email')"
          type="email"
          variant="outlined"
          rounded="lg"
          prepend-inner-icon="mdi-email-outline"
          :error="submitted && emailError.messages.length > 0"
          :error-messages="submitted ? emailError.messages : []"
        />

        <v-btn
          type="submit"
          color="primary"
          size="large"
          rounded="xl"
          block
          :loading="loading"
          :disabled="timerActive && timerRemaining > 0"
          class="mt-2"
        >
          <span v-if="timerActive && timerRemaining > 0">
            {{ $t('authRecovery.waitBefore') }} {{ timerRemaining }}s
          </span>
          <span v-else>
            {{ $t('authRecovery.sendResetLink') }}
          </span>
        </v-btn>
      </v-form>

      <div class="text-center mt-4">
        <router-link to="/login" class="text-primary font-weight-medium">{{ $t('authRecovery.backToSignIn') }}</router-link>
      </div>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { forgotPassword } from '@/api'

const COOLDOWN_SECONDS = 60
const TIMER_KEY = 'forgot_password_timer'

const formRef = ref(null)
const { t } = useI18n()
const email = ref('')
const loading = ref(false)
const successMsg = ref(null)
const errorMsg = ref(null)
const timerActive = ref(false)
const timerRemaining = ref(0)
const submitted = ref(false)
const emailError = ref({ show: false, messages: [] })
let timerInterval = null

function validateEmail() {
  const messages = []
  if (!email.value) {
    messages.push(t('auth.required'))
  } else if (!/.+@.+\..+/.test(email.value)) {
    messages.push(t('auth.invalidEmail'))
  }
  return messages
}

function startCooldown() {
  const now = Date.now()
  const expireTime = now + COOLDOWN_SECONDS * 1000
  localStorage.setItem(TIMER_KEY, expireTime.toString())
  timerActive.value = true
  updateTimer()
}

function updateTimer() {
  const expireTime = parseInt(localStorage.getItem(TIMER_KEY) || '0')
  const now = Date.now()
  const remaining = Math.max(0, Math.ceil((expireTime - now) / 1000))
  
  timerRemaining.value = remaining
  
  if (remaining <= 0) {
    timerActive.value = false
    localStorage.removeItem(TIMER_KEY)
    if (timerInterval) {
      clearInterval(timerInterval)
    }
  }
}

function checkExistingTimer() {
  const expireTime = parseInt(localStorage.getItem(TIMER_KEY) || '0')
  if (expireTime > 0) {
    updateTimer()
    if (timerRemaining.value > 0) {
      timerActive.value = true
      if (!timerInterval) {
        timerInterval = setInterval(updateTimer, 100)
      }
    }
  }
}

onMounted(() => {
  checkExistingTimer()
})

onUnmounted(() => {
  if (timerInterval) {
    clearInterval(timerInterval)
  }
})

async function handleSubmit() {
  if (timerActive.value && timerRemaining.value > 0) {
    return
  }

  submitted.value = true
  const emailMessages = validateEmail()
  emailError.value.messages = emailMessages
  emailError.value.show = emailMessages.length > 0

  if (emailError.value.show) {
    return
  }

  loading.value = true
  errorMsg.value = null
  try {
    const { data } = await forgotPassword(email.value)
    successMsg.value = data.status || t('authRecovery.resetLinkSentFallback')
    startCooldown()
  } catch (e) {
    let message = e.response?.data?.message
    if (!message) {
      message = e.response?.data?.errors?.email?.[0]
    }
    if (!message) {
      message = t('authRecovery.failedSendReset')
    }

    errorMsg.value = message
  } finally {
    loading.value = false
    if (!errorMsg.value) {
      startCooldown()
      if (!timerInterval) {
        timerInterval = setInterval(updateTimer, 100)
      }
    }
  }
}
</script>

<style scoped>
.auth-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 70vh;
}
</style>
