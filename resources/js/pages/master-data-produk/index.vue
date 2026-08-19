<script setup>
import { ref, onMounted, computed } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import AddNewProductDrawer from './AddNewProductDrawer.vue'
import SimpleConfirmDialog from '@/components/dialogs/SimpleConfirmDialog.vue'

const products = ref([])
const categories = ref([])
const search = ref('')
const selectedCategory = ref('all')
const selectedStatus = ref('all')
const isLoading = ref(false)
const isAddNewProductDrawerVisible = ref(false)
const selectedProduct = ref(null)

const isConfirmDeleteDialogVisible = ref(false)
const productToDelete = ref(null)

// Pagination
const page = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)
let searchTimeout = null

const snackbar = useSnackbarStore()

const stats = computed(() => {
  const all = products.value || []
  const total = totalItems.value || all.length
  const totalCat = categories.value.length
  const active = all.filter(p => (p.status || 'Aktif') === 'Aktif').length
  const fefo = all.filter(p => p.stock_method === 'FEFO').length
  
  return { total, totalCat, active, fefo }
})

const fetchProducts = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
    }
    
    if (search.value) {
      params.search = search.value
    }
    if (selectedCategory.value !== 'all') {
      params.category_id = selectedCategory.value
    }
    if (selectedStatus.value !== 'all') {
      params.status = selectedStatus.value
    }

    const data = await $api('/apps/products', { query: params })

    products.value = data.data || data
    if (data.total !== undefined) {
      totalItems.value = data.total
    }
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mengambil data produk', 'error')
  } finally {
    isLoading.value = false
  }
}

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchProducts()
  }, 400)
}

const fetchCategories = async () => {
  try {
    const data = await $api('/apps/categories', { query: { itemsPerPage: -1 } })
    categories.value = data.data || data
  } catch (error) {
    console.error(error)
  }
}

onMounted(() => {
  fetchProducts()
  fetchCategories()
})

const addNewProduct = async productData => {
  try {
    const formData = new FormData()
    
    for (const key in productData) {
      if (productData[key] !== null && productData[key] !== undefined) {
        formData.append(key, productData[key])
      }
    }

    if (productData.id) {
      formData.append('_method', 'PUT')
      await $api(`/apps/products/${productData.id}`, {
        method: 'POST',
        body: formData,
      })
      snackbar.show('Data produk berhasil diperbarui', 'success')
    } else {
      await $api('/apps/products', {
        method: 'POST',
        body: formData,
      })
      snackbar.show('Produk baru berhasil ditambahkan', 'success')
    }
    fetchProducts()
  } catch (error) {
    console.error(error)
    snackbar.show('Terjadi kesalahan saat menyimpan data produk', 'error')
  }
}

