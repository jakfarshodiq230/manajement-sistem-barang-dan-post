<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { paginationMeta } from '@/utils/paginationMeta'
import { useSnackbarStore } from '@/stores/snackbar'
import AddNewCustomerDrawer from './AddNewCustomerDrawer.vue'
import SimpleConfirmDialog from '@/components/dialogs/SimpleConfirmDialog.vue'
import { $api } from '@/utils/api'

const customers = ref([])
const search = ref('')
const selectedStatus = ref('all')
const isLoading = ref(false)
const totalItems = ref(0)
const page = ref(1)
const itemsPerPage = ref(10)

const isAddNewDrawerVisible = ref(false)
const selectedCustomer = ref(null)

const isConfirmDeleteDialogVisible = ref(false)
const customerToDelete = ref(null)

const snackbar = useSnackbarStore()

const summaryData = ref({
  total: 0,
  active: 0,
  withLimit: 0,
})

const stats = computed(() => {
  const all = customers.value || []
  return {
    total: summaryData.value.total || totalItems.value || all.length,
    active: summaryData.value.active || all.filter(c => c.is_active).length,
    withLimit: summaryData.value.with_limit || all.filter(c => Number(c.credit_limit || 0) > 0).length,
  }
})

const fetchCustomers = async () => {
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

    const data = await $api('/apps/customers', { query: params })

    customers.value = data.data || data
    totalItems.value = data.total || (data.data ? data.data.length : data.length)
    if (data.summary) {
      summaryData.value = data.summary
    }
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mengambil data pelanggan', 'error')
  } finally {
    isLoading.value = false
  }
}

let searchTimeout = null
const handleSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchCustomers()
  }, 400)
}

onMounted(() => {
  fetchCustomers()
})

