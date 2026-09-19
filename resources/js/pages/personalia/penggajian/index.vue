<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'

definePage({
  meta: {
    action: 'read',
    subject: 'Penggajian',
  },
})

const snackbar = useSnackbarStore()

const currentMonth = ref(new Date().getMonth() + 1)
const currentYear = ref(new Date().getFullYear())
const branchId = ref('all')
const branches = ref([])

const employeesData = ref([])
const historyData = ref([])

const activeTab = ref('generate') // generate, history
const isGenerating = ref(false)
const isLoading = ref(false)

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

const tableHeaders = [
  { title: 'NAMA', key: 'name' },
  { title: 'JABATAN', key: 'position' },
  { title: 'GAJI POKOK', key: 'base_salary' },
  { title: 'KEHADIRAN (HARI)', key: 'total_attendance_days' },
  { title: 'TUNJANGAN', key: 'allowances' },
  { title: 'POTONGAN', key: 'deductions' },
  { title: 'BONUS DINAMIS', key: 'bonus' },
  { title: 'GAJI BERSIH', key: 'net_salary' },
]

const historyHeaders = [
  { title: 'PERIODE', key: 'period' },
  { title: 'NAMA', key: 'employee.name' },
  { title: 'GAJI BERSIH', key: 'net_salary' },
  { title: 'STATUS', key: 'status' },
  { title: 'AKSI', key: 'actions', sortable: false },
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
    console.error('Failed to load branches:', e)
  }
}

const fetchEmployeesForPayroll = async () => {
  isLoading.value = true
  try {
    const res = await $api('/apps/payrolls/employees', { 
      query: { 
        branch_id: branchId.value !== 'all' ? branchId.value : undefined,
        month: currentMonth.value,
        year: currentYear.value,
      } 
    })
    employeesData.value = extractArray(res).map(emp => ({
      ...emp,
      // clone values so we can edit without reference issues
    }))
  } catch (e) {
    console.error(e)
    snackbar.show('Gagal memuat data karyawan', 'error')
  } finally {
    isLoading.value = false
  }
}

const fetchHistory = async () => {
  isLoading.value = true
  try {
    const params = {}
    if (branchId.value && branchId.value !== 'all') params.branch_id = branchId.value

    const res = await $api('/apps/payrolls', { query: params })
    historyData.value = extractArray(res)
  } catch (e) {
    console.error(e)
    snackbar.show('Gagal memuat riwayat penggajian', 'error')
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchBranches()
  fetchEmployeesForPayroll()
})

watch([activeTab, branchId, currentMonth, currentYear], () => {
  if (activeTab.value === 'generate') {
    fetchEmployeesForPayroll()
  } else {
    fetchHistory()
  }
})

const calculateNet = (item) => {
  const base = Number(item.base_salary) || 0
  const allowance = Number(item.allowances) || 0
  const bonus = Number(item.bonus) || 0
  const deduction = Number(item.deductions) || 0
  item.net_salary = base + allowance + bonus - deduction
}

const formatCurrency = value => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value)
}

const generatePayroll = async () => {
  if (branchId.value === 'all') {
    snackbar.show('Silakan pilih spesifik cabang terlebih dahulu untuk menerbitkan gaji!', 'warning')
    return
  }

  isGenerating.value = true
  try {
    await $api('/apps/payrolls/generate', {
      method: 'POST',
      body: {
        month: String(currentMonth.value),
        year: String(currentYear.value),
        branch_id: branchId.value,
        payrolls: employeesData.value,
      }
    })
    
    snackbar.show('Penggajian berhasil diterbitkan!', 'success')
    activeTab.value = 'history'
  } catch (e) {
    console.error(e)
    snackbar.show(e.data?.message || 'Gagal menerbitkan gaji', 'error')
  } finally {
    isGenerating.value = false
  }
}

