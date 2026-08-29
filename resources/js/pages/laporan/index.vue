<script setup>
import { ref, onMounted } from 'vue'

const isLoading = ref(false)
const stats = ref({
  total_revenue: 0,
  total_sales_count: 0,
  total_bank_balance: 0,
  total_receivables: 0,
  total_payables: 0,
  total_petty_cash: 0,
})

const reportCategories = [
  {
    title: 'Laporan Penjualan & Transaksi',
    description: 'Rekap riwayat transaksi bon kasir, omzet harian/bulanan, rincian per metode bayar, dan ekspor data ke Excel.',
    icon: 'ri-shopping-cart-2-line',
    color: 'primary',
    to: '/transaksi',
    badge: 'Real-time',
    badgeColor: 'success',
  },
  {
    title: 'Laporan Rekap & Mutasi Bank',
    description: 'Pelacakan saldo, omzet bulanan/tahunan per rekening bank (BCA, Mandiri, BRI, QRIS), dan mutasi bon penerimaan per cabang.',
    icon: 'ri-bank-card-line',
    color: 'info',
    to: '/bank-accounts',
    badge: 'Multi-Bank',
    badgeColor: 'primary',
  },
  {
    title: 'Laporan Audit & Closing Kasir',
    description: 'Rekonsiliasi kas harian kasir, selisih fisik vs sistem, setoran cicilan modal ke owner, dan rincian omzet bank.',
    icon: 'ri-calculator-line',
    color: 'warning',
    to: '/audit/closing-harian',
    badge: 'Audit Harian',
    badgeColor: 'warning',
  },
  {
    title: 'Laporan Piutang Pelanggan',
    description: 'Daftar piutang tempo kasbon pelanggan, monitoring jatuh tempo, riwayat cicilan, dan status pelunasan bon.',
    icon: 'ri-hand-coin-line',
    color: 'error',
    to: '/receivables',
    badge: 'Piutang',
    badgeColor: 'error',
  },
  {
    title: 'Laporan Hutang Pembelian (PO)',
    description: 'Daftar hutang pengadaan ke supplier per periode nota, progres pembayaran, dan checklist pelunasan item barang.',
    icon: 'ri-money-dollar-circle-line',
    color: 'secondary',
    to: '/payables',
    badge: 'Hutang Dagang',
    badgeColor: 'secondary',
  },
  {
    title: 'Laporan Stok Global Toko',
    description: 'Rekap kuantitas stok barang di seluruh cabang dan gudang pusat, nilai valuasi aset stok, serta batas minimum.',
    icon: 'ri-archive-line',
    color: 'success',
    to: '/laporan/stok-global',
    badge: 'Inventori',
    badgeColor: 'info',
  },
  {
    title: 'Laporan Umur & Aging Stok',
    description: 'Analisis umur stok barang FIFO/LIFO, identifikasi dead stock (>90 hari), dan peringatan barang mendekati kadaluarsa.',
    icon: 'ri-time-line',
    color: 'warning',
    to: '/laporan/stok-aging',
    badge: 'FIFO / LIFO',
    badgeColor: 'warning',
  },
  {
    title: 'Laporan Kas Kecil (Petty Cash)',
    description: 'Catatan pengeluaran operasional toko cabang harian, nota bukti struk pengeluaran, dan verifikasi supervisor.',
    icon: 'ri-wallet-3-line',
    color: 'primary',
    to: '/kas-kecil',
    badge: 'Operasional',
    badgeColor: 'primary',
  },
  {
    title: 'Laporan Stock Opname & Selisih',
    description: 'Hasil cycle counting fisik berkala, investigasi selisih stok sistem vs fisik, dan approval penyesuaian stok.',
    icon: 'ri-file-list-3-line',
    color: 'info',
    to: '/audit/stock-opname',
    badge: 'Stock Opname',
    badgeColor: 'info',
  },
]

const formatRupiah = val => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0)
}

const fetchDashboardStats = async () => {
  isLoading.value = true
  try {
    const res = await $api('/analytics')
    if (res.data) {
      stats.value.total_revenue = res.data.income?.monthly || 0
      stats.value.total_receivables = res.data.receivables?.outstanding || 0
    }
    const bankRes = await $api('/apps/bank-accounts')
    if (bankRes.summary) {
      stats.value.total_bank_balance = bankRes.summary.total_balance || 0
    }
  } catch (e) {
    console.error('Failed to fetch report summary:', e)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchDashboardStats()
})
</script>

