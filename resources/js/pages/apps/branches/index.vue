<script setup>
import { ref, computed, onMounted } from 'vue'
import AddNewBranchDrawer from './AddNewBranchDrawer.vue'
import SimpleConfirmDialog from '@/components/dialogs/SimpleConfirmDialog.vue'
import { useSnackbarStore } from '@/stores/snackbar'

definePage({
  meta: {
    public: true,
  },
})

const branches = ref([])
const owners = ref([])
const search = ref('')
const selectedType = ref('all')
const selectedStatus = ref('all')
const isDrawerOpen = ref(false)
const selectedBranch = ref(null)

const isConfirmDeleteDialogVisible = ref(false)
const branchToDelete = ref(null)

const isLoading = ref(false)

// Pagination
const page = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)
let searchTimeout = null

const snackbar = useSnackbarStore()

const stats = computed(() => {
  const all = branches.value || []
  const total = totalItems.value || all.length
  const stores = all.filter(b => b.type === 'store').length
  const warehouses = all.filter(b => b.type === 'warehouse').length
  const active = all.filter(b => (b.status || 'Aktif') === 'Aktif').length
  
  return { total, stores, warehouses, active }
})

const fetchBranches = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
    }
    
    if (search.value) {
      params.search = search.value
    }
    if (selectedType.value !== 'all') {
      params.type = selectedType.value
    }
    if (selectedStatus.value !== 'all') {
      params.is_active = selectedStatus.value === 'Aktif' ? 1 : 0
    }
    
    const data = await $api('/apps/branches', { query: params })

    branches.value = data.data || data
    if (data.total !== undefined) {
      totalItems.value = data.total
    }
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal memuat data cabang', 'error')
  } finally {
    isLoading.value = false
  }
}

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchBranches()
  }, 400)
}

const fetchOwners = async () => {
  try {
    const data = await $api('/apps/owners')
    owners.value = data.data || data
  } catch (error) {
    console.error(error)
  }
}

onMounted(() => {
  fetchBranches()
  fetchOwners()
})

const openAddDrawer = () => {
  selectedBranch.value = null
  isDrawerOpen.value = true
}

const editBranch = branch => {
  selectedBranch.value = branch
  isDrawerOpen.value = true
}

const confirmDeleteBranch = id => {
  branchToDelete.value = id
  isConfirmDeleteDialogVisible.value = true
}

const executeDeleteBranch = async isConfirmed => {
  if (!isConfirmed) return
  
  try {
    const res = await $api(`/apps/branches/${branchToDelete.value}`, { method: 'DELETE' })
    if (res.message) {
      snackbar.show(res.message, 'warning')
    } else {
      snackbar.show('Cabang berhasil diperbarui statusnya / dihapus', 'success')
    }
    fetchBranches()
  } catch (error) {
    console.error(error)
    snackbar.show('Terjadi kesalahan saat menghapus/menutup cabang', 'error')
  } finally {
    branchToDelete.value = null
  }
}

const saveBranch = async branchData => {
  try {
    const formData = new FormData()
    for (const key in branchData) {
      if (branchData[key] !== null && branchData[key] !== undefined) {
        formData.append(key, branchData[key])
      }
    }

    if (branchData.id) {
      formData.append('_method', 'PUT')
      await $api(`/apps/branches/${branchData.id}`, {
        method: 'POST',
        body: formData,
      })
      snackbar.show('Data cabang berhasil diperbarui', 'success')
    } else {
      await $api('/apps/branches', {
        method: 'POST',
        body: formData,
      })
      snackbar.show('Cabang baru berhasil ditambahkan', 'success')
    }
    fetchBranches()
  } catch (error) {
    console.error(error)
    snackbar.show('Terjadi kesalahan saat menyimpan data cabang', 'error')
  }
}

