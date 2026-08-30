<script setup>
import { ref, watch, nextTick, computed } from 'vue'
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
  selectedGr: {
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
const invoice_number_supplier = ref('')
const sales_name = ref('')
const date = ref(new Date().toISOString().substr(0, 10))
const received_date = ref(new Date().toISOString().substr(0, 10))
const due_date = ref('')
const notes = ref('')
const items = ref([])
const photos = ref([])
const photoPreviews = ref([])

// Tax & Discount Settings from PO / Invoice
const tax_type = ref('include')
const tax_percentage = ref(11.00)
const extra_discount = ref(0)
const extra_discount_display = ref('0')

const isEditMode = computed(() => !!props.selectedGr)

const rejectionReasonOptions = [
  'Barang Cacat / Rusak Fisik',
  'Kemasan Rusak / Bocor',
  'Spesifikasi / Merek Tidak Sesuai Pesanan',
  'Masa Kedaluwarsa Terlalu Dekat / Expired',
  'Jumlah Dikirim Kurang / Parsial',
  'Salah Kirim Produk oleh Supplier',
  'Lainnya (Tulis di Catatan)',
]

// Currency Formatting Helper Functions
const formatRupiahNumber = val => {
  if (val === null || val === undefined || val === '') return ''
  const num = typeof val === 'number' ? val : Number(String(val).replace(/[^0-9.-]+/g, ''))
  if (isNaN(num)) return ''
  return new Intl.NumberFormat('id-ID').format(Math.round(num))
}

const parseRupiahInput = val => {
  if (!val) return 0
  const clean = String(val).replace(/[^0-9]/g, '')
  return clean ? Number(clean) : 0
}

const formatCurrency = val => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val || 0)
}

const setDueDateOffset = days => {
  const base = received_date.value ? new Date(received_date.value) : new Date()
  if (isNaN(base.getTime())) return
  base.setDate(base.getDate() + days)
  due_date.value = base.toISOString().substr(0, 10)
}

// Generate previews when photos change
watch(() => photos.value, newPhotos => {
  photoPreviews.value.forEach(p => URL.revokeObjectURL(p))
  photoPreviews.value = []
  
  if (newPhotos) {
    const filesArray = Array.isArray(newPhotos) ? newPhotos : Array.from(newPhotos)

    filesArray.forEach(file => {
      const actualFile = (file && file.file) ? file.file : file
      if (actualFile instanceof File || actualFile instanceof Blob) {
        try {
          photoPreviews.value.push(URL.createObjectURL(actualFile))
        } catch(e) {
          console.error("Failed to create object URL for file", e)
        }
      }
    })
  }
}, { deep: true })

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
    invoice_number_supplier.value = ''
    items.value = []
    photos.value = []
    photoPreviews.value.forEach(p => URL.revokeObjectURL(p))
    photoPreviews.value = []
  })
}

// Calculation helpers
const calculateItemNetto = item => {
  const gross = Number(item.gross_price) || 0
  const d1 = Number(item.discount_percent_1) || 0
  const d2 = Number(item.discount_percent_2) || 0
  const d3 = Number(item.discount_percent_3) || 0
  const d4 = Number(item.discount_percent_4) || 0
  const d5 = Number(item.discount_percent_5) || 0
  const dNominal = Number(item.discount_amount) || 0
  const qty = Number(item.qty_received) || 1

  let cur = gross
  if (d1 > 0) cur *= (1 - (d1 / 100))
  if (d2 > 0) cur *= (1 - (d2 / 100))
  if (d3 > 0) cur *= (1 - (d3 / 100))
  if (d4 > 0) cur *= (1 - (d4 / 100))
  if (d5 > 0) cur *= (1 - (d5 / 100))

  const net = Math.max(0, cur - (dNominal > 0 && qty > 0 ? (dNominal / qty) : 0))
  return Math.round(net)
}

const calculateItemSubtotal = item => {
  if (!item.is_received || (Number(item.qty_received) || 0) <= 0) return 0
  const net = calculateItemNetto(item)
  const qty = Number(item.qty_received) || 0
  return Math.round(net * qty)
}

const calculateItemHppPerPcs = item => {
  const net = calculateItemNetto(item)
  const conv = Math.max(1, Number(item.conversion_qty) || 1)
  const baseHpp = net / conv
  
  // Jika faktur Exclude PPN (belum include PPN), maka HPP modal asli toko ditambah PPN 11% agar tidak tekor saat jual
  if (tax_type.value === 'exclude') {
    const rate = (Number(tax_percentage.value) || 0) / 100
    return Math.round(baseHpp * (1 + rate))
  }
  return Math.round(baseHpp)
}

const onTaxTypeChange = val => {
  tax_type.value = val
  if (val === 'none') {
    tax_percentage.value = 0
  } else if (!tax_percentage.value) {
    tax_percentage.value = 11.00
  }
  items.value.forEach(item => autoCalculatePrices(item))
}

const globalMarkupPercent = ref(25)
const globalMinNegoPercent = ref(10)

const applyGlobalPercentages = () => {
  items.value.forEach(item => {
    item.markup_percent = Number(globalMarkupPercent.value) || 0
    item.min_nego_percent = Number(globalMinNegoPercent.value) || 0
    item.is_price_customized = false
    item.is_min_nego_customized = false
    autoCalculatePrices(item)
  })
}

const applyMarkupPercent = (item, percent) => {
  item.markup_percent = Number(percent) || 0
  const hpp = calculateItemHppPerPcs(item)
  if (hpp > 0) {
    item.price = Math.ceil((hpp * (1 + (item.markup_percent / 100))) / 1000) * 1000
    item.price_display = formatRupiahNumber(item.price)
    item.is_price_customized = true
  }
}

const applyMinNegoPercent = (item, percent) => {
  item.min_nego_percent = Number(percent) || 0
  const hpp = calculateItemHppPerPcs(item)
  if (hpp > 0) {
    item.min_nego_price = Math.ceil((hpp * (1 + (item.min_nego_percent / 100))) / 1000) * 1000
    item.min_nego_price_display = formatRupiahNumber(item.min_nego_price)
    item.is_min_nego_customized = true
  }
}

const autoCalculatePrices = item => {
  const hpp = calculateItemHppPerPcs(item)
  if (hpp > 0) {
    const mPercent = (item.markup_percent !== undefined && item.markup_percent !== null) ? Number(item.markup_percent) : (Number(globalMarkupPercent.value) || 25)
    const nPercent = (item.min_nego_percent !== undefined && item.min_nego_percent !== null) ? Number(item.min_nego_percent) : (Number(globalMinNegoPercent.value) || 10)
    
    item.markup_percent = mPercent
    item.min_nego_percent = nPercent

    if (!item.is_price_customized) {
      item.price = Math.ceil((hpp * (1 + (mPercent / 100))) / 1000) * 1000
      item.price_display = formatRupiahNumber(item.price)
    }
    if (!item.is_min_nego_customized) {
      item.min_nego_price = Math.ceil((hpp * (1 + (nPercent / 100))) / 1000) * 1000
      item.min_nego_price_display = formatRupiahNumber(item.min_nego_price)
    }
  }
}

