<template>
  <v-container class="auth-container" style="max-width: 480px;">
    <v-card rounded="xl" class="pa-6">
      <div class="text-center mb-6">
        <v-icon color="primary" size="48">mdi-lock-reset</v-icon>
        <h2 class="mt-2">Forgot Password?</h2>
        <p class="text-medium-emphasis">Enter your email and we'll send you a reset link</p>
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
          label="Email"
          type="email"
          variant="outlined"
          rounded="lg"
          prepend-inner-icon="mdi-email-outline"
          :rules="[v => !!v || 'Required', v => /.+@.+\..+/.test(v) || 'Invalid email']"
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
          Send Reset Link
        </v-btn>
      </v-form>

      <div class="text-center mt-4">
        <router-link to="/login" class="text-primary font-weight-medium">Back to Sign In</router-link>
      </div>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref } from 'vue'
import { forgotPassword } from '@/api'

const formRef = ref(null)
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
    successMsg.value = data.status || 'Reset link sent! Check your email.'
  } catch (e) {
    errorMsg.value = e.response?.data?.message
      || e.response?.data?.errors?.email?.[0]
      || 'Failed to send reset link'
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
