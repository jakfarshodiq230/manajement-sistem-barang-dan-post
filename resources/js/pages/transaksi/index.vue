<script setup>
import { ref, onMounted, computed } from 'vue'
import { paginationMeta } from '@/utils/paginationMeta'
import { useSnackbarStore } from '@/stores/snackbar'
import SaleDetailDrawer from './SaleDetailDrawer.vue'

const sales = ref([])
const search = ref('')
const selectedStatus = ref(null)
const selectedBank = ref(null)
const bankAccounts = ref([])
const dateRange = ref('')
const isLoading = ref(false)
const summary = ref({ cash: 0, transfer: 0, qris: 0, tempo: 0, by_bank: [] })
const isDrawerVisible = ref(false)
const selectedSale = ref(null)

// Pagination
const page = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)
let searchTimeout = null

const isConfirmVoidVisible = ref(false)
const saleToVoid = ref(null)
const voidPin = ref('')

const snackbar = useSnackbarStore()

const fetchBankAccounts = async () => {
  try {
    const res = await $api('/apps/bank-accounts')
    bankAccounts.value = res.data || []
  } catch (e) {
    console.error('Failed to fetch bank accounts for filter:', e)
  }
}

const displayDate = computed(() => {
  if (dateRange.value) {
    const dates = dateRange.value.split(' to ')
    const start = new Date(dates[0]).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' })
    if (dates[1]) {
      const end = new Date(dates[1]).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' })
      return `${start} - ${end}`
    }
    return start
  }
  return new Date().toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }) + ' (Hari Ini)'
})

// Format currency
const formatRupiah = value => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value)
}

const fetchSales = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
    }
    
    if (search.value) {
      params.search = search.value
    }
    if (selectedStatus.value) {
      params.payment_status = selectedStatus.value
    }
    if (selectedBank.value) {
      params.bank_account_id = selectedBank.value
    }
    if (dateRange.value) {
      const dates = dateRange.value.split(' to ')
      params.start_date = dates[0]
      params.end_date = dates[1] || dates[0]
    }
    
    const data = await $api('/apps/sales', { query: params })

    sales.value = data.data || data
    if (data.total !== undefined) {
      totalItems.value = data.total
    }
    if (data.summary) {
      summary.value = data.summary
    }
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mengambil data transaksi', 'error')
  } finally {
    isLoading.value = false
  }
}

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchSales()
  }, 500)
}

onMounted(() => {
  fetchBankAccounts()
  fetchSales()
})