const printPdf = async (id) => {
  try {
    const response = await $api(`/apps/payrolls/${id}/pdf`, { responseType: 'blob' })
    
    // Create blob link to download
    const url = window.URL.createObjectURL(new Blob([response], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `Slip_Gaji_${id}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (e) {
    console.error('Failed to download PDF:', e)
    snackbar.show('Gagal mengunduh PDF', 'error')
  }
}

const isDetailDialogOpen = ref(false)
const selectedPayroll = ref(null)

const openDetail = (item) => {
  selectedPayroll.value = item
  isDetailDialogOpen.value = true
}
</script>

<template>
  <section>
    <!-- Header -->
    <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold mb-1 text-high-emphasis">
          Personalia - Penggajian
        </h4>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Proses slip gaji, absensi bulanan, serta bonus dan potongan.
        </p>
      </div>
      
      <div class="d-flex align-center gap-3">
        <VSelect
          v-model="branchId"
          :items="[{ id: 'all', name: 'Semua Cabang' }, ...branches]"
          item-title="name"
          item-value="id"
          density="compact"
          variant="outlined"
          prepend-inner-icon="ri-store-2-line"
          hide-details
          style="width: 200px"
        />
      </div>
    </div>

    <!-- Tabs -->
    <VTabs v-model="activeTab" class="mb-4">
      <VTab value="generate">Proses Gaji</VTab>
      <VTab value="history">Riwayat & Slip Gaji</VTab>
    </VTabs>

    <!-- Tab 1: Proses Gaji -->
    <VWindow v-model="activeTab">
      <VWindowItem value="generate">
        <VCard  class="border rounded-lg mb-4 pa-4">
          <div class="d-flex flex-wrap gap-4 align-center justify-space-between">
            <div class="d-flex flex-wrap gap-4 align-center">
              <VSelect
                v-model="currentMonth"
                :items="monthOptions"
                label="Bulan"
                density="compact"
                hide-details
                style="width: 150px"
              />
              <VSelect
                v-model="currentYear"
                :items="yearOptions"
                label="Tahun"
                density="compact"
                hide-details
                style="width: 150px"
              />
            </div>
            <div class="d-flex align-center gap-3">
              <VBtn
                color="primary"
                prepend-icon="ri-check-double-line"
                :loading="isGenerating"
                :disabled="!employeesData.length"
                @click="generatePayroll"
              >
                Terbitkan Gaji
              </VBtn>
            </div>
          </div>
        </VCard>

        <VCard  class="border rounded-lg mb-6 pa-4">
          <VDataTable
            :headers="tableHeaders"
            :items="employeesData"
            :loading="isLoading"
            class="text-no-wrap"
          >
            <!-- Custom columns for inputs -->
            <template #item.base_salary="{ item }">
              {{ formatCurrency(item.base_salary) }}
            </template>
            
            <template #item.total_attendance_days="{ item }">
              <VTextField
                v-model.number="item.total_attendance_days"
                type="number"
                density="compact"
                hide-details
                style="width: 80px"
              />
            </template>

            <template #item.allowances="{ item }">
              <VTextField
                v-model.number="item.allowances"
                type="number"
                density="compact"
                hide-details
                @input="calculateNet(item)"
              />
            </template>

            <template #item.deductions="{ item }">
              <VTextField
                v-model.number="item.deductions"
                type="number"
                density="compact"
                hide-details
                @input="calculateNet(item)"
              />
            </template>
            
            <template #item.bonus="{ item }">
              <VTextField
                v-model.number="item.bonus"
                type="number"
                density="compact"
                hide-details
                @input="calculateNet(item)"
              />
            </template>

            <template #item.net_salary="{ item }">
              <span class="font-weight-bold text-primary">{{ formatCurrency(item.net_salary) }}</span>
            </template>
            
          </VDataTable>
        </VCard>
      </VWindowItem>

      <!-- Tab 2: Riwayat -->
      <VWindowItem value="history">
        <VCard  class="border rounded-lg">
          <VDataTable
            :headers="historyHeaders"
            :items="historyData"
            :loading="isLoading"
          >
            <template #item.period="{ item }">
              {{ item.period_month }} / {{ item.period_year }}
            </template>
            <template #item.net_salary="{ item }">
              {{ formatCurrency(item.net_salary) }}
            </template>
            <template #item.status="{ item }">
              <VChip size="small" color="success" variant="tonal">
                {{ item.status }}
              </VChip>
            </template>
            <template #item.actions="{ item }">
              <IconBtn
                color="info"
                size="small"
                class="me-2"
                @click="openDetail(item)"
              >
                <VIcon icon="ri-eye-line" />
              </IconBtn>
              <IconBtn
                color="primary"
                size="small"
                @click="printPdf(item.id)"
              >
                <VIcon icon="ri-printer-line" />
              </IconBtn>
            </template>
          </VDataTable>
        </VCard>
      </VWindowItem>
    </VWindow>

    <!-- Dialog Rincian -->
    <VDialog v-model="isDetailDialogOpen" max-width="500">
      <VCard v-if="selectedPayroll">
        <VCardItem class="pb-0">
          <VCardTitle>Rincian Gaji - {{ selectedPayroll.employee?.name }}</VCardTitle>
          <VCardSubtitle>Periode: {{ selectedPayroll.period_month }} / {{ selectedPayroll.period_year }}</VCardSubtitle>
        </VCardItem>
        <VCardText class="pt-4">
          <VList lines="one" class="card-list">
            <VListItem>
              <template #prepend>
                <VIcon icon="ri-money-dollar-circle-line" class="me-3" color="primary" />
              </template>
              <VListItemTitle>Gaji Pokok</VListItemTitle>
              <template #append>
                <div class="font-weight-medium">{{ formatCurrency(selectedPayroll.base_salary) }}</div>
              </template>
            </VListItem>
            <VListItem>
              <template #prepend>
                <VIcon icon="ri-calendar-check-line" class="me-3" color="info" />
              </template>
              <VListItemTitle>Total Kehadiran</VListItemTitle>
              <template #append>
                <div class="font-weight-medium">{{ selectedPayroll.total_attendance_days }} Hari</div>
              </template>
            </VListItem>
            <VListItem>
              <template #prepend>
                <VIcon icon="ri-arrow-up-circle-line" class="me-3" color="success" />
              </template>
              <VListItemTitle>Total Tunjangan</VListItemTitle>
              <template #append>
                <div class="font-weight-medium text-success">+ {{ formatCurrency(selectedPayroll.allowances) }}</div>
              </template>
            </VListItem>
            <VListItem>
              <template #prepend>
                <VIcon icon="ri-gift-line" class="me-3" color="warning" />
              </template>
              <VListItemTitle>Bonus / Lembur</VListItemTitle>
              <template #append>
                <div class="font-weight-medium text-warning">+ {{ formatCurrency(selectedPayroll.bonus) }}</div>
              </template>
            </VListItem>
            <VListItem>
              <template #prepend>
                <VIcon icon="ri-arrow-down-circle-line" class="me-3" color="error" />
              </template>
              <VListItemTitle>Total Potongan</VListItemTitle>
              <template #append>
                <div class="font-weight-medium text-error">- {{ formatCurrency(selectedPayroll.deductions) }}</div>
              </template>
            </VListItem>
            <VDivider class="my-2" />
            <VListItem>
              <VListItemTitle class="font-weight-bold">TAKE HOME PAY</VListItemTitle>
              <template #append>
                <div class="font-weight-bold text-primary text-h6">{{ formatCurrency(selectedPayroll.net_salary) }}</div>
              </template>
            </VListItem>
          </VList>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn color="secondary" variant="tonal" @click="isDetailDialogOpen = false">Tutup</VBtn>
          <VBtn color="primary" variant="elevated" @click="printPdf(selectedPayroll.id)">Cetak PDF</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </section>
</template>
