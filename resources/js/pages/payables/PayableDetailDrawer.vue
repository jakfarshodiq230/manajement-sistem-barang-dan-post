<script setup>
import { ref, watch, computed } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'

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

    // Fetch supplier credits if supplier exists
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

watch(() => props.isDrawerOpen, newVal => {
  if (newVal && props.statementId) {
    isPaymentFormVisible.value = false
    selectedItemIds.value = []
    resetForm()
    fetchStatementDetail()
  } else {
    statement.value = null
    selectedItemIds.value = []
  }
})

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
  bankName.value = ''
  bankAccountNumber.value = ''
  bankAccountName.value = ''
  referenceNumber.value = ''
  paymentNotes.value = ''
  proofFile.value = null
  proofPreview.value = null
  selectedSupplierCreditId.value = null
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

  isSubmitting.value = true
  try {
    const formData = new FormData()
    formData.append('amount', paymentAmount.value)
    formData.append('payment_date', paymentDate.value)
    formData.append('payment_method', paymentMethod.value)
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

    await $api(`/apps/payables/${statement.value.id}/pay`, {
      method: 'POST',
      body: formData,
    })

    snackbar.show('Pembayaran tagihan bulanan dan barang berhasil dicatat!', 'success')
    isPaymentFormVisible.value = false
    selectedItemIds.value = []
    resetForm()
    await fetchStatementDetail()
    emit('paymentRecorded')
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
  return Math.min(100, Math.round((paid / total) * 100))
})

const isOverdue = computed(() => {
  if (!statement.value || statement.value.status === 'paid' || !statement.value.due_date) return false
  const today = new Date().toISOString().substring(0, 10)
  return String(statement.value.due_date).substring(0, 10) < today
})

const closeDrawer = () => {
  emit('update:isDrawerOpen', false)
}

const printStatement = () => {
  window.print()
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
          <VBtn
            icon="ri-printer-line"
            variant="tonal"
            size="small"
            color="secondary"
            title="Cetak Rekap Tagihan"
            @click="printStatement"
          />
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
            <div v-show="isPaymentFormVisible" class="pa-5 rounded-xl border bg-primary-lighten-5 border-primary border-opacity-25 shadow-xs mb-4">
              <VRow dense>
                <VCol cols="12" sm="7">
                  <div class="d-flex justify-space-between align-center mb-1">
                    <span class="text-caption font-weight-bold">
                      Nominal Pembayaran (Rp)
                      <span v-if="selectedItemIds.length > 0" class="text-primary font-weight-normal">
                        ({{ selectedItemIds.length }} barang dipilih)
                      </span>
                    </span>
                    <VBtn
                      size="x-small"
                      variant="text"
                      color="primary"
                      class="px-1 text-caption font-weight-bold"
                      @click="setFullPayment"
                    >
                      Bayar Lunas Semua ({{ formatCurrency(statement.remaining_amount) }})
                    </VBtn>
                  </div>
                  <VTextField
                    :model-value="paymentAmountDisplay"
                    prefix="Rp"
                    placeholder="0"
                    density="compact"
                    variant="outlined"
                    class="font-mono font-weight-bold"
                    hide-details
                    @update:model-value="onAmountInput"
                  />
                </VCol>
                <VCol cols="12" sm="5">
                  <div class="mb-1 text-caption font-weight-bold">Tanggal Pembayaran</div>
                  <VTextField
                    v-model="paymentDate"
                    type="date"
                    density="compact"
                    variant="outlined"
                    hide-details
                  />
                </VCol>

                <VCol cols="12" sm="6" class="mt-2">
                  <div class="mb-1 text-caption font-weight-bold">Metode Pembayaran</div>
                  <VSelect
                    v-model="paymentMethod"
                    :items="[
                      { title: 'Transfer Bank', value: 'bank_transfer' },
                      { title: 'Kas Tunai Toko', value: 'cash' },
                      { title: 'Giro / Cek', value: 'giro_cheque' },
                      { title: 'Potong Hutang Saldo Retur Supplier', value: 'supplier_credit' },
                    ]"
                    item-title="title"
                    item-value="value"
                    density="compact"
                    variant="outlined"
                    hide-details
                  />
                </VCol>

                <VCol v-if="paymentMethod === 'bank_transfer' || paymentMethod === 'giro_cheque'" cols="12" sm="6" class="mt-2">
                  <div class="mb-1 text-caption font-weight-bold">Nama Bank / Akun</div>
                  <VTextField
                    v-model="bankName"
                    placeholder="Misal: BCA / Mandiri..."
                    density="compact"
                    variant="outlined"
                    hide-details
                  />
                </VCol>

                <VCol v-if="paymentMethod === 'supplier_credit'" cols="12" class="mt-2">
                  <div class="mb-1 text-caption font-weight-bold">Pilih Saldo Retur Supplier (Credit Note)</div>
                  <VSelect
                    v-model="selectedSupplierCreditId"
                    :items="supplierCredits"
                    :item-title="item => `${item.credit_number} - Sisa Saldo: ${formatCurrency(item.remaining_amount)}`"
                    item-value="id"
                    placeholder="Pilih Saldo Retur..."
                    density="compact"
                    variant="outlined"
                    hide-details
                  />
                </VCol>

                <VCol cols="12" sm="6" class="mt-2">
                  <div class="mb-1 text-caption font-weight-bold">No. Referensi / No. Transaksi</div>
                  <VTextField
                    v-model="referenceNumber"
                    placeholder="Nomor referensi mutasi bank..."
                    density="compact"
                    variant="outlined"
                    hide-details
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
                    placeholder="Pilih berkas..."
                    hide-details
                    @change="onFileSelected"
                  />
                </VCol>

                <VCol cols="12" class="mt-2">
                  <div class="mb-1 text-caption font-weight-bold">Catatan Pembayaran (Opsional)</div>
                  <VTextarea
                    v-model="paymentNotes"
                    rows="2"
                    placeholder="Keterangan cicilan tahap 1, pelunasan barang tertentu, dll..."
                    density="compact"
                    variant="outlined"
                    hide-details
                  />
                </VCol>

                <VCol cols="12" class="mt-4 d-flex justify-end gap-2">
                  <VBtn variant="outlined" color="secondary" size="small" @click="isPaymentFormVisible = false">
                    Batal
                  </VBtn>
                  <VBtn
                    color="primary"
                    size="small"
                    prepend-icon="ri-save-line"
                    :loading="isSubmitting"
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
                    <div class="font-weight-medium text-capitalize">
                      {{ payment.payment_method.replace('_', ' ') }}
                    </div>
                    <div v-if="payment.bank_name" class="text-caption text-medium-emphasis">
                      {{ payment.bank_name }} {{ payment.reference_number ? `(${payment.reference_number})` : '' }}
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
</style>
