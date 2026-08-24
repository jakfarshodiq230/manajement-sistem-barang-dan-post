<script setup>
import { ref, onMounted, computed } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import AddNewMutasiDrawer from './AddNewMutasiDrawer.vue'
import SimpleConfirmDialog from '@/components/dialogs/SimpleConfirmDialog.vue'

const mutasiList = ref([])
const branches = ref([])
const employees = ref([])
const search = ref('')
const selectedSourceBranch = ref(null)
const selectedDestinationBranch = ref(null)
const isLoading = ref(false)
const isAddNewDrawerVisible = ref(false)
const isTrackingDialogVisible = ref(false)
const trackingMutasi = ref(null)
const activeTab = ref('all')

// Prepare / Verification Dialog State
const isPrepareDialogVisible = ref(false)
const prepareMutasi = ref(null)
const prepareItems = ref([])
const isSubmittingPrepare = ref(false)

// Pickup Dialog State (Tahap 3 Penjemputan Barang)
const isPickupDialogVisible = ref(false)
const pickupMutasi = ref(null)
const pickupEmployeeName = ref('')
const pickupNotes = ref('')
const isSubmittingPickup = ref(false)

// Reject Dialog State
const isRejectDialogVisible = ref(false)
const rejectMutasiId = ref(null)
const rejectReason = ref('')
const isSubmittingReject = ref(false)

// Delivery Note (Surat Jalan) State
const isDeliveryNoteDialogVisible = ref(false)
const deliveryNoteData = ref(null)
const isLoadingDeliveryNote = ref(false)

const openDeliveryNoteDialog = async item => {
  isLoadingDeliveryNote.value = true
  isDeliveryNoteDialogVisible.value = true
  deliveryNoteData.value = item
  try {
    const res = await $api(`/apps/stock-transfers/${item.id}/delivery-note`)
    deliveryNoteData.value = res.data || item
  } catch (e) {
    console.error('Failed to load delivery note detail:', e)
  } finally {
    isLoadingDeliveryNote.value = false
  }
}

const printDeliveryNote = () => {
  window.print()
}

// Pagination
const page = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)
let searchTimeout = null

const snackbar = useSnackbarStore()

const extractArray = val => {
  if (Array.isArray(val)) return val
  if (val && Array.isArray(val.data)) return val.data
  return []
}

// Fetch options once on mount
const fetchInitialOptions = async () => {
  try {
    const branchData = await $api('/apps/branches')
    branches.value = extractArray(branchData)
  } catch (e) {
    console.error('Failed to load initial options:', e)
    branches.value = []
  }
}

let isFetching = false
const fetchData = async () => {
  if (isFetching) return
  isFetching = true
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
    }
    
    if (search.value) params.search = search.value
    if (selectedSourceBranch.value) params.source_branch_id = selectedSourceBranch.value
    if (selectedDestinationBranch.value) params.destination_branch_id = selectedDestinationBranch.value
    
    if (activeTab.value === 'pending') {
      params.status = 'pending'
    } else if (activeTab.value === 'ready_for_pickup') {
      params.status = 'ready_for_pickup'
    } else if (activeTab.value === 'completed') {
      params.status = 'completed'
    } else if (activeTab.value === 'rejected_cancelled') {
      params.status = 'rejected_cancelled'
    }

    const mutasiData = await $api('/apps/stock-transfers', { query: params })

    mutasiList.value = extractArray(mutasiData)
    totalItems.value = mutasiData?.total ?? (Array.isArray(mutasiList.value) ? mutasiList.value.length : 0)
    
    // Refresh stats badge counts
    fetchStatusCounts()
  } catch (error) {
    console.error('Error loading mutasi:', error)
    snackbar.show('Gagal memuat daftar mutasi stok', 'error')
    mutasiList.value = []
  } finally {
    isLoading.value = false
    isFetching = false
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
  fetchInitialOptions()
  fetchData()
})

const saveMutasi = () => {
  page.value = 1
  fetchData()
}

