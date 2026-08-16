<script setup>
import { ref, onMounted, computed } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import ReceiveGoodsDrawer from './ReceiveGoodsDrawer.vue'
import DocumentActions from '@/components/DocumentActions.vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const pendingPOs = ref([])
const goodsReceipts = ref([])
const search = ref('')
const isLoading = ref(false)
const isDrawerVisible = ref(false)
const isDetailDialogVisible = ref(false)
const isPrintDialogVisible = ref(false)
const printUrl = ref('')
const detailDialogData = ref(null)
const selectedPO = ref(null)
const selectedGR = ref(null)

const isConfirmDeleteVisible = ref(false)
const grToDelete = ref(null)
const voidPin = ref('')

const activeTab = ref('pending')

// Pagination
const page = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)
let searchTimeout = null

const snackbar = useSnackbarStore()

import { useAbility } from '@casl/vue'

const ability = useAbility()

const isAllowedToEdit = computed(() => {
  return ability.can('write', 'Penerimaan Gudang') || ability.can('manage', 'all')
})

const fetchData = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
    }
    
    if (search.value) {
      params.search = search.value
    }
    
    if (activeTab.value === 'pending') {
      params.unreceived = 'true'
      const poData = await $api('/apps/purchase-orders', { query: params })
      pendingPOs.value = poData.data || poData
      if (poData.total !== undefined) {
        totalItems.value = poData.total
      }
    } else {
      const grData = await $api('/apps/goods-receipts', { query: params })
      goodsReceipts.value = grData.data || grData
      if (grData.total !== undefined) {
        totalItems.value = grData.total
      }
    }
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mengambil data', 'error')
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

const saveGoodsReceipt = async grData => {
  try {
    const isFormData = grData instanceof FormData
    const isEdit = isFormData ? grData.has('id') : grData.id
    const id = isFormData ? grData.get('id') : grData.id
    
    if (isEdit) {
      if (isFormData) {
        grData.append('_method', 'PUT')
      }
      await $api(`/apps/goods-receipts/${id}`, {
        method: isFormData ? 'POST' : 'PUT',
        body: grData,
      })
      snackbar.show('Revisi penerimaan barang berhasil disimpan dan stok disesuaikan!', 'success')
    } else {
      await $api('/apps/goods-receipts', {
        method: 'POST',
        body: grData,
      })
      snackbar.show('Barang berhasil diterima dan stok ditambahkan!', 'success')
    }
    fetchData()
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal memproses penerimaan barang', 'error')
  }
}

const tableHeadersPending = [
  { title: 'NO. PO', key: 'po_number' },
  { title: 'TANGGAL PO', key: 'date' },
  { title: 'CABANG TUJUAN', key: 'branch.name' },
  { title: 'SUPPLIER', key: 'supplier.name' },
]

