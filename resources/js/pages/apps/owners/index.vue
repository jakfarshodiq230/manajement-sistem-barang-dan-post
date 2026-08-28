<script setup>
import { ref, computed, onMounted } from 'vue'
import AddNewOwnerDrawer from './AddNewOwnerDrawer.vue'
import SimpleConfirmDialog from '@/components/dialogs/SimpleConfirmDialog.vue'
import { useSnackbarStore } from '@/stores/snackbar'

definePage({
  meta: {
    public: true,
  },
})

const owners = ref([])
const search = ref('')
const selectedType = ref('all')
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

const stats = computed(() => {
  const all = owners.value || []
  const total = totalItems.value || all.length
  const main = all.filter(o => !o.parent_id).length
  const sub = all.filter(o => !!o.parent_id).length
  return { total, main, sub }
})

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
  }, 400)
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
    snackbar.show('Gagal menghapus owner. Pastikan tidak ada cabang terkait.', 'error')
  } finally {
    ownerToDelete.value = null
  }
}

const saveOwner = async ownerData => {
  try {
    const formData = new FormData()
    for (const key in ownerData) {
      if (key === 'logo' || key === 'qris_image') {
        if (ownerData[key] instanceof File) {
          formData.append(key, ownerData[key])
        }
      } else if (key === 'parent_id') {
        if (ownerData[key] && ownerData[key] !== 'null' && ownerData[key] !== '') {
          formData.append(key, ownerData[key])
        }
      } else if (ownerData[key] !== null && ownerData[key] !== undefined && ownerData[key] !== '') {
        formData.append(key, ownerData[key])
      }
    }

    if (ownerData.id) {
      formData.append('_method', 'PUT')
      await $api(`/apps/owners/${ownerData.id}`, {
        method: 'POST',
        body: formData,
      })
      snackbar.show('Data owner berhasil diperbarui', 'success')
    } else {
      await $api('/apps/owners', {
        method: 'POST',
        body: formData,
      })
      snackbar.show('Owner baru berhasil ditambahkan', 'success')
    }
    fetchOwners()
  } catch (error) {
    console.error(error)
    const errMsg = error?.response?._data?.message || error?.data?.message || 'Terjadi kesalahan saat menyimpan data owner'
    snackbar.show(errMsg, 'error')
  }
}

