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
            :error="nameErrors().length > 0"
            :error-messages="nameErrors()"
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
            :error="emailErrors().length > 0"
            :error-messages="emailErrors()"
          />
          <v-text-field
            v-model="emailForm.email_confirmation"
            :label="$t('settings.confirmNewEmail')"
            type="email"
            variant="outlined"
            rounded="lg"
            prepend-inner-icon="mdi-email-check-outline"
            :error="emailConfirmErrors().length > 0"
            :error-messages="emailConfirmErrors()"
          />
          <v-text-field
            v-model="emailForm.password"
            :label="$t('settings.currentPassword')"
            type="password"
            variant="outlined"
            rounded="lg"
            prepend-inner-icon="mdi-lock-outline"
            :error="emailPasswordErrors().length > 0"
            :error-messages="emailPasswordErrors()"
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
            :error="currentPasswordErrors().length > 0"
            :error-messages="currentPasswordErrors()"
          />
          <v-text-field
            v-model="passwordForm.password"
            :label="$t('settings.newPassword')"
            type="password"
            variant="outlined"
            rounded="lg"
            prepend-inner-icon="mdi-lock-plus-outline"
            :error="newPasswordErrors().length > 0"
            :error-messages="newPasswordErrors()"
          />
          <v-text-field
            v-model="passwordForm.password_confirmation"
            :label="$t('settings.confirmNewPassword')"
            type="password"
            variant="outlined"
            rounded="lg"
            prepend-inner-icon="mdi-lock-check-outline"
            :error="confirmPasswordErrors().length > 0"
            :error-messages="confirmPasswordErrors()"
          />
          <v-btn type="submit" color="primary" rounded="xl" :loading="passwordSaving">{{ $t('settings.changePasswordBtn') }}</v-btn>
        </v-form>
      </v-card>

      <!-- Delete Account -->
      <v-card rounded="xl" class="pa-6 mt-6 settings-card settings-card-danger">
        <div class="section-title mb-4">
          <v-avatar size="36" color="error" variant="tonal">
            <v-icon size="20">mdi-delete-alert-outline</v-icon>
          </v-avatar>
          <h3 class="text-h6 font-weight-bold">{{ $t('settings.deleteAccount') }}</h3>
        </div>

        <p class="text-medium-emphasis mb-4">{{ $t('settings.deleteAccountHint') }}</p>

        <v-btn
          color="error"
          rounded="xl"
          prepend-icon="mdi-delete-outline"
          @click="openDeleteDialog"
        >
          {{ $t('settings.deleteAccount') }}
        </v-btn>
      </v-card>
    </template>

    <v-dialog v-model="deleteDialog" max-width="560" persistent>
      <v-card rounded="xl">
        <v-card-title class="d-flex align-center ga-2">
          <v-icon color="error">mdi-alert-circle-outline</v-icon>
          {{ $t('settings.deleteAccountDialogTitle') }}
        </v-card-title>

        <v-card-text>
          <p class="mb-3">{{ $t('settings.deleteAccountWarning') }}</p>

          <v-alert type="warning" variant="tonal" rounded="lg" class="mb-4">
            {{ $t('settings.deleteAccountIrreversible') }}
          </v-alert>

          <v-alert
            v-if="deleteMsg"
            type="error"
            variant="tonal"
            rounded="lg"
            class="mb-4"
          >
            {{ deleteMsg }}
          </v-alert>

          <v-text-field
            v-model="deleteForm.password"
            :label="$t('settings.deleteAccountPasswordLabel')"
            type="password"
            variant="outlined"
            rounded="lg"
            prepend-inner-icon="mdi-lock-outline"
            :error="deletePasswordErrors().length > 0"
            :error-messages="deletePasswordErrors()"
            @keyup.enter="handleDeleteAccount"
          />
        </v-card-text>

        <v-card-actions class="px-6 pb-6">
          <v-spacer />
          <v-btn variant="text" :disabled="deleteSubmitting" @click="closeDeleteDialog">
            {{ $t('settings.cancel') }}
          </v-btn>
          <v-btn
            color="error"
            variant="flat"
            :loading="deleteSubmitting"
            @click="handleDeleteAccount"
          >
            {{ $t('settings.deleteAccountConfirmBtn') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { getUserProfile, updateUserName, updateUserEmail, updateUserPassword, deleteUserAccount, resendVerification } from '@/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
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
const nameSubmitted = ref(false)

// Email
const emailFormRef = ref(null)
const emailSaving = ref(false)
const emailMsg = ref(null)
const emailForm = reactive({ email: '', email_confirmation: '', password: '' })
const emailSubmitted = ref(false)

// Password
const passwordFormRef = ref(null)
const passwordSaving = ref(false)
const passwordMsg = ref(null)
const passwordForm = reactive({ current_password: '', password: '', password_confirmation: '' })
const passwordSubmitted = ref(false)

// Delete account
const deleteDialog = ref(false)
const deleteSubmitting = ref(false)
const deleteMsg = ref(null)
const deleteSubmitted = ref(false)
const deleteForm = reactive({ password: '' })

function isValidEmail(value) {
  return /.+@.+\..+/.test(value)
}

function nameErrors() {
  if (!nameSubmitted.value) return []

  const name = nameForm.name?.trim() || ''
  if (!name) return [t('settings.required')]
  if (name.length > 100) return [t('settings.max100')]
  return []
}

function emailErrors() {
  if (!emailSubmitted.value) return []

  const email = emailForm.email?.trim() || ''
  if (!email) return [t('settings.required')]
  if (!isValidEmail(email)) return [t('settings.invalidEmail')]
  return []
}

function emailPasswordErrors() {
  if (!emailSubmitted.value) return []
  return emailForm.password ? [] : [t('settings.required')]
}

function emailConfirmErrors() {
  if (!emailSubmitted.value) return []

  const confirmEmail = emailForm.email_confirmation?.trim() || ''
  if (!confirmEmail) return [t('settings.required')]
  if (confirmEmail !== (emailForm.email?.trim() || '')) return [t('settings.emailsNoMatch')]
  return []
}

function currentPasswordErrors() {
  if (!passwordSubmitted.value) return []
  return passwordForm.current_password ? [] : [t('settings.required')]
}

function newPasswordErrors() {
  if (!passwordSubmitted.value) return []
  if (!passwordForm.password) return [t('settings.required')]
  if (passwordForm.password.length < 8) return [t('settings.min8')]
  return []
}

function confirmPasswordErrors() {
  if (!passwordSubmitted.value) return []
  if (!passwordForm.password_confirmation) return [t('settings.required')]
  if (passwordForm.password_confirmation !== passwordForm.password) return [t('settings.passwordsNoMatch')]
  return []
}

function deletePasswordErrors() {
  if (!deleteSubmitted.value) return []
  return deleteForm.password ? [] : [t('settings.required')]
}

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
    emailForm.email_confirmation = data.email
  } finally {
    loading.value = false
  }
}

