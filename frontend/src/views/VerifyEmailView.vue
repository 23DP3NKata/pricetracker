<template>
  <v-container class="auth-container" style="max-width: 520px;">
    <v-card rounded="xl" class="pa-6 text-center">
      <v-icon color="warning" size="64" class="mb-4">mdi-email-alert-outline</v-icon>
      <h2 class="mb-2">Verify Your Email</h2>
      <p class="text-medium-emphasis mb-6">
        We sent a verification link to <strong>{{ auth.user?.email }}</strong>.<br>
        Please check your inbox and click the link to activate your account.
      </p>

      <v-alert v-if="sent" type="success" variant="tonal" rounded="lg" class="mb-4">
        Verification link sent! Check your email.
      </v-alert>

      <v-alert v-if="error" type="error" variant="tonal" rounded="lg" class="mb-4" closable @click:close="error = null">
        {{ error }}
      </v-alert>

      <v-alert type="info" variant="tonal" rounded="lg" class="mb-6">
        Without verification your monthly check limit is <strong>5</strong>.
        After verifying it will be raised to <strong>180</strong>.
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
        Resend Verification Email
      </v-btn>

      <v-btn
        to="/dashboard"
        variant="text"
        rounded="xl"
        class="mt-3"
      >
        Continue to Dashboard
      </v-btn>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { resendVerification } from '@/api'

const auth = useAuthStore()
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
    error.value = e.response?.data?.message || 'Failed to send verification email'
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
