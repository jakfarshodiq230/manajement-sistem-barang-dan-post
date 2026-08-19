<script setup>
import { ref, onMounted } from 'vue'
import { $api } from '@/utils/api'
import dayjs from 'dayjs'

const analyticsData = ref({
  logs: [],
})

const summary = ref({
  total: 0,
  created: 0,
  updated: 0,
  deleted: 0,
})

const isLoading = ref(true)
const page = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)
const totalPages = ref(1)
const search = ref('')
const selectedEvent = ref(null)
let searchTimeout = null

const tableHeaders = [
  { title: 'WAKTU & TANGGAL', key: 'created_at' },
  { title: 'PENGGUNA / USER', key: 'user_name' },
  { title: 'AKSI / AKTIVITAS', key: 'description' },
  { title: 'MODUL ENTITAS', key: 'subject_type', align: 'center' },
  { title: 'DETAIL PERUBAHAN', key: 'properties', align: 'center', sortable: false },
]

const viewDialog = ref(false)
const selectedProperties = ref('{}')
const selectedLog = ref(null)

const showProperties = (properties, item) => {
  selectedLog.value = item
  try {
    selectedProperties.value = typeof properties === 'string' ? JSON.parse(properties) : properties
  } catch (e) {
    selectedProperties.value = properties
  }
  viewDialog.value = true
}

const fetchAnalytics = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
    }
    if (search.value) params.search = search.value
    if (selectedEvent.value) params.event = selectedEvent.value

    const res = await $api('/apps/dashboards/audit', { params })

    if (res.success) {
      analyticsData.value.logs = res.data.logs || []
      totalItems.value = res.data.total || 0
      totalPages.value = res.data.last_page || 1
      if (res.data.summary) {
        summary.value = res.data.summary
      }
    }
  } catch (error) {
    console.error('Error fetching audit analytics:', error)
  } finally {
    isLoading.value = false
  }
}

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchAnalytics()
  }, 450)
}

const onFilterChange = () => {
  page.value = 1
  fetchAnalytics()
}

onMounted(() => {
  fetchAnalytics()
})

const formatDate = date => {
  return dayjs(date).format('DD MMM YYYY, HH:mm:ss')
}

const formatSubject = type => {
  if (!type) return 'Sistem'
  const parts = type.split('\\')
  return parts[parts.length - 1]
}

const getEventColor = desc => {
  if (desc === 'created') return 'success'
  if (desc === 'updated') return 'info'
  if (desc === 'deleted') return 'error'
  return 'secondary'
}

const getEventIcon = desc => {
  if (desc === 'created') return 'ri-add-circle-line'
  if (desc === 'updated') return 'ri-edit-line'
  if (desc === 'deleted') return 'ri-delete-bin-line'
  return 'ri-information-line'
}

const formatDescription = desc => {
  const map = {
    'created': 'Membuat data baru',
    'updated': 'Memperbarui data',
    'deleted': 'Menghapus data',
  }
  return map[desc] || desc
}
</script>

