<script setup>
import { ref, onMounted, computed } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import AddNewCategoryDrawer from './AddNewCategoryDrawer.vue'

const categories = ref([])
const isLoading = ref(false)
const search = ref('')
const snackbar = useSnackbarStore()

// Pagination
const page = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)
let searchTimeout = null

const isAddNewCategoryDrawerVisible = ref(false)
const editingCategory = ref(null)

const isConfirmDeleteDialogVisible = ref(false)
const categoryToDelete = ref(null)

const tableHeaders = [
  { title: 'NAMA KATEGORI', key: 'name' },
  { title: 'DESKRIPSI', key: 'description' },
  { title: 'ACTIONS', key: 'actions', sortable: false, align: 'end' },
]

const fetchCategories = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
    }
    
    if (search.value) {
      params.search = search.value
    }
    
    const data = await $api('/apps/categories', { query: params })

    categories.value = data.data || data
    if (data.total !== undefined) {
      totalItems.value = data.total
    }
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mengambil data kategori', 'error')
  } finally {
    isLoading.value = false
  }
}

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchCategories()
  }, 500)
}

onMounted(() => {
  fetchCategories()
})

const openAddDrawer = () => {
  editingCategory.value = null
  isAddNewCategoryDrawerVisible.value = true
}

const openEditDrawer = category => {
  editingCategory.value = { ...category }
  isAddNewCategoryDrawerVisible.value = true
}

const confirmDelete = category => {
  categoryToDelete.value = category
  isConfirmDeleteDialogVisible.value = true
}

const handleCategoryData = async categoryData => {
  try {
    if (categoryData.id) {
      // Update
      await $api(`/apps/categories/${categoryData.id}`, {
        method: 'PUT',
        body: categoryData,
      })
      snackbar.show('Kategori berhasil diperbarui', 'success')
    } else {
      // Create
      await $api('/apps/categories', {
        method: 'POST',
        body: categoryData,
      })
      snackbar.show('Kategori berhasil ditambahkan', 'success')
    }
    fetchCategories()
  } catch (error) {
    console.error(error)
    snackbar.show('Terjadi kesalahan saat menyimpan data', 'error')
  }
}

const deleteCategory = async () => {
  if (!categoryToDelete.value) return
  
  try {
    await $api(`/apps/categories/${categoryToDelete.value.id}`, {
      method: 'DELETE',
    })
    snackbar.show('Kategori berhasil dihapus', 'success')
    fetchCategories()
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal menghapus kategori', 'error')
  } finally {
    isConfirmDeleteDialogVisible.value = false
    categoryToDelete.value = null
  }
}

// --- Import / Export / Template ---
const fileInput = ref(null)

const downloadTemplate = () => {
  const csvContent = 'Nama Kategori,Deskripsi\nContoh Kategori,Ini adalah contoh deskripsi'
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')

  link.href = url
  link.setAttribute('download', 'Template_Kategori.csv')
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const exportExcel = () => {
  if (!categories.value.length) {
    snackbar.show('Tidak ada data untuk diekspor', 'warning')
    
    return
  }
  const headers = ['Nama Kategori', 'Deskripsi']
  const csvRows = [headers.join(',')]
  
  categories.value.forEach(cat => {
    const row = [
      `"${cat.name || ''}"`,
      `"${cat.description || ''}"`,
    ]

    csvRows.push(row.join(','))
  })
  
  const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')

  link.href = url
  link.setAttribute('download', `Kategori_Barang_${new Date().toISOString().split('T')[0]}.csv`)
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
    const res = await $api('/apps/categories/import', {
      method: 'POST',
      body: formData,
    })

    snackbar.show(res.message || 'Import berhasil', 'success')
    fetchCategories()
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
          Master Data Kategori
        </h2>
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
          v-if="$can('import', 'Kategori Barang')"
          color="info"
          variant="tonal"
          prepend-icon="ri-download-cloud-line"
          @click="downloadTemplate"
        >
          Template
        </VBtn>
        <VBtn
          v-if="$can('import', 'Kategori Barang')"
          color="warning"
          variant="tonal"
          prepend-icon="ri-upload-cloud-line"
          :loading="isLoading"
          @click="triggerFileInput"
        >
          Import
        </VBtn>
        <VBtn
          v-if="$can('export', 'Kategori Barang')"
          color="success"
          variant="tonal"
          prepend-icon="ri-file-excel-2-line"
          @click="exportExcel"
        >
          Export
        </VBtn>
        <VBtn
          v-if="$can('create', 'Kategori Barang')"
          prepend-icon="ri-add-line"
          @click="openAddDrawer"
        >
          Tambah Kategori
        </VBtn>
      </div>
    </div>

    <!-- Card -->
    <VCard>
      <VCardItem class="pa-4 pb-0">
        <div class="d-flex align-center justify-space-between w-100">
          <VCardTitle class="px-0">
            Daftar Kategori
          </VCardTitle>
          <div style="width: 250px;">
            <VTextField
              v-model="search"
              prepend-inner-icon="ri-search-line"
              placeholder="Cari Kategori..."
              density="compact"
              hide-details
              variant="outlined"
              clearable
              @update:model-value="handleSearch"
            />
          </div>
        </div>
      </VCardItem>

      <!-- Table -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="tableHeaders"
        :items="categories"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        @update:options="fetchCategories"
      >
        <template #item.actions="{ item }">
          <div class="d-flex gap-1 justify-end">
            <IconBtn
              v-if="$can('write', 'Kategori Barang')"
              @click="openEditDrawer(item)"
            >
              <VIcon icon="ri-edit-line" />
            </IconBtn>
            <IconBtn
              v-if="$can('delete', 'Kategori Barang')"
              color="error"
              @click="confirmDelete(item)"
            >
              <VIcon icon="ri-delete-bin-line" />
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Drawer Add/Edit -->
    <AddNewCategoryDrawer
      v-model:is-drawer-open="isAddNewCategoryDrawerVisible"
      :editing-category="editingCategory"
      @category-data="handleCategoryData"
    />

    <!-- Dialog Konfirmasi Hapus -->
    <VDialog
      v-model="isConfirmDeleteDialogVisible"
      max-width="500"
    >
      <VCard>
        <VCardTitle class="text-h5">
          Konfirmasi Hapus
        </VCardTitle>
        <VCardText>
          Apakah Anda yakin ingin menghapus kategori <strong>{{ categoryToDelete?.name }}</strong>?
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            color="error"
            variant="outlined"
            @click="isConfirmDeleteDialogVisible = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="error"
            variant="elevated"
            @click="deleteCategory"
          >
            Hapus
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </section>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Kategori Barang
</route>
