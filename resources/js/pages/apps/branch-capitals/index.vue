<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { useSnackbarStore } from '@/stores/snackbar'

definePage({
  meta: {
    action: 'read',
    subject: 'Modal & ROI Cabang',
  },
})

const snackbar = useSnackbarStore()

// State Data
const transactions = ref([])
const branches = ref([])
const summary = ref({
  total_injected: 0,
  total_returned: 0,
  pending_returned: 0,
  remaining_capital: 0,
  payback_percentage: 0,
  branch_breakdown: [],
})

const isLoading = ref(false)
const isSummaryLoading = ref(false)
const isSubmitting = ref(false)

// Filters & Pagination
const selectedBranch = ref('all')
const selectedType = ref('all')
const selectedStatus = ref('all')
const search = ref('')
const page = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)
let searchTimeout = null

// Drawers & Modals Visibility
const isInflowDrawerOpen = ref(false)
const isRequestDrawerOpen = ref(false)
const isOutflowDrawerOpen = ref(false)
const isEditDrawerOpen = ref(false)

const isApproveDialogVisible = ref(false)
const isRejectDialogVisible = ref(false)
const isVoidDialogVisible = ref(false)
const isDeleteDialogVisible = ref(false)
const isDetailDialogVisible = ref(false)
const isPreviewDialogVisible = ref(false)

const selectedTransaction = ref(null)
const rejectReason = ref('')
const voidReason = ref('')
const previewImage = ref('')

// Form State 1: Injeksi Langsung Owner -> Cabang
const inflowForm = ref({
  branch_id: null,
  category: 'Modal Awal',
  amount: '',
  date: new Date().toISOString().substring(0, 10),
  payment_method: 'Transfer Bank',
  bank_name: '',
  account_number: '',
  account_name: '',
  proof_file: null,
  notes: '',
})

// Form State 2: Permintaan Modal Tambahan Cabang -> Owner
const requestForm = ref({
  branch_id: null,
  category: 'Permintaan Tambahan Stok',
  amount: '',
  date: new Date().toISOString().substring(0, 10),
  proof_file: null,
  notes: '',
})

// Form State 3: Setoran Pengembalian Modal Cabang -> Owner
const outflowForm = ref({
  branch_id: null,
  category: 'Setoran Laba Closing Shift',
  amount: '',
  date: new Date().toISOString().substring(0, 10),
  payment_method: 'Transfer Bank',
  bank_name: '',
  account_number: '',
  account_name: '',
  proof_file: null,
  notes: '',
})

// Form State 4: Edit Transaksi
const editForm = ref({
  id: null,
  branch_id: null,
  type: 'injection',
  category: 'Modal Awal',
  amount: '',
  date: '',
  payment_method: 'Transfer Bank',
  bank_name: '',
  account_number: '',
  account_name: '',
  proof_file: null,
  current_proof: null,
  notes: '',
})

// Form State 5: Approval Modal Penyaluran (Untuk Permintaan Injeksi)
const approveForm = ref({
  payment_method: 'Transfer Bank',
  bank_name: '',
  account_number: '',
  account_name: '',
  proof_file: null,
})

// Formatting Helpers
const formatCurrency = val => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(val || 0)
}

const formatInputRupiah = val => {
  if (!val && val !== 0) return ''
  const digits = String(val).replace(/\D/g, '')
  return digits ? new Intl.NumberFormat('id-ID').format(digits) : ''
}

const parseInputRupiah = val => {
  if (!val && val !== 0) return 0
  const digits = String(val).replace(/\D/g, '')
  return Number(digits) || 0
}

const formatDate = dateStr => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

const formatDateTime = dateStr => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

// Fetch Branches
const fetchBranches = async () => {
  try {
    const res = await $api('/apps/branches', { query: { itemsPerPage: 100 } })
    branches.value = res.branches || res.data || (Array.isArray(res) ? res : [])
    if (branches.value.length === 1) {
      selectedBranch.value = branches.value[0].id
    }
  } catch (error) {
    console.error('Error fetching branches:', error)
    branches.value = []
  }
}

// Fetch Executive Summary
const fetchSummary = async () => {
  isSummaryLoading.value = true
  try {
    const params = {}
    if (selectedBranch.value !== 'all') {
      params.branch_id = selectedBranch.value
    }
    const res = await $api('/apps/branch-capitals/summary', { query: params })
    summary.value = {
      total_injected: 0,
      total_returned: 0,
      pending_returned: 0,
      remaining_capital: 0,
      payback_percentage: 0,
      branch_breakdown: [],
      ...res,
    }
  } catch (error) {
    console.error('Error fetching summary:', error)
  } finally {
    isSummaryLoading.value = false
  }
}

// Fetch Transactions
const fetchTransactions = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      per_page: itemsPerPage.value,
    }
    if (selectedBranch.value !== 'all') params.branch_id = selectedBranch.value
    if (selectedType.value !== 'all') params.type = selectedType.value
    if (selectedStatus.value !== 'all') params.status = selectedStatus.value
    if (search.value) params.search = search.value

    const res = await $api('/apps/branch-capitals', { query: params })
    transactions.value = res.data || []
    totalItems.value = res.total || 0
  } catch (error) {
    console.error('Error fetching transactions:', error)
    snackbar.show('Gagal memuat riwayat mutasi modal', 'error')
  } finally {
    isLoading.value = false
  }
}

// Watchers
watch([selectedBranch, selectedType, selectedStatus], () => {
  page.value = 1
  fetchSummary()
  fetchTransactions()
})

watch(search, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchTransactions()
  }, 400)
})

watch([page, itemsPerPage], () => {
  fetchTransactions()
})

// Action Handlers
const openInflowDrawer = () => {
  resetInflowForm()
  isInflowDrawerOpen.value = true
}

const openRequestDrawer = () => {
  resetRequestForm()
  isRequestDrawerOpen.value = true
}

const openOutflowDrawer = () => {
  resetOutflowForm()
  isOutflowDrawerOpen.value = true
}

// Submit Inflow Langsung (Owner -> Cabang)
const handleInflowSubmit = async () => {
  const numericAmount = parseInputRupiah(inflowForm.value.amount)
  if (!inflowForm.value.branch_id || !numericAmount || numericAmount <= 0) {
    snackbar.show('Mohon lengkapi cabang tujuan dan nominal modal', 'warning')
    return
  }

  isSubmitting.value = true
  try {
    const formData = new FormData()
    formData.append('branch_id', inflowForm.value.branch_id)
    formData.append('type', 'injection')
    formData.append('category', inflowForm.value.category)
    formData.append('amount', numericAmount)
    formData.append('date', inflowForm.value.date)
    formData.append('payment_method', inflowForm.value.payment_method)
    if (inflowForm.value.bank_name) formData.append('bank_name', inflowForm.value.bank_name)
    if (inflowForm.value.account_number) formData.append('account_number', inflowForm.value.account_number)
    if (inflowForm.value.account_name) formData.append('account_name', inflowForm.value.account_name)
    if (inflowForm.value.proof_file) formData.append('proof_file', inflowForm.value.proof_file)
    if (inflowForm.value.notes) formData.append('notes', inflowForm.value.notes)

    await $api('/apps/branch-capitals', {
      method: 'POST',
      body: formData,
    })

    snackbar.show('Penyertaan modal berhasil dicatat dan disetujui!', 'success')
    isInflowDrawerOpen.value = false
    window.dispatchEvent(new Event('refresh-notifications'))
    fetchSummary()
    fetchTransactions()
  } catch (error) {
    console.error('Error submitting injection:', error)
    snackbar.show(error.response?._data?.message || 'Gagal menyimpan penyertaan modal', 'error')
  } finally {
    isSubmitting.value = false
  }
}

// Submit Permintaan Modal Tambahan (Cabang -> Owner)
const handleRequestSubmit = async () => {
  const numericAmount = parseInputRupiah(requestForm.value.amount)
  if (!requestForm.value.branch_id || !numericAmount || numericAmount <= 0) {
    snackbar.show('Mohon lengkapi cabang dan nominal permintaan modal', 'warning')
    return
  }

  isSubmitting.value = true
  try {
    const formData = new FormData()
    formData.append('branch_id', requestForm.value.branch_id)
    formData.append('type', 'injection')
    formData.append('is_request', '1')
    formData.append('category', requestForm.value.category)
    formData.append('amount', numericAmount)
    formData.append('date', requestForm.value.date)
    formData.append('payment_method', 'Transfer Bank')
    if (requestForm.value.proof_file) formData.append('proof_file', requestForm.value.proof_file)
    if (requestForm.value.notes) formData.append('notes', requestForm.value.notes)

    await $api('/apps/branch-capitals', {
      method: 'POST',
      body: formData,
    })

    snackbar.show('Permintaan modal tambahan berhasil diajukan ke Owner!', 'success')
    isRequestDrawerOpen.value = false
    window.dispatchEvent(new Event('refresh-notifications'))
    fetchSummary()
    fetchTransactions()
  } catch (error) {
    console.error('Error submitting request:', error)
    snackbar.show(error.response?._data?.message || 'Gagal mengajukan permintaan modal', 'error')
  } finally {
    isSubmitting.value = false
  }
}

// Submit Outflow Pengembalian Modal (Cabang -> Owner)
const handleOutflowSubmit = async () => {
  const numericAmount = parseInputRupiah(outflowForm.value.amount)
  if (!outflowForm.value.branch_id || !numericAmount || numericAmount <= 0) {
    snackbar.show('Mohon lengkapi cabang asal dan nominal pengembalian', 'warning')
    return
  }

  isSubmitting.value = true
  try {
    const formData = new FormData()
    formData.append('branch_id', outflowForm.value.branch_id)
    formData.append('type', 'return')
    formData.append('category', outflowForm.value.category)
    formData.append('amount', numericAmount)
    formData.append('date', outflowForm.value.date)
    formData.append('payment_method', outflowForm.value.payment_method)
    if (outflowForm.value.bank_name) formData.append('bank_name', outflowForm.value.bank_name)
    if (outflowForm.value.account_number) formData.append('account_number', outflowForm.value.account_number)
    if (outflowForm.value.account_name) formData.append('account_name', outflowForm.value.account_name)
    if (outflowForm.value.proof_file) formData.append('proof_file', outflowForm.value.proof_file)
    if (outflowForm.value.notes) formData.append('notes', outflowForm.value.notes)

    await $api('/apps/branch-capitals', {
      method: 'POST',
      body: formData,
    })

    snackbar.show('Pengajuan setoran pengembalian modal berhasil dikirim!', 'success')
    isOutflowDrawerOpen.value = false
    window.dispatchEvent(new Event('refresh-notifications'))
    fetchSummary()
    fetchTransactions()
  } catch (error) {
    console.error('Error submitting return:', error)
    snackbar.show(error.response?._data?.message || 'Gagal mengajukan pengembalian modal', 'error')
  } finally {
    isSubmitting.value = false
  }
}

