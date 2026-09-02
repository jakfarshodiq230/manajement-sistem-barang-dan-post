<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import PayableReceiptPrinter from './PayableReceiptPrinter.vue'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  statementId: {
    type: [Number, String],
    default: null,
  },
})

const emit = defineEmits(['update:isDrawerOpen', 'paymentRecorded', 'paymentVoided'])

const snackbar = useSnackbarStore()
const isLoading = ref(false)
const isSubmitting = ref(false)
const statement = ref(null)

// Kuitansi & Printer Settings from Database
const receiptPrinterRef = ref(null)
const selectedPaymentForPrint = ref(null)
const receiptSettings = ref([])
const activeReceiptSetting = computed(() => {
  if (receiptSettings.value.length === 0) return null
  return receiptSettings.value.find(s => s.is_default) || receiptSettings.value[0]
})

const fetchReceiptSettings = async () => {
  try {
    const res = await $api('/apps/receipt-settings')
    receiptSettings.value = res.data || res || []
  } catch (e) {
    console.error('Failed to load receipt settings:', e)
  }
}

const printPaymentReceipt = payment => {
  selectedPaymentForPrint.value = payment
  setTimeout(() => {
    if (receiptPrinterRef.value?.print) {
      receiptPrinterRef.value.print()
    }
  }, 100)
}

// Item Checklist State
const selectedItemIds = ref([])

// Payment Form State
const isPaymentFormVisible = ref(false)
const paymentAmount = ref(0)
const paymentAmountDisplay = ref('')
const paymentDate = ref(new Date().toISOString().substring(0, 10))
const paymentMethod = ref('bank_transfer')
const bankName = ref('')
const bankAccountNumber = ref('')
const bankAccountName = ref('')
const referenceNumber = ref('')
const paymentNotes = ref('')
const proofFile = ref(null)
const proofPreview = ref(null)
const supplierCredits = ref([])
const selectedSupplierCreditId = ref(null)
const bankAccounts = ref([])
const selectedBankAccountId = ref(null)

const selectedBankAccount = computed(() => {
  return bankAccounts.value.find(b => b.id === selectedBankAccountId.value) || null
})

const fetchBankAccounts = async () => {
  try {
    const res = await $api('/apps/bank-accounts', {
      params: {
        is_active: true,
        branch_id: statement.value?.branch_id || undefined,
      },
    })
    bankAccounts.value = res.data || res || []
    if (bankAccounts.value.length > 0 && !selectedBankAccountId.value) {
      const defaultBank = bankAccounts.value.find(b => b.is_default) || bankAccounts.value[0]
      if (defaultBank) {
        onSelectBankAccount(defaultBank.id)
      }
    }
  } catch (e) {
    console.error('Failed to load bank accounts:', e)
  }
}

const onSelectBankAccount = accountId => {
  selectedBankAccountId.value = accountId
  const found = bankAccounts.value.find(b => b.id === accountId)
  if (found) {
    bankName.value = found.bank_name
    bankAccountNumber.value = found.account_number || ''
    bankAccountName.value = found.account_name || ''
  }
}

const expandedPayableId = ref(null)

const formatCurrency = value => {
  if (!value || isNaN(value)) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)
}

const formatDate = dateString => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const formatDateRange = (start, end) => {
  if (!start || !end) return '-'
  const dStart = new Date(start).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
  const dEnd = new Date(end).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
  return `${dStart} - ${dEnd}`
}

const formatMonthLabel = periodMonth => {
  if (!periodMonth) return '-'
  const [year, month] = periodMonth.split('-')
  const date = new Date(year, parseInt(month) - 1, 1)
  return date.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
}

const formatInputRupiah = value => {
  if (value === null || value === undefined || value === '') return ''
  const num = typeof value === 'number' ? value : Number(String(value).replace(/[^0-9.-]+/g, ''))
  if (isNaN(num)) return ''
  return new Intl.NumberFormat('id-ID').format(Math.round(num))
}

const parseInputRupiah = value => {
  if (!value) return 0
  const clean = String(value).replace(/[^0-9]/g, '')
  return clean ? Number(clean) : 0
}

const onAmountInput = val => {
  const num = parseInputRupiah(val)
  paymentAmount.value = num
  paymentAmountDisplay.value = num ? formatInputRupiah(num) : ''
}

