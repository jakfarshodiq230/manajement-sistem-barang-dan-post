<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'

const snackbar = useSnackbarStore()

// Filter State: Year & Month
const currentDate = new Date()
const selectedYear = ref(currentDate.getFullYear())
const selectedMonth = ref(currentDate.getMonth() + 1) // 1-12
const availableYears = ref([2024, 2025, 2026, 2027])

const monthNames = [
  { value: 1, name: 'Januari', short: 'Jan' },
  { value: 2, name: 'Februari', short: 'Feb' },
  { value: 3, name: 'Maret', short: 'Mar' },
  { value: 4, name: 'April', short: 'Apr' },
  { value: 5, name: 'Mei', short: 'Mei' },
  { value: 6, name: 'Juni', short: 'Jun' },
  { value: 7, name: 'Juli', short: 'Jul' },
  { value: 8, name: 'Agustus', short: 'Agu' },
  { value: 9, name: 'September', short: 'Sep' },
  { value: 10, name: 'Oktober', short: 'Okt' },
  { value: 11, name: 'November', short: 'Nov' },
  { value: 12, name: 'Desember', short: 'Des' },
]

// Tab View State
const activeTab = ref('cards') // 'cards' | 'table' | 'transactions'

// Data State
const bankAccounts = ref([])
const branches = ref([])
const isLoading = ref(false)
const isSubmitting = ref(false)
const searchQuery = ref('')
const selectedBranch = ref(null)
const selectedType = ref(null)
let searchTimeout = null

// KPI Summary
const summary = ref({
  total_accounts: 0,
  active_accounts: 0,
  total_balance: 0,
  selected_month_received: 0,
  selected_month_tx_count: 0,
  selected_year_received: 0,
})

// Drawer Form State
const isDrawerOpen = ref(false)
const isEditing = ref(false)
const currentEditId = ref(null)

const formBankName = ref('')
const formAccountNumber = ref('')
const formAccountName = ref('')
const formType = ref('bank_transfer')
const formBranchId = ref(null)
const formInitialBalance = ref(0)
const formCurrentBalance = ref(0)
const formIsActive = ref(true)
const formIsDefault = ref(false)
const formColor = ref('#0066AE')
const formNotes = ref('')
const formQrisFile = ref(null)
const formQrisPreview = ref(null)

// QRIS Zoom Modal
const isQrisModalOpen = ref(false)
const selectedQrisImage = ref('')
const selectedQrisBankName = ref('')

// Recent Sales Modal
const isRecentSalesModalOpen = ref(false)
const selectedBankForSales = ref(null)
const recentSalesList = ref([])
const isLoadingRecentSales = ref(false)

// Rekening Koran / Bank Statement State
const isStatementModalOpen = ref(false)
const selectedBankForStatement = ref(null)
const statementData = ref({
  bank_account: {},
  period: {},
  summary: {
    initial_balance: 0,
    opening_balance: 0,
    total_credit: 0,
    total_debit: 0,
    closing_balance: 0,
    current_balance: 0,
    mutation_count: 0,
  },
  mutations: [],
})
const isLoadingStatement = ref(false)
const isDownloadingPdf = ref(false)
const statementSearch = ref('')
const statementTypeFilter = ref('all') // 'all' | 'credit' | 'debit'
const statementQuickRange = ref('month') // 'today' | '7days' | 'month' | 'year' | 'all' | 'custom'
const statementStartDate = ref('')
const statementEndDate = ref('')

const currentMonthName = computed(() => {
  const m = monthNames.find(item => item.value === selectedMonth.value)
  return m ? m.name : ''
})

const formatCurrency = value => {
  if (!value || isNaN(value)) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value)
}

const formatDate = dateString => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

const formatInputRupiah = value => {
  if (value === null || value === undefined || value === '') return ''
  const num = typeof value === 'number' ? value : Number(String(value).replace(/[^0-9.-]+/g, ''))
  if (isNaN(num)) return ''
  return new Intl.NumberFormat('id-ID').format(Math.round(num))
}

const parseInputRupiah = value => {
  if (!value) return 0
  const clean = String(value).replace(/[^0-9]/g, '')
  return clean ? Number(clean) : 0
}

const colorPresets = [
  { name: 'BCA Blue', hex: '#0066AE' },
  { name: 'Mandiri Navy', hex: '#003366' },
  { name: 'BRI Dark Blue', hex: '#00529C' },
  { name: 'BNI Orange', hex: '#F15A24' },
  { name: 'BSI Teal', hex: '#00A39D' },
  { name: 'QRIS Cyan', hex: '#0088CC' },
  { name: 'Dark Slate', hex: '#2C3E50' },
  { name: 'Emerald', hex: '#10B981' },
]

const bankSuggestions = [
  'BCA (Bank Central Asia)',
  'Bank Mandiri',
  'BRI (Bank Rakyat Indonesia)',
  'BNI (Bank Negara Indonesia)',
  'BSI (Bank Syariah Indonesia)',
  'QRIS BCA Merchant',
  'QRIS Mandiri Merchant',
  'Bank Riau Kepri Syariah',
  'Bank Jago',
  'SeaBank',
  'Kas Tunai Toko / Laci Kasir',
]

const fetchBranches = async () => {
  try {
    const res = await $api('/apps/branches?simple=true')
    branches.value = res.data || res || []
  } catch (error) {
    console.error('Failed to fetch branches:', error)
  }
}

const fetchBankAccounts = async () => {
  isLoading.value = true
  try {
    const params = {
      year: selectedYear.value,
      month: selectedMonth.value,
    }
    if (selectedBranch.value !== null) params.branch_id = selectedBranch.value
    if (selectedType.value) params.type = selectedType.value
    if (searchQuery.value) params.search = searchQuery.value

    const res = await $api('/apps/bank-accounts', { query: params })
    bankAccounts.value = res.data || []
    if (res.summary) {
      summary.value = res.summary
      if (res.summary.available_years && res.summary.available_years.length > 0) {
        availableYears.value = res.summary.available_years
      }
    }
  } catch (error) {
    console.error('Failed to fetch bank accounts:', error)
    snackbar.show('Gagal memuat data rekening bank', 'error')
  } finally {
    isLoading.value = false
  }
}

const copyToClipboard = (text, label) => {
  if (!text) return
  navigator.clipboard.writeText(text)
  snackbar.show(`${label || 'Nomor rekening'} berhasil disalin!`, 'success')
}

const openQrisPreview = account => {
  selectedQrisImage.value = account.qris_image
  selectedQrisBankName.value = account.bank_name
  isQrisModalOpen.value = true
}

const openRecentSales = async account => {
  selectedBankForSales.value = account
  isRecentSalesModalOpen.value = true
  isLoadingRecentSales.value = true
  try {
    const res = await $api(`/apps/bank-accounts/${account.id}`)
    recentSalesList.value = res.recent_sales || []
  } catch (error) {
    console.error('Failed to fetch sales for bank:', error)
    recentSalesList.value = []
  } finally {
    isLoadingRecentSales.value = false
  }
}

