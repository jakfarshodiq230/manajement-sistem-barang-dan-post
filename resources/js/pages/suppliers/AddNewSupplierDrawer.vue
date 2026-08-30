<script setup>
import { ref, watch, nextTick } from 'vue'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  selectedSupplier: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'saveData',
])

const isFormValid = ref(false)
const refForm = ref()
const id = ref(null)
const name = ref('')
const contact_person = ref('')
const phone = ref('')
const email = ref('')
const address = ref('')
const is_active = ref(true)

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  emit('update:is-drawer-open', false)
  emit('close')
  emit('cancel')
  nextTick(() => {
    refForm.value?.resetValidation()
  })
}

watch(() => props.selectedSupplier, newVal => {
  if (newVal) {
    id.value = newVal.id
    name.value = newVal.name
    contact_person.value = newVal.contact_person || ''
    phone.value = newVal.phone || ''
    email.value = newVal.email || ''
    address.value = newVal.address || ''
    is_active.value = newVal.is_active === 1 || newVal.is_active === true
  } else {
    id.value = null
    name.value = ''
    contact_person.value = ''
    phone.value = ''
    email.value = ''
    address.value = ''
    is_active.value = true
  }
}, { immediate: true })

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      emit('saveData', {
        id: id.value,
        name: name.value,
        contact_person: contact_person.value,
        phone: phone.value,
        email: email.value,
        address: address.value,
        is_active: is_active.value,
      })
      closeNavigationDrawer()
    }
  })
}

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
  emit('update:is-drawer-open', val)
  if (!val) {
    emit('close')
    emit('cancel')
  }
}
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="$vuetify.display.xs ? '100%' : ($vuetify.display.smAndDown ? '92vw' : 480)"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
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
            :icon="props.selectedSupplier ? 'ri-building-line' : 'ri-user-add-line'"
            size="24"
          />
        </VAvatar>
        <div>
          <h5 class="text-h6 font-weight-bold mb-0">
            {{ props.selectedSupplier ? 'Edit Data Supplier' : 'Tambah Supplier Baru' }}
          </h5>
          <span class="text-caption text-medium-emphasis">
            Kemitraan vendor & distributor pengadaan barang
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

    <PerfectScrollbar :options="{ wheelPropagation: false }" style="height: calc(100vh - 75px);">
      <VCard flat class="pa-6">
        <VForm
          ref="refForm"
          v-model="isFormValid"
          @submit.prevent="onSubmit"
        >
          <!-- Section 1: Profil Supplier -->
          <div class="mb-5">
            <div class="d-flex align-center gap-2 mb-3">
              <VIcon icon="ri-building-2-line" color="primary" size="18" />
              <span class="text-subtitle-2 font-weight-bold text-uppercase letter-spacing-1 text-primary">
                1. Profil Perusahaan / Vendor
              </span>
            </div>

            <VRow dense>
              <VCol cols="12">
                <VTextField
                  v-model="name"
                  :rules="[v => !!v || 'Nama supplier wajib diisi']"
                  label="Nama Perusahaan / Supplier"
                  placeholder="Misal: PT. Sparepart Auto Nusantara"
                  density="comfortable"
                  variant="outlined"
                  prepend-inner-icon="ri-building-line"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="contact_person"
                  label="Nama Kontak PIC"
                  placeholder="Misal: Bpk. Gunawan (Sales Manager)"
                  density="comfortable"
                  variant="outlined"
                  prepend-inner-icon="ri-user-line"
                />
              </VCol>
            </VRow>
          </div>

          <VDivider class="my-5" />

          <!-- Section 2: Kontak & Alamat -->
          <div class="mb-5">
            <div class="d-flex align-center gap-2 mb-3">
              <VIcon icon="ri-contacts-line" color="info" size="18" />
              <span class="text-subtitle-2 font-weight-bold text-uppercase letter-spacing-1 text-info">
                2. Kontak & Alamat Operasional
              </span>
            </div>

            <VRow dense>
              <VCol cols="12" sm="6">
                <VTextField
                  v-model="phone"
                  label="No. Telepon / WhatsApp"
                  placeholder="Misal: 081234567890"
                  density="comfortable"
                  variant="outlined"
                  prepend-inner-icon="ri-phone-line"
                />
              </VCol>

              <VCol cols="12" sm="6">
                <VTextField
                  v-model="email"
                  label="Alamat Email"
                  type="email"
                  placeholder="vendor@supplier.com"
                  density="comfortable"
                  variant="outlined"
                  prepend-inner-icon="ri-mail-line"
                />
              </VCol>

              <VCol cols="12" class="mt-2">
                <VTextarea
                  v-model="address"
                  label="Alamat Kantor / Gudang Supplier"
                  placeholder="Tuliskan alamat lengkap pengiriman/faktur..."
                  rows="3"
                  density="comfortable"
                  variant="outlined"
                  prepend-inner-icon="ri-map-pin-line"
                />
              </VCol>
            </VRow>
          </div>

          <VDivider class="my-5" />

          <!-- Section 3: Status Kemitraan -->
          <div class="mb-6">
            <div class="d-flex align-center gap-2 mb-3">
              <VIcon icon="ri-shield-check-line" color="success" size="18" />
              <span class="text-subtitle-2 font-weight-bold text-uppercase letter-spacing-1 text-success">
                3. Status Kemitraan
              </span>
            </div>

            <VSwitch
              v-model="is_active"
              :label="is_active ? 'Status: Kemitraan Aktif (Dapat Dipesan)' : 'Status: Kemitraan Nonaktif / Diblokir'"
              color="success"
              inset
            />
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
              {{ props.selectedSupplier ? 'Simpan Data Supplier' : 'Daftarkan Supplier' }}
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
      </VCard>
    </PerfectScrollbar>
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
