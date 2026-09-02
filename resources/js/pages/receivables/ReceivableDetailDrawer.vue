<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import { $api } from '@/utils/api'
import ReceivableReceiptPrinter from './ReceivableReceiptPrinter.vue'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  receivableId: {
    type: Number,
    default: null,
  },
})

const emit = defineEmits(['update:isDrawerOpen', 'paymentSaved'])

const snackbar = useSnackbarStore()
const userData = useCookie('userData')

const receivable = ref(null)
const isLoading = ref(false)
const lastPayment = ref(null)
const isSuccessDialogVisible = ref(false)

// Printer & Settings from Database
const receiptPrinterRef = ref(null)
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

// Bank Accounts from Database
const bankAccounts = ref([])
const isLoadingBankAccounts = ref(false)

const fetchBankAccounts = async () => {
  isLoadingBankAccounts.value = true
  try {
    const res = await $api('/apps/bank-accounts')
    bankAccounts.value = (res.data || res || []).filter(b => b.is_active)
  } catch (e) {
    console.error('Failed to load bank accounts:', e)
  } finally {
    isLoadingBankAccounts.value = false
  }
}

const activeBranch = computed(() => {
  if (receivable.value?.sale?.branch) {
    return receivable.value.sale.branch
  }
  if (userData.value?.assignments?.length > 0) {
    return { name: userData.value.assignments[0].branch_name }
  }
  return { name: 'Cabang Toko' }
})

const paymentForm = ref({
  amount: 0,
  payment_date: new Date().toISOString().substr(0, 10),
  payment_method: 'cash',
  bank_account_id: null,
  payment_proof: null,
  bank_name: '',
  bank_account_number: '',
  bank_account_name: '',
  transfer_phone_number: '',
})

const onBankSelected = bankId => {
  const bank = bankAccounts.value.find(b => b.id === bankId)
  if (bank) {
    paymentForm.value.bank_account_id = bank.id
    paymentForm.value.bank_name = bank.bank_name
    paymentForm.value.bank_account_number = bank.account_number
    paymentForm.value.bank_account_name = bank.account_name
  }
}

// Email logs and send email dialog
const emailLogs = ref([])
const isLoadingEmailLogs = ref(false)
const isSendEmailDialogVisible = ref(false)
const emailInput = ref('')
const isSendingEmail = ref(false)
const isRetryingEmail = ref({})

const fetchEmailLogs = async id => {
  if (!id) return
  isLoadingEmailLogs.value = true
  try {
    const res = await $api(`/apps/receivables/${id}/email-logs`)
    emailLogs.value = res.data || []
  } catch (e) {
    console.error('Failed to fetch email logs:', e)
  } finally {
    isLoadingEmailLogs.value = false
  }
}

const openSendEmailDialog = () => {
  emailInput.value = receivable.value?.customer?.email || ''
  isSendEmailDialogVisible.value = true
}

const submitSendEmail = async () => {
  if (!emailInput.value) {
    snackbar.show('Alamat email penerima wajib diisi', 'warning')
    return
  }
  isSendingEmail.value = true
  try {
    const res = await $api(`/apps/receivables/${receivable.value.id}/send-email`, {
      method: 'POST',
      body: { email: emailInput.value },
    })
    snackbar.show(res.message || 'Surat tagihan berhasil dikirim ke email', 'success')
    isSendEmailDialogVisible.value = false
    await fetchEmailLogs(receivable.value.id)
  } catch (error) {
    console.error(error)
    const errText = error.response?._data?.message || error.data?.message || error.message || 'Gagal mengirim email tagihan'
    snackbar.show(errText, 'error')
    await fetchEmailLogs(receivable.value.id)
  } finally {
    isSendingEmail.value = false
  }
}

