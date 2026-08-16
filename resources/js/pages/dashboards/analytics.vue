<script setup>
import { ref, onMounted } from 'vue'
import { $api } from '@/utils/api'

// Existing components
import AnalyticsCongratulationsJohn from '@/views/dashboards/analytics/AnalyticsCongratulationsJohn.vue'
import AnalyticsTotalProfitLineCharts from '@/views/dashboards/analytics/AnalyticsTotalProfitLineCharts.vue'
import AnalyticsTotalTransactions from '@/views/dashboards/analytics/AnalyticsTotalTransactions.vue'

const analyticsData = ref({
  low_stock: [],
  high_stock: [],
  income: {
    daily: 0,
    monthly: 0,
    yearly: 0,
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
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value)
}
</script>

<template>
  <VRow class="match-height">
    <!-- 👉 Congratulations / Welcome -->
    <VCol
      cols="12"
      md="8"
      lg="8"
    >
      <AnalyticsCongratulationsJohn />
    </VCol>

    <!-- 👉 Daily Income -->
    <VCol
      cols="12"
      sm="6"
      md="4"
      lg="4"
    >
      <VCard>
        <VCardText class="d-flex align-center justify-space-between pb-4">
          <div>
            <h6 class="text-h6 font-weight-medium mb-1">
              Pendapatan Hari Ini
            </h6>
            <h4 class="text-h4 text-primary">
              {{ formatCurrency(analyticsData.income.daily) }}
            </h4>
          </div>
          <VAvatar
            color="primary"
            variant="tonal"
            rounded
            size="42"
          >
            <VIcon
              icon="ri-money-dollar-circle-line"
              size="26"
            />
          </VAvatar>
        </VCardText>
      </VCard>
      
      <VCard class="mt-4">
        <VCardText class="d-flex align-center justify-space-between pb-4">
          <div>
            <h6 class="text-h6 font-weight-medium mb-1">
              Pendapatan Bulan Ini
            </h6>
            <h4 class="text-h4 text-success">
              {{ formatCurrency(analyticsData.income.monthly) }}
            </h4>
          </div>
          <VAvatar
            color="success"
            variant="tonal"
            rounded
            size="42"
          >
            <VIcon
              icon="ri-wallet-3-line"
              size="26"
            />
          </VAvatar>
        </VCardText>
      </VCard>
    </VCol>

    <!-- 👉 Low Stock Table -->
    <VCol
      cols="12"
      md="6"
    >
      <VCard
        title="⚠️ Peringatan Stok Menipis"
        subtitle="Produk yang segera butuh restock"
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
                Semua stok produk aman.
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
                    <h6 class="text-h6 font-weight-medium user-list-name">
                      {{ item.product?.name }}
                    </h6>
                    <span class="text-sm text-medium-emphasis">{{ item.product?.sku }}</span>
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

    <!-- 👉 High Stock Table -->
    <VCol
      cols="12"
      md="6"
    >
      <VCard
        title="📦 Stok Terbanyak"
        subtitle="Produk dengan jumlah stok berlebih"
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
            <tr v-if="analyticsData.high_stock.length === 0">
              <td
                colspan="3"
                class="text-center text-medium-emphasis"
              >
                Tidak ada produk stok berlebih.
              </td>
            </tr>
            <tr
              v-for="item in analyticsData.high_stock"
              :key="item.id"
            >
              <td>
                <div class="d-flex align-center">
                  <VAvatar
                    variant="tonal"
                    color="success"
                    class="me-3"
                    size="34"
                  >
                    <VIcon
                      icon="ri-checkbox-multiple-blank-line"
                      size="20"
                    />
                  </VAvatar>
                  <div class="d-flex flex-column">
                    <h6 class="text-h6 font-weight-medium user-list-name">
                      {{ item.product?.name }}
                    </h6>
                    <span class="text-sm text-medium-emphasis">{{ item.product?.sku }}</span>
                  </div>
                </div>
              </td>
              <td class="text-center">
                {{ item.branch?.name }}
              </td>
              <td class="text-center font-weight-bold text-success">
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

    <!-- 👉 Expiring Batches Table -->
    <VCol
      cols="12"
      md="12"
    >
      <VCard
        title="⚠️ Peringatan Barang Mendekati Kadaluwarsa"
        subtitle="Batch produk yang akan expired dalam 30 hari"
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
                Sisa Stok
              </th>
              <th class="text-uppercase text-center">
                Tgl Kadaluwarsa
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="analyticsData.expiring_batches.length === 0">
              <td
                colspan="4"
                class="text-center text-medium-emphasis"
              >
                Tidak ada produk yang mendekati kadaluwarsa.
              </td>
            </tr>
            <tr
              v-for="item in analyticsData.expiring_batches"
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
                      icon="ri-time-line"
                      size="20"
                    />
                  </VAvatar>
                  <div class="d-flex flex-column">
                    <h6 class="text-h6 font-weight-medium user-list-name">
                      {{ item.product_branch?.product?.name }}
                    </h6>
                    <span class="text-sm text-medium-emphasis">Batch ID: #{{ item.id }} (Masuk: {{ new Date(item.entry_date).toLocaleDateString('id-ID') }})</span>
                  </div>
                </div>
              </td>
              <td class="text-center">
                {{ item.product_branch?.branch?.name }}
              </td>
              <td class="text-center font-weight-bold">
                {{ item.qty }}
              </td>
              <td class="text-center font-weight-bold text-error">
                {{ new Date(item.expiration_date).toLocaleDateString('id-ID') }}
              </td>
            </tr>
          </tbody>
        </VTable>
        <VDivider />
        <VCardActions>
          <VBtn block variant="text" to="/laporan/stok-aging" color="primary">Buka Laporan Analisis Stok</VBtn>
        </VCardActions>
      </VCard>
    </VCol>

    <!-- 👉 Dead Stock Table (FIFO) -->
    <VCol
      cols="12"
      md="6"
    >
      <VCard
        title="🐌 Peringatan Dead Stock (FIFO)"
        subtitle="Stok lama mengendap > 90 hari"
      >
        <VTable class="text-no-wrap">
          <thead>
            <tr>
              <th class="text-uppercase">Produk</th>
              <th class="text-uppercase text-center">Cabang</th>
              <th class="text-uppercase text-center">Tgl Masuk</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="analyticsData.dead_stock && analyticsData.dead_stock.length === 0">
              <td colspan="3" class="text-center text-medium-emphasis">
                Tidak ada stok usang yang terdeteksi.
              </td>
            </tr>
            <tr v-for="item in analyticsData.dead_stock" :key="item.id">
              <td>
                <div class="d-flex flex-column">
                  <h6 class="text-h6 font-weight-medium">{{ item.product_branch?.product?.name }}</h6>
                  <span class="text-sm text-medium-emphasis">Sisa: {{ item.qty }} pcs</span>
                </div>
              </td>
              <td class="text-center">{{ item.product_branch?.branch?.name }}</td>
              <td class="text-center font-weight-bold text-error">
                {{ new Date(item.entry_date).toLocaleDateString('id-ID') }}
              </td>
            </tr>
          </tbody>
        </VTable>
        <VDivider />
        <VCardActions>
          <VBtn block variant="text" to="/laporan/stok-aging" color="primary">Analisis Selengkapnya</VBtn>
        </VCardActions>
      </VCard>
    </VCol>

    <!-- 👉 New Stock Table (LIFO) -->
    <VCol
      cols="12"
      md="6"
    >
      <VCard
        title="✨ Stok Baru Masuk (LIFO)"
        subtitle="Batch produk yang baru ditambahkan"
      >
        <VTable class="text-no-wrap">
          <thead>
            <tr>
              <th class="text-uppercase">Produk</th>
              <th class="text-uppercase text-center">Cabang</th>
              <th class="text-uppercase text-center">Tgl Masuk</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="analyticsData.new_stock && analyticsData.new_stock.length === 0">
              <td colspan="3" class="text-center text-medium-emphasis">
                Belum ada produk baru.
              </td>
            </tr>
            <tr v-for="item in analyticsData.new_stock" :key="item.id">
              <td>
                <div class="d-flex flex-column">
                  <h6 class="text-h6 font-weight-medium">{{ item.product_branch?.product?.name }}</h6>
                  <span class="text-sm text-medium-emphasis">Sisa: {{ item.qty }} pcs</span>
                </div>
              </td>
              <td class="text-center">{{ item.product_branch?.branch?.name }}</td>
              <td class="text-center font-weight-bold text-success">
                {{ new Date(item.entry_date).toLocaleDateString('id-ID') }}
              </td>
            </tr>
          </tbody>
        </VTable>
        <VDivider />
        <VCardActions>
          <VBtn block variant="text" to="/laporan/stok-aging" color="primary">Analisis Selengkapnya</VBtn>
        </VCardActions>
      </VCard>
    </VCol>
    
    <!-- 👉 Row of 4 stats: Piutang, Retur, Yearly Income, Opname -->
    <VCol cols="12">
      <VRow>
        <!-- 👉 Outstanding Receivables -->
        <VCol
          cols="12"
          md="3"
        >
          <VCard>
            <VCardText class="d-flex align-center justify-space-between pb-4">
              <div>
                <h6 class="text-h6 font-weight-medium mb-1">
                  Total Piutang
                </h6>
                <h4 class="text-h4 text-warning">
                  {{ formatCurrency(analyticsData.receivables.outstanding) }}
                </h4>
              </div>
              <VAvatar
                color="warning"
                variant="tonal"
                rounded
                size="42"
              >
                <VIcon
                  icon="ri-hand-coin-line"
                  size="26"
                />
              </VAvatar>
            </VCardText>
          </VCard>
        </VCol>

        <!-- 👉 Monthly Returns -->
        <VCol
          cols="12"
          md="3"
        >
          <VCard>
            <VCardText class="d-flex align-center justify-space-between pb-4">
              <div>
                <h6 class="text-h6 font-weight-medium mb-1">
                  Retur Bulan Ini
                </h6>
                <h4 class="text-h4 text-error">
                  {{ formatCurrency(analyticsData.returns.monthly) }}
                </h4>
              </div>
              <VAvatar
                color="error"
                variant="tonal"
                rounded
                size="42"
              >
                <VIcon
                  icon="ri-arrow-go-back-line"
                  size="26"
                />
              </VAvatar>
            </VCardText>
          </VCard>
        </VCol>
        
        <!-- 👉 Total Yearly Income -->
        <VCol
          cols="12"
          md="3"
        >
          <VCard>
            <VCardText class="d-flex align-center justify-space-between pb-4">
              <div>
                <h6 class="text-h6 font-weight-medium mb-1">
                  Pendapatan Tahun Ini
                </h6>
                <h4 class="text-h4 text-info">
                  {{ formatCurrency(analyticsData.income.yearly) }}
                </h4>
              </div>
              <VAvatar
                color="info"
                variant="tonal"
                rounded
                size="42"
              >
                <VIcon
                  icon="ri-bank-line"
                  size="26"
                />
              </VAvatar>
            </VCardText>
          </VCard>
        </VCol>

        <!-- 👉 Stock Opname Selisih -->
        <VCol
          cols="12"
          md="3"
        >
          <VCard v-if="analyticsData.latest_opname">
            <VCardText class="d-flex align-center justify-space-between pb-4">
              <div>
                <h6 class="text-h6 font-weight-medium mb-1">
                  Selisih Opname
                </h6>
                <h4 class="text-h4 text-secondary">
                  {{ analyticsData.latest_opname.total_discrepancy }}
                </h4>
                <div class="text-caption text-medium-emphasis">
                  {{ new Date(analyticsData.latest_opname.date).toLocaleDateString('id-ID') }}
                </div>
              </div>
              <VAvatar
                color="secondary"
                variant="tonal"
                rounded
                size="42"
              >
                <VIcon
                  icon="ri-survey-line"
                  size="26"
                />
              </VAvatar>
            </VCardText>
          </VCard>
          <VCard v-else>
            <VCardText class="d-flex align-center justify-space-between pb-4">
              <div>
                <h6 class="text-h6 font-weight-medium mb-1">
                  Selisih Opname
                </h6>
                <h4 class="text-h4 text-secondary">
                  -
                </h4>
              </div>
              <VAvatar
                color="secondary"
                variant="tonal"
                rounded
                size="42"
              >
                <VIcon
                  icon="ri-survey-line"
                  size="26"
                />
              </VAvatar>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core-scss/template/libs/apex-chart.scss";
</style>

<route lang="yaml">
meta:
  action: read
  subject: Dashboard Analytics
</route>