const tableHeaders = [
  { title: 'NO. BON', key: 'invoice_number' },
  { title: 'TANGGAL', key: 'date' },
  { title: 'CABANG', key: 'branch.name' },
  { title: 'KASIR', key: 'user.name' },
  { title: 'TOTAL (RP)', key: 'total_amount' },
  { title: 'METODE & BANK PENERIMA', key: 'payment_method' },
  { title: 'STATUS', key: 'status' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const filteredSales = computed(() => {
  let result = sales.value

  // Filter by date range (client side for now since backend doesn't handle date filter yet)
  if (dateRange.value) {
    const dates = dateRange.value.split(' to ')
    const start = dates[0]
    const end = dates[1] || dates[0]
    
    result = result.filter(item => {
      const itemDate = item.date ? item.date.split(' ')[0] : ''
      
      return itemDate >= start && itemDate <= end
    })
  }

  return result
})

const viewDetail = sale => {
  selectedSale.value = sale
  isDrawerVisible.value = true
}

const confirmVoidSale = sale => {
  saleToVoid.value = sale
  voidPin.value = ''
  isConfirmVoidVisible.value = true
}

const executeVoidSale = async () => {
  if (!saleToVoid.value) return
  if (!voidPin.value) {
    snackbar.show('Silakan masukkan PIN Anda', 'error')
    
    return
  }
  
  isLoading.value = true
  try {
    const res = await $api(`/apps/sales/${saleToVoid.value.id}`, {
      method: 'DELETE',
      body: { pin: voidPin.value },
    })

    snackbar.show('Transaksi berhasil dibatalkan dan stok dikembalikan.', 'success')
    isConfirmVoidVisible.value = false
    isDrawerVisible.value = false
    fetchSales() // Refresh the list
  } catch (error) {
    console.error(error)

    const errorMsg = error.response?._data?.message || 'Gagal membatalkan transaksi.'

    snackbar.show(errorMsg, 'error')
  } finally {
    isLoading.value = false
    saleToVoid.value = null
  }
}

const exportToExcel = () => {
  if (!dateRange.value) {
    snackbar.show('Silakan pilih rentang tanggal periode terlebih dahulu!', 'warning')
    
    return
  }
  
  if (!filteredSales.value || filteredSales.value.length === 0) {
    snackbar.show('Tidak ada data untuk diekspor pada periode ini', 'warning')
    
    return
  }
  
  // Create CSV Header
  const headers = ['NO. BON', 'TANGGAL', 'CABANG', 'KASIR', 'TOTAL (RP)', 'METODE BAYAR', 'STATUS']
  const csvRows = [headers.join(',')]
  
  // Format rows
  sales.value.forEach(sale => {
    const row = [
      `"${sale.invoice_number}"`,
      `"${sale.date || ''}"`,
      `"${sale.branch?.name || ''}"`,
      `"${sale.user?.name || ''}"`,
      `"${sale.total_amount}"`,
      `"${sale.payment_method === 'transfer' ? 'Transfer Bank' : (sale.payment_method === 'qris' ? 'QRIS' : (sale.payment_method === 'tempo' ? 'Tempo (Utang)' : 'Tunai'))}"`,
      `"${sale.status?.toUpperCase() || ''}"`,
    ]

    csvRows.push(row.join(','))
  })
  
  // Create Blob
  const csvData = csvRows.join('\n')
  const blob = new Blob([csvData], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  
  // Create download link
  const link = document.createElement('a')

  link.href = url
  link.setAttribute('download', `Riwayat_Transaksi_${new Date().toISOString().split('T')[0]}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}
</script>

<template>
  <div>
    <!-- Summary Cards -->
    <div class="mb-4">
      <h2 class="text-h5 font-weight-bold mb-1">Ringkasan Pendapatan</h2>
      <p class="text-body-1 text-medium-emphasis mb-4">
        Berdasarkan periode: <strong class="text-primary">{{ displayDate }}</strong>
      </p>
      <VRow>
      <VCol cols="12" sm="6" md="3">
        <VCard>
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <div class="text-subtitle-2 text-medium-emphasis">Tunai (Cash)</div>
              <div class="text-h6 font-weight-semibold">{{ formatRupiah(summary.cash || 0) }}</div>
            </div>
            <VAvatar rounded color="success" variant="tonal">
              <VIcon icon="ri-money-dollar-circle-line" size="24" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" sm="6" md="3">
        <VCard>
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <div class="text-subtitle-2 text-medium-emphasis">Transfer Bank</div>
              <div class="text-h6 font-weight-semibold">{{ formatRupiah(summary.transfer || 0) }}</div>
            </div>
            <VAvatar rounded color="info" variant="tonal">
              <VIcon icon="ri-bank-card-line" size="24" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" sm="6" md="3">
        <VCard>
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <div class="text-subtitle-2 text-medium-emphasis">QRIS</div>
              <div class="text-h6 font-weight-semibold">{{ formatRupiah(summary.qris || 0) }}</div>
            </div>
            <VAvatar rounded color="primary" variant="tonal">
              <VIcon icon="ri-qr-code-line" size="24" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" sm="6" md="3">
        <VCard>
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <div class="text-subtitle-2 text-medium-emphasis">Kasbon (Tempo)</div>
              <div class="text-h6 font-weight-semibold text-warning">{{ formatRupiah(summary.tempo || 0) }}</div>
            </div>
            <VAvatar rounded color="warning" variant="tonal">
              <VIcon icon="ri-time-line" size="24" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
    </div>

    <p class="text-2xl mb-6">
      Riwayat Transaksi Penjualan
    </p>

    <!-- Card -->
    <VCard>
      <!-- Card Header -->
      <VCardText class="d-flex flex-wrap gap-3 align-center">
        <AppDateTimePicker
          v-model="dateRange"
          placeholder="Filter Rentang Tanggal"
          prepend-inner-icon="ri-calendar-line"
          :config="{ mode: 'range' }"
          density="compact"
          style="width: 230px;"
          hide-details
          clearable
          @update:model-value="handleSearch"
        />
        <div style="min-width: 200px;">
          <VSelect
            v-model="selectedBank"
            :items="[{ title: 'Semua Rekening / Kas', value: null }, ...bankAccounts.map(b => ({ title: `${b.bank_name} (${b.account_number || 'Cash'})`, value: b.id }))]"
            item-title="title"
            item-value="value"
            placeholder="Filter Bank Penerima"
            density="compact"
            variant="outlined"
            clearable
            hide-details
            @update:model-value="fetchSales"
          />
        </div>
        <VTextField
          v-model="search"
          placeholder="Cari No Bon, Pelanggan, Kasir..."
          prepend-inner-icon="ri-search-line"
          density="compact"
          style="width: 240px;"
          hide-details
          clearable
          @update:model-value="handleSearch"
        />
        <VSpacer />
        
        <VBtn
          color="success"
          prepend-icon="ri-file-excel-2-line"
          :disabled="isLoading || sales.length === 0"
          @click="exportToExcel"
        >
          Export Excel
        </VBtn>
      </VCardText>

      <VDivider />

      <!-- Data Table -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="tableHeaders"
        :items="sales"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        @update:options="fetchSales"
      >
        <template #item.invoice_number="{ item }">
          <span class="font-weight-bold text-primary font-mono cursor-pointer" @click="viewDetail(item)">
            {{ item.invoice_number }}
          </span>
        </template>
        
        <template #item.total_amount="{ item }">
          <span class="font-weight-bold font-mono">{{ formatRupiah(item.total_amount) }}</span>
        </template>

        <template #item.payment_method="{ item }">
          <div class="d-flex flex-column gap-1">
            <div class="d-flex align-center gap-1">
              <VChip
                size="x-small"
                :color="item.payment_method === 'tempo' ? 'error' : (item.payment_method === 'cash' ? 'success' : (item.payment_method === 'qris' ? 'secondary' : 'primary'))"
                variant="tonal"
                class="font-weight-bold"
              >
                <VIcon
                  :icon="item.payment_method === 'tempo' ? 'ri-time-line' : (item.payment_method === 'cash' ? 'ri-money-dollar-circle-line' : (item.payment_method === 'qris' ? 'ri-qr-code-line' : 'ri-bank-card-line'))"
                  size="12"
                  class="me-1"
                />
                {{ item.payment_method === 'transfer' ? 'Transfer Bank' : (item.payment_method === 'qris' ? 'QRIS' : (item.payment_method === 'tempo' ? 'Tempo (Utang)' : 'Tunai Kasir')) }}
              </VChip>
            </div>

            <!-- Bank / Akun Penerima Detail -->
            <div v-if="item.payment_method === 'transfer' || item.payment_method === 'qris'" class="d-flex align-center gap-1 flex-wrap">
              <VChip
                size="x-small"
                color="info"
                variant="flat"
                class="font-weight-bold"
              >
                <VIcon icon="ri-bank-line" size="11" class="me-1" />
                {{ item.bank_name || item.bank_account?.bank_name || 'BCA (Bank Central Asia)' }}
              </VChip>
              <span v-if="item.bank_account_number || item.bank_account?.account_number" class="text-caption font-mono text-medium-emphasis" style="font-size: 11px;">
                {{ item.bank_account_number || item.bank_account?.account_number }}
              </span>
            </div>
          </div>
        </template>

        <template #item.status="{ item }">
          <VChip
            :color="item.status === 'completed' ? 'success' : (item.status === 'returned' ? 'warning' : 'error')"
            size="small"
          >
            {{ item.status.toUpperCase() }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <IconBtn
            size="small"
            @click="viewDetail(item)"
          >
            <VIcon icon="ri-eye-line" />
          </IconBtn>
        </template>

        <!-- Pagination -->
        <template #bottom>
          <VDivider />

          <div class="d-flex justify-end flex-wrap gap-x-6 px-4 py-2">
            <div class="d-flex align-center gap-x-2 text-medium-emphasis text-body-2">
              Baris per halaman:
              <VSelect
                v-model="itemsPerPage"
                class="per-page-select"
                variant="plain"
                density="compact"
                :items="[10, 20, 25, 50, 100]"
                hide-details
              />
            </div>

            <p class="d-flex align-center text-body-2 text-high-emphasis me-2 mb-0">
              {{ paginationMeta({ page, itemsPerPage }, totalItems) }}
            </p>

            <div class="d-flex gap-x-2 align-center me-2">
              <VBtn
                class="flip-in-rtl"
                icon="ri-arrow-left-s-line"
                variant="text"
                density="comfortable"
                color="high-emphasis"
                :disabled="page <= 1"
                @click="page <= 1 ? page = 1 : page--"
              />

              <VBtn
                class="flip-in-rtl"
                icon="ri-arrow-right-s-line"
                density="comfortable"
                variant="text"
                color="high-emphasis"
                :disabled="page >= Math.ceil(totalItems / itemsPerPage)"
                @click="page >= Math.ceil(totalItems / itemsPerPage) ? page = Math.ceil(totalItems / itemsPerPage) : page++"
              />
            </div>
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <SaleDetailDrawer
      v-if="selectedSale"
      v-model:is-drawer-open="isDrawerVisible"
      :sale="selectedSale"
      @void-sale="confirmVoidSale"
    />

    <!-- Void Confirmation Dialog -->
    <VDialog
      v-model="isConfirmVoidVisible"
      max-width="500"
    >
      <VCard>
        <VCardTitle class="text-error bg-error-lighten-4 pa-4">
          Konfirmasi Pembatalan
        </VCardTitle>
        <VCardText class="pa-6">
          <p class="text-body-1">
            Apakah Anda yakin ingin membatalkan transaksi <strong>{{ saleToVoid?.invoice_number }}</strong>?
          </p>
          <VAlert
            type="warning"
            variant="tonal"
            class="mt-4 text-caption mb-4"
          >
            Tindakan ini akan membatalkan bon secara utuh, mengubah statusnya menjadi "BATAL", dan mengembalikan stok barang ke sistem secara otomatis. Data yang dibatalkan tidak bisa dikembalikan.
          </VAlert>
          
          <VTextField
            v-model="voidPin"
            type="password"
            label="Masukkan PIN Anda"
            placeholder="****"
            variant="outlined"
            density="compact"
            autofocus
            @keyup.enter="executeVoidSale"
          />
        </VCardText>
        <VCardActions class="pa-4 pt-0 justify-end">
          <VBtn
            color="secondary"
            variant="outlined"
            @click="isConfirmVoidVisible = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="error"
            prepend-icon="ri-close-circle-line"
            :disabled="!voidPin"
            @click="executeVoidSale"
          >
            Ya, Batalkan Transaksi
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Transaksi
</route>

<style lang="scss">
.per-page-select {
  inline-size: 5.5rem;
}
</style>
