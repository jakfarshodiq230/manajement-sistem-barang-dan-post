<script setup>
import { ref, onMounted, computed } from 'vue'
import { paginationMeta } from '@/utils/paginationMeta'
import { useSnackbarStore } from '@/stores/snackbar'
import AddNewCategoryDrawer from './AddNewCategoryDrawer.vue'
import SimpleConfirmDialog from '@/components/dialogs/SimpleConfirmDialog.vue'

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

const stats = computed(() => {
  const all = categories.value || []
  const total = totalItems.value || all.length
  return { total }
})

const tableHeaders = [
  { title: 'IDENTITAS KATEGORI', key: 'name' },
  { title: 'DESKRIPSI & KETERANGAN', key: 'description' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
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
  }, 400)
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

const deleteCategory = async isConfirmed => {
  if (!isConfirmed || !categoryToDelete.value) return
  
  try {
    await $api(`/apps/categories/${categoryToDelete.value.id}`, {
      method: 'DELETE',
    })
    snackbar.show('Kategori berhasil dihapus', 'success')
    fetchCategories()
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal menghapus kategori. Pastikan tidak ada produk terikat.', 'error')
  } finally {
    categoryToDelete.value = null
  }
}

// --- Import / Export / Template ---
const fileInput = ref(null)

const downloadTemplate = () => {
  const csvContent = 'Nama Kategori,Deskripsi\nSembako & Pangan,Bahan pokok kebutuhan harian\nElektronik,Perangkat elektronik dan aksesoris'
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
    snackbar.show('Gagal melakukan import data kategori', 'error')
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
          Master Data Kategori Barang
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Kelola klasifikasi kategori produk untuk mempermudah inventarisasi, pencarian kasir, dan audit Cycle Counting.
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
          v-if="$can('import', 'Kategori Barang')"
          color="secondary"
          variant="tonal"
          prepend-icon="ri-download-cloud-line"
          @click="downloadTemplate"
        >
          Template CSV
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
          Export CSV
        </VBtn>

        <VBtn
          v-if="$can('create', 'Kategori Barang')"
          color="primary"
          prepend-icon="ri-add-line"
          @click="openAddDrawer"
        >
          Tambah Kategori
        </VBtn>
      </div>
    </div>

    <!-- KPI Summary Row -->
    <VRow class="mb-4">
      <VCol cols="12" sm="4">
        <VCard elevation="2" class="pa-4 border-s-lg border-primary">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-primary font-weight-bold">TOTAL KATEGORI</div>
              <div class="text-h4 font-weight-bold text-primary mt-1">{{ stats.total }} <span class="text-caption text-medium-emphasis">Kategori</span></div>
            </div>
            <VAvatar color="primary" variant="tonal" rounded size="44">
              <VIcon icon="ri-folder-3-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Seluruh pengelompokan aktif</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="4">
        <VCard elevation="2" class="pa-4 border-s-lg border-info">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-info font-weight-bold">CYCLE COUNTING AUDIT</div>
              <div class="text-h4 font-weight-bold text-info mt-1">Siap <span class="text-caption text-medium-emphasis">Parsial</span></div>
            </div>
            <VAvatar color="info" variant="tonal" rounded size="44">
              <VIcon icon="ri-survey-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Audit stok terpisah per kategori</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="4">
        <VCard elevation="2" class="pa-4 border-s-lg border-success">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-success font-weight-bold">STATUS DATABASE</div>
              <div class="text-h4 font-weight-bold text-success mt-1">Sinkron <span class="text-caption text-medium-emphasis">100%</span></div>
            </div>
            <VAvatar color="success" variant="tonal" rounded size="44">
              <VIcon icon="ri-database-2-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Terhubung ke katalog produk</div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Main Table Card -->
    <VCard elevation="2">
      <!-- Card Toolbar -->
      <VCardItem class="pa-4">
        <div class="d-flex flex-wrap align-center justify-space-between gap-4">
          <div style="min-width: 280px; max-width: 400px;" class="flex-grow-1">
            <VTextField
              v-model="search"
              prepend-inner-icon="ri-search-line"
              placeholder="Cari nama kategori atau deskripsi..."
              density="compact"
              variant="outlined"
              hide-details
              clearable
              @update:model-value="handleSearch"
            />
          </div>

          <div class="text-caption text-medium-emphasis">
            Total Terdaftar: <strong>{{ totalItems }}</strong> Kategori
          </div>
        </div>
      </VCardItem>

      <VDivider />

      <!-- Table -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="tableHeaders"
        :items="categories"
        :items-length="totalItems"
        :loading="isLoading"
        hover
        class="text-no-wrap"
        @update:options="fetchCategories"
      >
        <!-- Category Name -->
        <template #item.name="{ item }">
          <div class="d-flex align-center py-2">
            <VAvatar
              size="38"
              color="primary"
              variant="tonal"
              class="me-3 rounded-lg border"
            >
              <VIcon icon="ri-folder-line" size="20" />
            </VAvatar>
            <div>
              <div class="font-weight-bold text-subtitle-2">{{ item.name }}</div>
              <div class="text-caption text-disabled">ID: #{{ item.id }}</div>
            </div>
          </div>
        </template>

        <!-- Description -->
        <template #item.description="{ item }">
          <span class="text-body-2 text-medium-emphasis text-wrap" style="max-width: 450px; display: inline-block;">
            {{ item.description || '-' }}
          </span>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex align-center justify-center gap-1">
            <VBtn
              v-if="$can('write', 'Kategori Barang')"
              size="small"
              variant="text"
              color="primary"
              icon="ri-edit-box-line"
              title="Edit Kategori"
              @click="openEditDrawer(item)"
            />
            <VBtn
              v-if="$can('delete', 'Kategori Barang')"
              size="small"
              variant="text"
              color="error"
              icon="ri-delete-bin-line"
              title="Hapus Kategori"
              @click="confirmDelete(item)"
            />
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

    <!-- Drawer Add/Edit -->
    <AddNewCategoryDrawer
      v-model:is-drawer-open="isAddNewCategoryDrawerVisible"
      :editing-category="editingCategory"
      @category-data="handleCategoryData"
    />

    <!-- Dialog Konfirmasi Hapus -->
    <SimpleConfirmDialog
      v-model:is-dialog-visible="isConfirmDeleteDialogVisible"
      title="Hapus Kategori Barang?"
      :message="`Apakah Anda yakin ingin menghapus kategori '${categoryToDelete?.name}'? Pastikan tidak ada master produk yang masih terikat dengan kategori ini.`"
      confirm-text="Ya, Hapus"
      cancel-text="Batal"
      @confirm="deleteCategory"
    />
  </div>
</template>

<style lang="scss">
.per-page-select {
  inline-size: 5.5rem;
}
</style>

<route lang="yaml">
meta:
  action: read
  subject: Kategori Barang
</route>
