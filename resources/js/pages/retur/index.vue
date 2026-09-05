<script setup>
import { ref, onMounted, computed } from 'vue'
import { paginationMeta } from '@/utils/paginationMeta'
import { useSnackbarStore } from '@/stores/snackbar'
import CreateReturnDrawer from './CreateReturnDrawer.vue'
import ApprovalDialog from '../pos/ApprovalDialog.vue'

const returns = ref([])
const branches = ref([])
const isLoading = ref(false)
const snackbar = useSnackbarStore()

const isCreateDrawerVisible = ref(false)
const isApprovalDialogVisible = ref(false)
const isDetailDialogVisible = ref(false)
const selectedReturn = ref(null)
const returnToApprove = ref(null)
const activeTab = ref('all') // all, purchase, sale
const search = ref('')
const selectedBranch = ref(null)

// Pagination
const page = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)
let searchTimeout = null

const tableHeaders = [
  { title: 'NO. RETUR', key: 'return_number' },
  { title: 'TIPE RETUR', key: 'reference_type' },
  { title: 'CABANG', key: 'branch.name' },
  { title: 'KOMPENSASI', key: 'return_type' },
  { title: 'TOTAL NILAI', key: 'total_amount' },
  { title: 'STATUS', key: 'status' },
  { title: 'TANGGAL', key: 'created_at' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const formatRupiah = value => {
  if (!value || isNaN(value)) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)
}

const formatDate = dateString => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const fetchData = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      reference_type_filter: activeTab.value,
    }
    
    if (search.value) {
      params.search = search.value
    }
    if (selectedBranch.value) {
      params.branch_id = selectedBranch.value
    }
    
    const [returnsData, branchData] = await Promise.all([
      $api('/apps/returns', { query: params }),
      $api('/apps/branches'),
    ])

    returns.value = returnsData.data || returnsData
    if (returnsData.total !== undefined) {
      totalItems.value = returnsData.total
    }
    branches.value = branchData.data || branchData
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mengambil data retur', 'error')
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

const handleCreateReturn = async returnData => {
  try {
    const res = await $api('/apps/returns', {
      method: 'POST',
      body: returnData,
    })
    snackbar.show(res.message || 'Draft Retur berhasil dibuat', 'success')
    isCreateDrawerVisible.value = false
    fetchData()
  } catch (error) {
    console.error(error)
    const err = error.response?._data?.error || error.response?._data?.message || 'Terjadi kesalahan saat membuat retur'
    snackbar.show(err, 'error')
  }
}

const openDetail = async item => {
  try {
    const res = await $api(`/apps/returns/${item.id}`)
    selectedReturn.value = res.data || res
  } catch (e) {
    selectedReturn.value = item
  }
  isDetailDialogVisible.value = true
}

const promptApprove = item => {
  returnToApprove.value = item
  isApprovalDialogVisible.value = true
}

const handleApprovalSuccess = async approverId => {
  if (!returnToApprove.value) return
  isApprovalDialogVisible.value = false

  try {
    const res = await $api(`/apps/returns/${returnToApprove.value.id}/approve`, {
      method: 'POST',
      body: { approver_id: approverId },
    })
    snackbar.show(res.message || 'Retur berhasil disetujui (Approved) dengan otorisasi PIN!', 'success')
    isDetailDialogVisible.value = false
    fetchData()
  } catch (error) {
    console.error(error)
    const err = error.response?._data?.error || error.response?._data?.message || error.message || 'Gagal memproses approval retur'
    snackbar.show(err, 'error')
  } finally {
    returnToApprove.value = null
  }
}

const handleApprovalCancel = () => {
  isApprovalDialogVisible.value = false
  returnToApprove.value = null
}

const isReceivingReplacement = ref(false)
const isReceiveReplacementDialogVisible = ref(false)
const returnToReceiveReplacement = ref(null)

const openReceiveReplacementDialog = returnTx => {
  returnToReceiveReplacement.value = returnTx
  isReceiveReplacementDialogVisible.value = true
}

