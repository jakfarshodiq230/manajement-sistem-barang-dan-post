<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import PayableDetailDrawer from './PayableDetailDrawer.vue'

const activeTab = ref('statements') // 'statements' | 'invoices'

// State for Statements
const statements = ref([])
const isLoadingStatements = ref(false)
const totalStatements = ref(0)
const page = ref(1)
const itemsPerPage = ref(10)

// State for Invoices tab
const invoices = ref([])
const isLoadingInvoices = ref(false)
const totalInvoices = ref(0)
const invoicePage = ref(1)
const invoiceItemsPerPage = ref(10)

// Filters
const selectedPeriod = ref('all')
const availablePeriods = ref([])
const selectedStatus = ref('')
const selectedSupplier = ref(null)
const selectedBranch = ref(null)
const suppliers = ref([])
const branches = ref([])
const searchQuery = ref('')
let searchTimeout = null

const summary = ref({
  total_payable: 0,
  total_paid: 0,
  total_remaining: 0,
  total_overdue: 0,
  total_due_soon: 0,
  count_unpaid: 0,
  count_partial: 0,
  count_paid: 0,
  count_overdue: 0,
  count_total: 0,
})

const isDetailDrawerVisible = ref(false)
const selectedStatementId = ref(null)

const snackbar = useSnackbarStore()

const formatCurrency = value => {
  if (!value || isNaN(value)) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)
}

const formatDate = dateString => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const formatDateRange = (start, end) => {
  if (!start || !end) return '-'
  const dStart = new Date(start).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
  const dEnd = new Date(end).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
  return `${dStart} - ${dEnd}`
}

const formatMonthLabel = periodMonth => {
  if (!periodMonth) return '-'
  const [year, month] = periodMonth.split('-')
  const date = new Date(year, parseInt(month) - 1, 1)
  return date.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
}

const fetchInitialOptions = async () => {
  try {
    const [bRes, sRes] = await Promise.all([
      $api('/apps/branches'),
      $api('/apps/suppliers'),
    ])
    branches.value = bRes.data || bRes || []
    suppliers.value = sRes.data || sRes || []
  } catch (error) {
    console.error('Failed to load branches and suppliers:', error)
  }
}

const fetchStatements = async () => {
  isLoadingStatements.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
    }

    if (selectedPeriod.value && selectedPeriod.value !== 'all') params.period_month = selectedPeriod.value
    if (selectedStatus.value) params.status = selectedStatus.value
    if (selectedSupplier.value) params.supplier_id = selectedSupplier.value
    if (selectedBranch.value) params.branch_id = selectedBranch.value
    if (searchQuery.value) params.search = searchQuery.value

    const res = await $api('/apps/payables', { query: params })

    statements.value = res.data || res || []
    if (res.total !== undefined) {
      totalStatements.value = res.total
    }
    if (res.summary) {
      summary.value = res.summary
    }
    if (res.available_periods) {
      availablePeriods.value = res.available_periods
    }
  } catch (error) {
    console.error('Failed to fetch payable statements:', error)
    snackbar.show('Gagal mengambil data tagihan bulanan supplier', 'error')
  } finally {
    isLoadingStatements.value = false
  }
}

const fetchInvoices = async () => {
  isLoadingInvoices.value = true
  try {
    const params = {
      page: invoicePage.value,
      itemsPerPage: invoiceItemsPerPage.value,
    }

    if (selectedSupplier.value) params.supplier_id = selectedSupplier.value
    if (selectedBranch.value) params.branch_id = selectedBranch.value
    if (searchQuery.value) params.search = searchQuery.value

    const res = await $api('/apps/payables/invoices', { query: params })

    invoices.value = res.data || res || []
    if (res.total !== undefined) {
      totalInvoices.value = res.total
    }
  } catch (error) {
    console.error('Failed to fetch invoices:', error)
  } finally {
    isLoadingInvoices.value = false
  }
}

const onPaymentUpdated = () => {
  fetchStatements()
  fetchInvoices()
}

const refreshAll = () => {
  if (activeTab.value === 'statements') {
    fetchStatements()
  } else {
    fetchInvoices()
  }
}

watch(searchQuery, () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    invoicePage.value = 1
    refreshAll()
  }, 450)
})

