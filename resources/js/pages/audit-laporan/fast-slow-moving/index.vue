<script setup>
import { ref, onMounted, computed } from 'vue'
import * as XLSX from 'xlsx'

const data = ref([])
const summary = ref({
  total_items: 0,
  fast_moving: 0,
  medium_moving: 0,
  slow_moving: 0,
  dead_stock: 0,
  total_sales_revenue: 0,
  total_idle_asset_value: 0,
})
const isLoading = ref(false)
const search = ref('')
const selectedBranch = ref(null)
const selectedCategory = ref(null)
const selectedSpeed = ref(null)
const branches = ref([])
const categories = ref([])

// Pagination
const page = ref(1)
const itemsPerPage = ref(15)
const totalItems = ref(0)
let searchTimeout = null

const currentDate = new Date()
const selectedMonth = ref(currentDate.getMonth() + 1)
const selectedYear = ref(currentDate.getFullYear())

const months = [
  { title: 'Januari', value: 1 },
  { title: 'Februari', value: 2 },
  { title: 'Maret', value: 3 },
  { title: 'April', value: 4 },
  { title: 'Mei', value: 5 },
  { title: 'Juni', value: 6 },
  { title: 'Juli', value: 7 },
  { title: 'Agustus', value: 8 },
  { title: 'September', value: 9 },
  { title: 'Oktober', value: 10 },
  { title: 'November', value: 11 },
  { title: 'Desember', value: 12 },
]

const years = Array.from({ length: 5 }, (_, i) => currentDate.getFullYear() - 2 + i)

const speedOptions = [
  { title: 'Semua Klasifikasi', value: null },
  { title: 'Fast Moving (Laris)', value: 'fast' },
  { title: 'Medium Moving (Sedang)', value: 'medium' },
  { title: 'Slow Moving (Lambat)', value: 'slow' },
  { title: 'Dead Stock (Macet / 0 Terjual)', value: 'dead' },
]

const formatRupiah = val => {
  if (!val || isNaN(val)) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(val)
}

const fetchMasterData = async () => {
  try {
    const [branchRes, catRes] = await Promise.all([
      $api('/apps/branches'),
      $api('/apps/categories'),
    ])
    branches.value = branchRes.data || branchRes || []
    categories.value = catRes.data || catRes || []
  } catch (error) {
    console.error('Error loading master data:', error)
  }
}

const fetchReport = async () => {
  isLoading.value = true
  try {
    const params = {}
    if (selectedBranch.value) params.branch_id = selectedBranch.value
    if (selectedCategory.value) params.category_id = selectedCategory.value
    if (selectedSpeed.value) params.speed_status = selectedSpeed.value
    
    const startStr = `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}-01`
    const lastDay = new Date(selectedYear.value, selectedMonth.value, 0).getDate()
    const endStr = `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}-${lastDay}`
    
    params.start_date = startStr
    params.end_date = endStr
    params.page = page.value
    params.itemsPerPage = itemsPerPage.value
    if (search.value) {
      params.search = search.value
    }

    const res = await $api('/apps/reports/fast-slow-moving', { query: params })

    data.value = res.data || []
    if (res.total !== undefined) {
      totalItems.value = res.total
    }
    if (res.summary) {
      summary.value = res.summary
    }
  } catch (error) {
    console.error('Failed to fetch FSN report:', error)
  } finally {
    isLoading.value = false
  }
}

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchReport()
  }, 450)
}

const onFilterChange = () => {
  page.value = 1
  fetchReport()
}

