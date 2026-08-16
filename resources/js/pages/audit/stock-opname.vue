<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'

const { show: showSnackbar } = useSnackbarStore()

const branches = ref([])
const selectedBranch = ref(null)

const opnames = ref([])
const isLoading = ref(false)
const totalOpnames = ref(0)
const opnameOptions = ref({ page: 1, itemsPerPage: 10 })

const showCreateDialog = ref(false)

const createData = ref({
  audit_date: new Date().toISOString().substr(0, 10),
  notes: '',
})

const activeOpname = ref(null)
const searchQuery = ref('')

const opnameItems = ref([])
const itemsPage = ref(1)
const itemsTotalPages = ref(1)
const totalItems = ref(0)
const itemsPerPage = ref(20)
const isLoadingItems = ref(false)
const currentFetchUrl = ref('')

const fetchOpnameItems = async (page = 1) => {
  if (!activeOpname.value) return
  
  const url = `/apps/stock-opnames/${activeOpname.value.id}/items?page=${page}&search=${searchQuery.value}`
  if (currentFetchUrl.value === url) return // Mencegah infinite loop dari VPagination
  currentFetchUrl.value = url

  isLoadingItems.value = true
  try {
    const res = await $api(`/apps/stock-opnames/${activeOpname.value.id}/items?page=${page}&per_page=${itemsPerPage.value}&search=${searchQuery.value}`)

    opnameItems.value = res.data
    itemsPage.value = res.current_page
    itemsTotalPages.value = res.last_page
    totalItems.value = res.total
  } catch (error) {
    console.error('Error fetching items:', error)
  } finally {
    isLoadingItems.value = false
  }
}

let searchTimeout = null
watch(searchQuery, (newVal, oldVal) => {
  if (newVal === oldVal) return
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    itemsPage.value = 1
    fetchOpnameItems(1)
  }, 500)
})

const viewMode = ref('batchList') // batchList, branchList, detail
const activeBatch = ref(null)

const confirmDialog = ref(false)

const confirmData = ref({
  title: '',
  message: '',
  action: null,
  color: 'primary',
})

const openConfirm = (title, message, action, color = 'primary') => {
  confirmData.value = { title, message, action, color }
  confirmDialog.value = true
}

const executeConfirm = async () => {
  confirmDialog.value = false
  if (confirmData.value.action) {
    await confirmData.value.action()
  }
}

const formatDate = dateStr => {
  if (!dateStr) return '-'

  // Handle ISO strings by just taking the date part or formatting it
  try {
    const d = new Date(dateStr)
    
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
  } catch (e) {
    return dateStr.substring(0, 10)
  }
}

const detailHeaders = computed(() => {
  if (activeOpname.value && ['draft', 'in_progress'].includes(activeOpname.value.status)) {
    return [
      { title: 'SKU / Produk', key: 'product' },
      { title: 'Stok Sistem', key: 'system_qty' },
      { title: 'Terjual', key: 'sold_qty' },
      { title: 'Stok Fisik (Bagus)', key: 'physical_qty', width: '150px' },
      { title: 'Stok Rusak', key: 'damaged_qty', width: '150px' },
      { title: 'Selisih (Variance)', key: 'variance' },
      { title: 'Keterangan / Alasan', key: 'reason' },
      { title: 'Aksi', key: 'actions', sortable: false },
    ]
  }
  
  return [
    { title: 'SKU / Produk', key: 'product' },
    { title: 'Stok Sistem', key: 'system_qty' },
    { title: 'Terjual', key: 'sold_qty' },
    { title: 'Stok Fisik (Bagus)', key: 'physical_qty' },
    { title: 'Stok Rusak', key: 'damaged_qty' },
    { title: 'Selisih (Variance)', key: 'variance' },
    { title: 'Keterangan / Alasan', key: 'reason' },
  ]
})

const opnamesHeaders = computed(() => {
  if (viewMode.value === 'batchList') {
    return [
      { title: 'Tanggal Audit', key: 'audit_date' },
      { title: 'Pembuat', key: 'creator.name' },
      { title: 'Catatan', key: 'notes' },
      { title: 'Jml Cabang', key: 'total_branches' },
      { title: 'Aksi (Batch)', key: 'actions', sortable: false, align: 'center' },
    ]
  } else {
    const headers = []
    if (branches.value.length > 1) {
      headers.push({ title: 'Cabang', key: 'branch.name' })
    }
    headers.push(
      { title: 'ID Dokumen', key: 'id' },
      { title: 'Tanggal Audit', key: 'audit_date' },
      { title: 'Catatan', key: 'notes' },
      { title: 'Status', key: 'status' },
      { title: 'Aksi', key: 'actions', sortable: false, align: 'center' },
    )
    
    return headers
  }
})

