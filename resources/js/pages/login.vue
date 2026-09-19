<script setup>
import { VForm } from 'vuetify/components/VForm'
import { themeConfig } from '@themeConfig'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { requiredValidator, emailValidator } from '@core/utils/validators'

definePage({
  meta: {
    layout: 'blank',
    unauthenticatedOnly: true,
  },
})

const isPasswordVisible = ref(false)
const route = useRoute()
const router = useRouter()
const ability = useAbility()

const errors = ref({
  email: undefined,
  password: undefined,
})

const refVForm = ref()

const credentials = ref({
  email: '',
  password: '',
})

const rememberMe = ref(true)
const isLoading = ref(false)

const isUnverified = ref(false)
const unverifiedEmail = ref('')
const isResending = ref(false)
const resendSuccess = ref(false)

const login = async () => {
  isLoading.value = true
  isUnverified.value = false
  resendSuccess.value = false
  errors.value = { email: undefined, password: undefined }
  try {
    const res = await $api('/auth/login', {
      method: 'POST',
      body: {
        email: credentials.value.email,
        password: credentials.value.password,
        remember_me: rememberMe.value,
      },
      onResponseError({ response }) {
        if (response._data?.unverified) {
          isUnverified.value = true
          unverifiedEmail.value = response._data.email || credentials.value.email
        }
        
        errors.value = response._data?.errors || {
          email: [response._data?.message || 'Email atau kata sandi tidak valid. Silakan periksa kembali.']
        }
      },
    })

    if (!res) return // Stop execution if there was an error (handled in onResponseError)

    const { accessToken, userData, userAbilityRules } = res

    localStorage.setItem('userAbilityRules', JSON.stringify(userAbilityRules))
    ability.update(userAbilityRules)
    
    // Durasi Cookie: 7 Hari jika 'Ingat Saya' dicentang (604.800 detik), 24 Jam secara default (86.400 detik)
    const cookieOptions = rememberMe.value ? { maxAge: 604800 } : { maxAge: 86400 }

    useCookie('userData', cookieOptions).value = userData
    useCookie('accessToken', cookieOptions).value = accessToken

    await nextTick(() => {
      router.replace(route.query.to ? String(route.query.to) : '/')
    })
  } catch (err) {
    console.error(err)
  } finally {
    isLoading.value = false
  }
}

const resendVerification = async () => {
  isResending.value = true
  resendSuccess.value = false
  
  try {
    const res = await $api('/auth/email/verification-notification', {
      method: 'POST',
      body: {
        email: unverifiedEmail.value
      }
    })
    
    if (res?.message) {
      resendSuccess.value = true
    }
  } catch (err) {
    console.error(err)
  } finally {
    isResending.value = false
  }
}

const onSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) login()
  })
}

// Handle email verification redirect
const verificationSuccess = ref(false)
const verificationFailed = ref(false)
const verificationAlready = ref(false)

onMounted(() => {
  if (route.query.verification === 'success') {
    verificationSuccess.value = true
    setTimeout(() => verificationSuccess.value = false, 8000)
  } else if (route.query.verification === 'failed') {
    verificationFailed.value = true
    setTimeout(() => verificationFailed.value = false, 8000)
  } else if (route.query.verification === 'already') {
    verificationAlready.value = true
    setTimeout(() => verificationAlready.value = false, 8000)
  }
})
</script>

