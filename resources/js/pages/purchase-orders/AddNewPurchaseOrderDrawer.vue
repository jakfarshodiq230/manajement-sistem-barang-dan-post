<script setup>
import { ref, watch, nextTick, onMounted, computed } from 'vue'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { useSnackbarStore } from '@/stores/snackbar'

const snackbar = useSnackbarStore()

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
  'update:is-drawer-open',
  'close',
  'cancel',
  'saveData',
])

const isFormValid = ref(false)
const refForm = ref()
const branch_id = ref(null)
const supplier_id = ref(null)
const date = ref(new Date().toISOString().substr(0, 10))
const payment_method = ref('credit') // cash, credit
const due_date = ref('')
const notes = ref('')

// Live product search
const productOptions = ref([])
const isSearchingProduct = ref(false)
let searchTimeout = null

const formatRupiahNumber = val => {
  if (!val || isNaN(val)) return ''
  return new Intl.NumberFormat('id-ID').format(val)
}

const parseRupiahInput = val => {
  if (!val) return 0
  const clean = String(val).replace(/[^0-9]/g, '')
  return clean ? parseInt(clean, 10) : 0
}

const defaultItem = () => ({
  product_id: null,
  unit_name: 'pcs',
  conversion_qty: 1,
  qty: 1,
  gross_price: 0,
})

const items = ref([defaultItem()])

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
    payment_method.value = (newVal.due_date && newVal.due_date !== newVal.date) ? 'credit' : 'cash'
    due_date.value = newVal.due_date ? newVal.due_date.substr(0, 10) : ''
    notes.value = newVal.notes || ''
    
    if (newVal.items && newVal.items.length) {
      items.value = newVal.items.map(item => {
        if (item.product) {
          productOptions.value.push(item.product)
        }
        return {
          product_id: item.product_id,
          unit_name: item.unit_name || 'pcs',
          conversion_qty: item.conversion_qty || 1,
          qty: item.qty || 1,
          gross_price: item.gross_price || item.unit_cost || 0,
        }
      })
    }
  } else {
    branch_id.value = props.branches?.length > 0 ? props.branches[0].id : null
    supplier_id.value = null
    date.value = new Date().toISOString().substr(0, 10)
    payment_method.value = 'credit'
    due_date.value = ''
    notes.value = ''
    items.value = [defaultItem()]
  }
}, { immediate: true })

const availableSupplierCredit = ref(0)

watch(supplier_id, async newId => {
  if (!newId) {
    availableSupplierCredit.value = 0
    return
  }
  try {
    const res = await $api(`/apps/supplier-credits?supplier_id=${newId}&available_only=true`)
    availableSupplierCredit.value = Number(res.total_available_credit) || 0
  } catch (e) {
    availableSupplierCredit.value = 0
  }
})

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  emit('update:is-drawer-open', false)
  emit('close')
  emit('cancel')
  nextTick(() => {
    refForm.value?.resetValidation()
  })
}

const addItem = () => {
  items.value.push(defaultItem())
}

const removeItem = index => {
  if (items.value.length > 1) {
    items.value.splice(index, 1)
  }
}

const onProductSelected = (productId, itemIndex) => {
  const prod = productOptions.value.find(p => p.id === productId)
  if (prod && items.value[itemIndex]) {
    items.value[itemIndex].gross_price = prod.cost_price || 0
  }
}

const totalQtyPesanan = computed(() => {
  return items.value.reduce((sum, item) => sum + (Number(item.qty) || 0), 0)
})