const tableHeadersCompleted = [
  { title: 'NO. PENERIMAAN', key: 'receipt_number' },
  { title: 'NO. PO', key: 'purchase_order.po_number' },
  { title: 'TANGGAL TERIMA', key: 'date' },
  { title: 'CABANG TUJUAN', key: 'purchase_order.branch.name' },
  { title: 'STATUS', key: 'approval_status' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const activeHeaders = computed(() => {
  return activeTab.value === 'pending' ? tableHeadersPending : tableHeadersCompleted
})

const activeItems = computed(() => {
  return activeTab.value === 'pending' ? pendingPOs.value : goodsReceipts.value
})

const processPO = async poId => {
  try {
    const poDetail = await $api(`/apps/purchase-orders/${poId}`)

    selectedPO.value = poDetail
    selectedGR.value = null
    isDrawerVisible.value = true
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mengambil detail PO', 'error')
  }
}

const processEditGR = async gr => {
  try {
    const grDetail = await $api(`/apps/goods-receipts/${gr.id}`)

    selectedPO.value = grDetail.purchase_order
    selectedGR.value = grDetail
    isDrawerVisible.value = true
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mengambil detail Penerimaan', 'error')
  }
}

const isDownloading = ref(false)

const printGR = async id => {
  isDownloading.value = true
  try {
    const response = await $api(`/apps/documents/goods_receipt/${id}/pdf`, {
      responseType: 'blob',
    })
    
    // Create a blob URL from the response
    const blob = new Blob([response], { type: 'application/pdf' })

    printUrl.value = URL.createObjectURL(blob)
    
    // Open the popup modal
    isPrintDialogVisible.value = true
    snackbar.show('PDF berhasil dimuat', 'success')
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal memuat PDF', 'error')
  } finally {
    isDownloading.value = false
  }
}

const closePrintDialog = () => {
  isPrintDialogVisible.value = false
  if (printUrl.value) {
    URL.revokeObjectURL(printUrl.value)
    printUrl.value = ''
  }
}

const openDetailDialog = item => {
  detailDialogData.value = item
  isDetailDialogVisible.value = true
}

const handleActionFromDialog = () => {
  isDetailDialogVisible.value = false
  if (activeTab.value === 'pending') {
    processPO(detailDialogData.value.id)
  }
}

const confirmDeleteGR = item => {
  grToDelete.value = item
  isConfirmDeleteVisible.value = true
}

const executeDeleteGR = async () => {
  if (!grToDelete.value) return
  
  isLoading.value = true
  try {
    await $api(`/apps/goods-receipts/${grToDelete.value.id}`, {
      method: 'DELETE',
    })

    snackbar.show('Penerimaan Gudang berhasil dibatalkan, stok telah ditarik kembali.', 'success')
    isConfirmDeleteVisible.value = false
    fetchData()
  } catch (error) {
    console.error(error)

    const errorMsg = error.response?._data?.message || 'Gagal menghapus penerimaan gudang.'

    snackbar.show(errorMsg, 'error')
  } finally {
    isLoading.value = false
    grToDelete.value = null
  }
}
</script>

<template>
  <section>
    <!-- Page Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="text-h4 mb-0">
          Penerimaan Barang (Gudang)
        </h2>
      </div>
    </div>

    <VCard>
      <VTabs
        v-model="activeTab"
        class="px-4 border-b"
        @update:model-value="() => { page = 1; fetchData(); }"
      >
        <VTab value="pending">
          <span class="mr-2">Menunggu Penerimaan</span>
        </VTab>
        <VTab value="completed">
          Riwayat Selesai
        </VTab>
      </VTabs>

      <VCardItem class="pa-4 pb-0">
        <div class="d-flex align-center justify-space-between w-100">
          <VCardTitle class="px-0">
            Daftar Penerimaan
          </VCardTitle>
          <div style="width: 250px;">
            <VTextField
              v-model="search"
              :placeholder="activeTab === 'pending' ? 'Cari No PO atau Supplier...' : 'Cari No Penerimaan atau PO...'"
              prepend-inner-icon="ri-search-line"
              density="compact"
              hide-details
              variant="outlined"
              clearable
              @update:model-value="handleSearch"
            />
          </div>
        </div>
      </VCardItem>

      <VDataTableServer
        :key="activeTab"
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="activeHeaders"
        :items="activeItems"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        @update:options="fetchData"
      >
        <!-- NO. PO for Pending -->
        <template
          v-if="activeTab === 'pending'"
          #item.po_number="{ item }"
        >
          <a
            href="#"
            class="font-weight-bold text-primary text-decoration-none"
            @click.prevent="openDetailDialog(item)"
          >
            {{ item.po_number }}
          </a>
        </template>

        <!-- NO. PENERIMAAN for Completed -->
        <template
          v-if="activeTab === 'completed'"
          #item.receipt_number="{ item }"
        >
          <a
            href="#"
            class="font-weight-bold text-success text-decoration-none"
            @click.prevent="openDetailDialog(item)"
          >
            {{ item.receipt_number }}
          </a>
        </template>
        
        <!-- Format Date for Completed -->
        <template
          v-if="activeTab === 'completed'"
          #item.date="{ item }"
        >
          {{ item.date ? item.date.substring(0, 10) : '-' }}
        </template>
        
        <!-- Status for Completed -->
        <template
          v-if="activeTab === 'completed'"
          #item.approval_status="{ item }"
        >
          <VChip
            :color="
              item.approval_status === 'approved' ? 'success' :
              item.approval_status === 'rejected' ? 'error' :
              item.approval_status === 'pending' ? 'warning' : 'default'
            "
            size="small"
            class="text-uppercase"
          >
            {{ item.approval_status || 'draft' }}
          </VChip>
        </template>
        
        <template
          v-if="activeTab === 'completed'"
          #item.actions="{ item }"
        >
          <VBtn
            v-if="$can('delete', 'Penerimaan Gudang')"
            icon="ri-delete-bin-line"
            color="error"
            size="small"
            variant="text"
            @click="confirmDeleteGR(item)"
          />
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Detail Dialog (Popup) -->
    <VDialog
      v-model="isDetailDialogVisible"
      max-width="700"
    >
      <VCard v-if="detailDialogData">
        <VCardTitle class="d-flex justify-space-between align-center px-6 py-4 border-b">
          <span class="text-h6">
            Detail {{ activeTab === 'pending' ? `PO: ${detailDialogData.po_number}` : `Penerimaan: ${detailDialogData.receipt_number}` }}
          </span>
          <VBtn
            icon="ri-close-line"
            variant="text"
            size="small"
            @click="isDetailDialogVisible = false"
          />
        </VCardTitle>
        <VCardText class="pa-6">
          <!-- Content for PENDING -->
          <div v-if="activeTab === 'pending'">
            <div class="text-subtitle-1 font-weight-medium mb-4">
              Barang yang akan diterima dari Supplier: <span class="font-weight-bold text-primary">{{ detailDialogData.supplier?.name }}</span>
            </div>
            <div v-if="detailDialogData.items && detailDialogData.items.length">
              <table
                class="w-100"
                style="border-collapse: collapse;"
              >
                <thead>
                  <tr class="text-left border-b bg-grey-50">
                    <th class="pa-3 text-sm">
                      Barang
                    </th>
                    <th class="pa-3 text-sm text-right">
                      Qty Dipesan
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="i in detailDialogData.items"
                    :key="i.id"
                    class="border-b"
                  >
                    <td class="pa-3 text-sm">
                      {{ i.product?.name || i.product_branch?.product?.name || 'Item' }}
                    </td>
                    <td class="pa-3 text-sm text-right font-weight-bold">
                      {{ i.qty }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div
              v-else
              class="text-sm"
            >
              Tidak ada data barang.
            </div>
          </div>

          <!-- Content for COMPLETED -->
          <div v-else>
            <div class="text-subtitle-1 font-weight-medium mb-4">
              Barang yang telah diterima:
            </div>
            <div v-if="detailDialogData.items && detailDialogData.items.length">
              <table
                class="w-100"
                style="border-collapse: collapse;"
              >
                <thead>
                  <tr class="text-left border-b bg-grey-50">
                    <th class="pa-3 text-sm">
                      Barang
                    </th>
                    <th class="pa-3 text-sm text-right">
                      Qty Diterima
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="i in detailDialogData.items"
                    :key="i.id"
                    class="border-b"
                  >
                    <td class="pa-3 text-sm">
                      {{ i.product_branch?.product?.name || 'Item' }}
                    </td>
                    <td class="pa-3 text-sm text-right font-weight-bold text-success">
                      {{ i.qty_received }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div
              v-else
              class="text-sm text-disabled"
            >
              Tidak ada rincian barang
            </div>
            
            <!-- Foto Bukti -->
            <div
              v-if="detailDialogData.photos && detailDialogData.photos.length > 0"
              class="mt-6"
            >
              <div class="text-subtitle-2 font-weight-bold mb-2">
                Lampiran Bukti Penerimaan:
              </div>
              <div class="d-flex flex-wrap gap-2">
                <a 
                  v-for="(photo, index) in detailDialogData.photos" 
                  :key="index"
                  :href="'/storage/' + photo"
                  target="_blank"
                  class="d-inline-block border rounded overflow-hidden"
                  style="width: 100px; height: 100px;"
                >
                  <img
                    :src="'/storage/' + photo"
                    alt="Bukti Foto"
                    style="width: 100%; height: 100%; object-fit: cover;"
                  >
                </a>
              </div>
            </div>

            <div
              v-if="detailDialogData.notes"
              class="mt-6 pa-4 bg-grey-50 rounded text-sm border"
            >
              <span class="font-weight-bold">Catatan Penerimaan:</span><br> {{ detailDialogData.notes }}
            </div>
            
            <!-- Timeline -->
            <div class="mt-8">
              <h6 class="text-subtitle-1 font-weight-medium mb-6">
                Lacak Status Dokumen
              </h6>
              
              <div class="d-flex align-start justify-space-between position-relative mt-2">
                <!-- Line behind -->
                <div
                  class="position-absolute bg-grey-300"
                  style="height: 2px; top: 12px; left: 15%; right: 15%; z-index: 0;"
                />
                
                <!-- Created -->
                <div
                  class="d-flex flex-column align-center position-relative text-center"
                  style="z-index: 1; flex: 1;"
                >
                  <VAvatar
                    size="26"
                    color="primary"
                    class="mb-2 ring-2 ring-white"
                  >
                    <VIcon
                      icon="ri-file-add-line"
                      size="14"
                      color="white"
                    />
                  </VAvatar>
                  <div class="text-caption font-weight-bold mt-1">
                    Dibuat
                  </div>
                  <div class="text-caption text-grey-600">
                    {{ detailDialogData.created_at ? new Date(detailDialogData.created_at).toLocaleString('id-ID') : '-' }}
                  </div>
                </div>

                <!-- Validated -->
                <div
                  class="d-flex flex-column align-center position-relative text-center"
                  style="z-index: 1; flex: 1;"
                >
                  <VAvatar
                    size="26"
                    :color="detailDialogData.validated_by ? 'info' : 'grey-300'"
                    class="mb-2 ring-2 ring-white"
                  >
                    <VIcon
                      icon="ri-check-double-line"
                      size="14"
                      color="white"
                    />
                  </VAvatar>
                  <div class="text-caption font-weight-bold mt-1">
                    Divalidasi
                  </div>
                  <div
                    v-if="detailDialogData.validated_by"
                    class="text-caption text-grey-600"
                  >
                    {{ detailDialogData.validated_at ? new Date(detailDialogData.validated_at).toLocaleString('id-ID') : '-' }}
                  </div>
                  <div
                    v-else
                    class="text-caption text-grey-400"
                  >
                    Menunggu
                  </div>
                </div>

                <!-- Approved or Rejected -->
                <div
                  class="d-flex flex-column align-center position-relative text-center"
                  style="z-index: 1; flex: 1;"
                >
                  <VAvatar
                    size="26"
                    :color="detailDialogData.approval_status === 'rejected' ? 'error' : (detailDialogData.approved_by ? 'success' : 'grey-300')"
                    class="mb-2 ring-2 ring-white"
                  >
                    <VIcon
                      :icon="detailDialogData.approval_status === 'rejected' ? 'ri-close-circle-line' : 'ri-checkbox-circle-line'"
                      size="16"
                      color="white"
                    />
                  </VAvatar>
                  <div
                    class="text-caption font-weight-bold mt-1"
                    :class="detailDialogData.approval_status === 'rejected' ? 'text-error' : ''"
                  >
                    {{ detailDialogData.approval_status === 'rejected' ? 'Ditolak' : 'Disetujui' }}
                  </div>
                  <div
                    v-if="detailDialogData.approval_status === 'rejected' || detailDialogData.approved_by"
                    class="text-caption text-grey-600"
                    :class="detailDialogData.approval_status === 'rejected' ? 'text-error' : ''"
                  >
                    <span v-if="detailDialogData.approval_status === 'rejected'">{{ detailDialogData.rejection_reason || 'Tanpa alasan' }}</span>
                    <span v-else>{{ detailDialogData.approved_at ? new Date(detailDialogData.approved_at).toLocaleString('id-ID') : '-' }}</span>
                  </div>
                  <div
                    v-else
                    class="text-caption text-grey-400"
                  >
                    Menunggu
                  </div>
                </div>
              </div>
            </div>
          </div>
        </VCardText>
        
        <VCardActions class="px-6 py-4 border-t d-flex justify-end gap-3 bg-grey-50">
          <VBtn
            color="secondary"
            variant="outlined"
            @click="isDetailDialogVisible = false"
          >
            Tutup
          </VBtn>
          <VBtn
            v-if="activeTab === 'pending'"
            color="primary"
            @click="handleActionFromDialog"
          >
            Terima Fisik Barang
          </VBtn>
          <template v-if="activeTab === 'completed'">
            <VBtn
              v-if="isAllowedToEdit && (detailDialogData.approval_status === 'draft' || detailDialogData.approval_status === 'rejected')"
              color="warning"
              prepend-icon="ri-edit-line"
              @click="isDetailDialogVisible = false; processEditGR(detailDialogData)"
            >
              Edit Penerimaan
            </VBtn>
            
            <DocumentActions
              document-type="goods_receipt"
              :document-id="detailDialogData.id"
              :document-status="detailDialogData.status"
              :approval-status="detailDialogData.approval_status || 'draft'"
              @status-updated="fetchData(); isDetailDialogVisible = false"
            />
          </template>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Print Preview Dialog -->
    <VDialog
      v-model="isPrintDialogVisible"
      max-width="900"
      @update:model-value="(val) => { if(!val) closePrintDialog() }"
    >
      <VCard>
        <VCardTitle class="d-flex justify-space-between align-center px-6 pt-6 pb-4">
          <span>Pratinjau PDF</span>
          <div class="d-flex gap-2">
            <VBtn
              icon
              variant="text"
              size="small"
              @click="closePrintDialog"
            >
              <VIcon icon="ri-close-line" />
            </VBtn>
          </div>
        </VCardTitle>
        
        <VCardText class="px-6 pb-6 pt-0">
          <iframe
            v-if="printUrl"
            :src="printUrl"
            width="100%"
            height="600px"
            style="border: none; border-radius: 8px;"
          />
        </VCardText>
      </VCard>
    </VDialog>

    <ReceiveGoodsDrawer
      v-if="selectedPO"
      v-model:is-drawer-open="isDrawerVisible"
      :selected-po="selectedPO"
      :selected-gr="selectedGR"
      @save-data="saveGoodsReceipt"
    />

    <!-- Confirm Delete Dialog -->
    <VDialog
      v-model="isConfirmDeleteVisible"
      max-width="500"
    >
      <VCard>
        <VCardTitle class="text-error bg-error-lighten-4 pa-4">
          Konfirmasi Hapus Dokumen
        </VCardTitle>
        <VCardText class="pa-6">
          <p class="text-body-1">
            Apakah Anda yakin ingin menghapus Penerimaan Gudang <strong>{{ grToDelete?.receipt_number }}</strong>?
          </p>
          <VAlert
            type="warning"
            variant="tonal"
            class="mt-4 text-caption mb-4"
          >
            Jika dokumen ini sudah diverifikasi, menghapusnya akan <strong>menarik kembali stok barang</strong> yang sudah masuk ke gudang. Status PO akan dikembalikan menjadi 'pending'. Tindakan ini tidak bisa dikembalikan.
          </VAlert>
        </VCardText>
        <VCardActions class="pa-4 pt-0 justify-end">
          <VBtn
            color="secondary"
            variant="outlined"
            @click="isConfirmDeleteVisible = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="error"
            prepend-icon="ri-delete-bin-line"
            @click="executeDeleteGR"
          >
            Ya, Hapus Dokumen
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </section>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Penerimaan Gudang
</route>
