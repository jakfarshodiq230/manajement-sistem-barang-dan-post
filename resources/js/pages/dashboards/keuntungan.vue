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
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value)
}

const getMargin = () => {
  if (analyticsData.value.total_revenue == 0) return 0
  
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
    colors: [currentTheme.primary],
    plotOptions: {
      bar: {
        borderRadius: 4,
        columnWidth: '40%',
      },
    },
    dataLabels: {
      enabled: false,
    },
    xaxis: {
      categories: analyticsData.value.chart_data?.categories || [],
      axisBorder: { show: false },
      axisTicks: { show: false },
    },
    yaxis: {
      labels: {
        formatter: value => formatCurrency(value),
      },
    },
    grid: {
      borderColor: currentTheme['border-color'],
      strokeDashArray: 4,
      xaxis: { lines: { show: false } },
    },
    tooltip: {
      y: {
        formatter: value => formatCurrency(value),
      },
    },
  }
})

const chartSeries = computed(() => [
  {
    name: 'Keuntungan Bersih',
    data: analyticsData.value.chart_data?.series || [],
  },
])
</script>

<template>
  <VRow>
    <VCol
      cols="12"
      md="4"
    >
      <VCard>
        <VCardText class="d-flex flex-column pb-4">
          <h6 class="text-h6 font-weight-medium mb-1 text-center">
            Laba Bersih Hari Ini
          </h6>
          <h2 class="text-h2 text-success text-center mt-3">
            {{ formatCurrency(analyticsData.profit_today) }}
          </h2>
        </VCardText>
      </VCard>
    </VCol>

    <VCol
      cols="12"
      md="4"
    >
      <VCard>
        <VCardText class="d-flex flex-column pb-4">
          <h6 class="text-h6 font-weight-medium mb-1 text-center">
            Laba Bersih Bulan Ini
          </h6>
          <h2 class="text-h2 text-info text-center mt-3">
            {{ formatCurrency(analyticsData.profit_this_month) }}
          </h2>
        </VCardText>
      </VCard>
    </VCol>
    
    <VCol
      cols="12"
      md="4"
    >
      <VCard>
        <VCardText class="d-flex flex-column pb-4">
          <h6 class="text-h6 font-weight-medium mb-1 text-center">
            Margin Keuntungan Keseluruhan
          </h6>
          <h2 class="text-h2 text-primary text-center mt-3">
            {{ getMargin() }}%
          </h2>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12">
      <VCard>
        <VCardText class="d-flex align-center justify-space-between pb-4">
          <div>
            <h6 class="text-h6 font-weight-medium mb-1">
              Total Laba Bersih (Keseluruhan Waktu)
            </h6>
            <h1 class="text-h1 text-success mt-2">
              {{ formatCurrency(analyticsData.total_profit) }}
            </h1>
            <p class="text-sm text-medium-emphasis mt-1">
              Dihitung dari Gross Revenue - Harga Modal (COGS)
            </p>
          </div>
          <VAvatar
            color="success"
            variant="tonal"
            rounded
            size="80"
          >
            <VIcon
              icon="ri-money-dollar-circle-line"
              size="50"
            />
          </VAvatar>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12">
      <VCard title="Grafik Keuntungan 6 Bulan Terakhir">
        <VCardText>
          <VueApexCharts
            v-if="!isLoading"
            type="bar"
            height="350"
            :options="chartOptions"
            :series="chartSeries"
          />
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12">
      <VCard title="Catatan Rumus & Perolehan Angka">
        <VCardText>
          <VAlert
            color="info"
            variant="tonal"
            class="mb-4"
            icon="ri-information-line"
          >
            Angka keuntungan pada dasbor ini hanya dihitung dari transaksi penjualan yang memiliki status <strong>Selesai (Completed)</strong>. Transaksi yang masih tertunda atau dibatalkan tidak dimasukkan ke dalam perhitungan.
          </VAlert>
          
          <ul class="d-flex flex-column gap-3 ms-4 text-body-1">
            <li>
              <strong>Laba Bersih (Profit)</strong> = <code>Total Pendapatan (Harga Jual)</code> - <code>Total Harga Modal (HPP / COGS)</code><br>
              <span class="text-medium-emphasis text-sm">Contoh: Jika barang terjual seharga Rp100.000 dengan modal Rp70.000, maka Laba Bersih = Rp30.000. Sistem secara otomatis menjumlahkan semua barang yang terjual.</span>
            </li>
            <li>
              <strong>Laba Bersih Hari Ini</strong> = Diambil dari total transaksi yang diselesaikan tepat pada tanggal hari ini.
            </li>
            <li>
              <strong>Laba Bersih Bulan Ini</strong> = Diambil dari total transaksi yang diselesaikan pada bulan dan tahun saat ini berjalan.
            </li>
            <li>
              <strong>Margin Keuntungan</strong> = <code>(Total Laba Bersih / Total Pendapatan Keseluruhan) &times; 100%</code><br>
              <span class="text-medium-emphasis text-sm">Margin ini menunjukkan persentase rasio rata-rata keuntungan kotor bisnis Anda dari total omset yang didapat.</span>
            </li>
          </ul>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Dashboard Keuntungan
</route>
