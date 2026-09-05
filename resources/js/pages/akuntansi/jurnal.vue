<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { paginationMeta } from '@/utils/paginationMeta'

definePage({
  meta: {
    action: 'read',
    subject: 'Akuntansi',
  },
})

const isLoading = ref(true)
const isSaving = ref(false)
const searchQuery = ref('')
const selectedBranch = ref('all')
const selectedRefType = ref('all')
const startDate = ref('')
const endDate = ref('')
const page = ref(1)
const itemsPerPage = ref(15)
const totalItems = ref(0)
const branches = ref([])
const accountsList = ref([])
const journals = ref([])
const isDownloadingPdf = ref(false)

const tableHeaders = [
  { title: 'TANGGAL', key: 'entry_date' },
  { title: 'NO. JURNAL', key: 'entry_number' },
  { title: 'SUMBER / CABANG', key: 'reference_type' },
  { title: 'KETERANGAN / MEMO', key: 'notes' },
  { title: 'RINCIAN AKUN (DEBIT / KREDIT)', key: 'items', sortable: false },
  { title: 'TOTAL NILAI', key: 'total_amount', align: 'end', sortable: false },
]

// Manual Journal Dialog State
const manualDialog = ref(false)
const manualForm = ref({
  entry_date: new Date().toISOString().substring(0, 10),
  branch_id: null,
  notes: '',
  items: [
    { account_id: null, debit: 0, credit: 0, memo: '' },
    { account_id: null, debit: 0, credit: 0, memo: '' },
  ],
})

const formatCurrency = val => {
  if (val === null || val === undefined || isNaN(val)) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val || 0)
}

const getRefBadgeColor = type => {
  switch (type) {
    case 'Sale': return 'success'
    case 'GoodsReceipt': return 'primary'
    case 'PayablePayment': return 'error'
    case 'ReceivablePayment': return 'secondary'
    case 'PettyCash': return 'warning'
    case 'BranchCapital': return 'info'
    default: return 'default'
  }
}

const getRefLabel = type => {
  switch (type) {
    case 'Sale': return 'Penjualan POS'
    case 'GoodsReceipt': return 'Penerimaan Barang (GR)'
    case 'PayablePayment': return 'Bayar Hutang Supplier'
    case 'ReceivablePayment': return 'Setoran Piutang'
    case 'PettyCash': return 'Kas Kecil Operasional'
    case 'BranchCapital': return 'Modal Cabang / ROI'
    case 'Manual': return 'Jurnal Penyesuaian Manual'
    default: return type
  }
}

const fetchBranches = async () => {
  try {
    const res = await $api('/apps/branches', { params: { itemsPerPage: 100 } })
    branches.value = res.branches || res.data || (Array.isArray(res) ? res : [])
  } catch (e) {
    console.error('Error fetching branches:', e)
  }
}

const fetchAccounts = async () => {
  try {
    const res = await $api('/apps/accounting/accounts')
    accountsList.value = res.data || []
  } catch (e) {
    console.error('Error fetching accounts:', e)
  }
}

const fetchJournals = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
    }
    if (searchQuery.value) params.q = searchQuery.value
    if (selectedBranch.value && selectedBranch.value !== 'all') params.branch_id = selectedBranch.value
    if (selectedRefType.value && selectedRefType.value !== 'all') params.reference_type = selectedRefType.value
    if (startDate.value) params.start_date = startDate.value
    if (endDate.value) params.end_date = endDate.value

    const res = await $api('/apps/accounting/journals', { params })
    if (res.success) {
      journals.value = res.data || []
      totalItems.value = res.total || 0
    }
  } catch (e) {
    console.error('Failed to load journals:', e)
  } finally {
    isLoading.value = false
  }
}

// Download PDF
const downloadJournalPdf = async () => {
  isDownloadingPdf.value = true
  try {
    const params = new URLSearchParams()
    if (selectedBranch.value && selectedBranch.value !== 'all') params.append('branch_id', selectedBranch.value)
    if (selectedRefType.value && selectedRefType.value !== 'all') params.append('reference_type', selectedRefType.value)
    if (startDate.value) params.append('start_date', startDate.value)
    if (endDate.value) params.append('end_date', endDate.value)
    if (searchQuery.value) params.append('search', searchQuery.value)

    const token = useCookie('accessToken').value
    const res = await fetch(`/api/apps/accounting/journals/export-pdf?${params.toString()}`, {
      headers: {
        'Authorization': `Bearer ${token || ''}`,
        'Accept': 'application/pdf',
      },
    })

    if (!res.ok) {
      const errData = await res.json().catch(() => ({}))
      throw new Error(errData.message || 'Gagal mengunduh dokumen Jurnal Umum PDF')
    }

    const blob = await res.blob()
    const url = window.URL.createObjectURL(blob)

    window.open(url, '_blank')

    const a = document.createElement('a')
    a.href = url
    a.download = `Jurnal_Umum_${startDate.value || 'all'}_sd_${endDate.value || 'all'}.pdf`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)

    setTimeout(() => window.URL.revokeObjectURL(url), 10000)
  } catch (err) {
    console.error(err)
    alert(err.message || 'Gagal mencetak PDF Jurnal Umum')
  } finally {
    isDownloadingPdf.value = false
  }
}

