<script setup>
import { ref, onMounted, watch } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import PayableDetailDrawer from './PayableDetailDrawer.vue'

const payables = ref([])
const isLoading = ref(false)
const totalItems = ref(0)
const page = ref(1)
const itemsPerPage = ref(10)

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
const selectedPayableId = ref(null)

const snackbar = useSnackbarStore()

const formatCurrency = value => {
  if (!value || isNaN(value)) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)
}

const formatDate = dateString => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
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

const fetchPayables = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
    }

    if (selectedStatus.value) params.status = selectedStatus.value
    if (selectedSupplier.value) params.supplier_id = selectedSupplier.value
    if (selectedBranch.value) params.branch_id = selectedBranch.value
    if (searchQuery.value) params.search = searchQuery.value

    const res = await $api('/apps/payables', { query: params })

    payables.value = res.data || res || []
    if (res.total !== undefined) {
      totalItems.value = res.total
    }
    if (res.summary) {
      summary.value = res.summary
    }
  } catch (error) {
    console.error('Failed to fetch payables:', error)
    snackbar.show('Gagal mengambil data buku hutang supplier', 'error')
  } finally {
    isLoading.value = false
  }
}

watch(searchQuery, () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchPayables()
  }, 450)
})

watch([selectedStatus, selectedSupplier, selectedBranch], () => {
  page.value = 1
  fetchPayables()
})

onMounted(async () => {
  await fetchInitialOptions()
  fetchPayables()
})

const tableHeaders = [
  { title: 'NO. AP & FAKTUR SUPPLIER', key: 'payable_number', minWidth: '220px' },
  { title: 'SUPPLIER / VENDOR', key: 'supplier.name', minWidth: '180px' },
  { title: 'CABANG', key: 'branch.name' },
  { title: 'TOTAL TAGIHAN', key: 'total_amount', align: 'end' },
  { title: 'SUDAH BAYAR', key: 'paid_amount', align: 'end' },
  { title: 'SISA HUTANG', key: 'remaining_amount', align: 'end' },
  { title: 'JATUH TEMPO', key: 'due_date' },
  { title: 'STATUS', key: 'status', align: 'center' },
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

const getStatusBadge = item => {
  if (item.status === 'paid') return { color: 'success', text: 'Lunas', icon: 'ri-check-double-line' }
  if (isOverdue(item)) return { color: 'error', text: 'Lewat Jatuh Tempo', icon: 'ri-error-warning-line' }
  if (item.status === 'partial') return { color: 'warning', text: 'Dicicil', icon: 'ri-time-line' }
  return { color: 'secondary', text: 'Belum Dibayar', icon: 'ri-file-list-line' }
}

const openDetailDrawer = payableId => {
  selectedPayableId.value = payableId
  isDetailDrawerVisible.value = true
}

const onPaymentUpdated = () => {
  fetchPayables()
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
          Kelola seluruh kewajiban tagihan pengadaan barang ke supplier, jatuh tempo pembayaran, dan riwayat cicilan.
        </p>
      </div>
    </div>

    <!-- Financial KPI Summary Cards -->
    <VRow class="mb-6">
      <!-- 1. Total Hutang Supplier -->
      <VCol cols="12" sm="6" md="3">
        <VCard class="h-100 shadow-xs border-dashed">
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <span class="text-caption text-medium-emphasis font-weight-medium">Total Tagihan Hutang:</span>
              <h5 class="text-h5 font-weight-bold font-mono text-primary mt-1">
                {{ formatCurrency(summary.total_payable) }}
              </h5>
              <span class="text-caption text-medium-emphasis">
                {{ summary.count_total }} Transaksi PO/Faktur
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
                {{ summary.count_paid }} Faktur Lunas
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
                {{ summary.count_unpaid + summary.count_partial }} Faktur Aktif
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
                {{ summary.count_overdue }} Faktur Menunggak
              </span>
            </div>
            <VAvatar color="error" variant="flat" size="48" class="rounded-xl">
              <VIcon icon="ri-alarm-warning-line" color="white" size="24" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Main Table Card -->
    <VCard class="rounded-xl overflow-hidden shadow-xs border">
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

          <div style="width: 250px;">
            <VTextField
              v-model="searchQuery"
              placeholder="Cari faktur / supplier..."
              prepend-inner-icon="ri-search-line"
              density="compact"
              variant="outlined"
              clearable
              hide-details
            />
          </div>
        </div>
      </div>

      <!-- Data Table -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :items="payables"
        :items-length="totalItems"
        :headers="tableHeaders"
        :loading="isLoading"
        class="text-no-wrap"
        @update:options="fetchPayables"
      >
        <!-- No. AP & Faktur Supplier -->
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

        <!-- Aksi -->
        <template #item.actions="{ item }">
          <VBtn
            size="small"
            color="primary"
            variant="tonal"
            prepend-icon="ri-wallet-3-line"
            class="font-weight-bold"
            @click="openDetailDrawer(item.id)"
          >
            {{ item.status === 'paid' ? 'Detail' : 'Bayar / Cicil' }}
          </VBtn>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Drawer Detail & Cicilan -->
    <PayableDetailDrawer
      v-model:is-drawer-open="isDetailDrawerVisible"
      :payable-id="selectedPayableId"
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
