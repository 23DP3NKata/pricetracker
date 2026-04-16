<template>
  <v-container class="py-8 settings-view" style="max-width: 760px;">
    <div class="settings-head mb-6">
      <h1 class="text-h4 font-weight-bold">{{ $t('settings.title') }}</h1>
      <p class="text-medium-emphasis">{{ profile?.email || auth.user?.email }}</p>
    </div>

    <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-4" />

    <template v-if="profile">
      <div class="settings-rows">
        <div class="settings-row">
          <div class="row-label">Username</div>
          <div class="row-value">{{ profile.name || '-' }}</div>
          <div class="row-actions">
            <v-btn variant="outlined" color="primary" size="small" class="edit-btn" @click="beginEdit('name')">Edit</v-btn>
          </div>
        </div>
        <v-expand-transition>
          <div v-if="editingField === 'name'" class="row-expand">
            <v-alert v-if="nameMsg" :type="nameMsg.type" variant="tonal" rounded="lg" class="mb-3" closable @click:close="nameMsg = null">
              {{ nameMsg.text }}
            </v-alert>
            <v-form @submit.prevent="handleNameChange" ref="nameFormRef">
              <v-text-field
                v-model="nameForm.name"
                :placeholder="$t('settings.newUsername')"
                variant="plain"
                density="compact"
                prepend-inner-icon="mdi-account-outline"
                hide-details="auto"
                class="flat-field mb-2"
                :error="nameErrors().length > 0"
                :error-messages="nameErrors()"
              />
              <div class="text-caption text-medium-emphasis mb-3" v-if="profile.last_username_change">
                {{ $t('settings.lastChanged') }} {{ formatDate(profile.last_username_change) }} {{ $t('settings.canChange30') }}
              </div>
              <div class="form-actions">
                <v-btn variant="text" color="default" size="small" @click="cancelEdit">{{ $t('settings.cancel') }}</v-btn>
                <v-btn type="submit" variant="flat" color="primary" size="small" :loading="nameSaving">{{ $t('settings.updateName') }}</v-btn>
              </div>
            </v-form>
          </div>
        </v-expand-transition>

        <v-divider />

        <div class="settings-row">
          <div class="row-label">Email</div>
          <div class="row-value">{{ profile.email || '-' }}</div>
          <div class="row-actions">
            <v-btn variant="outlined" color="primary" size="small" class="edit-btn" @click="beginEdit('email')">Edit</v-btn>
          </div>
        </div>
        <v-expand-transition>
          <div v-if="editingField === 'email'" class="row-expand">
            <v-alert v-if="emailMsg" :type="emailMsg.type" variant="tonal" rounded="lg" class="mb-3" closable @click:close="emailMsg = null">
              {{ emailMsg.text }}
            </v-alert>
            <v-form @submit.prevent="handleEmailChange" ref="emailFormRef">
              <v-text-field
                v-model="emailForm.email"
                :placeholder="$t('settings.newEmail')"
                type="email"
                variant="plain"
                density="compact"
                prepend-inner-icon="mdi-email-outline"
                hide-details="auto"
                class="flat-field mb-2"
                :error="emailErrors().length > 0"
                :error-messages="emailErrors()"
              />
              <v-text-field
                v-model="emailForm.email_confirmation"
                :placeholder="$t('settings.confirmNewEmail')"
                type="email"
                variant="plain"
                density="compact"
                prepend-inner-icon="mdi-email-check-outline"
                hide-details="auto"
                class="flat-field mb-2"
                :error="emailConfirmErrors().length > 0"
                :error-messages="emailConfirmErrors()"
              />
              <v-text-field
                v-model="emailForm.password"
                :placeholder="$t('settings.currentPassword')"
                type="password"
                variant="plain"
                density="compact"
                prepend-inner-icon="mdi-lock-outline"
                hide-details="auto"
                class="flat-field mb-2"
                :error="emailPasswordErrors().length > 0"
                :error-messages="emailPasswordErrors()"
              />
              <div class="form-actions">
                <v-btn variant="text" color="default" size="small" @click="cancelEdit">{{ $t('settings.cancel') }}</v-btn>
                <v-btn type="submit" variant="flat" color="primary" size="small" :loading="emailSaving">{{ $t('settings.updateEmail') }}</v-btn>
              </div>
            </v-form>
          </div>
        </v-expand-transition>

        <v-divider />

        <div class="settings-row">
          <div class="row-label">Password</div>
          <div class="row-value">••••••••••••</div>
          <div class="row-actions">
            <v-btn variant="outlined" color="primary" size="small" class="edit-btn" @click="beginEdit('password')">Edit</v-btn>
          </div>
        </div>
        <v-expand-transition>
          <div v-if="editingField === 'password'" class="row-expand">
            <v-alert v-if="passwordMsg" :type="passwordMsg.type" variant="tonal" rounded="lg" class="mb-3" closable @click:close="passwordMsg = null">
              {{ passwordMsg.text }}
            </v-alert>
            <v-form @submit.prevent="handlePasswordChange" ref="passwordFormRef">
              <v-text-field
                v-model="passwordForm.current_password"
                :placeholder="$t('settings.currentPassword')"
                type="password"
                variant="plain"
                density="compact"
                prepend-inner-icon="mdi-lock-outline"
                hide-details="auto"
                class="flat-field mb-2"
                :error="currentPasswordErrors().length > 0"
                :error-messages="currentPasswordErrors()"
              />
              <v-text-field
                v-model="passwordForm.password"
                :placeholder="$t('settings.newPassword')"
                type="password"
                variant="plain"
                density="compact"
                prepend-inner-icon="mdi-lock-plus-outline"
                hide-details="auto"
                class="flat-field mb-2"
                :error="newPasswordErrors().length > 0"
                :error-messages="newPasswordErrors()"
              />
              <v-text-field
                v-model="passwordForm.password_confirmation"
                :placeholder="$t('settings.confirmNewPassword')"
                type="password"
                variant="plain"
                density="compact"
                prepend-inner-icon="mdi-lock-check-outline"
                hide-details="auto"
                class="flat-field mb-2"
                :error="confirmPasswordErrors().length > 0"
                :error-messages="confirmPasswordErrors()"
              />
              <div class="form-actions">
                <v-btn variant="text" color="default" size="small" @click="cancelEdit">{{ $t('settings.cancel') }}</v-btn>
                <v-btn type="submit" variant="flat" color="primary" size="small" :loading="passwordSaving">{{ $t('settings.changePasswordBtn') }}</v-btn>
              </div>
            </v-form>
          </div>
        </v-expand-transition>

        <v-divider />

        <div class="settings-row settings-row--info" v-if="isAdmin">
          <div class="row-label">{{ $t('settings.role') }}</div>
          <div class="row-value">{{ profile.role }}</div>
          <div class="row-actions"></div>
        </div>
        <v-divider v-if="isAdmin" />

        <div class="settings-row settings-row--info">
          <div class="row-label">{{ $t('settings.emailVerified') }}</div>
          <div class="row-value">
            <v-chip v-if="auth.emailVerified" color="success" variant="tonal" size="small">{{ $t('settings.verified') }}</v-chip>
            <v-chip v-else color="warning" variant="tonal" size="small">Not verified</v-chip>
          </div>
          <div class="row-actions" v-if="!auth.emailVerified">
            <v-btn v-if="!verifySent" variant="outlined" color="primary" size="small" class="edit-btn" :loading="verifySending" @click="handleResendVerification">
              {{ $t('settings.sendVerificationEmail') }}
            </v-btn>
            <span v-else class="text-caption text-medium-emphasis">{{ $t('settings.verificationLinkSent', { email: auth.user?.email || '' }) }}</span>
          </div>
          <div class="row-actions" v-else></div>
        </div>
        <v-divider />

        <div class="settings-row settings-row--info">
          <div class="row-label">{{ $t('settings.monthlyLimit') }}</div>
          <div class="row-value">{{ profile.checks_used }} / {{ profile.monthly_limit }}</div>
          <div class="row-actions"></div>
        </div>
        <v-divider />

        <div class="settings-row settings-row--info">
          <div class="row-label">{{ $t('settings.memberSince') }}</div>
          <div class="row-value">{{ formatDate(profile.created_at) }}</div>
          <div class="row-actions"></div>
        </div>
      </div>

      <div class="delete-row mt-6">
        <div class="row-label">{{ $t('settings.deleteAccount') }}</div>
        <div class="row-value text-medium-emphasis">{{ $t('settings.deleteAccountHint') }}</div>
        <div class="row-actions">
          <v-btn
            v-if="!deleteConfirmArmed"
            variant="outlined"
            color="error"
            size="small"
            class="edit-btn"
            @click="armDeleteConfirm"
          >
            {{ $t('settings.deleteAccount') }}
          </v-btn>
          <div v-else class="delete-confirm-actions">
            <v-btn variant="text" color="default" size="small" @click="cancelDeleteConfirm">{{ $t('settings.cancel') }}</v-btn>
            <v-btn variant="flat" color="error" size="small" @click="openDeleteDialog">{{ $t('settings.deleteAccountConfirmBtn') }}</v-btn>
          </div>
        </div>
      </div>
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
const editingField = ref(null)

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
const deleteConfirmArmed = ref(false)

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

  if (emailForm.password) {
    return []
  }

  return [t('settings.required')]
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

  if (passwordForm.current_password) {
    return []
  }

  return [t('settings.required')]
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

  if (deleteForm.password) {
    return []
  }

  return [t('settings.required')]
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString(undefined, { timeZone: 'UTC' })
}

