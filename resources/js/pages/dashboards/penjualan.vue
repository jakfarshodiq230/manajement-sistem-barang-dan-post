<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { $api } from '@/utils/api'
import VueApexCharts from 'vue3-apexcharts'
import { useTheme } from 'vuetify'

const theme = useTheme()

const period = ref('monthly')
const selectedBranch = ref('all')
const branches = ref([])

const analyticsData = ref({
  summary: {
    sales: { value: 0, growth: 0 },
    revenue: { value: 0, growth: 0 },
    profit: { value: 0, growth: 0 },
  },
  chart: [],
})

const isLoading = ref(true)

const fetchBranches = async () => {
  try {
    const data = await $api('/apps/branches?simple=true')
    branches.value = [{ id: 'all', name: 'Semua Cabang Toko' }, ...data]
  } catch (error) {
    console.error('Error fetching branches:', error)
  }
}

const fetchAnalytics = async () => {
  isLoading.value = true
  try {
    const res = await $api(`/apps/dashboards/sales-analytics?period=${period.value}&branch_id=${selectedBranch.value}`)
    if (res.summary) {
      analyticsData.value = res.data || res
    }
  } catch (error) {
    console.error('Error fetching sales analytics:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchBranches()
  fetchAnalytics()
})

watch([period, selectedBranch], () => {
  fetchAnalytics()
})

const formatCurrency = value => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value)
}

const getGrowthColor = growth => {
  if (growth > 0) return 'success'
  if (growth < 0) return 'error'
  return 'secondary'
}

const getGrowthIcon = growth => {
  if (growth > 0) return 'ri-arrow-up-line'
  if (growth < 0) return 'ri-arrow-down-line'
  return 'ri-subtract-line'
}

// Chart Configuration
const chartOptions = computed(() => {
  return {
    chart: {
      type: 'area',
      parentHeightOffset: 0,
      toolbar: { show: false },
      zoom: { enabled: false },
    },
    colors: [theme.current.value.colors.primary, theme.current.value.colors.success],
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2.5 },
    fill: {
      type: 'gradient',
      gradient: {
        shadeIntensity: 0.8,
        opacityFrom: 0.45,
        opacityTo: 0.05,
        stops: [0, 95, 100],
      },
    },
    xaxis: {
      categories: analyticsData.value.chart.map(item => item.date),
      labels: {
        style: { colors: theme.current.value.colors['on-surface'] },
      },
      axisBorder: { show: false },
      axisTicks: { show: false },
    },
    yaxis: {
      labels: {
        style: { colors: theme.current.value.colors['on-surface'] },
        formatter: val => {
          return new Intl.NumberFormat('id-ID', { notation: 'compact', compactDisplay: 'short' }).format(val)
        },
      },
    },
    tooltip: {
      theme: theme.current.value.dark ? 'dark' : 'light',
      y: {
        formatter: function (val) {
          return formatCurrency(val)
        },
      },
    },
    legend: {
      position: 'top',
      horizontalAlign: 'right',
      labels: { colors: theme.current.value.colors['on-surface'] },
    },
    grid: {
      borderColor: theme.current.value.colors['border-color'],
      strokeDashArray: 4,
      xaxis: { lines: { show: false } },
    },
  }
})

const chartSeries = computed(() => {
  return [
    {
      name: 'Total Omzet (Revenue)',
      data: analyticsData.value.chart.map(item => item.revenue),
    },
    {
      name: 'Laba Bersih (Net Profit)',
      data: analyticsData.value.chart.map(item => item.profit),
    },
  ]
})
</script>