// Edit Modal
const openEditDrawer = item => {
  editForm.value = {
    id: item.id,
    branch_id: item.branch_id,
    type: item.type,
    category: item.category,
    amount: formatInputRupiah(item.amount),
    date: item.date ? item.date.substring(0, 10) : new Date().toISOString().substring(0, 10),
    payment_method: item.payment_method || 'Transfer Bank',
    bank_name: item.bank_name || '',
    account_number: item.account_number || '',
    account_name: item.account_name || '',
    proof_file: null,
    current_proof: item.proof_file,
    notes: item.notes || '',
  }
  isEditDrawerOpen.value = true
}

const handleEditSubmit = async () => {
  const numericAmount = parseInputRupiah(editForm.value.amount)
  if (!editForm.value.branch_id || !numericAmount || numericAmount <= 0) {
    snackbar.show('Mohon lengkapi data dan nominal transaksi', 'warning')
    return
  }

  isSubmitting.value = true
  try {
    const formData = new FormData()
    formData.append('branch_id', editForm.value.branch_id)
    formData.append('type', editForm.value.type)
    formData.append('category', editForm.value.category)
    formData.append('amount', numericAmount)
    formData.append('date', editForm.value.date)
    formData.append('payment_method', editForm.value.payment_method)
    if (editForm.value.bank_name) formData.append('bank_name', editForm.value.bank_name)
    if (editForm.value.account_number) formData.append('account_number', editForm.value.account_number)
    if (editForm.value.account_name) formData.append('account_name', editForm.value.account_name)
    if (editForm.value.proof_file) formData.append('proof_file', editForm.value.proof_file)
    if (editForm.value.notes) formData.append('notes', editForm.value.notes)

    await $api(`/apps/branch-capitals/${editForm.value.id}`, {
      method: 'POST',
      body: formData,
    })

    snackbar.show('Transaksi modal berhasil diperbarui!', 'success')
    isEditDrawerOpen.value = false
    fetchSummary()
    fetchTransactions()
  } catch (error) {
    console.error('Error updating capital:', error)
    snackbar.show(error.response?._data?.message || 'Gagal memperbarui transaksi modal', 'error')
  } finally {
    isSubmitting.value = false
  }
}

// Approve Transaction
const confirmApprove = item => {
  selectedTransaction.value = item
  approveForm.value = {
    payment_method: item.payment_method || 'Transfer Bank',
    bank_name: item.bank_name || '',
    account_number: item.account_number || '',
    account_name: item.account_name || '',
    proof_file: null,
  }
  isApproveDialogVisible.value = true
}

const handleApprove = async () => {
  if (!selectedTransaction.value) return
  isSubmitting.value = true
  try {
    const formData = new FormData()
    if (approveForm.value.payment_method) formData.append('payment_method', approveForm.value.payment_method)
    if (approveForm.value.bank_name) formData.append('bank_name', approveForm.value.bank_name)
    if (approveForm.value.account_number) formData.append('account_number', approveForm.value.account_number)
    if (approveForm.value.account_name) formData.append('account_name', approveForm.value.account_name)
    if (approveForm.value.proof_file) formData.append('proof_file', approveForm.value.proof_file)

    await $api(`/apps/branch-capitals/${selectedTransaction.value.id}/approve`, {
      method: 'POST',
      body: formData,
    })

    const msg = selectedTransaction.value.type === 'injection'
      ? 'Permintaan modal tambahan telah disetujui & disalurkan!'
      : 'Setoran pengembalian modal telah disetujui!'

    snackbar.show(msg, 'success')
    isApproveDialogVisible.value = false
    fetchSummary()
    fetchTransactions()
  } catch (error) {
    console.error('Error approving:', error)
    snackbar.show('Gagal menyetujui transaksi modal', 'error')
  } finally {
    isSubmitting.value = false
  }
}

// Reject Transaction
const confirmReject = item => {
  selectedTransaction.value = item
  rejectReason.value = ''
  isRejectDialogVisible.value = true
}

const handleReject = async () => {
  if (!selectedTransaction.value || !rejectReason.value.trim()) {
    snackbar.show('Wajib mengisi alasan penolakan', 'warning')
    return
  }
  isSubmitting.value = true
  try {
    await $api(`/apps/branch-capitals/${selectedTransaction.value.id}/reject`, {
      method: 'POST',
      body: { reason: rejectReason.value },
    })
    snackbar.show('Transaksi modal telah ditolak.', 'info')
    isRejectDialogVisible.value = false
    fetchSummary()
    fetchTransactions()
  } catch (error) {
    console.error('Error rejecting:', error)
    snackbar.show('Gagal menolak transaksi', 'error')
  } finally {
    isSubmitting.value = false
  }
}

// Void (Batalkan Persetujuan)
const confirmVoid = item => {
  selectedTransaction.value = item
  voidReason.value = ''
  isVoidDialogVisible.value = true
}

const handleVoid = async () => {
  if (!selectedTransaction.value || !voidReason.value.trim()) {
    snackbar.show('Wajib mengisi alasan pembatalan persetujuan', 'warning')
    return
  }
  isSubmitting.value = true
  try {
    await $api(`/apps/branch-capitals/${selectedTransaction.value.id}/void`, {
      method: 'POST',
      body: { reason: voidReason.value },
    })
    snackbar.show('Persetujuan transaksi berhasil dibatalkan (void).', 'warning')
    isVoidDialogVisible.value = false
    fetchSummary()
    fetchTransactions()
  } catch (error) {
    console.error('Error voiding:', error)
    snackbar.show('Gagal membatalkan persetujuan', 'error')
  } finally {
    isSubmitting.value = false
  }
}

// Delete Transaction
const confirmDelete = item => {
  selectedTransaction.value = item
  isDeleteDialogVisible.value = true
}

const handleDelete = async () => {
  if (!selectedTransaction.value) return
  isSubmitting.value = true
  try {
    await $api(`/apps/branch-capitals/${selectedTransaction.value.id}`, { method: 'DELETE' })
    snackbar.show('Transaksi modal berhasil dihapus.', 'success')
    isDeleteDialogVisible.value = false
    fetchSummary()
    fetchTransactions()
  } catch (error) {
    console.error('Error deleting:', error)
    snackbar.show('Gagal menghapus transaksi modal', 'error')
  } finally {
    isSubmitting.value = false
  }
}

// Detail Dialog
const showDetail = item => {
  selectedTransaction.value = item
  isDetailDialogVisible.value = true
  fetchCapitalEmailLogs(item.id)
}

// ==================== EMAIL NOTIFICATIONS & AUDIT LOGS ====================
const isSendSummaryEmailDialogVisible = ref(false)
const summaryEmailInput = ref('')
const isSendingSummaryEmail = ref(false)

const isSendCapitalEmailDialogVisible = ref(false)
const capitalEmailInput = ref('')
const isSendingCapitalEmail = ref(false)

const capitalEmailLogs = ref([])
const isLoadingCapitalEmailLogs = ref(false)
const isRetryingCapitalEmail = ref({})

const fetchCapitalEmailLogs = async id => {
  if (!id) return
  isLoadingCapitalEmailLogs.value = true
  try {
    const res = await $api(`/apps/branch-capitals/${id}/email-logs`)
    capitalEmailLogs.value = res.data || []
  } catch (e) {
    console.error('Failed to fetch capital email logs:', e)
  } finally {
    isLoadingCapitalEmailLogs.value = false
  }
}

const openSendSummaryEmailDialog = () => {
  summaryEmailInput.value = ''
  isSendSummaryEmailDialogVisible.value = true
}

const submitSendSummaryEmail = async () => {
  isSendingSummaryEmail.value = true
  try {
    const res = await $api('/apps/branch-capitals/send-summary-email', {
      method: 'POST',
      body: { email: summaryEmailInput.value || undefined },
    })
    snackbar.show(res.message || 'Laporan rekap modal & ROI berhasil dikirim ke email Owner', 'success')
    isSendSummaryEmailDialogVisible.value = false
  } catch (error) {
    console.error(error)
    const errText = error.response?._data?.message || error.data?.message || error.message || 'Gagal mengirim rekap modal ke email'
    snackbar.show(errText, 'error')
  } finally {
    isSendingSummaryEmail.value = false
  }
}

const openSendCapitalEmailDialog = () => {
  capitalEmailInput.value = ''
  isSendCapitalEmailDialogVisible.value = true
}

const submitSendCapitalEmail = async () => {
  if (!selectedTransaction.value) return
  isSendingCapitalEmail.value = true
  try {
    const res = await $api(`/apps/branch-capitals/${selectedTransaction.value.id}/send-email`, {
      method: 'POST',
      body: { email: capitalEmailInput.value || undefined },
    })
    snackbar.show(res.message || 'Laporan setoran modal berhasil dikirim ke email', 'success')
    isSendCapitalEmailDialogVisible.value = false
    await fetchCapitalEmailLogs(selectedTransaction.value.id)
  } catch (error) {
    console.error(error)
    const errText = error.response?._data?.message || error.data?.message || error.message || 'Gagal mengirim email setoran modal'
    snackbar.show(errText, 'error')
    await fetchCapitalEmailLogs(selectedTransaction.value.id)
  } finally {
    isSendingCapitalEmail.value = false
  }
}

const retryCapitalEmail = async logId => {
  isRetryingCapitalEmail.value[logId] = true
  try {
    const res = await $api(`/apps/email-logs/${logId}/retry`, {
      method: 'POST',
    })
    snackbar.show(res.message || 'Email berhasil dikirim ulang', 'success')
    if (selectedTransaction.value) {
      await fetchCapitalEmailLogs(selectedTransaction.value.id)
    }
  } catch (error) {
    console.error(error)
    const errText = error.response?._data?.message || error.data?.message || error.message || 'Gagal mengirim ulang email'
    snackbar.show(errText, 'error')
    if (selectedTransaction.value) {
      await fetchCapitalEmailLogs(selectedTransaction.value.id)
    }
  } finally {
    isRetryingCapitalEmail.value[logId] = false
  }
}

