<script setup>
import { ref, onMounted, computed } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'

const agingData = ref([])
const branches = ref([])
const isLoading = ref(false)
const search = ref('')
const activeTab = ref('all')
const selectedBranch = ref(null)

const snackbar = useSnackbarStore()

// Pagination
const page = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)
let searchTimeout = null

const tableHeaders = [
  { title: 'KODE BARANG', key: 'kode_barang' },
  { title: 'NAMA BARANG', key: 'nama_barang' },
  { title: 'KATEGORI', key: 'kategori' },
  { title: 'CABANG', key: 'cabang' },
  { title: 'TGL MASUK', key: 'tanggal_masuk' },
  { title: 'TGL EXPIRED', key: 'tanggal_expired' },
  { title: 'SISA HARI EXPIRED', key: 'sisa_hari_expired', align: 'center' },
  { title: 'UMUR STOK (HARI)', key: 'umur_stok_hari', align: 'center' },
  { title: 'SISA QTY', key: 'qty_sisa', align: 'center' },
  { title: 'NILAI ASET', key: 'nilai_aset' },
]

const formatRupiah = value => {
  if (value === null || value === undefined) return '-'
  
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
  const totalMenit = Math.round(Math.abs(nilai) * 24 * 60)
  const hari = Math.floor(totalMenit / (60 * 24))
  const jam = Math.floor((totalMenit % (60 * 24)) / 60)
  const menit = totalMenit % 60

  if (hari > 0 && jam > 0) return `${hari} hari ${jam} jam`
  if (hari > 0) return `${hari} hari`
  if (jam > 0 && menit > 0) return `${jam} jam ${menit} mnt`
  if (jam > 0) return `${jam} jam`
  return `${menit} menit`
}

const fetchData = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      filter: activeTab.value,
    }
    
    if (search.value) {
      params.search = search.value
    }

    if (selectedBranch.value) {
      params.branch_id = selectedBranch.value
    }
    
    const [resData, resBranches] = await Promise.all([
      $api('/apps/reports/stock-aging', { query: params }),
      $api('/apps/branches')
    ])

    agingData.value = resData.data
    totalItems.value = resData.total || resData.data.length
    branches.value = resBranches
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal memuat data laporan', 'error')
  } finally {
    isLoading.value = false
  }
}

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchData()
  }, 500)
}

const handleTabChange = () => {
  page.value = 1
  fetchData()
}

onMounted(() => {
  fetchData()
})

