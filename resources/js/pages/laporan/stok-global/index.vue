<script setup>
import { ref, onMounted, computed } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'

const products = ref([])
const search = ref('')
const isLoading = ref(false)
const snackbar = useSnackbarStore()

// Pagination
const page = ref(1)
const itemsPerPage = ref(15)
const totalItems = ref(0)
let searchTimeout = null

const fetchGlobalStock = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
    }
    
    if (search.value) {
      params.search = search.value
    }
    
    const res = await $api('/apps/reports/global-stock', { query: params })
    if (res.success) {
      products.value = res.data
      if (res.total !== undefined) {
        totalItems.value = res.total
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
  }, 500)
}

onMounted(() => {
  fetchGlobalStock()
})

const tableHeaders = [
  { title: 'PRODUK', key: 'name' },
  { title: 'KATEGORI', key: 'category_name' },
  { title: 'SKU', key: 'sku' },
  { title: 'STOK GUDANG', key: 'total_warehouse_stock', align: 'center' },
  { title: 'STOK TOKO', key: 'total_store_stock', align: 'center' },
  { title: 'TOTAL STOK KESELURUHAN', key: 'total_overall', align: 'center' },
  { title: 'RINCIAN', key: 'data-table-expand', align: 'center' },
]


</script>

<template>
  <section>
    <VCard>
      <VCardText class="d-flex flex-wrap py-4 gap-4 align-center">
        <div>
          <h4 class="text-h4 mb-1">
            Laporan Stok Global
          </h4>
          <p class="text-body-1 text-disabled mb-0">
            Pantau total stok barang secara terpusat dari semua gudang dan toko.
          </p>
        </div>
        
        <VSpacer />

        <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
          <div style="inline-size: 15.625rem;">
            <VTextField
              v-model="search"
              placeholder="Cari nama produk atau SKU..."
              density="compact"
              variant="outlined"
              @update:model-value="handleSearch"
            />
          </div>
          <VBtn
            prepend-icon="ri-refresh-line"
            variant="tonal"
            @click="fetchGlobalStock"
            :loading="isLoading"
          >
            Refresh
          </VBtn>
        </div>
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
        @update:options="fetchGlobalStock"
      >
        <template #item.name="{ item }">
          <span class="font-weight-medium text-high-emphasis">{{ item.name }}</span>
        </template>
        
        <template #item.total_warehouse_stock="{ item }">
          <VChip color="warning" size="small" class="font-weight-bold">
            {{ item.total_warehouse_stock }}
          </VChip>
        </template>

        <template #item.total_store_stock="{ item }">
          <VChip color="primary" size="small" class="font-weight-bold">
            {{ item.total_store_stock }}
          </VChip>
        </template>

        <template #item.total_overall="{ item }">
          <span class="text-h6 font-weight-bold" :class="item.total_overall > 0 ? 'text-success' : 'text-error'">
            {{ item.total_overall }}
          </span>
        </template>

        <!-- Expanded Row for Branch Details -->
        <template #expanded-row="{ item }">
          <tr>
            <td :colspan="tableHeaders.length" class="pa-4 bg-var-theme-background">
              <VCard variant="outlined" class="mx-auto border">
                <VCardTitle class="text-subtitle-1 py-3 px-4 bg-light-primary">
                  Rincian Lokasi Stok: {{ item.name }}
                </VCardTitle>
                <VDivider />
                <VTable density="compact" class="text-no-wrap">
                  <thead>
                    <tr>
                      <th class="text-left font-weight-bold">NAMA CABANG</th>
                      <th class="text-center font-weight-bold">TIPE</th>
                      <th class="text-center font-weight-bold">JUMLAH STOK</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="!item.branches || item.branches.length === 0">
                      <td colspan="3" class="text-center text-disabled">Tidak ada stok di cabang manapun.</td>
                    </tr>
                    <tr v-for="(branch, index) in item.branches" :key="index">
                      <td>{{ branch.branch_name }}</td>
                      <td class="text-center">
                        <VChip :color="branch.branch_type === 'warehouse' ? 'warning' : 'primary'" size="x-small">
                          {{ branch.branch_type === 'warehouse' ? 'Gudang' : 'Toko' }}
                        </VChip>
                      </td>
                      <td class="text-center font-weight-bold">{{ branch.stock }}</td>
                    </tr>
                  </tbody>
                </VTable>
              </VCard>
            </td>
          </tr>
        </template>
      </VDataTableServer>
    </VCard>
  </section>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Dashboard Analytics
</route>
