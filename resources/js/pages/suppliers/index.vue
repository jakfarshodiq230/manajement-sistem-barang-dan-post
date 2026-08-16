<script setup>
import { ref, onMounted, computed } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import AddNewSupplierDrawer from './AddNewSupplierDrawer.vue'

const suppliers = ref([])
const search = ref('')
const isLoading = ref(false)
const totalItems = ref(0)
const options = ref({ page: 1, itemsPerPage: 10 })
const isAddNewDrawerVisible = ref(false)
const selectedSupplier = ref(null)

const snackbar = useSnackbarStore()

const fetchSuppliers = async () => {
  isLoading.value = true
  try {
    const data = await $api('/apps/suppliers', {
      params: {
        page: options.value.page,
        itemsPerPage: options.value.itemsPerPage,
        q: search.value,
      },
    })

    suppliers.value = data.data
    totalItems.value = data.total
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mengambil data supplier', 'error')
  } finally {
    isLoading.value = false
  }
}

let searchTimeout = null
watch(search, () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    options.value.page = 1
    fetchSuppliers()
  }, 500)
})

onMounted(() => {
  fetchSuppliers()
})

const saveSupplier = async supplierData => {
  try {
    if (supplierData.id) {
      await $api(`/apps/suppliers/${supplierData.id}`, {
        method: 'PUT',
        body: supplierData,
      })
      snackbar.show('Supplier berhasil diperbarui', 'success')
    } else {
      await $api('/apps/suppliers', {
        method: 'POST',
        body: supplierData,
      })
      snackbar.show('Supplier berhasil ditambahkan', 'success')
    }
    fetchSuppliers()
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal menyimpan supplier', 'error')
  }
}

const tableHeaders = [
  { title: 'NAMA SUPPLIER', key: 'name' },
  { title: 'KONTAK PERSON', key: 'contact_person' },
  { title: 'TELEPON', key: 'phone' },
  { title: 'STATUS', key: 'is_active' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

// Server side search is used instead of computed
const filteredSuppliers = computed(() => suppliers.value)

const editSupplier = supplier => {
  selectedSupplier.value = supplier
  isAddNewDrawerVisible.value = true
}

const confirmDeleteSupplier = async id => {
  if (confirm('Apakah Anda yakin ingin menghapus supplier ini?')) {
    try {
      await $api(`/apps/suppliers/${id}`, { method: 'DELETE' })
      snackbar.show('Supplier berhasil dihapus', 'success')
      fetchSuppliers()
    } catch (error) {
      console.error(error)
      snackbar.show('Gagal menghapus supplier', 'error')
    }
  }
}
</script>

<template>
  <div>
    <p class="text-2xl mb-6">
      Manajemen Supplier (Pemasok)
    </p>

    <!-- Card -->
    <VCard>
      <!-- Card Header -->
      <VCardText class="d-flex flex-wrap gap-4 align-center">
        <VTextField
          v-model="search"
          placeholder="Cari nama supplier..."
          density="compact"
          style="max-width: 300px;"
          hide-details
        />
        
        <VSpacer />

        <div class="d-flex gap-4">
          <VBtn
            v-if="$can('create', 'Data Supplier')"
            color="primary"
            prepend-icon="ri-add-line"
            @click="() => { selectedSupplier = null; isAddNewDrawerVisible = true }"
          >
            Tambah Supplier
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <!-- Data Table -->
      <VDataTableServer
        v-model:options="options"
        :headers="tableHeaders"
        :items="suppliers"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        @update:options="fetchSuppliers"
      >
        <template #item.is_active="{ item }">
          <VChip
            :color="item.is_active ? 'success' : 'error'"
            size="small"
          >
            {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <IconBtn
            v-if="$can('write', 'Data Supplier')"
            size="small"
            @click="editSupplier(item)"
          >
            <VIcon icon="ri-pencil-line" />
          </IconBtn>
          <IconBtn
            v-if="$can('delete', 'Data Supplier')"
            size="small"
            color="error"
            @click="confirmDeleteSupplier(item.id)"
          >
            <VIcon icon="ri-delete-bin-line" />
          </IconBtn>
        </template>
      </VDataTableServer>
    </VCard>

    <AddNewSupplierDrawer
      v-model:is-drawer-open="isAddNewDrawerVisible"
      :selected-supplier="selectedSupplier"
      @save-data="saveSupplier"
    />
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Data Supplier
</route>
