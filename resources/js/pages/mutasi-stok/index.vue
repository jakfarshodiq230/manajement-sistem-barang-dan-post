<script setup>
import { ref, onMounted, computed } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import AddNewMutasiDrawer from './AddNewMutasiDrawer.vue'
import SimpleConfirmDialog from '@/components/dialogs/SimpleConfirmDialog.vue'

const mutasiList = ref([])
const branches = ref([])
const masterProducts = ref([])
const search = ref('')
const isLoading = ref(false)
const isAddNewDrawerVisible = ref(false)
const isTrackingDialogVisible = ref(false)
const trackingMutasi = ref(null)
const activeTab = ref('all')

// Pagination
const page = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)
let searchTimeout = null

const snackbar = useSnackbarStore()

const fetchData = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
    }
    
    if (search.value) params.search = search.value
    
    if (activeTab.value === 'need_approval') {
      params.status = 'pending'
    } else if (activeTab.value === 'approved') {
      params.status = 'approved'
    } else if (activeTab.value === 'rejected') {
      params.status = 'rejected'
    }

    const [mutasiData, branchData, productData] = await Promise.all([
      $api('/apps/stock-transfers', { query: params }),
      $api('/apps/branches'),
      $api('/apps/products'),
    ])

    mutasiList.value = mutasiData.data || mutasiData
    if (mutasiData.total !== undefined) {
      totalItems.value = mutasiData.total
    }
    branches.value = branchData
    masterProducts.value = productData
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mengambil data mutasi stok', 'error')
  } finally {
    isLoading.value = false
  }
}

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchData()
  }, 500)
}

onMounted(() => {
  fetchData()
})

const saveMutasi = async data => {
  try {
    await $api('/apps/stock-transfers', {
      method: 'POST',
      body: data,
    })
    snackbar.show('Permintaan mutasi berhasil dibuat', 'success')
    fetchData()
  } catch (error) {
    console.error(error)
    const errorMsg = error.response?._data?.error || error.response?._data?.message || 'Gagal membuat mutasi'
    snackbar.show(errorMsg, 'error')
  }
}

