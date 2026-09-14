<script setup>
import { ref, onMounted } from 'vue'

const pendingJobs = ref(0)
const failedJobsCount = ref(0)

const failedJobs = ref([])
const totalFailedJobs = ref(0)
const loading = ref(false)
const options = ref({ page: 1, itemsPerPage: 10 })

const fetchStats = async () => {
  try {
    const res = await $api('/queue/stats')
    pendingJobs.value = res.pending
    failedJobsCount.value = res.failed
  } catch (error) {
    console.error('Gagal mengambil statistik antrean:', error)
  }
}

const fetchFailedJobs = async () => {
  loading.value = true
  try {
    const res = await $api('/queue/failed', {
      params: {
        page: options.value.page,
        itemsPerPage: options.value.itemsPerPage
      }
    })
    failedJobs.value = res.jobs
    totalFailedJobs.value = res.total
  } catch (error) {
    console.error('Gagal mengambil data antrean gagal:', error)
  } finally {
    loading.value = false
  }
}

const retryJob = async (id) => {
  if (!confirm('Apakah Anda yakin ingin mencoba ulang (retry) antrean ini?')) return
  try {
    await $api(`/queue/retry/${id}`, { method: 'POST' })
    fetchStats()
    fetchFailedJobs()
  } catch (error) {
    alert('Gagal me-retry antrean.')
  }
}

const deleteJob = async (id) => {
  if (!confirm('Apakah Anda yakin ingin menghapus antrean ini secara permanen?')) return
  try {
    await $api(`/queue/delete/${id}`, { method: 'DELETE' })
    fetchStats()
    fetchFailedJobs()
  } catch (error) {
    alert('Gagal menghapus antrean.')
  }
}

onMounted(() => {
  fetchStats()
  fetchFailedJobs()
  // Auto refresh stats every 5 seconds
  setInterval(fetchStats, 5000)
})

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Koneksi', key: 'connection' },
  { title: 'Antrean', key: 'queue' },
  { title: 'Exception', key: 'exception' },
  { title: 'Waktu Gagal', key: 'failed_at' },
  { title: 'Aksi', key: 'actions', sortable: false, align: 'center' }
]
</script>

<template>
  <div>
    <VRow>
      <VCol cols="12" md="6">
        <VCard class="text-center">
          <VCardText>
            <h3 class="text-h4 text-primary font-weight-bold">{{ pendingJobs }}</h3>
            <span class="text-subtitle-1">Antrean Menunggu (Pending)</span>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="6">
        <VCard class="text-center">
          <VCardText>
            <h3 class="text-h4 text-error font-weight-bold">{{ failedJobsCount }}</h3>
            <span class="text-subtitle-1">Antrean Gagal (Failed)</span>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VCard class="mt-6">
      <VCardTitle class="d-flex align-center pb-3">
        Daftar Antrean Gagal
        <VSpacer />
        <VBtn color="primary" @click="fetchFailedJobs" :loading="loading" prepend-icon="ri-refresh-line">
          Refresh Tabel
        </VBtn>
      </VCardTitle>

      <VDataTableServer
        v-model:items-per-page="options.itemsPerPage"
        v-model:page="options.page"
        :headers="headers"
        :items="failedJobs"
        :items-length="totalFailedJobs"
        :loading="loading"
        @update:options="fetchFailedJobs"
        class="text-no-wrap"
      >
        <template #item.exception="{ item }">
          <div class="text-truncate" style="max-width: 300px;" :title="item.exception">
            {{ item.exception.split('\\n')[0] }}
          </div>
        </template>
        <template #item.actions="{ item }">
          <VBtn icon size="small" color="info" variant="text" @click="retryJob(item.id)" title="Coba Ulang">
            <VIcon icon="ri-restart-line" />
          </VBtn>
          <VBtn icon size="small" color="error" variant="text" @click="deleteJob(item.id)" title="Hapus Permanen">
            <VIcon icon="ri-delete-bin-line" />
          </VBtn>
        </template>
      </VDataTableServer>
    </VCard>
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Manajemen Antrean
</route>