const fetchBranches = async () => {
  try {
    const res = await $api('/apps/branches?simple=true')
    if (res && res.length > 0) {
      branches.value = res.map(b => ({ title: b.name, value: b.id }))

      // If user is branch admin (only 1 branch), skip batch list
      if (branches.value.length === 1) {
        viewMode.value = 'branchList'
        selectedBranch.value = branches.value[0].value
      } else {
        viewMode.value = 'batchList'
      }
    }
  } catch (error) {
    console.error('Error fetching branches:', error)
  }
}

const fetchOpnames = async () => {
  isLoading.value = true
  try {
    let url = '/apps/stock-opnames'
    let queryParams = new URLSearchParams()
    
    if (viewMode.value === 'batchList') {
      queryParams.append('group_by_batch', 'true')
    } else if (viewMode.value === 'branchList' && activeBatch.value) {
      queryParams.append('batch_id', activeBatch.value.batch_id)
    } else if (branches.value.length === 1) {
      // Branch admin, fetch their opnames
      queryParams.append('branch_id', branches.value[0].value)
    }
    
    queryParams.append('page', opnameOptions.value.page)
    queryParams.append('itemsPerPage', opnameOptions.value.itemsPerPage)
    
    const res = await $api(`${url}?${queryParams.toString()}`)

    opnames.value = res.data
    totalOpnames.value = res.total || (res.data ? res.data.length : 0)
  } catch (error) {
    console.error('Error fetching opnames:', error)
  } finally {
    isLoading.value = false
  }
}

const openBatchDetails = batch => {
  activeBatch.value = batch
  viewMode.value = 'branchList'
  fetchOpnames()
}

const backToBatchList = () => {
  activeBatch.value = null
  viewMode.value = 'batchList'
  fetchOpnames()
}

const createOpname = async () => {
  isLoading.value = true
  try {
    await $api('/apps/stock-opnames', {
      method: 'POST',
      body: {
        audit_date: createData.value.audit_date,
        notes: createData.value.notes,
      },
    })
    showCreateDialog.value = false
    createData.value.notes = ''
    showSnackbar('Jadwal Stock Opname Massal Berhasil Dibuat', 'success')
    fetchOpnames()
  } catch (error) {
    console.error('Error creating opname:', error)
    showSnackbar(error.data?.message || 'Terjadi kesalahan', 'error')
  } finally {
    isLoading.value = false
  }
}

const showEditBatchDialog = ref(false)
const editBatchData = ref({ batch_id: '', audit_date: '', notes: '' })

const openEditBatch = item => {
  editBatchData.value = {
    batch_id: item.batch_id,
    audit_date: item.audit_date,
    notes: item.notes,
  }
  showEditBatchDialog.value = true
}

const updateBatch = async () => {
  isLoading.value = true
  try {
    await $api(`/apps/stock-opnames/batch/${editBatchData.value.batch_id}`, {
      method: 'PUT',
      body: {
        audit_date: editBatchData.value.audit_date,
        notes: editBatchData.value.notes,
      },
    })
    showEditBatchDialog.value = false
    showSnackbar('Jadwal berhasil diupdate', 'success')
    fetchOpnames()
  } catch (error) {
    showSnackbar(error.data?.message || 'Gagal update batch', 'error')
  } finally {
    isLoading.value = false
  }
}

const deleteBatch = batchId => {
  openConfirm(
    'Hapus Jadwal',
    'Anda yakin ingin menghapus jadwal ini untuk SEMUA cabang?',
    async () => {
      isLoading.value = true
      try {
        await $api(`/apps/stock-opnames/batch/${batchId}`, {
          method: 'DELETE',
        })
        showSnackbar('Jadwal berhasil dihapus', 'success')
        fetchOpnames()
      } catch (error) {
        showSnackbar(error.data?.message || 'Gagal hapus batch', 'error')
      } finally {
        isLoading.value = false
      }
    },
    'error',
  )
}

