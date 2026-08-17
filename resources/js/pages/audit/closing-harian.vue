<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import AppDateTimePicker from '@core/components/app-form-elements/AppDateTimePicker.vue'

const { show: showSnackbar } = useSnackbarStore()

const todayDate = new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
const todayRawDate = new Date().toISOString().split('T')[0]

const branches = ref([])
const selectedBranch = ref(null)

const isLoading = ref(false)
const requiredClosingDate = ref('')
const isPastClosing = ref(false)
const requiredClosingDateFormatted = ref('')
const closingStatus = ref('draft')
const userData = JSON.parse(localStorage.getItem('userData') || '{}')
const isSuperAdmin = computed(() => {
  if (!userData || !userData.roles) return false
  return userData.roles.some(r => r === 'Super Admin' || r.name === 'Super Admin')
})
const unclosedDates = ref([])
const draftDates = ref([])
const completedDates = ref([])

const fpConfig = computed(() => {
  return {
    disable: completedDates.value,
    onDayCreate: function(dObj, dStr, fp, dayElem) {
      if (!dayElem.dateObj) return;
      
      // Use Flatpickr's own formatter for consistency
      const localDate = fp.formatDate(dayElem.dateObj, "Y-m-d");
      
      let dotColor = '';
      if (completedDates.value.includes(localDate)) {
        dotColor = '#28c76f'; // Success (Green)
      } else if (draftDates.value.includes(localDate)) {
        dotColor = '#ff9f43'; // Warning (Orange)
      } else if (unclosedDates.value.includes(localDate)) {
        dotColor = '#ea5455'; // Error (Red)
      }
      
      if (dotColor) {
        dayElem.innerHTML += `<span class="event-dot" style="background-color: ${dotColor};"></span>`;
      }
    }
  }
})
const overridePin = ref('')

watch(requiredClosingDate, val => {
  if (val) isPastClosing.value = val < todayRawDate
})

const history = ref([])
const totalItems = ref(0)
const options = ref({ page: 1, itemsPerPage: 10 })
const showForm = ref(false)

const activeTab = ref('input')
const monitoringDate = ref(new Date().toISOString().split('T')[0])
const monitoringData = ref([])
const isMonitoringLoading = ref(false)

const fetchMonitoring = async () => {
  isMonitoringLoading.value = true
  try {
    const res = await $api('/apps/cash-reconciliations/monitoring', {
      params: { date: monitoringDate.value },
    })

    if (res && res.success) {
      monitoringData.value = res.data
    }
  } catch (error) {
    console.error('Error fetching monitoring data:', error)
  } finally {
    isMonitoringLoading.value = false
  }
}

watch(activeTab, val => {
  if (val === 'monitoring') {
    fetchMonitoring()
  }
})

watch(monitoringDate, () => {
  if (activeTab.value === 'monitoring') {
    fetchMonitoring()
  }
})

const tableHeaders = [
  { title: 'Tanggal', key: 'date' },
  { title: 'Kasir', key: 'user.name' },
  { title: 'Uang Sistem', key: 'expected_cash' },
  { title: 'Uang Fisik', key: 'actual_cash' },
  { title: 'Selisih (Variance)', key: 'variance' },
  { title: 'Status Waktu', key: 'status_waktu' },
  { title: 'Status Laporan', key: 'status' },
  { title: 'Catatan', key: 'notes' },
  { title: 'Aksi', key: 'actions', sortable: false, align: 'center' },
]

const actualCashRaw = ref(0)

const actualCashDisplay = computed({
  get: () => {
    return actualCashRaw.value ? new Intl.NumberFormat('id-ID').format(actualCashRaw.value) : ''
  },
  set: val => {
    const numericStr = String(val).replace(/\D/g, '')

    actualCashRaw.value = numericStr ? parseInt(numericStr, 10) : 0
  },
})

const notes = ref('')

const fetchBranches = async () => {
  try {
    const res = await $api('/apps/branches?simple=true')
    if (res && res.length > 0) {
      branches.value = res.map(b => ({ title: b.name, value: b.id }))
      if (branches.value.length > 0) {
        selectedBranch.value = branches.value[0].value
      }
    }
  } catch (error) {
    console.error('Error fetching branches:', error)
  }
}

const fetchRequiredDate = async () => {
  if (!selectedBranch.value) return
  try {
    const res = await $api('/apps/cash-reconciliations/required-date', {
      params: { branch_id: selectedBranch.value },
    })

    if (res && res.date) {
      requiredClosingDate.value = res.date
      isPastClosing.value = res.date < todayRawDate

      const d = new Date(res.date)

      requiredClosingDateFormatted.value = d.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
    }
  } catch (error) {
    console.error('Error fetching required date:', error)
  }
}

