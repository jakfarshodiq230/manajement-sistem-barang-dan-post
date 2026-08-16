<script setup>
import { ref, onMounted } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'

const snackbarStore = useSnackbarStore()

const items = ref([])
const loading = ref(false)

const dialog = ref(false)
const dialogDelete = ref(false)
const isEdit = ref(false)

const defaultItem = {
  id: null,
  name: '',
  width: '58mm',
  is_active: 1,
  is_default: 0,
  margin_top: 0,
  margin_bottom: 0,
  margin_left: 0,
  margin_right: 0
}

const editedItem = ref({ ...defaultItem })
const itemToDelete = ref(null)

const headers = [
  { title: 'NAMA', key: 'name' },
  { title: 'UKURAN KERTAS', key: 'width' },
  { title: 'MARGIN (T/B/L/R)', key: 'margin' },
  { title: 'STATUS', key: 'is_active' },
  { title: 'DEFAULT', key: 'is_default' },
  { title: 'AKSI', key: 'actions', sortable: false },
]

const loadData = async () => {
  loading.value = true
  try {
    const res = await $api('/apps/receipt-settings')
    items.value = res.data || res
  } catch (error) {
    snackbarStore.showError('Gagal memuat pengaturan: ' + (error.response?.data?.message || error.message))
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
})

const openAdd = () => {
  isEdit.value = false
  editedItem.value = { ...defaultItem }
  dialog.value = true
}

const openEdit = (item) => {
  isEdit.value = true
  editedItem.value = { ...item }
  // Ensure boolean binding works for checkboxes
  editedItem.value.is_active = !!item.is_active
  editedItem.value.is_default = !!item.is_default
  dialog.value = true
}

const openDelete = (item) => {
  itemToDelete.value = item
  dialogDelete.value = true
}

const close = () => {
  dialog.value = false
  setTimeout(() => {
    editedItem.value = { ...defaultItem }
  }, 300)
}

const closeDelete = () => {
  dialogDelete.value = false
  itemToDelete.value = null
}

const save = async () => {
  loading.value = true
  try {
    const payload = { ...editedItem.value }
    payload.is_active = payload.is_active ? 1 : 0
    payload.is_default = payload.is_default ? 1 : 0

    if (isEdit.value) {
      await $api(`/apps/receipt-settings/${payload.id}`, {
        method: 'PUT',
        body: payload
      })
      snackbarStore.showSuccess('Pengaturan struk berhasil diperbarui')
    } else {
      await $api('/apps/receipt-settings', {
        method: 'POST',
        body: payload
      })
      snackbarStore.showSuccess('Pengaturan struk berhasil ditambahkan')
    }
    close()
    loadData()
  } catch (error) {
    snackbarStore.showError('Gagal menyimpan: ' + (error.response?.data?.message || error.message))
  } finally {
    loading.value = false
  }
}

const confirmDelete = async () => {
  if (!itemToDelete.value) return
  loading.value = true
  try {
    await $api(`/apps/receipt-settings/${itemToDelete.value.id}`, {
      method: 'DELETE'
    })
    snackbarStore.showSuccess('Pengaturan struk berhasil dihapus')
    closeDelete()
    loadData()
  } catch (error) {
    snackbarStore.showError('Gagal menghapus: ' + (error.response?.data?.message || error.message))
  } finally {
    loading.value = false
  }
}

const setDefault = async (item) => {
  loading.value = true
  try {
    await $api(`/apps/receipt-settings/${item.id}`, {
      method: 'PUT',
      body: { ...item, is_default: 1 }
    })
    snackbarStore.showSuccess('Berhasil menjadikan default')
    loadData()
  } catch (error) {
    snackbarStore.showError('Gagal mengubah default: ' + (error.response?.data?.message || error.message))
    loading.value = false
  }
}
</script>

