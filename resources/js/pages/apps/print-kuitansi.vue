<script setup>
import { ref, computed, onMounted } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'

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
  { title: 'NAMA', key: 'name' },
  { title: 'LEBAR (WIDTH)', key: 'width' },
  { title: 'STATUS', key: 'status' },
  { title: 'DEFAULT', key: 'is_default' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
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
  editedItem.value = { id: null, name: '', width: '', is_active: true, is_default: false, margin_top: 0, margin_bottom: 0, margin_left: 0, margin_right: 0 }
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

const saveItem = async () => {
  try {
    if (editedItem.value.id) {
      await $api(`/apps/receipt-settings/${editedItem.value.id}`, {
        method: 'PUT',
        body: editedItem.value
      })
      snackbar.show('Pengaturan berhasil diperbarui', 'success')
    } else {
      await $api('/apps/receipt-settings', {
        method: 'POST',
        body: editedItem.value
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
      method: 'DELETE'
    })
    snackbar.show('Pengaturan berhasil dihapus', 'success')
    isDeleteDialogVisible.value = false
    fetchSettings()
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal menghapus pengaturan', 'error')
  }
}
</script>

<template>
  <div>
    <p class="text-2xl mb-6">
      Pengaturan Kertas Struk / Kuitansi
    </p>

    <VCard>
      <!-- Card Header -->
      <VCardText class="d-flex flex-wrap align-center py-4 gap-4">
        <VTextField
          v-model="search"
          placeholder="Cari Pengaturan..."
          density="compact"
          prepend-inner-icon="ri-search-line"
          style="width: 300px;"
          hide-details
          clearable
        />
        
        <VSpacer />
        
        <div class="d-flex gap-2">
          <VBtn
            color="primary"
            prepend-icon="ri-add-line"
            @click="openAddDialog"
          >
            Tambah Kertas
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <VDataTable
        :headers="tableHeaders"
        :items="settings"
        :items-per-page="10"
        :loading="isLoading"
        :sort-by="[]"
        :group-by="[]"
        class="text-no-wrap"
      >
        <template #item.width="{ item }">
          <VChip size="small" color="primary" variant="tonal">
            {{ item.width }}
          </VChip>
        </template>
        
        <template #item.status="{ item }">
          <VChip size="small" :color="item.is_active ? 'success' : 'error'">
            {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
          </VChip>
        </template>
        
        <template #item.is_default="{ item }">
          <VIcon v-if="item.is_default" icon="ri-check-line" color="success" />
          <span v-else>-</span>
        </template>
        
        <template #item.actions="{ item }">
          <IconBtn size="small" color="primary" @click="openEditDialog(item)">
            <VIcon icon="ri-edit-line" />
          </IconBtn>
          <IconBtn size="small" color="error" @click="confirmDelete(item)">
            <VIcon icon="ri-delete-bin-line" />
          </IconBtn>
        </template>
      </VDataTable>
      
      <VDivider />
      <VCardText class="d-flex align-center flex-wrap justify-space-between gap-4 py-3" v-if="totalPages > 1">
        <span class="text-sm text-disabled">
          Menampilkan pengaturan halaman {{ currentPage }} dari {{ totalPages }}
        </span>
        <VPagination
          v-model="currentPage"
          size="small"
          :total-visible="5"
          :length="totalPages"
          @update:modelValue="fetchSettings"
        />
      </VCardText>
    </VCard>

    <!-- Dialog Form -->
    <VDialog v-model="isDialogVisible" max-width="500">
      <VCard>
        <VCardTitle class="pa-5 pb-3">
          {{ editedItem.id ? 'Edit Pengaturan Kertas' : 'Tambah Pengaturan Kertas' }}
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-5">
          <VRow>
            <VCol cols="12">
              <VTextField
                v-model="editedItem.name"
                label="Nama Profil Kertas"
                placeholder="Contoh: Thermal Besar"
                variant="outlined"
              />
            </VCol>
            <VCol cols="12">
              <VTextField
                v-model="editedItem.width"
                label="Lebar Kertas (Width)"
                placeholder="Contoh: 80mm atau 210mm"
                variant="outlined"
                hint="Gunakan satuan 'mm' (Milimeter). Contoh: 58mm"
                persistent-hint
              />
            </VCol>
            
            <VCol cols="12" class="mt-4">
              <h5 class="text-h6 mb-3">Pengaturan Margin (mm)</h5>
              <VRow>
                <VCol cols="6">
                  <VTextField
                    v-model="editedItem.margin_top"
                    label="Margin Atas"
                    type="number"
                    variant="outlined"
                  />
                </VCol>
                <VCol cols="6">
                  <VTextField
                    v-model="editedItem.margin_bottom"
                    label="Margin Bawah"
                    type="number"
                    variant="outlined"
                  />
                </VCol>
                <VCol cols="6">
                  <VTextField
                    v-model="editedItem.margin_left"
                    label="Margin Kiri"
                    type="number"
                    variant="outlined"
                  />
                </VCol>
                <VCol cols="6">
                  <VTextField
                    v-model="editedItem.margin_right"
                    label="Margin Kanan"
                    type="number"
                    variant="outlined"
                  />
                </VCol>
              </VRow>
            </VCol>

            <VCol cols="6">
              <VSwitch
                v-model="editedItem.is_active"
                label="Aktif"
                color="primary"
              />
            </VCol>
            <VCol cols="6">
              <VSwitch
                v-model="editedItem.is_default"
                label="Jadikan Default"
                color="success"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VDivider />
        <VCardActions class="pa-4 justify-end">
          <VBtn color="secondary" variant="tonal" @click="isDialogVisible = false">Batal</VBtn>
          <VBtn color="primary" variant="elevated" @click="saveItem">Simpan</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Dialog Konfirmasi Hapus -->
    <VDialog v-model="isDeleteDialogVisible" max-width="400">
      <VCard>
        <VCardTitle class="pa-5 pb-3">Konfirmasi Hapus</VCardTitle>
        <VDivider />
        <VCardText class="pa-5">
          Apakah Anda yakin ingin menghapus profil kertas <strong>{{ itemToDelete?.name }}</strong>?
        </VCardText>
        <VDivider />
        <VCardActions class="pa-4 justify-end">
          <VBtn color="secondary" variant="tonal" @click="isDeleteDialogVisible = false">Batal</VBtn>
          <VBtn color="error" variant="elevated" @click="deleteItem">Hapus</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Print Kuitansi
</route>