const onSellingPriceInput = (val, item) => {
  const num = parseRupiahInput(val)
  item.price = num
  item.price_display = num ? formatRupiahNumber(num) : ''
  item.is_price_customized = true
  
  const hpp = calculateItemHppPerPcs(item)
  if (hpp > 0 && num > 0) {
    item.markup_percent = Math.round(((num - hpp) / hpp) * 100)
  }
}

const onMinNegoPriceInput = (val, item) => {
  const num = parseRupiahInput(val)
  item.min_nego_price = num
  item.min_nego_price_display = num ? formatRupiahNumber(num) : ''
  item.is_min_nego_customized = true
  
  const hpp = calculateItemHppPerPcs(item)
  if (hpp > 0 && num > 0) {
    item.min_nego_percent = Math.round(((num - hpp) / hpp) * 100)
  }
}

const onDiscountStringChange = (val, item) => {
  if (!val) {
    item.discount_percent_1 = 0
    item.discount_percent_2 = 0
    item.discount_percent_3 = 0
    item.discount_percent_4 = 0
    item.discount_percent_5 = 0
    autoCalculatePrices(item)
    return
  }
  // Parse formats like "15+5", "15%+5%", "15.00%+ 5.00%"
  const cleanStr = String(val).replace(/%/g, '')
  const parts = cleanStr.split('+').map(p => parseFloat(p.trim())).filter(p => !isNaN(p))
  item.discount_percent_1 = parts[0] ?? 0
  item.discount_percent_2 = parts[1] ?? 0
  item.discount_percent_3 = parts[2] ?? 0
  item.discount_percent_4 = parts[3] ?? 0
  item.discount_percent_5 = parts[4] ?? 0
  autoCalculatePrices(item)
}

const onGrossPriceInput = (val, item) => {
  const num = parseRupiahInput(val)
  item.gross_price = num
  item.gross_price_display = num ? formatRupiahNumber(num) : ''
  autoCalculatePrices(item)
}

const onDiscountAmountInput = (val, item) => {
  const num = parseRupiahInput(val)
  item.discount_amount = num
  item.discount_amount_display = num ? formatRupiahNumber(num) : ''
  autoCalculatePrices(item)
}

const onExtraDiscountInput = val => {
  const num = parseRupiahInput(val)
  extra_discount.value = num
  extra_discount_display.value = num ? formatRupiahNumber(num) : '0'
}

const onToggleItemReceived = (item, isChecked) => {
  item.is_received = isChecked
  if (!isChecked) {
    item.qty_received = 0
    item.qty_rejected = item.ordered_qty
    if (!item.rejection_reason) {
      item.rejection_reason = 'Barang Cacat / Rusak Fisik'
    }
  } else {
    item.qty_received = item.ordered_qty
    item.qty_rejected = 0
  }
}

const onQtyReceivedChange = item => {
  if (item.qty_received === null || item.qty_received === undefined || isNaN(item.qty_received) || item.qty_received < 0) {
    item.qty_received = 0
  }
  item.qty_rejected = Math.max(0, item.ordered_qty - item.qty_received)
  if (item.qty_rejected > 0 && !item.rejection_reason) {
    item.rejection_reason = 'Jumlah Dikirim Kurang / Parsial'
  }
}

const selectAllReceived = (isRec = true) => {
  items.value.forEach(item => {
    onToggleItemReceived(item, isRec)
  })
}

watch([() => props.selectedPo, () => props.selectedGr], ([newPo, newGr]) => {
  if (newGr) {
    // Edit Mode
    invoice_number_supplier.value = newGr.invoice_number_supplier || newGr.purchase_order?.invoice_number_supplier || ''
    sales_name.value = newGr.sales_name || ''
    date.value = newGr.date ? newGr.date.substring(0, 10) : new Date().toISOString().substr(0, 10)
    received_date.value = newGr.received_date ? newGr.received_date.substring(0, 10) : date.value
    due_date.value = newGr.due_date ? newGr.due_date.substring(0, 10) : ''
    notes.value = newGr.notes || ''
    tax_type.value = newGr.tax_type || 'include'
    tax_percentage.value = newGr.tax_percentage !== null ? Number(newGr.tax_percentage) : 11.00
    extra_discount.value = Number(newGr.extra_discount) || 0
    extra_discount_display.value = formatRupiahNumber(extra_discount.value)
    
    if (newGr.purchase_order && newGr.purchase_order.items) {
      items.value = newGr.purchase_order.items.map(poItem => {
        const grItem = newGr.items?.find(i => i.purchase_order_item_id === poItem.id)
        const isRec = grItem ? (grItem.is_received ?? (grItem.qty_received > 0)) : true
        const qtyRec = grItem ? grItem.qty_received : poItem.qty
        const qtyRej = grItem ? (grItem.qty_rejected ?? Math.max(0, poItem.qty - qtyRec)) : 0
        const gross = grItem?.gross_price ?? (poItem.gross_price || poItem.unit_cost || 0)
        const discNom = grItem?.discount_amount ?? (poItem.discount_amount || 0)
        
        const itm = {
          purchase_order_item_id: poItem.id,
          product_id: poItem.product_id,
          product_name: poItem.product?.name || 'Produk',
          sku: poItem.product?.sku || '-',
          unit_name: poItem.unit_name || 'pcs',
          conversion_qty: poItem.conversion_qty || 1,
          ordered_qty: poItem.qty,
          is_received: Boolean(isRec),
          qty_received: qtyRec,
          qty_rejected: qtyRej,
          rejection_reason: grItem?.rejection_reason || '',
          scc_code: grItem?.scc_code || '',
          batch_number: grItem?.batch_number || '',
          expiration_date: grItem?.expiration_date ? grItem.expiration_date.substring(0, 10) : '',
          gross_price: gross,
          gross_price_display: formatRupiahNumber(gross),
          discount_string: grItem?.discount_string ?? (poItem.discount_string || '15+5'),
          discount_percent_1: grItem?.discount_percent_1 ?? (poItem.discount_percent_1 || 15),
          discount_percent_2: grItem?.discount_percent_2 ?? (poItem.discount_percent_2 || 5),
          discount_percent_3: grItem?.discount_percent_3 ?? (poItem.discount_percent_3 || 0),
          discount_percent_4: grItem?.discount_percent_4 ?? (poItem.discount_percent_4 || 0),
          discount_percent_5: grItem?.discount_percent_5 ?? (poItem.discount_percent_5 || 0),
          discount_amount: discNom,
          discount_amount_display: formatRupiahNumber(discNom),
          price: grItem?.price || 0,
          price_display: grItem?.price ? formatRupiahNumber(grItem.price) : '',
          min_nego_price: grItem?.min_nego_price || 0,
          min_nego_price_display: grItem?.min_nego_price ? formatRupiahNumber(grItem.min_nego_price) : '',
          is_price_customized: Boolean(grItem?.price > 0),
          is_min_nego_customized: Boolean(grItem?.min_nego_price > 0),
          show_details: true,
        }
        autoCalculatePrices(itm)
        return itm
      })
    }
  } else if (newPo && newPo.items) {
    // Create Mode
    invoice_number_supplier.value = newPo.invoice_number_supplier || ''
    sales_name.value = ''
    date.value = new Date().toISOString().substr(0, 10)
    received_date.value = new Date().toISOString().substr(0, 10)
    due_date.value = ''
    notes.value = ''
    tax_type.value = 'include'
    tax_percentage.value = 11.00
    extra_discount.value = 0
    extra_discount_display.value = '0'

    items.value = newPo.items.map(poItem => {
      const gross = poItem.gross_price || poItem.unit_cost || 0
      const discNom = poItem.discount_amount || 0
      const itm = {
        purchase_order_item_id: poItem.id,
        product_id: poItem.product_id,
        product_name: poItem.product?.name || 'Produk',
        sku: poItem.product?.sku || '-',
        unit_name: poItem.unit_name || 'pcs',
        conversion_qty: poItem.conversion_qty || 1,
        ordered_qty: poItem.qty,
        is_received: true,
        qty_received: poItem.qty,
        qty_rejected: 0,
        rejection_reason: '',
        scc_code: '',
        batch_number: '',
        expiration_date: '',
        original_po_gross: gross,
        gross_price: gross,
        gross_price_display: formatRupiahNumber(gross),
        discount_string: poItem.discount_string || '15+5',
        discount_percent_1: poItem.discount_percent_1 || 15,
        discount_percent_2: poItem.discount_percent_2 || 5,
        discount_percent_3: poItem.discount_percent_3 || 0,
        discount_percent_4: poItem.discount_percent_4 || 0,
        discount_percent_5: poItem.discount_percent_5 || 0,
        discount_amount: discNom,
        discount_amount_display: formatRupiahNumber(discNom),
        price: 0,
        price_display: '',
        min_nego_price: 0,
        min_nego_price_display: '',
        is_price_customized: false,
        is_min_nego_customized: false,
        show_details: true,
      }
      autoCalculatePrices(itm)
      return itm
    })
  }
}, { immediate: true })

