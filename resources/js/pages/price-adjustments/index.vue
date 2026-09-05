<script setup>
import { ref, onMounted, watch } from 'vue'
import { paginationMeta } from '@/utils/paginationMeta'
import { useSnackbarStore } from '@/stores/snackbar'
import AddNewAdjustmentDrawer from './AddNewAdjustmentDrawer.vue'
import AdjustmentDetailDialog from './AdjustmentDetailDialog.vue'

const snackbar = useSnackbarStore()

// Tab state
const activeTab = ref('documents') // 'documents' or 'history'

// Shared options
const branches = ref([])
const categories = ref([])

// -------------------------------------------------------------
// TAB 1: DOKUMEN PENYESUAIAN HARGA
// -------------------------------------------------------------
const adjustments = ref([])
const totalAdjustments = ref(0)
const isLoading = ref(false)
const isDownloadingPdf = ref({})

// Filters for Documents
const searchDoc = ref('')
const selectedBranch = ref('all')
const selectedStatus = ref('all')
const startDate = ref('')
const endDate = ref('')
const pageDoc = ref(1)
const itemsPerPageDoc = ref(10)

// Dialog & Drawer States
const isAddDrawerVisible = ref(false)
const isDetailDialogVisible = ref(false)
const selectedAdjustmentId = ref(null)

const docHeaders = [
  { title: 'NO. DOKUMEN / SK', key: 'adjustment_number', sortable: false },
  { title: 'JUDUL PENYESUAIAN', key: 'title', sortable: false },
  { title: 'TANGGAL BERLAKU', key: 'effective_date', sortable: false },
  { title: 'TARGET CABANG', key: 'branch', sortable: false },
  { title: 'JUMLAH SKU', key: 'total_items', align: 'center', sortable: false },
  { title: 'STATUS', key: 'status', sortable: false },
  { title: 'PEMBUAT / PENGESAH', key: 'creator', sortable: false },
  { title: 'AKSI', key: 'actions', align: 'center', sortable: false },
]

// -------------------------------------------------------------
// TAB 2: RIWAYAT PERUBAHAN HARGA (AUDIT TRAIL)
// -------------------------------------------------------------
const histories = ref([])
const totalHistories = ref(0)
const isHistoryLoading = ref(false)

const searchHistory = ref('')
const selectedHistoryBranch = ref('all')
const historyStartDate = ref('')
const historyEndDate = ref('')
const pageHistory = ref(1)
const itemsPerPageHistory = ref(15)

const historyHeaders = [
  { title: 'TANGGAL EFEKTIF', key: 'effective_date', sortable: false },
  { title: 'NO. DOKUMEN', key: 'adjustment_number', sortable: false },
  { title: 'PRODUK & SKU', key: 'product', sortable: false },
  { title: 'CABANG', key: 'branch', sortable: false },
  { title: 'HPP MODAL', key: 'cost_price', align: 'end', sortable: false },
  { title: 'HARGA LAMA', key: 'old_price', align: 'end', sortable: false },
  { title: 'HARGA BARU', key: 'new_price', align: 'end', sortable: false },
  { title: 'KENAIKAN / SELISIH', key: 'diff', align: 'end', sortable: false },
  { title: 'ALASAN & PENGESAH', key: 'reason', sortable: false },
]

// Helpers
const formatCurrency = val => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val || 0)
}

const formatDate = dateStr => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

// Fetch Initial Options
const fetchOptions = async () => {
  try {
    const [branchRes, catRes] = await Promise.all([
      $api('/apps/branches').catch(() => []),
      $api('/apps/categories').catch(() => []),
    ])
    branches.value = Array.isArray(branchRes) ? branchRes : (branchRes.data || [])
    categories.value = Array.isArray(catRes) ? catRes : (catRes.data || [])
  } catch (e) {
    console.error('Error fetching options:', e)
  }
}

