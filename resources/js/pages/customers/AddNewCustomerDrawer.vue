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

const emit = defineEmits(['update:isDrawerOpen', 'saveData'])

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
}

const onSubmit = () => {
  emit('saveData', formData.value)
  emit('update:isDrawerOpen', false)
}
</script>

<template>
  <VNavigationDrawer
    :model-value="props.isDrawerOpen"
    temporary
    location="end"
    width="400"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- Header -->
    <div class="d-flex align-center pa-6 pb-4">
      <h6 class="text-h6">
        {{ props.selectedCustomer ? 'Edit Pelanggan' : 'Tambah Pelanggan Baru' }}
      </h6>
      <VSpacer />
      <VBtn
        icon
        variant="tonal"
        color="secondary"
        size="small"
        @click="emit('update:isDrawerOpen', false)"
      >
        <VIcon icon="ri-close-line" />
      </VBtn>
    </div>
    <VDivider />

    <!-- Form -->
    <div
      class="overflow-auto pa-5"
      style="max-height: calc(100vh - 80px);"
    >
      <VForm @submit.prevent="onSubmit">
        <VRow>
          <VCol cols="12">
            <VTextField
              v-model="formData.name"
              label="Nama Pelanggan"
              required
            />
          </VCol>
          <VCol cols="12">
            <VTextField
              v-model="formData.phone"
              label="No Telepon / WhatsApp"
            />
          </VCol>
          <VCol cols="12">
            <VTextField
              v-model="formData.nik"
              label="NIK / KTP"
            />
          </VCol>
          <VCol cols="12">
            <VTextField
              v-model="formData.email"
              label="Email"
              type="email"
            />
          </VCol>
          <VCol cols="12">
            <VTextField
              v-model="formData.company_name"
              label="Perusahaan / Instansi"
            />
          </VCol>
          <VCol cols="12">
            <VTextField 
              v-model="creditLimitDisplay" 
              label="Batas Piutang (Credit Limit)" 
              type="text" 
              prefix="Rp"
            />
          </VCol>
          <VCol cols="12">
            <VTextarea
              v-model="formData.address"
              label="Alamat Lengkap"
              rows="2"
            />
          </VCol>
          <VCol cols="6">
            <VTextField
              v-model="formData.city"
              label="Kota"
            />
          </VCol>
          <VCol cols="6">
            <VTextField
              v-model="formData.province"
              label="Provinsi"
            />
          </VCol>
          <VCol cols="12">
            <VTextarea
              v-model="formData.notes"
              label="Catatan"
              rows="2"
            />
          </VCol>
          <VCol cols="12">
            <VSwitch
              v-model="formData.is_active"
              label="Status Aktif"
              color="success"
            />
          </VCol>
          <VCol
            cols="12"
            class="d-flex gap-4"
          >
            <VBtn type="submit">
              Simpan
            </VBtn>
            <VBtn
              color="secondary"
              variant="outlined"
              @click="emit('update:isDrawerOpen', false)"
            >
              Batal
            </VBtn>
          </VCol>
        </VRow>
      </VForm>
    </div>
  </VNavigationDrawer>
</template>
