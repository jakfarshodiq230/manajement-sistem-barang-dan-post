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
const isLoading = ref(false)
const isAddNewDrawerVisible = ref(false)
const isTrackingDialogVisible = ref(false)
const isRejectDialogVisible = ref(false)
const isPhotoPreviewVisible = ref(false)
const previewPhotoUrl = ref('')
const selectedPO = ref(null)
const trackingPO = ref(null)
const grToReject = ref(null)
const rejectReason = ref('')
const isApproving = ref(false)
const activeTab = ref('all') // all, stage_gudang, stage_approval, stage_approved, stage_rejected
const dateRange = ref('')

// Pagination
const page = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)
let searchTimeout = null

const countStageGudang = ref(0)
const countStageApproval = ref(0)
const countStageApproved = ref(0)
const countStageRejected = ref(0)

const snackbar = useSnackbarStore()

const extractArray = val => {
  if (Array.isArray(val)) return val
  if (val && Array.isArray(val.data)) return val.data
  return []
}

// Format currency
const formatRupiah = value => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value || 0)
}

const formatDate = dateString => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatDiscountTiers = i => {
  if (i.discount_string) return i.discount_string
  const tiers = [i.discount_percent_1, i.discount_percent_2, i.discount_percent_3, i.discount_percent_4, i.discount_percent_5]
    .map(Number)
    .filter(d => d > 0)
  
  if (tiers.length > 0) {
    let res = tiers.map(d => `${d}%`).join(' + ')
    if (Number(i.discount_amount) > 0) {
      res += ` + Rp ${Number(i.discount_amount).toLocaleString('id-ID')}`
    }
    return res
  }
  if (Number(i.discount_amount) > 0) {
    return `Rp ${Number(i.discount_amount).toLocaleString('id-ID')}`
  }
  return '-'
}

const fetchData = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
    }
    
    if (search.value) params.search = search.value
    
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
      $api('/apps/products', { query: { itemsPerPage: 100 } }),
      $api('/apps/purchase-orders', { query: { itemsPerPage: -1 } }) // To get counts
    ])

    purchaseOrders.value = extractArray(poData)
    totalItems.value = poData?.total ?? (Array.isArray(purchaseOrders.value) ? purchaseOrders.value.length : 0)
    branches.value = extractArray(branchData)
    suppliers.value = extractArray(supplierData)
    masterProducts.value = extractArray(productData)
    
    // Update badge counts for 4-stage pipeline
    const allPOs = extractArray(countsData)
    countStageGudang.value = allPOs.filter(item => item.status === 'pending' && (!item.goods_receipt || item.goods_receipt.approval_status === 'rejected')).length
    countStageApproval.value = allPOs.filter(item => item.goods_receipt && item.goods_receipt.approval_status === 'pending_approval').length
    countStageApproved.value = allPOs.filter(item => item.status === 'completed' || (item.goods_receipt && item.goods_receipt.approval_status === 'approved')).length
    countStageRejected.value = allPOs.filter(item => item.goods_receipt && item.goods_receipt.approval_status === 'rejected').length

  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mengambil data', 'error')
    purchaseOrders.value = []
    branches.value = []
    suppliers.value = []
    masterProducts.value = []
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
      snackbar.show('PO berhasil dibuat dan diteruskan ke Petugas Gudang', 'success')
    }
    fetchData()
  } catch (error) {
    console.error(error)
    snackbar.show(data.id ? 'Gagal memperbarui PO' : 'Gagal membuat PO', 'error')
  }
}

