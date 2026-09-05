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

const login = async () => {
  isLoading.value = true
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
        errors.value = response._data?.errors || {
          email: [response._data?.message || 'Email atau kata sandi tidak valid. Silakan periksa kembali.']
        }
      },
    })

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

const onSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) login()
  })
}
</script>

<template>
  <div class="login-page-wrapper d-flex align-center justify-center">
    <div class="login-card-container w-100 max-w-420 px-4">
      <VCard class="login-card pa-6 pa-sm-8 rounded-xl border elevation-2">
        <!-- Logo & Header -->
        <div class="text-center mb-6">
          <div class="d-inline-flex align-center justify-center bg-primary-lighten-5 pa-3 rounded-xl mb-3">
            <VNodeRenderer :nodes="themeConfig.app.logo" />
          </div>
          <h1 class="text-h5 font-weight-extrabold text-high-emphasis mb-1">
            {{ themeConfig.app.title }}
          </h1>
          <p class="text-caption text-medium-emphasis mb-0">
            Sistem POS & Inventaris Barang
          </p>
        </div>

        <!-- Alert Error -->
        <VAlert
          v-if="errors.email || errors.password"
          color="error"
          variant="tonal"
          closable
          density="compact"
          class="mb-4 rounded-lg text-caption font-weight-medium"
        >
          {{ Array.isArray(errors.email) ? errors.email[0] : (errors.email || 'Email atau kata sandi tidak valid.') }}
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
            © 2026 PT. DUMAI • Seluruh Hak Cipta Dilindungi
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
  background-color: #f8fafc;
  background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
  background-size: 24px 24px;
}

.login-card-container {
  max-width: 420px;
}

.login-card {
  background-color: #ffffff !important;
  border-color: #e2e8f0 !important;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02) !important;
}

.bg-primary-lighten-5 {
  background-color: rgba(var(--v-theme-primary), 0.08);
}

.login-checkbox :deep(.v-label) {
  font-size: 13px !important;
}
</style>