const exportExcel = async () => {
  isLoading.value = true
  try {
    const params = {
      itemsPerPage: -1,
      filter: activeTab.value,
    }

    if (search.value) params.search = search.value
    if (selectedBranch.value) params.branch_id = selectedBranch.value
    
    const res = await $api('/apps/reports/stock-aging', { query: params })
    const allData = res.data

    if (!allData || allData.length === 0) {
      snackbar.show('Tidak ada data untuk diexport', 'warning')
      isLoading.value = false
      
      return
    }

    const headers = ['KODE BARANG', 'NAMA BARANG', 'KATEGORI', 'CABANG', 'TGL MASUK', 'TGL EXPIRED', 'SISA HARI EXPIRED', 'UMUR STOK (HARI)', 'SISA QTY', 'HARGA BELI', 'NILAI ASET']
    const csvRows = [headers.join(',')]

    allData.forEach(item => {
      const sisaLabel = item.sisa_hari_expired !== null
        ? (item.sisa_hari_expired < 0 ? `Expired (${formatDurasi(item.sisa_hari_expired)} lalu)` : formatDurasi(item.sisa_hari_expired))
        : '-'

      const umurLabel = item.umur_stok_hari !== null ? formatDurasi(item.umur_stok_hari) : '-'

      const row = [
        `"${item.kode_barang || ''}"`,
        `"${item.nama_barang || ''}"`,
        `"${item.kategori || ''}"`,
        `"${item.cabang || ''}"`,
        `"${item.tanggal_masuk ? item.tanggal_masuk.substring(0, 10) : ''}"`,
        `"${item.tanggal_expired ? item.tanggal_expired.substring(0, 10) : ''}"`,
        `"${sisaLabel}"`,
        `"${umurLabel}"`,
        `${item.qty_sisa || 0}`,
        `${item.harga_beli || 0}`,
        `${item.nilai_aset || 0}`,
      ]
      csvRows.push(row.join(','))
    })

    const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')

    link.href = url
    link.setAttribute('download', `Laporan_Usia_Stok_${activeTab.value}_${new Date().toISOString().split('T')[0]}.csv`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    snackbar.show('Berhasil export data', 'success')
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal export data', 'error')
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <section>
    <!-- Page Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="text-h4 mb-0">
          Analisis Usia Stok (Aging)
        </h2>
        <p class="text-body-1 mb-0 text-disabled mt-1">
          Pantau FIFO (Umur Stok Lama), FEFO (Segera Kedaluwarsa), dan LIFO (Stok Baru)
        </p>
      </div>
      
      <div class="d-flex gap-4">
        <VBtn
          color="success"
          variant="tonal"
          prepend-icon="ri-file-excel-2-line"
          :loading="isLoading"
          @click="exportExcel"
        >
          Export Excel
        </VBtn>
      </div>
    </div>

    <VCard>
      <!-- Tabs -->
      <VTabs
        v-model="activeTab"
        class="px-4 border-b"
        @update:model-value="handleTabChange"
      >
        <VTab value="all">
          Semua Stok
        </VTab>
        <VTab value="fefo">
          FEFO (Expired Soon)
        </VTab>
        <VTab value="fifo">
          FIFO (Dead Stock / Old)
        </VTab>
        <VTab value="lifo">
          LIFO (Stok Baru)
        </VTab>
      </VTabs>

      <VCardItem class="pa-4 pb-0">
        <div class="d-flex flex-wrap align-center justify-space-between w-100 gap-4">
          <VCardTitle class="px-0">
            Daftar Batch Stok
          </VCardTitle>
          <div class="d-flex align-center gap-4">
            <VSelect
              v-model="selectedBranch"
              :items="branches"
              item-title="name"
              item-value="id"
              placeholder="Filter Cabang"
              density="compact"
              clearable
              variant="outlined"
              style="min-width: 150px;"
              hide-details
              @update:model-value="handleSearch"
            />
            <div style="width: 250px;">
              <VTextField
                v-model="search"
                prepend-inner-icon="ri-search-line"
                placeholder="Cari Barang/SKU..."
                density="compact"
                hide-details
                variant="outlined"
                clearable
                @update:model-value="handleSearch"
              />
            </div>
          </div>
        </div>
      </VCardItem>

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="tableHeaders"
        :items="agingData"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        @update:options="fetchData"
      >
        <template #item.tanggal_masuk="{ item }">
          {{ formatDate(item.tanggal_masuk) }}
        </template>
        
        <template #item.tanggal_expired="{ item }">
          {{ formatDate(item.tanggal_expired) }}
        </template>

        <template #item.sisa_hari_expired="{ item }">
          <VChip
            v-if="item.sisa_hari_expired !== null"
            :color="item.sisa_hari_expired < 0 ? 'error' : (item.sisa_hari_expired <= 30 ? 'warning' : 'success')"
            size="small"
            variant="tonal"
          >
            {{ item.sisa_hari_expired < 0 ? 'Expired (' + formatDurasi(item.sisa_hari_expired) + ' lalu)' : formatDurasi(item.sisa_hari_expired) }}
          </VChip>
          <span v-else class="text-medium-emphasis">-</span>
        </template>

        <template #item.umur_stok_hari="{ item }">
          <VChip
            :color="item.umur_stok_hari > 90 ? 'error' : (item.umur_stok_hari > 30 ? 'warning' : 'success')"
            size="small"
            variant="tonal"
          >
            {{ formatDurasi(item.umur_stok_hari) }}
          </VChip>
        </template>
        
        <template #item.qty_sisa="{ item }">
          <span class="font-weight-bold">{{ item.qty_sisa }}</span>
        </template>
        
        <template #item.nilai_aset="{ item }">
          {{ formatRupiah(item.nilai_aset) }}
        </template>
      </VDataTableServer>
    </VCard>
  </section>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Analisis Stok
</route>