const tableHeaders = [
  { title: 'IDENTITAS CABANG', key: 'name' },
  { title: 'TIPE', key: 'type', align: 'center' },
  { title: 'OWNER / PEMILIK', key: 'owner.name' },
  { title: 'KONTAK RESMI', key: 'contact', sortable: false },
  { title: 'ALAMAT LOKASI', key: 'address', sortable: false },
  { title: 'STATUS', key: 'status', align: 'center' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const filteredBranches = computed(() => {
  let result = branches.value || []
  if (selectedType.value !== 'all') {
    result = result.filter(b => b.type === selectedType.value)
  }
  if (selectedStatus.value !== 'all') {
    result = result.filter(b => (b.status || 'Aktif') === selectedStatus.value)
  }
  return result
})
</script>

<template>
  <div class="pa-4">
    <!-- Header -->
    <div class="d-flex flex-wrap align-center justify-space-between mb-4 gap-4">
      <div>
        <h2 class="text-h4 font-weight-bold mb-1">
          Manajemen Cabang & Jaringan Toko
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Kelola entitas cabang toko ritel, gudang distribusi terpusat, penugasan owner, dan status operasional.
        </p>
      </div>
      
      <div class="d-flex gap-3">
        <VBtn
          color="secondary"
          variant="tonal"
          prepend-icon="ri-refresh-line"
          :loading="isLoading"
          @click="fetchBranches"
        >
          Muat Ulang
        </VBtn>

        <VBtn
          color="primary"
          prepend-icon="ri-add-line"
          @click="openAddDrawer"
        >
          Tambah Cabang Baru
        </VBtn>
      </div>
    </div>

    <!-- KPI Summary Row -->
    <VRow class="mb-4">
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-primary">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-primary font-weight-bold">TOTAL JARINGAN</div>
              <div class="text-h4 font-weight-bold text-primary mt-1">{{ stats.total }} <span class="text-caption text-medium-emphasis">Lokasi</span></div>
            </div>
            <VAvatar color="primary" variant="tonal" rounded size="44">
              <VIcon icon="ri-store-3-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Seluruh unit toko & gudang</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-info">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-info font-weight-bold">CABANG TOKO (STORE)</div>
              <div class="text-h4 font-weight-bold text-info mt-1">{{ stats.stores }} <span class="text-caption text-medium-emphasis">Toko</span></div>
            </div>
            <VAvatar color="info" variant="tonal" rounded size="44">
              <VIcon icon="ri-shopping-bag-3-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Unit gerai kasir & transaksi POS</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-warning">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-warning font-weight-bold">GUDANG (WAREHOUSE)</div>
              <div class="text-h4 font-weight-bold text-warning mt-1">{{ stats.warehouses }} <span class="text-caption text-medium-emphasis">Gudang</span></div>
            </div>
            <VAvatar color="warning" variant="tonal" rounded size="44">
              <VIcon icon="ri-building-2-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Pusat logistik & buffer stok</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-success">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-success font-weight-bold">STATUS AKTIF</div>
              <div class="text-h4 font-weight-bold text-success mt-1">{{ stats.active }} <span class="text-caption text-medium-emphasis">Aktif</span></div>
            </div>
            <VAvatar color="success" variant="tonal" rounded size="44">
              <VIcon icon="ri-checkbox-circle-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Siap melayani operasional</div>
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
              placeholder="Cari nama cabang, kontak, alamat..."
              density="compact"
              variant="outlined"
              hide-details
              clearable
              @update:model-value="handleSearch"
            />
          </VCol>

          <VCol cols="12" sm="6" md="3">
            <VSelect
              v-model="selectedType"
              :items="[
                { title: 'Semua Tipe Cabang', value: 'all' },
                { title: 'Toko (Store)', value: 'store' },
                { title: 'Gudang (Warehouse)', value: 'warehouse' }
              ]"
              item-title="title"
              item-value="value"
              density="compact"
              variant="outlined"
              hide-details
              @update:model-value="fetchBranches"
            />
          </VCol>

          <VCol cols="12" sm="6" md="3">
            <VSelect
              v-model="selectedStatus"
              :items="[
                { title: 'Semua Status', value: 'all' },
                { title: 'Aktif Beroperasi', value: 'Aktif' },
                { title: 'Tutup / Nonaktif', value: 'Tutup' }
              ]"
              item-title="title"
              item-value="value"
              density="compact"
              variant="outlined"
              hide-details
              @update:model-value="fetchBranches"
            />
          </VCol>

          <VCol cols="12" md="2" class="text-right d-none d-md-block">
            <div class="text-caption text-medium-emphasis">
              Total: <strong>{{ totalItems }}</strong> Cabang
            </div>
          </VCol>
        </VRow>
      </VCardItem>

      <VDivider />

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="tableHeaders"
        :items="filteredBranches"
        :items-length="totalItems"
        :loading="isLoading"
        hover
        class="text-no-wrap"
        @update:options="fetchBranches"
      >
        <!-- Name & Logo -->
        <template #item.name="{ item }">
          <div class="d-flex align-center py-2">
            <VAvatar
              size="40"
              :color="item.type === 'warehouse' ? 'warning' : 'primary'"
              variant="tonal"
              class="me-3 rounded-lg border"
            >
              <VImg
                v-if="item.logo"
                :src="`/storage/${item.logo}`"
                alt="Logo"
                cover
              />
              <VIcon
                v-else
                :icon="item.type === 'warehouse' ? 'ri-building-2-line' : 'ri-store-2-line'"
                size="22"
              />
            </VAvatar>
            <div>
              <div
                class="font-weight-bold text-subtitle-2"
                :class="{'text-decoration-line-through text-disabled': item.status === 'Tutup'}"
              >
                {{ item.name }}
              </div>
              <div class="text-caption text-disabled">ID: #{{ item.id }}</div>
            </div>
          </div>
        </template>

        <!-- Type -->
        <template #item.type="{ item }">
          <VChip
