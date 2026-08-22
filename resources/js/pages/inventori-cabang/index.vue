<script setup>
import { ref, onMounted, computed } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import AddNewProductBranchDrawer from './AddNewProductBranchDrawer.vue'
import StockInboundDrawer from './StockInboundDrawer.vue'
import ManageBatchesDialog from './ManageBatchesDialog.vue'
import PrintLabelDialog from './PrintLabelDialog.vue'

const productBranches = ref([])
const masterProducts = ref([])
const branches = ref([])
const categories = ref([])
const search = ref('')
const selectedBranch = ref(null)
const selectedCategory = ref(null)
const isLoading = ref(false)
const isAddNewDrawerVisible = ref(false)
const isStockDrawerVisible = ref(false)
const isPrintLabelDialogVisible = ref(false)
const isManageBatchesDialogVisible = ref(false)
const selectedProductBranch = ref(null)
const fileInput = ref(null)

// Pagination
const page = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)
let searchTimeout = null

// For formatting
const formatRupiah = value => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value)
}

const snackbar = useSnackbarStore()

const extractArray = val => {
  if (Array.isArray(val)) return val
  if (val && Array.isArray(val.data)) return val.data
  return []
}

const loadInitialOptions = async () => {
  try {
    const [bData, mData] = await Promise.all([
      $api('/apps/branches'),
      $api('/apps/products', { query: { itemsPerPage: 100 } }).catch(() => ({ data: [] })),
    ])
    branches.value = extractArray(bData)
    masterProducts.value = extractArray(mData)
  } catch (e) {
    console.error('Failed to load initial options:', e)
  }
}

const fetchData = async options => {
  if (options && typeof options === 'object') {
    if (options.page) page.value = options.page
    if (options.itemsPerPage) itemsPerPage.value = options.itemsPerPage
  }
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
    }
    
    if (search.value) {
      params.search = search.value
    }
    if (selectedBranch.value) {
      params.branch_id = selectedBranch.value
    }
    
    const pbData = await $api('/apps/product-branches', { query: params })

    productBranches.value = extractArray(pbData)
    totalItems.value = pbData?.total ?? (Array.isArray(productBranches.value) ? productBranches.value.length : 0)
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mengambil data inventori', 'error')
    productBranches.value = []
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
  loadInitialOptions()
  fetchData()
})

const saveProductBranch = async data => {
  try {
    if (data.id) {
      await $api(`/apps/product-branches/${data.id}`, {
        method: 'PUT',
        body: data,
      })
      snackbar.show('Data harga/cabang berhasil diperbarui', 'success')
    } else {
      await $api('/apps/product-branches', {
        method: 'POST',
        body: data,
      })
      snackbar.show('Produk berhasil ditambahkan ke cabang', 'success')
    }
    fetchData()
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal menyimpan data', 'error')
  }
}

const saveStockMovement = async data => {
  try {
    await $api('/apps/stock-movements', {
      method: 'POST',
      body: data,
    })
    snackbar.show('Stok berhasil ditambahkan', 'success')
    fetchData()
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mencatat mutasi stok', 'error')
  }
}

