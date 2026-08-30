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
  <div class="bank-accounts-page">
    <!-- Top Header -->
    <div class="d-flex justify-space-between align-center mb-5 flex-wrap gap-4">
      <div>
        <h4 class="text-h4 font-weight-bold mb-1 d-flex align-center gap-2">
          <VIcon icon="ri-bank-card-line" color="primary" size="32" />
          Rekening Bank & Pendapatan Penjualan
        </h4>
        <p class="text-body-1 text-medium-emphasis mb-0">
          Monitoring buku kas & rekening bank, rincian omzet per bulan, serta penetapan rekening penerima kasir POS.
        </p>
      </div>

      <div class="d-flex align-center gap-3">
        <VBtn
          color="primary"
          prepend-icon="ri-add-line"
          class="font-weight-bold"
          @click="openAddDrawer"
        >
          Tambah Rekening Bank
        </VBtn>
      </div>
    </div>

    <!-- Period Filter Toolbar: Year Selector & 12 Month Bar -->
    <VCard class="mb-6 rounded-xl border shadow-sm period-filter-card">
      <VCardText class="pa-4">
        <div class="d-flex align-center justify-space-between flex-wrap gap-4 mb-3">
          <!-- Left: Year Picker & Quick Jump -->
          <div class="d-flex align-center gap-3 flex-wrap">
            <div class="d-flex align-center gap-2">
              <VIcon icon="ri-calendar-event-line" color="primary" size="22" />
              <span class="font-weight-bold text-subtitle-2 text-uppercase letter-spacing-1">
                Pilih Periode Tahun:
              </span>
            </div>

            <div style="width: 140px;">
              <VSelect
                v-model="selectedYear"
                :items="availableYears"
                density="compact"
                variant="outlined"
                class="font-weight-bold font-mono"
                hide-details
              />
            </div>

            <VBtn
              size="small"
              variant="tonal"
              color="primary"
              prepend-icon="ri-time-line"
              @click="resetToCurrentPeriod"
            >
              Bulan Sekarang
            </VBtn>
          </div>

          <!-- Right: Branch & Type Quick Filters -->
          <div class="d-flex align-center gap-3 flex-wrap">
            <div style="min-width: 180px;">
              <VSelect
                v-model="selectedBranch"
                :items="[{ title: 'Semua Cabang', value: null }, ...branches.map(b => ({ title: b.name, value: b.id }))]"
                item-title="title"
                item-value="value"
                density="compact"
                variant="outlined"
                placeholder="Pilih Cabang"
                hide-details
              />
            </div>

            <div style="min-width: 160px;">
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
                density="compact"
                variant="outlined"
                placeholder="Tipe Akun"
                hide-details
              />
            </div>
          </div>
        </div>

        <!-- 12 Months Horizontal Selector Bar -->
        <div class="month-selector-wrapper pt-2 border-t">
          <div class="d-flex gap-2 overflow-x-auto pb-1 month-scroll">
            <button
              v-for="m in monthNames"
              :key="m.value"
              type="button"
              class="month-btn"
              :class="{ 'month-btn-active': selectedMonth === m.value }"
              @click="selectedMonth = m.value"
            >
              <span class="month-name">{{ m.name }}</span>
              <span class="month-year">{{ selectedYear }}</span>
            </button>
          </div>
        </div>
      </VCardText>
    </VCard>

    <!-- Monthly KPI Cards Overview -->
    <VRow class="mb-6">
      <!-- 1. Total Saldo Berjalan Semua Bank -->
      <VCol cols="12" sm="6" md="3">
        <VCard class="h-100 rounded-xl border shadow-xs">
          <VCardText class="d-flex align-center justify-space-between pa-4">
            <div>
              <span class="text-caption text-medium-emphasis font-weight-medium">Total Saldo Bank (Berjalan):</span>
              <h5 class="text-h5 font-weight-bold font-mono text-primary mt-1">
                {{ formatCurrency(summary.total_balance) }}
              </h5>
              <span class="text-caption text-medium-emphasis">
                {{ summary.active_accounts }} Rekening Bank Aktif
              </span>
            </div>
            <VAvatar color="primary" variant="tonal" size="46" class="rounded-xl">
              <VIcon icon="ri-wallet-3-line" size="22" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <!-- 2. Penerimaan Bulan Ini -->
      <VCol cols="12" sm="6" md="3">
        <VCard class="h-100 rounded-xl border shadow-xs">
          <VCardText class="d-flex align-center justify-space-between pa-4">
            <div>
              <span class="text-caption text-medium-emphasis font-weight-medium">
                Penerimaan {{ currentMonthName }} {{ selectedYear }}:
              </span>
              <h5 class="text-h5 font-weight-bold font-mono text-success mt-1">
                {{ formatCurrency(summary.selected_month_received) }}
              </h5>
              <span class="text-caption text-success font-weight-medium">
                {{ summary.selected_month_tx_count }} Transaksi Masuk
              </span>
            </div>
            <VAvatar color="success" variant="tonal" size="46" class="rounded-xl">
              <VIcon icon="ri-arrow-down-circle-line" size="22" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <!-- 3. Penerimaan Tahun Terpilih -->
      <VCol cols="12" sm="6" md="3">
        <VCard class="h-100 rounded-xl border shadow-xs">
          <VCardText class="d-flex align-center justify-space-between pa-4">
            <div>
              <span class="text-caption text-medium-emphasis font-weight-medium">
                Total Omzet Tahun {{ selectedYear }}:
              </span>
              <h5 class="text-h5 font-weight-bold font-mono text-high-emphasis mt-1">
                {{ formatCurrency(summary.selected_year_received) }}
              </h5>
              <span class="text-caption text-medium-emphasis">
                Akumulasi Seluruh Rekening
              </span>
            </div>
            <VAvatar color="info" variant="tonal" size="46" class="rounded-xl">
              <VIcon icon="ri-line-chart-line" size="22" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <!-- 4. Rata-rata per Transaksi -->
      <VCol cols="12" sm="6" md="3">
        <VCard class="h-100 rounded-xl border shadow-xs">
          <VCardText class="d-flex align-center justify-space-between pa-4">
            <div>
              <span class="text-caption text-medium-emphasis font-weight-medium">
                Rata-rata / Bon ({{ currentMonthName }}):
              </span>
              <h5 class="text-h5 font-weight-bold font-mono text-warning mt-1">
                {{ formatCurrency(summary.selected_month_tx_count > 0 ? (summary.selected_month_received / summary.selected_month_tx_count) : 0) }}
              </h5>
              <span class="text-caption text-medium-emphasis">
                Tiket Pembayaran Bank
              </span>
            </div>
            <VAvatar color="warning" variant="tonal" size="46" class="rounded-xl">
              <VIcon icon="ri-money-dollar-circle-line" size="22" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Main View Switcher & Search Bar -->
    <div class="d-flex justify-space-between align-center mb-4 flex-wrap gap-3">
      <VTabs v-model="activeTab" density="compact" class="border-b-0">
        <VTab value="cards" class="font-weight-bold">
          <VIcon icon="ri-grid-fill" size="18" class="me-1" />
          Kartu ATM & QRIS ({{ bankAccounts.length }})
        </VTab>
        <VTab value="table" class="font-weight-bold">
          <VIcon icon="ri-table-line" size="18" class="me-1" />
          Tabel Rekap Bulan {{ currentMonthName }}
        </VTab>
      </VTabs>

      <div class="d-flex align-center gap-3">
        <div style="width: 260px;">
          <VTextField
            v-model="searchQuery"
            placeholder="Cari nama bank, no. rek..."
            prepend-inner-icon="ri-search-line"
            density="compact"
            variant="outlined"
            clearable
            hide-details
          />
        </div>
        <VBtn
          color="secondary"
          variant="tonal"
          size="small"
          icon="ri-refresh-line"
          :loading="isLoading"
          title="Segarkan Data"
          @click="fetchBankAccounts"
        />
      </div>
    </div>

    <!-- TAB 1: Visual ATM Cards Gallery Grid -->
    <div v-if="activeTab === 'cards'">
      <VRow v-if="bankAccounts.length > 0">
        <VCol
          v-for="account in bankAccounts"
          :key="account.id"
          cols="12"
          sm="6"
          lg="4"
        >
          <div
            class="atm-card rounded-xl text-white position-relative overflow-hidden shadow-md"
            :style="{ background: `linear-gradient(135deg, ${account.color || '#0066AE'} 0%, #151a30 100%)` }"
          >
            <!-- Watermark Pattern -->
            <div class="card-decor-circle-1" />
            <div class="card-decor-circle-2" />

            <div class="pa-5 position-relative z-1">
              <!-- Top Row: Bank Header & Badges -->
              <div class="d-flex justify-space-between align-start mb-3">
                <div>
                  <span class="text-caption text-white text-opacity-75 text-uppercase font-weight-bold letter-spacing-1 d-block">
                    {{ account.type === 'qris' ? 'QRIS MERCHANT' : (account.type === 'edc_debit' ? 'EDC KASIR' : 'REKENING BANK') }}
                  </span>
                  <h5 class="text-h5 font-weight-bold text-white mb-0">
                    {{ account.bank_name }}
                  </h5>
                </div>
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
                    variant="flat"
                    class="font-weight-bold"
                  >
                    {{ account.is_active ? 'AKTIF' : 'NON-AKTIF' }}
                  </VChip>
                </div>
              </div>

              <!-- Chip & QRIS Preview Button -->
              <div class="d-flex justify-space-between align-center mb-3">
                <div class="atm-chip shadow-xs" />
                <div v-if="account.qris_image">
                  <VBtn
                    size="x-small"
                    color="white"
                    variant="flat"
                    class="text-primary font-weight-bold shadow-xs"
                    prepend-icon="ri-qr-code-line"
                    @click="openQrisPreview(account)"
                  >
                    QRIS Barcode
                  </VBtn>
                </div>
              </div>

              <!-- Account Number & 1-Click Copy -->
              <div class="mb-3">
                <span class="text-caption text-white text-opacity-75 d-block">Nomor Rekening / ID:</span>
                <div class="d-flex align-center gap-2">
                  <span class="text-h6 font-mono font-weight-bold text-white letter-spacing-2">
                    {{ account.account_number || '-' }}
                  </span>
                  <VBtn
                    v-if="account.account_number"
                    icon="ri-file-copy-line"
                    size="x-small"
                    variant="text"
                    color="white"
                    title="Salin Nomor Rekening"
                    @click="copyToClipboard(account.account_number, account.bank_name)"
                  />
                </div>
              </div>

              <!-- Account Holder & Balances -->
              <div class="d-flex justify-space-between align-end pt-2 border-t border-white border-opacity-25 mb-3">
                <div>
                  <span class="text-caption text-white text-opacity-75 d-block">Atas Nama:</span>
                  <div class="text-subtitle-2 font-weight-bold text-white text-uppercase">
                    {{ account.account_name || '-' }}
                  </div>
                  <span class="text-caption text-white text-opacity-75 font-weight-medium">
                    {{ account.branch?.name || 'Semua Cabang' }}
                  </span>
                </div>
                <div class="text-right">
                  <span class="text-caption text-white text-opacity-75 d-block">Saldo Berjalan:</span>
                  <div class="text-h6 font-mono font-weight-bold text-white">
                    {{ formatCurrency(account.current_balance) }}
                  </div>
                </div>
              </div>

              <!-- Monthly Highlight Box -->
              <div class="month-highlight-box pa-2 rounded-lg bg-black bg-opacity-25 border border-white border-opacity-15 mb-3">
                <div class="d-flex justify-space-between align-center text-caption">
                  <span class="text-white text-opacity-90">
                    <VIcon icon="ri-calendar-check-line" size="14" class="me-1" />
                    Omzet {{ currentMonthName }} {{ selectedYear }}:
                  </span>
                  <span class="font-weight-bold font-mono text-white">
                    {{ formatCurrency(account.month_received || 0) }}
                  </span>
                </div>
                <div class="d-flex justify-space-between align-center text-caption mt-1">
                  <span class="text-white text-opacity-75">
                    Transaksi Masuk Bulan Ini:
                  </span>
                  <span class="font-mono text-white text-opacity-90">
                    {{ account.month_tx_count || 0 }} Transaksi
                  </span>
                </div>
              </div>

              <!-- Card Action Buttons -->
              <div class="d-flex justify-space-between align-center pt-2 border-t border-white border-opacity-15">
                <VBtn
                  size="x-small"
                  variant="tonal"
                  color="white"
                  prepend-icon="ri-history-line"
                  @click="openRecentSales(account)"
                >
                  Mutasi Bon
                </VBtn>

                <div class="d-flex gap-1">
                  <VBtn
                    size="x-small"
                    variant="tonal"
                    color="white"
                    prepend-icon="ri-edit-line"
                    @click="openEditDrawer(account)"
                  >
                    Edit
                  </VBtn>
                  <VBtn
                    size="x-small"
                    variant="tonal"
                    color="error"
                    icon="ri-delete-bin-line"
                    @click="deleteBankAccount(account)"
                  />
                </div>
              </div>
            </div>
          </div>
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
                    variant="tonal"
                    color="primary"
                    icon="ri-history-line"
                    title="Lihat Mutasi Bon"
                    @click="openRecentSales(acc)"
                  />
                  <VBtn
                    size="x-small"
                    variant="tonal"
                    color="secondary"
                    icon="ri-edit-line"
                    title="Edit Rekening"
                    @click="openEditDrawer(acc)"
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
  </div>