const fetchHistory = async () => {
  if (!selectedBranch.value) return
  isLoading.value = true
  try {
    const res = await $api(`/apps/cash-reconciliations`, {
      params: {
        branch_id: selectedBranch.value,
        page: options.value.page,
        itemsPerPage: options.value.itemsPerPage,
      },
    })

    history.value = res.data
    totalItems.value = res.total
  } catch (error) {
    console.error('Error fetching history:', error)
  } finally {
    isLoading.value = false
  }
}

const submitClosing = async () => {
  if (!selectedBranch.value) return
  isLoading.value = true
  try {
    if (activeEditId.value) {
      await $api(`/apps/cash-reconciliations/${activeEditId.value}`, {
        method: 'PUT',
        body: {
          actual_cash: actualCashRaw.value,
          notes: notes.value,
        status: closingStatus.value,
          
        },
      })
      showSnackbar('Perubahan berhasil disimpan', 'success')
    } else {
      await $api('/apps/cash-reconciliations', {
        method: 'POST',
        body: {
          branch_id: selectedBranch.value,
          actual_cash: actualCashRaw.value,
          notes: notes.value,
        status: closingStatus.value,
          date: requiredClosingDate.value,
          
        },
      })
      showSnackbar('Closing berhasil disimpan', 'success')
    }
    showForm.value = false
    activeEditId.value = null
    actualCashRaw.value = 0
    closingStatus.value = 'draft'
    notes.value = ''
    fetchHistory()
    fetchRequiredDate()
  } catch (error) {
    console.error('Error submitting closing:', error)
    showSnackbar(error.data?.message || 'Terjadi kesalahan', 'error')
  } finally {
    isLoading.value = false
  }
}

const activeEditId = ref(null)

const showAjukanDialog = ref(false)
const pendingAction = ref(null)
const pendingItemId = ref(null)

const editItem = item => {
  activeEditId.value = item.id
  actualCashRaw.value = parseFloat(item.actual_cash)
  notes.value = item.notes
  closingStatus.value = item.status || 'draft'
  showForm.value = true
}

const deleteItem = async id => {
  if (confirm('Yakin ingin menghapus data ini?')) {
    isLoading.value = true
    try {
      await $api(`/apps/cash-reconciliations/${id}`, {
        method: 'DELETE'
      })
      showSnackbar('Data berhasil dihapus', 'success')
      fetchHistory()
      fetchRequiredDate()
    } catch (e) {
      showSnackbar(e.data?.message || 'Gagal menghapus data', 'error')
    } finally {
      isLoading.value = false
    }
  }
}

const ajukanItem = item => {
  pendingItemId.value = item.id
  actualCashRaw.value = parseFloat(item.actual_cash)
  notes.value = item.notes
  showAjukanDialog.value = true
}

const submitAjukan = async () => {
  isLoading.value = true
  try {
    await $api(`/apps/cash-reconciliations/${pendingItemId.value}`, {
      method: 'PUT',
      body: {
        actual_cash: actualCashRaw.value,
        notes: notes.value,
        status: 'completed',
      },
    })
    showSnackbar('Laporan berhasil diajukan menjadi Final', 'success')
    showAjukanDialog.value = false
    fetchHistory()
    fetchRequiredDate()
  } catch (e) {
    showSnackbar(e.data?.message || 'Gagal mengajukan laporan', 'error')
  } finally {
    isLoading.value = false
  }
}

const formatCurrency = value => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value)
}

watch(selectedBranch, () => {
  fetchRequiredDate()
  fetchHistory()
})

onMounted(async () => {
  await fetchBranches()
  fetchHistory()
})
</script>

