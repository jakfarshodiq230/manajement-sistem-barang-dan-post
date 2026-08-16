<script setup>
import { ref, computed, onMounted } from 'vue'
import AddNewBranchDrawer from './AddNewBranchDrawer.vue'
import SimpleConfirmDialog from '@/components/dialogs/SimpleConfirmDialog.vue'
import { useSnackbarStore } from '@/stores/snackbar'

const branches = ref([])
const owners = ref([])
const search = ref('')
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
  }, 500)
}

const fetchOwners = async () => {
  try {
    const data = await $api('/apps/owners')

    owners.value = data
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
      snackbar.show('Cabang berhasil dihapus', 'success')
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
  { title: 'NAMA CABANG', key: 'name' },
  { title: 'TIPE CABANG', key: 'type' },
  { title: 'OWNER / PEMILIK', key: 'owner.name' },
  { title: 'KONTAK', key: 'contact', sortable: false },
  { title: 'ALAMAT', key: 'address', sortable: false },
  { title: 'STATUS', key: 'status' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const filteredBranches = computed(() => {
  return branches.value
})
</script>

<template>
  <section>
    <!-- Page Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="text-h4 mb-0">
          Manajemen Cabang
        </h2>
        <p class="text-body-1 mb-0 text-disabled mt-1">
          Kelola daftar cabang bisnis Anda dan penugasannya ke para Owner.
        </p>
      </div>
      
      <div class="d-flex gap-4">
        <VBtn
          prepend-icon="ri-store-2-line"
          @click="openAddDrawer"
        >
          Tambah Cabang
        </VBtn>
      </div>
    </div>

    <VCard>
      <VCardItem class="pa-4 pb-0">
        <div class="d-flex align-center justify-space-between w-100">
          <VCardTitle class="px-0">
            Daftar Cabang
          </VCardTitle>
          <div style="width: 250px;">
            <VTextField
              v-model="search"
              prepend-inner-icon="ri-search-line"
              placeholder="Cari cabang atau owner..."
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
        :items="filteredBranches"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        @update:options="fetchBranches"
      >
        <template #item.name="{ item }">
          <div class="d-flex align-center">
            <VAvatar
              size="34"
              color="info"
              variant="tonal"
              class="mr-3"
            >
              <VImg
                v-if="item.logo"
                :src="`/storage/${item.logo}`"
                alt="Logo"
                cover
              />
              <VIcon
                v-else
                icon="ri-store-2-line"
              />
            </VAvatar>
            <div>
              <h6
                class="text-h6 font-weight-medium mb-0"
                :class="{'text-decoration-line-through text-disabled': item.status === 'Tutup'}"
              >
                {{ item.name }}
              </h6>
            </div>
          </div>
        </template>

        <template #item.type="{ item }">
          <VChip
            :color="item?.type === 'warehouse' ? 'warning' : 'primary'"
            size="small"
            class="text-capitalize"
          >
            {{ item?.type === 'warehouse' ? 'Gudang' : 'Toko' }}
          </VChip>
        </template>

        <template #item.owner.name="{ item }">
          <div v-if="item.owner">
            <span
              class="font-weight-medium"
              :class="{'text-disabled': item.status === 'Tutup'}"
            >{{ item.owner.name }}</span>
            <div
              v-if="item.owner.parent_id"
              class="text-caption text-disabled"
            >
              Sub-Owner
            </div>
            <div
              v-else
              class="text-caption text-success font-weight-bold"
            >
              Owner Utama
            </div>
          </div>
          <span
            v-else
            class="text-disabled italic"
          >Belum ditentukan</span>
        </template>

        <template #item.contact="{ item }">
          <div
            class="d-flex flex-column"
            :class="{'text-disabled': item.status === 'Tutup'}"
          >
            <span class="text-body-2 d-flex align-center"><VIcon
              size="16"
              icon="ri-mail-line"
              class="mr-1"
            /> {{ item.email || '-' }}</span>
            <span class="text-body-2 d-flex align-center mt-1"><VIcon
              size="16"
              icon="ri-phone-line"
              class="mr-1"
            /> {{ item.phone || '-' }}</span>
          </div>
        </template>

        <template #item.address="{ item }">
          <span
            class="text-body-2 text-wrap"
            style="max-width: 300px; display: inline-block;"
            :class="{'text-disabled': item.status === 'Tutup'}"
          >
            {{ item.address || '-' }}
          </span>
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
            v-if="$can('write', 'Cabang')"
            size="small"
            @click="editBranch(item)"
          >
            <VIcon icon="ri-pencil-line" />
          </IconBtn>
          <IconBtn
            size="small"
            color="error"
            title="Tutup / Hapus Cabang"
            @click="confirmDeleteBranch(item.id)"
          >
            <VIcon
              v-if="item.status === 'Aktif'"
              icon="ri-close-circle-line"
            />
            <VIcon
              v-else
              icon="ri-delete-bin-line"
            />
          </IconBtn>
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
      title="Hapus Cabang?"
      message="Peringatan: Menghapus cabang bisa merusak data transaksi yang berkaitan. Apakah Anda yakin ingin menghapusnya secara permanen? Jika tidak, lebih baik ubah statusnya menjadi Tutup."
      confirm-text="Ya, Hapus"
      cancel-text="Batal"
      @confirm="executeDeleteBranch"
    />
  </section>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Cabang
</route>
