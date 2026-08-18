<script setup>
import { ref, onMounted, computed } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import AddNewPurchaseOrderDrawer from './AddNewPurchaseOrderDrawer.vue'
import DocumentActions from '@/components/DocumentActions.vue'

const purchaseOrders = ref([])
const branches = ref([])
const suppliers = ref([])
const masterProducts = ref([])
const search = ref('')
const selectedBranch = ref(null)
const selectedStatus = ref(null)
const isLoading = ref(false)
const isAddNewDrawerVisible = ref(false)
const isTrackingDialogVisible = ref(false)
const selectedPO = ref(null)
const trackingPO = ref(null)
const activeTab = ref('all')
const dateRange = ref('')

// Pagination
const page = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)
let searchTimeout = null

const countNeedValidation = ref(0)
const countNeedApproval = ref(0)

const snackbar = useSnackbarStore()

// Format currency
const formatRupiah = value => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value)
}

const fetchData = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
    }
    
    if (search.value) params.search = search.value
    
    if (activeTab.value !== 'all') {
      params.approval_status_filter = activeTab.value
    }
    
    if (dateRange.value) {
      const dates = dateRange.value.split(' to ')
      if (dates.length > 0) {
        params.start_date = dates[0]
        params.end_date = dates[1] || dates[0]
      }
    }

    const [poData, branchData, supplierData, productData, countsData] = await Promise.all([
      $api('/apps/purchase-orders', { query: params }),
      $api('/apps/branches'),
      $api('/apps/suppliers?all=true'),
      $api('/apps/products'),
      $api('/apps/purchase-orders', { query: { itemsPerPage: -1 } }) // To get counts
    ])

    purchaseOrders.value = poData.data || poData
    if (poData.total !== undefined) {
      totalItems.value = poData.total
    }
    branches.value = branchData.data || branchData
    suppliers.value = supplierData
    masterProducts.value = productData.data || productData
    // Update badge counts
    const allPOs = countsData.data || countsData
    countNeedValidation.value = allPOs.filter(item => !item.approval_status || item.approval_status === 'draft' || item.approval_status === 'pending').length
    countNeedApproval.value = allPOs.filter(item => item.approval_status === 'validated').length

  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mengambil data', 'error')
  } finally {
    isLoading.value = false
  }
}

const handleSearchAndFilter = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchData()
  }, 500)
}

onMounted(() => {
  fetchData()
})

const savePurchaseOrder = async data => {
  try {
    if (data.id) {
      await $api(`/apps/purchase-orders/${data.id}`, {
        method: 'PUT',
        body: data,
      })
      snackbar.show('PO berhasil diperbarui', 'success')
    } else {
      await $api('/apps/purchase-orders', {
        method: 'POST',
        body: data,
      })
      snackbar.show('PO berhasil dibuat', 'success')
    }
    fetchData()
  } catch (error) {
    console.error(error)
    snackbar.show(data.id ? 'Gagal memperbarui PO' : 'Gagal membuat PO', 'error')
  }
}

