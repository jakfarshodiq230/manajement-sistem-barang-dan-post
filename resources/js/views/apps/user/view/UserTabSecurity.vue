<script setup>
import chrome from '@images/logos/chrome.png'

const props = defineProps({
  userData: {
    type: Object,
    required: true,
  },
})

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

const generateRandomPin = () => {
  const randomPin = Math.floor(100000 + Math.random() * 900000).toString()
  newPin.value = randomPin
  confirmPin.value = randomPin
  isPinVisible.value = true
  isConfirmPinVisible.value = true
}

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
    key: 'recentActivity',
  },
]

const recentDevices = computed(() => props.userData?.recentDevices || [])
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
                <VBtn
                  color="info"
                  variant="tonal"
                  class="ms-3"
                  @click="generateRandomPin"
                >
                  Generate Acak
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
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
              <VAvatar color="primary" variant="tonal" :size="30" class="me-4"><VIcon icon="ri-computer-line" size="20" /></VAvatar>
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
  
</template>
