<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { paginationMeta } from '@/utils/paginationMeta'

definePage({
  meta: {
    action: 'read',
    subject: 'Akuntansi',
  },
})

const route = useRoute()
const isLoading = ref(false)
const accountsList = ref([])
const branches = ref([])

const selectedAccount = ref(null)
const selectedBranch = ref('all')
const startDate = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().substring(0, 10))
const endDate = ref(new Date().toISOString().substring(0, 10))
const page = ref(1)
const itemsPerPage = ref(15)

const ledgerData = ref({
  account: null,
  beginning_balance: 0,
  total_debit: 0,
  total_credit: 0,
  ending_balance: 0,
  transactions: [],
})

const paginatedTransactions = computed(() => {
  const start = (page.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return ledgerData.value.transactions.slice(start, end)
})

const formatCurrency = val => {
  if (val === null || val === undefined || isNaN(val)) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val || 0)
}

const fetchAccounts = async () => {
  try {
    const res = await $api('/apps/accounting/accounts')
    accountsList.value = res.data || []

    if (route.query.account_id) {
      selectedAccount.value = Number(route.query.account_id)
    } else if (accountsList.value.length > 0) {
      selectedAccount.value = accountsList.value[0].id
    }
  } catch (e) {
    console.error('Error fetching accounts:', e)
  }
}

const fetchBranches = async () => {
  try {
    const res = await $api('/apps/branches', { params: { itemsPerPage: 100 } })
    branches.value = res.branches || res.data || (Array.isArray(res) ? res : [])
  } catch (e) {
    console.error('Error fetching branches:', e)
  }
}

const fetchLedger = async () => {
  if (!selectedAccount.value) return

  isLoading.value = true
  try {
    const params = {
      account_id: selectedAccount.value,
      start_date: startDate.value,
      end_date: endDate.value,
    }
    if (selectedBranch.value && selectedBranch.value !== 'all') {
      params.branch_id = selectedBranch.value
    }

    const res = await $api('/apps/accounting/general-ledger', { params })
    if (res.success && res.data) {
      ledgerData.value = res.data
    }
  } catch (e) {
    console.error('Failed to load ledger:', e)
  } finally {
    isLoading.value = false
  }
}

const isDownloadingPdf = ref(false)

const downloadLedgerPdf = async () => {
  if (!selectedAccount.value) return
  isDownloadingPdf.value = true
  try {
    const params = new URLSearchParams({
      account_id: selectedAccount.value,
      start_date: startDate.value,
      end_date: endDate.value,
    })
    if (selectedBranch.value && selectedBranch.value !== 'all') {
      params.append('branch_id', selectedBranch.value)
    }

    const token = useCookie('accessToken').value
    const res = await fetch(`/api/apps/accounting/general-ledger/export-pdf?${params.toString()}`, {
      headers: {
        'Authorization': `Bearer ${token || ''}`,
        'Accept': 'application/pdf',
      },
    })

    if (!res.ok) {
      const errData = await res.json().catch(() => ({}))
      throw new Error(errData.message || 'Gagal mengunduh PDF Buku Besar')
    }

    const blob = await res.blob()
    const url = window.URL.createObjectURL(blob)

    window.open(url, '_blank')

    const a = document.createElement('a')
    a.href = url
    a.download = `Buku_Besar_${ledgerData.value.account?.code || selectedAccount.value}_${startDate.value}_${endDate.value}.pdf`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)

    setTimeout(() => window.URL.revokeObjectURL(url), 10000)
  } catch (err) {
    console.error('Error exporting PDF:', err)
    alert(err.message || 'Gagal mencetak PDF Buku Besar')
  } finally {
    isDownloadingPdf.value = false
  }
}

const printLedger = () => {
  window.print()
}

watch([selectedAccount, selectedBranch, startDate, endDate], () => {
  fetchLedger()
})

