<script setup>
import { ref, computed, onMounted } from 'vue'
import AddNewOwnerDrawer from './AddNewOwnerDrawer.vue'
import SimpleConfirmDialog from '@/components/dialogs/SimpleConfirmDialog.vue'
import { useSnackbarStore } from '@/stores/snackbar'

const owners = ref([])
const search = ref('')
const isLoading = ref(false)

// Pagination
const page = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)
let searchTimeout = null
const isDrawerOpen = ref(false)
const selectedOwner = ref(null)

const isConfirmDeleteDialogVisible = ref(false)
const ownerToDelete = ref(null)

const snackbar = useSnackbarStore()

const fetchOwners = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
    }
    
    if (search.value) {
      params.search = search.value
    }
    
    const data = await $api('/apps/owners', { query: params })

    owners.value = data.data || data
    if (data.total !== undefined) {
      totalItems.value = data.total
    }
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal memuat data owner', 'error')
  } finally {
    isLoading.value = false
  }
}

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchOwners()
  }, 500)
}

onMounted(() => {
  fetchOwners()
})

const openAddDrawer = () => {
  selectedOwner.value = null
  isDrawerOpen.value = true
}

const editOwner = owner => {
  selectedOwner.value = owner
  isDrawerOpen.value = true
}

const confirmDeleteOwner = id => {
  ownerToDelete.value = id
  isConfirmDeleteDialogVisible.value = true
}

const executeDeleteOwner = async isConfirmed => {
  if (!isConfirmed) return
  
  try {
    await $api(`/apps/owners/${ownerToDelete.value}`, { method: 'DELETE' })
    snackbar.show('Owner berhasil dihapus', 'success')
    fetchOwners()
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal menghapus owner', 'error')
  } finally {
    ownerToDelete.value = null
  }
}

const saveOwner = async ownerData => {
  try {
    const formData = new FormData()
    for (const key in ownerData) {
      if (ownerData[key] !== null && ownerData[key] !== undefined) {
        formData.append(key, ownerData[key])
      }
    }

    if (ownerData.id) {
      formData.append('_method', 'PUT')
      await $api(`/apps/owners/${ownerData.id}`, {
        method: 'POST',
        body: formData,
      })
      snackbar.show('Data owner diperbarui', 'success')
    } else {
      await $api('/apps/owners', {
        method: 'POST',
        body: formData,
      })
      snackbar.show('Owner baru ditambahkan', 'success')
    }
    fetchOwners()
  } catch (error) {
    console.error(error)
    snackbar.show('Terjadi kesalahan saat menyimpan data owner', 'error')
  }
}

const tableHeaders = [
  { title: 'NAMA OWNER / PERUSAHAAN', key: 'name' },
  { title: 'KONTAK', key: 'contact', sortable: false },
  { title: 'CABANG', key: 'branches_count', sortable: false },
  { title: 'STATUS', key: 'status' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

// To display flat table but clearly indicate sub-owners
const flatTableData = computed(() => {
  return owners.value.map(item => {
    return {
      ...item,
      is_sub: !!item.parent_id,
      parent_name: item.parent?.name || 'Owner Utama',
    }
  })
})
</script>

<template>
  <section>
    <!-- Page Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="text-h4 mb-0">
          Manajemen Owner & Cabang
        </h2>
        <p class="text-body-1 mb-0 text-disabled mt-1">
          Kelola data pemilik bisnis, sub-owner (franchisee), dan relasi cabang.
        </p>
      </div>
      
      <div class="d-flex gap-4">
        <VBtn
          v-if="$can('create', 'Manajemen Owner')"
          prepend-icon="ri-add-line"
          @click="openAddDrawer"
        >
          Tambah Owner
        </VBtn>
      </div>
    </div>

    <VCard>
      <VCardItem class="pa-4 pb-0">
        <div class="d-flex align-center justify-space-between w-100">
          <VCardTitle class="px-0">
            Daftar Owner
          </VCardTitle>
          <div style="width: 250px;">
            <VTextField
              v-model="search"
              prepend-inner-icon="ri-search-line"
              placeholder="Cari owner..."
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
        :items="flatTableData"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        @update:options="fetchOwners"
      >
        <template #item.name="{ item }">
          <div class="d-flex align-center">
            <VIcon
              v-if="item.is_sub"
              icon="ri-corner-down-right-line"
              class="mr-2 text-disabled"
            />
            <VAvatar
              size="34"
              :color="item.is_sub ? 'secondary' : 'primary'"
              variant="tonal"
              class="mr-3"
            >
              <VImg
                v-if="item.logo"
                :src="`/storage/${item.logo}`"
                alt="Logo"
                cover
              />
              <span
                v-else
                class="text-h6"
              >{{ item.name.charAt(0).toUpperCase() }}</span>
            </VAvatar>
            <div>
              <h6 class="text-h6 font-weight-medium mb-0">
                {{ item.name }}
              </h6>
              <span
                v-if="item.is_sub"
                class="text-caption text-disabled"
              >Sub-Owner dari: {{ item.parent_name }}</span>
              <span
                v-else
                class="text-caption text-success font-weight-bold"
              >Owner Utama</span>
            </div>
          </div>
        </template>

        <template #item.contact="{ item }">
          <div class="text-body-2">
            <div>
              <VIcon
                icon="ri-mail-line"
                size="14"
                class="mr-1"
              /> {{ item.email || '-' }}
            </div>
            <div>
              <VIcon
                icon="ri-phone-line"
                size="14"
                class="mr-1"
              /> {{ item.phone || '-' }}
            </div>
          </div>
        </template>

        <template #item.branches_count="{ item }">
          <VChip
            size="small"
            color="info"
            variant="tonal"
          >
            {{ item.branches?.length || 0 }} Cabang
          </VChip>
        </template>

        <template #item.status="{ item }">
          <VChip
            :color="item.status === 'Aktif' ? 'success' : 'warning'"
            size="small"
          >
            {{ item.status }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <IconBtn
            v-if="$can('write', 'Manajemen Owner')"
            size="small"
            @click="editOwner(item)"
          >
            <VIcon icon="ri-pencil-line" />
          </IconBtn>
          <IconBtn
            v-if="$can('delete', 'Manajemen Owner')"
            size="small"
            color="error"
            @click="deleteOwner(item.id)"
          >
            <VIcon icon="ri-delete-bin-line" />
          </IconBtn>
        </template>
      </VDataTableServer>
    </VCard>

    <AddNewOwnerDrawer
      v-model:is-drawer-open="isDrawerOpen"
      :selected-owner="selectedOwner"
      :owners-list="owners"
      @owner-data="saveOwner"
    />
  </section>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Manajemen Owner
</route>
