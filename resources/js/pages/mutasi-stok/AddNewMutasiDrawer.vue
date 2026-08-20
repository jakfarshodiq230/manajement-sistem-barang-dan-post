<script setup>
import { ref, nextTick, watch, onMounted } from 'vue'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { useSnackbarStore } from '@/stores/snackbar'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  branches: {
    type: Array,
    default: () => [],
  },
  masterProducts: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'saveData',
])

const isFormValid = ref(false)
const refForm = ref()
const source_branch_id = ref(null)
const destination_branch_id = ref(null)
const notes = ref('')
const snackbar = useSnackbarStore()

// Dynamic Product Search
const productOptions = ref([])
const isSearchingProduct = ref(false)
let searchTimeout = null

const items = ref([
  { product_id: null, qty: 1 },
])

const fetchProducts = async (search = '') => {
  isSearchingProduct.value = true
  try {
    let fetched = []
    if (source_branch_id.value) {
      const params = {
        branch_id: source_branch_id.value,
        itemsPerPage: -1,
      }
      if (search) params.search = search
      const res = await $api('/apps/product-branches', { query: params })
      const list = res.data || res || []
      fetched = list.map(pb => ({
        id: pb.product_id,
        name: pb.product?.name || 'Item',
        sku: pb.product?.sku || '',
        stock: pb.stock ?? 0,
        unit: pb.product?.unit || 'Pcs',
      }))
    } else {
      const params = {
        itemsPerPage: -1,
      }
      if (search) params.search = search
      const res = await $api('/apps/products', { query: params })
      const list = res.data || res || []
      fetched = list.map(p => ({
        id: p.id,
        name: p.name,
        sku: p.sku,
        stock: null,
        unit: p.unit || 'Pcs',
      }))
    }

    // Merge with currently selected products so they don't disappear from dropdown
    const selectedIds = items.value.map(i => i.product_id).filter(Boolean)
    const existingSelected = productOptions.value.filter(p => selectedIds.includes(p.id))

    const map = new Map()
    existingSelected.forEach(p => map.set(p.id, p))
    fetched.forEach(p => map.set(p.id, p))

    productOptions.value = Array.from(map.values())
  } catch (error) {
    console.error('Failed to load products:', error)
  } finally {
    isSearchingProduct.value = false
  }
}

watch(source_branch_id, () => {
  fetchProducts('')
})

const onProductSearchInput = val => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchProducts(val || '')
  }, 350)
}

watch(() => props.isDrawerOpen, isOpen => {
  if (isOpen) {
    if (productOptions.value.length === 0) {
      fetchProducts('')
    }
  }
})

onMounted(() => {
  if (props.masterProducts && props.masterProducts.length > 0) {
    productOptions.value = [...props.masterProducts]
  } else {
    fetchProducts('')
  }
})

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
    items.value = [{ product_id: null, qty: 1 }]
  })
}

const addItem = () => {
  items.value.push({ product_id: null, qty: 1 })
}

const removeItem = index => {
  if (items.value.length > 1) {
    items.value.splice(index, 1)
  }
}

const isSubmitting = ref(false)

