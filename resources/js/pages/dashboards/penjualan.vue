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
    aov: { value: 0 },
    discount: { value: 0 },
    margin: 0,
  },
  chart: [],
  payment_breakdown: [],
  top_products: [],
  recent_transactions: [],
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
      analyticsData.value = {
        summary: res.summary,
        chart: res.chart || [],
        payment_breakdown: res.payment_breakdown || [],
        top_products: res.top_products || [],
        recent_transactions: res.recent_transactions || [],
      }
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
  if (value === null || value === undefined || isNaN(value)) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
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

// Area Chart Configuration (Revenue vs Profit)
const chartOptions = computed(() => {
  const currentTheme = theme.current.value.colors

  return {
    chart: {
      type: 'area',
      parentHeightOffset: 0,
      toolbar: { show: false },
      zoom: { enabled: false },
    },
    colors: [currentTheme.primary, currentTheme.success],
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: [3, 2.5] },
    fill: {
      type: 'gradient',
      gradient: {
        shadeIntensity: 0.8,
        opacityFrom: 0.5,
        opacityTo: 0.05,
        stops: [0, 95, 100],
      },
    },
    xaxis: {
      categories: analyticsData.value.chart.map(item => item.date),
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
        style: {
          colors: currentTheme['on-surface'],
          fontSize: '12px',
        },
        formatter: val => {
          if (val >= 1000000000) return 'Rp ' + (val / 1000000000).toFixed(1) + ' M'
          if (val >= 1000000) return 'Rp ' + (val / 1000000).toFixed(1) + ' jt'
          if (val >= 1000) return 'Rp ' + (val / 1000).toFixed(0) + ' rb'
          return 'Rp ' + val
        },
      },
    },
    tooltip: {
      theme: theme.current.value.dark ? 'dark' : 'light',
      y: {
        formatter: val => formatCurrency(val),
      },
    },
    legend: {
      position: 'top',
      horizontalAlign: 'right',
      labels: { colors: currentTheme['on-surface'] },
      markers: { radius: 12 },
    },
    grid: {
      borderColor: 'rgba(var(--v-border-color), 0.12)',
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

// Payment Method Donut Chart
const paymentChartOptions = computed(() => {
  const currentTheme = theme.current.value.colors
  const labels = analyticsData.value.payment_breakdown.map(p => {
    const mapName = {
      cash: 'Tunai',
      transfer: 'Transfer Bank',
      qris: 'QRIS',
      tempo: 'Piutang / Tempo',
      credit: 'Kredit',
    }
    return mapName[p.payment_method?.toLowerCase()] || p.payment_method || 'Lainnya'
  })

  return {
    chart: {
      type: 'donut',
      parentHeightOffset: 0,
    },
    labels: labels.length ? labels : ['Belum ada transaksi'],
    colors: [currentTheme.primary, currentTheme.success, currentTheme.info, currentTheme.warning, currentTheme.secondary],
    dataLabels: { enabled: false },
    legend: {
      position: 'bottom',
      labels: { colors: currentTheme['on-surface'] },
    },
    plotOptions: {
      pie: {
        donut: {
          size: '72%',
          labels: {
            show: true,
            total: {
              show: true,
              label: 'Total Tagihan',
              fontSize: '13px',
              color: currentTheme['on-surface'],
              formatter: () => formatCurrency(analyticsData.value.summary.revenue.value),
            },
          },
        },
      },
    },
    stroke: { width: 0 },
    tooltip: {
      y: {
        formatter: val => formatCurrency(val),
      },
    },
  }
})

const paymentChartSeries = computed(() => {
  const totals = analyticsData.value.payment_breakdown.map(p => Number(p.total) || 0)
  return totals.length ? totals : [0]
})

const formatPaymentLabel = method => {
  const map = {
    cash: { label: 'Tunai', color: 'success', icon: 'ri-money-dollar-circle-line' },
    transfer: { label: 'Transfer', color: 'primary', icon: 'ri-bank-card-line' },
    qris: { label: 'QRIS', color: 'info', icon: 'ri-qr-code-line' },
    tempo: { label: 'Piutang / Tempo', color: 'warning', icon: 'ri-time-line' },
  }
  return map[method?.toLowerCase()] || { label: method || 'Tunai', color: 'secondary', icon: 'ri-wallet-3-line' }
}
</script>

<template>
  <div class="pa-4">
    <!-- Header Banner -->
    <VCard elevation="2" class="mb-4 pa-4 rounded-xl border bg-var-theme-surface">
      <div class="d-flex flex-wrap align-center justify-space-between gap-4">
        <!-- Title & Subtitle -->
        <div class="d-flex align-center gap-3">
          <VAvatar color="primary" variant="tonal" rounded size="44">
            <VIcon icon="ri-line-chart-line" size="26" />
          </VAvatar>
          <div>
            <h2 class="text-h6 font-weight-bold mb-0">
              Dashboard Analisis Penjualan Kasir (POS)
            </h2>
            <p class="text-caption text-medium-emphasis mb-0">
              Pertumbuhan omzet riil, margin laba kotor, volume struk kasir, dan komposisi pembayaran
            </p>
          </div>
        </div>

        <!-- Controls (Branch & Period) -->
        <div class="d-flex flex-wrap align-center gap-3">
          <div style="min-width: 240px;">
            <VAutocomplete
              v-if="branches.length > 1"
              v-model="selectedBranch"
              :items="branches"
              item-title="name"
              item-value="id"
              density="compact"
              variant="outlined"
              placeholder="Semua Cabang Toko"
              prepend-inner-icon="ri-store-2-line"
              hide-details
            />
          </div>

          <!-- Segmented Period Buttons (Clean, no overlap) -->
          <div class="d-inline-flex rounded-lg border pa-1 bg-var-theme-background gap-1">
            <VBtn
              size="small"
              :variant="period === 'daily' ? 'elevated' : 'text'"
              :color="period === 'daily' ? 'primary' : 'default'"
              class="text-none font-weight-medium px-3"
              @click="period = 'daily'"
            >
              7 Hari
            </VBtn>
            <VBtn
              size="small"
              :variant="period === 'monthly' ? 'elevated' : 'text'"
              :color="period === 'monthly' ? 'primary' : 'default'"
              class="text-none font-weight-medium px-3"
              @click="period = 'monthly'"
            >
              6 Bulan
            </VBtn>
            <VBtn
              size="small"
              :variant="period === 'yearly' ? 'elevated' : 'text'"
              :color="period === 'yearly' ? 'primary' : 'default'"
              class="text-none font-weight-medium px-3"
              @click="period = 'yearly'"
            >
              5 Tahun
            </VBtn>
          </div>
        </div>
      </div>
    </VCard>

    <!-- Top 4 Executive KPI Cards -->
    <VRow class="mb-4 match-height">
      <!-- 1. Total Omzet (Revenue) -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-primary h-100 d-flex flex-column justify-space-between" :loading="isLoading">
          <div>
            <div class="d-flex align-center justify-space-between">
              <span class="text-caption text-primary font-weight-bold text-uppercase">Total Omzet (Penjualan)</span>
              <VAvatar color="primary" variant="tonal" rounded size="40">
                <VIcon icon="ri-wallet-3-line" size="22" />
              </VAvatar>
            </div>
            <div class="text-h5 font-weight-bold text-primary mt-2">
              {{ formatCurrency(analyticsData.summary.revenue.value) }}
            </div>
          </div>
          <div class="d-flex align-center gap-1 mt-3">
            <VChip
              :color="getGrowthColor(analyticsData.summary.revenue.growth)"
              size="x-small"
              variant="tonal"
              class="font-weight-bold"
            >
              <VIcon :icon="getGrowthIcon(analyticsData.summary.revenue.growth)" size="14" class="me-1" />
              {{ Math.abs(analyticsData.summary.revenue.growth) }}%
            </VChip>
            <span class="text-caption text-medium-emphasis">vs periode lalu</span>
          </div>
        </VCard>
      </VCol>

      <!-- 2. Laba Bersih (Net Profit) -->
      <VCol cols="12" sm="6" md="3">
        <VCard
          elevation="2"
          :class="['pa-4 border-s-lg h-100 d-flex flex-column justify-space-between', analyticsData.summary.profit.value >= 0 ? 'border-success' : 'border-error']"
          :loading="isLoading"
        >
          <div>
            <div class="d-flex align-center justify-space-between">
              <span :class="['text-caption font-weight-bold text-uppercase', analyticsData.summary.profit.value >= 0 ? 'text-success' : 'text-error']">
                Laba Bersih
              </span>
              <VChip
                size="x-small"
                :color="analyticsData.summary.margin >= 0 ? 'success' : 'error'"
                variant="tonal"
                class="font-weight-bold"
              >
                Margin: {{ analyticsData.summary.margin }}%
              </VChip>
            </div>
            <div :class="['text-h5 font-weight-bold mt-2', analyticsData.summary.profit.value >= 0 ? 'text-success' : 'text-error']">
              {{ formatCurrency(analyticsData.summary.profit.value) }}
            </div>
          </div>
          <div class="d-flex align-center gap-1 mt-3">
            <VChip
              :color="getGrowthColor(analyticsData.summary.profit.growth)"
              size="x-small"
              variant="tonal"
              class="font-weight-bold"
            >
              <VIcon :icon="getGrowthIcon(analyticsData.summary.profit.growth)" size="14" class="me-1" />
              {{ Math.abs(analyticsData.summary.profit.growth) }}%
            </VChip>
            <span class="text-caption text-medium-emphasis">setelah HPP modal</span>
          </div>
        </VCard>
      </VCol>

      <!-- 3. Volume Transaksi -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-info h-100 d-flex flex-column justify-space-between" :loading="isLoading">
          <div>
            <div class="d-flex align-center justify-space-between">
              <span class="text-caption text-info font-weight-bold text-uppercase">Volume Transaksi</span>
              <VAvatar color="info" variant="tonal" rounded size="40">
                <VIcon icon="ri-shopping-bag-3-line" size="22" />
              </VAvatar>
            </div>
            <div class="text-h5 font-weight-bold text-info mt-2">
              {{ analyticsData.summary.sales.value.toLocaleString('id-ID') }} <span class="text-body-2 font-weight-regular text-medium-emphasis">Struk</span>
            </div>
          </div>
          <div class="d-flex align-center gap-1 mt-3">
            <VChip
              :color="getGrowthColor(analyticsData.summary.sales.growth)"
              size="x-small"
              variant="tonal"
              class="font-weight-bold"
            >
              <VIcon :icon="getGrowthIcon(analyticsData.summary.sales.growth)" size="14" class="me-1" />
              {{ Math.abs(analyticsData.summary.sales.growth) }}%
            </VChip>
            <span class="text-caption text-medium-emphasis">struk kasir POS</span>
          </div>
        </VCard>
      </VCol>

      <!-- 4. Average Ticket Size (AOV) -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-warning h-100 d-flex flex-column justify-space-between" :loading="isLoading">
          <div>
            <div class="d-flex align-center justify-space-between">
              <span class="text-caption text-warning font-weight-bold text-uppercase">Rata-rata / Struk (AOV)</span>
              <VAvatar color="warning" variant="tonal" rounded size="40">
                <VIcon icon="ri-receipt-line" size="22" />
              </VAvatar>
            </div>
            <div class="text-h5 font-weight-bold text-warning mt-2">
              {{ formatCurrency(analyticsData.summary.aov?.value || 0) }}
            </div>
          </div>
          <div class="d-flex align-center justify-space-between mt-3 text-caption">
            <span class="text-medium-emphasis">Diskon Kasir:</span>
            <span class="font-weight-bold text-error">{{ formatCurrency(analyticsData.summary.discount?.value || 0) }}</span>
          </div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Main Charts Row (Sales Trend & Payment Methods) -->
    <VRow class="mb-4">
      <!-- Area Chart: Omzet vs Profit -->
      <VCol cols="12" lg="8">
        <VCard elevation="2" class="h-100 d-flex flex-column" :loading="isLoading">
          <VCardItem class="pb-0">
            <template #prepend>
              <VAvatar color="primary" variant="tonal" size="36" class="me-2">
                <VIcon icon="ri-line-chart-line" size="20" />
              </VAvatar>
            </template>
            <VCardTitle class="text-h6 font-weight-bold">Grafik Pertumbuhan Omzet vs Laba Bersih</VCardTitle>
            <VCardSubtitle>Visualisasi komparatif pendapatan kotor kasir dan laba bersih per periode</VCardSubtitle>
          </VCardItem>
          <VDivider class="mt-3" />

          <VCardText class="pt-4 flex-grow-1">
            <VueApexCharts
              type="area"
              height="330"
              :options="chartOptions"
              :series="chartSeries"
            />
          </VCardText>
        </VCard>
      </VCol>

      <!-- Donut Chart: Payment Methods Distribution -->
      <VCol cols="12" lg="4">
        <VCard elevation="2" class="h-100 d-flex flex-column" :loading="isLoading">
          <VCardItem class="pb-0">
            <template #prepend>
              <VAvatar color="success" variant="tonal" size="36" class="me-2">
                <VIcon icon="ri-pie-chart-2-line" size="20" />
              </VAvatar>
            </template>
            <VCardTitle class="text-h6 font-weight-bold">Metode Pembayaran</VCardTitle>
            <VCardSubtitle>Distribusi transaksi Tunai, Transfer, QRIS & Tempo</VCardSubtitle>
          </VCardItem>
          <VDivider class="mt-3" />

          <VCardText class="pt-4 d-flex align-center justify-center flex-grow-1">
            <VueApexCharts
              type="donut"
              height="300"
              :options="paymentChartOptions"
              :series="paymentChartSeries"
            />
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Bottom Row: Top 5 Best Sellers & Recent Transactions Feed -->
    <VRow>
      <!-- Top 5 Best Sellers -->
      <VCol cols="12" lg="6">
        <VCard elevation="2" class="h-100 d-flex flex-column" :loading="isLoading">
          <VCardItem class="pb-2">
            <template #prepend>
              <VAvatar color="warning" variant="tonal" size="36" class="me-2">
                <VIcon icon="ri-trophy-line" size="20" />
              </VAvatar>
            </template>
            <VCardTitle class="text-h6 font-weight-bold">5 Produk Terlaris (Best Sellers)</VCardTitle>
            <VCardSubtitle>Item dengan kontribusi kuantitas & omzet tertinggi</VCardSubtitle>
          </VCardItem>
          <VDivider />
          <VProgressLinear v-if="isLoading" indeterminate color="warning" height="2" />

          <VTable class="text-no-wrap" hover density="comfortable">
            <thead>
              <tr class="bg-grey-50">
                <th class="text-left font-weight-bold">PRODUK</th>
                <th class="text-center font-weight-bold">TERJUAL</th>
                <th class="text-right font-weight-bold">TOTAL KONTRIBUSI</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="3" class="text-center text-medium-emphasis py-6">
                  <VProgressCircular indeterminate color="warning" size="24" class="me-2" />
                  <span>Memuat produk terlaris...</span>
                </td>
              </tr>
              <tr v-else-if="analyticsData.top_products.length === 0">
                <td colspan="3" class="text-center text-medium-emphasis py-6">
                  Belum ada data penjualan pada periode ini.
                </td>
              </tr>
              <tr v-for="(item, idx) in analyticsData.top_products" :key="item.id">
                <td>
                  <div class="d-flex align-center gap-2">
                    <VBadge
                      :content="idx + 1"
                      :color="idx === 0 ? 'warning' : (idx === 1 ? 'secondary' : 'info')"
                      inline
                    />
                    <div>
                      <div class="font-weight-medium text-subtitle-2">{{ item.name }}</div>
                      <div class="text-caption text-disabled"><code>{{ item.sku || '-' }}</code></div>
                    </div>
                  </div>
                </td>
                <td class="text-center font-weight-bold">
                  <VChip size="small" variant="tonal" color="primary">
                    {{ item.total_qty }} pcs
                  </VChip>
                </td>
                <td class="text-right font-weight-bold text-success">
                  {{ formatCurrency(item.total_revenue) }}
                </td>
              </tr>
            </tbody>
          </VTable>

          <VSpacer />
          <VDivider />
          <VCardActions class="pa-2">
            <VBtn block variant="text" to="/laporan" color="primary" append-icon="ri-arrow-right-line">
              Buka Laporan Produk Lengkap
            </VBtn>
          </VCardActions>
        </VCard>
      </VCol>

      <!-- Recent 5 Sales Transactions -->
      <VCol cols="12" lg="6">
        <VCard elevation="2" class="h-100 d-flex flex-column" :loading="isLoading">
          <VCardItem class="pb-2">
            <template #prepend>
              <VAvatar color="info" variant="tonal" size="36" class="me-2">
                <VIcon icon="ri-history-line" size="20" />
              </VAvatar>
            </template>
            <VCardTitle class="text-h6 font-weight-bold">Transaksi Struk Kasir Terbaru</VCardTitle>
            <VCardSubtitle>Aliran transaksi penjualan live dari kasir POS</VCardSubtitle>
          </VCardItem>
          <VDivider />
          <VProgressLinear v-if="isLoading" indeterminate color="info" height="2" />

          <VTable class="text-no-wrap" hover density="comfortable">
            <thead>
              <tr class="bg-grey-50">
                <th class="text-left font-weight-bold">NO. INVOICE</th>
                <th class="text-center font-weight-bold">METODE</th>
                <th class="text-left font-weight-bold">KASIR / CABANG</th>
                <th class="text-right font-weight-bold">TOTAL</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="4" class="text-center text-medium-emphasis py-6">
                  <VProgressCircular indeterminate color="info" size="24" class="me-2" />
                  <span>Memuat transaksi terbaru...</span>
                </td>
              </tr>
              <tr v-else-if="analyticsData.recent_transactions.length === 0">
                <td colspan="4" class="text-center text-medium-emphasis py-6">
                  Belum ada transaksi struk kasir.
                </td>
              </tr>
              <tr v-for="sale in analyticsData.recent_transactions" :key="sale.id">
                <td>
                  <div class="font-weight-bold text-primary">{{ sale.invoice_number || ('#' + sale.id) }}</div>
                  <div class="text-caption text-disabled">{{ new Date(sale.created_at || sale.date).toLocaleString('id-ID') }}</div>
                </td>
                <td class="text-center">
                  <VChip
                    size="small"
                    :color="formatPaymentLabel(sale.payment_method).color"
                    variant="tonal"
                    class="font-weight-medium"
                  >
                    <VIcon :icon="formatPaymentLabel(sale.payment_method).icon" size="14" class="me-1" />
                    {{ formatPaymentLabel(sale.payment_method).label }}
                  </VChip>
                </td>
                <td>
                  <div class="text-subtitle-2">{{ sale.user?.name || 'Kasir' }}</div>
                  <div class="text-caption text-medium-emphasis">{{ sale.branch?.name || '-' }}</div>
                </td>
                <td class="text-right font-weight-bold text-primary">
                  {{ formatCurrency(sale.total_amount) }}
                </td>
              </tr>
            </tbody>
          </VTable>

          <VSpacer />
          <VDivider />
          <VCardActions class="pa-2">
            <VBtn block variant="text" to="/transaksi" color="primary" append-icon="ri-arrow-right-line">
              Lihat Seluruh Riwayat Transaksi
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
  subject: Dashboard Penjualan
</route>