// Flat list of all items across payables in this statement
const allStatementItems = computed(() => {
  if (!statement.value?.payables) return []
  const list = []
  statement.value.payables.forEach(payable => {
    const grItems = payable.goods_receipt?.items || payable.goodsReceipt?.items || []
    const poNum = payable.purchase_order?.po_number || payable.purchaseOrder?.po_number || payable.goods_receipt?.purchase_order?.po_number || payable.goodsReceipt?.purchaseOrder?.po_number || '-'

    if (grItems.length > 0) {
      grItems.forEach(item => {
        const qty = Number(item.qty_received || item.qty || 1)
        const unitPrice = Number(item.net_unit_price || item.unit_cost || item.gross_price || 0)
        const subtotal = item.calculated_subtotal !== undefined ? Number(item.calculated_subtotal) : (qty * unitPrice || Number(item.total_price || 0))
        const paid = Number(item.paid_amount || 0)
        const remaining = item.remaining_amount !== undefined ? Number(item.remaining_amount) : Math.max(0, subtotal - paid)
        const isPaid = (remaining <= 0 && subtotal > 0) || item.payment_status === 'paid'

        const prodName = item.product_name || item.productBranch?.product?.name || item.product_branch?.product?.name || item.purchaseOrderItem?.product?.name || item.purchase_order_item?.product?.name || item.product?.name || (payable.purchaseOrder?.items?.[0]?.product?.name) || (payable.purchase_order?.items?.[0]?.product?.name) || 'Produk'
        const skuVal = item.sku || item.productBranch?.product?.sku || item.product_branch?.product?.sku || item.purchaseOrderItem?.product?.sku || item.purchase_order_item?.product?.sku || item.product?.sku || (payable.purchaseOrder?.items?.[0]?.product?.sku) || (payable.purchase_order?.items?.[0]?.product?.sku) || '-'

        list.push({
          id: item.id,
          payable_id: payable.id,
          invoice_number_supplier: payable.invoice_number_supplier || payable.payable_number,
          invoice_date: payable.invoice_date,
          po_number: item.po_number && item.po_number !== '-' ? item.po_number : poNum,
          product_name: prodName,
          sku: skuVal,
          batch_number: item.batch_number || '-',
          expiration_date: item.expiration_date || null,
          qty,
          unit_name: item.unit_name || 'pcs',
          conversion_qty: item.conversion_qty || 1,
          unit_price: unitPrice,
          subtotal,
          paid_amount: paid,
          remaining_amount: remaining,
          is_paid: isPaid,
          status: isPaid ? 'paid' : (paid > 0 ? 'partial' : 'unpaid'),
        })
      })
    } else {
      // Fallback if item registered at payable level
      const subtotal = Number(payable.total_amount || 0)
      const paid = Number(payable.paid_amount || 0)
      const remaining = Number(payable.remaining_amount || 0)
      const isPaid = payable.status === 'paid'

      const poItems = payable.purchaseOrder?.items || payable.purchase_order?.items || []
      const fallbackName = poItems.length > 0 && poItems[0].product?.name ? poItems[0].product.name : ('Faktur ' + (payable.invoice_number_supplier || payable.payable_number))
      const fallbackSku = poItems.length > 0 && poItems[0].product?.sku ? poItems[0].product.sku : '-'

      list.push({
        id: 'p_' + payable.id,
        payable_id: payable.id,
        invoice_number_supplier: payable.invoice_number_supplier || payable.payable_number,
        invoice_date: payable.invoice_date,
        po_number: poNum,
        product_name: fallbackName,
        sku: fallbackSku,
        batch_number: '-',
        expiration_date: null,
        qty: 1,
        unit_name: 'Faktur',
        conversion_qty: 1,
        unit_price: subtotal,
        subtotal,
        paid_amount: paid,
        remaining_amount: remaining,
        is_paid: isPaid,
        status: isPaid ? 'paid' : (paid > 0 ? 'partial' : 'unpaid'),
      })
    }
  })
  return list
})

const unpaidItemsCount = computed(() => {
  return allStatementItems.value.filter(i => !i.is_paid).length
})

const selectedItemsTotal = computed(() => {
  return allStatementItems.value
    .filter(i => selectedItemIds.value.includes(i.id))
    .reduce((acc, i) => acc + (i.remaining_amount > 0 ? i.remaining_amount : i.subtotal), 0)
})

const toggleSelectAllUnpaid = () => {
  if (selectedItemIds.value.length === unpaidItemsCount.value && unpaidItemsCount.value > 0) {
    selectedItemIds.value = []
  } else {
    selectedItemIds.value = allStatementItems.value.filter(i => !i.is_paid).map(i => i.id)
  }
  updateAmountFromSelectedItems()
}

const onToggleItemCheckbox = (itemId, isPaid) => {
  if (isPaid) return
  const index = selectedItemIds.value.indexOf(itemId)
  if (index > -1) {
    selectedItemIds.value.splice(index, 1)
  } else {
    selectedItemIds.value.push(itemId)
  }
  updateAmountFromSelectedItems()
}

const updateAmountFromSelectedItems = () => {
  if (selectedItemIds.value.length > 0) {
    const total = selectedItemsTotal.value
    paymentAmount.value = total
    paymentAmountDisplay.value = formatInputRupiah(total)
    isPaymentFormVisible.value = true
  }
}

const setFullPayment = () => {
  if (!statement.value) return
  selectedItemIds.value = allStatementItems.value.filter(i => !i.is_paid).map(i => i.id)
  const remaining = Number(statement.value.remaining_amount) || 0
  paymentAmount.value = remaining
  paymentAmountDisplay.value = formatInputRupiah(remaining)
  isPaymentFormVisible.value = true
}

const fetchStatementDetail = async () => {
  if (!props.statementId) return
  isLoading.value = true
  try {
    const res = await $api(`/apps/payables/${props.statementId}`)
    statement.value = res.data || res

    // Fetch bank accounts, receipt settings, and supplier credits
    fetchBankAccounts()
    fetchReceiptSettings()
    if (statement.value?.supplier_id) {
      fetchSupplierCredits(statement.value.supplier_id)
    }
  } catch (error) {
    console.error('Failed to fetch statement detail:', error)
    snackbar.show('Gagal memuat rincian tagihan bulanan supplier', 'error')
  } finally {
    isLoading.value = false
  }
}

const fetchSupplierCredits = async supplierId => {
  try {
    const res = await $api('/apps/supplier-credits', { query: { supplier_id: supplierId, status: 'available' } })
    supplierCredits.value = res.data || res || []
  } catch (error) {
    console.error('Failed to fetch supplier credits:', error)
  }
}

watch(() => [props.isDrawerOpen, props.statementId], ([isOpen, stmtId]) => {
  if (isOpen && stmtId) {
    isPaymentFormVisible.value = false
    selectedItemIds.value = []
    resetForm()
    fetchStatementDetail()
  } else if (!isOpen) {
    statement.value = null
    selectedItemIds.value = []
  }
}, { immediate: true })

const onFileSelected = event => {
  const file = event.target.files[0]
  if (file) {
    proofFile.value = file
    proofPreview.value = URL.createObjectURL(file)
  }
}

