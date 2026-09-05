<script setup>
import { ref, onMounted, computed, watch } from 'vue'

definePage({
  meta: {
    action: 'read',
    subject: 'Keuangan',
  },
})

const isLoading = ref(true)
const selectedBranch = ref('all')
const branches = ref([])
const bankAccountsList = ref([])

const stats = ref({
  monthly_revenue: 0,
  monthly_profit: 0,
  monthly_expense: 0,
  total_bank_balance: 0,
  total_receivables: 0,
  total_payables: 0,
  remaining_capital: 0,
  payback_percentage: 0,
})

const formatCurrency = val => {
  if (val === null || val === undefined || isNaN(val)) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val || 0)
}

const fetchBranches = async () => {
  try {
    const res = await $api('/apps/branches', { params: { itemsPerPage: 100 } })
    branches.value = res.branches || res.data || (Array.isArray(res) ? res : [])
  } catch (e) {
    console.error('Error fetching branches:', e)
  }
}

const fetchFinancialOverview = async () => {
  isLoading.value = true
  try {
    const params = {}
    if (selectedBranch.value && selectedBranch.value !== 'all') {
      params.branch_id = selectedBranch.value
    }

    const [profitRes, bankRes, payableRes, receivableRes, capitalRes] = await Promise.allSettled([
      $api('/apps/dashboards/profit', { params }),
      $api('/apps/bank-accounts', { params: { is_active: true } }),
      $api('/apps/payables', { params }),
      $api('/apps/receivables', { params }),
      $api('/apps/branch-capitals/summary', { params }),
    ])

    if (profitRes.status === 'fulfilled' && profitRes.value?.data) {
      const pData = profitRes.value.data
      stats.value.monthly_revenue = pData.revenue_this_month || 0
      stats.value.monthly_profit = pData.profit_this_month || 0
      stats.value.monthly_expense = pData.expense_this_month || 0
    }

    if (bankRes.status === 'fulfilled') {
      const bData = bankRes.value?.data || (Array.isArray(bankRes.value) ? bankRes.value : [])
      bankAccountsList.value = bData
      const totalB = bData.reduce((acc, curr) => acc + (Number(curr.current_balance) || 0), 0)
      stats.value.total_bank_balance = totalB
    }

    if (payableRes.status === 'fulfilled') {
      const pSummary = payableRes.value?.summary || payableRes.value?.meta
      stats.value.total_payables = pSummary?.total_remaining || pSummary?.total_outstanding || 0
    }

    if (receivableRes.status === 'fulfilled') {
      const rSummary = receivableRes.value?.summary || receivableRes.value?.meta
      stats.value.total_receivables = rSummary?.total_remaining || rSummary?.total_outstanding || 0
    }

    if (capitalRes.status === 'fulfilled' && capitalRes.value) {
      const cData = capitalRes.value
      stats.value.remaining_capital = cData.remaining_capital || 0
      stats.value.payback_percentage = cData.payback_percentage || 0
    }
  } catch (error) {
    console.error('Failed to load financial overview:', error)
  } finally {
    isLoading.value = false
  }
}

watch(selectedBranch, () => {
  fetchFinancialOverview()
})

onMounted(async () => {
  await fetchBranches()
  await fetchFinancialOverview()
})

