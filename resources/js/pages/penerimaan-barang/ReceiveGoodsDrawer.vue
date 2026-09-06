<script setup>
import { ref, watch, nextTick, computed, onMounted } from 'vue'
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
  'update:is-drawer-open',
  'close',
  'cancel',
  'saveData',
])

const isFormValid = ref(false)
const refForm = ref()
const currentTab = ref('faktur') // 'faktur', 'physical', 'pricing', 'summary'

// Form Fields
const invoice_number_supplier = ref('')
const sales_name = ref('')
const checker_name = ref('')
const checker_employee_id = ref(null)
const date = ref(new Date().toISOString().substr(0, 10))
const received_date = ref(new Date().toISOString().substr(0, 10))
const due_date = ref('')
const notes = ref('')
const items = ref([])
const photos = ref([])
const photoPreviews = ref([])
const employees = ref([])

// Tax & Discount Settings
const tax_type = ref('include')
const tax_percentage = ref(11.00)
const extra_discount = ref(0)
const extra_discount_display = ref('0')

const isEditMode = computed(() => !!props.selectedGr)

const rejectionReasonOptions = [
  'Barang Cacat / Rusak Fisik (Defect)',
  'Barang Tidak Sesuai Pesanan / Spesifikasi',
  'Jumlah Dikirim Kurang / Parsial',
  'Kemasan Pecah / Bocor Saat Pengiriman',
  'Mendekati / Melewati Tanggal Kedaluwarsa',
  'Salah Kirim Produk oleh Supplier',
  'Lainnya / Ditolak Saat Cek Fisik',
]

// Fetch Employees List for Checker Selection
const fetchEmployeesList = async () => {
  try {
    const res = await $api('/apps/employees', { query: { itemsPerPage: 100 } })
    const list = Array.isArray(res) ? res : (res?.data || [])
    employees.value = list
  } catch (e) {
    console.error('Failed to load employees list:', e)
  }
}

