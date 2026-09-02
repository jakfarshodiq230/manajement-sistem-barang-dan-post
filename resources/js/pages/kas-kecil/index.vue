<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'

definePage({
  meta: {
    action: 'read',
    subject: 'Transaksi',
  },
})

const snackbar = useSnackbarStore()

// State
const searchQuery = ref('')
const selectedCategory = ref(null)
const selectedPaymentMethod = ref(null)
const selectedBranch = ref(null)
const startDate = ref('')
const endDate = ref('')
const itemsPerPage = ref(10)
const page = ref(1)
const isLoading = ref(false)

const pettyCashes = ref([])
const totalItems = ref(0)
const totalExpenseAmount = ref(0)
const totalCashAmount = ref(0)
const totalBankAmount = ref(0)
const branches = ref([])
const bankAccounts = ref([])

// Dialog State
const isAddEditDialogVisible = ref(false)
const isDeleteDialogOpen = ref(false)
const isSubmitting = ref(false)
const editingItem = ref(null)
const deletingItem = ref(null)

// Form Fields
const formCategory = ref('')
const customCategoryInput = ref('')
const formPaymentMethod = ref('cash')
const formBankAccountId = ref(null)
const formBranchId = ref(null)
const formAmount = ref('')
const formDate = ref(new Date().toISOString().substring(0, 10))
const formDescription = ref('')
const formReceiptImage = ref('')

const defaultCategories = [
  'Operasional Toko',
  'Listrik & PLN',
  'Air & Galon Minum',
  'Bensin & Transportasi Kurir',
  'ATK & Kertas Thermal',
  'Konsumsi & Snack Lembur',
  'Kebersihan & Perlengkapan',
  'Lain-lain',
]

const categories = ref([...defaultCategories])

const isCustomCategory = computed(() => {
  return formCategory.value === 'Lain-lain' || formCategory.value === 'Lainnya' || formCategory.value === '+ Tambah Kategori Baru...'
})

const selectedBankAccount = computed(() => {
  if (!formBankAccountId.value || !bankAccounts.value.length) return null
  return bankAccounts.value.find(b => b.id === formBankAccountId.value) || null
})

const isBankBalanceInsufficient = computed(() => {
  if (formPaymentMethod.value !== 'bank_transfer' || !selectedBankAccount.value) return false
  const enteredAmount = parseInputRupiah(formAmount.value)
  return enteredAmount > Number(selectedBankAccount.value.current_balance || 0)
})

const formatDate = dateStr => {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return dateStr
    return new Intl.DateTimeFormat('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    }).format(d)
  } catch (e) {
    return dateStr
  }
}

