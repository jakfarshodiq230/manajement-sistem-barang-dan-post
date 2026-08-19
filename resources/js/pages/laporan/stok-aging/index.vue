<script setup>
import { ref, onMounted } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import * as XLSX from 'xlsx'

const agingData = ref([])
const branches = ref([])
const isLoading = ref(false)
const search = ref('')
const activeTab = ref('all')
const selectedBranch = ref(null)

const summary = ref({
  total_batches: 0,
  total_qty: 0,
  total_asset_value: 0,
  expiring_soon: 0,
})

const snackbar = useSnackbarStore()

// Pagination
const page = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)
let searchTimeout = null

const tableHeaders = [
  { title: 'PRODUK / SKU', key: 'nama_barang' },
  { title: 'KATEGORI', key: 'kategori' },
  { title: 'CABANG', key: 'cabang' },
  { title: 'TGL MASUK', key: 'tanggal_masuk' },
  { title: 'STATUS KEDALUWARSA (FEFO)', key: 'sisa_hari_expired', align: 'center' },
  { title: 'UMUR STOK (HARI)', key: 'umur_stok_hari', align: 'center' },
  { title: 'SISA QTY', key: 'qty_sisa', align: 'center' },
  { title: 'NILAI MODAL ASET', key: 'nilai_aset', align: 'end' },
]

const formatRupiah = value => {
  if (value === null || value === undefined || isNaN(value)) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value)
}

const formatDate = dateString => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatDurasi = nilai => {
  if (nilai === null || nilai === undefined) return '-'
  const hari = Math.round(nilai)
  if (hari < 0) return `${Math.abs(hari)} hari lalu (Expired)`
  if (hari === 0) return 'Hari ini'
  return `${hari} hari lagi`
}

const formatUmurDisplay = item => {
  if (item.umur_format) return item.umur_format
  if (item.umur_stok_hari === null || item.umur_stok_hari === undefined) return '-'
  const days = Math.floor(Number(item.umur_stok_hari))
  const hours = item.umur_sisa_jam ? Math.floor(Number(item.umur_sisa_jam)) : 0
  if (days > 0 && hours > 0) return `${days} hari ${hours} jam`
  if (days > 0) return `${days} hari`
  if (hours > 0) return `${hours} jam`
  return 'Baru saja'
}

const fetchData = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      filter: activeTab.value,
    }
    
    if (search.value) params.search = search.value
    if (selectedBranch.value) params.branch_id = selectedBranch.value
    
    const [resData, resBranches] = await Promise.all([
      $api('/apps/reports/stock-aging', { query: params }),
      $api('/apps/branches'),
    ])

    agingData.value = resData.data || []
    totalItems.value = resData.total || agingData.value.length
    branches.value = resBranches.data || resBranches || []
    if (resData.summary) {
      summary.value = resData.summary
    }
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal memuat data laporan usia stok', 'error')
  } finally {
    isLoading.value = false
  }
}

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchData()
  }, 450)
}

const handleTabChange = () => {
  page.value = 1
  fetchData()
}

onMounted(() => {
  fetchData()
})

