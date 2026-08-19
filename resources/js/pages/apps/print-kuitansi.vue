<script setup>
import { ref, computed, onMounted } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'

definePage({
  meta: {
    public: true,
  },
})

const snackbar = useSnackbarStore()
const settings = ref([])
const safeSettings = computed(() => Array.isArray(settings.value) ? settings.value : [])
const isLoading = ref(false)
const isDialogVisible = ref(false)
const isDeleteDialogVisible = ref(false)
const currentPage = ref(1)
const totalPages = ref(1)
const totalItems = ref(0)
const itemsPerPage = ref(10)
const editedItem = ref({ id: null, name: '', width: '', is_active: true, is_default: false, margin_top: 0, margin_bottom: 0, margin_left: 0, margin_right: 0 })
const itemToDelete = ref(null)
const search = ref('')

const tableHeaders = ref([
  { title: 'Nama Profil Kertas', key: 'name' },
  { title: 'Lebar Kertas (Width)', key: 'width' },
  { title: 'Status', key: 'status' },
  { title: 'Default', key: 'is_default' },
  { title: 'Aksi', key: 'actions', sortable: false, align: 'end' },
])

const fetchSettings = async () => {
  isLoading.value = true
  try {
    const data = await $api(`/apps/receipt-settings?page=${currentPage.value}`)
    let newItems = []
    if (data && Array.isArray(data.data)) {
      newItems = data.data
    } else if (Array.isArray(data)) {
      newItems = data
    }
    settings.value = newItems
    totalPages.value = Number(data?.last_page) || 1
    totalItems.value = Number(data?.total) || newItems.length || 0
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal memuat pengaturan', 'error')
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchSettings()
})

const openAddDialog = () => {
  editedItem.value = { id: null, name: '', width: '80mm', is_active: true, is_default: false, margin_top: 0, margin_bottom: 0, margin_left: 0, margin_right: 0 }
  isDialogVisible.value = true
}

const openEditDialog = (item) => {
  editedItem.value = { ...item }
  isDialogVisible.value = true
}

const confirmDelete = (item) => {
  itemToDelete.value = item
  isDeleteDialogVisible.value = true
}

const applyPreset = type => {
  if (type === '58mm') {
    editedItem.value.name = 'Thermal 58mm'
    editedItem.value.width = '58mm'
    editedItem.value.margin_top = 0
    editedItem.value.margin_bottom = 0
    editedItem.value.margin_left = 2
    editedItem.value.margin_right = 2
  } else if (type === '80mm') {
    editedItem.value.name = 'Thermal 80mm'
    editedItem.value.width = '80mm'
    editedItem.value.margin_top = 2
    editedItem.value.margin_bottom = 2
    editedItem.value.margin_left = 4
    editedItem.value.margin_right = 4
  } else if (type === 'a5') {
    editedItem.value.name = 'Kuitansi A5'
    editedItem.value.width = '148mm'
    editedItem.value.margin_top = 5
    editedItem.value.margin_bottom = 5
    editedItem.value.margin_left = 5
    editedItem.value.margin_right = 5
  }
}

const saveItem = async () => {
  if (!editedItem.value.name?.trim()) {
    snackbar.show('Nama profil kertas wajib diisi', 'error')
    return
  }

  try {
    if (editedItem.value.id) {
      await $api(`/apps/receipt-settings/${editedItem.value.id}`, {
        method: 'PUT',
        body: editedItem.value,
      })
      snackbar.show('Pengaturan berhasil diperbarui', 'success')
    } else {
      await $api('/apps/receipt-settings', {
        method: 'POST',
        body: editedItem.value,
      })
      snackbar.show('Pengaturan berhasil ditambahkan', 'success')
    }
    isDialogVisible.value = false
    fetchSettings()
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal menyimpan pengaturan', 'error')
  }
}

const deleteItem = async () => {
  try {
    await $api(`/apps/receipt-settings/${itemToDelete.value.id}`, {
      method: 'DELETE',
    })
    snackbar.show('Pengaturan berhasil dihapus', 'success')
    isDeleteDialogVisible.value = false
    fetchSettings()
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal menghapus pengaturan', 'error')
  }
}

const filteredSettings = computed(() => {
  if (!search.value) return safeSettings.value
  const q = search.value.toLowerCase().trim()
  return safeSettings.value.filter(s => s.name?.toLowerCase().includes(q) || s.width?.toLowerCase().includes(q))
})
</script>