const tableHeaders = [
  { title: 'NO. PO & FAKTUR', key: 'po_number' },
  { title: 'TANGGAL & TEMPO', key: 'date' },
  { title: 'CABANG TUJUAN', key: 'branch.name' },
  { title: 'SUPPLIER', key: 'supplier.name' },
  { title: 'TOTAL BIAYA', key: 'total_amount' },
  { title: 'STATUS ALUR SOP', key: 'status_sop', align: 'center' },
  { title: 'STATUS PEMBAYARAN', key: 'payment_status', align: 'center' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const filteredPOs = computed(() => {
  let list = purchaseOrders.value
  if (activeTab.value === 'stage_gudang') {
    list = list.filter(item => item.status === 'pending' && (!item.goods_receipt || item.goods_receipt.approval_status === 'rejected'))
  } else if (activeTab.value === 'stage_approval') {
    list = list.filter(item => item.goods_receipt && item.goods_receipt.approval_status === 'pending_approval')
  } else if (activeTab.value === 'stage_approved') {
    list = list.filter(item => item.status === 'completed' || (item.goods_receipt && item.goods_receipt.approval_status === 'approved'))
  } else if (activeTab.value === 'stage_rejected') {
    list = list.filter(item => item.goods_receipt && item.goods_receipt.approval_status === 'rejected')
  }
  return list
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
  
  const headers = ['NO. PO', 'FAKTUR SUPPLIER', 'TANGGAL PESAN', 'CABANG', 'SUPPLIER', 'TOTAL (Rp)', 'STATUS SOP']
  const csvRows = [headers.join(',')]
  
  filteredPOs.value.forEach(po => {
    const row = [
      `"${po.po_number || ''}"`,
      `"${po.goods_receipt?.invoice_number_supplier || po.invoice_number_supplier || ''}"`,
      `"${po.date || ''}"`,
      `"${po.branch?.name || ''}"`,
      `"${po.supplier?.name || ''}"`,
      `"${po.goods_receipt?.total_amount || po.total_amount || 0}"`,
      `"${po.goods_receipt?.approval_status || po.status || ''}"`,
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
  selectedPO.value = po
  isAddNewDrawerVisible.value = true
}

const openTrackingDialog = async po => {
  try {
    const res = await $api(`/apps/purchase-orders/${po.id}`)
    trackingPO.value = res.data || res
  } catch (e) {
    trackingPO.value = po
  }
  isTrackingDialogVisible.value = true
}

const openPhotoZoom = url => {
  previewPhotoUrl.value = url
  isPhotoPreviewVisible.value = true
}

const isApproveDialogVisible = ref(false)
const grToApprove = ref(null)

const isDeletePoDialogVisible = ref(false)
const poToDelete = ref(null)

const openApproveDialog = gr => {
  grToApprove.value = gr
  isApproveDialogVisible.value = true
}

const executeApproveGR = async () => {
  if (!grToApprove.value) return

  isApproving.value = true
  try {
    const res = await $api(`/apps/goods-receipts/${grToApprove.value.id}/approve`, {
      method: 'POST',
    })
    snackbar.show(res.message || 'Penerimaan barang berhasil disetujui & stok fisik telah bertambah!', 'success')
    isApproveDialogVisible.value = false
    isTrackingDialogVisible.value = false
    fetchData()
  } catch (error) {
    console.error(error)
    const errorMsg = error.response?._data?.message || error.message || 'Gagal menyetujui penerimaan barang'
    snackbar.show(errorMsg, 'error')
  } finally {
    isApproving.value = false
  }
}

const openRejectDialog = gr => {
  grToReject.value = gr
  rejectReason.value = ''
  isRejectDialogVisible.value = true
}

const executeRejectGR = async () => {
  if (!grToReject.value) return
  isApproving.value = true
  try {
    const res = await $api(`/apps/goods-receipts/${grToReject.value.id}/reject`, {
      method: 'POST',
      body: { reason: rejectReason.value }
    })
    snackbar.show(res.message || 'Penerimaan barang ditolak dan dikembalikan ke petugas gudang untuk revisi.', 'info')
    isRejectDialogVisible.value = false
    isTrackingDialogVisible.value = false
    fetchData()
  } catch (error) {
    console.error(error)
    const errorMsg = error.response?._data?.message || error.message || 'Gagal menolak penerimaan barang'
    snackbar.show(errorMsg, 'error')
  } finally {
    isApproving.value = false
  }
}

const openDeletePoDialog = po => {
  poToDelete.value = po
  isDeletePoDialogVisible.value = true
}

const executeDeletePO = async () => {
  if (!poToDelete.value) return
  try {
    await $api(`/apps/purchase-orders/${poToDelete.value.id}`, { method: 'DELETE' })
    snackbar.show('PO berhasil dihapus', 'success')
    isDeletePoDialogVisible.value = false
    poToDelete.value = null
    fetchData()
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal menghapus PO', 'error')
  }
}
</script>

<template>
  <section>
    <!-- Page Header -->
    <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-4">
      <div>
        <h2 class="text-h4 font-weight-bold mb-1 d-flex align-center gap-2">
          <VIcon icon="ri-shopping-cart-2-line" color="primary" />
          Purchase Orders & Alur Penerimaan Gudang
        </h2>
        <p class="text-caption text-medium-emphasis mb-0">
          SOP 4 Tahap: Input PO (Ka. Divisi) &rarr; Validasi Fisik & Faktur (Gudang) &rarr; Validasi Harga & Diskon (Ka. Divisi) &rarr; Stok Masuk
        </p>
      </div>
      
      <div class="d-flex gap-2">
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

    <!-- 4-Stage Pipeline Filter Tabs -->
    <VCard class="mb-4">
      <VTabs
        v-model="activeTab"
        grow
        class="border-b"
      >
        <VTab value="all">
          <VIcon icon="ri-apps-line" class="me-1" size="18" />
          Semua PO
          <VChip size="x-small" class="ms-2" color="secondary">{{ purchaseOrders.length }}</VChip>
        </VTab>

        <VTab value="stage_gudang">
          <VIcon icon="ri-truck-line" class="me-1" size="18" color="info" />
          1. Menunggu Cek Fisik Gudang
          <VChip v-if="countStageGudang > 0" size="x-small" class="ms-2" color="info">{{ countStageGudang }}</VChip>
        </VTab>

        <VTab value="stage_approval">
          <VIcon icon="ri-shield-user-line" class="me-1" size="18" color="warning" />
          2. Menunggu Validasi Ka. Divisi
          <VChip v-if="countStageApproval > 0" size="x-small" class="ms-2 font-weight-bold" color="warning" variant="flat">{{ countStageApproval }}</VChip>
        </VTab>

        <VTab value="stage_approved">
          <VIcon icon="ri-checkbox-circle-line" class="me-1" size="18" color="success" />
          3. Disetujui & Masuk Stok
          <VChip v-if="countStageApproved > 0" size="x-small" class="ms-2" color="success">{{ countStageApproved }}</VChip>
        </VTab>

        <VTab value="stage_rejected">
          <VIcon icon="ri-close-circle-line" class="me-1" size="18" color="error" />
          4. Ditolak / Revisi
          <VChip v-if="countStageRejected > 0" size="x-small" class="ms-2" color="error">{{ countStageRejected }}</VChip>
        </VTab>
      </VTabs>

      <!-- Card Filter Header -->
      <VCardItem class="pa-4 pb-0">
        <div class="d-flex flex-wrap align-center justify-space-between w-100 gap-4">
          <div>
            <span class="text-caption text-medium-emphasis">
              Menampilkan {{ filteredPOs.length }} data purchase order berdasarkan filter tahap SOP.
            </span>
          </div>
          <div class="d-flex align-center gap-4 flex-wrap">
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
            <div style="width: 260px;">
              <VTextField
                v-model="search"
                placeholder="Cari No. PO, Faktur, Sales..."
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
        <!-- PO Number & Supplier Invoice -->
        <template #item.po_number="{ item }">
          <div>
            <a
              href="#"
              class="font-weight-bold text-primary text-decoration-none"
              @click.prevent="openTrackingDialog(item)"
            >
              {{ item.po_number }}
            </a>
            <div v-if="item.goods_receipt?.invoice_number_supplier" class="text-caption text-success font-weight-medium">
              <VIcon icon="ri-bill-line" size="13" class="me-1" />
              Faktur: {{ item.goods_receipt.invoice_number_supplier }}
            </div>
            <div v-if="item.goods_receipt?.sales_name" class="text-caption text-medium-emphasis">
              Sales: {{ item.goods_receipt.sales_name }}
            </div>
          </div>
        </template>

        <!-- Date & Due Date -->
        <template #item.date="{ item }">
          <div>
            <div class="font-weight-medium">{{ item.date || item.created_at?.substr(0, 10) }}</div>
            <div v-if="item.goods_receipt?.due_date" class="text-caption text-error font-weight-medium">
              Jatuh Tempo: {{ String(item.goods_receipt.due_date).substring(0, 10) }}
            </div>
            <div v-else-if="item.due_date && String(item.due_date).substring(0, 10) !== String(item.date || item.created_at).substring(0, 10)" class="text-caption text-medium-emphasis">
              Tempo: {{ String(item.due_date).substring(0, 10) }}
            </div>
          </div>
        </template>

        <!-- Branch -->
        <template #item.branch.name="{ item }">
          <span class="font-weight-medium">{{ item.branch?.name || '-' }}</span>
        </template>

        <!-- Supplier -->
        <template #item.supplier.name="{ item }">
          <div>
            <span class="font-weight-bold">{{ item.supplier?.name || '-' }}</span>
            <div class="text-caption text-disabled">{{ item.items ? item.items.length : 0 }} Item Produk</div>
          </div>
        </template>

        <!-- Total Amount -->
        <template #item.total_amount="{ item }">
          <div>
            <span class="font-weight-bold text-primary font-mono">
              {{ formatRupiah(item.goods_receipt?.total_amount || item.total_amount) }}
            </span>
            <div v-if="item.goods_receipt?.extra_discount > 0" class="text-caption text-success">
              Diskon Faktur: -{{ formatRupiah(item.goods_receipt.extra_discount) }}
            </div>
          </div>
        </template>

        <!-- Status SOP Alur 4 Tahap -->
        <template #item.status_sop="{ item }">
          <!-- Stage 3: Approved & Restocked -->
          <VChip
            v-if="item.status === 'completed' || item.goods_receipt?.approval_status === 'approved'"
            color="success"
            size="small"
            variant="flat"
            class="font-weight-bold"
          >
            <VIcon icon="ri-checkbox-circle-line" size="14" class="me-1" />
            Disetujui & Masuk Stok
          </VChip>

          <!-- Stage 2: Pending Approval from Ka. Divisi -->
          <VChip
            v-else-if="item.goods_receipt?.approval_status === 'pending_approval'"
            color="warning"
            size="small"
            variant="flat"
            class="font-weight-bold"
          >
            <VIcon icon="ri-shield-user-line" size="14" class="me-1" />
            Menunggu Validasi Ka. Divisi
          </VChip>

          <!-- Stage 4: Rejected / Revision needed -->
          <VChip
            v-else-if="item.goods_receipt?.approval_status === 'rejected'"
            color="error"
            size="small"
            variant="tonal"
            class="font-weight-bold"
          >
            <VIcon icon="ri-close-circle-line" size="14" class="me-1" />
            Ditolak / Perlu Revisi
          </VChip>

          <!-- Stage 1: Waiting for physical delivery & warehouse receipt -->
          <VChip
            v-else
            color="info"
            size="small"
            variant="tonal"
            class="font-weight-medium"
          >
            <VIcon icon="ri-truck-line" size="14" class="me-1" />
            Menunggu Cek Fisik Gudang
          </VChip>
        </template>

        <!-- Status Pembayaran Hutang Supplier (Hutang / Lunas) -->
        <template #item.payment_status="{ item }">
          <div v-if="item.status === 'completed' || item.goods_receipt?.approval_status === 'approved' || item.payable || item.goods_receipt?.payable">
            <VChip
              v-if="item.payable?.status === 'paid' || item.goods_receipt?.payable?.status === 'paid' || ((item.payable?.remaining_amount !== undefined && Number(item.payable?.remaining_amount) <= 0) || (item.goods_receipt?.payable?.remaining_amount !== undefined && Number(item.goods_receipt?.payable?.remaining_amount) <= 0))"
              color="success"
              size="small"
              variant="flat"
              class="font-weight-bold"
            >
              <VIcon icon="ri-checkbox-circle-fill" size="13" class="me-1" />
              Lunas
            </VChip>
            <VChip
              v-else-if="(Number(item.payable?.paid_amount) > 0) || (Number(item.goods_receipt?.payable?.paid_amount) > 0)"
              color="warning"
              size="small"
              variant="flat"
              class="font-weight-bold"
            >
              <VIcon icon="ri-time-fill" size="13" class="me-1" />
              Dicicil
            </VChip>
            <VChip
              v-else
              color="error"
              size="small"
              variant="flat"
              class="font-weight-bold"
            >
              <VIcon icon="ri-error-warning-fill" size="13" class="me-1" />
              Hutang
            </VChip>
            <div v-if="(Number(item.payable?.remaining_amount || item.goods_receipt?.payable?.remaining_amount || (item.goods_receipt?.total_amount || item.total_amount))) > 0" class="text-caption text-error font-mono font-weight-medium mt-0.5">
              Sisa: {{ formatRupiah(item.payable?.remaining_amount !== undefined ? item.payable?.remaining_amount : (item.goods_receipt?.payable?.remaining_amount !== undefined ? item.goods_receipt?.payable?.remaining_amount : (item.goods_receipt?.total_amount || item.total_amount))) }}
            </div>
          </div>
          <div v-else class="text-caption text-medium-emphasis">
            <VChip size="small" variant="tonal" color="secondary">
              Belum Ditagihkan
            </VChip>
          </div>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex align-center justify-center gap-1">
            <!-- Validasi & Setujui Button for Ka. Divisi -->
            <VBtn
              v-if="item.goods_receipt?.approval_status === 'pending_approval' && ($can('approve', 'Purchase Order') || $can('approve', 'Penerimaan Gudang') || $can('write', 'Purchase Order') || $can('manage all', 'all'))"
              size="small"
              color="primary"
              variant="elevated"
              prepend-icon="ri-check-double-line"
              class="me-1 font-weight-bold"
              @click="openTrackingDialog(item)"
            >
              Validasi & Setujui
            </VBtn>

            <IconBtn
              size="small"
              color="info"
              title="Lihat Rincian Pesanan & Faktur"
              @click="openTrackingDialog(item)"
            >
              <VIcon icon="ri-eye-line" />
            </IconBtn>

            <IconBtn
              v-if="$can('write', 'Purchase Order')"
              size="small"
              color="secondary"
              title="Edit PO"
              :disabled="item.status === 'completed' || (item.goods_receipt && item.goods_receipt.approval_status === 'approved')"
              @click="viewPO(item)"
            >
              <VIcon icon="ri-edit-line" />
            </IconBtn>

            <IconBtn
              v-if="$can('delete', 'Purchase Order')"
              size="small"
              color="error"
              title="Hapus PO"
              :disabled="item.status === 'completed' || (item.goods_receipt && item.goods_receipt.approval_status === 'approved')"
              @click="openDeletePoDialog(item)"
            >
              <VIcon icon="ri-delete-bin-line" />
            </IconBtn>
          </div>
        </template>

        <template #no-data>
          <div class="pa-6 text-center text-medium-emphasis">
            <VIcon icon="ri-inbox-line" size="36" class="mb-2 text-disabled" />
            <div>Tidak ada data purchase order pada filter ini.</div>
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <AddNewPurchaseOrderDrawer
      v-model:is-drawer-open="isAddNewDrawerVisible"
      :is-drawer-open="isAddNewDrawerVisible"
      :selected-po="selectedPO"
      :branches="branches"
      :suppliers="suppliers"
      :master-products="masterProducts"
      @update:is-drawer-open="val => isAddNewDrawerVisible = val"
      @update:isDrawerOpen="val => isAddNewDrawerVisible = val"
      @close="isAddNewDrawerVisible = false"
      @cancel="isAddNewDrawerVisible = false"
      @save-data="savePurchaseOrder"
    />

    <!-- Tracking & Approval Dialog (Kepala Divisi Review) -->
    <VDialog
      v-model="isTrackingDialogVisible"
      max-width="1150"
    >
      <VCard v-if="trackingPO">
        <VCardTitle class="d-flex justify-space-between align-center px-6 pt-6 pb-4 bg-light">
          <div class="d-flex align-center gap-3">
            <VAvatar color="primary" variant="tonal" size="42" class="rounded-lg">
              <VIcon icon="ri-file-shield-2-line" size="24" />
            </VAvatar>
            <div>
              <div class="d-flex align-center gap-2">
                <span class="text-h6 font-weight-bold">PO: {{ trackingPO.po_number }}</span>
                <VChip
                  v-if="trackingPO.goods_receipt?.approval_status === 'pending_approval'"
                  color="warning"
                  size="small"
                  variant="flat"
                >
                  Menunggu Validasi Ka. Divisi
                </VChip>
                <VChip
                  v-else-if="trackingPO.status === 'completed' || trackingPO.goods_receipt?.approval_status === 'approved'"
                  color="success"
                  size="small"
                  variant="flat"
                >
                  Disetujui & Masuk Stok
                </VChip>
              </div>
              <span class="text-caption text-medium-emphasis">
                Dibuat oleh: {{ trackingPO.user?.name || '-' }} pada {{ formatDate(trackingPO.created_at) }}
              </span>
            </div>
          </div>
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
          style="max-height: 75vh; overflow-y: auto;"
        >
          <!-- Rejection Alert if any -->
          <VAlert
            v-if="trackingPO.goods_receipt?.rejection_reason"
            type="error"
            variant="tonal"
            density="compact"
            class="mb-4"
            icon="ri-error-warning-line"
          >
            <strong>Penerimaan Ditolak / Perlu Revisi Gudang:</strong> {{ trackingPO.goods_receipt.rejection_reason || 'Periksa kembali kesesuaian fisik atau data faktur supplier.' }}
          </VAlert>

          <!-- Section 1: Data Faktur dari Petugas Gudang (Jika Barang Sudah Divalidasi Gudang) -->
          <div v-if="trackingPO.goods_receipt" class="pa-4 bg-var-theme-surface rounded-xl border mb-5 shadow-xs">
            <div class="d-flex align-center justify-space-between mb-3">
              <h6 class="text-subtitle-2 font-weight-bold text-uppercase letter-spacing-1 text-primary d-flex align-center gap-2 mb-0">
                <VIcon icon="ri-file-paper-2-line" size="18" />
                Data Faktur Masuk (Diinput oleh Petugas Gudang)
              </h6>
              <VChip size="small" variant="tonal" color="info">
                No. GR: {{ trackingPO.goods_receipt.receipt_number }}
              </VChip>
            </div>

            <VRow dense>
              <VCol cols="12" sm="4">
                <div class="text-caption text-medium-emphasis">No. Faktur Supplier:</div>
                <div class="font-weight-bold text-success">{{ trackingPO.goods_receipt.invoice_number_supplier || '-' }}</div>
              </VCol>
              <VCol cols="12" sm="4">
                <div class="text-caption text-medium-emphasis">Nama Sales Supplier:</div>
                <div class="font-weight-bold">{{ trackingPO.goods_receipt.sales_name || '-' }}</div>
              </VCol>
              <VCol cols="12" sm="4">
                <div class="text-caption text-medium-emphasis">Petugas Gudang (Cek Fisik):</div>
                <div class="font-weight-bold">{{ trackingPO.goods_receipt.validator?.name || '-' }} ({{ formatDate(trackingPO.goods_receipt.validated_at || trackingPO.goods_receipt.created_at) }})</div>
              </VCol>
              <VCol cols="12" sm="4" class="mt-2">
                <div class="text-caption text-medium-emphasis">Tanggal Barang Sampai:</div>
                <div class="font-weight-bold">{{ formatDate(trackingPO.goods_receipt.received_date || trackingPO.goods_receipt.date) }}</div>
              </VCol>
              <VCol cols="12" sm="4" class="mt-2">
                <div class="text-caption text-medium-emphasis">Tanggal Jatuh Tempo Faktur:</div>
                <div class="font-weight-bold text-error">{{ formatDate(trackingPO.goods_receipt.due_date) }}</div>
              </VCol>
              <VCol cols="12" sm="4" class="mt-2">
                <div class="text-caption text-medium-emphasis">Kepala Divisi (Validasi Harga):</div>
                <div class="font-weight-bold text-primary">{{ trackingPO.goods_receipt.approver?.name || (trackingPO.goods_receipt.approval_status === 'pending_approval' ? 'Menunggu Validasi Anda' : '-') }}</div>
              </VCol>

              <!-- Photo of invoice / surat jalan -->
              <VCol v-if="trackingPO.goods_receipt.photos && trackingPO.goods_receipt.photos.length" cols="12" class="mt-3">
                <div class="text-caption text-medium-emphasis mb-1">Bukti Foto Faktur / Surat Jalan Supplier:</div>
                <div class="d-flex gap-2 flex-wrap">
                  <VImg
                    v-for="(photo, idx) in trackingPO.goods_receipt.photos"
                    :key="idx"
                    :src="photo.startsWith('http') || photo.startsWith('/storage') ? photo : ('/storage/' + photo)"
                    width="100"
                    height="80"
                    cover
                    class="rounded-lg border cursor-pointer hover-elevation"
                    @click="openPhotoZoom(photo.startsWith('http') || photo.startsWith('/storage') ? photo : ('/storage/' + photo))"
                  />
                </div>
              </VCol>
            </VRow>
          </div>

          <!-- Section: Status & Rincian Pembayaran Hutang Supplier (Hutang / Lunas) -->
          <div
            v-if="trackingPO.status === 'completed' || trackingPO.goods_receipt?.approval_status === 'approved' || trackingPO.payable || trackingPO.goods_receipt?.payable"
            class="pa-4 rounded-xl border mb-5 shadow-xs"
            :class="(trackingPO.payable?.status === 'paid' || trackingPO.goods_receipt?.payable?.status === 'paid' || (trackingPO.payable?.remaining_amount !== undefined && Number(trackingPO.payable?.remaining_amount) <= 0) || (trackingPO.goods_receipt?.payable?.remaining_amount !== undefined && Number(trackingPO.goods_receipt?.payable?.remaining_amount) <= 0)) ? 'bg-success-lighten-5 border-success' : 'bg-var-theme-surface border-warning'"
          >
            <div class="d-flex align-center justify-space-between flex-wrap gap-2 mb-3">
              <h6 class="text-subtitle-2 font-weight-bold text-uppercase letter-spacing-1 d-flex align-center gap-2 mb-0" :class="(trackingPO.payable?.status === 'paid' || trackingPO.goods_receipt?.payable?.status === 'paid' || (trackingPO.payable?.remaining_amount !== undefined && Number(trackingPO.payable?.remaining_amount) <= 0) || (trackingPO.goods_receipt?.payable?.remaining_amount !== undefined && Number(trackingPO.goods_receipt?.payable?.remaining_amount) <= 0)) ? 'text-success' : 'text-warning-darken-1'">
                <VIcon icon="ri-wallet-3-line" size="18" />
                Status & Informasi Pembayaran Hutang Supplier
              </h6>
              
              <!-- Status Badge -->
              <VChip
                v-if="trackingPO.payable?.status === 'paid' || trackingPO.goods_receipt?.payable?.status === 'paid' || (trackingPO.payable?.remaining_amount !== undefined && Number(trackingPO.payable?.remaining_amount) <= 0) || (trackingPO.goods_receipt?.payable?.remaining_amount !== undefined && Number(trackingPO.goods_receipt?.payable?.remaining_amount) <= 0)"
                color="success"
                size="small"
                variant="flat"
                class="font-weight-bold"
              >
                <VIcon icon="ri-checkbox-circle-fill" size="14" class="me-1" />
                LUNAS
              </VChip>
              <VChip
                v-else-if="(Number(trackingPO.payable?.paid_amount) > 0) || (Number(trackingPO.goods_receipt?.payable?.paid_amount) > 0)"
                color="warning"
                size="small"
                variant="flat"
                class="font-weight-bold"
              >
                <VIcon icon="ri-time-fill" size="14" class="me-1" />
                DIBAYAR SEBAGIAN (DICICIL)
              </VChip>
              <VChip
                v-else
                color="error"
                size="small"
                variant="flat"
                class="font-weight-bold"
              >
                <VIcon icon="ri-error-warning-fill" size="14" class="me-1" />
                BELUM LUNAS (HUTANG)
              </VChip>
            </div>

            <VRow dense>
              <VCol cols="12" sm="3">
                <div class="text-caption text-medium-emphasis">No. Tagihan Hutang (AP):</div>
                <div class="font-weight-bold font-mono text-primary">
                  {{ trackingPO.payable?.payable_number || trackingPO.goods_receipt?.payable?.payable_number || ('AP-' + (trackingPO.po_number || '-')) }}
                </div>
              </VCol>
              <VCol cols="12" sm="3">
                <div class="text-caption text-medium-emphasis">Total Tagihan Faktur:</div>
                <div class="font-weight-bold font-mono">
                  {{ formatRupiah(trackingPO.payable?.total_amount || trackingPO.goods_receipt?.payable?.total_amount || trackingPO.goods_receipt?.total_amount || trackingPO.total_amount) }}
                </div>
              </VCol>
              <VCol cols="12" sm="3">
                <div class="text-caption text-medium-emphasis">Sudah Dibayar:</div>
                <div class="font-weight-bold font-mono text-success">
                  {{ formatRupiah(trackingPO.payable?.paid_amount || trackingPO.goods_receipt?.payable?.paid_amount || 0) }}
                </div>
              </VCol>
              <VCol cols="12" sm="3">
                <div class="text-caption text-medium-emphasis">Sisa Hutang:</div>
                <div class="font-weight-bold font-mono" :class="(Number(trackingPO.payable?.remaining_amount !== undefined ? trackingPO.payable?.remaining_amount : (trackingPO.goods_receipt?.payable?.remaining_amount !== undefined ? trackingPO.goods_receipt?.payable?.remaining_amount : (trackingPO.goods_receipt?.total_amount || trackingPO.total_amount)))) > 0 ? 'text-error font-weight-black' : 'text-success'">
                  {{ formatRupiah(trackingPO.payable?.remaining_amount !== undefined ? trackingPO.payable?.remaining_amount : (trackingPO.goods_receipt?.payable?.remaining_amount !== undefined ? trackingPO.goods_receipt?.payable?.remaining_amount : (trackingPO.goods_receipt?.total_amount || trackingPO.total_amount))) }}
                </div>
              </VCol>
            </VRow>
          </div>

          <!-- Section 2: Informasi Dasar PO -->
          <div class="pa-4 bg-grey-50 rounded-lg border mb-5">
            <VRow dense>
              <VCol cols="12" sm="4">
                <div class="text-caption text-medium-emphasis">Supplier Tujuan:</div>
                <div class="font-weight-bold text-primary">{{ trackingPO.supplier?.name || '-' }}</div>
              </VCol>
              <VCol cols="12" sm="4">
                <div class="text-caption text-medium-emphasis">Cabang Penerima:</div>
                <div class="font-weight-bold">{{ trackingPO.branch?.name || '-' }}</div>
              </VCol>
              <VCol cols="12" sm="4">
                <div class="text-caption text-medium-emphasis">Tanggal Pemesanan:</div>
                <div class="font-weight-bold">{{ trackingPO.date || trackingPO.created_at?.substr(0, 10) }}</div>
              </VCol>
              <VCol v-if="trackingPO.notes" cols="12" class="mt-2">
                <div class="text-caption text-medium-emphasis">Catatan Pesanan:</div>
                <div class="text-body-2">{{ trackingPO.notes }}</div>
              </VCol>
            </VRow>
          </div>

          <!-- Section 3: Item List with Capella Pricing & Tier Discounts -->
          <div class="mb-5">
            <div class="d-flex justify-space-between align-center mb-3">
              <h6 class="text-subtitle-1 font-weight-bold mb-0 d-flex align-center gap-2">
                <VIcon icon="ri-box-3-line" color="primary" size="18" />
                Rincian Barang & Kalkulasi Harga Modal Capella
              </h6>
              <span class="text-caption text-medium-emphasis">
                Total: {{ (trackingPO.goods_receipt?.items || trackingPO.items)?.length || 0 }} Item Produk
              </span>
            </div>

            <div class="border rounded overflow-x-auto shadow-xs">
              <table class="w-100 text-left" style="border-collapse: collapse; font-size: 12.5px; min-width: 900px;">
                <thead>
                  <tr class="bg-grey-100 border-b text-medium-emphasis font-weight-bold">
                    <th class="pa-3" style="min-width: 170px;">Barang & Batch</th>
                    <th class="pa-3 text-center" style="width: 100px;">Qty Diterima</th>
                    <th class="pa-3 text-right" style="width: 110px;">Harga Gross</th>
                    <th class="pa-3 text-center" style="width: 100px;">Diskon</th>
                    <th class="pa-3 text-right" style="width: 125px;">Harga Modal (HPP)</th>
                    <th class="pa-3 text-right" style="width: 125px;">Harga Jual Toko</th>
                    <th class="pa-3 text-right" style="width: 120px;">Batas Min. Nego</th>
                    <th class="pa-3 text-right" style="width: 125px;">Subtotal Faktur</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="i in (trackingPO.goods_receipt?.items || trackingPO.items || [])"
                    :key="i.id"
                    class="border-b hover-bg"
                  >
                    <!-- Barang & Batch -->
                    <td class="pa-3">
                      <div class="font-weight-bold text-high-emphasis">
                        {{ i.product?.name || i.product_branch?.product?.name || 'Item' }}
                      </div>
                      <div class="text-caption text-medium-emphasis font-mono">
                        <code>{{ i.product?.sku || i.product_branch?.product?.sku || '-' }}</code>
                      </div>
                      <div v-if="i.batch_number || i.expiration_date" class="text-caption text-primary mt-1" style="font-size: 11px;">
                        <span v-if="i.batch_number">Batch: <strong>{{ i.batch_number }}</strong></span>
                        <span v-if="i.expiration_date" class="ms-1">| Exp: <strong>{{ formatDate(i.expiration_date) }}</strong></span>
                      </div>
                    </td>

                    <!-- Qty Diterima & Satuan -->
                    <td class="pa-3 text-center">
                      <div class="font-weight-bold text-primary">
                        {{ i.qty_received || i.qty }} {{ i.unit_name || 'pcs' }}
                      </div>
                      <div v-if="i.conversion_qty && i.conversion_qty > 1" class="text-caption text-medium-emphasis" style="font-size: 10.5px;">
                        (Isi {{ i.conversion_qty }} pcs)
                      </div>
                    </td>

                    <!-- Harga Gross -->
                    <td class="pa-3 text-right font-mono">
                      {{ formatRupiah(i.gross_price || i.unit_cost) }}
                    </td>

                    <!-- Diskon Bertingkat -->
                    <td class="pa-3 text-center">
                      <VChip size="x-small" variant="tonal" color="warning" class="font-mono font-weight-bold">
                        {{ formatDiscountTiers(i) }}
                      </VChip>
                    </td>

                    <!-- Harga Modal HPP Dasar per Pcs/Satuan -->
                    <td class="pa-3 text-right font-mono">
                      <div class="font-weight-bold text-primary">
                        {{ formatRupiah(i.final_cost_per_piece || i.net_unit_price || i.unit_cost) }}
                      </div>
                      <div class="text-caption text-disabled" style="font-size: 10px;">
                        HPP per {{ (i.conversion_qty && i.conversion_qty > 1) ? 'pcs' : (i.unit_name || 'satuan') }}
                      </div>
                    </td>

                    <!-- Harga Jual Toko -->
                    <td class="pa-3 text-right font-mono">
                      <div class="font-weight-bold text-success">
                        {{ (i.price || i.selling_price) > 0 ? formatRupiah(i.price || i.selling_price) : '-' }}
                      </div>
                      <div
                        v-if="(i.price || i.selling_price) > 0 && (i.final_cost_per_piece || i.unit_cost) > 0"
                        class="text-caption text-success font-weight-medium"
                        style="font-size: 10px;"
                      >
                        +{{ formatRupiah((i.price || i.selling_price) - (i.final_cost_per_piece || i.unit_cost)) }}
                      </div>
                    </td>

                    <!-- Batas Min. Nego -->
                    <td class="pa-3 text-right font-mono">
                      <div class="font-weight-bold text-warning">
                        {{ (i.min_nego_price || 0) > 0 ? formatRupiah(i.min_nego_price) : '-' }}
                      </div>
                      <div v-if="(i.min_nego_price || 0) > 0" class="text-caption text-medium-emphasis" style="font-size: 10px;">
                        Batas Kasir
                      </div>
                    </td>

                    <!-- Subtotal Netto Faktur -->
                    <td class="pa-3 text-right font-bold font-mono text-primary">
                      {{ formatRupiah(i.total_price || i.subtotal || ((i.qty_received || i.qty || 1) * (i.net_unit_price || i.unit_cost || i.gross_price || 0))) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Section 4: Ringkasan Pajak & Total Faktur -->
          <div class="pa-4 bg-grey-50 rounded-lg border d-flex flex-wrap justify-space-between align-center gap-4">
            <div>
              <div class="text-caption text-medium-emphasis">Perlakuan Pajak:</div>
              <div class="font-weight-bold text-uppercase">
                PPN {{ (trackingPO.goods_receipt?.tax_type || trackingPO.tax_type || 'include') }} ({{ trackingPO.goods_receipt?.tax_percentage || trackingPO.tax_percentage || 11 }}%)
              </div>
            </div>

            <div class="text-right font-mono">
              <div v-if="(trackingPO.goods_receipt?.extra_discount || trackingPO.extra_discount) > 0" class="text-caption text-success mb-1">
                Diskon Tambahan Faktur: -{{ formatRupiah(trackingPO.goods_receipt?.extra_discount || trackingPO.extra_discount) }}
              </div>
              <span class="text-subtitle-2 text-medium-emphasis me-3">Total Biaya Faktur:</span>
              <span class="text-h5 font-weight-bold text-primary">
                {{ formatRupiah(trackingPO.goods_receipt?.total_amount || trackingPO.total_amount) }}
              </span>
            </div>
          </div>
        </VCardText>

        <VDivider />

        <VCardActions class="px-6 py-4 justify-space-between bg-grey-50 flex-wrap gap-2">
          <VBtn
            color="secondary"
            variant="outlined"
            @click="isTrackingDialogVisible = false"
          >
            Tutup
          </VBtn>

          <!-- Kepala Divisi Action Approval Buttons -->
          <div v-if="trackingPO.goods_receipt?.approval_status === 'pending_approval' && ($can('approve', 'Purchase Order') || $can('approve', 'Penerimaan Gudang') || $can('write', 'Purchase Order') || $can('manage all', 'all'))" class="d-flex gap-2">
            <VBtn
              color="error"
              variant="outlined"
              prepend-icon="ri-close-circle-line"
              :loading="isApproving"
              @click="openRejectDialog(trackingPO.goods_receipt)"
            >
              Tolak / Minta Revisi Gudang
            </VBtn>

            <VBtn
              color="success"
              variant="flat"
              prepend-icon="ri-check-double-line"
              class="font-weight-bold"
              :loading="isApproving"
              @click="openApproveDialog(trackingPO.goods_receipt)"
            >
              Setujui & Tambahkan ke Stok Fisik
            </VBtn>
          </div>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Dialog Konfirmasi Approval Ka. Divisi -->
    <VDialog
      v-model="isApproveDialogVisible"
      max-width="520"
    >
      <VCard class="rounded-xl overflow-hidden shadow-lg">
        <VCardTitle class="pa-5 pb-3 font-weight-bold text-h6 text-success d-flex align-center gap-2 bg-success-subtle border-b">
          <VIcon icon="ri-checkbox-circle-fill" color="success" size="24" />
          Konfirmasi Persetujuan Penerimaan Barang
        </VCardTitle>
        <VCardText class="pa-5 pt-4">
          <p class="text-body-1 mb-2">
            Apakah Anda yakin ingin menyetujui Dokumen Penerimaan <strong>#{{ grToApprove?.receipt_number }}</strong>?
          </p>
          <VAlert
            type="success"
            variant="tonal"
            class="text-caption mt-3 mb-0 rounded-lg"
            icon="ri-information-line"
          >
            Setelah disetujui, <strong>stok fisik barang</strong> akan otomatis bertambah ke gudang cabang dan nomor batch/SCC baru akan dicatat dalam sistem.
          </VAlert>
        </VCardText>
        <VCardActions class="pa-5 pt-0 justify-end gap-2 border-t bg-grey-50">
          <VBtn
            variant="outlined"
            color="secondary"
            @click="isApproveDialogVisible = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="success"
            variant="flat"
            prepend-icon="ri-checkbox-circle-fill"
            class="font-weight-bold"
            :loading="isApproving"
            @click="executeApproveGR"
          >
            Ya, Setujui & Tambahkan ke Stok
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Dialog Konfirmasi Hapus PO -->
    <VDialog
      v-model="isDeletePoDialogVisible"
      max-width="480"
    >
      <VCard class="rounded-xl overflow-hidden shadow-lg">
        <VCardTitle class="pa-5 pb-3 font-weight-bold text-h6 text-error d-flex align-center gap-2 bg-error-subtle border-b">
          <VIcon icon="ri-delete-bin-line" color="error" size="24" />
          Konfirmasi Hapus Purchase Order
        </VCardTitle>
        <VCardText class="pa-5 pt-4">
          <p class="text-body-1 mb-2">
            Apakah Anda yakin ingin menghapus PO <strong>#{{ poToDelete?.po_number }}</strong>?
          </p>
          <VAlert
            type="warning"
            variant="tonal"
            class="text-caption mt-3 mb-0 rounded-lg"
          >
            Tindakan ini tidak dapat dibatalkan. Data PO yang dihapus akan hilang dari sistem.
          </VAlert>
        </VCardText>
        <VCardActions class="pa-5 pt-0 justify-end gap-2 border-t bg-grey-50">
          <VBtn
            variant="outlined"
            color="secondary"
            @click="isDeletePoDialogVisible = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="error"
            variant="flat"
            prepend-icon="ri-delete-bin-line"
            class="font-weight-bold"
            @click="executeDeletePO"
          >
            Ya, Hapus PO
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Dialog Tolak GR -->
    <VDialog
      v-model="isRejectDialogVisible"
      max-width="500"
    >
      <VCard>
        <VCardTitle class="px-6 pt-6 pb-2 text-error d-flex align-center gap-2">
          <VIcon icon="ri-error-warning-line" />
          Alasan Penolakan Penerimaan Barang
        </VCardTitle>
        <VCardText class="px-6 py-4">
          <p class="text-caption text-medium-emphasis mb-3">
            Tuliskan instruksi revisi untuk orang gudang (misal: "Diskon Capella tidak sesuai faktur", "Foto faktur buram", dll).
          </p>
          <VTextarea
            v-model="rejectReason"
            label="Catatan / Alasan Penolakan"
            placeholder="Masukkan alasan penolakan..."
            rows="3"
            variant="outlined"
            auto-grow
          />
        </VCardText>
        <VDivider />
        <VCardActions class="px-6 py-4 justify-end gap-2">
          <VBtn
            variant="outlined"
            color="secondary"
            @click="isRejectDialogVisible = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="error"
            variant="flat"
            :loading="isApproving"
            @click="executeRejectGR"
          >
            Konfirmasi Tolak
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Dialog Photo Zoom -->
    <VDialog
      v-model="isPhotoPreviewVisible"
      max-width="800"
    >
      <VCard>
        <VCardTitle class="d-flex justify-space-between align-center pa-4">
          <span class="font-weight-bold">Pratinjau Foto Faktur / Surat Jalan</span>
          <VBtn icon variant="text" size="small" @click="isPhotoPreviewVisible = false">
            <VIcon icon="ri-close-line" />
          </VBtn>
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-4 text-center">
          <VImg :src="previewPhotoUrl" class="rounded-lg" max-height="600" contain />
        </VCardText>
      </VCard>
    </VDialog>
  </section>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Purchase Order
</route>
