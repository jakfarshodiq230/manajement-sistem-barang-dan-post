<script setup>
import { ref, watch, nextTick, onMounted, computed } from 'vue'
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
const invoice_number_supplier = ref('')
const date = ref(new Date().toISOString().substr(0, 10))
const due_date = ref(new Date().toISOString().substr(0, 10))
const notes = ref('')

// Tax and Discount Settings
const tax_type = ref('include')
const tax_percentage = ref(11.00)
const extra_discount = ref(0)

// Live product search
const productOptions = ref([])
const isSearchingProduct = ref(false)
let searchTimeout = null

const defaultItem = () => ({
  product_id: null,
  unit_name: 'pcs',
  conversion_qty: 1,
  qty: 1,
  gross_price: 0,
  discount_percent_1: 0,
  discount_percent_2: 0,
  discount_percent_3: 0,
  discount_percent_4: 0,
  discount_percent_5: 0,
  discount_string: '',
  discount_amount: 0,
  unit_cost: 0,
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

// Item calculation helpers (5-tier discount support D1..D5 + Diskon Rp)
const calculateItemNetto = item => {
  const gross = Number(item.gross_price) || 0
  const d1 = Number(item.discount_percent_1) || 0
  const d2 = Number(item.discount_percent_2) || 0
  const d3 = Number(item.discount_percent_3) || 0
  const d4 = Number(item.discount_percent_4) || 0
  const d5 = Number(item.discount_percent_5) || 0
  const dNominal = Number(item.discount_amount) || 0
  const qty = Number(item.qty) || 1

  let cur = gross
  if (d1 > 0) cur *= (1 - (d1 / 100))
  if (d2 > 0) cur *= (1 - (d2 / 100))
  if (d3 > 0) cur *= (1 - (d3 / 100))
  if (d4 > 0) cur *= (1 - (d4 / 100))
  if (d5 > 0) cur *= (1 - (d5 / 100))

  const net = Math.max(0, cur - (dNominal > 0 && qty > 0 ? (dNominal / qty) : 0))
  return Math.round(net * 100) / 100
}

const calculateItemEffectiveDiscountPercent = item => {
  const gross = Number(item.gross_price) || 0
  if (gross <= 0) return 0
  const net = calculateItemNetto(item)
  const eff = ((gross - net) / gross) * 100
  return Math.round(eff * 100) / 100
}

const onDiscountStringChange = (val, item) => {
  if (typeof val !== 'string') return
  const parts = val.replace(/[^0-9.+]/g, '').split('+').map(p => parseFloat(p.trim())).filter(p => !isNaN(p))
  item.discount_percent_1 = parts[0] ?? 0
  item.discount_percent_2 = parts[1] ?? 0
  item.discount_percent_3 = parts[2] ?? 0
  item.discount_percent_4 = parts[3] ?? 0
  item.discount_percent_5 = parts[4] ?? 0
}

const calculateItemSubtotal = item => {
  const net = calculateItemNetto(item)
  const qty = Number(item.qty) || 1
  return Math.round(net * qty)
}

const calculateItemHppPerPcs = item => {
  const subtotal = calculateItemSubtotal(item)
  const qty = Number(item.qty) || 1
  const conv = Math.max(1, Number(item.conversion_qty) || 1)
  const totalPcs = qty * conv
  return totalPcs > 0 ? Math.round((subtotal / totalPcs) * 100) / 100 : subtotal
}

// Summary computations
const subtotalBruto = computed(() => {
  return items.value.reduce((sum, item) => {
    const gross = Number(item.gross_price) || 0
    const qty = Number(item.qty) || 1
    return sum + (gross * qty)
  }, 0)
})

const subtotalNetto = computed(() => {
  return items.value.reduce((sum, item) => {
    return sum + calculateItemSubtotal(item)
  }, 0)
})

const totalTax = computed(() => {
  const net = Math.max(0, subtotalNetto.value - (Number(extra_discount.value) || 0))
  const rate = (Number(tax_percentage.value) || 0) / 100

  if (tax_type.value === 'include') {
    const dpp = net / (1 + rate)
    return Math.round(net - dpp)
  } else if (tax_type.value === 'exclude') {
    return Math.round(net * rate)
  }
  return 0
})

const totalDpp = computed(() => {
  const net = Math.max(0, subtotalNetto.value - (Number(extra_discount.value) || 0))
  const rate = (Number(tax_percentage.value) || 0) / 100

  if (tax_type.value === 'include') {
    return Math.round(net / (1 + rate))
  }
  return net
})

const grandTotal = computed(() => {
  const net = Math.max(0, subtotalNetto.value - (Number(extra_discount.value) || 0))
  if (tax_type.value === 'exclude') {
    return net + totalTax.value
  }
  return net
})

watch(() => props.selectedPo, newVal => {
  if (newVal) {
    branch_id.value = newVal.branch_id
    supplier_id.value = newVal.supplier_id
    invoice_number_supplier.value = newVal.invoice_number_supplier || ''
    date.value = newVal.date || newVal.created_at?.substr(0, 10)
    due_date.value = newVal.due_date || date.value
    tax_type.value = newVal.tax_type || 'include'
    tax_percentage.value = newVal.tax_percentage !== null ? Number(newVal.tax_percentage) : 11.00
    extra_discount.value = Number(newVal.extra_discount) || 0
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
          qty: item.qty,
          gross_price: item.gross_price || item.unit_cost || 0,
          discount_percent_1: item.discount_percent_1 || 0,
          discount_percent_2: item.discount_percent_2 || 0,
          discount_percent_3: item.discount_percent_3 || 0,
          discount_percent_4: item.discount_percent_4 || 0,
          discount_percent_5: item.discount_percent_5 || 0,
          discount_string: item.discount_string || '',
          discount_amount: item.discount_amount || 0,
          unit_cost: item.unit_cost || 0,
        }
      })
    }
  } else {
    branch_id.value = null
    supplier_id.value = null
    invoice_number_supplier.value = ''
    date.value = new Date().toISOString().substr(0, 10)
    due_date.value = new Date().toISOString().substr(0, 10)
    tax_type.value = 'include'
    tax_percentage.value = 11.00
    extra_discount.value = 0
    notes.value = ''
    items.value = [defaultItem()]
  }
}, { immediate: true })

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
    items.value = [defaultItem()]
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
    if (items.value[itemIndex].gross_price === 0) {
      items.value[itemIndex].gross_price = prod.cost_price || 0
    }
  }
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      if (items.value.some(i => !i.product_id || i.qty < 1 || i.gross_price < 0)) {
        alert('Mohon lengkapi semua baris barang dengan benar (Produk, Qty > 0, Harga Bruto >= 0).')
        return
      }

      const formattedItems = items.value.map(i => {
        const net = calculateItemNetto(i)
        const sub = calculateItemSubtotal(i)
        const hpp = calculateItemHppPerPcs(i)
        return {
          product_id: i.product_id,
          unit_name: i.unit_name || 'pcs',
          conversion_qty: Math.max(1, Number(i.conversion_qty) || 1),
          qty: Number(i.qty) || 1,
          gross_price: Number(i.gross_price) || 0,
          discount_percent_1: Number(i.discount_percent_1) || 0,
          discount_percent_2: Number(i.discount_percent_2) || 0,
          discount_percent_3: Number(i.discount_percent_3) || 0,
          discount_percent_4: Number(i.discount_percent_4) || 0,
          discount_percent_5: Number(i.discount_percent_5) || 0,
          discount_string: i.discount_string || null,
          discount_amount: Number(i.discount_amount) || 0,
          net_unit_price: net,
          unit_cost: net,
          total_price: sub,
          final_cost_per_piece: hpp,
        }
      })

      emit('saveData', {
        id: props.selectedPo?.id,
        branch_id: branch_id.value,
        supplier_id: supplier_id.value,
        invoice_number_supplier: invoice_number_supplier.value ? invoice_number_supplier.value.trim() : null,
        date: date.value,
        due_date: due_date.value,
        tax_type: tax_type.value,
        tax_percentage: Number(tax_percentage.value) || 0,
        extra_discount: Number(extra_discount.value) || 0,
        dpp_amount: totalDpp.value,
        tax_amount: totalTax.value,
        subtotal_bruto: subtotalBruto.value,
        total_amount: grandTotal.value,
        notes: notes.value,
        items: formattedItems,
      })
      closeNavigationDrawer()
    }
  })
}

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
}

