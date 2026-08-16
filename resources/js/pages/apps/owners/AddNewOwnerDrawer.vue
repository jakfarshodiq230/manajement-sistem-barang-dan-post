<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { VForm } from 'vuetify/components/VForm'
import { nextTick, ref, watch } from 'vue'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  selectedOwner: {
    type: Object,
    default: null,
  },
  ownersList: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'ownerData',
])

const isFormValid = ref(false)
const refForm = ref()

const name = ref('')
const email = ref('')
const phone = ref('')
const address = ref('')
const parent_id = ref(null)
const status = ref('Aktif')
const id = ref(null)
const logo = ref(null)
const previewLogo = ref(null)
const qrisImage = ref(null)
const previewQris = ref(null)

const parentOptions = computed(() => {
  // Only top level owners can be selected as parents, or just anyone except self
  return [
    { id: null, name: '- Pemilik Utama (Root) -' },
    ...props.ownersList.filter(o => o.id !== id.value && !o.parent_id),
  ]
})

watch(() => props.selectedOwner, newVal => {
  if (newVal) {
    id.value = newVal.id
    name.value = newVal.name
    email.value = newVal.email || ''
    phone.value = newVal.phone || ''
    address.value = newVal.address || ''
    parent_id.value = newVal.parent_id
    status.value = newVal.status || 'Aktif'
    logo.value = null // reset logo input when editing
    previewLogo.value = newVal.logo ? `/storage/${newVal.logo}` : null
    qrisImage.value = null
    previewQris.value = newVal.qris_image ? `/storage/${newVal.qris_image}` : null
  } else {
    id.value = null
    name.value = ''
    email.value = ''
    phone.value = ''
    address.value = ''
    parent_id.value = null
    status.value = 'Aktif'
    logo.value = null
    previewLogo.value = null
    qrisImage.value = null
    previewQris.value = null
  }
}, { immediate: true })

watch(logo, newVal => {
  let file = Array.isArray(newVal) ? newVal[0] : newVal
  if (file instanceof File) {
    previewLogo.value = URL.createObjectURL(file)
  } else if (props.selectedOwner && props.selectedOwner.logo) {
    previewLogo.value = `/storage/${props.selectedOwner.logo}`
  } else {
    previewLogo.value = null
  }
})

watch(qrisImage, newVal => {
  let file = Array.isArray(newVal) ? newVal[0] : newVal
  if (file instanceof File) {
    previewQris.value = URL.createObjectURL(file)
  } else if (props.selectedOwner && props.selectedOwner.qris_image) {
    previewQris.value = `/storage/${props.selectedOwner.qris_image}`
  } else {
    previewQris.value = null
  }
})

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
    logo.value = null
    qrisImage.value = null
  })
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      emit('ownerData', {
        id: id.value,
        name: name.value,
        email: email.value,
        phone: phone.value,
        address: address.value,
        parent_id: parent_id.value,
        status: status.value,
        logo: Array.isArray(logo.value) ? logo.value[0] : (logo.value instanceof File ? logo.value : null),
        qris_image: Array.isArray(qrisImage.value) ? qrisImage.value[0] : (qrisImage.value instanceof File ? qrisImage.value : null),
      })
      closeNavigationDrawer()
    }
  })
}
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="400"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="closeNavigationDrawer"
  >
    <!-- 👉 Title -->
    <div class="d-flex align-center pa-6 pb-1">
      <h6 class="text-h6">
        {{ props.selectedOwner ? 'Edit Owner' : 'Tambah Owner Baru' }}
      </h6>

      <VSpacer />

      <IconBtn @click="closeNavigationDrawer">
        <VIcon icon="ri-close-line" />
      </IconBtn>
    </div>

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
                  :rules="[v => !!v || 'Nama Owner wajib diisi']"
                  label="Nama Owner / Perusahaan"
                  placeholder="Misal: PT Maju Mundur"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="email"
                  :rules="[v => !!v || 'Email wajib diisi']"
                  label="Email"
                  type="email"
                  placeholder="johndoe@email.com"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="phone"
                  label="No. Telepon"
                  placeholder="081234567890"
                />
              </VCol>
              
              <VCol cols="12">
                <VTextarea
                  v-model="address"
                  label="Alamat"
                  rows="3"
                />
              </VCol>

              <VCol cols="12">
                <VSelect
                  v-model="parent_id"
                  :items="parentOptions"
                  item-title="name"
                  item-value="id"
                  label="Hierarki (Induk Owner)"
                  hint="Pilih jika ini adalah Sub-Owner/Franchisee"
                  persistent-hint
                />
              </VCol>

              <VCol cols="12">
                <div class="d-flex align-center gap-4">
                  <VAvatar
                    v-if="previewLogo"
                    size="60"
                    variant="tonal"
                    color="primary"
                    class="rounded"
                  >
                    <VImg
                      :src="previewLogo"
                      cover
                    />
                  </VAvatar>
                  <VFileInput
                    v-model="logo"
                    label="Logo Perusahaan"
                    accept="image/png, image/jpeg, image/jpg"
                    prepend-icon="ri-image-add-line"
                    show-size
                    clearable
                    hint="Format JPG/PNG. Maksimal 2MB."
                    persistent-hint
                    class="flex-grow-1"
                  />
                </div>
              </VCol>

              <VCol cols="12">
                <div class="d-flex align-center gap-4">
                  <VAvatar
                    v-if="previewQris"
                    size="60"
                    variant="tonal"
                    color="success"
                    class="rounded"
                  >
                    <VImg
                      :src="previewQris"
                      cover
                    />
                  </VAvatar>
                  <VFileInput
                    v-model="qrisImage"
                    label="Gambar QRIS"
                    accept="image/png, image/jpeg, image/jpg"
                    prepend-icon="ri-qr-code-line"
                    show-size
                    clearable
                    hint="Format JPG/PNG. Maksimal 2MB. Tampil di kasir."
                    persistent-hint
                    class="flex-grow-1"
                  />
                </div>
              </VCol>

              <VCol cols="12">
                <VSelect
                  v-model="status"
                  :items="['Aktif', 'Nonaktif']"
                  label="Status"
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
                  type="reset"
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
