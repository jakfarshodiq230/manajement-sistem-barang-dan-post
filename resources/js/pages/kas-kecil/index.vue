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
const selectedBranch = ref(null)
const startDate = ref('')
const endDate = ref('')
const itemsPerPage = ref(10)
const page = ref(1)
const isLoading = ref(false)

const pettyCashes = ref([])
const totalItems = ref(0)
const totalExpenseAmount = ref(0)
const branches = ref([])

// Dialog State
const isAddEditDialogVisible = ref(false)
const isDeleteDialogOpen = ref(false)
const isSubmitting = ref(false)
const editingItem = ref(null)
const deletingItem = ref(null)

// Form Fields
const formCategory = ref('')
const formBranchId = ref(null)
const formAmount = ref('')
const formDate = ref(new Date().toISOString().substring(0, 10))
const formDescription = ref('')
const formReceiptImage = ref('')

const categories = [
  'Operasional Toko',
  'Listrik & PLN',
  'Air & Galon Minum',
  'Bensin & Transportasi Kurir',
  'ATK & Kertas Thermal',
  'Konsumsi & Snack Lembur',
  'Kebersihan & Perlengkapan',
  'Lain-lain',
]

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
    console.error(e)
  }
}

const fetchData = async () => {
  isLoading.value = true
  try {
    const params = {
      q: searchQuery.value || undefined,
      category: (selectedCategory.value && selectedCategory.value !== 'Semua Kategori') ? selectedCategory.value : undefined,
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
  } catch (e) {
    console.error(e)
    snackbar.show('Gagal memuat data kas kecil', 'error')
  } finally {
    isLoading.value = false
  }
}

watch([searchQuery, selectedCategory, selectedBranch, startDate, endDate], () => {
  page.value = 1
  fetchData()
})

onMounted(async () => {
  await fetchBranches()
  await fetchData()
})

const openAddDialog = () => {
  editingItem.value = null
  formCategory.value = 'Operasional Toko'
  formBranchId.value = branches.value[0]?.id || null
  formAmount.value = ''
  formDate.value = new Date().toISOString().substring(0, 10)
  formDescription.value = ''
  formReceiptImage.value = ''
  isAddEditDialogVisible.value = true
}

const openEditDialog = item => {
  editingItem.value = item
  formCategory.value = item.category
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
  if (!formAmount.value || parseInputRupiah(formAmount.value) <= 0) {
    snackbar.show('Jumlah pengeluaran harus lebih dari 0', 'warning')
    return
  }
  if (!formDescription.value.trim()) {
    snackbar.show('Keterangan pengeluaran wajib diisi', 'warning')
    return
  }

  isSubmitting.value = true
  try {
    const payload = {
      branch_id: formBranchId.value,
      category: formCategory.value,
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
      await $api('/apps/petty-cashes', {
        method: 'POST',
        body: payload,
      })
      snackbar.show('Pengeluaran kas kecil berhasil dicatat', 'success')
    }

    isAddEditDialogVisible.value = false
    await fetchData()
  } catch (e) {
    snackbar.show(e.data?.message || 'Gagal menyimpan catatan kas kecil', 'error')
  } finally {
    isSubmitting.value = false
  }
}

const confirmDelete = async () => {
  if (!deletingItem.value) return
  isSubmitting.value = true
  try {
    await $api(`/apps/petty-cashes/${deletingItem.value.id}`, { method: 'DELETE' })
    snackbar.show('Catatan kas kecil berhasil dihapus', 'success')
    isDeleteDialogOpen.value = false
    deletingItem.value = null
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
          <VIcon icon="ri-hand-coin-line" color="primary" />
          Buku Kas Kecil (Petty Cash) Cabang
        </h3>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Pencatatan dan monitoring biaya operasional harian toko dan cabang (listrik, bensin, ATK, konsumsi).
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

      <VCol cols="12" sm="6" md="4">
        <VCard class="rounded-xl border elevation-1">
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <span class="text-caption text-medium-emphasis font-weight-medium">JUMLAH TRANSAKSI</span>
              <h4 class="text-h4 font-weight-bold text-primary mt-1">
                {{ totalItems }} Nota
              </h4>
              <span class="text-caption text-disabled">Operasional & Kasir</span>
            </div>
            <VAvatar color="primary" variant="tonal" size="52" rounded="lg">
              <VIcon icon="ri-file-list-3-line" size="30" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="4">
        <VCard class="rounded-xl border elevation-1">
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <span class="text-caption text-medium-emphasis font-weight-medium">INTEGRASI LAPORAN</span>
              <h4 class="text-h5 font-weight-bold text-success mt-1">
                Laba/Rugi Otomatis
              </h4>
              <span class="text-caption text-disabled">Mengurangi Beban Kas Toko</span>
            </div>
            <VAvatar color="success" variant="tonal" size="52" rounded="lg">
              <VIcon icon="ri-pie-chart-2-line" size="30" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Filter Card -->
    <VCard class="mb-6 rounded-xl border elevation-1">
      <VCardText class="pa-4">
        <VRow class="g-3">
          <VCol cols="12" sm="6" md="3">
            <VTextField
              v-model="searchQuery"
              placeholder="Cari keterangan / no. nota..."
              density="compact"
              variant="outlined"
              prepend-inner-icon="ri-search-line"
              clearable
              hide-details
            />
          </VCol>
          <VCol cols="12" sm="6" md="3">
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
          <VCol cols="12" sm="6" md="3">
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
        <template #item.date="{ item }">
          <span class="font-weight-medium text-body-2">{{ formatDate(item.date) }}</span>
        </template>

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

        <template #item.amount="{ item }">
          <span class="font-weight-bold text-error text-subtitle-2">
            - {{ formatRupiah(item.amount) }}
          </span>
        </template>

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
      max-width="500"
    >
      <VCard>
        <VCardTitle class="bg-primary text-white pa-4 d-flex align-center justify-space-between">
          <span class="font-weight-bold">{{ editingItem ? 'Edit Pengeluaran Kas Kecil' : 'Catat Pengeluaran Kas Kecil' }}</span>
          <VBtn icon="ri-close-line" variant="text" size="small" @click="isAddEditDialogVisible = false" />
        </VCardTitle>

        <VCardText class="pa-5">
          <VRow class="g-3">
            <VCol cols="12">
              <VSelect
                v-model="formBranchId"
                :items="branches"
                item-title="name"
                item-value="id"
                label="Cabang *"
                density="compact"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12">
              <VSelect
                v-model="formCategory"
                :items="categories"
                label="Kategori Pengeluaran *"
                density="compact"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12">
              <VTextField
                :model-value="formAmount"
                label="Jumlah Pengeluaran (Rp) *"
                prefix="Rp"
                density="compact"
                variant="outlined"
                placeholder="0"
                @update:model-value="val => formAmount = formatInputRupiah(val)"
              />
            </VCol>

            <VCol cols="12">
              <VTextField
                v-model="formDate"
                type="date"
                label="Tanggal Pengeluaran *"
                density="compact"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="formDescription"
                label="Keterangan / Rincian Belanja *"
                placeholder="Contoh: Beli 2 galon air minum & token listrik toko"
                rows="3"
                density="compact"
                variant="outlined"
              />
            </VCol>
          </VRow>
        </VCardText>

        <VCardActions class="pa-4 pt-0 justify-end gap-2">
          <VBtn variant="tonal" color="secondary" @click="isAddEditDialogVisible = false">
            Batal
          </VBtn>
          <VBtn color="primary" class="font-weight-bold" :loading="isSubmitting" @click="savePettyCash">
            {{ editingItem ? 'Simpan Perubahan' : 'Catat Pengeluaran' }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Confirm Delete Dialog -->
    <VDialog
      v-model="isDeleteDialogOpen"
      max-width="400"
    >
      <VCard title="Hapus Catatan Kas Kecil">
        <VCardText>
          Apakah Anda yakin ingin menghapus catatan pengeluaran ini sejumlah <strong>{{ formatRupiah(deletingItem?.amount) }}</strong>?
        </VCardText>
        <VCardActions class="pa-4 pt-0 justify-end gap-2">
          <VBtn variant="tonal" color="secondary" @click="isDeleteDialogOpen = false">
            Batal
          </VBtn>
          <VBtn color="error" class="font-weight-bold" :loading="isSubmitting" @click="confirmDelete">
            Ya, Hapus
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