const exportExcel = async () => {
  if (data.value.length === 0) return
  
  isLoading.value = true
  try {
    const params = {}
    if (selectedBranch.value) params.branch_id = selectedBranch.value
    if (selectedCategory.value) params.category_id = selectedCategory.value
    if (selectedSpeed.value) params.speed_status = selectedSpeed.value
    
    const startStr = `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}-01`
    const lastDay = new Date(selectedYear.value, selectedMonth.value, 0).getDate()
    const endStr = `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}-${lastDay}`
    
    params.start_date = startStr
    params.end_date = endStr
    params.itemsPerPage = -1 // Get all filtered data
    if (search.value) params.search = search.value

    const res = await $api('/apps/reports/fast-slow-moving', { query: params })
    const allData = res.data || res

    const exportData = allData.map((item, index) => {
      return {
        'No': index + 1,
        'Kode Barang (SKU)': item.kode_barang,
        'Nama Barang': item.nama_barang,
        'Merk': item.brand || '-',
        'Kategori': item.kategori,
        'Cabang': item.cabang,
        'Total Terjual (Unit)': item.terjual,
        'Total Omzet (Rp)': item.total_omset,
        'Sisa Stok': item.sisa_stok,
        'Harga Modal (HPP)': item.cost_price,
        'Nilai Aset Mengendap (Rp)': item.nilai_aset,
        'Klasifikasi FSN': item.kategori_kecepatan,
      }
    })

    const worksheet = XLSX.utils.json_to_sheet(exportData)
    const workbook = XLSX.utils.book_new()

    XLSX.utils.book_append_sheet(workbook, worksheet, "Fast Slow Moving")
    XLSX.writeFile(workbook, `Laporan_Fast_Slow_Moving_${selectedMonth.value}_${selectedYear.value}.xlsx`)
  } catch (error) {
    console.error("Export failed", error)
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  await fetchMasterData()
  fetchReport()
})

const headers = [
  { title: 'PRODUK / SKU', key: 'nama_barang' },
  { title: 'KATEGORI', key: 'kategori' },
  { title: 'CABANG', key: 'cabang' },
  { title: 'TERJUAL (QTY)', key: 'terjual', align: 'center' },
  { title: 'TOTAL OMZET', key: 'total_omset', align: 'end' },
  { title: 'SISA STOK', key: 'sisa_stok', align: 'center' },
  { title: 'NILAI MODAL STOK', key: 'nilai_aset', align: 'end' },
  { title: 'KLASIFIKASI FSN', key: 'kategori_kecepatan', align: 'center' },
]

const getSpeedBadge = speed => {
  switch (speed) {
    case 'Fast Moving':
      return { text: 'Fast Moving', color: 'success', icon: 'ri-rocket-line' }
    case 'Medium Moving':
      return { text: 'Medium Moving', color: 'info', icon: 'ri-box-3-line' }
    case 'Slow Moving':
      return { text: 'Slow Moving', color: 'warning', icon: 'ri-time-line' }
    case 'Dead Stock':
      return { text: 'Dead Stock (0 Terjual)', color: 'error', icon: 'ri-alert-line' }
    default:
      return { text: speed || 'Non-Active', color: 'secondary', icon: 'ri-information-line' }
  }
}
</script>