const openOpname = async id => {
  isLoading.value = true
  try {
    activeOpname.value = await $api(`/apps/stock-opnames/${id}`)
    viewMode.value = 'detail'
    searchQuery.value = ''
    itemsPage.value = 1
    currentFetchUrl.value = '' // Reset URL failsafe
    fetchOpnameItems(1)
  } catch (error) {
    console.error('Error opening opname:', error)
  } finally {
    isLoading.value = false
  }
}

const backToList = () => {
  activeOpname.value = null
  viewMode.value = activeBatch.value ? 'branchList' : (branches.value.length === 1 ? 'branchList' : 'batchList')
  fetchOpnames()
}

const updateItemQty = async (item, qty, damagedQty) => {
  if (qty === null || qty === '') return
  try {
    await $api(`/apps/stock-opnames/${activeOpname.value.id}/items/${item.id}`, {
      method: 'PUT',
      body: {
        physical_qty: qty,
        damaged_qty: damagedQty || 0,
        reason: item.reason,
      },
    })

    // Reload only current page
    currentFetchUrl.value = '' // Force refresh
    fetchOpnameItems(itemsPage.value)
  } catch (error) {
    console.error('Error updating item:', error)
    showSnackbar(error.data?.message || 'Gagal update qty', 'error')
  }
}

const approveOpname = () => {
  openConfirm(
    'Setujui Audit',
    'Anda yakin ingin menyetujui Audit ini? Inventori sistem akan diupdate secara permanen!',
    async () => {
      isLoading.value = true
      try {
        await $api(`/apps/stock-opnames/${activeOpname.value.id}/approve`, {
          method: 'POST',
        })
        showSnackbar('Stock Opname berhasil di-approve.', 'success')
        backToList()
      } catch (error) {
        console.error('Error approving opname:', error)
        showSnackbar(error.data?.message || 'Gagal approve Stock Opname', 'error')
      } finally {
        isLoading.value = false
      }
    },
    'success',
  )
}

const getLiveVariance = item => {
  if (item.temp_physical_qty === null || item.temp_physical_qty === '' || item.temp_physical_qty === undefined) return null
  const physical = parseFloat(item.temp_physical_qty)
  const damaged = parseFloat(item.temp_damaged_qty || item.damaged_qty || 0)
  const system = parseFloat(item.system_qty)
  if (isNaN(physical) || isNaN(system) || isNaN(damaged)) return null
  
  return (physical + damaged) - system
}

const submitPin = ref('')
const showSubmitPinDialog = ref(false)

const openSubmitDialog = () => {
  submitPin.value = ''
  showSubmitPinDialog.value = true
}

const submitReview = async () => {
  if (!submitPin.value) {
    showSnackbar('Masukkan PIN Kepala Cabang', 'warning')
    
    return
  }
  
  isLoading.value = true
  try {
    await $api(`/apps/stock-opnames/${activeOpname.value.id}/submit`, {
      method: 'POST',
      body: { pin: submitPin.value },
    })
    showSnackbar('Berhasil dikirim untuk review', 'success')
    showSubmitPinDialog.value = false
    openOpname(activeOpname.value.id) // reload
  } catch (error) {
    console.error('Error submitting review:', error)
    showSnackbar(error.data?.message || 'Gagal mengirim untuk review', 'error')
  } finally {
    isLoading.value = false
  }
}

const showRevisionDialog = ref(false)
const revisionNote = ref('')

const sendRevision = async () => {
  if (!revisionNote.value) {
    showSnackbar('Catatan revisi harus diisi', 'warning')
    
    return
  }
  isLoading.value = true
  try {
    await $api(`/apps/stock-opnames/${activeOpname.value.id}/revision`, {
      method: 'POST',
      body: { notes: revisionNote.value },
    })
    showSnackbar('Dokumen dikembalikan ke cabang', 'success')
    showRevisionDialog.value = false
    revisionNote.value = ''
    openOpname(activeOpname.value.id) // reload
  } catch (error) {
    console.error('Error sending revision:', error)
    showSnackbar(error.data?.message || 'Gagal mengirim revisi', 'error')
  } finally {
    isLoading.value = false
  }
}

watch(branches, () => {
  fetchOpnames()
})

