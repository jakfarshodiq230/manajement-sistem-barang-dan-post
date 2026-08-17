<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import ReceivableDetailDrawer from './ReceivableDetailDrawer.vue'
import { $api } from '@/utils/api'

const receivables = ref([])
const isLoading = ref(false)
const totalItems = ref(0)
const options = ref({ page: 1, itemsPerPage: 10 })

const selectedStatus = ref('')
const selectedBranch = ref(null)
const branches = ref([])

const fetchBranches = async () => {
  try {
    const data = await ('/apps/branches')
    branches.value = data.data || data
  } catch (error) {
    console.error(error)
  }
}

onMounted(() => {
  fetchBranches()
})
const searchQuery = ref('')
let searchTimeout = null

watch(searchQuery, () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    options.value.page = 1
    fetchReceivables()
  }, 500)
})

const statusOptions = [
  { title: 'Semua Status', value: '' },
  { title: 'Belum Lunas (Unpaid)', value: 'unpaid' },
  { title: 'Lunas Sebagian (Partial)', value: 'partial' },
  { title: 'Lunas (Paid)', value: 'paid' },
]

const isDetailDrawerVisible = ref(false)
const selectedReceivableId = ref(null)

const isConfirmDeleteVisible = ref(false)
const receivableToDelete = ref(null)
const voidPin = ref('')

const snackbar = useSnackbarStore()

const fetchReceivables = async () => {
  isLoading.value = true
  try {
    const data = await $api('/apps/receivables', {
      params: {
        page: options.value.page,
        itemsPerPage: options.value.itemsPerPage,
        status: selectedStatus.value || undefined,
      },
    })

    receivables.value = data.data || data
    totalItems.value = data.total || (data.data ? data.data.length : data.length)
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mengambil data piutang', 'error')
  } finally {
    isLoading.value = false
  }
}

watch(selectedStatus, () => {
  options.value.page = 1
  fetchReceivables()
})

onMounted(() => {
  fetchReceivables()
})

const tableHeaders = [
  { title: 'NO NOTA', key: 'sale.invoice_number' },
  { title: 'CABANG', key: 'sale.branch.name' },
  { title: 'PETUGAS', key: 'sale.user.name' },
  { title: 'PELANGGAN', key: 'customer.name' },
  { title: 'JATUH TEMPO', key: 'due_date' },
  { title: 'SISA HUTANG', key: 'remaining', sortable: false },
  { title: 'STATUS', key: 'status', align: 'center' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const formatCurrency = value => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value || 0)
}

