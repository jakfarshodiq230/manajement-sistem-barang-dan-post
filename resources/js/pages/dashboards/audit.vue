<script setup>
import { ref, onMounted } from 'vue'
import { $api } from '@/utils/api'
import dayjs from 'dayjs'

const analyticsData = ref({
  logs: [],
})

const isLoading = ref(true)
const page = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)
const totalPages = ref(1)

const tableHeaders = [
  { title: 'Waktu', key: 'created_at' },
  { title: 'Pengguna', key: 'user_name' },
  { title: 'Deskripsi Aktivitas', key: 'description' },
  { title: 'Modul (Event)', key: 'subject_type', align: 'center' },
  { title: 'Detail Properties', key: 'properties', align: 'center', sortable: false },
]

const viewDialog = ref(false)
const selectedProperties = ref('{}')

const showProperties = properties => {
  try {
    selectedProperties.value = typeof properties === 'string' ? JSON.parse(properties) : properties
  } catch (e) {
    selectedProperties.value = properties
  }
  viewDialog.value = true
}

const fetchAnalytics = async () => {
  try {
    isLoading.value = true

    const res = await $api('/apps/dashboards/audit', {
      params: {
        page: page.value,
        itemsPerPage: itemsPerPage.value,
      },
    })

    if (res.success) {
      analyticsData.value.logs = res.data.logs
      totalItems.value = res.data.total
      totalPages.value = res.data.last_page
    }
  } catch (error) {
    console.error('Error fetching audit analytics:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchAnalytics()
})

const handlePageChange = newPage => {
  page.value = newPage
  fetchAnalytics()
}

const formatDate = date => {
  return dayjs(date).format('DD MMM YYYY HH:mm:ss')
}

const formatSubject = type => {
  if (!type) return 'Sistem'
  const parts = type.split('\\')
  
  return parts[parts.length - 1]
}

const formatDescription = desc => {
  const map = {
    'created': 'Membuat data',
    'updated': 'Memperbarui data',
    'deleted': 'Menghapus data',
  }
  
  return map[desc] || desc
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard
        title="🛡️ Audit Keamanan & Log Aktivitas"
        subtitle="Jejak rekam aktivitas pengguna"
      >
        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          v-model:page="page"
          :headers="tableHeaders"
          :items="analyticsData.logs"
          :items-length="totalItems"
          :loading="isLoading"
          class="text-no-wrap"
          @update:options="fetchAnalytics"
        >
          <template #item.created_at="{ item }">
            <span class="text-medium-emphasis">{{ formatDate(item.created_at) }}</span>
          </template>

          <template #item.user_name="{ item }">
            <div class="d-flex align-center">
              <VAvatar
                variant="tonal"
                color="primary"
                class="me-3"
                size="34"
              >
                <VIcon
                  icon="ri-user-line"
                  size="20"
                />
              </VAvatar>
              <span class="font-weight-medium">{{ item.user_name || 'System' }}</span>
            </div>
          </template>

          <template #item.description="{ item }">
            {{ formatDescription(item.description) }}
          </template>

          <template #item.subject_type="{ item }">
            <VChip
              color="info"
              size="small"
            >
              {{ formatSubject(item.subject_type) }}
            </VChip>
          </template>

          <template #item.properties="{ item }">
            <VBtn
              v-if="item.properties && item.properties !== '[]' && item.properties !== '{}'"
              color="primary"
              variant="tonal"
              size="small"
              @click="showProperties(item.properties)"
            >
              Lihat Detail
            </VBtn>
            <span
              v-else
              class="text-disabled text-sm"
            >Tidak ada detail</span>
          </template>

          <template #no-data>
            Belum ada log aktivitas.
          </template>
        </VDataTableServer>
      </VCard>
    </VCol>
  </VRow>

  <!-- Dialog Properties -->
  <VDialog
    v-model="viewDialog"
    max-width="600"
  >
    <VCard title="Detail Properties">
      <VCardText>
        <pre class="bg-grey-100 pa-4 rounded text-sm overflow-x-auto">{{ JSON.stringify(selectedProperties, null, 2) }}</pre>
      </VCardText>
      <VCardActions>
        <VSpacer />
        <VBtn
          color="secondary"
          @click="viewDialog = false"
        >
          Tutup
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Dashboard Audit
</route>