const formatCurrency = val => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val || 0)
}
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="1120"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <AppDrawerHeaderSection
      :title="props.selectedPo ? 'Edit Purchase Order' : 'Buat Purchase Order Baru (Faktur Supplier)'"
      @cancel="closeNavigationDrawer"
    />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText class="pa-6">
          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
          >
            <!-- Header Faktur Info -->
            <VRow>
              <VCol cols="12" md="6">
                <VAutocomplete
                  v-model="branch_id"
                  :rules="[v => !!v || 'Cabang tujuan wajib dipilih']"
                  :items="props.branches"
                  item-title="name"
                  item-value="id"
                  label="Cabang Penerima (Tujuan Barang)"
                  placeholder="Pilih Cabang"
                  prepend-inner-icon="ri-store-2-line"
                  density="compact"
                />
              </VCol>

              <VCol cols="12" md="6">
                <VAutocomplete
                  v-model="supplier_id"
                  :rules="[v => !!v || 'Supplier wajib dipilih']"
                  :items="props.suppliers"
                  item-title="name"
                  item-value="id"
                  label="Supplier / Vendor"
                  placeholder="Pilih Supplier"
                  prepend-inner-icon="ri-truck-line"
                  density="compact"
                />
              </VCol>

              <VCol cols="12" md="4">
                <VTextField
                  v-model="invoice_number_supplier"
                  label="No. Faktur / Kuitansi Supplier"
                  placeholder="Contoh: FK.202608.01875"
                  prepend-inner-icon="ri-file-list-3-line"
                  density="compact"
                />
              </VCol>

              <VCol cols="12" md="4">
                <VTextField
                  v-model="date"
                  type="date"
                  :rules="[v => !!v || 'Tanggal wajib diisi']"
                  label="Tanggal Transaksi"
                  prepend-inner-icon="ri-calendar-line"
                  density="compact"
                />
              </VCol>

              <VCol cols="12" md="4">
                <VTextField
                  v-model="due_date"
                  type="date"
                  label="Tgl. Jatuh Tempo"
                  prepend-inner-icon="ri-calendar-event-line"
                  density="compact"
                />
              </VCol>

              <!-- Tax and Discount Settings -->
              <VCol cols="12" md="4">
                <VSelect
                  v-model="tax_type"
                  :items="[
                    { title: 'Sudah Termasuk Pajak (Include PPN)', value: 'include' },
                    { title: 'Pajak Terpisah (Exclude PPN)', value: 'exclude' },
                    { title: 'Non Pajak / Bebas PPN', value: 'none' }
                  ]"
                  label="Perlakuan PPN"
                  density="compact"
                  prepend-inner-icon="ri-percent-line"
                />
              </VCol>

              <VCol cols="12" md="4">
                <VTextField
                  v-model.number="tax_percentage"
                  type="number"
                  suffix="%"
                  label="Tarif PPN"
                  density="compact"
                  min="0"
                  :disabled="tax_type === 'none'"
                />
              </VCol>

              <VCol cols="12" md="4">
                <VTextField
                  v-model.number="extra_discount"
                  type="number"
                  prefix="Rp"
                  label="Extra Discount Faktur"
                  density="compact"
                  min="0"
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="notes"
                  label="Catatan / Keterangan PO"
                  rows="2"
                  placeholder="Keterangan cara pembayaran, ekspedisi, dll."
                  density="compact"
                  hide-details
                />
              </VCol>

              <!-- Items List -->
              <VCol cols="12">
                <VDivider class="my-4" />
                <div class="d-flex justify-space-between align-center mb-3">
                  <div>
                    <h6 class="text-subtitle-1 font-weight-bold d-flex align-center gap-2">
                      <VIcon icon="ri-shopping-cart-2-line" size="20" color="primary" />
                      Rincian Barang Faktur Pembelian (Multi-Column Diskon & HPP Otomatis)
                    </h6>
                    <span class="text-caption text-medium-emphasis">
                      Mendukung multi-satuan (Dus/Karton/Pcs), diskon bertingkat hingga 5 tingkat (D1 s/d D5), dan kalkulasi HPP modal real.
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

                <!-- Formula Note Alert Box -->
                <div class="pa-3 mb-4 rounded-lg bg-primary-lighten-5 border border-primary border-opacity-25">
                  <div class="d-flex align-center gap-2 mb-1">
                    <VIcon icon="ri-information-line" size="18" color="primary" />
                    <span class="font-weight-bold text-caption text-primary">Catatan Rumus Multi-Column Diskon & HPP Real:</span>
                  </div>
                  <div class="text-caption text-medium-emphasis ps-6" style="font-size: 11px;">
                    <div>• <strong>Diskon Bertingkat (s/d 5 Tingkat):</strong> <code>Harga Bruto x (1 - D1%) x (1 - D2%) x (1 - D3%) x (1 - D4%) x (1 - D5%) - Diskon Rp = DPP Netto</code></div>
                    <div>• <strong>Smart Quick Input:</strong> Anda bisa langsung ketik di kolom <em>Format Cepat</em> misal <code>10+5+2+2+1</code> dan sistem akan otomatis memecahnya ke D1 s/d D5.</div>
                    <div>• <strong>PPN (Pajak):</strong> <code>{{ tax_type === 'include' ? 'Include (Harga faktur sudah termasuk PPN 11%)' : (tax_type === 'exclude' ? 'Exclude (+11% PPN ditambahkan ke total faktur)' : 'Non-PPN (0% Bebas Pajak)') }}</code></div>
                    <div>• <strong>HPP per Pcs:</strong> <code>Total Real / (Qty Beli x Isi Satuan)</code> &rarr; <em>Menghasilkan modal real eceran yang otomatis terupdate di Master Produk & POS Kasir.</em></div>
                  </div>
                </div>

                <!-- Item Cards / Rows -->
                <div
                  v-for="(item, index) in items"
                  :key="index"
                  class="mb-4 pa-4 rounded-lg bg-grey-50 border"
                >
                  <div class="d-flex justify-space-between align-center mb-3 flex-wrap gap-2">
                    <div class="d-flex align-center gap-2">
                      <span class="font-weight-bold text-caption text-primary">#Baris {{ index + 1 }}</span>
                      <VChip v-if="calculateItemEffectiveDiscountPercent(item) > 0" color="info" size="x-small" variant="tonal">
                        Diskon Efektif: <strong>{{ calculateItemEffectiveDiscountPercent(item) }}%</strong>
                      </VChip>
                    </div>
                    <div class="d-flex align-center gap-2">
                      <!-- Live HPP Badge -->
                      <VChip color="success" size="small" variant="elevated">
                        <VIcon icon="ri-price-tag-3-line" size="14" class="me-1" />
                        HPP: <strong>{{ formatCurrency(calculateItemHppPerPcs(item)) }}</strong> / pcs
                      </VChip>
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

                  <VRow dense class="g-2">
                    <!-- Baris 1: Produk & Satuan Beli -->
                    <VCol cols="12" md="6">
                      <VAutocomplete
                        v-model="item.product_id"
                        :items="productOptions"
                        :item-title="formatProductTitle"
                        item-value="id"
                        label="Pilih Produk"
                        placeholder="Ketik SKU atau nama..."
                        density="compact"
                        clearable
                        :loading="isSearchingProduct"
                        :rules="[v => !!v || 'Pilih produk']"
                        @update:search="onProductSearchInput"
                        @update:model-value="val => onProductSelected(val, index)"
                      />
                    </VCol>

                    <VCol cols="4" md="2">
                      <VSelect
                        v-model="item.unit_name"
                        :items="['pcs', 'dus', 'box', 'karton', 'pack', 'set', 'koli']"
                        label="Satuan Beli"
                        density="compact"
                      />
                    </VCol>

                    <VCol cols="4" md="2">
                      <VTextField
                        v-model.number="item.conversion_qty"
                        type="number"
                        min="1"
                        label="Isi (Pcs)"
                        density="compact"
                        hint="1 dus = isi pcs"
                      />
                    </VCol>

                    <VCol cols="4" md="2">
                      <VTextField
                        v-model.number="item.qty"
                        type="number"
                        min="1"
                        label="Qty Beli"
                        density="compact"
                        :rules="[v => v > 0 || 'Min. 1']"
                      />
                    </VCol>

                    <!-- Baris 2: Harga & Multi-Column Diskon Bertingkat (D1 s/d D5) -->
                    <VCol cols="12" md="3">
                      <VTextField
                        v-model.number="item.gross_price"
                        type="number"
                        prefix="Rp"
                        label="Harga Bruto (HRG/@)"
                        density="compact"
                        min="0"
                      />
                    </VCol>

                    <VCol cols="12" md="3">
                      <VTextField
                        v-model="item.discount_string"
                        label="Format Cepat (cth: 10+5+2+2+1)"
                        placeholder="Ketik 10+5+2..."
                        density="compact"
                        hint="Auto-isi D1 s/d D5"
                        @update:model-value="val => onDiscountStringChange(val, item)"
                      />
                    </VCol>

                    <VCol cols="4" sm="2" md="1">
                      <VTextField
                        v-model.number="item.discount_percent_1"
                        type="number"
                        suffix="%"
                        label="D1"
                        density="compact"
                        min="0"
                        max="100"
                      />
                    </VCol>

                    <VCol cols="4" sm="2" md="1">
                      <VTextField
                        v-model.number="item.discount_percent_2"
                        type="number"
                        suffix="%"
                        label="D2"
                        density="compact"
                        min="0"
                        max="100"
                      />
                    </VCol>

                    <VCol cols="4" sm="2" md="1">
                      <VTextField
                        v-model.number="item.discount_percent_3"
                        type="number"
                        suffix="%"
                        label="D3"
                        density="compact"
                        min="0"
                        max="100"
                      />
                    </VCol>

                    <VCol cols="4" sm="2" md="1">
                      <VTextField
                        v-model.number="item.discount_percent_4"
                        type="number"
                        suffix="%"
                        label="D4"
                        density="compact"
                        min="0"
                        max="100"
                      />
                    </VCol>

                    <VCol cols="4" sm="2" md="1">
                      <VTextField
                        v-model.number="item.discount_percent_5"
                        type="number"
                        suffix="%"
                        label="D5"
                        density="compact"
                        min="0"
                        max="100"
                      />
                    </VCol>

                    <VCol cols="6" sm="4" md="2">
                      <VTextField
                        v-model.number="item.discount_amount"
                        type="number"
                        prefix="Rp"
                        label="Diskon Rp"
                        density="compact"
                        min="0"
                      />
                    </VCol>

                    <VCol cols="6" sm="8" md="3">
                      <VTextField
                        :model-value="formatCurrency(calculateItemSubtotal(item))"
                        label="Subtotal DPP Netto"
                        density="compact"
                        readonly
                        bg-color="grey-100"
                      />
                    </VCol>
                  </VRow>
                </div>
              </VCol>

              <!-- Footer Faktur Summary -->
              <VCol cols="12">
                <VCard variant="outlined" class="pa-4 bg-light rounded-lg">
                  <VRow>
                    <VCol cols="12" md="6">
                      <div class="text-caption text-medium-emphasis mb-2 font-weight-bold">
                        RINGKASAN PERHITUNGAN FAKTUR SUPPLIER:
                      </div>
                      <div class="d-flex justify-space-between text-body-2 mb-1">
                        <span>Total Bruto:</span>
                        <span class="font-weight-medium">{{ formatCurrency(subtotalBruto) }}</span>
                      </div>
                      <div class="d-flex justify-space-between text-body-2 mb-1">
                        <span>Extra Discount:</span>
                        <span class="text-error font-weight-medium">- {{ formatCurrency(extra_discount) }}</span>
                      </div>
                      <div class="d-flex justify-space-between text-body-2 mb-1">
                        <span>DPP (Dasar Pengenaan Pajak):</span>
                        <span class="font-weight-medium">{{ formatCurrency(totalDpp) }}</span>
                      </div>
                      <div class="d-flex justify-space-between text-body-2 mb-1">
                        <span>PPN ({{ tax_percentage }}%):</span>
                        <span class="font-weight-medium">{{ formatCurrency(totalTax) }}</span>
                      </div>
                    </VCol>

                    <VCol cols="12" md="6" class="d-flex flex-column justify-center align-end border-s">
                      <div class="text-caption text-medium-emphasis font-weight-bold mb-1">
                        TOTAL TAGIHAN FAKTUR (INC PPN)
                      </div>
                      <div class="text-h4 font-weight-bold text-primary mb-2">
                        {{ formatCurrency(grandTotal) }}
                      </div>
                      <span class="text-caption text-success font-weight-medium">
                        *HPP produk otomatis disesuaikan secara presisi
                      </span>
                    </VCol>
                  </VRow>
                </VCard>
              </VCol>

              <!-- Actions -->
              <VCol cols="12" class="mt-4 d-flex gap-2">
                <VBtn
                  type="submit"
                  color="primary"
                  prepend-icon="ri-send-plane-line"
                  class="font-weight-bold"
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