<template>
  <div>
    <!-- Page Header Banner -->
    <div class="mb-6 pa-6 rounded-xl bg-gradient-header border shadow-sm">
      <div class="d-flex align-center justify-space-between flex-wrap gap-4">
        <div class="d-flex align-center gap-4">
          <VAvatar color="primary" size="56" variant="tonal" class="rounded-xl shadow-xs">
            <VIcon icon="ri-file-chart-line" size="32" />
          </VAvatar>
          <div>
            <h3 class="text-h4 font-weight-bold text-high-emphasis mb-1">
              Pusat Laporan & Analitik (Reports Hub)
            </h3>
            <p class="text-body-1 text-medium-emphasis mb-0">
              Pusat rekap laporan finansial, mutasi rekening bank, inventori barang, closing audit, dan piutang PT. DUMAI.
            </p>
          </div>
        </div>

        <div class="d-flex gap-2">
          <VBtn
            color="primary"
            prepend-icon="ri-refresh-line"
            variant="tonal"
            :loading="isLoading"
            @click="fetchDashboardStats"
          >
            Segarkan Data
          </VBtn>
        </div>
      </div>
    </div>

    <!-- Quick Financial KPI Highlight Cards -->
    <VRow class="mb-6">
      <VCol cols="12" sm="6" md="4">
        <VCard class="pa-4 rounded-xl border shadow-xs h-100 kpi-hover">
          <div class="d-flex justify-space-between align-center mb-2">
            <span class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Total Omzet Bulan Ini</span>
            <VAvatar color="primary" variant="tonal" size="36" class="rounded-lg">
              <VIcon icon="ri-money-dollar-circle-line" size="20" />
            </VAvatar>
          </div>
          <div class="text-h5 font-weight-bold text-primary font-mono">
            {{ formatRupiah(stats.total_revenue) }}
          </div>
          <div class="text-caption text-medium-emphasis mt-1">
            Penjualan kasir seluruh cabang
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="4">
        <VCard class="pa-4 rounded-xl border shadow-xs h-100 kpi-hover">
          <div class="d-flex justify-space-between align-center mb-2">
            <span class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Total Saldo di Bank</span>
            <VAvatar color="info" variant="tonal" size="36" class="rounded-lg">
              <VIcon icon="ri-bank-card-line" size="20" />
            </VAvatar>
          </div>
          <div class="text-h5 font-weight-bold text-info font-mono">
            {{ formatRupiah(stats.total_bank_balance) }}
          </div>
          <div class="text-caption text-medium-emphasis mt-1">
            Akumulasi saldo seluruh rekening aktif
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="4">
        <VCard class="pa-4 rounded-xl border shadow-xs h-100 kpi-hover">
          <div class="d-flex justify-space-between align-center mb-2">
            <span class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Sisa Piutang Pelanggan</span>
            <VAvatar color="warning" variant="tonal" size="36" class="rounded-lg">
              <VIcon icon="ri-hand-coin-line" size="20" />
            </VAvatar>
          </div>
          <div class="text-h5 font-weight-bold text-warning font-mono">
            {{ formatRupiah(stats.total_receivables) }}
          </div>
          <div class="text-caption text-medium-emphasis mt-1">
            Tagihan tempo belum terlunasi
          </div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Report Modules Grid -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div class="text-h6 font-weight-bold text-high-emphasis">
        Daftar Modul Laporan Sistem
      </div>
      <span class="text-caption text-medium-emphasis">Klik pada salah satu modul untuk membuka laporan lengkap</span>
    </div>

    <VRow>
      <VCol
        v-for="(rep, idx) in reportCategories"
        :key="idx"
        cols="12"
        sm="6"
        lg="4"
      >
        <VCard
          class="pa-5 rounded-xl border h-100 report-card d-flex flex-column justify-space-between"
          :to="rep.to"
        >
          <div>
            <div class="d-flex justify-space-between align-center mb-3">
              <VAvatar
                :color="rep.color"
                variant="tonal"
                size="46"
                class="rounded-xl"
              >
                <VIcon :icon="rep.icon" size="24" />
              </VAvatar>
              <VChip
                :color="rep.badgeColor"
                size="x-small"
                variant="flat"
                class="font-weight-bold"
              >
                {{ rep.badge }}
              </VChip>
            </div>

            <h4 class="text-subtitle-1 font-weight-bold text-high-emphasis mb-1">
              {{ rep.title }}
            </h4>
            <p class="text-caption text-medium-emphasis mb-4" style="line-height: 1.5;">
              {{ rep.description }}
            </p>
          </div>

          <div class="d-flex align-center justify-space-between pt-3 border-t text-caption text-primary font-weight-bold">
            <span>Buka Laporan</span>
            <VIcon icon="ri-arrow-right-line" size="16" class="arrow-anim" />
          </div>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<style scoped>
.bg-gradient-header {
  background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.08) 0%, rgba(var(--v-theme-surface), 1) 100%);
}

.kpi-hover {
  transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.kpi-hover:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08) !important;
}

.report-card {
  cursor: pointer;
  transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.2s ease;
  text-decoration: none;
}

.report-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(var(--v-theme-primary), 0.12) !important;
  border-color: rgba(var(--v-theme-primary), 0.4) !important;
}

.report-card:hover .arrow-anim {
  transform: translateX(4px);
  transition: transform 0.2s ease;
}
</style>
