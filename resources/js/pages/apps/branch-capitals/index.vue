<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'

definePage({
  meta: {
    action: 'read',
    subject: 'Modal & ROI Cabang',
  },
})

const { show: showSnackbar } = useSnackbarStore()

// ============================
// State
// ============================
const activeTab      = ref('distribusi')
const branches       = ref([])
const products       = ref([])
const isLoading      = ref(false)
const isSubmitting   = ref(false)

// Distribusi tab
const distributions   = ref([])
const totalDistrib    = ref(0)
const distribPage     = ref(1)
const distribPerPage  = ref(10)
const filterBranch    = ref('all')
const filterStatus    = ref('all')
const filterPeriod    = ref(currentMonthStr())
const filterSearch    = ref('')

// Rekonsiliasi tab
const rekonData      = ref([])
const rekonPeriod    = ref(currentMonthStr())
const rekonBranch    = ref('all')
const isRekonLoading = ref(false)

// Summary KPI
const summary = ref({
  total_capital_value: 0,
  total_transactions: 0,
  total_sales: 0,
  branch_breakdown: [],
})

// Form kirim modal barang
const showSendDialog  = ref(false)
const formItems       = ref([{ product_id: null, qty: 1, cost_price: 0, subtotal: 0 }])
const formBranchId    = ref(null)
const formNotes       = ref('')

function currentMonthStr() {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
}

const totalModalValue = computed(() =>
  formItems.value.reduce((s, i) => s + (parseFloat(i.qty || 0) * parseFloat(i.cost_price || 0)), 0)
)

// ============================
// Formatters
// ============================
const fmtCurrency = v => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v || 0)
const fmtDate = d => d ? new Date(d).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' }) : '-'

const statusColor = s => ({
  completed: 'success', received: 'success',
  in_transit: 'info', ready_for_pickup: 'primary', approved: 'primary',
  pending: 'warning', rejected: 'error', cancelled: 'error',
}[s] || 'secondary')

const statusLabel = s => ({
  pending: 'Menunggu', approved: 'Disetujui', ready_for_pickup: 'Siap Kirim',
  in_transit: 'Dalam Pengiriman', completed: 'Selesai',
  rejected: 'Ditolak', cancelled: 'Dibatalkan',
}[s] || s)

// ============================
// Fetch data
// ============================
const fetchBranches = async () => {
  try {
    const res = await $api('/apps/branches?simple=true')
    if (res?.length) {
      branches.value = res.map(b => ({ title: b.name, value: b.id }))
    }
  } catch (e) { console.error(e) }
}

const fetchProducts = async () => {
  try {
    const res = await $api('/apps/products?per_page=1000')
    products.value = (res?.data || res || []).map(p => ({
      title: `${p.name}${p.sku ? ' [' + p.sku + ']' : ''}`,
      value: p.id,
      cost_price: p.cost_price || 0,
    }))
  } catch (e) { console.error(e) }
}

const fetchSummary = async () => {
  try {
    const res = await $api('/apps/branch-capitals/summary', {
      query: { period: filterPeriod.value, branch_id: filterBranch.value }
    })
    summary.value = res
  } catch (e) { console.error(e) }
}

const fetchDistributions = async () => {
  isLoading.value = true
  try {
    const res = await $api('/apps/branch-capitals/distributions', {
      query: {
        branch_id: filterBranch.value,
        status: filterStatus.value,
        period: filterPeriod.value,
        search: filterSearch.value,
        per_page: distribPerPage.value,
        page: distribPage.value,
      }
    })
    distributions.value = res.data
    totalDistrib.value  = res.total
  } catch (e) { console.error(e) }
  finally { isLoading.value = false }
}

const fetchRekonsiliasi = async () => {
  isRekonLoading.value = true
  try {
    const res = await $api('/apps/branch-capitals/reconciliation', {
      query: { period: rekonPeriod.value, branch_id: rekonBranch.value }
    })
    rekonData.value = res.data
  } catch (e) { console.error(e) }
  finally { isRekonLoading.value = false }
}

