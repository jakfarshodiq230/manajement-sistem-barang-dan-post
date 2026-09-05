<script setup>
import { ref, onMounted, computed, watch } from 'vue'

definePage({
  meta: {
    action: 'read',
    subject: 'Akuntansi',
  },
})

const isLoading = ref(true)
const isSyncing = ref(false)
const syncDialog = ref(false)
const syncResult = ref(null)
const selectedBranch = ref('all')
const branches = ref([])

const stats = ref({
  total_assets: 0,
  total_liabilities: 0,
  total_equity: 0,
  total_revenue: 0,
  total_cogs: 0,
  total_expenses: 0,
  net_profit: 0,
  total_journals_count: 0,
  is_balance_sheet_balanced: true,
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

const fetchOverview = async () => {
  isLoading.value = true
  try {
    const params = {}
    if (selectedBranch.value && selectedBranch.value !== 'all') {
      params.branch_id = selectedBranch.value
    }
    const res = await $api('/apps/accounting/overview', { params })
    if (res.success && res.data) {
      stats.value = res.data
    }
  } catch (e) {
    console.error('Failed to load accounting overview:', e)
  } finally {
    isLoading.value = false
  }
}

const triggerSync = async () => {
  isSyncing.value = true
  try {
    const res = await $api('/apps/accounting/sync-historical', { method: 'POST' })
    syncResult.value = res
    syncDialog.value = true
    await fetchOverview()
  } catch (e) {
    console.error('Failed to sync journals:', e)
  } finally {
    isSyncing.value = false
  }
}

watch(selectedBranch, () => {
  fetchOverview()
})

onMounted(async () => {
  await fetchBranches()
  await fetchOverview()
})

const accountingModules = [
  {
    title: 'Bagan Akun (Chart of Accounts / COA)',
    subtitle: 'Struktur kode akun baku (1-Aktiva, 2-Hutang, 3-Modal, 4-Pendapatan, 5-HPP, 6-Beban) dengan saldo normal debit/kredit.',
    icon: 'ri-node-tree',
    color: 'primary',
    to: '/akuntansi/coa',
    badge: 'Master COA',
    actionText: 'Kelola Bagan Akun',
  },
  {
    title: 'Jurnal Umum (General Journal)',
    subtitle: 'Catatan seluruh transaksi debit dan kredit otomatis (POS, GR, Hutang/Piutang) dan input Jurnal Penyesuaian Manual.',
    icon: 'ri-file-list-3-line',
    color: 'info',
    to: '/akuntansi/jurnal',
    badge: 'Jurnal Transaksi',
    actionText: 'Buka Jurnal Umum',
  },
  {
    title: 'Buku Besar (General Ledger)',
    subtitle: 'Rekapitulasi mutasi debit/kredit dan saldo berjalan per akun COA untuk rentang tanggal dan cabang terpilih.',
    icon: 'ri-book-2-line',
    color: 'success',
    to: '/akuntansi/buku-besar',
    badge: 'Buku Besar',
    actionText: 'Lihat Buku Besar',
  },
  {
    title: 'Neraca & Laporan Keuangan Baku',
    subtitle: 'Laporan Neraca Saldo (Trial Balance), Neraca Keuangan (Balance Sheet), dan Laporan Laba Rugi format PSAK.',
    icon: 'ri-scales-3-line',
    color: 'warning',
    to: '/akuntansi/neraca',
    badge: 'Laporan Formal',
    actionText: 'Lihat Neraca Keuangan',
  },
]
const downloadFinancialPdf = () => {
  const token = useCookie('accessToken').value
  const branchParam = selectedBranch.value !== 'all' ? `&branch_id=${selectedBranch.value}` : ''
  const url = `/api/accounting/financial-statements/export-pdf?token=${token}${branchParam}`
  window.open(url, '_blank')
}
</script>

<template>
  <div>
    <!-- Header Page -->
    <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold mb-1 text-high-emphasis">
          Pusat Akuntansi & Buku Besar
        </h4>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Sistem pembukuan berpasangan (*Double-Entry General Ledger*), Bagan Akun (COA), Jurnal Otomatis, dan Neraca Keuangan.
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
          color="error"
          variant="tonal"
          prepend-icon="ri-file-pdf-line"
          @click="downloadFinancialPdf"
        >
          Cetak Laporan PDF
        </VBtn>

        <VBtn
          color="secondary"
          variant="tonal"
          prepend-icon="ri-refresh-line"
          :loading="isSyncing"
          @click="triggerSync"
        >
          Sinkronisasi Jurnal
        </VBtn>

        <VBtn
          color="primary"
          prepend-icon="ri-file-add-line"
          to="/akuntansi/jurnal"
        >
          Input Jurnal Penyesuaian
        </VBtn>
      </div>
    </div>

    <!-- KPI Ringkasan Posisi Keuangan Real-Time -->
    <VRow class="mb-6 match-height">
      <!-- 1. Total Aset (Aktiva) -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="1" class="h-100 pa-4 border rounded-lg">
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Total Aset (Aktiva)</span>
            <VAvatar color="primary" variant="tonal" size="36" rounded>
              <VIcon icon="ri-bank-line" size="20" />
            </VAvatar>
          </div>
          <div class="text-h5 font-weight-bold text-primary mb-1">
            {{ formatCurrency(stats.total_assets) }}
          </div>
          <div class="text-caption text-medium-emphasis">
            Kas, Bank, Piutang, Persediaan & Aset
          </div>
        </VCard>
      </VCol>

      <!-- 2. Total Kewajiban (Hutang) -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="1" class="h-100 pa-4 border rounded-lg">
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Total Kewajiban (Hutang)</span>
            <VAvatar color="error" variant="tonal" size="36" rounded>
              <VIcon icon="ri-money-dollar-circle-line" size="20" />
            </VAvatar>
          </div>
          <div class="text-h5 font-weight-bold text-error mb-1">
            {{ formatCurrency(stats.total_liabilities) }}
          </div>
          <div class="text-caption text-medium-emphasis">
            Hutang Usaha & Biaya Berjalan
          </div>
        </VCard>
      </VCol>

      <!-- 3. Total Ekuitas & Laba -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="1" class="h-100 pa-4 border rounded-lg">
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Ekuitas & Modal</span>
            <VAvatar color="warning" variant="tonal" size="36" rounded>
              <VIcon icon="ri-funds-line" size="20" />
            </VAvatar>
          </div>
          <div class="text-h5 font-weight-bold text-warning mb-1">
            {{ formatCurrency(stats.total_equity) }}
          </div>
          <div class="text-caption text-medium-emphasis">
            Modal Disetor & Laba Ditahan
          </div>
        </VCard>
      </VCol>

      <!-- 4. Laba Bersih Akuntansi -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="1" class="h-100 pa-4 border rounded-lg">
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Laba Bersih Akuntansi</span>
            <VAvatar :color="stats.net_profit >= 0 ? 'success' : 'error'" variant="tonal" size="36" rounded>
              <VIcon :icon="stats.net_profit >= 0 ? 'ri-line-chart-line' : 'ri-arrow-down-line'" size="20" />
            </VAvatar>
          </div>
          <div class="text-h5 font-weight-bold mb-1" :class="stats.net_profit >= 0 ? 'text-success' : 'text-error'">
            {{ formatCurrency(stats.net_profit) }}
          </div>
          <div class="text-caption text-medium-emphasis">
            Pendapatan - HPP - Beban
          </div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Status Validasi Neraca (Balance Indicator) -->
    <VCard elevation="1" class="mb-6 border rounded-lg pa-4" :color="stats.is_balance_sheet_balanced ? 'success-lighten-5' : 'error-lighten-5'">
      <div class="d-flex flex-wrap align-center justify-space-between gap-4">
        <div class="d-flex align-center gap-3">
          <VAvatar :color="stats.is_balance_sheet_balanced ? 'success' : 'error'" variant="tonal" size="40" rounded>
            <VIcon :icon="stats.is_balance_sheet_balanced ? 'ri-checkbox-circle-line' : 'ri-alert-line'" size="24" />
          </VAvatar>
          <div>
            <div class="font-weight-bold text-body-1" :class="stats.is_balance_sheet_balanced ? 'text-success' : 'text-error'">
              {{ stats.is_balance_sheet_balanced ? 'Persamaan Akuntansi Seimbang (Balanced)' : 'Perhatian: Terdapat Selisih Jurnal' }}
            </div>
            <div class="text-caption text-medium-emphasis">
              Total {{ stats.total_journals_count }} transaksi jurnal terverifikasi dengan prinsip Aset = Kewajiban + Ekuitas.
            </div>
          </div>
        </div>

        <div class="d-flex align-center gap-2">
          <VBtn
            size="small"
            color="primary"
            variant="tonal"
            to="/akuntansi/neraca"
            prepend-icon="ri-scales-3-line"
          >
            Lihat Neraca Lengkap
          </VBtn>
        </div>
      </div>
    </VCard>

    <!-- Grid Menu Modul Akuntansi -->
    <div class="d-flex align-center justify-space-between mb-4">
      <h5 class="text-h5 font-weight-bold d-flex align-center gap-2 mb-0">
        <VIcon icon="ri-apps-2-line" color="primary" size="24" />
        Modul & Menu Akuntansi
      </h5>
    </div>

    <VRow class="match-height mb-6">
      <VCol
        v-for="mod in accountingModules"
        :key="mod.title"
        cols="12"
        sm="6"
        md="6"
        lg="3"
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

    <!-- Modal Dialog Hasil Sinkronisasi -->
    <VDialog v-model="syncDialog" max-width="500">
      <VCard class="pa-4">
        <VCardTitle class="d-flex align-center gap-2">
          <VIcon icon="ri-checkbox-circle-line" color="success" size="28" />
          <span>Sinkronisasi Berhasil</span>
        </VCardTitle>
        <VCardText v-if="syncResult">
          <p class="text-body-2 mb-4">{{ syncResult.message }}</p>
          <VList density="compact" class="border rounded-lg pa-2">
            <VListItem title="Penjualan POS" :subtitle="`${syncResult.details?.sales || 0} transaksi`" />
            <VListItem title="Penerimaan Barang (GR)" :subtitle="`${syncResult.details?.goods_receipts || 0} faktur`" />
            <VListItem title="Pembayaran Hutang" :subtitle="`${syncResult.details?.payable_payments || 0} pembayaran`" />
            <VListItem title="Setoran Piutang" :subtitle="`${syncResult.details?.receivable_payments || 0} setoran`" />
            <VListItem title="Kas Kecil Toko" :subtitle="`${syncResult.details?.petty_cash || 0} pengeluaran`" />
            <VListItem title="Modal Cabang / ROI" :subtitle="`${syncResult.details?.branch_capitals || 0} setoran`" />
          </VList>
        </VCardText>
        <VCardActions class="justify-end pt-4">
          <VBtn color="primary" @click="syncDialog = false">Tutup</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
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