const setQuickRange = range => {
  statementQuickRange.value = range
  const now = new Date()
  const year = now.getFullYear()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')

  if (range === 'today') {
    statementStartDate.value = `${year}-${month}-${day}`
    statementEndDate.value = `${year}-${month}-${day}`
  } else if (range === '7days') {
    const prior = new Date(now.getTime() - 6 * 24 * 60 * 60 * 1000)
    const pYear = prior.getFullYear()
    const pMonth = String(prior.getMonth() + 1).padStart(2, '0')
    const pDay = String(prior.getDate()).padStart(2, '0')
    statementStartDate.value = `${pYear}-${pMonth}-${pDay}`
    statementEndDate.value = `${year}-${month}-${day}`
  } else if (range === 'month') {
    const lastDay = new Date(year, now.getMonth() + 1, 0).getDate()
    statementStartDate.value = `${year}-${month}-01`
    statementEndDate.value = `${year}-${month}-${String(lastDay).padStart(2, '0')}`
  } else if (range === 'year') {
    statementStartDate.value = `${year}-01-01`
    statementEndDate.value = `${year}-12-31`
  } else if (range === 'all') {
    statementStartDate.value = ''
    statementEndDate.value = ''
  }
  fetchStatement()
}

const openStatementModal = async account => {
  selectedBankForStatement.value = account
  isStatementModalOpen.value = true
  statementSearch.value = ''
  statementTypeFilter.value = 'all'
  setQuickRange('month')
}

const fetchStatement = async () => {
  if (!selectedBankForStatement.value) return
  isLoadingStatement.value = true
  try {
    const params = {}
    if (statementStartDate.value) params.start_date = statementStartDate.value
    if (statementEndDate.value) params.end_date = statementEndDate.value
    if (statementSearch.value) params.search = statementSearch.value
    if (statementTypeFilter.value && statementTypeFilter.value !== 'all') params.type_filter = statementTypeFilter.value

    const res = await $api(`/apps/bank-accounts/${selectedBankForStatement.value.id}/statement`, { query: params })
    statementData.value = res || {}
  } catch (error) {
    console.error('Failed to fetch bank statement:', error)
    snackbar.show('Gagal memuat rekening koran bank', 'error')
  } finally {
    isLoadingStatement.value = false
  }
}

const downloadStatementPdf = async () => {
  if (!selectedBankForStatement.value) return
  isDownloadingPdf.value = true
  try {
    const queryParams = new URLSearchParams()
    if (statementStartDate.value) queryParams.append('start_date', statementStartDate.value)
    if (statementEndDate.value) queryParams.append('end_date', statementEndDate.value)
    if (statementSearch.value) queryParams.append('search', statementSearch.value)
    if (statementTypeFilter.value && statementTypeFilter.value !== 'all') queryParams.append('type_filter', statementTypeFilter.value)

    const url = `/api/apps/bank-accounts/${selectedBankForStatement.value.id}/export-pdf?${queryParams.toString()}`
    
    // Fetch blob with auth token
    const token = useCookie('accessToken').value
    const response = await fetch(url, {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/pdf',
      },
    })

    if (!response.ok) throw new Error('Download failed')

    const blob = await response.blob()
    const blobUrl = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = blobUrl
    link.download = `Rekening_Koran_${selectedBankForStatement.value.bank_name.replace(/[^a-zA-Z0-9]/g, '_')}_${statementStartDate.value || 'All'}.pdf`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(blobUrl)

    snackbar.show('Buku Rekening Koran PDF berhasil diunduh!', 'success')
  } catch (error) {
    console.error('Failed to download PDF:', error)
    snackbar.show('Gagal mengunduh PDF Rekening Koran', 'error')
  } finally {
    isDownloadingPdf.value = false
  }
}

const openAddDrawer = () => {
  isEditing.value = false
  currentEditId.value = null
  formBankName.value = ''
  formAccountNumber.value = ''
  formAccountName.value = ''
  formType.value = 'bank_transfer'
  formBranchId.value = null
  formInitialBalance.value = 0
  formCurrentBalance.value = 0
  formIsActive.value = true
  formIsDefault.value = false
  formColor.value = '#0066AE'
  formNotes.value = ''
  formQrisFile.value = null
  formQrisPreview.value = null
  isDrawerOpen.value = true
}

const openEditDrawer = account => {
  isEditing.value = true
  currentEditId.value = account.id
  formBankName.value = account.bank_name
  formAccountNumber.value = account.account_number || ''
  formAccountName.value = account.account_name || ''
  formType.value = account.type || 'bank_transfer'
  formBranchId.value = account.branch_id || null
  formInitialBalance.value = Number(account.initial_balance) || 0
  formCurrentBalance.value = Number(account.current_balance) || 0
  formIsActive.value = Boolean(account.is_active)
  formIsDefault.value = Boolean(account.is_default)
  formColor.value = account.color || '#0066AE'
  formNotes.value = account.notes || ''
  formQrisFile.value = null
  formQrisPreview.value = account.qris_image || null
  isDrawerOpen.value = true
}

const onQrisFileSelected = event => {
  const file = event.target.files[0]
  if (file) {
    formQrisFile.value = file
    formQrisPreview.value = URL.createObjectURL(file)
  }
}

const saveBankAccount = async () => {
  if (!formBankName.value) {
    snackbar.show('Nama Bank wajib diisi', 'error')
    return
  }

  isSubmitting.value = true
  try {
    const formData = new FormData()
    formData.append('bank_name', formBankName.value)
    if (formAccountNumber.value) formData.append('account_number', formAccountNumber.value)
    if (formAccountName.value) formData.append('account_name', formAccountName.value)
    formData.append('type', formType.value)
    if (formBranchId.value) formData.append('branch_id', formBranchId.value)
    formData.append('initial_balance', formInitialBalance.value)
    if (isEditing.value) formData.append('current_balance', formCurrentBalance.value)
    formData.append('is_active', formIsActive.value ? '1' : '0')
    formData.append('is_default', formIsDefault.value ? '1' : '0')
    if (formColor.value) formData.append('color', formColor.value)
    if (formNotes.value) formData.append('notes', formNotes.value)
    if (formQrisFile.value) formData.append('qris_image', formQrisFile.value)

    if (isEditing.value) {
      formData.append('_method', 'PUT')
      await $api(`/apps/bank-accounts/${currentEditId.value}`, {
        method: 'POST',
        body: formData,
      })
      snackbar.show('Rekening bank berhasil diperbarui!', 'success')
    } else {
      await $api('/apps/bank-accounts', {
        method: 'POST',
        body: formData,
      })
      snackbar.show('Rekening bank baru berhasil ditambahkan!', 'success')
    }

    isDrawerOpen.value = false
    await fetchBankAccounts()
  } catch (error) {
    console.error('Failed to save bank account:', error)
    snackbar.show(error.data?.message || 'Gagal menyimpan rekening bank', 'error')
  } finally {
    isSubmitting.value = false
  }
}

