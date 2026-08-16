<script setup>
import chrome from '@images/logos/chrome.png'

const isNewPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)
const smsVerificationNumber = ref('')
const isTwoFactorDialogOpen = ref(false)

const isPinVisible = ref(false)
const isConfirmPinVisible = ref(false)
const newPin = ref('')
const confirmPin = ref('')
const isSavingPin = ref(false)

import { useSnackbarStore } from '@/stores/snackbar'

const { show: showSnackbar } = useSnackbarStore()

const savePin = async () => {
  if (newPin.value !== confirmPin.value) {
    showSnackbar('Konfirmasi PIN tidak cocok', 'error')
    
    return
  }

  if (newPin.value.length !== 6 || !/^\d+$/.test(newPin.value)) {
    showSnackbar('PIN harus 6 digit angka', 'error')
    
    return
  }

  isSavingPin.value = true
  try {
    const response = await $api('/apps/update-pin', {
      method: 'POST',
      body: { pin: newPin.value },
    })

    showSnackbar(response.message || 'PIN Kasir berhasil disimpan', 'success')
    newPin.value = ''
    confirmPin.value = ''
  } catch (error) {
    showSnackbar(error.data?.message || 'Gagal menyimpan PIN', 'error')
  } finally {
    isSavingPin.value = false
  }
}

// Recent devices Headers
const recentDeviceHeader = [
  {
    title: 'BROWSER',
    key: 'browser',
  },
  {
    title: 'DEVICE',
    key: 'device',
  },
  {
    title: 'LOCATION',
    key: 'location',
  },
  {
    title: 'RECENT ACTIVITY',
    key: 'activity',
  },
]

const recentDevices = [
  {
    browser: 'Chrome on Windows',
    logo: chrome,
    device: 'Dell XPS 15',
    location: 'United States',
    activity: '10, Jan 2020 20:07',
  },
  {
    browser: 'Chrome on Android',
    logo: chrome,
    device: 'Google Pixel 3a',
    location: 'Ghana',
    activity: '11, Jan 2020 10:16',
  },
  {
    browser: 'Chrome on macOS',
    logo: chrome,
    device: 'Apple iMac',
    location: 'Mayotte',
    activity: '11, Jan 2020 12:10',
  },
  {
    browser: 'Chrome on iPhone',
    logo: chrome,
    device: 'Apple iPhone XR',
    location: 'Mauritania',
    activity: '12, Jan 2020 8:29',
  },
]
</script>

<template>
  <VRow>
    <VCol cols="12">
      <!-- 👉 Change password -->
      <VCard title="Change Password">
        <VCardText>
          <VAlert
            variant="tonal"
            color="warning"
            closable
            class="mb-4"
          >
            <VAlertTitle>Ensure that these requirements are met</VAlertTitle>
            <span>Minimum 8 characters long, uppercase & symbol</span>
          </VAlert>

          <VForm @submit.prevent="() => {}">
            <VRow>
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  label="New Password"
                  placeholder="············"
                  :type="isNewPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isNewPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                  @click:append-inner="isNewPasswordVisible = !isNewPasswordVisible"
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  label="Confirm Password"
                  autocomplete="confirm-password"
                  placeholder="············"
                  :type="isConfirmPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isConfirmPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                  @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
                />
              </VCol>

              <VCol cols="12">
                <VBtn type="submit">
                  Change Password
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12">
      <!-- 👉 POS PIN Setup -->
      <VCard
        title="Pengaturan PIN Kasir (POS)"
        subtitle="PIN 6 digit ini digunakan untuk otorisasi khusus (Nego, Retur, dll) di mesin Kasir POS."
      >
        <VCardText>
          <VForm @submit.prevent="savePin">
            <VRow>
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="newPin"
                  label="PIN Baru (6 Angka)"
                  placeholder="123456"
                  maxlength="6"
                  :type="isPinVisible ? 'text' : 'password'"
                  :append-inner-icon="isPinVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                  @click:append-inner="isPinVisible = !isPinVisible"
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="confirmPin"
                  label="Konfirmasi PIN Baru"
                  placeholder="123456"
                  maxlength="6"
                  :type="isConfirmPinVisible ? 'text' : 'password'"
                  :append-inner-icon="isConfirmPinVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                  @click:append-inner="isConfirmPinVisible = !isConfirmPinVisible"
                />
              </VCol>

              <VCol cols="12">
                <VBtn
                  type="submit"
                  :loading="isSavingPin"
                >
                  Simpan PIN
                </VBtn>
                <VBtn
                  type="reset"
                  color="secondary"
                  variant="outlined"
                  class="ms-3"
                  @click="newPin = ''; confirmPin = ''"
                >
                  Reset
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12">
      <!-- 👉 Two step verification -->
      <VCard
        title="Two-step verification"
        subtitle="Keep your account secure with authentication step."
      >
        <VCardText>
          <div>
            <h6 class="text-h6 mb-1">
              SMS
            </h6>
            <VTextField
              :model-value="smsVerificationNumber"
              readonly
              placeholder="+1(968) 819-2547"
              density="compact"
            >
              <template #append>
                <IconBtn
                  rounded
                  variant="outlined"
                  color="secondary"
                  class="me-2 ms-1"
                >
                  <VIcon
                    icon="ri-edit-box-line"
                    @click="isTwoFactorDialogOpen = true"
                  />
                </IconBtn>

                <IconBtn
                  rounded
                  variant="outlined"
                  color="secondary"
                >
                  <VIcon icon="ri-user-add-line" />
                </IconBtn>
              </template>
            </VTextField>
          </div>

          <p class="mb-0 mt-4">
            Two-factor authentication adds an additional layer of security to your account by requiring more than just a password to log in. <a
              href="javascript:void(0)"
              class="text-decoration-none"
            >Learn more</a>.
          </p>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12">
      <!-- 👉 Recent devices -->
      <VCard title="Recent devices">
        <VDataTable
          :items="recentDevices"
          :headers="recentDeviceHeader"
          hide-default-footer
          class="text-no-wrap rounded-0"
        >
          <template #item.browser="{ item }">
            <div class="d-flex align-center">
              <VAvatar
                :image="item.logo"
                :size="22"
                class="me-4"
              />
              <h6 class="text-h6 font-weight-regular">
                {{ item.browser }}
              </h6>
            </div>
          </template>
          <!-- TODO Refactor this after vuetify provides proper solution for removing default footer -->
          <template #bottom />
        </VDataTable>
      </VCard>
    </VCol>
  </VRow>

  <!-- 👉 Enable One Time Password Dialog -->
  <TwoFactorAuthDialog
    v-model:is-dialog-visible="isTwoFactorDialogOpen"
    :sms-code="smsVerificationNumber"
  />
</template>
