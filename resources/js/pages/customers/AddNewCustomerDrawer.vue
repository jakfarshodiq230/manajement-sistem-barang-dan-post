<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  selectedCustomer: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'update:is-drawer-open',
  'close',
  'cancel',
  'saveData',
])

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  emit('update:is-drawer-open', false)
  emit('close')
  emit('cancel')
}

const formData = ref({
  name: '',
  email: '',
  phone: '',
  nik: '',
  company_name: '',
  address: '',
  city: '',
  province: '',
  notes: '',
  credit_limit: 0,
  is_active: true,
})

watch(() => props.selectedCustomer, newVal => {
  if (newVal) {
    formData.value = { ...newVal }
  } else {
    formData.value = {
      name: '',
      email: '',
      phone: '',
      nik: '',
      company_name: '',
      address: '',
      city: '',
      province: '',
      notes: '',
      credit_limit: 0,
      is_active: true,
    }
  }
}, { immediate: true })

const creditLimitDisplay = computed({
  get: () => {
    return formData.value.credit_limit ? new Intl.NumberFormat('id-ID').format(formData.value.credit_limit) : ''
  },
  set: val => {
    const numericStr = String(val).replace(/\D/g, '')
    formData.value.credit_limit = numericStr ? parseInt(numericStr, 10) : 0
  },
})

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
  emit('update:is-drawer-open', val)
  if (!val) {
    emit('close')
    emit('cancel')
  }
}

const onSubmit = () => {
  emit('saveData', formData.value)
  closeNavigationDrawer()
}
</script>

