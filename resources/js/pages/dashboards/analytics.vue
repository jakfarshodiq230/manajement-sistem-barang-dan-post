<script setup>
import { ref, onMounted, computed } from 'vue'
import { $api } from '@/utils/api'
import VueApexCharts from 'vue3-apexcharts'
import { useTheme } from 'vuetify'

const vuetifyTheme = useTheme()

// Components
import AnalyticsCongratulationsJohn from '@/views/dashboards/analytics/AnalyticsCongratulationsJohn.vue'

const analyticsData = ref({
  low_stock: [],
  high_stock: [],
  income: {
    daily: 0,
    monthly: 0,
    yearly: 0,
  },
  purchases: {
    monthly: 0,
    pending_count: 0,
  },
  discounts: {
    monthly: 0,
  },
  receivables: {
    outstanding: 0,
  },
  returns: {
    monthly: 0,
  },
  expiring_batches: [],
  dead_stock: [],
  new_stock: [],
  latest_opname: null,
  recent_sales: [],
  chart: {
    monthly_income: [],
  },
})

const isLoading = ref(true)

const fetchAnalytics = async () => {
  isLoading.value = true
  try {
    const res = await $api('/apps/dashboards/analytics')
    if (res.success) {
      analyticsData.value = res.data
    }
  } catch (error) {
    console.error('Error fetching analytics:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchAnalytics()
})

const formatCurrency = value => {
  if (value === null || value === undefined || isNaN(value)) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value)
}

const chartOptions = computed(() => {
  const currentTheme = vuetifyTheme.current.value.colors

  return {
    chart: {
      type: 'area',
      parentHeightOffset: 0,
      toolbar: { show: false },
    },
    dataLabels: { enabled: false },
    stroke: {
      curve: 'smooth',
      width: 2.5,
    },
    colors: [currentTheme.primary],
    fill: {
      type: 'gradient',
      gradient: {
        shadeIntensity: 0.8,
        opacityFrom: 0.6,
        opacityTo: 0.1,
        stops: [0, 90, 100],
      },
    },
    xaxis: {
      categories: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
      labels: {
        style: {
          colors: currentTheme['on-surface'],
          fontSize: '12px',
        },
      },
      axisBorder: { show: false },
      axisTicks: { show: false },
    },
    yaxis: {
      labels: {
        formatter: val => {
          if (val >= 1000000000) return 'Rp ' + (val / 1000000000).toFixed(1) + ' M'
          if (val >= 1000000) return 'Rp ' + (val / 1000000).toFixed(1) + ' jt'
          if (val >= 1000) return 'Rp ' + (val / 1000).toFixed(0) + ' rb'
          return 'Rp ' + val
        },
        style: {
          colors: currentTheme['on-surface'],
          fontSize: '12px',
        },
      },
    },
    tooltip: {
      y: {
        formatter: val => formatCurrency(val),
      },
    },
    grid: {
      borderColor: 'rgba(var(--v-border-color), 0.12)',
      strokeDashArray: 4,
    },
  }
})

