<script setup>
import { ref, watch, computed } from 'vue'
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

const activeBranch = computed(() => {
  if (userData.value?.assignments?.length > 0) {
    // Basic fallback to the first assigned branch for printing
    return { name: userData.value.assignments[0].branch_name }
  }
  
  return { name: 'Cabang Utama' }
})

const paymentForm = ref({
  amount: 0,
  payment_date: new Date().toISOString().substr(0, 10),
  payment_method: 'cash',
  payment_proof: null,
  bank_name: '',
  bank_account_number: '',
  bank_account_name: '',
  transfer_phone_number: '',
})

const fetchReceivable = async id => {
  isLoading.value = true
  try {
    const response = await $api(`/apps/receivables/${id}`)

    receivable.value = response.data || response
    paymentForm.value.amount = remainingBalance.value
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
  } else {
    receivable.value = null
  }
}, { immediate: true })

const remainingBalance = computed(() => {
  if (!receivable.value) return 0
  
  return Number(receivable.value.amount_due) - Number(receivable.value.amount_paid)
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
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value || 0)
}

const formatDate = dateString => {
  if (!dateString) return '-'
  
  return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
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

    snackbar.show('Pembayaran berhasil dicatat', 'success')
    emit('paymentSaved')
    
    // Save last payment for printing and show success dialog
    lastPayment.value = res.payment

    // Append user to payment if not there, for printer
    if (!lastPayment.value.user && userData.value) {
      lastPayment.value.user = { name: userData.value.fullName || userData.value.name }
    }
    
    // Fetch fresh receivable with relations
    await fetchReceivable(receivable.value.id)
    
    isSuccessDialogVisible.value = true
    
    // Reset form
    paymentForm.value.amount = 0
    paymentForm.value.payment_method = 'cash'
    paymentForm.value.payment_proof = null
    paymentForm.value.bank_name = ''
    paymentForm.value.bank_account_number = ''
    paymentForm.value.bank_account_name = ''
    paymentForm.value.transfer_phone_number = ''
  } catch (error) {
    console.error(error)
    snackbar.show(error.response?.data?.message || 'Gagal memproses pembayaran', 'error')
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
    window.print()
  }, 100)
}
</script>