async function handleNameChange() {
  nameSubmitted.value = true
  if (nameErrors().length) return

  nameSaving.value = true
  nameMsg.value = null
  try {
    const { data } = await updateUserName({ name: nameForm.name.trim() })
    nameMsg.value = { type: 'success', text: data.message }
    nameSubmitted.value = false
    await auth.fetchUser()
    await loadProfile()
  } catch (e) {
    nameMsg.value = { type: 'error', text: e.response?.data?.message || t('settings.failedUpdateName') }
  } finally {
    nameSaving.value = false
  }
}

async function handleEmailChange() {
  emailSubmitted.value = true
  if (emailErrors().length || emailConfirmErrors().length || emailPasswordErrors().length) return

  emailSaving.value = true
  emailMsg.value = null
  try {
    const { data } = await updateUserEmail({
      email: emailForm.email.trim(),
      email_confirmation: emailForm.email_confirmation.trim(),
      password: emailForm.password,
    })
    emailMsg.value = { type: 'success', text: data.message }
    emailForm.email_confirmation = emailForm.email
    emailForm.password = ''
    emailSubmitted.value = false
    await auth.fetchUser()
    await loadProfile()
  } catch (e) {
    emailMsg.value = { type: 'error', text: e.response?.data?.message || t('settings.failedUpdateEmail') }
  } finally {
    emailSaving.value = false
  }
}

async function handlePasswordChange() {
  passwordSubmitted.value = true
  if (
    currentPasswordErrors().length
    || newPasswordErrors().length
    || confirmPasswordErrors().length
  ) {
    return
  }

  passwordSaving.value = true
  passwordMsg.value = null
  try {
    const { data } = await updateUserPassword(passwordForm)
    passwordMsg.value = { type: 'success', text: data.message }
    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
    passwordSubmitted.value = false
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

function openDeleteDialog() {
  deleteSubmitted.value = false
  deleteMsg.value = null
  deleteForm.password = ''
  deleteDialog.value = true
}

function closeDeleteDialog() {
  if (deleteSubmitting.value) return
  deleteDialog.value = false
  deleteSubmitted.value = false
  deleteForm.password = ''
}

async function handleDeleteAccount() {
  deleteSubmitted.value = true
  if (deletePasswordErrors().length) return

  deleteSubmitting.value = true
  deleteMsg.value = null

  try {
    await deleteUserAccount({ password: deleteForm.password })

    try {
      await auth.logout()
    } catch {
      // Account is already deleted; local auth state will still be cleared.
    }

    await router.push({ name: 'home' })
  } catch (e) {
    deleteMsg.value = e.response?.data?.message || t('settings.failedDeleteAccount')
  } finally {
    deleteSubmitting.value = false
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

.settings-card-danger {
  border-color: rgba(var(--v-theme-error), 0.25);
  box-shadow: 0 14px 26px rgba(175, 42, 42, 0.08);
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