// ============================
// Form submit: kirim modal barang
// ============================
const onProductSelect = (idx) => {
  const p = products.value.find(p => p.value === formItems.value[idx].product_id)
  if (p) {
    formItems.value[idx].cost_price = p.cost_price
    recalcSubtotal(idx)
  }
}

const recalcSubtotal = (idx) => {
  const item = formItems.value[idx]
  item.subtotal = (parseFloat(item.qty || 0) * parseFloat(item.cost_price || 0))
}

const addItem = () => formItems.value.push({ product_id: null, qty: 1, cost_price: 0, subtotal: 0 })
const removeItem = (idx) => { if (formItems.value.length > 1) formItems.value.splice(idx, 1) }

const submitKirimModal = async () => {
  if (!formBranchId.value) { showSnackbar('Pilih cabang tujuan terlebih dahulu.', 'error'); return }
  const invalid = formItems.value.some(i => !i.product_id || i.qty < 1)
  if (invalid) { showSnackbar('Lengkapi semua item produk dan kuantitas.', 'error'); return }

  isSubmitting.value = true
  try {
    // Ambil ID Gudang Pusat (branch type = warehouse, atau source_branch dari server default)
    const sourceBranch = branches.value[0] // akan dikonfigurasi dari server; pakai yang pertama sebagai fallback
    // Cari branch warehouse/pusat
    const warehouseRes = await $api('/apps/branches?type=warehouse&simple=true')
    const sourceId = warehouseRes?.[0]?.id || sourceBranch?.value

    await $api('/apps/stock-transfers', {
      method: 'POST',
      body: {
        source_branch_id:      sourceId,
        destination_branch_id: formBranchId.value,
        is_capital_transfer:   true,
        notes:                 formNotes.value || 'Distribusi Modal Barang dari Pusat',
        items: formItems.value.map(i => ({
          product_id: i.product_id,
          qty:        i.qty,
          cost_price: i.cost_price,
        })),
      }
    })

    showSnackbar('Distribusi modal barang berhasil dikirim!', 'success')
    showSendDialog.value = false
    formItems.value = [{ product_id: null, qty: 1, cost_price: 0, subtotal: 0 }]
    formBranchId.value = null
    formNotes.value = ''
    fetchDistributions()
    fetchSummary()
  } catch (e) {
    showSnackbar(e?.data?.message || 'Gagal mengirim distribusi modal barang.', 'error')
  } finally {
    isSubmitting.value = false
  }
}

// ============================
// Watchers
// ============================
watch([filterBranch, filterStatus, filterPeriod, filterSearch], () => {
  distribPage.value = 1
  fetchDistributions()
  fetchSummary()
})

watch([rekonPeriod, rekonBranch], () => { fetchRekonsiliasi() })

watch(activeTab, val => {
  if (val === 'rekonsiliasi') fetchRekonsiliasi()
})