const onSubmit = async () => {
  const result = await refForm.value?.validate()
  if (result?.valid) {
    if (source_branch_id.value === destination_branch_id.value) {
      snackbar.show('Cabang asal dan cabang tujuan tidak boleh sama!', 'warning')
      return
    }

    if (items.value.some(i => !i.product_id || i.qty < 1)) {
      snackbar.show('Mohon lengkapi semua baris barang (Produk dan Qty harus minimal 1).', 'warning')
      return
    }

    isSubmitting.value = true
    try {
      const res = await $api('/apps/stock-transfers', {
        method: 'POST',
        body: {
          source_branch_id: source_branch_id.value,
          destination_branch_id: destination_branch_id.value,
          notes: notes.value,
          items: items.value,
        },
      })
      snackbar.show(res.message || 'Pengajuan mutasi berhasil dibuat', 'success')
      emit('saveData')
      closeNavigationDrawer()
    } catch (error) {
      console.error(error)
      const errorMsg = error.response?._data?.error || error.response?._data?.message || 'Gagal membuat mutasi'
      snackbar.show(errorMsg, 'error')
    } finally {
      isSubmitting.value = false
    }
  }
}

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
}
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="720"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <AppDrawerHeaderSection
      title="Buat Pengajuan Mutasi Stok"
      @cancel="closeNavigationDrawer"
    />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <div class="mb-4 pa-3 bg-primary-lighten-5 rounded border border-primary">
            <p class="text-caption text-primary mb-0 font-weight-medium">
              <VIcon icon="ri-information-line" size="14" class="me-1" />
              Permintaan barang dapat diajukan ke Gudang Pusat ataupun antar Cabang. Cari barang dengan mengetik Nama atau SKU pada kolom pencarian.
            </p>
          </div>

          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <VCol cols="12" md="6">
                <VAutocomplete
                  v-model="source_branch_id"
                  :rules="[v => !!v || 'Cabang / Unit asal wajib dipilih']"
                  :items="props.branches"
                  item-title="name"
                  item-value="id"
                  label="Cabang / Gudang Asal (Sumber Barang)"
                  placeholder="Pilih Pusat atau Cabang Asal"
                  prepend-inner-icon="ri-store-2-line"
                />
              </VCol>

              <VCol cols="12" md="6">
                <VAutocomplete
                  v-model="destination_branch_id"
                  :rules="[v => !!v || 'Cabang pemohon wajib dipilih']"
                  :items="props.branches"
                  item-title="name"
                  item-value="id"
                  label="Cabang Pemohon (Tujuan Penerimaan)"
                  placeholder="Pilih Cabang Pemohon"
                  prepend-inner-icon="ri-store-3-line"
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="notes"
                  label="Catatan / Instruksi Permintaan"
                  rows="2"
                  placeholder="Contoh: Permintaan restock darurat, dijemput hari Kamis siang..."
                  prepend-inner-icon="ri-file-text-line"
                />
              </VCol>

              <VCol cols="12">
                <VDivider class="my-2" />
                <div class="d-flex justify-space-between align-center my-3">
                  <div>
                    <h6 class="text-subtitle-1 font-weight-bold d-flex align-center gap-1">
                      <VIcon icon="ri-box-3-line" size="18" color="primary" />
                      Daftar Barang yang Diminta
                    </h6>
                    <span class="text-caption text-medium-emphasis">
                      Ketik Nama atau SKU untuk mencari dari seluruh data barang
                    </span>
                  </div>
                  <VBtn
                    size="small"
                    variant="tonal"
                    prepend-icon="ri-add-line"
                    @click="addItem"
                  >
                    Tambah Baris
                  </VBtn>
                </div>

                <div
                  v-for="(item, index) in items"
                  :key="index"
                  class="d-flex align-center gap-3 mb-3 pa-2 rounded bg-grey-50 border"
                >
                  <div class="flex-grow-1">
                    <VAutocomplete
                      v-model="item.product_id"
                      :items="productOptions"
                      :item-title="prod => prod.sku ? `[${prod.sku}] ${prod.name}${prod.stock !== null ? ` (Stok Cabang Asal: ${prod.stock} ${prod.unit || ''})` : ''}` : prod.name"
                      item-value="id"
                      label="Cari Produk (Nama / SKU)"
                      placeholder="Ketik untuk mencari barang..."
                      density="compact"
                      clearable
                      :loading="isSearchingProduct"
                      :rules="[v => !!v || 'Pilih produk']"
                      @update:search="onProductSearchInput"
                    >
                      <template #no-data>
                        <div class="pa-2 text-caption text-medium-emphasis">
                          {{ isSearchingProduct ? 'Mencari data barang...' : 'Ketik nama barang / SKU untuk mencari...' }}
                        </div>
                      </template>
                    </VAutocomplete>
                  </div>
                  <div style="width: 130px;">
                    <VTextField
                      v-model.number="item.qty"
                      type="number"
                      label="Qty Diminta"
                      density="compact"
                      min="1"
                      :rules="[v => v > 0 || 'Min. 1']"
                    />
                  </div>
                  <div>
                    <IconBtn
                      size="small"
                      color="error"
                      variant="text"
                      :disabled="items.length === 1"
                      @click="removeItem(index)"
                    >
                      <VIcon icon="ri-delete-bin-line" />
                    </IconBtn>
                  </div>
                </div>
              </VCol>

              <VCol cols="12" class="mt-4 d-flex gap-2">
                <VBtn
                  type="submit"
                  color="primary"
                  prepend-icon="ri-send-plane-line"
                  :loading="isSubmitting"
                >
                  Kirim Pengajuan Mutasi
                </VBtn>
                <VBtn
                  type="reset"
                  variant="outlined"
                  color="secondary"
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
