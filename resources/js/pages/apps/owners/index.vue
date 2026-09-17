<script setup>
import { ref, onMounted } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'

definePage({
  meta: {
    public: true,
  },
})

const snackbar = useSnackbarStore()
const isLoading = ref(false)
const isSaving = ref(false)

const ownerForm = ref({
  id: null,
  name: '',
  email: '',
  phone: '',
  address: '',
  logo: null,
  qris_image: null,
})

const previewLogo = ref(null)
const previewQris = ref(null)

const fetchOwnerProfile = async () => {
  isLoading.value = true
  try {
    // Assuming we fetch all and take the first one since it's a single owner system
    const data = await $api('/apps/owners', { query: { page: 1, itemsPerPage: 1 } })
    const ownersList = data.data || data
    if (ownersList && ownersList.length > 0) {
      const owner = ownersList[0]
      ownerForm.value = {
        id: owner.id,
        name: owner.name || '',
        email: owner.email || '',
        phone: owner.phone || '',
        address: owner.address || '',
        logo: null, // Keep null for file input
        qris_image: null,
      }
      previewLogo.value = owner.logo ? `/storage/${owner.logo}` : null
      previewQris.value = owner.qris_image ? `/storage/${owner.qris_image}` : null
    }
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal memuat profil owner', 'error')
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchOwnerProfile()
})

const handleLogoChange = (file) => {
  const f = Array.isArray(file) ? file[0] : file
  if (f instanceof File) {
    previewLogo.value = URL.createObjectURL(f)
  } else if (!f) {
    previewLogo.value = null
  }
}

const handleQrisChange = (file) => {
  const f = Array.isArray(file) ? file[0] : file
  if (f instanceof File) {
    previewQris.value = URL.createObjectURL(f)
  } else if (!f) {
    previewQris.value = null
  }
}