const resetForm = () => {
  paymentAmount.value = 0
  paymentAmountDisplay.value = ''
  paymentDate.value = new Date().toISOString().substring(0, 10)
  paymentMethod.value = 'bank_transfer'
  selectedBankAccountId.value = null
  bankName.value = ''
  bankAccountNumber.value = ''
  bankAccountName.value = ''
  referenceNumber.value = ''
  paymentNotes.value = ''
  proofFile.value = null
  proofPreview.value = null
  selectedSupplierCreditId.value = null

  if (bankAccounts.value.length > 0) {
    const defaultBank = bankAccounts.value.find(b => b.is_default) || bankAccounts.value[0]
    if (defaultBank) {
      onSelectBankAccount(defaultBank.id)
    }
  }
}

const submitPayment = async () => {
  if (!statement.value) return
  if (paymentAmount.value <= 0) {
    snackbar.show('Nominal pembayaran harus lebih besar dari 0', 'error')
    return
  }

  if (paymentAmount.value > Number(statement.value.remaining_amount) + 0.01) {
    snackbar.show('Nominal pembayaran melebihi sisa tagihan periode', 'error')
    return
  }

  if (paymentMethod.value === 'bank_transfer') {
    if (!selectedBankAccountId.value) {
      snackbar.show('Silakan pilih rekening bank sumber dana pembayaran', 'warning')
      return
    }
    if (selectedBankAccount.value && Number(selectedBankAccount.value.current_balance) < paymentAmount.value) {
      snackbar.show(`Saldo rekening ${selectedBankAccount.value.bank_name} tidak mencukupi (Tersedia: ${formatCurrency(selectedBankAccount.value.current_balance)})`, 'error')
      return
    }
  }

  isSubmitting.value = true
  try {
    const formData = new FormData()
    formData.append('amount', paymentAmount.value)
    formData.append('payment_date', paymentDate.value)
    formData.append('payment_method', paymentMethod.value)
    if (paymentMethod.value === 'bank_transfer' && selectedBankAccountId.value) {
      formData.append('bank_account_id', selectedBankAccountId.value)
    }
    if (bankName.value) formData.append('bank_name', bankName.value)
    if (bankAccountNumber.value) formData.append('bank_account_number', bankAccountNumber.value)
    if (bankAccountName.value) formData.append('bank_account_name', bankAccountName.value)
    if (referenceNumber.value) formData.append('reference_number', referenceNumber.value)
    if (paymentNotes.value) formData.append('notes', paymentNotes.value)
    if (selectedSupplierCreditId.value) formData.append('supplier_credit_id', selectedSupplierCreditId.value)
    if (proofFile.value) formData.append('proof_file', proofFile.value)

    // Filter only integer GoodsReceiptItem IDs
    const numericItemIds = selectedItemIds.value.filter(id => typeof id === 'number')
    if (numericItemIds.length > 0) {
      numericItemIds.forEach(id => {
        formData.append('selected_item_ids[]', id)
      })
    }

    const payRes = await $api(`/apps/payables/${statement.value.id}/pay`, {
      method: 'POST',
      body: formData,
    })

    snackbar.show('Pembayaran tagihan bulanan dan barang berhasil dicatat!', 'success')
    isPaymentFormVisible.value = false
    selectedItemIds.value = []
    resetForm()
    await fetchStatementDetail()
    emit('paymentRecorded')

    // Automatically trigger Kuitansi printing with database paper rule
    if (statement.value?.payments && statement.value.payments.length > 0) {
      printPaymentReceipt(statement.value.payments[0])
    }
  } catch (error) {
    console.error('Failed to submit payment:', error)
    snackbar.show(error.data?.message || 'Gagal menyimpan pembayaran', 'error')
  } finally {
    isSubmitting.value = false
  }
}

const voidPayment = async paymentId => {
  if (!confirm('Apakah Anda yakin ingin membatalkan transaksi pembayaran ini? Barang yang sebelumnya terbayar akan kembali belum lunas.')) return

  try {
    await $api(`/apps/payables/${statement.value.id}/payments/${paymentId}`, {
      method: 'DELETE',
    })
    snackbar.show('Pembayaran berhasil dibatalkan', 'success')
    await fetchStatementDetail()
    emit('paymentVoided')
  } catch (error) {
    console.error('Failed to void payment:', error)
    snackbar.show('Gagal membatalkan pembayaran', 'error')
  }
}

const toggleExpandPayable = id => {
  expandedPayableId.value = expandedPayableId.value === id ? null : id
}

const progressPercentage = computed(() => {
  if (!statement.value || Number(statement.value.total_amount) === 0) return 0
  const paid = Number(statement.value.paid_amount) || 0
  const total = Number(statement.value.total_amount) || 1
  const pct = (paid / total) * 100
  if (pct > 0 && pct < 1) {
    return Number(pct.toFixed(2))
  }
  return Math.min(100, Math.round(pct))
})

const isOverdue = computed(() => {
  if (!statement.value || statement.value.status === 'paid' || !statement.value.due_date) return false
  const today = new Date().toISOString().substring(0, 10)
  return String(statement.value.due_date).substring(0, 10) < today
})

const closeDrawer = () => {
  emit('update:isDrawerOpen', false)
}
</script>

