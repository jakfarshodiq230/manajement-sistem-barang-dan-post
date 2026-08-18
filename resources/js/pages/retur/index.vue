<script setup>
import { ref, onMounted } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import CreateReturnDrawer from './CreateReturnDrawer.vue'
import ApprovalDialog from '../pos/ApprovalDialog.vue'

const returns = ref([])
const branches = ref([])
const isLoading = ref(false)
const snackbar = useSnackbarStore()

const isCreateDrawerVisible = ref(false)
const isApprovalDialogVisible = ref(false)
const returnToApprove = ref(null)
const activeTab = ref('all') // all, purchase, sale
const search = ref('')
const selectedBranch = ref(null)

// Pagination
const page = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)
let searchTimeout = null

const tableHeaders = [
  { title: 'NO. RETUR', key: 'return_number' },
  { title: 'JENIS RETUR', key: 'return_type' },
  { title: 'REFERENSI', key: 'reference_type' },
  { title: 'TOTAL NILAI', key: 'total_amount' },
  { title: 'STATUS', key: 'status' },
  { title: 'TANGGAL', key: 'created_at' },
  { title: 'ACTIONS', key: 'actions', sortable: false, align: 'end' },
]

const formatRupiah = value => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)
}

const formatDate = dateString => {
  if (!dateString) return '-'
  
  return new Date(dateString).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const fetchData = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      reference_type_filter: activeTab.value,
    }
    
    if (search.value) {
      params.search = search.value
    }
    if (selectedBranch.value) {
      params.branch_id = selectedBranch.value
    }
    
    const [returnsData, branchData] = await Promise.all([
      $api('/apps/returns', { query: params }),
      $api('/apps/branches'),
    ])

    returns.value = returnsData.data || returnsData
    if (returnsData.total !== undefined) {
      totalItems.value = returnsData.total
    }
    branches.value = branchData.data || branchData
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mengambil data', 'error')
  } finally {
    isLoading.value = false
  }
}

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchData()
  }, 500)
}

onMounted(() => {
  fetchData()
})

const filteredReturns = computed(() => {
  return returns.value
})

const handleCreateReturn = async returnData => {
  try {
    await $api('/apps/returns', {
      method: 'POST',
      body: returnData,
    })
    snackbar.show('Draft Retur berhasil dibuat', 'success')
    isCreateDrawerVisible.value = false
    fetchData()
  } catch (error) {
    console.error(error)
    snackbar.show('Terjadi kesalahan saat membuat retur', 'error')
  }
}

const promptApprove = item => {
  returnToApprove.value = item
  isApprovalDialogVisible.value = true
}

const handleApprovalSuccess = async approverId => {
  if (!returnToApprove.value) return
  isApprovalDialogVisible.value = false

  try {
    await $api(`/apps/returns/${returnToApprove.value.id}/approve`, {
      method: 'POST',
      body: { approver_id: approverId },
    })
    snackbar.show('Retur berhasil disetujui (Approved)', 'success')
    fetchData()
  } catch (error) {
    console.error(error)
    snackbar.show(error.response?.data?.message || 'Gagal memproses approval retur', 'error')
  } finally {
    returnToApprove.value = null
  }
}

const handleApprovalCancel = () => {
  isApprovalDialogVisible.value = false
  returnToApprove.value = null
}
</script>

<template>
  <section>
    <!-- Page Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="text-h4 mb-0">
          Manajemen Retur
        </h2>
      </div>
      
      <div class="d-flex gap-4">
        <VBtn
          v-if="$can('create', 'Retur Barang')"
          prepend-icon="ri-add-line"
          color="primary"
          @click="isCreateDrawerVisible = true"
        >
          Buat Retur
        </VBtn>
      </div>
    </div>

    <!-- Tabs -->
    <VTabs
      v-model="activeTab"
      class="mb-4"
      @update:model-value="() => { page = 1; fetchData(); }"
    >
      <VTab value="all">
        Semua Retur
      </VTab>
      <VTab value="purchase">
        Retur Pembelian (Tukar Barang)
      </VTab>
      <VTab value="sale">
        Retur Penjualan (Pengembalian Uang)
      </VTab>
    </VTabs>

    <!-- Card -->
    <VCard>
      <VCardItem class="pa-4 pb-0">
        <div class="d-flex align-center justify-space-between w-100">
          <VCardTitle class="px-0">
            Daftar Retur
          </VCardTitle>
          <div style="width: 250px;">
            <VTextField
              v-model="search"
              prepend-inner-icon="ri-search-line"
              placeholder="Cari No Retur..."
              density="compact"
              hide-details
              variant="outlined"
              clearable
              @update:model-value="handleSearch"
            />
          </div>
        </div>
      </VCardItem>
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="tableHeaders"
        :items="filteredReturns"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        @update:options="fetchData"
      >
        <template #item.return_type="{ item }">
          <VChip
            size="small"
            :color="item.return_type === 'tukar_barang' ? 'info' : 'warning'"
          >
            {{ item.return_type === 'tukar_barang' ? 'Tukar Barang' : 'Refund Uang' }}
          </VChip>
        </template>

        <template #item.reference_type="{ item }">
          <div class="font-weight-medium">
            {{ item.reference_type === 'purchase' ? 'PO' : 'Penjualan' }}
          </div>
          <div class="text-caption text-disabled">
            {{ item.reference_type === 'purchase' ? (item.purchase_order?.po_number || 'ID: ' + item.reference_id) : (item.sale?.invoice_number || 'ID: ' + item.reference_id) }}
          </div>
        </template>

        <template #item.total_amount="{ item }">
          {{ formatRupiah(item.total_amount) }}
        </template>

        <template #item.status="{ item }">
          <VChip
            size="small"
            :color="item.status === 'completed' ? 'success' : (item.status === 'rejected' ? 'error' : 'warning')"
          >
            {{ item.status.toUpperCase() }}
          </VChip>
        </template>

        <template #item.created_at="{ item }">
          {{ formatDate(item.created_at) }}
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex gap-1 justify-end">
            <!-- Approve Button -->
            <VBtn
              v-if="item.status === 'pending'"
              size="small"
              color="primary"
              variant="tonal"
              @click="promptApprove(item)"
            >
              Approve
            </VBtn>
            <VBtn
              v-else
              size="small"
              variant="text"
              disabled
            >
              -
            </VBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Create Drawer -->
    <CreateReturnDrawer
      v-model:is-drawer-open="isCreateDrawerVisible"
      :branches="branches"
      @return-data="handleCreateReturn"
    />

    <!-- Approval Dialog from POS -->
    <ApprovalDialog
      v-model:is-dialog-visible="isApprovalDialogVisible"
      @success="handleApprovalSuccess"
      @cancel="handleApprovalCancel"
    />
  </section>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Retur Barang
</route>