// Fetch Document List
const fetchDocuments = async () => {
  isLoading.value = true
  try {
    const res = await $api('/apps/price-adjustments', {
      query: {
        page: pageDoc.value,
        itemsPerPage: itemsPerPageDoc.value,
        q: searchDoc.value,
        branch_id: selectedBranch.value,
        status: selectedStatus.value,
        start_date: startDate.value,
        end_date: endDate.value,
      },
    })
    adjustments.value = res.data || []
    totalAdjustments.value = res.total || 0
  } catch (err) {
    console.error('Error fetching price adjustments:', err)
    snackbar.showSnackbar('Gagal memuat dokumen penyesuaian harga.', 'error')
  } finally {
    isLoading.value = false
  }
}

// Fetch History List
const fetchHistories = async () => {
  isHistoryLoading.value = true
  try {
    const res = await $api('/apps/price-adjustments/history', {
      query: {
        page: pageHistory.value,
        itemsPerPage: itemsPerPageHistory.value,
        q: searchHistory.value,
        branch_id: selectedHistoryBranch.value,
        start_date: historyStartDate.value,
        end_date: historyEndDate.value,
      },
    })
    histories.value = res.data || []
    totalHistories.value = res.total || 0
  } catch (err) {
    console.error('Error fetching price history:', err)
    snackbar.showSnackbar('Gagal memuat riwayat perubahan harga.', 'error')
  } finally {
    isHistoryLoading.value = false
  }
}

// Watchers for Document Filters
let searchDocDebounce = null
watch([searchDoc, selectedBranch, selectedStatus, startDate, endDate], () => {
  clearTimeout(searchDocDebounce)
  searchDocDebounce = setTimeout(() => {
    pageDoc.value = 1
    fetchDocuments()
  }, 300)
})

watch([pageDoc, itemsPerPageDoc], () => {
  fetchDocuments()
})

// Watchers for History Filters
let searchHistoryDebounce = null
watch([searchHistory, selectedHistoryBranch, historyStartDate, historyEndDate], () => {
  clearTimeout(searchHistoryDebounce)
  searchHistoryDebounce = setTimeout(() => {
    pageHistory.value = 1
    fetchHistories()
  }, 300)
})

watch([pageHistory, itemsPerPageHistory], () => {
  fetchHistories()
})

watch(activeTab, tab => {
  if (tab === 'history') {
    fetchHistories()
  } else {
    fetchDocuments()
  }
})

// Actions
const viewDetail = item => {
  selectedAdjustmentId.value = item.id
  isDetailDialogVisible.value = true
}

const downloadPdf = async item => {
  isDownloadingPdf.value[item.id] = true
  try {
    const token = useCookie('accessToken').value
    const res = await fetch(`/api/apps/price-adjustments/${item.id}/export-pdf`, {
      headers: {
        'Authorization': `Bearer ${token || ''}`,
        'Accept': 'application/pdf',
      },
    })

    if (!res.ok) {
      const errData = await res.json().catch(() => ({}))
      throw new Error(errData.message || 'Gagal mengunduh dokumen SK Penetapan Harga PDF')
    }

    const blob = await res.blob()
    const url = window.URL.createObjectURL(blob)

    // Open in new tab for direct viewing & printing
    window.open(url, '_blank')

    // Also download file
    const a = document.createElement('a')
    a.href = url
    a.download = `SK_Penetapan_Harga_${item.adjustment_number}.pdf`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)

    setTimeout(() => window.URL.revokeObjectURL(url), 10000)
  } catch (err) {
    console.error(err)
    snackbar.showSnackbar(err.message || 'Gagal mencetak dokumen PDF', 'error')
  } finally {
    isDownloadingPdf.value[item.id] = false
  }
}

const onAdjustmentApplied = () => {
  fetchDocuments()
  if (activeTab.value === 'history') {
    fetchHistories()
  }
}

onMounted(() => {
  fetchOptions()
  fetchDocuments()
})
</script>

