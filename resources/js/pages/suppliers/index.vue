<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import AddNewSupplierDrawer from './AddNewSupplierDrawer.vue'
import SimpleConfirmDialog from '@/components/dialogs/SimpleConfirmDialog.vue'

const suppliers = ref([])
const search = ref('')
const selectedStatus = ref('all')
const isLoading = ref(false)
const totalItems = ref(0)
const page = ref(1)
const itemsPerPage = ref(10)

const isAddNewDrawerVisible = ref(false)
const selectedSupplier = ref(null)

const isConfirmDeleteDialogVisible = ref(false)
const supplierToDelete = ref(null)

const snackbar = useSnackbarStore()

const summaryData = ref({
  total: 0,
  active: 0,
  inactive: 0,
})

const stats = computed(() => {
  const all = suppliers.value || []
  return {
    total: summaryData.value.total || totalItems.value || all.length,
    active: summaryData.value.active || all.filter(s => s.is_active).length,
    inactive: summaryData.value.inactive || all.filter(s => !s.is_active).length,
  }
})

const fetchSuppliers = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      q: search.value || undefined,
    }
    if (selectedStatus.value !== 'all') {
      params.is_active = selectedStatus.value === '1' ? 1 : 0
    }

    const data = await $api('/apps/suppliers', { query: params })

    suppliers.value = data.data || data
    totalItems.value = data.total || (data.data ? data.data.length : 0)
    if (data.summary) {
      summaryData.value = data.summary
    }
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mengambil data supplier', 'error')
  } finally {
    isLoading.value = false
  }
}

let searchTimeout = null
const handleSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchSuppliers()
  }, 400)
}

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
      snackbar.show('Data supplier berhasil diperbarui', 'success')
    } else {
      await $api('/apps/suppliers', {
        method: 'POST',
        body: supplierData,
      })
      snackbar.show('Supplier baru berhasil ditambahkan', 'success')
    }
    fetchSuppliers()
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal menyimpan supplier', 'error')
  }
}