const subtotalGrossReceived = computed(() => {
  return items.value.reduce((sum, item) => {
    if (!item.is_received || (Number(item.qty_received) || 0) <= 0) return sum
    const gross = Number(item.gross_price) || 0
    const qty = Number(item.qty_received) || 0
    return sum + (gross * qty)
  }, 0)
})

const subtotalNettoReceived = computed(() => {
  return items.value.reduce((sum, item) => {
    return sum + calculateItemSubtotal(item)
  }, 0)
})

const totalDpp = computed(() => {
  const net = Math.max(0, subtotalNettoReceived.value - (Number(extra_discount.value) || 0))
  if (tax_type.value === 'include') {
    return Math.round(net / (1 + ((Number(tax_percentage.value) || 0) / 100)))
  }
  return Math.round(net)
})

const totalTax = computed(() => {
  if (tax_type.value === 'none') return 0
  const rate = (Number(tax_percentage.value) || 0) / 100
  if (tax_type.value === 'include') {
    return Math.max(0, Math.round(subtotalNettoReceived.value - (Number(extra_discount.value) || 0) - totalDpp.value))
  } else if (tax_type.value === 'exclude') {
    return Math.round(totalDpp.value * rate)
  }
  return 0
})

const grandTotal = computed(() => {
  const net = Math.max(0, subtotalNettoReceived.value - (Number(extra_discount.value) || 0))
  if (tax_type.value === 'exclude') {
    return net + totalTax.value
  }
  return net
})

const hasRejectedItems = computed(() => {
  return items.value.some(i => !i.is_received || i.qty_rejected > 0)
})

const totalRejectedCount = computed(() => {
  return items.value.reduce((sum, i) => {
    return sum + (i.is_received ? (Number(i.qty_rejected) || 0) : (Number(i.ordered_qty) || 0))
  }, 0)
})

const totalReceivedCount = computed(() => {
  return items.value.reduce((sum, i) => {
    return sum + (i.is_received ? (Number(i.qty_received) || 0) : 0)
  }, 0)
})

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      // Check if any rejected item lacks a reason
      const unreasonedRejection = items.value.find(i => (!i.is_received || (Number(i.qty_rejected) || 0) > 0) && !i.rejection_reason)
      if (unreasonedRejection) {
        snackbar.show(`Mohon pilih Alasan Retur / Penolakan untuk barang "${unreasonedRejection.product_name}".`, 'error')
        return
      }

      const formattedItems = items.value.map(i => {
        const isRec = Boolean(i.is_received)
        const qtyRec = isRec ? Number(i.qty_received) || 0 : 0
        const qtyRej = isRec ? Math.max(0, (Number(i.ordered_qty) || 0) - qtyRec) : (Number(i.ordered_qty) || 0)
        const net = calculateItemNetto(i)
        const hpp = calculateItemHppPerPcs(i)

        const actionLabel = i.return_action === 'potong_hutang' ? 'Potong Hutang' : (i.return_action === 'pengembalian_dana' ? 'Refund Dana' : 'Tukar Barang')
        const combinedReason = (!isRec || qtyRej > 0)
          ? `[${actionLabel}] ${i.rejection_reason || 'Barang Ditolak Fisik'}${i.rejection_notes ? ' - ' + i.rejection_notes : ''}`
          : ''

        return {
          purchase_order_item_id: i.purchase_order_item_id,
          product_id: i.product_id,
          unit_name: i.unit_name,
          conversion_qty: i.conversion_qty || 1,
          ordered_qty: i.ordered_qty,
          is_received: isRec,
          qty_received: qtyRec,
          qty_rejected: qtyRej,
          rejection_reason: combinedReason,
          scc_code: i.scc_code || '',
          batch_number: i.batch_number || '',
          expiration_date: i.expiration_date || null,
          gross_price: Number(i.gross_price) || 0,
          discount_string: i.discount_string || '15+5',
          discount_percent_1: Number(i.discount_percent_1) || 0,
          discount_percent_2: Number(i.discount_percent_2) || 0,
          discount_percent_3: Number(i.discount_percent_3) || 0,
          discount_percent_4: Number(i.discount_percent_4) || 0,
          discount_percent_5: Number(i.discount_percent_5) || 0,
          discount_amount: Number(i.discount_amount) || 0,
          net_unit_price: net,
          final_cost_per_piece: hpp,
          price: Number(i.price) || 0,
          min_nego_price: Number(i.min_nego_price) || 0,
        }
      })

      const formData = new FormData()
      if (isEditMode.value) formData.append('id', props.selectedGr.id)
      formData.append('purchase_order_id', isEditMode.value ? props.selectedGr.purchase_order_id : props.selectedPo.id)
      formData.append('invoice_number_supplier', invoice_number_supplier.value || '')
      formData.append('sales_name', sales_name.value || '')
      formData.append('date', date.value)
      formData.append('received_date', received_date.value || date.value)
      formData.append('due_date', due_date.value || '')
      formData.append('tax_type', tax_type.value)
      formData.append('tax_percentage', tax_percentage.value)
      formData.append('extra_discount', extra_discount.value)
      formData.append('dpp_amount', totalDpp.value)
      formData.append('tax_amount', totalTax.value)
      formData.append('notes', notes.value || '')
      formData.append('items', JSON.stringify(formattedItems))
      
      if (photos.value) {
        const filesArray = Array.isArray(photos.value) ? photos.value : Array.from(photos.value)

        filesArray.forEach(file => {
          const actualFile = (file && file.file) ? file.file : file
          if (actualFile instanceof File || actualFile instanceof Blob) {
            formData.append('photos[]', actualFile)
          }
        })
      }

      emit('saveData', formData)
      closeNavigationDrawer()
    }
  })
}

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
}

