<template>
  <v-container class="py-8" style="max-width: 700px;">
    <div class="settings-head mb-6">
      <h1 class="text-h4 font-weight-bold">{{ $t('settings.title') }}</h1>
      <p class="text-medium-emphasis">{{ profile?.email || auth.user?.email }}</p>
    </div>

    <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-4" />

    <template v-if="profile">
      <!-- Account Info -->
      <v-card rounded="xl" class="pa-6 mb-6 settings-card">
        <div class="section-title mb-4">
          <v-avatar size="36" color="primary" variant="tonal">
            <v-icon size="20">mdi-account-circle-outline</v-icon>
          </v-avatar>
          <h3 class="text-h6 font-weight-bold">{{ $t('settings.accountInfo') }}</h3>
        </div>
        <v-row>
          <v-col v-if="isAdmin" cols="12" sm="6">
            <div class="text-caption text-medium-emphasis">{{ $t('settings.role') }}</div>
            <v-chip color="warning" size="small" variant="tonal">
              {{ profile.role }}
            </v-chip>
          </v-col>
          <v-col cols="12" sm="6">
            <div class="text-caption text-medium-emphasis">{{ $t('settings.emailVerified') }}</div>
            <v-chip v-if="auth.emailVerified" color="success" size="small" variant="tonal" prepend-icon="mdi-check-circle">
              {{ $t('settings.verified') }}
            </v-chip>
            <v-chip v-else color="warning" size="small" variant="tonal" prepend-icon="mdi-alert-circle">
              {{ $t('settings.notVerified') }}
            </v-chip>
          </v-col>
          <v-col cols="12" sm="6">
            <div class="text-caption text-medium-emphasis">{{ $t('settings.monthlyLimit') }}</div>
            <div class="text-body-1 font-weight-medium">{{ profile.checks_used }} / {{ profile.monthly_limit }}</div>
          </v-col>
          <v-col cols="12" sm="6">
            <div class="text-caption text-medium-emphasis">{{ $t('settings.memberSince') }}</div>
            <div class="text-body-1 font-weight-medium">{{ formatDate(profile.created_at) }}</div>
          </v-col>
        </v-row>
      </v-card>

      <!-- Email Verification -->
      <v-card v-if="!auth.emailVerified" rounded="xl" class="pa-6 mb-6 settings-card">
        <div class="section-title mb-2">
          <v-avatar size="36" color="warning" variant="tonal">
            <v-icon size="20">mdi-email-fast-outline</v-icon>
          </v-avatar>
          <h3 class="text-h6 font-weight-bold">{{ $t('settings.emailVerification') }}</h3>
        </div>
        <p class="text-medium-emphasis mb-4">
          {{ $t('settings.verificationInfo') }}
        </p>
        <v-alert v-if="verifySent" type="success" variant="tonal" rounded="lg" class="mb-4">
          {{ $t('settings.verificationLinkSent', { email: auth.user?.email || '' }) }}
        </v-alert>
        <v-btn
          v-if="!verifySent"
          color="warning"
          rounded="xl"
          :loading="verifySending"
          @click="handleResendVerification"
          prepend-icon="mdi-email-fast-outline"
        >
          {{ $t('settings.sendVerificationEmail') }}
        </v-btn>
      </v-card>

      <!-- Change Name -->
      <v-card rounded="xl" class="pa-6 mb-6 settings-card">
        <div class="section-title mb-4">
          <v-avatar size="36" color="primary" variant="tonal">
            <v-icon size="20">mdi-account-edit-outline</v-icon>
          </v-avatar>
          <h3 class="text-h6 font-weight-bold">{{ $t('settings.changeName') }}</h3>
        </div>
        <v-alert v-if="nameMsg" :type="nameMsg.type" variant="tonal" rounded="lg" class="mb-4" closable @click:close="nameMsg = null">
          {{ nameMsg.text }}
        </v-alert>
        <v-form @submit.prevent="handleNameChange" ref="nameFormRef">
          <v-text-field
            v-model="nameForm.name"
            :label="$t('settings.newUsername')"
            variant="outlined"
            rounded="lg"
            prepend-inner-icon="mdi-account-outline"
            :rules="[v => !!v || $t('settings.required'), v => v.length <= 100 || $t('settings.max100')]"
          />
          <div class="text-caption text-medium-emphasis mb-3" v-if="profile.last_username_change">
            {{ $t('settings.lastChanged') }} {{ formatDate(profile.last_username_change) }}
            {{ $t('settings.canChange30') }}
          </div>
          <v-btn type="submit" color="primary" rounded="xl" :loading="nameSaving">{{ $t('settings.updateName') }}</v-btn>
        </v-form>
      </v-card>

      <!-- Change Email -->
      <v-card rounded="xl" class="pa-6 mb-6 settings-card">
        <div class="section-title mb-4">
          <v-avatar size="36" color="primary" variant="tonal">
            <v-icon size="20">mdi-email-edit-outline</v-icon>
          </v-avatar>
          <h3 class="text-h6 font-weight-bold">{{ $t('settings.changeEmail') }}</h3>
        </div>
        <v-alert v-if="emailMsg" :type="emailMsg.type" variant="tonal" rounded="lg" class="mb-4" closable @click:close="emailMsg = null">
          {{ emailMsg.text }}
        </v-alert>
        <v-form @submit.prevent="handleEmailChange" ref="emailFormRef">
          <v-text-field
            v-model="emailForm.email"
            :label="$t('settings.newEmail')"
            type="email"
            variant="outlined"
            rounded="lg"
            prepend-inner-icon="mdi-email-outline"
            :rules="[v => !!v || $t('settings.required'), v => /.+@.+\..+/.test(v) || $t('settings.invalidEmail')]"
          />
          <v-text-field
            v-model="emailForm.password"
            :label="$t('settings.currentPassword')"
            type="password"
            variant="outlined"
            rounded="lg"
            prepend-inner-icon="mdi-lock-outline"
            :rules="[v => !!v || $t('settings.required')]"
          />
          <v-btn type="submit" color="primary" rounded="xl" :loading="emailSaving">{{ $t('settings.updateEmail') }}</v-btn>
        </v-form>
      </v-card>

      <!-- Change Password -->
      <v-card rounded="xl" class="pa-6 settings-card">
        <div class="section-title mb-4">
          <v-avatar size="36" color="primary" variant="tonal">
            <v-icon size="20">mdi-lock-reset</v-icon>
          </v-avatar>
          <h3 class="text-h6 font-weight-bold">{{ $t('settings.changePassword') }}</h3>
        </div>
        <v-alert v-if="passwordMsg" :type="passwordMsg.type" variant="tonal" rounded="lg" class="mb-4" closable @click:close="passwordMsg = null">
          {{ passwordMsg.text }}
        </v-alert>
        <v-form @submit.prevent="handlePasswordChange" ref="passwordFormRef">
          <v-text-field
            v-model="passwordForm.current_password"
            :label="$t('settings.currentPassword')"
            type="password"
            variant="outlined"
            rounded="lg"
            prepend-inner-icon="mdi-lock-outline"
            :rules="[v => !!v || $t('settings.required')]"
          />
          <v-text-field
            v-model="passwordForm.password"
            :label="$t('settings.newPassword')"
            type="password"
            variant="outlined"
            rounded="lg"
            prepend-inner-icon="mdi-lock-plus-outline"
            :rules="[v => !!v || $t('settings.required'), v => v.length >= 8 || $t('settings.min8')]"
          />
          <v-text-field
            v-model="passwordForm.password_confirmation"
            :label="$t('settings.confirmNewPassword')"
            type="password"
            variant="outlined"
            rounded="lg"
            prepend-inner-icon="mdi-lock-check-outline"
            :rules="[v => !!v || $t('settings.required'), v => v === passwordForm.password || $t('settings.passwordsNoMatch')]"
          />
          <v-btn type="submit" color="primary" rounded="xl" :loading="passwordSaving">{{ $t('settings.changePasswordBtn') }}</v-btn>
        </v-form>
      </v-card>
    </template>
  </v-container>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { getUserProfile, updateUserName, updateUserEmail, updateUserPassword, resendVerification } from '@/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const { t } = useI18n()
