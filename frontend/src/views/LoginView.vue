<template>
  <v-container class="auth-container" style="max-width: 480px;">
    <v-card rounded="xl" class="pa-6">
      <div class="text-center mb-6">
        <v-icon color="primary" size="48">mdi-chart-line-variant</v-icon>
        <h2 class="mt-2">Welcome Back</h2>
        <p class="text-medium-emphasis">Sign in to your account</p>
      </div>

      <v-alert v-if="auth.error" type="error" variant="tonal" rounded="lg" class="mb-4" closable @click:close="auth.error = null">
        {{ auth.error }}
      </v-alert>

      <v-form @submit.prevent="handleLogin" ref="formRef">
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
          :rules="[v => !!v || 'Required']"
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
          Sign In
        </v-btn>
      </v-form>

      <div class="text-center mt-4">
        <span class="text-medium-emphasis">Don't have an account?</span>
        <router-link to="/register" class="ml-1 text-primary font-weight-medium">Sign Up</router-link>
      </div>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const formRef = ref(null)
const showPassword = ref(false)

const form = reactive({
  email: '',
  password: '',
})

async function handleLogin() {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  try {
    await auth.login(form)
    router.push(route.query.redirect || '/dashboard')
  } catch {
    // error is handled in store
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