function beginEdit(field) {
  editingField.value = field

  if (field === 'name') {
    nameForm.name = profile.value?.name || ''
    nameSubmitted.value = false
    nameMsg.value = null
    return
  }

  if (field === 'email') {
    emailForm.email = profile.value?.email || ''
    emailForm.email_confirmation = profile.value?.email || ''
    emailForm.password = ''
    emailSubmitted.value = false
    emailMsg.value = null
    return
  }

  if (field === 'password') {
    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
    passwordSubmitted.value = false
    passwordMsg.value = null
  }
}

function cancelEdit() {
  editingField.value = null
  nameSubmitted.value = false
  emailSubmitted.value = false
  passwordSubmitted.value = false
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
    editingField.value = null
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
    editingField.value = null
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
    editingField.value = null
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
  deleteConfirmArmed.value = false
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

function armDeleteConfirm() {
  deleteConfirmArmed.value = true
}

function cancelDeleteConfirm() {
  deleteConfirmArmed.value = false
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

.settings-view {
  background: transparent;
}

.settings-rows {
  border-top: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

.settings-row {
  display: grid;
  grid-template-columns: 260px 1fr auto;
  align-items: center;
  gap: 12px;
  padding: 14px 2px;
}

.settings-row--info {
  min-height: 58px;
}

.row-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), 0.68);
}

