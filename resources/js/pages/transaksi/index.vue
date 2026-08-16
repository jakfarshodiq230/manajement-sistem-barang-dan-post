<script setup>
import { ref, onMounted, computed } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import SaleDetailDrawer from './SaleDetailDrawer.vue'

const sales = ref([])
const search = ref('')
const dateRange = ref('')
const isLoading = ref(false)
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
    
    const data = await $api('/apps/sales', { query: params })

    sales.value = data.data || data
    if (data.total !== undefined) {
      totalItems.value = data.total
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
  fetchSales()
})

const tableHeaders = [
  { title: 'NO. BON', key: 'invoice_number' },
  { title: 'TANGGAL', key: 'date' },
  { title: 'CABANG', key: 'branch.name' },
  { title: 'KASIR', key: 'user.name' },
  { title: 'TOTAL (RP)', key: 'total_amount' },
  { title: 'METODE BAYAR', key: 'payment_method' },
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
  filteredSales.value.forEach(sale => {
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
    <p class="text-2xl mb-6">
      Riwayat Transaksi Penjualan
    </p>

    <!-- Card -->
    <VCard>
      <!-- Card Header -->
      <VCardText class="d-flex flex-wrap gap-4 align-center">
        <AppDateTimePicker
          v-model="dateRange"
          placeholder="Filter Rentang Tanggal"
          prepend-inner-icon="ri-calendar-line"
          :config="{ mode: 'range' }"
          density="compact"
          style="width: 250px;"
          hide-details
          clearable
        />
        <VTextField
          v-model="search"
          placeholder="Cari No Bon atau Kasir..."
          prepend-inner-icon="ri-search-line"
          density="compact"
          style="width: 250px;"
          hide-details
          clearable
          @update:model-value="handleSearch"
        />
        <VSpacer />
        
        <VBtn
          color="success"
          prepend-icon="ri-file-excel-2-line"
          :disabled="isLoading || filteredSales.length === 0"
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
        :items="filteredSales"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        @update:options="fetchSales"
      >
        <template #item.invoice_number="{ item }">
          <span class="font-weight-bold text-primary">{{ item.invoice_number }}</span>
        </template>
        
        <template #item.total_amount="{ item }">
          <span class="font-weight-medium">{{ formatRupiah(item.total_amount) }}</span>
        </template>

        <template #item.payment_method="{ item }">
          <span
            class="text-capitalize font-weight-bold"
            :class="item.payment_method === 'tempo' ? 'text-error' : ''"
          >
            {{ item.payment_method === 'transfer' ? 'Transfer Bank' : (item.payment_method === 'qris' ? 'QRIS' : (item.payment_method === 'tempo' ? 'Tempo (Utang)' : 'Tunai')) }}
          </span>
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
