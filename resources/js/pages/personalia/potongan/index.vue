<script setup>
import { ref, onMounted } from 'vue'
import { paginationMeta } from '@/utils/paginationMeta'
import { useSnackbarStore } from '@/stores/snackbar'
import AddNewPotonganDrawer from './AddNewPotonganDrawer.vue'

definePage({
  meta: {
    action: 'read',
    subject: 'Master Potongan',
  },
})

const isLoading = ref(true)
const searchQuery = ref('')
const items = ref([])

const page = ref(1)
const itemsPerPage = ref(15)
const totalItems = ref(0)
const isAddNewDrawerVisible = ref(false)
const selectedItem = ref(null)

const tableHeaders = [
  { title: 'NAMA', key: 'name' },
  { title: 'JENIS', key: 'type' },
  { title: 'NOMINAL / PERSEN', key: 'amount' },
  { title: 'STATUS', key: 'status', align: 'center' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const snackbar = useSnackbarStore()

const extractArray = val => {
  if (Array.isArray(val)) return val
  if (val && Array.isArray(val.data)) return val.data
  return []
}

const fetchData = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
    }
    
    if (searchQuery.value) params.q = searchQuery.value
    
    const res = await $api('/apps/deduction-types', { query: params })
    items.value = extractArray(res)
    totalItems.value = res.total || items.value.length
  } catch (e) {
    console.error('Failed to load data:', e)
    snackbar.show('Gagal memuat data master potongan', 'error')
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchData()
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

const editItem = item => {
  selectedItem.value = { ...item }
  isAddNewDrawerVisible.value = true
}

const deleteItem = async id => {
  if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) return
  
  try {
    await $api(`/apps/deduction-types/${id}`, { method: 'DELETE' })
    snackbar.show('Data berhasil dihapus', 'success')
    fetchData()
  } catch (e) {
    console.error(e)
    snackbar.show(e.data?.message || 'Gagal menghapus data', 'error')
  }
}

const handleFormSubmit = async data => {
  try {
    const endpoint = data.id ? `/apps/deduction-types/${data.id}` : '/apps/deduction-types'
    const method = data.id ? 'PUT' : 'POST'
    
    await $api(endpoint, {
      method,
      body: data,
    })
    
    snackbar.show(data.id ? 'Data berhasil diperbarui' : 'Data berhasil ditambahkan', 'success')
    isAddNewDrawerVisible.value = false
    fetchData()
  } catch (e) {
    console.error(e)
    snackbar.show(e.data?.message || 'Gagal menyimpan data', 'error')
  }
}

let searchTimeout = null
const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchData()
  }, 500)
}

</script>

<template>
  <section>
    <!-- Header -->
    <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold mb-1 text-high-emphasis">
          Master Potongan & Tunjangan
        </h4>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Kelola referensi komponen penambah atau pengurang gaji.
        </p>
      </div>

      <div class="d-flex align-center gap-3">
        <VBtn
          color="primary"
          prepend-icon="ri-add-line"
          @click="() => { selectedItem = null; isAddNewDrawerVisible = true }"
        >
          Tambah Master
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
              placeholder="Cari nama komponen..."
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
        :items="items"
        :items-length="totalItems"
        :headers="tableHeaders"
        :loading="isLoading"
        class="text-no-wrap rounded-lg"
        @update:options="fetchData"
      >
        <template #item.name="{ item }">
          <span class="font-weight-medium text-high-emphasis">{{ item.name }}</span>
        </template>
        
        <template #item.type="{ item }">
          <VChip size="small" :color="item.type === 'tunjangan' ? 'success' : 'warning'" variant="tonal" class="text-capitalize">
            {{ item.type }}
          </VChip>
        </template>
        
        <template #item.amount="{ item }">
          <span v-if="item.is_percentage" class="font-weight-medium">{{ item.amount }} %</span>
          <span v-else class="font-weight-medium">{{ formatCurrency(item.amount) }}</span>
        </template>

        <template #item.status="{ item }">
          <VChip
            :color="item.status === 'Aktif' ? 'info' : 'error'"
            size="small"
            class="text-capitalize"
            variant="tonal"
          >
            {{ item.status }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <IconBtn @click="editItem(item)" size="small">
            <VIcon icon="ri-edit-box-line" />
            <VTooltip activator="parent" location="top">Edit</VTooltip>
          </IconBtn>
          
          <IconBtn @click="deleteItem(item.id)" color="error" size="small">
            <VIcon icon="ri-delete-bin-line" />
            <VTooltip activator="parent" location="top">Hapus</VTooltip>
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
                @update:model-value="fetchData"
              />
            </div>
          </VCardText>
        </template>
      </VDataTableServer>
    </VCard>

    <AddNewPotonganDrawer
      v-model:isDrawerOpen="isAddNewDrawerVisible"
      :selected-data="selectedItem"
      @submit="handleFormSubmit"
    />
  </section>
</template>
