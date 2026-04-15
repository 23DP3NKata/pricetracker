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
          :rules="[v => !!v || $t('auth.required'), v => /.+@.+\..+/.test(v) || $t('auth.invalidEmail')]"
        />

        <v-btn
          type="submit"
          color="primary"
          size="large"
          rounded="xl"
          block
          :loading="loading"
          class="mt-2"
        >
          {{ $t('authRecovery.sendResetLink') }}
        </v-btn>
      </v-form>

      <div class="text-center mt-4">
        <router-link to="/login" class="text-primary font-weight-medium">{{ $t('authRecovery.backToSignIn') }}</router-link>
      </div>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { forgotPassword } from '@/api'

const formRef = ref(null)
const { t } = useI18n()
const email = ref('')
const loading = ref(false)
const successMsg = ref(null)
const errorMsg = ref(null)

async function handleSubmit() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  loading.value = true
  errorMsg.value = null
  try {
    const { data } = await forgotPassword(email.value)
    successMsg.value = data.status || t('authRecovery.resetLinkSentFallback')
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