const deleteBankAccount = async account => {
  if (!confirm(`Yakin ingin menghapus rekening ${account.bank_name} (${account.account_number || '-'})?`)) return

  try {
    const res = await $api(`/apps/bank-accounts/${account.id}`, { method: 'DELETE' })
    snackbar.show(res.message || 'Rekening bank berhasil dihapus', 'success')
    await fetchBankAccounts()
  } catch (error) {
    console.error('Failed to delete bank account:', error)
    snackbar.show(error.data?.message || 'Gagal menghapus rekening bank', 'error')
  }
}

const resetToCurrentPeriod = () => {
  const d = new Date()
  selectedYear.value = d.getFullYear()
  selectedMonth.value = d.getMonth() + 1
}

watch([selectedYear, selectedMonth, selectedBranch, selectedType], () => {
  fetchBankAccounts()
})

watch(searchQuery, () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchBankAccounts()
  }, 400)
})

onMounted(() => {
  fetchBranches()
  fetchBankAccounts()
})
</script>

<template>
  <div class="pa-4">
    <!-- Header -->
    <div class="d-flex flex-wrap align-center justify-space-between mb-4 gap-4">
      <div>
        <h2 class="text-h4 font-weight-bold mb-1">
          Rekening Bank & Kas Penerimaan
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Monitoring buku bank, rekening koran resmi, penerimaan penjualan kasir, dan penagihan piutang.
        </p>
      </div>

      <div class="d-flex align-center gap-3">
        <VBtn
          color="secondary"
          variant="tonal"
          prepend-icon="ri-refresh-line"
          :loading="isLoading"
          @click="fetchBankAccounts"
        >
          Muat Ulang
        </VBtn>
        <VBtn
          color="primary"
          prepend-icon="ri-add-line"
          @click="openAddDrawer"
        >
          Tambah Rekening Bank
        </VBtn>
      </div>
    </div>

    <!-- KPI Summary Row (Matching standard template design) -->
    <VRow class="mb-4">
      <!-- 1. Total Saldo Bank (Berjalan) -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-primary">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-primary font-weight-bold">TOTAL SALDO BANK</div>
              <div class="text-h5 font-weight-bold text-primary mt-1 font-mono">
                {{ formatCurrency(summary.total_balance) }}
              </div>
            </div>
            <VAvatar color="primary" variant="tonal" rounded size="44">
              <VIcon icon="ri-wallet-3-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">{{ summary.active_accounts }} Rekening Bank Aktif</div>
        </VCard>
      </VCol>

      <!-- 2. Penerimaan Bulan Ini -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-success">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-success font-weight-bold">PENERIMAAN {{ currentMonthName.toUpperCase() }}</div>
              <div class="text-h5 font-weight-bold text-success mt-1 font-mono">
                {{ formatCurrency(summary.selected_month_received) }}
              </div>
            </div>
            <VAvatar color="success" variant="tonal" rounded size="44">
              <VIcon icon="ri-arrow-down-circle-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">{{ summary.selected_month_tx_count }} Transaksi Masuk</div>
        </VCard>
      </VCol>

      <!-- 3. Omzet Tahun Berjalan -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-info">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-info font-weight-bold">TOTAL OMZET TAHUN {{ selectedYear }}</div>
              <div class="text-h5 font-weight-bold text-info mt-1 font-mono">
                {{ formatCurrency(summary.selected_year_received) }}
              </div>
            </div>
            <VAvatar color="info" variant="tonal" rounded size="44">
              <VIcon icon="ri-line-chart-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Akumulasi Seluruh Rekening</div>
        </VCard>
      </VCol>

      <!-- 4. Rata-rata Nominal per Transaksi -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-warning">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-warning font-weight-bold">RATA-RATA / BON</div>
              <div class="text-h5 font-weight-bold text-warning mt-1 font-mono">
                {{ formatCurrency(summary.selected_month_tx_count > 0 ? (summary.selected_month_received / summary.selected_month_tx_count) : 0) }}
              </div>
            </div>
            <VAvatar color="warning" variant="tonal" rounded size="44">
              <VIcon icon="ri-money-dollar-circle-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Rata-rata Penerimaan Kasir</div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Clean Single-Row Filter Card -->
    <VCard elevation="2" class="mb-4">
      <VCardText class="pa-4">
        <VRow dense align="center">
          <VCol cols="12" sm="6" md="2">
            <VSelect
              v-model="selectedYear"
              :items="availableYears"
              label="Tahun"
              density="compact"
              variant="outlined"
              hide-details
            />
          </VCol>
          <VCol cols="12" sm="6" md="2">
            <VSelect
              v-model="selectedMonth"
              :items="monthNames"
              item-title="name"
              item-value="value"
              label="Bulan"
              density="compact"
              variant="outlined"
              hide-details
            />
          </VCol>
          <VCol cols="12" sm="6" md="2">
            <VSelect
              v-model="selectedBranch"
              :items="[{ title: 'Semua Cabang', value: null }, ...branches.map(b => ({ title: b.name, value: b.id }))]"
              item-title="title"
              item-value="value"
              label="Cabang"
              density="compact"
              variant="outlined"
              hide-details
            />
          </VCol>
          <VCol cols="12" sm="6" md="2">
            <VSelect
              v-model="selectedType"
              :items="[
                { title: 'Semua Tipe', value: null },
                { title: 'Transfer Bank', value: 'bank_transfer' },
                { title: 'QRIS Merchant', value: 'qris' },
                { title: 'EDC Kasir', value: 'edc_debit' },
                { title: 'Kas Tunai', value: 'cash' },
              ]"
              item-title="title"
              item-value="value"
              label="Tipe Akun"
              density="compact"
              variant="outlined"
              hide-details
            />
          </VCol>
          <VCol cols="12" sm="12" md="4">
            <VTextField
              v-model="searchQuery"
              placeholder="Cari nama bank, no. rek..."
              prepend-inner-icon="ri-search-line"
              density="compact"
              variant="outlined"
              clearable
              hide-details
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Main View Switcher -->
    <div class="d-flex justify-space-between align-center mb-4">
      <VTabs v-model="activeTab" density="compact">
        <VTab value="cards" class="font-weight-bold">
          <VIcon icon="ri-grid-fill" size="18" class="me-1" />
          Kartu Rekening ({{ bankAccounts.length }})
        </VTab>
        <VTab value="table" class="font-weight-bold">
          <VIcon icon="ri-table-line" size="18" class="me-1" />
          Tabel Rekap Bulan {{ currentMonthName }}
        </VTab>
      </VTabs>
    </div>

    <!-- TAB 1: Clean Bank Cards Gallery Grid -->
    <div v-if="activeTab === 'cards'">
      <VRow v-if="bankAccounts.length > 0">
        <VCol
          v-for="account in bankAccounts"
          :key="account.id"
          cols="12"
          sm="6"
          lg="4"
        >
          <VCard elevation="2" class="h-100 d-flex flex-column rounded-xl border">
            <!-- Card Header -->
            <VCardItem class="pb-3">
              <template #prepend>
                <VAvatar
                  :color="account.type === 'qris' ? 'info' : (account.type === 'edc_debit' ? 'warning' : 'primary')"
                  variant="tonal"
                  rounded
                  size="44"
                  class="me-3"
                >
                  <VIcon :icon="account.type === 'qris' ? 'ri-qr-code-line' : (account.type === 'edc_debit' ? 'ri-bank-card-line' : 'ri-bank-line')" size="24" />
                </VAvatar>
              </template>

              <VCardTitle class="text-h6 font-weight-bold d-flex align-center justify-space-between">
                <span>{{ account.bank_name }}</span>
                <div class="d-flex align-center gap-1">
                  <VChip
                    v-if="account.is_default"
                    size="x-small"
                    color="warning"
                    variant="flat"
                    class="font-weight-bold"
                  >
                    UTAMA
                  </VChip>
                  <VChip
                    :color="account.is_active ? 'success' : 'secondary'"
                    size="x-small"
                    variant="tonal"
                    class="font-weight-bold"
                  >
                    {{ account.is_active ? 'Aktif' : 'Non-aktif' }}
                  </VChip>
                </div>
              </VCardTitle>

              <VCardSubtitle class="text-caption text-medium-emphasis">
                {{ account.branch?.name || 'Semua Cabang' }} &bull; {{ account.account_name || '-' }}
              </VCardSubtitle>
            </VCardItem>

            <VDivider />

            <!-- Card Body -->
            <VCardText class="py-3 flex-grow-1">
              <!-- Account Number Row -->
              <div class="d-flex justify-space-between align-center mb-3">
                <div class="d-flex align-center gap-1.5">
                  <span class="text-caption text-medium-emphasis">No. Rek:</span>
                  <span class="font-mono font-weight-bold text-body-1">{{ account.account_number || '-' }}</span>
                  <VBtn
                    v-if="account.account_number"
                    icon="ri-file-copy-line"
                    size="x-small"
                    variant="text"
                    color="secondary"
                    title="Salin Nomor Rekening"
                    @click="copyToClipboard(account.account_number, account.bank_name)"
                  />
                </div>
                <VBtn
                  v-if="account.qris_image"
                  size="x-small"
                  variant="tonal"
                  color="info"
                  prepend-icon="ri-qr-code-line"
                  @click="openQrisPreview(account)"
                >
                  QRIS
                </VBtn>
              </div>

              <!-- Saldo & Omzet 2-Column Info -->
              <VRow dense class="mt-1">
                <VCol cols="6">
                  <div class="text-caption text-medium-emphasis">Saldo Berjalan</div>
                  <div class="text-h6 font-weight-bold font-mono text-primary mt-1">
                    {{ formatCurrency(account.current_balance) }}
                  </div>
                </VCol>
                <VCol cols="6" class="text-right">
                  <div class="text-caption text-medium-emphasis">Penerimaan ({{ currentMonthName }})</div>
                  <div class="text-subtitle-1 font-weight-bold font-mono text-success mt-1">
                    +{{ formatCurrency(account.month_received || 0) }}
                  </div>
                  <div class="text-caption text-disabled" style="font-size: 11px;">
                    {{ account.month_tx_count || 0 }} Transaksi
                  </div>
                </VCol>
              </VRow>
            </VCardText>

            <VDivider />

            <!-- Card Actions -->
            <VCardActions class="pa-3 bg-surface d-flex justify-space-between align-center">
              <div class="d-flex gap-2">
                <VBtn
                  size="small"
                  variant="flat"
                  color="primary"
                  prepend-icon="ri-book-read-line"
                  class="font-weight-bold"
                  @click="openStatementModal(account)"
                >
                  Buku Bank
                </VBtn>
                <VBtn
                  size="small"
                  variant="tonal"
                  color="secondary"
                  prepend-icon="ri-history-line"
                  @click="openRecentSales(account)"
                >
                  Mutasi Bon
                </VBtn>
              </div>

              <div class="d-flex gap-1">
                <VBtn
                  size="small"
                  variant="text"
                  color="secondary"
                  icon="ri-edit-line"
                  title="Edit Rekening"
                  @click="openEditDrawer(account)"
                />
                <VBtn
                  size="small"
                  variant="text"
                  color="error"
                  icon="ri-delete-bin-line"
                  title="Hapus Rekening"
                  @click="deleteBankAccount(account)"
                />
              </div>
            </VCardActions>
          </VCard>
        </VCol>
      </VRow>

      <!-- Empty State -->
      <VCard v-else-if="!isLoading" class="pa-12 text-center rounded-xl border">
        <VIcon icon="ri-bank-card-line" size="48" class="text-disabled mb-2" />
        <h5 class="text-h5 font-weight-bold">Belum Ada Rekening Bank</h5>
        <p class="text-body-2 text-medium-emphasis mb-4">
          Daftarkan rekening bank Anda agar dapat dipilih kasir pada transaksi penjualan non-tunai.
        </p>
        <VBtn color="primary" prepend-icon="ri-add-line" @click="openAddDrawer">
          Tambah Rekening Pertama
        </VBtn>
      </VCard>
    </div>

    <!-- TAB 2: Table Summary View per Month -->
    <VCard v-else-if="activeTab === 'table'" class="rounded-xl border shadow-xs overflow-hidden">
      <div class="pa-4 bg-gradient-header border-b d-flex justify-space-between align-center flex-wrap gap-2">
        <div class="d-flex align-center gap-2">
          <VIcon icon="ri-file-list-3-line" color="primary" size="22" />
          <span class="font-weight-bold text-subtitle-1">
            Rekap Penerimaan Bank Periode {{ currentMonthName }} {{ selectedYear }}
          </span>
        </div>
        <VChip color="primary" variant="flat" size="small" class="font-weight-bold">
          Total Omzet Bulan Ini: {{ formatCurrency(summary.selected_month_received) }}
        </VChip>
      </div>

      <div class="table-responsive">
        <table class="w-100 text-caption table-clean" style="border-collapse: collapse;">
          <thead>
            <tr class="bg-grey-100 text-left border-b font-weight-bold text-uppercase">
              <th class="pa-3">REKENING BANK</th>
              <th class="pa-3">NO. REKENING</th>
              <th class="pa-3">ATAS NAMA</th>
              <th class="pa-3">CABANG</th>
              <th class="pa-3 text-center">TX BULAN INI</th>
              <th class="pa-3 text-right">OMZET BULAN INI ({{ currentMonthName }})</th>
              <th class="pa-3 text-right">TOTAL TAHUN {{ selectedYear }}</th>
              <th class="pa-3 text-right">SALDO BERJALAN</th>
              <th class="pa-3 text-center">AKSI</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="acc in bankAccounts"
              :key="acc.id"
              class="border-b table-row-hover"
            >
              <td class="pa-3 font-weight-bold">
                <div class="d-flex align-center gap-2">
                  <div class="color-indicator-dot" :style="{ backgroundColor: acc.color || '#0066AE' }" />
                  <span>{{ acc.bank_name }}</span>
                  <VChip v-if="acc.is_default" size="x-small" color="warning" variant="flat">UTAMA</VChip>
                </div>
              </td>
              <td class="pa-3 font-mono font-weight-medium">
                {{ acc.account_number || '-' }}
              </td>
              <td class="pa-3 text-uppercase font-weight-medium">
                {{ acc.account_name || '-' }}
              </td>
              <td class="pa-3">
                {{ acc.branch?.name || 'Semua Cabang' }}
              </td>
              <td class="pa-3 text-center font-mono font-weight-bold">
                {{ acc.month_tx_count || 0 }}
              </td>
              <td class="pa-3 text-right font-mono font-weight-bold text-success">
                {{ formatCurrency(acc.month_received || 0) }}
              </td>
              <td class="pa-3 text-right font-mono font-weight-medium text-high-emphasis">
                {{ formatCurrency(acc.year_received || 0) }}
              </td>
              <td class="pa-3 text-right font-mono font-weight-bold text-primary">
                {{ formatCurrency(acc.current_balance) }}
              </td>
              <td class="pa-3 text-center">
                <div class="d-flex justify-center gap-1">
                  <VBtn
                    size="x-small"
                    variant="flat"
                    color="primary"
                    prepend-icon="ri-book-read-line"
                    class="font-weight-bold"
                    title="Buku Bank / Rekening Koran"
                    @click="openStatementModal(acc)"
                  >
                    Buku Bank
                  </VBtn>
                  <VBtn
                    size="x-small"
                    variant="tonal"
                    color="secondary"
                    icon="ri-edit-line"
                    title="Edit Rekening"
                    @click="openEditDrawer(acc)"
                  />
                  <VBtn
                    size="x-small"
                    variant="tonal"
                    color="error"
                    icon="ri-delete-bin-line"
                    title="Hapus Rekening"
                    @click="deleteBankAccount(acc)"
                  />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </VCard>

    <!-- Drawer Form Tambah / Edit Rekening Bank -->
    <VNavigationDrawer
      v-model="isDrawerOpen"
      location="end"
      temporary
      :width="$vuetify.display.xs ? '100%' : ($vuetify.display.smAndDown ? '90vw' : 500)"
    >
      <div class="d-flex flex-column h-100">
        <div class="pa-5 border-b bg-gradient-header d-flex justify-space-between align-center">
          <div class="d-flex align-center gap-3">
            <VAvatar color="primary" variant="tonal" size="44" class="rounded-lg">
              <VIcon :icon="isEditing ? 'ri-edit-2-line' : 'ri-bank-card-line'" size="24" />
            </VAvatar>
            <div>
              <h6 class="text-h6 font-weight-bold mb-0">
                {{ isEditing ? 'Edit Rekening Bank' : 'Tambah Rekening Bank Baru' }}
              </h6>
              <span class="text-caption text-medium-emphasis">
                Konfigurasi akun penerimaan pembayaran non-tunai
              </span>
            </div>
          </div>
          <VBtn icon="ri-close-line" variant="text" size="small" @click="isDrawerOpen = false" />
        </div>

        <div class="pa-6 flex-grow-1 overflow-y-auto">
          <VRow dense>
            <!-- Nama Bank (Combobox) -->
            <VCol cols="12">
              <div class="mb-1 text-caption font-weight-bold">Nama Bank / Merchant QRIS *</div>
              <VCombobox
                v-model="formBankName"
                :items="bankSuggestions"
                placeholder="Pilih atau ketik nama bank..."
                density="compact"
                variant="outlined"
                hide-details
              />
            </VCol>

            <!-- Tipe Akun -->
            <VCol cols="12" class="mt-3">
              <div class="mb-1 text-caption font-weight-bold">Tipe Akun Pembayaran *</div>
              <VSelect
                v-model="formType"
                :items="[
                  { title: 'Transfer Bank', value: 'bank_transfer' },
                  { title: 'QRIS Merchant', value: 'qris' },
                  { title: 'EDC Debit / Kredit', value: 'edc_debit' },
                  { title: 'Kas Tunai Toko', value: 'cash' },
                ]"
                item-title="title"
                item-value="value"
                density="compact"
                variant="outlined"
                hide-details
              />
            </VCol>

            <!-- Nomor Rekening / Merchant ID -->
            <VCol cols="12" class="mt-3">
              <div class="mb-1 text-caption font-weight-bold">Nomor Rekening / Merchant ID</div>
              <VTextField
                v-model="formAccountNumber"
                placeholder="Contoh: 8210998877 atau NMID..."
                density="compact"
                variant="outlined"
                class="font-mono"
                hide-details
              />
            </VCol>

            <!-- Atas Nama Pemilik -->
            <VCol cols="12" class="mt-3">
              <div class="mb-1 text-caption font-weight-bold">Atas Nama Rekening</div>
              <VTextField
                v-model="formAccountName"
                placeholder="Contoh: PT. DUMAI BERKAH ABADI"
                density="compact"
                variant="outlined"
                hide-details
              />
            </VCol>

            <!-- Cabang Pemilik -->
            <VCol cols="12" class="mt-3">
              <div class="mb-1 text-caption font-weight-bold">Cabang Kepemilikan</div>
              <VSelect
                v-model="formBranchId"
                :items="[{ title: 'Semua Cabang (Global / Pusat)', value: null }, ...branches.map(b => ({ title: b.name, value: b.id }))]"
                item-title="title"
                item-value="value"
                density="compact"
                variant="outlined"
                hide-details
              />
            </VCol>

            <!-- Saldo Awal -->
            <VCol cols="12" class="mt-3">
              <div class="mb-1 text-caption font-weight-bold">Saldo Awal (Rp)</div>
              <VTextField
                :model-value="formatInputRupiah(formInitialBalance)"
                prefix="Rp"
                placeholder="0"
                density="compact"
                variant="outlined"
                class="font-mono font-weight-bold"
                hide-details
                @update:model-value="val => formInitialBalance = parseInputRupiah(val)"
              />
            </VCol>

            <!-- Saldo Berjalan (Edit Mode) -->
            <VCol v-if="isEditing" cols="12" class="mt-3">
              <div class="mb-1 text-caption font-weight-bold">Saldo Berjalan Saat Ini (Rp)</div>
              <VTextField
                :model-value="formatInputRupiah(formCurrentBalance)"
                prefix="Rp"
                density="compact"
                variant="outlined"
                class="font-mono font-weight-bold text-primary"
                hide-details
                @update:model-value="val => formCurrentBalance = parseInputRupiah(val)"
              />
            </VCol>

            <!-- Warna Kartu ATM -->
            <VCol cols="12" class="mt-3">
              <div class="mb-1 text-caption font-weight-bold">Pilih Tema Warna Kartu</div>
              <div class="d-flex gap-2 flex-wrap mb-2">
                <div
                  v-for="preset in colorPresets"
                  :key="preset.hex"
                  class="color-preset-dot cursor-pointer"
                  :style="{ backgroundColor: preset.hex, outline: formColor === preset.hex ? '3px solid #000' : 'none' }"
                  :title="preset.name"
                  @click="formColor = preset.hex"
                />
              </div>
            </VCol>

            <!-- Upload QRIS Image -->
            <VCol v-if="formType === 'qris'" cols="12" class="mt-3">
              <div class="mb-1 text-caption font-weight-bold">Unggah Gambar Barcode QRIS</div>
              <VFileInput
                density="compact"
                variant="outlined"
                accept="image/*"
                prepend-icon=""
                prepend-inner-icon="ri-qr-code-line"
                placeholder="Pilih berkas barcode QRIS..."
                hide-details
                @change="onQrisFileSelected"
              />
              <div v-if="formQrisPreview" class="mt-2 text-center">
                <img
                  :src="formQrisPreview"
                  style="max-width: 160px; max-height: 160px; border-radius: 8px; border: 1px solid #ddd;"
                />
              </div>
            </VCol>

            <!-- Status Switches -->
            <VCol cols="12" sm="6" class="mt-3">
              <VSwitch
                v-model="formIsActive"
                label="Status Aktif"
                color="primary"
                density="compact"
                hide-details
              />
            </VCol>
            <VCol cols="12" sm="6" class="mt-3">
              <VSwitch
                v-model="formIsDefault"
                label="Jadikan Utama"
                color="warning"
                density="compact"
                hide-details
              />
            </VCol>

            <!-- Catatan -->
            <VCol cols="12" class="mt-3">
              <div class="mb-1 text-caption font-weight-bold">Catatan (Opsional)</div>
              <VTextarea
                v-model="formNotes"
                rows="2"
                placeholder="Keterangan tambahan..."
                density="compact"
                variant="outlined"
                hide-details
              />
            </VCol>
          </VRow>
        </div>

        <div class="pa-4 border-t d-flex justify-end gap-2 bg-grey-50">
          <VBtn variant="outlined" color="secondary" @click="isDrawerOpen = false">
            Batal
          </VBtn>
          <VBtn
            color="primary"
            prepend-icon="ri-save-line"
            :loading="isSubmitting"
            @click="saveBankAccount"
          >
            {{ isEditing ? 'Simpan Perubahan' : 'Tambah Rekening' }}
          </VBtn>
        </div>
      </div>
    </VNavigationDrawer>

    <!-- QRIS Preview Modal -->
    <VDialog
      v-model="isQrisModalOpen"
      :fullscreen="$vuetify.display.xs"
      max-width="400"
    >
      <VCard class="pa-4 text-center rounded-xl">
        <VCardTitle class="font-weight-bold text-h6 pb-1">
          QRIS {{ selectedQrisBankName }}
        </VCardTitle>
        <span class="text-caption text-medium-emphasis mb-4 d-block">
          Scan QRIS menggunakan BCA Mobile, Mandiri Livin, GoPay, OVO, ShopeePay, Dana, dll.
        </span>
        <div class="d-flex justify-center mb-4">
          <img
            v-if="selectedQrisImage"
            :src="selectedQrisImage"
            style="max-width: 280px; max-height: 280px; border-radius: 12px; border: 2px solid #eee;"
          />
          <div v-else class="pa-10 border rounded-lg text-medium-emphasis">
            Gambar QRIS belum diunggah.
          </div>
        </div>
        <VBtn color="primary" block @click="isQrisModalOpen = false">
          Tutup
        </VBtn>
      </VCard>
    </VDialog>

    <!-- Recent Sales History Modal -->
    <VDialog
      v-model="isRecentSalesModalOpen"
      :fullscreen="$vuetify.display.xs"
      max-width="850"
    >
      <VCard v-if="selectedBankForSales" class="rounded-xl overflow-hidden shadow-lg">
        <VCardTitle class="pa-5 bg-gradient-header d-flex justify-space-between align-center">
          <div class="d-flex align-center gap-3">
            <VAvatar :color="selectedBankForSales.color || 'primary'" variant="tonal" size="44" class="rounded-lg">
              <VIcon icon="ri-history-line" size="24" />
            </VAvatar>
            <div>
              <div class="d-flex align-center gap-2">
                <span class="text-h6 font-weight-bold">
                  Mutasi Penerimaan: {{ selectedBankForSales.bank_name }}
                </span>
                <VChip size="x-small" color="primary" variant="flat">
                  {{ selectedBankForSales.branch?.name || 'Semua Cabang (Global)' }}
                </VChip>
              </div>
              <div class="text-caption text-medium-emphasis">
                No. Rek: <strong class="font-mono">{{ selectedBankForSales.account_number || '-' }}</strong> | A.N: <strong>{{ selectedBankForSales.account_name || '-' }}</strong> | Saldo: <strong class="text-primary font-mono">{{ formatCurrency(selectedBankForSales.current_balance) }}</strong>
              </div>
            </div>
          </div>
          <VBtn icon="ri-close-line" variant="text" size="small" @click="isRecentSalesModalOpen = false" />
        </VCardTitle>

        <VCardText class="pa-5">
          <div v-if="isLoadingRecentSales" class="d-flex justify-center py-8">
            <VProgressCircular indeterminate color="primary" />
          </div>
          <div v-else-if="recentSalesList.length > 0" class="border rounded-xl overflow-hidden shadow-xs">
            <table class="w-100" style="border-collapse: collapse; font-size: 12.5px;">
              <thead>
                <tr class="bg-grey-100 text-left border-b font-weight-bold text-uppercase" style="font-size: 11px;">
                  <th class="pa-3">NO. BON</th>
                  <th class="pa-3">TANGGAL</th>
                  <th class="pa-3">CABANG</th>
                  <th class="pa-3">KASIR</th>
                  <th class="pa-3">PELANGGAN</th>
                  <th class="pa-3 text-right">TOTAL NOMINAL</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="sale in recentSalesList" :key="sale.id" class="border-b table-row-hover">
                  <td class="pa-3 font-mono font-weight-bold text-primary">
                    {{ sale.invoice_number }}
                  </td>
                  <td class="pa-3">{{ formatDate(sale.date || sale.created_at) }}</td>
                  <td class="pa-3">
                    <VChip size="x-small" color="info" variant="tonal" class="font-weight-medium">
                      {{ sale.branch_name || 'Pusat / Utama' }}
                    </VChip>
                  </td>
                  <td class="pa-3 font-weight-medium">
                    {{ sale.cashier_name || '-' }}
                  </td>
                  <td class="pa-3">
                    {{ sale.customer_name || 'Umum' }}
                  </td>
                  <td class="pa-3 text-right font-mono font-weight-bold text-success">
                    +{{ formatCurrency(sale.total_amount) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="pa-10 text-center text-medium-emphasis border rounded-xl bg-grey-50">
            <VIcon icon="ri-inbox-line" size="40" class="mb-2 text-disabled" />
            <div class="text-subtitle-2 font-weight-bold">Belum Ada Riwayat Transaksi</div>
            <div class="text-caption">Belum ada transaksi penjualan yang dicatat masuk ke rekening bank ini.</div>
          </div>
        </VCardText>
      </VCard>
    </VDialog>
    <!-- MODAL BUKU BANK & REKENING KORAN (CLEAN & ELEGANT) -->
    <VDialog
      v-model="isStatementModalOpen"
      max-width="1100"
      scrollable
      transition="dialog-bottom-transition"
    >
      <VCard class="rounded-xl overflow-hidden border">
        <!-- Dialog Header -->
        <VCardItem class="pa-5 bg-surface border-b">
          <template #prepend>
            <VAvatar color="primary" variant="tonal" rounded size="44" class="me-3">
              <VIcon icon="ri-book-read-line" size="24" />
            </VAvatar>
          </template>

          <VCardTitle class="text-h6 font-weight-bold d-flex align-center justify-space-between">
            <div class="d-flex align-center gap-2 flex-wrap">
              <span>Buku Mutasi & Rekening Koran</span>
              <VChip size="small" color="primary" variant="tonal" class="font-weight-bold">
                {{ selectedBankForStatement?.bank_name }}
              </VChip>
              <VChip
                v-if="selectedBankForStatement?.is_default"
                size="small"
                color="warning"
                variant="flat"
                class="font-weight-bold"
              >
                UTAMA
              </VChip>
            </div>
            <VBtn
              icon="ri-close-line"
              variant="text"
              color="secondary"
              size="small"
              @click="isStatementModalOpen = false"
            />
          </VCardTitle>

          <VCardSubtitle class="text-caption text-medium-emphasis mt-1">
            <span class="d-inline-flex align-center gap-1">
              <span>No. Rek:</span>
              <strong class="font-mono text-high-emphasis">{{ selectedBankForStatement?.account_number || '-' }}</strong>
              <VBtn
                v-if="selectedBankForStatement?.account_number"
                icon="ri-file-copy-line"
                size="x-small"
                variant="text"
                color="secondary"
                title="Salin No. Rekening"
                @click="copyToClipboard(selectedBankForStatement.account_number, selectedBankForStatement.bank_name)"
              />
            </span>
            <span class="mx-2">&bull;</span>
            <span>Atas Nama: <strong class="text-high-emphasis">{{ selectedBankForStatement?.account_name || '-' }}</strong></span>
            <span class="mx-2">&bull;</span>
            <span>Cabang: <strong>{{ selectedBankForStatement?.branch?.name || 'Semua Cabang' }}</strong></span>
          </VCardSubtitle>
        </VCardItem>

        <!-- Filter & Date Controls -->
        <div class="pa-4 bg-surface border-b">
          <!-- Top Row: Quick Presets & Refresh -->
          <div class="d-flex align-center justify-space-between flex-wrap gap-2 mb-3">
            <div class="d-flex align-center gap-1.5 flex-wrap">
              <span class="text-caption font-weight-bold text-medium-emphasis me-1">Rentang:</span>
              <VBtn
                v-for="range in [
                  { key: 'today', label: 'Hari Ini' },
                  { key: '7days', label: '7 Hari' },
                  { key: 'month', label: 'Bulan Ini' },
                  { key: 'year', label: 'Tahun Ini' },
                  { key: 'all', label: 'Semua' },
                ]"
                :key="range.key"
                size="small"
                :variant="statementQuickRange === range.key ? 'flat' : 'tonal'"
                :color="statementQuickRange === range.key ? 'primary' : 'secondary'"
                class="font-weight-bold"
                @click="setQuickRange(range.key)"
              >
                {{ range.label }}
              </VBtn>
            </div>

            <VBtn
              size="small"
              variant="tonal"
              color="secondary"
              prepend-icon="ri-refresh-line"
              :loading="isLoadingStatement"
              @click="fetchStatement"
            >
              Segarkan
            </VBtn>
          </div>

          <!-- Bottom Row: Filter Inputs with proper full widths -->
          <VRow dense align="center">
            <VCol cols="12" sm="3">
              <VTextField
                v-model="statementStartDate"
                type="date"
                label="Dari Tanggal"
                density="compact"
                variant="outlined"
                hide-details
                @change="fetchStatement"
              />
            </VCol>
            <VCol cols="12" sm="3">
              <VTextField
                v-model="statementEndDate"
                type="date"
                label="Sampai Tanggal"
                density="compact"
                variant="outlined"
                hide-details
                @change="fetchStatement"
              />
            </VCol>
            <VCol cols="12" sm="3">
              <VSelect
                v-model="statementTypeFilter"
                :items="[
                  { title: 'Semua Mutasi', value: 'all' },
                  { title: 'Masuk (Kredit +)', value: 'credit' },
                  { title: 'Keluar (Debet -)', value: 'debit' },
                ]"
                item-title="title"
                item-value="value"
                label="Jenis Mutasi"
                density="compact"
                variant="outlined"
                hide-details
                @update:model-value="fetchStatement"
              />
            </VCol>
            <VCol cols="12" sm="3">
              <VTextField
                v-model="statementSearch"
                placeholder="Cari transaksi..."
                prepend-inner-icon="ri-search-line"
                density="compact"
                variant="outlined"
                clearable
                hide-details
                @input="fetchStatement"
              />
            </VCol>
          </VRow>
        </div>

        <!-- 4-KPI Balance Summary (Standard Left-Border Style) -->
        <div class="pa-4 bg-surface">
          <VRow dense>
            <!-- 1. Saldo Awal -->
            <VCol cols="12" sm="6" md="3">
              <VCard elevation="1" class="pa-3 border-s-lg border-primary h-100">
                <div class="text-caption text-primary font-weight-bold">SALDO AWAL PERIODE</div>
                <div class="text-h6 font-weight-bold font-mono text-primary mt-1">
                  {{ formatCurrency(statementData.summary?.opening_balance || 0) }}
                </div>
                <div class="text-caption text-medium-emphasis mt-1">
                  Per {{ formatDate(statementData.period?.start_date) }}
                </div>
              </VCard>
            </VCol>

            <!-- 2. Total Masuk (Kredit +) -->
            <VCol cols="12" sm="6" md="3">
              <VCard elevation="1" class="pa-3 border-s-lg border-success h-100">
                <div class="text-caption text-success font-weight-bold">TOTAL MASUK (KREDIT +)</div>
                <div class="text-h6 font-weight-bold font-mono text-success mt-1">
                  +{{ formatCurrency(statementData.summary?.total_credit || 0) }}
                </div>
                <div class="text-caption text-medium-emphasis mt-1">
                  Penjualan POS, Piutang & Modal
                </div>
              </VCard>
            </VCol>

            <!-- 3. Total Keluar (Debet -) -->
            <VCol cols="12" sm="6" md="3">
              <VCard elevation="1" class="pa-3 border-s-lg border-error h-100">
                <div class="text-caption text-error font-weight-bold">TOTAL KELUAR (DEBET -)</div>
                <div class="text-h6 font-weight-bold font-mono text-error mt-1">
                  -{{ formatCurrency(statementData.summary?.total_debit || 0) }}
                </div>
                <div class="text-caption text-medium-emphasis mt-1">
                  Hutang Supplier & Kas Kecil
                </div>
              </VCard>
            </VCol>

            <!-- 4. Saldo Akhir Berjalan -->
            <VCol cols="12" sm="6" md="3">
              <VCard elevation="1" class="pa-3 border-s-lg border-info h-100">
                <div class="text-caption text-info font-weight-bold">SALDO AKHIR PERIODE</div>
                <div class="text-h6 font-weight-bold font-mono text-info mt-1">
                  {{ formatCurrency(statementData.summary?.closing_balance || 0) }}
                </div>
                <div class="text-caption text-medium-emphasis mt-1">
                  Saldo akhir per {{ formatDate(statementData.period?.end_date) }}
                </div>
              </VCard>
            </VCol>
          </VRow>
        </div>

        <!-- Ledger Table / Mutations -->
        <VCardText class="pa-4 bg-surface" style="max-height: 480px; overflow-y: auto;">
          <div v-if="isLoadingStatement" class="py-10 text-center">
            <VProgressCircular indeterminate color="primary" size="38" />
            <div class="text-caption text-medium-emphasis mt-2">Memuat riwayat buku mutasi bank...</div>
          </div>

          <div v-else-if="statementData.mutations && statementData.mutations.length > 0">
            <div class="border rounded-lg overflow-hidden">
              <table class="w-100 table-clean" style="border-collapse: collapse;">
                <thead>
                  <tr class="bg-grey-100 text-medium-emphasis text-uppercase text-caption font-weight-bold border-b">
                    <th class="pa-3 text-center" style="width: 45px;">No</th>
                    <th class="pa-3 text-center" style="width: 105px;">Tgl & Jam</th>
                    <th class="pa-3 text-left" style="width: 140px;">No. Referensi</th>
                    <th class="pa-3 text-left" style="width: 150px;">Kategori</th>
                    <th class="pa-3 text-left">Keterangan</th>
                    <th class="pa-3 text-right" style="width: 125px;">Debet (-)</th>
                    <th class="pa-3 text-right" style="width: 125px;">Kredit (+)</th>
                    <th class="pa-3 text-right" style="width: 135px;">Saldo Berjalan</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Opening Balance Row -->
                  <tr class="font-italic text-caption border-b bg-grey-50">
                    <td class="pa-3 text-center font-weight-bold text-disabled">-</td>
                    <td class="pa-3 text-center font-weight-bold text-medium-emphasis">{{ formatDate(statementData.period?.start_date) }}</td>
                    <td class="pa-3 text-left font-weight-bold text-disabled">-</td>
                    <td class="pa-3 text-left">
                      <VChip size="x-small" color="secondary" variant="flat" class="font-weight-bold">SALDO AWAL</VChip>
                    </td>
                    <td class="pa-3 text-left font-weight-medium text-medium-emphasis">Saldo awal sebelum periode mutasi berjalan</td>
                    <td class="pa-3 text-right font-mono text-disabled">-</td>
                    <td class="pa-3 text-right font-mono text-disabled">-</td>
                    <td class="pa-3 text-right font-mono font-weight-bold text-primary">
                      {{ formatCurrency(statementData.summary?.opening_balance || 0) }}
                    </td>
                  </tr>

                  <!-- Mutation Rows -->
                  <tr
                    v-for="(item, idx) in statementData.mutations"
                    :key="item.id || idx"
                    class="border-b table-row-hover text-caption"
                  >
                    <td class="pa-3 text-center text-disabled">{{ idx + 1 }}</td>
                    <td class="pa-3 text-center">
                      <div class="font-weight-bold text-high-emphasis">{{ formatDate(item.date) }}</div>
                      <div class="text-caption text-disabled" style="font-size: 10px;">{{ item.time || '' }}</div>
                    </td>
                    <td class="pa-3 font-mono font-weight-bold text-primary">
                      {{ item.reference_no }}
                    </td>
                    <td class="pa-3">
                      <VChip
                        size="x-small"
                        :color="
                          item.category.includes('Penjualan') ? 'info' :
                          (item.category.includes('Piutang') ? 'teal' :
                          (item.category.includes('Modal') ? 'purple' :
                          (item.category.includes('Hutang') ? 'warning' :
                          (item.category.includes('Kas Kecil') ? 'secondary' : 'error'))))
                        "
                        variant="tonal"
                        class="font-weight-bold"
                      >
                        {{ item.category }}
                      </VChip>
                    </td>
                    <td class="pa-3 text-high-emphasis" style="max-width: 300px;">
                      <div class="font-weight-medium text-truncate" :title="item.description">
                        {{ item.description }}
                      </div>
                    </td>
                    <td class="pa-3 text-right font-mono font-weight-bold text-error">
                      {{ item.debit > 0 ? ('-' + formatCurrency(item.debit)) : '-' }}
                    </td>
                    <td class="pa-3 text-right font-mono font-weight-bold text-success">
                      {{ item.credit > 0 ? ('+' + formatCurrency(item.credit)) : '-' }}
                    </td>
                    <td class="pa-3 text-right font-mono font-weight-bold text-high-emphasis">
                      {{ formatCurrency(item.running_balance) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div v-else class="pa-10 text-center text-medium-emphasis border rounded-lg bg-surface">
            <VAvatar color="secondary" variant="tonal" size="48" class="mb-2">
              <VIcon icon="ri-file-list-3-line" size="24" />
            </VAvatar>
            <div class="text-subtitle-2 font-weight-bold text-high-emphasis">Tidak Ada Mutasi Transaksi</div>
            <div class="text-caption text-medium-emphasis mt-1">
              Tidak ditemukan pergerakan debet/kredit pada rekening ini untuk rentang tanggal yang dipilih.
            </div>
          </div>
        </VCardText>

        <!-- Dialog Footer Action Bar -->
        <VDivider />
        <div class="pa-4 bg-surface d-flex justify-space-between align-center flex-wrap gap-2">
          <div class="text-caption text-medium-emphasis">
            Total <strong>{{ statementData.mutations?.length || 0 }}</strong> baris mutasi dalam periode laporan.
          </div>
          <div class="d-flex align-center gap-2">
            <VBtn
              variant="outlined"
              color="secondary"
              @click="isStatementModalOpen = false"
            >
              Tutup
            </VBtn>
            <VBtn
              color="primary"
              variant="flat"
              prepend-icon="ri-file-pdf-2-line"
              class="font-weight-bold"
              :loading="isDownloadingPdf"
              @click="downloadStatementPdf"
            >
              Unduh Rekening Koran (PDF)
            </VBtn>
          </div>
        </div>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.table-clean th {
  letter-spacing: 0.5px;
  font-size: 11px;
}

.table-row-hover:hover {
  background-color: rgba(var(--v-theme-primary), 0.03);
}

.month-scroll::-webkit-scrollbar {
  height: 4px;
}

.month-scroll::-webkit-scrollbar-thumb {
  background: rgba(var(--v-theme-primary), 0.2);
  border-radius: 4px;
}
</style>
