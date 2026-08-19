<script setup>
import { ref, onMounted } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import * as XLSX from 'xlsx'

const products = ref([])
const categories = ref([])
const selectedCategory = ref(null)
const search = ref('')
const isLoading = ref(false)
const snackbar = useSnackbarStore()

const summary = ref({
  total_sku: 0,
  total_warehouse: 0,
  total_store: 0,
  total_overall: 0,
})

// Pagination
const page = ref(1)
const itemsPerPage = ref(15)
const totalItems = ref(0)
let searchTimeout = null

const fetchCategories = async () => {
  try {
    const res = await $api('/apps/categories')
    categories.value = res.data || res || []
  } catch (error) {
    console.error(error)
  }
}

const fetchGlobalStock = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
    }
    
    if (selectedCategory.value) params.category_id = selectedCategory.value
    if (search.value) params.search = search.value
    
    const res = await $api('/apps/reports/global-stock', { query: params })
    if (res.success) {
      products.value = res.data || []
      if (res.total !== undefined) {
        totalItems.value = res.total
      }
      if (res.summary) {
        summary.value = res.summary
      }
    }
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal memuat data stok global', 'error')
  } finally {
    isLoading.value = false
  }
}

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchGlobalStock()
  }, 450)
}

const onFilterChange = () => {
  page.value = 1
  fetchGlobalStock()
}

const exportExcel = async () => {
  if (products.value.length === 0) return
  
  isLoading.value = true
  try {
    const params = {
      itemsPerPage: -1,
    }
    if (selectedCategory.value) params.category_id = selectedCategory.value
    if (search.value) params.search = search.value

    const res = await $api('/apps/reports/global-stock', { query: params })
    const allData = res.data || []

    const exportData = allData.map((item, index) => {
      return {
        'No': index + 1,
        'Kode SKU': item.sku,
        'Nama Produk': item.name,
        'Merk': item.brand || '-',
        'Kategori': item.category_name,
        'Stok Gudang Pusat': item.total_warehouse_stock,
        'Stok Toko / Cabang': item.total_store_stock,
        'Total Stok Keseluruhan': item.total_overall,
      }
    })

    const worksheet = XLSX.utils.json_to_sheet(exportData)
    const workbook = XLSX.utils.book_new()

    XLSX.utils.book_append_sheet(workbook, worksheet, "Stok Global")
    XLSX.writeFile(workbook, `Laporan_Stok_Global_${new Date().toISOString().split('T')[0]}.xlsx`)
  } catch (error) {
    console.error("Export failed", error)
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  await fetchCategories()
  fetchGlobalStock()
})

const tableHeaders = [
  { title: 'PRODUK / SKU', key: 'name' },
  { title: 'KATEGORI', key: 'category_name' },
  { title: 'STOK GUDANG', key: 'total_warehouse_stock', align: 'center' },
  { title: 'STOK TOKO', key: 'total_store_stock', align: 'center' },
  { title: 'TOTAL STOK', key: 'total_overall', align: 'center' },
  { title: 'RINCIAN LOKASI', key: 'data-table-expand', align: 'center' },
]
</script>

