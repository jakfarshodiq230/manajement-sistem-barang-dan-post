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
const selectedType = ref('all')
const accounts = ref([])
const page = ref(1)
const itemsPerPage = ref(15)
const accountDialog = ref(false)
const isEditing = ref(false)
const deleteDialog = ref(false)
const accountToDelete = ref(null)
const isDownloadingPdf = ref(false)

const tableHeaders = [
  { title: 'KODE AKUN', key: 'code' },
  { title: 'NAMA AKUN', key: 'name' },
  { title: 'KLASIFIKASI / TIPE', key: 'type' },
  { title: 'SALDO NORMAL', key: 'normal_balance', align: 'center' },
  { title: 'SALDO BERJALAN', key: 'current_balance', align: 'end' },
  { title: 'STATUS', key: 'is_active', align: 'center' },
  { title: 'AKSI', key: 'actions', align: 'center', sortable: false },
]

const paginatedAccounts = computed(() => {
  const start = (page.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return accounts.value.slice(start, end)
})

const accountForm = ref({
  id: null,
  code: '',
  name: '',
  type: 'asset',
  category: 'current_asset',
  normal_balance: 'debit',
  parent_id: null,
  opening_balance: 0,
  description: '',
  is_active: true,
})

const typeOptions = [
  { title: 'Semua Tipe Akun', value: 'all' },
  { title: '1 - Aset / Aktiva (Asset)', value: 'asset' },
  { title: '2 - Kewajiban / Hutang (Liability)', value: 'liability' },
  { title: '3 - Ekuitas / Modal (Equity)', value: 'equity' },
  { title: '4 - Pendapatan (Revenue)', value: 'revenue' },
  { title: '5 - HPP (Cost of Goods Sold)', value: 'cogs' },
  { title: '6 - Beban Operasional (Expense)', value: 'expense' },
]

const formatCurrency = val => {
  if (val === null || val === undefined || isNaN(val)) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val || 0)
}

const getTypeColor = type => {
  switch (type) {
    case 'asset': return 'primary'
    case 'liability': return 'error'
    case 'equity': return 'warning'
    case 'revenue': return 'success'
    case 'cogs': return 'secondary'
    case 'expense': return 'info'
    default: return 'default'
  }
}

const getTypeLabel = type => {
  switch (type) {
    case 'asset': return 'Aset (Aktiva)'
    case 'liability': return 'Kewajiban (Hutang)'
    case 'equity': return 'Ekuitas (Modal)'
    case 'revenue': return 'Pendapatan'
    case 'cogs': return 'HPP'
    case 'expense': return 'Beban Operasional'
    default: return type
  }
}

const fetchAccounts = async () => {
  isLoading.value = true
  try {
    const params = {}
    if (searchQuery.value) params.q = searchQuery.value
    if (selectedType.value && selectedType.value !== 'all') params.type = selectedType.value

    const res = await $api('/apps/accounting/accounts', { params })
    if (res.success && res.data) {
      accounts.value = res.data
    }
  } catch (e) {
    console.error('Failed to load accounts:', e)
  } finally {
    isLoading.value = false
  }
}

// Download PDF
const downloadCoaPdf = async () => {
  isDownloadingPdf.value = true
  try {
    const params = new URLSearchParams()
    if (selectedType.value && selectedType.value !== 'all') params.append('type', selectedType.value)
    if (searchQuery.value) params.append('search', searchQuery.value)

    const token = useCookie('accessToken').value
    const res = await fetch(`/api/apps/accounting/accounts/export-pdf?${params.toString()}`, {
      headers: {
        'Authorization': `Bearer ${token || ''}`,
        'Accept': 'application/pdf',
      },
    })

    if (!res.ok) {
      const errData = await res.json().catch(() => ({}))
      throw new Error(errData.message || 'Gagal mengunduh dokumen Bagan Akun (COA) PDF')
    }

    const blob = await res.blob()
    const url = window.URL.createObjectURL(blob)

    window.open(url, '_blank')

    const a = document.createElement('a')
    a.href = url
    a.download = `Bagan_Akun_COA.pdf`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)

    setTimeout(() => window.URL.revokeObjectURL(url), 10000)
  } catch (err) {
    console.error(err)
    alert(err.message || 'Gagal mencetak PDF Bagan Akun (COA)')
  } finally {
    isDownloadingPdf.value = false
  }
}

const parentAccountOptions = computed(() => {
  return accounts.value.filter(a => !a.parent_id).map(a => ({
    title: `${a.code} - ${a.name}`,
    value: a.id,
  }))
})