<template>
  <VNavigationDrawer
    :model-value="props.isDrawerOpen"
    temporary
    location="end"
    :width="$vuetify.display.xs ? '100%' : ($vuetify.display.smAndDown ? '92vw' : 520)"
    class="scrollable-content"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- Header -->
    <div class="d-flex align-center justify-space-between px-6 py-5 border-b bg-gradient-header">
      <div class="d-flex align-center gap-3">
        <VAvatar
          size="42"
          color="primary"
          variant="tonal"
          class="rounded-lg"
        >
          <VIcon
            :icon="props.selectedCustomer ? 'ri-user-settings-line' : 'ri-user-add-line'"
            size="24"
          />
        </VAvatar>
        <div>
          <h5 class="text-h6 font-weight-bold mb-0">
            {{ props.selectedCustomer ? 'Edit Data Pelanggan' : 'Tambah Pelanggan Baru' }}
          </h5>
          <span class="text-caption text-medium-emphasis">
            Buku kontak pelanggan & batas limit kredit piutang
          </span>
        </div>
      </div>
      <VBtn
        icon="ri-close-line"
        variant="tonal"
        color="secondary"
        size="small"
        type="button"
        @click.stop="closeNavigationDrawer"
      />
    </div>

    <div
      class="overflow-auto pa-6"
      style="max-height: calc(100vh - 75px);"
    >
      <VForm @submit.prevent="onSubmit">
        <!-- Section 1: Data Identitas -->
        <div class="mb-5">
          <div class="d-flex align-center gap-2 mb-3">
            <VIcon icon="ri-user-line" color="primary" size="18" />
            <span class="text-subtitle-2 font-weight-bold text-uppercase letter-spacing-1 text-primary">
              1. Identitas & Kontak Pelanggan
            </span>
          </div>

          <VRow dense>
            <VCol cols="12">
              <VTextField
                v-model="formData.name"
                :rules="[v => !!v || 'Nama pelanggan wajib diisi']"
                label="Nama Lengkap Pelanggan"
                placeholder="Misal: Bapak Hendra Wijaya"
                density="comfortable"
                variant="outlined"
                prepend-inner-icon="ri-user-3-line"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <VTextField
                v-model="formData.phone"
                label="No. Telepon / WhatsApp"
                placeholder="Misal: 081234567890"
                density="comfortable"
                variant="outlined"
                prepend-inner-icon="ri-phone-line"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <VTextField
                v-model="formData.nik"
                label="NIK / No. KTP"
                placeholder="16 Digit NIK"
                density="comfortable"
                variant="outlined"
                prepend-inner-icon="ri-id-card-line"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <VTextField
                v-model="formData.email"
                label="Alamat Email"
                type="email"
                placeholder="pelanggan@email.com"
                density="comfortable"
                variant="outlined"
                prepend-inner-icon="ri-mail-line"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <VTextField
                v-model="formData.company_name"
                label="Nama Perusahaan / Toko"
                placeholder="Misal: PT / Toko Bangun Jaya"
                density="comfortable"
                variant="outlined"
                prepend-inner-icon="ri-building-line"
              />
            </VCol>
          </VRow>
        </div>

        <VDivider class="my-5" />

        <!-- Section 2: Kebijakan Piutang (Credit Limit) -->
        <div class="mb-5">
          <div class="d-flex align-center gap-2 mb-3">
            <VIcon icon="ri-hand-coin-line" color="warning" size="18" />
            <span class="text-subtitle-2 font-weight-bold text-uppercase letter-spacing-1 text-warning">
              2. Batas Limit Piutang (Kredit)
            </span>
          </div>

          <VRow dense>
            <VCol cols="12">
              <VTextField 
                v-model="creditLimitDisplay" 
                label="Batas Maksimal Piutang (Credit Limit) (Rp)" 
                type="text" 
                prefix="Rp"
                placeholder="0"
                density="comfortable"
                variant="outlined"
                prepend-inner-icon="ri-wallet-3-line"
                hint="Maksimal nominal tagihan piutang bon yang diperbolehkan di kasir POS"
                persistent-hint
              />
            </VCol>
          </VRow>
        </div>

        <VDivider class="my-5" />

        <!-- Section 3: Alamat & Catatan -->
        <div class="mb-6">
          <div class="d-flex align-center gap-2 mb-3">
            <VIcon icon="ri-map-pin-line" color="info" size="18" />
            <span class="text-subtitle-2 font-weight-bold text-uppercase letter-spacing-1 text-info">
              3. Alamat Domisili & Status
            </span>
          </div>

          <VRow dense>
            <VCol cols="12">
              <VTextarea
                v-model="formData.address"
                label="Alamat Tempat Tinggal / Toko"
                placeholder="Tuliskan jalan, nomor, RT/RW..."
                rows="2"
                density="comfortable"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <VTextField
                v-model="formData.city"
                label="Kota / Kabupaten"
                placeholder="Misal: Dumai"
                density="comfortable"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <VTextField
                v-model="formData.province"
                label="Provinsi"
                placeholder="Misal: Riau"
                density="comfortable"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12" class="mt-2">
              <VTextarea
                v-model="formData.notes"
                label="Catatan Khusus Pelanggan"
                placeholder="Keterangan preferensi order, diskon khusus, dll..."
                rows="2"
                density="comfortable"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12">
              <VSwitch
                v-model="formData.is_active"
                :label="formData.is_active ? 'Status: Pelanggan Aktif' : 'Status: Pelanggan Nonaktif'"
                color="success"
                inset
                class="mt-2"
              />
            </VCol>
          </VRow>
        </div>

        <!-- Sticky Action Bar -->
        <div class="d-flex align-center gap-3 pt-2">
          <VBtn
            type="submit"
            color="primary"
            size="large"
            prepend-icon="ri-save-3-line"
            class="font-weight-bold flex-grow-1 rounded-lg shadow-sm"
          >
            {{ props.selectedCustomer ? 'Simpan Perubahan Data' : 'Daftarkan Pelanggan' }}
          </VBtn>
          <VBtn
            type="button"
            variant="outlined"
            color="secondary"
            size="large"
            class="rounded-lg px-5"
            @click.stop="closeNavigationDrawer"
          >
            Batal
          </VBtn>
        </div>
      </VForm>
    </div>
  </VNavigationDrawer>
</template>

<style scoped>
.bg-gradient-header {
  background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.05) 0%, rgba(var(--v-theme-surface), 1) 100%);
}
.letter-spacing-1 {
  letter-spacing: 0.5px;
}
</style>