<template>
  <div class="pa-4">
    <!-- Header -->
    <div class="d-flex flex-wrap align-center justify-space-between mb-4 gap-4">
      <div>
        <h2 class="text-h4 font-weight-bold mb-1">
          Dashboard Analisis Penjualan
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Metrik pertumbuhan performa omzet penjualan, volume transaksi, dan laba kotor toko.
        </p>
      </div>

      <!-- Controls -->
      <div class="d-flex flex-wrap align-center gap-3">
        <VAutocomplete
          v-if="branches.length > 1"
          v-model="selectedBranch"
          :items="branches"
          item-title="name"
          item-value="id"
          density="compact"
          variant="outlined"
          placeholder="Semua Cabang Toko"
          label="Pilih Cabang"
          style="min-width: 220px; max-width: 260px;"
          hide-details
        />

        <VBtnToggle
          v-model="period"
          mandatory
          density="compact"
          color="primary"
          variant="tonal"
          divided
          rounded="lg"
          class="border"
        >
          <VBtn value="daily" class="px-4 text-none font-weight-medium" style="min-width: 90px;">
            7 Hari
          </VBtn>
          <VBtn value="monthly" class="px-4 text-none font-weight-medium" style="min-width: 90px;">
            6 Bulan
          </VBtn>
          <VBtn value="yearly" class="px-4 text-none font-weight-medium" style="min-width: 90px;">
            5 Tahun
          </VBtn>
        </VBtnToggle>
      </div>
    </div>

    <!-- Summary KPI Cards -->
    <VRow class="mb-4">
      <VCol cols="12" sm="6" md="4">
        <VCard elevation="2" class="pa-4 border-s-lg border-primary" :loading="isLoading">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-medium-emphasis font-weight-medium">VOLUME TRANSAKSI</div>
              <div class="text-h4 font-weight-bold text-primary mt-1">{{ analyticsData.summary.sales.value.toLocaleString('id-ID') }} <span class="text-caption text-medium-emphasis">Struk</span></div>
              <div class="d-flex align-center mt-2">
                <VChip
                  :color="getGrowthColor(analyticsData.summary.sales.growth)"
                  size="x-small"
                  variant="tonal"
                  class="font-weight-bold me-1"
                >
                  <VIcon :icon="getGrowthIcon(analyticsData.summary.sales.growth)" size="14" class="me-1" />
                  {{ Math.abs(analyticsData.summary.sales.growth) }}%
                </VChip>
                <span class="text-caption text-medium-emphasis">vs periode sebelumnya</span>
              </div>
            </div>
            <VAvatar color="primary" variant="tonal" size="48">
              <VIcon icon="ri-shopping-cart-2-line" size="26" />
            </VAvatar>
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="4">
        <VCard elevation="2" class="pa-4 border-s-lg border-info" :loading="isLoading">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-medium-emphasis font-weight-medium">TOTAL PENDAPATAN (OMZET)</div>
              <div class="text-h4 font-weight-bold text-info mt-1">{{ formatCurrency(analyticsData.summary.revenue.value) }}</div>
              <div class="d-flex align-center mt-2">
                <VChip
                  :color="getGrowthColor(analyticsData.summary.revenue.growth)"
                  size="x-small"
                  variant="tonal"
                  class="font-weight-bold me-1"
                >
                  <VIcon :icon="getGrowthIcon(analyticsData.summary.revenue.growth)" size="14" class="me-1" />
                  {{ Math.abs(analyticsData.summary.revenue.growth) }}%
                </VChip>
                <span class="text-caption text-medium-emphasis">vs periode sebelumnya</span>
              </div>
            </div>
            <VAvatar color="info" variant="tonal" size="48">
              <VIcon icon="ri-wallet-3-line" size="26" />
            </VAvatar>
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="4">
        <VCard elevation="2" class="pa-4 border-s-lg border-success" :loading="isLoading">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-medium-emphasis font-weight-medium">LABA BERSIH (NET PROFIT)</div>
              <div class="text-h4 font-weight-bold text-success mt-1">{{ formatCurrency(analyticsData.summary.profit.value) }}</div>
              <div class="d-flex align-center mt-2">
                <VChip
                  :color="getGrowthColor(analyticsData.summary.profit.growth)"
                  size="x-small"
                  variant="tonal"
                  class="font-weight-bold me-1"
                >
                  <VIcon :icon="getGrowthIcon(analyticsData.summary.profit.growth)" size="14" class="me-1" />
                  {{ Math.abs(analyticsData.summary.profit.growth) }}%
                </VChip>
                <span class="text-caption text-medium-emphasis">vs periode sebelumnya</span>
              </div>
            </div>
            <VAvatar color="success" variant="tonal" size="48">
              <VIcon icon="ri-money-dollar-circle-line" size="26" />
            </VAvatar>
          </div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Chart -->
    <VCard elevation="2" :loading="isLoading">
      <VCardItem class="pb-2">
        <template #prepend>
          <VAvatar color="primary" variant="tonal" size="36" class="me-2">
            <VIcon icon="ri-line-chart-line" size="20" />
          </VAvatar>
        </template>
        <VCardTitle class="text-h6 font-weight-bold">Grafik Tren Omzet vs Keuntungan</VCardTitle>
        <VCardSubtitle>Visualisasi komparatif pendapatan kotor dan laba bersih per siklus waktu</VCardSubtitle>
      </VCardItem>
      <VDivider />

      <VCardText class="pt-4">
        <VueApexCharts
          type="area"
          height="380"
          :options="chartOptions"
          :series="chartSeries"
        />
      </VCardText>
    </VCard>
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Dashboard Penjualan
</route>
