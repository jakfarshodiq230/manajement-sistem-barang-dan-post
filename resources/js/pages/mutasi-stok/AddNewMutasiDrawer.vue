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
  'update:is-drawer-open',
  'close',
  'cancel',
  'saveData',
])

const isFormValid = ref(false)
const refForm = ref()
const source_branch_id = ref(null)
const destination_branch_id = ref(null)
const notes = ref('')
const snackbar = useSnackbarStore()
const isSubmitting = ref(false)

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  emit('update:is-drawer-open', false)
  emit('close')
  emit('cancel')
  nextTick(() => {
    refForm.value?.resetValidation()
    source_branch_id.value = null
    destination_branch_id.value = null
    notes.value = ''
    items.value = [{ product_id: null, qty: 1 }]
  })
}

// Dynamic Product Search
const productOptions = ref([])
const isSearchingProduct = ref(false)
let searchTimeout = null

const items = ref([
  { product_id: null, qty: 1 },
])

const extractArray = val => {
  if (Array.isArray(val)) return val
  if (val && Array.isArray(val.data)) return val.data
  if (val && Array.isArray(val.products)) return val.products
  if (val && Array.isArray(val.productBranches)) return val.productBranches
  return []
}

const formatProductTitle = prod => {
  if (!prod) return ''
  if (typeof prod === 'string') return prod
  if (typeof prod !== 'object') return String(prod)
  
  const skuPart = prod.sku ? `[${prod.sku}] ` : ''
  const namePart = prod.name || 'Barang'
  const stockPart = prod.stock !== undefined && prod.stock !== null ? ` (Stok: ${prod.stock} ${prod.unit || ''})` : ''
  
  return `${skuPart}${namePart}${stockPart}`
}

const fetchProducts = async (searchQuery = '') => {
  isSearchingProduct.value = true
  try {
    // 1. Fetch master products
    const pParams = { itemsPerPage: -1 }
    if (searchQuery) pParams.search = searchQuery
    const pRes = await $api('/apps/products', { params: pParams })
    const masterList = extractArray(pRes)

    // 2. If source branch is selected, fetch branch stocks to display stock
    let branchStockMap = new Map()
    if (source_branch_id.value) {
      try {
        const pbRes = await $api('/apps/product-branches', {
          params: { branch_id: source_branch_id.value, itemsPerPage: -1 }
        })
        const pbList = extractArray(pbRes)
        pbList.forEach(pb => {
          if (pb.product_id) {
            branchStockMap.set(pb.product_id, {
              stock: pb.stock ?? 0,
              unit: pb.product?.unit || 'Pcs',
            })
          }
        })
      } catch (e) {
        console.warn('Could not load branch stock:', e)
      }
    }

    const fetched = masterList.map(p => {
      const branchInfo = branchStockMap.get(p.id)
      return {
        id: p.id,
        name: p.name || 'Barang',
        sku: p.sku || '',
        stock: branchInfo ? branchInfo.stock : (source_branch_id.value ? 0 : null),
        unit: p.unit || 'Pcs',
      }
    })

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


const addItem = () => {
  items.value.push({ product_id: null, qty: 1 })
}

const removeItem = index => {
  if (items.value.length > 1) {
    items.value.splice(index, 1)
  }
}

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
      window.dispatchEvent(new Event('refresh-notifications'))
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
    :width="$vuetify.display.xs ? '100%' : ($vuetify.display.smAndDown ? '92vw' : 750)"
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
          <VIcon icon="ri-arrow-left-right-line" size="24" />
        </VAvatar>
        <div>
          <h5 class="text-h6 font-weight-bold mb-0">
            Pengajuan Mutasi Antar Cabang
          </h5>
          <span class="text-caption text-medium-emphasis">
            Transfer stok barang antar outlet & gudang pusat
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
          <!-- Section 1: Rute Mutasi -->
          <div class="mb-5">
            <div class="d-flex align-center gap-2 mb-3">
              <VIcon icon="ri-route-line" color="primary" size="18" />
              <span class="text-subtitle-2 font-weight-bold text-uppercase letter-spacing-1 text-primary">
                1. Rute Asal & Tujuan Mutasi
              </span>
            </div>

            <VRow dense>
              <VCol cols="12" md="6">
                <VAutocomplete
                  v-model="source_branch_id"
                  :rules="[v => !!v || 'Cabang sumber asal wajib dipilih']"
                  :items="props.branches"
                  item-title="name"
                  item-value="id"
                  label="Cabang / Gudang Asal (Sumber)"
                  placeholder="Pilih Gudang Pusat / Cabang"
                  density="comfortable"
                  variant="outlined"
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
                  label="Cabang Tujuan (Pemohon)"
                  placeholder="Pilih Cabang Penerima"
                  density="comfortable"
                  variant="outlined"
                  prepend-inner-icon="ri-store-3-line"
                />
              </VCol>

              <VCol cols="12" class="mt-2">
                <VTextarea
                  v-model="notes"
                  label="Catatan / Instruksi Permintaan Barang"
                  rows="2"
                  placeholder="Contoh: Permintaan restok darurat karena stok menipis..."
                  density="comfortable"
                  variant="outlined"
                  prepend-inner-icon="ri-file-text-line"
                />
              </VCol>
            </VRow>
          </div>

          <VDivider class="my-5" />

          <!-- Section 2: Daftar Barang -->
          <div class="mb-6">
            <div class="d-flex justify-space-between align-center mb-3">
              <div class="d-flex align-center gap-2">
                <VIcon icon="ri-box-3-line" color="success" size="18" />
                <span class="text-subtitle-2 font-weight-bold text-uppercase letter-spacing-1 text-success">
                  2. Rincian Barang yang Diminta
                </span>
              </div>
              <VBtn
                size="small"
                variant="tonal"
                color="primary"
                prepend-icon="ri-add-line"
                class="rounded-lg"
                @click="addItem"
              >
                Tambah Baris Barang
              </VBtn>
            </div>

            <div
              v-for="(item, index) in items"
              :key="index"
              class="d-flex align-center gap-3 mb-3 pa-3 rounded-xl border bg-var-theme-surface shadow-xs"
            >
              <div class="flex-grow-1">
                <VAutocomplete
                  v-model="item.product_id"
                  :items="productOptions"
                  :item-title="formatProductTitle"
                  item-value="id"
                  label="Pilih Produk (Ketik Nama / SKU)"
                  placeholder="Ketik untuk mencari..."
                  density="comfortable"
                  variant="outlined"
                  clearable
                  hide-details
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
              <div style="width: 140px;">
                <VTextField
                  v-model.number="item.qty"
                  type="number"
                  label="Qty Diminta"
                  density="comfortable"
                  variant="outlined"
                  min="1"
                  hide-details
                  :rules="[v => v > 0 || 'Min. 1']"
                />
              </div>
              <div>
                <VBtn
                  icon="ri-delete-bin-line"
                  variant="text"
                  color="error"
                  size="small"
                  :disabled="items.length === 1"
                  @click="removeItem(index)"
                />
              </div>
            </div>
          </div>

          <!-- Sticky Action Bar -->
          <div class="d-flex align-center gap-3 pt-2">
            <VBtn
              type="submit"
              color="primary"
              size="large"
              prepend-icon="ri-send-plane-2-line"
              :loading="isSubmitting"
              class="font-weight-bold flex-grow-1 rounded-lg shadow-sm"
            >
              Kirim Pengajuan Mutasi
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
