<script setup>
import { ref, onMounted, computed } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import ReceiveGoodsDrawer from './ReceiveGoodsDrawer.vue'
import DocumentActions from '@/components/DocumentActions.vue'
import { useRouter } from 'vue-router'
import { useAbility } from '@casl/vue'

const router = useRouter()
const ability = useAbility()
const snackbar = useSnackbarStore()

const pendingPOs = ref([])
const goodsReceipts = ref([])
const search = ref('')
const isLoading = ref(false)
const isDrawerVisible = ref(false)
const isDetailDialogVisible = ref(false)
const isPrintDialogVisible = ref(false)
const printUrl = ref('')
const detailDialogData = ref(null)
const selectedPO = ref(null)
const selectedGR = ref(null)

const isConfirmDeleteVisible = ref(false)
const grToDelete = ref(null)

// 5-Stage SOP Tabs
const activeTab = ref('pending') // 'pending', 'pending_approval', 'rejected', 'approved', 'all'

const counts = ref({
  pending_pos: 0,
  pending_approval: 0,
  rejected: 0,
  approved: 0,
  total: 0,
})

// Pagination
const page = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)
let searchTimeout = null

const isAllowedToEdit = computed(() => {
  return ability.can('write', 'Penerimaan Gudang') || ability.can('manage', 'all')
})

const isApproving = ref(false)
const isRejectDialogVisible = ref(false)
const grToReject = ref(null)
const rejectReason = ref('')

const extractArray = val => {
  if (Array.isArray(val)) return val
  if (val && Array.isArray(val.data)) return val.data
  return []
}

const formatRupiah = val => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val || 0)
}

const fetchCounts = async () => {
  try {
    const res = await $api('/apps/goods-receipts', { query: { itemsPerPage: 1 } })
    if (res?.counts) {
      counts.value.pending_approval = res.counts.pending_approval || 0
      counts.value.rejected = res.counts.rejected || 0
      counts.value.approved = res.counts.approved || 0
      counts.value.total = res.counts.total || 0
    }
    const poRes = await $api('/apps/purchase-orders', { query: { unreceived: 'true', itemsPerPage: 1 } })
    counts.value.pending_pos = poRes?.total ?? (Array.isArray(poRes?.data) ? poRes.data.length : 0)
  } catch (e) {
    console.error('Failed to fetch summary counts', e)
  }
}

const fetchData = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
    }
    
    if (search.value) {
      params.search = search.value
    }
    
    if (activeTab.value === 'pending') {
      params.unreceived = 'true'
      const poData = await $api('/apps/purchase-orders', { query: params })
      pendingPOs.value = extractArray(poData)
      totalItems.value = poData?.total ?? (Array.isArray(pendingPOs.value) ? pendingPOs.value.length : 0)
      counts.value.pending_pos = totalItems.value
    } else {
      if (activeTab.value !== 'all') {
        params.status = activeTab.value
      }
      const grData = await $api('/apps/goods-receipts', { query: params })
      goodsReceipts.value = extractArray(grData)
      totalItems.value = grData?.total ?? (Array.isArray(goodsReceipts.value) ? goodsReceipts.value.length : 0)
      if (grData?.counts) {
        counts.value.pending_approval = grData.counts.pending_approval || 0
        counts.value.rejected = grData.counts.rejected || 0
        counts.value.approved = grData.counts.approved || 0
        counts.value.total = grData.counts.total || 0
      }
    }
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mengambil data', 'error')
    pendingPOs.value = []
    goodsReceipts.value = []
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
  fetchCounts()
  fetchData()
})

const saveGoodsReceipt = async grData => {
  try {
    const isFormData = grData instanceof FormData
    const isEdit = isFormData ? grData.has('id') : grData.id
    const id = isFormData ? grData.get('id') : grData.id
    
    if (isEdit) {
      if (isFormData) {
        grData.append('_method', 'PUT')
      }
      await $api(`/apps/goods-receipts/${id}`, {
        method: isFormData ? 'POST' : 'PUT',
        body: grData,
      })
      snackbar.show('Revisi dokumen penerimaan berhasil disimpan dan diajukan ulang ke Kepala Divisi!', 'success')
    } else {
      await $api('/apps/goods-receipts', {
        method: 'POST',
        body: grData,
      })
      snackbar.show('Faktur & ceklis fisik berhasil diajukan ke Kepala Divisi!', 'success')
    }
    fetchCounts()
    fetchData()
  } catch (error) {
    console.error(error)
    const errorMsg = error.response?._data?.message || error.data?.message || error.message || 'Gagal memproses penerimaan barang'
    snackbar.show(errorMsg, 'error')
  }
}

const isApproveDialogVisible = ref(false)
const grToApprove = ref(null)

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
    isDetailDialogVisible.value = false
    fetchCounts()
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
    snackbar.show(res.message || 'Penerimaan barang telah ditolak / dikembalikan untuk revisi.', 'info')
    isRejectDialogVisible.value = false
    isDetailDialogVisible.value = false
    fetchCounts()
    fetchData()
  } catch (error) {
    console.error(error)
    const errorMsg = error.response?._data?.message || error.message || 'Gagal menolak penerimaan barang'
    snackbar.show(errorMsg, 'error')
  } finally {
    isApproving.value = false
  }
}

