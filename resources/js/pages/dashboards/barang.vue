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
const options = ref({ page: 1, itemsPerPage: 10 })
const totalMovements = ref(0)

const fetchAnalytics = async () => {
  isLoading.value = true
  try {
    const res = await $api('/apps/dashboards/inventory', {
      query: {
        page: options.value.page,
        limit: options.value.itemsPerPage,
      },
    })

    if (res.success) {
      analyticsData.value.low_stock = res.data.low_stock
      analyticsData.value.out_of_stock = res.data.out_of_stock
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
  return dayjs(date).format('DD MMM YYYY HH:mm')
}

const headers = [
  { title: 'TANGGAL', key: 'created_at' },
  { title: 'PRODUK', key: 'product_name' },
  { title: 'CABANG', key: 'branch_name' },
  { title: 'TIPE', key: 'type', align: 'center' },
  { title: 'KUANTITAS', key: 'quantity', align: 'center' },
  { title: 'CATATAN', key: 'notes' },
]
</script>

<template>
  <VRow>
    <VCol
      cols="12"
      md="6"
    >
      <VCard
        title="⚠️ Peringatan Stok Menipis"
        subtitle="Produk yang sisa stoknya di bawah 10 unit"
      >
        <VTable class="text-no-wrap">
          <thead>
            <tr>
              <th class="text-uppercase">
                Produk
              </th>
              <th class="text-uppercase text-center">
                Cabang
              </th>
              <th class="text-uppercase text-center">
                Stok Sisa
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="analyticsData.low_stock.length === 0">
              <td
                colspan="3"
                class="text-center text-medium-emphasis"
              >
                Stok produk aman.
              </td>
            </tr>
            <tr
              v-for="item in analyticsData.low_stock"
              :key="item.id"
            >
              <td>
                <div class="d-flex align-center">
                  <VAvatar
                    variant="tonal"
                    color="warning"
                    class="me-3"
                    size="34"
                  >
                    <VIcon
                      icon="ri-box-3-line"
                      size="20"
                    />
                  </VAvatar>
                  <div class="d-flex flex-column">
                    <h6 class="text-h6 font-weight-medium">
                      {{ item.product?.name }}
                    </h6>
                  </div>
                </div>
              </td>
              <td class="text-center">
                {{ item.branch?.name }}
              </td>
              <td class="text-center font-weight-bold text-warning">
                {{ item.stock }}
              </td>
            </tr>
          </tbody>
        </VTable>
        <VDivider />
        <VCardActions>
          <VBtn block variant="text" to="/inventori-cabang" color="primary">Lihat Semua Data Stok</VBtn>
        </VCardActions>
      </VCard>
    </VCol>

    <VCol
      cols="12"
      md="6"
    >
      <VCard
        title="🚫 Stok Habis (Out of Stock)"
        subtitle="Produk yang stoknya 0"
      >
        <VTable class="text-no-wrap">
          <thead>
            <tr>
              <th class="text-uppercase">
                Produk
              </th>
              <th class="text-uppercase text-center">
                Cabang
              </th>
              <th class="text-uppercase text-center">
                Stok
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="analyticsData.out_of_stock.length === 0">
              <td
                colspan="3"
                class="text-center text-medium-emphasis"
              >
                Tidak ada produk habis.
              </td>
            </tr>
            <tr
              v-for="item in analyticsData.out_of_stock"
              :key="item.id"
            >
              <td>
                <div class="d-flex align-center">
                  <VAvatar
                    variant="tonal"
                    color="error"
                    class="me-3"
                    size="34"
                  >
                    <VIcon
                      icon="ri-close-circle-line"
                      size="20"
                    />
                  </VAvatar>
                  <div class="d-flex flex-column">
                    <h6 class="text-h6 font-weight-medium">
                      {{ item.product?.name }}
                    </h6>
                  </div>
                </div>
              </td>
              <td class="text-center">
                {{ item.branch?.name }}
              </td>
              <td class="text-center font-weight-bold text-error">
                {{ item.stock }}
              </td>
            </tr>
          </tbody>
        </VTable>
        <VDivider />
        <VCardActions>
          <VBtn block variant="text" to="/inventori-cabang" color="primary">Lihat Semua Data Stok</VBtn>
        </VCardActions>
      </VCard>
    </VCol>

    <VCol cols="12">
      <VCard
        title="🔄 Mutasi Stok Terakhir"
        subtitle="Aktivitas barang masuk / keluar terbaru"
      >
        <VDataTableServer
          v-model:options="options"
          :headers="headers"
          :items="analyticsData.recent_movements"
          :items-length="totalMovements"
          :loading="isLoading"
          class="text-no-wrap"
          @update:options="fetchAnalytics"
        >
          <template #item.created_at="{ item }">
            {{ formatDate(item.created_at) }}
          </template>
          
          <template #item.product_name="{ item }">
            {{ item.productBranch?.product?.name || item.product_name || '-' }}
          </template>
          
          <template #item.type="{ item }">
            <VChip
              :color="item.type === 'in' ? 'success' : 'error'"
              size="small"
            >
              {{ item.type === 'in' ? 'Masuk' : 'Keluar' }}
            </VChip>
          </template>
          
          <template #item.quantity="{ item }">
            <span
              class="font-weight-bold"
              :class="item.type === 'in' ? 'text-success' : 'text-error'"
            >
              {{ item.type === 'in' ? '+' : '-' }}{{ Math.abs(item.quantity) }}
            </span>
          </template>
          
          <template #item.notes="{ item }">
            {{ item.notes || '-' }}
          </template>
          
          <template #no-data>
            <div class="text-center text-medium-emphasis my-4">
              Belum ada mutasi stok.
            </div>
          </template>
        </VDataTableServer>
      </VCard>
    </VCol>
  </VRow>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Dashboard Barang
</route>
