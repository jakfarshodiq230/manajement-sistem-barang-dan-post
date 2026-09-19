<script setup>
import authV2ForgotPasswordIllustrationBorderedDark from '@images/pages/auth-v2-forgot-password-illustration-bordered-dark.png'
import authV2ForgotPasswordIllustrationBorderedLight from '@images/pages/auth-v2-forgot-password-illustration-bordered-light.png'
import authV2ForgotPasswordIllustrationDark from '@images/pages/auth-v2-forgot-password-illustration-dark.png'
import authV2ForgotPasswordIllustrationLight from '@images/pages/auth-v2-forgot-password-illustration-light.png'
import authV2ForgotPasswordMaskDark from '@images/pages/auth-v2-forgot-password-mask-dark.png'
import authV2ForgotPasswordMaskLight from '@images/pages/auth-v2-forgot-password-mask-light.png'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'

const authThemeImg = useGenerateImageVariant(authV2ForgotPasswordIllustrationLight, authV2ForgotPasswordIllustrationDark, authV2ForgotPasswordIllustrationBorderedLight, authV2ForgotPasswordIllustrationBorderedDark, true)
const authThemeMask = useGenerateImageVariant(authV2ForgotPasswordMaskLight, authV2ForgotPasswordMaskDark)
const email = ref('')
const isLoading = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

const submit = async () => {
  isLoading.value = true
  successMessage.value = ''
  errorMessage.value = ''
  
  try {
    const response = await $api('/auth/forgot-password', {
      method: 'POST',
      body: { email: email.value },
      onResponseError({ response }) {
        errorMessage.value = response._data?.message || 'Gagal mengirim tautan reset kata sandi.'
      },
    })
    
    if (response?.message) {
      successMessage.value = response.message
      email.value = '' // Clear email after success
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
  <div class="login-page-wrapper d-flex align-center justify-center pa-4">
    <div class="w-100 login-card-container">
      <VCard class="login-card rounded border px-sm-8 px-4 py-8" >
        <!-- Logo & Header -->
        <div class="d-flex flex-column align-center text-center mb-8">
          <div class="bg-primary-lighten-5 pa-4 rounded-circle mb-4">
            <VNodeRenderer :nodes="themeConfig.app.logo" />
          </div>
          
          <h2 class="text-h5 font-weight-bold mb-2">
            Lupa Kata Sandi? 🔒
          </h2>
          <p class="text-body-2 text-medium-emphasis mb-0 px-2" v-if="!successMessage">
            Masukkan email Anda yang terdaftar dan kami akan mengirimkan instruksi untuk mengatur ulang kata sandi.
          </p>
        </div>

        <!-- Success State -->
        <div v-if="successMessage" class="text-center">
          <div class="mb-6">
            <VAvatar
              color="success"
              variant="tonal"
              size="80"
              class="mb-4"
            >
              <VIcon icon="ri-mail-send-line" size="40" />
            </VAvatar>
            <h4 class="text-h5 mb-2 font-weight-bold">
              Email Terkirim! ✉️
            </h4>
            <p class="text-body-2 text-medium-emphasis mb-6 px-4">
              {{ successMessage }}
            </p>
          </div>
          
          <VBtn
            block
            color="primary"
            variant="flat"
            size="large"
            rounded="lg"
            :to="{ name: 'login' }"
            class="mb-4 text-none font-weight-bold"
          >
            Kembali ke Halaman Login
          </VBtn>
          <div class="text-caption text-medium-emphasis mt-2">
            Belum menerima email? Coba periksa folder Spam atau 
            <a href="#" class="text-primary font-weight-bold text-decoration-none" @click.prevent="successMessage = ''">Kirim Ulang</a>
          </div>
        </div>

        <!-- Form State -->
        <VForm v-else @submit.prevent="submit">
          <VAlert
            v-if="errorMessage"
            type="error"
            variant="tonal"
            class="mb-6 rounded-lg text-caption"
            closable
            @click:close="errorMessage = ''"
          >
            {{ errorMessage }}
          </VAlert>

          <div class="d-flex flex-column gap-4">
            <!-- Email -->
            <div>
              <label class="text-caption font-weight-bold text-medium-emphasis mb-1 d-block">
                Alamat Email
              </label>
              <VTextField
                v-model="email"
                placeholder="nama@perusahaan.com"
                type="email"
                prepend-inner-icon="ri-mail-line"
                autofocus
                variant="outlined"
                density="compact"
                rounded="lg"
                required
                hide-details="auto"
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
              :disabled="isLoading || !email"
              class="font-weight-bold elevation-1 text-none mt-2"
            >
              Kirim Tautan Reset
              <template #loader>
                <span class="d-flex align-center">
                  <VProgressCircular indeterminate size="20" width="2" class="me-2" />
                  Mengirim...
                </span>
              </template>
            </VBtn>
          </div>

          <!-- back to login -->
          <div class="mt-6 text-center">
            <RouterLink
              class="d-flex align-center justify-center text-primary font-weight-medium text-decoration-none text-caption"
              :to="{ name: 'login' }"
            >
              <VIcon
                icon="ri-arrow-left-s-line"
                size="16"
                class="me-1 flip-in-rtl"
              />
              <span>Kembali ke Halaman Login</span>
            </RouterLink>
          </div>
        </VForm>

        <!-- Footer Note -->
        <div class="mt-8 pt-4 border-t text-center">
          <div class="text-caption text-disabled" style="font-size: 12px;">
            © {{ new Date().getFullYear() }} Manajemen Barang & POS — Seluruh Hak Cipta Dilindungi
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
</style>