const tableHeaders = [
  { title: 'INFORMASI PRODUK & SKU', key: 'name' },
  { title: 'MEREK / BRAND', key: 'brand' },
  { title: 'KATEGORI', key: 'category' },
  { title: 'METODE STOK', key: 'stock_method', align: 'center' },
  { title: 'STATUS', key: 'status', align: 'center' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const editProduct = product => {
  selectedProduct.value = product
  isAddNewProductDrawerVisible.value = true
}

const openDeleteDialog = id => {
  productToDelete.value = id
  isConfirmDeleteDialogVisible.value = true
}

const executeDeleteProduct = async isConfirmed => {
  if (!isConfirmed) return
  
  try {
    await $api(`/apps/products/${productToDelete.value}`, { method: 'DELETE' })
    snackbar.show('Produk berhasil dihapus dari sistem', 'success')
    fetchProducts()
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal menghapus produk', 'error')
  } finally {
    productToDelete.value = null
  }
}

// --- Import / Export / Template ---
const fileInput = ref(null)

const downloadTemplate = () => {
  let csvContent = 'SKU,Nama Produk,Kategori (Wajib),Type,Merek,Satuan,Qty Stok,Harga Modal,Harga Jual Pusat,Harga Cabang Bandung,Harga Cabang Sudirman\n'
  csvContent += 'SKU-001,Produk Contoh A,Mesin,R175,Dongfeng,Unit,10,1000000,1200000,1300000,1350000\n'
  csvContent += 'SKU-002,Produk Contoh B,Minuman,Aqua 600ml,Danone,Karton,50,45000,55000,55000,56000\n\n'
  
  if (categories.value && categories.value.length > 0) {
    csvContent += ',,,,\n'
    csvContent += '--- REFERENSI KATEGORI YANG SUDAH ADA ---,,,,\n'
    categories.value.forEach(c => {
      csvContent += `${c.name},,,,\n`
    })
    csvContent += '(Anda juga bisa mengetik nama kategori baru di atas dan sistem akan membuatnya otomatis),,,,\n'
  }

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')

  link.href = url
  link.setAttribute('download', 'Template_Master_Produk.csv')
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const exportExcel = () => {
  if (!products.value.length) {
    snackbar.show('Tidak ada data untuk diekspor', 'warning')
    return
  }
  const headers = ['Nama Produk', 'SKU', 'Kategori', 'Deskripsi', 'Merek', 'Barcode', 'Satuan', 'Status']
  const csvRows = [headers.join(',')]
  
  products.value.forEach(prod => {
    const row = [
      `"${prod.name || ''}"`,
      `"${prod.sku || ''}"`,
      `"${prod.category?.name || ''}"`,
      `"${prod.description || ''}"`,
      `"${prod.brand || ''}"`,
      `"${prod.barcode || ''}"`,
      `"${prod.unit || ''}"`,
      `"${prod.status || ''}"`,
    ]

    csvRows.push(row.join(','))
  })
  
  const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')

  link.href = url
  link.setAttribute('download', `Master_Produk_${new Date().toISOString().split('T')[0]}.csv`)
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
    const res = await $api('/apps/products/import', {
      method: 'POST',
      body: formData,
    })

    snackbar.show(res.message || 'Import berhasil', 'success')
    fetchProducts()
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal melakukan import data', 'error')
  } finally {
    isLoading.value = false
    event.target.value = '' // Reset input
  }
}
</script>

<template>
  <div class="pa-4">
    <!-- Header -->
    <div class="d-flex flex-wrap align-center justify-space-between mb-4 gap-4">
      <div>
        <h2 class="text-h4 font-weight-bold mb-1">
          Master Data Katalog Produk
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Kelola master SKU, barcode scanner, spesifikasi brand, kategori, dan metode mutasi stok terpusat.
        </p>
      </div>
      
      <div class="d-flex flex-wrap gap-3">
        <input 
          ref="fileInput" 
          type="file" 
          accept=".csv" 
          style="display: none" 
          @change="handleFileUpload"
        >

        <VBtn
          v-if="$can('import', 'Produk')"
          color="secondary"
          variant="tonal"
          prepend-icon="ri-download-cloud-line"
          @click="downloadTemplate"
        >
          Template CSV
        </VBtn>

        <VBtn
          v-if="$can('import', 'Produk')"
          color="warning"
          variant="tonal"
          prepend-icon="ri-upload-cloud-line"
          :loading="isLoading"
          @click="triggerFileInput"
        >
          Import
        </VBtn>

        <VBtn
          v-if="$can('export', 'Produk')"
          color="success"
          variant="tonal"
          prepend-icon="ri-file-excel-2-line"
          @click="exportExcel"
        >
          Export CSV
        </VBtn>

        <VBtn
          v-if="$can('create', 'Produk')"
          color="primary"
          prepend-icon="ri-add-line"
          @click="() => { selectedProduct = null; isAddNewProductDrawerVisible = true }"
        >
          Tambah Produk
        </VBtn>
      </div>
    </div>

    <!-- KPI Summary Row -->
    <VRow class="mb-4">
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-primary">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-primary font-weight-bold">TOTAL MASTER PRODUK</div>
              <div class="text-h4 font-weight-bold text-primary mt-1">{{ stats.total }} <span class="text-caption text-medium-emphasis">SKU</span></div>
            </div>
            <VAvatar color="primary" variant="tonal" rounded size="44">
              <VIcon icon="ri-box-3-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Seluruh varian terdaftar</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-info">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-info font-weight-bold">KATEGORI PRODUK</div>
              <div class="text-h4 font-weight-bold text-info mt-1">{{ stats.totalCat }} <span class="text-caption text-medium-emphasis">Kategori</span></div>
            </div>
            <VAvatar color="info" variant="tonal" rounded size="44">
              <VIcon icon="ri-folder-3-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Pengelompokan jenis barang</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-success">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-success font-weight-bold">PRODUK AKTIF</div>
              <div class="text-h4 font-weight-bold text-success mt-1">{{ stats.active }} <span class="text-caption text-medium-emphasis">Item</span></div>
            </div>
            <VAvatar color="success" variant="tonal" rounded size="44">
              <VIcon icon="ri-checkbox-circle-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Dapat dijual & diorder</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-warning">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-warning font-weight-bold">PRODUK FEFO (EXPIRED)</div>
              <div class="text-h4 font-weight-bold text-warning mt-1">{{ stats.fefo }} <span class="text-caption text-medium-emphasis">Item</span></div>
            </div>
            <VAvatar color="warning" variant="tonal" rounded size="44">
              <VIcon icon="ri-time-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Wajib pelacakan tanggal kadaluwarsa</div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Main Table Card -->
    <VCard elevation="2">
      <!-- Toolbar & Filters -->
      <VCardItem class="pa-4">
        <VRow align="center">
          <VCol cols="12" md="4">
            <VTextField
              v-model="search"
              prepend-inner-icon="ri-search-line"
              placeholder="Cari nama produk, SKU, barcode..."
              density="compact"
              variant="outlined"
              hide-details
              clearable
              @update:model-value="handleSearch"
            />
          </VCol>

          <VCol cols="12" sm="6" md="3">
            <VSelect
              v-model="selectedCategory"
              :items="[{ id: 'all', name: 'Semua Kategori' }, ...categories]"
              item-title="name"
              item-value="id"
              density="compact"
              variant="outlined"
              hide-details
              @update:model-value="fetchProducts"
            />
          </VCol>

          <VCol cols="12" sm="6" md="3">
            <VSelect
              v-model="selectedStatus"
              :items="[
                { title: 'Semua Status', value: 'all' },
                { title: 'Aktif Dijual', value: 'Aktif' },
                { title: 'Nonaktif / Arsip', value: 'Nonaktif' }
              ]"
              item-title="title"
              item-value="value"
              density="compact"
              variant="outlined"
              hide-details
              @update:model-value="fetchProducts"
            />
          </VCol>

          <VCol cols="12" md="2" class="text-right d-none d-md-block">
            <div class="text-caption text-medium-emphasis">
              Total: <strong>{{ totalItems }}</strong> Produk
            </div>
          </VCol>
        </VRow>
      </VCardItem>

      <VDivider />

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="tableHeaders"
        :items="products"
        :items-length="totalItems"
        :loading="isLoading"
        hover
        class="text-no-wrap"
        @update:options="fetchProducts"
      >
        <!-- Product Name, SKU & Barcode -->
        <template #item.name="{ item }">
          <div class="d-flex align-center py-2">
            <VAvatar
              size="44"
              color="primary"
              variant="tonal"
              class="me-3 rounded-lg border flex-shrink-0"
            >
              <VImg
                v-if="item.image"
                :src="`/storage/${item.image}`"
                alt="Produk"
                cover
              />
              <VIcon
                v-else
                icon="ri-box-3-line"
                size="24"
              />
            </VAvatar>
            <div>
              <div class="font-weight-bold text-subtitle-2 text-wrap" style="max-width: 320px;">
                {{ item.name }}
              </div>
              <div class="d-flex align-center gap-2 mt-1">
                <span class="text-caption text-medium-emphasis">SKU: <code>{{ item.sku || '-' }}</code></span>
                <span v-if="item.barcode" class="text-caption text-disabled">| Barcode: <code>{{ item.barcode }}</code></span>
              </div>
            </div>
          </div>
        </template>

        <!-- Brand -->
        <template #item.brand="{ item }">
          <div class="text-body-2 font-weight-medium">
            {{ item.brand || '-' }}
          </div>
          <div class="text-caption text-disabled">
            Satuan: {{ item.unit || 'Pcs' }}
          </div>
        </template>

        <!-- Category -->
        <template #item.category="{ item }">
          <VChip
            size="small"
            variant="tonal"
            color="secondary"
            class="font-weight-medium"
          >
            <VIcon icon="ri-folder-line" size="14" class="me-1" />
            {{ item.category ? item.category.name : 'Tanpa Kategori' }}
          </VChip>
        </template>

        <!-- Stock Method -->
        <template #item.stock_method="{ item }">
          <VChip
            size="small"
            variant="tonal"
            :color="item.stock_method === 'FEFO' ? 'error' : item.stock_method === 'LIFO' ? 'info' : 'primary'"
            class="font-weight-bold"
          >
            {{ item.stock_method || 'FIFO' }}
          </VChip>
        </template>

        <!-- Status -->
        <template #item.status="{ item }">
          <VChip
            :color="item.status === 'Aktif' ? 'success' : 'error'"
            size="small"
            variant="elevated"
            class="font-weight-bold"
          >
            <VIcon
              :icon="item.status === 'Aktif' ? 'ri-checkbox-circle-fill' : 'ri-close-circle-fill'"
              size="14"
              class="me-1"
            />
            {{ item.status || 'Aktif' }}
          </VChip>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex align-center justify-center gap-1">
            <VBtn
              v-if="$can('write', 'Produk')"
              size="small"
              variant="text"
              color="primary"
              icon="ri-edit-box-line"
              title="Edit Produk"
              @click="editProduct(item)"
            />
            <VBtn
              v-if="$can('delete', 'Produk')"
              size="small"
              variant="text"
              color="error"
              icon="ri-delete-bin-line"
              title="Hapus Produk"
              @click="openDeleteDialog(item.id)"
            />
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <AddNewProductDrawer
      v-model:is-drawer-open="isAddNewProductDrawerVisible"
      :selected-product="selectedProduct"
      :categories-list="categories"
      @product-data="addNewProduct"
    />

    <SimpleConfirmDialog
      v-model:is-dialog-visible="isConfirmDeleteDialogVisible"
      title="Hapus Master Produk?"
      message="Peringatan: Menghapus produk ini akan menghapus referensi master data. Pastikan produk tidak memiliki pergerakan transaksi aktif yang belum diselesaikan."
      confirm-text="Ya, Hapus"
      cancel-text="Batal"
      @confirm="executeDeleteProduct"
    />
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Produk
</route>
