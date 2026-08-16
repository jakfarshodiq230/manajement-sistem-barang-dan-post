<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { VForm } from 'vuetify/components/VForm'
import { nextTick, ref, watch } from 'vue'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  selectedProduct: {
    type: Object,
    default: null,
  },
  categoriesList: {
    type: Array,
    default: () => [],
  },
  branchesList: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'productData',
])

const isFormValid = ref(false)
const refForm = ref()

const id = ref(null)
const name = ref('')
const sku = ref('')
const category_id = ref(null)
const description = ref('')
const status = ref('Aktif')
const stock_method = ref('fifo')
const brand = ref('')
const barcode = ref('')
const unit = ref('Pcs')
const weight = ref(null)
const length = ref(null)
const width = ref(null)
const height = ref(null)
const is_returnable = ref(true)
const tax_type = ref(null)
const image = ref(null)
const previewImage = ref(null)

watch(() => props.selectedProduct, newVal => {
  if (newVal) {
    id.value = newVal.id
    name.value = newVal.name
    sku.value = newVal.sku
    category_id.value = newVal.category_id
    description.value = newVal.description || ''
    status.value = newVal.status || 'Aktif'
    stock_method.value = newVal.stock_method || 'fifo'
    brand.value = newVal.brand || ''
    barcode.value = newVal.barcode || ''
    unit.value = newVal.unit || 'Pcs'
    weight.value = newVal.weight || null
    length.value = newVal.length || null
    width.value = newVal.width || null
    height.value = newVal.height || null
    is_returnable.value = newVal.is_returnable ?? true
    tax_type.value = newVal.tax_type || null
    image.value = null
    previewImage.value = newVal.image ? `/storage/${newVal.image}` : null
  } else {
    id.value = null
    name.value = ''
    sku.value = ''
    category_id.value = null
    description.value = ''
    status.value = 'Aktif'
    stock_method.value = 'fifo'
    brand.value = ''
    barcode.value = ''
    unit.value = 'Pcs'
    weight.value = null
    length.value = null
    width.value = null
    height.value = null
    is_returnable.value = true
    tax_type.value = null
    image.value = null
    previewImage.value = null
  }
}, { immediate: true })

watch(image, newVal => {
  if (newVal && newVal.length > 0) {
    previewImage.value = URL.createObjectURL(newVal[0])
  } else if (props.selectedProduct && props.selectedProduct.image) {
    previewImage.value = `/storage/${props.selectedProduct.image}`
  } else {
    previewImage.value = null
  }
})

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
    image.value = null
  })
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      emit('productData', {
        id: id.value,
        name: name.value,
        sku: sku.value,
        category_id: category_id.value,
        description: description.value,
        status: status.value,
        stock_method: stock_method.value,
        brand: brand.value,
        barcode: barcode.value,
        unit: unit.value,
        weight: weight.value,
        length: length.value,
        width: width.value,
        height: height.value,
        is_returnable: is_returnable.value,
        tax_type: tax_type.value,
        image: image.value ? image.value[0] : null,
      })
      closeNavigationDrawer()
    }
  })
}
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="600"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="val => emit('update:isDrawerOpen', val)"
  >
    <div class="d-flex align-center pa-6 pb-1">
      <h6 class="text-h6">
        {{ id ? 'Edit Produk' : 'Tambah Produk' }}
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
                <div class="d-flex align-center gap-4">
                  <VAvatar
                    v-if="previewImage"
                    size="60"
                    variant="tonal"
                    color="primary"
                    class="rounded"
                  >
                    <VImg
                      :src="previewImage"
                      cover
                    />
                  </VAvatar>
                  <VFileInput
                    v-model="image"
                    label="Foto Produk"
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
                <VAutocomplete
                  v-model="category_id"
                  :items="categoriesList"
                  item-title="name"
                  item-value="id"
                  label="Kategori Produk"
                  placeholder="Pilih Kategori"
                  clearable
                />
              </VCol>

              <VCol cols="12" md="6">
                <VTextField
                  v-model="name"
                  :rules="[v => !!v || 'Nama Produk wajib diisi']"
                  label="Nama Produk"
                  placeholder="Misal: iPhone 14 Pro"
                />
              </VCol>

              <VCol cols="12" md="6">
                <VTextField
                  v-model="brand"
                  label="Merek"
                  placeholder="Misal: Apple"
                />
              </VCol>

              <VCol cols="12" md="6">
                <VTextField
                  v-model="sku"
                  :rules="[v => !!v || 'SKU wajib diisi']"
                  label="Kode SKU (Internal)"
                  placeholder="IP-14PRO-128"
                />
              </VCol>

              <VCol cols="12" md="6">
                <VTextField
                  v-model="barcode"
                  label="Barcode (Pabrik)"
                  placeholder="EAN/UPC Code"
                />
              </VCol>

              <VCol cols="12" md="6">
                <VCombobox
                  v-model="unit"
                  :items="['Pcs', 'Unit', 'Lembar', 'Kg', 'Gram', 'Liter', 'Ml', 'Karton', 'Box', 'Pak', 'Set', 'Lusin', 'Kodi']"
                  label="Satuan Dasar"
                  placeholder="Pilih atau ketik satuan baru"
                  clearable
                />
              </VCol>

              <VCol cols="12" md="6">
                <VSelect
                  v-model="tax_type"
                  :items="['Include PPN', 'Exclude PPN', 'Non-Tax']"
                  label="Status Pajak"
                  clearable
                />
              </VCol>

              <VCol cols="12">
                <VDivider class="my-2" />
                <h6 class="text-subtitle-1 font-weight-medium">Dimensi & Logistik</h6>
              </VCol>

              <VCol cols="12" md="3">
                <VTextField
                  v-model="weight"
                  type="number"
                  label="Berat (g)"
                />
              </VCol>
              <VCol cols="12" md="3">
                <VTextField
                  v-model="length"
                  type="number"
                  label="Panjang (cm)"
                />
              </VCol>
              <VCol cols="12" md="3">
                <VTextField
                  v-model="width"
                  type="number"
                  label="Lebar (cm)"
                />
              </VCol>
              <VCol cols="12" md="3">
                <VTextField
                  v-model="height"
                  type="number"
                  label="Tinggi (cm)"
                />
              </VCol>

              <VCol cols="12">
                <VDivider class="my-2" />
                <h6 class="text-subtitle-1 font-weight-medium">Pengaturan Lanjutan</h6>
              </VCol>

              <VCol cols="12" md="6">
                <VSwitch
                  v-model="is_returnable"
                  label="Barang bisa diretur?"
                  color="primary"
                />
              </VCol>

              <!-- Removed branch specifics -->
              
              <VCol cols="12">
                <VSelect
                  v-model="stock_method"
                  :items="[
                    { title: 'FIFO (First In First Out)', value: 'fifo' },
                    { title: 'LIFO (Last In First Out)', value: 'lifo' },
                    { title: 'FEFO (First Expired First Out)', value: 'fefo' }
                  ]"
                  item-title="title"
                  item-value="value"
                  label="Metode Pemotongan Stok"
                  hint="Pilih bagaimana urutan stok akan dipotong saat terjadi penjualan."
                  persistent-hint
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="description"
                  label="Deskripsi Produk"
                  rows="3"
                />
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
