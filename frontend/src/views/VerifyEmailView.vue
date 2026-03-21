<template>
  <v-container class="auth-container" style="max-width: 520px;">
    <v-card rounded="xl" class="pa-6 text-center">
      <v-icon color="warning" size="64" class="mb-4">mdi-email-alert-outline</v-icon>
      <h2 class="mb-2">{{ $t('authRecovery.verifyTitle') }}</h2>
      <p class="text-medium-emphasis mb-6">
        {{ $t('authRecovery.verifySubtitle', { email: auth.user?.email || '' }) }}
      </p>

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
        :loading="loading"
        @click="resend"
        prepend-icon="mdi-email-fast-outline"
      >
        {{ $t('authRecovery.resendVerificationEmail') }}
      </v-btn>

      <v-btn
        to="/dashboard"
        variant="text"
        rounded="xl"
        class="mt-3"
      >
        {{ $t('authRecovery.continueToDashboard') }}
      </v-btn>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { resendVerification } from '@/api'

const auth = useAuthStore()
const { t } = useI18n()
const loading = ref(false)
const sent = ref(false)
const error = ref(null)

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
</script>

<style scoped>
.auth-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 70vh;
}
</style>