<template>
  <VNavigationDrawer
    :model-value="props.isDrawerOpen"
    location="end"
    temporary
    width="850"
    @update:model-value="val => emit('update:isDrawerOpen', val)"
  >
    <div class="d-flex flex-column h-100">
      <!-- Header -->
      <div class="pa-5 border-b bg-gradient-header d-flex justify-space-between align-center">
        <div class="d-flex align-center gap-3">
          <VAvatar color="primary" variant="tonal" size="44" class="rounded-lg">
            <VIcon icon="ri-calendar-check-line" size="24" />
          </VAvatar>
          <div>
            <h6 class="text-h6 font-weight-bold mb-0">
              Rekap Tagihan Bulanan & Seleksi Pembayaran Barang
            </h6>
            <span class="text-caption text-medium-emphasis">
              No. Tagihan: <strong>{{ statement?.statement_number || '-' }}</strong>
            </span>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <VBtn icon="ri-close-line" variant="text" size="small" @click="closeDrawer" />
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="d-flex justify-center align-center flex-grow-1">
        <VProgressCircular indeterminate color="primary" size="40" />
      </div>

      <!-- Content -->
      <div v-else-if="statement" class="pa-6 flex-grow-1 overflow-y-auto print-area">
        <!-- Status & Progress Banner -->
        <div class="mb-5 pa-4 rounded-xl border bg-var-theme-surface shadow-xs">
          <div class="d-flex justify-space-between align-center flex-wrap gap-3 mb-3">
            <div>
              <span class="text-caption text-medium-emphasis">Supplier / Vendor:</span>
              <div class="text-subtitle-1 font-weight-bold text-primary">
                {{ statement.supplier?.name }}
              </div>
              <span class="text-caption text-medium-emphasis">
                {{ statement.supplier?.phone || '-' }} | {{ statement.branch?.name || 'Semua Cabang' }}
              </span>
            </div>
            <div class="text-right">
              <VChip
                :color="statement.status === 'paid' ? 'success' : (isOverdue ? 'error' : (statement.status === 'partial' ? 'warning' : 'secondary'))"
                class="font-weight-bold"
                size="small"
              >
                {{ statement.status === 'paid' ? 'Lunas' : (isOverdue ? 'Lewat Jatuh Tempo' : (statement.status === 'partial' ? 'Dicicil' : 'Belum Dibayar')) }}
              </VChip>
              <div v-if="statement.due_date" class="text-caption mt-1" :class="isOverdue ? 'text-error font-weight-bold' : 'text-medium-emphasis'">
                Jatuh Tempo Pembayaran: {{ formatDate(statement.due_date) }}
              </div>
            </div>
          </div>

          <!-- Periode Siklus Cutoff Info -->
          <div class="pa-3 mb-3 rounded-lg bg-grey-50 border d-flex justify-space-between align-center flex-wrap gap-2">
            <div class="d-flex align-center gap-2">
              <VIcon icon="ri-time-line" size="18" color="primary" />
              <span class="text-caption font-weight-medium">
                Siklus Periode Cutoff (Tgl 26 - 25):
              </span>
              <strong class="text-caption text-primary">
                {{ formatMonthLabel(statement.period_month) }}
              </strong>
            </div>
            <span class="text-caption font-mono font-weight-bold text-medium-emphasis">
              {{ formatDateRange(statement.period_start_date, statement.period_end_date) }}
            </span>
          </div>

          <!-- Progress Bar Pelunasan -->
          <div class="mb-2">
            <div class="d-flex justify-space-between text-caption font-weight-medium mb-1">
              <span>Progres Pelunasan Tagihan Periode:</span>
              <span class="font-weight-bold text-primary">{{ progressPercentage }}%</span>
            </div>
            <VProgressLinear
              :model-value="progressPercentage"
              height="8"
              rounded
              :color="statement.status === 'paid' ? 'success' : 'primary'"
            />
          </div>

          <VRow dense class="mt-2 pt-2 border-t text-center">
            <VCol cols="4">
              <span class="text-caption text-medium-emphasis">Total Tagihan Periode:</span>
              <div class="font-weight-bold text-subtitle-2 text-primary font-mono">
                {{ formatCurrency(statement.total_amount) }}
              </div>
            </VCol>
            <VCol cols="4">
              <span class="text-caption text-medium-emphasis">Sudah Dicicil / Bayar:</span>
              <div class="font-weight-bold text-subtitle-2 text-success font-mono">
                {{ formatCurrency(statement.paid_amount) }}
              </div>
            </VCol>
            <VCol cols="4">
              <span class="text-caption text-medium-emphasis">Sisa Kewajiban Hutang:</span>
              <div class="font-weight-bold text-subtitle-2 text-error font-mono">
                {{ formatCurrency(statement.remaining_amount) }}
              </div>
            </VCol>
          </VRow>
        </div>

        <!-- SECTION: DAFTAR CEKLIS BARANG YANG MAU DIBAYAR DI PERIODE INI -->
        <div class="mb-6">
          <div class="d-flex justify-space-between align-center flex-wrap gap-2 mb-3">
            <div>
              <h6 class="text-subtitle-1 font-weight-bold mb-0 d-flex align-center gap-2">
                <VIcon icon="ri-checkbox-multiple-line" size="20" color="primary" />
                Pilih Barang Yang Mau Dibayar ({{ allStatementItems.length }} Item)
              </h6>
              <span class="text-caption text-medium-emphasis">
                Ceklis barang belum lunas untuk dibayarkan. Barang lunas otomatis tercentang hijau.
              </span>
            </div>

            <!-- Quick Action Toolbar -->
            <div v-if="statement.status !== 'paid'" class="d-flex align-center gap-2">
              <VBtn
                size="small"
                variant="tonal"
                color="primary"
                prepend-icon="ri-checkbox-line"
                @click="toggleSelectAllUnpaid"
              >
                {{ selectedItemIds.length === unpaidItemsCount && unpaidItemsCount > 0 ? 'Batal Pilih Semua' : 'Pilih Semua Belum Lunas' }}
              </VBtn>
            </div>
          </div>

          <!-- Selection Bar Alert -->
          <div v-if="selectedItemIds.length > 0" class="pa-3 mb-3 rounded-lg bg-primary-lighten-5 border border-primary border-opacity-25 d-flex justify-space-between align-center flex-wrap gap-2">
            <div class="d-flex align-center gap-2">
              <VIcon icon="ri-check-line" color="primary" size="20" />
              <span class="text-body-2 font-weight-bold text-primary">
                {{ selectedItemIds.length }} Barang Terpilih untuk Dibayar
              </span>
            </div>
            <div class="d-flex align-center gap-3">
              <span class="text-body-2 font-mono font-weight-bold text-primary">
                Total: {{ formatCurrency(selectedItemsTotal) }}
              </span>
              <VBtn
                size="small"
                color="primary"
                prepend-icon="ri-wallet-3-line"
                @click="isPaymentFormVisible = true"
              >
                Bayar Sekarang
              </VBtn>
            </div>
          </div>

          <!-- Table of Checklist Items -->
          <div class="border rounded-xl overflow-hidden shadow-xs">
            <table class="w-100 table-items">
              <thead>
                <tr class="bg-grey-100 text-left">
                  <th class="pa-3 text-center" style="width: 45px;">
                    <VCheckboxBtn
                      :model-value="selectedItemIds.length === unpaidItemsCount && unpaidItemsCount > 0"
                      :disabled="unpaidItemsCount === 0"
                      @click="toggleSelectAllUnpaid"
                    />
                  </th>
                  <th class="pa-3 text-xs">BARANG & SKU</th>
                  <th class="pa-3 text-xs">NO. FAKTUR & PO</th>
                  <th class="pa-3 text-xs text-center">QTY</th>
                  <th class="pa-3 text-xs text-right">HARGA MODAL</th>
                  <th class="pa-3 text-xs text-right">TOTAL NILAI</th>
                  <th class="pa-3 text-xs text-center">STATUS</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="item in allStatementItems"
                  :key="item.id"
                  class="border-b item-row"
                  :class="{
                    'bg-success-lighten-5': item.is_paid,
                    'bg-primary-lighten-5': selectedItemIds.includes(item.id) && !item.is_paid,
                  }"
                  @click="onToggleItemCheckbox(item.id, item.is_paid)"
                >
                  <!-- Checkbox Selection -->
                  <td class="pa-3 text-center" @click.stop>
                    <VCheckboxBtn
                      v-if="!item.is_paid"
                      :model-value="selectedItemIds.includes(item.id)"
                      @update:model-value="() => onToggleItemCheckbox(item.id, item.is_paid)"
                    />
                    <VIcon
                      v-else
                      icon="ri-checkbox-circle-fill"
                      color="success"
                      size="20"
                      title="Barang ini sudah Lunas"
                    />
                  </td>

                  <!-- Nama Produk & SKU -->
                  <td class="pa-3 text-xs">
                    <div class="font-weight-bold text-high-emphasis">{{ item.product_name }}</div>
                    <div class="text-caption text-medium-emphasis font-mono">
                      <code>{{ item.sku }}</code>
                      <span v-if="item.batch_number && item.batch_number !== '-'" class="ms-1 text-primary">
                        | Batch: {{ item.batch_number }}
                      </span>
                    </div>
                  </td>

                  <!-- No Faktur & PO -->
                  <td class="pa-3 text-xs">
                    <div class="font-weight-bold font-mono text-primary">{{ item.invoice_number_supplier }}</div>
                    <div class="text-caption text-medium-emphasis">PO: {{ item.po_number }}</div>
                  </td>

                  <!-- Qty -->
                  <td class="pa-3 text-xs text-center font-weight-bold">
                    {{ item.qty }} {{ item.unit_name }}
                  </td>

                  <!-- Harga Modal Satuan -->
                  <td class="pa-3 text-xs text-right font-mono">
                    {{ formatCurrency(item.unit_price) }}
                  </td>

                  <!-- Subtotal Nilai Barang -->
                  <td class="pa-3 text-xs text-right font-mono">
                    <div class="font-weight-bold text-primary">{{ formatCurrency(item.subtotal) }}</div>
                    <div v-if="item.paid_amount > 0 && !item.is_paid" class="text-caption text-success font-weight-medium">
                      Sudah dicicil: {{ formatCurrency(item.paid_amount) }}
                    </div>
                  </td>

                  <!-- Status Pelunasan Barang -->
                  <td class="pa-3 text-xs text-center">
                    <VChip
                      v-if="item.is_paid"
                      size="x-small"
                      color="success"
                      variant="flat"
                      class="font-weight-bold"
                    >
                      <VIcon icon="ri-check-double-line" size="12" class="mr-1" />
                      Lunas
                    </VChip>
                    <VChip
                      v-else-if="item.paid_amount > 0"
                      size="x-small"
                      color="warning"
                      variant="tonal"
                      class="font-weight-bold"
                    >
                      Dicicil
                    </VChip>
                    <VChip
                      v-else
                      size="x-small"
                      color="secondary"
                      variant="tonal"
                      class="font-weight-bold"
                    >
                      Belum Lunas
                    </VChip>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- SECTION: FORM INPUT CICILAN / PELUNASAN PEMBAYARAN -->
        <div v-if="statement.status !== 'paid'" class="mb-6">
          <div class="d-flex justify-space-between align-center mb-3">
            <h6 class="text-subtitle-1 font-weight-bold mb-0 d-flex align-center gap-2">
              <VIcon icon="ri-secure-payment-line" size="20" color="primary" />
              Form Pembayaran / Cicilan Tagihan
            </h6>
            <VBtn
              size="small"
              :variant="isPaymentFormVisible ? 'tonal' : 'flat'"
              color="primary"
              :prepend-icon="isPaymentFormVisible ? 'ri-close-line' : 'ri-add-line'"
              @click="isPaymentFormVisible = !isPaymentFormVisible"
            >
              {{ isPaymentFormVisible ? 'Tutup Form' : 'Bayar / Cicil Tagihan' }}
            </VBtn>
          </div>

          <VExpandTransition>
            <div v-show="isPaymentFormVisible" class="pa-5 rounded-xl border bg-var-theme-surface shadow-sm mb-5">
              <!-- Form Header -->
              <div class="d-flex align-center justify-space-between pb-3 mb-4 border-b">
                <div class="d-flex align-center gap-2">
                  <VAvatar color="primary" variant="tonal" size="36" rounded="lg">
                    <VIcon icon="ri-hand-coin-line" size="20" />
                  </VAvatar>
                  <div>
                    <div class="font-weight-bold text-body-1">Form Pencatatan Pembayaran</div>
                    <div class="text-caption text-medium-emphasis">Catat cicilan atau pelunasan tagihan supplier</div>
                  </div>
                </div>
                <VBtn
                  size="x-small"
                  variant="text"
                  color="primary"
                  class="font-weight-bold px-2 rounded-lg"
                  @click="setFullPayment"
                >
                  <VIcon icon="ri-check-double-line" size="14" class="mr-1" />
                  Bayar Lunas Semua ({{ formatCurrency(statement.remaining_amount) }})
                </VBtn>
              </div>

              <VRow dense class="g-3">
                <!-- Nominal & Tanggal -->
                <VCol cols="12" sm="7">
                  <div class="d-flex justify-space-between align-center mb-1">
                    <span class="text-caption font-weight-bold">
                      Nominal Pembayaran *
                      <span v-if="selectedItemIds.length > 0" class="text-primary font-weight-normal">
                        ({{ selectedItemIds.length }} barang dipilih)
                      </span>
                    </span>
                  </div>
                  <VTextField
                    :model-value="paymentAmountDisplay"
                    prefix="Rp"
                    placeholder="0"
                    density="compact"
                    variant="outlined"
                    class="font-mono font-weight-bold"
                    prepend-inner-icon="ri-money-dollar-circle-line"
                    hide-details="auto"
                    @update:model-value="onAmountInput"
                  />
                </VCol>

                <VCol cols="12" sm="5">
                  <div class="mb-1 text-caption font-weight-bold">Tanggal Pembayaran *</div>
                  <VTextField
                    v-model="paymentDate"
                    type="date"
                    density="compact"
                    variant="outlined"
                    prepend-inner-icon="ri-calendar-line"
                    hide-details="auto"
                  />
                </VCol>

                <!-- Metode Pembayaran Card Selector -->
                <VCol cols="12" class="mt-2">
                  <div class="text-caption font-weight-bold text-medium-emphasis mb-2 text-uppercase letter-spacing-1">
                    Metode Pembayaran (Sumber Dana) *
                  </div>
                  <div class="d-grid grid-cols-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <!-- Option 1: Transfer Bank -->
                    <div
                      class="payable-payment-card pa-3 rounded-xl border cursor-pointer d-flex align-center gap-3"
                      :class="paymentMethod === 'bank_transfer' ? 'selected-bank-card' : 'unselected-card'"
                      @click="paymentMethod = 'bank_transfer'"
                    >
                      <VAvatar
                        :color="paymentMethod === 'bank_transfer' ? 'info' : 'secondary'"
                        :variant="paymentMethod === 'bank_transfer' ? 'flat' : 'tonal'"
                        size="38"
                        rounded="lg"
                      >
                        <VIcon icon="ri-bank-card-line" size="20" :color="paymentMethod === 'bank_transfer' ? 'white' : undefined" />
                      </VAvatar>
                      <div class="flex-grow-1 overflow-hidden">
                        <div class="font-weight-bold text-body-2 text-truncate" :class="paymentMethod === 'bank_transfer' ? 'text-info' : ''">
                          Transfer Bank
                        </div>
                        <div class="text-caption text-medium-emphasis text-truncate" style="font-size: 11px;">
                          Potong Saldo Rekening
                        </div>
                      </div>
                      <VIcon
                        v-if="paymentMethod === 'bank_transfer'"
                        icon="ri-checkbox-circle-fill"
                        color="info"
                        size="20"
                      />
                    </div>

                    <!-- Option 2: Kas Tunai Toko -->
                    <div
                      class="payable-payment-card pa-3 rounded-xl border cursor-pointer d-flex align-center gap-3"
                      :class="paymentMethod === 'cash' ? 'selected-cash-card' : 'unselected-card'"
                      @click="paymentMethod = 'cash'"
                    >
                      <VAvatar
                        :color="paymentMethod === 'cash' ? 'warning' : 'secondary'"
                        :variant="paymentMethod === 'cash' ? 'flat' : 'tonal'"
                        size="38"
                        rounded="lg"
                      >
                        <VIcon icon="ri-cash-line" size="20" :color="paymentMethod === 'cash' ? 'white' : undefined" />
                      </VAvatar>
                      <div class="flex-grow-1 overflow-hidden">
                        <div class="font-weight-bold text-body-2 text-truncate" :class="paymentMethod === 'cash' ? 'text-warning' : ''">
                          Kas Tunai Toko
                        </div>
                        <div class="text-caption text-medium-emphasis text-truncate" style="font-size: 11px;">
                          Laci Kasir / Tunai
                        </div>
                      </div>
                      <VIcon
                        v-if="paymentMethod === 'cash'"
                        icon="ri-checkbox-circle-fill"
                        color="warning"
                        size="20"
                      />
                    </div>
                  </div>
                </VCol>

                <!-- Bank Account Selection for Bank Transfer -->
                <VCol v-if="paymentMethod === 'bank_transfer'" cols="12" class="mt-2">
                  <div class="pa-4 rounded-xl border border-info" style="background-color: rgba(var(--v-theme-info), 0.04);">
                    <div class="d-flex align-center justify-space-between mb-2">
                      <span class="text-caption font-weight-bold text-info d-flex align-center gap-1">
                        <VIcon icon="ri-bank-line" size="16" />
                        Pilih Rekening Bank Sumber Dana:
                      </span>
                      <span v-if="selectedBankAccount" class="text-caption text-medium-emphasis">
                        Saldo: <strong class="text-success font-mono">{{ formatCurrency(selectedBankAccount.current_balance) }}</strong>
                      </span>
                    </div>

                    <VSelect
                      :model-value="selectedBankAccountId"
                      :items="bankAccounts"
                      item-value="id"
                      placeholder="-- Pilih Rekening Bank --"
                      density="compact"
                      variant="outlined"
                      prepend-inner-icon="ri-bank-card-line"
                      hide-details
                      @update:model-value="onSelectBankAccount"
                    >
                      <template #selection="{ item }">
                        <span class="font-weight-medium text-body-2">
                          {{ item.raw.bank_name }} - {{ item.raw.account_number }} (a/n {{ item.raw.account_name }})
                        </span>
                      </template>
                      <template #item="{ props: itemProps, item }">
                        <VListItem v-bind="itemProps" class="py-2">
                          <template #title>
                            <div class="d-flex justify-space-between align-center">
                              <span class="font-weight-bold">{{ item.raw.bank_name }} - {{ item.raw.account_number }}</span>
                              <VChip size="x-small" color="success" variant="tonal" class="font-weight-bold font-mono">
                                Saldo: {{ formatCurrency(item.raw.current_balance) }}
                              </VChip>
                            </div>
                          </template>
                          <template #subtitle>
                            <span class="text-caption text-medium-emphasis">
                              a/n {{ item.raw.account_name }} {{ item.raw.branch ? `• Cabang ${item.raw.branch.name}` : '• Semua Cabang' }}
                            </span>
                          </template>
                        </VListItem>
                      </template>
                    </VSelect>

                    <!-- Warning jika Saldo Kurang -->
                    <VAlert
                      v-if="selectedBankAccount && Number(selectedBankAccount.current_balance) < paymentAmount"
                      type="error"
                      variant="tonal"
                      density="compact"
                      class="mt-3 text-caption rounded-lg"
                      icon="ri-error-warning-line"
                    >
                      <strong>Saldo Tidak Cukup:</strong> Saldo rekening {{ selectedBankAccount.bank_name }} ({{ formatCurrency(selectedBankAccount.current_balance) }}) kurang dari nominal pembayaran ({{ formatCurrency(paymentAmount) }}).
                    </VAlert>
                  </div>
                </VCol>

                <!-- Cash explanation banner -->
                <VCol v-if="paymentMethod === 'cash'" cols="12" class="mt-2">
                  <div class="pa-3 rounded-xl border bg-var-theme-surface text-caption text-medium-emphasis d-flex align-center gap-2">
                    <VIcon icon="ri-information-line" size="20" color="warning" />
                    <div>Pembayaran kas tunai memotong tagihan tanpa memotong saldo rekening bank.</div>
                  </div>
                </VCol>

                <!-- No. Referensi & Unggah Bukti -->
                <VCol cols="12" sm="6" class="mt-2">
                  <div class="mb-1 text-caption font-weight-bold">No. Referensi / No. Transaksi</div>
                  <VTextField
                    v-model="referenceNumber"
                    placeholder="Nomor referensi / bukti..."
                    density="compact"
                    variant="outlined"
                    prepend-inner-icon="ri-hashtag"
                    hide-details="auto"
                  />
                </VCol>

                <VCol cols="12" sm="6" class="mt-2">
                  <div class="mb-1 text-caption font-weight-bold">Unggah Bukti Transfer / Kwitansi</div>
                  <VFileInput
                    density="compact"
                    variant="outlined"
                    accept="image/*,application/pdf"
                    prepend-icon=""
                    prepend-inner-icon="ri-upload-2-line"
                    placeholder="Pilih berkas bukti..."
                    hide-details="auto"
                    @change="onFileSelected"
                  />
                </VCol>

                <!-- Catatan Pembayaran -->
                <VCol cols="12" class="mt-2">
                  <div class="mb-1 text-caption font-weight-bold">Catatan Pembayaran (Opsional)</div>
                  <VTextarea
                    v-model="paymentNotes"
                    rows="2"
                    placeholder="Keterangan cicilan tahap 1, pelunasan barang tertentu, dll..."
                    density="compact"
                    variant="outlined"
                    hide-details="auto"
                  />
                </VCol>

                <!-- Action Buttons -->
                <VCol cols="12" class="mt-4 pt-3 border-t d-flex justify-end gap-2">
                  <VBtn variant="tonal" color="secondary" size="small" class="rounded-lg px-4" @click="isPaymentFormVisible = false">
                    Batal
                  </VBtn>
                  <VBtn
                    color="primary"
                    size="small"
                    prepend-icon="ri-save-line"
                    class="font-weight-bold rounded-lg px-5 shadow-xs"
                    :loading="isSubmitting"
                    :disabled="paymentMethod === 'bank_transfer' && selectedBankAccount && Number(selectedBankAccount.current_balance) < paymentAmount"
                    @click="submitPayment"
                  >
                    {{ selectedItemIds.length > 0 ? `Bayar ${selectedItemIds.length} Barang Terpilih` : 'Simpan Pembayaran' }}
                  </VBtn>
                </VCol>
              </VRow>
            </div>
          </VExpandTransition>
        </div>

        <!-- SECTION: RIWAYAT CICILAN & PELUNASAN PERIODE INI -->
        <div class="mb-5">
          <h6 class="text-subtitle-1 font-weight-bold mb-3 d-flex align-center gap-2">
            <VIcon icon="ri-history-line" size="20" color="primary" />
            Riwayat Cicilan & Pelunasan Periode Ini ({{ statement.payments?.length || 0 }})
          </h6>

          <div v-if="statement.payments && statement.payments.length > 0" class="border rounded-xl overflow-hidden shadow-xs">
            <table class="w-100 table-payments">
              <thead>
                <tr class="bg-grey-100 text-left">
                  <th class="pa-3 text-xs">NO. TRANSAKSI & TGL</th>
                  <th class="pa-3 text-xs">METODE & BANK</th>
                  <th class="pa-3 text-xs text-right">NOMINAL DIBAYAR</th>
                  <th class="pa-3 text-xs text-center">BUKTI</th>
                  <th class="pa-3 text-xs text-center" style="width: 60px;">CETAK</th>
                  <th class="pa-3 text-xs text-center" style="width: 50px;">BATAL</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="payment in statement.payments" :key="payment.id" class="border-b">
                  <td class="pa-3 text-xs">
                    <div class="font-weight-bold font-mono text-primary">{{ payment.payment_number }}</div>
                    <div class="text-caption text-medium-emphasis">{{ formatDate(payment.payment_date) }}</div>
                  </td>
                  <td class="pa-3 text-xs">
                    <div class="font-weight-medium text-capitalize d-flex align-center gap-1">
                      <VIcon
                        :icon="payment.payment_method === 'bank_transfer' ? 'ri-bank-card-line' : (payment.payment_method === 'cash' ? 'ri-money-dollar-circle-line' : (payment.payment_method === 'supplier_credit' ? 'ri-refund-2-line' : 'ri-file-list-3-line'))"
                        size="14"
                        :color="payment.payment_method === 'bank_transfer' ? 'primary' : (payment.payment_method === 'cash' ? 'success' : 'amber')"
                      />
                      <span>{{ payment.payment_method === 'bank_transfer' ? 'Transfer Bank' : (payment.payment_method === 'cash' ? 'Kas Tunai' : (payment.payment_method === 'supplier_credit' ? 'Saldo Retur' : 'Giro / Cek')) }}</span>
                    </div>
                    <div v-if="payment.bank_account || payment.bank_name" class="text-caption text-medium-emphasis font-weight-medium">
                      {{ payment.bank_account ? `${payment.bank_account.bank_name} - ${payment.bank_account.account_number} (${payment.bank_account.account_name})` : payment.bank_name }}
                      <span v-if="payment.reference_number"> • Ref: {{ payment.reference_number }}</span>
                    </div>
                    <div v-if="payment.notes" class="text-caption text-disabled italic">
                      "{{ payment.notes }}"
                    </div>
                  </td>
                  <td class="pa-3 text-xs text-right font-mono font-weight-bold text-success">
                    {{ formatCurrency(payment.amount) }}
                  </td>
                  <td class="pa-3 text-xs text-center">
                    <a
                      v-if="payment.proof_file"
                      :href="payment.proof_file"
                      target="_blank"
                      class="text-primary font-weight-medium d-inline-flex align-center gap-1"
                    >
                      <VIcon icon="ri-attachment-line" size="14" />
                      <span>Lihat</span>
                    </a>
                    <span v-else class="text-disabled">-</span>
                  </td>
                  <td class="pa-3 text-xs text-center">
                    <VBtn
                      icon="ri-printer-line"
                      size="x-small"
                      color="primary"
                      variant="tonal"
                      title="Cetak Kuitansi Pembayaran (Kas Keluar)"
                      @click="printPaymentReceipt(payment)"
                    />
                  </td>
                  <td class="pa-3 text-xs text-center">
                    <VBtn
                      icon="ri-delete-bin-line"
                      size="x-small"
                      color="error"
                      variant="text"
                      title="Batalkan pembayaran ini"
                      @click="voidPayment(payment.id)"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="pa-6 text-center border rounded-xl bg-grey-50 text-medium-emphasis">
            <VIcon icon="ri-inbox-line" size="32" class="mb-1 text-disabled" />
            <div class="text-caption">Belum ada catatan cicilan atau pembayaran untuk tagihan bulanan ini.</div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="pa-4 border-t d-flex justify-end bg-grey-50">
        <VBtn variant="outlined" color="secondary" @click="closeDrawer">
          Tutup
        </VBtn>
      </div>
    </div>

    <!-- Kuitansi Printer Component with Database Paper Rules -->
    <PayableReceiptPrinter
      ref="receiptPrinterRef"
      :statement="statement"
      :payment="selectedPaymentForPrint"
      :branch="statement?.branch"
      :setting="activeReceiptSetting"
      :print-format="activeReceiptSetting?.name?.toLowerCase().includes('thermal') ? 'thermal' : 'kwitansi'"
    />
  </VNavigationDrawer>