const chartSeries = computed(() => {
  return [
    {
      name: 'Omzet Penjualan',
      data: analyticsData.value.chart?.monthly_income?.length === 12 
        ? analyticsData.value.chart.monthly_income 
        : [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    },
  ]
})
</script>

<template>
  <div class="pa-4">
    <!-- Top Welcome & Daily Metrics Row -->
    <VRow class="match-height mb-4">
      <!-- Welcome Hero Banner -->
      <VCol cols="12" md="8">
        <AnalyticsCongratulationsJohn
          :daily-income="analyticsData.income.daily"
          :monthly-income="analyticsData.income.monthly"
        />
      </VCol>

      <!-- Side Daily & Monthly Income Cards -->
      <VCol cols="12" md="4" class="d-flex flex-column gap-4">
        <VCard elevation="2" class="border-s-lg border-primary flex-grow-1">
          <VCardText class="d-flex align-center justify-space-between py-4">
            <div>
              <div class="text-caption text-primary font-weight-bold">
                PENDAPATAN HARI INI
              </div>
              <div class="text-h4 font-weight-bold text-primary mt-1">
                {{ formatCurrency(analyticsData.income.daily) }}
              </div>
              <div class="text-caption text-medium-emphasis mt-1">Total transaksi kasir hari ini</div>
            </div>
            <VAvatar color="primary" variant="tonal" rounded size="48">
              <VIcon icon="ri-money-dollar-circle-line" size="26" />
            </VAvatar>
          </VCardText>
        </VCard>

        <VCard elevation="2" class="border-s-lg border-success flex-grow-1">
          <VCardText class="d-flex align-center justify-space-between py-4">
            <div>
              <div class="text-caption text-success font-weight-bold">
                PENDAPATAN BULAN INI
              </div>
              <div class="text-h4 font-weight-bold text-success mt-1">
                {{ formatCurrency(analyticsData.income.monthly) }}
              </div>
              <div class="text-caption text-medium-emphasis mt-1">Akumulasi omzet bulan berjalan</div>
            </div>
            <VAvatar color="success" variant="tonal" rounded size="48">
              <VIcon icon="ri-wallet-3-line" size="26" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Row of 6 Key Business Stats -->
    <VRow class="mb-4">
      <!-- 1. Pendapatan Tahun Ini -->
      <VCol cols="12" sm="6" md="4" lg="2">
        <VCard elevation="2" class="pa-4 border-s-lg border-info h-100">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-info font-weight-bold">OMZET TAHUN INI</div>
              <div class="text-h6 font-weight-bold text-info mt-1">{{ formatCurrency(analyticsData.income.yearly) }}</div>
            </div>
            <VAvatar color="info" variant="tonal" rounded size="40">
              <VIcon icon="ri-bank-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Tahun berjalan</div>
        </VCard>
      </VCol>

      <!-- 2. Pengadaan PO Bulan Ini -->
      <VCol cols="12" sm="6" md="4" lg="2">
        <VCard elevation="2" class="pa-4 border-s-lg border-primary h-100" to="/purchase-orders">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-primary font-weight-bold">PENGADAAN (PO)</div>
              <div class="text-h6 font-weight-bold text-primary mt-1">{{ formatCurrency(analyticsData.purchases?.monthly || 0) }}</div>
            </div>
            <VAvatar color="primary" variant="tonal" rounded size="40">
              <VIcon icon="ri-truck-line" size="22" />
            </VAvatar>
          </div>
          <div class="d-flex align-center gap-1 mt-2">
            <VChip v-if="analyticsData.purchases?.pending_count > 0" size="x-small" color="warning" variant="elevated">
              {{ analyticsData.purchases.pending_count }} PO Pending
            </VChip>
            <span v-else class="text-caption text-medium-emphasis">Bulan berjalan</span>
          </div>
        </VCard>
      </VCol>

      <!-- 3. Total Diskon Kasir -->
      <VCol cols="12" sm="6" md="4" lg="2">
        <VCard elevation="2" class="pa-4 border-s-lg border-success h-100">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-success font-weight-bold">DISKON KASIR</div>
              <div class="text-h6 font-weight-bold text-success mt-1">{{ formatCurrency(analyticsData.discounts?.monthly || 0) }}</div>
            </div>
            <VAvatar color="success" variant="tonal" rounded size="40">
              <VIcon icon="ri-discount-percent-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Total diskon bon</div>
        </VCard>
      </VCol>

      <!-- 4. Total Piutang Aktif -->
      <VCol cols="12" sm="6" md="4" lg="2">
        <VCard elevation="2" class="pa-4 border-s-lg border-warning h-100" to="/receivables">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-warning font-weight-bold">PIUTANG AKTIF</div>
              <div class="text-h6 font-weight-bold text-warning mt-1">{{ formatCurrency(analyticsData.receivables.outstanding) }}</div>
            </div>
            <VAvatar color="warning" variant="tonal" rounded size="40">
              <VIcon icon="ri-hand-coin-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Sisa tagihan tempo</div>
        </VCard>
      </VCol>

      <!-- 5. Retur Bulan Ini -->
      <VCol cols="12" sm="6" md="4" lg="2">
        <VCard elevation="2" class="pa-4 border-s-lg border-error h-100" to="/retur">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-error font-weight-bold">RETUR BULAN INI</div>
              <div class="text-h6 font-weight-bold text-error mt-1">{{ formatCurrency(analyticsData.returns.monthly) }}</div>
            </div>
            <VAvatar color="error" variant="tonal" rounded size="40">
              <VIcon icon="ri-arrow-go-back-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Retur barang</div>
        </VCard>
      </VCol>

      <!-- 6. Selisih Opname Terakhir -->
      <VCol cols="12" sm="6" md="4" lg="2">
        <VCard elevation="2" class="pa-4 border-s-lg border-secondary h-100" to="/audit/stock-opname">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-secondary font-weight-bold">SELISIH OPNAME</div>
              <div class="text-h6 font-weight-bold text-secondary mt-1">
                {{ analyticsData.latest_opname ? analyticsData.latest_opname.total_discrepancy : '0' }} <span class="text-caption">Pcs</span>
              </div>
            </div>
            <VAvatar color="secondary" variant="tonal" rounded size="40">
              <VIcon icon="ri-survey-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Audit terakhir</div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Quick Shortcuts Navigation -->
    <VCard elevation="2" class="mb-4 pa-4 rounded-xl bg-var-theme-background border">
      <div class="d-flex align-center justify-space-between flex-wrap gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="ri-flashlight-line" color="primary" size="22" />
          <span class="font-weight-bold text-subtitle-2">Akses Cepat Modul Utama:</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <VBtn size="small" variant="elevated" color="primary" prepend-icon="ri-shopping-cart-2-line" to="/pos">
            Kasir POS
          </VBtn>
          <VBtn size="small" variant="tonal" color="info" prepend-icon="ri-truck-line" to="/purchase-orders">
            Purchase Order
          </VBtn>
          <VBtn size="small" variant="tonal" color="success" prepend-icon="ri-store-2-line" to="/inventori-cabang">
            Inventori Cabang
          </VBtn>
          <VBtn size="small" variant="tonal" color="warning" prepend-icon="ri-archive-stack-line" to="/audit/stock-opname">
            Stock Opname
          </VBtn>
          <VBtn size="small" variant="tonal" color="secondary" prepend-icon="ri-line-chart-line" to="/dashboards/keuntungan">
            Laba Rugi
          </VBtn>
          <VBtn size="small" variant="outlined" color="primary" prepend-icon="ri-book-read-line" to="/panduan-sistem">
            Panduan Sistem
          </VBtn>
        </div>
      </div>
    </VCard>

    <!-- 12-Month Sales Income Trend Chart -->
    <VCard elevation="2" class="mb-4">
      <VCardItem class="pb-0">
        <div class="d-flex align-center justify-space-between flex-wrap gap-2">
          <div>
            <VCardTitle class="text-h6 font-weight-bold">Tren Omzet Penjualan Bulanan (12 Bulan)</VCardTitle>
            <VCardSubtitle>Performa pendapatan kasir tahun berjalan</VCardSubtitle>
          </div>
          <VBtn size="small" variant="text" color="primary" to="/laporan" append-icon="ri-arrow-right-line">
            Buka Laporan Lengkap
          </VBtn>
        </div>
      </VCardItem>
      <VCardText class="pt-2">
        <VueApexCharts
          type="area"
          height="260"
          :options="chartOptions"
          :series="chartSeries"
        />
      </VCardText>
    </VCard>

    <!-- Row: Low Stock & Expiring Batches -->
    <VRow class="mb-4">
      <!-- Low Stock Table -->
      <VCol cols="12" md="6">
        <VCard elevation="2" class="h-100 d-flex flex-column" :loading="isLoading">
          <VCardItem class="pb-2">
            <template #prepend>
              <VAvatar color="warning" variant="tonal" size="36" class="me-2">
                <VIcon icon="ri-alarm-warning-line" size="20" />
              </VAvatar>
            </template>
            <VCardTitle class="text-h6 font-weight-bold">Peringatan Stok Menipis</VCardTitle>
            <VCardSubtitle>Produk yang sisa stoknya kritis (&le; 10 unit)</VCardSubtitle>
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
                  <span>Memuat data stok menipis...</span>
                </td>
              </tr>
              <tr v-else-if="analyticsData.low_stock.length === 0">
                <td colspan="3" class="text-center text-medium-emphasis py-6">
                  <VIcon icon="ri-checkbox-circle-line" color="success" size="18" class="me-1" />
                  Semua stok produk aman.
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
                  <VChip size="small" variant="elevated" color="error" class="font-weight-bold">
                    {{ item.stock }} unit
                  </VChip>
                </td>
              </tr>
            </tbody>
          </VTable>

          <VSpacer />
          <VDivider />
          <VCardActions class="pa-2">
            <VBtn block variant="text" to="/inventori-cabang" color="primary" append-icon="ri-arrow-right-line">
              Lihat Semua Data Stok
            </VBtn>
          </VCardActions>
        </VCard>
      </VCol>

      <!-- Expiring Batches (FEFO) Table -->
      <VCol cols="12" md="6">
        <VCard elevation="2" class="h-100 d-flex flex-column" :loading="isLoading">
          <VCardItem class="pb-2">
            <template #prepend>
              <VAvatar color="error" variant="tonal" size="36" class="me-2">
                <VIcon icon="ri-time-line" size="20" />
              </VAvatar>
            </template>
            <VCardTitle class="text-h6 font-weight-bold">Barang Segera Kedaluwarsa (FEFO)</VCardTitle>
            <VCardSubtitle>Batch produk yang akan expired dalam 30 hari</VCardSubtitle>
          </VCardItem>
          <VDivider />
          <VProgressLinear v-if="isLoading" indeterminate color="error" height="2" />

          <VTable class="text-no-wrap" hover density="comfortable">
            <thead>
              <tr class="bg-grey-50">
                <th class="text-left font-weight-bold">PRODUK</th>
                <th class="text-center font-weight-bold">CABANG</th>
                <th class="text-center font-weight-bold">SISA QTY</th>
                <th class="text-center font-weight-bold">TGL EXPIRED</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="4" class="text-center text-medium-emphasis py-6">
                  <VProgressCircular indeterminate color="error" size="24" class="me-2" />
                  <span>Memuat data kedaluwarsa...</span>
                </td>
              </tr>
              <tr v-else-if="analyticsData.expiring_batches.length === 0">
                <td colspan="4" class="text-center text-medium-emphasis py-6">
                  <VIcon icon="ri-checkbox-circle-line" color="success" size="18" class="me-1" />
                  Tidak ada produk yang mendekati kedaluwarsa.
                </td>
              </tr>
              <tr v-for="item in analyticsData.expiring_batches" :key="item.id">
                <td>
                  <div class="font-weight-medium text-subtitle-2">{{ item.product_branch?.product?.name || '-' }}</div>
                  <div class="text-caption text-disabled">Batch #{{ item.id }}</div>
                </td>
                <td class="text-center">
                  <VChip size="x-small" variant="tonal" color="secondary">{{ item.product_branch?.branch?.name || '-' }}</VChip>
                </td>
                <td class="text-center font-weight-bold">{{ item.qty }}</td>
                <td class="text-center">
                  <VChip size="small" variant="elevated" color="error" class="font-weight-bold">
                    {{ new Date(item.expiration_date).toLocaleDateString('id-ID') }}
                  </VChip>
                </td>
              </tr>
            </tbody>
          </VTable>

          <VSpacer />
          <VDivider />
          <VCardActions class="pa-2">
            <VBtn block variant="text" to="/laporan/stok-aging" color="error" append-icon="ri-arrow-right-line">
              Buka Analisis Usia Stok
            </VBtn>
          </VCardActions>
        </VCard>
      </VCol>
    </VRow>

    <!-- Row: Dead Stock (FIFO) & New Stock (LIFO) -->
    <VRow>
      <!-- Dead Stock Table (FIFO) -->
      <VCol cols="12" md="6">
        <VCard elevation="2" class="h-100 d-flex flex-column" :loading="isLoading">
          <VCardItem class="pb-2">
            <template #prepend>
              <VAvatar color="warning" variant="tonal" size="36" class="me-2">
                <VIcon icon="ri-history-line" size="20" />
              </VAvatar>
            </template>
            <VCardTitle class="text-h6 font-weight-bold">Peringatan Dead Stock (FIFO)</VCardTitle>
            <VCardSubtitle>Stok lama mengendap di gudang &gt; 90 hari</VCardSubtitle>
          </VCardItem>
          <VDivider />
          <VProgressLinear v-if="isLoading" indeterminate color="warning" height="2" />

          <VTable class="text-no-wrap" hover density="comfortable">
            <thead>
              <tr class="bg-grey-50">
                <th class="text-left font-weight-bold">PRODUK</th>
                <th class="text-center font-weight-bold">CABANG</th>
                <th class="text-center font-weight-bold">TGL MASUK</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="3" class="text-center text-medium-emphasis py-6">
                  <VProgressCircular indeterminate color="warning" size="24" class="me-2" />
                  <span>Memuat data dead stock...</span>
                </td>
              </tr>
              <tr v-else-if="analyticsData.dead_stock && analyticsData.dead_stock.length === 0">
                <td colspan="3" class="text-center text-medium-emphasis py-6">
                  <VIcon icon="ri-checkbox-circle-line" color="success" size="18" class="me-1" />
                  Tidak ada stok usang yang terdeteksi.
                </td>
              </tr>
              <tr v-for="item in analyticsData.dead_stock" :key="item.id">
                <td>
                  <div class="font-weight-medium text-subtitle-2">{{ item.product_branch?.product?.name }}</div>
                  <div class="text-caption text-disabled">Sisa: {{ item.qty }} pcs</div>
                </td>
                <td class="text-center">
                  <VChip size="x-small" variant="tonal" color="secondary">{{ item.product_branch?.branch?.name }}</VChip>
                </td>
                <td class="text-center">
                  <VChip size="small" variant="tonal" color="warning" class="font-weight-bold">
                    {{ new Date(item.entry_date).toLocaleDateString('id-ID') }}
                  </VChip>
                </td>
              </tr>
            </tbody>
          </VTable>

          <VSpacer />
          <VDivider />
          <VCardActions class="pa-2">
            <VBtn block variant="text" to="/laporan/stok-aging" color="warning" append-icon="ri-arrow-right-line">
              Analisis Stok FIFO
            </VBtn>
          </VCardActions>
        </VCard>
      </VCol>

      <!-- New Stock Table (LIFO) -->
      <VCol cols="12" md="6">
        <VCard elevation="2" class="h-100 d-flex flex-column" :loading="isLoading">
          <VCardItem class="pb-2">
            <template #prepend>
              <VAvatar color="info" variant="tonal" size="36" class="me-2">
                <VIcon icon="ri-inbox-unarchive-line" size="20" />
              </VAvatar>
            </template>
            <VCardTitle class="text-h6 font-weight-bold">Stok Baru Masuk (LIFO)</VCardTitle>
            <VCardSubtitle>Batch produk yang baru ditambahkan ke gudang</VCardSubtitle>
          </VCardItem>
          <VDivider />
          <VProgressLinear v-if="isLoading" indeterminate color="info" height="2" />

          <VTable class="text-no-wrap" hover density="comfortable">
            <thead>
              <tr class="bg-grey-50">
                <th class="text-left font-weight-bold">PRODUK</th>
                <th class="text-center font-weight-bold">CABANG</th>
                <th class="text-center font-weight-bold">TGL MASUK</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="3" class="text-center text-medium-emphasis py-6">
                  <VProgressCircular indeterminate color="info" size="24" class="me-2" />
                  <span>Memuat data stok baru...</span>
                </td>
              </tr>
              <tr v-else-if="analyticsData.new_stock && analyticsData.new_stock.length === 0">
                <td colspan="3" class="text-center text-medium-emphasis py-6">
                  Belum ada produk baru masuk.
                </td>
              </tr>
              <tr v-for="item in analyticsData.new_stock" :key="item.id">
                <td>
                  <div class="font-weight-medium text-subtitle-2">{{ item.product_branch?.product?.name }}</div>
                  <div class="text-caption text-disabled">Sisa: {{ item.qty }} pcs</div>
                </td>
                <td class="text-center">
                  <VChip size="x-small" variant="tonal" color="secondary">{{ item.product_branch?.branch?.name }}</VChip>
                </td>
                <td class="text-center">
                  <VChip size="small" variant="tonal" color="info" class="font-weight-bold">
                    {{ new Date(item.entry_date).toLocaleDateString('id-ID') }}
                  </VChip>
                </td>
              </tr>
            </tbody>
          </VTable>

          <VSpacer />
          <VDivider />
          <VCardActions class="pa-2">
            <VBtn block variant="text" to="/laporan/stok-aging" color="info" append-icon="ri-arrow-right-line">
              Analisis Stok LIFO
            </VBtn>
          </VCardActions>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<style lang="scss">
@use "@core-scss/template/libs/apex-chart.scss";
</style>

<route lang="yaml">
meta:
  action: read
  subject: Dashboard Analytics
</route>