watch([selectedPeriod, selectedStatus, selectedSupplier, selectedBranch], () => {
  page.value = 1
  invoicePage.value = 1
  refreshAll()
})

watch(activeTab, newTab => {
  if (newTab === 'statements') {
    fetchStatements()
  } else {
    fetchInvoices()
  }
})

onMounted(async () => {
  await fetchInitialOptions()
  fetchStatements()
})

const statementHeaders = [
  { title: 'NO. TAGIHAN & SIKLUS', key: 'statement_number', minWidth: '220px' },
  { title: 'SUPPLIER / VENDOR', key: 'supplier.name', minWidth: '180px' },
  { title: 'CABANG', key: 'branch.name' },
  { title: 'FAKTUR', key: 'total_invoices_count', align: 'center' },
  { title: 'TOTAL TAGIHAN', key: 'total_amount', align: 'end' },
  { title: 'SUDAH DIBAYAR', key: 'paid_amount', align: 'end' },
  { title: 'SISA HUTANG', key: 'remaining_amount', align: 'end' },
  { title: 'PROGRES PELUNASAN', key: 'progress', minWidth: '160px' },
  { title: 'JATUH TEMPO', key: 'due_date' },
  { title: 'STATUS', key: 'status', align: 'center' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const invoiceHeaders = [
  { title: 'NO. AP & FAKTUR SUPPLIER', key: 'payable_number', minWidth: '200px' },
  { title: 'TGL FAKTUR / TERIMA', key: 'invoice_date' },
  { title: 'SUPPLIER / VENDOR', key: 'supplier.name' },
  { title: 'CABANG', key: 'branch.name' },
  { title: 'TAGIHAN BULANAN', key: 'payable_statement.statement_number', minWidth: '180px' },
  { title: 'TOTAL NOMINAL', key: 'total_amount', align: 'end' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const isOverdue = item => {
  if (!item || item.status === 'paid' || !item.due_date) return false
  const today = new Date().toISOString().substring(0, 10)
  return String(item.due_date).substring(0, 10) < today
}

const isDueSoon = item => {
  if (!item || item.status === 'paid' || !item.due_date) return false
  const today = new Date().toISOString().substring(0, 10)
  const d = String(item.due_date).substring(0, 10)
  const next7Days = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().substring(0, 10)
  return d >= today && d <= next7Days
}

const getStatementProgress = item => {
  if (!item || Number(item.total_amount) === 0) return 0
  const paid = Number(item.paid_amount) || 0
  const total = Number(item.total_amount) || 1
  const pct = (paid / total) * 100
  if (pct > 0 && pct < 1) {
    return Number(pct.toFixed(2))
  }
  return Math.min(100, Math.round(pct))
}

const getStatusBadge = item => {
  if (item.status === 'paid') return { color: 'success', text: 'Lunas', icon: 'ri-check-double-line' }
  if (isOverdue(item)) return { color: 'error', text: 'Lewat Jatuh Tempo', icon: 'ri-error-warning-line' }
  if (item.status === 'partial') return { color: 'warning', text: 'Dicicil', icon: 'ri-time-line' }
  return { color: 'secondary', text: 'Belum Dibayar', icon: 'ri-file-list-line' }
}

const openDetailDrawer = statementId => {
  selectedStatementId.value = statementId
  isDetailDrawerVisible.value = true
}
</script>

<template>
  <div class="payables-page">
    <!-- Top Header Breadcrumb & Title -->
    <div class="d-flex justify-space-between align-center mb-6 flex-wrap gap-4">
      <div>
        <h4 class="text-h4 font-weight-bold mb-1 d-flex align-center gap-2">
          <VIcon icon="ri-wallet-3-line" color="primary" size="32" />
          Buku Hutang Supplier (Accounts Payable)
        </h4>
        <p class="text-body-1 text-medium-emphasis mb-0">
          Akumulasi tagihan pembelian barang per siklus cutoff (tgl 26 - 25), jatuh tempo pembayaran bulan depan, dan progres cicilan per bulan.
        </p>
      </div>

      <!-- Quick Actions / Period Picker -->
      <div class="d-flex align-center gap-3">
        <div style="min-width: 260px;">
          <VSelect
            v-model="selectedPeriod"
            :items="[
              { period_month: 'all', label: 'Semua Periode Tagihan' },
              ...availablePeriods
            ]"
            item-title="label"
            item-value="period_month"
            density="compact"
            variant="outlined"
            label="Periode Tagihan Siklus"
            hide-details
            prepend-inner-icon="ri-calendar-event-line"
          />
        </div>
        <VBtn
          color="primary"
          variant="tonal"
          prepend-icon="ri-refresh-line"
          :loading="isLoadingStatements || isLoadingInvoices"
          @click="refreshAll"
        >
          Muat Ulang
        </VBtn>
      </div>
    </div>

    <!-- Financial KPI Summary Cards -->
    <VRow class="mb-6">
      <!-- 1. Total Hutang Supplier -->
      <VCol cols="12" sm="6" md="3">
        <VCard class="h-100 shadow-xs border-dashed">
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <span class="text-caption text-medium-emphasis font-weight-medium">Total Tagihan Periode:</span>
              <h5 class="text-h5 font-weight-bold font-mono text-primary mt-1">
                {{ formatCurrency(summary.total_payable) }}
              </h5>
              <span class="text-caption text-medium-emphasis">
                {{ summary.count_total }} Tagihan Bulanan Aktif
              </span>
            </div>
            <VAvatar color="primary" variant="tonal" size="48" class="rounded-xl">
              <VIcon icon="ri-file-list-3-line" size="24" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <!-- 2. Total Sudah Dibayar -->
      <VCol cols="12" sm="6" md="3">
        <VCard class="h-100 shadow-xs border-dashed">
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <span class="text-caption text-medium-emphasis font-weight-medium">Sudah Dibayar / Dicicil:</span>
              <h5 class="text-h5 font-weight-bold font-mono text-success mt-1">
                {{ formatCurrency(summary.total_paid) }}
              </h5>
              <span class="text-caption text-success font-weight-medium">
                {{ summary.count_paid }} Tagihan Lunas
              </span>
            </div>
            <VAvatar color="success" variant="tonal" size="48" class="rounded-xl">
              <VIcon icon="ri-checkbox-circle-line" size="24" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <!-- 3. Sisa Hutang Berjalan -->
      <VCol cols="12" sm="6" md="3">
        <VCard class="h-100 shadow-xs border-dashed">
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <span class="text-caption text-medium-emphasis font-weight-medium">Sisa Hutang Berjalan:</span>
              <h5 class="text-h5 font-weight-bold font-mono text-error mt-1">
                {{ formatCurrency(summary.total_remaining) }}
              </h5>
              <span class="text-caption text-warning font-weight-medium">
                {{ summary.count_unpaid + summary.count_partial }} Tagihan Belum Lunas
              </span>
            </div>
            <VAvatar color="error" variant="tonal" size="48" class="rounded-xl">
              <VIcon icon="ri-wallet-3-line" size="24" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <!-- 4. Hutang Jatuh Tempo -->
      <VCol cols="12" sm="6" md="3">
        <VCard class="h-100 shadow-xs border-dashed">
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <span class="text-caption text-medium-emphasis font-weight-medium">Lewat Jatuh Tempo:</span>
              <h5 class="text-h5 font-weight-bold font-mono text-error mt-1">
                {{ formatCurrency(summary.total_overdue) }}
              </h5>
              <span class="text-caption text-error font-weight-medium">
                {{ summary.count_overdue }} Tagihan Menunggak
              </span>
            </div>
            <VAvatar color="error" variant="flat" size="48" class="rounded-xl">
              <VIcon icon="ri-alarm-warning-line" color="white" size="24" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Main Card with Tabs -->
    <VCard class="rounded-xl overflow-hidden shadow-xs border">
      <!-- Tabs Header -->
      <div class="border-b bg-var-theme-surface">
        <VTabs v-model="activeTab" color="primary">
          <VTab value="statements" class="font-weight-bold">
            <VIcon icon="ri-calendar-check-line" size="18" class="mr-2" />
            Rekap Tagihan Bulanan (Billing Statements)
            <VChip size="x-small" color="primary" class="ml-2 font-weight-bold">{{ totalStatements }}</VChip>
          </VTab>
          <VTab value="invoices" class="font-weight-bold">
            <VIcon icon="ri-file-list-line" size="18" class="mr-2" />
            Rincian Seluruh Faktur Pembelian (All Invoices)
            <VChip size="x-small" color="secondary" class="ml-2 font-weight-bold">{{ totalInvoices }}</VChip>
          </VTab>
        </VTabs>
      </div>

      <!-- TAB 1: Statements View -->
      <div v-show="activeTab === 'statements'">
        <!-- Filter Toolbar -->
        <div class="pa-4 border-b bg-var-theme-surface d-flex justify-space-between align-center flex-wrap gap-4">
          <!-- Status Tabs / Filter -->
          <div class="d-flex align-center gap-2 flex-wrap">
            <VBtn
              size="small"
              :variant="selectedStatus === '' ? 'flat' : 'tonal'"
              color="primary"
              class="rounded-lg"
              @click="selectedStatus = ''"
            >
              Semua ({{ summary.count_total }})
            </VBtn>
            <VBtn
              size="small"
              :variant="selectedStatus === 'unpaid' ? 'flat' : 'tonal'"
              color="secondary"
              class="rounded-lg"
              @click="selectedStatus = 'unpaid'"
            >
              Belum Bayar ({{ summary.count_unpaid }})
            </VBtn>
            <VBtn
              size="small"
              :variant="selectedStatus === 'partial' ? 'flat' : 'tonal'"
              color="warning"
              class="rounded-lg"
              @click="selectedStatus = 'partial'"
            >
              Dicicil ({{ summary.count_partial }})
            </VBtn>
            <VBtn
              size="small"
              :variant="selectedStatus === 'paid' ? 'flat' : 'tonal'"
              color="success"
              class="rounded-lg"
              @click="selectedStatus = 'paid'"
            >
              Lunas ({{ summary.count_paid }})
            </VBtn>
            <VBtn
              size="small"
              :variant="selectedStatus === 'overdue' ? 'flat' : 'tonal'"
              color="error"
              class="rounded-lg"
              @click="selectedStatus = 'overdue'"
            >
              Lewat Tempo ({{ summary.count_overdue }})
            </VBtn>
          </div>

          <!-- Search and Select Filters -->
          <div class="d-flex align-center gap-3 flex-wrap">
            <div style="width: 200px;">
              <VSelect
                v-model="selectedSupplier"
                :items="suppliers"
                item-title="name"
                item-value="id"
                label="Filter Supplier"
                placeholder="Semua Supplier"
                density="compact"
                variant="outlined"
                clearable
                hide-details
              />
            </div>

            <div style="width: 180px;">
              <VSelect
                v-model="selectedBranch"
                :items="branches"
                item-title="name"
                item-value="id"
                label="Filter Cabang"
                placeholder="Semua Cabang"
                density="compact"
                variant="outlined"
                clearable
                hide-details
              />
            </div>

            <div style="width: 240px;">
              <VTextField
                v-model="searchQuery"
                placeholder="Cari no. tagihan / supplier..."
                prepend-inner-icon="ri-search-line"
                density="compact"
                variant="outlined"
                clearable
                hide-details
              />
            </div>
          </div>
        </div>

        <!-- Data Table Statements -->
        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          v-model:page="page"
          :items="statements"
          :items-length="totalStatements"
          :headers="statementHeaders"
          :loading="isLoadingStatements"
          class="text-no-wrap"
          @update:options="fetchStatements"
        >
          <!-- No. Tagihan & Siklus -->
          <template #item.statement_number="{ item }">
            <div class="py-2">
              <div class="font-weight-bold font-mono text-primary text-body-2">
                {{ item.statement_number }}
              </div>
              <div class="d-flex align-center gap-1 mt-1">
                <VChip size="x-small" color="primary" variant="tonal" class="font-weight-bold">
                  {{ formatMonthLabel(item.period_month) }}
                </VChip>
                <span class="text-caption text-medium-emphasis">
                  ({{ formatDateRange(item.period_start_date, item.period_end_date) }})
                </span>
              </div>
            </div>
          </template>

          <!-- Supplier -->
          <template #item.supplier.name="{ item }">
            <div>
              <div class="font-weight-bold text-body-2">{{ item.supplier?.name || '-' }}</div>
              <div class="text-caption text-medium-emphasis">{{ item.supplier?.phone || '-' }}</div>
            </div>
          </template>

          <!-- Cabang -->
          <template #item.branch.name="{ item }">
            <span class="text-body-2">{{ item.branch?.name || 'Semua Cabang' }}</span>
          </template>

          <!-- Total Invoices -->
          <template #item.total_invoices_count="{ item }">
            <VChip size="small" variant="tonal" color="info" class="font-weight-bold">
              <VIcon icon="ri-file-list-3-line" size="14" class="mr-1" />
              {{ item.total_invoices_count || (item.payables ? item.payables.length : 0) }} Faktur
            </VChip>
          </template>

          <!-- Total Tagihan -->
          <template #item.total_amount="{ item }">
            <span class="font-weight-bold font-mono text-body-2 text-primary">
              {{ formatCurrency(item.total_amount) }}
            </span>
          </template>

          <!-- Sudah Bayar -->
          <template #item.paid_amount="{ item }">
            <span class="font-weight-bold font-mono text-body-2 text-success">
              {{ formatCurrency(item.paid_amount) }}
            </span>
          </template>

          <!-- Sisa Hutang -->
          <template #item.remaining_amount="{ item }">
            <span class="font-weight-bold font-mono text-body-2 text-error">
              {{ formatCurrency(item.remaining_amount) }}
            </span>
          </template>

          <!-- Progres Pelunasan -->
          <template #item.progress="{ item }">
            <div style="min-width: 140px;" class="py-1">
              <div class="d-flex justify-space-between text-caption font-weight-bold mb-1">
                <span>{{ getStatementProgress(item) }}%</span>
                <span class="text-caption text-medium-emphasis font-weight-normal">
                  {{ item.status === 'paid' ? 'Lunas' : 'Sisa ' + formatCurrency(item.remaining_amount) }}
                </span>
              </div>
              <VProgressLinear
                :model-value="getStatementProgress(item)"
                height="6"
                rounded
                :color="item.status === 'paid' ? 'success' : (getStatementProgress(item) > 0 ? 'warning' : 'secondary')"
              />
            </div>
          </template>

          <!-- Jatuh Tempo -->
          <template #item.due_date="{ item }">
            <div>
              <div class="text-body-2" :class="isOverdue(item) ? 'text-error font-weight-bold' : ''">
                {{ formatDate(item.due_date) }}
              </div>
              <VChip
                v-if="isOverdue(item)"
                size="x-small"
                color="error"
                variant="flat"
                class="font-weight-bold mt-1"
              >
                Lewat Tempo
              </VChip>
              <VChip
                v-else-if="isDueSoon(item)"
                size="x-small"
                color="warning"
                variant="tonal"
                class="font-weight-bold mt-1"
              >
                Jatuh Tempo Segera
              </VChip>
              <span v-else class="text-caption text-medium-emphasis d-block">
                Bulan Depan
              </span>
            </div>
          </template>

          <!-- Status -->
          <template #item.status="{ item }">
            <VChip
              :color="getStatusBadge(item).color"
              size="small"
              class="font-weight-bold"
            >
              <VIcon :icon="getStatusBadge(item).icon" size="14" class="mr-1" />
              {{ getStatusBadge(item).text }}
            </VChip>
          </template>

          <!-- Aksi Tab 1 -->
          <template #item.actions="{ item }">
            <VBtn
              size="small"
              color="primary"
              :variant="item.status === 'paid' ? 'tonal' : 'flat'"
              prepend-icon="ri-checkbox-multiple-line"
              class="font-weight-bold"
              @click="openDetailDrawer(item.id)"
            >
              {{ item.status === 'paid' ? 'Rincian Barang' : 'Bayar / Pilih' }}
            </VBtn>
          </template>
        </VDataTableServer>
      </div>

      <!-- TAB 2: Invoices Breakdown View -->
      <div v-show="activeTab === 'invoices'">
        <div class="pa-4 border-b bg-var-theme-surface d-flex justify-space-between align-center flex-wrap gap-4">
          <div class="text-body-2 text-medium-emphasis">
            Daftar seluruh kuitansi penerimaan barang (Goods Receipts) dan status pengelompokan tagihan bulanannya.
          </div>
          <div class="d-flex align-center gap-3">
            <div style="width: 200px;">
              <VSelect
                v-model="selectedSupplier"
                :items="suppliers"
                item-title="name"
                item-value="id"
                label="Filter Supplier"
                placeholder="Semua Supplier"
                density="compact"
                variant="outlined"
                clearable
                hide-details
              />
            </div>
            <div style="width: 250px;">
              <VTextField
                v-model="searchQuery"
                placeholder="Cari no. faktur / PO..."
                prepend-inner-icon="ri-search-line"
                density="compact"
                variant="outlined"
                clearable
                hide-details
              />
            </div>
          </div>
        </div>

        <VDataTableServer
          v-model:items-per-page="invoiceItemsPerPage"
          v-model:page="invoicePage"
          :items="invoices"
          :items-length="totalInvoices"
          :headers="invoiceHeaders"
          :loading="isLoadingInvoices"
          class="text-no-wrap"
          @update:options="fetchInvoices"
        >
          <!-- No AP & Faktur Supplier -->
          <template #item.payable_number="{ item }">
            <div class="py-2">
              <div class="font-weight-bold font-mono text-primary text-body-2">
                {{ item.payable_number }}
              </div>
              <div v-if="item.invoice_number_supplier" class="text-caption text-high-emphasis">
                Faktur: <code>{{ item.invoice_number_supplier }}</code>
              </div>
              <div v-if="item.purchase_order" class="text-caption text-medium-emphasis">
                PO: {{ item.purchase_order.po_number }}
              </div>
            </div>
          </template>

          <!-- Tanggal Faktur -->
          <template #item.invoice_date="{ item }">
            <span class="text-body-2">{{ formatDate(item.invoice_date) }}</span>
          </template>

          <!-- Supplier -->
          <template #item.supplier.name="{ item }">
            <div>
              <div class="font-weight-bold text-body-2">{{ item.supplier?.name || '-' }}</div>
              <div class="text-caption text-medium-emphasis">{{ item.supplier?.phone || '-' }}</div>
            </div>
          </template>

          <!-- Cabang -->
          <template #item.branch.name="{ item }">
            <span class="text-body-2">{{ item.branch?.name || '-' }}</span>
          </template>

          <!-- Tagihan Bulanan (Statement) -->
          <template #item.payable_statement.statement_number="{ item }">
            <div v-if="item.payable_statement">
              <a
                href="javascript:void(0)"
                class="font-mono text-primary font-weight-bold text-caption text-decoration-underline"
                @click="openDetailDrawer(item.payable_statement.id)"
              >
                {{ item.payable_statement.statement_number }}
              </a>
              <div class="text-caption text-medium-emphasis">
                Periode: {{ formatMonthLabel(item.payable_statement.period_month) }}
              </div>
            </div>
            <span v-else class="text-disabled">-</span>
          </template>

          <!-- Total Nominal -->
          <template #item.total_amount="{ item }">
            <span class="font-weight-bold font-mono text-body-2 text-primary">
              {{ formatCurrency(item.total_amount) }}
            </span>
          </template>

          <!-- Aksi Tab 2 -->
          <template #item.actions="{ item }">
            <VBtn
              v-if="item.payable_statement"
              size="small"
              color="primary"
              variant="tonal"
              prepend-icon="ri-file-list-3-line"
              @click="openDetailDrawer(item.payable_statement.id)"
            >
              Lihat Tagihan
            </VBtn>
          </template>
        </VDataTableServer>
      </div>
    </VCard>

    <!-- Drawer Detail & Cicilan Tagihan Bulanan -->
    <PayableDetailDrawer
      v-model:is-drawer-open="isDetailDrawerVisible"
      :statement-id="selectedStatementId"
      @payment-recorded="onPaymentUpdated"
      @payment-voided="onPaymentUpdated"
    />
  </div>
</template>

<style scoped>
.border-dashed {
  border: 1px dashed rgba(var(--v-border-color), var(--v-border-opacity));
}
</style>

<route lang="yaml">
meta:
  action: read
  subject: Buku Hutang Supplier
</route>