const openAddModal = () => {
  isEditing.value = false
  accountForm.value = {
    id: null,
    code: '',
    name: '',
    type: 'asset',
    category: 'current_asset',
    normal_balance: 'debit',
    parent_id: null,
    opening_balance: 0,
    description: '',
    is_active: true,
  }
  accountDialog.value = true
}

const openEditModal = acc => {
  isEditing.value = true
  accountForm.value = {
    id: acc.id,
    code: acc.code,
    name: acc.name,
    type: acc.type,
    category: acc.category,
    normal_balance: acc.normal_balance,
    parent_id: acc.parent_id,
    opening_balance: acc.opening_balance || 0,
    description: acc.description || '',
    is_active: !!acc.is_active,
  }
  accountDialog.value = true
}

const saveAccount = async () => {
  if (!accountForm.value.code || !accountForm.value.name) return

  isSaving.value = true
  try {
    if (isEditing.value) {
      await $api(`/apps/accounting/accounts/${accountForm.value.id}`, {
        method: 'PUT',
        body: accountForm.value,
      })
    } else {
      await $api('/apps/accounting/accounts', {
        method: 'POST',
        body: accountForm.value,
      })
    }
    accountDialog.value = false
    await fetchAccounts()
  } catch (e) {
    console.error('Error saving account:', e)
    alert(e.data?.message || 'Gagal menyimpan akun COA.')
  } finally {
    isSaving.value = false
  }
}

const confirmDelete = acc => {
  accountToDelete.value = acc
  deleteDialog.value = true
}

const deleteAccount = async () => {
  if (!accountToDelete.value) return
  try {
    const res = await $api(`/apps/accounting/accounts/${accountToDelete.value.id}`, {
      method: 'DELETE',
    })
    deleteDialog.value = false
    await fetchAccounts()
  } catch (e) {
    alert(e.data?.message || 'Gagal menghapus akun.')
  }
}

watch([searchQuery, selectedType], () => {
  fetchAccounts()
})