// Preview Proof / Open PDF
const previewProof = item => {
  if (!item.proof_file) return
  if (item.proof_file.toLowerCase().endsWith('.pdf')) {
    window.open(`/storage/${item.proof_file}`, '_blank')
    return
  }
  previewImage.value = `/storage/${item.proof_file}`
  isPreviewDialogVisible.value = true
}

// Reset form helpers
const resetInflowForm = () => {
  inflowForm.value = {
    branch_id: branches.value[0]?.id || null,
    category: 'Modal Awal',
    amount: '',
    date: new Date().toISOString().substring(0, 10),
    payment_method: 'Transfer Bank',
    bank_name: '',
    account_number: '',
    account_name: '',
    proof_file: null,
    notes: '',
  }
}

const resetRequestForm = () => {
  requestForm.value = {
    branch_id: branches.value[0]?.id || null,
    category: 'Permintaan Tambahan Stok',
    amount: '',
    date: new Date().toISOString().substring(0, 10),
    proof_file: null,
    notes: '',
  }
}

const resetOutflowForm = () => {
  outflowForm.value = {
    branch_id: branches.value[0]?.id || null,
    category: 'Setoran Laba Closing Shift',
    amount: '',
    date: new Date().toISOString().substring(0, 10),
    payment_method: 'Transfer Bank',
    bank_name: '',
    account_number: '',
    account_name: '',
    proof_file: null,
    notes: '',
  }
}

onMounted(async () => {
  await fetchBranches()
  if (branches.value.length > 0) {
    inflowForm.value.branch_id = branches.value[0].id
    requestForm.value.branch_id = branches.value[0].id
    outflowForm.value.branch_id = branches.value[0].id
  }
  fetchSummary()
  fetchTransactions()
})
</script>