const tableHeaders = [
  { title: 'IDENTITAS SUPPLIER / VENDOR', key: 'name' },
  { title: 'CONTACT PERSON', key: 'contact_person' },
  { title: 'KONTAK & TELEPON', key: 'phone', sortable: false },
  { title: 'ALAMAT GUDANG / KANTOR', key: 'address', sortable: false },
  { title: 'STATUS', key: 'is_active', align: 'center' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const editSupplier = supplier => {
  selectedSupplier.value = supplier
  isAddNewDrawerVisible.value = true
}

const openDeleteDialog = id => {
  supplierToDelete.value = id
  isConfirmDeleteDialogVisible.value = true
}

const executeDeleteSupplier = async isConfirmed => {
  if (!isConfirmed || !supplierToDelete.value) return
  try {
    await $api(`/apps/suppliers/${supplierToDelete.value}`, { method: 'DELETE' })
    snackbar.show('Supplier berhasil dihapus dari sistem', 'success')
    fetchSuppliers()
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal menghapus supplier', 'error')
  } finally {
    supplierToDelete.value = null
  }
}
</script>

<template>
  <div class="pa-4">
    <!-- Header -->
    <div class="d-flex flex-wrap align-center justify-space-between mb-4 gap-4">
      <div>
        <h2 class="text-h4 font-weight-bold mb-1">
          Manajemen Data Supplier & Vendor
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Kelola mitra penyedia pasokan barang, contact person, alamat pengiriman, dan riwayat purchase order.
        </p>
      </div>
      
      <div class="d-flex gap-3">
        <VBtn
          color="secondary"
          variant="tonal"
          prepend-icon="ri-refresh-line"
          :loading="isLoading"
          @click="fetchSuppliers"
        >
          Muat Ulang
        </VBtn>

        <VBtn
          color="primary"
          prepend-icon="ri-add-line"
          @click="() => { selectedSupplier = null; isAddNewDrawerVisible = true }"
        >
          Tambah Supplier
        </VBtn>
      </div>
    </div>

    <!-- KPI Summary Row -->
    <VRow class="mb-4">
      <VCol cols="12" sm="4">
        <VCard elevation="2" class="pa-4 border-s-lg border-primary">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-primary font-weight-bold">TOTAL VENDOR MITRA</div>
              <div class="text-h4 font-weight-bold text-primary mt-1">{{ stats.total }} <span class="text-caption text-medium-emphasis">Supplier</span></div>
            </div>
            <VAvatar color="primary" variant="tonal" rounded size="44">
              <VIcon icon="ri-truck-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Seluruh rekanan pengadaan</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="4">
        <VCard elevation="2" class="pa-4 border-s-lg border-success">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-success font-weight-bold">SUPPLIER AKTIF</div>
              <div class="text-h4 font-weight-bold text-success mt-1">{{ stats.active }} <span class="text-caption text-medium-emphasis">Aktif</span></div>
            </div>
            <VAvatar color="success" variant="tonal" rounded size="44">
              <VIcon icon="ri-checkbox-circle-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Siap untuk Purchase Order (PO)</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="4">
        <VCard elevation="2" class="pa-4 border-s-lg border-secondary">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-secondary font-weight-bold">SUPPLIER NONAKTIF</div>
              <div class="text-h4 font-weight-bold text-secondary mt-1">{{ stats.inactive }} <span class="text-caption text-medium-emphasis">Arsip</span></div>
            </div>
            <VAvatar color="secondary" variant="tonal" rounded size="44">
              <VIcon icon="ri-archive-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Tidak menerima order baru</div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Main Table Card -->
    <VCard elevation="2">
      <!-- Card Toolbar -->
      <VCardItem class="pa-4">
        <VRow align="center">
          <VCol cols="12" sm="6" md="4">
            <VTextField
              v-model="search"
              prepend-inner-icon="ri-search-line"
              placeholder="Cari nama supplier, CP, nomor telp..."
              density="compact"
              variant="outlined"
              hide-details
              clearable
              @update:model-value="handleSearch"
            />
          </VCol>

          <VCol cols="12" sm="6" md="3">
            <VSelect
              v-model="selectedStatus"
              :items="[
                { title: 'Semua Status', value: 'all' },
                { title: 'Aktif Bekerja Sama', value: '1' },
                { title: 'Nonaktif / Ditutup', value: '0' }
              ]"
              item-title="title"
              item-value="value"
              density="compact"
              variant="outlined"
              hide-details
              @update:model-value="fetchSuppliers"
            />
          </VCol>

          <VCol cols="12" md="5" class="text-right d-none d-md-block">
            <div class="text-caption text-medium-emphasis">
              Total Terdata: <strong>{{ totalItems }}</strong> Rekanan
            </div>
          </VCol>
        </VRow>
      </VCardItem>

      <VDivider />

      <!-- Data Table -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="tableHeaders"
        :items="suppliers"
        :items-length="totalItems"
        :loading="isLoading"
        hover
        class="text-no-wrap"
        @update:options="fetchSuppliers"
      >
        <!-- Supplier Name -->
        <template #item.name="{ item }">
          <div class="d-flex align-center py-2">
            <VAvatar
              size="40"
              color="primary"
              variant="tonal"
              class="me-3 rounded-lg border flex-shrink-0"
            >
              <VIcon icon="ri-truck-line" size="22" />
            </VAvatar>
            <div>
              <div class="font-weight-bold text-subtitle-2">{{ item.name }}</div>
              <div class="text-caption text-disabled">ID Vendor: #{{ item.id }}</div>
            </div>
          </div>
        </template>

        <!-- Contact Person -->
        <template #item.contact_person="{ item }">
          <div class="d-flex align-center gap-2">
            <VAvatar color="secondary" variant="tonal" size="28">
              <VIcon icon="ri-user-line" size="14" />
            </VAvatar>
            <span class="text-body-2 font-weight-medium">{{ item.contact_person || '-' }}</span>
          </div>
        </template>

        <!-- Phone & Email -->
        <template #item.phone="{ item }">
          <div class="d-flex flex-column gap-1">
            <div class="text-caption d-flex align-center">
              <VIcon size="14" icon="ri-phone-line" class="me-1 text-success" />
              <span>{{ item.phone || '-' }}</span>
            </div>
            <div v-if="item.email" class="text-caption d-flex align-center">
              <VIcon size="14" icon="ri-mail-line" class="me-1 text-primary" />
              <span>{{ item.email }}</span>
            </div>
          </div>
        </template>

        <!-- Address -->
        <template #item.address="{ item }">
          <div class="d-flex align-start text-caption" style="max-width: 250px;">
            <VIcon size="14" icon="ri-map-pin-line" class="me-1 text-error flex-shrink-0 mt-1" />
            <span class="text-wrap">{{ item.address || '-' }}</span>
          </div>
        </template>

        <!-- Status -->
        <template #item.is_active="{ item }">
          <VChip
            :color="item.is_active ? 'success' : 'secondary'"
            size="small"
            variant="elevated"
            class="font-weight-bold"
          >
            <VIcon
              :icon="item.is_active ? 'ri-checkbox-circle-fill' : 'ri-close-circle-fill'"
              size="14"
              class="me-1"
            />
            {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
          </VChip>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex align-center justify-center gap-1">
            <VBtn
              size="small"
              variant="text"
              color="primary"
              icon="ri-edit-box-line"
              title="Edit Supplier"
              @click="editSupplier(item)"
            />
            <VBtn
              size="small"
              variant="text"
              color="error"
              icon="ri-delete-bin-line"
              title="Hapus Supplier"
              @click="openDeleteDialog(item.id)"
            />
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <AddNewSupplierDrawer
      v-model:is-drawer-open="isAddNewDrawerVisible"
      :selected-supplier="selectedSupplier"
      @supplier-data="saveSupplier"
    />

    <SimpleConfirmDialog
      v-model:is-dialog-visible="isConfirmDeleteDialogVisible"
      title="Hapus Data Supplier?"
      message="Apakah Anda yakin ingin menghapus supplier ini? Tindakan ini tidak dapat dibatalkan jika supplier memiliki riwayat PO terkait."
      confirm-text="Ya, Hapus"
      cancel-text="Batal"
      @confirm="executeDeleteSupplier"
    />
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Data Supplier
</route>
