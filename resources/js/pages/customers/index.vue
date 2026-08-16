<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import AddNewCustomerDrawer from './AddNewCustomerDrawer.vue'
import { $api } from '@/utils/api' // Adjust import depending on their global $api setup

const customers = ref([])
const search = ref('')
const isLoading = ref(false)
const totalItems = ref(0)
const options = ref({ page: 1, itemsPerPage: 10 })
const isAddNewDrawerVisible = ref(false)
const selectedCustomer = ref(null)

const snackbar = useSnackbarStore()

const fetchCustomers = async () => {
  isLoading.value = true
  try {
    const data = await $api('/apps/customers', {
      params: {
        page: options.value.page,
        itemsPerPage: options.value.itemsPerPage,
        q: search.value,
      },
    })


    // Depending on pagination structure
    customers.value = data.data || data
    totalItems.value = data.total || (data.data ? data.data.length : data.length)
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mengambil data pelanggan', 'error')
  } finally {
    isLoading.value = false
  }
}

let searchTimeout = null
watch(search, () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    options.value.page = 1
    fetchCustomers()
  }, 500)
})

onMounted(() => {
  fetchCustomers()
})

const saveCustomer = async customerData => {
  try {
    if (customerData.id) {
      await $api(`/apps/customers/${customerData.id}`, {
        method: 'PUT',
        body: customerData,
      })
      snackbar.show('Data pelanggan berhasil diperbarui', 'success')
    } else {
      await $api('/apps/customers', {
        method: 'POST',
        body: customerData,
      })
      snackbar.show('Pelanggan berhasil ditambahkan', 'success')
    }
    fetchCustomers()
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal menyimpan data pelanggan', 'error')
  }
}

const tableHeaders = [
  { title: 'NAMA PELANGGAN', key: 'name' },
  { title: 'PERUSAHAAN', key: 'company_name' },
  { title: 'TELEPON', key: 'phone' },
  { title: 'LIMIT PIUTANG', key: 'credit_limit' },
  { title: 'STATUS', key: 'is_active' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const formatCurrency = value => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value || 0)
}

const editCustomer = customer => {
  selectedCustomer.value = customer
  isAddNewDrawerVisible.value = true
}

const confirmDeleteCustomer = async id => {
  if (confirm('Apakah Anda yakin ingin menghapus pelanggan ini?')) {
    try {
      await $api(`/apps/customers/${id}`, { method: 'DELETE' })
      snackbar.show('Pelanggan berhasil dihapus', 'success')
      fetchCustomers()
    } catch (error) {
      console.error(error)
      snackbar.show('Gagal menghapus pelanggan', 'error')
    }
  }
}
</script>

<template>
  <div>
    <p class="text-2xl mb-6">
      Master Data Pelanggan
    </p>

    <!-- Card -->
    <VCard>
      <!-- Card Header -->
      <VCardText class="d-flex flex-wrap gap-4 align-center">
        <VTextField
          v-model="search"
          placeholder="Cari pelanggan..."
          density="compact"
          style="max-width: 300px;"
          hide-details
        />
        
        <VSpacer />

        <div class="d-flex gap-4">
          <VBtn
            color="primary"
            prepend-icon="ri-add-line"
            @click="() => { selectedCustomer = null; isAddNewDrawerVisible = true }"
          >
            Tambah Pelanggan
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <!-- Data Table -->
      <VDataTableServer
        v-model:options="options"
        :headers="tableHeaders"
        :items="customers"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        @update:options="fetchCustomers"
      >
        <template #item.credit_limit="{ item }">
          {{ formatCurrency(item.credit_limit) }}
        </template>
        
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
            size="small"
            @click="editCustomer(item)"
          >
            <VIcon icon="ri-pencil-line" />
          </IconBtn>
          <IconBtn
            size="small"
            color="error"
            @click="confirmDeleteCustomer(item.id)"
          >
            <VIcon icon="ri-delete-bin-line" />
          </IconBtn>
        </template>
      </VDataTableServer>
    </VCard>

    <AddNewCustomerDrawer
      v-model:is-drawer-open="isAddNewDrawerVisible"
      :selected-customer="selectedCustomer"
      @save-data="saveCustomer"
    />
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Data Pelanggan
</route>
