<template>
  <v-container class="auth-container" style="max-width: 480px;">
    <v-card rounded="xl" class="pa-6">
      <div class="text-center mb-6">
        <v-icon color="primary" size="48">mdi-chart-line-variant</v-icon>
        <h2 class="mt-2">Create Account</h2>
        <p class="text-medium-emphasis">Start tracking prices today</p>
      </div>

      <v-alert v-if="auth.error" type="error" variant="tonal" rounded="lg" class="mb-4" closable @click:close="auth.error = null">
        {{ auth.error }}
      </v-alert>

      <v-alert v-if="errors" type="error" variant="tonal" rounded="lg" class="mb-4" closable @click:close="errors = null">
        <ul class="pl-4">
          <template v-for="(msgs, field) in errors" :key="field">
            <li v-for="msg in msgs" :key="msg">{{ msg }}</li>
          </template>
        </ul>
      </v-alert>

      <v-form @submit.prevent="handleRegister" ref="formRef">
        <v-text-field
          v-model="form.name"
          label="Name"
          variant="outlined"
          rounded="lg"
          prepend-inner-icon="mdi-account-outline"
          :rules="[v => !!v || 'Required', v => v.length >= 2 || 'Min 2 characters']"
        />

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
          label="Password"
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
          label="Confirm Password"
          :type="showPassword ? 'text' : 'password'"
          variant="outlined"
          rounded="lg"
          prepend-inner-icon="mdi-lock-check-outline"
          :rules="[v => !!v || 'Required', v => v === form.password || 'Passwords don\'t match']"
        />

        <v-btn
          type="submit"
          color="primary"
          size="large"
          rounded="xl"
          block
          :loading="auth.loading"
          class="mt-2"
        >
          Create Account
        </v-btn>
      </v-form>

      <div class="text-center mt-4">
        <span class="text-medium-emphasis">Already have an account?</span>
        <router-link to="/login" class="ml-1 text-primary font-weight-medium">Sign In</router-link>
      </div>
    </v-card>
  </v-container>
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
.auth-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 70vh;
}
</style>
