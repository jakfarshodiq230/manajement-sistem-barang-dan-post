<script setup>
import authV2ResetPasswordIllustrationBorderedDark from '@images/pages/auth-v2-reset-password-illustration-bordered-dark.png'
import authV2ResetPasswordIllustrationBorderedLight from '@images/pages/auth-v2-reset-password-illustration-bordered-light.png'
import authV2ResetPasswordIllustrationDark from '@images/pages/auth-v2-reset-password-illustration-dark.png'
import authV2ResetPasswordIllustrationLight from '@images/pages/auth-v2-reset-password-illustration-light.png'
import authV2MaskDark from '@images/pages/auth-v2-reset-password-mask-dark.png'
import authV2MaskLight from '@images/pages/auth-v2-reset-password-mask-light.png'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'

const authThemeImg = useGenerateImageVariant(authV2ResetPasswordIllustrationLight, authV2ResetPasswordIllustrationDark, authV2ResetPasswordIllustrationBorderedLight, authV2ResetPasswordIllustrationBorderedDark, true)
const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)

const route = useRoute()
const router = useRouter()

const token = ref(route.query.token || '')
const email = ref(route.query.email || '')
const password = ref('')
const passwordConfirmation = ref('')
const isPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)

const isLoading = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

const submit = async () => {
  isLoading.value = true
  successMessage.value = ''
  errorMessage.value = ''
  
  if (password.value !== passwordConfirmation.value) {
    errorMessage.value = 'Kata sandi dan konfirmasi kata sandi tidak cocok.'
    isLoading.value = false
    return
  }
  
  try {
    const response = await $api('/auth/reset-password', {
      method: 'POST',
      body: {
        token: token.value,
        email: email.value,
        password: password.value,
        password_confirmation: passwordConfirmation.value
      },
      onResponseError({ response }) {
        errorMessage.value = response._data?.message || 'Gagal mengatur ulang kata sandi. Tautan mungkin telah kedaluwarsa.'
      },
    })
    
    if (response?.message) {
      successMessage.value = response.message
      setTimeout(() => {
        router.push('/login')
      }, 3000)
    }
  } catch (error) {
    console.error(error)
  } finally {
    isLoading.value = false
  }
}

definePage({
  meta: {
    layout: 'blank',
    unauthenticatedOnly: true,
  },
})
</script>

<template>
  <RouterLink to="/">
    <div class="app-logo auth-logo">
      <VNodeRenderer :nodes="themeConfig.app.logo" />
      <h1 class="app-logo-title">
        {{ themeConfig.app.title }}
      </h1>
    </div>
  </RouterLink>

  <VRow
    class="auth-wrapper"
    no-gutters
  >
    <VCol
      md="8"
      class="d-none d-md-flex align-center justify-center position-relative"
    >
      <div class="d-flex align-center justify-center pa-10">
        <img
          :src="authThemeImg"
          class="auth-illustration w-100"
          alt="auth-illustration"
        >
      </div>
      <VImg
        :src="authThemeMask"
        class="d-none d-md-flex auth-footer-mask"
        alt="auth-mask"
      />
    </VCol>

    <VCol
      cols="12"
      md="4"
      class="auth-card-v2 d-flex align-center justify-center"
      style="background-color: rgb(var(--v-theme-surface));"
    >
      <VCard
        flat
        :max-width="500"
        class="mt-12 mt-sm-0 pa-5 pa-lg-7"
      >
        <VCardText>
          <h4 class="text-h4 mb-1">
            Atur Ulang Kata Sandi 🔒
          </h4>
          <p class="mb-0">
            Kata sandi baru Anda harus berbeda dari kata sandi yang digunakan sebelumnya
          </p>
        </VCardText>

        <VCardText>
          <VAlert
            v-if="successMessage"
            type="success"
            variant="tonal"
            class="mb-4"
          >
            {{ successMessage }}<br>Mengalihkan ke halaman login...
          </VAlert>

          <VAlert
            v-if="errorMessage"
            type="error"
            variant="tonal"
            class="mb-4"
          >
            {{ errorMessage }}
          </VAlert>

          <VForm @submit.prevent="submit" v-if="!successMessage">
            <VRow>
              <!-- email (hidden but required if they want to verify, or we can just show it disabled) -->
              <VCol cols="12">
                <VTextField
                  v-model="email"
                  label="Email"
                  type="email"
                  readonly
                  disabled
                />
              </VCol>

              <!-- password -->
              <VCol cols="12">
                <VTextField
                  v-model="password"
                  label="Kata Sandi Baru"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                  required
                />
              </VCol>

              <!-- Confirm Password -->
              <VCol cols="12">
                <VTextField
                  v-model="passwordConfirmation"
                  label="Konfirmasi Kata Sandi"
                  :type="isConfirmPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isConfirmPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                  @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
                  required
                />
              </VCol>

              <!-- Submit Button -->
              <VCol cols="12">
                <VBtn
                  block
                  type="submit"
                  :loading="isLoading"
                  :disabled="isLoading || !password || !passwordConfirmation"
                >
                  Simpan Kata Sandi Baru
                </VBtn>
              </VCol>

              <!-- back to login -->
              <VCol cols="12">
                <RouterLink
                  class="d-flex align-center justify-center"
                  :to="{ name: 'login' }"
                >
                  <VIcon
                    icon="ri-arrow-left-s-line"
                    size="20"
                    class="me-2 flip-in-rtl"
                  />
                  <span>Kembali ke Login</span>
                </RouterLink>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>