const tableHeaders = [
  { title: 'NO. REFERENSI', key: 'reference_no' },
  { title: 'TANGGAL REQUEST', key: 'created_at' },
  { title: 'CABANG ASAL (SUMBER)', key: 'source_branch.name' },
  { title: 'CABANG TUJUAN (PEMOHON)', key: 'destination_branch.name' },
  { title: 'PENJEMPUT', key: 'picked_up_by_name' },
  { title: 'STATUS TAHAPAN', key: 'status' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const statusCounts = ref({
  total: 0,
  pending: 0,
  ready_for_pickup: 0,
  completed: 0,
  rejected_cancelled: 0,
})

const countPending = computed(() => statusCounts.value.pending)
const countReadyForPickup = computed(() => statusCounts.value.ready_for_pickup)

const fetchStatusCounts = async () => {
  try {
    const params = {}
    if (selectedSourceBranch.value) params.source_branch_id = selectedSourceBranch.value
    if (selectedDestinationBranch.value) params.destination_branch_id = selectedDestinationBranch.value
    
    const res = await $api('/apps/stock-transfers/status-counts', { query: params })
    if (res) {
      statusCounts.value = {
        total: res.total ?? 0,
        pending: res.pending ?? 0,
        ready_for_pickup: res.ready_for_pickup ?? 0,
        completed: res.completed ?? 0,
        rejected_cancelled: res.rejected_cancelled ?? 0,
      }
    }
  } catch (e) {
    console.error('Failed to fetch status counts:', e)
  }
}

const openTrackingDialog = async mutasi => {
  try {
    const res = await $api(`/apps/stock-transfers/${mutasi.id}`)
    trackingMutasi.value = res
  } catch (e) {
    trackingMutasi.value = mutasi
  }
  isTrackingDialogVisible.value = true
}

// Open Prepare / Checklist Dialog
const openPrepareDialog = async mutasi => {
  try {
    const res = await $api(`/apps/stock-transfers/${mutasi.id}`)
    prepareMutasi.value = res
    prepareItems.value = (res.items || []).map(item => {
      const stock = item.source_current_stock ?? 0
      const isAvail = stock > 0
      return {
        id: item.id,
        product_id: item.product_id,
        product: item.product,
        qty_requested: item.qty,
        source_current_stock: stock,
        is_available: isAvail,
        qty_prepared: isAvail ? Math.min(item.qty, stock) : 0,
        cancel_reason: isAvail ? '' : 'Barang Kosong / Habis di Unit Asal',
      }
    })
    isPrepareDialogVisible.value = true
  } catch (e) {
    console.error(e)
    snackbar.show('Gagal memuat rincian stok cabang asal', 'error')
  }
}

const handleCheckboxChange = item => {
  if (item.is_available) {
    item.qty_prepared = Math.min(item.qty_requested, Math.max(item.source_current_stock, 1))
    item.cancel_reason = ''
  } else {
    item.qty_prepared = 0
    if (!item.cancel_reason) {
      item.cancel_reason = 'Barang Kosong / Habis di Unit Asal'
    }
  }
}

const totalAvailableItemsCount = computed(() => {
  return prepareItems.value.filter(i => i.is_available && i.qty_prepared > 0).length
})

const submitPrepare = async () => {
  // Validate if any item has invalid qty
  for (const item of prepareItems.value) {
    if (item.is_available) {
      if (item.qty_prepared <= 0) {
        snackbar.show(`Barang ${item.product?.name} diceklis ada, namun jumlah kirim masih 0. Masukkan jumlah atau uncheck.`, 'warning')
        return
      }
      if (item.qty_prepared > item.source_current_stock) {
        snackbar.show(`Jumlah kirim untuk ${item.product?.name} (${item.qty_prepared}) melebihi sisa stok di unit asal (${item.source_current_stock})!`, 'error')
        return
      }
    }
  }

  isSubmittingPrepare.value = true
  try {
    const payload = {
      items: prepareItems.value.map(i => ({
        id: i.id,
        is_available: i.is_available,
        qty_prepared: i.is_available ? i.qty_prepared : 0,
        cancel_reason: !i.is_available ? (i.cancel_reason || 'Barang Kosong') : null,
      })),
    }

    const res = await $api(`/apps/stock-transfers/${prepareMutasi.value.id}/prepare`, {
      method: 'POST',
      body: payload,
    })

    snackbar.show(res.message || 'Barang berhasil disiapkan', 'success')
    isPrepareDialogVisible.value = false
    isTrackingDialogVisible.value = false
    fetchData()
  } catch (error) {
    console.error(error)
    const errorMsg = error.response?._data?.error || error.response?._data?.message || 'Gagal memproses penyiapan'
    snackbar.show(`Gagal: ${errorMsg}`, 'error')
  } finally {
    isSubmittingPrepare.value = false
  }
}

// Reject Dialog handlers
const openRejectDialog = mutasi => {
  rejectMutasiId.value = mutasi.id
  rejectReason.value = ''
  isRejectDialogVisible.value = true
}

const submitReject = async () => {
  if (!rejectMutasiId.value) return
  isSubmittingReject.value = true
  try {
    const res = await $api(`/apps/stock-transfers/${rejectMutasiId.value}/reject`, {
      method: 'POST',
      body: { reason: rejectReason.value },
    })
    snackbar.show(res.message || 'Permintaan mutasi ditolak', 'success')
    isRejectDialogVisible.value = false
    isTrackingDialogVisible.value = false
    fetchData()
  } catch (error) {
    console.error(error)
    const errorMsg = error.response?._data?.error || error.response?._data?.message || 'Gagal menolak mutasi'
    snackbar.show(`Gagal: ${errorMsg}`, 'error')
  } finally {
    isSubmittingReject.value = false
  }
}

// Pickup Dialog Handlers (Tahap 3)
const openPickupDialog = async mutasi => {
  pickupMutasi.value = mutasi
  pickupEmployeeName.value = ''
  pickupNotes.value = ''
  isPickupDialogVisible.value = true
  if (employees.value.length === 0) {
    try {
      const employeeData = await $api('/apps/employees', { query: { itemsPerPage: 100 } })
      employees.value = extractArray(employeeData)
    } catch (e) {
      console.error('Failed to load employees:', e)
    }
  }
}

const submitPickup = async () => {
  let employeeName = ''
  if (typeof pickupEmployeeName.value === 'object' && pickupEmployeeName.value !== null) {
    employeeName = pickupEmployeeName.value.name || ''
  } else if (typeof pickupEmployeeName.value === 'string') {
    employeeName = pickupEmployeeName.value.trim()
  }

  if (!employeeName) {
    snackbar.show('Mohon masukkan nama karyawan yang menjemput barang!', 'warning')
    return
  }

  if (!pickupMutasi.value) return

  isSubmittingPickup.value = true
  try {
    const res = await $api(`/apps/stock-transfers/${pickupMutasi.value.id}/receive`, {
      method: 'POST',
      body: {
        picked_up_by_name: employeeName,
        pickup_notes: pickupNotes.value ? String(pickupNotes.value).trim() : null,
      },
    })
    snackbar.show(res.message || 'Barang berhasil dikonfirmasi telah dijemput', 'success')
    isPickupDialogVisible.value = false
    isTrackingDialogVisible.value = false
    fetchData()
  } catch (error) {
    console.error(error)
    const errorMsg = error.response?._data?.error || error.response?._data?.message || error.message || 'Gagal memproses penjemputan'
    snackbar.show(`Gagal: ${errorMsg}`, 'error')
  } finally {
    isSubmittingPickup.value = false
  }
}

// Simple Confirm Actions for Cancel
const pendingAction = ref(null)
const isConfirmDialogVisible = ref(false)
const confirmTitle = ref('')
const confirmMessage = ref('')
const confirmActionText = ref('')
const confirmColor = ref('')

const handleActionConfirm = (id, action) => {
  if (action === 'cancel') {
    confirmTitle.value = 'Batalkan Mutasi Stok'
    confirmMessage.value = 'Apakah Anda yakin ingin membatalkan mutasi ini? Stok barang yang sudah disiapkan akan otomatis dikembalikan ke unit asal.'
    confirmActionText.value = 'Ya, Batalkan'
    confirmColor.value = 'error'
  }
  
  pendingAction.value = { id, action }
  isConfirmDialogVisible.value = true
}

const onConfirmAction = async confirmed => {
  if (confirmed && pendingAction.value) {
    const { id, action } = pendingAction.value
    try {
      const res = await $api(`/apps/stock-transfers/${id}/${action}`, { 
        method: 'POST',
      })
      snackbar.show(res.message || 'Aksi berhasil diproses', 'success')
      isTrackingDialogVisible.value = false
      fetchData()
    } catch (error) {
      console.error(error)
      const errorMsg = error.response?._data?.error || error.response?._data?.message || error.message || 'Gagal memproses'
      snackbar.show(`Gagal: ${errorMsg}`, 'error')
    }
  }
  pendingAction.value = null
}

const getStatusBadge = status => {
  switch (status) {
    case 'pending':
      return { text: '1. Request Diajukan (Menunggu Respon)', color: 'warning', icon: 'ri-time-line' }
    case 'ready_for_pickup':
    case 'approved':
      return { text: '2. Disiapkan (Siap Dijemput)', color: 'info', icon: 'ri-truck-line' }
    case 'completed':
      return { text: '3. Selesai (Sudah Dijemput)', color: 'success', icon: 'ri-checkbox-circle-line' }
    case 'rejected':
      return { text: 'Ditolak', color: 'error', icon: 'ri-close-circle-line' }
    case 'cancelled':
      return { text: 'Dibatalkan', color: 'secondary', icon: 'ri-forbid-2-line' }
    default:
      return { text: status, color: 'primary', icon: 'ri-information-line' }
  }
}

const formatDateTime = dateStr => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold mb-1">
          Mutasi Stok Barang
        </h4>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Alur 3 Tahap: Pengajuan Request &rarr; Disiapkan Unit Asal &rarr; Dijemput & Diterima Cabang Pemohon
        </p>
      </div>

      <VBtn
        v-if="$can('create', 'Mutasi Stok')"
        color="primary"
        prepend-icon="ri-arrow-left-right-line"
        @click="isAddNewDrawerVisible = true"
      >
        Buat Pengajuan Mutasi
      </VBtn>
    </div>

    <!-- Main Card -->
    <VCard elevation="2">
      <!-- Tabs Navigation -->
      <VTabs
        v-model="activeTab"
        class="px-4 border-b"
        @update:model-value="() => { page = 1; fetchData(); }"
      >
        <VTab value="all">
          <VIcon icon="ri-list-check-2" class="me-2" />
          Semua Mutasi
        </VTab>
        <VTab value="pending">
          <VIcon icon="ri-time-line" class="me-2 text-warning" />
          <span>1. Menunggu Respon</span>
          <VBadge
            v-if="countPending > 0"
            color="warning"
            :content="countPending"
            inline
            class="ms-2"
          />
        </VTab>
        <VTab value="ready_for_pickup">
          <VIcon icon="ri-truck-line" class="me-2 text-info" />
          <span>2. Siap Dijemput</span>
          <VBadge
            v-if="countReadyForPickup > 0"
            color="info"
            :content="countReadyForPickup"
            inline
            class="ms-2"
          />
        </VTab>
        <VTab value="completed">
          <VIcon icon="ri-checkbox-circle-line" class="me-2 text-success" />
          3. Selesai (Sudah Dijemput)
        </VTab>
        <VTab value="rejected_cancelled">
          <VIcon icon="ri-close-circle-line" class="me-2 text-error" />
          Ditolak / Batal
        </VTab>
      </VTabs>

      <!-- Filter Controls -->
      <VCardText class="d-flex flex-wrap align-center py-4 gap-4">
        <VTextField
          v-model="search"
          placeholder="Cari No Referensi..."
          density="compact"
          prepend-inner-icon="ri-search-line"
          style="max-width: 320px;"
          hide-details
          clearable
          @update:model-value="handleSearch"
        />

        <VAutocomplete
          v-model="selectedSourceBranch"
          :items="branches"
          item-title="name"
          item-value="id"
          placeholder="Filter Cabang Asal"
          density="compact"
          style="max-width: 240px;"
          hide-details
          clearable
          @update:model-value="() => { page = 1; fetchData(); }"
        />

        <VAutocomplete
          v-model="selectedDestinationBranch"
          :items="branches"
          item-title="name"
          item-value="id"
          placeholder="Filter Cabang Tujuan"
          density="compact"
          style="max-width: 240px;"
          hide-details
          clearable
          @update:model-value="() => { page = 1; fetchData(); }"
        />
        
        <VSpacer />

        <VBtn
          variant="tonal"
          color="secondary"
          size="small"
          prepend-icon="ri-refresh-line"
          :loading="isLoading"
          @click="fetchData"
        >
          Muat Ulang
        </VBtn>
      </VCardText>

      <VDivider />

      <!-- Data Table -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="tableHeaders"
        :items="mutasiList"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        @update:options="fetchData"
      >
        <template #item.reference_no="{ item }">
          <a
            href="#"
            class="font-weight-bold text-primary text-decoration-none"
            @click.prevent="openTrackingDialog(item)"
          >
            {{ item.reference_no }}
          </a>
        </template>
        
        <template #item.created_at="{ item }">
          <span>{{ formatDateTime(item.created_at) }}</span>
        </template>
        
        <template #item.source_branch.name="{ item }">
          <div class="d-flex align-center gap-2">
            <VIcon icon="ri-store-2-line" size="small" class="text-error" />
            <span class="font-weight-medium">{{ item.source_branch?.name || '-' }}</span>
          </div>
        </template>

        <template #item.destination_branch.name="{ item }">
          <div class="d-flex align-center gap-2">
            <VIcon icon="ri-store-3-line" size="small" class="text-success" />
            <span class="font-weight-medium">{{ item.destination_branch?.name || '-' }}</span>
          </div>
        </template>

        <template #item.picked_up_by_name="{ item }">
          <div v-if="item.picked_up_by_name" class="d-flex align-center gap-1">
            <VIcon icon="ri-user-follow-line" size="14" class="text-success" />
            <span class="font-weight-medium text-success text-caption">{{ item.picked_up_by_name }}</span>
          </div>
          <span v-else class="text-caption text-disabled">-</span>
        </template>

        <template #item.status="{ item }">
          <VChip
            :color="getStatusBadge(item.status).color"
            size="small"
            variant="tonal"
            class="font-weight-medium"
          >
            <VIcon
              :icon="getStatusBadge(item.status).icon"
              size="14"
              class="me-1"
            />
            {{ getStatusBadge(item.status).text }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex align-center justify-center gap-1">
            <VBtn
              size="small"
              variant="tonal"
              color="primary"
              prepend-icon="ri-eye-line"
              @click="openTrackingDialog(item)"
            >
              Detail
            </VBtn>

            <VBtn
              size="small"
              variant="outlined"
              color="info"
              prepend-icon="ri-printer-line"
              @click="openDeliveryNoteDialog(item)"
            >
              Surat Jalan
            </VBtn>

            <VBtn
              v-if="item.status === 'pending' && ($can('approve', 'Mutasi Stok') || $can('validate', 'Mutasi Stok'))"
              size="small"
              variant="elevated"
              color="primary"
              prepend-icon="ri-box-3-line"
              @click="openPrepareDialog(item)"
            >
              Siapkan
            </VBtn>

            <VBtn
              v-if="['ready_for_pickup', 'approved'].includes(item.status)"
              size="small"
              variant="elevated"
              color="success"
              prepend-icon="ri-truck-line"
              @click="openPickupDialog(item)"
            >
              Jemput
            </VBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Create Mutasi Drawer -->
    <AddNewMutasiDrawer
      v-model:is-drawer-open="isAddNewDrawerVisible"
      :branches="branches"
      @save-data="saveMutasi"
    />

    <!-- Verification & Item Checklist Dialog (Tahap 2 Penyiapan Unit Asal) -->
    <VDialog
      v-model="isPrepareDialogVisible"
      max-width="900"
      persistent
    >
      <VCard v-if="prepareMutasi">
        <VCardTitle class="d-flex justify-space-between align-center px-6 pt-6 pb-3 bg-light">
          <div>
            <div class="d-flex align-center gap-2 mb-1">
              <VIcon icon="ri-checkbox-multiple-line" color="primary" size="24" />
              <span class="text-h5 font-weight-bold">Verifikasi & Penyiapan Barang</span>
            </div>
            <p class="text-caption text-medium-emphasis mb-0">
              No. Referensi: <strong>{{ prepareMutasi.reference_no }}</strong> | Cabang Asal: <strong>{{ prepareMutasi.source_branch?.name }}</strong>
            </p>
          </div>

          <VBtn
            icon
            variant="text"
            size="small"
            @click="isPrepareDialogVisible = false"
          >
            <VIcon icon="ri-close-line" />
          </VBtn>
        </VCardTitle>

        <VDivider />

        <VCardText class="px-6 py-4" style="max-height: 70vh; overflow-y: auto;">
          <!-- Instructions Alert -->
          <VAlert
            v-if="totalAvailableItemsCount > 0"
            color="primary"
            variant="tonal"
            density="compact"
            icon="ri-information-line"
            class="mb-4"
          >
            Ceklis barang yang tersedia di unit asal dan tentukan jumlah (Qty) yang akan dikirim. Barang yang <strong>tidak diceklis</strong> akan otomatis dibatalkan dengan alasan barang kosong.
          </VAlert>
          <VAlert
            v-else
            color="error"
            variant="tonal"
            density="compact"
            icon="ri-alert-line"
            class="mb-4"
          >
            <strong>Perhatian:</strong> Semua barang tidak diceklis (kosong). Menyimpan proses ini akan otomatis <strong>menolak / membatalkan</strong> permintaan mutasi stok ini.
          </VAlert>

          <!-- Items Table with Checkboxes -->
          <div class="border rounded overflow-hidden">
            <VTable density="compact">
              <thead>
                <tr class="bg-grey-100">
                  <th style="width: 140px;" class="font-weight-bold">Status / Ceklis</th>
                  <th class="font-weight-bold">Nama Barang & SKU</th>
                  <th class="text-center font-weight-bold" style="width: 110px;">Diminta</th>
                  <th class="text-center font-weight-bold" style="width: 130px;">Stok Asal</th>
                  <th class="text-center font-weight-bold" style="width: 140px;">Qty Dikirim</th>
                  <th class="font-weight-bold" style="width: 220px;">Keterangan / Alasan</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(item, idx) in prepareItems"
                  :key="item.id"
                  :class="{'bg-red-lighten-5': !item.is_available}"
                >
                  <!-- Checkbox -->
                  <td>
                    <VCheckbox
                      v-model="item.is_available"
                      :label="item.is_available ? 'Ada (Kirim)' : 'Kosong'"
                      color="primary"
                      density="compact"
                      hide-details
                      @update:model-value="handleCheckboxChange(item)"
                    />
                  </td>

                  <!-- Product Info -->
                  <td>
                    <div class="font-weight-medium text-subtitle-2">{{ item.product?.name }}</div>
                    <div class="text-caption text-disabled"><code>{{ item.product?.sku || '-' }}</code></div>
                  </td>

                  <!-- Requested Qty -->
                  <td class="text-center font-weight-bold text-primary">
                    {{ item.qty_requested }}
                  </td>

                  <!-- Source Stock -->
                  <td class="text-center">
                    <VChip
                      size="x-small"
                      :color="item.source_current_stock >= item.qty_requested ? 'success' : (item.source_current_stock > 0 ? 'warning' : 'error')"
                      variant="tonal"
                    >
                      Sisa: {{ item.source_current_stock }}
                    </VChip>
                  </td>

                  <!-- Prepared Qty Input -->
                  <td>
                    <VTextField
                      v-model.number="item.qty_prepared"
                      type="number"
                      density="compact"
                      min="1"
                      :max="item.source_current_stock"
                      :disabled="!item.is_available"
                      hide-details
                      style="width: 110px; margin: 0 auto;"
                    />
                  </td>

                  <!-- Cancel reason or status text -->
                  <td>
                    <div v-if="!item.is_available">
                      <VTextField
                        v-model="item.cancel_reason"
                        placeholder="Alasan barang kosong..."
                        density="compact"
                        hide-details
                        class="text-error"
                      />
                    </div>
                    <div v-else>
                      <VChip
                        v-if="item.qty_prepared < item.qty_requested"
                        size="x-small"
                        color="warning"
                        variant="tonal"
                      >
                        Parsial ({{ item.qty_prepared }}/{{ item.qty_requested }})
                      </VChip>
                      <VChip
                        v-else
                        size="x-small"
                        color="success"
                        variant="tonal"
                      >
                        Lengkap ({{ item.qty_prepared }})
                      </VChip>
                    </div>
                  </td>
                </tr>
              </tbody>
            </VTable>
          </div>
        </VCardText>

        <VDivider />

        <VCardActions class="px-6 py-4 justify-space-between bg-grey-50">
          <VBtn
            variant="outlined"
            color="secondary"
            :disabled="isSubmittingPrepare"
            @click="isPrepareDialogVisible = false"
          >
            Batal
          </VBtn>

          <VBtn
            color="primary"
            variant="elevated"
            prepend-icon="ri-check-line"
            :loading="isSubmittingPrepare"
            @click="submitPrepare"
          >
            {{ totalAvailableItemsCount > 0 ? 'Simpan & Siapkan Barang' : 'Batalkan Mutasi (Semua Kosong)' }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Reject Reason Dialog -->
    <VDialog
      v-model="isRejectDialogVisible"
      max-width="500"
    >
      <VCard>
        <VCardTitle class="px-6 pt-6 pb-2">
          <span class="text-h5 font-weight-bold text-error">Tolak Permintaan Mutasi</span>
        </VCardTitle>
        <VCardText class="px-6 py-4">
          <p class="text-body-2 mb-4">
            Apakah Anda yakin ingin menolak permintaan mutasi ini? Anda dapat menyertakan alasan penolakan di bawah:
          </p>
          <VTextarea
            v-model="rejectReason"
            label="Alasan Penolakan (Opsional)"
            placeholder="Contoh: Stok sedang dialokasikan untuk order lain, unit asal tutup..."
            rows="3"
          />
        </VCardText>
        <VDivider />
        <VCardActions class="px-6 py-4 justify-end gap-2 bg-grey-50">
          <VBtn
            variant="outlined"
            color="secondary"
            :disabled="isSubmittingReject"
            @click="isRejectDialogVisible = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="error"
            variant="elevated"
            :loading="isSubmittingReject"
            @click="submitReject"
          >
            Ya, Tolak Permintaan
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Tracking & Workflow Stepper Dialog -->
    <VDialog
      v-model="isTrackingDialogVisible"
      max-width="850"
    >
      <VCard v-if="trackingMutasi" class="overflow-hidden">
        <VCardTitle class="d-flex justify-space-between align-center px-6 pt-6 pb-4 bg-light">
          <div>
            <div class="d-flex align-center gap-2 mb-1">
              <span class="text-h5 font-weight-bold">Mutasi: {{ trackingMutasi.reference_no }}</span>
              <VChip
                :color="getStatusBadge(trackingMutasi.status).color"
                size="small"
                variant="elevated"
              >
                {{ getStatusBadge(trackingMutasi.status).text }}
              </VChip>
            </div>
            <span class="text-caption text-medium-emphasis">
              Diajukan pada {{ formatDateTime(trackingMutasi.created_at) }}
            </span>
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

        <VCardText class="px-6 py-6" style="max-height: 72vh; overflow-y: auto;">
          <!-- Visual 3-Step Timeline -->
          <div class="mb-6 pa-4 bg-var-theme-background rounded-lg border">
            <h6 class="text-subtitle-2 font-weight-bold mb-4 d-flex align-center gap-2">
              <VIcon icon="ri-node-tree" size="20" color="primary" />
              Progress Alur Mutasi Barang
            </h6>
            
            <VRow>
              <!-- Step 1 -->
              <VCol cols="12" md="4">
                <VCard
                  variant="outlined"
                  class="pa-3 h-100"
                  :class="{
                    'border-primary bg-primary-lighten-5': trackingMutasi.status === 'pending',
                    'border-success bg-success-lighten-5': ['ready_for_pickup', 'completed', 'approved'].includes(trackingMutasi.status),
                    'border-error bg-error-lighten-5': ['rejected', 'cancelled'].includes(trackingMutasi.status),
                  }"
                >
                  <div class="d-flex align-center gap-2 mb-2">
                    <VAvatar
                      size="28"
                      :color="['ready_for_pickup', 'completed', 'approved'].includes(trackingMutasi.status) ? 'success' : 'primary'"
                    >
                      <VIcon icon="ri-file-add-line" size="16" color="white" />
                    </VAvatar>
                    <span class="font-weight-bold text-subtitle-2">1. Pengajuan Request</span>
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    <div>Pemohon: <strong>{{ trackingMutasi.created_by?.name || '-' }}</strong></div>
                    <div>Waktu: {{ formatDateTime(trackingMutasi.created_at) }}</div>
                  </div>
                  <VChip size="x-small" color="success" class="mt-2">
                    Request Terkirim
                  </VChip>
                </VCard>
              </VCol>

              <!-- Step 2 -->
              <VCol cols="12" md="4">
                <VCard
                  variant="outlined"
                  class="pa-3 h-100"
                  :class="{
                    'border-primary bg-primary-lighten-5': trackingMutasi.status === 'ready_for_pickup',
                    'border-success bg-success-lighten-5': trackingMutasi.status === 'completed',
                    'opacity-60': trackingMutasi.status === 'pending',
                  }"
                >
                  <div class="d-flex align-center gap-2 mb-2">
                    <VAvatar
                      size="28"
                      :color="['ready_for_pickup', 'completed', 'approved'].includes(trackingMutasi.status) ? 'success' : 'grey'"
                    >
                      <VIcon icon="ri-box-3-line" size="16" color="white" />
                    </VAvatar>
                    <span class="font-weight-bold text-subtitle-2">2. Disiapkan Unit Asal</span>
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    <div v-if="['ready_for_pickup', 'completed', 'approved'].includes(trackingMutasi.status)">
                      <div>Petugas: <strong>{{ trackingMutasi.prepared_by?.name || trackingMutasi.approved_by?.name || 'Unit Asal' }}</strong></div>
                      <div>Waktu: {{ formatDateTime(trackingMutasi.prepared_at || trackingMutasi.updated_at) }}</div>
                    </div>
                    <div v-else class="text-warning">
                      Menunggu verifikasi & penyiapan stok oleh unit asal.
                    </div>
                  </div>
                  <VChip
                    v-if="['ready_for_pickup', 'completed', 'approved'].includes(trackingMutasi.status)"
                    size="x-small"
                    color="info"
                    class="mt-2"
                  >
                    Stok Asal Terpotong & Siap Dijemput
                  </VChip>
                </VCard>
              </VCol>

              <!-- Step 3 -->
              <VCol cols="12" md="4">
                <VCard
                  variant="outlined"
                  class="pa-3 h-100"
                  :class="{
                    'border-success bg-success-lighten-5': trackingMutasi.status === 'completed',
                    'opacity-60': trackingMutasi.status !== 'completed',
                  }"
                >
                  <div class="d-flex align-center gap-2 mb-2">
                    <VAvatar
                      size="28"
                      :color="trackingMutasi.status === 'completed' ? 'success' : 'grey'"
                    >
                      <VIcon icon="ri-checkbox-circle-line" size="16" color="white" />
                    </VAvatar>
                    <span class="font-weight-bold text-subtitle-2">3. Dijemput Cabang</span>
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    <div v-if="trackingMutasi.status === 'completed'">
                      <div v-if="trackingMutasi.picked_up_by_name" class="text-success font-weight-bold mb-1">
                        <VIcon icon="ri-user-follow-line" size="14" class="me-1" />
                        Penjemput: {{ trackingMutasi.picked_up_by_name }}
                      </div>
                      <div>Penerima (Akun): <strong>{{ trackingMutasi.received_by?.name || 'Cabang Pemohon' }}</strong></div>
                      <div>Waktu: {{ formatDateTime(trackingMutasi.received_at || trackingMutasi.updated_at) }}</div>
                      <div v-if="trackingMutasi.pickup_notes" class="mt-1">
                        <i>Catatan: "{{ trackingMutasi.pickup_notes }}"</i>
                      </div>
                    </div>
                    <div v-else-if="['ready_for_pickup', 'approved'].includes(trackingMutasi.status)" class="text-info font-weight-medium">
                      Barang siap dijemput oleh pihak cabang pemohon.
                    </div>
                    <div v-else>
                      Belum dijemput.
                    </div>
                  </div>
                  <VChip
                    v-if="trackingMutasi.status === 'completed'"
                    size="x-small"
                    color="success"
                    class="mt-2"
                  >
                    Selesai & Masuk Stok Cabang
                  </VChip>
                </VCard>
              </VCol>
            </VRow>
          </div>

          <!-- Unit Asal & Tujuan Information -->
          <VRow class="mb-4">
            <VCol cols="12" md="5">
              <VCard variant="tonal" color="error" class="pa-4">
                <div class="text-caption text-error font-weight-bold mb-1">
                  <VIcon icon="ri-upload-2-line" size="14" class="me-1" />
                  CABANG / GUDANG ASAL (SUMBER STOK)
                </div>
                <div class="text-h6 font-weight-bold">{{ trackingMutasi.source_branch?.name }}</div>
                <div class="text-caption">{{ trackingMutasi.source_branch?.address || 'Alamat tidak tersedia' }}</div>
              </VCard>
            </VCol>

            <VCol cols="12" md="2" class="d-flex align-center justify-center">
              <VIcon icon="ri-arrow-right-line" size="32" color="primary" />
            </VCol>

            <VCol cols="12" md="5">
              <VCard variant="tonal" color="success" class="pa-4">
                <div class="text-caption text-success font-weight-bold mb-1">
                  <VIcon icon="ri-download-2-line" size="14" class="me-1" />
                  CABANG PEMOHON (TUJUAN PENERIMAAN)
                </div>
                <div class="text-h6 font-weight-bold">{{ trackingMutasi.destination_branch?.name }}</div>
                <div class="text-caption">{{ trackingMutasi.destination_branch?.address || 'Alamat tidak tersedia' }}</div>
              </VCard>
            </VCol>
          </VRow>
          
          <!-- Catatan -->
          <div v-if="trackingMutasi.notes" class="mb-6 pa-3 bg-grey-50 rounded border">
            <div class="text-caption font-weight-bold text-medium-emphasis mb-1">
              Catatan / Instruksi Permintaan:
            </div>
            <p class="text-body-2 mb-0">{{ trackingMutasi.notes }}</p>
          </div>

          <!-- Item List Table with Fulfillment breakdown -->
          <div>
            <h6 class="text-subtitle-1 font-weight-bold mb-3 d-flex align-center gap-2">
              <VIcon icon="ri-box-3-line" size="20" color="primary" />
              Daftar Barang Mutasi & Status Pemenuhan
            </h6>
            <div v-if="trackingMutasi.items && trackingMutasi.items.length" class="border rounded overflow-hidden">
              <VTable density="compact">
                <thead>
                  <tr class="bg-grey-100">
                    <th class="font-weight-bold">SKU</th>
                    <th class="font-weight-bold">Nama Barang</th>
                    <th class="text-center font-weight-bold">Qty Diminta</th>
                    <th class="text-center font-weight-bold">Qty Disiapkan</th>
                    <th class="font-weight-bold">Status Pemenuhan Item</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="i in trackingMutasi.items"
                    :key="i.id"
                    :class="{'bg-red-lighten-5': i.status === 'cancelled'}"
                  >
                    <td><code>{{ i.product?.sku || '-' }}</code></td>
                    <td class="font-weight-medium">{{ i.product?.name || 'Item' }}</td>
                    <td class="text-center font-weight-bold">{{ i.qty }}</td>
                    <td class="text-center font-weight-bold">
                      <span v-if="i.status === 'cancelled'" class="text-error font-weight-bold">0</span>
                      <span v-else class="text-primary font-weight-bold">{{ i.qty_prepared ?? i.qty }}</span>
                    </td>
                    <td>
                      <!-- Cancelled / Out of stock item -->
                      <div v-if="i.status === 'cancelled'">
                        <VChip size="x-small" color="error" variant="tonal">
                          <VIcon icon="ri-close-circle-line" size="12" class="me-1" />
                          Dibatalkan: {{ i.cancel_reason || 'Barang Kosong' }}
                        </VChip>
                      </div>

                      <!-- Prepared Item -->
                      <div v-else-if="['ready_for_pickup', 'completed', 'approved'].includes(trackingMutasi.status) || i.status === 'prepared'">
                        <VChip
                          v-if="i.qty_prepared !== null && i.qty_prepared < i.qty"
                          size="x-small"
                          color="warning"
                          variant="tonal"
                        >
                          Disiapkan Parsial ({{ i.qty_prepared }} dari {{ i.qty }})
                        </VChip>
                        <VChip
                          v-else
                          size="x-small"
                          color="success"
                          variant="tonal"
                        >
                          Disiapkan Lengkap ({{ i.qty_prepared ?? i.qty }})
                        </VChip>
                      </div>

                      <!-- Pending verification -->
                      <div v-else>
                        <VChip size="x-small" color="warning" variant="tonal">
                          Menunggu Verifikasi Unit Asal
                        </VChip>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </VTable>
            </div>
            <div v-else class="text-caption text-disabled">Tidak ada data barang.</div>
          </div>
        </VCardText>

        <VDivider />

        <!-- Actions -->
        <VCardActions class="px-6 py-4 justify-space-between bg-grey-50">
          <div>
            <VBtn
              variant="outlined"
              color="secondary"
              @click="isTrackingDialogVisible = false"
            >
              Tutup
            </VBtn>
          </div>

          <div class="d-flex gap-2">
            <!-- Tahap 1: Pending Actions (Pusat / Unit Asal merespon) -->
            <template v-if="trackingMutasi.status === 'pending'">
              <VBtn
                color="error"
                variant="tonal"
                prepend-icon="ri-close-line"
                @click="openRejectDialog(trackingMutasi)"
              >
                Tolak Permintaan
              </VBtn>

              <VBtn
                color="primary"
                variant="elevated"
                prepend-icon="ri-checkbox-multiple-line"
                @click="openPrepareDialog(trackingMutasi)"
              >
                Verifikasi & Siapkan Barang
              </VBtn>
            </template>

            <!-- Tahap 2: Ready for Pickup Actions (Cabang menjemput / batal) -->
            <template v-else-if="['ready_for_pickup', 'approved'].includes(trackingMutasi.status)">
              <VBtn
                color="error"
                variant="tonal"
                prepend-icon="ri-forbid-2-line"
                @click="handleActionConfirm(trackingMutasi.id, 'cancel')"
              >
                Batalkan Mutasi
              </VBtn>

              <VBtn
                color="success"
                variant="elevated"
                prepend-icon="ri-truck-line"
                @click="openPickupDialog(trackingMutasi)"
              >
                Konfirmasi Barang Telah Dijemput
              </VBtn>
            </template>

            <!-- Completed / Closed -->
            <template v-else>
              <span class="text-caption text-medium-emphasis align-self-center font-italic">
                Mutasi ini telah selesai ({{ trackingMutasi.status }}).
              </span>
            </template>
          </div>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Pickup Dialog (Tahap 3 Konfirmasi Penjemputan Barang) -->
    <VDialog
      v-model="isPickupDialogVisible"
      max-width="600"
      persistent
    >
      <VCard v-if="pickupMutasi">
        <VCardTitle class="d-flex justify-space-between align-center px-6 pt-6 pb-3 bg-success-lighten-5">
          <div class="d-flex align-center gap-2">
            <VAvatar color="success" size="36">
              <VIcon icon="ri-truck-line" color="white" size="20" />
            </VAvatar>
            <div>
              <span class="text-h6 font-weight-bold text-success">Konfirmasi Penjemputan Barang</span>
              <p class="text-caption text-medium-emphasis mb-0">
                No. Referensi: <strong>{{ pickupMutasi.reference_no }}</strong>
              </p>
            </div>
          </div>
          <VBtn icon variant="text" size="small" @click="isPickupDialogVisible = false">
            <VIcon icon="ri-close-line" />
          </VBtn>
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-6">
          <VAlert color="info" variant="tonal" class="mb-4" density="compact">
            <div class="text-caption">
              Barang akan dijemput dari <b>{{ pickupMutasi.source_branch?.name }}</b> menuju <b>{{ pickupMutasi.destination_branch?.name }}</b>. Stok akan otomatis bertambah di cabang tujuan setelah penjemputan dikonfirmasi.
            </div>
          </VAlert>

          <VForm @submit.prevent="submitPickup">
            <div class="mb-4">
              <label class="text-subtitle-2 font-weight-bold mb-1 d-block">
                Nama Karyawan yang Menjemput <span class="text-error">*</span>
              </label>
              <VCombobox
                v-model="pickupEmployeeName"
                :items="employees"
                item-title="name"
                item-value="name"
                placeholder="Pilih atau ketik nama karyawan penjemput..."
                density="compact"
                clearable
                prepend-inner-icon="ri-user-follow-line"
                :rules="[v => !!v || 'Nama karyawan yang menjemput wajib diisi']"
              />
              <span class="text-caption text-medium-emphasis">
                Pilih dari daftar karyawan atau ketik nama driver/kurir/staf penjemput.
              </span>
            </div>

            <div class="mb-2">
              <label class="text-subtitle-2 font-weight-bold mb-1 d-block">
                Catatan Penjemputan (Opsional)
              </label>
              <VTextarea
                v-model="pickupNotes"
                placeholder="Contoh: Dijemput menggunakan kendaraan operasional, kondisi barang lengkap dan tersegel baik..."
                rows="2"
                density="compact"
                hide-details
              />
            </div>
          </VForm>
        </VCardText>

        <VDivider />

        <VCardActions class="px-6 py-4 justify-space-between bg-grey-50">
          <VBtn variant="outlined" color="secondary" @click="isPickupDialogVisible = false">
            Batal
          </VBtn>
          <VBtn
            color="success"
            variant="elevated"
            prepend-icon="ri-checkbox-circle-line"
            :loading="isSubmittingPickup"
            @click="submitPickup"
          >
            Konfirmasi Barang Dijemput & Terima Stok
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ================= SURAT JALAN / DELIVERY NOTE DIALOG ================= -->
    <VDialog
      v-model="isDeliveryNoteDialogVisible"
      max-width="850"
    >
      <VCard v-if="deliveryNoteData">
        <VCardTitle class="bg-primary text-white pa-4 d-flex align-center justify-space-between d-print-none">
          <div class="d-flex align-center gap-2">
            <VIcon icon="ri-file-paper-2-line" />
            <span class="font-weight-bold">Dokumen Surat Jalan Mutasi Antar Cabang</span>
          </div>
          <div class="d-flex align-center gap-2">
            <VBtn
              color="white"
              variant="elevated"
              size="small"
              class="text-primary font-weight-bold"
              prepend-icon="ri-printer-line"
              @click="printDeliveryNote"
            >
              Cetak Dokumen (Print)
            </VBtn>
            <VBtn icon="ri-close-line" variant="text" size="small" @click="isDeliveryNoteDialogVisible = false" />
          </div>
        </VCardTitle>

        <!-- Printable Document Body -->
        <VCardText class="pa-6 printable-area bg-white text-black" id="printable-surat-jalan">
          <!-- Document Header -->
          <div class="border-b-2 pb-4 mb-4">
            <div class="d-flex justify-space-between align-center">
              <div>
                <h3 class="text-h4 font-weight-bold mb-1 text-uppercase text-primary">
                  SURAT JALAN MUTASI BARANG
                </h3>
                <p class="text-subtitle-2 text-medium-emphasis mb-0">
                  PT. DUMAI MANAJEMEN SISTEM INVENTORI & LOGISTIK
                </p>
              </div>
              <div class="text-right">
                <div class="text-caption text-medium-emphasis">NO. DOKUMEN:</div>
                <div class="text-h6 font-weight-bold">{{ deliveryNoteData.reference_no }}</div>
                <div class="text-caption font-weight-medium">Tgl: {{ formatDateTime(deliveryNoteData.created_at) }}</div>
              </div>
            </div>
          </div>

          <!-- Cabang Asal vs Cabang Tujuan Grid -->
          <VRow class="mb-4 g-3">
            <VCol cols="6">
              <div class="pa-3 border rounded bg-grey-50">
                <div class="text-caption font-weight-bold text-uppercase text-medium-emphasis mb-1">
                  PENGIRIM (CABANG ASAL):
                </div>
                <div class="font-weight-bold text-body-1">{{ deliveryNoteData.source_branch?.name || '-' }}</div>
                <div class="text-caption text-medium-emphasis">{{ deliveryNoteData.source_branch?.address || 'Alamat Cabang Asal' }}</div>
                <div class="text-caption mt-1">Dibuat Oleh: <strong>{{ deliveryNoteData.user?.name || '-' }}</strong></div>
              </div>
            </VCol>
            <VCol cols="6">
              <div class="pa-3 border rounded bg-grey-50">
                <div class="text-caption font-weight-bold text-uppercase text-medium-emphasis mb-1">
                  PENERIMA (CABANG TUJUAN):
                </div>
                <div class="font-weight-bold text-body-1 text-primary">{{ deliveryNoteData.destination_branch?.name || '-' }}</div>
                <div class="text-caption text-medium-emphasis">{{ deliveryNoteData.destination_branch?.address || 'Alamat Cabang Tujuan' }}</div>
                <div class="text-caption mt-1">Kurir / Penjemput: <strong>{{ deliveryNoteData.pickup_employee_name || 'Kurir Operasional' }}</strong></div>
              </div>
            </VCol>
          </VRow>

          <!-- Items Table -->
          <div class="border rounded mb-4 overflow-hidden">
            <table class="w-100" style="border-collapse: collapse; width: 100%;">
              <thead>
                <tr style="background-color: #f3f4f6; border-bottom: 2px solid #e5e7eb;">
                  <th style="padding: 10px; text-align: center; width: 40px; font-size: 12px;">NO</th>
                  <th style="padding: 10px; text-align: left; font-size: 12px;">KODE / SKU</th>
                  <th style="padding: 10px; text-align: left; font-size: 12px;">NAMA BARANG</th>
                  <th style="padding: 10px; text-align: center; width: 100px; font-size: 12px;">QTY MINTA</th>
                  <th style="padding: 10px; text-align: center; width: 100px; font-size: 12px;">QTY KIRIM</th>
                  <th style="padding: 10px; text-align: left; font-size: 12px;">STATUS / BATCH</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(it, idx) in (deliveryNoteData.items || [])"
                  :key="idx"
                  style="border-bottom: 1px solid #e5e7eb;"
                >
                  <td style="padding: 8px 10px; text-align: center; font-size: 12px;">{{ idx + 1 }}</td>
                  <td style="padding: 8px 10px; font-size: 12px; font-weight: bold;">{{ it.product?.sku || '-' }}</td>
                  <td style="padding: 8px 10px; font-size: 12px;">{{ it.product?.name || '-' }}</td>
                  <td style="padding: 8px 10px; text-align: center; font-size: 12px;">{{ it.qty }}</td>
                  <td style="padding: 8px 10px; text-align: center; font-size: 12px; font-weight: bold; color: #16a34a;">
                    {{ it.qty_prepared ?? it.qty }}
                  </td>
                  <td style="padding: 8px 10px; font-size: 11px;">
                    <span v-if="it.status === 'prepared'" class="text-success font-weight-bold">Siap Kirim</span>
                    <span v-else-if="it.status === 'received'" class="text-primary font-weight-bold">Diterima</span>
                    <span v-else>{{ it.status }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Notes -->
          <div v-if="deliveryNoteData.notes || deliveryNoteData.pickup_notes" class="pa-3 mb-6 border rounded bg-grey-50 text-caption">
            <div v-if="deliveryNoteData.notes"><strong>Catatan Mutasi:</strong> {{ deliveryNoteData.notes }}</div>
            <div v-if="deliveryNoteData.pickup_notes" class="mt-1"><strong>Catatan Penjemputan:</strong> {{ deliveryNoteData.pickup_notes }}</div>
          </div>

          <!-- 3-Party Signature Block -->
          <div class="mt-8 pt-4">
            <VRow class="text-center">
              <VCol cols="4">
                <div class="text-caption font-weight-bold mb-12">PENGIRIM (CABANG ASAL)</div>
                <div class="border-t mx-6 pt-1 font-weight-bold text-body-2">
                  ( {{ deliveryNoteData.prepared_by?.name || deliveryNoteData.created_by?.name || 'Petugas Gudang' }} )
                </div>
              </VCol>
              <VCol cols="4">
                <div class="text-caption font-weight-bold mb-12">KURIR / PENGANTAR</div>
                <div class="border-t mx-6 pt-1 font-weight-bold text-body-2">
                  ( {{ deliveryNoteData.picked_up_by_name || 'Driver / Kurir' }} )
                </div>
              </VCol>
              <VCol cols="4">
                <div class="text-caption font-weight-bold mb-12">PENERIMA (CABANG TUJUAN)</div>
                <div class="border-t mx-6 pt-1 font-weight-bold text-body-2">
                  ( {{ deliveryNoteData.received_by?.name || '........................................' }} )
                </div>
              </VCol>
            </VRow>
          </div>
        </VCardText>

        <VCardActions class="pa-4 bg-grey-50 justify-end d-print-none">
          <VBtn variant="tonal" color="secondary" @click="isDeliveryNoteDialogVisible = false">
            Tutup
          </VBtn>
          <VBtn color="primary" prepend-icon="ri-printer-line" class="font-weight-bold" @click="printDeliveryNote">
            Cetak Surat Jalan
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Confirm Dialog -->
    <SimpleConfirmDialog
      v-model:is-dialog-visible="isConfirmDialogVisible"
      :title="confirmTitle"
      :message="confirmMessage"
      :confirm-text="confirmActionText"
      :color="confirmColor"
      @confirm="onConfirmAction"
    />
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Mutasi Stok
</route>