// --- Import / Export / Template ---
const downloadTemplate = () => {
  const csvContent = 'SKU Produk,Nama Cabang,Harga Modal,Harga Jual,Harga Nego,Pajak (%),Biaya Lainnya\nPRD-0001,Cabang Utama,10000,15000,14000,11,5000'
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')

  link.href = url
  link.setAttribute('download', 'Template_Inventori_Cabang.csv')
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const exportExcel = () => {
  if (!productBranches.value.length) {
    snackbar.show('Tidak ada data untuk diekspor', 'warning')
    
    return
  }
  const headers = ['SKU Produk', 'Nama Produk', 'Nama Cabang', 'Harga Modal', 'Harga Jual', 'Harga Nego', 'Pajak (%)', 'Biaya Lainnya', 'Stok']
  const csvRows = [headers.join(',')]
  
  productBranches.value.forEach(pb => {
    const row = [
      `"${pb.product?.sku || ''}"`,
      `"${pb.product?.name || ''}"`,
      `"${pb.branch?.name || ''}"`,
      `${pb.cost_price || 0}`,
      `${pb.price || 0}`,
      `${pb.min_nego_price || 0}`,
      `${pb.tax_percentage || 0}`,
      `${pb.other_fees || 0}`,
      `${pb.stock || 0}`,
    ]

    csvRows.push(row.join(','))
  })
  
  const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')

  link.href = url
  link.setAttribute('download', `Inventori_Cabang_${new Date().toISOString().split('T')[0]}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const triggerFileInput = () => {
  fileInput.value.click()
}

const handleFileUpload = async event => {
  const file = event.target.files[0]
  if (!file) return
  
  const formData = new FormData()

  formData.append('file', file)
  
  isLoading.value = true
  try {
    const res = await $api('/apps/product-branches/import', {
      method: 'POST',
      body: formData,
    })

    snackbar.show(res.message || 'Import berhasil', 'success')
    fetchData()
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal melakukan import data', 'error')
  } finally {
    isLoading.value = false
    event.target.value = '' // Reset input
  }
}


const tableHeaders = [
  { title: 'PRODUK', key: 'product.name' },
  { title: 'CABANG', key: 'branch.name' },
  { title: 'HARGA MODAL', key: 'cost_price' },
  { title: 'HARGA JUAL', key: 'price' },
  { title: 'HARGA NEGO', key: 'min_nego_price' },
  { title: 'PAJAK & BIAYA', key: 'taxes' },
  { title: 'STOK', key: 'stock' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const filteredItems = computed(() => {
  return Array.isArray(productBranches.value) ? productBranches.value : []
})

const editItem = item => {
  selectedProductBranch.value = item
  isAddNewDrawerVisible.value = true
}

const addStock = item => {
  selectedProductBranch.value = item
  isStockDrawerVisible.value = true
}

const printLabel = async item => {
  try {
    const data = await $api(`/apps/product-branches/${item.id}`)

    selectedProductBranch.value = data.data || data
    isPrintLabelDialogVisible.value = true
  } catch(error) {
    console.error(error)
    snackbar.show('Gagal mengambil data batch', 'error')
  }
}

const manageBatches = item => {
  selectedProductBranch.value = item
  isManageBatchesDialogVisible.value = true
}

const confirmDelete = async id => {
  if (confirm('Apakah Anda yakin ingin menghapus produk ini dari cabang?')) {
    try {
      await $api(`/apps/product-branches/${id}`, { method: 'DELETE' })
      snackbar.show('Data berhasil dihapus', 'success')
      fetchData()
    } catch (error) {
      console.error(error)
      snackbar.show('Gagal menghapus data', 'error')
    }
  }
}
</script>

<template>
  <section>
    <!-- Page Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="text-h4 mb-0">
          Inventori & Harga Cabang
        </h2>
        <p class="text-body-1 mb-0 text-disabled mt-1">
          Atur harga modal, harga jual, dan stok masuk untuk masing-masing cabang.
        </p>
      </div>
      
      <div class="d-flex gap-4">
        <input 
          ref="fileInput" 
          type="file" 
          accept=".csv" 
          style="display: none" 
          @change="handleFileUpload"
        >
        <VBtn
          v-if="$can('import', 'Inventori Cabang')"
          color="info"
          variant="tonal"
          prepend-icon="ri-download-cloud-line"
          @click="downloadTemplate"
        >
          Template
        </VBtn>
        <VBtn
          v-if="$can('import', 'Inventori Cabang')"
          color="warning"
          variant="tonal"
          prepend-icon="ri-upload-cloud-line"
          :loading="isLoading"
          @click="triggerFileInput"
        >
          Import
        </VBtn>
        <VBtn
          v-if="$can('export', 'Inventori Cabang')"
          color="success"
          variant="tonal"
          prepend-icon="ri-file-excel-2-line"
          @click="exportExcel"
        >
          Export
        </VBtn>
        <VBtn
          v-if="$can('create', 'Inventori Cabang')"
          color="primary"
          prepend-icon="ri-add-line"
          @click="() => { selectedProductBranch = null; isAddNewDrawerVisible = true }"
        >
          Daftarkan Produk ke Cabang
        </VBtn>
      </div>
    </div>

    <VCard>
      <VCardItem class="pa-4 pb-0">
        <div class="d-flex align-center justify-space-between w-100">
          <VCardTitle class="px-0">
            Daftar Inventori
          </VCardTitle>
          <div class="d-flex align-center gap-3">
            <div style="width: 220px;">
              <VAutocomplete
                v-model="selectedBranch"
                :items="branches"
                item-title="name"
                item-value="id"
                placeholder="Semua Cabang"
                density="compact"
                hide-details
                clearable
                @update:model-value="() => { page = 1; fetchData(); }"
              />
            </div>
            <div style="width: 250px;">
              <VTextField
                v-model="search"
                prepend-inner-icon="ri-search-line"
                placeholder="Cari produk cabang..."
                density="compact"
                hide-details
                variant="outlined"
                clearable
                @update:model-value="handleSearch"
              />
            </div>
          </div>
        </div>
      </VCardItem>



      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="tableHeaders"
        :items="filteredItems"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        @update:options="fetchData"
      >
        <template #item.product.name="{ item }">
          <div class="d-flex align-center">
            <VAvatar
              size="45"
              color="info"
              variant="tonal"
              class="mr-3 rounded"
            >
              <VImg
                v-if="item.product?.image"
                :src="`/storage/${item.product.image}`"
                alt="Produk"
                cover
              />
              <VIcon
                v-else
                icon="ri-box-3-line"
              />
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-h6 font-weight-medium mb-0">
                {{ item.product?.name }}
              </h6>
              <span class="text-caption text-disabled">SKU: {{ item.product?.sku }}</span>
            </div>
          </div>
        </template>

        <template #item.branch.name="{ item }">
          <span class="font-weight-medium text-body-2">{{ item.branch?.name || '-' }}</span>
        </template>

        <template #item.cost_price="{ item }">
          <div class="d-flex align-center">
            <span class="text-error font-weight-medium">{{ formatRupiah(item.active_batch?.cost_price || item.cost_price) }}</span>
            <VIcon
              v-if="item.active_batch"
              icon="ri-information-line"
              size="14"
              class="ms-1 text-disabled"
              title="Harga modal batch aktif (FEFO/FIFO)"
            />
          </div>
        </template>

        <template #item.price="{ item }">
          <div class="d-flex align-center">
            <span class="font-weight-bold text-success">{{ formatRupiah(item.active_batch?.price || item.price) }}</span>
            <VIcon
              v-if="item.active_batch"
              icon="ri-information-line"
              size="14"
              class="ms-1 text-disabled"
              title="Harga jual batch aktif (FEFO/FIFO)"
            />
          </div>
        </template>

        <template #item.min_nego_price="{ item }">
          <div class="d-flex align-center">
            <span class="text-warning font-weight-medium">{{ (item.active_batch?.min_nego_price || item.min_nego_price) > 0 ? formatRupiah(item.active_batch?.min_nego_price || item.min_nego_price) : '-' }}</span>
          </div>
        </template>

        <template #item.taxes="{ item }">
          <div class="d-flex flex-column">
            <span
              v-if="item.tax_percentage > 0"
              class="text-caption text-error font-weight-medium"
            >
              + PPN {{ Number(item.tax_percentage) }}%
            </span>
            <span
              v-else
              class="text-caption text-error font-weight-medium"
            >
              Tanpa PPN
            </span>
            
            <span
              v-if="item.other_fees > 0"
              class="text-caption text-warning"
            >
              + Biaya {{ formatRupiah(item.other_fees) }}
            </span>
          </div>
        </template>

        <template #item.stock="{ item }">
          <div class="d-flex flex-column align-start">
            <VChip
              :color="item.stock <= 0 ? 'error' : (item.stock <= (item.product?.min_stock || 5) ? 'warning' : 'success')"
              size="small"
              class="font-weight-bold"
            >
              <VIcon
                :icon="item.stock <= 0 ? 'ri-close-circle-line' : (item.stock <= (item.product?.min_stock || 5) ? 'ri-alert-line' : 'ri-check-line')"
                size="14"
                class="me-1"
              />
              {{ item.stock }} {{ item.product?.unit || 'Unit' }}
            </VChip>
            <span
              v-if="item.stock <= (item.product?.min_stock || 5) && item.stock > 0"
              class="text-caption text-warning font-weight-bold mt-1"
              style="font-size: 10px;"
            >
              ⚠️ Perlu Restok (Min: {{ item.product?.min_stock || 5 }})
            </span>
            <span
              v-else-if="item.stock <= 0"
              class="text-caption text-error font-weight-bold mt-1"
              style="font-size: 10px;"
            >
              Habis Total
            </span>
          </div>
        </template>

        <template #item.actions="{ item }">
          <VBtn
            v-if="$can('write', 'Inventori Cabang')"
            size="small"
            color="primary"
            variant="tonal"
            class="mr-2"
            @click="addStock(item)"
          >
            Inbound Stok
          </VBtn>
          <VBtn
            v-if="$can('read', 'Inventori Cabang')"
            size="small"
            color="info"
            variant="tonal"
            class="mr-2"
            @click="printLabel(item)"
          >
            Cetak Label
          </VBtn>
          <VBtn
            v-if="$can('write', 'Inventori Cabang')"
            size="small"
            color="secondary"
            variant="tonal"
            class="mr-2"
            @click="manageBatches(item)"
          >
            Batch
          </VBtn>
          <IconBtn
            v-if="$can('write', 'Inventori Cabang')"
            size="small"
            @click="editItem(item)"
          >
            <VIcon icon="ri-pencil-line" />
          </IconBtn>
          <IconBtn
            v-if="$can('delete', 'Inventori Cabang')"
            size="small"
            color="error"
            @click="confirmDelete(item.id)"
          >
            <VIcon icon="ri-delete-bin-line" />
          </IconBtn>
        </template>
      </VDataTableServer>
    </VCard>

    <AddNewProductBranchDrawer
      v-model:is-drawer-open="isAddNewDrawerVisible"
      :selected-data="selectedProductBranch"
      :master-products="masterProducts"
      :branches-list="branches"
      @save-data="saveProductBranch"
    />

    <StockInboundDrawer
      v-model:is-drawer-open="isStockDrawerVisible"
      :selected-branch-product="selectedProductBranch"
      @save-movement="saveStockMovement"
    />

    <ManageBatchesDialog
      v-model:is-dialog-visible="isManageBatchesDialogVisible"
      :selected-data="selectedProductBranch"
      @refresh-data="fetchData"
    />

    <PrintLabelDialog
      v-model:is-dialog-visible="isPrintLabelDialogVisible"
      :selected-data="selectedProductBranch"
    />
  </section>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Inventori Cabang
</route>