<template>
  <div class="pa-4">
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="text-h4 font-weight-bold mb-1">
          Laporan Stok Global
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Konsolidasi stok barang terpusat dari semua gudang logistik dan cabang toko.
        </p>
      </div>
      <VBtn
        color="success"
        prepend-icon="ri-file-excel-2-line"
        :disabled="isLoading || products.length === 0"
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
              <div class="text-h5 font-weight-bold text-primary mt-1">{{ totalItems }} <span class="text-caption text-medium-emphasis">Item</span></div>
            </div>
            <VAvatar color="primary" variant="tonal" size="42">
              <VIcon icon="ri-box-3-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Katalog produk terdata</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-warning">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-medium-emphasis font-weight-medium">STOK DI GUDANG</div>
              <div class="text-h5 font-weight-bold text-warning mt-1">{{ summary.total_warehouse.toLocaleString('id-ID') }} <span class="text-caption text-medium-emphasis">Pcs</span></div>
            </div>
            <VAvatar color="warning" variant="tonal" size="42">
              <VIcon icon="ri-building-2-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Persediaan Gudang Pusat</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-info">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-medium-emphasis font-weight-medium">STOK DI TOKO</div>
              <div class="text-h5 font-weight-bold text-info mt-1">{{ summary.total_store.toLocaleString('id-ID') }} <span class="text-caption text-medium-emphasis">Pcs</span></div>
            </div>
            <VAvatar color="info" variant="tonal" size="42">
              <VIcon icon="ri-store-2-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Display Cabang Penjualan</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-success">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-medium-emphasis font-weight-medium">TOTAL KESELURUHAN</div>
              <div class="text-h5 font-weight-bold text-success mt-1">{{ summary.total_overall.toLocaleString('id-ID') }} <span class="text-caption text-medium-emphasis">Pcs</span></div>
            </div>
            <VAvatar color="success" variant="tonal" size="42">
              <VIcon icon="ri-stack-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Stok Keseluruhan Jaringan</div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Table Card -->
    <VCard elevation="2">
      <VCardText class="d-flex flex-wrap py-4 gap-4 align-center">
        <VAutocomplete
          v-model="selectedCategory"
          :items="categories"
          item-title="name"
          item-value="id"
          placeholder="Semua Kategori"
          label="Kategori"
          density="compact"
          style="max-width: 220px;"
          clearable
          hide-details
          @update:model-value="onFilterChange"
        />

        <VTextField
          v-model="search"
          placeholder="Cari Produk / SKU / Merk..."
          prepend-inner-icon="ri-search-line"
          density="compact"
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
          @click="fetchGlobalStock"
        >
          Muat Ulang
        </VBtn>
      </VCardText>

      <VDivider />

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="tableHeaders"
        :items="products"
        :items-length="totalItems"
        :loading="isLoading"
        expand-on-click
        class="text-no-wrap"
        hover
        @update:options="fetchGlobalStock"
      >
        <template #item.name="{ item }">
          <div>
            <div class="font-weight-medium text-subtitle-2">{{ item.name }}</div>
            <div class="text-caption text-disabled">
              <code>{{ item.sku }}</code>
              <span v-if="item.brand && item.brand !== '-'"> &bull; {{ item.brand }}</span>
            </div>
          </div>
        </template>
        
        <template #item.total_warehouse_stock="{ item }">
          <VChip
            :color="item.total_warehouse_stock > 0 ? 'warning' : 'secondary'"
            size="small"
            variant="tonal"
            class="font-weight-bold"
          >
            <VIcon icon="ri-building-2-line" size="14" class="me-1" />
            {{ item.total_warehouse_stock }}
          </VChip>
        </template>

        <template #item.total_store_stock="{ item }">
          <VChip
            :color="item.total_store_stock > 0 ? 'info' : 'secondary'"
            size="small"
            variant="tonal"
            class="font-weight-bold"
          >
            <VIcon icon="ri-store-2-line" size="14" class="me-1" />
            {{ item.total_store_stock }}
          </VChip>
        </template>

        <template #item.total_overall="{ item }">
          <VChip
            :color="item.total_overall > 0 ? 'success' : 'error'"
            size="small"
            variant="elevated"
            class="font-weight-bold"
          >
            {{ item.total_overall }} unit
          </VChip>
        </template>

        <!-- Expanded Row for Branch Details -->
        <template #expanded-row="{ item }">
          <tr>
            <td :colspan="tableHeaders.length" class="pa-4 bg-grey-50">
              <VCard variant="outlined" class="mx-auto border bg-white">
                <VCardTitle class="text-subtitle-2 py-2 px-4 bg-primary text-white d-flex align-center gap-2">
                  <VIcon icon="ri-map-pin-2-line" size="16" />
                  <span>Rincian Stok Tiap Cabang: {{ item.name }} ({{ item.sku }})</span>
                </VCardTitle>
                <VTable density="compact" class="text-no-wrap">
                  <thead>
                    <tr class="bg-grey-100">
                      <th class="text-left font-weight-bold">NAMA CABANG / GUDANG</th>
                      <th class="text-center font-weight-bold">TIPE UNIT</th>
                      <th class="text-center font-weight-bold">SISA STOK FISIK</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="!item.branches || item.branches.length === 0">
                      <td colspan="3" class="text-center text-medium-emphasis py-3">Tidak ada stok di cabang manapun.</td>
                    </tr>
                    <tr v-for="(branch, index) in item.branches" :key="index">
                      <td class="font-weight-medium">{{ branch.branch_name }}</td>
                      <td class="text-center">
                        <VChip
                          :color="branch.branch_type === 'warehouse' ? 'warning' : 'info'"
                          size="x-small"
                          variant="tonal"
                        >
                          {{ branch.branch_type === 'warehouse' ? 'Gudang Pusat' : 'Toko / Cabang' }}
                        </VChip>
                      </td>
                      <td class="text-center font-weight-bold" :class="branch.stock > 0 ? 'text-primary' : 'text-disabled'">
                        {{ branch.stock }} pcs
                      </td>
                    </tr>
                  </tbody>
                </VTable>
              </VCard>
            </td>
          </tr>
        </template>

        <template #no-data>
          <div class="pa-4 text-center text-medium-emphasis">
            Tidak ada produk yang ditemukan.
          </div>
        </template>
      </VDataTableServer>
    </VCard>
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Dashboard Analytics
</route>