<template>
  <div class="pa-4">
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="text-h4 font-weight-bold mb-1">
          Laporan Analisis Fast & Slow Moving (FSN)
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Analisis perputaran barang untuk mengidentifikasi produk terlaris, lambat laku, dan stok macet (dead stock).
        </p>
      </div>
      <VBtn
        color="success"
        prepend-icon="ri-file-excel-2-line"
        :disabled="isLoading || data.length === 0"
        @click="exportExcel"
      >
        Export Excel
      </VBtn>
    </div>

    <!-- Summary KPI Cards -->
    <VRow class="mb-4">
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-success">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-medium-emphasis font-weight-medium">FAST MOVING</div>
              <div class="text-h5 font-weight-bold text-success mt-1">{{ summary.fast_moving }} <span class="text-caption text-medium-emphasis">SKU</span></div>
            </div>
            <VAvatar color="success" variant="tonal" size="42">
              <VIcon icon="ri-rocket-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Penjualan rutin & perputaran tinggi</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-info">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-medium-emphasis font-weight-medium">MEDIUM MOVING</div>
              <div class="text-h5 font-weight-bold text-info mt-1">{{ summary.medium_moving }} <span class="text-caption text-medium-emphasis">SKU</span></div>
            </div>
            <VAvatar color="info" variant="tonal" size="42">
              <VIcon icon="ri-box-3-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Penjualan stabil normal</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-warning">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-medium-emphasis font-weight-medium">SLOW MOVING</div>
              <div class="text-h5 font-weight-bold text-warning mt-1">{{ summary.slow_moving }} <span class="text-caption text-medium-emphasis">SKU</span></div>
            </div>
            <VAvatar color="warning" variant="tonal" size="42">
              <VIcon icon="ri-time-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Penjualan rendah / lambat</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-error">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-error font-weight-bold">DEAD STOCK (MACET)</div>
              <div class="text-h5 font-weight-bold text-error mt-1">{{ summary.dead_stock }} <span class="text-caption text-medium-emphasis">SKU</span></div>
            </div>
            <VAvatar color="error" variant="tonal" size="42">
              <VIcon icon="ri-alert-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-error font-weight-medium mt-2">
            Nilai Aset Mengendap: {{ formatRupiah(summary.total_idle_asset_value) }}
          </div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Filter Controls Card -->
    <VCard elevation="2" class="mb-4">
      <VCardText class="d-flex flex-wrap gap-4 align-center py-4">
        <!-- Month & Year -->
        <VSelect
          v-model="selectedMonth"
          :items="months"
          item-title="title"
          item-value="value"
          density="compact"
          label="Bulan"
          style="max-width: 140px"
          hide-details
          @update:model-value="onFilterChange"
        />

        <VSelect
          v-model="selectedYear"
          :items="years"
          density="compact"
          label="Tahun"
          style="max-width: 110px"
          hide-details
          @update:model-value="onFilterChange"
        />

        <!-- Branch Filter -->
        <VAutocomplete
          v-model="selectedBranch"
          :items="branches"
          item-title="name"
          item-value="id"
          placeholder="Semua Cabang"
          label="Cabang"
          clearable
          density="compact"
          style="max-width: 200px"
          hide-details
          @update:model-value="onFilterChange"
        />

        <!-- Category Filter -->
        <VAutocomplete
          v-model="selectedCategory"
          :items="categories"
          item-title="name"
          item-value="id"
          placeholder="Semua Kategori"
          label="Kategori"
          clearable
          density="compact"
          style="max-width: 200px"
          hide-details
          @update:model-value="onFilterChange"
        />

        <!-- Speed Filter -->
        <VSelect
          v-model="selectedSpeed"
          :items="speedOptions"
          item-title="title"
          item-value="value"
          label="Klasifikasi FSN"
          density="compact"
          style="max-width: 220px"
          hide-details
          @update:model-value="onFilterChange"
        />

        <VSpacer />

        <!-- Search input -->
        <VTextField
          v-model="search"
          density="compact"
          placeholder="Cari Produk / SKU / Merk..."
          prepend-inner-icon="ri-search-line"
          style="max-width: 260px;"
          hide-details
          clearable
          @update:model-value="handleSearch"
        />
      </VCardText>

      <VDivider />

      <!-- Data Table -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="headers"
        :items="data"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        hover
        @update:options="fetchReport"
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

        <template #item.terjual="{ item }">
          <VChip
            :color="item.terjual > 0 ? 'primary' : 'secondary'"
            size="small"
            variant="tonal"
            class="font-weight-bold"
          >
            {{ item.terjual }} unit
          </VChip>
        </template>

        <template #item.total_omset="{ item }">
          <span class="font-weight-medium text-success">
            {{ formatRupiah(item.total_omset) }}
          </span>
        </template>

        <template #item.sisa_stok="{ item }">
          <VChip
            :color="item.sisa_stok > 0 ? 'info' : 'error'"
            size="small"
            variant="tonal"
          >
            {{ item.sisa_stok }} unit
          </VChip>
        </template>

        <template #item.nilai_aset="{ item }">
          <span :class="item.kategori_kecepatan === 'Dead Stock' ? 'text-error font-weight-bold' : 'text-medium-emphasis'">
            {{ formatRupiah(item.nilai_aset) }}
          </span>
        </template>
        
        <template #item.kategori_kecepatan="{ item }">
          <VChip
            :color="getSpeedBadge(item.kategori_kecepatan).color"
            size="small"
            variant="elevated"
            class="font-weight-medium"
          >
            <VIcon
              :icon="getSpeedBadge(item.kategori_kecepatan).icon"
              size="14"
              class="me-1"
            />
            {{ getSpeedBadge(item.kategori_kecepatan).text }}
          </VChip>
        </template>

        <template #no-data>
          <div class="pa-4 text-center text-medium-emphasis">
            Tidak ada data transaksi produk pada periode ini.
          </div>
        </template>
      </VDataTableServer>
    </VCard>
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Fast/Slow Moving
</route>