<template>
  <div class="pa-4">
    <!-- Header Banner -->
    <VCard elevation="2" class="mb-4 pa-4 rounded-xl border bg-var-theme-surface">
      <div class="d-flex flex-wrap align-center justify-space-between gap-4">
        <!-- Title & Subtitle -->
        <div class="d-flex align-center gap-3">
          <VAvatar color="primary" variant="tonal" rounded size="48">
            <VIcon icon="ri-hand-coin-line" size="28" />
          </VAvatar>
          <div>
            <h2 class="text-h5 font-weight-bold mb-0">
              Manajemen Modal & ROI Cabang
            </h2>
            <p class="text-caption text-medium-emphasis mb-0">
              Monitoring penyertaan modal usaha, permintaan modal tambahan, dan pengembalian modal cabang ke Owner
            </p>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex flex-wrap align-center gap-2">
          <!-- 1. Injeksi Langsung Owner -->
          <VBtn
            v-if="$can('approve', 'Modal & ROI Cabang') || $can('write', 'Modal & ROI Cabang') || $can('manage', 'all')"
            color="primary"
            variant="elevated"
            prepend-icon="ri-hand-coin-line"
            class="text-none font-weight-medium"
            @click="openInflowDrawer"
          >
            Injeksi Modal (Owner)
          </VBtn>

          <!-- 2. Permintaan Modal Cabang -->
          <VBtn
            v-if="$can('create', 'Modal & ROI Cabang') || $can('manage', 'all')"
            color="warning"
            variant="tonal"
            prepend-icon="ri-add-circle-line"
            class="text-none font-weight-medium"
            @click="openRequestDrawer"
          >
            Ajukan Permintaan Modal
          </VBtn>

          <!-- 3. Setor Pengembalian Modal -->
          <VBtn
            v-if="$can('create', 'Modal & ROI Cabang') || $can('manage', 'all')"
            color="success"
            variant="elevated"
            prepend-icon="ri-arrow-go-back-line"
            class="text-none font-weight-medium"
            @click="openOutflowDrawer"
          >
            Setor Pengembalian Modal
          </VBtn>

          <!-- 4. Kirim Rekap Modal ke Email Owner -->
          <VBtn
            color="primary"
            variant="tonal"
            prepend-icon="ri-mail-send-line"
            class="text-none font-weight-medium"
            @click="openSendSummaryEmailDialog"
          >
            Kirim Rekap Modal ke Email Owner
          </VBtn>
        </div>
      </div>
    </VCard>

    <!-- Top 4 Executive KPI Cards -->
    <VRow class="mb-4 match-height">
      <!-- 1. Total Modal Disuntikkan -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-primary h-100 d-flex flex-column justify-space-between" :loading="isSummaryLoading">
          <div>
            <div class="d-flex align-center justify-space-between">
              <span class="text-caption text-primary font-weight-bold text-uppercase">Total Modal Diberikan</span>
              <VAvatar color="primary" variant="tonal" rounded size="40">
                <VIcon icon="ri-hand-coin-line" size="22" />
              </VAvatar>
            </div>
            <div class="text-h5 font-weight-bold text-primary mt-2">
              {{ formatCurrency(summary.total_injected) }}
            </div>
          </div>
          <div class="d-flex align-center gap-1 mt-3 text-caption text-medium-emphasis">
            <VIcon icon="ri-shield-check-line" size="14" color="primary" class="me-1" />
            <span>Penyertaan modal riil Owner</span>
          </div>
        </VCard>
      </VCol>

      <!-- 2. Total Modal Dikembalikan -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-success h-100 d-flex flex-column justify-space-between" :loading="isSummaryLoading">
          <div>
            <div class="d-flex align-center justify-space-between">
              <span class="text-caption text-success font-weight-bold text-uppercase">Modal Dikembalikan</span>
              <VAvatar color="success" variant="tonal" rounded size="40">
                <VIcon icon="ri-arrow-left-right-line" size="22" />
              </VAvatar>
            </div>
            <div class="text-h5 font-weight-bold text-success mt-2">
              {{ formatCurrency(summary.total_returned) }}
            </div>
          </div>
          <div class="d-flex align-center justify-space-between mt-3 text-caption">
            <span class="text-medium-emphasis">Menunggu Approval:</span>
            <span class="font-weight-bold text-warning">{{ formatCurrency(summary.pending_returned) }}</span>
          </div>
        </VCard>
      </VCol>

      <!-- 3. Sisa Modal Tertanam (Outstanding) -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-warning h-100 d-flex flex-column justify-space-between" :loading="isSummaryLoading">
          <div>
            <div class="d-flex align-center justify-space-between">
              <span class="text-caption text-warning font-weight-bold text-uppercase">Sisa Modal Tertanam</span>
              <VAvatar color="warning" variant="tonal" rounded size="40">
                <VIcon icon="ri-wallet-3-line" size="22" />
              </VAvatar>
            </div>
            <div class="text-h5 font-weight-bold text-warning mt-2">
              {{ formatCurrency(summary.remaining_capital) }}
            </div>
          </div>
          <div class="d-flex align-center gap-1 mt-3 text-caption text-medium-emphasis">
            <span>Dana belum kembali ke kas Owner</span>
          </div>
        </VCard>
      </VCol>

      <!-- 4. Payback Progress Bar (% ROI) -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-info h-100 d-flex flex-column justify-space-between" :loading="isSummaryLoading">
          <div>
            <div class="d-flex align-center justify-space-between">
              <span class="text-caption text-info font-weight-bold text-uppercase">Progres Pengembalian</span>
              <VChip color="info" size="x-small" variant="tonal" class="font-weight-bold">
                ROI: {{ summary.payback_percentage }}%
              </VChip>
            </div>
            <div class="text-h5 font-weight-bold text-info mt-2">
              {{ summary.payback_percentage }}%
            </div>
          </div>
          <div class="mt-3">
            <VProgressLinear
              :model-value="summary.payback_percentage"
              color="info"
              height="8"
              rounded
              striped
            />
          </div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Branch Breakdown Cards (If multi-branch) -->
    <VCard v-if="summary?.branch_breakdown && summary.branch_breakdown.length > 1 && selectedBranch === 'all'" elevation="2" class="mb-4 pa-4 rounded-xl border">
      <div class="d-flex align-center gap-2 mb-3">
        <VIcon icon="ri-store-2-line" color="primary" size="20" />
        <span class="text-subtitle-2 font-weight-bold">Progres Pengembalian Modal per Cabang Toko</span>
      </div>
      <VRow>
        <VCol
          v-for="b in (summary?.branch_breakdown || [])"
          :key="b.branch_id"
          cols="12"
          sm="6"
          md="4"
        >
          <VCard variant="outlined" class="pa-3 rounded-lg bg-var-theme-background">
            <div class="d-flex align-center justify-space-between mb-1">
              <span class="font-weight-bold text-body-2">{{ b.branch_name }}</span>
              <VChip size="x-small" color="primary" variant="tonal" class="font-weight-bold">
                {{ b.payback_percentage }}% Balik
              </VChip>
            </div>
            <div class="d-flex align-center justify-space-between text-caption text-medium-emphasis mb-2">
              <span>Modal: <strong>{{ formatCurrency(b.total_injected) }}</strong></span>
              <span>Sisa: <strong class="text-warning">{{ formatCurrency(b.remaining_capital) }}</strong></span>
            </div>
            <VProgressLinear
              :model-value="b.payback_percentage"
              :color="b.payback_percentage >= 100 ? 'success' : 'primary'"
              height="6"
              rounded
            />
          </VCard>
        </VCol>
      </VRow>
    </VCard>

    <!-- Main Table Card -->
    <VCard elevation="2" class="rounded-xl border">
      <!-- Filter Bar -->
      <VCardText class="pa-4 border-b">
        <div class="d-flex flex-wrap align-center justify-space-between gap-3">
          <div class="d-flex flex-wrap align-center gap-3 flex-grow-1">
            <!-- Branch Filter -->
            <VAutocomplete
              v-model="selectedBranch"
              :items="[{ id: 'all', name: 'Semua Cabang Toko' }, ...branches]"
              item-title="name"
              item-value="id"
              density="compact"
              variant="outlined"
              label="Pilih Cabang"
              prepend-inner-icon="ri-store-2-line"
              style="min-width: 220px; max-width: 280px;"
              hide-details
            />

            <!-- Type Filter -->
            <VSelect
              v-model="selectedType"
              :items="[
                { value: 'all', title: 'Semua Jenis Mutasi' },
                { value: 'injection', title: 'Injeksi / Permintaan Modal' },
                { value: 'return', title: 'Pengembalian Modal' }
              ]"
              density="compact"
              variant="outlined"
              label="Jenis Mutasi"
              style="min-width: 210px; max-width: 260px;"
              hide-details
            />

            <!-- Status Filter -->
            <VSelect
              v-model="selectedStatus"
              :items="[
                { value: 'all', title: 'Semua Status' },
                { value: 'approved', title: 'Disetujui (Approved)' },
                { value: 'pending', title: 'Menunggu Approval' },
                { value: 'rejected', title: 'Ditolak / Dibatalkan' }
              ]"
              density="compact"
              variant="outlined"
              label="Status Transaksi"
              style="min-width: 200px; max-width: 240px;"
              hide-details
            />
          </div>

          <!-- Search Box -->
          <div style="min-width: 220px;">
            <VTextField
              v-model="search"
              placeholder="Cari referensi, catatan, bank..."
              density="compact"
              variant="outlined"
              prepend-inner-icon="ri-search-line"
              clearable
              hide-details
            />
          </div>
        </div>
      </VCardText>

      <!-- Table Body -->
      <VTable class="text-no-wrap" hover>
        <thead>
          <tr>
            <th class="text-uppercase font-weight-bold">No. Referensi</th>
            <th class="text-uppercase font-weight-bold">Tanggal</th>
            <th class="text-uppercase font-weight-bold">Cabang</th>
            <th class="text-uppercase font-weight-bold">Jenis & Kategori</th>
            <th class="text-uppercase font-weight-bold text-end">Nominal (Rp)</th>
            <th class="text-uppercase font-weight-bold">Metode & Rekening</th>
            <th class="text-uppercase font-weight-bold text-center">Bukti / PDF</th>
            <th class="text-uppercase font-weight-bold text-center">Status</th>
            <th class="text-uppercase font-weight-bold text-center" style="min-width: 140px;">Aksi</th>
          </tr>
        </thead>

        <tbody>
          <tr v-if="isLoading">
            <td colspan="9" class="text-center pa-6">
              <VProgressCircular indeterminate color="primary" size="32" class="me-2" />
              <span>Memuat riwayat transaksi modal...</span>
            </td>
          </tr>

          <tr v-else-if="transactions.length === 0">
            <td colspan="9" class="text-center pa-6 text-medium-emphasis">
              <VIcon icon="ri-inbox-line" size="36" class="d-block mx-auto mb-2 opacity-50" />
              <span>Belum ada riwayat transaksi modal cabang.</span>
            </td>
          </tr>

          <tr v-for="item in transactions" :key="item.id">
            <!-- No Ref -->
            <td>
              <span class="font-weight-medium text-body-2 cursor-pointer text-primary" @click="showDetail(item)">
                {{ item.reference_no }}
              </span>
              <div v-if="item.cash_shift_id" class="text-caption text-secondary">
                Shift Kasir #{{ item.cash_shift_id }}
              </div>
            </td>

            <!-- Date -->
            <td>{{ formatDate(item.date) }}</td>

            <!-- Branch -->
            <td>
              <span class="font-weight-medium">{{ item.branch?.name || '-' }}</span>
            </td>

            <!-- Type & Category -->
            <td>
              <div class="d-flex align-center gap-1">
                <VChip
                  size="x-small"
                  :color="item.type === 'injection' ? (item.status === 'pending' ? 'warning' : 'primary') : 'success'"
                  variant="tonal"
                  class="font-weight-bold"
                >
                  <VIcon :icon="item.type === 'injection' ? (item.status === 'pending' ? 'ri-time-line' : 'ri-download-2-line') : 'ri-upload-2-line'" size="12" class="me-1" />
                  {{ item.type === 'injection' ? (item.status === 'pending' ? 'Permintaan Modal' : 'Injeksi Modal') : 'Setor Modal' }}
                </VChip>
                <span class="text-caption text-medium-emphasis">{{ item.category }}</span>
              </div>
            </td>

            <!-- Amount -->
            <td class="text-end">
              <span :class="['font-weight-bold', item.type === 'injection' ? 'text-primary' : 'text-success']">
                {{ item.type === 'injection' ? '+' : '-' }} {{ formatCurrency(item.amount) }}
              </span>
            </td>

            <!-- Payment & Bank -->
            <td>
              <div class="text-body-2 font-weight-medium">{{ item.payment_method }}</div>
              <div v-if="item.bank_name" class="text-caption text-medium-emphasis">
                {{ item.bank_name }} - {{ item.account_number || '-' }} ({{ item.account_name || '-' }})
              </div>
            </td>

            <!-- Proof File / PDF Proposal -->
            <td class="text-center">
              <VBtn
                v-if="item.proof_file"
                :icon="item.proof_file.toLowerCase().endsWith('.pdf') ? 'ri-file-pdf-2-line' : 'ri-image-line'"
                size="x-small"
                variant="tonal"
                :color="item.proof_file.toLowerCase().endsWith('.pdf') ? 'error' : 'info'"
                :title="item.proof_file.toLowerCase().endsWith('.pdf') ? 'Buka Dokumen PDF (Proposal/Lampiran)' : 'Pratinjau Gambar'"
                @click="previewProof(item)"
              />
              <span v-else class="text-caption text-medium-emphasis">-</span>
            </td>

            <!-- Status -->
            <td class="text-center">
              <VChip
                size="small"
                :color="item.status === 'approved' ? 'success' : (item.status === 'pending' ? (item.type === 'injection' ? 'warning' : 'amber') : 'error')"
                variant="tonal"
                class="font-weight-bold"
              >
                {{ item.status === 'approved' ? 'Disetujui' : (item.status === 'pending' ? (item.type === 'injection' ? 'Menunggu Injeksi' : 'Menunggu Setoran') : 'Ditolak / Batal') }}
              </VChip>
              <div v-if="item.approved_by && item.status === 'approved'" class="text-caption text-medium-emphasis">
                Oleh: {{ item.approved_by?.name }}
              </div>
            </td>

            <!-- Actions -->
            <td class="text-center">
              <div class="d-flex align-center justify-center gap-1">
                <!-- Detail (Eye icon) -->
                <IconBtn
                  v-if="$can('read', 'Modal & ROI Cabang') || $can('manage', 'all')"
                  size="small"
                  color="info"
                  title="Lihat Detail Transaksi & Audit"
                  @click="showDetail(item)"
                >
                  <VIcon icon="ri-eye-line" />
                </IconBtn>

                <!-- Approve Button (If pending) -->
                <VBtn
                  v-if="item.status === 'pending' && ($can('approve', 'Modal & ROI Cabang') || $can('manage', 'all'))"
                  size="x-small"
                  color="success"
                  class="px-2 font-weight-bold"
                  @click="confirmApprove(item)"
                >
                  Approve
                </VBtn>

                <!-- Reject Button (If pending) -->
                <VBtn
                  v-if="item.status === 'pending' && ($can('approve', 'Modal & ROI Cabang') || $can('manage', 'all'))"
                  size="x-small"
                  color="error"
                  variant="tonal"
                  class="px-2"
                  @click="confirmReject(item)"
                >
                  Tolak
                </VBtn>

                <!-- Edit Button -->
                <IconBtn
                  v-if="$can('write', 'Modal & ROI Cabang') || $can('manage', 'all')"
                  size="small"
                  color="primary"
                  title="Edit Transaksi"
                  @click="openEditDrawer(item)"
                >
                  <VIcon icon="ri-edit-box-line" />
                </IconBtn>

                <!-- Void Button (If approved) -->
                <IconBtn
                  v-if="item.status === 'approved' && ($can('approve', 'Modal & ROI Cabang') || $can('write', 'Modal & ROI Cabang') || $can('manage', 'all'))"
                  size="small"
                  color="warning"
                  title="Batalkan Persetujuan (Void)"
                  @click="confirmVoid(item)"
                >
                  <VIcon icon="ri-arrow-go-forward-line" />
                </IconBtn>

                <!-- Delete Button -->
                <IconBtn
                  v-if="$can('delete', 'Modal & ROI Cabang') || $can('manage', 'all')"
                  size="small"
                  color="error"
                  title="Hapus Transaksi"
                  @click="confirmDelete(item)"
                >
                  <VIcon icon="ri-delete-bin-line" />
                </IconBtn>
              </div>
            </td>
          </tr>
        </tbody>
      </VTable>

      <!-- Pagination -->
      <VCardText class="d-flex align-center justify-space-between pa-4 border-t">
        <span class="text-caption text-medium-emphasis">
          Menampilkan {{ (page - 1) * itemsPerPage + 1 }} - {{ Math.min(page * itemsPerPage, totalItems) }} dari {{ totalItems }} data
        </span>
        <VPagination
          v-model="page"
          :length="Math.ceil(totalItems / itemsPerPage) || 1"
          total-visible="5"
          density="compact"
          size="small"
        />
      </VCardText>
    </VCard>

    <!-- DRAWER 1: INJEKSI MODAL LANGSUNG (OWNER -> CABANG) -->
    <VNavigationDrawer
      v-model="isInflowDrawerOpen"
      temporary
      location="end"
      width="450"
      class="scrollable-content"
    >
      <div class="pa-4 border-b d-flex align-center justify-space-between bg-var-theme-surface">
        <div class="d-flex align-center gap-2">
          <VAvatar color="primary" variant="tonal" size="36">
            <VIcon icon="ri-hand-coin-line" size="20" />
          </VAvatar>
          <span class="text-subtitle-1 font-weight-bold">Injeksi Modal Langsung (Owner &rarr; Cabang)</span>
        </div>
        <VBtn icon="ri-close-line" variant="text" density="compact" @click="isInflowDrawerOpen = false" />
      </div>

      <PerfectScrollbar :options="{ wheelPropagation: false }" style="max-height: calc(100vh - 75px); overflow-y: auto;">
        <div class="pa-4">
          <VForm @submit.prevent="handleInflowSubmit">
            <VRow>
              <!-- Cabang Tujuan -->
              <VCol cols="12">
                <VSelect
                  v-model="inflowForm.branch_id"
                  :items="branches"
                  item-title="name"
                  item-value="id"
                  label="Cabang Penerima Modal *"
                  density="compact"
                  variant="outlined"
                />
              </VCol>

              <!-- Kategori Modal -->
              <VCol cols="12">
                <VSelect
                  v-model="inflowForm.category"
                  :items="['Modal Awal', 'Modal Tambahan Stok', 'Modal Aset & Renovasi', 'Modal Kerja Operasional', 'Dana Talangan Darurat']"
                  label="Kategori / Peruntukan Modal *"
                  density="compact"
                  variant="outlined"
                />
              </VCol>

              <!-- Nominal Modal -->
              <VCol cols="12">
                <VTextField
                  :model-value="formatInputRupiah(inflowForm.amount)"
                  label="Nominal Modal (Rp) *"
                  prefix="Rp"
                  placeholder="50.000.000"
                  density="compact"
                  variant="outlined"
                  hint="Ketik angka, otomatis terformat ribuan Rupiah"
                  persistent-hint
                  @update:model-value="val => inflowForm.amount = formatInputRupiah(val)"
                />
                <!-- Quick Suggestion Chips -->
                <div class="d-flex flex-wrap gap-1 mt-2">
                  <VChip
                    size="x-small"
                    variant="tonal"
                    color="primary"
                    class="cursor-pointer font-weight-medium"
                    @click="inflowForm.amount = '10.000.000'"
                  >
                    +10 Jt
                  </VChip>
                  <VChip
                    size="x-small"
                    variant="tonal"
                    color="primary"
                    class="cursor-pointer font-weight-medium"
                    @click="inflowForm.amount = '25.000.000'"
                  >
                    +25 Jt
                  </VChip>
                  <VChip
                    size="x-small"
                    variant="tonal"
                    color="primary"
                    class="cursor-pointer font-weight-medium"
                    @click="inflowForm.amount = '50.000.000'"
                  >
                    +50 Jt
                  </VChip>
                  <VChip
                    size="x-small"
                    variant="tonal"
                    color="primary"
                    class="cursor-pointer font-weight-medium"
                    @click="inflowForm.amount = '100.000.000'"
                  >
                    +100 Jt
                  </VChip>
                </div>
              </VCol>

              <!-- Tanggal Injeksi -->
              <VCol cols="12">
                <VTextField
                  v-model="inflowForm.date"
                  label="Tanggal Transaksi *"
                  type="date"
                  density="compact"
                  variant="outlined"
                />
              </VCol>

              <!-- Metode Pembayaran -->
              <VCol cols="12">
                <VSelect
                  v-model="inflowForm.payment_method"
                  :items="['Transfer Bank', 'Kas Tunai', 'Cek / Bilyet Giro']"
                  label="Metode Penyaluran Modal *"
                  density="compact"
                  variant="outlined"
                />
              </VCol>

              <!-- Bank & Rekening Sumber -->
              <VCol cols="12" v-if="inflowForm.payment_method === 'Transfer Bank'">
                <VTextField
                  v-model="inflowForm.bank_name"
                  label="Nama Bank Pengirim (Owner)"
                  placeholder="BCA / Mandiri / BRI"
                  density="compact"
                  variant="outlined"
                  class="mb-2"
                />
                <VTextField
                  v-model="inflowForm.account_number"
                  label="Nomor Rekening Pengirim"
                  placeholder="1234567890"
                  density="compact"
                  variant="outlined"
                  class="mb-2"
                />
                <VTextField
                  v-model="inflowForm.account_name"
                  label="Atas Nama Rekening"
                  placeholder="PT / Nama Owner"
                  density="compact"
                  variant="outlined"
                />
              </VCol>

              <!-- Bukti Transfer -->
              <VCol cols="12">
                <VFileInput
                  v-model="inflowForm.proof_file"
                  label="Lampirkan Bukti Transfer / Nota"
                  density="compact"
                  variant="outlined"
                  prepend-icon=""
                  prepend-inner-icon="ri-attachment-line"
                  accept="image/*,application/pdf"
                />
              </VCol>

              <!-- Catatan -->
              <VCol cols="12">
                <VTextarea
                  v-model="inflowForm.notes"
                  label="Catatan / Keterangan"
                  placeholder="Contoh: Modal pembukaan gerai baru dan pengadaan stok awal..."
                  density="compact"
                  variant="outlined"
                  rows="3"
                />
              </VCol>

              <!-- Submit Button -->
              <VCol cols="12" class="d-flex gap-2 justify-end mt-2">
                <VBtn variant="outlined" color="secondary" @click="isInflowDrawerOpen = false">
                  Batal
                </VBtn>
                <VBtn color="primary" type="submit" :loading="isSubmitting">
                  Simpan Injeksi Modal
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </div>
      </PerfectScrollbar>
    </VNavigationDrawer>

    <!-- DRAWER 2: AJUKAN PERMINTAAN MODAL TAMBAHAN (CABANG -> OWNER) -->
    <VNavigationDrawer
      v-model="isRequestDrawerOpen"
      temporary
      location="end"
      width="450"
      class="scrollable-content"
    >
      <div class="pa-4 border-b d-flex align-center justify-space-between bg-var-theme-surface">
        <div class="d-flex align-center gap-2">
          <VAvatar color="warning" variant="tonal" size="36">
            <VIcon icon="ri-hand-heart-line" size="20" />
          </VAvatar>
          <span class="text-subtitle-1 font-weight-bold">Ajukan Permintaan Modal (Cabang &rarr; Owner)</span>
        </div>
        <VBtn icon="ri-close-line" variant="text" density="compact" @click="isRequestDrawerOpen = false" />
      </div>

      <PerfectScrollbar :options="{ wheelPropagation: false }" style="max-height: calc(100vh - 75px); overflow-y: auto;">
        <div class="pa-4">
          <VForm @submit.prevent="handleRequestSubmit">
            <VRow>
              <!-- Cabang Pemohon -->
              <VCol cols="12">
                <VSelect
                  v-model="requestForm.branch_id"
                  :items="branches"
                  item-title="name"
                  item-value="id"
                  label="Cabang Pemohon Modal *"
                  density="compact"
                  variant="outlined"
                />
              </VCol>

              <!-- Kategori Permintaan -->
              <VCol cols="12">
                <VSelect
                  v-model="requestForm.category"
                  :items="['Permintaan Tambahan Stok', 'Pengadaan Aset & Renovasi', 'Kas Darurat Operasional', 'Kebutuhan Operasional Lainnya']"
                  label="Tujuan / Kategori Kebutuhan *"
                  density="compact"
                  variant="outlined"
                />
              </VCol>

              <!-- Nominal Permintaan -->
              <VCol cols="12">
                <VTextField
                  :model-value="formatInputRupiah(requestForm.amount)"
                  label="Nominal Modal yang Diajukan (Rp) *"
                  prefix="Rp"
                  placeholder="20.000.000"
                  density="compact"
                  variant="outlined"
                  hint="Ketik nominal yang dibutuhkan toko"
                  persistent-hint
                  @update:model-value="val => requestForm.amount = formatInputRupiah(val)"
                />
                <!-- Quick Suggestion Chips -->
                <div class="d-flex flex-wrap gap-1 mt-2">
                  <VChip
                    size="x-small"
                    variant="tonal"
                    color="warning"
                    class="cursor-pointer font-weight-medium"
                    @click="requestForm.amount = '5.000.000'"
                  >
                    +5 Jt
                  </VChip>
                  <VChip
                    size="x-small"
                    variant="tonal"
                    color="warning"
                    class="cursor-pointer font-weight-medium"
                    @click="requestForm.amount = '10.000.000'"
                  >
                    +10 Jt
                  </VChip>
                  <VChip
                    size="x-small"
                    variant="tonal"
                    color="warning"
                    class="cursor-pointer font-weight-medium"
                    @click="requestForm.amount = '20.000.000'"
                  >
                    +20 Jt
                  </VChip>
                  <VChip
                    size="x-small"
                    variant="tonal"
                    color="warning"
                    class="cursor-pointer font-weight-medium"
                    @click="requestForm.amount = '50.000.000'"
                  >
                    +50 Jt
                  </VChip>
                </div>
              </VCol>

              <!-- Tanggal Pengajuan -->
              <VCol cols="12">
                <VTextField
                  v-model="requestForm.date"
                  label="Tanggal Pengajuan *"
                  type="date"
                  density="compact"
                  variant="outlined"
                />
              </VCol>

              <!-- Upload Proposal / Surat Permohonan (PDF / Gambar) -->
              <VCol cols="12">
                <VFileInput
                  v-model="requestForm.proof_file"
                  label="Upload Proposal / Surat Permohonan (PDF / Gambar)"
                  density="compact"
                  variant="outlined"
                  prepend-icon=""
                  prepend-inner-icon="ri-file-pdf-line"
                  accept="application/pdf,image/*"
                  hint="Opsional: Upload dokumen PDF Proposal/RAB atau foto surat resmi"
                  persistent-hint
                />
              </VCol>

              <!-- Justifikasi Kebutuhan -->
              <VCol cols="12">
                <VTextarea
                  v-model="requestForm.notes"
                  label="Alasan / Justifikasi Kebutuhan Modal *"
                  placeholder="Jelaskan secara detail peruntukan dana modal tambahan ini (contoh: restock 50 unit barang X menjelang promo bulanan)..."
                  density="compact"
                  variant="outlined"
                  rows="4"
                />
              </VCol>

              <!-- Submit Button -->
              <VCol cols="12" class="d-flex gap-2 justify-end mt-2">
                <VBtn variant="outlined" color="secondary" @click="isRequestDrawerOpen = false">
                  Batal
                </VBtn>
                <VBtn color="warning" type="submit" :loading="isSubmitting">
                  Ajukan ke Owner
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </div>
      </PerfectScrollbar>
    </VNavigationDrawer>

    <!-- DRAWER 3: SETOR PENGEMBALIAN MODAL (CABANG -> OWNER) -->
    <VNavigationDrawer
      v-model="isOutflowDrawerOpen"
      temporary
      location="end"
      width="450"
      class="scrollable-content"
    >
      <div class="pa-4 border-b d-flex align-center justify-space-between bg-var-theme-surface">
        <div class="d-flex align-center gap-2">
          <VAvatar color="success" variant="tonal" size="36">
            <VIcon icon="ri-arrow-go-back-line" size="20" />
          </VAvatar>
          <span class="text-subtitle-1 font-weight-bold">Setor Pengembalian Modal (Cabang &rarr; Owner)</span>
        </div>
        <VBtn icon="ri-close-line" variant="text" density="compact" @click="isOutflowDrawerOpen = false" />
      </div>

      <PerfectScrollbar :options="{ wheelPropagation: false }" style="max-height: calc(100vh - 75px); overflow-y: auto;">
        <div class="pa-4">
          <VForm @submit.prevent="handleOutflowSubmit">
            <VRow>
              <!-- Cabang Asal -->
              <VCol cols="12">
                <VSelect
                  v-model="outflowForm.branch_id"
                  :items="branches"
                  item-title="name"
                  item-value="id"
                  label="Cabang Pengirim Setoran *"
                  density="compact"
                  variant="outlined"
                />
              </VCol>

              <!-- Kategori Setoran -->
              <VCol cols="12">
                <VSelect
                  v-model="outflowForm.category"
                  :items="['Setoran Laba Closing Shift', 'Cicilan Pengembalian Modal', 'Pelunasan Sisa Modal Toko', 'Bagi Hasil / Dividen Keuntungan']"
                  label="Kategori Setoran *"
                  density="compact"
                  variant="outlined"
                />
              </VCol>

              <!-- Nominal Setoran -->
              <VCol cols="12">
                <VTextField
                  :model-value="formatInputRupiah(outflowForm.amount)"
                  label="Nominal Setoran (Rp) *"
                  prefix="Rp"
                  placeholder="10.000.000"
                  density="compact"
                  variant="outlined"
                  hint="Ketik angka, otomatis terformat ribuan Rupiah"
                  persistent-hint
                  @update:model-value="val => outflowForm.amount = formatInputRupiah(val)"
                />
                <!-- Quick Suggestion Chips -->
                <div class="d-flex flex-wrap gap-1 mt-2">
                  <VChip
                    size="x-small"
                    variant="tonal"
                    color="success"
                    class="cursor-pointer font-weight-medium"
                    @click="outflowForm.amount = '5.000.000'"
                  >
                    +5 Jt
                  </VChip>
                  <VChip
                    size="x-small"
                    variant="tonal"
                    color="success"
                    class="cursor-pointer font-weight-medium"
                    @click="outflowForm.amount = '10.000.000'"
                  >
                    +10 Jt
                  </VChip>
                  <VChip
                    size="x-small"
                    variant="tonal"
                    color="success"
                    class="cursor-pointer font-weight-medium"
                    @click="outflowForm.amount = '20.000.000'"
                  >
                    +20 Jt
                  </VChip>
                  <VChip
                    size="x-small"
                    variant="tonal"
                    color="success"
                    class="cursor-pointer font-weight-medium"
                    @click="outflowForm.amount = '50.000.000'"
                  >
                    +50 Jt
                  </VChip>
                </div>
              </VCol>

              <!-- Tanggal Setor -->
              <VCol cols="12">
                <VTextField
                  v-model="outflowForm.date"
                  label="Tanggal Setor *"
                  type="date"
                  density="compact"
                  variant="outlined"
                />
              </VCol>

              <!-- Metode Pembayaran -->
              <VCol cols="12">
                <VSelect
                  v-model="outflowForm.payment_method"
                  :items="['Transfer Bank', 'Kas Tunai']"
                  label="Metode Setoran *"
                  density="compact"
                  variant="outlined"
                />
              </VCol>

              <!-- Bank & Rekening Tujuan Owner -->
              <VCol cols="12" v-if="outflowForm.payment_method === 'Transfer Bank'">
                <VTextField
                  v-model="outflowForm.bank_name"
                  label="Bank Tujuan Owner"
                  placeholder="BCA / Mandiri"
                  density="compact"
                  variant="outlined"
                  class="mb-2"
                />
                <VTextField
                  v-model="outflowForm.account_number"
                  label="No. Rekening Tujuan Owner"
                  placeholder="1234567890"
                  density="compact"
                  variant="outlined"
                  class="mb-2"
                />
                <VTextField
                  v-model="outflowForm.account_name"
                  label="Atas Nama Rekening Owner"
                  placeholder="Nama Pemilik Rekening"
                  density="compact"
                  variant="outlined"
                />
              </VCol>

              <!-- Bukti Transfer -->
              <VCol cols="12">
                <VFileInput
                  v-model="outflowForm.proof_file"
                  label="Lampirkan Struk / Bukti Transfer Bank"
                  density="compact"
                  variant="outlined"
                  prepend-icon=""
                  prepend-inner-icon="ri-attachment-line"
                  accept="image/*,application/pdf"
                />
              </VCol>

              <!-- Catatan -->
              <VCol cols="12">
                <VTextarea
                  v-model="outflowForm.notes"
                  label="Catatan / Keterangan"
                  placeholder="Contoh: Setoran cicilan modal tahap 1 dari surplus kas toko..."
                  density="compact"
                  variant="outlined"
                  rows="3"
                />
              </VCol>

              <!-- Submit Button -->
              <VCol cols="12" class="d-flex gap-2 justify-end mt-2">
                <VBtn variant="outlined" color="secondary" @click="isOutflowDrawerOpen = false">
                  Batal
                </VBtn>
                <VBtn color="success" type="submit" :loading="isSubmitting">
                  Ajukan Pengembalian Modal
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </div>
      </PerfectScrollbar>
    </VNavigationDrawer>

    <!-- DRAWER 4: EDIT TRANSAKSI MODAL -->
    <VNavigationDrawer
      v-model="isEditDrawerOpen"
      temporary
      location="end"
      width="450"
      class="scrollable-content"
    >
      <div class="pa-4 border-b d-flex align-center justify-space-between bg-var-theme-surface">
        <div class="d-flex align-center gap-2">
          <VAvatar color="primary" variant="tonal" size="36">
            <VIcon icon="ri-edit-box-line" size="20" />
          </VAvatar>
          <span class="text-subtitle-1 font-weight-bold">Edit Data Transaksi Modal</span>
        </div>
        <VBtn icon="ri-close-line" variant="text" density="compact" @click="isEditDrawerOpen = false" />
      </div>

      <PerfectScrollbar :options="{ wheelPropagation: false }" style="max-height: calc(100vh - 75px); overflow-y: auto;">
        <div class="pa-4">
          <VForm @submit.prevent="handleEditSubmit">
            <VRow>
              <!-- Cabang -->
              <VCol cols="12">
                <VSelect
                  v-model="editForm.branch_id"
                  :items="branches"
                  item-title="name"
                  item-value="id"
                  label="Cabang Toko *"
                  density="compact"
                  variant="outlined"
                />
              </VCol>

              <!-- Tipe & Kategori -->
              <VCol cols="12">
                <VSelect
                  v-model="editForm.type"
                  :items="[
                    { value: 'injection', title: 'Injeksi / Penambahan Modal' },
                    { value: 'return', title: 'Pengembalian Modal' }
                  ]"
                  label="Jenis Mutasi *"
                  density="compact"
                  variant="outlined"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="editForm.category"
                  label="Kategori / Peruntukan *"
                  density="compact"
                  variant="outlined"
                />
              </VCol>

              <!-- Nominal -->
              <VCol cols="12">
                <VTextField
                  :model-value="formatInputRupiah(editForm.amount)"
                  label="Nominal (Rp) *"
                  prefix="Rp"
                  density="compact"
                  variant="outlined"
                  @update:model-value="val => editForm.amount = formatInputRupiah(val)"
                />
              </VCol>

              <!-- Tanggal -->
              <VCol cols="12">
                <VTextField
                  v-model="editForm.date"
                  label="Tanggal Transaksi *"
                  type="date"
                  density="compact"
                  variant="outlined"
                />
              </VCol>

              <!-- Metode & Bank -->
              <VCol cols="12">
                <VSelect
                  v-model="editForm.payment_method"
                  :items="['Transfer Bank', 'Kas Tunai', 'Cek / Bilyet Giro']"
                  label="Metode Pembayaran *"
                  density="compact"
                  variant="outlined"
                />
              </VCol>

              <VCol cols="12" v-if="editForm.payment_method === 'Transfer Bank'">
                <VTextField
                  v-model="editForm.bank_name"
                  label="Nama Bank"
                  density="compact"
                  variant="outlined"
                  class="mb-2"
                />
                <VTextField
                  v-model="editForm.account_number"
                  label="Nomor Rekening"
                  density="compact"
                  variant="outlined"
                  class="mb-2"
                />
                <VTextField
                  v-model="editForm.account_name"
                  label="Atas Nama Rekening"
                  density="compact"
                  variant="outlined"
                />
              </VCol>

              <!-- Bukti Transfer / Proposal (Ganti File) -->
              <VCol cols="12">
                <div v-if="editForm.current_proof" class="mb-2 text-caption">
                  <span>File Bukti / Proposal Saat Ini:</span>
                  <a :href="'/storage/' + editForm.current_proof" target="_blank" class="text-primary font-weight-medium ms-1">
                    Lihat Dokumen
                  </a>
                </div>
                <VFileInput
                  v-model="editForm.proof_file"
                  label="Ganti File Bukti / Dokumen (Opsional)"
                  density="compact"
                  variant="outlined"
                  prepend-icon=""
                  prepend-inner-icon="ri-attachment-line"
                  accept="image/*,application/pdf"
                />
              </VCol>

              <!-- Catatan -->
              <VCol cols="12">
                <VTextarea
                  v-model="editForm.notes"
                  label="Catatan / Keterangan"
                  density="compact"
                  variant="outlined"
                  rows="3"
                />
              </VCol>

              <!-- Action Buttons -->
              <VCol cols="12" class="d-flex gap-2 justify-end mt-2">
                <VBtn variant="outlined" color="secondary" @click="isEditDrawerOpen = false">
                  Batal
                </VBtn>
                <VBtn color="primary" type="submit" :loading="isSubmitting">
                  Simpan Perubahan
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </div>
      </PerfectScrollbar>
    </VNavigationDrawer>

    <!-- DIALOG 1: APPROVAL MODAL (RETURN / INJEKSI DENGAN BUKTI DANA) -->
    <VDialog v-model="isApproveDialogVisible" max-width="500">
      <VCard class="pa-4 rounded-xl">
        <div class="d-flex align-center gap-2 mb-3">
          <VAvatar color="success" variant="tonal" size="36">
            <VIcon icon="ri-checkbox-circle-line" size="22" />
          </VAvatar>
          <span class="text-h6 font-weight-bold">
            {{ selectedTransaction?.type === 'injection' ? 'Setujui & Salurkan Modal Cabang' : 'Persetujuan Setoran Pengembalian Modal' }}
          </span>
        </div>

        <VCardText class="pa-0 mb-4">
          <p class="text-body-2 mb-3">
            Apakah Anda yakin ingin menyetujui transaksi berikut?
          </p>

          <VCard variant="outlined" class="pa-3 rounded-lg mb-3 bg-var-theme-background">
            <div class="d-flex justify-space-between text-body-2 mb-1">
              <span class="text-medium-emphasis">No. Referensi:</span>
              <span class="font-weight-medium">{{ selectedTransaction?.reference_no }}</span>
            </div>
            <div class="d-flex justify-space-between text-body-2 mb-1">
              <span class="text-medium-emphasis">Cabang:</span>
              <span class="font-weight-medium">{{ selectedTransaction?.branch?.name }}</span>
            </div>
            <div class="d-flex justify-space-between text-body-2">
              <span class="text-medium-emphasis">Nominal:</span>
              <span class="font-weight-bold text-primary">{{ formatCurrency(selectedTransaction?.amount) }}</span>
            </div>
          </VCard>

          <!-- Extra Input for Injection Request Funding -->
          <div v-if="selectedTransaction?.type === 'injection'">
            <div class="text-subtitle-2 font-weight-bold mb-2">Informasi Transfer Penyaluran Owner (Opsional):</div>
            <VTextField
              v-model="approveForm.bank_name"
              label="Bank Pengirim Owner"
              placeholder="BCA / Mandiri"
              density="compact"
              variant="outlined"
              class="mb-2"
            />
            <VTextField
              v-model="approveForm.account_number"
              label="No. Rekening Pengirim"
              density="compact"
              variant="outlined"
              class="mb-2"
            />
            <VFileInput
              v-model="approveForm.proof_file"
              label="Lampirkan Struk Transfer Penyaluran Dana"
              density="compact"
              variant="outlined"
              prepend-icon=""
              prepend-inner-icon="ri-attachment-line"
              accept="image/*,application/pdf"
            />
          </div>
        </VCardText>

        <div class="d-flex justify-end gap-2">
          <VBtn variant="outlined" color="secondary" @click="isApproveDialogVisible = false">
            Batal
          </VBtn>
          <VBtn color="success" :loading="isSubmitting" @click="handleApprove">
            Ya, Setujui Sekarang
          </VBtn>
        </div>
      </VCard>
    </VDialog>

    <!-- DIALOG 2: TOLAK TRANSAKSI MODAL -->
    <VDialog v-model="isRejectDialogVisible" max-width="500">
      <VCard class="pa-4 rounded-xl">
        <div class="d-flex align-center gap-2 mb-3">
          <VAvatar color="error" variant="tonal" size="36">
            <VIcon icon="ri-close-circle-line" size="22" />
          </VAvatar>
          <span class="text-h6 font-weight-bold">Tolak Transaksi Modal</span>
        </div>

        <p class="text-body-2 mb-3">
          Apakah Anda yakin ingin menolak transaksi <strong>{{ selectedTransaction?.reference_no }}</strong>?
        </p>

        <VTextarea
          v-model="rejectReason"
          label="Alasan Penolakan *"
          placeholder="Tuliskan alasan penolakan secara jelas (contoh: proposal kurang lengkap, mutasi kas belum masuk)..."
          density="compact"
          variant="outlined"
          rows="3"
          class="mb-4"
        />

        <div class="d-flex justify-end gap-2">
          <VBtn variant="outlined" color="secondary" @click="isRejectDialogVisible = false">
            Batal
          </VBtn>
          <VBtn color="error" :loading="isSubmitting" @click="handleReject">
            Tolak Transaksi
          </VBtn>
        </div>
      </VCard>
    </VDialog>

    <!-- DIALOG 3: VOID / BATALKAN PERSETUJUAN (APPROVED -> VOID) -->
    <VDialog v-model="isVoidDialogVisible" max-width="500">
      <VCard class="pa-4 rounded-xl">
        <div class="d-flex align-center gap-2 mb-3">
          <VAvatar color="warning" variant="tonal" size="36">
            <VIcon icon="ri-arrow-go-forward-line" size="22" />
          </VAvatar>
          <span class="text-h6 font-weight-bold">Batalkan Persetujuan (Void)</span>
        </div>

        <p class="text-body-2 text-warning mb-3">
          <strong>Perhatian:</strong> Membatalkan persetujuan akan mengoreksi kembali total modal & sisa modal cabang.
        </p>

        <VCard variant="outlined" class="pa-3 rounded-lg mb-3 bg-var-theme-background">
          <div class="d-flex justify-space-between text-body-2 mb-1">
            <span class="text-medium-emphasis">No. Referensi:</span>
            <span class="font-weight-medium">{{ selectedTransaction?.reference_no }}</span>
          </div>
          <div class="d-flex justify-space-between text-body-2">
            <span class="text-medium-emphasis">Nominal:</span>
            <span class="font-weight-bold">{{ formatCurrency(selectedTransaction?.amount) }}</span>
          </div>
        </VCard>

        <VTextarea
          v-model="voidReason"
          label="Alasan Pembatalan Persetujuan (Void) *"
          placeholder="Jelaskan alasan pembatalan (contoh: salah input nominal oleh kasir, mutasi bank tidak valid)..."
          density="compact"
          variant="outlined"
          rows="3"
          class="mb-4"
        />

        <div class="d-flex justify-end gap-2">
          <VBtn variant="outlined" color="secondary" @click="isVoidDialogVisible = false">
            Kembali
          </VBtn>
          <VBtn color="warning" :loading="isSubmitting" @click="handleVoid">
            Batalkan Persetujuan
          </VBtn>
        </div>
      </VCard>
    </VDialog>

    <!-- DIALOG 4: KONFIRMASI HAPUS TRANSAKSI -->
    <VDialog v-model="isDeleteDialogVisible" max-width="450">
      <VCard class="pa-4 rounded-xl">
        <div class="d-flex align-center gap-2 mb-3">
          <VAvatar color="error" variant="tonal" size="36">
            <VIcon icon="ri-delete-bin-line" size="22" />
          </VAvatar>
          <span class="text-h6 font-weight-bold">Hapus Transaksi Modal</span>
        </div>

        <p class="text-body-2 mb-4">
          Apakah Anda yakin ingin menghapus permanen transaksi <strong>{{ selectedTransaction?.reference_no }}</strong> senilai <strong>{{ formatCurrency(selectedTransaction?.amount) }}</strong>?
        </p>

        <div class="d-flex justify-end gap-2">
          <VBtn variant="outlined" color="secondary" @click="isDeleteDialogVisible = false">
            Batal
          </VBtn>
          <VBtn color="error" :loading="isSubmitting" @click="handleDelete">
            Ya, Hapus Permanen
          </VBtn>
        </div>
      </VCard>
    </VDialog>

    <!-- DIALOG 5: DETAIL TRANSAKSI & AUDIT LOG -->
    <VDialog v-model="isDetailDialogVisible" max-width="600">
      <VCard class="pa-4 rounded-xl">
        <div class="d-flex align-center justify-space-between mb-3 border-b pb-3">
          <div class="d-flex align-center gap-2">
            <VAvatar :color="selectedTransaction?.type === 'injection' ? 'primary' : 'success'" variant="tonal" size="36">
              <VIcon :icon="selectedTransaction?.type === 'injection' ? 'ri-download-2-line' : 'ri-upload-2-line'" size="20" />
            </VAvatar>
            <div>
              <span class="text-subtitle-1 font-weight-bold d-block">{{ selectedTransaction?.reference_no }}</span>
              <span class="text-caption text-medium-emphasis">Detail Transaksi Modal & Riwayat Audit</span>
            </div>
          </div>
          <div class="d-flex align-center gap-2">
            <VBtn
              size="small"
              color="info"
              variant="flat"
              class="font-weight-bold"
              prepend-icon="ri-mail-send-line"
              @click="openSendCapitalEmailDialog"
            >
              Kirim Bukti ke Email
            </VBtn>
            <VBtn icon="ri-close-line" variant="text" density="compact" @click="isDetailDialogVisible = false" />
          </div>
        </div>

        <div v-if="selectedTransaction">
          <!-- Summary Header in Dialog -->
          <div class="d-flex align-center justify-space-between pa-3 rounded-lg bg-var-theme-background mb-3">
            <div>
              <span class="text-caption text-medium-emphasis d-block">Nominal Transaksi</span>
              <span :class="['text-h6 font-weight-bold', selectedTransaction.type === 'injection' ? 'text-primary' : 'text-success']">
                {{ selectedTransaction.type === 'injection' ? '+' : '-' }} {{ formatCurrency(selectedTransaction.amount) }}
              </span>
            </div>
            <VChip
              :color="selectedTransaction.status === 'approved' ? 'success' : (selectedTransaction.status === 'pending' ? 'warning' : 'error')"
              variant="tonal"
              class="font-weight-bold"
            >
              {{ selectedTransaction.status === 'approved' ? 'Disetujui' : (selectedTransaction.status === 'pending' ? 'Menunggu Approval' : 'Ditolak / Batal') }}
            </VChip>
          </div>

          <!-- Transaction Fields Grid -->
          <VRow class="mb-3 text-body-2">
            <VCol cols="6" class="py-1">
              <span class="text-medium-emphasis d-block">Cabang:</span>
              <strong>{{ selectedTransaction.branch?.name || '-' }}</strong>
            </VCol>
            <VCol cols="6" class="py-1">
              <span class="text-medium-emphasis d-block">Tanggal:</span>
              <strong>{{ formatDate(selectedTransaction.date) }}</strong>
            </VCol>
            <VCol cols="6" class="py-1">
              <span class="text-medium-emphasis d-block">Jenis Mutasi:</span>
              <strong>{{ selectedTransaction.type === 'injection' ? 'Injeksi / Permintaan Modal' : 'Setor Pengembalian Modal' }}</strong>
            </VCol>
            <VCol cols="6" class="py-1">
              <span class="text-medium-emphasis d-block">Kategori:</span>
              <strong>{{ selectedTransaction.category }}</strong>
            </VCol>
            <VCol cols="6" class="py-1">
              <span class="text-medium-emphasis d-block">Metode:</span>
              <strong>{{ selectedTransaction.payment_method }}</strong>
            </VCol>
            <VCol cols="6" class="py-1" v-if="selectedTransaction.bank_name">
              <span class="text-medium-emphasis d-block">Rekening:</span>
              <strong>{{ selectedTransaction.bank_name }} - {{ selectedTransaction.account_number || '-' }}</strong>
            </VCol>
          </VRow>

          <!-- Audit Timeline -->
          <div class="pa-3 rounded-lg border mb-3">
            <span class="text-caption font-weight-bold text-uppercase d-block mb-2 text-primary">Jejak Audit Transaksi</span>
            <div class="text-caption text-medium-emphasis mb-1">
              • <strong>Dibuat Oleh:</strong> {{ selectedTransaction.user?.name || 'Kasir / Sistem' }} ({{ formatDateTime(selectedTransaction.created_at) }})
            </div>
            <div v-if="selectedTransaction.approved_by" class="text-caption text-medium-emphasis mb-1">
              • <strong>Disetujui Oleh:</strong> {{ selectedTransaction.approved_by?.name }} ({{ formatDateTime(selectedTransaction.approved_at) }})
            </div>
            <div v-if="selectedTransaction.cash_shift_id" class="text-caption text-medium-emphasis">
              • <strong>Terhubung ke Shift POS:</strong> Shift #{{ selectedTransaction.cash_shift_id }}
            </div>
          </div>

          <!-- Riwayat Email Log Section -->
          <div class="d-flex align-center justify-space-between mt-3 mb-2">
            <span class="text-caption font-weight-bold text-uppercase d-flex align-center gap-1 text-primary">
              <VIcon icon="ri-mail-check-line" size="16" />
              Riwayat Log Pengiriman Email
            </span>
            <VBtn
              size="x-small"
              variant="text"
              icon="ri-refresh-line"
              :loading="isLoadingCapitalEmailLogs"
              @click="fetchCapitalEmailLogs(selectedTransaction.id)"
            />
          </div>

          <VCard class="border rounded-lg mb-3" variant="flat">
            <VTable density="compact">
              <thead>
                <tr>
                  <th>Penerima</th>
                  <th>Mode</th>
                  <th>Status</th>
                  <th class="text-right">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="capitalEmailLogs.length === 0">
                  <td colspan="4" class="text-center text-medium-emphasis py-3 text-caption">
                    Belum ada riwayat email untuk transaksi modal ini
                  </td>
                </tr>
                <tr v-for="log in capitalEmailLogs" :key="log.id">
                  <td class="py-2">
                    <div class="font-weight-medium text-caption">{{ log.recipient_email }}</div>
                    <div class="text-disabled" style="font-size: 10px;">{{ log.created_at ? formatDate(log.created_at) : '-' }}</div>
                  </td>
                  <td>
                    <span class="text-caption font-weight-medium">{{ log.trigger_mode === 'automatic' ? 'Otomatis' : 'Manual' }}</span>
                  </td>
                  <td>
                    <VChip
                      :color="log.status === 'sent' ? 'success' : (log.status === 'failed' ? 'error' : 'warning')"
                      size="x-small"
                      variant="elevated"
                      class="font-weight-bold"
                    >
                      {{ log.status === 'sent' ? 'Terkirim' : (log.status === 'failed' ? 'Gagal' : 'Pending') }}
                    </VChip>
                    <div v-if="log.error_message" class="text-error mt-1 text-truncate" style="max-width: 130px; font-size: 10px;" :title="log.error_message">
                      {{ log.error_message }}
                    </div>
                  </td>
                  <td class="text-right">
                    <VBtn
                      v-if="log.status === 'failed'"
                      size="x-small"
                      color="error"
                      variant="tonal"
                      prepend-icon="ri-refresh-line"
                      :loading="isRetryingCapitalEmail[log.id]"
                      @click="retryCapitalEmail(log.id)"
                    >
                      Kirim Ulang
                    </VBtn>
                    <VIcon v-else-if="log.status === 'sent'" icon="ri-check-double-line" color="success" size="16" />
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCard>

          <!-- Notes -->
          <div v-if="selectedTransaction.notes" class="pa-3 rounded-lg bg-var-theme-background mb-3 text-caption">
            <span class="font-weight-bold d-block mb-1">Catatan / Alasan:</span>
            <span class="text-pre-wrap">{{ selectedTransaction.notes }}</span>
          </div>

          <!-- Proof File / PDF Proposal Preview -->
          <div v-if="selectedTransaction.proof_file" class="pa-3 border rounded-lg mb-3">
            <span class="text-caption font-weight-bold d-block mb-2">Lampiran Bukti / Dokumen Proposal:</span>
            
            <!-- If PDF Document -->
            <div v-if="selectedTransaction.proof_file.toLowerCase().endsWith('.pdf')" class="d-flex align-center justify-space-between pa-3 rounded-lg bg-var-theme-background">
              <div class="d-flex align-center gap-2">
                <VAvatar color="error" variant="tonal" size="36">
                  <VIcon icon="ri-file-pdf-2-line" size="20" />
                </VAvatar>
                <div>
                  <span class="font-weight-medium text-body-2 d-block">Dokumen Proposal (PDF)</span>
                  <span class="text-caption text-medium-emphasis">Klik tombol untuk membaca PDF di tab baru</span>
                </div>
              </div>
              <VBtn
                color="error"
                size="small"
                variant="tonal"
                prepend-icon="ri-external-link-line"
                :href="'/storage/' + selectedTransaction.proof_file"
                target="_blank"
              >
                Buka PDF
              </VBtn>
            </div>

            <!-- If Image -->
            <div v-else class="text-center">
              <a :href="'/storage/' + selectedTransaction.proof_file" target="_blank">
                <img
                  :src="'/storage/' + selectedTransaction.proof_file"
                  alt="Bukti Transaksi"
                  class="rounded-lg"
                  style="max-width: 100%; max-height: 250px; object-fit: contain;"
                />
              </a>
              <div class="text-caption text-medium-emphasis mt-1">Klik gambar untuk membuka ukuran penuh</div>
            </div>
          </div>
        </div>
      </VCard>
    </VDialog>

    <!-- DIALOG KIRIM EMAIL REKAP MODAL & ROI KE OWNER -->
    <VDialog v-model="isSendSummaryEmailDialogVisible" max-width="480">
      <VCard>
        <VCardTitle class="bg-primary text-white pa-4 d-flex align-center justify-space-between">
          <div class="d-flex align-center gap-2">
            <VIcon icon="ri-mail-send-line" />
            <span>Kirim Rekap Modal ke Email Owner</span>
          </div>
          <VBtn icon="ri-close-line" variant="text" size="small" @click="isSendSummaryEmailDialogVisible = false" />
        </VCardTitle>

        <VCardText class="pa-5">
          <p class="text-body-2 text-medium-emphasis mb-4">
            Kirimkan laporan ringkasan portofolio permodalan konsolidasi dan tingkat pengembalian (ROI) ke inbox email Owner:
          </p>

          <VTextField
            v-model="summaryEmailInput"
            label="Alamat Email Tujuan (Opsional)"
            placeholder="Kosongkan untuk kirim otomatis ke email Owner terdaftar"
            prepend-inner-icon="ri-mail-line"
            type="email"
            variant="outlined"
            density="compact"
            class="mb-3"
          />

          <div class="pa-3 rounded bg-light-primary border text-caption">
            <div class="d-flex justify-space-between mb-1">
              <span>Total Modal Diberikan:</span>
              <strong>{{ formatCurrency(summary.total_injected) }}</strong>
            </div>
            <div class="d-flex justify-space-between mb-1">
              <span>Modal Dikembalikan:</span>
              <strong class="text-success">{{ formatCurrency(summary.total_returned) }}</strong>
            </div>
            <div class="d-flex justify-space-between">
              <span>Sisa Modal Tertanam:</span>
              <strong class="text-warning">{{ formatCurrency(summary.remaining_capital) }}</strong>
            </div>
          </div>
        </VCardText>

        <VCardActions class="pa-4 pt-0 justify-end gap-2">
          <VBtn variant="tonal" color="secondary" @click="isSendSummaryEmailDialogVisible = false">
            Batal
          </VBtn>
          <VBtn
            color="primary"
            class="font-weight-bold"
            prepend-icon="ri-send-plane-fill"
            :loading="isSendingSummaryEmail"
            @click="submitSendSummaryEmail"
          >
            Kirim Laporan
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- DIALOG KIRIM BUKTI SETORAN MODAL KE EMAIL -->
    <VDialog v-model="isSendCapitalEmailDialogVisible" max-width="480">
      <VCard>
        <VCardTitle class="bg-success text-white pa-4 d-flex align-center justify-space-between">
          <div class="d-flex align-center gap-2">
            <VIcon icon="ri-mail-send-line" />
            <span>Kirim Bukti Setoran ke Email</span>
          </div>
          <VBtn icon="ri-close-line" variant="text" size="small" @click="isSendCapitalEmailDialogVisible = false" />
        </VCardTitle>

        <VCardText class="pa-5">
          <p class="text-body-2 text-medium-emphasis mb-4">
            Kirimkan rincian setoran dan verifikasi mutasi modal ini ke alamat email:
          </p>

          <VTextField
            v-model="capitalEmailInput"
            label="Alamat Email Tujuan (Opsional)"
            placeholder="Kosongkan untuk kirim otomatis ke email Owner terdaftar"
            prepend-inner-icon="ri-mail-line"
            type="email"
            variant="outlined"
            density="compact"
            class="mb-3"
          />

          <div class="pa-3 rounded bg-light-primary border text-caption">
            <div class="d-flex justify-space-between mb-1">
              <span>No. Referensi:</span>
              <strong>{{ selectedTransaction?.reference_no }}</strong>
            </div>
            <div class="d-flex justify-space-between mb-1">
              <span>Cabang:</span>
              <strong>{{ selectedTransaction?.branch?.name }}</strong>
            </div>
            <div class="d-flex justify-space-between">
              <span>Nominal:</span>
              <strong class="text-success">{{ formatCurrency(selectedTransaction?.amount) }}</strong>
            </div>
          </div>
        </VCardText>

        <VCardActions class="pa-4 pt-0 justify-end gap-2">
          <VBtn variant="tonal" color="secondary" @click="isSendCapitalEmailDialogVisible = false">
            Batal
          </VBtn>
          <VBtn
            color="success"
            class="font-weight-bold"
            prepend-icon="ri-send-plane-fill"
            :loading="isSendingCapitalEmail"
            @click="submitSendCapitalEmail"
          >
            Kirim Email
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- PREVIEW BUKTI TRANSFER DIALOG -->
    <VDialog v-model="isPreviewDialogVisible" max-width="550">
      <VCard class="pa-4 rounded-xl">
        <div class="d-flex align-center justify-space-between mb-3">
          <span class="text-subtitle-1 font-weight-bold">Lampiran Bukti Transaksi</span>
          <VBtn icon="ri-close-line" variant="text" density="compact" @click="isPreviewDialogVisible = false" />
        </div>
        <div class="text-center pa-2">
          <a :href="previewImage" target="_blank">
            <img :src="previewImage" alt="Bukti Transfer" class="rounded-lg" style="max-width: 100%; max-height: 480px; object-fit: contain;" />
          </a>
          <div class="text-caption text-medium-emphasis mt-2">
            Klik gambar untuk membuka di tab baru
          </div>
        </div>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
.text-pre-wrap {
  white-space: pre-wrap;
}
</style>
