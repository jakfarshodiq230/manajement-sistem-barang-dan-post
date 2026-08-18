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

    branches.value = [{ id: 'all', name: 'Semua Cabang' }, ...data]
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
    },
    colors: [theme.current.value.colors.primary, theme.current.value.colors.success],
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    xaxis: {
      categories: analyticsData.value.chart.map(item => item.date),
      labels: {
        style: { colors: theme.current.value.colors['on-surface'] },
      },
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
      y: {
        formatter: function (val) {
          return formatCurrency(val)
        },
      },
    },
    legend: {
      position: 'top',
      horizontalAlign: 'left',
      labels: { colors: theme.current.value.colors['on-surface'] },
    },
    grid: {
      borderColor: theme.current.value.colors['border-color'],
      strokeDashArray: 4,
    },
  }
})

const chartSeries = computed(() => {
  return [
    {
      name: 'Pendapatan',
      data: analyticsData.value.chart.map(item => item.revenue),
    },
    {
      name: 'Keuntungan',
      data: analyticsData.value.chart.map(item => item.profit),
    },
  ]
})
</script>

<template>
  <div>
    <div class="d-flex justify-space-between align-center mb-6">
      <h4 class="text-h4 font-weight-bold">
        Dashboard Penjualan
      </h4>
      <div class="d-flex gap-4">
        <div
          v-if="branches.length > 1"
          style="width: 200px;"
        >
          <VSelect
            v-model="selectedBranch"
            :items="branches"
            item-title="name"
            item-value="id"
            density="compact"
            hide-details
            variant="outlined"
            bg-color="surface"
          />
        </div>
        <div style="width: 200px;">
          <VSelect
            v-model="period"
            :items="[
              { title: 'Harian (7 Hari Terakhir)', value: 'daily' },
              { title: 'Bulanan (6 Bulan Terakhir)', value: 'monthly' },
              { title: 'Tahunan (5 Tahun Terakhir)', value: 'yearly' }
            ]"
            density="compact"
            hide-details
            variant="outlined"
            bg-color="surface"
          />
        </div>
      </div>
    </div>

    <!-- Summary Cards -->
    <VRow>
      <VCol
        cols="12"
        md="4"
      >
        <VCard :loading="isLoading">
          <VCardText class="d-flex align-center justify-space-between pb-4">
            <div>
              <h6 class="text-h6 font-weight-medium mb-1">
                Total Transaksi
              </h6>
              <h3 class="text-h3 text-primary">
                {{ analyticsData.summary.sales.value }}
              </h3>
              <div class="d-flex align-center mt-1">
                <VIcon
                  :icon="getGrowthIcon(analyticsData.summary.sales.growth)"
                  :color="getGrowthColor(analyticsData.summary.sales.growth)"
                  size="18"
                  class="me-1"
                />
                <span
                  class="text-sm font-weight-medium"
                  :class="`text-${getGrowthColor(analyticsData.summary.sales.growth)}`"
                >
                  {{ Math.abs(analyticsData.summary.sales.growth) }}%
                </span>
                <span class="text-sm text-medium-emphasis ms-1">vs periode lalu</span>
              </div>
            </div>
            <VAvatar
              color="primary"
              variant="tonal"
              rounded
              size="50"
            >
              <VIcon
                icon="ri-shopping-cart-2-line"
                size="32"
              />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        md="4"
      >
        <VCard :loading="isLoading">
          <VCardText class="d-flex align-center justify-space-between pb-4">
            <div>
              <h6 class="text-h6 font-weight-medium mb-1">
                Pendapatan
              </h6>
              <h3 class="text-h3 text-info">
                {{ formatCurrency(analyticsData.summary.revenue.value) }}
              </h3>
              <div class="d-flex align-center mt-1">
                <VIcon
                  :icon="getGrowthIcon(analyticsData.summary.revenue.growth)"
                  :color="getGrowthColor(analyticsData.summary.revenue.growth)"
                  size="18"
                  class="me-1"
                />
                <span
                  class="text-sm font-weight-medium"
                  :class="`text-${getGrowthColor(analyticsData.summary.revenue.growth)}`"
                >
                  {{ Math.abs(analyticsData.summary.revenue.growth) }}%
                </span>
                <span class="text-sm text-medium-emphasis ms-1">vs periode lalu</span>
              </div>
            </div>
            <VAvatar
              color="info"
              variant="tonal"
              rounded
              size="50"
            >
              <VIcon
                icon="ri-wallet-3-line"
                size="32"
              />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        md="4"
      >
        <VCard :loading="isLoading">
          <VCardText class="d-flex align-center justify-space-between pb-4">
            <div>
              <h6 class="text-h6 font-weight-medium mb-1">
                Keuntungan Bersih
              </h6>
              <h3 class="text-h3 text-success">
                {{ formatCurrency(analyticsData.summary.profit.value) }}
              </h3>
              <div class="d-flex align-center mt-1">
                <VIcon
                  :icon="getGrowthIcon(analyticsData.summary.profit.growth)"
                  :color="getGrowthColor(analyticsData.summary.profit.growth)"
                  size="18"
                  class="me-1"
                />
                <span
                  class="text-sm font-weight-medium"
                  :class="`text-${getGrowthColor(analyticsData.summary.profit.growth)}`"
                >
                  {{ Math.abs(analyticsData.summary.profit.growth) }}%
                </span>
                <span class="text-sm text-medium-emphasis ms-1">vs periode lalu</span>
              </div>
            </div>
            <VAvatar
              color="success"
              variant="tonal"
              rounded
              size="50"
            >
              <VIcon
                icon="ri-money-dollar-circle-line"
                size="32"
              />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Chart -->
    <VRow class="mt-4">
      <VCol cols="12">
        <VCard
          title="📈 Tren Pendapatan & Keuntungan"
          subtitle="Pergerakan performa finansial berdasarkan periode yang dipilih"
          :loading="isLoading"
        >
          <VCardText>
            <VueApexCharts
              type="area"
              height="400"
              :options="chartOptions"
              :series="chartSeries"
            />
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Dashboard Penjualan
</route>
