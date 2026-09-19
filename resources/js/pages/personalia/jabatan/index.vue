<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { paginationMeta } from '@/utils/paginationMeta'
import { useSnackbarStore } from '@/stores/snackbar'
import AddNewJabatanDrawer from './AddNewJabatanDrawer.vue'

definePage({
  meta: {
    action: 'read',
    subject: 'Jabatan & Departemen',
  },
})

const isLoading = ref(true)
const searchQuery = ref('')
const positions = ref([])

const page = ref(1)
const itemsPerPage = ref(15)
const totalItems = ref(0)
const isAddNewDrawerVisible = ref(false)
const selectedPosition = ref(null)

const tableHeaders = [
  { title: 'JABATAN', key: 'name' },
  { title: 'DEPARTEMEN', key: 'department' },
  { title: 'LEVEL', key: 'level' },
  { title: 'GAJI POKOK', key: 'base_salary' },
  { title: 'STATUS', key: 'status', align: 'center' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const snackbar = useSnackbarStore()

const extractArray = val => {
  if (Array.isArray(val)) return val
  if (val && Array.isArray(val.data)) return val.data
  return []
}

const fetchPositions = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
    }
    
    if (searchQuery.value) {
      params.q = searchQuery.value
    }
    
    const res = await $api('/apps/positions', { query: params })
    positions.value = extractArray(res)
    totalItems.value = res.total || positions.value.length
  } catch (e) {
    console.error('Failed to load positions:', e)
    snackbar.show('Gagal memuat data jabatan', 'error')
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchPositions()
})

const formatCurrency = value => {
  if (!value) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value)
}

const editPosition = item => {
  selectedPosition.value = { ...item }
  isAddNewDrawerVisible.value = true
}

const deletePosition = async id => {
  if (!confirm('Apakah Anda yakin ingin menghapus jabatan ini?')) return
  
  try {
    await $api(`/apps/positions/${id}`, { method: 'DELETE' })
    snackbar.show('Jabatan berhasil dihapus', 'success')
    fetchPositions()
  } catch (e) {
    console.error(e)
    snackbar.show(e.data?.message || 'Gagal menghapus jabatan', 'error')
  }
}

const handleFormSubmit = async data => {
  try {
    const endpoint = data.id ? `/apps/positions/${data.id}` : '/apps/positions'
    const method = data.id ? 'PUT' : 'POST'
    
    await $api(endpoint, {
      method,
      body: data,
    })
    
    snackbar.show(data.id ? 'Jabatan berhasil diperbarui' : 'Jabatan berhasil ditambahkan', 'success')
    isAddNewDrawerVisible.value = false
    fetchPositions()
  } catch (e) {
    console.error(e)
    snackbar.show(e.data?.message || 'Gagal menyimpan data jabatan', 'error')
  }
}

let searchTimeout = null
const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchPositions()
  }, 500)
}

</script>

<template>
  <section>
    <!-- Header -->
    <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold mb-1 text-high-emphasis">
          Jabatan & Departemen
        </h4>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Kelola daftar jabatan, departemen, level, dan standar gaji pokok.
        </p>
      </div>

      <div class="d-flex align-center gap-3">
        <VBtn
          color="primary"
          prepend-icon="ri-add-line"
          @click="() => { selectedPosition = null; isAddNewDrawerVisible = true }"
        >
          Tambah Jabatan
        </VBtn>
      </div>
    </div>

    <!-- Filter Card -->
    <VCard  class="border rounded-lg mb-6">
      <VCardText class="pa-4">
        <VRow>
          <VCol cols="12" md="6">
            <AppTextField
              v-model="searchQuery"
              placeholder="Cari nama jabatan atau departemen..."
              prepend-inner-icon="ri-search-line"
              density="compact"
              @input="handleSearch"
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Data Table -->
    <VCard  class="border rounded-lg">
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :items="positions"
        :items-length="totalItems"
        :headers="tableHeaders"
        :loading="isLoading"
        class="text-no-wrap rounded-lg"
        @update:options="fetchPositions"
      >
        <template #item.name="{ item }">
          <span class="font-weight-medium text-high-emphasis">{{ item.name }}</span>
        </template>
        
        <template #item.department="{ item }">
          {{ item.department || '-' }}
        </template>
        
        <template #item.level="{ item }">
          <VChip size="small" color="info" variant="tonal">
            Level {{ item.level }}
          </VChip>
        </template>
        
        <template #item.base_salary="{ item }">
          {{ formatCurrency(item.base_salary) }}
        </template>

        <template #item.status="{ item }">
          <VChip
            :color="item.status === 'Aktif' ? 'success' : 'error'"
            size="small"
            class="text-capitalize"
            variant="tonal"
          >
            {{ item.status }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <IconBtn @click="editPosition(item)" size="small">
            <VIcon icon="ri-edit-box-line" />
            <VTooltip activator="parent" location="top">Edit Jabatan</VTooltip>
          </IconBtn>
          
          <IconBtn @click="deletePosition(item.id)" color="error" size="small">
            <VIcon icon="ri-delete-bin-line" />
            <VTooltip activator="parent" location="top">Hapus Jabatan</VTooltip>
          </IconBtn>
        </template>

        <template #bottom>
          <VCardText class="pt-2 pb-0">
            <div class="d-flex flex-wrap justify-space-between align-center gap-4">
              <p class="text-sm text-disabled mb-0">
                {{ paginationMeta({ page, itemsPerPage }, totalItems) }}
              </p>
              <VPagination
                v-model="page"
                :length="Math.ceil(totalItems / itemsPerPage)"
                :total-visible="5"
                density="compact"
                active-color="primary"
                @update:model-value="fetchPositions"
              />
            </div>
          </VCardText>
        </template>
      </VDataTableServer>
    </VCard>

    <AddNewJabatanDrawer
      v-model:isDrawerOpen="isAddNewDrawerVisible"
      :position="selectedPosition"
      @submit="handleFormSubmit"
    />
  </section>
</template>
