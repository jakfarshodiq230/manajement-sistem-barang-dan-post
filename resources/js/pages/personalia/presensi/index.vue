<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { paginationMeta } from '@/utils/paginationMeta'
import { useSnackbarStore } from '@/stores/snackbar'

definePage({
  meta: {
    action: 'read',
    subject: 'Presensi',
  },
})

const isLoading = ref(true)
const isDownloadingTemplate = ref(false)
const isImporting = ref(false)
const snackbar = useSnackbarStore()
const fileInput = ref(null)

const currentMonth = ref(new Date().getMonth() + 1)
const currentYear = ref(new Date().getFullYear())
const branchId = ref('all')
const branches = ref([])

const activeTab = ref('summary') // summary, log
const summaryData = ref([])
const attendances = ref([])

const page = ref(1)
const itemsPerPage = ref(15)
const totalItems = ref(0)

const monthOptions = [
  { title: 'Januari', value: 1 },
  { title: 'Februari', value: 2 },
  { title: 'Maret', value: 3 },
  { title: 'April', value: 4 },
  { title: 'Mei', value: 5 },
  { title: 'Juni', value: 6 },
  { title: 'Juli', value: 7 },
  { title: 'Agustus', value: 8 },
  { title: 'September', value: 9 },
  { title: 'Oktober', value: 10 },
  { title: 'November', value: 11 },
  { title: 'Desember', value: 12 },
]

const yearOptions = computed(() => {
  const current = new Date().getFullYear()
  return [current - 1, current, current + 1]
})

const summaryHeaders = [
  { title: 'NAMA KARYAWAN', key: 'name' },
  { title: 'JABATAN', key: 'position' },
  { title: 'HADIR (HARI)', key: 'total_hadir', align: 'center' },
  { title: 'IZIN', key: 'total_izin', align: 'center' },
  { title: 'SAKIT', key: 'total_sakit', align: 'center' },
  { title: 'ALPHA', key: 'total_alpha', align: 'center' },
  { title: 'CUTI', key: 'total_cuti', align: 'center' },
]

const logHeaders = [
  { title: 'TANGGAL', key: 'date' },
  { title: 'NAMA', key: 'employee.name' },
  { title: 'CABANG', key: 'branch.name' },
  { title: 'JAM MASUK', key: 'clock_in' },
  { title: 'JAM KELUAR', key: 'clock_out' },
  { title: 'STATUS', key: 'status', align: 'center' },
]

const extractArray = val => {
  if (Array.isArray(val)) return val
  if (val && Array.isArray(val.data)) return val.data
  return []
}

const fetchBranches = async () => {
  try {
    const res = await $api('/apps/branches')
    branches.value = extractArray(res)
  } catch (e) {
    console.error(e)
  }
}

const fetchSummary = async () => {
  isLoading.value = true
  try {
    const params = {
      month: currentMonth.value,
      year: currentYear.value,
    }
    if (branchId.value && branchId.value !== 'all') params.branch_id = branchId.value

    const res = await $api('/apps/attendances/summary', { query: params })
    summaryData.value = extractArray(res)
  } catch (e) {
    console.error(e)
    snackbar.show('Gagal memuat rekap presensi', 'error')
  } finally {
    isLoading.value = false
  }
}

const fetchLogs = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      month: currentMonth.value,
      year: currentYear.value,
    }
    if (branchId.value && branchId.value !== 'all') params.branch_id = branchId.value

    const res = await $api('/apps/attendances', { query: params })
    attendances.value = extractArray(res)
    totalItems.value = res.total || attendances.value.length
  } catch (e) {
    console.error(e)
    snackbar.show('Gagal memuat log presensi', 'error')
  } finally {
    isLoading.value = false
  }
}

const fetchData = () => {
  if (activeTab.value === 'summary') {
    fetchSummary()
  } else {
    fetchLogs()
  }
}

onMounted(() => {
  fetchBranches()
  fetchData()
})

watch([activeTab, currentMonth, currentYear, branchId], () => {
  page.value = 1
  fetchData()
})

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
    const res = await $api('/apps/attendances/import', {
      method: 'POST',
      body: formData,
    })
    snackbar.show(res.message || 'Import presensi berhasil', 'success')
    fetchData()
  } catch (error) {
    console.error(error)
    snackbar.show(error.data?.message || 'Gagal melakukan import presensi', 'error')
  } finally {
    isImporting.value = false
    event.target.value = ''
  }
}

const getStatusColor = (status) => {
  const colors = {
    hadir: 'success',
    izin: 'warning',
    sakit: 'info',
    alpha: 'error',
    cuti: 'primary'
  }
  return colors[status] || 'secondary'
}