const formatCurrency = value => {
  if (value === null || value === undefined || isNaN(value)) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value)
}

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
  { title: 'PROFIL PELANGGAN', key: 'name' },
  { title: 'INSTANSI / PERUSAHAAN', key: 'company_name' },
  { title: 'KONTAK & WHATSAPP', key: 'phone', sortable: false },
  { title: 'PLAFON LIMIT PIUTANG', key: 'credit_limit', align: 'center' },
  { title: 'STATUS', key: 'is_active', align: 'center' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const editCustomer = customer => {
  selectedCustomer.value = customer
  isAddNewDrawerVisible.value = true
}

const openDeleteDialog = id => {
  customerToDelete.value = id
  isConfirmDeleteDialogVisible.value = true
}

const executeDeleteCustomer = async isConfirmed => {
  if (!isConfirmed || !customerToDelete.value) return
  try {
    await $api(`/apps/customers/${customerToDelete.value}`, { method: 'DELETE' })
    snackbar.show('Data pelanggan berhasil dihapus', 'success')
    fetchCustomers()
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal menghapus pelanggan. Pastikan tidak ada piutang aktif.', 'error')
  } finally {
    customerToDelete.value = null
  }
}
</script>

<template>
  <div class="pa-4">
    <!-- Header -->
    <div class="d-flex flex-wrap align-center justify-space-between mb-4 gap-4">
      <div>
        <h2 class="text-h4 font-weight-bold mb-1">
          Master Data Pelanggan & Klien
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Kelola kontak pelanggan tetap, riwayat transaksi kasir, plafon limit piutang, dan status keanggotaan.
        </p>
      </div>
      
      <div class="d-flex gap-3">
        <VBtn
          color="secondary"
          variant="tonal"
          prepend-icon="ri-refresh-line"
          :loading="isLoading"
          @click="fetchCustomers"
        >
          Muat Ulang
        </VBtn>

        <VBtn
          color="primary"
          prepend-icon="ri-add-line"
          @click="() => { selectedCustomer = null; isAddNewDrawerVisible = true }"
        >
          Tambah Pelanggan
        </VBtn>
      </div>
    </div>

    <!-- KPI Summary Row -->
    <VRow class="mb-4">
      <VCol cols="12" sm="4">
        <VCard elevation="2" class="pa-4 border-s-lg border-primary">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-primary font-weight-bold">TOTAL PELANGGAN TERDAFTAR</div>
              <div class="text-h4 font-weight-bold text-primary mt-1">{{ stats.total }} <span class="text-caption text-medium-emphasis">Orang/Entitas</span></div>
            </div>
            <VAvatar color="primary" variant="tonal" rounded size="44">
              <VIcon icon="ri-user-smile-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Seluruh database kontak pelanggan</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="4">
        <VCard elevation="2" class="pa-4 border-s-lg border-success">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-success font-weight-bold">PELANGGAN AKTIF</div>
              <div class="text-h4 font-weight-bold text-success mt-1">{{ stats.active }} <span class="text-caption text-medium-emphasis">Aktif</span></div>
            </div>
            <VAvatar color="success" variant="tonal" rounded size="44">
              <VIcon icon="ri-checkbox-circle-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Dapat bertransaksi & kasbon tempo</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="4">
        <VCard elevation="2" class="pa-4 border-s-lg border-warning">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-warning font-weight-bold">DENGAN PLAFON PIUTANG</div>
              <div class="text-h4 font-weight-bold text-warning mt-1">{{ stats.withLimit }} <span class="text-caption text-medium-emphasis">Akun</span></div>
            </div>
            <VAvatar color="warning" variant="tonal" rounded size="44">
              <VIcon icon="ri-wallet-3-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Memiliki batas kredit kasbon</div>
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
              placeholder="Cari nama, perusahaan, telepon..."
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
                { title: 'Aktif Bertransaksi', value: '1' },
                { title: 'Nonaktif / Ditutup', value: '0' }
              ]"
              item-title="title"
              item-value="value"
              density="compact"
              variant="outlined"
              hide-details
              @update:model-value="fetchCustomers"
            />
          </VCol>

          <VCol cols="12" md="5" class="text-right d-none d-md-block">
            <div class="text-caption text-medium-emphasis">
              Total Terdata: <strong>{{ totalItems }}</strong> Pelanggan
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
        :items="customers"
        :items-length="totalItems"
        :loading="isLoading"
        hover
        class="text-no-wrap"
        @update:options="fetchCustomers"
      >
        <!-- Customer Name & Avatar -->
        <template #item.name="{ item }">
          <div class="d-flex align-center py-2">
            <VAvatar
              size="40"
              color="primary"
              variant="tonal"
              class="me-3 rounded-lg border flex-shrink-0"
            >
              <VIcon icon="ri-user-smile-line" size="22" />
            </VAvatar>
            <div>
              <div class="font-weight-bold text-subtitle-2">{{ item.name }}</div>
              <div class="text-caption text-disabled">ID Pelanggan: #{{ item.id }}</div>
            </div>
          </div>
        </template>

        <!-- Company Name -->
        <template #item.company_name="{ item }">
          <div v-if="item.company_name" class="d-flex align-center gap-1">
            <VIcon icon="ri-building-line" size="14" class="text-medium-emphasis" />
            <span class="text-body-2 font-weight-medium">{{ item.company_name }}</span>
          </div>
          <span v-else class="text-disabled text-caption">Perorangan (Individu)</span>
        </template>

        <!-- Phone / WA -->
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

        <!-- Credit Limit -->
        <template #item.credit_limit="{ item }">
          <VChip
            v-if="Number(item.credit_limit || 0) > 0"
            color="warning"
            size="small"
            variant="tonal"
            class="font-weight-bold"
          >
            <VIcon icon="ri-money-dollar-circle-line" size="14" class="me-1" />
            {{ formatCurrency(item.credit_limit) }}
          </VChip>
          <span v-else class="text-disabled text-caption">Tanpa Plafon (Tunai)</span>
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
              title="Edit Data Pelanggan"
              @click="editCustomer(item)"
            />
            <VBtn
              size="small"
              variant="text"
              color="error"
              icon="ri-delete-bin-line"
              title="Hapus Pelanggan"
              @click="openDeleteDialog(item.id)"
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

    <AddNewCustomerDrawer
      v-model:is-drawer-open="isAddNewDrawerVisible"
      :selected-customer="selectedCustomer"
      @customer-data="saveCustomer"
    />

    <SimpleConfirmDialog
      v-model:is-dialog-visible="isConfirmDeleteDialogVisible"
      title="Hapus Data Pelanggan?"
      message="Apakah Anda yakin ingin menghapus data pelanggan ini? Pastikan pelanggan tidak memiliki tagihan piutang aktif yang belum diselesaikan."
      confirm-text="Ya, Hapus"
      cancel-text="Batal"
      @confirm="executeDeleteCustomer"
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
  subject: Data Pelanggan
</route>