<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h4 class="text-h4 mb-1">
          Audit Harian (Closing Kasir)
        </h4>
        <div class="text-body-1 text-medium-emphasis">
          {{ todayDate }}
        </div>
      </div>
      <div class="d-flex gap-4">
        <div style="width: 250px;">
          <VSelect
            v-model="selectedBranch"
            :items="branches"
            item-title="title"
            item-value="value"
            variant="outlined"
            density="compact"
            hide-details
            label="Pilih Cabang"
          />
        </div>
        <VBtn
          v-if="!showForm"
          color="primary"
          @click="showForm = true"
        >
          + Input Closing
        </VBtn>
      </div>
    </div>

    <VTabs
      v-model="activeTab"
      class="mb-4"
    >
      <VTab value="input">
        Input & Riwayat
      </VTab>
      <VTab value="monitoring">
        Monitoring Cabang
      </VTab>
    </VTabs>

    <VWindow v-model="activeTab">
      <VWindowItem value="input">
        <!-- Form Input Closing -->
        <VCard
          v-if="showForm"
          class="mb-4"
          :title="activeEditId ? 'Edit Kas Fisik' : 'Input Kas Fisik (Blind Close)'"
        >
          <VCardText>
            <VAlert
              color="info"
              variant="tonal"
              class="mb-4"
            >
              <div v-if="activeEditId">
                <strong>Tanggal Target Closing:</strong> Edit Mode (Tanggal Tidak Berubah)
              </div>
              <div v-else class="d-flex align-center gap-4">
                <strong style="white-space: nowrap;">Pilih Tanggal Closing:</strong>
                <AppDateTimePicker
                  :key="completedDates.length"
                  v-model="requiredClosingDate"
                  placeholder="Pilih Tanggal"
                  :config="fpConfig"
                  density="compact"
                  hide-details
                  style="min-width: 200px; background: white; border-radius: 6px;"
                />
              </div>
            </VAlert>

            

            <VAlert
                v-if="!activeEditId && isPastClosing"
                color="warning"
                variant="tonal"
                class="mb-4"
              >
                <strong>Keterlambatan!</strong> Anda sedang melakukan closing untuk hari kemarin karena melewati batas jam 12 malam. Pastikan alasan keterlambatan jelas pada catatan.
              </VAlert>

              <p class="text-body-2 mb-4">
              Hitung uang tunai yang ada di laci kasir saat ini dan masukkan totalnya. Sistem akan otomatis menghitung selisihnya.
            </p>
            <VRow>
              <VCol
                cols="12"
                md="4"
              >
                <VTextField
                  v-model="actualCashDisplay"
                  label="Total Uang Fisik (Rp)"
                  type="text"
                  variant="outlined"
                />
              </VCol>
              <VCol
                cols="12"
                md="8"
              >
                <VTextField
                  v-model="notes"
                  label="Catatan (Opsional)"
                  variant="outlined"
                />
              </VCol>
              
            </VRow>

          </VCardText>
          <VCardActions class="px-4 pb-4">
            <VSpacer />
            <VBtn
              variant="outlined"
              color="secondary"
              @click="showForm = false; activeEditId = null"
            >
              Batal
            </VBtn>
            <VBtn
              color="primary"
              :loading="isLoading"
              @click="submitClosing"
            >
              {{ activeEditId ? 'Simpan Perubahan' : 'Simpan Closing' }}
            </VBtn>
          </VCardActions>
        </VCard>

        <!-- History Table -->
        <VCard title="Riwayat Closing Cabang">
          <VDataTableServer
            v-model:options="options"
            :headers="tableHeaders"
            :items="history"
            :items-length="totalItems"
            :loading="isLoading"
            class="text-no-wrap"
            show-expand
            @update:options="fetchHistory"
          >
            <template #expanded-row="{ columns, item }">
              <tr>
                <td :colspan="columns.length" class="pa-4" style="background-color: rgba(var(--v-theme-on-surface), 0.04);">
                  <div class="d-flex flex-column gap-1">
                    <div class="text-subtitle-2 mb-1 text-primary">Rincian Uang Sistem ({{ formatCurrency(item.expected_cash) }})</div>
                    <div class="d-flex justify-space-between" style="max-width: 400px;">
                      <span class="text-body-2 text-medium-emphasis">Penjualan Tunai Murni:</span>
                      <span class="text-body-2 font-weight-medium">{{ formatCurrency(item.cash_sales_amount || 0) }}</span>
                    </div>
                    <div class="d-flex justify-space-between" style="max-width: 400px;">
                      <span class="text-body-2 text-medium-emphasis">Uang Muka (DP) Tunai:</span>
                      <span class="text-body-2 font-weight-medium">{{ formatCurrency(item.dp_cash_amount || 0) }}</span>
                    </div>
                    <div class="d-flex justify-space-between" style="max-width: 400px;">
                      <span class="text-body-2 text-medium-emphasis">Pelunasan Piutang Tunai:</span>
                      <span class="text-body-2 font-weight-medium">{{ formatCurrency(item.receivable_payments_amount || 0) }}</span>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
            <template #item.date="{ item }">
              {{ new Date(item.date).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' }) }}
            </template>
        
            <template #item.expected_cash="{ item }">
              {{ formatCurrency(item.expected_cash) }}
            </template>
        
            <template #item.actual_cash="{ item }">
              {{ formatCurrency(item.actual_cash) }}
            </template>
        
            <template #item.variance="{ item }">
              <VChip
                :color="item.variance == 0 ? 'success' : 'error'"
                size="small"
              >
                {{ formatCurrency(item.variance) }}
              </VChip>
            </template>

            <template #item.status_waktu="{ item }">
              <VChip
                :color="new Date(item.created_at).toISOString().split('T')[0] > item.date ? 'warning' : 'success'"
                size="small"
              >
                {{ new Date(item.created_at).toISOString().split('T')[0] > item.date ? 'Terlambat' : 'Tepat Waktu' }}
              </VChip>
            </template>
        
            <template #item.notes="{ item }">
              {{ item.notes || '-' }}
            </template>
            <template #item.status="{ item }">
              <VChip
                :color="item.status === 'completed' ? 'success' : 'warning'"
                size="small"
              >
                {{ item.status === 'completed' ? 'Final (Completed)' : 'Draft' }}
              </VChip>
            </template>
            <template #item.actions="{ item }">
              <VBtn
                v-if="item.status !== 'completed' || isSuperAdmin"
                size="small"
                color="success"
                variant="tonal"
                @click="ajukanItem(item)"
                class="mr-2"
              >
                Ajukan
              </VBtn>

              <VBtn
                v-if="item.status !== 'completed' || isSuperAdmin"
                icon="ri-edit-line"
                variant="text"
                size="small"
                color="primary"
                @click="editItem(item)"
              />
              <VBtn
                v-if="item.status !== 'completed' || isSuperAdmin"
                icon="ri-delete-bin-line"
                variant="text"
                size="small"
                color="error"
                @click="deleteItem(item.id)"
              />
            </template>
        
            <template #no-data>
              Belum ada riwayat closing.
            </template>
          </VDataTableServer>
        </VCard>
      </VWindowItem>

      <VWindowItem value="monitoring">
        <VCard title="Monitoring Status Closing Cabang">
          <template #append>
            <div style="width: 200px">
              <VTextField
                v-model="monitoringDate"
                type="date"
                label="Tanggal Monitoring"
                density="compact"
                hide-details
                variant="outlined"
              />
            </div>
          </template>
          
          <VDataTable
            :headers="[
              { title: 'Cabang', key: 'branch_name' },
              { title: 'Status', key: 'is_closed', align: 'center' },
              { title: 'Status Waktu', key: 'status_waktu', align: 'center' },
              { title: 'Waktu Closing', key: 'closed_at' },
              { title: 'Petugas', key: 'closed_by' },
              { title: 'Uang Fisik', key: 'actual_cash' },
              { title: 'Selisih Kas', key: 'variance' },
            ]"
            :items="monitoringData"
            :loading="isMonitoringLoading"
            :items-per-page="-1"
            hide-default-footer
            class="text-no-wrap"
          >
            <template #item.is_closed="{ item }">
              <VChip
                :color="item.is_closed ? 'success' : 'error'"
                size="small"
              >
                {{ item.is_closed ? 'Sudah Closing' : 'Belum Closing' }}
              </VChip>
            </template>
            <template #item.status_waktu="{ item }">
              <VChip
                v-if="item.is_closed"
                :color="new Date(item.closed_at).toISOString().split('T')[0] > monitoringDate ? 'warning' : 'success'"
                size="small"
              >
                {{ new Date(item.closed_at).toISOString().split('T')[0] > monitoringDate ? 'Terlambat' : 'Tepat Waktu' }}
              </VChip>
              <span v-else>-</span>
            </template>
            <template #item.closed_at="{ item }">
              {{ item.closed_at ? new Date(item.closed_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '-' }}
            </template>
            <template #item.closed_by="{ item }">
              {{ item.closed_by || '-' }}
            </template>
            <template #item.actual_cash="{ item }">
              {{ item.is_closed ? formatCurrency(item.actual_cash) : '-' }}
            </template>
            <template #item.variance="{ item }">
              <VChip
                v-if="item.is_closed"
                :color="item.variance == 0 ? 'success' : (item.variance > 0 ? 'info' : 'error')"
                size="small"
              >
                {{ formatCurrency(item.variance) }}
              </VChip>
              <span v-else>-</span>
            </template>
          </VDataTable>
        </VCard>
      </VWindowItem>
    </VWindow>

    <!-- PIN Dialog -->
    
    <!-- Dialog Konfirmasi Ajukan -->
    <VDialog v-model="showAjukanDialog" max-width="400">
      <VCard>
        <VCardTitle class="text-h5 pt-4 px-4 pb-2">Konfirmasi Pengajuan</VCardTitle>
        <VCardText>
          Apakah Anda yakin ingin mengajukan laporan closing ini menjadi <strong>Final (Completed)</strong>? 
          <br><br>
          <span class="text-error">Perhatian: Data yang sudah diajukan tidak dapat diubah atau dihapus kembali!</span>
        </VCardText>
        <VCardActions class="px-4 pb-4">
          <VSpacer />
          <VBtn color="secondary" variant="outlined" @click="showAjukanDialog = false">Batal</VBtn>
          <VBtn color="success" @click="submitAjukan">Ya, Ajukan Final</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Closing Harian
</route>

<style>
.event-dot {
  position: absolute;
  bottom: 3px;
  left: 50%;
  transform: translateX(-50%);
  width: 5px;
  height: 5px;
  border-radius: 50%;
}
.flatpickr-day {
  position: relative;
}
</style>