<template>
  <div class="pa-4">
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="text-h4 font-weight-bold mb-1">
          Audit Keamanan & Log Aktivitas
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Jejak rekam audit internal, pencatatan aktivitas pengguna, dan histori modifikasi data.
        </p>
      </div>

      <VBtn
        color="secondary"
        variant="tonal"
        prepend-icon="ri-refresh-line"
        :loading="isLoading"
        @click="fetchAnalytics"
      >
        Muat Ulang
      </VBtn>
    </div>

    <!-- KPI Summary Row -->
    <VRow class="mb-4">
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-primary">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-medium-emphasis font-weight-medium">TOTAL LOG TERCATAT</div>
              <div class="text-h4 font-weight-bold text-primary mt-1">{{ summary.total.toLocaleString('id-ID') }} <span class="text-caption text-medium-emphasis">Aksi</span></div>
            </div>
            <VAvatar color="primary" variant="tonal" size="44">
              <VIcon icon="ri-shield-check-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Seluruh jejak aktivitas</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-success">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-success font-weight-bold">DATA BARU DIBUAT</div>
              <div class="text-h4 font-weight-bold text-success mt-1">{{ summary.created.toLocaleString('id-ID') }} <span class="text-caption text-medium-emphasis">Record</span></div>
            </div>
            <VAvatar color="success" variant="tonal" size="44">
              <VIcon icon="ri-add-circle-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Aktivitas insert/create</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-info">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-info font-weight-bold">DATA DIPERBARUI</div>
              <div class="text-h4 font-weight-bold text-info mt-1">{{ summary.updated.toLocaleString('id-ID') }} <span class="text-caption text-medium-emphasis">Record</span></div>
            </div>
            <VAvatar color="info" variant="tonal" size="44">
              <VIcon icon="ri-edit-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Aktivitas edit/update</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-error">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-error font-weight-bold">DATA DIHAPUS</div>
              <div class="text-h4 font-weight-bold text-error mt-1">{{ summary.deleted.toLocaleString('id-ID') }} <span class="text-caption text-medium-emphasis">Record</span></div>
            </div>
            <VAvatar color="error" variant="tonal" size="44">
              <VIcon icon="ri-delete-bin-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Aktivitas delete/destroy</div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Audit Logs Table Card -->
    <VCard elevation="2">
      <VCardText class="d-flex flex-wrap gap-4 align-center py-4">
        <VSelect
          v-model="selectedEvent"
          :items="[
            { title: 'Semua Tipe Aksi', value: null },
            { title: 'Membuat (Created)', value: 'created' },
            { title: 'Memperbarui (Updated)', value: 'updated' },
            { title: 'Menghapus (Deleted)', value: 'deleted' },
          ]"
          item-title="title"
          item-value="value"
          density="compact"
          label="Filter Aksi"
          style="max-width: 220px;"
          hide-details
          @update:model-value="onFilterChange"
        />

        <VTextField
          v-model="search"
          density="compact"
          placeholder="Cari Pengguna / Entitas / Aksi..."
          prepend-inner-icon="ri-search-line"
          style="max-width: 320px;"
          clearable
          hide-details
          @update:model-value="handleSearch"
        />

        <VSpacer />
      </VCardText>

      <VDivider />

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="tableHeaders"
        :items="analyticsData.logs"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        hover
        @update:options="fetchAnalytics"
      >
        <template #item.created_at="{ item }">
          <span class="text-caption font-weight-medium">{{ formatDate(item.created_at) }}</span>
        </template>

        <template #item.user_name="{ item }">
          <div class="d-flex align-center">
            <VAvatar variant="tonal" color="primary" class="me-2" size="32">
              <VIcon icon="ri-user-line" size="18" />
            </VAvatar>
            <span class="font-weight-medium">{{ item.user_name || 'System / Auto' }}</span>
          </div>
        </template>

        <template #item.description="{ item }">
          <VChip
            :color="getEventColor(item.description)"
            size="small"
            variant="tonal"
            class="font-weight-bold"
          >
            <VIcon :icon="getEventIcon(item.description)" size="14" class="me-1" />
            {{ formatDescription(item.description) }}
          </VChip>
        </template>

        <template #item.subject_type="{ item }">
          <VChip color="secondary" size="small" variant="outlined" class="text-caption">
            {{ formatSubject(item.subject_type) }}
          </VChip>
        </template>

        <template #item.properties="{ item }">
          <VBtn
            v-if="item.properties && item.properties !== '[]' && item.properties !== '{}'"
            color="primary"
            variant="tonal"
            size="x-small"
            prepend-icon="ri-eye-line"
            @click="showProperties(item.properties, item)"
          >
            Inspeksi Perubahan
          </VBtn>
          <span v-else class="text-disabled text-caption">-</span>
        </template>

        <template #no-data>
          <div class="pa-6 text-center text-medium-emphasis">
            Belum ada jejak audit log yang sesuai dengan filter.
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Dialog Properties Inspector -->
    <VDialog v-model="viewDialog" max-width="650">
      <VCard>
        <VCardTitle class="d-flex align-center justify-space-between pa-4 bg-primary text-white">
          <div class="d-flex align-center gap-2">
            <VIcon icon="ri-file-code-line" size="20" />
            <span>Detail Rekam Perubahan Data</span>
          </div>
          <VBtn icon variant="text" color="white" size="small" @click="viewDialog = false">
            <VIcon icon="ri-close-line" size="20" />
          </VBtn>
        </VCardTitle>
        <VCardText class="pa-4">
          <div v-if="selectedLog" class="mb-3 d-flex flex-wrap gap-4 text-caption text-medium-emphasis">
            <div><strong>User:</strong> {{ selectedLog.user_name || 'System' }}</div>
            <div><strong>Waktu:</strong> {{ formatDate(selectedLog.created_at) }}</div>
            <div><strong>Entitas:</strong> {{ formatSubject(selectedLog.subject_type) }}</div>
          </div>
          <VDivider class="mb-3" />
          <pre class="bg-grey-100 pa-4 rounded text-caption overflow-x-auto border font-monospace" style="max-height: 400px;">{{ JSON.stringify(selectedProperties, null, 2) }}</pre>
        </VCardText>
        <VCardActions class="pa-4 border-t">
          <VSpacer />
          <VBtn color="secondary" variant="tonal" @click="viewDialog = false">
            Tutup
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Dashboard Analytics
</route>