const financialModules = [
  {
    title: 'Sistem Akuntansi & Buku Besar',
    subtitle: 'Bagan Akun (COA), Jurnal Umum otomatis, Buku Besar (General Ledger), dan Laporan Neraca Keuangan baku.',
    icon: 'ri-book-read-line',
    color: 'primary',
    to: '/akuntansi',
    badge: 'Akuntansi PSAK',
    actionText: 'Buka Pusat Akuntansi',
  },
  {
    title: 'Rekap Keuangan & Laba Rugi',
    subtitle: 'Laporan komprehensif omzet, HPP modal, pengeluaran kas kecil, dan laba bersih resmi toko per periode.',
    icon: 'ri-file-chart-line',
    color: 'success',
    to: '/audit/rekap',
    badge: 'Laba Rugi',
    actionText: 'Buka Rekap Keuangan',
  },
  {
    title: 'Dashboard Laba & Keuntungan',
    subtitle: 'Grafik visual margin keuntungan, tren omzet penjualan harian/bulanan, dan performa kategori produk.',
    icon: 'ri-line-chart-line',
    color: 'success',
    to: '/dashboards/keuntungan',
    badge: 'Analytics',
    actionText: 'Lihat Analisis Laba',
  },
  {
    title: 'Rekening Bank & Mutasi',
    subtitle: 'Manajemen saldo rekening bank (BCA, Mandiri, BRI, QRIS), pemantauan arus transaksi, dan mutasi debit/kredit.',
    icon: 'ri-bank-card-line',
    color: 'info',
    to: '/bank-accounts',
    badge: 'Multi-Bank',
    actionText: 'Kelola Rekening',
  },
  {
    title: 'Modal & ROI Cabang',
    subtitle: 'Penyertaan modal awal/tambahan oleh Owner ke cabang serta monitoring cicilan setoran pengembalian modal.',
    icon: 'ri-hand-coin-line',
    color: 'warning',
    to: '/apps/branch-capitals',
    badge: 'Modal Usaha',
    actionText: 'Kelola Modal Cabang',
  },
  {
    title: 'Hutang Usaha (Payables)',
    subtitle: 'Pencatatan tagihan pengadaan barang supplier, pemantauan jatuh tempo, pembayaran cicilan, dan kuitansi.',
    icon: 'ri-money-dollar-circle-line',
    color: 'error',
    to: '/payables',
    badge: 'Hutang Supplier',
    actionText: 'Kelola Tagihan Hutang',
  },
  {
    title: 'Piutang Pelanggan (Receivables)',
    subtitle: 'Daftar piutang kasbon tempo transaksi pelanggan, pelacakan tanggal jatuh tempo, dan riwayat pembayaran cicilan.',
    icon: 'ri-calendar-todo-line',
    color: 'secondary',
    to: '/receivables',
    badge: 'Piutang Bon',
    actionText: 'Kelola Piutang',
  },
  {
    title: 'Kas Kecil (Petty Cash)',
    subtitle: 'Pencatatan pengeluaran operasional cabang harian dengan lampiran nota kuitansi fisik dan verifikasi supervisor.',
    icon: 'ri-wallet-3-line',
    color: 'warning',
    to: '/kas-kecil',
    badge: 'Kas Keluar',
    actionText: 'Catat Kas Kecil',
  },
  {
    title: 'Audit & Closing Kasir',
    subtitle: 'Rekonsiliasi kas fisik laci kasir vs bon sistem POS harian, setoran cicilan modal ke owner, dan mutasi bank.',
    icon: 'ri-calculator-line',
    color: 'primary',
    to: '/audit/closing-harian',
    badge: 'Closing Shift',
    actionText: 'Rekonsiliasi Kasir',
  },
  {
    title: 'Pusat Laporan & Ekspor',
    subtitle: 'Katalog laporan lengkap: transaksi penjualan, mutasi stok, audit stok opname, dan ekspor formal ke Excel/PDF.',
    icon: 'ri-folder-chart-line',
    color: 'info',
    to: '/laporan',
    badge: 'Pusat Laporan',
    actionText: 'Lihat Semua Laporan',
  },
]
</script>