const tableHeaders = [
  { title: 'IDENTITAS OWNER / PERUSAHAAN', key: 'name' },
  { title: 'STRUKTUR HIERARKI', key: 'hierarchy' },
  { title: 'KONTAK RESMI', key: 'contact', sortable: false },
  { title: 'TOTAL CABANG', key: 'branches_count', align: 'center', sortable: false },
  { title: 'STATUS', key: 'status', align: 'center' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const flatTableData = computed(() => {
  let list = Array.isArray(owners.value) ? owners.value : (owners.value?.data || [])
  if (selectedType.value === 'main') {
    list = list.filter(o => !o.parent_id)
  } else if (selectedType.value === 'sub') {
    list = list.filter(o => !!o.parent_id)
  }
  return Array.isArray(list) ? list.map(item => ({
    ...item,
    is_sub: !!item.parent_id,
    parent_name: item.parent?.name || 'Owner Utama',
  })) : []
})
</script>

<template>
  <div class="pa-4">
    <!-- Header -->
    <div class="d-flex flex-wrap align-center justify-space-between mb-4 gap-4">
      <div>
        <h2 class="text-h4 font-weight-bold mb-1">
          Manajemen Pemilik Bisnis (Owner & Sub-Owner)
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Kelola kepemilikan bisnis, entitas PT/CV induk, sub-owner mitra, dan alokasi cabang terkait.
        </p>
      </div>
      
      <div class="d-flex gap-3">
        <VBtn
          color="secondary"
          variant="tonal"
          prepend-icon="ri-refresh-line"
          :loading="isLoading"
          @click="fetchOwners"
        >
          Muat Ulang
        </VBtn>

        <VBtn
          color="primary"
          prepend-icon="ri-add-line"
          @click="openAddDrawer"
        >
          Tambah Owner Baru
        </VBtn>
      </div>
    </div>

    <!-- KPI Summary Row -->
    <VRow class="mb-4">
      <VCol cols="12" sm="4">
        <VCard elevation="2" class="pa-4 border-s-lg border-primary">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-primary font-weight-bold">TOTAL OWNER TERDATA</div>
              <div class="text-h4 font-weight-bold text-primary mt-1">{{ stats.total }} <span class="text-caption text-medium-emphasis">Entitas</span></div>
            </div>
            <VAvatar color="primary" variant="tonal" rounded size="44">
              <VIcon icon="ri-user-star-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Seluruh pemegang hak usaha</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="4">
        <VCard elevation="2" class="pa-4 border-s-lg border-success">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-success font-weight-bold">OWNER UTAMA (HOLDING)</div>
              <div class="text-h4 font-weight-bold text-success mt-1">{{ stats.main }} <span class="text-caption text-medium-emphasis">Induk</span></div>
            </div>
            <VAvatar color="success" variant="tonal" rounded size="44">
              <VIcon icon="ri-building-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Pemilik modal entitas utama</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="4">
        <VCard elevation="2" class="pa-4 border-s-lg border-info">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-info font-weight-bold">SUB-OWNER / MITRA</div>
              <div class="text-h4 font-weight-bold text-info mt-1">{{ stats.sub }} <span class="text-caption text-medium-emphasis">Mitra</span></div>
            </div>
            <VAvatar color="info" variant="tonal" rounded size="44">
              <VIcon icon="ri-team-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Pemilik unit cabang turunan</div>
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
              placeholder="Cari nama owner, kontak, email..."
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
                { title: 'Semua Tipe Owner', value: 'all' },
                { title: 'Owner Utama Sahaja', value: 'main' },
                { title: 'Sub-Owner Sahaja', value: 'sub' }
              ]"
              item-title="title"
              item-value="value"
              density="compact"
              variant="outlined"
              hide-details
            />
          </VCol>

          <VCol cols="12" md="5" class="text-right d-none d-md-block">
            <div class="text-caption text-medium-emphasis">
              Total Terdaftar: <strong>{{ totalItems }}</strong> Pemilik Usaha
            </div>
          </VCol>
        </VRow>
      </VCardItem>

      <VDivider />

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="tableHeaders"
        :items="flatTableData"
        :items-length="totalItems"
        :loading="isLoading"
        hover
        class="text-no-wrap"
        @update:options="fetchOwners"
      >
        <!-- Owner Name -->
        <template #item.name="{ item }">
          <div class="d-flex align-center py-2">
            <VAvatar
              size="40"
              :color="item.is_sub ? 'info' : 'primary'"
              variant="tonal"
              class="me-3 rounded-lg border flex-shrink-0"
            >
              <VImg
                v-if="item.logo"
                :src="`/storage/${item.logo}`"
                alt="Logo"
                cover
              />
              <VIcon
                v-else
                :icon="item.is_sub ? 'ri-user-follow-line' : 'ri-user-star-line'"
                size="22"
              />
            </VAvatar>
            <div>
              <div class="font-weight-bold text-subtitle-2">{{ item.name }}</div>
              <div class="text-caption text-disabled">ID Owner: #{{ item.id }}</div>
            </div>
          </div>
        </template>

        <!-- Hierarchy -->
        <template #item.hierarchy="{ item }">
          <VChip
            v-if="!item.is_sub"
            color="success"
            size="small"
            variant="tonal"
            class="font-weight-bold"
          >
            <VIcon icon="ri-shield-star-line" size="14" class="me-1" />
            Owner Utama (Holding)
          </VChip>
          <div v-else class="d-flex align-center gap-1">
            <VChip
              color="info"
              size="small"
              variant="tonal"
              class="font-weight-medium"
            >
              <VIcon icon="ri-corner-down-right-line" size="14" class="me-1" />
              Sub: {{ item.parent_name }}
            </VChip>
          </div>
        </template>

        <!-- Contact -->
        <template #item.contact="{ item }">
          <div class="d-flex flex-column gap-1">
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

        <!-- Branches Count -->
        <template #item.branches_count="{ item }">
          <VChip
            size="small"
            variant="tonal"
            color="primary"
            class="font-weight-bold"
          >
            {{ item.branches_count || (item.branches ? item.branches.length : 0) }} Cabang
          </VChip>
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
              size="small"
              variant="text"
              color="primary"
              icon="ri-edit-box-line"
              title="Edit Data Owner"
              @click="editOwner(item)"
            />
            <VBtn
              size="small"
              variant="text"
              color="error"
              icon="ri-delete-bin-line"
              title="Hapus Owner"
              @click="confirmDeleteOwner(item.id)"
            />
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <AddNewOwnerDrawer
      v-model:is-drawer-open="isDrawerOpen"
      :selected-owner="selectedOwner"
      :owners-list="owners"
      @owner-data="saveOwner"
    />

    <SimpleConfirmDialog
      v-model:is-dialog-visible="isConfirmDeleteDialogVisible"
      title="Hapus Data Owner?"
      message="Apakah Anda yakin ingin menghapus owner ini? Pastikan tidak ada cabang atau sub-owner yang masih bergantung pada entitas ini."
      confirm-text="Ya, Hapus"
      cancel-text="Batal"
      @confirm="executeDeleteOwner"
    />
  </div>
</template>
