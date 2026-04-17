<template>
  <v-container class="auth-container" style="max-width: 520px;">
    <v-card rounded="xl" class="pa-6 text-center">
      <v-icon :color="iconColor" size="64" class="mb-4">{{ iconName }}</v-icon>
      <h2 class="mb-2">{{ $t('authRecovery.verifyTitle') }}</h2>
      <p class="text-medium-emphasis mb-4">
        {{ subtitleText }}
      </p>

      <v-alert v-if="isAutoVerifying" type="info" variant="tonal" rounded="lg" class="mb-4">
        {{ $t('authRecovery.verifyingFromLink') }}
      </v-alert>

      <v-alert v-else-if="verifyDone && verifySuccess" type="success" variant="tonal" rounded="lg" class="mb-4">
        {{ verifySuccessText }}
      </v-alert>

      <v-alert v-else-if="verifyDone && !verifySuccess" type="error" variant="tonal" rounded="lg" class="mb-4">
        {{ verifyError || $t('authRecovery.verifyInvalidOrExpired') }}
      </v-alert>

      <v-alert v-if="sent" type="success" variant="tonal" rounded="lg" class="mb-4">
        {{ $t('authRecovery.verifyLinkSent') }}
      </v-alert>

      <v-alert v-if="error" type="error" variant="tonal" rounded="lg" class="mb-4" closable @click:close="error = null">
        {{ error }}
      </v-alert>

      <v-alert type="info" variant="tonal" rounded="lg" class="mb-6">
        {{ $t('authRecovery.verifyLimitInfo') }}
      </v-alert>

      <v-btn
        color="primary"
        rounded="xl"
        size="large"
        block
        :loading="loading || isAutoVerifying"
        :disabled="isAutoVerifying"
        @click="resend"
        prepend-icon="mdi-email-fast-outline"
      >
        {{ $t('authRecovery.resendVerificationEmail') }}
      </v-btn>

      <v-btn
        :to="verifySuccess ? '/login' : '/dashboard'"
        variant="text"
        rounded="xl"
        class="mt-3"
      >
        {{ verifySuccess ? $t('authRecovery.goToLogin') : $t('authRecovery.continueToDashboard') }}
      </v-btn>
    </v-card>
  </v-container>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { resendVerification, verifyEmail } from '@/api'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const loading = ref(false)
const sent = ref(false)
const error = ref(null)
const isAutoVerifying = ref(false)
const verifyDone = ref(false)
const verifySuccess = ref(false)
const verifyError = ref('')
const verifyStatus = ref('')

const isVerifiedFromQuery = computed(() => {
  const verified = String(route.query.verified || '').toLowerCase()
  const status = String(route.query.status || '').toLowerCase()

  return verified === '1' || verified === 'true' || status === 'already_verified' || status === 'verified'
})

const hasLinkData = computed(() => {
  return Boolean(route.params.id && route.params.hash && route.query.expires && route.query.signature)
})

const subtitleText = computed(() => {
  if (hasLinkData.value) {
    return t('authRecovery.verifyFromLinkSubtitle')
  }

  return t('authRecovery.verifySubtitle', { email: auth.user?.email || '' })
})

const iconName = computed(() => {
  if (isAutoVerifying.value) return 'mdi-email-sync-outline'
  if (verifyDone.value && verifySuccess.value) return 'mdi-email-check-outline'
  if (verifyDone.value && !verifySuccess.value) return 'mdi-email-remove-outline'
  return 'mdi-email-alert-outline'
})

const iconColor = computed(() => {
  if (isAutoVerifying.value) return 'info'
  if (verifyDone.value && verifySuccess.value) return 'success'
  if (verifyDone.value && !verifySuccess.value) return 'error'
  return 'warning'
})

const verifySuccessText = computed(() => {
  if (verifyStatus.value === 'already_verified') {
    return t('authRecovery.verifyAlreadyVerifiedMessage')
  }

  return t('authRecovery.verifySuccessMessage')
})

async function autoVerifyFromLink() {
  if (isVerifiedFromQuery.value) {
    verifyStatus.value = String(route.query.status || 'already_verified').toLowerCase()

    if (auth.isAuthenticated) {
      try {
        await auth.fetchUser()
      } catch {
        // Ignore fetch errors here; dashboard will still load.
      }
    }

    await router.replace({
      path: '/dashboard',
      query: { verified: '1', status: String(route.query.status || 'already_verified') },
    })
    return
  }

  if (!hasLinkData.value) return

  isAutoVerifying.value = true
  verifyDone.value = false
  verifySuccess.value = false
  verifyError.value = ''

  try {
    const response = await verifyEmail(route.params.id, route.params.hash, {
      expires: route.query.expires,
      signature: route.query.signature,
    })

    const status = response?.data?.status
    const verified = response?.data?.verified
    verifyStatus.value = String(status || '').toLowerCase()

    if (status === 'already_verified' || status === 'verified' || verified === true) {
      if (auth.isAuthenticated) {
        try {
          await auth.fetchUser()
        } catch {
          // Ignore fetch errors here; dashboard will still load.
        }
      }

      verifySuccess.value = true
      await router.replace({
        path: '/dashboard',
        query: { verified: '1', status: status || 'verified' },
      })
      return
    }

    if (auth.isAuthenticated) {
      try {
        await auth.fetchUser()
      } catch {
        // Ignore fetch errors here; dashboard will still load.
      }
    }

    verifySuccess.value = true
    verifyStatus.value = 'verified'
    await router.replace({
      path: '/dashboard',
      query: { verified: '1', status: 'verified' },
    })
  } catch (e) {
    verifyStatus.value = ''
    verifyError.value = e.response?.data?.message || t('authRecovery.verifyFailedTryResend')
  } finally {
    verifyDone.value = true
    isAutoVerifying.value = false
  }
}

async function resend() {
  loading.value = true
  sent.value = false
  error.value = null
  try {
    await resendVerification()
    sent.value = true
  } catch (e) {
    error.value = e.response?.data?.message || t('authRecovery.failedSendVerification')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  autoVerifyFromLink()
})
</script>

<style scoped>
.auth-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 70vh;
}
</style>