const displayPo = computed(() => isEditMode.value ? props.selectedGr?.purchase_order : props.selectedPo)
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="1160"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- Header -->
    <div class="d-flex align-center justify-space-between pa-6 bg-gradient-header border-b">
      <div>
        <div class="d-flex align-center gap-2">
          <VIcon icon="ri-truck-line" color="primary" size="24" />
          <h5 class="text-h6 font-weight-bold mb-0 text-primary">
            {{ isEditMode ? 'Revisi Dokumen Penerimaan Gudang' : 'Form Penerimaan Fisik & Verifikasi Faktur Gudang' }}
          </h5>
        </div>
        <span class="text-caption text-medium-emphasis">
          SOP Gudang: Validasi fisik & input rincian faktur dari supplier untuk diajukan ke Kepala Divisi.
        </span>
      </div>
      <VBtn
        icon
        variant="tonal"
        color="secondary"
        size="small"
        @click="closeNavigationDrawer"
      >
        <VIcon icon="ri-close-line" />
      </VBtn>
    </div>

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat class="pa-6">
        <VForm
          ref="refForm"
          v-model="isFormValid"
          @submit.prevent="onSubmit"
        >
          <!-- Rejection Warning Alert if editing a rejected goods receipt -->
          <VAlert
            v-if="props.selectedGr && props.selectedGr.approval_status === 'rejected'"
            type="error"
            variant="tonal"
            class="mb-6 pa-4 rounded-xl border-dashed"
            icon="ri-error-warning-fill"
          >
            <div class="font-weight-bold text-subtitle-2 mb-1">
              Dokumen Ini Ditolak / Diminta Revisi oleh Supervisor / Kepala Divisi:
            </div>
            <div class="text-body-2 bg-var-theme-surface pa-3 rounded border text-error font-weight-medium">
              "{{ props.selectedGr.rejection_reason || 'Silakan cek kembali kesesuaian fisik, rincian diskon, atau kejelasan foto faktur.' }}"
            </div>
            <div class="text-caption mt-2 text-medium-emphasis">
              Silakan sesuaikan data faktur atau barang di bawah ini, lalu klik tombol <strong>"Kirim Ulang Revisi ke Kepala Divisi"</strong> di bagian bawah.
            </div>
          </VAlert>

          <!-- Section 1: Dokumen & Bukti Surat Jalan / Faktur Supplier -->
          <div class="mb-6 pa-5 rounded-xl border bg-var-theme-surface shadow-xs">
            <div class="d-flex align-center gap-2 mb-4">
              <VIcon icon="ri-file-paper-2-line" color="primary" size="20" />
              <span class="font-weight-bold text-subtitle-2 text-uppercase letter-spacing-1">
                1. Data Faktur & Informasi Sales Supplier
              </span>
            </div>

            <VRow dense>
              <!-- No. Faktur Supplier -->
              <VCol cols="12" sm="6" md="3">
                <VTextField
                  v-model="invoice_number_supplier"
                  label="No. Faktur / Kuitansi Supplier"
                  placeholder="Contoh: FK.202608.01875"
                  prepend-inner-icon="ri-bill-line"
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>

              <!-- Nama Sales Supplier -->
              <VCol cols="12" sm="6" md="3">
                <VTextField
                  v-model="sales_name"
                  label="Nama Sales Supplier"
                  placeholder="Contoh: Bpk. Hendra / Capella"
                  prepend-inner-icon="ri-user-star-line"
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>

              <!-- Tanggal Barang Sampai di Gudang -->
              <VCol cols="12" sm="6" md="3">
                <VTextField
                  v-model="received_date"
                  type="date"
                  :rules="[v => !!v || 'Tanggal barang sampai wajib diisi']"
                  label="Tanggal Barang Sampai"
                  prepend-inner-icon="ri-calendar-check-line"
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>

              <!-- Tanggal Jatuh Tempo Faktur -->
              <VCol cols="12" sm="6" md="3">
                <VTextField
                  v-model="due_date"
                  type="date"
                  label="Tanggal Jatuh Tempo Faktur"
                  prepend-inner-icon="ri-calendar-event-line"
                  density="comfortable"
                  variant="outlined"
                  persistent-hint
                  hint="Termin kredit supplier (masuk Buku Hutang)"
                />
                <div class="d-flex gap-1 mt-1 flex-wrap">
                  <VChip size="x-small" variant="tonal" color="secondary" class="cursor-pointer" @click="setDueDateOffset(0)">Cash</VChip>
                  <VChip size="x-small" variant="tonal" color="primary" class="cursor-pointer" @click="setDueDateOffset(14)">+14h</VChip>
                  <VChip size="x-small" variant="tonal" color="primary" class="cursor-pointer" @click="setDueDateOffset(30)">+30h</VChip>
                  <VChip size="x-small" variant="tonal" color="primary" class="cursor-pointer" @click="setDueDateOffset(45)">+45h</VChip>
                  <VChip size="x-small" variant="tonal" color="primary" class="cursor-pointer" @click="setDueDateOffset(60)">+60h</VChip>
                </div>
              </VCol>

              <!-- Foto Faktur / Surat Jalan Fisik -->
              <VCol cols="12" md="4" class="mt-2">
                <VFileInput
                  v-model="photos"
                  multiple
                  chips
                  show-size
                  accept="image/*"
                  label="Foto Faktur / Surat Jalan Fisik"
                  prepend-icon=""
                  prepend-inner-icon="ri-camera-lens-line"
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>

              <!-- Perlakuan PPN -->
              <VCol cols="12" md="4" class="mt-2">
                <VSelect
                  :model-value="tax_type"
                  :items="[
                    { title: 'Include PPN (Sudah Termasuk PPN 11%) - Capella', value: 'include' },
                    { title: 'Exclude PPN (Belum Termasuk PPN, +11% di Bawah)', value: 'exclude' },
                    { title: 'Non-PPN (Tanpa Pajak / 0%)', value: 'none' },
                  ]"
                  item-title="title"
                  item-value="value"
                  label="Perlakuan Pajak PPN Faktur"
                  density="comfortable"
                  variant="outlined"
                  prepend-inner-icon="ri-percent-line"
                  @update:model-value="onTaxTypeChange"
                />
              </VCol>

              <!-- Tarif PPN % -->
              <VCol v-if="tax_type !== 'none'" cols="12" md="4" class="mt-2">
                <VTextField
                  v-model.number="tax_percentage"
                  type="number"
                  label="Tarif PPN (%)"
                  suffix="%"
                  density="comfortable"
                  variant="outlined"
                  @update:model-value="() => items.forEach(i => autoCalculatePrices(i))"
                />
              </VCol>

              <!-- Catatan Penerimaan -->
              <VCol cols="12" class="mt-2">
                <VTextField
                  v-model="notes"
                  label="Catatan Penerimaan Gudang (Opsional)"
                  placeholder="Misal: Barang diterima lengkap dan faktur sesuai..."
                  prepend-inner-icon="ri-edit-line"
                  density="comfortable"
                  variant="outlined"
                  hide-details
                />
              </VCol>

              <!-- Photo Previews Gallery -->
              <VCol v-if="photoPreviews.length > 0" cols="12" class="mt-3">
                <div class="text-caption font-weight-bold mb-2 text-primary d-flex align-center gap-1">
                  <VIcon icon="ri-image-line" size="16" />
                  Pratinjau Foto Lampiran ({{ photoPreviews.length }} Foto):
                </div>
                <div class="d-flex flex-wrap gap-3">
                  <div 
                    v-for="(preview, index) in photoPreviews" 
                    :key="index"
                    class="border rounded-lg overflow-hidden position-relative shadow-sm hover-scale"
                    style="width: 84px; height: 84px;"
                  >
                    <img
                      :src="preview"
                      alt="Preview"
                      style="width: 100%; height: 100%; object-fit: cover;"
                    >
                    <div
                      class="position-absolute bg-primary text-white text-caption px-1 rounded font-weight-bold"
                      style="top: 3px; left: 3px; line-height: 1.2; font-size: 10px;"
                    >
                      #{{ index + 1 }}
                    </div>
                  </div>
                </div>
              </VCol>
            </VRow>
          </div>

          <!-- Section 2: Ceklis & Verifikasi Fisik Barang -->
          <div class="mb-6">
            <div class="d-flex align-center justify-space-between mb-3 flex-wrap gap-2">
              <div>
                <h6 class="text-subtitle-1 font-weight-bold d-flex align-center gap-2 mb-0">
                  <VIcon icon="ri-checkbox-multiple-line" size="22" color="primary" />
                  2. Ceklis & Kalkulasi Faktur Barang Datang
                </h6>
                <span class="text-caption text-medium-emphasis">
                  Format angka Rupiah diformat langsung di input (HRG/@, Diskon 15%+5%, Netto & Kode SCC Aki).
                </span>
              </div>
              <div class="d-flex align-center gap-2">
                <VChip color="success" size="small" variant="flat" class="font-weight-bold">
                  Diterima: {{ totalReceivedCount }} Unit
                </VChip>
                <VChip v-if="totalRejectedCount > 0" color="error" size="small" variant="flat" class="font-weight-bold">
                  Diretur: {{ totalRejectedCount }} Unit
                </VChip>
              </div>
            </div>

            <!-- Alert if some items are rejected -->
            <VAlert
              v-if="hasRejectedItems"
              color="warning"
              variant="tonal"
              density="compact"
              class="mb-4 rounded-xl border border-warning"
              icon="ri-information-line"
            >
              <div class="font-weight-bold text-caption">
                Alur Retur Otomatis Aktif:
              </div>
              <div class="text-caption">
                Item yang <strong>tidak diceklis</strong> atau <strong>Qty berkurang/rusak</strong> akan otomatis dibuatkan <strong>Draft Dokumen Retur Pembelian ke Supplier</strong> dan <strong>SAMA SEKALI TIDAK AKAN MENAMBAH STOK FISIK CABANG</strong>.
              </div>
            </VAlert>

            <!-- Quick Preset Toolbar for Bulk Margins -->
            <div class="mb-4 pa-4 rounded-xl border bg-primary-lighten-5 border-primary border-opacity-25 shadow-xs">
              <div class="d-flex align-center justify-space-between flex-wrap gap-2 mb-2">
                <div class="d-flex align-center gap-2">
                  <VIcon icon="ri-scales-3-line" color="primary" size="20" />
                  <span class="text-subtitle-2 font-weight-bold text-primary">
                    Ketentuan Hukum & Margin Penjualan Toko (Auto Pricing):
                  </span>
                </div>
                <div class="d-flex align-center gap-2">
                  <VBtn
                    size="small"
                    color="primary"
                    variant="flat"
                    prepend-icon="ri-magic-line"
                    class="font-weight-bold shadow-xs"
                    @click="applyGlobalPercentages"
                  >
                    Terapkan Persen ke Semua Barang
                  </VBtn>
                </div>
              </div>
              <p class="text-caption text-medium-emphasis mb-3" style="font-size: 11px;">
                <em>Ketentuan Margin & Harga:</em> Menjaga margin keuntungan yang sehat dan mencegah penetapan harga di bawah modal. Anda dapat memilih persentase markup penjualan & batas nego di bawah ini:
              </p>
              <VRow dense align="center">
                <VCol cols="12" md="6">
                  <div class="d-flex align-center gap-2 flex-wrap">
                    <span class="text-caption font-weight-bold text-medium-emphasis">Preset Jual Normal:</span>
                    <VChip
                      v-for="p in [15, 20, 25, 30, 35, 40]"
                      :key="p"
                      size="small"
                      :color="globalMarkupPercent === p ? 'success' : 'default'"
                      :variant="globalMarkupPercent === p ? 'flat' : 'outlined'"
                      class="font-weight-bold cursor-pointer"
                      @click="() => { globalMarkupPercent = p; applyGlobalPercentages(); }"
                    >
                      +{{ p }}% {{ p === 25 ? '(Retail)' : (p === 15 ? '(Grosir)' : '') }}
                    </VChip>
                  </div>
                </VCol>
                <VCol cols="12" md="6">
                  <div class="d-flex align-center gap-2 flex-wrap justify-md-end">
                    <span class="text-caption font-weight-bold text-medium-emphasis">Preset Batas Nego:</span>
                    <VChip
                      v-for="n in [5, 10, 15, 20]"
                      :key="n"
                      size="small"
                      :color="globalMinNegoPercent === n ? 'warning' : 'default'"
                      :variant="globalMinNegoPercent === n ? 'flat' : 'outlined'"
                      class="font-weight-bold cursor-pointer"
                      @click="() => { globalMinNegoPercent = n; applyGlobalPercentages(); }"
                    >
                      +{{ n }}% {{ n === 10 ? '(Standar)' : (n === 5 ? '(Min)' : '') }}
                    </VChip>
                  </div>
                </VCol>
              </VRow>
            </div>

            <!-- Toolbar & Header Ceklist Barang -->
            <div class="d-flex justify-space-between align-center mb-3 flex-wrap gap-2">
              <div class="d-flex align-center gap-2">
                <VIcon icon="ri-checkbox-multiple-line" color="primary" size="20" />
                <span class="font-weight-bold text-subtitle-2 text-uppercase letter-spacing-1">
                  Daftar Barang & Cek Fisik Gudang
                </span>
              </div>
              <div class="d-flex gap-2 flex-wrap">
                <VBtn
                  size="x-small"
                  color="success"
                  variant="tonal"
                  prepend-icon="ri-checkbox-circle-line"
                  class="font-weight-bold"
                  @click="() => selectAllReceived(true)"
                >
                  Terima Semua Item
                </VBtn>
                <VBtn
                  size="x-small"
                  color="error"
                  variant="outlined"
                  prepend-icon="ri-close-circle-line"
                  class="font-weight-bold"
                  @click="() => selectAllReceived(false)"
                >
                  Tolak Semua / Retur
                </VBtn>
              </div>
            </div>

            <!-- Cards for Item Checklist -->
            <div class="d-flex flex-column gap-4">
              <div
                v-for="(item, index) in items"
                :key="index"
                class="border rounded-xl transition-all shadow-xs overflow-hidden"
                :class="item.is_received ? 'bg-var-theme-surface border-success' : 'bg-red-50 border-error'"
                style="border-width: 2px;"
              >
                <div class="pa-4">
                  <VRow align="center" dense>
                    <!-- Checkbox & Product Info -->
                    <VCol cols="12" sm="5" class="d-flex align-center gap-3">
                      <VTooltip text="Klik untuk mengubah status Terima Fisik atau Tolak/Retur" location="top">
                        <template #activator="{ props: tooltipProps }">
                          <VCheckbox
                            v-bind="tooltipProps"
                            v-model="item.is_received"
                            color="success"
                            hide-details
                            density="compact"
                            @update:model-value="val => onToggleItemReceived(item, val)"
                          />
                        </template>
                      </VTooltip>
                      <div>
                        <div class="font-weight-bold text-subtitle-2 d-flex align-center gap-2 flex-wrap">
                          {{ item.product_name }}
                          <VChip
                            :color="item.is_received ? 'success' : 'error'"
                            size="x-small"
                            variant="flat"
                            class="font-weight-bold cursor-pointer"
                            @click="() => onToggleItemReceived(item, !item.is_received)"
                          >
                            {{ item.is_received ? 'DITERIMA' : 'DITOLAK / RETUR' }}
                          </VChip>
                        </div>
                        <div class="text-caption text-medium-emphasis mt-1">
                          SKU: <code>{{ item.sku }}</code> | Kemasan: <strong>{{ item.unit_name }}</strong> (isi {{ item.conversion_qty }} pcs)
                        </div>
                      </div>
                    </VCol>

                    <!-- Qty Info -->
                    <VCol cols="6" sm="2" class="text-center">
                      <div class="text-caption text-medium-emphasis">Dipesan (PO)</div>
                      <div class="font-weight-bold text-subtitle-2">{{ item.ordered_qty }} {{ item.unit_name }}</div>
                    </VCol>

                    <VCol cols="6" sm="2">
                      <div class="text-caption font-weight-bold" :class="item.is_received ? 'text-success' : 'text-error'">
                        {{ item.is_received ? 'Qty Diterima Fisik' : 'Qty Retur / Ditolak' }}
                      </div>
                      <VTextField
                        v-if="item.is_received"
                        v-model.number="item.qty_received"
                        type="number"
                        min="0"
                        density="compact"
                        variant="outlined"
                        hide-details
                        class="mt-1"
                        @update:model-value="onQtyReceivedChange(item)"
                      />
                      <VTextField
                        v-else
                        v-model.number="item.qty_rejected"
                        type="number"
                        min="0"
                        density="compact"
                        variant="outlined"
                        hide-details
                        class="mt-1 text-error"
                      />
                    </VCol>

                    <VCol cols="12" sm="3" class="text-right d-flex align-center justify-end gap-2">
                      <div class="text-right">
                        <div class="text-caption text-medium-emphasis">Subtotal Netto</div>
                        <div class="text-h6 font-weight-bold font-mono" :class="item.is_received ? 'text-success' : 'text-error'">
                          {{ formatCurrency(calculateItemSubtotal(item)) }}
                        </div>
                      </div>
                      <VBtn
                        icon
                        size="small"
                        variant="text"
                        :color="item.show_details ? 'primary' : 'secondary'"
                        @click="item.show_details = !item.show_details"
                      >
                        <VIcon :icon="item.show_details ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" />
                      </VBtn>
                    </VCol>
                  </VRow>

                  <!-- Section Opsi & Alasan Retur ke Supplier jika item ditolak/parsial -->
                  <div
                    v-if="!item.is_received || item.qty_rejected > 0"
                    class="mt-3 pa-3 bg-red-50 border border-error border-opacity-25 rounded-lg"
                  >
                    <div class="d-flex align-center gap-1 font-weight-bold text-caption text-error mb-2">
                      <VIcon icon="ri-error-warning-line" size="16" />
                      Pilihan Retur & Tindakan Kompensasi Supplier (Qty: {{ item.qty_rejected }} {{ item.unit_name }}):
                    </div>
                    <VRow dense>
                      <VCol cols="12" sm="6">
                        <VSelect
                          v-model="item.rejection_reason"
                          label="Alasan Penolakan / Retur"
                          :items="[
                            'Barang Rusak / Cacat Fisik (Defect)',
                            'Barang Tidak Sesuai Pesanan / Spesifikasi',
                            'Jumlah Dikirim Kurang / Parsial',
                            'Kemasan Pecah / Bocor Saat Pengiriman',
                            'Mendekati / Melewati Tanggal Kedaluwarsa',
                            'Lainnya / Ditolak Saat Cek Fisik'
                          ]"
                          density="compact"
                          variant="outlined"
                          hide-details
                        />
                      </VCol>
                      <VCol cols="12" sm="6">
                        <VSelect
                          v-model="item.return_action"
                          label="Opsi Tindakan Kompensasi"
                          :items="[
                            { title: 'Tukar Barang Fisik yang Bagus (Replacement)', value: 'tukar_barang' },
                            { title: 'Potong Hutang / Faktur Tagihan (Nota Kredit)', value: 'potong_hutang' },
                            { title: 'Pengembalian Dana / Saldo Kas (Refund)', value: 'pengembalian_dana' }
                          ]"
                          density="compact"
                          variant="outlined"
                          hide-details
                        />
                      </VCol>
                      <VCol cols="12" class="mt-2">
                        <VTextField
                          v-model="item.rejection_notes"
                          label="Catatan Kerusakan / Keterangan Fisik Tambahan"
                          placeholder="Misal: Dus basah robek, botol pecah 2 pcs dari ekspedisi"
                          density="compact"
                          variant="outlined"
                          hide-details
                        />
                      </VCol>
                    </VRow>
                  </div>

                  <!-- Expandable Details (SCC, Batch, Capella Pricing Calculator) -->
                  <div v-show="item.show_details" class="mt-4 pt-4 border-t">
                    <!-- SCC & Batch Input Row -->
                    <VRow dense>
                      <VCol cols="12" sm="4">
                        <VTextField
                          v-model="item.scc_code"
                          label="Kode SCC (Serial Control Code Aki)"
                          placeholder="Misal: SCC-GS-98234"
                          density="compact"
                          variant="outlined"
                          prepend-inner-icon="ri-qr-code-line"
                        />
                      </VCol>

                      <VCol cols="12" sm="4">
                        <VTextField
                          v-model="item.batch_number"
                          label="Nomor Batch Produksi"
                          placeholder="Misal: BATCH-2026-08"
                          density="compact"
                          variant="outlined"
                          prepend-inner-icon="ri-hashtag"
                        />
                      </VCol>

                      <VCol cols="12" sm="4">
                        <VTextField
                          v-model="item.expiration_date"
                          type="date"
                          label="Tanggal Kedaluwarsa (Exp Date)"
                          density="compact"
                          variant="outlined"
                          prepend-inner-icon="ri-calendar-line"
                        />
                      </VCol>
                    </VRow>

                    <!-- Invoice & Discount Calculator (Capella Style) -->
                    <div class="text-caption font-weight-bold text-success mt-4 mb-2 d-flex align-center flex-wrap gap-1">
                      <VIcon icon="ri-calculator-line" size="18" />
                      Kalkulator Faktur Supplier Capella (HRG/@ Harga Bruto, Diskon 15%+5%, Netto):
                      <VChip
                        v-if="item.original_po_gross > 0 && Number(item.gross_price) !== Number(item.original_po_gross)"
                        size="x-small"
                        :color="Number(item.gross_price) > Number(item.original_po_gross) ? 'warning' : 'info'"
                        variant="tonal"
                        class="font-weight-bold ml-1"
                      >
                        {{ Number(item.gross_price) > Number(item.original_po_gross) ? 'Kenaikan Harga Faktur' : 'Penurunan Harga' }}
                        (PO: Rp {{ formatRupiahNumber(item.original_po_gross) }})
                      </VChip>
                    </div>
                    <VRow dense align="center">
                      <VCol cols="12" sm="3">
                        <VTextField
                          :model-value="item.gross_price_display"
                          label="HRG/@ (Harga Bruto Faktur)"
                          placeholder="Contoh: 1.085.000"
                          density="compact"
                          variant="outlined"
                          prefix="Rp"
                          @update:model-value="val => onGrossPriceInput(val, item)"
                        />
                      </VCol>

                      <VCol cols="12" sm="3">
                        <VTextField
                          v-model="item.discount_string"
                          label="DISCOUNT (%)"
                          placeholder="Contoh: 15+5"
                          density="compact"
                          variant="outlined"
                          prepend-inner-icon="ri-percent-line"
                          @update:model-value="val => onDiscountStringChange(val, item)"
                        />
                      </VCol>

                      <VCol cols="12" sm="3">
                        <VTextField
                          :model-value="item.discount_amount_display"
                          label="EXTRA DISCOUNT (Rp)"
                          placeholder="0"
                          density="compact"
                          variant="outlined"
                          prefix="Rp"
                          @update:model-value="val => onDiscountAmountInput(val, item)"
                        />
                      </VCol>

                      <VCol cols="12" sm="3">
                        <div class="pa-2 bg-success-lighten-5 border border-success border-opacity-25 rounded-lg text-center">
                          <div class="text-caption text-medium-emphasis">JUMLAH RP (Inc Ppn)</div>
                          <div class="font-weight-bold text-success text-subtitle-2">
                            {{ formatCurrency(calculateItemSubtotal(item)) }}
                          </div>
                        </div>
                      </VCol>
                    </VRow>

                    <!-- 3 Tingkatan Harga Sesuai Ketentuan Hukum & Margin Penjualan -->
                    <div class="mt-4 pa-4 rounded-xl border bg-var-theme-surface shadow-xs">
                      <div class="d-flex justify-space-between align-center mb-3 flex-wrap gap-2">
                        <span class="text-caption font-weight-bold text-primary d-flex align-center gap-1">
                          <VIcon icon="ri-price-tag-3-line" size="18" />
                          3 Tingkatan Harga Sesuai Ketentuan Hukum & Margin Penjualan:
                        </span>
                        <VChip size="x-small" color="primary" variant="tonal" class="font-weight-bold" prepend-icon="ri-flashlight-line">
                          Otomatis Masuk ke Inventori & POS
                        </VChip>
                      </div>

                      <VRow dense align="stretch">
                        <!-- 1. Modal HPP Real -->
                        <VCol cols="12" md="4">
                          <div class="pa-3 bg-red-50 border border-error border-opacity-25 rounded-lg text-center h-100 d-flex flex-column justify-center">
                            <div class="text-caption font-weight-bold text-error">1. MODAL REAL (HPP/Pcs)</div>
                            <div class="font-weight-bold text-error text-h6 font-mono mt-1">
                              {{ formatCurrency(calculateItemHppPerPcs(item)) }}
                            </div>
                            <div class="text-caption text-medium-emphasis" style="font-size: 10px;">
                              {{ tax_type === 'exclude' ? '(HPP + PPN 11% Faktur Exclude)' : '(Inc. Diskon Capella & PPN)' }}
                            </div>
                          </div>
                        </VCol>

                        <!-- 2. Harga Jual Normal POS -->
                        <VCol cols="12" md="4">
                          <div class="pa-3 bg-grey-50 border rounded-lg h-100 d-flex flex-column justify-space-between">
                            <div>
                              <div class="d-flex justify-space-between align-center mb-1">
                                <span class="text-caption font-weight-bold text-success">2. HARGA JUAL (NORMAL POS)</span>
                                <span v-if="calculateItemHppPerPcs(item) > 0" class="text-caption font-weight-bold text-success font-mono">
                                  +{{ item.markup_percent || 25 }}%
                                </span>
                              </div>
                              <!-- Preset Markup Chips -->
                              <div class="d-flex gap-1 flex-wrap mb-2">
                                <VChip
                                  v-for="p in [15, 20, 25, 30, 35, 40]"
                                  :key="p"
                                  size="x-small"
                                  :color="(item.markup_percent || 25) === p ? 'success' : 'default'"
                                  :variant="(item.markup_percent || 25) === p ? 'flat' : 'outlined'"
                                  class="cursor-pointer font-weight-medium"
                                  @click="applyMarkupPercent(item, p)"
                                >
                                  {{ p }}%
                                </VChip>
                              </div>
                            </div>

                            <VTextField
                              :model-value="item.price_display || (item.price ? formatRupiahNumber(item.price) : '')"
                              placeholder="0"
                              density="compact"
                              variant="outlined"
                              prefix="Rp"
                              hide-details
                              @update:model-value="val => onSellingPriceInput(val, item)"
                            />

                            <div v-if="(item.price || 0) > calculateItemHppPerPcs(item)" class="text-caption text-success font-weight-medium mt-1" style="font-size: 10.5px;">
                              Laba Untung: +{{ formatCurrency((item.price || 0) - calculateItemHppPerPcs(item)) }}
                            </div>
                          </div>
                        </VCol>

                        <!-- 3. Batas Nego Minimum Kasir -->
                        <VCol cols="12" md="4">
                          <div class="pa-3 bg-grey-50 border rounded-lg h-100 d-flex flex-column justify-space-between">
                            <div>
                              <div class="d-flex justify-space-between align-center mb-1">
                                <span class="text-caption font-weight-bold text-warning">3. BATAS NEGO MINIMUM (KASIR)</span>
                                <span v-if="calculateItemHppPerPcs(item) > 0" class="text-caption font-weight-bold text-warning font-mono">
                                  +{{ item.min_nego_percent || 10 }}%
                                </span>
                              </div>
                              <!-- Preset Nego Chips -->
                              <div class="d-flex gap-1 flex-wrap mb-2">
                                <VChip
                                  v-for="n in [5, 10, 15, 20]"
                                  :key="n"
                                  size="x-small"
                                  :color="(item.min_nego_percent || 10) === n ? 'warning' : 'default'"
                                  :variant="(item.min_nego_percent || 10) === n ? 'flat' : 'outlined'"
                                  class="cursor-pointer font-weight-medium"
                                  @click="applyMinNegoPercent(item, n)"
                                >
                                  {{ n }}%
                                </VChip>
                              </div>
                            </div>

                            <VTextField
                              :model-value="item.min_nego_price_display || (item.min_nego_price ? formatRupiahNumber(item.min_nego_price) : '')"
                              placeholder="0"
                              density="compact"
                              variant="outlined"
                              prefix="Rp"
                              hide-details
                              @update:model-value="val => onMinNegoPriceInput(val, item)"
                            />

                            <div v-if="(item.min_nego_price || 0) > calculateItemHppPerPcs(item)" class="text-caption text-warning font-weight-medium mt-1" style="font-size: 10.5px;">
                              Min. Laba: +{{ formatCurrency((item.min_nego_price || 0) - calculateItemHppPerPcs(item)) }} (Batas Kasir)
                            </div>
                          </div>
                        </VCol>
                      </VRow>

                      <!-- Education Legal Compliance Note -->
                      <div class="mt-3 pt-2 border-t d-flex align-center gap-1 text-caption text-medium-emphasis" style="font-size: 10.5px;">
                        <VIcon icon="ri-shield-check-line" size="14" color="success" />
                        <span><em>Kepatuhan Hukum:</em> Menjaga harga tidak melanggar larangan jual rugi bawah modal tanpa otorisasi (UU Perlindungan Konsumen & UU Persaingan Usaha Sehat).</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Section 3: Ringkasan Total Faktur Penerimaan (Capella Style) -->
          <div class="pa-5 bg-var-theme-surface border rounded-xl shadow-xs mb-6">
            <VRow align="center" justify="space-between">
              <VCol cols="12" md="6">
                <div class="text-caption text-medium-emphasis mb-2 font-weight-bold">
                  Status Rekapitulasi Fisik Gudang:
                </div>
                <div class="d-flex gap-3 flex-wrap mb-3">
                  <VChip color="success" variant="tonal" class="font-weight-bold">
                    <VIcon icon="ri-check-line" size="16" class="mr-1" />
                    {{ totalReceivedCount }} Unit Diterima
                  </VChip>
                  <VChip v-if="totalRejectedCount > 0" color="error" variant="tonal" class="font-weight-bold">
                    <VIcon icon="ri-close-line" size="16" class="mr-1" />
                    {{ totalRejectedCount }} Unit Diretur
                  </VChip>
                </div>
                <div class="text-caption text-medium-emphasis">
                  D P P: <strong class="text-body-2 font-mono">{{ formatCurrency(totalDpp) }}</strong> | 
                  P P N (11%): <strong class="text-body-2 font-mono">{{ formatCurrency(totalTax) }}</strong>
                </div>
              </VCol>

              <VCol cols="12" md="6">
                <div class="d-flex flex-column align-end">
                  <div class="text-caption text-medium-emphasis font-weight-bold">TOTAL (Inc Ppn):</div>
                  <div class="text-h4 font-weight-bold text-success font-mono">
                    {{ formatCurrency(grandTotal) }}
                  </div>
                </div>
              </VCol>
            </VRow>
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
              {{ isEditMode ? 'Simpan Revisi Dokumen Penerimaan' : 'Ajukan Penerimaan ke Kepala Divisi (Verifikasi Fisik)' }}
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
.bg-gradient-header {
  background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.05) 0%, rgba(var(--v-theme-surface), 1) 100%);
}
.hover-scale {
  transition: transform 0.2s;
}
.hover-scale:hover {
  transform: scale(1.05);
}
</style>
