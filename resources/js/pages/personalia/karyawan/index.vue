<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { paginationMeta } from '@/utils/paginationMeta'
import { useSnackbarStore } from '@/stores/snackbar'
import AddNewKaryawanDrawer from './AddNewKaryawanDrawer.vue'

definePage({
  meta: {
    action: 'read',
    subject: 'Master Karyawan',
  },
})

const isLoading = ref(true)
const searchQuery = ref('')
const selectedBranch = ref('all')
const employees = ref([])
const branches = ref([])

const page = ref(1)
const itemsPerPage = ref(15)
const totalItems = ref(0)
const isAddNewDrawerVisible = ref(false)
const selectedEmployee = ref(null)
const isDownloadingTemplate = ref(false)
const isImporting = ref(false)
const fileInput = ref(null)

const tableHeaders = [
  { title: 'NAMA', key: 'name' },
  { title: 'NIK / NO. KTP', key: 'nik' },
  { title: 'CABANG', key: 'branch_name' },
  { title: 'POTONGAN/TUNJANGAN', key: 'deductionTypes' },
  { title: 'STATUS', key: 'status', align: 'center' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const snackbar = useSnackbarStore()

const extractArray = val => {
  if (Array.isArray(val)) return val
  if (val && Array.isArray(val.data)) return val.data
  return []
}

const fetchInitialOptions = async () => {
  try {
    const res = await $api('/apps/branches')
    branches.value = extractArray(res)
  } catch (e) {
    console.error('Failed to load branches:', e)
  }
}

const fetchEmployees = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
    }
    
    if (searchQuery.value) params.search = searchQuery.value
    if (selectedBranch.value && selectedBranch.value !== 'all') params.branch_id = selectedBranch.value

    const res = await $api('/apps/employees', { query: params })
    if (res && res.data) {
      employees.value = res.data
      totalItems.value = res.total || res.meta?.total || employees.value.length
    } else {
      employees.value = extractArray(res)
      totalItems.value = employees.value.length
    }
  } catch (e) {
    console.error('Failed to load employees:', e)
    snackbar.show('Gagal mengambil data karyawan', 'error')
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchInitialOptions()
  fetchEmployees()
})

const saveEmployee = async data => {
  try {
    const method = data.id ? 'PUT' : 'POST'
    const endpoint = data.id ? `/apps/employees/${data.id}` : '/apps/employees'
    
    await $api(endpoint, {
      method,
      body: data,
    })

    snackbar.show(data.id ? 'Data karyawan berhasil diperbarui' : 'Karyawan baru berhasil ditambahkan', 'success')
    isAddNewDrawerVisible.value = false
    fetchEmployees()
  } catch (error) {
    console.error(error)
    snackbar.show(error.data?.message || 'Gagal menyimpan data karyawan', 'error')
  }
}

const confirmDelete = async id => {
  if (confirm('Apakah Anda yakin ingin menghapus data karyawan ini?')) {
    try {
      await $api(`/apps/employees/${id}`, { method: 'DELETE' })
      snackbar.show('Data berhasil dihapus', 'success')
      fetchEmployees()
    } catch (error) {
      console.error(error)
      snackbar.show('Gagal menghapus data', 'error')
    }
  }
}

let searchTimeout = null
const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchEmployees()
  }, 500)
}

watch(selectedBranch, () => {
  page.value = 1
  fetchEmployees()
})

const downloadTemplate = async () => {
  isDownloadingTemplate.value = true
  try {
    const response = await $api('/apps/employees/import-template')
    const blob = new Blob([response.csv], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'Template_Karyawan.csv')
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mengunduh template', 'error')
  } finally {
    isDownloadingTemplate.value = false
  }
}

const triggerFileInput = () => {
  fileInput.value.click()
}

const handleFileUpload = async event => {
  const file = event.target.files[0]
  if (!file) return
  
  const formData = new FormData()
  formData.append('file', file)
  
  isImporting.value = true
  try {
    const res = await $api('/apps/employees/import', {
      method: 'POST',
      body: formData,
    })
    snackbar.show(res.message || 'Import karyawan berhasil', 'success')
    await fetchEmployees()
  } catch (error) {
    console.error(error)
    snackbar.show(error.data?.message || 'Gagal melakukan import data karyawan', 'error')
  } finally {
    isImporting.value = false
    event.target.value = ''
  }
}