<<<<<<< HEAD
            :color="item?.type === 'warehouse' ? 'warning' : 'primary'"
=======
            :color="item.type === 'warehouse' ? 'warning' : 'info'"
>>>>>>> 637f98f (feat(master-data): Peningkatan Modul Master Data Owner, Cabang, Karyawan, Produk & Supplier)
            size="small"
            variant="tonal"
            class="font-weight-bold"
          >
<<<<<<< HEAD
            {{ item?.type === 'warehouse' ? 'Gudang' : 'Toko' }}
=======
            <VIcon
              :icon="item.type === 'warehouse' ? 'ri-building-2-line' : 'ri-shopping-cart-2-line'"
              size="14"
              class="me-1"
            />
            {{ item.type === 'warehouse' ? 'Gudang' : 'Toko' }}
>>>>>>> 637f98f (feat(master-data): Peningkatan Modul Master Data Owner, Cabang, Karyawan, Produk & Supplier)
          </VChip>
        </template>

        <!-- Owner -->
        <template #item.owner.name="{ item }">
          <div v-if="item.owner" class="d-flex align-center gap-2">
            <VAvatar color="secondary" variant="tonal" size="28">
              <VIcon icon="ri-user-3-line" size="16" />
            </VAvatar>
            <div>
              <div
                class="font-weight-medium text-body-2"
                :class="{'text-disabled': item.status === 'Tutup'}"
              >
                {{ item.owner.name }}
              </div>
              <span
                v-if="!item.owner.parent_id"
                class="text-caption text-success font-weight-medium"
              >
                Owner Utama
              </span>
              <span
                v-else
                class="text-caption text-medium-emphasis"
              >
                Sub-Owner
              </span>
            </div>
          </div>
          <span
            v-else
            class="text-disabled italic text-caption"
          >
            Belum ditentukan
          </span>
        </template>

        <!-- Contact -->
        <template #item.contact="{ item }">
          <div
            class="d-flex flex-column gap-1"
            :class="{'text-disabled': item.status === 'Tutup'}"
          >
            <div class="text-caption d-flex align-center">
              <VIcon size="14" icon="ri-mail-line" class="me-1 text-primary" />
              <span>{{ item.email || '-' }}</span>
            </div>
            <div class="text-caption d-flex align-center">
              <VIcon size="14" icon="ri-phone-line" class="me-1 text-success" />
              <span>{{ item.phone || '-' }}</span>
            </div>
          </div>
        </template>

        <!-- Address -->
        <template #item.address="{ item }">
          <div class="d-flex align-start text-caption" style="max-width: 250px;">
            <VIcon size="14" icon="ri-map-pin-line" class="me-1 text-error flex-shrink-0 mt-1" />
            <span
              class="text-wrap"
              :class="{'text-disabled': item.status === 'Tutup'}"
            >
              {{ item.address || '-' }}
            </span>
          </div>
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
              v-if="$can('write', 'Cabang')"
              size="small"
              variant="text"
              color="primary"
              icon="ri-edit-box-line"
              title="Edit Cabang"
              @click="editBranch(item)"
            />
            <VBtn
              size="small"
              variant="text"
              color="error"
              :icon="item.status === 'Aktif' ? 'ri-close-circle-line' : 'ri-delete-bin-line'"
              :title="item.status === 'Aktif' ? 'Tutup Operasional Cabang' : 'Hapus Data Cabang'"
              @click="confirmDeleteBranch(item.id)"
            />
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <AddNewBranchDrawer
      v-model:is-drawer-open="isDrawerOpen"
      :selected-branch="selectedBranch"
      :owners-list="owners"
      @branch-data="saveBranch"
    />

    <SimpleConfirmDialog
      v-model:is-dialog-visible="isConfirmDeleteDialogVisible"
      title="Tutup / Hapus Cabang?"
      message="Peringatan: Menghapus cabang dapat berdampak pada riwayat transaksi terkait. Apakah Anda yakin ingin memproses aksi ini? Disarankan untuk mengubah status menjadi 'Tutup' jika cabang sudah memiliki transaksi riil."
      confirm-text="Ya, Lanjutkan"
      cancel-text="Batal"
      @confirm="executeDeleteBranch"
    />
  </div>
</template>