</template>

<style scoped>
.bg-gradient-header {
  background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.06) 0%, rgba(var(--v-theme-surface), 1) 100%);
}
.table-items,
.table-payments {
  border-collapse: collapse;
}
.table-items th,
.table-payments th {
  font-weight: 600;
}
.item-row {
  cursor: pointer;
  transition: background-color 0.15s ease;
}
.item-row:hover {
  background-color: rgba(var(--v-theme-primary), 0.04);
}

.payable-payment-card {
  border: 1.5px solid rgba(var(--v-border-color), var(--v-border-opacity));
  background-color: rgb(var(--v-theme-surface));
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  user-select: none;
}

.payable-payment-card:hover {
  border-color: rgba(var(--v-theme-primary), 0.5);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.selected-cash-card {
  border: 2px solid rgb(var(--v-theme-warning)) !important;
  background-color: rgba(var(--v-theme-warning), 0.06) !important;
}

.selected-bank-card {
  border: 2px solid rgb(var(--v-theme-info)) !important;
  background-color: rgba(var(--v-theme-info), 0.06) !important;
}

.unselected-card {
  opacity: 0.75;
}

.unselected-card:hover {
  opacity: 1;
}

.letter-spacing-1 {
  letter-spacing: 0.5px;
}

@media print {
  @page {
    margin: 10mm;
    size: auto;
  }
  body * {
    visibility: hidden;
  }
  .print-area, .print-area * {
    visibility: visible;
  }
  .print-area {
    position: absolute;
    left: 0;
    top: 0;
    width: 100% !important;
    padding: 0 !important;
    background: white !important;
    color: black !important;
  }
  .v-btn, .v-checkbox-btn, .payment-form-container {
    display: none !important;
  }
}
</style>