</script>

<template>
  <section>
    <!-- Header -->
    <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold mb-1 text-high-emphasis">
          Master Data Karyawan
        </h4>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Kelola profil karyawan, penempatan cabang, dan akses sistem (HRIS).
        </p>
      </div>

      <div class="d-flex align-center gap-3">
        <input 
          ref="fileInput" 
          type="file" 
          accept=".csv" 
          style="display: none" 
          @change="handleFileUpload"
        >
        <VBtn
          color="info"
          variant="tonal"
          prepend-icon="ri-download-cloud-line"
          :loading="isDownloadingTemplate"
          @click="downloadTemplate"
        >
          Template
        </VBtn>
        <VBtn
          color="warning"
          variant="tonal"
          prepend-icon="ri-upload-cloud-line"
          :loading="isImporting"
          @click="triggerFileInput"
        >
          Import
        </VBtn>
        <VBtn
          color="primary"
          prepend-icon="ri-user-add-line"
          @click="() => { selectedEmployee = null; isAddNewDrawerVisible = true }"
        >
          Tambah Karyawan
        </VBtn>
      </div>
    </div>

    <!-- Filter Card -->
    <VCard elevation="1" class="border rounded-lg mb-6">
      <VCardText class="pa-4">
        <VRow>
          <VCol cols="12" md="4">
            <VTextField
              v-model="searchQuery"
              placeholder="Cari Nama, NIK, No HP..."
              prepend-inner-icon="ri-search-line"
              density="compact"
              variant="outlined"
              clearable
              hide-details
              @update:model-value="handleSearch"
            />
          </VCol>
          <VCol cols="12" md="4">
            <VSelect
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
        </VRow>
      </VCardText>
    </VCard>

    <!-- Table Card -->
    <VCard elevation="1" class="border rounded-lg">
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="tableHeaders"
        :items="employees"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        @update:options="fetchEmployees"
      >
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-3">
            <VAvatar size="34" color="primary" variant="tonal">
              {{ item.name ? item.name.charAt(0).toUpperCase() : 'U' }}
            </VAvatar>
            <div class="d-flex flex-column">
              <span class="font-weight-medium text-high-emphasis">{{ item.name }}</span>
              <span class="text-caption text-medium-emphasis">{{ item.email || 'Tidak ada email' }}</span>
            </div>
          </div>
        </template>
        
        <template #item.nik="{ item }">
          <span class="font-mono text-body-2">{{ item.nik || '-' }}</span>
        </template>
        
        <template #item.branch_name="{ item }">
          <span class="text-body-2">{{ item.branch_name || 'Global / Pusat' }}</span>
        </template>
        
        <template #item.deductionTypes="{ item }">
          <div v-if="item.deduction_types && item.deduction_types.length" class="d-flex flex-wrap gap-1">
            <VChip 
              v-for="dType in item.deduction_types" 
              :key="dType.id" 
              size="x-small" 
              :color="dType.type === 'tunjangan' ? 'success' : 'warning'"
              variant="tonal"
            >
              {{ dType.name }}
            </VChip>
          </div>
          <span v-else class="text-caption text-medium-emphasis">Tidak ada</span>
        </template>

        <template #item.status="{ item }">
          <VChip :color="item.status === 'Aktif' ? 'success' : 'error'" size="small" variant="tonal">
            {{ item.status }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex align-center justify-center gap-2">
            <VBtn icon size="small" color="info" variant="text" @click="() => { selectedEmployee = item; isAddNewDrawerVisible = true }">
              <VIcon icon="ri-edit-line" />
              <VTooltip activator="parent" location="top">Edit Karyawan</VTooltip>
            </VBtn>
            <VBtn icon size="small" color="error" variant="text" @click="confirmDelete(item.id)">
              <VIcon icon="ri-delete-bin-line" />
              <VTooltip activator="parent" location="top">Hapus Karyawan</VTooltip>
            </VBtn>
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
    
    <AddNewKaryawanDrawer
      v-if="isAddNewDrawerVisible"
      v-model:is-drawer-open="isAddNewDrawerVisible"
      :selected-data="selectedEmployee"
      :branches-list="branches"
      @save-data="saveEmployee"
    />
  </section>
</template>

<style lang="scss">
.per-page-select {
  .v-field__input {
    padding-inline-start: 0;
  }
}
</style>
