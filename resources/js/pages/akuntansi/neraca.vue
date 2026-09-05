<script setup>
import { ref, onMounted, computed, watch } from 'vue'

definePage({
  meta: {
    action: 'read',
    subject: 'Akuntansi',
  },
})

const activeTab = ref('balance-sheet')
const isLoading = ref(false)
const selectedBranch = ref('all')
const asOfDate = ref(new Date().toISOString().substring(0, 10))
const startDate = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().substring(0, 10))
const endDate = ref(new Date().toISOString().substring(0, 10))
const branches = ref([])

// Balance Sheet Data
const balanceSheet = ref({
  assets: [],
  total_assets: 0,
  liabilities: [],
  total_liabilities: 0,
  equities: [],
  total_equity: 0,
  current_period_earnings: 0,
  total_equity_with_earnings: 0,
  total_liabilities_and_equity: 0,
  difference: 0,
  is_balanced: true,
})

// Trial Balance Data
const trialBalance = ref({
  rows: [],
  total_debit: 0,
  total_credit: 0,
  difference: 0,
  is_balanced: true,
})

// Income Statement Data
const incomeStatement = ref({
  revenues: [],
  total_revenue: 0,
  cogs: [],
  total_cogs: 0,
  gross_profit: 0,
  expenses: [],
  total_expenses: 0,
  net_profit: 0,
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

const fetchReportData = async () => {
  isLoading.value = true
  try {
    const params = {
      branch_id: selectedBranch.value !== 'all' ? selectedBranch.value : undefined,
      as_of_date: asOfDate.value,
      start_date: startDate.value,
      end_date: endDate.value,
    }

    const [bsRes, tbRes, plRes] = await Promise.allSettled([
      $api('/apps/accounting/balance-sheet', { params }),
      $api('/apps/accounting/trial-balance', { params }),
      $api('/apps/accounting/income-statement', { params }),
    ])

    if (bsRes.status === 'fulfilled' && bsRes.value?.data) {
      balanceSheet.value = bsRes.value.data
    }

    if (tbRes.status === 'fulfilled' && tbRes.value?.data) {
      trialBalance.value = tbRes.value.data
    }

    if (plRes.status === 'fulfilled' && plRes.value?.data) {
      incomeStatement.value = plRes.value.data
    }
  } catch (e) {
    console.error('Failed to load accounting reports:', e)
  } finally {
    isLoading.value = false
  }
}

const isDownloadingPdf = ref(false)

const downloadFinancialPdf = async () => {
  isDownloadingPdf.value = true
  try {
    const params = new URLSearchParams({
      as_of_date: asOfDate.value,
      start_date: startDate.value,
      end_date: endDate.value,
    })
    if (selectedBranch.value && selectedBranch.value !== 'all') {
      params.append('branch_id', selectedBranch.value)
    }

    const token = useCookie('accessToken').value
    const res = await fetch(`/api/apps/accounting/financial-statements/export-pdf?${params.toString()}`, {
      headers: {
        'Authorization': `Bearer ${token || ''}`,
        'Accept': 'application/pdf',
      },
    })

    if (!res.ok) {
      const errData = await res.json().catch(() => ({}))
      throw new Error(errData.message || 'Gagal mengunduh PDF Laporan Keuangan')
    }

    const blob = await res.blob()
    const url = window.URL.createObjectURL(blob)

    window.open(url, '_blank')

    const a = document.createElement('a')
    a.href = url
    a.download = `Laporan_Keuangan_${asOfDate.value}.pdf`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)

    setTimeout(() => window.URL.revokeObjectURL(url), 10000)
  } catch (err) {
    console.error('Error exporting PDF:', err)
    alert(err.message || 'Gagal mencetak PDF Laporan Keuangan')
  } finally {
    isDownloadingPdf.value = false
  }
}

const printReport = () => {
  window.print()
}

watch([selectedBranch, asOfDate, startDate, endDate], () => {
  fetchReportData()
})

onMounted(async () => {
  await fetchBranches()
  await fetchReportData()
})
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6 d-print-none">
      <div>
        <h4 class="text-h4 font-weight-bold mb-1 text-high-emphasis">
          Laporan Neraca & Laporan Keuangan Baku
        </h4>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Neraca Keuangan (*Balance Sheet*), Neraca Saldo (*Trial Balance*), dan Laporan Laba Rugi (*Income Statement*).
        </p>
      </div>

      <div class="d-flex flex-wrap align-center gap-3">
        <VBtn
          color="primary"
          prepend-icon="ri-file-pdf-2-line"
          :loading="isDownloadingPdf"
          @click="downloadFinancialPdf"
        >
          Cetak PDF
        </VBtn>
        <VBtn
          color="secondary"
          variant="tonal"
          prepend-icon="ri-printer-line"
          @click="printReport"
        >
          Print Browser
        </VBtn>
        <VBtn
          color="primary"
          variant="tonal"
          prepend-icon="ri-arrow-left-line"
          to="/akuntansi"
        >
          Kembali ke Hub
        </VBtn>
      </div>
    </div>

    <!-- Filter Bar -->
    <VCard elevation="1" class="border rounded-lg mb-6 d-print-none">
      <VCardText class="pa-4">
        <VRow align="center">
          <VCol cols="12" md="4">
            <VSelect
              v-if="branches.length > 1"
              v-model="selectedBranch"
              :items="[{ id: 'all', name: 'Semua Cabang' }, ...branches]"
              item-title="name"
              item-value="id"
              label="Cabang"
              density="compact"
              variant="outlined"
              prepend-inner-icon="ri-store-2-line"
              hide-details
            />
          </VCol>

          <VCol cols="12" md="4" v-if="activeTab === 'balance-sheet' || activeTab === 'trial-balance'">
            <VTextField
              v-model="asOfDate"
              type="date"
              label="Posisi Per Tanggal (As of Date)"
              density="compact"
              variant="outlined"
              hide-details
            />
          </VCol>

          <VCol cols="12" md="4" v-if="activeTab === 'income-statement'">
            <div class="d-flex gap-2">
              <VTextField
                v-model="startDate"
                type="date"
                label="Dari Tanggal"
                density="compact"
                variant="outlined"
                hide-details
              />
              <VTextField
                v-model="endDate"
                type="date"
                label="Sampai Tanggal"
                density="compact"
                variant="outlined"
                hide-details
              />
            </div>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Tabs Navigation -->
    <VTabs v-model="activeTab" class="mb-6 d-print-none" color="primary">
      <VTab value="balance-sheet" prepend-icon="ri-scales-3-line">
        Neraca Keuangan (Balance Sheet)
      </VTab>
      <VTab value="trial-balance" prepend-icon="ri-list-check">
        Neraca Saldo (Trial Balance)
      </VTab>
      <VTab value="income-statement" prepend-icon="ri-line-chart-line">
        Laporan Laba Rugi (Income Statement)
      </VTab>
    </VTabs>

    <!-- Top Progress Bar -->
    <VProgressLinear
      :active="isLoading"
      indeterminate
      color="primary"
      height="3"
      class="mb-4 rounded"
    />

    <!-- TAB 1: NERACA KEUANGAN (BALANCE SHEET) -->
    <div v-show="activeTab === 'balance-sheet'">
      <!-- Status Balance Box -->
      <VCard
        elevation="1"
        class="mb-6 pa-4 border rounded-lg"
        :color="balanceSheet.is_balanced ? 'success-lighten-5' : 'error-lighten-5'"
      >
        <div class="d-flex align-center justify-space-between flex-wrap gap-2">
          <div class="d-flex align-center gap-3">
            <VIcon :icon="balanceSheet.is_balanced ? 'ri-checkbox-circle-fill' : 'ri-alert-fill'" size="28" :color="balanceSheet.is_balanced ? 'success' : 'error'" />
            <div>
              <div class="font-weight-bold" :class="balanceSheet.is_balanced ? 'text-success' : 'text-error'">
                {{ balanceSheet.is_balanced ? 'Neraca Seimbang (Balanced)' : 'Perhatian: Neraca Belum Seimbang' }}
              </div>
              <div class="text-caption text-medium-emphasis">
                Total Aset (Aktiva) = Total Kewajiban (Hutang) + Total Ekuitas (Modal)
              </div>
            </div>
          </div>
          <div class="text-end font-mono">
            <div class="text-caption text-medium-emphasis">Selisih:</div>
            <div class="font-weight-bold text-body-1" :class="balanceSheet.is_balanced ? 'text-success' : 'text-error'">
              {{ formatCurrency(balanceSheet.difference) }}
            </div>
          </div>
        </div>
      </VCard>

      <VRow>
        <!-- Kolom Kiri: ASET / AKTIVA -->
        <VCol cols="12" md="6">
          <VCard elevation="1" class="border rounded-lg h-100">
            <VCardItem class="pa-4 bg-primary-lighten-5 border-b">
              <VCardTitle class="text-h6 font-weight-bold text-primary">
                ASET / AKTIVA (ASSETS)
              </VCardTitle>
            </VCardItem>
            <VCardText class="pa-4">
              <VTable density="compact" class="text-no-wrap">
                <tbody>
                  <tr v-for="a in balanceSheet.assets" :key="a.id">
                    <td>
                      <span class="font-mono text-primary font-weight-bold me-2">{{ a.code }}</span>
                      {{ a.name }}
                    </td>
                    <td class="text-end font-mono font-weight-semibold">
                      {{ formatCurrency(a.balance) }}
                    </td>
                  </tr>
                  <tr v-if="balanceSheet.assets.length === 0">
                    <td colspan="2" class="text-center py-4 text-medium-emphasis">Tidak ada aset tercatat.</td>
                  </tr>
                </tbody>
              </VTable>
            </VCardText>
            <VDivider />
            <VCardActions class="pa-4 bg-var-theme-surface d-flex justify-space-between">
              <span class="font-weight-bold text-body-1">TOTAL ASET (AKTIVA):</span>
              <span class="font-mono font-weight-bold text-h6 text-primary">{{ formatCurrency(balanceSheet.total_assets) }}</span>
            </VCardActions>
          </VCard>
        </VCol>

        <!-- Kolom Kanan: KEWAJIBAN & EKUITAS (PASIVA) -->
        <VCol cols="12" md="6">
          <VCard elevation="1" class="border rounded-lg h-100">
            <VCardItem class="pa-4 bg-warning-lighten-5 border-b">
              <VCardTitle class="text-h6 font-weight-bold text-warning">
                KEWAJIBAN & EKUITAS (PASIVA)
              </VCardTitle>
            </VCardItem>
            <VCardText class="pa-4">
              <!-- Kewajiban / Hutang -->
              <div class="text-caption font-weight-bold text-uppercase text-medium-emphasis mb-2">
                1. Kewajiban (Hutang)
              </div>
              <VTable density="compact" class="text-no-wrap mb-4">
                <tbody>
                  <tr v-for="l in balanceSheet.liabilities" :key="l.id">
                    <td>
                      <span class="font-mono text-error font-weight-bold me-2">{{ l.code }}</span>
                      {{ l.name }}
                    </td>
                    <td class="text-end font-mono font-weight-semibold">
                      {{ formatCurrency(l.balance) }}
                    </td>
                  </tr>
                  <tr class="bg-var-theme-surface font-weight-semibold">
                    <td>Subtotal Kewajiban</td>
                    <td class="text-end font-mono text-error">{{ formatCurrency(balanceSheet.total_liabilities) }}</td>
                  </tr>
                </tbody>
              </VTable>

              <!-- Ekuitas / Modal -->
              <div class="text-caption font-weight-bold text-uppercase text-medium-emphasis mb-2">
                2. Ekuitas & Modal Pemilik
              </div>
              <VTable density="compact" class="text-no-wrap">
                <tbody>
                  <tr v-for="e in balanceSheet.equities" :key="e.id">
                    <td>
                      <span class="font-mono text-warning font-weight-bold me-2">{{ e.code }}</span>
                      {{ e.name }}
                    </td>
                    <td class="text-end font-mono font-weight-semibold">
                      {{ formatCurrency(e.balance) }}
                    </td>
                  </tr>
                  <!-- Laba Periode Berjalan -->
                  <tr class="bg-success-lighten-5 font-weight-semibold">
                    <td>
                      <span class="font-mono text-success font-weight-bold me-2">3104</span>
                      Laba / (Rugi) Periode Berjalan
                    </td>
                    <td class="text-end font-mono text-success font-weight-bold">
                      {{ formatCurrency(balanceSheet.current_period_earnings) }}
                    </td>
                  </tr>
                  <tr class="bg-var-theme-surface font-weight-semibold">
                    <td>Subtotal Ekuitas</td>
                    <td class="text-end font-mono text-warning">{{ formatCurrency(balanceSheet.total_equity_with_earnings) }}</td>
                  </tr>
                </tbody>
              </VTable>
            </VCardText>
            <VDivider />
            <VCardActions class="pa-4 bg-var-theme-surface d-flex justify-space-between">
              <span class="font-weight-bold text-body-1">TOTAL PASIVA (KEWAJIBAN + EKUITAS):</span>
              <span class="font-mono font-weight-bold text-h6 text-warning">{{ formatCurrency(balanceSheet.total_liabilities_and_equity) }}</span>
            </VCardActions>
          </VCard>
        </VCol>
      </VRow>
    </div>

    <!-- TAB 2: NERACA SALDO (TRIAL BALANCE) -->
    <div v-show="activeTab === 'trial-balance'">
      <VCard elevation="1" class="border rounded-lg">
        <VCardItem class="pa-4 pb-2">
          <VCardTitle class="font-weight-bold text-h6">
            Neraca Saldo (Trial Balance) Per {{ asOfDate }}
          </VCardTitle>
          <VCardSubtitle>
            Verifikasi keseimbangan saldo debit dan kredit seluruh akun aktif
          </VCardSubtitle>
        </VCardItem>

        <VDivider />

        <VTable class="text-no-wrap" hover>
          <thead>
            <tr>
              <th class="text-uppercase font-weight-bold">Kode Akun</th>
              <th class="text-uppercase font-weight-bold">Nama Akun</th>
              <th class="text-uppercase font-weight-bold">Tipe Akun</th>
              <th class="text-uppercase font-weight-bold text-end">Debit (Rp)</th>
              <th class="text-uppercase font-weight-bold text-end">Kredit (Rp)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in trialBalance.rows" :key="row.id">
              <td class="font-mono font-weight-bold text-primary">{{ row.code }}</td>
              <td class="font-weight-medium">{{ row.name }}</td>
              <td class="text-caption text-uppercase text-medium-emphasis">{{ row.type }}</td>
              <td class="text-end font-mono" :class="{ 'text-primary font-weight-bold': row.debit > 0 }">
                {{ row.debit > 0 ? formatCurrency(row.debit) : '-' }}
              </td>
              <td class="text-end font-mono" :class="{ 'text-warning font-weight-bold': row.credit > 0 }">
                {{ row.credit > 0 ? formatCurrency(row.credit) : '-' }}
              </td>
            </tr>

            <!-- Baris Total Trial Balance -->
            <tr class="bg-var-theme-surface font-weight-bold text-h6">
              <td colspan="3" class="text-end text-uppercase">TOTAL NERACA SALDO:</td>
              <td class="text-end font-mono text-primary">{{ formatCurrency(trialBalance.total_debit) }}</td>
              <td class="text-end font-mono text-warning">{{ formatCurrency(trialBalance.total_credit) }}</td>
            </tr>
          </tbody>
        </VTable>
      </VCard>
    </div>

    <!-- TAB 3: LAPORAN LABA RUGI (INCOME STATEMENT) -->
    <div v-show="activeTab === 'income-statement'">
      <VCard elevation="1" class="border rounded-lg">
        <VCardItem class="pa-4 pb-2">
          <VCardTitle class="font-weight-bold text-h6">
            Laporan Laba Rugi (Income Statement)
          </VCardTitle>
          <VCardSubtitle>
            Periode: {{ startDate }} s/d {{ endDate }}
          </VCardSubtitle>
        </VCardItem>

        <VDivider />

        <VCardText class="pa-4">
          <!-- 1. Pendapatan Usaha -->
          <div class="text-subtitle-1 font-weight-bold text-primary mb-2">
            1. PENDAPATAN USAHA (REVENUE)
          </div>
          <VTable density="compact" class="text-no-wrap mb-4 border rounded">
            <tbody>
              <tr v-for="r in incomeStatement.revenues" :key="r.code">
                <td><span class="font-mono font-weight-bold text-primary me-2">{{ r.code }}</span>{{ r.name }}</td>
                <td class="text-end font-mono font-weight-semibold">{{ formatCurrency(r.amount) }}</td>
              </tr>
              <tr class="bg-var-theme-surface font-weight-bold">
                <td>Total Pendapatan Usaha</td>
                <td class="text-end font-mono text-primary">{{ formatCurrency(incomeStatement.total_revenue) }}</td>
              </tr>
            </tbody>
          </VTable>

          <!-- 2. HPP -->
          <div class="text-subtitle-1 font-weight-bold text-secondary mb-2">
            2. HARGA POKOK PENJUALAN (COGS)
          </div>
          <VTable density="compact" class="text-no-wrap mb-4 border rounded">
            <tbody>
              <tr v-for="c in incomeStatement.cogs" :key="c.code">
                <td><span class="font-mono font-weight-bold text-secondary me-2">{{ c.code }}</span>{{ c.name }}</td>
                <td class="text-end font-mono font-weight-semibold">{{ formatCurrency(c.amount) }}</td>
              </tr>
              <tr class="bg-var-theme-surface font-weight-bold">
                <td>Total HPP Modal Barang</td>
                <td class="text-end font-mono text-secondary">{{ formatCurrency(incomeStatement.total_cogs) }}</td>
              </tr>
            </tbody>
          </VTable>

          <!-- Gross Profit Summary -->
          <div class="d-flex justify-space-between align-center pa-3 bg-primary-lighten-5 rounded-lg border mb-4">
            <span class="font-weight-bold text-body-1 text-primary">LABA KOTOR (GROSS PROFIT):</span>
            <span class="font-mono font-weight-bold text-h6 text-primary">{{ formatCurrency(incomeStatement.gross_profit) }}</span>
          </div>

          <!-- 3. Beban Operasional -->
          <div class="text-subtitle-1 font-weight-bold text-error mb-2">
            3. BEBAN OPERASIONAL (OPERATING EXPENSES)
          </div>
          <VTable density="compact" class="text-no-wrap mb-4 border rounded">
            <tbody>
              <tr v-for="e in incomeStatement.expenses" :key="e.code">
                <td><span class="font-mono font-weight-bold text-info me-2">{{ e.code }}</span>{{ e.name }}</td>
                <td class="text-end font-mono font-weight-semibold">{{ formatCurrency(e.amount) }}</td>
              </tr>
              <tr class="bg-var-theme-surface font-weight-bold">
                <td>Total Beban Operasional</td>
                <td class="text-end font-mono text-error">{{ formatCurrency(incomeStatement.total_expenses) }}</td>
              </tr>
            </tbody>
          </VTable>

          <!-- Net Profit Summary -->
          <div class="d-flex justify-space-between align-center pa-4 bg-success-lighten-5 rounded-lg border">
            <span class="font-weight-bold text-h6 text-success">LABA BERSIH RESMI (NET PROFIT):</span>
            <span class="font-mono font-weight-bold text-h5 text-success">{{ formatCurrency(incomeStatement.net_profit) }}</span>
          </div>
        </VCardText>
      </VCard>
    </div>
  </div>
</template>