const tableHeaders = [
  { title: 'NO. PO', key: 'po_number' },
  { title: 'TANGGAL', key: 'date' },
  { title: 'CABANG', key: 'branch.name' },
  { title: 'SUPPLIER', key: 'supplier.name' },
  { title: 'TOTAL', key: 'total_amount' },
  { title: 'PERSETUJUAN', key: 'approval_status' },
  { title: 'STATUS PO', key: 'status' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const filteredPOs = computed(() => {
  return purchaseOrders.value
})

const exportExcel = () => {
  if (!dateRange.value) {
    snackbar.show('Silakan pilih rentang tanggal periode terlebih dahulu!', 'warning')
    
    return
  }
  
  if (!filteredPOs.value.length) {
    snackbar.show('Tidak ada data untuk diekspor pada periode ini', 'warning')
    
    return
  }
  
  const headers = ['NO. PO', 'TANGGAL', 'CABANG', 'SUPPLIER', 'TOTAL (Rp)', 'STATUS PERSETUJUAN', 'STATUS PO']
  const csvRows = [headers.join(',')]
  
  filteredPOs.value.forEach(po => {
    const row = [
      `"${po.po_number || ''}"`,
      `"${po.date || ''}"`,
      `"${po.branch?.name || ''}"`,
      `"${po.supplier?.name || ''}"`,
      `"${po.total_amount || 0}"`,
      `"${po.approval_status || ''}"`,
      `"${po.status || ''}"`,
    ]

    csvRows.push(row.join(','))
  })
  
  const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')

  link.href = url
  link.setAttribute('download', `Purchase_Order_${new Date().toISOString().split('T')[0]}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const viewPO = po => {
  // For now, edit view acts as read-only if it's completed, or editable if not implemented yet
  selectedPO.value = po
  isAddNewDrawerVisible.value = true
}

const openTrackingDialog = po => {
  trackingPO.value = po
  isTrackingDialogVisible.value = true
}

const updateStatus = async (id, status) => {
  if (confirm(`Apakah Anda yakin ingin mengubah status PO menjadi ${status}?`)) {
    try {
      await $api(`/apps/purchase-orders/${id}`, { 
        method: 'PUT',
        body: { status },
      })
      snackbar.show('Status PO berhasil diperbarui', 'success')
      fetchData()
    } catch (error) {
      console.error(error)
      snackbar.show('Gagal memperbarui status', 'error')
    }
  }
}

const confirmDeletePO = async id => {
  if (confirm('Apakah Anda yakin ingin menghapus PO ini?')) {
    try {
      await $api(`/apps/purchase-orders/${id}`, { method: 'DELETE' })
      snackbar.show('PO berhasil dihapus', 'success')
      fetchData()
    } catch (error) {
      console.error(error)
      snackbar.show('Gagal menghapus PO', 'error')
    }
  }
}
</script>

<template>
  <section>
    <!-- Page Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="text-h4 mb-0">
          Purchase Orders (PO)
        </h2>
      </div>
      
      <div class="d-flex gap-4">
        <VBtn
          v-if="$can('read', 'Purchase Order')"
          color="success"
          variant="tonal"
          prepend-icon="ri-file-excel-2-line"
          :disabled="isLoading || filteredPOs.length === 0"
          @click="exportExcel"
        >
          Export Excel
        </VBtn>
        <VBtn
          v-if="$can('create', 'Purchase Order')"
          color="primary"
          prepend-icon="ri-add-line"
          @click="() => { selectedPO = null; isAddNewDrawerVisible = true }"
        >
          Buat PO Baru
        </VBtn>
      </div>
    </div>

    <!-- Card -->
    <VCard>
      <VTabs
        v-model="activeTab"
        class="px-4 border-b"
        @update:model-value="() => { page = 1; fetchData(); }"
      >
        <VTab value="all">
          Semua
        </VTab>
        <VTab value="need_validation">
          <span class="mr-2">Butuh Validasi</span>
          <VBadge
            v-if="countNeedValidation > 0"
            color="error"
            :content="countNeedValidation"
            inline
          />
        </VTab>
        <VTab value="need_approval">
          <span class="mr-2">Butuh Persetujuan</span>
          <VBadge
            v-if="countNeedApproval > 0"
            color="warning"
            :content="countNeedApproval"
            inline
          />
        </VTab>
        <VTab value="approved">
          Selesai / Disetujui
        </VTab>
      </VTabs>

      <!-- Card Header -->
      <VCardItem class="pa-4 pb-0">
        <div class="d-flex flex-wrap align-center justify-space-between w-100 gap-4">
          <VCardTitle class="px-0">
            Daftar PO
          </VCardTitle>
          <div class="d-flex align-center gap-4">
            <AppDateTimePicker
              v-model="dateRange"
              placeholder="Filter Rentang Tanggal"
              prepend-inner-icon="ri-calendar-line"
              :config="{ mode: 'range' }"
              density="compact"
              style="width: 250px;"
              hide-details
              clearable
              @update:model-value="handleSearchAndFilter"
            />
            <div style="width: 250px;">
              <VTextField
                v-model="search"
                placeholder="Cari No PO atau Supplier..."
                density="compact"
                prepend-inner-icon="ri-search-line"
                hide-details
                clearable
                @update:model-value="handleSearchAndFilter"
              />
            </div>
          </div>
        </div>
      </VCardItem>

      <!-- Data Table -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="tableHeaders"
        :items="filteredPOs"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        @update:options="fetchData"
      >
        <template #item.po_number="{ item }">
          <a
            href="#"
            class="font-weight-bold text-primary text-decoration-none"
            @click.prevent="openTrackingDialog(item)"
          >
            {{ item.po_number }}
          </a>
        </template>
        
        <template #item.total_amount="{ item }">
          <span class="font-weight-medium">{{ formatRupiah(item.total_amount) }}</span>
        </template>

        <template #item.approval_status="{ item }">
          <VChip
            :color="item.approval_status === 'draft' ? 'secondary' : ((!item.approval_status || item.approval_status === 'pending') ? 'warning' : (item.approval_status === 'validated' ? 'info' : (item.approval_status === 'approved' ? 'success' : 'error')))"
            size="small"
            variant="tonal"
          >
            {{ item.approval_status === 'draft' ? 'Draf' : ((!item.approval_status || item.approval_status === 'pending') ? 'Menunggu Validasi' : (item.approval_status === 'validated' ? 'Menunggu Persetujuan' : (item.approval_status === 'approved' ? 'Disetujui' : 'Ditolak'))) }}
          </VChip>
        </template>

        <template #item.status="{ item }">
          <VChip
            :color="item.status === 'completed' ? 'success' : (item.status === 'pending' ? 'secondary' : 'error')"
            size="small"
          >
            {{ item.status.toUpperCase() }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <IconBtn
            v-if="$can('write', 'Purchase Order')"
            size="small"
            color="primary"
            :disabled="item.approval_status !== 'draft' && item.approval_status !== 'rejected'"
            @click="viewPO(item)"
          >
            <VIcon icon="ri-edit-line" />
          </IconBtn>
          <IconBtn
            v-if="$can('delete', 'Purchase Order')"
            size="small"
            color="error"
            :disabled="item.status === 'completed' || item.status === 'cancelled'"
            @click="confirmDeletePO(item.id)"
          >
            <VIcon icon="ri-delete-bin-line" />
          </IconBtn>
        </template>

        <!-- Expanded Row for Items -->
      </VDataTableServer>
    </VCard>

    <AddNewPurchaseOrderDrawer
      v-model:is-drawer-open="isAddNewDrawerVisible"
      :selected-po="selectedPO"
      :branches="branches"
      :suppliers="suppliers"
      :master-products="masterProducts"
      @save-data="savePurchaseOrder"
    />

    <!-- Tracking & Validation Dialog -->
    <VDialog
      v-model="isTrackingDialogVisible"
      max-width="800"
    >
      <VCard v-if="trackingPO">
        <VCardTitle class="d-flex justify-space-between align-center px-6 pt-6 pb-4">
          <span class="text-h5">Detail & Validasi: {{ trackingPO.po_number }}</span>
          <VBtn
            icon
            variant="text"
            size="small"
            @click="isTrackingDialogVisible = false"
          >
            <VIcon icon="ri-close-line" />
          </VBtn>
        </VCardTitle>
        <VDivider />
        <VCardText
          class="px-6 py-6"
          style="max-height: 70vh; overflow-y: auto;"
        >
          <!-- Item List -->
          <div class="mb-6">
            <h6 class="text-h6 mb-3">
              Daftar Barang
            </h6>
            <div v-if="trackingPO.items && trackingPO.items.length">
              <table
                class="w-100"
                style="border-collapse: collapse;"
              >
                <thead>
                  <tr class="text-left border-b">
                    <th class="pb-2">
                      Barang
                    </th>
                    <th class="pb-2">
                      Qty
                    </th>
                    <th class="pb-2">
                      Harga Beli
                    </th>
                    <th class="pb-2 text-right">
                      Subtotal
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="i in trackingPO.items"
                    :key="i.id"
                    class="border-b"
                  >
                    <td class="py-2">
                      {{ i.product?.name || i.product_branch?.product?.name || 'Item' }}
                    </td>
                    <td class="py-2">
                      {{ i.qty }}
                    </td>
                    <td class="py-2">
                      {{ formatRupiah(i.unit_cost) }}
                    </td>
                    <td class="py-2 text-right font-weight-medium">
                      {{ formatRupiah(i.total_price || i.subtotal) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else>
              Tidak ada data barang.
            </div>
          </div>

          <!-- Timeline -->
          <div class="mt-8">
            <h6 class="text-h6 mb-6">
              Lacak Status Dokumen
            </h6>
            
            <div class="d-flex align-start justify-space-between position-relative mt-2">
              <!-- Line behind -->
              <div
                class="position-absolute bg-grey-300"
                style="height: 2px; top: 12px; left: 15%; right: 15%; z-index: 0;"
              />
              
              <!-- Created -->
              <div
                class="d-flex flex-column align-center position-relative text-center"
                style="z-index: 1; flex: 1;"
              >
                <VAvatar
                  size="26"
                  color="primary"
                  class="mb-2 ring-2 ring-white"
                >
                  <VIcon
                    icon="ri-file-add-line"
                    size="14"
                    color="white"
                  />
                </VAvatar>
                <div class="text-caption font-weight-bold mt-1">
                  Dibuat
                </div>
                <div class="text-caption text-grey-600">
                  {{ new Date(trackingPO.created_at || trackingPO.date).toLocaleString('id-ID') }}
                </div>
              </div>

              <!-- Validated -->
              <div
                class="d-flex flex-column align-center position-relative text-center"
                style="z-index: 1; flex: 1;"
              >
                <VAvatar
                  size="26"
                  :color="trackingPO.validated_by ? 'info' : 'grey-300'"
                  class="mb-2 ring-2 ring-white"
                >
                  <VIcon
                    icon="ri-check-double-line"
                    size="14"
                    color="white"
                  />
                </VAvatar>
                <div class="text-caption font-weight-bold mt-1">
                  Divalidasi
                </div>
                <div
                  v-if="trackingPO.validated_by"
                  class="text-caption text-grey-600"
                >
                  {{ trackingPO.validated_at ? new Date(trackingPO.validated_at).toLocaleString('id-ID') : '-' }}
                </div>
                <div
                  v-else
                  class="text-caption text-grey-400"
                >
                  Menunggu
                </div>
              </div>

              <!-- Approved or Rejected -->
              <div
                class="d-flex flex-column align-center position-relative text-center"
                style="z-index: 1; flex: 1;"
              >
                <VAvatar
                  size="26"
                  :color="trackingPO.approval_status === 'rejected' ? 'error' : (trackingPO.approved_by ? 'success' : 'grey-300')"
                  class="mb-2 ring-2 ring-white"
                >
                  <VIcon
                    :icon="trackingPO.approval_status === 'rejected' ? 'ri-close-circle-line' : 'ri-checkbox-circle-line'"
                    size="16"
                    color="white"
                  />
                </VAvatar>
                <div
                  class="text-caption font-weight-bold mt-1"
                  :class="trackingPO.approval_status === 'rejected' ? 'text-error' : ''"
                >
                  {{ trackingPO.approval_status === 'rejected' ? 'Ditolak' : 'Disetujui' }}
                </div>
                <div
                  v-if="trackingPO.approval_status === 'rejected' || trackingPO.approved_by"
                  class="text-caption text-grey-600"
                  :class="trackingPO.approval_status === 'rejected' ? 'text-error' : ''"
                >
                  <span v-if="trackingPO.approval_status === 'rejected'">{{ trackingPO.rejection_reason || 'Tanpa alasan' }}</span>
                  <span v-else>{{ trackingPO.approved_at ? new Date(trackingPO.approved_at).toLocaleString('id-ID') : '-' }}</span>
                </div>
                <div
                  v-else
                  class="text-caption text-grey-400"
                >
                  Menunggu
                </div>
              </div>
            </div>
          </div>
        </VCardText>
        <VDivider />
        <VCardActions class="px-6 py-4 justify-end bg-grey-50">
          <DocumentActions 
            document-type="purchase_order"
            :document-id="trackingPO.id"
            :document-status="trackingPO.status"
            :approval-status="trackingPO.approval_status || 'pending'"
            @status-updated="fetchData"
          />
        </VCardActions>
      </VCard>
    </VDialog>
  </section>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Purchase Order
</route>