onMounted(() => {
  fetchEmployeesList()
})

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
  emit('update:is-drawer-open', false)
  emit('close')
  emit('cancel')
  nextTick(() => {
    refForm.value?.resetValidation()
    invoice_number_supplier.value = ''
    sales_name.value = ''
    checker_name.value = ''
    checker_employee_id.value = null
    items.value = []
    photos.value = []
    photoPreviews.value.forEach(p => URL.revokeObjectURL(p))
    photoPreviews.value = []
    currentTab.value = 'faktur'
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
      item.rejection_reason = 'Barang Cacat / Rusak Fisik (Defect)'
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

// Watch Props for PO or GR change
watch([() => props.selectedPo, () => props.selectedGr], ([newPo, newGr]) => {
  if (newGr) {
    // Edit Mode
    invoice_number_supplier.value = newGr.invoice_number_supplier || newGr.purchase_order?.invoice_number_supplier || ''
    sales_name.value = newGr.sales_name || ''
    checker_name.value = newGr.checker_name || (newGr.checker_employee?.name || '')
    checker_employee_id.value = newGr.checker_employee_id || null
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
        }
        autoCalculatePrices(itm)
        return itm
      })
    }
  } else if (newPo && newPo.items) {
    // Create Mode
    invoice_number_supplier.value = newPo.invoice_number_supplier || ''
    sales_name.value = ''
    checker_name.value = ''
    checker_employee_id.value = null
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

const displayPo = computed(() => isEditMode.value ? props.selectedGr?.purchase_order : props.selectedPo)

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      // Check if any rejected item lacks a reason
      const unreasonedRejection = items.value.find(i => (!i.is_received || (Number(i.qty_rejected) || 0) > 0) && !i.rejection_reason)
      if (unreasonedRejection) {
        snackbar.show(`Mohon pilih Alasan Retur / Penolakan untuk barang "${unreasonedRejection.product_name}".`, 'error')
        currentTab.value = 'physical'
        return
      }

      // Resolve checker name and employee id
      let checkerEmployeeId = checker_employee_id.value
      let checkerName = ''

      if (typeof checker_name.value === 'object' && checker_name.value !== null) {
        checkerEmployeeId = checker_name.value.id || null
        checkerName = checker_name.value.name || ''
      } else {
        checkerName = String(checker_name.value || '').trim()
        const matchedEmp = employees.value.find(e => e.name?.toLowerCase() === checkerName.toLowerCase())
        if (matchedEmp) {
          checkerEmployeeId = matchedEmp.id
        }
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
      formData.append('checker_name', checkerName)
      if (checkerEmployeeId) formData.append('checker_employee_id', checkerEmployeeId)
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
    :width="$vuetify.display.xs ? '100%' : ($vuetify.display.smAndDown ? '95vw' : 1180)"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- Modern Header with Badges -->
    <div class="pa-5 bg-gradient-header border-b d-flex align-center justify-space-between">
      <div>
        <div class="d-flex align-center gap-2 flex-wrap">
          <VAvatar color="primary" variant="tonal" size="36" class="rounded-lg">
            <VIcon icon="ri-truck-line" size="20" />
          </VAvatar>
          <h5 class="text-h6 font-weight-bold mb-0 text-primary">
            {{ isEditMode ? 'Revisi Dokumen Penerimaan' : 'Penerimaan Fisik & Faktur Gudang' }}
          </h5>
          <VChip v-if="displayPo" color="primary" size="small" variant="flat" class="font-weight-bold font-mono">
            PO: {{ displayPo.po_number }}
          </VChip>
          <VChip v-if="displayPo?.supplier" color="secondary" size="small" variant="tonal" class="font-weight-medium">
            {{ displayPo.supplier.name }}
          </VChip>
        </div>
        <span class="text-caption text-medium-emphasis mt-1 d-block">
          SOP Terstruktur: 1. Faktur & Checker ➔ 2. Cek Fisik ➔ 3. Atur Margin ➔ 4. Rekapitulasi & Validasi.
        </span>
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

    <!-- 4-Step Segmented Navigation Bar -->
    <div class="px-5 py-2 border-b bg-var-theme-surface d-flex align-center gap-2 overflow-x-auto">
      <VBtn
        :color="currentTab === 'faktur' ? 'primary' : 'default'"
        :variant="currentTab === 'faktur' ? 'flat' : 'tonal'"
        size="small"
        rounded="lg"
        class="font-weight-bold text-none px-3"
        @click="currentTab = 'faktur'"
      >
        <VIcon icon="ri-file-text-line" size="16" class="me-1" />
        1. Faktur & Checker
      </VBtn>

      <VIcon icon="ri-arrow-right-s-line" size="16" class="text-medium-emphasis flex-shrink-0" />

      <VBtn
        :color="currentTab === 'physical' ? 'primary' : 'default'"
        :variant="currentTab === 'physical' ? 'flat' : 'tonal'"
        size="small"
        rounded="lg"
        class="font-weight-bold text-none px-3"
        @click="currentTab = 'physical'"
      >
        <VIcon icon="ri-checkbox-multiple-line" size="16" class="me-1" />
        2. Ceklis Fisik & Serial
        <VBadge
          v-if="totalRejectedCount > 0"
          color="error"
          content="Retur"
          inline
          class="ms-1"
        />
      </VBtn>

      <VIcon icon="ri-arrow-right-s-line" size="16" class="text-medium-emphasis flex-shrink-0" />

      <VBtn
        :color="currentTab === 'pricing' ? 'primary' : 'default'"
        :variant="currentTab === 'pricing' ? 'flat' : 'tonal'"
        size="small"
        rounded="lg"
        class="font-weight-bold text-none px-3"
        @click="currentTab = 'pricing'"
      >
        <VIcon icon="ri-price-tag-3-line" size="16" class="me-1" />
        3. Harga & Margin POS
      </VBtn>

      <VIcon icon="ri-arrow-right-s-line" size="16" class="text-medium-emphasis flex-shrink-0" />

      <VBtn
        :color="currentTab === 'summary' ? 'primary' : 'default'"
        :variant="currentTab === 'summary' ? 'flat' : 'tonal'"
        size="small"
        rounded="lg"
        class="font-weight-bold text-none px-3"
        @click="currentTab = 'summary'"
      >
        <VIcon icon="ri-file-list-3-line" size="16" class="me-1" />
        4. Rekap & Ajukan
      </VBtn>

      <!-- Live Total Indicator on the right -->
      <div class="ms-auto d-flex align-center gap-2 flex-shrink-0">
        <span class="text-caption text-medium-emphasis">Total:</span>
        <span class="font-weight-bold font-mono text-success text-subtitle-2">
          {{ formatCurrency(grandTotal) }}
        </span>
      </div>
    </div>

    <!-- Scrollable Body Content -->
    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat class="pa-5">
        <VForm
          ref="refForm"
          v-model="isFormValid"
          @submit.prevent="onSubmit"
        >
          <!-- Rejection Warning Alert (if editing a rejected GR) -->
          <VAlert
            v-if="props.selectedGr && props.selectedGr.approval_status === 'rejected'"
            type="error"
            variant="tonal"
            class="mb-5 pa-4 rounded-xl border-dashed"
            icon="ri-error-warning-fill"
          >
            <div class="font-weight-bold text-subtitle-2 mb-1">
              Dokumen Ini Ditolak / Diminta Revisi oleh Supervisor / Kepala Divisi:
            </div>
            <div class="text-body-2 bg-var-theme-surface pa-3 rounded border text-error font-weight-medium">
              "{{ props.selectedGr.rejection_reason || 'Silakan cek kembali kesesuaian fisik, rincian diskon, atau kejelasan foto faktur.' }}"
            </div>
          </VAlert>

          <!-- ============================================================== -->
          <!-- TAB 1: DATA FAKTUR & PETUGAS CHECKER                           -->
          <!-- ============================================================== -->
          <div v-show="currentTab === 'faktur'" class="tab-pane-content">
            <!-- Header Group 1: Supplier & PO Info Summary -->
            <div class="pa-4 mb-4 rounded-xl border bg-var-theme-surface d-flex align-center justify-space-between flex-wrap gap-3">
              <div class="d-flex align-center gap-3">
                <VAvatar color="primary" variant="tonal" size="42" class="rounded-lg">
                  <VIcon icon="ri-store-2-line" size="22" />
                </VAvatar>
                <div>
                  <div class="text-caption text-medium-emphasis">Vendor / Supplier Pemasok:</div>
                  <div class="font-weight-bold text-subtitle-2 text-primary">
                    {{ displayPo?.supplier?.name || 'PT. CAPELLA PATRIA UTAMA' }}
                  </div>
                </div>
              </div>

              <div class="d-flex align-center gap-4 flex-wrap">
                <div>
                  <div class="text-caption text-medium-emphasis">Cabang Tujuan:</div>
                  <div class="font-weight-bold text-body-2">{{ displayPo?.branch?.name || '-' }}</div>
                </div>
                <div>
                  <div class="text-caption text-medium-emphasis">Tanggal PO:</div>
                  <div class="font-weight-bold font-mono text-body-2">{{ displayPo?.date ? displayPo.date.substring(0, 10) : '-' }}</div>
                </div>
                <div>
                  <div class="text-caption text-medium-emphasis">Jumlah Item:</div>
                  <div class="font-weight-bold text-body-2">{{ items.length }} Produk</div>
                </div>
              </div>
            </div>

            <!-- Card: Form Input Faktur & Checker -->
            <VCard class="border rounded-xl pa-5 mb-4 shadow-xs">
              <div class="d-flex align-center gap-2 mb-4 pb-2 border-b">
                <VIcon icon="ri-shield-user-line" color="primary" size="20" />
                <h6 class="text-subtitle-1 font-weight-bold mb-0">
                  Data Faktur & Karyawan Checker Barang
                </h6>
              </div>

              <VRow dense>
                <!-- No. Faktur Supplier -->
                <VCol cols="12" sm="6" md="4">
                  <VTextField
                    v-model="invoice_number_supplier"
                    label="No. Faktur / Kuitansi Supplier *"
                    placeholder="Contoh: FK.202608.01875"
                    prepend-inner-icon="ri-bill-line"
                    density="comfortable"
                    variant="outlined"
                    :rules="[v => !!v || 'Nomor faktur supplier wajib diisi']"
                  />
                </VCol>

                <!-- Nama Sales Supplier -->
                <VCol cols="12" sm="6" md="4">
                  <VTextField
                    v-model="sales_name"
                    label="Nama Sales Supplier"
                    placeholder="Contoh: Bpk. Hendra / Capella"
                    prepend-inner-icon="ri-user-star-line"
                    density="comfortable"
                    variant="outlined"
                  />
                </VCol>

                <!-- KARYAWAN CHECKER (Pengecek Fisik Barang) -->
                <VCol cols="12" sm="12" md="4">
                  <VCombobox
                    v-model="checker_name"
                    :items="employees"
                    item-title="name"
                    item-value="name"
                    label="Karyawan Checker (Pengecek Fisik) *"
                    placeholder="Pilih karyawan atau ketik nama..."
                    prepend-inner-icon="ri-user-search-line"
                    density="comfortable"
                    variant="outlined"
                    clearable
                    :rules="[v => !!v || 'Karyawan checker wajib diisi']"
                    persistent-hint
                    hint="Staf gudang yang memeriksa fisik barang datang"
                  >
                    <template #item="{ item, props: itemProps }">
                      <VListItem v-bind="itemProps" :subtitle="item.raw.branch?.name ? `Cabang: ${item.raw.branch.name}` : ''">
                        <template #prepend>
                          <VAvatar size="26" color="primary" variant="tonal" class="text-caption font-weight-bold me-2">
                            {{ (item.raw.name || 'C').charAt(0) }}
                          </VAvatar>
                        </template>
                      </VListItem>
                    </template>
                  </VCombobox>
                </VCol>

                <!-- Tanggal Barang Sampai -->
                <VCol cols="12" sm="6" md="3" class="mt-2">
                  <VTextField
                    v-model="received_date"
                    type="date"
                    :rules="[v => !!v || 'Tanggal barang sampai wajib diisi']"
                    label="Tanggal Barang Sampai *"
                    prepend-inner-icon="ri-calendar-check-line"
                    density="comfortable"
                    variant="outlined"
                  />
                </VCol>

                <!-- Tanggal Jatuh Tempo Faktur -->
                <VCol cols="12" sm="6" md="5" class="mt-2">
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
                    <VChip size="x-small" variant="tonal" color="secondary" class="cursor-pointer" @click="setDueDateOffset(0)">Cash / Tunai</VChip>
                    <VChip size="x-small" variant="tonal" color="primary" class="cursor-pointer" @click="setDueDateOffset(14)">+14 Hari</VChip>
                    <VChip size="x-small" variant="tonal" color="primary" class="cursor-pointer" @click="setDueDateOffset(30)">+30 Hari</VChip>
                    <VChip size="x-small" variant="tonal" color="primary" class="cursor-pointer" @click="setDueDateOffset(45)">+45 Hari</VChip>
                    <VChip size="x-small" variant="tonal" color="primary" class="cursor-pointer" @click="setDueDateOffset(60)">+60 Hari</VChip>
                  </div>
                </VCol>

                <!-- Perlakuan PPN -->
                <VCol cols="12" sm="6" md="4" class="mt-2">
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

                <!-- Tarif PPN % (Jika Non-PPN tidak aktif) -->
                <VCol v-if="tax_type !== 'none'" cols="12" sm="6" md="3" class="mt-2">
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

                <!-- Diskon Tambahan Faktur -->
                <VCol cols="12" sm="6" md="4" class="mt-2">
                  <VTextField
                    :model-value="extra_discount_display"
                    label="Diskon Tambahan / Ekstra Faktur (Rp)"
                    placeholder="0"
                    prefix="Rp"
                    density="comfortable"
                    variant="outlined"
                    @update:model-value="onExtraDiscountInput"
                  />
                </VCol>

                <!-- Foto Faktur / Surat Jalan Fisik -->
                <VCol cols="12" sm="12" :md="tax_type !== 'none' ? 5 : 8" class="mt-2">
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

                <!-- Catatan Penerimaan -->
                <VCol cols="12" class="mt-2">
                  <VTextField
                    v-model="notes"
                    label="Catatan Penerimaan Gudang (Opsional)"
                    placeholder="Misal: Barang diterima lengkap oleh staf checker dan faktur asli terlampir..."
                    prepend-inner-icon="ri-edit-line"
                    density="comfortable"
                    variant="outlined"
                  />
                </VCol>

                <!-- Photo Previews Gallery -->
                <VCol v-if="photoPreviews.length > 0" cols="12" class="mt-2">
                  <div class="text-caption font-weight-bold mb-2 text-primary d-flex align-center gap-1">
                    <VIcon icon="ri-image-line" size="16" />
                    Pratinjau Foto Lampiran ({{ photoPreviews.length }} Foto):
                  </div>
                  <div class="d-flex flex-wrap gap-3">
                    <div 
                      v-for="(preview, index) in photoPreviews" 
                      :key="index"
                      class="border rounded-lg overflow-hidden position-relative shadow-sm"
                      style="width: 80px; height: 80px;"
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
            </VCard>

            <!-- Bottom Navigation for Tab 1 -->
            <div class="d-flex justify-end mt-4">
              <VBtn
                color="primary"
                size="large"
                append-icon="ri-arrow-right-line"
                class="font-weight-bold rounded-lg shadow-sm"
                @click="currentTab = 'physical'"
              >
                Lanjut ke Ceklis Fisik Barang (Tahap 2)
              </VBtn>
            </div>
          </div>

          <!-- ============================================================== -->
          <!-- TAB 2: CEKLIS FISIK & NOMOR SERI SCC / BATCH                   -->
          <!-- ============================================================== -->
          <div v-show="currentTab === 'physical'" class="tab-pane-content">
            <!-- Toolbar Ceklis Fisik -->
            <div class="pa-4 mb-4 rounded-xl border bg-var-theme-surface d-flex align-center justify-space-between flex-wrap gap-3">
              <div>
                <h6 class="text-subtitle-1 font-weight-bold mb-0 d-flex align-center gap-2">
                  <VIcon icon="ri-checkbox-multiple-line" color="primary" size="20" />
                  Pemeriksaan Fisik Barang & Nomor Seri
                </h6>
                <span class="text-caption text-medium-emphasis">
                  Cocokkan kuantitas fisik yang tiba di gudang. Item yang ditolak/rusak otomatis masuk draft retur.
                </span>
              </div>

              <div class="d-flex align-center gap-2 flex-wrap">
                <VChip color="success" variant="flat" size="small" class="font-weight-bold">
                  {{ totalReceivedCount }} Unit Diterima
                </VChip>
                <VChip v-if="totalRejectedCount > 0" color="error" variant="flat" size="small" class="font-weight-bold">
                  {{ totalRejectedCount }} Unit Diretur
                </VChip>

                <VBtn
                  size="small"
                  color="success"
                  variant="tonal"
                  prepend-icon="ri-checkbox-circle-line"
                  class="font-weight-bold ms-2"
                  @click="() => selectAllReceived(true)"
                >
                  Terima Semua
                </VBtn>
                <VBtn
                  size="small"
                  color="error"
                  variant="outlined"
                  prepend-icon="ri-close-circle-line"
                  class="font-weight-bold"
                  @click="() => selectAllReceived(false)"
                >
                  Tolak Semua
                </VBtn>
              </div>
            </div>

            <!-- List Item Cards for Physical Check -->
            <div class="d-flex flex-column gap-3 mb-4">
              <VCard
                v-for="(item, index) in items"
                :key="index"
                class="border rounded-xl pa-4 transition-all shadow-xs"
                :class="item.is_received ? 'bg-var-theme-surface border-success' : 'bg-red-50 border-error'"
                style="border-width: 1.5px;"
              >
                <VRow dense align="center">
                  <!-- Checkbox & Product Info -->
                  <VCol cols="12" md="4" class="d-flex align-center gap-3">
                    <VCheckbox
                      v-model="item.is_received"
                      color="success"
                      hide-details
                      density="compact"
                      @update:model-value="val => onToggleItemReceived(item, val)"
                    />
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
                        SKU: <code>{{ item.sku }}</code> | Satuan: <strong>{{ item.unit_name }}</strong> (isi {{ item.conversion_qty }} pcs)
                      </div>
                    </div>
                  </VCol>

                  <!-- Qty PO & Received -->
                  <VCol cols="6" sm="3" md="2" class="text-center">
                    <div class="text-caption text-medium-emphasis">Dipesan (PO)</div>
                    <div class="font-weight-bold text-subtitle-2">{{ item.ordered_qty }} {{ item.unit_name }}</div>
                  </VCol>

                  <VCol cols="6" sm="3" md="2">
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

                  <!-- Physical Codes (SCC, Batch, Exp Date) -->
                  <VCol cols="12" md="4">
                    <div class="d-flex gap-2">
                      <VTextField
                        v-model="item.scc_code"
                        label="Kode SCC (Aki)"
                        placeholder="SCC-123"
                        density="compact"
                        variant="outlined"
                        hide-details
                        prepend-inner-icon="ri-qr-code-line"
                      />
                      <VTextField
                        v-model="item.batch_number"
                        label="No. Batch"
                        placeholder="BATCH-01"
                        density="compact"
                        variant="outlined"
                        hide-details
                      />
                      <VTextField
                        v-model="item.expiration_date"
                        type="date"
                        label="Exp Date"
                        density="compact"
                        variant="outlined"
                        hide-details
                      />
                    </div>
                  </VCol>
                </VRow>

                <!-- Return Option Box (if item is rejected or partial) -->
                <div
                  v-if="!item.is_received || item.qty_rejected > 0"
                  class="mt-3 pa-3 bg-red-50 border border-error border-opacity-25 rounded-lg"
                >
                  <div class="d-flex align-center gap-1 font-weight-bold text-caption text-error mb-2">
                    <VIcon icon="ri-error-warning-line" size="16" />
                    Rincian Retur ke Supplier (Qty: {{ item.qty_rejected }} {{ item.unit_name }}):
                  </div>
                  <VRow dense>
                    <VCol cols="12" sm="4">
                      <VSelect
                        v-model="item.rejection_reason"
                        label="Alasan Penolakan / Retur *"
                        :items="rejectionReasonOptions"
                        density="compact"
                        variant="outlined"
                        hide-details
                      />
                    </VCol>
                    <VCol cols="12" sm="4">
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
                    <VCol cols="12" sm="4">
                      <VTextField
                        v-model="item.rejection_notes"
                        label="Catatan Kerusakan Fisik"
                        placeholder="Misal: Dus basah robek, segel rusak"
                        density="compact"
                        variant="outlined"
                        hide-details
                      />
                    </VCol>
                  </VRow>
                </div>
              </VCard>
            </div>

            <!-- Bottom Navigation for Tab 2 -->
            <div class="d-flex justify-space-between mt-4">
              <VBtn
                variant="tonal"
                color="secondary"
                size="large"
                prepend-icon="ri-arrow-left-line"
                @click="currentTab = 'faktur'"
              >
                Kembali ke Data Faktur
              </VBtn>

              <VBtn
                color="primary"
                size="large"
                append-icon="ri-arrow-right-line"
                class="font-weight-bold rounded-lg shadow-sm"
                @click="currentTab = 'pricing'"
              >
                Lanjut ke Atur Harga & Margin (Tahap 3)
              </VBtn>
            </div>
          </div>

          <!-- ============================================================== -->
          <!-- TAB 3: HARGA FAKTUR & MARGIN PENJUALAN POS (AUTO PRICING)     -->
          <!-- ============================================================== -->
          <div v-show="currentTab === 'pricing'" class="tab-pane-content">
            <!-- Global Margin Preset Card -->
            <div class="pa-4 mb-4 rounded-xl border bg-primary-lighten-5 border-primary border-opacity-25 shadow-xs">
              <div class="d-flex align-center justify-space-between flex-wrap gap-2 mb-2">
                <div class="d-flex align-center gap-2">
                  <VIcon icon="ri-magic-line" color="primary" size="20" />
                  <span class="text-subtitle-2 font-weight-bold text-primary">
                    Preset Cepat Margin Penjualan (Auto Pricing):
                  </span>
                </div>
                <VBtn
                  size="small"
                  color="primary"
                  variant="flat"
                  prepend-icon="ri-check-double-line"
                  class="font-weight-bold shadow-xs"
                  @click="applyGlobalPercentages"
                >
                  Terapkan Persen ke Semua Barang
                </VBtn>
              </div>

              <VRow dense align="center">
                <VCol cols="12" md="6">
                  <div class="d-flex align-center gap-2 flex-wrap">
                    <span class="text-caption font-weight-bold text-medium-emphasis">Markup Harga Jual POS:</span>
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
                    <span class="text-caption font-weight-bold text-medium-emphasis">Batas Nego Minimum:</span>
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

            <!-- Pricing Matrix List per Item -->
            <div class="d-flex flex-column gap-4 mb-4">
              <VCard
                v-for="(item, index) in items"
                :key="index"
                class="border rounded-xl pa-4 shadow-xs"
              >
                <!-- Item Title & Cost Header -->
                <div class="d-flex justify-space-between align-center mb-3 pb-2 border-b flex-wrap gap-2">
                  <div>
                    <span class="font-weight-bold text-subtitle-2 text-primary">{{ item.product_name }}</span>
                    <span class="text-caption text-medium-emphasis ms-2">({{ item.qty_received }} {{ item.unit_name }} diterima)</span>
                  </div>
                  <div class="d-flex align-center gap-2">
                    <span class="text-caption text-medium-emphasis">Subtotal Netto:</span>
                    <span class="font-weight-bold font-mono text-success text-subtitle-2">{{ formatCurrency(calculateItemSubtotal(item)) }}</span>
                  </div>
                </div>

                <!-- 1. Kalkulator Diskon Faktur Capella -->
                <div class="pa-3 bg-grey-50 rounded-lg border mb-3">
                  <div class="text-caption font-weight-bold text-slate-700 mb-2 d-flex align-center gap-1">
                    <VIcon icon="ri-calculator-line" size="16" color="primary" />
                    Kalkulator Diskon Faktur Supplier:
                  </div>
                  <VRow dense align="center">
                    <VCol cols="12" sm="4">
                      <VTextField
                        :model-value="item.gross_price_display"
                        label="HRG/@ (Harga Bruto Faktur)"
                        placeholder="0"
                        density="compact"
                        variant="outlined"
                        prefix="Rp"
                        hide-details
                        @update:model-value="val => onGrossPriceInput(val, item)"
                      />
                    </VCol>

                    <VCol cols="12" sm="4">
                      <VTextField
                        v-model="item.discount_string"
                        label="DISCOUNT (%)"
                        placeholder="15+5"
                        density="compact"
                        variant="outlined"
                        prepend-inner-icon="ri-percent-line"
                        hide-details
                        @update:model-value="val => onDiscountStringChange(val, item)"
                      />
                    </VCol>

                    <VCol cols="12" sm="4">
                      <VTextField
                        :model-value="item.discount_amount_display"
                        label="EXTRA DISCOUNT (Rp)"
                        placeholder="0"
                        density="compact"
                        variant="outlined"
                        prefix="Rp"
                        hide-details
                        @update:model-value="val => onDiscountAmountInput(val, item)"
                      />
                    </VCol>
                  </VRow>
                </div>

                <!-- 2. Tiga Tingkatan Harga (HPP, Jual POS, Batas Nego) -->
                <VRow dense align="stretch">
                  <!-- HPP Real Modal Toko -->
                  <VCol cols="12" md="4">
                    <div class="pa-3 bg-red-50 border border-error border-opacity-25 rounded-lg text-center h-100 d-flex flex-column justify-center">
                      <div class="text-caption font-weight-bold text-error">1. MODAL REAL (HPP/Pcs)</div>
                      <div class="font-weight-bold text-error text-h6 font-mono mt-1">
                        {{ formatCurrency(calculateItemHppPerPcs(item)) }}
                      </div>
                      <div class="text-caption text-medium-emphasis" style="font-size: 10px;">
                        {{ tax_type === 'exclude' ? '(HPP + PPN 11% Exclude)' : '(Inc. Diskon & PPN)' }}
                      </div>
                    </div>
                  </VCol>

                  <!-- Harga Jual Normal POS -->
                  <VCol cols="12" md="4">
                    <div class="pa-3 bg-green-50 border border-success border-opacity-25 rounded-lg h-100 d-flex flex-column justify-space-between">
                      <div>
                        <div class="d-flex justify-space-between align-center mb-1">
                          <span class="text-caption font-weight-bold text-success">2. HARGA JUAL (NORMAL POS)</span>
                          <span v-if="calculateItemHppPerPcs(item) > 0" class="text-caption font-weight-bold text-success font-mono">
                            +{{ item.markup_percent || 25 }}%
                          </span>
                        </div>
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

                  <!-- Batas Nego Minimum Kasir -->
                  <VCol cols="12" md="4">
                    <div class="pa-3 bg-amber-50 border border-warning border-opacity-25 rounded-lg h-100 d-flex flex-column justify-space-between">
                      <div>
                        <div class="d-flex justify-space-between align-center mb-1">
                          <span class="text-caption font-weight-bold text-warning">3. BATAS NEGO MINIMUM (KASIR)</span>
                          <span v-if="calculateItemHppPerPcs(item) > 0" class="text-caption font-weight-bold text-warning font-mono">
                            +{{ item.min_nego_percent || 10 }}%
                          </span>
                        </div>
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
                        Min. Laba: +{{ formatCurrency((item.min_nego_price || 0) - calculateItemHppPerPcs(item)) }} (Batas Bawah)
                      </div>
                    </div>
                  </VCol>
                </VRow>
              </VCard>
            </div>

            <!-- Bottom Navigation for Tab 3 -->
            <div class="d-flex justify-space-between mt-4">
              <VBtn
                variant="tonal"
                color="secondary"
                size="large"
                prepend-icon="ri-arrow-left-line"
                @click="currentTab = 'physical'"
              >
                Kembali ke Ceklis Fisik
              </VBtn>

              <VBtn
                color="primary"
                size="large"
                append-icon="ri-arrow-right-line"
                class="font-weight-bold rounded-lg shadow-sm"
                @click="currentTab = 'summary'"
              >
                Lihat Rekapitulasi & Submit (Tahap 4)
              </VBtn>
            </div>
          </div>

          <!-- ============================================================== -->
          <!-- TAB 4: REKAPITULASI & PENGAJUAN DOKUMEN                         -->
          <!-- ============================================================== -->
          <div v-show="currentTab === 'summary'" class="tab-pane-content">
            <!-- Header Ringkasan Info Dokumen -->
            <div class="pa-4 mb-4 rounded-xl border bg-var-theme-surface shadow-xs">
              <div class="d-flex justify-space-between align-center mb-3 pb-3 border-b flex-wrap gap-2">
                <div>
                  <h6 class="text-subtitle-1 font-weight-bold text-primary mb-0">
                    {{ displayPo?.supplier?.name || 'PT. CAPELLA PATRIA UTAMA' }}
                  </h6>
                  <span class="text-caption text-medium-emphasis">
                    No. Faktur: <strong>{{ invoice_number_supplier || '-' }}</strong> | Sales: <strong>{{ sales_name || '-' }}</strong>
                  </span>
                </div>
                <div>
                  <VChip :color="isEditMode ? 'warning' : 'primary'" size="small" variant="flat" class="font-weight-bold">
                    {{ isEditMode ? 'REVISI PENERIMAAN' : 'DRAFT PENERIMAAN BARANG' }}
                  </VChip>
                </div>
              </div>

              <VRow dense>
                <VCol cols="12" sm="3">
                  <div class="text-caption text-medium-emphasis">Karyawan Checker:</div>
                  <div class="font-weight-bold text-primary">
                    <VIcon icon="ri-user-search-line" size="14" class="me-1" />
                    {{ typeof checker_name === 'object' ? (checker_name?.name || '-') : (checker_name || '-') }}
                  </div>
                </VCol>
                <VCol cols="12" sm="3">
                  <div class="text-caption text-medium-emphasis">Cabang Tujuan:</div>
                  <div class="font-weight-bold text-truncate">{{ displayPo?.branch?.name || '-' }}</div>
                </VCol>
                <VCol cols="12" sm="3">
                  <div class="text-caption text-medium-emphasis">Tgl Barang Sampai:</div>
                  <div class="font-weight-bold">{{ received_date || '-' }}</div>
                </VCol>
                <VCol cols="12" sm="3">
                  <div class="text-caption text-medium-emphasis">Jatuh Tempo:</div>
                  <div class="font-weight-bold font-mono">{{ due_date || 'Tunai / Selesai' }}</div>
                </VCol>
              </VRow>
            </div>

            <!-- Ringkasan Item Table -->
            <div class="border rounded-xl overflow-hidden mb-4">
              <table class="w-100 table-receipt">
                <thead>
                  <tr class="bg-grey-100 text-left">
                    <th class="pa-3 text-xs text-center" style="width: 40px;">NO</th>
                    <th class="pa-3 text-xs">NAMA BARANG & SERIAL</th>
                    <th class="pa-3 text-xs text-center" style="width: 80px;">QTY FISIK</th>
                    <th class="pa-3 text-xs text-right" style="width: 120px;">HPP MODAL</th>
                    <th class="pa-3 text-xs text-right" style="width: 120px;">HARGA JUAL</th>
                    <th class="pa-3 text-xs text-right" style="width: 130px;">SUBTOTAL NETTO</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(item, idx) in items"
                    :key="idx"
                    class="border-b"
                    :class="!item.is_received || item.qty_received === 0 ? 'bg-red-50' : ''"
                  >
                    <td class="pa-3 text-xs text-center">{{ idx + 1 }}</td>
                    <td class="pa-3 text-xs">
                      <div class="font-weight-bold">{{ item.product_name }}</div>
                      <div class="text-caption text-medium-emphasis d-flex gap-2 flex-wrap">
                        <span v-if="item.scc_code" class="text-primary font-weight-medium">SCC: <code>{{ item.scc_code }}</code></span>
                        <span v-if="item.batch_number">Batch: <code>{{ item.batch_number }}</code></span>
                        <span v-if="item.expiration_date">Exp: {{ item.expiration_date }}</span>
                      </div>
                      <div v-if="!item.is_received || item.qty_rejected > 0" class="text-caption text-error font-weight-bold mt-1">
                        Diretur ({{ item.qty_rejected }} unit): {{ item.rejection_reason || 'Ditolak fisik' }}
                      </div>
                    </td>
                    <td class="pa-3 text-xs text-center font-weight-bold" :class="item.qty_received > 0 ? 'text-success' : 'text-error'">
                      {{ item.qty_received }} {{ item.unit_name }}
                    </td>
                    <td class="pa-3 text-xs text-right font-mono font-weight-medium text-error">
                      {{ formatCurrency(calculateItemHppPerPcs(item)) }}
                    </td>
                    <td class="pa-3 text-xs text-right font-mono font-weight-bold text-success">
                      {{ formatCurrency(item.price || 0) }}
                    </td>
                    <td class="pa-3 text-xs text-right font-mono font-weight-bold">
                      {{ formatCurrency(calculateItemSubtotal(item)) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Grand Financial Summary Card -->
            <div class="pa-5 bg-var-theme-surface border rounded-xl shadow-xs mb-6">
              <VRow align="center" justify="space-between">
                <VCol cols="12" md="6">
                  <div class="text-caption text-medium-emphasis mb-2 font-weight-bold">
                    Rekapitulasi Fisik & Pajak Gudang:
                  </div>
                  <div class="d-flex gap-2 flex-wrap mb-3">
                    <VChip color="success" variant="tonal" class="font-weight-bold">
                      <VIcon icon="ri-check-line" size="16" class="me-1" />
                      {{ totalReceivedCount }} Unit Diterima
                    </VChip>
                    <VChip v-if="totalRejectedCount > 0" color="error" variant="tonal" class="font-weight-bold">
                      <VIcon icon="ri-close-line" size="16" class="me-1" />
                      {{ totalRejectedCount }} Unit Diretur
                    </VChip>
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    D P P : <strong class="text-body-2 font-mono">{{ formatCurrency(totalDpp) }}</strong> | 
                    P P N ({{ tax_percentage }}%) : <strong class="text-body-2 font-mono">{{ formatCurrency(totalTax) }}</strong>
                  </div>
                </VCol>

                <VCol cols="12" md="6">
                  <div class="d-flex flex-column align-end">
                    <div class="text-caption text-medium-emphasis font-weight-bold">TOTAL FAKTUR (Inc Ppn):</div>
                    <div class="text-h4 font-weight-bold text-success font-mono">
                      {{ formatCurrency(grandTotal) }}
                    </div>
                  </div>
                </VCol>
              </VRow>
            </div>

            <!-- Final Submit Action Bar -->
            <div class="d-flex align-center gap-3 pt-2">
              <VBtn
                variant="tonal"
                color="secondary"
                size="large"
                prepend-icon="ri-arrow-left-line"
                @click="currentTab = 'pricing'"
              >
                Kembali ke Atur Harga
              </VBtn>

              <VBtn
                type="submit"
                color="primary"
                size="large"
                prepend-icon="ri-send-plane-fill"
                class="font-weight-bold flex-grow-1 rounded-lg shadow-sm"
              >
                {{ isEditMode ? 'Simpan Revisi Penerimaan Barang' : 'Simpan & Ajukan Penerimaan Barang' }}
              </VBtn>
            </div>
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
.table-receipt {
  border-collapse: collapse;
}
.table-receipt th {
  border-bottom: 1px solid rgba(var(--v-border-color), 0.15);
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.table-receipt td {
  padding: 8px 12px;
}
</style>
