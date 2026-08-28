<script setup>
import { ref, watch, computed } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  payableId: {
    type: [Number, String],
    default: null,
  },
})

const emit = defineEmits(['update:isDrawerOpen', 'paymentRecorded', 'paymentVoided'])

const snackbar = useSnackbarStore()
const isLoading = ref(false)
const isSubmitting = ref(false)
const payable = ref(null)

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

const formatCurrency = value => {
  if (!value || isNaN(value)) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)
}

const formatDate = dateString => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
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

const setFullPayment = () => {
  if (!payable.value) return
  const remaining = Number(payable.value.remaining_amount) || 0
  paymentAmount.value = remaining
  paymentAmountDisplay.value = formatInputRupiah(remaining)
}

const fetchPayableDetail = async () => {
  if (!props.payableId) return
  isLoading.value = true
  try {
    const res = await $api(`/apps/payables/${props.payableId}`)
    payable.value = res.data || res

    // Fetch supplier credits if supplier exists
    if (payable.value?.supplier_id) {
      fetchSupplierCredits(payable.value.supplier_id)
    }
  } catch (error) {
    console.error('Failed to fetch payable detail:', error)
    snackbar.show('Gagal memuat rincian hutang supplier', 'error')
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
  if (newVal && props.payableId) {
    isPaymentFormVisible.value = false
    resetForm()
    fetchPayableDetail()
  } else {
    payable.value = null
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
  if (!payable.value) return
  if (paymentAmount.value <= 0) {
    snackbar.show('Nominal pembayaran harus lebih besar dari 0', 'error')
    return
  }

  if (paymentAmount.value > Number(payable.value.remaining_amount)) {
    snackbar.show('Nominal pembayaran melebihi sisa hutang', 'error')
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

    await $api(`/apps/payables/${payable.value.id}/pay`, {
      method: 'POST',
      body: formData,
    })

    snackbar.show('Pembayaran hutang berhasil dicatat!', 'success')
    isPaymentFormVisible.value = false
    resetForm()
    await fetchPayableDetail()
    emit('paymentRecorded')
  } catch (error) {
    console.error('Failed to submit payment:', error)
    snackbar.show(error.data?.message || 'Gagal menyimpan pembayaran', 'error')
  } finally {
    isSubmitting.value = false
  }
}

const voidPayment = async paymentId => {
  if (!confirm('Apakah Anda yakin ingin membatalkan transaksi pembayaran ini?')) return

  try {
    await $api(`/apps/payables/${payable.value.id}/payments/${paymentId}`, {
      method: 'DELETE',
    })
    snackbar.show('Pembayaran berhasil dibatalkan', 'success')
    await fetchPayableDetail()
    emit('paymentVoided')
  } catch (error) {
    console.error('Failed to void payment:', error)
    snackbar.show('Gagal membatalkan pembayaran', 'error')
  }
}

const progressPercentage = computed(() => {
  if (!payable.value || Number(payable.value.total_amount) === 0) return 0
  const paid = Number(payable.value.paid_amount) || 0
  const total = Number(payable.value.total_amount) || 1
  return Math.min(100, Math.round((paid / total) * 100))
})

const isOverdue = computed(() => {
  if (!payable.value || payable.value.status === 'paid' || !payable.value.due_date) return false
  const today = new Date().toISOString().substring(0, 10)
  return String(payable.value.due_date).substring(0, 10) < today
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
    width="750"
    @update:model-value="val => emit('update:isDrawerOpen', val)"
  >
    <div class="d-flex flex-column h-100">
      <!-- Header -->
      <div class="pa-5 border-b bg-gradient-header d-flex justify-space-between align-center">
        <div class="d-flex align-center gap-3">
          <VAvatar color="primary" variant="tonal" size="44" class="rounded-lg">
            <VIcon icon="ri-wallet-3-line" size="24" />
          </VAvatar>
          <div>
            <h6 class="text-h6 font-weight-bold mb-0">
              Rincian Buku Hutang Supplier
            </h6>
            <span class="text-caption text-medium-emphasis">
              No. AP: <strong>{{ payable?.payable_number || '-' }}</strong>
            </span>
          </div>
        </div>
        <VBtn icon="ri-close-line" variant="text" size="small" @click="closeDrawer" />
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="d-flex justify-center align-center flex-grow-1">
        <VProgressCircular indeterminate color="primary" size="40" />
      </div>

      <!-- Content -->
      <div v-else-if="payable" class="pa-6 flex-grow-1 overflow-y-auto">
        <!-- Status & Progress Banner -->
        <div class="mb-5 pa-4 rounded-xl border bg-var-theme-surface shadow-xs">
          <div class="d-flex justify-space-between align-center flex-wrap gap-3 mb-3">
            <div>
              <span class="text-caption text-medium-emphasis">Supplier / Vendor:</span>
              <div class="text-subtitle-1 font-weight-bold text-primary">
                {{ payable.supplier?.name }}
              </div>
              <span class="text-caption text-medium-emphasis">
                {{ payable.supplier?.phone || '-' }} | {{ payable.branch?.name || '-' }}
              </span>
            </div>
            <div class="text-right">
              <VChip
                :color="payable.status === 'paid' ? 'success' : (isOverdue ? 'error' : (payable.status === 'partial' ? 'warning' : 'secondary'))"
                class="font-weight-bold"
                size="small"
              >
                {{ payable.status === 'paid' ? 'Lunas' : (isOverdue ? 'Lewat Jatuh Tempo' : (payable.status === 'partial' ? 'Dicicil' : 'Belum Dibayar')) }}
              </VChip>
              <div v-if="payable.due_date" class="text-caption mt-1" :class="isOverdue ? 'text-error font-weight-bold' : 'text-medium-emphasis'">
                Jatuh Tempo: {{ formatDate(payable.due_date) }}
              </div>
            </div>
          </div>

          <!-- Progress Bar Pelunasan -->
          <div class="mb-2">
            <div class="d-flex justify-space-between text-caption font-weight-medium mb-1">
              <span>Progres Pelunasan:</span>
              <span>{{ progressPercentage }}%</span>
            </div>
            <VProgressLinear
              :model-value="progressPercentage"
              height="8"
              rounded
              :color="payable.status === 'paid' ? 'success' : 'primary'"
            />
          </div>

          <VRow dense class="mt-2 pt-2 border-t text-center">
            <VCol cols="4">
              <span class="text-caption text-medium-emphasis">Total Tagihan:</span>
              <div class="font-weight-bold text-subtitle-2 text-primary font-mono">
                {{ formatCurrency(payable.total_amount) }}
              </div>
            </VCol>
            <VCol cols="4">
              <span class="text-caption text-medium-emphasis">Sudah Dibayar:</span>
              <div class="font-weight-bold text-subtitle-2 text-success font-mono">
                {{ formatCurrency(payable.paid_amount) }}
              </div>
            </VCol>
            <VCol cols="4">
              <span class="text-caption text-medium-emphasis">Sisa Hutang:</span>
              <div class="font-weight-bold text-subtitle-2 text-error font-mono">
                {{ formatCurrency(payable.remaining_amount) }}
              </div>
            </VCol>
          </VRow>
        </div>

        <!-- Section: Dokumen Terkait -->
        <div class="mb-5 pa-4 rounded-xl border bg-grey-50">
          <h6 class="text-caption font-weight-bold text-uppercase mb-2 text-medium-emphasis d-flex align-center gap-1">
            <VIcon icon="ri-file-text-line" size="16" /> Dokumen Referensi Pembelian
          </h6>
          <VRow dense>
            <VCol cols="6" sm="4">
              <div class="text-caption text-medium-emphasis">No. Faktur Supplier:</div>
              <div class="font-weight-medium font-mono text-body-2">{{ payable.invoice_number_supplier || '-' }}</div>
            </VCol>
            <VCol cols="6" sm="4">
              <div class="text-caption text-medium-emphasis">No. Purchase Order:</div>
              <div class="font-weight-medium font-mono text-body-2">{{ payable.purchase_order?.po_number || '-' }}</div>
            </VCol>
            <VCol cols="6" sm="4">
              <div class="text-caption text-medium-emphasis">Tanggal Faktur:</div>
              <div class="font-weight-medium text-body-2">{{ formatDate(payable.invoice_date) }}</div>
            </VCol>
          </VRow>
        </div>

        <!-- Section: Form Pembayaran Cicilan Baru -->
        <div v-if="payable.status !== 'paid'" class="mb-6">
          <div class="d-flex justify-space-between align-center mb-3">
            <h6 class="text-subtitle-1 font-weight-bold mb-0 d-flex align-center gap-2">
              <VIcon icon="ri-secure-payment-line" size="20" color="primary" />
              Input Cicilan Pembayaran
            </h6>
            <VBtn
              size="small"
              :variant="isPaymentFormVisible ? 'tonal' : 'flat'"
              color="primary"
              :prepend-icon="isPaymentFormVisible ? 'ri-close-line' : 'ri-add-line'"
              @click="isPaymentFormVisible = !isPaymentFormVisible"
            >
              {{ isPaymentFormVisible ? 'Batal Input' : 'Bayar / Cicil Hutang' }}
            </VBtn>
          </div>

          <VExpandTransition>
            <div v-show="isPaymentFormVisible" class="pa-5 rounded-xl border bg-primary-lighten-5 border-primary border-opacity-25 shadow-xs mb-4">
              <VRow dense>
                <VCol cols="12" sm="7">
                  <div class="d-flex justify-space-between align-center mb-1">
                    <span class="text-caption font-weight-bold">Nominal Pembayaran (Rp)</span>
                    <VBtn
                      size="x-small"
                      variant="text"
                      color="primary"
                      class="px-1 text-caption font-weight-bold"
                      @click="setFullPayment"
                    >
                      Bayar Lunas Semua ({{ formatCurrency(payable.remaining_amount) }})
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
                      { title: 'Potong Hutang Retur Supplier', value: 'supplier_credit' },
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
                    placeholder="Keterangan cicilan tahap 1, pelunasan sisa tagihan, dll..."
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
                    Simpan Pembayaran
                  </VBtn>
                </VCol>
              </VRow>
            </div>
          </VExpandTransition>
        </div>

        <!-- Section: Riwayat Cicilan / Pembayaran -->
        <div class="mb-5">
          <h6 class="text-subtitle-1 font-weight-bold mb-3 d-flex align-center gap-2">
            <VIcon icon="ri-history-line" size="20" color="primary" />
            Riwayat Cicilan & Pelunasan ({{ payable.payments?.length || 0 }})
          </h6>

          <div v-if="payable.payments && payable.payments.length > 0" class="border rounded-xl overflow-hidden shadow-xs">
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
                <tr v-for="payment in payable.payments" :key="payment.id" class="border-b">
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
            <div class="text-caption">Belum ada catatan cicilan atau pembayaran untuk faktur ini.</div>
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
.table-payments {
  border-collapse: collapse;
}
.table-payments th {
  font-weight: 600;
}
</style>