const formatRupiah = val => {
  return 'Rp ' + new Intl.NumberFormat('id-ID').format(val || 0)
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

const headers = [
  { title: 'TANGGAL', key: 'date', sortable: true },
  { title: 'KATEGORI', key: 'category' },
  { title: 'KETERANGAN', key: 'description' },
  { title: 'METODE / SUMBER DANA', key: 'payment_method' },
  { title: 'CABANG', key: 'branch.name' },
  { title: 'PETUGAS', key: 'user.name' },
  { title: 'JUMLAH PENGELUARAN', key: 'amount', align: 'end' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const fetchBranches = async () => {
  try {
    const res = await $api('/apps/branches')
    branches.value = res.data || res || []
  } catch (e) {
    console.error('Failed to load branches:', e)
  }
}

const fetchBankAccounts = async () => {
  try {
    const res = await $api('/apps/bank-accounts', { params: { is_active: 1 } })
    bankAccounts.value = res.data || res || []
  } catch (e) {
    console.error('Failed to load bank accounts:', e)
  }
}

const fetchData = async () => {
  isLoading.value = true
  try {
    const params = {
      q: searchQuery.value || undefined,
      category: (selectedCategory.value && selectedCategory.value !== 'Semua Kategori') ? selectedCategory.value : undefined,
      payment_method: (selectedPaymentMethod.value && selectedPaymentMethod.value !== 'all') ? selectedPaymentMethod.value : undefined,
      branch_id: selectedBranch.value || undefined,
      start_date: startDate.value || undefined,
      end_date: endDate.value || undefined,
      itemsPerPage: itemsPerPage.value,
      page: page.value,
    }

    const res = await $api('/apps/petty-cashes', { params })
    pettyCashes.value = res.data || []
    totalItems.value = res.total || 0
    totalExpenseAmount.value = res.totalAmount || 0
    totalCashAmount.value = res.totalCash || 0
    totalBankAmount.value = res.totalBank || 0

    if (res.categories && Array.isArray(res.categories)) {
      const merged = Array.from(new Set([...defaultCategories, ...res.categories]))
      categories.value = merged
    }
  } catch (e) {
    console.error(e)
    snackbar.show('Gagal memuat data kas kecil', 'error')
  } finally {
    isLoading.value = false
  }
}

watch([searchQuery, selectedCategory, selectedPaymentMethod, selectedBranch, startDate, endDate], () => {
  page.value = 1
  fetchData()
})

onMounted(async () => {
  await Promise.all([
    fetchBranches(),
    fetchBankAccounts(),
  ])
  await fetchData()
})

const openAddDialog = () => {
  editingItem.value = null
  formCategory.value = 'Operasional Toko'
  customCategoryInput.value = ''
  formPaymentMethod.value = 'cash'
  formBankAccountId.value = bankAccounts.value[0]?.id || null
  formBranchId.value = branches.value[0]?.id || null
  formAmount.value = ''
  formDate.value = new Date().toISOString().substring(0, 10)
  formDescription.value = ''
  formReceiptImage.value = ''
  isAddEditDialogVisible.value = true
}

const openEditDialog = item => {
  editingItem.value = item
  if (item.category && !categories.value.includes(item.category)) {
    categories.value.push(item.category)
  }
  formCategory.value = item.category
  customCategoryInput.value = ''
  formPaymentMethod.value = item.payment_method || 'cash'
  formBankAccountId.value = item.bank_account_id || bankAccounts.value[0]?.id || null
  formBranchId.value = item.branch_id
  formAmount.value = formatInputRupiah(item.amount)
  formDate.value = item.date ? item.date.substring(0, 10) : new Date().toISOString().substring(0, 10)
  formDescription.value = item.description
  formReceiptImage.value = item.receipt_image || ''
  isAddEditDialogVisible.value = true
}

const openDeleteDialog = item => {
  deletingItem.value = item
  isDeleteDialogOpen.value = true
}

const savePettyCash = async () => {
  let resolvedCategory = formCategory.value

  if (isCustomCategory.value) {
    if (!customCategoryInput.value.trim()) {
      snackbar.show('Silakan ketik nama kategori baru Anda', 'warning')
      return
    }
    resolvedCategory = customCategoryInput.value.trim()
    if (!categories.value.includes(resolvedCategory)) {
      const idx = categories.value.indexOf('Lain-lain')
      if (idx !== -1) {
        categories.value.splice(idx, 0, resolvedCategory)
      } else {
        categories.value.push(resolvedCategory)
      }
    }
  }

  if (!resolvedCategory) {
    snackbar.show('Pilih atau masukkan kategori pengeluaran', 'warning')
    return
  }

  if (!formAmount.value || parseInputRupiah(formAmount.value) <= 0) {
    snackbar.show('Jumlah pengeluaran harus lebih dari 0', 'warning')
    return
  }

  if (formPaymentMethod.value === 'bank_transfer') {
    if (!formBankAccountId.value) {
      snackbar.show('Silakan pilih rekening bank sumber dana pengeluaran', 'warning')
      return
    }
    if (isBankBalanceInsufficient.value) {
      snackbar.show(`Saldo rekening ${selectedBankAccount.value?.bank_name} tidak mencukupi untuk pengeluaran ini`, 'error')
      return
    }
  }

  if (!formDescription.value.trim()) {
    snackbar.show('Keterangan pengeluaran wajib diisi', 'warning')
    return
  }

  isSubmitting.value = true
  try {
    const payload = {
      branch_id: formBranchId.value,
      category: resolvedCategory,
      payment_method: formPaymentMethod.value,
      bank_account_id: formPaymentMethod.value === 'bank_transfer' ? formBankAccountId.value : null,
      amount: parseInputRupiah(formAmount.value),
      date: formDate.value,
      description: formDescription.value,
      receipt_image: formReceiptImage.value || null,
    }

    if (editingItem.value) {
      await $api(`/apps/petty-cashes/${editingItem.value.id}`, {
        method: 'PUT',
        body: payload,
      })
      snackbar.show('Catatan kas kecil berhasil diperbarui', 'success')
    } else {
      const res = await $api('/apps/petty-cashes', {
        method: 'POST',
        body: payload,
      })
      snackbar.show(res.message || 'Pengeluaran kas kecil berhasil dicatat', 'success')
    }
    isAddEditDialogVisible.value = false
    await fetchBankAccounts()
    await fetchData()
  } catch (e) {
    console.error(e)
    const errMessage = e.response?._data?.message || e.data?.message || 'Gagal menyimpan data kas kecil'
    snackbar.show(errMessage, 'error')
  } finally {
    isSubmitting.value = false
  }
}

const confirmDelete = async () => {
  if (!deletingItem.value) return
  isSubmitting.value = true
  try {
    const res = await $api(`/apps/petty-cashes/${deletingItem.value.id}`, { method: 'DELETE' })
    snackbar.show(res.message || 'Catatan kas kecil berhasil dihapus', 'success')
    isDeleteDialogOpen.value = false
    deletingItem.value = null
    await fetchBankAccounts()
    await fetchData()
  } catch (e) {
    snackbar.show('Gagal menghapus catatan', 'error')
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div>
    <!-- Page Header & Action -->
    <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
      <div>
        <h3 class="text-h3 font-weight-bold d-flex align-center gap-2">
          <VIcon icon="ri-hand-coin-line" color="primary" size="32" />
          Buku Kas Kecil (Petty Cash) Cabang
        </h3>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Pencatatan dan monitoring biaya operasional harian toko (listrik, bensin, ATK, konsumsi) via Kas Tunai & Potong Saldo Rekening Bank.
        </p>
      </div>

      <div class="d-flex align-center gap-3">
        <VBtn
          color="primary"
          prepend-icon="ri-add-circle-line"
          class="font-weight-bold shadow-sm"
          @click="openAddDialog"
        >
          Catat Pengeluaran Baru
        </VBtn>
      </div>
    </div>

    <!-- KPI Summary Cards -->
    <VRow class="mb-6">
      <!-- 1. Total Semua Pengeluaran -->
      <VCol cols="12" sm="6" md="4">
        <VCard class="rounded-xl border elevation-1">
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <span class="text-caption text-medium-emphasis font-weight-medium">TOTAL PENGELUARAN (TERFILTER)</span>
              <h4 class="text-h4 font-weight-bold text-error mt-1">
                {{ formatRupiah(totalExpenseAmount) }}
              </h4>
              <span class="text-caption text-disabled">{{ totalItems }} Transaksi Tercatat</span>
            </div>
            <VAvatar color="error" variant="tonal" size="52" rounded="lg">
              <VIcon icon="ri-money-dollar-circle-line" size="30" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <!-- 2. Pengeluaran Kas Tunai -->
      <VCol cols="12" sm="6" md="4">
        <VCard class="rounded-xl border elevation-1">
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <span class="text-caption text-medium-emphasis font-weight-medium">DANA KAS TUNAI TOKO</span>
              <h4 class="text-h4 font-weight-bold text-warning mt-1">
                {{ formatRupiah(totalCashAmount) }}
              </h4>
              <span class="text-caption text-medium-emphasis">Kas Operasional / Laci Kasir</span>
            </div>
            <VAvatar color="warning" variant="tonal" size="52" rounded="lg">
              <VIcon icon="ri-cash-line" size="30" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <!-- 3. Pengeluaran Transfer Bank -->
      <VCol cols="12" sm="6" md="4">
        <VCard class="rounded-xl border elevation-1">
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <span class="text-caption text-medium-emphasis font-weight-medium">DANA POTONG SALDO BANK</span>
              <h4 class="text-h4 font-weight-bold text-info mt-1">
                {{ formatRupiah(totalBankAmount) }}
              </h4>
              <span class="text-caption text-medium-emphasis">Transfer via Rekening Bank</span>
            </div>
            <VAvatar color="info" variant="tonal" size="52" rounded="lg">
              <VIcon icon="ri-bank-card-line" size="30" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Filter Card -->
    <VCard class="mb-6 rounded-xl border elevation-1">
      <VCardText class="pa-4">
        <VRow class="g-3">
          <!-- Search -->
          <VCol cols="12" sm="6" md="3">
            <VTextField
              v-model="searchQuery"
              placeholder="Cari keterangan / no. nota / bank..."
              density="compact"
              variant="outlined"
              prepend-inner-icon="ri-search-line"
              clearable
              hide-details
            />
          </VCol>

          <!-- Kategori -->
          <VCol cols="12" sm="6" md="2">
            <VSelect
              v-model="selectedCategory"
              :items="['Semua Kategori', ...categories]"
              label="Filter Kategori"
              density="compact"
              variant="outlined"
              clearable
              hide-details
            />
          </VCol>

          <!-- Metode Bayar -->
          <VCol cols="12" sm="6" md="2">
            <VSelect
              v-model="selectedPaymentMethod"
              :items="[
                { title: 'Semua Metode', value: 'all' },
                { title: 'Kas Tunai Toko (Cash)', value: 'cash' },
                { title: 'Transfer Bank', value: 'bank_transfer' },
              ]"
              item-title="title"
              item-value="value"
              label="Sumber Dana"
              density="compact"
              variant="outlined"
              clearable
              hide-details
            />
          </VCol>

          <!-- Cabang -->
          <VCol cols="12" sm="6" md="2">
            <VSelect
              v-model="selectedBranch"
              :items="branches"
              item-title="name"
              item-value="id"
              label="Filter Cabang"
              density="compact"
              variant="outlined"
              clearable
              hide-details
            />
          </VCol>

          <!-- Tanggal -->
          <VCol cols="12" sm="6" md="3">
            <div class="d-flex gap-2">
              <VTextField
                v-model="startDate"
                type="date"
                label="Dari Tanggal"
                density="compact"
                variant="outlined"
                hide-details
              />
              <VTextField
                v-model="endDate"
                type="date"
                label="Sampai"
                density="compact"
                variant="outlined"
                hide-details
              />
            </div>
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <!-- Data Table -->
      <VDataTable
        :headers="headers"
        :items="pettyCashes"
        :loading="isLoading"
        :items-per-page="itemsPerPage"
        hover
        class="text-no-wrap"
      >
        <!-- Tanggal -->
        <template #item.date="{ item }">
          <span class="font-weight-medium text-body-2">{{ formatDate(item.date) }}</span>
        </template>

        <!-- Kategori -->
        <template #item.category="{ item }">
          <VChip
            size="small"
            color="primary"
            variant="tonal"
            class="font-weight-bold"
          >
            {{ item.category }}
          </VChip>
        </template>

        <!-- Metode & Sumber Dana -->
        <template #item.payment_method="{ item }">
          <div v-if="item.payment_method === 'bank_transfer'" class="d-flex align-center gap-1">
            <VChip size="small" color="info" variant="tonal" class="font-weight-medium">
              <VIcon icon="ri-bank-card-line" size="14" class="mr-1" />
              {{ item.bank_account?.bank_name || 'Transfer Bank' }}
            </VChip>
            <span v-if="item.bank_account?.account_number" class="text-caption text-medium-emphasis">
              ({{ item.bank_account.account_number }})
            </span>
          </div>
          <div v-else class="d-flex align-center gap-1">
            <VChip size="small" color="warning" variant="tonal" class="font-weight-medium">
              <VIcon icon="ri-cash-line" size="14" class="mr-1" />
              Kas Tunai Toko
            </VChip>
          </div>
        </template>

        <!-- Cabang -->
        <template #item.branch.name="{ item }">
          <span class="text-body-2">{{ item.branch?.name || '-' }}</span>
        </template>

        <!-- Petugas -->
        <template #item.user.name="{ item }">
          <span class="text-body-2">{{ item.user?.name || '-' }}</span>
        </template>

        <!-- Jumlah Pengeluaran -->
        <template #item.amount="{ item }">
          <span class="font-weight-bold text-error text-subtitle-2">
            - {{ formatRupiah(item.amount) }}
          </span>
        </template>

        <!-- Aksi -->
        <template #item.actions="{ item }">
          <div class="d-flex justify-center gap-1">
            <IconBtn size="small" color="primary" @click="openEditDialog(item)">
              <VIcon icon="ri-edit-line" size="18" />
            </IconBtn>
            <IconBtn size="small" color="error" @click="openDeleteDialog(item)">
              <VIcon icon="ri-delete-bin-line" size="18" />
            </IconBtn>
          </div>
        </template>

        <template #no-data>
          <div class="py-8 text-center text-disabled">
            <VIcon icon="ri-inbox-line" size="40" class="mb-2" />
            <p>Belum ada catatan pengeluaran kas kecil.</p>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <!-- Dialog Add/Edit Petty Cash -->
    <VDialog
      v-model="isAddEditDialogVisible"
      :fullscreen="$vuetify.display.xs"
      max-width="580"
      scrollable
    >
      <VCard class="rounded-xl overflow-hidden shadow-lg border d-flex flex-column" max-height="85vh">
        <!-- Dialog Header (Fixed) -->
        <div class="pa-4 px-5 bg-var-theme-surface border-b d-flex align-center justify-space-between flex-shrink-0">
          <div class="d-flex align-center gap-3">
            <VAvatar color="primary" variant="tonal" size="38" rounded="lg">
              <VIcon :icon="editingItem ? 'ri-edit-line' : 'ri-hand-coin-line'" size="20" />
            </VAvatar>
            <div>
              <h5 class="text-h6 font-weight-bold mb-0">
                {{ editingItem ? 'Edit Pengeluaran Kas Kecil' : 'Catat Pengeluaran Baru' }}
              </h5>
              <span class="text-caption text-medium-emphasis">
                {{ editingItem ? 'Perbarui data rincian belanja toko' : 'Catat transaksi biaya operasional toko' }}
              </span>
            </div>
          </div>
          <VBtn icon="ri-close-line" variant="text" size="small" class="rounded-lg" @click="isAddEditDialogVisible = false" />
        </div>

        <!-- Dialog Content (Scrollable) -->
        <VCardText class="pa-5 overflow-y-auto flex-grow-1" style="max-height: calc(85vh - 130px);">
          <VRow class="g-3">
            <!-- Row 1: Cabang & Tanggal -->
            <VCol cols="12" sm="6">
              <VSelect
                v-model="formBranchId"
                :items="branches"
                item-title="name"
                item-value="id"
                label="Cabang *"
                density="compact"
                variant="outlined"
                prepend-inner-icon="ri-store-2-line"
                hide-details="auto"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <VTextField
                v-model="formDate"
                type="date"
                label="Tanggal *"
                density="compact"
                variant="outlined"
                prepend-inner-icon="ri-calendar-line"
                hide-details="auto"
              />
            </VCol>

            <!-- Row 2: Sumber Dana Card Selector -->
            <VCol cols="12">
              <div class="text-caption font-weight-bold text-medium-emphasis mb-2 text-uppercase letter-spacing-1">
                Sumber Dana Pengeluaran *
              </div>
              <div class="d-grid grid-cols-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <!-- Option 1: Kas Tunai -->
                <div
                  class="payment-option-card pa-3 rounded-xl border cursor-pointer d-flex align-center gap-3"
                  :class="formPaymentMethod === 'cash' ? 'selected-cash' : 'unselected-card'"
                  @click="formPaymentMethod = 'cash'"
                >
                  <VAvatar
                    :color="formPaymentMethod === 'cash' ? 'warning' : 'secondary'"
                    :variant="formPaymentMethod === 'cash' ? 'flat' : 'tonal'"
                    size="36"
                    rounded="lg"
                  >
                    <VIcon icon="ri-cash-line" size="18" :color="formPaymentMethod === 'cash' ? 'white' : undefined" />
                  </VAvatar>
                  <div class="flex-grow-1 overflow-hidden">
                    <div class="font-weight-bold text-body-2 text-truncate" :class="formPaymentMethod === 'cash' ? 'text-warning' : ''">
                      Kas Tunai Toko
                    </div>
                    <div class="text-caption text-medium-emphasis text-truncate" style="font-size: 11px;">
                      Laci Kasir / Tunai
                    </div>
                  </div>
                  <VIcon
                    v-if="formPaymentMethod === 'cash'"
                    icon="ri-checkbox-circle-fill"
                    color="warning"
                    size="18"
                  />
                </div>

                <!-- Option 2: Transfer Bank -->
                <div
                  class="payment-option-card pa-3 rounded-xl border cursor-pointer d-flex align-center gap-3"
                  :class="formPaymentMethod === 'bank_transfer' ? 'selected-bank' : 'unselected-card'"
                  @click="formPaymentMethod = 'bank_transfer'"
                >
                  <VAvatar
                    :color="formPaymentMethod === 'bank_transfer' ? 'info' : 'secondary'"
                    :variant="formPaymentMethod === 'bank_transfer' ? 'flat' : 'tonal'"
                    size="36"
                    rounded="lg"
                  >
                    <VIcon icon="ri-bank-card-line" size="18" :color="formPaymentMethod === 'bank_transfer' ? 'white' : undefined" />
                  </VAvatar>
                  <div class="flex-grow-1 overflow-hidden">
                    <div class="font-weight-bold text-body-2 text-truncate" :class="formPaymentMethod === 'bank_transfer' ? 'text-info' : ''">
                      Transfer Bank
                    </div>
                    <div class="text-caption text-medium-emphasis text-truncate" style="font-size: 11px;">
                      Potong Rekening
                    </div>
                  </div>
                  <VIcon
                    v-if="formPaymentMethod === 'bank_transfer'"
                    icon="ri-checkbox-circle-fill"
                    color="info"
                    size="18"
                  />
                </div>
              </div>
            </VCol>

            <!-- Row 2b: Dropdown Rekening Bank jika Transfer Bank -->
            <VCol v-if="formPaymentMethod === 'bank_transfer'" cols="12">
              <div class="pa-4 rounded-xl border border-info" style="background-color: rgba(var(--v-theme-info), 0.04);">
                <div class="d-flex align-center justify-space-between mb-2">
                  <span class="text-caption font-weight-bold text-info d-flex align-center gap-1">
                    <VIcon icon="ri-bank-line" size="16" />
                    Pilih Rekening Bank Sumber Dana:
                  </span>
                  <span v-if="selectedBankAccount" class="text-caption text-medium-emphasis">
                    Saldo: <strong class="text-success font-mono">{{ formatRupiah(selectedBankAccount.current_balance) }}</strong>
                  </span>
                </div>

                <VSelect
                  v-model="formBankAccountId"
                  :items="bankAccounts"
                  item-value="id"
                  density="compact"
                  variant="outlined"
                  placeholder="-- Pilih Rekening Bank --"
                  prepend-inner-icon="ri-bank-line"
                  hide-details
                >
                  <template #selection="{ item }">
                    <span class="font-weight-medium text-body-2">
                      {{ item.raw.bank_name }} - {{ item.raw.account_number }} (a/n {{ item.raw.account_name }})
                    </span>
                  </template>
                  <template #item="{ item, props: itemProps }">
                    <VListItem v-bind="itemProps" class="py-2">
                      <template #title>
                        <div class="d-flex justify-space-between align-center">
                          <span class="font-weight-bold">{{ item.raw.bank_name }} - {{ item.raw.account_number }}</span>
                          <VChip size="x-small" color="success" variant="tonal" class="font-weight-bold font-mono">
                            Saldo: {{ formatRupiah(item.raw.current_balance) }}
                          </VChip>
                        </div>
                      </template>
                      <template #subtitle>
                        <span class="text-caption text-medium-emphasis">a/n {{ item.raw.account_name }}</span>
                      </template>
                    </VListItem>
                  </template>
                </VSelect>

                <!-- Warning jika Saldo Kurang -->
                <VAlert
                  v-if="isBankBalanceInsufficient"
                  type="error"
                  variant="tonal"
                  density="compact"
                  class="mt-3 text-caption rounded-lg"
                  icon="ri-error-warning-line"
                >
                  <strong>Saldo Tidak Cukup:</strong> Saldo rekening {{ selectedBankAccount?.bank_name }} ({{ formatRupiah(selectedBankAccount?.current_balance) }}) kurang untuk nominal pengeluaran ini.
                </VAlert>
              </div>
            </VCol>

            <!-- Row 3: Kategori Pengeluaran -->
            <VCol cols="12">
              <VSelect
                v-model="formCategory"
                :items="[...categories, '+ Tambah Kategori Baru...']"
                label="Kategori Pengeluaran *"
                density="compact"
                variant="outlined"
                prepend-inner-icon="ri-folder-line"
                hide-details="auto"
              />
            </VCol>

            <!-- Input Kategori Baru jika dipilih -->
            <VCol v-if="isCustomCategory" cols="12">
              <div class="pa-3 rounded-xl border border-primary" style="background-color: rgba(var(--v-theme-primary), 0.04);">
                <div class="d-flex align-center gap-1 mb-2 text-primary font-weight-bold text-caption">
                  <VIcon icon="ri-add-circle-line" size="16" />
                  Ketik Nama Kategori Baru:
                </div>
                <VTextField
                  v-model="customCategoryInput"
                  label="Nama Kategori Baru *"
                  placeholder="Contoh: Servis Mesin Genset / Beli Kipas Angin"
                  density="compact"
                  variant="outlined"
                  autofocus
                  hide-details
                />
                <span class="text-caption text-medium-emphasis mt-1 d-block" style="font-size: 11px;">
                  ✨ Kategori baru akan otomatis tersimpan untuk penggunaan berikutnya.
                </span>
              </div>
            </VCol>

            <!-- Row 4: Nominal Pengeluaran -->
            <VCol cols="12">
              <VTextField
                :model-value="formAmount"
                label="Jumlah Pengeluaran *"
                prefix="Rp"
                density="compact"
                variant="outlined"
                placeholder="0"
                prepend-inner-icon="ri-money-dollar-circle-line"
                hide-details="auto"
                @update:model-value="val => formAmount = formatInputRupiah(val)"
              />
            </VCol>

            <!-- Row 5: Keterangan Belanja -->
            <VCol cols="12">
              <VTextarea
                v-model="formDescription"
                label="Keterangan / Rincian Belanja *"
                placeholder="Contoh: Beli 2 galon air minum & token listrik toko"
                rows="2"
                density="compact"
                variant="outlined"
                hide-details="auto"
              />
            </VCol>
          </VRow>
        </VCardText>

        <VDivider class="flex-shrink-0" />

        <!-- Dialog Footer (Fixed) -->
        <VCardActions class="pa-3 px-5 justify-end gap-2 bg-var-theme-surface flex-shrink-0">
          <VBtn variant="tonal" color="secondary" class="rounded-lg" @click="isAddEditDialogVisible = false">
            Batal
          </VBtn>
          <VBtn
            color="primary"
            class="font-weight-bold rounded-lg px-5 shadow-xs"
            :loading="isSubmitting"
            :disabled="isBankBalanceInsufficient"
            @click="savePettyCash"
          >
            {{ editingItem ? 'Simpan Perubahan' : 'Catat Pengeluaran' }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Confirm Delete Dialog -->
    <VDialog
      v-model="isDeleteDialogOpen"
      :fullscreen="$vuetify.display.xs"
      max-width="420"
    >
      <VCard class="rounded-xl overflow-hidden shadow-lg border">
        <VCardTitle class="pa-4 bg-var-theme-surface border-b d-flex align-center gap-2 text-error">
          <VIcon icon="ri-delete-bin-line" size="22" />
          <span class="font-weight-bold text-h6">Hapus Catatan Kas Kecil</span>
        </VCardTitle>
        <VCardText class="pa-5">
          <p class="mb-2">
            Apakah Anda yakin ingin menghapus catatan pengeluaran ini sejumlah <strong class="text-error">{{ formatRupiah(deletingItem?.amount) }}</strong>?
          </p>
          <div v-if="deletingItem?.payment_method === 'bank_transfer'" class="pa-3 rounded-lg border border-info bg-var-theme-surface d-flex align-center gap-2 text-info text-caption">
            <VIcon icon="ri-information-line" size="18" />
            <span>Saldo rekening bank terkait akan otomatis dikembalikan (direfund).</span>
          </div>
        </VCardText>
        <VDivider />
        <VCardActions class="pa-4 justify-end gap-2 bg-var-theme-surface">
          <VBtn variant="tonal" color="secondary" class="rounded-lg" @click="isDeleteDialogOpen = false">
            Batal
          </VBtn>
          <VBtn color="error" class="font-weight-bold rounded-lg px-4" :loading="isSubmitting" @click="confirmDelete">
            Ya, Hapus
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.payment-option-card {
  border: 1.5px solid rgba(var(--v-border-color), var(--v-border-opacity));
  background-color: rgb(var(--v-theme-surface));
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  user-select: none;
}

.payment-option-card:hover {
  border-color: rgba(var(--v-theme-primary), 0.5);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.selected-cash {
  border: 2px solid rgb(var(--v-theme-warning)) !important;
  background-color: rgba(var(--v-theme-warning), 0.06) !important;
}

.selected-bank {
  border: 2px solid rgb(var(--v-theme-info)) !important;
  background-color: rgba(var(--v-theme-info), 0.06) !important;
}

.unselected-card {
  opacity: 0.75;
}

.unselected-card:hover {
  opacity: 1;
}

.letter-spacing-1 {
  letter-spacing: 0.5px;
}
</style>

