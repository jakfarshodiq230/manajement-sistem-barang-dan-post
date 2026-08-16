<script setup>
import { ref, onMounted, computed } from 'vue'
import * as XLSX from 'xlsx'

const data = ref([])
const isLoading = ref(false)
const search = ref('')
const selectedBranch = ref(null)
const branches = ref([])

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

const fetchBranches = async () => {
  try {
    const res = await $api('/apps/branches')

    branches.value = res.data || res
  } catch (error) {
    console.error(error)
  }
}

const fetchReport = async () => {
  isLoading.value = true
  try {
    const params = {}
    if (selectedBranch.value) {
      params.branch_id = selectedBranch.value
    }
    
    // Add date range based on selected month and year
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

    data.value = res.data
    if (res.total !== undefined) {
      totalItems.value = res.total
    }
  } catch (error) {
    console.error(error)
  } finally {
    isLoading.value = false
  }
}

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchReport()
  }, 500)
}

const exportExcel = async () => {
  if (data.value.length === 0) return
  
  isLoading.value = true
  try {
    const params = {}
    if (selectedBranch.value) params.branch_id = selectedBranch.value
    
    const startStr = `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}-01`
    const lastDay = new Date(selectedYear.value, selectedMonth.value, 0).getDate()
    const endStr = `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}-${lastDay}`
    
    params.start_date = startStr
    params.end_date = endStr
    params.itemsPerPage = -1 // Get all data
    if (search.value) params.search = search.value

    const res = await $api('/apps/reports/fast-slow-moving', { query: params })
    const allData = res.data || res

    const exportData = allData.map((item, index) => {
      return {
        'No': index + 1,
        'Kode Barang': item.kode_barang,
        'Nama Barang': item.nama_barang,
        'Kategori': item.kategori,
        'Cabang': item.cabang,
        'Total Terjual': item.terjual,
        'Status': item.kategori_kecepatan,
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
  await fetchBranches()
  fetchReport()
})

const headers = [
  { title: 'KODE BARANG', key: 'kode_barang' },
  { title: 'NAMA BARANG', key: 'nama_barang' },
  { title: 'KATEGORI', key: 'kategori' },
  { title: 'CABANG', key: 'cabang' },
  { title: 'TERJUAL (QTY)', key: 'terjual', align: 'center' },
  { title: 'SISA STOK', key: 'sisa_stok', align: 'center' },
  { title: 'KATEGORI KECEPATAN', key: 'kategori_kecepatan', align: 'center' },
]

const getSpeedColor = speed => {
  if (speed === 'Fast Moving') return 'success'
  if (speed === 'Slow Moving') return 'error'
  
  return 'warning'
}
</script>

<template>
  <div class="pa-4">
    <div class="d-flex align-center justify-space-between mb-4">
      <h2 class="text-h5 font-weight-bold">
        Laporan Fast & Slow Moving
      </h2>
      <VBtn
        color="success"
        prepend-icon="ri-file-excel-line"
        :disabled="isLoading || data.length === 0"
        @click="exportExcel"
      >
        Export Excel
      </VBtn>
    </div>

    <VCard>
      <VCardText class="d-flex flex-wrap gap-4 align-center">
        <VSelect
          v-model="selectedMonth"
          :items="months"
          item-title="title"
          item-value="value"
          density="compact"
          style="max-width: 150px"
          hide-details
          @update:model-value="fetchReport"
        />

        <VSelect
          v-model="selectedYear"
          :items="years"
          density="compact"
          style="max-width: 120px"
          hide-details
          @update:model-value="fetchReport"
        />

        <VSpacer />
        
        <VAutocomplete
          v-model="selectedBranch"
          :items="branches"
          item-title="name"
          item-value="id"
          placeholder="Semua Cabang"
          clearable
          density="compact"
          style="max-width: 250px"
          @update:model-value="fetchReport"
        />

        <VTextField
          v-model="search"
          density="compact"
          placeholder="Cari Produk..."
          append-inner-icon="ri-search-line"
          style="max-width: 300px;"
          @update:model-value="handleSearch"
        />
      </VCardText>

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
        <template #item.terjual="{ item }">
          <span class="font-weight-bold text-primary">{{ item.terjual }}</span>
        </template>
        
        <template #item.kategori_kecepatan="{ item }">
          <VChip
            :color="getSpeedColor(item.kategori_kecepatan)"
            size="small"
            variant="elevated"
          >
            {{ item.kategori_kecepatan }}
          </VChip>
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
