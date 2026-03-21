<template>
  <v-container class="auth-container" style="max-width: 480px;">
    <v-card rounded="xl" class="pa-6">
      <div class="text-center mb-6">
        <v-icon color="primary" size="48">mdi-chart-line-variant</v-icon>
        <h2 class="mt-2">{{ $t('auth.welcomeBack') }}</h2>
        <p class="text-medium-emphasis">{{ $t('auth.signInAccount') }}</p>
      </div>

      <v-alert v-if="auth.error" type="error" variant="tonal" rounded="lg" class="mb-4" closable @click:close="auth.error = null">
        {{ auth.error }}
      </v-alert>

      <v-form @submit.prevent="handleLogin" ref="formRef">
        <v-text-field
          v-model="form.email"
          :label="$t('auth.email')"
          type="email"
          variant="outlined"
          rounded="lg"
          prepend-inner-icon="mdi-email-outline"
          :rules="[v => !!v || $t('auth.required'), v => /.+@.+\..+/.test(v) || $t('auth.invalidEmail')]"
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
          :rules="[v => !!v || $t('auth.required')]"
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
          {{ $t('auth.signIn') }}
        </v-btn>
      </v-form>

      <div class="text-center mt-3">
        <router-link to="/forgot-password" class="text-medium-emphasis text-body-2">{{ $t('auth.forgotPassword') }}</router-link>
      </div>

      <div class="text-center mt-3">
        <span class="text-medium-emphasis">{{ $t('auth.dontHaveAccount') }}</span>
        <router-link to="/register" class="ml-1 text-primary font-weight-medium">{{ $t('auth.signUp') }}</router-link>
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