const executeReceiveReplacement = async () => {
  if (!returnToReceiveReplacement.value) return

  isReceivingReplacement.value = true
  try {
    const res = await $api(`/apps/returns/${returnToReceiveReplacement.value.id}/receive-replacement`, {
      method: 'POST',
    })
    snackbar.show(res.message || 'Barang pengganti berhasil diterima fisik & stok telah bertambah!', 'success')
    isReceiveReplacementDialogVisible.value = false
    isDetailDialogVisible.value = false
    fetchData()
  } catch (error) {
    console.error(error)
    const err = error.response?._data?.message || 'Gagal memproses penerimaan barang pengganti'
    snackbar.show(err, 'error')
  } finally {
    isReceivingReplacement.value = false
  }
}
</script>

<template>
  <section class="pa-4">
    <!-- Page Header -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h2 class="text-h4 font-weight-bold mb-1">
          Manajemen Retur Barang
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Kelola Retur Penjualan (Customer &rarr; Masuk Stok Toko) dan Retur Pembelian (Supplier &rarr; Keluar Stok Toko).
        </p>
      </div>
      
      <div class="d-flex gap-4">
        <VBtn
          v-if="$can('create', 'Retur Barang')"
          prepend-icon="ri-arrow-go-back-line"
          color="primary"
          @click="isCreateDrawerVisible = true"
        >
          Buat Pengajuan Retur
        </VBtn>
      </div>
    </div>

    <!-- Main Card with Tabs -->
    <VCard elevation="2">
      <!-- Tabs Navigation -->
      <VTabs
        v-model="activeTab"
        class="px-4 border-b"
        @update:model-value="() => { page = 1; fetchData(); }"
      >
        <VTab value="all">
          <VIcon icon="ri-list-check" class="me-2" />
          Semua Retur
        </VTab>
        <VTab value="sale">
          <VIcon icon="ri-user-shared-line" class="me-2 text-info" />
          Retur Penjualan (Dari Pelanggan)
        </VTab>
        <VTab value="purchase">
          <VIcon icon="ri-truck-line" class="me-2 text-warning" />
          Retur Pembelian (Ke Supplier)
        </VTab>
      </VTabs>

      <!-- Filter Bar -->
      <VCardText class="d-flex flex-wrap align-center py-4 gap-4">
        <VTextField
          v-model="search"
          prepend-inner-icon="ri-search-line"
          placeholder="Cari No Retur / User..."
          density="compact"
          hide-details
          style="max-width: 300px;"
          clearable
          @update:model-value="handleSearch"
        />

        <VAutocomplete
          v-model="selectedBranch"
          :items="branches"
          item-title="name"
          item-value="id"
          placeholder="Semua Cabang"
          density="compact"
          style="max-width: 240px;"
          hide-details
          clearable
          @update:model-value="() => { page = 1; fetchData(); }"
        />

        <VSpacer />

        <VBtn
          variant="tonal"
          color="secondary"
          size="small"
          prepend-icon="ri-refresh-line"
          :loading="isLoading"
          @click="fetchData"
        >
          Muat Ulang
        </VBtn>
      </VCardText>

      <VDivider />

      <!-- Data Table -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="tableHeaders"
        :items="returns"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        hover
        @update:options="fetchData"
      >
        <!-- Return Number -->
        <template #item.return_number="{ item }">
          <a
            href="#"
            class="font-weight-bold text-primary text-decoration-none"
            @click.prevent="openDetail(item)"
          >
            {{ item.return_number }}
          </a>
        </template>

        <!-- Reference Type -->
        <template #item.reference_type="{ item }">
          <div class="d-flex align-center gap-2">
            <VIcon
              :icon="item.reference_type === 'purchase' ? 'ri-truck-line' : 'ri-shopping-cart-2-line'"
              size="16"
              :color="item.reference_type === 'purchase' ? 'warning' : 'info'"
            />
            <div>
              <span class="font-weight-medium">
                {{ item.reference_type === 'purchase' ? 'Retur Pembelian' : 'Retur Penjualan' }}
              </span>
              <div class="text-caption text-disabled">
                {{ item.reference_type === 'purchase' ? (item.purchase_order?.po_number || 'Ref ID: ' + item.reference_id) : (item.sale?.invoice_number || 'Ref ID: ' + item.reference_id) }}
              </div>
            </div>
          </div>
        </template>

        <!-- Branch -->
        <template #item.branch.name="{ item }">
          <span>{{ item.branch?.name || '-' }}</span>
        </template>

        <!-- Compensation / Return Type -->
        <template #item.return_type="{ item }">
          <VChip
            v-if="item.return_type === 'potong_hutang'"
            size="small"
            color="success"
            variant="flat"
            class="font-weight-bold"
          >
            <VIcon icon="ri-money-dollar-circle-line" size="14" class="me-1" />
            Potong Hutang Supplier
          </VChip>
          <VChip
            v-else-if="item.return_type === 'tukar_barang'"
            size="small"
            color="info"
            variant="tonal"
            class="font-weight-medium"
          >
            <VIcon icon="ri-exchange-box-line" size="14" class="me-1" />
            Tukar Barang
          </VChip>
          <VChip
            v-else
            size="small"
            color="warning"
            variant="tonal"
            class="font-weight-medium"
          >
            <VIcon icon="ri-refund-line" size="14" class="me-1" />
            Refund Uang
          </VChip>
        </template>

        <!-- Total Amount -->
        <template #item.total_amount="{ item }">
          <span class="font-weight-bold font-mono text-primary">{{ formatRupiah(item.total_amount) }}</span>
        </template>

        <!-- Status -->
        <template #item.status="{ item }">
          <VChip
            size="small"
            :color="item.status === 'completed' ? 'success' : (item.status === 'rejected' ? 'error' : 'warning')"
            variant="elevated"
            class="font-weight-medium"
          >
            {{ item.status === 'completed' ? 'Disetujui' : (item.status === 'rejected' ? 'Ditolak' : 'Menunggu Approval') }}
          </VChip>
        </template>

        <!-- Created At -->
        <template #item.created_at="{ item }">
          <span>{{ formatDate(item.created_at) }}</span>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1 justify-center align-center flex-wrap">
            <VBtn
              size="small"
              variant="tonal"
              color="secondary"
              prepend-icon="ri-eye-line"
              @click="openDetail(item)"
            >
              Detail
            </VBtn>

            <VBtn
              v-if="item.status === 'pending'"
              size="small"
              color="primary"
              variant="elevated"
              prepend-icon="ri-check-line"
              @click="promptApprove(item)"
            >
              Approve
            </VBtn>

            <VBtn
              v-if="item.reference_type === 'purchase' && item.return_type === 'tukar_barang' && item.status === 'completed' && !item.notes?.includes('telah diterima fisik')"
              size="small"
              color="success"
              variant="tonal"
              prepend-icon="ri-inbox-archive-line"
              :loading="isReceivingReplacement"
              @click="receiveReplacementGoods(item)"
            >
              Terima Pengganti
            </VBtn>
          </div>
        </template>

        <template #no-data>
          <div class="pa-4 text-center text-medium-emphasis">
            Belum ada transaksi retur.
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
                :items="[10, 20, 25, 50, 100]"
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

    <!-- Create Drawer -->
    <CreateReturnDrawer
      v-model:is-drawer-open="isCreateDrawerVisible"
      :branches="branches"
      @return-data="handleCreateReturn"
    />

    <!-- Detail Dialog -->
    <VDialog
      v-model="isDetailDialogVisible"
      max-width="750"
    >
      <VCard v-if="selectedReturn">
        <VCardTitle class="d-flex justify-space-between align-center px-6 pt-6 pb-3 bg-light">
          <div>
            <div class="d-flex align-center gap-2 mb-1">
              <span class="text-h5 font-weight-bold">Retur: {{ selectedReturn.return_number }}</span>
              <VChip
                :color="selectedReturn.status === 'completed' ? 'success' : (selectedReturn.status === 'rejected' ? 'error' : 'warning')"
                size="small"
              >
                {{ selectedReturn.status === 'completed' ? 'Disetujui' : (selectedReturn.status === 'rejected' ? 'Ditolak' : 'Menunggu Approval') }}
              </VChip>
            </div>
            <span class="text-caption text-medium-emphasis">
              Dibuat oleh: {{ selectedReturn.user?.name || '-' }} pada {{ formatDate(selectedReturn.created_at) }}
            </span>
          </div>

          <VBtn
            icon
            variant="text"
            size="small"
            @click="isDetailDialogVisible = false"
          >
            <VIcon icon="ri-close-line" />
          </VBtn>
        </VCardTitle>

        <VDivider />

        <VCardText class="px-6 py-4">
          <!-- Notification for Potong Hutang / Saldo Kredit -->
          <VAlert
            v-if="selectedReturn.return_type === 'potong_hutang' && selectedReturn.status === 'completed'"
            type="success"
            variant="tonal"
            density="compact"
            class="mb-4 text-caption"
            icon="ri-wallet-3-line"
          >
            <strong>Saldo Kredit Otomatis:</strong> Nilai retur sebesar <strong>{{ formatRupiah(selectedReturn.total_amount) }}</strong> telah diterbitkan sebagai saldo kredit supplier dan otomatis dapat memotong pembayaran tagihan PO supplier berikutnya.
          </VAlert>

          <!-- Information Rows -->
          <VRow class="mb-4">
            <VCol cols="12" md="6">
              <div class="text-caption text-medium-emphasis">Jenis Retur:</div>
              <div class="font-weight-medium">
                {{ selectedReturn.reference_type === 'purchase' ? 'Retur Pembelian (Ke Supplier)' : 'Retur Penjualan (Dari Pelanggan)' }}
              </div>
            </VCol>

            <VCol cols="12" md="6">
              <div class="text-caption text-medium-emphasis">Kompensasi:</div>
              <div class="font-weight-medium">
                {{ selectedReturn.return_type === 'potong_hutang' ? 'Pengembalian Dana / Potong Hutang Berjalan' : (selectedReturn.return_type === 'tukar_barang' ? 'Tukar Barang Sejenis' : 'Pengembalian Uang (Refund)') }}
              </div>
            </VCol>

            <VCol cols="12" md="6">
              <div class="text-caption text-medium-emphasis">Cabang:</div>
              <div class="font-weight-medium">{{ selectedReturn.branch?.name || '-' }}</div>
            </VCol>

            <VCol cols="12" md="6">
              <div class="text-caption text-medium-emphasis">Disetujui Oleh:</div>
              <div class="font-weight-medium">{{ selectedReturn.approver?.name || '-' }}</div>
            </VCol>

            <VCol v-if="selectedReturn.notes" cols="12">
              <div class="text-caption text-medium-emphasis">Catatan:</div>
              <div class="text-body-2 bg-grey-50 pa-2 rounded border">{{ selectedReturn.notes }}</div>
            </VCol>
          </VRow>

          <!-- Items Table -->
          <h6 class="text-subtitle-1 font-weight-bold mb-3 d-flex align-center gap-1">
            <VIcon icon="ri-box-3-line" size="18" color="primary" />
            Daftar Barang yang Diretur
          </h6>

          <div class="border rounded overflow-hidden mb-4">
            <VTable density="compact">
              <thead>
                <tr class="bg-grey-100">
                  <th class="font-weight-bold">Nama Barang</th>
                  <th class="text-center font-weight-bold">Qty Diretur</th>
                  <th class="text-right font-weight-bold">Harga Satuan</th>
                  <th class="text-right font-weight-bold">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in (selectedReturn.items || [])" :key="item.id">
                  <td>
                    <div class="font-weight-medium">
                      {{ item.product_branch?.product?.name || item.productBranch?.product?.name || 'Produk' }}
                    </div>
                    <div class="text-caption text-disabled">
                      <code>{{ item.product_branch?.product?.sku || item.productBranch?.product?.sku || '-' }}</code>
                    </div>
                  </td>
                  <td class="text-center font-weight-bold text-primary">{{ item.qty }}</td>
                  <td class="text-right">{{ formatRupiah(item.unit_price) }}</td>
                  <td class="text-right font-weight-bold">{{ formatRupiah(item.subtotal || (item.qty * item.unit_price)) }}</td>
                </tr>
              </tbody>
            </VTable>
          </div>

          <!-- Total Summary -->
          <div class="d-flex justify-end">
            <div class="text-right">
              <span class="text-subtitle-2 text-medium-emphasis me-3">Total Nilai Retur:</span>
              <span class="text-h6 font-weight-bold text-primary">{{ formatRupiah(selectedReturn.total_amount) }}</span>
            </div>
          </div>
        </VCardText>

        <VDivider />

        <VCardActions class="px-6 py-4 justify-space-between bg-grey-50 flex-wrap gap-2">
          <VBtn
            variant="outlined"
            color="secondary"
            @click="isDetailDialogVisible = false"
          >
            Tutup
          </VBtn>

          <div class="d-flex gap-2">
            <VBtn
              v-if="selectedReturn.reference_type === 'purchase' && selectedReturn.return_type === 'tukar_barang' && selectedReturn.status === 'completed' && !selectedReturn.notes?.includes('telah diterima fisik')"
              color="success"
              variant="flat"
              prepend-icon="ri-inbox-archive-line"
              :loading="isReceivingReplacement"
              @click="openReceiveReplacementDialog(selectedReturn)"
            >
              Terima Barang Pengganti
            </VBtn>

            <VBtn
              v-if="selectedReturn.status === 'pending'"
              color="primary"
              variant="elevated"
              prepend-icon="ri-check-line"
              @click="promptApprove(selectedReturn)"
            >
              Setujui Retur (Approve)
            </VBtn>
          </div>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Dialog Konfirmasi Terima Barang Pengganti Retur -->
    <VDialog
      v-model="isReceiveReplacementDialogVisible"
      max-width="520"
    >
      <VCard class="rounded-xl overflow-hidden shadow-lg">
        <VCardTitle class="pa-5 pb-3 font-weight-bold text-h6 text-success d-flex align-center gap-2 bg-success-subtle border-b">
          <VIcon icon="ri-inbox-archive-line" color="success" size="24" />
          Konfirmasi Penerimaan Barang Pengganti
        </VCardTitle>
        <VCardText class="pa-5 pt-4">
          <p class="text-body-1 mb-2">
            Catat penerimaan barang pengganti fisik dari supplier untuk Retur <strong>#{{ returnToReceiveReplacement?.return_number }}</strong>?
          </p>
          <VAlert
            type="success"
            variant="tonal"
            class="text-caption mt-3 mb-0 rounded-lg"
            icon="ri-information-line"
          >
            Stok fisik cabang dan nomor batch baru akan otomatis bertambah sesuai dengan item retur pengganti.
          </VAlert>
        </VCardText>
        <VCardActions class="pa-5 pt-0 justify-end gap-2 border-t bg-grey-50">
          <VBtn
            variant="outlined"
            color="secondary"
            @click="isReceiveReplacementDialogVisible = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="success"
            variant="flat"
            prepend-icon="ri-inbox-archive-line"
            class="font-weight-bold"
            :loading="isReceivingReplacement"
            @click="executeReceiveReplacement"
          >
            Ya, Terima Barang Pengganti
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Dialog Otorisasi PIN Persetujuan Retur -->
    <ApprovalDialog
      v-model:is-dialog-visible="isApprovalDialogVisible"
      title="Otorisasi Persetujuan Retur Barang"
      :description="`Transaksi Retur #${returnToApprove?.return_number || ''} memerlukan persetujuan dan verifikasi PIN dari Kepala Cabang / Supervisor.`"
      @success="handleApprovalSuccess"
      @cancel="handleApprovalCancel"
    />
  </section>
</template>

<style lang="scss">
.per-page-select {
  inline-size: 5.5rem;
}
</style>

<route lang="yaml">
meta:
  action: read
  subject: Retur Barang
</route>