const retryEmail = async logId => {
  isRetryingEmail.value[logId] = true
  try {
    const res = await $api(`/apps/email-logs/${logId}/retry`, {
      method: 'POST',
    })
    snackbar.show(res.message || 'Email berhasil dikirim ulang', 'success')
    await fetchEmailLogs(receivable.value.id)
  } catch (error) {
    console.error(error)
    const errText = error.response?._data?.message || error.data?.message || error.message || 'Gagal mengirim ulang email'
    snackbar.show(errText, 'error')
    await fetchEmailLogs(receivable.value.id)
  } finally {
    isRetryingEmail.value[logId] = false
  }
}

const fetchReceivable = async id => {
  isLoading.value = true
  try {
    const response = await $api(`/apps/receivables/${id}`)
    receivable.value = response.data || response
    paymentForm.value.amount = remainingBalance.value
    fetchEmailLogs(id)
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal memuat detail piutang', 'error')
  } finally {
    isLoading.value = false
  }
}

watch(() => props.receivableId, newId => {
  if (newId) {
    fetchReceivable(newId)
    fetchBankAccounts()
    fetchReceiptSettings()
  } else {
    receivable.value = null
    emailLogs.value = []
  }
}, { immediate: true })

const remainingBalance = computed(() => {
  if (!receivable.value) return 0
  return Math.max(0, Number(receivable.value.amount_due) - Number(receivable.value.amount_paid))
})

const amountDisplay = computed({
  get: () => {
    return paymentForm.value.amount ? new Intl.NumberFormat('id-ID').format(paymentForm.value.amount) : ''
  },
  set: val => {
    const numericStr = String(val).replace(/\D/g, '')
    paymentForm.value.amount = numericStr ? parseInt(numericStr, 10) : 0
  },
})

const formatCurrency = value => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value || 0)
}

const formatDate = dateString => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}