// Calculations for Manual Voucher Dialog
const manualTotalDebit = computed(() => {
  return manualForm.value.items.reduce((acc, curr) => acc + (Number(curr.debit) || 0), 0)
})

const manualTotalCredit = computed(() => {
  return manualForm.value.items.reduce((acc, curr) => acc + (Number(curr.credit) || 0), 0)
})

const manualIsBalanced = computed(() => {
  return manualTotalDebit.value > 0 && Math.abs(manualTotalDebit.value - manualTotalCredit.value) < 0.01
})

const addManualRow = () => {
  manualForm.value.items.push({ account_id: null, debit: 0, credit: 0, memo: '' })
}

const removeManualRow = index => {
  if (manualForm.value.items.length > 2) {
    manualForm.value.items.splice(index, 1)
  }
}

const openManualModal = () => {
  manualForm.value = {
    entry_date: new Date().toISOString().substring(0, 10),
    branch_id: selectedBranch.value !== 'all' ? selectedBranch.value : null,
    notes: '',
    items: [
      { account_id: null, debit: 0, credit: 0, memo: '' },
      { account_id: null, debit: 0, credit: 0, memo: '' },
    ],
  }
  manualDialog.value = true
}

const saveManualJournal = async () => {
  if (!manualIsBalanced.value || !manualForm.value.notes) {
    alert('Harap pastikan jurnal memiliki memo dan nilai Debit sama dengan Kredit.')
    return
  }

  isSaving.value = true
  try {
    await $api('/apps/accounting/journals/manual', {
      method: 'POST',
      body: manualForm.value,
    })
    manualDialog.value = false
    await fetchJournals()
  } catch (e) {
    alert(e.data?.message || 'Gagal menyimpan Jurnal Penyesuaian Manual.')
  } finally {
    isSaving.value = false
  }
}

watch([searchQuery, selectedBranch, selectedRefType, startDate, endDate, page], () => {
  fetchJournals()
})

