<script setup>
import { ref, onMounted, computed } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import AddNewMutasiDrawer from './AddNewMutasiDrawer.vue'
import SimpleConfirmDialog from '@/components/dialogs/SimpleConfirmDialog.vue'
import QrcodeVue from 'qrcode.vue'

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

// Prepare / Verification Dialog State (Tahap 2 Penyiapan Unit Asal)
const isPrepareDialogVisible = ref(false)
const prepareMutasi = ref(null)
const prepareItems = ref([])
const isSubmittingPrepare = ref(false)

// Pickup Dialog State (Tahap 3 Validasi Penjemputan Kurir)
const isPickupDialogVisible = ref(false)
const pickupMutasi = ref(null)
const pickupItems = ref([])
const pickupEmployeeName = ref('')
const pickupCourierType = ref('internal_courier')
const pickupNotes = ref('')
const pickupPhotoFile = ref(null)
const pickupPhotoPreview = ref('')
const isSubmittingPickup = ref(false)

// Receive Dialog State (Tahap 4 Validasi Penerimaan Toko Tujuan)
const isReceiveDialogVisible = ref(false)
const receiveMutasi = ref(null)
const receiveItems = ref([])
const receiveNotes = ref('')
const receivedPhotoFile = ref(null)
const receivedPhotoPreview = ref('')
const isSubmittingReceive = ref(false)