const loading = ref(true)
const profile = ref(null)
const isAdmin = computed(() => profile.value?.role === 'admin' || auth.isAdmin)

// Verification
const verifySending = ref(false)
const verifySent = ref(false)

// Name
const nameFormRef = ref(null)
const nameSaving = ref(false)
const nameMsg = ref(null)
const nameForm = reactive({ name: '' })

// Email
const emailFormRef = ref(null)
const emailSaving = ref(false)
const emailMsg = ref(null)
const emailForm = reactive({ email: '', password: '' })

// Password
const passwordFormRef = ref(null)
const passwordSaving = ref(false)
const passwordMsg = ref(null)
const passwordForm = reactive({ current_password: '', password: '', password_confirmation: '' })

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString(undefined, { timeZone: 'UTC' })
}

async function loadProfile() {
  loading.value = true
  try {
    const { data } = await getUserProfile()
    profile.value = data
    nameForm.name = data.name
    emailForm.email = data.email
  } finally {
    loading.value = false
  }
}

async function handleNameChange() {
  const { valid } = await nameFormRef.value.validate()
  if (!valid) return
  nameSaving.value = true
  nameMsg.value = null
  try {
    const { data } = await updateUserName({ name: nameForm.name })
    nameMsg.value = { type: 'success', text: data.message }
    await auth.fetchUser()
    await loadProfile()
  } catch (e) {
    nameMsg.value = { type: 'error', text: e.response?.data?.message || t('settings.failedUpdateName') }
  } finally {
    nameSaving.value = false
  }
}

async function handleEmailChange() {
  const { valid } = await emailFormRef.value.validate()
  if (!valid) return
  emailSaving.value = true
  emailMsg.value = null
  try {
    const { data } = await updateUserEmail({ email: emailForm.email, password: emailForm.password })
    emailMsg.value = { type: 'success', text: data.message }
    emailForm.password = ''
    await auth.fetchUser()
    await loadProfile()
  } catch (e) {
    emailMsg.value = { type: 'error', text: e.response?.data?.message || t('settings.failedUpdateEmail') }
  } finally {
    emailSaving.value = false
  }
}

async function handlePasswordChange() {
  const { valid } = await passwordFormRef.value.validate()
  if (!valid) return
  passwordSaving.value = true
  passwordMsg.value = null
  try {
    const { data } = await updateUserPassword(passwordForm)
    passwordMsg.value = { type: 'success', text: data.message }
    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
  } catch (e) {
    passwordMsg.value = { type: 'error', text: e.response?.data?.message || t('settings.failedChangePassword') }
  } finally {
    passwordSaving.value = false
  }
}

onMounted(() => loadProfile())

async function handleResendVerification() {
  verifySending.value = true
  try {
    await resendVerification()
    verifySent.value = true
  } finally {
    verifySending.value = false
  }
}
</script>

<style scoped>
.settings-head p {
  margin-top: 6px;
}

.settings-card {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  box-shadow: 0 14px 26px rgba(18, 24, 38, 0.06);
}

.section-title {
  display: flex;
  align-items: center;
  gap: 10px;
}

.section-title h3 {
  margin: 0;
}
</style>
