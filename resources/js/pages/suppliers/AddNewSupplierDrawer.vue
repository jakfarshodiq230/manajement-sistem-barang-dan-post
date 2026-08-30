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
}
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="400"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <AppDrawerHeaderSection
      :title="props.selectedSupplier ? 'Edit Supplier' : 'Tambah Supplier Baru'"
      @cancel="closeNavigationDrawer"
    />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <VCol cols="12">
                <VTextField
                  v-model="name"
                  :rules="[v => !!v || 'Nama supplier wajib diisi']"
                  label="Nama Supplier / Perusahaan"
                  placeholder="Misal: PT. Sparepart Auto"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="contact_person"
                  label="Nama Kontak (PIC)"
                  placeholder="Misal: Budi"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="phone"
                  label="Nomor Telepon/WA"
                  placeholder="08123456789"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="email"
                  label="Email"
                  type="email"
                  placeholder="email@perusahaan.com"
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="address"
                  label="Alamat Lengkap"
                  rows="3"
                />
              </VCol>
              
              <VCol cols="12">
                <VSwitch
                  v-model="is_active"
                  :label="is_active ? 'Status: Aktif' : 'Status: Nonaktif'"
                  color="success"
                />
              </VCol>

              <VCol cols="12">
                <VBtn
                  type="submit"
                  class="me-3"
                >
                  Simpan
                </VBtn>
                <VBtn
                  type="button"
                  variant="outlined"
                  color="error"
                  @click="closeNavigationDrawer"
                >
                  Batal
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>