// Reject Dialog State
const isRejectDialogVisible = ref(false)
const rejectMutasiId = ref(null)
const rejectReason = ref('')
const isSubmittingReject = ref(false)

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
    } else if (activeTab.value === 'in_transit') {
      params.status = 'in_transit'
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
  { title: 'KURIR / PENJEMPUT', key: 'picked_up_by_name' },
  { title: 'STATUS TAHAPAN', key: 'status' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const statusCounts = ref({
  total: 0,
  pending: 0,
  ready_for_pickup: 0,
  in_transit: 0,
  completed: 0,
  rejected_cancelled: 0,
})

const countPending = computed(() => statusCounts.value.pending)
const countReadyForPickup = computed(() => statusCounts.value.ready_for_pickup)
const countInTransit = computed(() => statusCounts.value.in_transit)
const countCompleted = computed(() => statusCounts.value.completed)

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
        in_transit: res.in_transit ?? 0,
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

const onRowClick = (event, { item }) => {
  if (event.target.closest('button') || event.target.closest('a') || event.target.closest('.v-btn')) {
    return
  }
  openTrackingDialog(item)
}

// Open Prepare / Checklist Dialog (Tahap 2)
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
  for (const item of prepareItems.value) {
    if (item.is_available) {
      if (item.qty_prepared <= 0) {
        snackbar.show(`Barang ${item.product?.name} diceklis ada, namun jumlah kirim masih 0.`, 'warning')
        return
      }
      if (item.qty_prepared > item.source_current_stock) {
        snackbar.show(`Jumlah kirim untuk ${item.product?.name} (${item.qty_prepared}) melebihi sisa stok asal (${item.source_current_stock})!`, 'error')
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

    snackbar.show(res.message || 'Barang berhasil disiapkan dan stok asal telah dipotong', 'success')
    window.dispatchEvent(new Event('refresh-notifications'))
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
    window.dispatchEvent(new Event('refresh-notifications'))
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

// Pickup Dialog Handlers (Tahap 3 Penjemputan Kurir)
const openPickupDialog = async mutasi => {
  try {
    const res = await $api(`/apps/stock-transfers/${mutasi.id}`)
    pickupMutasi.value = res
    pickupEmployeeName.value = res.picked_up_by_name || ''
    pickupCourierType.value = res.pickup_courier_type || 'internal_courier'
    pickupNotes.value = res.pickup_notes || ''
    pickupPhotoFile.value = null
    pickupPhotoPreview.value = ''
    
    // Per item pickup checklist
    pickupItems.value = (res.items || [])
      .filter(i => i.status !== 'cancelled' && (i.qty_prepared === null || i.qty_prepared > 0))
      .map(i => {
        const prepared = i.qty_prepared ?? i.qty
        return {
          id: i.id,
          product: i.product,
          qty_prepared: prepared,
          qty_picked: i.qty_picked ?? prepared,
          item_notes: i.item_notes || '',
        }
      })

    isPickupDialogVisible.value = true

    if (employees.value.length === 0) {
      try {
        const employeeData = await $api('/apps/employees', { query: { itemsPerPage: 100 } })
        employees.value = extractArray(employeeData)
      } catch (e) {
        console.error('Failed to load employees:', e)
      }
    }
  } catch (e) {
    console.error('Failed to open pickup dialog:', e)
    snackbar.show('Gagal memuat rincian mutasi untuk penjemputan', 'error')
  }
}

const onPickupPhotoSelected = event => {
  const file = event.target.files[0]
  if (file) {
    pickupPhotoFile.value = file
    pickupPhotoPreview.value = URL.createObjectURL(file)
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
    snackbar.show('Mohon masukkan nama kurir / petugas penjemput!', 'warning')
    return
  }

  for (const item of pickupItems.value) {
    if (item.qty_picked < 0) {
      snackbar.show(`Jumlah jemput untuk ${item.product?.name} tidak boleh negatif!`, 'warning')
      return
    }
    if (item.qty_picked > item.qty_prepared) {
      snackbar.show(`Jumlah jemput untuk ${item.product?.name} (${item.qty_picked}) melebihi jumlah yang disiapkan (${item.qty_prepared})!`, 'error')
      return
    }
  }

  if (!pickupMutasi.value) return

  isSubmittingPickup.value = true
  try {
    const formData = new FormData()
    formData.append('picked_up_by_name', employeeName)
    formData.append('pickup_courier_type', pickupCourierType.value || 'internal_courier')
    if (pickupNotes.value) formData.append('pickup_notes', pickupNotes.value.trim())
    if (pickupPhotoFile.value) formData.append('pickup_photo', pickupPhotoFile.value)
    
    formData.append('items', JSON.stringify(pickupItems.value.map(i => ({
      id: i.id,
      qty_picked: i.qty_picked,
      item_notes: i.item_notes || null,
    }))))

    const res = await $api(`/apps/stock-transfers/${pickupMutasi.value.id}/pickup`, {
      method: 'POST',
      body: formData,
    })

    snackbar.show(res.message || 'Barang berhasil divalidasi dan dibawa kurir (Status: In-Transit)', 'success')
    window.dispatchEvent(new Event('refresh-notifications'))
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

// Receive Dialog Handlers (Tahap 4 Penerimaan Toko Tujuan)
const openReceiveDialog = async mutasi => {
  try {
    const res = await $api(`/apps/stock-transfers/${mutasi.id}`)
    receiveMutasi.value = res
    receiveNotes.value = res.receive_notes || ''
    receivedPhotoFile.value = null
    receivedPhotoPreview.value = ''
    
    // Per item receive inspection checklist
    receiveItems.value = (res.items || [])
      .filter(i => i.status !== 'cancelled' && (i.qty_picked === null || i.qty_picked > 0 || i.qty_prepared > 0))
      .map(i => {
        const picked = i.qty_picked ?? i.qty_prepared ?? i.qty
        return {
          id: i.id,
          product: i.product,
          qty_picked: picked,
          qty_received: i.qty_received ?? picked,
          receive_condition: i.receive_condition || 'good',
          item_notes: i.item_notes || '',
        }
      })

    isReceiveDialogVisible.value = true
  } catch (e) {
    console.error('Failed to open receive dialog:', e)
    snackbar.show('Gagal memuat rincian mutasi untuk penerimaan', 'error')
  }
}

const onReceivedPhotoSelected = event => {
  const file = event.target.files[0]
  if (file) {
    receivedPhotoFile.value = file
    receivedPhotoPreview.value = URL.createObjectURL(file)
  }
}

const submitReceive = async () => {
  if (!receiveMutasi.value) return

  for (const item of receiveItems.value) {
    if (item.qty_received < 0) {
      snackbar.show(`Jumlah terima untuk ${item.product?.name} tidak boleh negatif!`, 'warning')
      return
    }
  }

  isSubmittingReceive.value = true
  try {
    const formData = new FormData()
    if (receiveNotes.value) formData.append('receive_notes', receiveNotes.value.trim())
    if (receivedPhotoFile.value) formData.append('received_photo', receivedPhotoFile.value)
    
    formData.append('items', JSON.stringify(receiveItems.value.map(i => ({
      id: i.id,
      qty_received: i.qty_received,
      receive_condition: i.receive_condition || 'good',
      item_notes: i.item_notes || null,
    }))))

    const res = await $api(`/apps/stock-transfers/${receiveMutasi.value.id}/receive`, {
      method: 'POST',
      body: formData,
    })

    snackbar.show(res.message || 'Barang telah berhasil diterima dan stok resmi masuk ke cabang tujuan!', 'success')
    window.dispatchEvent(new Event('refresh-notifications'))
    isReceiveDialogVisible.value = false
    isTrackingDialogVisible.value = false
    fetchData()
  } catch (error) {
    console.error(error)
    const errorMsg = error.response?._data?.error || error.response?._data?.message || error.message || 'Gagal memproses penerimaan'
    snackbar.show(`Gagal: ${errorMsg}`, 'error')
  } finally {
    isSubmittingReceive.value = false
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
      return { text: '1. Request Diajukan', color: 'warning', icon: 'ri-time-line' }
    case 'ready_for_pickup':
    case 'approved':
      return { text: '2. Siap Dijemput', color: 'info', icon: 'ri-box-3-line' }
    case 'in_transit':
      return { text: '3. Dibawa Kurir (In-Transit)', color: 'purple', icon: 'ri-truck-line' }
    case 'completed':
      return { text: '4. Selesai (Diterima Toko)', color: 'success', icon: 'ri-checkbox-circle-line' }
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

// Delivery Note / Surat Jalan State & Methods
const isDeliveryNoteDialogVisible = ref(false)
const deliveryNoteData = ref(null)

const openDeliveryNoteDialog = async item => {
  try {
    const res = await $api(`/apps/stock-transfers/${item.id}`)
    deliveryNoteData.value = res.stock_transfer || res
    isDeliveryNoteDialogVisible.value = true
  } catch (e) {
    console.error('Failed to open delivery note dialog:', e)
    deliveryNoteData.value = item
    isDeliveryNoteDialogVisible.value = true
  }
}

const getVerifyUrl = uuid => {
  if (!uuid) return ''
  return `${window.location.origin}/verify/${uuid}`
}

const deliveryNotePrintFormat = ref('continuous_form')

const isSignatureInfoDialogVisible = ref(false)
const selectedSignatureInfo = ref(null)

const openSignatureDetail = (role, data) => {
  if (!data) return
  let title = ''
  let roleTitle = ''
  let signerName = ''
  let signedAt = ''
  let branchName = ''
  let notes = ''
  let icon = 'ri-fingerprint-line'
  let color = 'primary'

  if (role === 'sender') {
    title = 'Validasi Pengirim (Cabang Asal)'
    roleTitle = 'Petugas Penyiapan Asal'
    signerName = data.prepared_by?.name || data.created_by?.name || 'Petugas Gudang Asal'
    signedAt = data.prepared_at || data.created_at
    branchName = data.source_branch?.name || '-'
    notes = data.notes || 'Stok asal telah diverifikasi dan dipotong sistem.'
    icon = 'ri-box-3-line'
    color = 'primary'
  } else if (role === 'courier') {
    title = 'Validasi Kurir / Penjemput'
    roleTitle = 'Petugas Penjemput / Driver'
    signerName = data.picked_up_by_name || 'Driver / Kurir'
    signedAt = data.picked_up_at
    branchName = `Pengiriman dari ${data.source_branch?.name || '-'} menuju ${data.destination_branch?.name || '-'}`
    notes = data.pickup_notes || 'Barang lengkap telah dimuat ke armada transportasi.'
    icon = 'ri-truck-line'
    color = 'purple'
  } else if (role === 'receiver') {
    title = 'Validasi Penerima (Toko Tujuan)'
    roleTitle = 'Petugas Penerima Toko'
    signerName = data.received_by?.name || 'Petugas Penerima'
    signedAt = data.received_at
    branchName = data.destination_branch?.name || '-'
    notes = data.receive_notes || 'Barang telah diterima dan dicek fisik di toko tujuan.'
    icon = 'ri-checkbox-circle-line'
    color = 'success'
  }

  selectedSignatureInfo.value = {
    role,
    title,
    roleTitle,
    signerName,
    signedAt,
    branchName,
    notes,
    icon,
    color,
    reference_no: data.reference_no,
    uuid: data.uuid,
    status: data.status,
  }
  isSignatureInfoDialogVisible.value = true
}

const printDeliveryNote = () => {
  const printContent = document.getElementById('printable-surat-jalan')
  if (!printContent) {
    window.print()
    return
  }

  const iframe = document.createElement('iframe')
  iframe.style.position = 'fixed'
  iframe.style.right = '0'
  iframe.style.bottom = '0'
  iframe.style.width = '0'
  iframe.style.height = '0'
  iframe.style.border = '0'
  document.body.appendChild(iframe)

  const isContinuous = deliveryNotePrintFormat.value === 'continuous_form'
  const fontFamily = isContinuous ? "'Courier New', Courier, monospace" : "'Inter', Roboto, Arial, sans-serif"
  const fontSize = isContinuous ? '11px' : '12px'

  const doc = iframe.contentWindow.document
  doc.open()
  doc.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title></title>
      <style>
        @page {
          size: auto;
          margin: 0 !important;
        }
        * {
          box-sizing: border-box;
          margin: 0;
          padding: 0;
        }
        body {
          font-family: ${fontFamily};
          color: #000;
          background: #fff;
          font-size: ${fontSize};
          line-height: 1.25;
          padding: 4mm 6mm;
          margin: 0;
        }
        .header-section {
          border-bottom: 2px solid #000;
          padding-bottom: 6px;
          margin-bottom: 8px;
        }
        .d-flex {
          display: flex;
        }
        .justify-space-between {
          justify-content: space-between;
        }
        .align-center {
          align-items: center;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-weight-bold { font-weight: bold; }
        .text-uppercase { text-transform: uppercase; }
        .branch-info-row {
          display: flex;
          gap: 10px;
          margin-bottom: 8px;
        }
        .branch-info-row > div {
          flex: 1;
        }
        .border {
          border: 1px solid #000;
        }
        .rounded {
          border-radius: 4px;
        }
        .pa-3 { padding: 8px; }
        .pa-2 { padding: 6px; }
        .mb-3 { margin-bottom: 8px; }
        .mb-2 { margin-bottom: 6px; }
        .mb-1 { margin-bottom: 4px; }
        .mt-1 { margin-top: 4px; }
        .mt-4 { margin-top: 12px; }
        .mt-5 { margin-top: 15px; }
        .pt-2 { padding-top: 6px; }
        .pt-3 { padding-top: 8px; }
        .border-t { border-top: 1px solid #000; }
        .border-b-2 { border-bottom: 2px solid #000; }
        table.items-table {
          width: 100%;
          border-collapse: collapse;
          margin-bottom: 8px;
          font-size: 11px;
        }
        table.items-table th {
          border-top: 1px solid #000;
          border-bottom: 1px solid #000;
          padding: 4px 6px;
          font-weight: bold;
          background: transparent;
        }
        table.items-table td {
          border-bottom: 1px dashed #000;
          padding: 4px 6px;
        }
        table.signatures-table {
          width: 100% !important;
          border: none !important;
          margin-top: 10px !important;
          border-collapse: collapse !important;
        }
        table.signatures-table td {
          width: 33.33% !important;
          border: none !important;
          vertical-align: top !important;
          text-align: center !important;
          padding: 4px 6px !important;
        }
        table.signatures-table svg {
          display: block !important;
          margin: 0 auto !important;
        }
        .sig-name-box {
          margin: 8px auto 0 auto !important;
          border-top: 1px solid #000 !important;
          display: inline-block !important;
          min-width: 130px !important;
          padding-top: 4px !important;
          font-weight: bold !important;
          font-size: 11px !important;
          text-align: center !important;
        }
        .d-print-none { display: none !important; }
      </style>
    </head>
    <body>
      ${printContent.innerHTML}
    </body>
    </html>
  `)
  doc.close()

  setTimeout(() => {
    iframe.contentWindow.focus()
    iframe.contentWindow.print()
    setTimeout(() => {
      document.body.removeChild(iframe)
    }, 1500)
  }, 250)
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
          Alur 4 Tahap: Pengajuan Request &rarr; Disiapkan Unit Asal &rarr; Dibawa Kurir (In-Transit) &rarr; Validasi Penerimaan Toko
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
          <VIcon icon="ri-box-3-line" class="me-2 text-info" />
          <span>2. Siap Dijemput</span>
          <VBadge
            v-if="countReadyForPickup > 0"
            color="info"
            :content="countReadyForPickup"
            inline
            class="ms-2"
          />
        </VTab>
        <VTab value="in_transit">
          <VIcon icon="ri-truck-line" class="me-2 text-purple" />
          <span>3. Dibawa Kurir</span>
          <VBadge
            v-if="countInTransit > 0"
            color="purple"
            :content="countInTransit"
            inline
            class="ms-2"
          />
        </VTab>
        <VTab value="completed">
          <VIcon icon="ri-checkbox-circle-line" class="me-2 text-success" />
          <span>4. Selesai (Diterima Toko)</span>
          <VBadge
            v-if="countCompleted > 0"
            color="success"
            :content="countCompleted"
            inline
            class="ms-2"
          />
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
        class="text-no-wrap clickable-rows-table"
        @update:options="fetchData"
        @click:row="onRowClick"
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
            <VIcon icon="ri-truck-line" size="14" class="text-purple" />
            <span class="font-weight-medium text-purple text-caption">{{ item.picked_up_by_name }}</span>
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

            <!-- Tahap 2: Siapkan Barang di Unit Asal -->
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

            <!-- Tahap 3: Validasi Penjemputan Kurir -->
            <VBtn
              v-if="['ready_for_pickup', 'approved'].includes(item.status)"
              size="small"
              variant="elevated"
              color="purple"
              prepend-icon="ri-truck-line"
              @click="openPickupDialog(item)"
            >
              Validasi Jemput
            </VBtn>

            <!-- Tahap 4: Validasi Penerimaan Toko Tujuan -->
            <VBtn
              v-if="item.status === 'in_transit'"
              size="small"
              variant="elevated"
              color="success"
              prepend-icon="ri-checkbox-circle-line"
              @click="openReceiveDialog(item)"
            >
              Validasi Terima
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

    <!-- ================= TAHAP 2: VERIFIKASI & PENYIAPAN UNIT ASAL ================= -->
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
              <span class="text-h5 font-weight-bold">Tahap 2: Verifikasi & Penyiapan Barang</span>
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
          <VAlert
            v-if="totalAvailableItemsCount > 0"
            color="primary"
            variant="tonal"
            density="compact"
            icon="ri-information-line"
            class="mb-4"
          >
            Ceklis barang yang tersedia di unit asal dan tentukan jumlah (Qty) yang akan disiapkan. Barang yang <strong>tidak diceklis</strong> akan dibatalkan dengan alasan barang kosong.
          </VAlert>
          <VAlert
            v-else
            color="error"
            variant="tonal"
            density="compact"
            icon="ri-alert-line"
            class="mb-4"
          >
            <strong>Perhatian:</strong> Semua barang tidak diceklis (kosong). Menyimpan proses ini akan otomatis <strong>menolak</strong> permintaan mutasi stok ini.
          </VAlert>

          <div class="border rounded overflow-hidden">
            <VTable density="compact">
              <thead>
                <tr class="bg-grey-100">
                  <th style="width: 140px;" class="font-weight-bold">Status / Ceklis</th>
                  <th class="font-weight-bold">Nama Barang & SKU</th>
                  <th class="text-center font-weight-bold" style="width: 110px;">Diminta</th>
                  <th class="text-center font-weight-bold" style="width: 130px;">Stok Asal</th>
                  <th class="text-center font-weight-bold" style="width: 140px;">Qty Disiapkan</th>
                  <th class="font-weight-bold" style="width: 220px;">Keterangan / Alasan</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(item, idx) in prepareItems"
                  :key="item.id"
                  :class="{'bg-red-lighten-5': !item.is_available}"
                >
                  <td>
                    <VCheckbox
                      v-model="item.is_available"
                      :label="item.is_available ? 'Ada (Kirim)' : 'Kosong'"
                      color="primary"
                      density="compact"
                      hide-details
                      @update:model-value="() => handleCheckboxChange(item)"
                    />
                  </td>
                  <td>
                    <div class="font-weight-bold text-body-2">{{ item.product?.name }}</div>
                    <div class="text-caption text-medium-emphasis">SKU: <code>{{ item.product?.sku }}</code></div>
                  </td>
                  <td class="text-center font-weight-bold text-body-2">{{ item.qty_requested }}</td>
                  <td class="text-center font-weight-bold text-body-2">
                    <VChip
                      :color="item.source_current_stock >= item.qty_requested ? 'success' : (item.source_current_stock > 0 ? 'warning' : 'error')"
                      size="small"
                      variant="tonal"
                    >
                      {{ item.source_current_stock }}
                    </VChip>
                  </td>
                  <td>
                    <VTextField
                      v-model.number="item.qty_prepared"
                      type="number"
                      density="compact"
                      min="0"
                      :max="item.source_current_stock"
                      :disabled="!item.is_available"
                      hide-details
                      class="text-center"
                    />
                  </td>
                  <td>
                    <VTextField
                      v-model="item.cancel_reason"
                      placeholder="Alasan jika kosong/parsial"
                      density="compact"
                      hide-details
                      :disabled="item.is_available && item.qty_prepared === item.qty_requested"
                    />
                  </td>
                </tr>
              </tbody>
            </VTable>
          </div>
        </VCardText>

        <VDivider />

        <VCardActions class="px-6 py-4 justify-space-between bg-grey-50">
          <VBtn variant="outlined" color="secondary" @click="isPrepareDialogVisible = false">
            Batal
          </VBtn>
          <VBtn
            color="primary"
            variant="elevated"
            prepend-icon="ri-box-3-line"
            :loading="isSubmittingPrepare"
            @click="submitPrepare"
          >
            Konfirmasi Barang Disiapkan (Potong Stok Asal)
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ================= TAHAP 3: VALIDASI PENJEMPUTAN KURIR ================= -->
    <VDialog
      v-model="isPickupDialogVisible"
      max-width="850"
      persistent
    >
      <VCard v-if="pickupMutasi">
        <VCardTitle class="d-flex justify-space-between align-center px-6 pt-6 pb-3 bg-purple-lighten-5">
          <div class="d-flex align-center gap-2">
            <VAvatar color="purple" size="36">
              <VIcon icon="ri-truck-line" color="white" size="20" />
            </VAvatar>
            <div>
              <span class="text-h6 font-weight-bold text-purple">Tahap 3: Validasi Penjemputan Barang oleh Kurir</span>
              <p class="text-caption text-medium-emphasis mb-0">
                No. Referensi: <strong>{{ pickupMutasi.reference_no }}</strong> | Dari: <strong>{{ pickupMutasi.source_branch?.name }}</strong> &rarr; Ke: <strong>{{ pickupMutasi.destination_branch?.name }}</strong>
              </p>
            </div>
          </div>
          <VBtn icon variant="text" size="small" @click="isPickupDialogVisible = false">
            <VIcon icon="ri-close-line" />
          </VBtn>
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-6" style="max-height: 70vh; overflow-y: auto;">
          <VAlert color="purple" variant="tonal" class="mb-4" density="compact">
            <div class="text-caption">
              Kurir / Petugas Jemput memverifikasi fisik barang yang telah disiapkan di unit asal. Setelah divalidasi, status mutasi beralih ke <strong>Dalam Perjalanan (In-Transit / Dibawa Kurir)</strong>.
            </div>
          </VAlert>

          <!-- Courier Information Form -->
          <VRow class="mb-4">
            <VCol cols="12" md="6">
              <label class="text-subtitle-2 font-weight-bold mb-1 d-block">
                Nama Kurir / Petugas Penjemput <span class="text-error">*</span>
              </label>
              <VCombobox
                v-model="pickupEmployeeName"
                :items="employees"
                item-title="name"
                item-value="name"
                placeholder="Pilih karyawan atau ketik nama kurir..."
                density="compact"
                clearable
                prepend-inner-icon="ri-user-follow-line"
                hide-details="auto"
              />
            </VCol>

            <VCol cols="12" md="6">
              <label class="text-subtitle-2 font-weight-bold mb-1 d-block">
                Jenis Layanan Kurir / Transportasi
              </label>
              <VSelect
                v-model="pickupCourierType"
                :items="[
                  { title: 'Kurir Internal Toko / Staf', value: 'internal_courier' },
                  { title: 'Driver Operasional Mobil / Pick-up', value: 'driver_car' },
                  { title: 'Kurir Eksternal / Ekspedisi Pihak Ketiga', value: 'external_courier' },
                  { title: 'Lainnya', value: 'other' }
                ]"
                density="compact"
                hide-details
              />
            </VCol>

            <VCol cols="12" md="6">
              <label class="text-subtitle-2 font-weight-bold mb-1 d-block">
                Foto Bukti Serah Terima / Muat Barang (Opsional)
              </label>
              <VFileInput
                accept="image/*"
                density="compact"
                placeholder="Upload foto bukti serah terima..."
                prepend-icon="ri-camera-line"
                hide-details
                @change="onPickupPhotoSelected"
              />
              <div v-if="pickupPhotoPreview" class="mt-2">
                <img :src="pickupPhotoPreview" style="max-height: 90px; border-radius: 6px; border: 1px solid #ddd;" />
              </div>
            </VCol>

            <VCol cols="12" md="6">
              <label class="text-subtitle-2 font-weight-bold mb-1 d-block">
                Catatan Penjemputan (Opsional)
              </label>
              <VTextField
                v-model="pickupNotes"
                placeholder="Contoh: Barang lengkap & dalam kondisi segel aman..."
                density="compact"
                hide-details
              />
            </VCol>
          </VRow>

          <!-- Per-Item Pickup Checklist -->
          <div class="border rounded overflow-hidden">
            <div class="bg-grey-100 px-4 py-2 font-weight-bold text-subtitle-2 border-b">
              Checklist Fisik Barang yang Dijemput Kurir
            </div>
            <VTable density="compact">
              <thead>
                <tr class="bg-grey-50">
                  <th class="font-weight-bold">Nama Barang & SKU</th>
                  <th class="text-center font-weight-bold" style="width: 130px;">Qty Disiapkan</th>
                  <th class="text-center font-weight-bold" style="width: 150px;">Qty Dijemput</th>
                  <th class="font-weight-bold" style="width: 220px;">Catatan Penjemputan</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in pickupItems" :key="item.id">
                  <td>
                    <div class="font-weight-bold">{{ item.product?.name }}</div>
                    <div class="text-caption text-medium-emphasis">SKU: <code>{{ item.product?.sku }}</code></div>
                  </td>
                  <td class="text-center font-weight-bold text-primary">
                    {{ item.qty_prepared }}
                  </td>
                  <td>
                    <VTextField
                      v-model.number="item.qty_picked"
                      type="number"
                      density="compact"
                      min="0"
                      :max="item.qty_prepared"
                      hide-details
                      class="text-center"
                    />
                  </td>
                  <td>
                    <VTextField
                      v-model="item.item_notes"
                      placeholder="Catatan kondisi/kemasan..."
                      density="compact"
                      hide-details
                    />
                  </td>
                </tr>
              </tbody>
            </VTable>
          </div>
        </VCardText>

        <VDivider />

        <VCardActions class="px-6 py-4 justify-space-between bg-grey-50">
          <VBtn variant="outlined" color="secondary" @click="isPickupDialogVisible = false">
            Batal
          </VBtn>
          <VBtn
            color="purple"
            variant="elevated"
            prepend-icon="ri-truck-line"
            :loading="isSubmittingPickup"
            @click="submitPickup"
          >
            Konfirmasi Barang Dibawa Kurir (In-Transit)
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ================= TAHAP 4: VALIDASI PENERIMAAN TOKO TUJUAN ================= -->
    <VDialog
      v-model="isReceiveDialogVisible"
      max-width="850"
      persistent
    >
      <VCard v-if="receiveMutasi">
        <VCardTitle class="d-flex justify-space-between align-center px-6 pt-6 pb-3 bg-success-lighten-5">
          <div class="d-flex align-center gap-2">
            <VAvatar color="success" size="36">
              <VIcon icon="ri-checkbox-circle-line" color="white" size="20" />
            </VAvatar>
            <div>
              <span class="text-h6 font-weight-bold text-success">Tahap 4: Validasi Penerimaan Barang oleh Toko Tujuan</span>
              <p class="text-caption text-medium-emphasis mb-0">
                No. Referensi: <strong>{{ receiveMutasi.reference_no }}</strong> | Toko Penerima: <strong>{{ receiveMutasi.destination_branch?.name }}</strong>
              </p>
            </div>
          </div>
          <VBtn icon variant="text" size="small" @click="isReceiveDialogVisible = false">
            <VIcon icon="ri-close-line" />
          </VBtn>
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-6" style="max-height: 70vh; overflow-y: auto;">
          <VAlert color="success" variant="tonal" class="mb-4" density="compact">
            <div class="text-caption">
              Pihak toko tujuan memeriksa fisik barang saat kurir tiba. Pastikan kuantitas dan kondisi barang sesuai. <strong>Stok di cabang pemohon akan resmi bertambah</strong> sesuai jumlah fisik yang diverifikasi di bawah.
            </div>
          </VAlert>

          <VRow class="mb-4">
            <VCol cols="12" md="6">
              <label class="text-subtitle-2 font-weight-bold mb-1 d-block">
                Foto Bukti Penerimaan Barang (Opsional)
              </label>
              <VFileInput
                accept="image/*"
                density="compact"
                placeholder="Upload foto barang tiba / nota terima..."
                prepend-icon="ri-camera-line"
                hide-details
                @change="onReceivedPhotoSelected"
              />
              <div v-if="receivedPhotoPreview" class="mt-2">
                <img :src="receivedPhotoPreview" style="max-height: 90px; border-radius: 6px; border: 1px solid #ddd;" />
              </div>
            </VCol>

            <VCol cols="12" md="6">
              <label class="text-subtitle-2 font-weight-bold mb-1 d-block">
                Catatan Penerimaan Toko (Opsional)
              </label>
              <VTextarea
                v-model="receiveNotes"
                placeholder="Contoh: Barang telah tiba lengkap & langsung dimasukkan ke etalase/gudang..."
                rows="2"
                density="compact"
                hide-details
              />
            </VCol>
          </VRow>

          <!-- Per-Item Receive Inspection Table -->
          <div class="border rounded overflow-hidden">
            <div class="bg-grey-100 px-4 py-2 font-weight-bold text-subtitle-2 border-b">
              Pemeriksaan Fisik Barang Tiba per Item
            </div>
            <VTable density="compact">
              <thead>
                <tr class="bg-grey-50">
                  <th class="font-weight-bold">Nama Barang & SKU</th>
                  <th class="text-center font-weight-bold" style="width: 110px;">Qty Dibawa</th>
                  <th class="text-center font-weight-bold" style="width: 130px;">Qty Diterima</th>
                  <th class="font-weight-bold" style="width: 140px;">Kondisi Fisik</th>
                  <th class="font-weight-bold" style="width: 190px;">Catatan</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in receiveItems" :key="item.id">
                  <td>
                    <div class="font-weight-bold">{{ item.product?.name }}</div>
                    <div class="text-caption text-medium-emphasis">SKU: <code>{{ item.product?.sku }}</code></div>
                  </td>
                  <td class="text-center font-weight-bold text-purple">
                    {{ item.qty_picked }}
                  </td>
                  <td>
                    <VTextField
                      v-model.number="item.qty_received"
                      type="number"
                      density="compact"
                      min="0"
                      hide-details
                      class="text-center font-weight-bold text-success"
                    />
                  </td>
                  <td>
                    <VSelect
                      v-model="item.receive_condition"
                      :items="[
                        { title: 'Baik (Good)', value: 'good' },
                        { title: 'Rusak (Damaged)', value: 'damaged' },
                        { title: 'Hilang (Missing)', value: 'missing' }
                      ]"
                      density="compact"
                      hide-details
                    />
                  </td>
                  <td>
                    <VTextField
                      v-model="item.item_notes"
                      placeholder="Catatan kondisi fisik..."
                      density="compact"
                      hide-details
                    />
                  </td>
                </tr>
              </tbody>
            </VTable>
          </div>
        </VCardText>

        <VDivider />

        <VCardActions class="px-6 py-4 justify-space-between bg-grey-50">
          <VBtn variant="outlined" color="secondary" @click="isReceiveDialogVisible = false">
            Batal
          </VBtn>
          <VBtn
            color="success"
            variant="elevated"
            prepend-icon="ri-checkbox-circle-line"
            :loading="isSubmittingReceive"
            @click="submitReceive"
          >
            Konfirmasi Diterima & Masukkan ke Stok Toko
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

    <!-- ================= TRACKING & PROGRESS STEPPER DIALOG ================= -->
    <VDialog
      v-model="isTrackingDialogVisible"
      max-width="950"
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
          <!-- Visual 4-Step Timeline -->
          <div class="mb-6 pa-4 bg-var-theme-background rounded-lg border">
            <h6 class="text-subtitle-2 font-weight-bold mb-4 d-flex align-center gap-2">
              <VIcon icon="ri-node-tree" size="20" color="primary" />
              Progress Alur 4 Tahap Mutasi Barang
            </h6>
            
            <VRow>
              <!-- Step 1: Request -->
              <VCol cols="12" sm="6" md="3">
                <VCard
                  variant="outlined"
                  class="pa-3 h-100"
                  :class="{
                    'border-warning bg-warning-lighten-5': trackingMutasi.status === 'pending',
                    'border-success bg-success-lighten-5': ['ready_for_pickup', 'in_transit', 'completed', 'approved'].includes(trackingMutasi.status),
                    'border-error bg-error-lighten-5': ['rejected', 'cancelled'].includes(trackingMutasi.status),
                  }"
                >
                  <div class="d-flex align-center gap-2 mb-2">
                    <VAvatar
                      size="28"
                      :color="['ready_for_pickup', 'in_transit', 'completed', 'approved'].includes(trackingMutasi.status) ? 'success' : 'warning'"
                    >
                      <VIcon icon="ri-file-add-line" size="16" color="white" />
                    </VAvatar>
                    <span class="font-weight-bold text-caption">1. Request Diajukan</span>
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    <div>Pemohon: <strong>{{ trackingMutasi.created_by?.name || '-' }}</strong></div>
                    <div>{{ formatDateTime(trackingMutasi.created_at) }}</div>
                  </div>
                </VCard>
              </VCol>

              <!-- Step 2: Disiapkan Unit Asal -->
              <VCol cols="12" sm="6" md="3">
                <VCard
                  variant="outlined"
                  class="pa-3 h-100"
                  :class="{
                    'border-info bg-info-lighten-5': trackingMutasi.status === 'ready_for_pickup',
                    'border-success bg-success-lighten-5': ['in_transit', 'completed'].includes(trackingMutasi.status),
                    'opacity-60': trackingMutasi.status === 'pending',
                  }"
                >
                  <div class="d-flex align-center gap-2 mb-2">
                    <VAvatar
                      size="28"
                      :color="['ready_for_pickup', 'in_transit', 'completed', 'approved'].includes(trackingMutasi.status) ? 'info' : 'grey'"
                    >
                      <VIcon icon="ri-box-3-line" size="16" color="white" />
                    </VAvatar>
                    <span class="font-weight-bold text-caption">2. Disiapkan Asal</span>
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    <div v-if="['ready_for_pickup', 'in_transit', 'completed', 'approved'].includes(trackingMutasi.status)">
                      <div>Petugas: <strong>{{ trackingMutasi.prepared_by?.name || 'Unit Asal' }}</strong></div>
                      <div>{{ formatDateTime(trackingMutasi.prepared_at || trackingMutasi.updated_at) }}</div>
                    </div>
                    <div v-else class="text-warning">Menunggu verifikasi asal</div>
                  </div>
                </VCard>
              </VCol>

              <!-- Step 3: Dibawa Kurir (In-Transit) -->
              <VCol cols="12" sm="6" md="3">
                <VCard
                  variant="outlined"
                  class="pa-3 h-100"
                  :class="{
                    'border-purple bg-purple-lighten-5': trackingMutasi.status === 'in_transit',
                    'border-success bg-success-lighten-5': trackingMutasi.status === 'completed',
                    'opacity-60': !['in_transit', 'completed'].includes(trackingMutasi.status),
                  }"
                >
                  <div class="d-flex align-center gap-2 mb-2">
                    <VAvatar
                      size="28"
                      :color="trackingMutasi.status === 'completed' ? 'success' : (trackingMutasi.status === 'in_transit' ? 'purple' : 'grey')"
                    >
                      <VIcon icon="ri-truck-line" size="16" color="white" />
                    </VAvatar>
                    <span class="font-weight-bold text-caption">3. Dibawa Kurir</span>
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    <div v-if="['in_transit', 'completed'].includes(trackingMutasi.status)">
                      <div>Kurir: <strong>{{ trackingMutasi.picked_up_by_name || '-' }}</strong></div>
                      <div>{{ formatDateTime(trackingMutasi.picked_up_at || trackingMutasi.updated_at) }}</div>
                    </div>
                    <div v-else>Belum dijemput</div>
                  </div>
                </VCard>
              </VCol>

              <!-- Step 4: Diterima Toko Tujuan -->
              <VCol cols="12" sm="6" md="3">
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
                    <span class="font-weight-bold text-caption">4. Diterima Toko</span>
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    <div v-if="trackingMutasi.status === 'completed'">
                      <div>Penerima: <strong>{{ trackingMutasi.received_by?.name || 'Cabang Pemohon' }}</strong></div>
                      <div>{{ formatDateTime(trackingMutasi.received_at) }}</div>
                    </div>
                    <div v-else>Menunggu konfirmasi tiba</div>
                  </div>
                </VCard>
              </VCol>
            </VRow>
          </div>

          <!-- Digital Verification & QR Code Banner -->
          <VCard variant="outlined" class="pa-4 mb-6 bg-grey-50 rounded-lg border">
            <div class="d-flex align-center justify-space-between flex-wrap gap-3">
              <div class="d-flex align-center gap-3">
                <div class="pa-1 bg-white border rounded shadow-sm">
                  <QrcodeVue
                    :value="getVerifyUrl(trackingMutasi.uuid)"
                    :size="64"
                    level="M"
                    render-as="svg"
                  />
                </div>
                <div>
                  <div class="d-flex align-center gap-1">
                    <VIcon icon="ri-shield-check-fill" color="success" size="18" />
                    <span class="font-weight-bold text-subtitle-2">Verifikasi & Tanda Tangan Digital Terpusat</span>
                  </div>
                  <p class="text-caption text-medium-emphasis mb-0">
                    Dokumen ini dilengkapi QR Code validasi digital per tahapan (Pengirim, Kurir, Penerima).
                  </p>
                </div>
              </div>

              <div>
                <VBtn
                  size="small"
                  variant="tonal"
                  color="primary"
                  prepend-icon="ri-external-link-line"
                  :href="getVerifyUrl(trackingMutasi.uuid)"
                  target="_blank"
                >
                  Buka Halaman Verifikasi
                </VBtn>
              </div>
            </div>
          </VCard>

          <!-- Unit Asal & Tujuan Information -->
          <VRow class="mb-4">
            <VCol cols="12" md="5">
              <VCard variant="tonal" color="error" class="pa-4">
                <div class="text-caption text-error font-weight-bold mb-1">
                  <VIcon icon="ri-upload-2-line" size="14" class="me-1" />
                  CABANG ASAL (SUMBER STOK)
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
                  CABANG TUJUAN (PEMOHON)
                </div>
                <div class="text-h6 font-weight-bold">{{ trackingMutasi.destination_branch?.name }}</div>
                <div class="text-caption">{{ trackingMutasi.destination_branch?.address || 'Alamat tidak tersedia' }}</div>
              </VCard>
            </VCol>
          </VRow>
          
          <!-- Catatan -->
          <div v-if="trackingMutasi.notes || trackingMutasi.pickup_notes || trackingMutasi.receive_notes" class="mb-6 pa-3 bg-grey-50 rounded border">
            <div v-if="trackingMutasi.notes" class="mb-2">
              <span class="font-weight-bold text-caption">Catatan Permintaan:</span>
              <p class="text-body-2 mb-0">{{ trackingMutasi.notes }}</p>
            </div>
            <div v-if="trackingMutasi.pickup_notes" class="mb-2">
              <span class="font-weight-bold text-caption text-purple">Catatan Penjemputan Kurir:</span>
              <p class="text-body-2 mb-0">{{ trackingMutasi.pickup_notes }}</p>
            </div>
            <div v-if="trackingMutasi.receive_notes">
              <span class="font-weight-bold text-caption text-success">Catatan Penerimaan Toko:</span>
              <p class="text-body-2 mb-0">{{ trackingMutasi.receive_notes }}</p>
            </div>
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
                    <th class="text-center font-weight-bold">Qty Minta</th>
                    <th class="text-center font-weight-bold">Qty Disiapkan</th>
                    <th class="text-center font-weight-bold">Qty Dijemput</th>
                    <th class="text-center font-weight-bold">Qty Diterima</th>
                    <th class="font-weight-bold">Kondisi / Catatan</th>
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
                    <td class="text-center font-weight-bold text-primary">{{ i.qty_prepared ?? '-' }}</td>
                    <td class="text-center font-weight-bold text-purple">{{ i.qty_picked ?? '-' }}</td>
                    <td class="text-center font-weight-bold text-success">{{ i.qty_received ?? '-' }}</td>
                    <td>
                      <VChip v-if="i.receive_condition === 'good'" size="x-small" color="success" variant="tonal">Baik</VChip>
                      <VChip v-else-if="i.receive_condition === 'damaged'" size="x-small" color="error" variant="tonal">Rusak</VChip>
                      <VChip v-else-if="i.receive_condition === 'missing'" size="x-small" color="warning" variant="tonal">Hilang/Kurang</VChip>
                      <span v-else class="text-caption text-medium-emphasis">-</span>
                    </td>
                  </tr>
                </tbody>
              </VTable>
            </div>
            <div v-else class="text-caption text-disabled">Tidak ada data barang.</div>
          </div>
        </VCardText>

        <VDivider />

        <!-- Actions in Tracking Dialog -->
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
            <!-- Tahap 1: Pending Actions -->
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
                prepend-icon="ri-box-3-line"
                @click="openPrepareDialog(trackingMutasi)"
              >
                Verifikasi & Siapkan Barang
              </VBtn>
            </template>

            <!-- Tahap 2: Ready for Pickup Actions -->
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
                color="purple"
                variant="elevated"
                prepend-icon="ri-truck-line"
                @click="openPickupDialog(trackingMutasi)"
              >
                Validasi Penjemputan Kurir
              </VBtn>
            </template>

            <!-- Tahap 3: In-Transit Actions -->
            <template v-else-if="trackingMutasi.status === 'in_transit'">
              <VBtn
                color="success"
                variant="elevated"
                prepend-icon="ri-checkbox-circle-line"
                @click="openReceiveDialog(trackingMutasi)"
              >
                Validasi Penerimaan Toko
              </VBtn>
            </template>

            <!-- Tahap 4: Completed -->
            <template v-else-if="trackingMutasi.status === 'completed'">
              <VBtn
                color="info"
                variant="outlined"
                prepend-icon="ri-printer-line"
                @click="openDeliveryNoteDialog(trackingMutasi)"
              >
                Cetak Surat Jalan
              </VBtn>
            </template>
          </div>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ================= SURAT JALAN / DELIVERY NOTE DIALOG (3 PIHAK) ================= -->
    <VDialog
      v-model="isDeliveryNoteDialogVisible"
      max-width="900"
    >
      <VCard v-if="deliveryNoteData">
        <VCardTitle class="bg-primary text-white pa-4 d-flex align-center justify-space-between d-print-none flex-wrap gap-2">
          <div class="d-flex align-center gap-2">
            <VIcon icon="ri-file-paper-2-line" />
            <span class="font-weight-bold">Dokumen Surat Jalan Mutasi Antar Cabang</span>
          </div>
          <div class="d-flex align-center gap-3">
            <div style="width: 260px;">
              <VSelect
                v-model="deliveryNotePrintFormat"
                :items="[
                  { title: 'Continuous Form (Dot Matrix 9.5 x 5.5 Inch)', value: 'continuous_form' },
                  { title: 'Standar Modern (A4 / Laser)', value: 'standard_a4' }
                ]"
                density="compact"
                hide-details
                bg-color="white"
                class="text-caption"
              />
            </div>
            <VBtn
              color="white"
              variant="elevated"
              size="small"
              class="text-primary font-weight-bold"
              prepend-icon="ri-printer-line"
              @click="printDeliveryNote"
            >
              Cetak (Print)
            </VBtn>
            <VBtn icon="ri-close-line" variant="text" size="small" @click="isDeliveryNoteDialogVisible = false" />
          </div>
        </VCardTitle>

        <!-- Printable Document Body -->
        <VCardText
          class="pa-6 printable-area bg-white text-black"
          id="printable-surat-jalan"
          :class="{'format-continuous-form-mode': deliveryNotePrintFormat === 'continuous_form'}"
        >
          <!-- Document Header -->
          <div class="border-b-2 pb-3 mb-4 header-section">
            <div class="d-flex justify-space-between align-center">
              <div>
                <h3 class="text-h5 font-weight-bold mb-0 text-uppercase text-primary doc-main-title">
                  SURAT JALAN MUTASI BARANG
                </h3>
                <p class="text-caption text-medium-emphasis mb-0 doc-company-sub">
                  PT. DUMAI MANAJEMEN SISTEM INVENTORI & LOGISTIK
                </p>
              </div>
              <div class="text-right">
                <div class="text-caption font-mono">NO. DOKUMEN: <strong class="text-body-1">{{ deliveryNoteData.reference_no }}</strong></div>
                <div class="text-caption font-medium">Tgl: {{ formatDateTime(deliveryNoteData.created_at) }}</div>
              </div>
            </div>
          </div>

          <!-- Cabang Asal vs Cabang Tujuan Grid -->
          <VRow class="mb-3 g-2 branch-info-row">
            <VCol cols="6">
              <div class="pa-3 border rounded bg-light-section">
                <div class="text-caption font-weight-bold text-uppercase mb-1">
                  PENGIRIM (CABANG ASAL):
                </div>
                <div class="font-weight-bold text-body-2">{{ deliveryNoteData.source_branch?.name || '-' }}</div>
                <div class="text-caption text-medium-emphasis">{{ deliveryNoteData.source_branch?.address || 'Alamat Cabang Asal' }}</div>
                <div class="text-caption mt-1">Petugas Penyiapan: <strong>{{ deliveryNoteData.prepared_by?.name || deliveryNoteData.user?.name || '-' }}</strong></div>
              </div>
            </VCol>
            <VCol cols="6">
              <div class="pa-3 border rounded bg-light-section">
                <div class="text-caption font-weight-bold text-uppercase mb-1 text-primary">
                  PENERIMA (CABANG TUJUAN):
                </div>
                <div class="font-weight-bold text-body-2 text-primary">{{ deliveryNoteData.destination_branch?.name || '-' }}</div>
                <div class="text-caption text-medium-emphasis">{{ deliveryNoteData.destination_branch?.address || 'Alamat Cabang Tujuan' }}</div>
                <div class="text-caption mt-1">Kurir / Penjemput: <strong>{{ deliveryNoteData.picked_up_by_name || 'Kurir Operasional' }}</strong></div>
              </div>
            </VCol>
          </VRow>

          <!-- Items Table -->
          <div class="border rounded mb-3 overflow-hidden table-wrapper">
            <table class="w-100 items-table" style="border-collapse: collapse; width: 100%;">
              <thead>
                <tr style="background-color: #f3f4f6; border-bottom: 2px solid #000;">
                  <th style="padding: 6px 8px; text-align: center; width: 35px; font-size: 11px;">NO</th>
                  <th style="padding: 6px 8px; text-align: left; font-size: 11px;">KODE / SKU</th>
                  <th style="padding: 6px 8px; text-align: left; font-size: 11px;">NAMA BARANG</th>
                  <th style="padding: 6px 8px; text-align: center; width: 85px; font-size: 11px;">QTY MINTA</th>
                  <th style="padding: 6px 8px; text-align: center; width: 85px; font-size: 11px;">QTY KIRIM</th>
                  <th style="padding: 6px 8px; text-align: center; width: 85px; font-size: 11px;">QTY TERIMA</th>
                  <th style="padding: 6px 8px; text-align: left; width: 80px; font-size: 11px;">KONDISI</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(it, idx) in (deliveryNoteData.items || [])"
                  :key="idx"
                  style="border-bottom: 1px solid #ddd;"
                >
                  <td style="padding: 5px 8px; text-align: center; font-size: 11px;">{{ idx + 1 }}</td>
                  <td style="padding: 5px 8px; font-size: 11px; font-weight: bold;">{{ it.product?.sku || '-' }}</td>
                  <td style="padding: 5px 8px; font-size: 11px;">{{ it.product?.name || '-' }}</td>
                  <td style="padding: 5px 8px; text-align: center; font-size: 11px;">{{ it.qty }}</td>
                  <td style="padding: 5px 8px; text-align: center; font-size: 11px; font-weight: bold;">
                    {{ it.qty_picked ?? (it.qty_prepared ?? it.qty) }}
                  </td>
                  <td style="padding: 5px 8px; text-align: center; font-size: 11px; font-weight: bold;">
                    {{ it.qty_received ?? '-' }}
                  </td>
                  <td style="padding: 5px 8px; font-size: 11px;">
                    <span v-if="it.receive_condition === 'good'" style="font-weight: bold;">Baik</span>
                    <span v-else-if="it.receive_condition === 'damaged'" style="color: #dc2626; font-weight: bold;">Rusak</span>
                    <span v-else-if="it.receive_condition === 'missing'" style="color: #d97706; font-weight: bold;">Hilang</span>
                    <span v-else>-</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Notes -->
          <div v-if="deliveryNoteData.notes || deliveryNoteData.pickup_notes || deliveryNoteData.receive_notes" class="pa-2 mb-3 border rounded text-caption notes-section">
            <div v-if="deliveryNoteData.notes"><strong>Catatan Permintaan:</strong> {{ deliveryNoteData.notes }}</div>
            <div v-if="deliveryNoteData.pickup_notes"><strong>Catatan Penjemputan:</strong> {{ deliveryNoteData.pickup_notes }}</div>
            <div v-if="deliveryNoteData.receive_notes"><strong>Catatan Penerimaan:</strong> {{ deliveryNoteData.receive_notes }}</div>
          </div>

          <!-- 3-Party Digital Signature & QR Code Block (Clean without label texts) -->
          <div class="mt-4 pt-3 border-t signatures-section">
            <table class="w-100 signatures-table" style="width: 100%; border: none; border-collapse: collapse;">
              <tbody>
                <tr>
                  <!-- 1. Cabang Asal -->
                  <td style="width: 33.33%; text-align: center; vertical-align: top; border: none; padding: 0 8px;">
                    <div class="text-caption font-weight-bold mb-2 text-uppercase" style="font-size: 11px; text-align: center;">
                      1. DISERAHKAN (CABANG ASAL)
                    </div>
                    
                    <div
                      v-if="deliveryNoteData.prepared_at || ['ready_for_pickup', 'in_transit', 'completed', 'approved'].includes(deliveryNoteData.status)"
                      style="text-align: center; margin: 4px auto 8px auto; width: 100%; display: flex; justify-content: center; align-items: center;"
                    >
                      <div
                        style="display: inline-block; margin: 0 auto; text-align: center; border: 1px solid #ddd; padding: 3px; border-radius: 4px; background: #fff;"
                        class="cursor-pointer qr-sig-hover"
                        title="Klik untuk melihat bukti TTD Elektronik"
                        @click="openSignatureDetail('sender', deliveryNoteData)"
                      >
                        <QrcodeVue
                          :value="getVerifyUrl(deliveryNoteData.uuid)"
                          :size="84"
                          level="M"
                          render-as="svg"
                        />
                      </div>
                    </div>
                    <div v-else class="my-6 text-caption text-disabled border border-dashed rounded pa-3 mx-2" style="font-size: 10px; text-align: center;">
                      (Menunggu Validasi Asal)
                    </div>

                    <div class="sig-name-box">
                      ( {{ deliveryNoteData.prepared_by?.name || deliveryNoteData.created_by?.name || 'Petugas Gudang' }} )
                    </div>
                  </td>

                  <!-- 2. Kurir Penjemput -->
                  <td style="width: 33.33%; text-align: center; vertical-align: top; border: none; padding: 0 8px;">
                    <div class="text-caption font-weight-bold mb-2 text-uppercase text-purple" style="font-size: 11px; text-align: center;">
                      2. DIBAWA / KURIR PENJEMPUT
                    </div>

                    <div
                      v-if="deliveryNoteData.picked_up_at || ['in_transit', 'completed'].includes(deliveryNoteData.status)"
                      style="text-align: center; margin: 4px auto 8px auto; width: 100%; display: flex; justify-content: center; align-items: center;"
                    >
                      <div
                        style="display: inline-block; margin: 0 auto; text-align: center; border: 1px solid #ddd; padding: 3px; border-radius: 4px; background: #fff;"
                        class="cursor-pointer qr-sig-hover"
                        title="Klik untuk melihat bukti TTD Elektronik"
                        @click="openSignatureDetail('courier', deliveryNoteData)"
                      >
                        <QrcodeVue
                          :value="getVerifyUrl(deliveryNoteData.uuid)"
                          :size="84"
                          level="M"
                          render-as="svg"
                        />
                      </div>
                    </div>
                    <div v-else class="my-6 text-caption text-disabled border border-dashed rounded pa-3 mx-2" style="font-size: 10px; text-align: center;">
                      (Menunggu Penjemputan)
                    </div>

                    <div class="sig-name-box">
                      ( {{ deliveryNoteData.picked_up_by_name || 'Driver / Kurir' }} )
                    </div>
                  </td>

                  <!-- 3. Cabang Pemohon -->
                  <td style="width: 33.33%; text-align: center; vertical-align: top; border: none; padding: 0 8px;">
                    <div class="text-caption font-weight-bold mb-2 text-uppercase text-success" style="font-size: 11px; text-align: center;">
                      3. DITERIMA (CABANG PEMOHON)
                    </div>

                    <div
                      v-if="deliveryNoteData.received_at || deliveryNoteData.status === 'completed'"
                      style="text-align: center; margin: 4px auto 8px auto; width: 100%; display: flex; justify-content: center; align-items: center;"
                    >
                      <div
                        style="display: inline-block; margin: 0 auto; text-align: center; border: 1px solid #ddd; padding: 3px; border-radius: 4px; background: #fff;"
                        class="cursor-pointer qr-sig-hover"
                        title="Klik untuk melihat bukti TTD Elektronik"
                        @click="openSignatureDetail('receiver', deliveryNoteData)"
                      >
                        <QrcodeVue
                          :value="getVerifyUrl(deliveryNoteData.uuid)"
                          :size="84"
                          level="M"
                          render-as="svg"
                        />
                      </div>
                    </div>
                    <div v-else class="my-6 text-caption text-disabled border border-dashed rounded pa-3 mx-2" style="font-size: 10px; text-align: center;">
                      (Menunggu Penerimaan)
                    </div>

                    <div class="sig-name-box">
                      ( {{ deliveryNoteData.received_by?.name || 'Petugas Penerima' }} )
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>

            <div class="text-center mt-4 text-caption text-medium-emphasis d-flex align-center justify-center gap-1 d-print-none" style="font-size: 11px;">
              <VIcon icon="ri-fingerprint-line" size="14" color="primary" />
              <span>Klik QR Code di atas atau scan dengan kamera smartphone untuk melihat dokumen informasi Tanda Tangan Elektronik.</span>
            </div>
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

    <!-- ================= DIALOG INFORMASI TANDA TANGAN ELEKTRONIK ================= -->
    <VDialog
      v-model="isSignatureInfoDialogVisible"
      max-width="520"
    >
      <VCard v-if="selectedSignatureInfo" class="rounded-xl overflow-hidden">
        <VCardTitle class="bg-primary text-white pa-4 d-flex align-center justify-space-between">
          <div class="d-flex align-center gap-2">
            <VIcon icon="ri-shield-check-fill" color="white" />
            <span class="font-weight-bold text-subtitle-1">Informasi Tanda Tangan Elektronik</span>
          </div>
          <VBtn icon="ri-close-line" variant="text" size="small" color="white" @click="isSignatureInfoDialogVisible = false" />
        </VCardTitle>

        <VCardText class="pa-6">
          <div class="text-center mb-4">
            <VAvatar :color="selectedSignatureInfo.color" variant="tonal" size="56" class="mb-2">
              <VIcon :icon="selectedSignatureInfo.icon" size="32" />
            </VAvatar>
            <h5 class="text-h6 font-weight-bold text-primary mb-1">
              {{ selectedSignatureInfo.title }}
            </h5>
            <VChip color="success" size="small" variant="elevated" class="font-weight-bold">
              ✓ TTD ELEKTRONIK SAH & TERVERIFIKASI
            </VChip>
          </div>

          <VDivider class="mb-4" />

          <div class="bg-grey-50 pa-3 rounded-lg border mb-4">
            <VRow dense>
              <VCol cols="5" class="text-caption text-medium-emphasis">Penandatangan:</VCol>
              <VCol cols="7" class="text-caption font-weight-bold text-primary">{{ selectedSignatureInfo.signerName }}</VCol>

              <VCol cols="5" class="text-caption text-medium-emphasis">Peran/Jabatan:</VCol>
              <VCol cols="7" class="text-caption font-weight-medium">{{ selectedSignatureInfo.roleTitle }}</VCol>

              <VCol cols="5" class="text-caption text-medium-emphasis">Waktu Penandatanganan:</VCol>
              <VCol cols="7" class="text-caption font-weight-medium">{{ formatDateTime(selectedSignatureInfo.signedAt) }}</VCol>

              <VCol cols="5" class="text-caption text-medium-emphasis">Cabang/Rute:</VCol>
              <VCol cols="7" class="text-caption font-weight-medium">{{ selectedSignatureInfo.branchName }}</VCol>

              <VCol cols="5" class="text-caption text-medium-emphasis">No. Dokumen:</VCol>
              <VCol cols="7" class="text-caption font-mono font-weight-bold">{{ selectedSignatureInfo.reference_no }}</VCol>

              <VCol cols="5" class="text-caption text-medium-emphasis">Catatan:</VCol>
              <VCol cols="7" class="text-caption font-italic">"{{ selectedSignatureInfo.notes }}"</VCol>
            </VRow>
          </div>

          <div class="text-caption text-disabled text-center mb-0">
            Kode Keamanan: <code>{{ selectedSignatureInfo.uuid }}</code>
          </div>
        </VCardText>

        <VCardActions class="pa-4 bg-grey-50 justify-space-between">
          <VBtn
            variant="tonal"
            color="primary"
            size="small"
            prepend-icon="ri-external-link-line"
            :href="getVerifyUrl(selectedSignatureInfo.uuid)"
            target="_blank"
          >
            Buka Halaman Verifikasi
          </VBtn>
          <VBtn
            variant="outlined"
            color="secondary"
            size="small"
            @click="isSignatureInfoDialogVisible = false"
          >
            Tutup
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

<style>
.clickable-rows-table tbody tr {
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.clickable-rows-table tbody tr:hover {
  background-color: rgba(var(--v-theme-primary), 0.05) !important;
}

.qr-sig-hover {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.qr-sig-hover:hover {
  transform: scale(1.04);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.cursor-pointer {
  cursor: pointer;
}

@media print {
  body * {
    visibility: hidden;
  }
  
  #printable-surat-jalan, #printable-surat-jalan * {
    visibility: visible;
  }
  
  #printable-surat-jalan {
    position: absolute;
    left: 0;
    top: 0;
    width: 241mm !important;
    max-width: 241mm !important;
    margin: 0 !important;
    padding: 4mm 6mm !important;
    background: #fff !important;
    color: #000 !important;
  }

  /* CONTINUOUS FORM (DOT MATRIX) PRINT MEDIA STYLING */
  #printable-surat-jalan.format-continuous-form-mode {
    font-family: 'Courier New', Courier, monospace !important;
    font-size: 11px !important;
    line-height: 1.2 !important;
  }

  #printable-surat-jalan.format-continuous-form-mode .bg-light-section,
  #printable-surat-jalan.format-continuous-form-mode .notes-section {
    background-color: transparent !important;
    border: 1px solid #000 !important;
  }

  #printable-surat-jalan.format-continuous-form-mode table {
    border-collapse: collapse !important;
  }

  #printable-surat-jalan.format-continuous-form-mode th {
    background-color: transparent !important;
    border-top: 1px solid #000 !important;
    border-bottom: 1px solid #000 !important;
    color: #000 !important;
  }

  #printable-surat-jalan.format-continuous-form-mode td {
    border-bottom: 1px dashed #000 !important;
    color: #000 !important;
  }

  .d-print-none {
    display: none !important;
  }

  @page {
    size: auto;
    margin: 0;
  }
}
</style>

<route lang="yaml">
meta:
  action: read
  subject: Mutasi Stok
</route>
