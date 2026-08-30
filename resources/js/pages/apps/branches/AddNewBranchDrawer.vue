<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { VForm } from 'vuetify/components/VForm'
import { nextTick, ref, watch } from 'vue'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  selectedBranch: {
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
  'branchData',
])

const isFormValid = ref(false)
const refForm = ref()

const id = ref(null)
const name = ref('')
const type = ref('store')
const email = ref('')
const phone = ref('')
const address = ref('')
const owner_id = ref(null)
const status = ref('Aktif')
const logo = ref(null)
const previewLogo = ref(null)

watch(() => props.selectedBranch, newVal => {
  if (newVal) {
    id.value = newVal.id
    name.value = newVal.name
    type.value = newVal.type || 'store'
    email.value = newVal.email || ''
    phone.value = newVal.phone || ''
    address.value = newVal.address || ''
    owner_id.value = newVal.owner_id || null
    status.value = newVal.status || 'Aktif'
    logo.value = null
    previewLogo.value = newVal.logo ? `/storage/${newVal.logo}` : null
  } else {
    id.value = null
    name.value = ''
    type.value = 'store'
    email.value = ''
    phone.value = ''
    address.value = ''
    owner_id.value = null
    status.value = 'Aktif'
    logo.value = null
    previewLogo.value = null
  }
}, { immediate: true })

watch(logo, newVal => {
  if (newVal && newVal.length > 0) {
    previewLogo.value = URL.createObjectURL(newVal[0])
  } else if (props.selectedBranch && props.selectedBranch.logo) {
    previewLogo.value = `/storage/${props.selectedBranch.logo}`
  } else {
    previewLogo.value = null
  }
})

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.resetValidation()
    logo.value = null
  })
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      emit('branchData', {
        id: id.value,
        name: name.value,
        type: type.value,
        email: email.value,
        phone: phone.value,
        address: address.value,
        owner_id: owner_id.value,
        status: status.value,
        logo: logo.value ? logo.value[0] : null,
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
    <div class="d-flex align-center pa-6 pb-1">
      <h6 class="text-h6">
        {{ props.selectedBranch ? 'Edit Cabang' : 'Tambah Cabang Baru' }}
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
                  :rules="[v => !!v || 'Nama Cabang wajib diisi']"
                  label="Nama Cabang"
                  placeholder="Misal: Cabang Jakarta Pusat"
                />
              </VCol>

              <VCol cols="12">
                <VSelect
                  v-model="type"
                  :items="[{ title: 'Toko (Store)', value: 'store' }, { title: 'Gudang (Warehouse)', value: 'warehouse' }]"
                  label="Tipe Cabang"
                  hint="Pilih apakah ini Toko atau Gudang penyimpanan"
                  persistent-hint
                />
              </VCol>
              
              <VCol cols="12">
                <VTextField
                  v-model="email"
                  :rules="[v => !!v || 'Email wajib diisi']"
                  label="Email"
                  type="email"
                  placeholder="cabang@email.com"
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
                  label="Alamat Lengkap"
                  rows="3"
                />
              </VCol>

              <VCol cols="12">
                <VSelect
                  v-model="owner_id"
                  :items="props.ownersList"
                  item-title="name"
                  item-value="id"
                  label="Owner / Pemilik"
                  placeholder="Pilih pemilik cabang"
                  hint="Pilih Owner atau Sub-Owner yang memegang cabang ini"
                  persistent-hint
                />
              </VCol>

              <VCol cols="12">
                <div class="d-flex align-center gap-4">
                  <VAvatar
                    v-if="previewLogo"
                    size="60"
                    variant="tonal"
                    color="info"
                    class="rounded"
                  >
                    <VImg
                      :src="previewLogo"
                      cover
                    />
                  </VAvatar>
                  <VFileInput
                    v-model="logo"
                    label="Logo Cabang"
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
                <VSelect
                  v-model="status"
                  :items="['Aktif', 'Tutup']"
                  label="Status Cabang"
                  hint="Ubah ke 'Tutup' alih-alih menghapus data"
                  persistent-hint
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
