<script setup>
import { ref, onMounted } from 'vue'
import { $api } from '@/utils/api'
import dayjs from 'dayjs'

const analyticsData = ref({
  low_stock: [],
  out_of_stock: [],
  recent_movements: [],
})

const isLoading = ref(true)
const page = ref(1)
const itemsPerPage = ref(10)
const totalMovements = ref(0)

const fetchAnalytics = async () => {
  isLoading.value = true
  try {
    const res = await $api('/apps/dashboards/inventory', {
      query: {
        page: page.value,
        limit: itemsPerPage.value,
      },
    })

    if (res.success) {
      analyticsData.value.low_stock = res.data.low_stock || []
      analyticsData.value.out_of_stock = res.data.out_of_stock || []
      analyticsData.value.recent_movements = res.data.recent_movements.data || []
      totalMovements.value = res.data.recent_movements.total || 0
    }
  } catch (error) {
    console.error('Error fetching inventory analytics:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchAnalytics()
})

const formatDate = date => {
  return dayjs(date).format('DD MMM YYYY, HH:mm')
}

const headers = [
  { title: 'WAKTU & TANGGAL', key: 'created_at' },
  { title: 'PRODUK / SKU', key: 'product_name' },
  { title: 'LOKASI CABANG', key: 'branch_name' },
  { title: 'TIPE MUTASI', key: 'type', align: 'center' },
  { title: 'KUANTITAS', key: 'quantity', align: 'center' },
  { title: 'KETERANGAN', key: 'notes' },
]
</script>

<template>
  <div class="pa-4">
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="text-h4 font-weight-bold mb-1">
          Dashboard Inventori & Logistik
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Monitoring stok kritis, ketersediaan produk di rak, dan rekaman pergerakan mutasi fisik.
        </p>
      </div>

      <div class="d-flex gap-3">
        <VBtn
          color="secondary"
          variant="tonal"
          prepend-icon="ri-refresh-line"
          :loading="isLoading"
          @click="fetchAnalytics"
        >
          Refresh
        </VBtn>
      </div>
    </div>

    <!-- KPI Summary Row -->
    <VRow class="mb-4">
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-warning">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-warning font-weight-bold">STOK MENIPIS (&le; 10)</div>
              <div class="text-h4 font-weight-bold text-warning mt-1">{{ analyticsData.low_stock.length }} <span class="text-caption text-medium-emphasis">Item</span></div>
            </div>
            <VAvatar color="warning" variant="tonal" size="44">
              <VIcon icon="ri-alarm-warning-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Segera buat Purchase Order</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-error">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-error font-weight-bold">STOK HABIS (0)</div>
              <div class="text-h4 font-weight-bold text-error mt-1">{{ analyticsData.out_of_stock.length }} <span class="text-caption text-medium-emphasis">Item</span></div>
            </div>
            <VAvatar color="error" variant="tonal" size="44">
              <VIcon icon="ri-close-circle-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Ketersediaan rak kosong</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-info">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-info font-weight-bold">TOTAL LOG MUTASI</div>
              <div class="text-h4 font-weight-bold text-info mt-1">{{ totalMovements.toLocaleString('id-ID') }} <span class="text-caption text-medium-emphasis">Log</span></div>
            </div>
            <VAvatar color="info" variant="tonal" size="44">
              <VIcon icon="ri-history-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Jejak pergerakan stok</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-success">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-success font-weight-bold">KONTROL GUDANG</div>
              <div class="text-h5 font-weight-bold text-success mt-1">Stok Terpusat</div>
            </div>
            <VAvatar color="success" variant="tonal" size="44">
              <VIcon icon="ri-building-2-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Sinkronisasi multi cabang</div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Top Alerts Row: Low Stock & Out of Stock -->
    <VRow class="mb-4">
      <!-- Low Stock Column -->
      <VCol cols="12" md="6">
        <VCard elevation="2" class="h-100 d-flex flex-column" :loading="isLoading">
          <VCardItem class="pb-2">
            <template #prepend>
              <VAvatar color="warning" variant="tonal" size="36" class="me-2">
                <VIcon icon="ri-alarm-warning-line" size="20" />
              </VAvatar>
            </template>
            <VCardTitle class="text-h6 font-weight-bold">Peringatan Stok Menipis</VCardTitle>
            <VCardSubtitle>Produk yang sisa stoknya di bawah 10 unit</VCardSubtitle>
          </VCardItem>
          <VDivider />
          <VProgressLinear v-if="isLoading" indeterminate color="warning" height="2" />

          <VTable class="text-no-wrap" hover density="comfortable">
            <thead>
              <tr class="bg-grey-50">
                <th class="text-left font-weight-bold">PRODUK</th>
                <th class="text-center font-weight-bold">CABANG</th>
                <th class="text-center font-weight-bold">SISA STOK</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="3" class="text-center text-medium-emphasis py-6">
                  <VProgressCircular indeterminate color="warning" size="24" class="me-2" />
                  <span>Memuat peringatan stok...</span>
                </td>
              </tr>
              <tr v-else-if="analyticsData.low_stock.length === 0">
                <td colspan="3" class="text-center text-medium-emphasis py-6">
                  <VIcon icon="ri-checkbox-circle-line" color="success" size="20" class="me-1" />
                  Semua stok produk dalam kondisi aman.
                </td>
              </tr>
              <tr v-for="item in analyticsData.low_stock" :key="item.id">
                <td>
                  <div class="font-weight-medium text-subtitle-2">{{ item.product?.name || '-' }}</div>
                  <div class="text-caption text-disabled"><code>{{ item.product?.sku || '-' }}</code></div>
                </td>
                <td class="text-center">
                  <VChip size="x-small" variant="tonal" color="secondary">{{ item.branch?.name || '-' }}</VChip>
                </td>
                <td class="text-center">
                  <VChip size="small" variant="elevated" color="warning" class="font-weight-bold">
                    {{ item.stock }} unit
                  </VChip>
                </td>
              </tr>
            </tbody>
          </VTable>

          <VSpacer />
          <VDivider />
          <VCardActions class="pa-2">
            <VBtn block variant="text" to="/inventori-cabang" color="warning" append-icon="ri-arrow-right-line">
              Kelola Stok Cabang
            </VBtn>
          </VCardActions>
        </VCard>
      </VCol>

      <!-- Out of Stock Column -->
      <VCol cols="12" md="6">
        <VCard elevation="2" class="h-100 d-flex flex-column" :loading="isLoading">
          <VCardItem class="pb-2">
            <template #prepend>
              <VAvatar color="error" variant="tonal" size="36" class="me-2">
                <VIcon icon="ri-close-circle-line" size="20" />
              </VAvatar>
            </template>
            <VCardTitle class="text-h6 font-weight-bold">Stok Habis (Out of Stock)</VCardTitle>
            <VCardSubtitle>Produk yang saat ini berjumlah 0 di rak</VCardSubtitle>
          </VCardItem>
          <VDivider />
          <VProgressLinear v-if="isLoading" indeterminate color="error" height="2" />

          <VTable class="text-no-wrap" hover density="comfortable">
            <thead>
              <tr class="bg-grey-50">
                <th class="text-left font-weight-bold">PRODUK</th>
                <th class="text-center font-weight-bold">CABANG</th>
                <th class="text-center font-weight-bold">STATUS</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="3" class="text-center text-medium-emphasis py-6">
                  <VProgressCircular indeterminate color="error" size="24" class="me-2" />
                  <span>Memuat produk habis...</span>
                </td>
              </tr>
              <tr v-else-if="analyticsData.out_of_stock.length === 0">
                <td colspan="3" class="text-center text-medium-emphasis py-6">
                  <VIcon icon="ri-checkbox-circle-line" color="success" size="20" class="me-1" />
                  Tidak ada produk yang kehabisan stok.
                </td>
              </tr>
              <tr v-for="item in analyticsData.out_of_stock" :key="item.id">
                <td>
                  <div class="font-weight-medium text-subtitle-2">{{ item.product?.name || '-' }}</div>
                  <div class="text-caption text-disabled"><code>{{ item.product?.sku || '-' }}</code></div>
                </td>
                <td class="text-center">
                  <VChip size="x-small" variant="tonal" color="secondary">{{ item.branch?.name || '-' }}</VChip>
                </td>
                <td class="text-center">
                  <VChip size="small" variant="elevated" color="error" class="font-weight-bold">
                    Kosong (0)
                  </VChip>
                </td>
              </tr>
            </tbody>
          </VTable>

          <VSpacer />
          <VDivider />
          <VCardActions class="pa-2">
            <VBtn block variant="text" to="/apps/purchases" color="error" append-icon="ri-shopping-cart-line">
              Buat Purchase Order (PO)
            </VBtn>
          </VCardActions>
        </VCard>
      </VCol>
    </VRow>

    <!-- Recent Stock Movements Table -->
    <VCard elevation="2" :loading="isLoading">
      <VCardItem class="pb-2">
        <template #prepend>
          <VAvatar color="primary" variant="tonal" size="36" class="me-2">
            <VIcon icon="ri-history-line" size="20" />
          </VAvatar>
        </template>
        <VCardTitle class="text-h6 font-weight-bold">Aktivitas Mutasi Fisik Terbaru</VCardTitle>
        <VCardSubtitle>Log transaksi masuk, keluar, penjualan, retur, dan penyesuaian opname</VCardSubtitle>
      </VCardItem>
      <VDivider />
      <VProgressLinear v-if="isLoading" indeterminate color="primary" height="2" />

      <VTable class="text-no-wrap" hover density="comfortable">
        <thead>
          <tr class="bg-grey-50">
            <th class="text-left font-weight-bold">WAKTU & TANGGAL</th>
            <th class="text-left font-weight-bold">PRODUK</th>
            <th class="text-left font-weight-bold">CABANG</th>
            <th class="text-center font-weight-bold">TIPE MUTASI</th>
            <th class="text-center font-weight-bold">KUANTITAS</th>
            <th class="text-left font-weight-bold">CATATAN</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="isLoading">
            <td colspan="6" class="text-center text-medium-emphasis py-6">
              <VProgressCircular indeterminate color="primary" size="24" class="me-2" />
              <span>Memuat riwayat mutasi stok...</span>
            </td>
          </tr>
          <tr v-else-if="analyticsData.recent_movements.length === 0">
            <td colspan="6" class="text-center text-medium-emphasis py-6">
              Belum ada riwayat mutasi stok.
            </td>
          </tr>
          <tr v-for="item in analyticsData.recent_movements" :key="item.id">
            <td class="text-caption text-medium-emphasis">{{ formatDate(item.created_at) }}</td>
            <td class="font-weight-medium text-subtitle-2">{{ item.product_name || '-' }}</td>
            <td>
              <VChip size="x-small" variant="tonal" color="secondary">{{ item.branch_name || '-' }}</VChip>
            </td>
            <td class="text-center">
              <VChip
                :color="item.type === 'in' ? 'success' : 'error'"
                size="small"
                variant="tonal"
                class="font-weight-bold"
              >
                <VIcon :icon="item.type === 'in' ? 'ri-arrow-down-line' : 'ri-arrow-up-line'" size="14" class="me-1" />
                {{ item.type === 'in' ? 'Masuk' : 'Keluar' }}
              </VChip>
            </td>
            <td class="text-center font-weight-bold" :class="item.type === 'in' ? 'text-success' : 'text-error'">
              {{ item.type === 'in' ? '+' : '-' }}{{ item.quantity }}
            </td>
            <td class="text-caption text-medium-emphasis">{{ item.notes || '-' }}</td>
          </tr>
        </tbody>
      </VTable>
    </VCard>
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Dashboard Analytics
</route>