.row-value {
  font-size: 0.95rem;
  font-weight: 500;
}

.row-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
}

.edit-btn {
  text-transform: none;
  min-height: 30px;
}

.row-expand {
  padding: 0 2px 14px;
}

.flat-field :deep(.v-field) {
  border-radius: 10px;
  background: rgba(var(--v-theme-on-surface), 0.03);
  padding-inline: 10px;
}

.flat-field :deep(.v-field--focused) {
  background: rgba(var(--v-theme-on-surface), 0.04);
}

.flat-field :deep(.v-field__outline) {
  display: none;
}

.flat-field :deep(.v-field__input) {
  min-height: 38px;
  align-items: center;
  padding-top: 8px;
  padding-bottom: 8px;
}

.form-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 6px;
}

.delete-row {
  display: grid;
  grid-template-columns: 160px 1fr auto;
  align-items: center;
  gap: 12px;
  padding-top: 14px;
  border-top: 1px solid rgba(var(--v-theme-error), 0.2);
}

.delete-confirm-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

@media (max-width: 740px) {
  .settings-row,
  .delete-row {
    grid-template-columns: 1fr;
    align-items: start;
    gap: 8px;
    padding: 12px 0;
  }

  .row-actions,
  .form-actions {
    justify-content: flex-start;
  }
}
</style>
