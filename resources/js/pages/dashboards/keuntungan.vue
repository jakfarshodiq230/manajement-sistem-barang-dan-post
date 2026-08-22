<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { $api } from '@/utils/api'
import VueApexCharts from 'vue3-apexcharts'
import { useTheme } from 'vuetify'

const vuetifyTheme = useTheme()

const analyticsData = ref({
  revenue_today: 0,
  cogs_today: 0,
  expense_today: 0,
  gross_profit_today: 0,
  profit_today: 0,

  revenue_this_month: 0,
  cogs_this_month: 0,
  expense_this_month: 0,
  gross_profit_this_month: 0,
  profit_this_month: 0,

  total_revenue: 0,
  total_cogs: 0,
  total_expense: 0,
  total_gross_profit: 0,
  total_profit: 0,
  margin: 0,

  chart_data: {
    categories: [],
    series: [],
    series_gross: [],
    series_expenses: [],
  },
})

const selectedBranch = ref('all')
const branches = ref([])
const isLoading = ref(true)

const fetchBranches = async () => {
  try {
    const res = await $api('/apps/branches')
    branches.value = res.data || res || []
  } catch (e) {
    console.error('Error fetching branches:', e)
  }
}

const fetchAnalytics = async () => {
  isLoading.value = true
  try {
    const params = {}
    if (selectedBranch.value && selectedBranch.value !== 'all') {
      params.branch_id = selectedBranch.value
    }
    const res = await $api('/apps/dashboards/profit', { params })
    if (res.success) {
      analyticsData.value = res.data
    }
  } catch (error) {
    console.error('Error fetching profit analytics:', error)
  } finally {
    isLoading.value = false
  }
}

watch(selectedBranch, () => {
  fetchAnalytics()
})

onMounted(async () => {
  await fetchBranches()
  await fetchAnalytics()
})

const formatCurrency = value => {
  if (value === null || value === undefined || isNaN(value)) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value)
}

const chartOptions = computed(() => {
  const currentTheme = vuetifyTheme.current.value.colors

  return {
    chart: {
      type: 'bar',
      parentHeightOffset: 0,
      toolbar: { show: false },
    },
    colors: [currentTheme.primary, currentTheme.error, currentTheme.success],
    plotOptions: {
      bar: {
        borderRadius: 4,
        columnWidth: '50%',
      },
    },
    stroke: {
      show: true,
      width: 2,
      colors: ['transparent'],
    },
    dataLabels: {
      enabled: false,
    },
    xaxis: {
      categories: analyticsData.value.chart_data?.categories || [],
      axisBorder: { show: false },
      axisTicks: { show: false },
      labels: {
        style: { colors: currentTheme['on-surface'] },
      },
    },
    yaxis: {
      labels: {
        style: { colors: currentTheme['on-surface'] },
        formatter: value => {
          return new Intl.NumberFormat('id-ID', { notation: 'compact', compactDisplay: 'short' }).format(value)
        },
      },
    },
    legend: {
      position: 'top',
      horizontalAlign: 'left',
      labels: {
        colors: currentTheme['on-surface'],
      },
    },
    grid: {
      borderColor: currentTheme['border-color'],
      strokeDashArray: 4,
      xaxis: { lines: { show: false } },
    },
    tooltip: {
      theme: vuetifyTheme.current.value.dark ? 'dark' : 'light',
      y: {
        formatter: value => formatCurrency(value),
      },
    },
  }
})

const chartSeries = computed(() => [
  {
    name: 'Laba Kotor (Gross)',
    data: analyticsData.value.chart_data?.series_gross || [],
  },
  {
    name: 'Beban Kas Kecil (Expenses)',
    data: analyticsData.value.chart_data?.series_expenses || [],
  },
  {
    name: 'Laba Bersih Riil (Net Profit)',
    data: analyticsData.value.chart_data?.series || [],
  },
])
</script>