const tableHeaders = [
  { title: 'NO. REFERENSI', key: 'reference_no' },
  { title: 'TANGGAL', key: 'created_at' },
  { title: 'CABANG ASAL', key: 'source_branch.name' },
  { title: 'CABANG TUJUAN', key: 'destination_branch.name' },
  { title: 'PEMBUAT', key: 'created_by.name' },
  { title: 'STATUS', key: 'status' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const countNeedApproval = computed(() => {
  return mutasiList.value.filter(item => item.status === 'pending').length
})

const filteredMutasi = computed(() => {
  return mutasiList.value
})

const openTrackingDialog = mutasi => {
  trackingMutasi.value = mutasi
  isTrackingDialogVisible.value = true
}

const pendingAction = ref(null)
const isConfirmDialogVisible = ref(false)
const confirmTitle = ref('')
const confirmMessage = ref('')
const confirmActionText = ref('')
const confirmColor = ref('')

const confirmUpdateStatus = (id, action) => {
  const isApprove = action === 'approve'
  confirmTitle.value = isApprove ? 'Setujui Mutasi' : 'Tolak Mutasi'
  confirmMessage.value = `Apakah Anda yakin ingin ${isApprove ? 'menyetujui' : 'menolak'} mutasi ini?`
  confirmActionText.value = isApprove ? 'Ya, Setujui' : 'Ya, Tolak'
  confirmColor.value = isApprove ? 'success' : 'error'
  
  pendingAction.value = { id, action }
  isConfirmDialogVisible.value = true
}

const onConfirmAction = async (confirmed) => {
  if (confirmed && pendingAction.value) {
    const { id, action } = pendingAction.value
    try {
      await $api(`/apps/stock-transfers/${id}/${action}`, { 
        method: 'POST',
      })
      snackbar.show(`Mutasi berhasil di-${action}`, 'success')
      isTrackingDialogVisible.value = false
      fetchData()
    } catch (error) {
      console.error(error)
      const errorMsg = error.response?.data?.message || error.message || 'Gagal memproses';
      snackbar.show(`Gagal: ${errorMsg}`, 'error')
    }
  }
  pendingAction.value = null
}
</script>

<template>
  <div>
    <p class="text-2xl mb-6">
      Mutasi Stok
    </p>

    <!-- Card -->
    <VCard>
      <VTabs
        v-model="activeTab"
        class="px-4 border-b"
        @update:model-value="() => { page = 1; fetchData(); }"
      >
        <VTab value="all">Semua</VTab>
        <VTab value="need_approval">
          <span class="mr-2">Menunggu Persetujuan</span>
          <VBadge
            v-if="countNeedApproval > 0"
            color="warning"
            :content="countNeedApproval"
            inline
          />
        </VTab>
        <VTab value="approved">Disetujui</VTab>
        <VTab value="rejected">Ditolak</VTab>
      </VTabs>

      <!-- Card Header -->
      <VCardText class="d-flex flex-wrap align-center py-4 gap-4">
        <VTextField
          v-model="search"
          placeholder="Cari No Referensi..."
          density="compact"
          prepend-inner-icon="ri-search-line"
          style="width: 300px;"
          hide-details
          clearable
          @update:model-value="handleSearch"
        />
        
        <VSpacer />
        
        <div class="d-flex gap-2">
          <VBtn
            v-if="$can('create', 'Mutasi Stok')"
            color="primary"
            prepend-icon="ri-arrow-left-right-line"
            @click="isAddNewDrawerVisible = true"
          >
            Buat Mutasi Baru
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <!-- Data Table -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="tableHeaders"
        :items="filteredMutasi"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        @update:options="fetchData"
      >
        <template #item.reference_no="{ item }">
          <a
            href="#"
            class="font-weight-bold text-primary text-decoration-none"
            @click.prevent="openTrackingDialog(item)"
          >
            {{ item.reference_no }}
          </a>
        </template>
        
        <template #item.created_at="{ item }">
          <span>{{ new Date(item.created_at).toLocaleString('id-ID') }}</span>
        </template>
        
        <template #item.source_branch.name="{ item }">
          <div class="d-flex align-center gap-2">
            <VIcon icon="ri-store-2-line" size="small" class="text-error" />
            <span>{{ item.source_branch?.name || '-' }}</span>
          </div>
        </template>

        <template #item.destination_branch.name="{ item }">
          <div class="d-flex align-center gap-2">
            <VIcon icon="ri-store-3-line" size="small" class="text-success" />
            <span>{{ item.destination_branch?.name || '-' }}</span>
          </div>
        </template>

        <template #item.status="{ item }">
          <VChip
            :color="item.status === 'completed' || item.status === 'approved' ? 'success' : (item.status === 'pending' ? 'warning' : 'error')"
            size="small"
            variant="tonal"
          >
            {{ item.status.toUpperCase() }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <IconBtn
            size="small"
            color="primary"
            @click="openTrackingDialog(item)"
          >
            <VIcon icon="ri-eye-line" />
          </IconBtn>
        </template>
      </VDataTableServer>
    </VCard>

    <AddNewMutasiDrawer
      v-model:is-drawer-open="isAddNewDrawerVisible"
      :branches="branches"
      :master-products="masterProducts"
      @save-data="saveMutasi"
    />

    <!-- Tracking & Validation Dialog -->
    <VDialog
      v-model="isTrackingDialogVisible"
      max-width="800"
    >
      <VCard v-if="trackingMutasi">
        <VCardTitle class="d-flex justify-space-between align-center px-6 pt-6 pb-4">
          <span class="text-h5">Detail Mutasi: {{ trackingMutasi.reference_no }}</span>
          <VBtn
            icon
            variant="text"
            size="small"
            @click="isTrackingDialogVisible = false"
          >
            <VIcon icon="ri-close-line" />
          </VBtn>
        </VCardTitle>
        <VDivider />
        <VCardText class="px-6 py-6" style="max-height: 70vh; overflow-y: auto;">
          <!-- Info Cabang -->
          <VRow class="mb-6">
            <VCol cols="12" md="5">
              <VCard variant="outlined" class="pa-4 bg-error-lighten-5 border-error">
                <div class="text-caption text-error mb-1 font-weight-bold">DARI (SUMBER)</div>
                <div class="text-h6">{{ trackingMutasi.source_branch?.name }}</div>
              </VCard>
            </VCol>
            <VCol cols="12" md="2" class="d-flex align-center justify-center">
              <VIcon icon="ri-arrow-right-line" size="x-large" color="primary" />
            </VCol>
            <VCol cols="12" md="5">
              <VCard variant="outlined" class="pa-4 bg-success-lighten-5 border-success">
                <div class="text-caption text-success mb-1 font-weight-bold">KE (TUJUAN)</div>
                <div class="text-h6">{{ trackingMutasi.destination_branch?.name }}</div>
              </VCard>
            </VCol>
          </VRow>
          
          <div v-if="trackingMutasi.notes" class="mb-6">
            <div class="text-caption font-weight-bold mb-1">Catatan:</div>
            <p class="text-body-2">{{ trackingMutasi.notes }}</p>
          </div>

          <!-- Item List -->
          <div class="mb-6">
            <h6 class="text-h6 mb-3">Daftar Barang Mutasi</h6>
            <div v-if="trackingMutasi.items && trackingMutasi.items.length">
              <table class="w-100" style="border-collapse: collapse;">
                <thead>
                  <tr class="text-left border-b bg-grey-50">
                    <th class="py-2 px-3">Nama Barang</th>
                    <th class="py-2 px-3 text-right">Qty Mutasi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="i in trackingMutasi.items" :key="i.id" class="border-b">
                    <td class="py-2 px-3">{{ i.product?.name || 'Item' }}</td>
                    <td class="py-2 px-3 text-right font-weight-bold text-primary">{{ i.qty }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else>Tidak ada data barang.</div>
          </div>
        </VCardText>
        <VDivider />
        <VCardActions class="px-6 py-4 justify-end bg-grey-50">
          <div v-if="trackingMutasi.status === 'pending' && $can('approve', 'Mutasi Stok')" class="d-flex gap-2">
             <VBtn color="error" variant="tonal" @click="confirmUpdateStatus(trackingMutasi.id, 'reject')">
                Tolak Mutasi
             </VBtn>
             <VBtn color="success" variant="elevated" @click="confirmUpdateStatus(trackingMutasi.id, 'approve')">
                Setujui & Pindahkan Stok
             </VBtn>
          </div>
          <div v-else-if="trackingMutasi.status !== 'pending'" class="text-caption text-grey-600 font-italic">
            Mutasi ini telah diproses ({{ trackingMutasi.status }}).
          </div>
          <div v-else class="text-caption text-grey-600 font-italic">
            Menunggu persetujuan admin/manajer.
          </div>
        </VCardActions>
      </VCard>
    </VDialog>

    <SimpleConfirmDialog
      v-model:is-dialog-visible="isConfirmDialogVisible"
      :title="confirmTitle"
      :message="confirmMessage"
      :confirm-text="confirmActionText"
      :color="confirmColor"
      @confirm="onConfirmAction"
    />
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Mutasi Stok
</route>