const saveProfile = async () => {
  isSaving.value = true
  try {
    const formData = new FormData()
    
    if (ownerForm.value.name) formData.append('name', ownerForm.value.name)
    if (ownerForm.value.email) formData.append('email', ownerForm.value.email)
    if (ownerForm.value.phone) formData.append('phone', ownerForm.value.phone)
    if (ownerForm.value.address) formData.append('address', ownerForm.value.address)
    formData.append('status', 'Aktif')

    let logoFile = Array.isArray(ownerForm.value.logo) ? ownerForm.value.logo[0] : ownerForm.value.logo
    if (logoFile instanceof File) {
      formData.append('logo', logoFile)
    }

    let qrisFile = Array.isArray(ownerForm.value.qris_image) ? ownerForm.value.qris_image[0] : ownerForm.value.qris_image
    if (qrisFile instanceof File) {
      formData.append('qris_image', qrisFile)
    }

    if (ownerForm.value.id) {
      formData.append('_method', 'PUT')
      await $api(`/apps/owners/${ownerForm.value.id}`, {
        method: 'POST',
        body: formData,
      })
    } else {
      await $api('/apps/owners', {
        method: 'POST',
        body: formData,
      })
    }
    snackbar.show('Profil perusahaan berhasil diperbarui', 'success')
    fetchOwnerProfile()
  } catch (error) {
    console.error(error)
    const errMsg = error?.response?._data?.message || error?.data?.message || 'Gagal menyimpan profil'
    snackbar.show(errMsg, 'error')
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div class="pa-4 max-w-1200 mx-auto">
    <div class="mb-6">
      <h2 class="text-h4 font-weight-bold mb-1">
        Profil Perusahaan
      </h2>
      <p class="text-body-2 text-medium-emphasis mb-0">
        Kelola informasi utama dan identitas perusahaan Anda.
      </p>
    </div>

    <VRow v-if="isLoading">
      <VCol cols="12" class="text-center pa-10">
        <VProgressCircular indeterminate color="primary" size="64" />
      </VCol>
    </VRow>

    <VRow v-else>
      <VCol cols="12" md="4">
        <!-- Identity Card -->
        <VCard elevation="2" class="mb-4 text-center">
          <VCardText class="pa-6">
            <div class="d-flex justify-center mb-4">
              <VAvatar
                size="120"
                color="primary"
                variant="tonal"
                class="rounded-circle border"
              >
                <VImg
                  v-if="previewLogo"
                  :src="previewLogo"
                  cover
                />
                <VIcon
                  v-else
                  icon="ri-building-line"
                  size="60"
                />
              </VAvatar>
            </div>
            <h3 class="text-h5 font-weight-bold mb-1">
              {{ ownerForm.name || 'Nama Perusahaan' }}
            </h3>
            <p class="text-body-2 text-medium-emphasis mb-4">
              <VIcon icon="ri-map-pin-2-line" size="16" class="me-1" />
              {{ ownerForm.address || 'Alamat Belum Diatur' }}
            </p>
            <div class="d-flex justify-center gap-4">
              <VChip color="success" size="small" variant="elevated" class="font-weight-bold">
                <VIcon icon="ri-checkbox-circle-fill" size="14" class="me-1" />
                Status Aktif
              </VChip>
            </div>
          </VCardText>
        </VCard>

        <!-- QRIS Card -->
        <VCard elevation="2">
          <VCardTitle class="pa-4 pb-0 text-subtitle-1 font-weight-bold text-center">
            QRIS Pembayaran
          </VCardTitle>
          <VCardText class="pa-4 text-center">
            <div
              class="bg-grey-100 rounded-lg pa-4 d-flex align-center justify-center mx-auto"
              style="width: 200px; height: 200px; border: 2px dashed #ccc; background: #f8f9fa;"
            >
              <VImg
                v-if="previewQris"
                :src="previewQris"
                cover
                class="rounded"
              />
              <div v-else class="text-center text-medium-emphasis">
                <VIcon icon="ri-qr-code-line" size="48" class="mb-2" />
                <div class="text-caption">Belum ada QRIS</div>
              </div>
            </div>
            <p class="text-caption text-medium-emphasis mt-4">
              Gambar QRIS ini akan ditampilkan pada sistem kasir atau struk pembayaran.
            </p>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="8">
        <VCard elevation="2">
          <VCardTitle class="pa-4 font-weight-bold d-flex align-center">
            <VIcon icon="ri-file-edit-line" class="me-2 text-primary" />
            Edit Informasi Perusahaan
          </VCardTitle>
          <VDivider />
          <VCardText class="pa-6">
            <VForm @submit.prevent="saveProfile">
              <VRow>
                <VCol cols="12" md="6">
                  <VTextField
                    v-model="ownerForm.name"
                    label="Nama Perusahaan *"
                    placeholder="Masukkan nama perusahaan"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="ri-building-line"
                    :rules="[v => !!v || 'Nama wajib diisi']"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField
                    v-model="ownerForm.email"
                    label="Alamat Email *"
                    type="email"
                    placeholder="contoh@email.com"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="ri-mail-line"
                    :rules="[v => !!v || 'Email wajib diisi']"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField
                    v-model="ownerForm.phone"
                    label="Nomor Telepon"
                    placeholder="0812xxxxxxxx"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="ri-phone-line"
                  />
                </VCol>
                <VCol cols="12">
                  <VTextarea
                    v-model="ownerForm.address"
                    label="Alamat Lengkap"
                    placeholder="Masukkan alamat lengkap perusahaan"
                    variant="outlined"
                    density="comfortable"
                    prepend-inner-icon="ri-map-pin-line"
                    rows="3"
                  />
                </VCol>

                <VCol cols="12" md="6">
                  <VFileInput
                    v-model="ownerForm.logo"
                    label="Ubah Logo Perusahaan"
                    accept="image/png, image/jpeg, image/jpg"
                    prepend-icon=""
                    prepend-inner-icon="ri-image-add-line"
                    variant="outlined"
                    density="comfortable"
                    show-size
                    clearable
                    hint="Format JPG/PNG. Maksimal 2MB."
                    persistent-hint
                    @update:model-value="handleLogoChange"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <VFileInput
                    v-model="ownerForm.qris_image"
                    label="Ubah Gambar QRIS"
                    accept="image/png, image/jpeg, image/jpg"
                    prepend-icon=""
                    prepend-inner-icon="ri-qr-code-line"
                    variant="outlined"
                    density="comfortable"
                    show-size
                    clearable
                    hint="Format JPG/PNG. Maksimal 2MB."
                    persistent-hint
                    @update:model-value="handleQrisChange"
                  />
                </VCol>

                <VCol cols="12" class="d-flex justify-end mt-4">
                  <VBtn
                    color="primary"
                    type="submit"
                    size="large"
                    :loading="isSaving"
                    prepend-icon="ri-save-line"
                  >
                    Simpan Perubahan
                  </VBtn>
                </VCol>
              </VRow>
            </VForm>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<style scoped>
.max-w-1200 {
  max-width: 1200px;
}
</style>
