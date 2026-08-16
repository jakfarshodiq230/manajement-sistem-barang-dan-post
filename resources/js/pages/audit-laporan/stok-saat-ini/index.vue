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
    params.page = page.value
    params.itemsPerPage = itemsPerPage.value
    if (search.value) {
      params.search = search.value
    }
    const res = await $api('/apps/reports/current-stock', { query: params })

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
    params.itemsPerPage = -1 // Get all data
    if (search.value) params.search = search.value

    const res = await $api('/apps/reports/current-stock', { query: params })
    const allData = res.data || res

    const exportData = allData.map((item, index) => {
      return {
        'No': index + 1,
        'Kode Barang': item.kode_barang,
        'Nama Barang': item.nama_barang,
        'Kategori': item.kategori,
        'Cabang': item.cabang,
        'Harga Jual': item.harga_jual,
        'Stok Saat Ini': item.sisa_stok,
        'Nilai Persediaan': item.nilai_aset,
      }
    })

  const worksheet = XLSX.utils.json_to_sheet(exportData)
  const workbook = XLSX.utils.book_new()

  XLSX.utils.book_append_sheet(workbook, worksheet, "Stok Saat Ini")
  XLSX.writeFile(workbook, `Laporan_Stok_Saat_Ini.xlsx`)
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

const formatCurrency = value => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value)
}

const headers = [
  { title: 'KODE BARANG', key: 'kode_barang' },
  { title: 'NAMA BARANG', key: 'nama_barang' },
  { title: 'KATEGORI', key: 'kategori' },
  { title: 'CABANG', key: 'cabang' },
  { title: 'HARGA JUAL', key: 'harga_jual', align: 'end' },
  { title: 'SISA STOK', key: 'sisa_stok', align: 'center' },
  { title: 'NILAI ASET', key: 'nilai_aset', align: 'end' },
]
</script>

<template>
  <div class="pa-4">
    <div class="d-flex align-center justify-space-between mb-4">
      <h2 class="text-h5 font-weight-bold">
        Laporan Stok Barang Saat Ini
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
        <template #item.harga_jual="{ item }">
          {{ formatCurrency(item.harga_jual) }}
        </template>
        
        <template #item.sisa_stok="{ item }">
          <VChip
            :color="item.sisa_stok > 10 ? 'success' : (item.sisa_stok > 0 ? 'warning' : 'error')"
            size="small"
          >
            {{ item.sisa_stok }}
          </VChip>
        </template>
        
        <template #item.nilai_aset="{ item }">
          <span class="font-weight-bold">{{ formatCurrency(item.nilai_aset) }}</span>
        </template>
      </VDataTableServer>
    </VCard>
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Stok Saat Ini
</route>
