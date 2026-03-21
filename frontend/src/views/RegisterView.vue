<template>
  <v-container class="auth-container" style="max-width: 480px;">
    <v-card rounded="xl" class="pa-6">
      <div class="text-center mb-6">
        <v-icon color="primary" size="48">mdi-chart-line-variant</v-icon>
        <h2 class="mt-2">{{ $t('auth.createAccount') }}</h2>
        <p class="text-medium-emphasis">{{ $t('auth.startTrackingToday') }}</p>
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
          :label="$t('auth.name')"
          variant="outlined"
          rounded="lg"
          prepend-inner-icon="mdi-account-outline"
          :rules="[v => !!v || $t('auth.required'), v => v.length >= 2 || $t('auth.min2Chars')]"
        />

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
          :rules="[v => !!v || $t('auth.required'), v => v.length >= 8 || $t('auth.min8Chars')]"
        />

        <v-text-field
          v-model="form.password_confirmation"
          :label="$t('auth.confirmPassword')"
          :type="showPassword ? 'text' : 'password'"
          variant="outlined"
          rounded="lg"
          prepend-inner-icon="mdi-lock-check-outline"
          :rules="[v => !!v || $t('auth.required'), v => v === form.password || $t('auth.passwordsDontMatch')]"
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
          {{ $t('auth.createAccountBtn') }}
        </v-btn>
      </v-form>

      <div class="text-center mt-4">
        <span class="text-medium-emphasis">{{ $t('auth.alreadyHaveAccount') }}</span>
        <router-link to="/login" class="ml-1 text-primary font-weight-medium">{{ $t('auth.signIn') }}</router-link>
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
