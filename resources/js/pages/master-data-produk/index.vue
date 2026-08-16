<script setup>
import { ref, onMounted, computed } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import AddNewProductDrawer from './AddNewProductDrawer.vue'

const products = ref([])
const categories = ref([])
const search = ref('')
const isLoading = ref(false)
const isAddNewProductDrawerVisible = ref(false)
const selectedProduct = ref(null)

// Pagination
const page = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)
let searchTimeout = null

// Format currency
const formatRupiah = value => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value)
}

const snackbar = useSnackbarStore()

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
  }, 500)
}

const fetchCategories = async () => {
  try {
    const data = await $api('/apps/categories')

    categories.value = data
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
    
    // Append all data
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
  { title: 'PRODUK', key: 'name' },
  { title: 'MEREK', key: 'brand' },
  { title: 'KATEGORI', key: 'category' },
  { title: 'METODE STOK', key: 'stock_method' },
  { title: 'STATUS', key: 'status' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const filteredProducts = computed(() => {
  return products.value
})

const editProduct = product => {
  selectedProduct.value = product
  isAddNewProductDrawerVisible.value = true
}

const confirmDeleteProduct = async id => {
  if (confirm('Apakah Anda yakin ingin menghapus produk ini?')) {
    try {
      await $api(`/apps/products/${id}`, { method: 'DELETE' })
      snackbar.show('Produk berhasil dihapus', 'success')
      fetchProducts()
    } catch (error) {
      console.error(error)
      snackbar.show('Gagal menghapus produk', 'error')
    }
  }
}

// --- Import / Export / Template ---
const fileInput = ref(null)

const downloadTemplate = () => {
  let csvContent = 'Nama Produk,SKU,Kategori (Wajib),Deskripsi,Merek,Barcode,Satuan,Berat (Gram),Bisa Retur (true/false)\n'
  csvContent += 'Produk Contoh A,SKU001,Makanan Ringan,Deskripsi produk A,Indofood,89912345678,Pcs,150,true\n'
  csvContent += 'Produk Contoh B,SKU002,Minuman,Deskripsi produk B,Aqua,,Karton,5000,false\n\n'
  
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
  <section>
    <!-- Page Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="text-h4 mb-0">
          Master Data Produk (Pusat)
        </h2>
        <p class="text-body-1 mb-0 text-disabled mt-1">
          Kelola daftar master produk dari kantor pusat. Harga dan stok diatur di menu Inventori Cabang.
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
          v-if="$can('import', 'Produk')"
          color="info"
          variant="tonal"
          prepend-icon="ri-download-cloud-line"
          @click="downloadTemplate"
        >
          Template
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
          Export
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

    <!-- Card -->
    <VCard>
      <!-- Card Header -->
      <VCardItem class="pa-4 pb-0">
        <div class="d-flex align-center justify-space-between w-100">
          <VCardTitle class="px-0">
            Daftar Produk
          </VCardTitle>
          <div style="width: 250px;">
            <VTextField
              v-model="search"
              prepend-inner-icon="ri-search-line"
              placeholder="Cari produk atau SKU..."
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
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="tableHeaders"
        :items="products"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        @update:options="fetchProducts"
      >
        <template #item.name="{ item }">
          <div class="d-flex align-center">
            <VAvatar
              size="45"
              color="info"
              variant="tonal"
              class="mr-3 rounded"
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
              />
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-h6 font-weight-medium mb-0">
                {{ item.name }}
              </h6>
              <span class="text-caption text-disabled">SKU: {{ item.sku }}</span>
            </div>
          </div>
        </template>

        <template #item.category="{ item }">
          <span class="text-body-2">{{ item.category ? item.category.name : '-' }}</span>
        </template>

        <template #item.stock_method="{ item }">
          <VChip
            size="small"
            variant="tonal"
            color="primary"
            class="text-uppercase font-weight-bold"
          >
            {{ item.stock_method || 'FIFO' }}
          </VChip>
        </template>

        <template #item.status="{ item }">
          <VChip
            :color="item.status === 'Aktif' ? 'success' : 'error'"
            size="small"
          >
            {{ item.status || 'Aktif' }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <IconBtn
            v-if="$can('write', 'Produk')"
            size="small"
            @click="editProduct(item)"
          >
            <VIcon icon="ri-pencil-line" />
          </IconBtn>
          <IconBtn
            v-if="$can('delete', 'Produk')"
            size="small"
            color="error"
            @click="confirmDeleteProduct(item.id)"
          >
            <VIcon icon="ri-delete-bin-line" />
          </IconBtn>
        </template>
      </VDataTableServer>
    </VCard>

    <AddNewProductDrawer
      v-model:is-drawer-open="isAddNewProductDrawerVisible"
      :selected-product="selectedProduct"
      :categories-list="categories"
      @product-data="addNewProduct"
    />
  </section>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Produk
</route>