const downloadTemplate = async () => {
  if (branchId.value === 'all') {
    snackbar.show('Silakan pilih spesifik cabang terlebih dahulu untuk mengunduh template!', 'warning')
    return
  }

  isDownloadingTemplate.value = true
  try {
    const response = await $api('/apps/attendances/import-template', {
      query: {
        branch_id: branchId.value,
        month: currentMonth.value,
        year: currentYear.value,
      }
    })
    const blob = new Blob([response.csv || response], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `Template_Presensi_${currentYear.value}_${currentMonth.value}.csv`)
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
</script>

<template>
  <section>
    <!-- Header -->
    <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold mb-1 text-high-emphasis">
          Presensi & Kehadiran
        </h4>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Kelola log kehadiran harian dan rekap bulanan karyawan.
        </p>
      </div>

      <div class="d-flex align-center gap-3">
        <VBtn
          color="success"
          variant="outlined"
          prepend-icon="ri-download-line"
          :loading="isDownloadingTemplate"
          @click="downloadTemplate"
        >
          Download Template
        </VBtn>
        <input 
          ref="fileInput" 
          type="file" 
          accept=".csv" 
          style="display: none" 
          @change="handleFileUpload"
        >
        <VBtn
          color="warning"
          variant="tonal"
          prepend-icon="ri-upload-cloud-line"
          :loading="isImporting"
          @click="triggerFileInput"
        >
          Import Mesin Absen
        </VBtn>
      </div>
    </div>

    <!-- Filter Card -->
    <VCard elevation="1" class="border rounded-lg mb-6 pa-4">
      <div class="d-flex flex-wrap gap-4 align-center">
        <VSelect
          v-model="branchId"
          :items="[{ id: 'all', name: 'Semua Cabang' }, ...branches]"
          item-title="name"
          item-value="id"
          label="Cabang"
          density="compact"
          variant="outlined"
          prepend-inner-icon="ri-store-2-line"
          hide-details
          style="max-width: 250px"
        />
        <VSelect
          v-model="currentMonth"
          :items="monthOptions"
          label="Bulan"
          density="compact"
          hide-details
          style="max-width: 150px"
        />
        <VSelect
          v-model="currentYear"
          :items="yearOptions"
          label="Tahun"
          density="compact"
          hide-details
          style="max-width: 150px"
        />
      </div>
    </VCard>

    <VTabs v-model="activeTab" class="mb-4">
      <VTab value="summary">Rekap Bulanan</VTab>
      <VTab value="log">Log Harian</VTab>
    </VTabs>

    <VWindow v-model="activeTab">
      <!-- Tab Rekap -->
      <VWindowItem value="summary">
        <VCard elevation="1" class="border rounded-lg">
          <VDataTable
            :headers="summaryHeaders"
            :items="summaryData"
            :loading="isLoading"
            class="text-no-wrap"
          >
            <template #item.total_hadir="{ item }">
              <VChip size="small" color="success" variant="tonal">{{ item.total_hadir }}</VChip>
            </template>
            <template #item.total_izin="{ item }">
              <VChip size="small" color="warning" variant="tonal">{{ item.total_izin }}</VChip>
            </template>
            <template #item.total_sakit="{ item }">
              <VChip size="small" color="info" variant="tonal">{{ item.total_sakit }}</VChip>
            </template>
            <template #item.total_alpha="{ item }">
              <VChip size="small" color="error" variant="tonal">{{ item.total_alpha }}</VChip>
            </template>
          </VDataTable>
        </VCard>
      </VWindowItem>

      <!-- Tab Log -->
      <VWindowItem value="log">
        <VCard elevation="1" class="border rounded-lg">
          <VDataTableServer
            v-model:items-per-page="itemsPerPage"
            v-model:page="page"
            :headers="logHeaders"
            :items="attendances"
            :items-length="totalItems"
            :loading="isLoading"
            class="text-no-wrap"
            @update:options="fetchLogs"
          >
            <template #item.date="{ item }">
              <span class="font-weight-medium">{{ item.date }}</span>
            </template>
            <template #item.status="{ item }">
              <VChip size="small" :color="getStatusColor(item.status)" variant="tonal" class="text-capitalize">
                {{ item.status }}
              </VChip>
            </template>
            <template #item.clock_in="{ item }">
              {{ item.clock_in || '-' }}
            </template>
            <template #item.clock_out="{ item }">
              {{ item.clock_out || '-' }}
            </template>
            <template #bottom>
              <VCardText class="pt-2 pb-0">
                <div class="d-flex flex-wrap justify-space-between align-center gap-4">
                  <p class="text-sm text-disabled mb-0">
                    {{ paginationMeta({ page, itemsPerPage }, totalItems) }}
                  </p>
                  <VPagination
                    v-model="page"
                    :length="Math.ceil(totalItems / itemsPerPage)"
                    :total-visible="5"
                    density="comfortable"
                    active-color="primary"
                    @update:model-value="fetchLogs"
                  />
                </div>
              </VCardText>
            </template>
          </VDataTableServer>
        </VCard>
      </VWindowItem>
    </VWindow>

  </section>
</template>