onMounted(async () => {
  await fetchBranches()
  await fetchAccounts()
  await fetchLedger()
})
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6 d-print-none">
      <div>
        <h4 class="text-h4 font-weight-bold mb-1 text-high-emphasis">
          Buku Besar (General Ledger)
        </h4>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Rincian mutasi debit & kredit beserta saldo akhir berjalan untuk setiap akun buku besar.
        </p>
      </div>

      <div class="d-flex flex-wrap align-center gap-3">
        <VBtn
          color="primary"
          prepend-icon="ri-file-pdf-2-line"
          :loading="isDownloadingPdf"
          @click="downloadLedgerPdf"
        >
          Cetak PDF
        </VBtn>
        <VBtn
          color="secondary"
          variant="tonal"
          prepend-icon="ri-printer-line"
          @click="printLedger"
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

    <!-- Filter Bar Card -->
    <VCard elevation="1" class="border rounded-lg mb-6 d-print-none">
      <VCardText class="pa-4">
        <VRow>
          <VCol cols="12" md="5">
            <VSelect
              v-model="selectedAccount"
              :items="accountsList"
              :item-title="item => `${item.code} - ${item.name} (${item.normal_balance?.toUpperCase()})`"
              item-value="id"
              label="Pilih Akun Buku Besar *"
              density="compact"
              variant="outlined"
              prepend-inner-icon="ri-book-2-line"
              hide-details
            />
          </VCol>

          <VCol cols="12" md="3">
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

          <VCol cols="12" md="4">
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

    <!-- Ringkasan Akun KPI -->
    <VRow class="mb-6 match-height">
      <!-- Saldo Awal -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="1" class="pa-4 border rounded-lg h-100">
          <div class="text-caption font-weight-bold text-uppercase text-medium-emphasis mb-1">
            Saldo Awal (Beginning)
          </div>
          <div class="text-h6 font-weight-bold text-medium-emphasis">
            {{ formatCurrency(ledgerData.beginning_balance) }}
          </div>
          <div class="text-caption text-medium-emphasis">Sebelum {{ startDate }}</div>
        </VCard>
      </VCol>

      <!-- Total Debit -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="1" class="pa-4 border rounded-lg h-100">
          <div class="text-caption font-weight-bold text-uppercase text-primary mb-1">
            Total Mutasi Debit
          </div>
          <div class="text-h6 font-weight-bold text-primary">
            + {{ formatCurrency(ledgerData.total_debit) }}
          </div>
          <div class="text-caption text-medium-emphasis">Periode terpilih</div>
        </VCard>
      </VCol>

      <!-- Total Kredit -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="1" class="pa-4 border rounded-lg h-100">
          <div class="text-caption font-weight-bold text-uppercase text-warning mb-1">
            Total Mutasi Kredit
          </div>
          <div class="text-h6 font-weight-bold text-warning">
            - {{ formatCurrency(ledgerData.total_credit) }}
          </div>
          <div class="text-caption text-medium-emphasis">Periode terpilih</div>
        </VCard>
      </VCol>

      <!-- Saldo Akhir -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="1" class="pa-4 border rounded-lg h-100 bg-primary-lighten-5">
          <div class="text-caption font-weight-bold text-uppercase text-primary mb-1">
            Saldo Akhir (Ending Balance)
          </div>
          <div class="text-h5 font-weight-bold text-primary">
            {{ formatCurrency(ledgerData.ending_balance) }}
          </div>
          <div class="text-caption text-primary font-weight-medium">Per {{ endDate }}</div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Ledger Table Card -->
    <VCard elevation="1" class="border rounded-lg">
      <VCardItem class="pa-4 pb-2">
        <VCardTitle class="font-weight-bold text-h6">
          <span class="font-mono text-primary">{{ ledgerData.account?.code }}</span> - {{ ledgerData.account?.name }}
        </VCardTitle>
        <VCardSubtitle>
          Klasifikasi: {{ ledgerData.account?.type?.toUpperCase() }} • Saldo Normal: {{ ledgerData.account?.normal_balance?.toUpperCase() }}
        </VCardSubtitle>
      </VCardItem>

      <VProgressLinear
        :active="isLoading"
        indeterminate
        color="primary"
        height="3"
      />

      <VTable class="text-no-wrap" hover>
        <thead>
          <tr>
            <th class="text-uppercase font-weight-bold">Tanggal</th>
            <th class="text-uppercase font-weight-bold">No. Jurnal</th>
            <th class="text-uppercase font-weight-bold">Keterangan / Memo</th>
            <th class="text-uppercase font-weight-bold">Cabang</th>
            <th class="text-uppercase font-weight-bold text-end">Debit</th>
            <th class="text-uppercase font-weight-bold text-end">Kredit</th>
            <th class="text-uppercase font-weight-bold text-end">Saldo Berjalan</th>
          </tr>
        </thead>
        <tbody>
          <!-- Baris Saldo Awal -->
          <tr class="bg-var-theme-surface">
            <td class="font-mono text-caption">{{ startDate }}</td>
            <td class="font-mono font-weight-bold">-</td>
            <td class="font-weight-semibold text-medium-emphasis">SALDO AWAL PERIODE</td>
            <td>-</td>
            <td class="text-end">-</td>
            <td class="text-end">-</td>
            <td class="text-end font-mono font-weight-bold text-primary">
              {{ formatCurrency(ledgerData.beginning_balance) }}
            </td>
          </tr>

          <template v-if="!isLoading && ledgerData.transactions.length === 0">
            <tr>
              <td colspan="7" class="text-center py-6 text-medium-emphasis">
                Tidak ada mutasi transaksi pada periode ini.
              </td>
            </tr>
          </template>
          <template v-else>
            <tr v-for="row in paginatedTransactions" :key="row.id">
              <td class="font-mono text-body-2">{{ row.entry_date }}</td>
              <td class="font-mono font-weight-bold text-primary">{{ row.entry_number }}</td>
              <td style="max-width: 300px; white-space: normal;">{{ row.notes || '-' }}</td>
              <td class="text-caption text-medium-emphasis">{{ row.branch_name }}</td>
              <td class="text-end font-mono" :class="{ 'text-primary font-weight-bold': row.debit > 0 }">
                {{ row.debit > 0 ? formatCurrency(row.debit) : '-' }}
              </td>
              <td class="text-end font-mono" :class="{ 'text-warning font-weight-bold': row.credit > 0 }">
                {{ row.credit > 0 ? formatCurrency(row.credit) : '-' }}
              </td>
              <td class="text-end font-mono font-weight-bold text-body-2">
                {{ formatCurrency(row.balance) }}
              </td>
            </tr>
          </template>

          <!-- Baris Saldo Akhir -->
          <tr class="bg-var-theme-surface font-weight-bold">
            <td colspan="4" class="text-uppercase text-end">TOTAL MUTASI & SALDO AKHIR:</td>
            <td class="text-end font-mono text-primary">{{ formatCurrency(ledgerData.total_debit) }}</td>
            <td class="text-end font-mono text-warning">{{ formatCurrency(ledgerData.total_credit) }}</td>
            <td class="text-end font-mono text-primary text-h6">{{ formatCurrency(ledgerData.ending_balance) }}</td>
          </tr>
        </tbody>
      </VTable>

      <!-- Pagination -->
      <VDivider v-if="ledgerData.transactions.length > 0" />

      <div v-if="ledgerData.transactions.length > 0" class="d-flex justify-end flex-wrap gap-x-6 px-4 py-2">
        <div class="d-flex align-center gap-x-2 text-medium-emphasis text-body-2">
          Baris per halaman:
          <VSelect
            v-model="itemsPerPage"
            class="per-page-select"
            variant="plain"
            density="compact"
            :items="[10, 15, 25, 50, 100]"
            hide-details
          />
        </div>

        <p class="d-flex align-center text-body-2 text-high-emphasis me-2 mb-0">
          {{ paginationMeta({ page, itemsPerPage }, ledgerData.transactions.length) }}
        </p>

        <div class="d-flex gap-x-2 align-center me-2">
          <VBtn
            class="flip-in-rtl"
            icon="ri-arrow-left-s-line"
            variant="text"
            density="comfortable"
            color="high-emphasis"
            :disabled="page <= 1"
            @click="page <= 1 ? page = 1 : page--"
          />

          <VBtn
            class="flip-in-rtl"
            icon="ri-arrow-right-s-line"
            density="comfortable"
            variant="text"
            color="high-emphasis"
            :disabled="page >= Math.ceil(ledgerData.transactions.length / itemsPerPage)"
            @click="page >= Math.ceil(ledgerData.transactions.length / itemsPerPage) ? page = Math.ceil(ledgerData.transactions.length / itemsPerPage) : page++"
          />
        </div>
      </div>
    </VCard>
  </div>
</template>

<style lang="scss">
.per-page-select {
  inline-size: 5.5rem;
}
</style>