const formatDateTime = dateString => {
  if (!dateString) return '-'
  const d = new Date(dateString)
  if (isNaN(d.getTime())) return dateString
  return d.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const submitPayment = async () => {
  if (paymentForm.value.amount <= 0) {
    snackbar.show('Nominal pembayaran tidak valid', 'error')
    return
  }
  if (paymentForm.value.amount > remainingBalance.value) {
    snackbar.show('Nominal pembayaran melebihi sisa hutang', 'error')
    return
  }

  try {
    const formData = new FormData()

    formData.append('amount', paymentForm.value.amount)
    formData.append('payment_date', paymentForm.value.payment_date)
    formData.append('payment_method', paymentForm.value.payment_method)
    
    if (paymentForm.value.payment_method !== 'cash') {
      if (paymentForm.value.bank_account_id) formData.append('bank_account_id', paymentForm.value.bank_account_id)
      if (paymentForm.value.bank_name) formData.append('bank_name', paymentForm.value.bank_name)
      if (paymentForm.value.bank_account_number) formData.append('bank_account_number', paymentForm.value.bank_account_number)
      if (paymentForm.value.bank_account_name) formData.append('bank_account_name', paymentForm.value.bank_account_name)
      if (paymentForm.value.transfer_phone_number) formData.append('transfer_phone_number', paymentForm.value.transfer_phone_number)
      if (paymentForm.value.payment_proof && paymentForm.value.payment_proof.length > 0) {
        formData.append('payment_proof', paymentForm.value.payment_proof[0])
      }
    }

    const res = await $api(`/apps/receivables/${receivable.value.id}/pay`, {
      method: 'POST',
      body: formData,
    })

    snackbar.show('Pembayaran berhasil dicatat & saldo diperbarui!', 'success')
    emit('paymentSaved')
    
    // Save last payment for printing and show success dialog
    lastPayment.value = res.payment

    if (!lastPayment.value.user && userData.value) {
      lastPayment.value.user = { name: userData.value.fullName || userData.value.name }
    }
    
    // Fetch fresh receivable with relations
    await fetchReceivable(receivable.value.id)
    
    isSuccessDialogVisible.value = true
    
    // Reset form
    paymentForm.value.amount = 0
    paymentForm.value.payment_method = 'cash'
    paymentForm.value.bank_account_id = null
    paymentForm.value.payment_proof = null
    paymentForm.value.bank_name = ''
    paymentForm.value.bank_account_number = ''
    paymentForm.value.bank_account_name = ''
    paymentForm.value.transfer_phone_number = ''
  } catch (error) {
    console.error(error)
    const errText = error.response?._data?.message || error.data?.message || error.message || 'Gagal memproses pembayaran'
    snackbar.show(errText, 'error')
  }
}

const getStatusColor = status => {
  switch (status) {
  case 'paid': return 'success'
  case 'partial': return 'warning'
  default: return 'error'
  }
}

const printReceipt = () => {
  setTimeout(() => {
    if (receiptPrinterRef.value?.print) {
      receiptPrinterRef.value.print()
    }
  }, 100)
}

const printPastPaymentReceipt = payment => {
  lastPayment.value = payment
  printReceipt()
}

onMounted(() => {
  fetchBankAccounts()
  fetchReceiptSettings()
})
</script>

<template>
  <VNavigationDrawer
    :model-value="props.isDrawerOpen"
    temporary
    location="end"
    :width="$vuetify.display.xs ? '100%' : ($vuetify.display.smAndDown ? '90vw' : 580)"
    @update:model-value="emit('update:isDrawerOpen', $event)"
  >
    <!-- Header -->
    <div class="d-flex align-center pa-6 pb-4">
      <div class="d-flex align-center gap-2">
        <VAvatar color="primary" variant="tonal" size="36">
          <VIcon icon="ri-hand-coin-line" size="20" />
        </VAvatar>
        <h6 class="text-h6 font-weight-bold mb-0">
          Detail Piutang Pelanggan
        </h6>
      </div>
      <VSpacer />
      <VBtn
        icon
        variant="tonal"
        color="secondary"
        size="small"
        @click="emit('update:isDrawerOpen', false)"
      >
        <VIcon icon="ri-close-line" />
      </VBtn>
    </div>
    <VDivider />

    <div
      class="overflow-auto"
      style="max-height: calc(100vh - 80px);"
    >
      <div
        v-if="isLoading"
        class="d-flex justify-center pa-10"
      >
        <VProgressCircular indeterminate color="primary" />
      </div>

      <div
        v-else-if="receivable"
        class="pa-6 pt-4"
      >
        <!-- Customer & Sale Info -->
        <VCard
          variant="tonal"
          class="mb-4 pa-4 rounded-xl"
        >
          <div class="d-flex justify-space-between align-start mb-2">
            <div>
              <p class="text-caption mb-0 text-medium-emphasis">Pelanggan</p>
              <h5 class="text-h6 font-weight-bold">
                {{ receivable.customer?.name || 'Pelanggan Umum' }}
              </h5>
              <div class="text-caption text-medium-emphasis">
                {{ receivable.customer?.phone || '-' }} | {{ receivable.customer?.address || '-' }}
              </div>
            </div>
            <VChip
              :color="getStatusColor(receivable.status)"
              size="small"
              class="text-capitalize font-weight-bold"
            >
              {{ receivable.status === 'paid' ? 'Lunas' : (receivable.status === 'partial' ? 'Cicilan' : 'Belum Bayar') }}
            </VChip>
          </div>

          <VDivider class="my-3" />

          <div class="d-flex justify-space-between align-center text-caption">
            <div>
              <span class="text-medium-emphasis">No. Transaksi:</span>
              <strong class="font-mono ml-1">{{ receivable.sale?.invoice_number }}</strong>
            </div>
            <div>
              <span class="text-medium-emphasis">Jatuh Tempo:</span>
              <strong class="text-error ml-1">{{ formatDate(receivable.due_date) }}</strong>
            </div>
          </div>
        </VCard>

        <!-- Balance Summary -->
        <VRow class="mb-4">
          <VCol cols="4">
            <VCard
              variant="outlined"
              class="pa-3 text-center rounded-xl"
            >
              <div class="text-caption text-medium-emphasis">Total Piutang</div>
              <div class="text-body-1 font-weight-bold">
                {{ formatCurrency(receivable.amount_due) }}
              </div>
            </VCard>
          </VCol>
          <VCol cols="4">
            <VCard
              variant="outlined"
              class="pa-3 text-center rounded-xl"
            >
              <div class="text-caption text-success">Sudah Dibayar</div>
              <div class="text-body-1 font-weight-bold text-success">
                {{ formatCurrency(receivable.amount_paid) }}
              </div>
            </VCard>
          </VCol>
          <VCol cols="4">
            <VCard
              variant="outlined"
              class="pa-3 text-center rounded-xl"
              :class="remainingBalance > 0 ? 'border-error' : ''"
            >
              <div class="text-caption text-error">Sisa Piutang</div>
              <div class="text-body-1 font-weight-bold text-error">
                {{ formatCurrency(remainingBalance) }}
              </div>
            </VCard>
          </VCol>
        </VRow>

        <!-- Action Button: Kirim Tagihan Email -->
        <div class="mb-4">
          <VBtn
            block
            color="info"
            variant="tonal"
            prepend-icon="ri-mail-send-line"
            class="rounded-lg"
            @click="openSendEmailDialog"
          >
            Kirim Surat Tagihan via Email
          </VBtn>
        </div>

        <!-- Payment Form -->
        <VCard
          v-if="remainingBalance > 0"
          variant="outlined"
          class="mb-6 rounded-xl border-primary"
        >
          <VCardItem class="bg-primary bg-opacity-10 py-3">
            <VCardTitle class="text-subtitle-1 font-weight-bold text-primary d-flex align-center gap-2">
              <VIcon icon="ri-secure-payment-line" size="20" />
              Catat Pembayaran Cicilan
            </VCardTitle>
          </VCardItem>

          <VCardText class="pa-4">
            <VForm @submit.prevent="submitPayment">
              <VRow dense>
                <VCol cols="12" sm="6">
                  <VTextField 
                    v-model="amountDisplay" 
                    label="Nominal Bayar" 
                    type="text"
                    prefix="Rp"
                    required 
                  />
                </VCol>
                <VCol cols="12" sm="6">
                  <VTextField 
                    v-model="paymentForm.payment_date" 
                    label="Tanggal Bayar" 
                    type="date"
                    required 
                  />
                </VCol>

                <VCol cols="12">
                  <VSelect 
                    v-model="paymentForm.payment_method" 
                    :items="[
                      { title: 'Kas Tunai Toko', value: 'cash' },
                      { title: 'Transfer Bank (Tambah Saldo Rekening)', value: 'bank_transfer' },
                    ]"
                    item-title="title"
                    item-value="value"
                    label="Metode Pembayaran" 
                  />
                </VCol>
              
                <!-- Pilihan Rekening Bank Tujuan Jika Non-Tunai -->
                <VCol
                  v-if="paymentForm.payment_method === 'bank_transfer' || paymentForm.payment_method === 'qris' || paymentForm.payment_method === 'transfer'"
                  cols="12"
                >
                  <VCard
                    variant="tonal"
                    color="info"
                    class="pa-3 mb-2 rounded-lg"
                  >
                    <div class="text-caption font-weight-bold mb-2">
                      🏦 Pilih Rekening Bank Tujuan (Saldo Otomatis Bertambah):
                    </div>
                    
                    <VSelect
                      v-model="paymentForm.bank_account_id"
                      :items="bankAccounts"
                      :item-title="item => `${item.bank_name} - ${item.account_number} (a/n ${item.account_name})`"
                      item-value="id"
                      label="Rekening Bank Tujuan"
                      placeholder="Pilih rekening bank..."
                      density="compact"
                      class="mb-2"
                      @update:model-value="onBankSelected"
                    />

                    <VRow dense>
                      <VCol cols="12" sm="6">
                        <VTextField
                          v-model="paymentForm.bank_name"
                          label="Nama Bank"
                          density="compact"
                        />
                      </VCol>
                      <VCol cols="12" sm="6">
                        <VTextField
                          v-model="paymentForm.bank_account_number"
                          label="Nomor Rekening"
                          density="compact"
                        />
                      </VCol>
                      <VCol cols="12">
                        <VTextField
                          v-model="paymentForm.bank_account_name"
                          label="Atas Nama Rekening"
                          density="compact"
                        />
                      </VCol>
                    </VRow>
                  </VCard>
                </VCol>

                <VCol cols="12">
                  <VFileInput 
                    v-model="paymentForm.payment_proof" 
                    label="Bukti Transfer / Nota (Opsional)" 
                    accept="image/*" 
                    prepend-icon="ri-image-add-line" 
                  />
                </VCol>

                <VCol cols="12" class="mt-2">
                  <VBtn
                    type="submit"
                    color="success"
                    block
                    size="large"
                    prepend-icon="ri-check-double-line"
                    class="font-weight-bold"
                  >
                    Simpan Pembayaran & Perbarui Saldo
                  </VBtn>
                </VCol>
              </VRow>
            </VForm>
          </VCardText>
        </VCard>

        <!-- Riwayat Pembayaran -->
        <div class="d-flex align-center justify-space-between mb-2">
          <p class="font-weight-bold mb-0 d-flex align-center gap-1">
            <VIcon icon="ri-history-line" size="18" color="primary" />
            Riwayat Cicilan & Pembayaran
          </p>
        </div>

        <VTable
          density="compact"
          class="border rounded-xl mb-6"
        >
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Metode</th>
              <th class="text-right">Nominal</th>
              <th class="text-center" style="width: 50px;">Cetak</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="receivable.payments?.length === 0">
              <td
                colspan="4"
                class="text-center text-medium-emphasis py-3"
              >
                Belum ada catatan pembayaran cicilan
              </td>
            </tr>
            <tr
              v-for="payment in receivable.payments"
              :key="payment.id"
            >
              <td>{{ formatDate(payment.payment_date) }}</td>
              <td class="text-capitalize">
                {{ payment.payment_method === 'bank_transfer' ? 'Transfer Bank' : (payment.payment_method === 'qris' ? 'QRIS' : (payment.payment_method === 'cash' ? 'Tunai' : payment.payment_method)) }}
                <div v-if="payment.bank_account || payment.bank_name" style="font-size: 10px; color: #666;">
                  {{ payment.bank_account ? payment.bank_account.bank_name : payment.bank_name }}
                </div>
              </td>
              <td class="text-right text-success font-weight-bold">
                +{{ formatCurrency(payment.amount) }}
              </td>
              <td class="text-center">
                <VBtn
                  icon="ri-printer-line"
                  size="x-small"
                  variant="text"
                  color="primary"
                  title="Cetak Kuitansi Pembayaran"
                  @click="printPastPaymentReceipt(payment)"
                />
              </td>
            </tr>
          </tbody>
        </VTable>

        <!-- Section Riwayat Email Log & Audit Trail -->
        <div class="d-flex align-center justify-space-between mt-6 mb-2">
          <p class="font-weight-bold mb-0 d-flex align-center gap-1">
            <VIcon icon="ri-mail-check-line" size="18" color="primary" />
            Riwayat Log Pengiriman Email
          </p>
          <VBtn
            size="x-small"
            variant="text"
            icon="ri-refresh-line"
            :loading="isLoadingEmailLogs"
            @click="fetchEmailLogs(receivable.id)"
          />
        </div>

        <VCard class="border rounded-xl" variant="flat" :loading="isLoadingEmailLogs">
          <VProgressLinear v-if="isLoadingEmailLogs" indeterminate color="primary" height="2" />
          <VTable density="compact">
            <thead>
              <tr>
                <th>Tanggal & Waktu</th>
                <th>Tipe Dokumen</th>
                <th>Penerima</th>
                <th>Status</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="emailLogs.length === 0">
                <td colspan="5" class="text-center text-medium-emphasis py-4">
                  Belum ada riwayat pengiriman email
                </td>
              </tr>
              <tr v-for="log in emailLogs" :key="log.id">
                <td class="text-caption font-mono">{{ formatDateTime(log.sent_at || log.created_at) }}</td>
                <td>
                  <VChip size="x-small" variant="outlined" color="primary">
                    {{ log.mailable_type?.includes('Invoice') ? 'Surat Tagihan' : 'Kwitansi Cicilan' }}
                  </VChip>
                </td>
                <td class="text-caption font-weight-bold">{{ log.recipient_email }}</td>
                <td>
                  <VChip
                    size="x-small"
                    :color="log.status === 'sent' ? 'success' : 'error'"
                    class="text-capitalize"
                  >
                    {{ log.status === 'sent' ? 'Terkirim' : 'Gagal' }}
                  </VChip>
                </td>
                <td class="text-center">
                  <VBtn
                    v-if="log.status === 'failed'"
                    size="x-small"
                    variant="tonal"
                    color="warning"
                    prepend-icon="ri-refresh-line"
                    :loading="isRetryingEmail[log.id]"
                    @click="retryEmail(log.id)"
                  >
                    Kirim Ulang
                  </VBtn>
                  <span v-else class="text-caption text-success font-weight-bold">
                    <VIcon icon="ri-check-line" size="14" /> Sukses
                  </span>
                </td>
              </tr>
            </tbody>
          </VTable>
        </VCard>
      </div>
    </div>
  </VNavigationDrawer>

  <!-- Dialog Kirim Surat Tagihan Email Manual -->
  <VDialog
    v-model="isSendEmailDialogVisible"
    max-width="480"
  >
    <VCard class="rounded-xl">
      <VCardTitle class="pa-5 pb-3 font-weight-bold text-h6 d-flex align-center gap-2">
        <VIcon icon="ri-mail-send-line" color="primary" />
        Kirim Surat Tagihan Piutang
      </VCardTitle>
      
      <VCardText class="px-5 py-3">
        <p class="text-caption text-medium-emphasis mb-3">
          Sistem akan men-generate PDF Invoice Tagihan secara otomatis dan mengirimkannya langsung ke alamat email pelanggan.
        </p>

        <VTextField
          v-model="emailInput"
          label="Alamat Email Pelanggan"
          placeholder="contoh@domain.com"
          type="email"
          prepend-inner-icon="ri-mail-line"
          outlined
          dense
          required
        />
      </VCardText>

      <VCardActions class="pa-5 pt-0 d-flex justify-end gap-2">
        <VBtn
          variant="outlined"
          color="secondary"
          @click="isSendEmailDialogVisible = false"
        >
          Batal
        </VBtn>
        <VBtn
          color="primary"
          variant="flat"
          prepend-icon="ri-send-plane-fill"
          :loading="isSendingEmail"
          @click="submitSendEmail"
        >
          Kirim Sekarang
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Success Dialog for Printing -->
  <VDialog
    v-model="isSuccessDialogVisible"
    max-width="400"
    persistent
  >
    <VCard class="text-center pa-6 rounded-xl">
      <VIcon
        icon="ri-checkbox-circle-fill"
        color="success"
        size="64"
        class="mb-4 mx-auto"
      />
      <VCardTitle class="text-h5 font-weight-bold mb-2">
        Pembayaran Berhasil!
      </VCardTitle>
      <VCardText>
        Cicilan piutang telah berhasil dicatat & saldo rekening bank diperbarui secara otomatis.
      </VCardText>
      
      <VCardActions class="d-flex flex-column gap-3 mt-4">
        <VBtn
          color="primary"
          block
          variant="flat"
          size="large"
          prepend-icon="ri-printer-line"
          class="font-weight-bold"
          @click="printReceipt"
        >
          Cetak Kuitansi Pembayaran
        </VBtn>
        <VBtn
          color="secondary"
          block
          variant="outlined"
          size="large"
          @click="isSuccessDialogVisible = false"
        >
          Selesai / Tutup
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Hidden Printable Component using Database Paper Rules -->
  <ReceivableReceiptPrinter 
    ref="receiptPrinterRef"
    :receivable="receivable" 
    :last-payment="lastPayment"
    :branch="activeBranch"
    :setting="activeReceiptSetting"
    :print-format="activeReceiptSetting?.name?.toLowerCase().includes('thermal') ? 'thermal' : 'kwitansi'"
  />
</template>