<template>
  <div>
    <!-- Page Header & Banner -->
    <VCard elevation="0" class="border rounded-lg mb-6 bg-var-theme-surface">
      <VCardText class="pa-6">
        <div class="d-flex flex-wrap align-center justify-space-between gap-4">
          <div class="d-flex align-center gap-4">
            <VAvatar color="primary" variant="tonal" rounded="lg" size="52">
              <VIcon icon="ri-price-tag-3-line" size="30" />
            </VAvatar>
            <div>
              <h4 class="text-h5 font-weight-bold text-high-emphasis">
                Penyesuaian Harga Periode & Riwayat Perubahan
              </h4>
              <p class="text-body-2 text-medium-emphasis mb-0">
                Pusat penetapan harga jual resmi berkala (misal tiap 3 bulan / kenaikan pabrik) lengkap dengan rekam jejak audit trail.
              </p>
            </div>
          </div>

          <div class="d-flex align-center gap-3">
            <VBtn
              color="primary"
              prepend-icon="ri-add-line"
              @click="isAddDrawerVisible = true"
            >
              Buat Penyesuaian Harga Baru
            </VBtn>
          </div>
        </div>
      </VCardText>

      <!-- Tabs Navigation -->
      <VTabs v-model="activeTab" class="px-4 border-t">
        <VTab value="documents" prepend-icon="ri-file-list-3-line">
          Dokumen Penyesuaian Harga ({{ totalAdjustments }})
        </VTab>
        <VTab value="history" prepend-icon="ri-history-line">
          Log Riwayat Perubahan Harga (Audit Trail)
        </VTab>
      </VTabs>
    </VCard>

    <!-- TAB 1: DOKUMEN PENYESUAIAN HARGA -->
    <div v-if="activeTab === 'documents'">
      <!-- Filter Toolbar -->
      <VCard elevation="0" class="border rounded-lg mb-4 pa-4 bg-var-theme-surface">
        <VRow dense align="center">
          <VCol cols="12" sm="3">
            <VTextField
              v-model="searchDoc"
              placeholder="Cari No. Dokumen / Judul / Alasan..."
              prepend-inner-icon="ri-search-line"
              density="compact"
              variant="outlined"
              clearable
              hide-details
            />
          </VCol>

          <VCol cols="12" sm="3">
            <VSelect
              v-model="selectedBranch"
              :items="[{ id: 'all', name: 'Semua Cabang (Pusat & Cabang)' }, ...branches]"
              item-title="name"
              item-value="id"
              label="Filter Cabang"
              density="compact"
              variant="outlined"
              hide-details
            />
          </VCol>

          <VCol cols="12" sm="2">
            <VSelect
              v-model="selectedStatus"
              :items="[
                { title: 'Semua Status', value: 'all' },
                { title: 'Disetujui / Berlaku', value: 'approved' },
                { title: 'Draft Usulan', value: 'draft' },
                { title: 'Dibatalkan', value: 'cancelled' },
              ]"
              label="Status"
              density="compact"
              variant="outlined"
              hide-details
            />
          </VCol>

          <VCol cols="12" sm="2">
            <VTextField
              v-model="startDate"
              type="date"
              label="Dari Tanggal"
              density="compact"
              variant="outlined"
              hide-details
            />
          </VCol>

          <VCol cols="12" sm="2">
            <VTextField
              v-model="endDate"
              type="date"
              label="Sampai Tanggal"
              density="compact"
              variant="outlined"
              hide-details
            />
          </VCol>
        </VRow>
      </VCard>

      <!-- Main Data Table -->
      <VCard elevation="0" class="border rounded-lg">
        <VDataTableServer
          v-model:items-per-page="itemsPerPageDoc"
          v-model:page="pageDoc"
          :headers="docHeaders"
          :items="adjustments"
          :items-length="totalAdjustments"
          :loading="isLoading"
          class="text-no-wrap"
          hover
        >
          <!-- No. Dokumen -->
          <template #item.adjustment_number="{ item }">
            <div class="font-mono font-weight-bold text-primary cursor-pointer" @click="viewDetail(item)">
              {{ item.adjustment_number }}
            </div>
            <div class="text-caption text-medium-emphasis">{{ formatDate(item.created_at) }}</div>
          </template>

          <!-- Judul -->
          <template #item.title="{ item }">
            <div class="font-weight-semibold text-body-2">{{ item.title }}</div>
            <div class="text-caption text-medium-emphasis">{{ item.reason || 'Penyesuaian Berkala' }}</div>
          </template>

          <!-- Tanggal Berlaku -->
          <template #item.effective_date="{ item }">
            <div class="font-weight-medium text-body-2">
              {{ formatDate(item.effective_date) }}
            </div>
          </template>

          <!-- Target Cabang -->
          <template #item.branch="{ item }">
            <VChip size="small" variant="tonal" color="info">
              {{ item.branch ? item.branch.name : 'Semua Cabang' }}
            </VChip>
          </template>

          <!-- Jumlah SKU -->
          <template #item.total_items="{ item }">
            <VChip size="small" variant="tonal" color="primary" class="font-weight-bold font-mono">
              {{ item.total_items }} SKU
            </VChip>
          </template>

          <!-- Status -->
          <template #item.status="{ item }">
            <VChip
              size="small"
              class="font-weight-bold text-uppercase"
              :color="item.status === 'approved' ? 'success' : (item.status === 'draft' ? 'warning' : 'secondary')"
            >
              {{ item.status === 'approved' ? 'BERLAKU' : (item.status === 'draft' ? 'DRAFT' : 'BATAL') }}
            </VChip>
          </template>

          <!-- Pembuat / Pengesah -->
          <template #item.creator="{ item }">
            <div class="text-body-2 font-weight-medium">{{ item.creator?.name || 'Admin' }}</div>
            <div v-if="item.approver" class="text-caption text-success">
              Disahkan: {{ item.approver.name }}
            </div>
          </template>

          <!-- Actions -->
          <template #item.actions="{ item }">
            <div class="d-flex align-center justify-center gap-1">
              <VBtn
                icon="ri-eye-line"
                size="small"
                variant="text"
                color="default"
                title="Lihat Rincian"
                @click="viewDetail(item)"
              />

              <VBtn
                icon="ri-file-pdf-2-line"
                size="small"
                variant="text"
                color="secondary"
                title="Cetak PDF SK Penetapan Harga"
                :loading="isDownloadingPdf[item.id]"
                @click="downloadPdf(item)"
              />
            </div>
          </template>

          <!-- Bottom Pagination -->
          <template #bottom>
            <VDivider />
            <div class="d-flex align-center justify-space-between flex-wrap gap-3 pa-4">
              <span class="text-caption text-medium-emphasis">
                {{ paginationMeta({ page: pageDoc, itemsPerPage: itemsPerPageDoc }, totalAdjustments) }}
              </span>
              <VPagination
                v-model="pageDoc"
                :length="Math.ceil(totalAdjustments / itemsPerPageDoc)"
                :total-visible="5"
                density="compact"
                size="small"
              />
            </div>
          </template>
        </VDataTableServer>
      </VCard>
    </div>

    <!-- TAB 2: RIWAYAT PERUBAHAN HARGA (AUDIT TRAIL) -->
    <div v-if="activeTab === 'history'">
      <!-- Filter Toolbar -->
      <VCard elevation="0" class="border rounded-lg mb-4 pa-4 bg-var-theme-surface">
        <VRow dense align="center">
          <VCol cols="12" sm="4">
            <VTextField
              v-model="searchHistory"
              placeholder="Cari Nama Produk / SKU / No. Dokumen / Alasan..."
              prepend-inner-icon="ri-search-line"
              density="compact"
              variant="outlined"
              clearable
              hide-details
            />
          </VCol>

          <VCol cols="12" sm="3">
            <VSelect
              v-model="selectedHistoryBranch"
              :items="[{ id: 'all', name: 'Semua Cabang' }, ...branches]"
              item-title="name"
              item-value="id"
              label="Filter Cabang"
              density="compact"
              variant="outlined"
              hide-details
            />
          </VCol>

          <VCol cols="12" sm="2">
            <VTextField
              v-model="historyStartDate"
              type="date"
              label="Dari Tanggal"
              density="compact"
              variant="outlined"
              hide-details
            />
          </VCol>

          <VCol cols="12" sm="2">
            <VTextField
              v-model="historyEndDate"
              type="date"
              label="Sampai Tanggal"
              density="compact"
              variant="outlined"
              hide-details
            />
          </VCol>

          <VCol cols="12" sm="1">
            <VBtn
              icon="ri-refresh-line"
              variant="tonal"
              color="secondary"
              density="compact"
              @click="fetchHistories"
            />
          </VCol>
        </VRow>
      </VCard>

      <!-- History Data Table -->
      <VCard elevation="0" class="border rounded-lg">
        <VDataTableServer
          v-model:items-per-page="itemsPerPageHistory"
          v-model:page="pageHistory"
          :headers="historyHeaders"
          :items="histories"
          :items-length="totalHistories"
          :loading="isHistoryLoading"
          class="text-no-wrap"
          hover
        >
          <!-- Tanggal Efektif -->
          <template #item.effective_date="{ item }">
            <div class="font-weight-medium text-body-2">{{ formatDate(item.effective_date) }}</div>
          </template>

          <!-- No. Dokumen -->
          <template #item.adjustment_number="{ item }">
            <span class="font-mono text-caption text-primary font-weight-bold">
              {{ item.adjustment_number || '-' }}
            </span>
          </template>

          <!-- Produk & SKU -->
          <template #item.product="{ item }">
            <div class="font-weight-bold text-body-2">{{ item.product?.name || '-' }}</div>
            <div class="text-caption font-mono text-medium-emphasis">
              {{ item.product?.sku || '-' }} • {{ item.product?.category?.name || 'Umum' }}
            </div>
          </template>

          <!-- Cabang -->
          <template #item.branch="{ item }">
            <VChip size="x-small" variant="tonal" color="info">
              {{ item.branch?.name || 'Semua Cabang' }}
            </VChip>
          </template>

          <!-- HPP Modal -->
          <template #item.cost_price="{ item }">
            <div class="font-mono text-caption text-medium-emphasis">
              {{ formatCurrency(item.new_cost_price) }}
            </div>
          </template>

          <!-- Harga Lama -->
          <template #item.old_price="{ item }">
            <div class="font-mono text-caption text-medium-emphasis">
              {{ formatCurrency(item.old_price) }}
            </div>
          </template>

          <!-- Harga Baru -->
          <template #item.new_price="{ item }">
            <div class="font-mono font-weight-bold text-body-2 text-primary">
              {{ formatCurrency(item.new_price) }}
            </div>
          </template>

          <!-- Kenaikan / Selisih -->
          <template #item.diff="{ item }">
            <div class="font-mono">
              <VChip
                size="x-small"
                class="font-weight-bold"
                :color="item.new_price > item.old_price ? 'success' : (item.new_price < item.old_price ? 'error' : 'secondary')"
              >
                {{ item.new_price > item.old_price ? '+' : '' }}{{ formatCurrency(item.new_price - item.old_price) }}
              </VChip>
            </div>
          </template>

          <!-- Alasan & Pengesah -->
          <template #item.reason="{ item }">
            <div class="text-body-2 font-weight-medium">{{ item.reason || '-' }}</div>
            <div class="text-caption text-medium-emphasis">Oleh: {{ item.user?.name || 'Owner' }}</div>
          </template>

          <!-- Bottom Pagination -->
          <template #bottom>
            <VDivider />
            <div class="d-flex align-center justify-space-between flex-wrap gap-3 pa-4">
              <span class="text-caption text-medium-emphasis">
                {{ paginationMeta({ page: pageHistory, itemsPerPage: itemsPerPageHistory }, totalHistories) }}
              </span>
              <VPagination
                v-model="pageHistory"
                :length="Math.ceil(totalHistories / itemsPerPageHistory)"
                :total-visible="5"
                density="compact"
                size="small"
              />
            </div>
          </template>
        </VDataTableServer>
      </VCard>
    </div>

    <!-- Add Drawer Component -->
    <AddNewAdjustmentDrawer
      v-model:is-drawer-open="isAddDrawerVisible"
      :branches="branches"
      :categories="categories"
      @saved="fetchDocuments"
    />

    <!-- Detail Dialog Component -->
    <AdjustmentDetailDialog
      v-model:is-dialog-visible="isDetailDialogVisible"
      :adjustment-id="selectedAdjustmentId"
      @applied="onAdjustmentApplied"
    />
  </div>
</template>