<template>
  <div class="print-kuitansi-page">
    <!-- Header Banner -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-4 mb-6">
      <div>
        <div class="d-flex align-center gap-2 mb-1">
          <VChip color="primary" variant="tonal" size="small" class="font-weight-bold">
            <VIcon icon="ri-file-paper-2-line" size="14" class="me-1" />
            CETAK KUITANSI & STRUK
          </VChip>
        </div>
        <h1 class="text-h4 font-weight-extrabold text-high-emphasis mb-1">
          Pengaturan Kertas Struk & Kuitansi
        </h1>
        <p class="text-body-1 text-medium-emphasis mb-0">
          Kelola profil ukuran kertas thermal (58mm/80mm) dan kertas faktur kuitansi untuk seluruh modul kasir dan piutang.
        </p>
      </div>

      <div class="d-flex gap-3">
        <VBtn
          color="primary"
          class="font-weight-bold text-none"
          prepend-icon="ri-add-line"
          @click="openAddDialog"
        >
          Tambah Profil Kertas
        </VBtn>
      </div>
    </div>

    <!-- Main Card -->
    <VCard class="rounded-xl border elevation-1">
      <VCardText class="d-flex flex-wrap align-center py-5 gap-4">
        <VTextField
          v-model="search"
          placeholder="Cari profil kertas..."
          density="compact"
          variant="outlined"
          rounded="lg"
          prepend-inner-icon="ri-search-line"
          style="max-width: 280px;"
          hide-details
          clearable
        />
        
        <VSpacer />
        
        <div class="d-flex align-center gap-2 text-caption text-medium-emphasis">
          <VIcon icon="ri-printer-line" size="18" />
          <span>{{ safeSettings.length }} Profil Terdaftar</span>
        </div>
      </VCardText>

      <VDivider />

      <VDataTable
        :headers="tableHeaders"
        :items="filteredSettings"
        :items-per-page="itemsPerPage"
        :loading="isLoading"
        class="text-no-wrap"
      >
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-3 py-2">
            <VAvatar color="primary" variant="tonal" size="36" rounded="lg">
              <VIcon icon="ri-file-text-line" size="18" />
            </VAvatar>
            <div>
              <span class="font-weight-bold text-subtitle-2 text-high-emphasis d-block">{{ item.name }}</span>
              <span class="text-caption text-medium-emphasis">ID: #{{ item.id }}</span>
            </div>
          </div>
        </template>

        <template #item.width="{ item }">
          <VChip size="small" color="info" variant="tonal" class="font-weight-bold">
            <VIcon icon="ri-ruler-line" size="13" class="me-1" />
            {{ item.width }}
          </VChip>
        </template>
        
        <template #item.status="{ item }">
          <VChip
            size="small"
            :color="item.is_active ? 'success' : 'secondary'"
            variant="elevated"
            class="font-weight-bold"
          >
            {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
          </VChip>
        </template>
        
        <template #item.is_default="{ item }">
          <VChip v-if="item.is_default" size="small" color="primary" variant="elevated" class="font-weight-bold">
            <VIcon icon="ri-check-double-line" size="13" class="me-1" />
            Default
          </VChip>
          <span v-else class="text-caption text-medium-emphasis">-</span>
        </template>
        
        <template #item.actions="{ item }">
          <div class="d-flex align-center justify-end gap-1">
            <IconBtn size="small" color="primary" variant="tonal" title="Edit Profil" @click="openEditDialog(item)">
              <VIcon icon="ri-edit-line" size="18" />
            </IconBtn>
            <IconBtn size="small" color="error" variant="tonal" title="Hapus Profil" @click="confirmDelete(item)">
              <VIcon icon="ri-delete-bin-line" size="18" />
            </IconBtn>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <!-- Dialog Form -->
    <VDialog v-model="isDialogVisible" max-width="560">
      <VCard class="rounded-2xl pa-6">
        <VCardItem class="pa-0 mb-4">
          <div class="d-flex align-center gap-3">
            <VAvatar color="primary" variant="tonal" size="48" rounded="xl">
              <VIcon icon="ri-file-paper-2-line" size="26" />
            </VAvatar>
            <div>
              <h3 class="text-h6 font-weight-bold mb-0">
                {{ editedItem.id ? 'Edit Profil Kertas' : 'Tambah Profil Kertas Baru' }}
              </h3>
              <p class="text-caption text-medium-emphasis mb-0">
                Atur ukuran lebar kertas dan margin cetak kuitansi / struk.
              </p>
            </div>
          </div>
        </VCardItem>

        <VCardText class="pa-0 mb-4">
          <!-- Preset Chips -->
          <div class="mb-4">
            <label class="text-caption font-weight-bold text-medium-emphasis mb-1 d-block">
              Template Preset Cepat:
            </label>
            <div class="d-flex flex-wrap gap-2">
              <VChip size="small" color="primary" variant="tonal" class="cursor-pointer font-weight-medium" @click="applyPreset('58mm')">
                Thermal 58mm (Mini)
              </VChip>
              <VChip size="small" color="success" variant="tonal" class="cursor-pointer font-weight-medium" @click="applyPreset('80mm')">
                Thermal 80mm (Standar POS)
              </VChip>
              <VChip size="small" color="info" variant="tonal" class="cursor-pointer font-weight-medium" @click="applyPreset('a5')">
                Kertas A5 (Kuitansi/Faktur)
              </VChip>
            </div>
          </div>

          <VRow>
            <VCol cols="12" md="6">
              <label class="text-caption font-weight-bold text-high-emphasis mb-1 d-block">Nama Profil Kertas</label>
              <VTextField
                v-model="editedItem.name"
                placeholder="Contoh: Thermal Standar 80mm"
                variant="outlined"
                density="comfortable"
                rounded="lg"
                hide-details
              />
            </VCol>
            <VCol cols="12" md="6">
              <label class="text-caption font-weight-bold text-high-emphasis mb-1 d-block">Lebar Kertas (Width)</label>
              <VTextField
                v-model="editedItem.width"
                placeholder="Contoh: 80mm"
                variant="outlined"
                density="comfortable"
                rounded="lg"
                hide-details
              />
            </VCol>
            
            <VCol cols="12">
              <label class="text-caption font-weight-bold text-high-emphasis mb-2 d-block">Pengaturan Margin (mm)</label>
              <VRow>
                <VCol cols="6" sm="3">
                  <VTextField v-model="editedItem.margin_top" label="Atas" type="number" variant="outlined" density="compact" rounded="lg" hide-details />
                </VCol>
                <VCol cols="6" sm="3">
                  <VTextField v-model="editedItem.margin_bottom" label="Bawah" type="number" variant="outlined" density="compact" rounded="lg" hide-details />
                </VCol>
                <VCol cols="6" sm="3">
                  <VTextField v-model="editedItem.margin_left" label="Kiri" type="number" variant="outlined" density="compact" rounded="lg" hide-details />
                </VCol>
                <VCol cols="6" sm="3">
                  <VTextField v-model="editedItem.margin_right" label="Kanan" type="number" variant="outlined" density="compact" rounded="lg" hide-details />
                </VCol>
              </VRow>
            </VCol>

            <VCol cols="12" sm="6">
              <VSwitch
                v-model="editedItem.is_active"
                label="Status Aktif"
                color="success"
                hide-details
              />
            </VCol>
            <VCol cols="12" sm="6">
              <VSwitch
                v-model="editedItem.is_default"
                label="Jadikan Default"
                color="primary"
                hide-details
              />
            </VCol>
          </VRow>
        </VCardText>

        <VCardActions class="pa-0 d-flex justify-end gap-2">
          <VBtn color="secondary" variant="tonal" @click="isDialogVisible = false">Batal</VBtn>
          <VBtn color="primary" class="font-weight-bold" @click="saveItem">Simpan</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Dialog Konfirmasi Hapus -->
    <VDialog v-model="isDeleteDialogVisible" max-width="440">
      <VCard class="rounded-2xl pa-6 text-center">
        <VAvatar color="error" variant="tonal" size="64" class="mx-auto mb-4">
          <VIcon icon="ri-delete-bin-line" size="36" />
        </VAvatar>
        <h3 class="text-h6 font-weight-bold mb-2">Konfirmasi Hapus</h3>
        <p class="text-body-2 text-medium-emphasis mb-5">
          Apakah Anda yakin ingin menghapus profil kertas <strong>{{ itemToDelete?.name }}</strong>?
        </p>
        <div class="d-flex justify-center gap-2">
          <VBtn color="secondary" variant="tonal" @click="isDeleteDialogVisible = false">Batal</VBtn>
          <VBtn color="error" class="font-weight-bold" @click="deleteItem">Ya, Hapus</VBtn>
        </div>
      </VCard>
    </VDialog>
  </div>
</template>
