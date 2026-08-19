<script setup>
import { ref, onMounted, computed } from 'vue'
import { $api } from '@/utils/api'
import VueApexCharts from 'vue3-apexcharts'
import { useTheme } from 'vuetify'

const vuetifyTheme = useTheme()

const analyticsData = ref({
  total_profit: 0,
  total_revenue: 0,
  profit_today: 0,
  profit_this_month: 0,
  chart_data: {
    categories: [],
    series: [],
  },
})

const isLoading = ref(true)

const fetchAnalytics = async () => {
  isLoading.value = true
  try {
    const res = await $api('/apps/dashboards/profit')
    if (res.success) {
      analyticsData.value = res.data
    }
  } catch (error) {
    console.error('Error fetching profit analytics:', error)
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
  }).format(value)
}

const getMargin = () => {
  if (!analyticsData.value.total_revenue || analyticsData.value.total_revenue === 0) return 0
  return ((analyticsData.value.total_profit / analyticsData.value.total_revenue) * 100).toFixed(1)
}

const chartOptions = computed(() => {
  const currentTheme = vuetifyTheme.current.value.colors

  return {
    chart: {
      type: 'bar',
      parentHeightOffset: 0,
      toolbar: { show: false },
    },
    colors: [currentTheme.success],
    plotOptions: {
      bar: {
        borderRadius: 6,
        columnWidth: '45%',
        distributed: false,
      },
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
    name: 'Keuntungan Bersih (Profit)',
    data: analyticsData.value.chart_data?.series || [],
  },
])
</script>

<template>
  <div class="pa-4">
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="text-h4 font-weight-bold mb-1">
          Dashboard Keuntungan & Margin
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Analisis laba bersih riil (Omzet - HPP Modal), persentase margin laba, dan performa profitabilitas.
        </p>
      </div>

      <VBtn
        color="secondary"
        variant="tonal"
        prepend-icon="ri-refresh-line"
        :loading="isLoading"
        @click="fetchAnalytics"
      >
        Muat Ulang
      </VBtn>
    </div>

    <!-- Summary KPI Row -->
    <VRow class="mb-4">
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-success" :loading="isLoading">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-success font-weight-bold">LABA HARI INI</div>
              <div class="text-h4 font-weight-bold text-success mt-1">{{ formatCurrency(analyticsData.profit_today) }}</div>
            </div>
            <VAvatar color="success" variant="tonal" size="44">
              <VIcon icon="ri-calendar-check-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Penjualan hari ini dikurangi HPP</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-info" :loading="isLoading">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-info font-weight-bold">LABA BULAN INI</div>
              <div class="text-h4 font-weight-bold text-info mt-1">{{ formatCurrency(analyticsData.profit_this_month) }}</div>
            </div>
            <VAvatar color="info" variant="tonal" size="44">
              <VIcon icon="ri-line-chart-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Akumulasi laba bulan berjalan</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-primary" :loading="isLoading">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-primary font-weight-bold">MARGIN KEUNTUNGAN</div>
              <div class="text-h4 font-weight-bold text-primary mt-1">{{ getMargin() }}%</div>
            </div>
            <VAvatar color="primary" variant="tonal" size="44">
              <VIcon icon="ri-percent-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Rasio profit terhadap omzet</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-warning" :loading="isLoading">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-warning font-weight-bold">TOTAL OMZET KESELURUHAN</div>
              <div class="text-h4 font-weight-bold text-warning mt-1">{{ formatCurrency(analyticsData.total_revenue) }}</div>
            </div>
            <VAvatar color="warning" variant="tonal" size="44">
              <VIcon icon="ri-wallet-3-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Gross revenue all-time</div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Highlight Total Net Profit Card -->
    <VCard elevation="2" class="mb-4 bg-primary text-white" :loading="isLoading">
      <VCardText class="d-flex flex-wrap align-center justify-space-between pa-6">
        <div>
          <div class="text-subtitle-1 text-white-50 font-weight-medium mb-1">
            TOTAL AKUMULASI LABA BERSIH (KESELURUHAN WAKTU)
          </div>
          <div class="text-h2 font-weight-bold text-white my-2">
            {{ formatCurrency(analyticsData.total_profit) }}
          </div>
          <div class="d-flex align-center gap-2 text-caption text-white">
            <VIcon icon="ri-checkbox-circle-fill" size="16" />
            <span>Dihitung dari Total Gross Revenue dikurangi Total Beban Pokok Penjualan (HPP/COGS).</span>
          </div>
        </div>
        <VAvatar color="white" variant="tonal" size="80" class="d-none d-sm-flex">
          <VIcon icon="ri-money-dollar-circle-line" size="48" color="white" />
        </VAvatar>
      </VCardText>
    </VCard>

    <!-- Monthly Profit Bar Chart -->
    <VCard elevation="2" class="mb-4" :loading="isLoading">
      <VCardItem class="pb-2">
        <template #prepend>
          <VAvatar color="success" variant="tonal" size="36" class="me-2">
            <VIcon icon="ri-bar-chart-grouped-line" size="20" />
          </VAvatar>
        </template>
        <VCardTitle class="text-h6 font-weight-bold">Grafik Pertumbuhan Laba Bersih (6 Bulan Terakhir)</VCardTitle>
        <VCardSubtitle>Tren performa profitabilitas bulanan usaha Anda</VCardSubtitle>
      </VCardItem>
      <VDivider />

      <VCardText class="pt-4">
        <VueApexCharts
          v-if="!isLoading"
          type="bar"
          height="350"
          :options="chartOptions"
          :series="chartSeries"
        />
      </VCardText>
    </VCard>

    <!-- Accounting Formula Explanation Card -->
    <VCard elevation="2">
      <VCardItem class="pb-2">
        <template #prepend>
          <VAvatar color="info" variant="tonal" size="36" class="me-2">
            <VIcon icon="ri-information-line" size="20" />
          </VAvatar>
        </template>
        <VCardTitle class="text-h6 font-weight-bold">Metodologi & Standar Perhitungan Akuntansi</VCardTitle>
        <VCardSubtitle>Rumus baku yang diterapkan oleh sistem untuk menjaga akurasi laporan laba</VCardSubtitle>
      </VCardItem>
      <VDivider />

      <VCardText class="pa-4">
        <VRow>
          <VCol cols="12" md="4">
            <div class="pa-3 bg-grey-50 rounded border">
              <div class="font-weight-bold text-subtitle-2 mb-1 text-primary">1. Laba Bersih (Net Profit)</div>
              <div class="text-caption text-medium-emphasis">
                <code>Laba Bersih = Omzet - HPP (COGS)</code>
              </div>
              <div class="text-caption text-disabled mt-1">HPP dihitung otomatis per item berdasarkan harga modal beli saat batch masuk.</div>
            </div>
          </VCol>
          <VCol cols="12" md="4">
            <div class="pa-3 bg-grey-50 rounded border">
              <div class="font-weight-bold text-subtitle-2 mb-1 text-primary">2. Margin Keuntungan (%)</div>
              <div class="text-caption text-medium-emphasis">
                <code>Margin = (Laba Bersih / Omzet) &times; 100%</code>
              </div>
              <div class="text-caption text-disabled mt-1">Mengukur efisiensi laba dari setiap rupiah pendapatan yang dihasilkan.</div>
            </div>
          </VCol>
          <VCol cols="12" md="4">
            <div class="pa-3 bg-grey-50 rounded border">
              <div class="font-weight-bold text-subtitle-2 mb-1 text-primary">3. Nilai Persediaan Sisa</div>
              <div class="text-caption text-medium-emphasis">
                <code>Aset Sisa = Sisa Qty &times; Harga Beli</code>
              </div>
              <div class="text-caption text-disabled mt-1">Barang yang belum terjual tetap tercatat sebagai modal aset persediaan aktif.</div>
            </div>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Dashboard Keuntungan
</route>
