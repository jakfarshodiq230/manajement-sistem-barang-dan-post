<script setup>
import { ref, watch, nextTick, onMounted } from 'vue'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  selectedPo: {
    type: Object,
    default: null,
  },
  branches: {
    type: Array,
    default: () => [],
  },
  suppliers: {
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
const branch_id = ref(null)
const supplier_id = ref(null)
const date = ref(new Date().toISOString().substr(0, 10))
const notes = ref('')

// Live product search
const productOptions = ref([])
const isSearchingProduct = ref(false)
let searchTimeout = null

const items = ref([
  { product_id: null, qty: 1, unit_cost: 0 },
])

const extractArray = val => {
  if (Array.isArray(val)) return val
  if (val && Array.isArray(val.data)) return val.data
  if (val && Array.isArray(val.products)) return val.products
  return []
}

const formatProductTitle = prod => {
  if (!prod) return ''
  if (typeof prod === 'string') return prod
  if (typeof prod !== 'object') return String(prod)
  return prod.sku ? `[${prod.sku}] ${prod.name}` : (prod.name || 'Barang')
}

const fetchProducts = async (search = '') => {
  isSearchingProduct.value = true
  try {
    const params = {
      itemsPerPage: -1,
    }
    if (search) {
      params.search = search
    }

    const res = await $api('/apps/products', { params })
    const fetched = extractArray(res)

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

watch(() => props.selectedPo, newVal => {
  if (newVal) {
    branch_id.value = newVal.branch_id
    supplier_id.value = newVal.supplier_id
    date.value = newVal.date || newVal.created_at?.substr(0, 10)
    notes.value = newVal.notes || ''
    
    if (newVal.items && newVal.items.length) {
      items.value = newVal.items.map(item => {
        if (item.product) {
          productOptions.value.push(item.product)
        }
        return {
          product_id: item.product_id,
          qty: item.qty,
          unit_cost: item.unit_cost,
        }
      })
    }
  } else {
    branch_id.value = null
    supplier_id.value = null
    date.value = new Date().toISOString().substr(0, 10)
    notes.value = ''
    items.value = [{ product_id: null, qty: 1, unit_cost: 0 }]
  }
}, { immediate: true })

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
    items.value = [{ product_id: null, qty: 1, unit_cost: 0 }]
  })
}

const addItem = () => {
  items.value.push({ product_id: null, qty: 1, unit_cost: 0 })
}

const removeItem = index => {
  if (items.value.length > 1) {
    items.value.splice(index, 1)
  }
}

const onProductSelected = (productId, itemIndex) => {
  const prod = productOptions.value.find(p => p.id === productId)
  if (prod && prod.cost_price && items.value[itemIndex]) {
    if (items.value[itemIndex].unit_cost === 0) {
      items.value[itemIndex].unit_cost = prod.cost_price
    }
  }
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      // Validate items
      if (items.value.some(i => !i.product_id || i.qty < 1 || i.unit_cost < 0)) {
        alert('Mohon lengkapi semua baris barang dengan benar (Produk, Qty > 0, Harga Modal >= 0).')
        return
      }

      emit('saveData', {
        id: props.selectedPo?.id,
        branch_id: branch_id.value,
        supplier_id: supplier_id.value,
        date: date.value,
        notes: notes.value,
        items: items.value,
      })
      closeNavigationDrawer()
    }
  })
}

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
}

const formatNumber = value => {
  if (!value) return ''
  const number = value.toString().replace(/\D/g, '')
  return new Intl.NumberFormat('id-ID').format(number)
}

const parseNumber = value => {
  if (!value) return 0
  const number = value.toString().replace(/\D/g, '')
  return number ? parseInt(number, 10) : 0
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
      :title="props.selectedPo ? 'Edit Purchase Order' : 'Buat Purchase Order Baru'"
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
              <VCol cols="6">
                <VAutocomplete
                  v-model="branch_id"
                  :rules="[v => !!v || 'Cabang wajib dipilih']"
                  :items="props.branches"
                  item-title="name"
                  item-value="id"
                  label="Pilih Cabang (Tujuan Barang)"
                  placeholder="Pilih Cabang"
                  prepend-inner-icon="ri-store-2-line"
                />
              </VCol>

              <VCol cols="6">
                <VAutocomplete
                  v-model="supplier_id"
                  :rules="[v => !!v || 'Supplier wajib dipilih']"
                  :items="props.suppliers"
                  item-title="name"
                  item-value="id"
                  label="Pilih Supplier"
                  placeholder="Pilih Supplier"
                  prepend-inner-icon="ri-truck-line"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="date"
                  type="date"
                  :rules="[v => !!v || 'Tanggal wajib diisi']"
                  label="Tanggal PO"
                  prepend-inner-icon="ri-calendar-line"
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="notes"
                  label="Catatan PO"
                  rows="2"
                  placeholder="Instruksi pengiriman, terms pembayaran, dll."
                  prepend-inner-icon="ri-file-text-line"
                />
              </VCol>

              <VCol cols="12">
                <VDivider class="my-3" />
                <div class="d-flex justify-space-between align-center mb-3">
                  <div>
                    <h6 class="text-subtitle-1 font-weight-bold d-flex align-center gap-1">
                      <VIcon icon="ri-box-3-line" size="18" color="primary" />
                      Daftar Barang (Item PO)
                    </h6>
                    <span class="text-caption text-medium-emphasis">
                      Ketik Nama atau SKU untuk mencari barang dari seluruh katalog
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
                      :item-title="formatProductTitle"
                      item-value="id"
                      label="Cari Produk (Nama / SKU)"
                      placeholder="Ketik nama atau SKU barang..."
                      density="compact"
                      clearable
                      :loading="isSearchingProduct"
                      :rules="[v => !!v || 'Pilih produk']"
                      @update:search="onProductSearchInput"
                      @update:model-value="val => onProductSelected(val, index)"
                    >
                      <template #no-data>
                        <div class="pa-2 text-caption text-medium-emphasis">
                          {{ isSearchingProduct ? 'Mencari produk...' : 'Ketik nama atau SKU untuk mencari barang...' }}
                        </div>
                      </template>
                    </VAutocomplete>
                  </div>
                  <div style="width: 100px;">
                    <VTextField
                      v-model.number="item.qty"
                      type="number"
                      label="Qty"
                      density="compact"
                      min="1"
                      :rules="[v => v > 0 || 'Min. 1']"
                    />
                  </div>
                  <div style="width: 180px;">
                    <VTextField
                      :model-value="formatNumber(item.unit_cost)"
                      prefix="Rp"
                      label="Harga Modal Satuan"
                      density="compact"
                      @update:model-value="val => item.unit_cost = parseNumber(val)"
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
                >
                  {{ props.selectedPo ? 'Simpan Perubahan' : 'Buat Purchase Order' }}
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