const tableHeadersPending = [
  { title: 'NO. PO', key: 'po_number' },
  { title: 'TANGGAL PO', key: 'date' },
  { title: 'CABANG TUJUAN', key: 'branch.name' },
  { title: 'SUPPLIER', key: 'supplier.name' },
  { title: 'TOTAL ITEM', key: 'items' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const tableHeadersCompleted = [
  { title: 'NO. PENERIMAAN / STATUS', key: 'receipt_number' },
  { title: 'NO. PO ASAL', key: 'purchase_order.po_number' },
  { title: 'FAKTUR SUPPLIER', key: 'invoice_number_supplier' },
  { title: 'SALES SUPPLIER', key: 'sales_name' },
  { title: 'TGL SAMPAI', key: 'received_date' },
  { title: 'JATUH TEMPO', key: 'due_date' },
  { title: 'TOTAL FAKTUR', key: 'total_amount' },
  { title: 'STATUS TAHAPAN', key: 'approval_status' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const activeHeaders = computed(() => {
  return activeTab.value === 'pending' ? tableHeadersPending : tableHeadersCompleted
})

const activeItems = computed(() => {
  return activeTab.value === 'pending' ? pendingPOs.value : goodsReceipts.value
})

const processPO = async poId => {
  try {
    const poDetail = await $api(`/apps/purchase-orders/${poId}`)
    selectedPO.value = poDetail
    selectedGR.value = null
    isDrawerVisible.value = true
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mengambil detail PO', 'error')
  }
}

const processEditGR = async gr => {
  try {
    const grDetail = await $api(`/apps/goods-receipts/${gr.id}`)
    selectedPO.value = grDetail.purchase_order
    selectedGR.value = grDetail
    isDrawerVisible.value = true
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mengambil detail Penerimaan', 'error')
  }
}

const isDownloading = ref(false)

const printGR = async id => {
  isDownloading.value = true
  try {
    const response = await $api(`/apps/documents/goods_receipt/${id}/pdf`, {
      responseType: 'blob',
    })
    
    const blob = new Blob([response], { type: 'application/pdf' })
    printUrl.value = URL.createObjectURL(blob)
    isPrintDialogVisible.value = true
    snackbar.show('PDF berhasil dimuat', 'success')
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal memuat PDF', 'error')
  } finally {
    isDownloading.value = false
  }
}

const closePrintDialog = () => {
  isPrintDialogVisible.value = false
  if (printUrl.value) {
    URL.revokeObjectURL(printUrl.value)
    printUrl.value = ''
  }
}

const openDetailDialog = item => {
  detailDialogData.value = item
  isDetailDialogVisible.value = true
}

const handleActionFromDialog = () => {
  isDetailDialogVisible.value = false
  if (activeTab.value === 'pending') {
    processPO(detailDialogData.value.id)
  }
}

const confirmDeleteGR = item => {
  grToDelete.value = item
  isConfirmDeleteVisible.value = true
}

const executeDeleteGR = async () => {
  if (!grToDelete.value) return
  
  isLoading.value = true
  try {
    await $api(`/apps/goods-receipts/${grToDelete.value.id}`, {
      method: 'DELETE',
    })

    snackbar.show('Penerimaan Gudang berhasil dibatalkan, stok telah ditarik kembali.', 'success')
    isConfirmDeleteVisible.value = false
    fetchCounts()
    fetchData()
  } catch (error) {
    console.error(error)
    const errorMsg = error.response?._data?.message || 'Gagal menghapus penerimaan gudang.'
    snackbar.show(errorMsg, 'error')
  } finally {
    isLoading.value = false
    grToDelete.value = null
  }
}
</script>

<template>
  <section>
    <!-- Page Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="text-h4 mb-0 font-weight-bold">
          Penerimaan Barang (Gudang)
        </h2>
        <span class="text-caption text-medium-emphasis">
          Alur SOP: Cek Fisik Gudang &rarr; Input Faktur & Foto &rarr; Validasi Ka. Divisi &rarr; Masuk Stok Fisik Cabang
        </span>
      </div>
    </div>

    <!-- Stat KPI Cards Header -->
    <VRow class="mb-5">
      <VCol cols="12" sm="6" md="3">
        <VCard
          class="pa-4 border rounded-xl shadow-xs cursor-pointer hover-card"
          :class="activeTab === 'pending' ? 'border-warning border-2 bg-warning-subtle' : ''"
          @click="() => { activeTab = 'pending'; page = 1; fetchData(); }"
        >
          <div class="d-flex align-center gap-3">
            <VAvatar color="warning" variant="tonal" size="48" class="rounded-lg">
              <VIcon icon="ri-truck-line" size="26" />
            </VAvatar>
            <div>
              <span class="text-caption text-medium-emphasis font-weight-medium">1. Menunggu Cek Fisik</span>
              <h4 class="text-h5 font-weight-bold text-warning mb-0">
                {{ counts.pending_pos }} <span class="text-caption font-weight-normal text-medium-emphasis">PO</span>
              </h4>
            </div>
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard
          class="pa-4 border rounded-xl shadow-xs cursor-pointer hover-card"
          :class="activeTab === 'pending_approval' ? 'border-info border-2 bg-info-subtle' : ''"
          @click="() => { activeTab = 'pending_approval'; page = 1; fetchData(); }"
        >
          <div class="d-flex align-center gap-3">
            <VAvatar color="info" variant="tonal" size="48" class="rounded-lg">
              <VIcon icon="ri-time-line" size="26" />
            </VAvatar>
            <div>
              <span class="text-caption text-medium-emphasis font-weight-medium">2. Menunggu Ka. Divisi</span>
              <h4 class="text-h5 font-weight-bold text-info mb-0">
                {{ counts.pending_approval }} <span class="text-caption font-weight-normal text-medium-emphasis">Dokumen</span>
              </h4>
            </div>
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard
          class="pa-4 border rounded-xl shadow-xs cursor-pointer hover-card"
          :class="activeTab === 'rejected' ? 'border-error border-2 bg-error-subtle' : (counts.rejected > 0 ? 'border-error' : '')"
          @click="() => { activeTab = 'rejected'; page = 1; fetchData(); }"
        >
          <div class="d-flex align-center gap-3">
            <VAvatar color="error" variant="tonal" size="48" class="rounded-lg">
              <VIcon icon="ri-error-warning-fill" size="26" />
            </VAvatar>
            <div>
              <span class="text-caption text-medium-emphasis font-weight-bold text-error">3. Ditolak / Perlu Revisi</span>
              <h4 class="text-h5 font-weight-bold text-error mb-0">
                {{ counts.rejected }} <span class="text-caption font-weight-bold text-error">Perlu Revisi</span>
              </h4>
            </div>
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard
          class="pa-4 border rounded-xl shadow-xs cursor-pointer hover-card"
          :class="activeTab === 'approved' ? 'border-success border-2 bg-success-subtle' : ''"
          @click="() => { activeTab = 'approved'; page = 1; fetchData(); }"
        >
          <div class="d-flex align-center gap-3">
            <VAvatar color="success" variant="tonal" size="48" class="rounded-lg">
              <VIcon icon="ri-checkbox-circle-fill" size="26" />
            </VAvatar>
            <div>
              <span class="text-caption text-medium-emphasis font-weight-medium">4. Disetujui & Masuk Stok</span>
              <h4 class="text-h5 font-weight-bold text-success mb-0">
                {{ counts.approved }} <span class="text-caption font-weight-normal text-medium-emphasis">Selesai</span>
              </h4>
            </div>
          </div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Main Tabs & Table Card -->
    <VCard class="border rounded-xl shadow-xs">
      <VTabs
        v-model="activeTab"
        class="px-4 border-b"
        @update:model-value="() => { page = 1; fetchData(); }"
      >
        <VTab value="pending">
          <VIcon icon="ri-truck-line" size="18" class="mr-2 text-warning" />
          <span class="mr-2 font-weight-bold">1. Menunggu Cek Fisik Gudang</span>
          <VBadge
            v-if="counts.pending_pos > 0"
            color="warning"
            :content="counts.pending_pos"
            inline
          />
        </VTab>

        <VTab value="pending_approval">
          <VIcon icon="ri-time-line" size="18" class="mr-2 text-info" />
          <span class="mr-2 font-weight-bold">2. Menunggu Validasi Ka. Divisi</span>
          <VBadge
            v-if="counts.pending_approval > 0"
            color="info"
            :content="counts.pending_approval"
            inline
          />
        </VTab>

        <VTab value="rejected" class="text-error font-weight-bold">
          <VIcon icon="ri-close-circle-fill" size="18" class="mr-2 text-error" />
          <span class="mr-2 font-weight-bold">3. Ditolak / Perlu Revisi</span>
          <VBadge
            v-if="counts.rejected > 0"
            color="error"
            :content="counts.rejected"
            inline
          />
        </VTab>

        <VTab value="approved">
          <VIcon icon="ri-checkbox-circle-fill" size="18" class="mr-2 text-success" />
          <span class="mr-2 font-weight-bold">4. Disetujui & Masuk Stok</span>
          <VBadge
            v-if="counts.approved > 0"
            color="success"
            :content="counts.approved"
            inline
          />
        </VTab>

        <VTab value="all">
          <VIcon icon="ri-file-list-3-line" size="18" class="mr-2" />
          <span class="font-weight-bold">Semua Dokumen</span>
        </VTab>
      </VTabs>

      <VCardItem class="pa-5 pb-3">
        <div class="d-flex align-center justify-space-between w-100 flex-wrap gap-4">
          <div>
            <VCardTitle class="px-0 font-weight-bold text-h6 d-flex align-center gap-2">
              <span v-if="activeTab === 'pending'">Daftar PO yang Menunggu Cek Fisik Gudang</span>
              <span v-else-if="activeTab === 'pending_approval'">Dokumen Penerimaan Menunggu Validasi Kepala Divisi</span>
              <span v-else-if="activeTab === 'rejected'" class="text-error">Dokumen Penerimaan yang Ditolak / Perlu Revisi</span>
              <span v-else-if="activeTab === 'approved'" class="text-success">Dokumen Penerimaan yang Disetujui & Masuk Stok</span>
              <span v-else>Semua Dokumen Penerimaan Barang Gudang</span>
            </VCardTitle>
            <span class="text-caption text-medium-emphasis">
              <span v-if="activeTab === 'pending'">Klik <strong>"Terima Fisik Barang"</strong> untuk mencatat nomor SCC/Batch, cek fisik, dan mengisi data faktur supplier.</span>
              <span v-else-if="activeTab === 'pending_approval'">Dokumen telah dicek fisik oleh staf gudang dan sedang menunggu validasi harga faktur dari Kepala Divisi.</span>
              <span v-else-if="activeTab === 'rejected'" class="text-error font-weight-medium">Kepala Divisi meminta revisi faktur/fisik. Klik tombol <strong>"Perbaiki & Ajukan Ulang"</strong> untuk merevisi dokumen.</span>
              <span v-else-if="activeTab === 'approved'">Dokumen telah disetujui Kepala Divisi dan stok fisik cabang telah otomatis bertambah.</span>
              <span v-else>Seluruh riwayat penerimaan barang fisik di gudang.</span>
            </span>
          </div>

          <div class="d-flex align-center gap-3 flex-wrap">
            <div style="width: 280px;">
              <VTextField
                v-model="search"
                :placeholder="activeTab === 'pending' ? 'Cari No PO atau Supplier...' : 'Cari No Penerimaan, Faktur, Sales...'"
                prepend-inner-icon="ri-search-line"
                density="compact"
                hide-details
                variant="outlined"
                clearable
                @update:model-value="handleSearch"
              />
            </div>
          </div>
        </div>
      </VCardItem>

      <VDataTableServer
        :key="activeTab"
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="activeHeaders"
        :items="activeItems"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        @update:options="fetchData"
      >
        <!-- NO. PO for Pending -->
        <template
          v-if="activeTab === 'pending'"
          #item.po_number="{ item }"
        >
          <a
            href="#"
            class="font-weight-bold text-primary text-decoration-none"
            @click.prevent="processPO(item.id)"
          >
            {{ item.po_number }}
          </a>
        </template>

        <!-- TOTAL ITEM for Pending -->
        <template
          v-if="activeTab === 'pending'"
          #item.items="{ item }"
        >
          <VChip size="small" variant="tonal" color="primary" class="font-weight-medium">
            {{ item.items ? item.items.length : 0 }} Produk ({{ item.items ? item.items.reduce((s, i) => s + (Number(i.qty) || 0), 0) : 0 }} unit)
          </VChip>
        </template>

        <!-- AKSI for Pending -->
        <template
          v-if="activeTab === 'pending'"
          #item.actions="{ item }"
        >
          <VBtn
            color="primary"
            size="small"
            prepend-icon="ri-truck-line"
            class="font-weight-bold shadow-xs"
            @click="processPO(item.id)"
          >
            Terima Fisik Barang
          </VBtn>
        </template>

        <!-- NO. PENERIMAAN for Goods Receipts -->
        <template
          v-if="activeTab !== 'pending'"
          #item.receipt_number="{ item }"
        >
          <div>
            <a
              href="#"
              class="font-weight-bold text-primary text-decoration-none"
              @click.prevent="openDetailDialog(item)"
            >
              {{ item.receipt_number }}
            </a>
            
            <!-- Rejection Notice Box directly under receipt number -->
            <div
              v-if="item.approval_status === 'rejected'"
              class="mt-1 pa-1 px-2 rounded bg-error-subtle border border-error text-error text-caption font-weight-bold d-flex align-center gap-1"
              style="max-width: 320px; white-space: normal;"
            >
              <VIcon icon="ri-error-warning-fill" size="14" class="flex-shrink-0" />
              <span>Alasan Tolak: "{{ item.rejection_reason || 'Perlu Revisi Gudang' }}"</span>
            </div>
          </div>
        </template>

        <!-- NO. PO for Goods Receipts -->
        <template
          v-if="activeTab !== 'pending'"
          #item.purchase_order.po_number="{ item }"
        >
          <span class="font-weight-medium">{{ item.purchase_order?.po_number || '-' }}</span>
        </template>

        <!-- FAKTUR SUPPLIER -->
        <template
          v-if="activeTab !== 'pending'"
          #item.invoice_number_supplier="{ item }"
        >
          <span class="font-mono font-weight-medium">{{ item.invoice_number_supplier || '-' }}</span>
        </template>

        <!-- SALES SUPPLIER -->
        <template
          v-if="activeTab !== 'pending'"
          #item.sales_name="{ item }"
        >
          <span>{{ item.sales_name || '-' }}</span>
        </template>
        
        <!-- Format Received Date -->
        <template
          v-if="activeTab !== 'pending'"
          #item.received_date="{ item }"
        >
          <span>{{ item.received_date ? item.received_date.substring(0, 10) : (item.date ? item.date.substring(0, 10) : '-') }}</span>
        </template>

        <!-- Format Due Date -->
        <template
          v-if="activeTab !== 'pending'"
          #item.due_date="{ item }"
        >
          <span v-if="item.due_date" class="font-mono text-caption">{{ item.due_date.substring(0, 10) }}</span>
          <span v-else class="text-caption text-medium-emphasis">Tunai / Selesai</span>
        </template>

        <!-- Total Faktur -->
        <template
          v-if="activeTab !== 'pending'"
          #item.total_amount="{ item }"
        >
          <span class="font-weight-bold font-mono text-success">{{ formatRupiah(item.total_amount) }}</span>
        </template>
        
        <!-- Status Approval Chips -->
        <template
          v-if="activeTab !== 'pending'"
          #item.approval_status="{ item }"
        >
          <VChip
            v-if="item.approval_status === 'approved'"
            color="success"
            size="small"
            variant="flat"
            prepend-icon="ri-checkbox-circle-fill"
            class="font-weight-bold text-uppercase"
          >
            Disetujui & Masuk Stok
          </VChip>
          <VChip
            v-else-if="item.approval_status === 'rejected'"
            color="error"
            size="small"
            variant="flat"
            prepend-icon="ri-close-circle-fill"
            class="font-weight-bold text-uppercase"
          >
            Ditolak / Revisi
          </VChip>
          <VChip
            v-else
            color="info"
            size="small"
            variant="flat"
            prepend-icon="ri-time-line"
            class="font-weight-bold text-uppercase"
          >
            Menunggu Ka. Divisi
          </VChip>
        </template>
        
        <!-- Actions for Goods Receipts -->
        <template
          v-if="activeTab !== 'pending'"
          #item.actions="{ item }"
        >
          <div class="d-flex align-center justify-center gap-1 flex-wrap">
            <!-- Action for REJECTED: Perbaiki & Ajukan Ulang -->
            <VBtn
              v-if="item.approval_status === 'rejected'"
              color="error"
              size="small"
              variant="flat"
              prepend-icon="ri-edit-2-line"
              class="font-weight-bold shadow-xs"
              @click="processEditGR(item)"
            >
              Perbaiki & Ajukan Ulang
            </VBtn>

            <!-- Approval Quick Action Button for Division Head -->
            <template v-if="item.approval_status === 'pending_approval' || !item.approval_status">
              <VBtn
                v-if="$can('approve', 'Purchase Order') || $can('approve', 'Penerimaan Gudang') || $can('manage all', 'all')"
                color="success"
                size="small"
                variant="flat"
                prepend-icon="ri-check-line"
                class="font-weight-bold"
                :loading="isApproving"
                @click="openApproveDialog(item)"
              >
                Setujui
              </VBtn>

              <VBtn
                v-if="$can('approve', 'Purchase Order') || $can('approve', 'Penerimaan Gudang') || $can('manage all', 'all')"
                color="error"
                size="small"
                variant="tonal"
                @click="openRejectDialog(item)"
              >
                Tolak
              </VBtn>
            </template>

            <VBtn
              icon="ri-eye-line"
              color="primary"
              size="small"
              variant="text"
              @click="openDetailDialog(item)"
            />

            <VBtn
              icon="ri-printer-line"
              color="secondary"
              size="small"
              variant="text"
              @click="printGR(item.id)"
            />

            <VBtn
              v-if="$can('delete', 'Penerimaan Gudang')"
              icon="ri-delete-bin-line"
              color="error"
              size="small"
              variant="text"
              @click="confirmDeleteGR(item)"
            />
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Detail Dialog (Capella Receipt Layout) -->
    <VDialog
      v-model="isDetailDialogVisible"
      max-width="960"
      scrollable
    >
      <VCard v-if="detailDialogData" class="rounded-xl overflow-hidden shadow-lg d-flex flex-column" style="max-height: 90vh;">
        <!-- Header Kuitansi / Dialog -->
        <div class="px-6 py-5 border-b bg-gradient-header d-flex justify-space-between align-center flex-shrink-0">
          <div class="d-flex align-center gap-3">
            <VAvatar color="primary" variant="tonal" size="44" class="rounded-lg">
              <VIcon icon="ri-bill-line" size="24" />
            </VAvatar>
            <div>
              <h5 class="text-h6 font-weight-bold mb-0">
                {{ activeTab === 'pending' ? `Rincian Permintaan PO: ${detailDialogData.po_number}` : `Faktur Penerimaan Barang Gudang: ${detailDialogData.receipt_number}` }}
              </h5>
              <span class="text-caption text-medium-emphasis">
                {{ activeTab === 'pending' ? 'Daftar barang yang harus diverifikasi kedatangannya.' : 'Dokumen fisik penerimaan barang & verifikasi harga oleh Kepala Divisi.' }}
              </span>
            </div>
          </div>
          <VBtn
            icon="ri-close-line"
            variant="text"
            size="small"
            color="secondary"
            @click="isDetailDialogVisible = false"
          />
        </div>

        <VCardText class="pa-6 overflow-y-auto" style="max-height: calc(90vh - 130px);">
          <!-- Content for PENDING -->
          <div v-if="activeTab === 'pending'">
            <div class="mb-4 pa-4 rounded-xl border bg-var-theme-surface">
              <VRow dense>
                <VCol cols="12" sm="4">
                  <div class="text-caption text-medium-emphasis">Supplier / Vendor:</div>
                  <div class="font-weight-bold text-primary">{{ detailDialogData.supplier?.name || '-' }}</div>
                </VCol>
                <VCol cols="12" sm="4">
                  <div class="text-caption text-medium-emphasis">Cabang Tujuan:</div>
                  <div class="font-weight-bold">{{ detailDialogData.branch?.name || '-' }}</div>
                </VCol>
                <VCol cols="12" sm="4">
                  <div class="text-caption text-medium-emphasis">Tanggal Pemesanan:</div>
                  <div class="font-weight-bold">{{ detailDialogData.date ? detailDialogData.date.substring(0, 10) : '-' }}</div>
                </VCol>
              </VRow>
            </div>

            <div class="border rounded-xl overflow-hidden mb-4">
              <table class="w-100 table-receipt">
                <thead>
                  <tr class="bg-grey-100 text-left">
                    <th class="pa-3 text-sm text-center" style="width: 50px;">NO</th>
                    <th class="pa-3 text-sm">NAMA BARANG</th>
                    <th class="pa-3 text-sm text-center" style="width: 140px;">QTY DIPESAN</th>
                    <th class="pa-3 text-sm text-center" style="width: 100px;">SATUAN</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(i, idx) in detailDialogData.items"
                    :key="i.id"
                    class="border-b"
                  >
                    <td class="pa-3 text-sm text-center">{{ idx + 1 }}</td>
                    <td class="pa-3 text-sm font-weight-bold">
                      {{ i.product?.name || i.product_branch?.product?.name || 'Item' }}
                    </td>
                    <td class="pa-3 text-sm text-center font-weight-bold text-primary">
                      {{ i.qty }}
                    </td>
                    <td class="pa-3 text-sm text-center text-uppercase">
                      {{ i.unit_name || 'pcs' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Content for COMPLETED (Capella Official Receipt View) -->
          <div v-else>
            <!-- Rejection Alert Banner at top of detail dialog -->
            <VAlert
              v-if="detailDialogData.approval_status === 'rejected'"
              type="error"
              variant="tonal"
              class="mb-4 pa-4 rounded-xl border-dashed"
              icon="ri-error-warning-fill"
            >
              <div class="font-weight-bold text-subtitle-2 mb-1">
                Dokumen Ini Dikembalikan / Ditolak oleh Kepala Divisi:
              </div>
              <div class="text-body-2 bg-var-theme-surface pa-3 rounded border text-error font-weight-medium">
                "{{ detailDialogData.rejection_reason || 'Silakan cek kembali kesesuaian fisik, rincian diskon, atau kejelasan foto faktur.' }}"
              </div>
            </VAlert>

            <!-- Invoice Header Box -->
            <div class="mb-5 pa-4 rounded-xl border bg-var-theme-surface shadow-xs">
              <div class="d-flex justify-space-between align-center mb-3 pb-3 border-b flex-wrap gap-2">
                <div>
                  <div class="text-subtitle-1 font-weight-bold text-primary">
                    {{ detailDialogData.purchase_order?.supplier?.name || 'PT. CAPELLA PATRIA UTAMA' }}
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    No. Faktur Supplier: <strong>{{ detailDialogData.invoice_number_supplier || '-' }}</strong> | Sales: <strong>{{ detailDialogData.sales_name || '-' }}</strong>
                  </div>
                </div>
                <div class="text-right">
                  <VChip
                    v-if="detailDialogData.approval_status === 'approved'"
                    color="success"
                    size="small"
                    variant="flat"
                    prepend-icon="ri-checkbox-circle-fill"
                    class="font-weight-bold"
                  >
                    DISETUJUI & MASUK STOK
                  </VChip>
                  <VChip
                    v-else-if="detailDialogData.approval_status === 'rejected'"
                    color="error"
                    size="small"
                    variant="flat"
                    prepend-icon="ri-close-circle-fill"
                    class="font-weight-bold"
                  >
                    DITOLAK / PERLU REVISI
                  </VChip>
                  <VChip
                    v-else
                    color="info"
                    size="small"
                    variant="flat"
                    prepend-icon="ri-time-line"
                    class="font-weight-bold"
                  >
                    MENUNGGU APPROVAL KA. DIVISI
                  </VChip>
                </div>
              </div>

              <VRow dense>
                <VCol cols="12" sm="3">
                  <div class="text-caption text-medium-emphasis">Cabang Penerima:</div>
                  <div class="font-weight-bold text-truncate">{{ detailDialogData.purchase_order?.branch?.name || '-' }}</div>
                </VCol>
                <VCol cols="12" sm="3">
                  <div class="text-caption text-medium-emphasis">Nomor PO Asal:</div>
                  <div class="font-weight-bold text-primary">{{ detailDialogData.purchase_order?.po_number || '-' }}</div>
                </VCol>
                <VCol cols="12" sm="3">
                  <div class="text-caption text-medium-emphasis">Tgl Barang Sampai:</div>
                  <div class="font-weight-bold">{{ detailDialogData.received_date ? detailDialogData.received_date.substring(0, 10) : (detailDialogData.date ? detailDialogData.date.substring(0, 10) : '-') }}</div>
                </VCol>
                <VCol cols="12" sm="3">
                  <div class="text-caption text-medium-emphasis">Jatuh Tempo Faktur:</div>
                  <div class="font-weight-bold font-mono">{{ detailDialogData.due_date ? detailDialogData.due_date.substring(0, 10) : 'Tunai' }}</div>
                </VCol>
              </VRow>

              <div class="mt-3 pt-2 border-t d-flex gap-4 flex-wrap text-caption text-medium-emphasis">
                <div>Petugas Gudang (Input): <strong>{{ detailDialogData.validator?.name || detailDialogData.user?.name || '-' }}</strong></div>
                <div v-if="detailDialogData.approver">Disetujui Oleh (Ka. Divisi): <strong>{{ detailDialogData.approver?.name }}</strong> ({{ detailDialogData.approved_at ? detailDialogData.approved_at.substring(0, 16) : '-' }})</div>
              </div>
            </div>

            <!-- Table Items Matching Capella Invoice -->
            <div class="border rounded-xl overflow-hidden mb-5">
              <table class="w-100 table-receipt">
                <thead>
                  <tr class="bg-grey-100 text-left">
                    <th class="pa-3 text-xs text-center" style="width: 40px;">NO</th>
                    <th class="pa-3 text-xs">KODEPART / NAMA BRG.</th>
                    <th class="pa-3 text-xs text-center" style="width: 70px;">QTY</th>
                    <th class="pa-3 text-xs text-right" style="width: 120px;">HRG/@</th>
                    <th class="pa-3 text-xs text-center" style="width: 110px;">DISCOUNT</th>
                    <th class="pa-3 text-xs text-right" style="width: 120px;">NETTO</th>
                    <th class="pa-3 text-xs text-right" style="width: 140px;">JUMLAH RP (Inc Ppn)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(i, idx) in detailDialogData.items"
                    :key="i.id"
                    class="border-b"
                    :class="!i.is_received || i.qty_received === 0 ? 'bg-red-50' : ''"
                  >
                    <td class="pa-3 text-xs text-center">{{ idx + 1 }}</td>
                    <td class="pa-3 text-xs">
                      <div class="font-weight-bold">
                        {{ i.product_branch?.product?.name || i.purchase_order_item?.product?.name || 'Item' }}
                      </div>
                      <div class="text-caption text-medium-emphasis d-flex gap-2 flex-wrap mt-1">
                        <span v-if="i.scc_code" class="text-primary font-weight-medium">SCC: <code>{{ i.scc_code }}</code></span>
                        <span v-if="i.batch_number">Batch: <code>{{ i.batch_number }}</code></span>
                        <span v-if="i.expiration_date">Exp: {{ i.expiration_date.substring(0, 10) }}</span>
                      </div>
                      <div v-if="!i.is_received || i.qty_rejected > 0" class="text-caption text-error font-weight-bold mt-1 d-flex align-center gap-1">
                        <VIcon icon="ri-close-circle-line" size="14" color="error" />
                        Diretur ({{ i.qty_rejected }} unit): {{ i.rejection_reason || 'Ditolak saat verifikasi fisik' }}
                      </div>
                    </td>
                    <td class="pa-3 text-xs text-center font-weight-bold" :class="i.qty_received > 0 ? 'text-success' : 'text-error'">
                      {{ i.qty_received }} {{ i.unit_name || 'pcs' }}
                    </td>
                    <td class="pa-3 text-xs text-right font-mono">
                      {{ formatRupiah(i.gross_price || 0) }}
                    </td>
                    <td class="pa-3 text-xs text-center font-mono">
                      {{ i.discount_string || (i.discount_percent_1 ? `${i.discount_percent_1}%` : '0%') }}
                      <span v-if="i.discount_amount > 0" class="d-block text-caption text-medium-emphasis">
                        -{{ formatRupiah(i.discount_amount) }}
                      </span>
                    </td>
                    <td class="pa-3 text-xs text-right font-mono font-weight-medium">
                      {{ formatRupiah(i.final_cost_per_piece || i.net_unit_price || 0) }}
                    </td>
                    <td class="pa-3 text-xs text-right font-mono font-weight-bold text-success">
                      {{ formatRupiah((i.qty_received || 0) * (i.net_unit_price || i.final_cost_per_piece || 0)) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Bottom Capella Invoice Summary Box -->
            <div class="pa-4 bg-var-theme-surface border rounded-xl shadow-xs mb-5">
              <VRow align="center" justify="space-between">
                <VCol cols="12" md="6">
                  <div class="text-caption text-medium-emphasis mb-1 font-weight-bold">
                    Rincian Pajak & Faktur Capella:
                  </div>
                  <div class="text-body-2">
                    D P P : <strong class="font-mono">{{ formatRupiah(detailDialogData.dpp_amount || (detailDialogData.total_amount ? Math.round(detailDialogData.total_amount / 1.11) : 0)) }}</strong>
                  </div>
                  <div class="text-body-2">
                    P P N (11%) : <strong class="font-mono">{{ formatRupiah(detailDialogData.tax_amount || (detailDialogData.total_amount ? (detailDialogData.total_amount - Math.round(detailDialogData.total_amount / 1.11)) : 0)) }}</strong>
                  </div>
                  <div class="text-caption text-medium-emphasis mt-1">
                    * Harga sudah termasuk PPN 11%
                  </div>
                </VCol>

                <VCol cols="12" md="6">
                  <div class="d-flex flex-column align-end">
                    <div class="text-caption text-medium-emphasis font-weight-bold">TOTAL (Inc Ppn):</div>
                    <div class="text-h4 font-weight-bold text-success font-mono">
                      {{ formatRupiah(detailDialogData.total_amount || 0) }}
                    </div>
                  </div>
                </VCol>
              </VRow>
            </div>

            <!-- Foto Lampiran Surat Jalan & Fisik -->
            <div
              v-if="detailDialogData.photos && detailDialogData.photos.length > 0"
              class="mb-4"
            >
              <div class="text-caption font-weight-bold mb-2 text-primary d-flex align-center gap-1">
                <VIcon icon="ri-image-line" size="16" />
                Lampiran Bukti Foto Surat Jalan & Fisik ({{ detailDialogData.photos.length }} Foto):
              </div>
              <div class="d-flex flex-wrap gap-3">
                <a 
                  v-for="(photo, index) in detailDialogData.photos" 
                  :key="index"
                  :href="'/storage/' + photo"
                  target="_blank"
                  class="border rounded-lg overflow-hidden position-relative shadow-sm hover-scale d-inline-block"
                  style="width: 90px; height: 90px;"
                >
                  <img
                    :src="'/storage/' + photo"
                    alt="Bukti Foto"
                    style="width: 100%; height: 100%; object-fit: cover;"
                  >
                  <div
                    class="position-absolute bg-primary text-white text-caption px-1 rounded font-weight-bold"
                    style="top: 3px; left: 3px; line-height: 1.2; font-size: 10px;"
                  >
                    #{{ index + 1 }}
                  </div>
                </a>
              </div>
            </div>

            <!-- Catatan Gudang -->
            <div
              v-if="detailDialogData.notes"
              class="pa-3 bg-grey-50 rounded-lg text-caption border"
            >
              <span class="font-weight-bold">Catatan Penerimaan Gudang:</span> {{ detailDialogData.notes }}
            </div>
          </div>
        </VCardText>
        
        <VCardActions class="px-6 py-4 border-t d-flex justify-end gap-3 bg-grey-50 flex-wrap flex-shrink-0">
          <VBtn
            color="secondary"
            variant="outlined"
            @click="isDetailDialogVisible = false"
          >
            Tutup
          </VBtn>

          <VBtn
            v-if="activeTab === 'pending'"
            color="primary"
            prepend-icon="ri-truck-line"
            @click="handleActionFromDialog"
          >
            Terima Fisik Barang
          </VBtn>

          <template v-if="activeTab !== 'pending'">
            <!-- REVISION ACTION: If Rejected, allow direct revision -->
            <VBtn
              v-if="detailDialogData.approval_status === 'rejected'"
              color="error"
              variant="flat"
              prepend-icon="ri-edit-2-line"
              class="font-weight-bold"
              @click="isDetailDialogVisible = false; processEditGR(detailDialogData)"
            >
              Perbaiki Dokumen Sekarang
            </VBtn>

            <!-- Division Head Action Buttons -->
            <VBtn
              v-if="($can('approve', 'Purchase Order') || $can('approve', 'Penerimaan Gudang') || $can('manage all', 'all')) && (detailDialogData.approval_status === 'pending_approval' || !detailDialogData.approval_status)"
              color="error"
              variant="outlined"
              prepend-icon="ri-close-line"
              @click="openRejectDialog(detailDialogData)"
            >
              Tolak / Minta Revisi
            </VBtn>

            <VBtn
              v-if="($can('approve', 'Purchase Order') || $can('approve', 'Penerimaan Gudang') || $can('manage all', 'all')) && (detailDialogData.approval_status === 'pending_approval' || !detailDialogData.approval_status)"
              color="success"
              variant="flat"
              prepend-icon="ri-checkbox-circle-fill"
              class="font-weight-bold"
              :loading="isApproving"
              @click="openApproveDialog(detailDialogData)"
            >
              Setujui & Tambahkan ke Stok Fisik
            </VBtn>

            <VBtn
              color="primary"
              variant="tonal"
              prepend-icon="ri-printer-line"
              @click="printGR(detailDialogData.id)"
            >
              Cetak Faktur PDF
            </VBtn>
          </template>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Dialog Konfirmasi Approval Penerimaan Gudang -->
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

    <!-- Dialog Penolakan Penerimaan Gudang -->
    <VDialog
      v-model="isRejectDialogVisible"
      max-width="500"
    >
      <VCard class="rounded-xl">
        <VCardTitle class="pa-5 pb-2 font-weight-bold text-h6 text-error d-flex align-center gap-2">
          <VIcon icon="ri-error-warning-line" />
          Tolak Dokumen Penerimaan Gudang
        </VCardTitle>
        <VCardText class="pa-5">
          <p class="text-body-2 mb-3">
            Masukkan alasan penolakan atau instruksi koreksi untuk staf gudang:
          </p>
          <VTextarea
            v-model="rejectReason"
            label="Alasan Penolakan / Catatan Revisi"
            placeholder="Misal: Harga diskon Capella pada faktur tidak sesuai atau foto faktur buram..."
            rows="3"
            variant="outlined"
          />
        </VCardText>
        <VCardActions class="pa-5 pt-0 justify-end gap-2">
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

    <!-- Print Preview Dialog -->
    <VDialog
      v-model="isPrintDialogVisible"
      max-width="900"
      @update:model-value="(val) => { if(!val) closePrintDialog() }"
    >
      <VCard>
        <VCardTitle class="d-flex justify-space-between align-center px-6 pt-6 pb-4">
          <span>Pratinjau PDF</span>
          <div class="d-flex gap-2">
            <VBtn
              icon
              variant="text"
              size="small"
              @click="closePrintDialog"
            >
              <VIcon icon="ri-close-line" />
            </VBtn>
          </div>
        </VCardTitle>
        
        <VCardText class="px-6 pb-6 pt-0">
          <iframe
            v-if="printUrl"
            :src="printUrl"
            width="100%"
            height="600px"
            style="border: none; border-radius: 8px;"
          />
        </VCardText>
      </VCard>
    </VDialog>

    <ReceiveGoodsDrawer
      v-if="selectedPO"
      v-model:is-drawer-open="isDrawerVisible"
      :is-drawer-open="isDrawerVisible"
      :selected-po="selectedPO"
      :selected-gr="selectedGR"
      @update:is-drawer-open="val => isDrawerVisible = val"
      @update:isDrawerOpen="val => isDrawerVisible = val"
      @close="isDrawerVisible = false"
      @cancel="isDrawerVisible = false"
      @save-data="saveGoodsReceipt"
    />

    <!-- Confirm Delete Dialog -->
    <VDialog
      v-model="isConfirmDeleteVisible"
      max-width="500"
    >
      <VCard>
        <VCardTitle class="text-error bg-error-lighten-4 pa-4">
          Konfirmasi Hapus Dokumen
        </VCardTitle>
        <VCardText class="pa-6">
          <p class="text-body-1">
            Apakah Anda yakin ingin menghapus Penerimaan Gudang <strong>{{ grToDelete?.receipt_number }}</strong>?
          </p>
          <VAlert
            type="warning"
            variant="tonal"
            class="mt-4 text-caption mb-4"
          >
            Jika dokumen ini sudah diverifikasi, menghapusnya akan <strong>menarik kembali stok barang</strong> yang sudah masuk ke gudang. Status PO akan dikembalikan menjadi 'pending'. Tindakan ini tidak bisa dikembalikan.
          </VAlert>
        </VCardText>
        <VCardActions class="pa-4 pt-0 justify-end">
          <VBtn
            color="secondary"
            variant="outlined"
            @click="isConfirmDeleteVisible = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="error"
            prepend-icon="ri-delete-bin-line"
            @click="executeDeleteGR"
          >
            Ya, Hapus Dokumen
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </section>
</template>

<style scoped>
.bg-gradient-header {
  background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.05) 0%, rgba(var(--v-theme-surface), 1) 100%);
}
.hover-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}
.hover-scale {
  transition: transform 0.2s ease;
}
.hover-scale:hover {
  transform: scale(1.05);
}
.bg-error-subtle {
  background-color: rgba(var(--v-theme-error), 0.08);
}
.bg-warning-subtle {
  background-color: rgba(var(--v-theme-warning), 0.08);
}
.bg-info-subtle {
  background-color: rgba(var(--v-theme-info), 0.08);
}
.bg-success-subtle {
  background-color: rgba(var(--v-theme-success), 0.08);
}
.table-receipt th {
  border-bottom: 2px solid rgba(var(--v-border-color), 0.12);
  background-color: rgba(var(--v-theme-surface-variant), 0.3);
  font-weight: 700;
}
.table-receipt td {
  border-bottom: 1px solid rgba(var(--v-border-color), 0.08);
}
</style>

<route lang="yaml">
meta:
  action: read
  subject: Penerimaan Gudang
</route>