</template>

<style scoped>
.period-filter-card {
  background: linear-gradient(135deg, rgba(var(--v-theme-surface), 1) 0%, rgba(var(--v-theme-primary), 0.03) 100%);
}

.month-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 8px 16px;
  min-width: 90px;
  border-radius: 10px;
  border: 1px solid rgba(var(--v-theme-primary), 0.15);
  background: rgba(var(--v-theme-surface), 1);
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  user-select: none;
}

.month-btn:hover {
  background: rgba(var(--v-theme-primary), 0.08);
  border-color: rgba(var(--v-theme-primary), 0.4);
  transform: translateY(-2px);
}

.month-btn-active {
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)) 0%, #4338ca 100%) !important;
  border-color: transparent !important;
  box-shadow: 0 4px 12px rgba(var(--v-theme-primary), 0.35);
  transform: translateY(-2px);
}

.month-btn-active .month-name,
.month-btn-active .month-year {
  color: #ffffff !important;
  font-weight: 700;
}

.month-name {
  font-size: 13px;
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), 0.87);
}

.month-year {
  font-size: 10px;
  color: rgba(var(--v-theme-on-surface), 0.55);
}

.atm-card {
  min-height: 220px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.25);
  transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s ease;
}

.atm-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 16px 32px -5px rgba(0, 0, 0, 0.35);
}

.card-decor-circle-1 {
  position: absolute;
  top: -50px;
  right: -50px;
  width: 160px;
  height: 160px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.07);
  pointer-events: none;
}

.card-decor-circle-2 {
  position: absolute;
  bottom: -60px;
  left: -40px;
  width: 180px;
  height: 180px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.04);
  pointer-events: none;
}

.atm-chip {
  width: 38px;
  height: 28px;
  border-radius: 5px;
  background: linear-gradient(135deg, #ffd700 0%, #d4af37 100%);
  border: 1px solid rgba(0, 0, 0, 0.25);
}

.color-preset-dot {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: 2px solid #fff;
  box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.color-indicator-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.bg-gradient-header {
  background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.05) 0%, rgba(var(--v-theme-surface), 1) 100%);
}

.table-clean th {
  letter-spacing: 0.5px;
  font-size: 11px;
}

.table-row-hover:hover {
  background-color: rgba(var(--v-theme-primary), 0.02);
}

.month-scroll::-webkit-scrollbar {
  height: 4px;
}

.month-scroll::-webkit-scrollbar-thumb {
  background: rgba(var(--v-theme-primary), 0.2);
  border-radius: 4px;
}
</style>
