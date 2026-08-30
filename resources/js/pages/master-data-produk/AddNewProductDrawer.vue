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
    :width="$vuetify.display.xs ? '100%' : ($vuetify.display.smAndDown ? '92vw' : 680)"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="val => emit('update:isDrawerOpen', val)"
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
            :icon="id ? 'ri-edit-box-line' : 'ri-box-3-line'"
            size="24"
          />
        </VAvatar>
        <div>
          <h5 class="text-h6 font-weight-bold mb-0">
            {{ id ? 'Edit Master Produk' : 'Tambah Master Produk Baru' }}
          </h5>
          <span class="text-caption text-medium-emphasis">
            Katalog data induk barang & spesifikasi umum
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
          <!-- Section 1: Identitas Produk -->
          <div class="mb-5">
            <div class="d-flex align-center gap-2 mb-3">
              <VIcon icon="ri-box-line" color="primary" size="18" />
              <span class="text-subtitle-2 font-weight-bold text-uppercase letter-spacing-1 text-primary">
                1. Identitas & Media Produk
              </span>
            </div>

            <VRow dense>
              <VCol cols="12" class="mb-2">
                <div class="d-flex align-center gap-4 pa-3 border rounded-xl bg-var-theme-surface">
                  <VAvatar
                    v-if="previewImage"
                    size="64"
                    variant="tonal"
                    color="primary"
                    rounded="lg"
                  >
                    <VImg
                      :src="previewImage"
                      cover
                    />
                  </VAvatar>
                  <VAvatar
                    v-else
                    size="64"
                    color="secondary"
                    variant="tonal"
                    rounded="lg"
                  >
                    <VIcon icon="ri-image-line" size="28" />
                  </VAvatar>
                  <VFileInput
                    v-model="image"
                    label="Unggah Foto Produk"
                    accept="image/png, image/jpeg, image/jpg"
                    prepend-icon=""
                    prepend-inner-icon="ri-image-add-line"
                    show-size
                    density="comfortable"
                    variant="outlined"
                    clearable
                    hint="Format JPG/PNG. Maks. 2MB."
                    persistent-hint
                    class="flex-grow-1"
                  />
                </div>
              </VCol>

              <VCol cols="12" md="8">
                <VTextField
                  v-model="name"
                  :rules="[v => !!v || 'Nama Produk wajib diisi']"
                  label="Nama Master Produk"
                  placeholder="Misal: Aki GS Astra Hybrid NS60"
                  density="comfortable"
                  variant="outlined"
                  prepend-inner-icon="ri-box-3-line"
                />
              </VCol>

              <VCol cols="12" md="4">
                <VAutocomplete
                  v-model="category_id"
                  :items="categoriesList"
                  item-title="name"
                  item-value="id"
                  label="Kategori"
                  placeholder="Pilih Kategori"
                  density="comfortable"
                  variant="outlined"
                  clearable
                />
              </VCol>

              <VCol cols="12" md="6">
                <VTextField
                  v-model="sku"
                  :rules="[v => !!v || 'Kode SKU wajib diisi']"
                  label="Kode SKU (Internal)"
                  placeholder="Misal: AKI-GS-NS60"
                  density="comfortable"
                  variant="outlined"
                  prepend-inner-icon="ri-qr-code-line"
                />
              </VCol>

              <VCol cols="12" md="6">
                <VTextField
                  v-model="barcode"
                  label="Barcode Pabrik (EAN/UPC)"
                  placeholder="Misal: 8991234567890"
                  density="comfortable"
                  variant="outlined"
                  prepend-inner-icon="ri-barcode-line"
                />
              </VCol>

              <VCol cols="12" md="6">
                <VTextField
                  v-model="brand"
                  label="Merek / Brand"
                  placeholder="Misal: GS Astra"
                  density="comfortable"
                  variant="outlined"
                  prepend-inner-icon="ri-building-line"
                />
              </VCol>

              <VCol cols="12" md="6">
                <VCombobox
                  v-model="unit"
                  :items="['Pcs', 'Unit', 'Lembar', 'Kg', 'Gram', 'Liter', 'Ml', 'Karton', 'Box', 'Pak', 'Set', 'Lusin', 'Kodi']"
                  label="Satuan Dasar"
                  placeholder="Pilih atau ketik satuan"
                  density="comfortable"
                  variant="outlined"
                  clearable
                />
              </VCol>
            </VRow>
          </div>

          <VDivider class="my-5" />

          <!-- Section 2: Dimensi & Logistik -->
          <div class="mb-5">
            <div class="d-flex align-center gap-2 mb-3">
              <VIcon icon="ri-truck-line" color="info" size="18" />
              <span class="text-subtitle-2 font-weight-bold text-uppercase letter-spacing-1 text-info">
                2. Spesifikasi Fisik & Dimensi
              </span>
            </div>

            <VRow dense>
              <VCol cols="6" sm="3">
                <VTextField
                  v-model="weight"
                  type="number"
                  label="Berat"
                  suffix="g"
                  placeholder="0"
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>
              <VCol cols="6" sm="3">
                <VTextField
                  v-model="length"
                  type="number"
                  label="Panjang"
                  suffix="cm"
                  placeholder="0"
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>
              <VCol cols="6" sm="3">
                <VTextField
                  v-model="width"
                  type="number"
                  label="Lebar"
                  suffix="cm"
                  placeholder="0"
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>
              <VCol cols="6" sm="3">
                <VTextField
                  v-model="height"
                  type="number"
                  label="Tinggi"
                  suffix="cm"
                  placeholder="0"
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>
            </VRow>
          </div>

          <VDivider class="my-5" />

          <!-- Section 3: Inventori & Pengaturan Sistem -->
          <div class="mb-6">
            <div class="d-flex align-center gap-2 mb-3">
              <VIcon icon="ri-settings-4-line" color="success" size="18" />
              <span class="text-subtitle-2 font-weight-bold text-uppercase letter-spacing-1 text-success">
                3. Kebijakan Inventori & Sistem
              </span>
            </div>

            <VRow dense>
              <VCol cols="12" md="6">
                <VSelect
                  v-model="stock_method"
                  :items="[
                    { title: 'FIFO (First In First Out)', value: 'fifo' },
                    { title: 'FEFO (First Expired First Out)', value: 'fefo' },
                    { title: 'LIFO (Last In First Out)', value: 'lifo' }
                  ]"
                  item-title="title"
                  item-value="value"
                  label="Metode Pemotongan Stok"
                  density="comfortable"
                  variant="outlined"
                  hint="Urutan batch yang akan otomatis terpotong saat transaksi POS"
                  persistent-hint
                />
              </VCol>

              <VCol cols="12" md="6">
                <VSelect
                  v-model="status"
                  :items="['Aktif', 'Nonaktif']"
                  label="Status Publikasi"
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>

              <VCol cols="12" class="mt-2">
                <VTextarea
                  v-model="description"
                  label="Deskripsi & Spesifikasi Produk"
                  placeholder="Tuliskan keterangan detail mengenai produk ini..."
                  rows="3"
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>

              <VCol cols="12">
                <VSwitch
                  v-model="is_returnable"
                  label="Produk ini mengizinkan retur klaim / garansi pembeli"
                  color="primary"
                  inset
                  class="mt-1"
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
              {{ id ? 'Simpan Perubahan Master Produk' : 'Daftarkan Master Produk' }}
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
