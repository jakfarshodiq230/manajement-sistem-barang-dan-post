<script setup>
import { ref, onMounted } from 'vue'
import { $api } from '@/utils/api'

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
  }).format(value)
}
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

    <!-- Row of 4 Stats: Piutang, Retur, Yearly Income, Opname -->
    <VRow class="mb-4">
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-warning">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-warning font-weight-bold">TOTAL PIUTANG AKTIF</div>
              <div class="text-h4 font-weight-bold text-warning mt-1">{{ formatCurrency(analyticsData.receivables.outstanding) }}</div>
            </div>
            <VAvatar color="warning" variant="tonal" rounded size="46">
              <VIcon icon="ri-hand-coin-line" size="26" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Sisa tagihan belum lunas</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-error">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-error font-weight-bold">RETUR BULAN INI</div>
              <div class="text-h4 font-weight-bold text-error mt-1">{{ formatCurrency(analyticsData.returns.monthly) }}</div>
            </div>
            <VAvatar color="error" variant="tonal" rounded size="46">
              <VIcon icon="ri-arrow-go-back-line" size="26" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Pengembalian barang</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-info">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-info font-weight-bold">PENDAPATAN TAHUN INI</div>
              <div class="text-h4 font-weight-bold text-info mt-1">{{ formatCurrency(analyticsData.income.yearly) }}</div>
            </div>
            <VAvatar color="info" variant="tonal" rounded size="46">
              <VIcon icon="ri-bank-line" size="26" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Akumulasi omzet tahun berjalan</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-secondary">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-secondary font-weight-bold">SELISIH OPNAME TERAKHIR</div>
              <div class="text-h4 font-weight-bold text-secondary mt-1">
                {{ analyticsData.latest_opname ? analyticsData.latest_opname.total_discrepancy : '0' }} <span class="text-caption text-medium-emphasis">Pcs</span>
              </div>
            </div>
            <VAvatar color="secondary" variant="tonal" rounded size="46">
              <VIcon icon="ri-survey-line" size="26" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">
            {{ analyticsData.latest_opname ? ('Audit ' + new Date(analyticsData.latest_opname.date).toLocaleDateString('id-ID')) : 'Belum ada data opname' }}
          </div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Row: Low Stock & Expiring Batches -->
    <VRow class="mb-4">
      <!-- Low Stock Table -->
      <VCol cols="12" md="6">
        <VCard elevation="2" class="h-100 d-flex flex-column">
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

          <VTable class="text-no-wrap" hover density="comfortable">
            <thead>
              <tr class="bg-grey-50">
                <th class="text-left font-weight-bold">PRODUK</th>
                <th class="text-center font-weight-bold">CABANG</th>
                <th class="text-center font-weight-bold">SISA STOK</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="analyticsData.low_stock.length === 0">
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
        <VCard elevation="2" class="h-100 d-flex flex-column">
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
              <tr v-if="analyticsData.expiring_batches.length === 0">
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
        <VCard elevation="2" class="h-100 d-flex flex-column">
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

          <VTable class="text-no-wrap" hover density="comfortable">
            <thead>
              <tr class="bg-grey-50">
                <th class="text-left font-weight-bold">PRODUK</th>
                <th class="text-center font-weight-bold">CABANG</th>
                <th class="text-center font-weight-bold">TGL MASUK</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="analyticsData.dead_stock && analyticsData.dead_stock.length === 0">
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
        <VCard elevation="2" class="h-100 d-flex flex-column">
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

          <VTable class="text-no-wrap" hover density="comfortable">
            <thead>
              <tr class="bg-grey-50">
                <th class="text-left font-weight-bold">PRODUK</th>
                <th class="text-center font-weight-bold">CABANG</th>
                <th class="text-center font-weight-bold">TGL MASUK</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="analyticsData.new_stock && analyticsData.new_stock.length === 0">
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