<template>
  <div class="pa-4">
    <!-- Header with Branch Filter -->
    <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
      <div>
        <h2 class="text-h4 font-weight-bold mb-1">
          Dashboard Keuntungan & Laba Rugi
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Analisis laba bersih riil terintegrasi: <span class="font-weight-semibold text-primary">Omzet Penjualan &minus; HPP Modal Barang &minus; Beban Operasional Kas Kecil</span>
        </p>
      </div>

      <div class="d-flex align-center gap-3">
        <VSelect
          v-model="selectedBranch"
          :items="[{ id: 'all', name: '🏢 Semua Cabang' }, ...branches]"
          item-title="name"
          item-value="id"
          density="compact"
          variant="outlined"
          style="min-width: 220px;"
          hide-details
          prepend-inner-icon="ri-store-2-line"
        />

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

    <!-- Highlight Total Net Profit Hero Card -->
    <VCard elevation="3" class="mb-6 bg-gradient-primary text-white rounded-xl" :loading="isLoading">
      <VCardText class="d-flex flex-wrap align-center justify-space-between pa-6">
        <div>
          <div class="text-caption font-weight-bold text-uppercase tracking-wider text-white-50">
            TOTAL AKUMULASI LABA BERSIH RIIL (NET PROFIT)
          </div>
          <div class="text-h2 font-weight-extrabold text-white my-2">
            {{ formatCurrency(analyticsData.total_profit) }}
          </div>
          <div class="d-flex flex-wrap align-center gap-4 text-caption text-white mt-3">
            <div class="d-flex align-center gap-1">
              <VIcon icon="ri-shopping-cart-line" size="16" />
              <span>Total Omzet: <strong>{{ formatCurrency(analyticsData.total_revenue) }}</strong></span>
            </div>
            <div class="d-flex align-center gap-1">
              <VIcon icon="ri-archive-line" size="16" />
              <span>Total HPP Modal: <strong>{{ formatCurrency(analyticsData.total_cogs) }}</strong></span>
            </div>
            <div class="d-flex align-center gap-1">
              <VIcon icon="ri-money-dollar-box-line" size="16" />
              <span>Beban Kas Kecil: <strong>{{ formatCurrency(analyticsData.total_expense) }}</strong></span>
            </div>
            <div class="d-flex align-center gap-1">
              <VChip size="x-small" color="white" variant="flat" class="text-primary font-weight-bold">
                Margin Bersih: {{ analyticsData.margin }}%
              </VChip>
            </div>
          </div>
        </div>
        <div class="d-none d-md-flex align-center gap-2">
          <VBtn
            to="/kas-kecil"
            color="white"
            variant="flat"
            class="text-primary font-weight-bold"
            prepend-icon="ri-wallet-3-line"
          >
            Kelola Kas Kecil
          </VBtn>
          <VBtn
            to="/audit/rekap"
            color="white"
            variant="outlined"
            prepend-icon="ri-file-chart-line"
          >
            Rekap Tahunan
          </VBtn>
        </div>
      </VCardText>
    </VCard>

    <!-- Summary KPI Row -->
    <VRow class="mb-6">
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 rounded-xl border-s-lg border-success h-100" :loading="isLoading">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-success font-weight-bold">LABA BERSIH HARI INI</div>
              <div class="text-h4 font-weight-bold text-success mt-1">{{ formatCurrency(analyticsData.profit_today) }}</div>
            </div>
            <VAvatar color="success" variant="tonal" size="46" rounded="lg">
              <VIcon icon="ri-calendar-check-line" size="26" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">
            Omzet hari ini: {{ formatCurrency(analyticsData.revenue_today) }}
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 rounded-xl border-s-lg border-primary h-100" :loading="isLoading">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-primary font-weight-bold">LABA BERSIH BULAN INI</div>
              <div class="text-h4 font-weight-bold text-primary mt-1">{{ formatCurrency(analyticsData.profit_this_month) }}</div>
            </div>
            <VAvatar color="primary" variant="tonal" size="46" rounded="lg">
              <VIcon icon="ri-line-chart-line" size="26" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">
            Laba kotor: {{ formatCurrency(analyticsData.gross_profit_this_month) }}
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 rounded-xl border-s-lg border-error h-100" :loading="isLoading">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-error font-weight-bold">BEBAN KAS KECIL BULAN INI</div>
              <div class="text-h4 font-weight-bold text-error mt-1">{{ formatCurrency(analyticsData.expense_this_month) }}</div>
            </div>
            <VAvatar color="error" variant="tonal" size="46" rounded="lg">
              <VIcon icon="ri-hand-coin-line" size="26" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">
            Listrik, galon, kurir, ATK & lembur
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 rounded-xl border-s-lg border-warning h-100" :loading="isLoading">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-warning font-weight-bold">TOTAL OMZET PENJUALAN</div>
              <div class="text-h4 font-weight-bold text-warning mt-1">{{ formatCurrency(analyticsData.total_revenue) }}</div>
            </div>
            <VAvatar color="warning" variant="tonal" size="46" rounded="lg">
              <VIcon icon="ri-wallet-3-line" size="26" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">
            Margin Bersih: {{ analyticsData.margin }}%
          </div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Monthly Multi-Bar Chart -->
    <VCard elevation="2" class="mb-6 rounded-xl" :loading="isLoading">
      <VCardItem class="pb-2">
        <template #prepend>
          <VAvatar color="primary" variant="tonal" size="38" class="me-2" rounded="lg">
            <VIcon icon="ri-bar-chart-grouped-line" size="22" />
          </VAvatar>
        </template>
        <VCardTitle class="text-h6 font-weight-bold">Grafik Laba Kotor vs Beban Operasional vs Laba Bersih</VCardTitle>
        <VCardSubtitle>Perbandingan tren 6 bulan terakhir: Penjualan dikurangi modal dan beban kas toko</VCardSubtitle>
      </VCardItem>
      <VDivider />

      <VCardText class="pt-4">
        <VueApexCharts
          v-if="!isLoading"
          type="bar"
          height="360"
          :options="chartOptions"
          :series="chartSeries"
        />
      </VCardText>
    </VCard>

    <!-- Accounting Formula & Integration Explanation Card -->
    <VCard elevation="2" class="rounded-xl">
      <VCardItem class="pb-2">
        <template #prepend>
          <VAvatar color="info" variant="tonal" size="38" class="me-2" rounded="lg">
            <VIcon icon="ri-calculator-line" size="22" />
          </VAvatar>
        </template>
        <VCardTitle class="text-h6 font-weight-bold">Standar Akuntansi & Integrasi Laba Rugi Toko</VCardTitle>
        <VCardSubtitle>Struktur perhitungan terpadu antara modul POS Kasir, Gudang HPP, dan Buku Kas Kecil</VCardSubtitle>
      </VCardItem>
      <VDivider />

      <VCardText class="pa-5">
        <VRow>
          <VCol cols="12" md="4">
            <div class="pa-4 bg-var-theme-surface rounded-xl border h-100">
              <div class="font-weight-bold text-subtitle-2 mb-1 text-primary d-flex align-center gap-2">
                <VIcon icon="ri-coins-line" size="18" />
                1. Laba Kotor (Gross Profit)
              </div>
              <div class="text-caption text-medium-emphasis mt-2">
                <code>Laba Kotor = Omzet Penjualan &minus; HPP (COGS Modal)</code>
              </div>
              <div class="text-caption text-disabled mt-2">
                Dihitung otomatis saat kasir menyelesaikan transaksi POS berdasarkan batch modal barang yang masuk.
              </div>
            </div>
          </VCol>

          <VCol cols="12" md="4">
            <div class="pa-4 bg-var-theme-surface rounded-xl border h-100">
              <div class="font-weight-bold text-subtitle-2 mb-1 text-error d-flex align-center gap-2">
                <VIcon icon="ri-hand-coin-line" size="18" />
                2. Beban Operasional (Kas Kecil)
              </div>
              <div class="text-caption text-medium-emphasis mt-2">
                <code>Total Kas Kecil = Listrik + Air + Bensin + ATK + Konsumsi</code>
              </div>
              <div class="text-caption text-disabled mt-2">
                Tercatat pada menu Buku Kas Kecil cabang dan mengurangi langsung hasil laba operasional toko.
              </div>
            </div>
          </VCol>

          <VCol cols="12" md="4">
            <div class="pa-4 bg-var-theme-surface rounded-xl border h-100">
              <div class="font-weight-bold text-subtitle-2 mb-1 text-success d-flex align-center gap-2">
                <VIcon icon="ri-trophy-line" size="18" />
                3. Laba Bersih Riil (Net Profit)
              </div>
              <div class="text-caption text-medium-emphasis mt-2">
                <code>Laba Bersih = Laba Kotor &minus; Beban Operasional</code>
              </div>
              <div class="text-caption text-disabled mt-2">
                Menghasilkan angka keuntungan bersih riil yang menjadi dasar pembagian dividen owner dan rekap tahunan.
              </div>
            </div>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
  </div>
</template>

<style scoped>
.bg-gradient-primary {
  background: linear-gradient(135deg, #7367F0 0%, #4834D4 100%);
}
</style>

<route lang="yaml">
meta:
  action: read
  subject: Dashboard Keuntungan
</route>