onMounted(() => {
  fetchBranches()
  fetchAccounts()
  fetchJournals()
})
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold mb-1 text-high-emphasis">
          Jurnal Umum (General Journal)
        </h4>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Riwayat pencatatan debit dan kredit transaksi otomatis serta input Jurnal Penyesuaian Manual (*Journal Voucher*).
        </p>
      </div>

      <div class="d-flex flex-wrap align-center gap-3">
        <VBtn
          color="secondary"
          variant="tonal"
          prepend-icon="ri-arrow-left-line"
          to="/akuntansi"
        >
          Kembali ke Hub
        </VBtn>
        <VBtn
          color="info"
          variant="tonal"
          prepend-icon="ri-file-pdf-2-line"
          :loading="isDownloadingPdf"
          @click="downloadJournalPdf"
        >
          Cetak PDF
        </VBtn>
        <VBtn
          color="primary"
          prepend-icon="ri-add-line"
          @click="openManualModal"
        >
          Buat Jurnal Penyesuaian
        </VBtn>
      </div>
    </div>

    <!-- Filter Card -->
    <VCard elevation="1" class="border rounded-lg mb-6">
      <VCardText class="pa-4">
        <VRow>
          <VCol cols="12" sm="6" md="3">
            <VTextField
              v-model="searchQuery"
              placeholder="Cari Nomor Jurnal / Memo..."
              prepend-inner-icon="ri-search-line"
              density="compact"
              variant="outlined"
              clearable
              hide-details
            />
          </VCol>

          <VCol cols="12" sm="6" md="3">
            <VSelect
              v-model="selectedRefType"
              :items="[
                { title: 'Semua Sumber Transaksi', value: 'all' },
                { title: 'Penjualan POS', value: 'Sale' },
                { title: 'Penerimaan Barang (GR)', value: 'GoodsReceipt' },
                { title: 'Bayar Hutang Supplier', value: 'PayablePayment' },
                { title: 'Setoran Piutang', value: 'ReceivablePayment' },
                { title: 'Kas Kecil Toko', value: 'PettyCash' },
                { title: 'Modal Cabang / ROI', value: 'BranchCapital' },
                { title: 'Jurnal Penyesuaian Manual', value: 'Manual' },
              ]"
              item-title="title"
              item-value="value"
              density="compact"
              variant="outlined"
              prepend-inner-icon="ri-filter-line"
              hide-details
            />
          </VCol>

          <VCol cols="12" sm="6" md="3">
            <VSelect
              v-if="branches.length > 1"
              v-model="selectedBranch"
              :items="[{ id: 'all', name: 'Semua Cabang' }, ...branches]"
              item-title="name"
              item-value="id"
              density="compact"
              variant="outlined"
              prepend-inner-icon="ri-store-2-line"
              hide-details
            />
          </VCol>

          <VCol cols="12" sm="6" md="3">
            <div class="d-flex gap-2">
              <VTextField
                v-model="startDate"
                type="date"
                density="compact"
                variant="outlined"
                hide-details
              />
              <VTextField
                v-model="endDate"
                type="date"
                density="compact"
                variant="outlined"
                hide-details
              />
            </div>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Table of Journals -->
    <VCard elevation="1" class="border rounded-lg">
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="tableHeaders"
        :items="journals"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        @update:options="fetchJournals"
      >
        <template #item.entry_date="{ item }">
          <span class="font-mono text-body-2">{{ item.entry_date }}</span>
        </template>

        <template #item.entry_number="{ item }">
          <span class="font-mono font-weight-bold text-primary">{{ item.entry_number }}</span>
        </template>

        <template #item.reference_type="{ item }">
          <VChip :color="getRefBadgeColor(item.reference_type)" size="small" variant="tonal" class="font-weight-medium">
            {{ getRefLabel(item.reference_type) }}
          </VChip>
          <div v-if="item.branch" class="text-caption text-medium-emphasis mt-1">
            {{ item.branch.name }}
          </div>
        </template>

        <template #item.notes="{ item }">
          <div class="text-body-2 font-weight-medium" style="max-width: 260px; white-space: normal;">
            {{ item.notes || '-' }}
          </div>
        </template>

        <template #item.items="{ item }">
          <div class="border rounded bg-var-theme-surface pa-2 my-1" style="min-width: 300px;">
            <div
              v-for="line in item.items"
              :key="line.id"
              class="d-flex justify-space-between align-center text-caption py-1 border-b-dashed"
            >
              <div>
                <span class="font-mono font-weight-bold text-primary">{{ line.account?.code }}</span>
                <span class="ms-1" :class="{ 'ps-3 text-medium-emphasis': Number(line.credit) > 0 }">
                  {{ line.account?.name }}
                </span>
              </div>
              <div class="font-mono font-weight-semibold">
                <span v-if="Number(line.debit) > 0" class="text-primary font-weight-bold">
                  (D) {{ formatCurrency(line.debit) }}
                </span>
                <span v-else class="text-warning font-weight-bold">
                  (K) {{ formatCurrency(line.credit) }}
                </span>
              </div>
            </div>
          </div>
        </template>

        <template #item.total_amount="{ item }">
          <span class="font-mono font-weight-bold text-body-2 text-primary">
            {{ formatCurrency(item.items?.reduce((acc, curr) => acc + Number(curr.debit), 0)) }}
          </span>
        </template>

        <template #no-data>
          <div class="pa-6 text-center text-medium-emphasis">
            <VIcon icon="ri-inbox-line" size="36" class="mb-2 text-disabled" />
            <div>Belum ada data jurnal transaksi pada periode ini.</div>
          </div>
        </template>

        <!-- Pagination -->
        <template #bottom>
          <VDivider />

          <div class="d-flex justify-end flex-wrap gap-x-6 px-4 py-2">
            <div class="d-flex align-center gap-x-2 text-medium-emphasis text-body-2">
              Baris per halaman:
              <VSelect
                v-model="itemsPerPage"
                class="per-page-select"
                variant="plain"
                density="compact"
                :items="[10, 15, 25, 50, 100]"
                hide-details
              />
            </div>

            <p class="d-flex align-center text-body-2 text-high-emphasis me-2 mb-0">
              {{ paginationMeta({ page, itemsPerPage }, totalItems) }}
            </p>

            <div class="d-flex gap-x-2 align-center me-2">
              <VBtn
                class="flip-in-rtl"
                icon="ri-arrow-left-s-line"
                variant="text"
                density="comfortable"
                color="high-emphasis"
                :disabled="page <= 1"
                @click="page <= 1 ? page = 1 : page--"
              />

              <VBtn
                class="flip-in-rtl"
                icon="ri-arrow-right-s-line"
                density="comfortable"
                variant="text"
                color="high-emphasis"
                :disabled="page >= Math.ceil(totalItems / itemsPerPage)"
                @click="page >= Math.ceil(totalItems / itemsPerPage) ? page = Math.ceil(totalItems / itemsPerPage) : page++"
              />
            </div>
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Dialog Input Jurnal Penyesuaian Manual -->
    <VDialog v-model="manualDialog" max-width="850">
      <VCard class="pa-4">
        <VCardTitle class="px-0 pt-0 pb-2 font-weight-bold d-flex justify-space-between align-center">
          <span>Input Jurnal Penyesuaian Manual (*Journal Voucher*)</span>
          <VChip :color="manualIsBalanced ? 'success' : 'error'" size="small" variant="tonal" class="font-weight-bold">
            {{ manualIsBalanced ? 'SEIMBANG (BALANCED)' : 'BELUM SEIMBANG' }}
          </VChip>
        </VCardTitle>
        <VDivider class="mb-4" />

        <VCardText class="px-0 py-2">
          <VRow class="mb-4">
            <VCol cols="12" sm="4">
              <VTextField
                v-model="manualForm.entry_date"
                type="date"
                label="Tanggal Jurnal *"
                density="compact"
                variant="outlined"
                required
              />
            </VCol>
            <VCol cols="12" sm="4">
              <VSelect
                v-model="manualForm.branch_id"
                :items="branches"
                item-title="name"
                item-value="id"
                label="Cabang (Opsional)"
                density="compact"
                variant="outlined"
                clearable
              />
            </VCol>
            <VCol cols="12" sm="4">
              <VTextField
                v-model="manualForm.notes"
                label="Memo / Keterangan Transaksi *"
                placeholder="misal: Penyesuaian Biaya Listrik"
                density="compact"
                variant="outlined"
                required
              />
            </VCol>
          </VRow>

          <!-- Dynamic Item Rows -->
          <div class="border rounded-lg pa-3 bg-var-theme-surface mb-3">
            <div class="d-flex justify-space-between align-center mb-2 px-1">
              <span class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Daftar Akun Debit & Kredit</span>
              <VBtn size="x-small" color="primary" variant="tonal" prepend-icon="ri-add-line" @click="addManualRow">
                Tambah Baris
              </VBtn>
            </div>

            <div v-for="(row, idx) in manualForm.items" :key="idx" class="d-flex flex-wrap gap-2 align-center mb-2">
              <div style="flex: 2; min-width: 220px;">
                <VSelect
                  v-model="row.account_id"
                  :items="accountsList"
                  :item-title="item => `${item.code} - ${item.name}`"
                  item-value="id"
                  placeholder="Pilih Akun COA *"
                  density="compact"
                  variant="outlined"
                  hide-details
                />
              </div>
              <div style="flex: 1; min-width: 140px;">
                <VTextField
                  v-model="row.debit"
                  type="number"
                  placeholder="Debit (Rp)"
                  prefix="D:"
                  density="compact"
                  variant="outlined"
                  hide-details
                />
              </div>
              <div style="flex: 1; min-width: 140px;">
                <VTextField
                  v-model="row.credit"
                  type="number"
                  placeholder="Kredit (Rp)"
                  prefix="K:"
                  density="compact"
                  variant="outlined"
                  hide-details
                />
              </div>
              <div style="flex: 1.5; min-width: 150px;">
                <VTextField
                  v-model="row.memo"
                  placeholder="Catatan baris (opsional)"
                  density="compact"
                  variant="outlined"
                  hide-details
                />
              </div>
              <div>
                <VBtn
                  icon="ri-delete-bin-line"
                  size="small"
                  variant="text"
                  color="error"
                  :disabled="manualForm.items.length <= 2"
                  @click="removeManualRow(idx)"
                />
              </div>
            </div>
          </div>

          <!-- Total Balance Verification Box -->
          <div class="d-flex justify-space-between align-center pa-3 rounded-lg border" :class="manualIsBalanced ? 'bg-success-lighten-5' : 'bg-error-lighten-5'">
            <div>
              <span class="text-caption font-weight-bold">Total Debit: </span>
              <span class="font-mono font-weight-bold text-primary">{{ formatCurrency(manualTotalDebit) }}</span>
            </div>
            <div>
              <span class="text-caption font-weight-bold">Total Kredit: </span>
              <span class="font-mono font-weight-bold text-warning">{{ formatCurrency(manualTotalCredit) }}</span>
            </div>
            <div>
              <span class="text-caption font-weight-bold">Selisih: </span>
              <span class="font-mono font-weight-bold" :class="manualIsBalanced ? 'text-success' : 'text-error'">
                {{ formatCurrency(Math.abs(manualTotalDebit - manualTotalCredit)) }}
              </span>
            </div>
          </div>
        </VCardText>

        <VCardActions class="justify-end gap-2 px-0 pt-4">
          <VBtn variant="text" @click="manualDialog = false">Batal</VBtn>
          <VBtn color="primary" :disabled="!manualIsBalanced" :loading="isSaving" @click="saveManualJournal">
            Posting Jurnal Penyesuaian
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style lang="scss">
.per-page-select {
  inline-size: 5.5rem;
}
</style>
