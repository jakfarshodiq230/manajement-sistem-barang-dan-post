<script setup>
import { ref, onMounted, computed } from 'vue'
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

    const res = await $api('/apps/reports/stock-history', { query: params })

    dates.value = res.dates
    data.value = res.data
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

    const res = await $api('/apps/reports/stock-history', { query: params })
    const allData = res.data
    const allDates = res.dates

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
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value)
}

const formatDate = dateString => {
  return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
}
</script>

<template>
  <div class="pa-4">
    <div class="d-flex align-center justify-space-between mb-4">
      <h2 class="text-h5 font-weight-bold">
        Laporan Riwayat Stok
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

      <VCardText
        class="px-0 pt-0 pb-0"
        style="overflow-x: auto;"
      >
        <VTable
          class="text-no-wrap text-caption"
          density="compact"
          hover
        >
          <thead>
            <tr>
              <th
                rowspan="2"
                class="text-uppercase text-center border-b font-weight-bold bg-green-lighten-4"
              >
                No
              </th>
              <th
                rowspan="2"
                class="text-uppercase text-center border-b font-weight-bold bg-green-lighten-4"
              >
                Kode Barang
              </th>
              <th
                rowspan="2"
                class="text-uppercase text-center border-b font-weight-bold bg-green-lighten-4"
              >
                Nama Barang
              </th>
              <th
                rowspan="2"
                class="text-uppercase text-center border-b font-weight-bold bg-green-lighten-4"
              >
                Kategori
              </th>
              <th
                rowspan="2"
                class="text-uppercase text-center border-b font-weight-bold bg-green-lighten-4"
              >
                Cabang
              </th>
              <th
                rowspan="2"
                class="text-uppercase text-center border-b font-weight-bold bg-green-lighten-4"
              >
                Harga Barang
              </th>
              <th
                rowspan="2"
                class="text-uppercase text-center border-b font-weight-bold bg-green-lighten-4"
              >
                Stok Awal
              </th>
              <th
                v-if="dates.length"
                :colspan="dates.length * 2"
                class="text-uppercase text-center border-b font-weight-bold bg-green-lighten-3"
              >
                Periode Harian
              </th>
              <th
                rowspan="2"
                class="text-uppercase text-center border-b font-weight-bold bg-green-lighten-4"
              >
                Sisa Stok
              </th>
              <th
                rowspan="2"
                class="text-uppercase text-center border-b font-weight-bold bg-green-lighten-4"
              >
                Nilai Persediaan Akhir
              </th>
            </tr>
            <tr>
              <template
                v-for="d in dates"
                :key="d"
              >
                <th
                  class="text-center font-weight-bold border-b bg-green-lighten-5"
                  colspan="2"
                >
                  {{ formatDate(d) }}
                </th>
              </template>
            </tr>
            <tr>
              <th
                colspan="7"
                class="border-b bg-grey-lighten-4"
              />
              <template
                v-for="d in dates"
                :key="d + '-sub'"
              >
                <th class="text-center border-b bg-blue-lighten-5">
                  Msk
                </th>
                <th class="text-center border-b bg-red-lighten-5">
                  Klr
                </th>
              </template>
              <th
                colspan="2"
                class="border-b bg-grey-lighten-4"
              />
            </tr>
          </thead>
          <tbody>
            <tr v-if="isLoading">
              <td
                :colspan="9 + (dates.length * 2)"
                class="text-center py-4"
              >
                Memuat data...
              </td>
            </tr>
            <tr v-else-if="data.length === 0">
              <td
                :colspan="7 + dates.length * 2 + 2"
                class="text-center py-4 text-disabled"
              >
                Belum ada data stok pada periode ini.
              </td>
            </tr>
            <tr
              v-for="(item, index) in data"
              :key="item.id"
            >
              <td class="text-center border-b">
                {{ (page - 1) * itemsPerPage + index + 1 }}
              </td>
              <td>{{ item.kode_barang }}</td>
              <td class="font-weight-medium">
                {{ item.nama_barang }}
              </td>
              <td>{{ item.kategori }}</td>
              <td>{{ item.cabang }}</td>
              <td class="text-right">
                {{ formatCurrency(item.harga_barang) }}
              </td>
              <td class="text-center font-weight-bold">
                {{ item.stok_awal }}
              </td>
              <template
                v-for="d in dates"
                :key="d + '-data'"
              >
                <td class="text-center text-success font-weight-medium">
                  {{ item.harian[d]?.in || 0 }}
                </td>
                <td class="text-center text-error font-weight-medium">
                  {{ item.harian[d]?.out || 0 }}
                </td>
              </template>
              <td class="text-center font-weight-bold text-primary">
                {{ item.sisa_stok }}
              </td>
              <td class="text-right font-weight-bold">
                {{ formatCurrency(item.nilai_persediaan_akhir) }}
              </td>
            </tr>
          </tbody>
        </VTable>
      </VCardText>
      <VCardText class="d-flex align-center justify-end flex-wrap gap-4 pt-4 pb-4 border-t">
        <div class="d-flex align-center gap-2">
          <span class="text-sm text-medium-emphasis">Item per halaman:</span>
          <VSelect
            v-model="itemsPerPage"
            :items="[10, 15, 25, 50, 100]"
            variant="plain"
            density="compact"
            hide-details
            style="width: 70px"
            @update:model-value="page = 1; fetchReport()"
          />
        </div>

        <span class="text-sm text-medium-emphasis">
          {{ data.length > 0 ? (page - 1) * itemsPerPage + 1 : 0 }}-{{ Math.min(page * itemsPerPage, totalItems) }} dari {{ totalItems }}
        </span>

        <div class="d-flex align-center">
          <VBtn
            icon
            variant="text"
            color="secondary"
            size="small"
            :disabled="page === 1"
            @click="page = 1; fetchReport()"
          >
            <VIcon icon="ri-skip-left-line" size="24" />
          </VBtn>
          <VBtn
            icon
            variant="text"
            color="secondary"
            size="small"
            :disabled="page === 1"
            @click="page--; fetchReport()"
          >
            <VIcon icon="ri-arrow-left-s-line" size="24" />
          </VBtn>
          <VBtn
            icon
            variant="text"
            color="secondary"
            size="small"
            :disabled="page === totalPages || totalPages === 0"
            @click="page++; fetchReport()"
          >
            <VIcon icon="ri-arrow-right-s-line" size="24" />
          </VBtn>
          <VBtn
            icon
            variant="text"
            color="secondary"
            size="small"
            :disabled="page === totalPages || totalPages === 0"
            @click="page = totalPages; fetchReport()"
          >
            <VIcon icon="ri-skip-right-line" size="24" />
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