onMounted(async () => {
  await fetchBranches()
  fetchOpnames()
})
</script>

<template>
  <div class="pa-4">
    <div class="d-flex align-center justify-space-between mb-4">
      <h2 class="text-h5 font-weight-bold">
        <template v-if="viewMode === 'branchList' && activeBatch">
          <VBtn
            icon="ri-arrow-left-line"
            variant="text"
            size="small"
            class="me-2"
            @click="backToBatchList"
          />
          Progress Cabang (Audit {{ formatDate(activeBatch?.audit_date) }})
        </template>
        <template v-else>
          Audit Bulanan (Stock Opname)
        </template>
      </h2>
      <div class="d-flex gap-4">
        <!-- Hide dropdown completely, system handles it automatically -->
        <VBtn
          v-if="viewMode === 'batchList'"
          color="primary"
          prepend-icon="ri-add-line"
          @click="showCreateDialog = true"
        >
          Mulai Stock Opname
        </VBtn>
      </div>
    </div>

    <!-- Active Opname Detail -->
    <div v-if="viewMode === 'detail' && activeOpname">
      <div class="d-flex align-center mb-4">
        <VBtn
          icon="ri-arrow-left-line"
          variant="text"
          class="me-2"
          @click="backToList"
        />
        <h4 class="text-h4 mb-0">
          Detail Stock Opname #{{ activeOpname.id }}
        </h4>
        <VSpacer />
        <VChip
          :color="activeOpname.status === 'approved' ? 'success' : (activeOpname.status === 'in_progress' ? 'info' : (activeOpname.status === 'completed' ? 'primary' : 'warning'))"
          class="me-4"
        >
          {{ activeOpname.status === 'completed' ? 'MENUNGGU REVIEW' : activeOpname.status.toUpperCase() }}
        </VChip>
        <VBtn 
          v-if="['draft', 'in_progress'].includes(activeOpname.status)"
          color="primary" 
          :loading="isLoading"
          @click="openSubmitDialog"
        >
          Selesai & Kirim Laporan
        </VBtn>
        <VBtn 
          v-if="activeOpname.status === 'completed'"
          color="error" 
          variant="outlined"
          class="me-2"
          @click="showRevisionDialog = true"
        >
          <VIcon
            start
            icon="ri-close-line"
          /> Perbaiki (Revisi)
        </VBtn>
        <VBtn 
          v-if="activeOpname.status === 'completed'"
          color="success" 
          :loading="isLoading"
          @click="approveOpname"
        >
          <VIcon
            start
            icon="ri-check-line"
          /> Sesuai (Approve)
        </VBtn>
      </div>

      <VCard class="mb-4">
        <VCardText>
          <VRow>
            <VCol
              cols="12"
              md="4"
            >
              <strong>Cabang:</strong> {{ activeOpname.branch?.name }}
            </VCol>
            <VCol
              cols="12"
              md="4"
            >
              <strong>Tanggal Audit:</strong> {{ formatDate(activeOpname.audit_date) }}
            </VCol>
            <VCol
              cols="12"
              md="4"
            >
              <strong>Catatan:</strong> {{ activeOpname.notes || '-' }}
            </VCol>
          </VRow>
        </VCardText>
      </VCard>

      <VCard>
        <VCardItem class="pa-4 pb-0">
          <div class="d-flex align-center justify-space-between w-100">
            <VCardTitle class="px-0">
              Daftar Barang (Input Hitung Fisik)
            </VCardTitle>
            <div style="width: 250px;">
              <VTextField
                v-model="searchQuery"
                prepend-inner-icon="ri-search-line"
                placeholder="Cari SKU / Produk..."
                density="compact"
                hide-details
                variant="outlined"
              />
            </div>
          </div>
        </VCardItem>
        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          v-model:page="itemsPage"
          :headers="detailHeaders"
          :items="opnameItems"
          :items-length="totalItems"
          :loading="isLoadingItems"
          @update:options="fetchOpnameItems($event.page)"
        >
          <template #item.product="{ item }">
            <div class="font-weight-medium">
              {{ item.product?.name }}
            </div>
            <div class="text-xs text-medium-emphasis">
              {{ item.product?.sku }}
            </div>
          </template>

          <template #item.system_qty="{ item }">
            {{ item.system_qty }}
          </template>

          <template #item.sold_qty="{ item }">
            {{ item.sold_qty ?? 0 }}
          </template>

          <template #item.physical_qty="{ item }">
            <VTextField
              v-if="['draft', 'in_progress'].includes(activeOpname.status)"
              v-model="item.temp_physical_qty"
              :placeholder="item.physical_qty !== null ? item.physical_qty.toString() : 'Ketik angka'"
              type="number"
              density="compact"
              hide-details
              variant="outlined"
            />
            <span v-else>{{ item.physical_qty ?? 'Belum dihitung' }}</span>
          </template>

          <template #item.damaged_qty="{ item }">
            <VTextField
              v-if="['draft', 'in_progress'].includes(activeOpname.status)"
              v-model="item.temp_damaged_qty"
              :placeholder="item.damaged_qty !== null ? item.damaged_qty.toString() : '0'"
              type="number"
              density="compact"
              hide-details
              variant="outlined"
            />
            <span v-else>{{ item.damaged_qty ?? 0 }}</span>
          </template>

          <template #item.variance="{ item }">
            <VChip 
              v-if="getLiveVariance(item) !== null" 
              :color="getLiveVariance(item) === 0 ? 'success' : 'error'" 
              size="small"
            >
              {{ getLiveVariance(item) > 0 ? '+' : '' }}{{ getLiveVariance(item) }}
            </VChip>
            <VChip 
              v-else-if="item.variance !== null" 
              :color="item.variance === 0 ? 'success' : 'error'" 
              size="small"
            >
              {{ item.variance > 0 ? '+' : '' }}{{ item.variance }}
            </VChip>
            <span
              v-else
              class="text-medium-emphasis"
            >-</span>
          </template>

          <template #item.reason="{ item }">
            <VTextField
              v-if="['draft', 'in_progress'].includes(activeOpname.status)"
              v-model="item.reason"
              placeholder="Opsional"
              density="compact"
              hide-details
              variant="underlined"
            />
            <span v-else>{{ item.reason || '-' }}</span>
          </template>

          <template #item.actions="{ item }">
            <VBtn 
              size="small" 
              color="primary" 
              variant="tonal"
              :disabled="!item.temp_physical_qty && item.temp_physical_qty !== 0"
              @click="updateItemQty(item, item.temp_physical_qty, item.temp_damaged_qty)"
            >
              Simpan
            </VBtn>
          </template>
        </VDataTableServer>
      </VCard>
    </div>

    <!-- Opnames / Batches List -->
    <VCard v-if="viewMode !== 'detail'">
      <VDataTableServer
        v-model:options="opnameOptions"
        :headers="opnamesHeaders"
        :items="opnames"
        :items-length="totalOpnames"
        :loading="isLoading"
        class="text-no-wrap"
        @update:options="fetchOpnames"
      >
        <template #item.audit_date="{ item }">
          {{ formatDate(item.audit_date) }}
        </template>
        
        <template #item.creator.name="{ item }">
          <template v-if="viewMode === 'batchList'">
            {{ item.creator?.name }}
          </template>
        </template>
        
        <template #item.notes="{ item }">
          {{ item.notes || '-' }}
        </template>
        
        <template #item.total_branches="{ item }">
          {{ item.total_branches }}
        </template>
        
        <template #item.id="{ item }">
          <template v-if="viewMode !== 'batchList'">
            #{{ item.id }}
          </template>
        </template>
        
        <template #item.branch.name="{ item }">
          {{ item.branch?.name }}
        </template>
        
        <template #item.status="{ item }">
          <VChip
            :color="item.status === 'approved' ? 'success' : (item.status === 'in_progress' ? 'info' : (item.status === 'completed' ? 'primary' : 'warning'))"
            size="small"
          >
            {{ item.status === 'completed' ? 'menunggu review' : item.status }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <template v-if="viewMode === 'batchList'">
            <VBtn
              size="small"
              variant="text"
              color="primary"
              class="me-2"
              @click="openBatchDetails(item)"
            >
              Progress Cabang
            </VBtn>
            <VBtn
              size="small"
              variant="text"
              color="warning"
              class="me-2"
              @click="openEditBatch(item)"
            >
              Edit
            </VBtn>
            <VBtn
              size="small"
              variant="text"
              color="error"
              @click="deleteBatch(item.batch_id)"
            >
              Hapus
            </VBtn>
          </template>
          <template v-else>
            <VBtn
              size="small"
              variant="text"
              color="primary"
              @click="openOpname(item.id)"
            >
              Buka Dokumen
            </VBtn>
          </template>
        </template>

        <template #no-data>
          Belum ada sesi Stock Opname.
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Create Dialog -->
    <VDialog
      v-model="showCreateDialog"
      max-width="500"
    >
      <VCard title="Mulai Stock Opname Baru">
        <VCardText>
          <VTextField
            v-model="createData.audit_date"
            label="Tanggal Audit"
            type="date"
            class="mb-4"
          />
          <VTextarea
            v-model="createData.notes"
            label="Catatan (Tujuan audit, dll)"
            rows="3"
          />
        </VCardText>
        <VCardActions class="px-4 pb-4">
          <VSpacer />
          <VBtn
            color="secondary"
            variant="text"
            @click="showCreateDialog = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="primary"
            :loading="isLoading"
            @click="createOpname"
          >
            Buat Sesi
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Revision Dialog -->
    <VDialog
      v-model="showRevisionDialog"
      max-width="500"
    >
      <VCard title="Catatan Perbaikan / Revisi">
        <VCardText>
          <p class="text-body-2 mb-4">
            Berikan catatan bagian mana yang perlu dihitung ulang atau diperbaiki oleh cabang.
          </p>
          <VTextarea
            v-model="revisionNote"
            label="Catatan Revisi"
            rows="3"
            variant="outlined"
          />
        </VCardText>
        <VCardActions class="pa-4 pt-0">
          <VSpacer />
          <VBtn
            color="error"
            variant="text"
            @click="showRevisionDialog = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="primary"
            :loading="isLoading"
            :disabled="!revisionNote"
            @click="sendRevision"
          >
            Kirim Revisi
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Submit PIN Dialog -->
    <VDialog
      v-model="showSubmitPinDialog"
      max-width="400"
    >
      <VCard title="Otorisasi Kepala Cabang">
        <VCardText>
          <p class="text-body-2 mb-4">
            Kirim hasil hitung fisik ini ke Owner untuk di-review? Anda tidak dapat mengubah data lagi setelah dikirim.
            Silakan masukkan PIN Kepala Cabang / Admin Cabang.
          </p>
          <VTextField
            v-model="submitPin"
            label="PIN Otorisasi"
            type="password"
            variant="outlined"
            @keyup.enter="submitReview"
          />
        </VCardText>
        <VCardActions class="px-4 pb-4">
          <VSpacer />
          <VBtn
            variant="text"
            color="secondary"
            @click="showSubmitPinDialog = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="primary"
            :loading="isLoading"
            @click="submitReview"
          >
            Verifikasi & Kirim
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Edit Batch Dialog -->
    <VDialog
      v-model="showEditBatchDialog"
      max-width="500"
    >
      <VCard title="Edit Stock Opname Massal">
        <VCardText>
          <VTextField
            v-model="editBatchData.audit_date"
            label="Tanggal Audit"
            type="date"
            class="mb-4"
          />
          <VTextarea
            v-model="editBatchData.notes"
            label="Catatan"
            rows="3"
            variant="outlined"
          />
        </VCardText>
        <VCardActions class="px-4 pb-4">
          <VSpacer />
          <VBtn
            color="secondary"
            variant="text"
            @click="showEditBatchDialog = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="primary"
            :loading="isLoading"
            @click="updateBatch"
          >
            Simpan Perubahan
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Global Confirm Dialog -->
    <VDialog
      v-model="confirmDialog"
      max-width="400"
    >
      <VCard>
        <VCardTitle class="text-h5 font-weight-bold pa-4 pb-2">
          {{ confirmData.title }}
        </VCardTitle>
        <VCardText class="pa-4 pt-2">
          {{ confirmData.message }}
        </VCardText>
        <VCardActions class="pa-4 pt-0">
          <VSpacer />
          <VBtn
            variant="text"
            @click="confirmDialog = false"
          >
            Batal
          </VBtn>
          <VBtn
            :color="confirmData.color"
            :loading="isLoading"
            @click="executeConfirm"
          >
            Ya, Lanjutkan
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Stock Opname
</route>