onMounted(() => {
  fetchAccounts()
})
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold mb-1 text-high-emphasis">
          Bagan Akun (Chart of Accounts / COA)
        </h4>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Master penomoran akun pembukuan dan posisi saldo normal untuk seluruh transaksi keuangan bisnis.
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
          @click="downloadCoaPdf"
        >
          Cetak PDF
        </VBtn>
        <VBtn
          color="primary"
          prepend-icon="ri-add-line"
          @click="openAddModal"
        >
          Tambah Akun COA
        </VBtn>
      </div>
    </div>

    <!-- Filter & Table Card -->
    <VCard elevation="1" class="border rounded-lg">
      <VCardText class="pa-4">
        <VRow class="mb-2">
          <VCol cols="12" md="4">
            <VTextField
              v-model="searchQuery"
              placeholder="Cari Kode atau Nama Akun..."
              prepend-inner-icon="ri-search-line"
              density="compact"
              variant="outlined"
              clearable
              hide-details
            />
          </VCol>
          <VCol cols="12" md="4">
            <VSelect
              v-model="selectedType"
              :items="typeOptions"
              item-title="title"
              item-value="value"
              density="compact"
              variant="outlined"
              prepend-inner-icon="ri-filter-3-line"
              hide-details
            />
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="tableHeaders"
        :items="paginatedAccounts"
        :items-length="accounts.length"
        :loading="isLoading"
        class="text-no-wrap"
        @update:options="fetchAccounts"
      >
        <template #item.code="{ item }">
          <span class="font-mono font-weight-bold text-primary">{{ item.code }}</span>
        </template>

        <template #item.name="{ item }">
          <div class="font-weight-medium">
            <span v-if="item.parent_id" class="text-medium-emphasis me-1">↳</span>
            {{ item.name }}
          </div>
          <div v-if="item.description" class="text-caption text-medium-emphasis">
            {{ item.description }}
          </div>
        </template>

        <template #item.type="{ item }">
          <VChip :color="getTypeColor(item.type)" size="small" variant="tonal" class="font-weight-medium">
            {{ getTypeLabel(item.type) }}
          </VChip>
        </template>

        <template #item.normal_balance="{ item }">
          <VChip
            size="x-small"
            :color="item.normal_balance === 'debit' ? 'primary' : 'warning'"
            variant="outlined"
            class="text-uppercase font-weight-bold"
          >
            {{ item.normal_balance }}
          </VChip>
        </template>

        <template #item.current_balance="{ item }">
          <span class="font-mono font-weight-bold">
            {{ formatCurrency(item.current_balance) }}
          </span>
        </template>

        <template #item.is_active="{ item }">
          <VChip size="x-small" :color="item.is_active ? 'success' : 'error'" variant="tonal">
            {{ item.is_active ? 'Aktif' : 'Non-Aktif' }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex align-center justify-center gap-1">
            <VBtn
              icon="ri-book-read-line"
              size="small"
              variant="text"
              color="primary"
              :to="`/akuntansi/buku-besar?account_id=${item.id}`"
              title="Buka Buku Besar Akun Ini"
            />
            <VBtn
              icon="ri-edit-line"
              size="small"
              variant="text"
              color="default"
              @click="openEditModal(item)"
              title="Edit Akun"
            />
            <VBtn
              v-if="!item.is_system"
              icon="ri-delete-bin-line"
              size="small"
              variant="text"
              color="error"
              @click="confirmDelete(item)"
              title="Hapus Akun"
            />
          </div>
        </template>

        <template #no-data>
          <div class="pa-6 text-center text-medium-emphasis">
            <VIcon icon="ri-inbox-line" size="36" class="mb-2 text-disabled" />
            <div>Tidak ada akun yang sesuai dengan filter pencarian.</div>
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
              {{ paginationMeta({ page, itemsPerPage }, accounts.length) }}
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
                :disabled="page >= Math.ceil(accounts.length / itemsPerPage)"
                @click="page >= Math.ceil(accounts.length / itemsPerPage) ? page = Math.ceil(accounts.length / itemsPerPage) : page++"
              />
            </div>
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Dialog Add / Edit Account -->
    <VDialog v-model="accountDialog" max-width="600">
      <VCard class="pa-4">
        <VCardTitle class="px-0 pt-0 pb-3 font-weight-bold">
          {{ isEditing ? 'Edit Akun COA' : 'Tambah Akun COA Baru' }}
        </VCardTitle>
        <VDivider class="mb-4" />
        <VCardText class="px-0 py-2">
          <VRow>
            <VCol cols="12" sm="5">
              <VTextField
                v-model="accountForm.code"
                label="Kode Akun *"
                placeholder="misal: 1107"
                density="compact"
                variant="outlined"
                required
              />
            </VCol>
            <VCol cols="12" sm="7">
              <VTextField
                v-model="accountForm.name"
                label="Nama Akun *"
                placeholder="misal: Kas Operasional 2"
                density="compact"
                variant="outlined"
                required
              />
            </VCol>
            <VCol cols="12" sm="6">
              <VSelect
                v-model="accountForm.type"
                :items="typeOptions.filter(t => t.value !== 'all')"
                label="Tipe Akun *"
                density="compact"
                variant="outlined"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <VSelect
                v-model="accountForm.normal_balance"
                :items="[{ title: 'DEBIT', value: 'debit' }, { title: 'KREDIT', value: 'credit' }]"
                label="Saldo Normal *"
                density="compact"
                variant="outlined"
              />
            </VCol>
            <VCol cols="12">
              <VSelect
                v-model="accountForm.parent_id"
                :items="parentAccountOptions"
                label="Akun Induk (Parent Account - Opsional)"
                density="compact"
                variant="outlined"
                clearable
              />
            </VCol>
            <VCol cols="12">
              <VTextField
                v-model="accountForm.opening_balance"
                label="Saldo Awal (Opening Balance)"
                type="number"
                density="compact"
                variant="outlined"
                prefix="Rp"
              />
            </VCol>
            <VCol cols="12">
              <VTextarea
                v-model="accountForm.description"
                label="Keterangan / Catatan"
                placeholder="Fungsi atau peruntukan akun ini..."
                rows="2"
                density="compact"
                variant="outlined"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions class="justify-end gap-2 px-0 pt-4">
          <VBtn variant="text" @click="accountDialog = false">Batal</VBtn>
          <VBtn color="primary" :loading="isSaving" @click="saveAccount">Simpan Akun</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Dialog Konfirmasi Hapus -->
    <VDialog v-model="deleteDialog" max-width="450">
      <VCard class="pa-4">
        <VCardTitle class="d-flex align-center gap-2 text-error">
          <VIcon icon="ri-error-warning-line" size="24" />
          <span>Hapus Akun COA</span>
        </VCardTitle>
        <VCardText v-if="accountToDelete" class="py-2">
          Apakah Anda yakin ingin menghapus akun <strong>{{ accountToDelete.code }} - {{ accountToDelete.name }}</strong>?
        </VCardText>
        <VCardActions class="justify-end gap-2 pt-4">
          <VBtn variant="text" @click="deleteDialog = false">Batal</VBtn>
          <VBtn color="error" @click="deleteAccount">Ya, Hapus</VBtn>
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
