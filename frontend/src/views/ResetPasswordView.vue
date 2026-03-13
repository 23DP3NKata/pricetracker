<template>
  <v-container class="auth-container" style="max-width: 480px;">
    <v-card rounded="xl" class="pa-6">
      <div class="text-center mb-6">
        <v-icon color="primary" size="48">mdi-lock-check-outline</v-icon>
        <h2 class="mt-2">Set New Password</h2>
        <p class="text-medium-emphasis">Enter your new password below</p>
      </div>

      <v-alert v-if="successMsg" type="success" variant="tonal" rounded="lg" class="mb-4">
        {{ successMsg }}
        <div class="mt-2">
          <router-link to="/login" class="text-success font-weight-medium">Go to Sign In</router-link>
        </div>
      </v-alert>

      <v-alert v-if="errorMsg" type="error" variant="tonal" rounded="lg" class="mb-4" closable @click:close="errorMsg = null">
        {{ errorMsg }}
      </v-alert>

      <v-form v-if="!successMsg" @submit.prevent="handleSubmit" ref="formRef">
        <v-text-field
          v-model="form.email"
          label="Email"
          type="email"
          variant="outlined"
          rounded="lg"
          prepend-inner-icon="mdi-email-outline"
          :rules="[v => !!v || 'Required', v => /.+@.+\..+/.test(v) || 'Invalid email']"
        />

        <v-text-field
          v-model="form.password"
          label="New Password"
          :type="showPassword ? 'text' : 'password'"
          variant="outlined"
          rounded="lg"
          prepend-inner-icon="mdi-lock-outline"
          :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
          @click:append-inner="showPassword = !showPassword"
          :rules="[v => !!v || 'Required', v => v.length >= 8 || 'Min 8 characters']"
        />

        <v-text-field
          v-model="form.password_confirmation"
          label="Confirm New Password"
          :type="showPassword ? 'text' : 'password'"
          variant="outlined"
          rounded="lg"
          prepend-inner-icon="mdi-lock-check-outline"
          :rules="[v => !!v || 'Required', v => v === form.password || 'Passwords do not match']"
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
          Reset Password
        </v-btn>
      </v-form>

      <div class="text-center mt-4">
        <router-link to="/login" class="text-primary font-weight-medium">Back to Sign In</router-link>
      </div>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { resetPassword } from '@/api'

const route = useRoute()
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
  form.email = route.query.email || ''
})

async function handleSubmit() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  loading.value = true
  errorMsg.value = null
  try {
    const { data } = await resetPassword(form)
    successMsg.value = data.status || 'Password reset successfully!'
  } catch (e) {
    errorMsg.value = e.response?.data?.message
      || e.response?.data?.errors?.email?.[0]
      || e.response?.data?.errors?.password?.[0]
      || 'Failed to reset password'
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
