<script setup>
import { ref, onMounted } from 'vue'
import * as XLSX from 'xlsx'

const data = ref([])
const dates = ref([])
const isLoading = ref(false)
const search = ref('')
const selectedBranch = ref(null)
const branches = ref([])

// Pagination
const page = ref(1)
const itemsPerPage = ref(15)
const totalPages = ref(1)
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
    branches.value = res.data || res || []
  } catch (error) {
    console.error(error)
  }
}

const fetchReport = async () => {
  isLoading.value = true
  try {
    const params = {}
    if (selectedBranch.value) params.branch_id = selectedBranch.value
    
    // Add date range based on selected month and year
    const startStr = `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}-01`
    const lastDay = new Date(selectedYear.value, selectedMonth.value, 0).getDate()
    const endStr = `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}-${lastDay}`
    
    params.start_date = startStr
    params.end_date = endStr
    params.page = page.value
    params.itemsPerPage = itemsPerPage.value
    if (search.value) params.search = search.value

    const res = await $api('/apps/reports/stock-history', { query: params })

    dates.value = res.dates || []
    data.value = res.data || []
    if (res.last_page !== undefined) {
      totalPages.value = res.last_page
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
    
    const startStr = `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}-01`
    const lastDay = new Date(selectedYear.value, selectedMonth.value, 0).getDate()
    const endStr = `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}-${lastDay}`
    
    params.start_date = startStr
    params.end_date = endStr
    params.itemsPerPage = -1 // Get all data
    if (search.value) params.search = search.value

    const res = await $api('/apps/reports/stock-history', { query: params })
    const allData = res.data || []
    const allDates = res.dates || []

    const exportData = allData.map((item, index) => {
      const row = {
        'No': index + 1,
        'Kode Barang': item.kode_barang,
        'Nama Barang': item.nama_barang,
        'Kategori': item.kategori,
        'Cabang': item.cabang,
        'Harga Barang': item.harga_barang,
        'Stok Awal': item.stok_awal,
      }

      allDates.forEach(date => {
        row[`${formatDate(date)} (Masuk)`] = item.harian[date]?.in || 0
        row[`${formatDate(date)} (Keluar)`] = item.harian[date]?.out || 0
      })

      row['Sisa Stok Akhir'] = item.sisa_stok
      row['Nilai Persediaan Akhir'] = item.nilai_persediaan_akhir

      return row
    })

    const worksheet = XLSX.utils.json_to_sheet(exportData)
    const workbook = XLSX.utils.book_new()

    XLSX.utils.book_append_sheet(workbook, worksheet, "Riwayat Stok")
    XLSX.writeFile(workbook, `Laporan_Riwayat_Stok_${selectedMonth.value}_${selectedYear.value}.xlsx`)
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

const formatDate = dateString => {
  return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
}
</script>

<template>
  <div class="pa-4">
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="text-h4 font-weight-bold mb-1">
          Laporan Riwayat Mutasi Stok Harian
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Kartu stok akuntansi pergerakan kuantitas masuk (pembelian/retur/inbound) dan keluar (penjualan/mutasi) per tanggal.
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

    <!-- Filter Card -->
    <VCard elevation="2">
      <VCardText class="d-flex flex-wrap gap-4 align-center py-4">
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

        <VAutocomplete
          v-model="selectedBranch"
          :items="branches"
          item-title="name"
          item-value="id"
          placeholder="Semua Cabang"
          label="Cabang"
          clearable
          density="compact"
          style="max-width: 220px"
          hide-details
          @update:model-value="onFilterChange"
        />

        <VTextField
          v-model="search"
          density="compact"
          placeholder="Cari Produk / SKU..."
          prepend-inner-icon="ri-search-line"
          style="max-width: 260px;"
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

      <!-- Scrollable Matrix Table -->
      <div style="overflow-x: auto;">
        <VTable
          class="text-no-wrap text-caption"
          density="compact"
          hover
        >
          <thead>
            <tr class="bg-primary text-white">
              <th rowspan="2" class="text-center font-weight-bold border-b text-white" style="width: 45px;">No</th>
              <th rowspan="2" class="text-left font-weight-bold border-b text-white">Kode SKU</th>
              <th rowspan="2" class="text-left font-weight-bold border-b text-white">Nama Produk</th>
              <th rowspan="2" class="text-left font-weight-bold border-b text-white">Kategori</th>
              <th rowspan="2" class="text-left font-weight-bold border-b text-white">Cabang</th>
              <th rowspan="2" class="text-right font-weight-bold border-b text-white">Harga</th>
              <th rowspan="2" class="text-center font-weight-bold border-b text-white">Stok Awal</th>
              <th
                v-if="dates.length"
                :colspan="dates.length * 2"
                class="text-center font-weight-bold border-b text-white bg-primary-darken-1"
              >
                Pergerakan Mutasi Harian
              </th>
              <th rowspan="2" class="text-center font-weight-bold border-b text-white">Sisa Stok</th>
              <th rowspan="2" class="text-right font-weight-bold border-b text-white">Nilai Akhir</th>
            </tr>
            <tr class="bg-grey-100">
              <template v-for="d in dates" :key="d">
                <th class="text-center font-weight-bold border-b text-primary" colspan="2">
                  {{ formatDate(d) }}
                </th>
              </template>
            </tr>
            <tr class="bg-grey-50">
              <th colspan="7" class="border-b" />
              <template v-for="d in dates" :key="d + '-sub'">
                <th class="text-center border-b text-success font-weight-bold" style="min-width: 38px;">Msk</th>
                <th class="text-center border-b text-error font-weight-bold" style="min-width: 38px;">Klr</th>
              </template>
              <th colspan="2" class="border-b" />
            </tr>
          </thead>
          <tbody>
            <tr v-if="isLoading">
              <td :colspan="9 + (dates.length * 2)" class="text-center py-6 text-medium-emphasis">
                <VProgressCircular indeterminate color="primary" size="24" class="me-2" />
                Memuat data mutasi...
              </td>
            </tr>
            <tr v-else-if="data.length === 0">
              <td :colspan="9 + (dates.length * 2)" class="text-center py-6 text-medium-emphasis">
                Belum ada data riwayat stok pada periode yang dipilih.
              </td>
            </tr>
            <tr
              v-for="(item, index) in data"
              :key="item.id"
            >
              <td class="text-center border-b text-medium-emphasis">
                {{ (page - 1) * itemsPerPage + index + 1 }}
              </td>
              <td><code>{{ item.kode_barang }}</code></td>
              <td class="font-weight-medium text-subtitle-2">{{ item.nama_barang }}</td>
              <td>{{ item.kategori }}</td>
              <td>{{ item.cabang }}</td>
              <td class="text-right">{{ formatCurrency(item.harga_barang) }}</td>
              <td class="text-center font-weight-bold bg-grey-50">{{ item.stok_awal }}</td>
              <template v-for="d in dates" :key="d + '-data'">
                <td class="text-center font-weight-medium" :class="item.harian[d]?.in > 0 ? 'text-success font-weight-bold bg-green-50' : 'text-disabled'">
                  {{ item.harian[d]?.in || 0 }}
                </td>
                <td class="text-center font-weight-medium" :class="item.harian[d]?.out > 0 ? 'text-error font-weight-bold bg-red-50' : 'text-disabled'">
                  {{ item.harian[d]?.out || 0 }}
                </td>
              </template>
              <td class="text-center font-weight-bold text-primary bg-grey-50">
                {{ item.sisa_stok }}
              </td>
              <td class="text-right font-weight-bold text-success">
                {{ formatCurrency(item.nilai_persediaan_akhir) }}
              </td>
            </tr>
          </tbody>
        </VTable>
      </div>

      <!-- Pagination Footer -->
      <VCardText class="d-flex align-center justify-end flex-wrap gap-4 pt-4 pb-4 border-t">
        <div class="d-flex align-center gap-2">
          <span class="text-sm text-medium-emphasis">Item per halaman:</span>
          <VSelect
            v-model="itemsPerPage"
            :items="[10, 15, 25, 50, 100]"
            variant="plain"
            density="compact"
            hide-details
            style="width: 75px"
            @update:model-value="page = 1; fetchReport()"
          />
        </div>

        <span class="text-sm text-medium-emphasis">
          {{ data.length > 0 ? (page - 1) * itemsPerPage + 1 : 0 }}-{{ Math.min(page * itemsPerPage, totalItems) }} dari {{ totalItems }}
        </span>

        <div class="d-flex align-center gap-1">
          <VBtn
            icon
            variant="text"
            color="secondary"
            size="small"
            :disabled="page === 1"
            @click="page = 1; fetchReport()"
          >
            <VIcon icon="ri-skip-left-line" size="20" />
          </VBtn>
          <VBtn
            icon
            variant="text"
            color="secondary"
            size="small"
            :disabled="page === 1"
            @click="page--; fetchReport()"
          >
            <VIcon icon="ri-arrow-left-s-line" size="20" />
          </VBtn>
          <VBtn
            icon
            variant="text"
            color="secondary"
            size="small"
            :disabled="page === totalPages || totalPages === 0"
            @click="page++; fetchReport()"
          >
            <VIcon icon="ri-arrow-right-s-line" size="20" />
          </VBtn>
          <VBtn
            icon
            variant="text"
            color="secondary"
            size="small"
            :disabled="page === totalPages || totalPages === 0"
            @click="page = totalPages; fetchReport()"
          >
            <VIcon icon="ri-skip-right-line" size="20" />
          </VBtn>
        </div>
      </VCardText>
    </VCard>
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Riwayat Stok
</route>