<template>
  <section>
    <VRow>
      <VCol cols="12">
        <VCard title="Pengaturan Struk / Kertas POS">
          <template #append>
            <VBtn prepend-icon="ri-add-line" @click="openAdd">
              Tambah Pengaturan
            </VBtn>
          </template>

          <VCardText>
            <VAlert
              color="info"
              variant="tonal"
              icon="ri-information-line"
              class="mb-4"
            >
              Pengaturan default yang dipilih di sini akan otomatis diterapkan di halaman Point of Sales (POS) saat kasir mencetak struk.
            </VAlert>

            <VDataTable
              :headers="headers"
              :items="items"
              :loading="loading"
              class="text-no-wrap"
            >
              <template #item.margin="{ item }">
                {{ item.margin_top || 0 }} / {{ item.margin_bottom || 0 }} / {{ item.margin_left || 0 }} / {{ item.margin_right || 0 }} mm
              </template>

              <template #item.is_active="{ item }">
                <VChip :color="item.is_active ? 'success' : 'error'" size="small">
                  {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                </VChip>
              </template>

              <template #item.is_default="{ item }">
                <VChip v-if="item.is_default" color="primary" size="small" variant="elevated">
                  Default
                </VChip>
                <VBtn v-else size="x-small" variant="outlined" color="primary" @click="setDefault(item)">
                  Set Default
                </VBtn>
              </template>

              <template #item.actions="{ item }">
                <VBtn icon variant="text" size="small" color="primary" @click="openEdit(item)">
                  <VIcon icon="ri-pencil-line" />
                </VBtn>
                <VBtn icon variant="text" size="small" color="error" @click="openDelete(item)">
                  <VIcon icon="ri-delete-bin-line" />
                </VBtn>
              </template>
            </VDataTable>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Dialog Add/Edit -->
    <VDialog v-model="dialog" max-width="600px">
      <VCard :title="isEdit ? 'Edit Pengaturan Struk' : 'Tambah Pengaturan Struk'">
        <VCardText>
          <VRow>
            <VCol cols="12" md="6">
              <VTextField
                v-model="editedItem.name"
                label="Nama Pengaturan (Contoh: Printer 58mm)"
                required
              />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField
                v-model="editedItem.width"
                label="Lebar Kertas (Contoh: 58mm atau 80mm)"
                required
              />
            </VCol>

            <VCol cols="12">
              <h6 class="text-h6 mb-2">Margin Kertas (dalam mm)</h6>
            </VCol>
            <VCol cols="6" md="3">
              <VTextField v-model="editedItem.margin_top" label="Atas (Top)" type="number" />
            </VCol>
            <VCol cols="6" md="3">
              <VTextField v-model="editedItem.margin_bottom" label="Bawah (Bottom)" type="number" />
            </VCol>
            <VCol cols="6" md="3">
              <VTextField v-model="editedItem.margin_left" label="Kiri (Left)" type="number" />
            </VCol>
            <VCol cols="6" md="3">
              <VTextField v-model="editedItem.margin_right" label="Kanan (Right)" type="number" />
            </VCol>

            <VCol cols="12" md="6">
              <VSwitch
                v-model="editedItem.is_active"
                label="Aktif"
              />
            </VCol>
            <VCol cols="12" md="6">
              <VSwitch
                v-model="editedItem.is_default"
                label="Jadikan Default"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn color="error" variant="outlined" @click="close">Batal</VBtn>
          <VBtn color="primary" variant="elevated" @click="save" :loading="loading">Simpan</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Dialog Delete -->
    <VDialog v-model="dialogDelete" max-width="500px">
      <VCard title="Konfirmasi Hapus">
        <VCardText>
          Apakah Anda yakin ingin menghapus pengaturan struk <strong>{{ itemToDelete?.name }}</strong>?
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn color="secondary" variant="outlined" @click="closeDelete">Batal</VBtn>
          <VBtn color="error" variant="elevated" @click="confirmDelete" :loading="loading">Hapus</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </section>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Pengaturan Struk
</route>
