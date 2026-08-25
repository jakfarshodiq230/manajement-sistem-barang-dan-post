<script setup>
import { ref, onMounted } from 'vue'
import * as XLSX from 'xlsx'

const data = ref([])
const isLoading = ref(false)
const search = ref('')
const selectedBranch = ref(null)
const branches = ref([])

const summary = ref({
  total_items: 0,
  total_stock: 0,
  total_asset_value: 0,
  total_low_stock: 0,
})

// Pagination
const page = ref(1)
const itemsPerPage = ref(15)
const totalItems = ref(0)
let searchTimeout = null

const fetchBranches = async () => {
  try {
    const res = await $api('/apps/branches')
    branches.value = res.data || res || []
  } catch (error) {
    console.error(error)
  }
}

const fetchReport = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
    }
    if (selectedBranch.value) params.branch_id = selectedBranch.value
    if (search.value) params.search = search.value

    const res = await $api('/apps/reports/current-stock', { query: params })

    data.value = res.data || []
    if (res.total !== undefined) {
      totalItems.value = res.total
    }
    if (res.summary) {
      summary.value = res.summary
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
    const params = {
      itemsPerPage: -1,
    }
    if (selectedBranch.value) params.branch_id = selectedBranch.value
    if (search.value) params.search = search.value

    const res = await $api('/apps/reports/current-stock', { query: params })
    const allData = res.data || res

    const exportData = allData.map((item, index) => {
      return {
        'No': index + 1,
        'Kode SKU': item.kode_barang,
        'Nama Produk': item.nama_barang,
        'Merk': item.brand || '-',
        'Kategori': item.kategori,
        'Cabang': item.cabang,
        'Harga Jual Satuan': item.harga_jual,
        'Sisa Stok Fisik': item.sisa_stok,
        'Total Nilai Aset': item.nilai_aset,
      }
    })

    const worksheet = XLSX.utils.json_to_sheet(exportData)
    const workbook = XLSX.utils.book_new()

    XLSX.utils.book_append_sheet(workbook, worksheet, "Stok Saat Ini")
    XLSX.writeFile(workbook, `Laporan_Stok_Saat_Ini_${new Date().toISOString().split('T')[0]}.xlsx`)
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
  if (!value || isNaN(value)) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value)
}

const headers = [
  { title: 'PRODUK / SKU', key: 'nama_barang' },
  { title: 'KATEGORI', key: 'kategori' },
  { title: 'CABANG', key: 'cabang' },
  { title: 'HARGA JUAL', key: 'harga_jual', align: 'end' },
  { title: 'SISA STOK', key: 'sisa_stok', align: 'center' },
  { title: 'NILAI ASET POTENSIAL', key: 'nilai_aset', align: 'end' },
]
</script>

<template>
  <div class="pa-4">
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="text-h4 font-weight-bold mb-1">
          Laporan Stok Barang Saat Ini
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Ringkasan posisi stok fisik terkini dan valuasi total nilai persediaan barang.
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
        <VCard elevation="2" class="pa-4 border-s-lg border-primary">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-medium-emphasis font-weight-medium">TOTAL SKU AKTIF</div>
              <div class="text-h5 font-weight-bold text-primary mt-1">{{ summary.total_items }} <span class="text-caption text-medium-emphasis">SKU</span></div>
            </div>
            <VAvatar color="primary" variant="tonal" size="42">
              <VIcon icon="ri-box-3-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Varian barang di inventori</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-info">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-medium-emphasis font-weight-medium">TOTAL FISIK BARANG</div>
              <div class="text-h5 font-weight-bold text-info mt-1">{{ summary.total_stock.toLocaleString('id-ID') }} <span class="text-caption text-medium-emphasis">Unit</span></div>
            </div>
            <VAvatar color="info" variant="tonal" size="42">
              <VIcon icon="ri-stack-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Kuantitas on-hand cabang</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-success">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-medium-emphasis font-weight-medium">TOTAL VALUASI ASET</div>
              <div class="text-h5 font-weight-bold text-success mt-1">{{ formatCurrency(summary.total_asset_value) }}</div>
            </div>
            <VAvatar color="success" variant="tonal" size="42">
              <VIcon icon="ri-money-dollar-circle-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Estimasi nilai harga jual</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-warning">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-warning font-weight-bold">STOK MENIPIS (&le; 5)</div>
              <div class="text-h5 font-weight-bold text-warning mt-1">{{ summary.total_low_stock }} <span class="text-caption text-medium-emphasis">SKU</span></div>
            </div>
            <VAvatar color="warning" variant="tonal" size="42">
              <VIcon icon="ri-alarm-warning-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-warning font-weight-medium mt-2">Segera lakukan re-order PO</div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Table Card -->
    <VCard elevation="2">
      <VCardText class="d-flex flex-wrap gap-4 align-center py-4">
        <VAutocomplete
          v-model="selectedBranch"
          :items="branches"
          item-title="name"
          item-value="id"
          placeholder="Semua Cabang"
          label="Cabang"
          density="compact"
          style="max-width: 240px;"
          clearable
          hide-details
          @update:model-value="onFilterChange"
        />

        <VTextField
          v-model="search"
          density="compact"
          placeholder="Cari Produk / SKU / Merk..."
          prepend-inner-icon="ri-search-line"
          style="max-width: 280px;"
          clearable
          hide-details
          @update:model-value="handleSearch"
        />

        <VSpacer />

        <VBtn
          prepend-icon="ri-refresh-line"
          variant="tonal"
          color="secondary"
          size="small"
          :loading="isLoading"
          @click="fetchReport"
        >
          Muat Ulang
        </VBtn>
      </VCardText>

      <VDivider />

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

        <template #item.harga_jual="{ item }">
          <span class="font-weight-medium">{{ formatCurrency(item.harga_jual) }}</span>
        </template>

        <template #item.sisa_stok="{ item }">
          <VChip
            :color="item.sisa_stok > 10 ? 'success' : (item.sisa_stok > 0 ? 'warning' : 'error')"
            size="small"
            variant="tonal"
            class="font-weight-bold"
          >
            {{ item.sisa_stok }} unit
          </VChip>
        </template>

        <template #item.nilai_aset="{ item }">
          <span class="font-weight-bold text-success">
            {{ formatCurrency(item.nilai_aset) }}
          </span>
        </template>

        <template #no-data>
          <div class="pa-4 text-center text-medium-emphasis">
            Tidak ada produk yang cocok dengan pencarian.
          </div>
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