const totalEstimatedAmount = computed(() => {
  return items.value.reduce((sum, item) => {
    return sum + ((Number(item.qty) || 0) * (Number(item.gross_price) || 0))
  }, 0)
})

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      if (items.value.some(i => !i.product_id || Number(i.qty) < 1)) {
        snackbar.show('Mohon pilih produk dan isi jumlah pesan (Qty) minimal 1 untuk semua baris barang.', 'error')
        return
      }

      const formattedItems = items.value.map(i => {
        const prod = productOptions.value.find(p => p.id === i.product_id)
        const price = Number(i.gross_price) || (prod ? Number(prod.cost_price) || 0 : 0)
        const qty = Number(i.qty) || 1
        const conv = Math.max(1, Number(i.conversion_qty) || 1)

        return {
          product_id: i.product_id,
          unit_name: i.unit_name || 'pcs',
          conversion_qty: conv,
          qty: qty,
          gross_price: price,
          discount_percent_1: 0,
          discount_percent_2: 0,
          discount_percent_3: 0,
          discount_percent_4: 0,
          discount_percent_5: 0,
          discount_string: null,
          discount_amount: 0,
          net_unit_price: price,
          unit_cost: price,
          total_price: price * qty,
          final_cost_per_piece: conv > 0 ? (price / conv) : price,
        }
      })

      const subtotal = formattedItems.reduce((sum, it) => sum + it.total_price, 0)
      const resolvedDueDate = payment_method.value === 'credit' ? (due_date.value || date.value) : date.value

      const payload = {
        branch_id: branch_id.value,
        supplier_id: supplier_id.value,
        date: date.value,
        due_date: resolvedDueDate,
        notes: notes.value,
        tax_type: 'include',
        tax_percentage: 11.00,
        extra_discount: 0,
        subtotal_bruto: subtotal,
        total_amount: subtotal,
        items: formattedItems,
      }

      emit('saveData', {
        id: props.selectedPo?.id,
        branch_id: branch_id.value,
        supplier_id: supplier_id.value,
        invoice_number_supplier: null,
        date: date.value,
        due_date: date.value,
        tax_type: 'include',
        tax_percentage: 11.00,
        extra_discount: 0,
        dpp_amount: subtotal,
        tax_amount: 0,
        subtotal_bruto: subtotal,
        total_amount: subtotal,
        notes: notes.value,
        items: formattedItems,
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
    :width="$vuetify.display.xs ? '100%' : ($vuetify.display.smAndDown ? '92vw' : 820)"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- Drawer Header -->
    <div class="d-flex align-center justify-space-between px-6 py-5 border-b bg-gradient-header">
      <div class="d-flex align-center gap-3">
        <VAvatar
          size="42"
          color="primary"
          variant="tonal"
          class="rounded-lg"
        >
          <VIcon
            :icon="props.selectedPo ? 'ri-edit-box-line' : 'ri-file-add-line'"
            size="24"
          />
        </VAvatar>
        <div>
          <h5 class="text-h6 font-weight-bold mb-0">
            {{ props.selectedPo ? 'Edit Purchase Order' : 'Buat Purchase Order Baru' }}
          </h5>
          <span class="text-caption text-medium-emphasis">
            Pemesanan barang ke supplier secara cepat & praktis
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

    <PerfectScrollbar :options="{ wheelPropagation: false }" style="height: calc(100vh - 80px);">
      <VCard flat class="pa-6">
        <VForm
          ref="refForm"
          v-model="isFormValid"
          @submit.prevent="onSubmit"
        >
          <!-- Section 1: Info Pemesanan -->
          <div class="mb-6 pa-5 rounded-xl border bg-var-theme-surface shadow-xs">
            <div class="d-flex align-center gap-2 mb-4">
              <VIcon icon="ri-information-line" color="primary" size="20" />
              <span class="font-weight-bold text-subtitle-2 text-uppercase letter-spacing-1">
                Informasi Tujuan Pesanan
              </span>
            </div>

            <VRow dense>
              <VCol cols="12" sm="6">
                <VAutocomplete
                  v-model="supplier_id"
                  :rules="[v => !!v || 'Supplier tujuan wajib dipilih']"
                  :items="props.suppliers"
                  item-title="name"
                  item-value="id"
                  label="Pilih Supplier / Vendor"
                  placeholder="Ketik atau cari nama supplier"
                  prepend-inner-icon="ri-truck-line"
                  density="comfortable"
                  variant="outlined"
                />
                <VAlert
                  v-if="availableSupplierCredit > 0"
                  type="success"
                  variant="tonal"
                  density="compact"
                  class="mt-2 text-caption py-2"
                  icon="ri-wallet-3-line"
                >
                  <strong>Saldo Kredit Retur Tersedia:</strong> Rp {{ availableSupplierCredit.toLocaleString('id-ID') }} (Otomatis dapat memotong tagihan PO ini).
                </VAlert>
              </VCol>

              <VCol cols="12" sm="6">
                <VAutocomplete
                  v-model="branch_id"
                  :rules="[v => !!v || 'Cabang penerima wajib dipilih']"
                  :items="props.branches"
                  item-title="name"
                  item-value="id"
                  label="Cabang Penerima Barang"
                  placeholder="Pilih Cabang"
                  prepend-inner-icon="ri-store-2-line"
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>

              <VCol cols="12" sm="6" class="mt-2">
                <VTextField
                  v-model="date"
                  type="date"
                  :rules="[v => !!v || 'Tanggal pemesanan wajib diisi']"
                  label="Tanggal Pemesanan PO"
                  prepend-inner-icon="ri-calendar-event-line"
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>

              <VCol cols="12" sm="6" class="mt-2">
                <VSelect
                  v-model="payment_method"
                  :items="[
                    { title: 'Kredit / Hutang Supplier (Tempo)', value: 'credit' },
                    { title: 'Tunai / Lunas Langsung (Cash on Delivery)', value: 'cash' }
                  ]"
                  label="Metode Pembayaran ke Supplier"
                  prepend-inner-icon="ri-bank-card-line"
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>

              <VCol v-if="payment_method === 'credit'" cols="12" sm="6" class="mt-2">
                <VTextField
                  :model-value="due_date ? String(due_date).substring(0, 10) : 'Diisi saat Penerimaan Gudang'"
                  readonly
                  label="Tanggal Jatuh Tempo Pembayaran"
                  prepend-inner-icon="ri-time-line"
                  density="comfortable"
                  variant="outlined"
                  hint="Tanggal jatuh tempo faktur resmi diisi saat fisik barang diterima di Penerimaan Gudang"
                  persistent-hint
                />
              </VCol>

              <VCol cols="12" :sm="payment_method === 'credit' ? 6 : 12" class="mt-2">
                <VTextField
                  v-model="notes"
                  label="Catatan Pemesanan (Opsional)"
                  placeholder="Misal: Prioritaskan pengiriman pagi..."
                  prepend-inner-icon="ri-file-text-line"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                />
              </VCol>
            </VRow>
          </div>

          <!-- Section 2: Daftar Barang Pesanan -->
          <div class="mb-6">
            <div class="d-flex justify-space-between align-center mb-3">
              <div>
                <h6 class="text-subtitle-1 font-weight-bold d-flex align-center gap-2 mb-0">
                  <VIcon icon="ri-shopping-bag-3-line" size="20" color="primary" />
                  Daftar Barang yang Dipesan
                </h6>
                <span class="text-caption text-medium-emphasis">
                  Tentukan item barang, jumlah pesanan, dan satuan fisik (harga aktual faktur dimasukkan di modul Penerimaan Barang).
                </span>
              </div>
              <VBtn
                size="small"
                variant="tonal"
                color="primary"
                prepend-icon="ri-add-line"
                class="font-weight-bold rounded-lg px-3"
                @click="addItem"
              >
                Tambah Baris Barang
              </VBtn>
            </div>

            <!-- Items Table Card -->
            <div class="border rounded-xl overflow-hidden shadow-xs">
              <VTable density="comfortable" class="po-items-table">
                <thead>
                  <tr class="bg-grey-100">
                    <th class="font-weight-bold py-3 text-uppercase text-caption" style="min-width: 280px;">
                      Produk / Barang
                    </th>
                    <th class="font-weight-bold py-3 text-center text-uppercase text-caption" style="width: 150px;">
                      Jumlah (Qty)
                    </th>
                    <th class="font-weight-bold py-3 text-uppercase text-caption" style="width: 140px;">
                      Satuan
                    </th>
                    <th class="font-weight-bold py-3 text-center text-uppercase text-caption" style="width: 60px;">
                      Hapus
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(item, index) in items"
                    :key="index"
                    class="item-row"
                  >
                    <td class="py-2">
                      <VAutocomplete
                        v-model="item.product_id"
                        :items="productOptions"
                        :item-title="formatProductTitle"
                        item-value="id"
                        placeholder="Cari SKU atau nama barang..."
                        density="compact"
                        variant="outlined"
                        clearable
                        hide-details
                        :loading="isSearchingProduct"
                        :rules="[v => !!v || 'Pilih produk']"
                        @update:search="onProductSearchInput"
                        @update:model-value="val => onProductSelected(val, index)"
                      />
                    </td>
                    <td class="py-2 text-center">
                      <VTextField
                        v-model.number="item.qty"
                        type="number"
                        min="1"
                        density="compact"
                        variant="outlined"
                        hide-details
                        class="text-center font-weight-bold qty-input"
                        :rules="[v => v > 0 || 'Min. 1']"
                      />
                    </td>
                    <td class="py-2">
                      <VSelect
                        v-model="item.unit_name"
                        :items="['pcs', 'dus', 'box', 'karton', 'pack', 'set', 'koli', 'unit']"
                        density="compact"
                        variant="outlined"
                        hide-details
                      />
                    </td>
                    <td class="text-center py-2">
                      <IconBtn
                        size="small"
                        color="error"
                        variant="text"
                        :disabled="items.length === 1"
                        @click="removeItem(index)"
                      >
                        <VIcon icon="ri-delete-bin-line" size="18" />
                      </IconBtn>
                    </td>
                  </tr>
                </tbody>
              </VTable>
            </div>
          </div>

          <!-- Section 3: Ringkasan Total Pesanan -->
          <div class="pa-4 bg-primary-lighten-5 border border-primary border-opacity-25 rounded-xl d-flex justify-space-between align-center mb-6 flex-wrap gap-3">
            <div class="d-flex align-center gap-3">
              <VAvatar color="primary" variant="flat" size="38" class="rounded-lg">
                <VIcon icon="ri-stack-line" color="white" size="20" />
              </VAvatar>
              <div>
                <div class="text-caption text-medium-emphasis">Total Jenis Produk:</div>
                <div class="font-weight-bold text-subtitle-1 text-primary">
                  {{ items.length }} Item Barang
                </div>
              </div>
            </div>
            <div>
              <div class="text-caption text-medium-emphasis">Metode Pembayaran:</div>
              <div class="font-weight-bold text-caption text-primary">
                {{ payment_method === 'credit' ? 'Kredit / Tempo' : 'Tunai / Lunas' }}
                {{ payment_method === 'credit' && due_date ? `(Jatuh Tempo: ${String(due_date).substring(0, 10)})` : '' }}
              </div>
            </div>
            <div class="text-right">
              <div class="text-caption text-medium-emphasis">Total Kuantitas Pesanan:</div>
              <div class="font-weight-bold text-h6 text-success">
                {{ totalQtyPesanan }} Unit
              </div>
            </div>
          </div>

          <!-- Sticky Action Bar -->
          <div class="d-flex align-center gap-3 pt-2">
            <VBtn
              type="submit"
              color="primary"
              size="large"
              prepend-icon="ri-send-plane-fill"
              class="font-weight-bold flex-grow-1 rounded-lg shadow-sm"
            >
              {{ props.selectedPo ? 'Simpan Perubahan PO' : 'Ajukan Purchase Order' }}
            </VBtn>
            <VBtn
              variant="outlined"
              color="secondary"
              size="large"
              class="rounded-lg px-5"
              @click="closeNavigationDrawer"
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
.letter-spacing-1 {
  letter-spacing: 0.5px;
}
.po-items-table tr:hover {
  background-color: rgba(var(--v-theme-primary), 0.03);
}
.bg-gradient-header {
  background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.05) 0%, rgba(var(--v-theme-surface), 1) 100%);
}
</style>