<template>
  <div>
    <!-- Header Page -->
    <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold mb-1 text-high-emphasis">
          Pusat Keuangan & Kas
        </h4>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Ringkasan arus kas terpadu, saldo rekening bank, performa laba rugi, dan pengelolaan hutang piutang usaha.
        </p>
      </div>

      <div class="d-flex flex-wrap align-center gap-3">
        <!-- Branch Filter -->
        <VSelect
          v-if="branches.length > 1"
          v-model="selectedBranch"
          :items="[{ id: 'all', name: 'Semua Cabang' }, ...branches]"
          item-title="name"
          item-value="id"
          density="compact"
          variant="outlined"
          prepend-inner-icon="ri-store-2-line"
          style="min-width: 180px;"
          hide-details
        />

        <VBtn
          color="primary"
          prepend-icon="ri-file-chart-line"
          to="/audit/rekap"
        >
          Rekap Laba Rugi
        </VBtn>
        <VBtn
          color="secondary"
          variant="tonal"
          prepend-icon="ri-bank-card-line"
          to="/bank-accounts"
        >
          Rekening Bank
        </VBtn>
      </div>
    </div>

    <!-- Ringkasan Eksekutif Keuangan Real-Time (KPI Cards) -->
    <VRow class="mb-6 match-height">
      <!-- 1. Total Saldo Bank -->
      <VCol cols="12" sm="6" md="4" lg="2">
        <VCard elevation="1" class="h-100 pa-4 border rounded-lg">
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Saldo Bank</span>
            <VAvatar color="primary" variant="tonal" size="36" rounded>
              <VIcon icon="ri-bank-card-line" size="20" />
            </VAvatar>
          </div>
          <div class="text-h6 font-weight-bold text-primary mb-1">
            {{ formatCurrency(stats.total_bank_balance) }}
          </div>
          <div class="text-caption text-medium-emphasis">
            Total Rekening & QRIS
          </div>
        </VCard>
      </VCol>

      <!-- 2. Omzet Bulan Ini -->
      <VCol cols="12" sm="6" md="4" lg="2">
        <VCard elevation="1" class="h-100 pa-4 border rounded-lg">
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Omzet Bulan Ini</span>
            <VAvatar color="info" variant="tonal" size="36" rounded>
              <VIcon icon="ri-shopping-cart-2-line" size="20" />
            </VAvatar>
          </div>
          <div class="text-h6 font-weight-bold text-info mb-1">
            {{ formatCurrency(stats.monthly_revenue) }}
          </div>
          <div class="text-caption text-medium-emphasis">
            Penjualan Kasir POS
          </div>
        </VCard>
      </VCol>

      <!-- 3. Estimasi Laba Bersih -->
      <VCol cols="12" sm="6" md="4" lg="2">
        <VCard elevation="1" class="h-100 pa-4 border rounded-lg">
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Laba Bersih</span>
            <VAvatar color="success" variant="tonal" size="36" rounded>
              <VIcon icon="ri-line-chart-line" size="20" />
            </VAvatar>
          </div>
          <div class="text-h6 font-weight-bold text-success mb-1">
            {{ formatCurrency(stats.monthly_profit) }}
          </div>
          <div class="text-caption text-medium-emphasis">
            Estimasi Laba Bulan Ini
          </div>
        </VCard>
      </VCol>

      <!-- 4. Piutang Pelanggan -->
      <VCol cols="12" sm="6" md="4" lg="2">
        <VCard elevation="1" class="h-100 pa-4 border rounded-lg">
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Piutang Pelanggan</span>
            <VAvatar color="secondary" variant="tonal" size="36" rounded>
              <VIcon icon="ri-hand-coin-line" size="20" />
            </VAvatar>
          </div>
          <div class="text-h6 font-weight-bold text-secondary mb-1">
            {{ formatCurrency(stats.total_receivables) }}
          </div>
          <div class="text-caption text-medium-emphasis">
            Kasbon Tempo Belum Lunas
          </div>
        </VCard>
      </VCol>

      <!-- 5. Hutang Supplier -->
      <VCol cols="12" sm="6" md="4" lg="2">
        <VCard elevation="1" class="h-100 pa-4 border rounded-lg">
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Hutang Supplier</span>
            <VAvatar color="error" variant="tonal" size="36" rounded>
              <VIcon icon="ri-money-dollar-circle-line" size="20" />
            </VAvatar>
          </div>
          <div class="text-h6 font-weight-bold text-error mb-1">
            {{ formatCurrency(stats.total_payables) }}
          </div>
          <div class="text-caption text-medium-emphasis">
            Tagihan Nota Pengadaan
          </div>
        </VCard>
      </VCol>

      <!-- 6. Sisa Modal Cabang -->
      <VCol cols="12" sm="6" md="4" lg="2">
        <VCard elevation="1" class="h-100 pa-4 border rounded-lg">
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Sisa Modal</span>
            <VAvatar color="warning" variant="tonal" size="36" rounded>
              <VIcon icon="ri-wallet-3-line" size="20" />
            </VAvatar>
          </div>
          <div class="text-h6 font-weight-bold text-warning mb-1">
            {{ formatCurrency(stats.remaining_capital) }}
          </div>
          <div class="text-caption text-medium-emphasis">
            Modal Tertanam di Toko
          </div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Grid Menu Modul Keuangan Terpadu -->
    <div class="d-flex align-center justify-space-between mb-4">
      <h5 class="text-h5 font-weight-bold d-flex align-center gap-2 mb-0">
        <VIcon icon="ri-apps-2-line" color="primary" size="24" />
        Modul & Menu Keuangan
      </h5>
    </div>

    <VRow class="match-height mb-6">
      <VCol
        v-for="mod in financialModules"
        :key="mod.title"
        cols="12"
        sm="6"
        md="4"
      >
        <VCard
          class="h-100 d-flex flex-column border rounded-lg module-card"
          elevation="1"
          :to="mod.to"
        >
          <VCardText class="pa-5 flex-grow-1">
            <div class="d-flex justify-space-between align-center mb-4">
              <VAvatar :color="mod.color" variant="tonal" size="44" rounded>
                <VIcon :icon="mod.icon" size="24" />
              </VAvatar>
              <VChip :color="mod.color" size="small" variant="tonal" class="font-weight-medium">
                {{ mod.badge }}
              </VChip>
            </div>

            <h6 class="text-h6 font-weight-bold mb-2">
              {{ mod.title }}
            </h6>
            <p class="text-body-2 text-medium-emphasis mb-0" style="line-height: 1.55;">
              {{ mod.subtitle }}
            </p>
          </VCardText>

          <VDivider />

          <VCardActions class="pa-3 px-4 bg-var-theme-surface">
            <VBtn
              :color="mod.color"
              variant="text"
              class="w-100 justify-space-between font-weight-semibold px-2"
              append-icon="ri-arrow-right-line"
            >
              {{ mod.actionText }}
            </VBtn>
          </VCardActions>
        </VCard>
      </VCol>
    </VRow>

    <!-- Quick Overview: Rekening Bank Aktif & Pintasan Cepat -->
    <VRow class="match-height">
      <!-- Rekening Bank Terdaftar -->
      <VCol cols="12" md="7">
        <VCard class="h-100 border rounded-lg" elevation="1">
          <VCardItem class="pa-4 pb-2">
            <template #prepend>
              <VAvatar color="primary" variant="tonal" size="36" rounded class="me-2">
                <VIcon icon="ri-bank-card-line" size="20" />
              </VAvatar>
            </template>
            <VCardTitle class="font-weight-bold">
              Rekening Bank & Kas Aktif
            </VCardTitle>
            <VCardSubtitle>
              Daftar rekening bank penerima & penyalur dana toko
            </VCardSubtitle>
            <template #append>
              <VBtn
                variant="text"
                color="primary"
                size="small"
                to="/bank-accounts"
                append-icon="ri-arrow-right-line"
              >
                Kelola
              </VBtn>
            </template>
          </VCardItem>

          <VCardText class="pa-4 pt-2">
            <VList class="py-0">
              <template v-if="bankAccountsList.length === 0">
                <div class="text-center py-6 text-medium-emphasis">
                  <VIcon icon="ri-inbox-line" size="32" class="mb-2 opacity-50" />
                  <div class="text-body-2">Belum ada rekening bank yang terdaftar.</div>
                </div>
              </template>
              <template v-else>
                <VListItem
                  v-for="(acc, idx) in bankAccountsList.slice(0, 4)"
                  :key="acc.id"
                  class="px-0 py-2"
                  :class="{ 'border-b': idx < Math.min(bankAccountsList.length, 4) - 1 }"
                >
                  <template #prepend>
                    <VAvatar
                      :color="acc.type === 'qris' ? 'success' : (acc.type === 'cash_drawer' ? 'warning' : 'primary')"
                      variant="tonal"
                      size="38"
                      rounded
                      class="me-3"
                    >
                      <VIcon :icon="acc.type === 'qris' ? 'ri-qr-code-line' : (acc.type === 'cash_drawer' ? 'ri-money-dollar-circle-line' : 'ri-bank-line')" size="20" />
                    </VAvatar>
                  </template>

                  <VListItemTitle class="font-weight-bold text-body-2">
                    {{ acc.bank_name }}
                    <VChip v-if="acc.is_default" size="x-small" color="primary" variant="tonal" class="ms-1 font-weight-medium">
                      Utama
                    </VChip>
                  </VListItemTitle>
                  <VListItemSubtitle class="text-caption">
                    {{ acc.account_number || '-' }} • {{ acc.account_name || 'Toko' }}
                  </VListItemSubtitle>

                  <template #append>
                    <div class="text-end">
                      <div class="font-weight-bold text-body-2 text-primary font-mono">
                        {{ formatCurrency(acc.current_balance) }}
                      </div>
                      <span class="text-caption text-success font-weight-medium">Aktif</span>
                    </div>
                  </template>
                </VListItem>
              </template>
            </VList>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Pintasan Aksi Cepat -->
      <VCol cols="12" md="5">
        <VCard class="h-100 border rounded-lg" elevation="1">
          <VCardItem class="pa-4 pb-2">
            <template #prepend>
              <VAvatar color="warning" variant="tonal" size="36" rounded class="me-2">
                <VIcon icon="ri-flashlight-line" size="20" />
              </VAvatar>
            </template>
            <VCardTitle class="font-weight-bold">
              Pintasan Aksi Keuangan
            </VCardTitle>
            <VCardSubtitle>
              Akses cepat pencatatan dan monitoring kas
            </VCardSubtitle>
          </VCardItem>

          <VCardText class="pa-4 pt-2">
            <div class="d-flex flex-column gap-2">
              <VBtn
                variant="tonal"
                color="warning"
                block
                class="justify-start text-none py-3"
                prepend-icon="ri-wallet-3-line"
                to="/kas-kecil"
              >
                Catat Pengeluaran Kas Kecil Toko
              </VBtn>
              <VBtn
                variant="tonal"
                color="primary"
                block
                class="justify-start text-none py-3"
                prepend-icon="ri-calculator-line"
                to="/audit/closing-harian"
              >
                Closing Shift & Rekonsiliasi Kasir
              </VBtn>
              <VBtn
                variant="tonal"
                color="info"
                block
                class="justify-start text-none py-3"
                prepend-icon="ri-hand-coin-line"
                to="/apps/branch-capitals"
              >
                Penyertaan / Setoran Modal Cabang
              </VBtn>
              <VBtn
                variant="tonal"
                color="error"
                block
                class="justify-start text-none py-3"
                prepend-icon="ri-money-dollar-circle-line"
                to="/payables"
              >
                Pembayaran Tagihan Hutang Supplier
              </VBtn>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<style scoped>
.module-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.module-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 18px rgba(var(--v-theme-primary), 0.12);
  border-color: rgba(var(--v-theme-primary), 0.4) !important;
}
</style>