// ============================
// Init
// ============================
onMounted(async () => {
  await fetchBranches()
  fetchProducts()
  fetchSummary()
  fetchDistributions()
})
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h4 class="text-h4 mb-1">
          <VIcon icon="ri-store-3-line" color="primary" class="mr-2" />
          Distribusi Modal Barang
        </h4>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Kelola pengiriman barang dari Pusat/Gudang ke Cabang dan pantau rekonsiliasi omset akhir bulan.
        </p>
      </div>
      <VBtn
        color="primary"
        prepend-icon="ri-send-plane-line"
        @click="showSendDialog = true"
      >
        Kirim Modal Barang ke Cabang
      </VBtn>
    </div>

    <!-- KPI Summary Cards -->
    <VRow class="mb-6">
      <VCol cols="12" md="4">
        <VCard variant="tonal" color="primary" class="h-100">
          <VCardText class="d-flex align-center gap-4">
            <VAvatar color="primary" size="52" rounded="lg">
              <VIcon icon="ri-store-3-line" size="28" />
            </VAvatar>
            <div>
              <div class="text-caption text-medium-emphasis text-uppercase font-weight-bold">Total Nilai Modal Barang Dikirim</div>
              <div class="text-h5 font-weight-bold text-primary">{{ fmtCurrency(summary.total_capital_value) }}</div>
              <div class="text-caption text-medium-emphasis">{{ summary.total_transactions }} distribusi • {{ filterPeriod }}</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="4">
        <VCard variant="tonal" color="success" class="h-100">
          <VCardText class="d-flex align-center gap-4">
            <VAvatar color="success" size="52" rounded="lg">
              <VIcon icon="ri-money-dollar-circle-line" size="28" />
            </VAvatar>
            <div>
              <div class="text-caption text-medium-emphasis text-uppercase font-weight-bold">Total Omset Penjualan Cabang</div>
              <div class="text-h5 font-weight-bold text-success">{{ fmtCurrency(summary.total_sales) }}</div>
              <div class="text-caption text-medium-emphasis">Periode {{ filterPeriod }}</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="4">
        <VCard variant="tonal" color="warning" class="h-100">
          <VCardText class="d-flex align-center gap-4">
            <VAvatar color="warning" size="52" rounded="lg">
              <VIcon icon="ri-scales-3-line" size="28" />
            </VAvatar>
            <div>
              <div class="text-caption text-medium-emphasis text-uppercase font-weight-bold">Estimasi Sisa Modal Belum Terjual</div>
              <div class="text-h5 font-weight-bold text-warning">
                {{ fmtCurrency(Math.max(0, summary.total_capital_value - summary.total_sales)) }}
              </div>
              <div class="text-caption text-medium-emphasis">
                {{ summary.total_capital_value > 0 ? Math.round(Math.min(100, (summary.total_sales / summary.total_capital_value) * 100)) : 0 }}% Terjual
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Tabs -->
    <VTabs v-model="activeTab" class="mb-4">
      <VTab value="distribusi">
        <VIcon icon="ri-truck-line" class="mr-2" size="18" /> Riwayat Distribusi Modal
      </VTab>
      <VTab value="rekonsiliasi">
        <VIcon icon="ri-bar-chart-grouped-line" class="mr-2" size="18" /> Rekonsiliasi Akhir Bulan
      </VTab>
    </VTabs>

    <VWindow v-model="activeTab">
      <!-- =========================================== -->
      <!-- TAB 1: DISTRIBUSI MODAL BARANG             -->
      <!-- =========================================== -->
      <VWindowItem value="distribusi">

        <!-- Filters -->
        <VCard class="mb-4">
          <VCardText>
            <VRow dense>
              <VCol cols="12" md="3">
                <VSelect
                  v-model="filterBranch"
                  :items="[{ title: 'Semua Cabang', value: 'all' }, ...branches]"
                  label="Filter Cabang"
                  variant="outlined"
                  density="compact"
                  hide-details
                />
              </VCol>
              <VCol cols="12" md="3">
                <VSelect
                  v-model="filterStatus"
                  :items="[
                    { title: 'Semua Status', value: 'all' },
                    { title: 'Menunggu', value: 'pending' },
                    { title: 'Dalam Pengiriman', value: 'in_transit' },
                    { title: 'Selesai Diterima', value: 'completed' },
                    { title: 'Dibatalkan', value: 'cancelled' },
                  ]"
                  label="Status"
                  variant="outlined"
                  density="compact"
                  hide-details
                />
              </VCol>
              <VCol cols="12" md="2">
                <VTextField
                  v-model="filterPeriod"
                  type="month"
                  label="Periode"
                  variant="outlined"
                  density="compact"
                  hide-details
                />
              </VCol>
              <VCol cols="12" md="4">
                <VTextField
                  v-model="filterSearch"
                  label="Cari No. Referensi"
                  variant="outlined"
                  density="compact"
                  hide-details
                  prepend-inner-icon="ri-search-line"
                  clearable
                />
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <!-- Table -->
        <VCard>
          <VDataTableServer
            :headers="[
              { title: 'Ref. Distribusi', key: 'reference_no' },
              { title: 'Tgl Dibuat', key: 'created_at' },
              { title: 'Dari', key: 'source_branch_id' },
              { title: 'Ke Cabang', key: 'destination_branch_id' },
              { title: 'Jml Item', key: 'items_count', align: 'center' },
              { title: 'Nilai Modal (HPP)', key: 'capital_value', align: 'end' },
              { title: 'Status', key: 'status', align: 'center' },
              { title: 'Aksi', key: 'actions', sortable: false, align: 'center' },
            ]"
            :items="distributions"
            :items-length="totalDistrib"
            :loading="isLoading"
            :page="distribPage"
            :items-per-page="distribPerPage"
            class="text-no-wrap"
            show-expand
            @update:page="distribPage = $event; fetchDistributions()"
            @update:items-per-page="distribPerPage = $event; fetchDistributions()"
          >
            <template #item.reference_no="{ item }">
              <span class="font-weight-bold text-primary font-mono">{{ item.reference_no }}</span>
            </template>
            <template #item.created_at="{ item }">
              {{ fmtDate(item.created_at) }}
            </template>
            <template #item.source_branch_id="{ item }">
              <VChip size="small" color="secondary" variant="tonal">
                <VIcon icon="ri-building-line" size="12" class="mr-1" />
                {{ item.source_branch?.name || '-' }}
              </VChip>
            </template>
            <template #item.destination_branch_id="{ item }">
              <VChip size="small" color="primary" variant="tonal">
                <VIcon icon="ri-store-2-line" size="12" class="mr-1" />
                {{ item.destination_branch?.name || '-' }}
              </VChip>
            </template>
            <template #item.items_count="{ item }">
              <VChip size="small" color="info" variant="tonal">
                {{ item.items?.length || 0 }} produk
              </VChip>
            </template>
            <template #item.capital_value="{ item }">
              <span class="font-weight-bold text-primary font-mono">
                {{ item.capital_value ? fmtCurrency(item.capital_value) : '-' }}
              </span>
            </template>
            <template #item.status="{ item }">
              <VChip :color="statusColor(item.status)" size="small" variant="tonal" class="font-weight-bold">
                {{ statusLabel(item.status) }}
              </VChip>
            </template>
            <template #item.actions="{ item }">
              <VBtn
                icon="ri-eye-line"
                variant="text"
                size="small"
                color="primary"
                :href="`/mutasi-stok?id=${item.id}`"
              />
            </template>

            <!-- Expanded row: daftar produk -->
            <template #expanded-row="{ columns, item }">
              <tr>
                <td :colspan="columns.length" class="pa-4" style="background: rgba(var(--v-theme-primary), 0.04);">
                  <div class="text-subtitle-2 text-primary font-weight-bold mb-3">
                    <VIcon icon="ri-list-unordered" size="16" class="mr-1" />
                    Daftar Barang Modal — {{ item.reference_no }}
                  </div>
                  <VTable density="compact" class="rounded border">
                    <thead>
                      <tr>
                        <th>Produk</th>
                        <th class="text-center">Qty Dikirim</th>
                        <th class="text-end">Harga Pokok (HPP)</th>
                        <th class="text-end">Nilai Modal</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="row in (item.items || [])" :key="row.id">
                        <td>
                          <span class="font-weight-medium">{{ row.product?.name || '-' }}</span>
                          <span v-if="row.product?.sku" class="text-caption text-medium-emphasis ml-2">[{{ row.product.sku }}]</span>
                        </td>
                        <td class="text-center font-mono">{{ row.qty }} pcs</td>
                        <td class="text-end font-mono">{{ fmtCurrency(row.product?.cost_price || 0) }}</td>
                        <td class="text-end font-weight-bold font-mono text-primary">{{ fmtCurrency((row.qty || 0) * (row.product?.cost_price || 0)) }}</td>
                      </tr>
                      <tr v-if="!(item.items?.length)">
                        <td colspan="4" class="text-center text-medium-emphasis py-3">Tidak ada detail item.</td>
                      </tr>
                    </tbody>
                    <tfoot v-if="item.capital_value">
                      <tr class="bg-var-theme-surface">
                        <td colspan="3" class="font-weight-bold text-uppercase text-right">Total Nilai Modal HPP:</td>
                        <td class="text-end font-weight-bold text-primary font-mono text-subtitle-2">{{ fmtCurrency(item.capital_value) }}</td>
                      </tr>
                    </tfoot>
                  </VTable>
                </td>
              </tr>
            </template>
            <template #no-data>
              <div class="text-center py-8 text-medium-emphasis">
                <VIcon icon="ri-store-3-line" size="48" class="mb-3 opacity-30" />
                <div>Belum ada distribusi modal barang.</div>
              </div>
            </template>
          </VDataTableServer>
        </VCard>
      </VWindowItem>

      <!-- =========================================== -->
      <!-- TAB 2: REKONSILIASI AKHIR BULAN            -->
      <!-- =========================================== -->
      <VWindowItem value="rekonsiliasi">
        <!-- Filter rekonsiliasi -->
        <VCard class="mb-4">
          <VCardText>
            <VRow dense align="center">
              <VCol cols="12" md="3">
                <VTextField
                  v-model="rekonPeriod"
                  type="month"
                  label="Periode Rekonsiliasi"
                  variant="outlined"
                  density="compact"
                  hide-details
                />
              </VCol>
              <VCol cols="12" md="3">
                <VSelect
                  v-model="rekonBranch"
                  :items="[{ title: 'Semua Cabang', value: 'all' }, ...branches]"
                  label="Filter Cabang"
                  variant="outlined"
                  density="compact"
                  hide-details
                />
              </VCol>
              <VCol cols="auto">
                <VBtn
                  color="primary"
                  variant="tonal"
                  prepend-icon="ri-refresh-line"
                  :loading="isRekonLoading"
                  @click="fetchRekonsiliasi"
                >
                  Refresh
                </VBtn>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <div v-if="isRekonLoading" class="text-center py-12">
          <VProgressCircular indeterminate color="primary" size="48" />
          <div class="mt-3 text-medium-emphasis">Menghitung rekonsiliasi...</div>
        </div>

        <div v-else-if="rekonData.length === 0" class="text-center py-12 text-medium-emphasis">
          <VIcon icon="ri-bar-chart-grouped-line" size="64" class="mb-3 opacity-30" />
          <div>Tidak ada data distribusi modal barang pada periode ini.</div>
        </div>

        <VRow v-else>
          <VCol
            v-for="branch in rekonData"
            :key="branch.branch_id"
            cols="12"
            md="6"
            lg="4"
          >
            <VCard class="h-100" :class="{
              'border-success': branch.status === 'seimbang',
              'border-error': branch.status === 'selisih',
              'border-warning': branch.status === 'surplus',
            }">
              <VCardTitle class="d-flex align-center justify-space-between pt-4 px-4 pb-2">
                <div class="d-flex align-center gap-2">
                  <VAvatar color="primary" size="36" rounded="lg">
                    <VIcon icon="ri-store-2-line" size="20" />
                  </VAvatar>
                  <div>
                    <div class="text-subtitle-1 font-weight-bold">{{ branch.branch_name }}</div>
                    <div class="text-caption text-medium-emphasis">{{ branch.period }}</div>
                  </div>
                </div>
                <VChip
                  :color="branch.status === 'seimbang' ? 'success' : branch.status === 'selisih' ? 'error' : 'warning'"
                  size="small"
                  class="font-weight-bold"
                >
                  <VIcon :icon="branch.status === 'seimbang' ? 'ri-check-line' : branch.status === 'selisih' ? 'ri-alert-line' : 'ri-arrow-up-line'" size="14" class="mr-1" />
                  {{ branch.status === 'seimbang' ? 'Seimbang ✓' : branch.status === 'selisih' ? 'Ada Selisih' : 'Surplus' }}
                </VChip>
              </VCardTitle>

              <VCardText class="pt-0">
                <!-- Progress bar penjualan -->
                <div class="mb-3">
                  <div class="d-flex justify-space-between text-caption mb-1">
                    <span class="text-medium-emphasis">Progress Terjual</span>
                    <span class="font-weight-bold text-primary">{{ branch.pct_terjual }}%</span>
                  </div>
                  <VProgressLinear
                    :model-value="branch.pct_terjual"
                    :color="branch.pct_terjual >= 80 ? 'success' : branch.pct_terjual >= 50 ? 'warning' : 'error'"
                    rounded
                    height="8"
                    bg-color="rgba(var(--v-theme-on-surface), 0.08)"
                  />
                </div>

                <!-- Rincian angka -->
                <VDivider class="mb-3" />
                <div class="d-flex justify-space-between mb-2">
                  <div class="text-caption text-medium-emphasis d-flex align-center gap-1">
                    <VIcon icon="ri-store-3-line" size="14" color="primary" />
                    Modal Barang Dikirim
                  </div>
                  <span class="font-weight-bold font-mono text-caption text-primary">{{ fmtCurrency(branch.modal_value) }}</span>
                </div>
                <div class="d-flex justify-space-between mb-2">
                  <div class="text-caption text-medium-emphasis d-flex align-center gap-1">
                    <VIcon icon="ri-money-dollar-circle-line" size="14" color="success" />
                    Omset Penjualan
                  </div>
                  <span class="font-weight-bold font-mono text-caption text-success">{{ fmtCurrency(branch.omset_penjualan) }}</span>
                </div>
                <div class="d-flex justify-space-between mb-2">
                  <div class="text-caption text-medium-emphasis d-flex align-center gap-1">
                    <VIcon icon="ri-archive-line" size="14" color="warning" />
                    Estimasi Sisa Stok (Nilai HPP)
                  </div>
                  <span class="font-weight-bold font-mono text-caption text-warning">{{ fmtCurrency(branch.sisa_stok_nilai) }}</span>
                </div>
                <VDivider class="my-2" />
                <div class="d-flex justify-space-between">
                  <div class="text-caption font-weight-bold d-flex align-center gap-1">
                    <VIcon
                      :icon="branch.selisih === 0 ? 'ri-check-double-line' : branch.selisih > 0 ? 'ri-arrow-up-circle-line' : 'ri-arrow-down-circle-line'"
                      :color="branch.selisih === 0 ? 'success' : branch.selisih > 0 ? 'info' : 'error'"
                      size="14"
                    />
                    Selisih / Susut
                  </div>
                  <span
                    class="font-weight-bold font-mono text-caption"
                    :class="branch.selisih < 0 ? 'text-error' : branch.selisih > 0 ? 'text-info' : 'text-success'"
                  >
                    {{ branch.selisih >= 0 ? '+' : '' }}{{ fmtCurrency(branch.selisih) }}
                  </span>
                </div>

                <!-- Detail produk per cabang -->
                <VExpansionPanels class="mt-3" variant="accordion">
                  <VExpansionPanel>
                    <VExpansionPanelTitle class="text-caption font-weight-bold py-2 px-3">
                      <VIcon icon="ri-list-check-2" size="14" class="mr-2" />
                      Detail Per Produk ({{ branch.product_details?.length || 0 }} item)
                    </VExpansionPanelTitle>
                    <VExpansionPanelText class="pa-0">
                      <VTable density="compact" class="text-caption">
                        <thead>
                          <tr>
                            <th>Produk</th>
                            <th class="text-center">Dikirim</th>
                            <th class="text-center">Terjual</th>
                            <th class="text-center">Sisa</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="pd in branch.product_details" :key="pd.product_id">
                            <td>{{ pd.product_name }}</td>
                            <td class="text-center font-mono">{{ pd.qty_dikirim }}</td>
                            <td class="text-center font-mono text-success">{{ pd.qty_terjual }}</td>
                            <td class="text-center font-mono text-warning">{{ pd.qty_dikirim - pd.qty_terjual }}</td>
                          </tr>
                          <tr v-if="!branch.product_details?.length">
                            <td colspan="4" class="text-center text-medium-emphasis py-2">Tidak ada data.</td>
                          </tr>
                        </tbody>
                      </VTable>
                    </VExpansionPanelText>
                  </VExpansionPanel>
                </VExpansionPanels>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </VWindowItem>
    </VWindow>

    <!-- =========================================== -->
    <!-- DIALOG: Kirim Modal Barang ke Cabang       -->
    <!-- =========================================== -->
    <VDialog v-model="showSendDialog" max-width="800" scrollable>
      <VCard>
        <VCardTitle class="text-h5 pt-5 px-5 pb-0 d-flex align-center gap-2">
          <VIcon icon="ri-send-plane-line" color="primary" />
          Kirim Modal Barang ke Cabang
        </VCardTitle>
        <VCardText class="px-5 pt-4">
          <VAlert color="info" variant="tonal" class="mb-4" density="compact">
            <VIcon icon="ri-information-line" /> Barang akan otomatis diproses sebagai <strong>Distribusi Stok Modal</strong> dari Gudang Pusat ke cabang tujuan. Stok Gudang Pusat akan berkurang setelah diterima.
          </VAlert>

          <!-- Cabang tujuan -->
          <VSelect
            v-model="formBranchId"
            :items="branches"
            label="Cabang Tujuan *"
            variant="outlined"
            class="mb-4"
          />

          <!-- Daftar produk -->
          <div class="text-subtitle-2 font-weight-bold mb-2">Daftar Barang Modal</div>
          <VTable class="mb-3 border rounded">
            <thead>
              <tr>
                <th style="width: 40%">Produk</th>
                <th style="width: 15%">Qty</th>
                <th style="width: 25%">HPP / Unit (Rp)</th>
                <th style="width: 15%">Subtotal</th>
                <th style="width: 5%"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, idx) in formItems" :key="idx">
                <td class="pa-2">
                  <VAutocomplete
                    v-model="item.product_id"
                    :items="products"
                    variant="outlined"
                    density="compact"
                    hide-details
                    placeholder="Pilih produk..."
                    @update:model-value="onProductSelect(idx)"
                  />
                </td>
                <td class="pa-2">
                  <VTextField
                    v-model.number="item.qty"
                    type="number"
                    min="1"
                    variant="outlined"
                    density="compact"
                    hide-details
                    @update:model-value="recalcSubtotal(idx)"
                  />
                </td>
                <td class="pa-2">
                  <VTextField
                    v-model.number="item.cost_price"
                    type="number"
                    min="0"
                    variant="outlined"
                    density="compact"
                    hide-details
                    @update:model-value="recalcSubtotal(idx)"
                  />
                </td>
                <td class="pa-2 text-right font-mono font-weight-bold text-primary">
                  {{ fmtCurrency(item.subtotal) }}
                </td>
                <td class="pa-1 text-center">
                  <VBtn
                    icon="ri-delete-bin-line"
                    size="small"
                    variant="text"
                    color="error"
                    :disabled="formItems.length <= 1"
                    @click="removeItem(idx)"
                  />
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="3" class="pa-3 font-weight-bold text-right text-uppercase">Total Nilai Modal HPP:</td>
                <td class="pa-3 font-weight-bold font-mono text-primary text-subtitle-2">{{ fmtCurrency(totalModalValue) }}</td>
                <td></td>
              </tr>
            </tfoot>
          </VTable>
          <VBtn variant="tonal" color="primary" prepend-icon="ri-add-line" size="small" @click="addItem" class="mb-4">
            + Tambah Produk
          </VBtn>

          <!-- Catatan -->
          <VTextField
            v-model="formNotes"
            label="Catatan (opsional)"
            variant="outlined"
            placeholder="Misal: Modal awal toko baru, tambahan stok September..."
          />
        </VCardText>
        <VCardActions class="px-5 pb-5">
          <VSpacer />
          <VBtn variant="outlined" color="secondary" @click="showSendDialog = false">Batal</VBtn>
          <VBtn
            color="primary"
            :loading="isSubmitting"
            prepend-icon="ri-send-plane-line"
            @click="submitKirimModal"
          >
            Kirim Modal Barang
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Modal & ROI Cabang
</route>