const exportExcel = async () => {
  if (agingData.value.length === 0) return
  
  isLoading.value = true
  try {
    const params = {
      itemsPerPage: -1,
      filter: activeTab.value,
    }

    if (search.value) params.search = search.value
    if (selectedBranch.value) params.branch_id = selectedBranch.value
    
    const res = await $api('/apps/reports/stock-aging', { query: params })
    const allData = res.data || []

    const exportData = allData.map((item, index) => {
      return {
        'No': index + 1,
        'Kode SKU': item.kode_barang,
        'Nama Produk': item.nama_barang,
        'Merk': item.brand || '-',
        'Kategori': item.kategori,
        'Cabang': item.cabang,
        'Tanggal Masuk': item.tanggal_masuk ? item.tanggal_masuk.substring(0, 10) : '-',
        'Status Kedaluwarsa (FEFO)': item.expired_format || (item.sisa_hari_expired !== null ? `${item.sisa_hari_expired} hari` : '-'),
        'Umur Stok': item.umur_format || `${item.umur_stok_hari} hari`,
        'Sisa Qty': item.qty_sisa,
        'Harga Modal Beli': item.harga_beli,
        'Nilai Aset Terikat (Rp)': item.nilai_aset,
      }
    })

    const worksheet = XLSX.utils.json_to_sheet(exportData)
    const workbook = XLSX.utils.book_new()

    XLSX.utils.book_append_sheet(workbook, worksheet, "Usia Stok")
    XLSX.writeFile(workbook, `Laporan_Usia_Stok_${activeTab.value}_${new Date().toISOString().split('T')[0]}.xlsx`)
  } catch (error) {
    console.error("Export failed", error)
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="pa-4">
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="text-h4 font-weight-bold mb-1">
          Analisis Usia Stok (Stock Aging)
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Audit umur persediaan per batch menggunakan metode FEFO (Kedaluwarsa), FIFO (Stok Lama), dan LIFO (Stok Baru).
        </p>
      </div>
      
      <VBtn
        color="success"
        prepend-icon="ri-file-excel-2-line"
        :disabled="isLoading || agingData.length === 0"
        @click="exportExcel"
      >
        Export Excel
      </VBtn>
    </div>

    <!-- Summary KPI Cards -->
    <VRow class="mb-4">
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-primary">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-medium-emphasis font-weight-medium">TOTAL BATCH AKTIF</div>
              <div class="text-h5 font-weight-bold text-primary mt-1">{{ summary.total_batches }} <span class="text-caption text-medium-emphasis">Batch</span></div>
            </div>
            <VAvatar color="primary" variant="tonal" size="42">
              <VIcon icon="ri-archive-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Batch memiliki stok tersisa</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-info">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-medium-emphasis font-weight-medium">TOTAL UNIT TERSEDIA</div>
              <div class="text-h5 font-weight-bold text-info mt-1">{{ summary.total_qty.toLocaleString('id-ID') }} <span class="text-caption text-medium-emphasis">Pcs</span></div>
            </div>
            <VAvatar color="info" variant="tonal" size="42">
              <VIcon icon="ri-box-3-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Kuantitas fisik on-hand</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-success">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-medium-emphasis font-weight-medium">NILAI ASET PERSEDIAAN</div>
              <div class="text-h5 font-weight-bold text-success mt-1">{{ formatRupiah(summary.total_asset_value) }}</div>
            </div>
            <VAvatar color="success" variant="tonal" size="42">
              <VIcon icon="ri-money-dollar-circle-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Modal modal barang aktif</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-error">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-error font-weight-bold">PERLU PERHATIAN (FEFO)</div>
              <div class="text-h5 font-weight-bold text-error mt-1">{{ summary.expiring_soon }} <span class="text-caption text-medium-emphasis">Batch</span></div>
            </div>
            <VAvatar color="error" variant="tonal" size="42">
              <VIcon icon="ri-alarm-warning-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-error font-weight-medium mt-2">Expired &le; 30 hari atau lewat</div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Card with Tabs -->
    <VCard elevation="2">
      <!-- Tabs Navigation -->
      <VTabs
        v-model="activeTab"
        class="px-4 border-b"
        @update:model-value="handleTabChange"
      >
        <VTab value="all">
          <VIcon icon="ri-stack-line" class="me-2" />
          Semua Batch
        </VTab>
        <VTab value="fefo">
          <VIcon icon="ri-alarm-warning-line" class="me-2 text-error" />
          FEFO (Segera Kedaluwarsa)
        </VTab>
        <VTab value="fifo">
          <VIcon icon="ri-history-line" class="me-2 text-warning" />
          FIFO (Stok Paling Lama)
        </VTab>
        <VTab value="lifo">
          <VIcon icon="ri-inbox-unarchive-line" class="me-2 text-info" />
          LIFO (Stok Terbaru Masuk)
        </VTab>
      </VTabs>

      <!-- Filter Controls -->
      <VCardText class="d-flex flex-wrap align-center py-4 gap-4">
        <VTextField
          v-model="search"
          placeholder="Cari Produk / SKU / Merk..."
          prepend-inner-icon="ri-search-line"
          density="compact"
          style="max-width: 300px;"
          clearable
          hide-details
          @update:model-value="handleSearch"
        />

        <VAutocomplete
          v-model="selectedBranch"
          :items="branches"
          item-title="name"
          item-value="id"
          placeholder="Semua Cabang"
          label="Cabang"
          density="compact"
          style="max-width: 220px;"
          clearable
          hide-details
          @update:model-value="handleTabChange"
        />

        <VSpacer />

        <VBtn
          prepend-icon="ri-refresh-line"
          variant="tonal"
          color="secondary"
          size="small"
          :loading="isLoading"
          @click="fetchData"
        >
          Muat Ulang
        </VBtn>
      </VCardText>

      <VDivider />

      <!-- Data Table -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="tableHeaders"
        :items="agingData"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        hover
        @update:options="fetchData"
      >
        <template #item.nama_barang="{ item }">
          <div>
            <div class="font-weight-medium text-subtitle-2">{{ item.nama_barang }}</div>
            <div class="text-caption text-disabled">
              <code>{{ item.kode_barang }}</code>
              <span v-if="item.brand && item.brand !== '-'"> &bull; {{ item.brand }}</span>
            </div>
          </div>
        </template>

        <template #item.tanggal_masuk="{ item }">
          <span>{{ formatDate(item.tanggal_masuk) }}</span>
        </template>

        <template #item.sisa_hari_expired="{ item }">
          <div v-if="item.sisa_hari_expired !== null && item.tanggal_expired && item.tanggal_expired !== '0000-00-00'">
            <VChip
              v-if="item.sisa_hari_expired < 0"
              color="error"
              size="small"
              variant="elevated"
              class="font-weight-bold"
            >
              <VIcon icon="ri-close-circle-line" size="14" class="me-1" />
              {{ item.expired_format || ('Expired ' + Math.abs(item.sisa_hari_expired) + ' hari lalu') }}
            </VChip>
            <VChip
              v-else-if="item.sisa_hari_expired <= 30"
              color="warning"
              size="small"
              variant="elevated"
              class="font-weight-bold"
            >
              <VIcon icon="ri-alarm-warning-line" size="14" class="me-1" />
              {{ item.expired_format || ('Sisa ' + item.sisa_hari_expired + ' hari') }}
            </VChip>
            <VChip
              v-else
              color="success"
              size="small"
              variant="tonal"
            >
              <VIcon icon="ri-check-line" size="14" class="me-1" />
              {{ item.expired_format || ('Sisa ' + item.sisa_hari_expired + ' hari') }}
            </VChip>
            <div class="text-caption text-disabled mt-1">{{ formatDate(item.tanggal_expired) }}</div>
          </div>
          <div v-else>
            <VChip
              color="secondary"
              size="small"
              variant="tonal"
              class="text-caption font-weight-medium"
            >
              <VIcon icon="ri-checkbox-blank-circle-line" size="10" class="me-1" />
              Non-Expired
            </VChip>
          </div>
        </template>

        <template #item.umur_stok_hari="{ item }">
          <VChip
            :color="item.umur_stok_hari > 90 ? 'warning' : 'secondary'"
            size="small"
            variant="tonal"
            class="font-weight-medium"
          >
            <VIcon icon="ri-time-line" size="14" class="me-1" />
            {{ formatUmurDisplay(item) }}
          </VChip>
        </template>

        <template #item.qty_sisa="{ item }">
          <VChip
            :color="item.qty_sisa > 0 ? 'primary' : 'secondary'"
            size="small"
            variant="tonal"
            class="font-weight-bold"
          >
            {{ item.qty_sisa }} unit
          </VChip>
        </template>

        <template #item.nilai_aset="{ item }">
          <span class="font-weight-bold text-success">
            {{ formatRupiah(item.nilai_aset) }}
          </span>
        </template>

        <template #no-data>
          <div class="pa-4 text-center text-medium-emphasis">
            Tidak ada data usia stok batch pada kriteria ini.
          </div>
        </template>
      </VDataTableServer>
    </VCard>
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Analisis Usia Stok
</route>