const formatDate = dateString => {
  if (!dateString) return '-'
  
  return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const getStatusColor = status => {
  switch (status) {
  case 'paid': return 'success'
  case 'partial': return 'warning'
  default: return 'error'
  }
}

const getStatusText = status => {
  switch (status) {
  case 'paid': return 'LUNAS'
  case 'partial': return 'SEBAGIAN'
  default: return 'BELUM LUNAS'
  }
}

const isOverdue = (dueDate, status) => {
  if (status === 'paid') return false
  const today = new Date()

  today.setHours(0, 0, 0, 0)

  const due = new Date(dueDate)
  
  return due < today
}

const viewDetails = id => {
  selectedReceivableId.value = id
  isDetailDrawerVisible.value = true
}

const onPaymentSaved = () => {
  fetchReceivables()
}

const confirmDelete = item => {
  receivableToDelete.value = item
  voidPin.value = ''
  isConfirmDeleteVisible.value = true
}

const executeDeleteReceivable = async () => {
  if (!receivableToDelete.value) return
  if (!voidPin.value) {
    snackbar.show('Silakan masukkan PIN Anda', 'error')
    
    return
  }
  
  isLoading.value = true
  try {
    await $api(`/apps/receivables/${receivableToDelete.value.id}`, {
      method: 'DELETE',
      body: { pin: voidPin.value },
    })

    snackbar.show('Piutang dan Penjualan berhasil dibatalkan, stok telah dikembalikan.', 'success')
    isConfirmDeleteVisible.value = false
    fetchReceivables()
  } catch (error) {
    console.error(error)

    const errorMsg = error.response?._data?.message || 'Gagal menghapus piutang.'

    snackbar.show(errorMsg, 'error')
  } finally {
    isLoading.value = false
    receivableToDelete.value = null
  }
}
</script>

<template>
  <div>
    <p class="text-2xl mb-6">
      Manajemen Piutang
    </p>

    <!-- Card -->
    <VCard>
      <!-- Card Header -->
      <VCardText class="d-flex flex-wrap gap-4 align-center">
        <VSelect
          v-model="selectedStatus"
          :items="statusOptions"
          label="Filter Status"
          density="compact"
          style="max-width: 250px;"
          hide-details
        />
        
        <VSpacer />
      </VCardText>

      <VDivider />

      <!-- Data Table -->
      <VDataTableServer
        v-model:options="options"
        :headers="tableHeaders"
        :items="receivables"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        @update:options="fetchReceivables"
      >
        <template #item.sale.invoice_number="{ item }">
          <span class="font-weight-bold">{{ item.sale?.invoice_number || '-' }}</span>
        </template>
        
        <template #item.sale.branch.name="{ item }">
          {{ item.sale?.branch?.name || '-' }}
        </template>
        
        <template #item.sale.user.name="{ item }">
          {{ item.sale?.user?.name || '-' }}
        </template>

        <template #item.customer.name="{ item }">
          {{ item.customer?.name || '-' }}
        </template>

        <template #item.due_date="{ item }">
          <div :class="{'text-error font-weight-bold': isOverdue(item.due_date, item.status)}">
            {{ formatDate(item.due_date) }}
            <VIcon
              v-if="isOverdue(item.due_date, item.status)"
              icon="ri-error-warning-line"
              size="small"
              class="ml-1"
            />
          </div>
        </template>

        <template #item.remaining="{ item }">
          <span class="font-weight-bold text-error">
            {{ formatCurrency(item.amount_due - item.amount_paid) }}
          </span>
        </template>
        
        <template #item.status="{ item }">
          <VChip
            :color="getStatusColor(item.status)"
            size="small"
            class="font-weight-bold"
          >
            {{ getStatusText(item.status) }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex align-center gap-2">
            <VBtn 
              size="small" 
              :color="item.status === 'paid' ? 'secondary' : 'primary'" 
              variant="tonal" 
              @click="viewDetails(item.id)"
            >
              {{ item.status === 'paid' ? 'Detail' : 'Bayar/Detail' }}
            </VBtn>
            <VBtn
              v-if="$can('delete', 'Kasir (POS)')"
              icon="ri-delete-bin-line"
              color="error"
              size="small"
              variant="text"
              @click="confirmDelete(item)"
            />
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <ReceivableDetailDrawer
      v-model:is-drawer-open="isDetailDrawerVisible"
      :receivable-id="selectedReceivableId"
      @payment-saved="onPaymentSaved"
    />

    <!-- Void Confirmation Dialog -->
    <VDialog
      v-model="isConfirmDeleteVisible"
      max-width="500"
    >
      <VCard>
        <VCardTitle class="text-error bg-error-lighten-4 pa-4">
          Konfirmasi Pembatalan Piutang
        </VCardTitle>
        <VCardText class="pa-6">
          <p class="text-body-1">
            Apakah Anda yakin ingin menghapus piutang dan membatalkan transaksi dari <strong>{{ receivableToDelete?.customer?.name }}</strong> (Bon: {{ receivableToDelete?.sale?.invoice_number }})?
          </p>
          <VAlert
            type="warning"
            variant="tonal"
            class="mt-4 text-caption mb-4"
          >
            Tindakan ini akan <strong>menghapus bersih data piutang ini (termasuk cicilan/DP)</strong>, membatalkan transaksi penjualan secara utuh, dan mengembalikan stok barang ke gudang secara otomatis. Data yang dihapus tidak bisa dikembalikan.
          </VAlert>
          
          <VTextField
            v-model="voidPin"
            type="password"
            label="Masukkan PIN Kasir Anda"
            placeholder="****"
            variant="outlined"
            density="compact"
            autofocus
            @keyup.enter="executeDeleteReceivable"
          />
        </VCardText>
        <VCardActions class="pa-4 pt-0 justify-end">
          <VBtn
            color="secondary"
            variant="outlined"
            @click="isConfirmDeleteVisible = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="error"
            prepend-icon="ri-delete-bin-line"
            :disabled="!voidPin"
            @click="executeDeleteReceivable"
          >
            Ya, Hapus & Batalkan Transaksi
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Data Piutang
</route>