<template>
  <div class="login-page-wrapper d-flex align-center justify-center">
    <div class="login-card-container w-100 max-w-420 px-4">
      <VCard class="login-card pa-6 pa-sm-8 rounded border elevation-2">
        <!-- Logo & Header -->
        <div class="text-center mb-6">
          <div class="d-inline-flex align-center justify-center bg-primary-lighten-5 pa-3 rounded mb-3">
            <VNodeRenderer :nodes="themeConfig.app.logo" />
          </div>
          <h1 class="text-h5 font-weight-extrabold text-high-emphasis mb-1">
            {{ themeConfig.app.title }}
          </h1>
          <p class="text-caption text-medium-emphasis mb-0">
            Sistem POS & Inventaris Barang
          </p>
        </div>

        <!-- Verification Success/Error Alerts -->
        <VAlert
          v-if="verificationSuccess"
          color="success"
          variant="tonal"
          class="mb-6 rounded-lg text-body-2"
          closable
        >
          Email berhasil diverifikasi! Silakan masuk menggunakan akun Anda.
        </VAlert>

        <VAlert
          v-if="verificationFailed"
          color="error"
          variant="tonal"
          class="mb-6 rounded-lg text-body-2"
          closable
        >
          Tautan verifikasi tidak valid atau telah kedaluwarsa.
        </VAlert>

        <VAlert
          v-if="verificationAlready"
          color="info"
          variant="tonal"
          class="mb-6 rounded-lg text-body-2"
          closable
        >
          Email Anda sudah diverifikasi sebelumnya. Silakan masuk.
        </VAlert>

        <!-- Alert Error -->
        <VAlert
          v-if="errors.email || errors.password"
          color="error"
          variant="tonal"
          class="mb-6 rounded-lg text-body-2"
        >
          <template v-if="isUnverified">
            <div class="d-flex flex-column align-center text-center">
              <VIcon icon="ri-error-warning-line" size="32" class="mb-2" />
              <div class="mb-3 font-weight-medium">
                {{ errors.email ? errors.email[0] : (errors.password ? errors.password[0] : '') }}
              </div>
              <div v-if="resendSuccess" class="text-success font-weight-bold mb-2">
                Tautan verifikasi telah dikirim ulang ke {{ unverifiedEmail }}.
              </div>
              <VBtn
                v-else
                color="primary"
                variant="outlined"
                size="small"
                rounded="lg"
                :loading="isResending"
                @click="resendVerification"
              >
                Kirim Ulang Email Verifikasi
              </VBtn>
            </div>
          </template>
          <template v-else>
            {{ errors.email ? errors.email[0] : (errors.password ? errors.password[0] : '') }}
          </template>
        </VAlert>

        <!-- Form -->
        <VForm
          ref="refVForm"
          @submit.prevent="onSubmit"
        >
          <div class="d-flex flex-column gap-3">
            <!-- Email -->
            <div>
              <label class="text-caption font-weight-bold text-medium-emphasis mb-1 d-block">
                Email
              </label>
              <VTextField
                v-model="credentials.email"
                placeholder="nama@perusahaan.com"
                type="email"
                prepend-inner-icon="ri-mail-line"
                autofocus
                variant="outlined"
                density="compact"
                rounded="lg"
                :rules="[requiredValidator, emailValidator]"
                :error-messages="errors.email"
                hide-details="auto"
              />
            </div>

            <!-- Password -->
            <div>
              <div class="d-flex align-center justify-space-between mb-1">
                <label class="text-caption font-weight-bold text-medium-emphasis d-block">
                  Kata Sandi
                </label>
                <RouterLink
                  class="text-caption text-primary font-weight-medium"
                  :to="{ name: 'forgot-password' }"
                >
                  Lupa Sandi?
                </RouterLink>
              </div>
              <VTextField
                v-model="credentials.password"
                placeholder="············"
                prepend-inner-icon="ri-lock-line"
                :rules="[requiredValidator]"
                :type="isPasswordVisible ? 'text' : 'password'"
                autocomplete="current-password"
                variant="outlined"
                density="compact"
                rounded="lg"
                :error-messages="errors.password"
                :append-inner-icon="isPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                @click:append-inner="isPasswordVisible = !isPasswordVisible"
                hide-details="auto"
              />
            </div>

            <!-- Remember Me -->
            <div class="d-flex align-center mt-1">
              <VCheckbox
                v-model="rememberMe"
                label="Ingat sesi saya"
                density="compact"
                hide-details
                color="primary"
                class="login-checkbox"
              />
            </div>

            <!-- Submit Button -->
            <VBtn
              block
              size="large"
              type="submit"
              color="primary"
              rounded="lg"
              :loading="isLoading"
              :disabled="isLoading"
              class="font-weight-bold elevation-1 text-none mt-2"
              prepend-icon="ri-login-box-line"
            >
              Masuk
            </VBtn>
          </div>
        </VForm>

        <!-- Footer Note -->
        <div class="mt-6 pt-4 border-t text-center">
          <div class="text-caption text-disabled" style="font-size: 12px;">
            © {{ new Date().getFullYear() }} Manajemen Barang & POS • Seluruh Hak Cipta Dilindungi
          </div>
        </div>
      </VCard>
    </div>
  </div>
</template>

<style scoped>
.login-page-wrapper {
  height: 100vh;
  max-height: 100vh;
  width: 100vw;
  overflow: hidden;
  background-color: rgb(var(--v-theme-background));
  background-image: radial-gradient(rgba(var(--v-theme-on-surface), 0.1) 1px, transparent 1px);
  background-size: 24px 24px;
}

.login-card-container {
  max-width: 420px;
}

.login-card {
  background-color: rgb(var(--v-theme-surface)) !important;
  border-color: rgba(var(--v-theme-border-color)) !important;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02) !important;
}

.bg-primary-lighten-5 {
  background-color: rgba(var(--v-theme-primary), 0.08);
}

.login-checkbox :deep(.v-label) {
  font-size: 13px !important;
}
</style>
