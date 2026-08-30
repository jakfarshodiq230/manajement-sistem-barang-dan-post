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
const isPriceGuideVisible = ref(true)

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
  { title: 'HARGA MODAL (HPP)', key: 'cost_price' },
  { title: 'HARGA JUAL NORMAL', key: 'price' },
  { title: 'HARGA NEGO (MINIMAL)', key: 'min_nego_price' },
  { title: 'PAJAK PENJUALAN POS', key: 'taxes' },
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

const openBatchesDialog = manageBatches

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
    <div class="d-flex flex-column flex-md-row align-start align-md-center justify-space-between gap-4 mb-4">
      <div>
        <h2 class="text-h5 text-md-h4 font-weight-bold mb-1">
          Inventori & Harga Cabang
        </h2>
        <p class="text-body-2 text-md-body-1 mb-0 text-medium-emphasis">
          Atur harga modal, harga jual, dan stok masuk untuk masing-masing cabang.
        </p>
      </div>
      
      <div class="d-flex flex-wrap align-center gap-2 w-100 w-md-auto">
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
          size="small"
          prepend-icon="ri-download-cloud-line"
          class="flex-grow-1 flex-sm-grow-0"
          @click="downloadTemplate"
        >
          Template
        </VBtn>
        <VBtn
          v-if="$can('import', 'Inventori Cabang')"
          color="warning"
          variant="tonal"
          size="small"
          prepend-icon="ri-upload-cloud-line"
          :loading="isLoading"
          class="flex-grow-1 flex-sm-grow-0"
          @click="triggerFileInput"
        >
          Import
        </VBtn>
        <VBtn
          v-if="$can('export', 'Inventori Cabang')"
          color="success"
          variant="tonal"
          size="small"
          prepend-icon="ri-file-excel-2-line"
          class="flex-grow-1 flex-sm-grow-0"
          @click="exportExcel"
        >
          Export
        </VBtn>
        <VBtn
          v-if="$can('create', 'Inventori Cabang')"
          color="primary"
          size="small"
          prepend-icon="ri-add-line"
          class="flex-grow-1 flex-sm-grow-0 font-weight-semibold shadow-xs"
          @click="() => { selectedProductBranch = null; isAddNewDrawerVisible = true }"
        >
          Daftarkan Produk ke Cabang
        </VBtn>
      </div>
    </div>

    <!-- Price Structure & Tax Policy Guide Banner -->
    <VCard class="mb-5 border border-primary border-opacity-25 bg-primary-lighten-5 rounded-xl">
      <VCardItem class="pa-4">
        <div class="d-flex align-center justify-space-between flex-wrap gap-2">
          <div class="d-flex align-center gap-3">
            <VAvatar color="primary" variant="tonal" size="44" rounded="lg">
              <VIcon icon="ri-price-tag-3-line" size="24" />
            </VAvatar>
            <div>
              <h4 class="text-subtitle-1 font-weight-bold text-primary mb-0">
                Panduan Struktur Harga Modal (HPP), Harga Jual, Harga Nego & Pajak Kasir POS
              </h4>
              <p class="text-caption text-medium-emphasis mb-0">
                Ketentuan perhitungan 3 tingkatan harga dan perlakuan PPN Masukan Supplier vs PPN Keluaran Kasir POS.
              </p>
            </div>
          </div>
          <VBtn
            size="small"
            variant="tonal"
            color="primary"
            :prepend-icon="isPriceGuideVisible ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'"
            @click="isPriceGuideVisible = !isPriceGuideVisible"
          >
            {{ isPriceGuideVisible ? 'Tutup Catatan Ketentuan' : 'Buka Catatan Ketentuan' }}
          </VBtn>
        </div>

        <VExpandTransition>
          <div v-show="isPriceGuideVisible" class="mt-4 pt-3 border-t">
            <VRow dense class="g-3">
              <!-- 1. Harga Modal Real (HPP) -->
              <VCol cols="12" md="3">
                <div class="pa-3 bg-white border rounded-lg h-100">
                  <div class="d-flex align-center gap-2 mb-1">
                    <VIcon icon="ri-archive-line" size="18" color="error" />
                    <span class="text-caption font-weight-bold text-error">1. Harga Modal (HPP Real)</span>
                  </div>
                  <div class="text-caption text-medium-emphasis mb-2" style="font-size: 11px;">
                    Harga bersih per unit dari PO yang <strong>sudah mencakup diskon bertingkat (D1 s/d D5) dan PPN Masukan 11%</strong> dari Supplier.
                  </div>
                  <div class="pa-1 bg-error-lighten-5 rounded text-caption text-error font-mono font-weight-medium" style="font-size: 10.5px;">
                    Total Bayar Supplier / Total Pcs
                  </div>
                </div>
              </VCol>

              <!-- 2. Harga Jual Normal -->
              <VCol cols="12" md="3">
                <div class="pa-3 bg-white border rounded-lg h-100">
                  <div class="d-flex align-center gap-2 mb-1">
                    <VIcon icon="ri-shopping-bag-3-line" size="18" color="success" />
                    <span class="text-caption font-weight-bold text-success">2. Harga Jual Normal (POS)</span>
                  </div>
                  <div class="text-caption text-medium-emphasis mb-2" style="font-size: 11px;">
                    Harga pricelist standar eceran di kasir dengan target margin untung retail toko (contoh markup 35% - 40%).
                  </div>
                  <div class="pa-1 bg-success-lighten-5 rounded text-caption text-success font-mono font-weight-medium" style="font-size: 10.5px;">
                    Modal HPP x (1 + Target Markup %)
                  </div>
                </div>
              </VCol>

              <!-- 3. Harga Nego Minimum -->
              <VCol cols="12" md="3">
                <div class="pa-3 bg-white border rounded-lg h-100">
                  <div class="d-flex align-center gap-2 mb-1">
                    <VIcon icon="ri-hand-coin-line" size="18" color="warning" />
                    <span class="text-caption font-weight-bold text-warning">3. Harga Nego (Batas Kasir)</span>
                  </div>
                  <div class="text-caption text-medium-emphasis mb-2" style="font-size: 11px;">
                    Batas terendah saat tawar-menawar / grosir. Jika pembeli menawar di bawah ini, POS terkunci & wajib <strong>PIN Supervisor/Owner</strong>.
                  </div>
                  <div class="pa-1 bg-warning-lighten-5 rounded text-caption text-warning font-mono font-weight-medium" style="font-size: 10.5px;">
                    Modal HPP x (1 + Min. Margin 10-15%)
                  </div>
                </div>
              </VCol>

              <!-- 4. Pajak Penjualan Kasir POS -->
              <VCol cols="12" md="3">
                <div class="pa-3 bg-white border rounded-lg h-100">
                  <div class="d-flex align-center gap-2 mb-1">
                    <VIcon icon="ri-percent-line" size="18" color="primary" />
                    <span class="text-caption font-weight-bold text-primary">4. Pajak Penjualan POS</span>
                  </div>
                  <div class="text-caption text-medium-emphasis mb-2" style="font-size: 11px;">
                    <strong>Harga Final (Netto):</strong> Kasir menjual harga bersih tanpa tambahan pajak di struk. <br>
                    <strong>+ PPN 11%:</strong> PPN ditambahkan di struk POS ke pembeli.
                  </div>
                  <div class="pa-1 bg-primary-lighten-5 rounded text-caption text-primary font-mono font-weight-medium" style="font-size: 10.5px;">
                    Pengaturan Struk Kasir POS
                  </div>
                </div>
              </VCol>

              <!-- 5. Ketentuan Multi-Batch & FIFO -->
              <VCol cols="12" class="mt-2">
                <div class="pa-3 bg-info-lighten-5 border border-info border-opacity-25 rounded-lg text-caption text-info d-flex align-center gap-2">
                  <VIcon icon="ri-information-fill" size="20" />
                  <div>
                    <strong>Ketentuan Barang dengan Banyak Batch Fisik (Multi-Batch):</strong> Jika suatu produk memiliki beberapa batch pengiriman dari supplier dengan modal berbeda, sistem kasir POS secara otomatis menggunakan harga dari <strong>Batch Aktif (metode FIFO/FEFO)</strong>. Owner dapat mengedit modal & harga jual setiap batch atau menyamakan seluruh batch melalui tombol <strong>"Kelola Batch"</strong>.
                  </div>
                </div>
              </VCol>
            </VRow>
          </div>
        </VExpandTransition>
      </VCardItem>
    </VCard>

    <VCard class="rounded-xl shadow-xs">
      <VCardItem class="pa-4 pb-2">
        <div class="d-flex flex-column flex-sm-row align-start align-sm-center justify-space-between gap-3 w-100">
          <VCardTitle class="px-0 text-h6 font-weight-bold">
            Daftar Inventori
          </VCardTitle>
          <div class="d-flex flex-column flex-sm-row align-stretch align-sm-center gap-3 w-100 w-sm-auto">
            <div style="min-width: 180px;" class="w-100 w-sm-auto">
              <VAutocomplete
                v-model="selectedBranch"
                :items="branches"
                item-title="name"
                item-value="id"
                label="Filter Cabang"
                placeholder="Pilih Cabang"
                density="compact"
                variant="outlined"
                clearable
                hide-details
                @update:model-value="fetchData"
              />
            </div>
            <div style="min-width: 220px;" class="w-100 w-sm-auto">
              <VTextField
                v-model="search"
                placeholder="Cari nama barang / SKU..."
                prepend-inner-icon="ri-search-line"
                density="compact"
                variant="outlined"
                clearable
                hide-details
                @update:model-value="fetchData"
              />
            </div>
          </div>
        </div>
      </VCardItem>

      <VCardText class="pa-0 mt-2">
        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          v-model:page="page"
          :items="filteredItems"
          :items-length="totalItems"
          :headers="tableHeaders"
          :loading="isLoading"
          class="text-no-wrap"
          @update:options="fetchData"
        >
        <!-- Product & SKU -->
        <template #item.product.name="{ item }">
          <div class="d-flex align-center py-2">
            <VAvatar
              size="40"
              rounded="lg"
              class="me-3 border"
              :color="item.product?.image ? undefined : 'primary'"
              :variant="item.product?.image ? undefined : 'tonal'"
            >
              <VImg
                v-if="item.product?.image"
                :src="`/storage/${item.product.image}`"
                cover
              />
              <span v-else class="text-uppercase font-weight-bold">{{ item.product?.name?.substring(0, 2) }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <span class="font-weight-bold text-body-1 text-high-emphasis">{{ item.product?.name }}</span>
              <span class="text-caption font-mono text-medium-emphasis">SKU: {{ item.product?.sku || '-' }}</span>
            </div>
          </div>
        </template>

        <template #item.branch.name="{ item }">
          <span class="font-weight-medium text-body-2">{{ item.branch?.name || '-' }}</span>
        </template>

        <template #item.cost_price="{ item }">
          <div>
            <div class="d-flex align-center flex-wrap gap-1">
              <span class="text-error font-weight-bold text-subtitle-2">{{ formatRupiah(item.active_batch?.cost_price || item.cost_price) }}</span>
              <VChip
                v-if="item.product_batches && item.product_batches.length > 1"
                size="x-small"
                color="primary"
                variant="tonal"
                class="cursor-pointer font-weight-bold"
                title="Klik untuk melihat dan mengedit harga per batch"
                @click="manageBatches(item)"
              >
                {{ item.product_batches.length }} Batch
              </VChip>
            </div>
            <div v-if="item.active_batch?.scc_code || item.active_batch?.batch_number" class="text-caption text-primary font-weight-medium" style="font-size: 11px;">
              Batch Aktif: <strong>{{ item.active_batch.scc_code || item.active_batch.batch_number }}</strong>
            </div>
            <div v-else-if="item.product_batches && item.product_batches.length > 1" class="text-caption text-medium-emphasis" style="font-size: 10px;">
              (Harga Batch Aktif FIFO)
            </div>
            <div v-else class="text-caption text-medium-emphasis" style="font-size: 10px;">
              (Inc. PPN Masukan)
            </div>
          </div>
        </template>

        <template #item.price="{ item }">
          <div>
            <div class="d-flex align-center flex-wrap gap-1">
              <span class="font-weight-bold text-success text-subtitle-2">{{ formatRupiah(item.active_batch?.price || item.price) }}</span>
              <VChip
                v-if="item.product_batches && item.product_batches.some(b => b.qty > 0 && Number(b.price) === 0)"
                size="x-small"
                color="warning"
                variant="flat"
                class="cursor-pointer font-weight-bold"
                title="Ada batch yang belum diset harga jualnya. Klik untuk mengatur."
                @click="manageBatches(item)"
              >
                <VIcon icon="ri-error-warning-line" size="14" class="mr-1" />
                Set Harga
              </VChip>
            </div>
            <div v-if="(item.active_batch?.price || item.price) > (item.active_batch?.cost_price || item.cost_price)" class="text-caption text-success font-weight-medium" style="font-size: 10.5px;">
              Laba: +{{ formatRupiah((item.active_batch?.price || item.price) - (item.active_batch?.cost_price || item.cost_price)) }}
            </div>
          </div>
        </template>

        <template #item.min_nego_price="{ item }">
          <div>
            <div class="d-flex align-center">
              <span class="text-warning font-weight-medium">{{ (item.active_batch?.min_nego_price || item.min_nego_price) > 0 ? formatRupiah(item.active_batch?.min_nego_price || item.min_nego_price) : '-' }}</span>
            </div>
            <div v-if="(item.active_batch?.min_nego_price || item.min_nego_price) > (item.active_batch?.cost_price || item.cost_price)" class="text-caption text-warning" style="font-size: 10px;">
              Min. Laba: +{{ formatRupiah((item.active_batch?.min_nego_price || item.min_nego_price) - (item.active_batch?.cost_price || item.cost_price)) }}
            </div>
          </div>
        </template>

        <template #item.taxes="{ item }">
          <div class="d-flex flex-column">
            <span
              v-if="item.tax_percentage > 0"
              class="text-caption text-primary font-weight-medium"
            >
              + PPN Kasir {{ Number(item.tax_percentage) }}%
            </span>
            <span
              v-else
              class="text-caption text-medium-emphasis"
            >
              Harga Final (Netto)
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
              class="text-caption text-warning font-weight-bold mt-1 d-flex align-center"
              style="font-size: 10px;"
            >
              <VIcon icon="ri-alert-line" size="12" class="me-1" />
              Perlu Restok (Min: {{ item.product?.min_stock || 5 }})
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
          <div class="d-flex align-center flex-wrap gap-1 py-1" style="min-width: 260px;">
            <VBtn
              v-if="$can('write', 'Inventori Cabang')"
              size="x-small"
              color="primary"
              variant="tonal"
              prepend-icon="ri-inbox-archive-line"
              class="font-weight-medium"
              @click="addStock(item)"
            >
              Inbound
            </VBtn>
            <VBtn
              v-if="$can('read', 'Inventori Cabang')"
              size="x-small"
              color="info"
              variant="tonal"
              prepend-icon="ri-printer-line"
              class="font-weight-medium"
              @click="printLabel(item)"
            >
              Label
            </VBtn>
            <VBtn
              v-if="$can('write', 'Inventori Cabang')"
              size="x-small"
              color="secondary"
              variant="tonal"
              prepend-icon="ri-qr-code-line"
              class="font-weight-medium"
              @click="manageBatches(item)"
            >
              Batch & SCC
            </VBtn>
            <VBtn
              v-if="$can('write', 'Inventori Cabang')"
              icon="ri-pencil-line"
              variant="text"
              size="x-small"
              color="secondary"
              @click="editItem(item)"
            />
            <VBtn
              v-if="$can('delete', 'Inventori Cabang')"
              icon="ri-delete-bin-line"
              variant="text"
              size="x-small"
              color="error"
              @click="confirmDelete(item.id)"
            />
          </div>
        </template>
      </VDataTableServer>
    </VCardText>
  </VCard>

    <AddNewProductBranchDrawer
      v-model:is-drawer-open="isAddNewDrawerVisible"
      :is-drawer-open="isAddNewDrawerVisible"
      :selected-data="selectedProductBranch"
      :master-products="masterProducts"
      :branches-list="branches"
      @update:is-drawer-open="val => isAddNewDrawerVisible = val"
      @update:isDrawerOpen="val => isAddNewDrawerVisible = val"
      @close="isAddNewDrawerVisible = false"
      @cancel="isAddNewDrawerVisible = false"
      @save-data="saveProductBranch"
    />

    <StockInboundDrawer
      v-model:is-drawer-open="isStockDrawerVisible"
      :is-drawer-open="isStockDrawerVisible"
      :selected-branch-product="selectedProductBranch"
      @update:is-drawer-open="val => isStockDrawerVisible = val"
      @update:isDrawerOpen="val => isStockDrawerVisible = val"
      @close="isStockDrawerVisible = false"
      @cancel="isStockDrawerVisible = false"
      @save-movement="saveStockMovement"
    />

    <ManageBatchesDialog
      v-model:is-dialog-visible="isManageBatchesDialogVisible"
      :is-dialog-visible="isManageBatchesDialogVisible"
      :selected-data="selectedProductBranch"
      @update:is-dialog-visible="val => isManageBatchesDialogVisible = val"
      @refresh-data="fetchData"
    />

    <PrintLabelDialog
      v-model:is-dialog-visible="isPrintLabelDialogVisible"
      :is-dialog-visible="isPrintLabelDialogVisible"
      :selected-data="selectedProductBranch"
      @update:is-dialog-visible="val => isPrintLabelDialogVisible = val"
    />
  </section>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Inventori Cabang
</route>