<template>
  <VNavigationDrawer
    :model-value="props.isDrawerOpen"
    temporary
    location="end"
    width="500"
    @update:model-value="emit('update:isDrawerOpen', $event)"
  >
    <!-- Header -->
    <div class="d-flex align-center pa-6 pb-4">
      <h6 class="text-h6">
        Detail Piutang
      </h6>
      <VSpacer />
      <IconBtn @click="emit('update:isDrawerOpen', false)">
        <VIcon icon="ri-close-line" />
      </IconBtn>
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
        <VProgressCircular
          indeterminate
          color="primary"
        />
      </div>

      <div
        v-else-if="receivable"
        class="pa-6"
      >
        <!-- Button Print Terakhir (jika ada pembayaran) -->
        <VBtn 
          v-if="receivable.payments?.length > 0" 
          color="secondary" 
          variant="outlined" 
          block 
          class="mb-4" 
          prepend-icon="ri-printer-line"
          @click="() => { lastPayment = receivable.payments[receivable.payments.length - 1]; printReceipt(); }"
        >
          Cetak Struk Pembayaran Terakhir
        </VBtn>

        <!-- Info Ringkas -->
        <VCard
          class="mb-4 bg-light-primary"
          variant="flat"
        >
          <VCardText>
            <div class="d-flex justify-space-between mb-2">
              <span class="font-weight-bold">Pelanggan:</span>
              <span>{{ receivable.customer?.name || '-' }}</span>
            </div>
            <div class="d-flex justify-space-between mb-2">
              <span class="font-weight-bold">No. Nota (Sale):</span>
              <span>{{ receivable.sale?.invoice_number || '-' }}</span>
            </div>
            <div class="d-flex justify-space-between mb-2">
              <span class="font-weight-bold">Jatuh Tempo:</span>
              <span class="text-error font-weight-bold">{{ formatDate(receivable.due_date) }}</span>
            </div>
            <VDivider class="my-2" />
            <div class="d-flex justify-space-between mb-1">
              <span>Total Hutang:</span>
              <span>{{ formatCurrency(receivable.amount_due) }}</span>
            </div>
            <div class="d-flex justify-space-between mb-1">
              <span>Sudah Dibayar:</span>
              <span class="text-success">{{ formatCurrency(receivable.amount_paid) }}</span>
            </div>
            <div class="d-flex justify-space-between mt-2 pt-2 border-t">
              <span class="text-h6 font-weight-bold">Sisa Hutang:</span>
              <span class="text-h6 font-weight-bold text-error">{{ formatCurrency(remainingBalance) }}</span>
            </div>
          </VCardText>
        </VCard>

        <!-- Daftar Barang -->
        <p class="font-weight-bold mb-2">
          Barang yang Dibeli (Utang)
        </p>
        <VCard
          class="mb-4 border"
          variant="flat"
        >
          <VTable density="compact">
            <thead>
              <tr>
                <th>Barang</th>
                <th class="text-center">
                  Qty
                </th>
                <th class="text-right">
                  Harga
                </th>
                <th class="text-right">
                  Subtotal
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!receivable.sale?.items?.length">
                <td
                  colspan="4"
                  class="text-center text-medium-emphasis"
                >
                  Data barang tidak ditemukan
                </td>
              </tr>
              <tr
                v-for="item in receivable.sale?.items"
                :key="item.id"
              >
                <td>{{ item.product_branch?.product?.name || 'Produk Tidak Dikenal' }}</td>
                <td class="text-center">
                  {{ item.qty }}
                </td>
                <td class="text-right">
                  {{ formatCurrency(item.price) }}
                </td>
                <td class="text-right">
                  {{ formatCurrency(item.subtotal) }}
                </td>
              </tr>
            </tbody>
          </VTable>
        </VCard>

        <!-- Form Pembayaran (Jika belum lunas) -->
        <VCard
          v-if="receivable.status !== 'paid'"
          class="mb-4 border"
        >
          <VCardTitle class="text-base py-3 bg-light">
            Terima Pembayaran
          </VCardTitle>
          <VDivider />
          <VCardText>
            <VForm @submit.prevent="submitPayment">
              <VRow>
                <VCol cols="12">
                  <VTextField 
                    v-model="amountDisplay" 
                    label="Nominal Dibayar" 
                    type="text"
                    prefix="Rp"
                    required 
                  />
                </VCol>
                <VCol
                  cols="12"
                  md="6"
                >
                  <VTextField 
                    v-model="paymentForm.payment_date" 
                    label="Tanggal Bayar" 
                    type="date"
                    required 
                  />
                </VCol>
                <VCol
                  cols="12"
                  md="6"
                >
                  <VSelect 
                    v-model="paymentForm.payment_method" 
                    :items="['cash', 'transfer', 'qris']" 
                    label="Metode Pembayaran" 
                  />
                </VCol>
              
                <!-- Extra Fields for Non-Cash -->
                <VCol
                  v-if="paymentForm.payment_method !== 'cash'"
                  cols="12"
                >
                  <VCard
                    variant="outlined"
                    class="pa-4 bg-light"
                  >
                    <VRow>
                      <VCol
                        v-if="paymentForm.payment_method === 'transfer'"
                        cols="12"
                        md="6"
                      >
                        <VTextField
                          v-model="paymentForm.bank_name"
                          label="Nama Bank (Cth: BCA, Mandiri)"
                        />
                      </VCol>
                      <VCol
                        v-if="paymentForm.payment_method === 'transfer'"
                        cols="12"
                        md="6"
                      >
                        <VTextField
                          v-model="paymentForm.bank_account_number"
                          label="Nomor Rekening"
                        />
                      </VCol>
                      <VCol
                        v-if="paymentForm.payment_method === 'transfer'"
                        cols="12"
                        md="6"
                      >
                        <VTextField
                          v-model="paymentForm.bank_account_name"
                          label="Atas Nama Rekening"
                        />
                      </VCol>
                      <VCol
                        v-if="paymentForm.payment_method === 'qris'"
                        cols="12"
                        md="6"
                      >
                        <VTextField
                          v-model="paymentForm.transfer_phone_number"
                          label="No. Handphone / E-Wallet"
                        />
                      </VCol>
                      <VCol cols="12">
                        <VFileInput 
                          v-model="paymentForm.payment_proof" 
                          label="Bukti Pembayaran (Opsional)" 
                          accept="image/*" 
                          prepend-icon="ri-image-add-line" 
                        />
                      </VCol>
                    </VRow>
                  </VCard>
                </VCol>

                <VCol cols="12">
                  <VBtn
                    type="submit"
                    color="success"
                    block
                    prepend-icon="ri-check-line"
                  >
                    Proses Pembayaran
                  </VBtn>
                </VCol>
              </VRow>
            </VForm>
          </VCardText>
        </VCard>

        <!-- Riwayat Pembayaran -->
        <p class="font-weight-bold mb-2">
          Riwayat Pembayaran
        </p>
        <VTable
          density="compact"
          class="border"
        >
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Metode</th>
              <th class="text-right">
                Nominal
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="receivable.payments?.length === 0">
              <td
                colspan="3"
                class="text-center text-medium-emphasis"
              >
                Belum ada pembayaran
              </td>
            </tr>
            <tr
              v-for="payment in receivable.payments"
              :key="payment.id"
            >
              <td>{{ formatDate(payment.payment_date) }}</td>
              <td class="text-capitalize">
                {{ payment.payment_method }}
              </td>
              <td class="text-right text-success">
                +{{ formatCurrency(payment.amount) }}
              </td>
            </tr>
          </tbody>
        </VTable>
      </div>
    </div>
  </VNavigationDrawer>

  <!-- Success Dialog for Printing -->
  <VDialog
    v-model="isSuccessDialogVisible"
    max-width="400"
    persistent
  >
    <VCard class="text-center pa-6">
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
        Cicilan piutang telah berhasil dicatat ke dalam sistem.
      </VCardText>
      
      <VCardActions class="d-flex flex-column gap-3 mt-4">
        <VBtn
          color="primary"
          block
          variant="flat"
          size="large"
          prepend-icon="ri-printer-line"
          @click="printReceipt"
        >
          Cetak Struk Pembayaran
        </VBtn>
        <VBtn
          color="secondary"
          block
          variant="outlined"
          size="large"
          @click="isSuccessDialogVisible = false"
        >
          Tutup
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Hidden Printable Component -->
  <ReceivableReceiptPrinter 
    :receivable="receivable" 
    :last-payment="lastPayment"
    :branch="activeBranch"
  />
</template>
