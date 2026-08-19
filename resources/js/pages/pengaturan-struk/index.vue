<script setup>
import { ref, onMounted, computed } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'

definePage({
  meta: {
    public: true,
  },
})

const snackbarStore = useSnackbarStore()

const items = ref([])
const loading = ref(false)
const search = ref('')

const dialog = ref(false)
const dialogDelete = ref(false)
const isEdit = ref(false)

const defaultItem = {
  id: null,
  name: '',
  width: '58mm',
  is_active: true,
  is_default: false,
  margin_top: 0,
  margin_bottom: 0,
  margin_left: 0,
  margin_right: 0,
}

const editedItem = ref({ ...defaultItem })
const itemToDelete = ref(null)

const headers = [
  { title: 'Nama Profil Printer', key: 'name' },
  { title: 'Lebar Kertas', key: 'width' },
  { title: 'Margin (T/B/L/R)', key: 'margin' },
  { title: 'Status', key: 'is_active' },
  { title: 'Default POS', key: 'is_default' },
  { title: 'Aksi', key: 'actions', sortable: false, align: 'end' },
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

const openEdit = item => {
  isEdit.value = true
  editedItem.value = {
    ...item,
    is_active: !!item.is_active,
    is_default: !!item.is_default,
  }
  dialog.value = true
}

const openDelete = item => {
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

const applyPreset = type => {
  if (type === '58mm') {
    editedItem.value.name = 'Thermal Mini 58mm'
    editedItem.value.width = '58mm'
    editedItem.value.margin_top = 0
    editedItem.value.margin_bottom = 0
    editedItem.value.margin_left = 2
    editedItem.value.margin_right = 2
  } else if (type === '80mm') {
    editedItem.value.name = 'Thermal Standar POS 80mm'
    editedItem.value.width = '80mm'
    editedItem.value.margin_top = 2
    editedItem.value.margin_bottom = 2
    editedItem.value.margin_left = 4
    editedItem.value.margin_right = 4
  } else if (type === 'a5') {
    editedItem.value.name = 'Faktur Kuitansi A5'
    editedItem.value.width = '148mm'
    editedItem.value.margin_top = 5
    editedItem.value.margin_bottom = 5
    editedItem.value.margin_left = 5
    editedItem.value.margin_right = 5
  }
}

const save = async () => {
  if (!editedItem.value.name.trim()) {
    snackbarStore.showError('Nama profil printer wajib diisi')
    return
  }

  loading.value = true
  try {
    const payload = {
      ...editedItem.value,
      is_active: editedItem.value.is_active ? 1 : 0,
      is_default: editedItem.value.is_default ? 1 : 0,
    }

    if (isEdit.value) {
      await $api(`/apps/receipt-settings/${payload.id}`, {
        method: 'PUT',
        body: payload,
      })
      snackbarStore.showSuccess('Pengaturan struk berhasil diperbarui')
    } else {
      await $api('/apps/receipt-settings', {
        method: 'POST',
        body: payload,
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
      method: 'DELETE',
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

const setDefault = async item => {
  loading.value = true
  try {
    await $api(`/apps/receipt-settings/${item.id}`, {
      method: 'PUT',
      body: { ...item, is_default: 1 },
    })
    snackbarStore.showSuccess('Berhasil menjadikan profil default')
    loadData()
  } catch (error) {
    snackbarStore.showError('Gagal mengubah default: ' + (error.response?.data?.message || error.message))
    loading.value = false
  }
}

const filteredItems = computed(() => {
  if (!search.value) return items.value
  const q = search.value.toLowerCase().trim()
  return items.value.filter(i => i.name.toLowerCase().includes(q) || i.width.toLowerCase().includes(q))
})
</script>

<template>
  <div class="pengaturan-struk-page">
    <!-- Header Banner -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-4 mb-6">
      <div>
        <div class="d-flex align-center gap-2 mb-1">
          <VChip color="primary" variant="tonal" size="small" class="font-weight-bold">
            <VIcon icon="ri-printer-line" size="14" class="me-1" />
            PERIPHERAL KASIR
          </VChip>
        </div>
        <h1 class="text-h4 font-weight-extrabold text-high-emphasis mb-1">
          Pengaturan Format Kertas Struk & POS
        </h1>
        <p class="text-body-1 text-medium-emphasis mb-0">
          Konfigurasikan ukuran kertas printer thermal (58mm/80mm), margin cetak, dan template struk default kasir.
        </p>
      </div>

      <div class="d-flex gap-3">
        <VBtn
          color="primary"
          class="font-weight-bold text-none"
          prepend-icon="ri-add-line"
          @click="openAdd"
        >
          Tambah Profil Printer
        </VBtn>
      </div>
    </div>

    <!-- Alert Info Card -->
    <VCard class="pa-4 rounded-xl border elevation-1 mb-6 bg-primary-lighten-5">
      <div class="d-flex align-center gap-3">
        <VAvatar color="primary" variant="tonal" size="44" rounded="lg">
          <VIcon icon="ri-information-line" size="24" />
        </VAvatar>
        <div>
          <h4 class="text-subtitle-2 font-weight-bold text-high-emphasis mb-0">
            Integrasi Otomatis Cetak Struk POS
          </h4>
          <span class="text-caption text-medium-emphasis">
            Pengaturan yang ditandai sebagai <strong>Default</strong> akan langsung digunakan oleh layar kasir POS saat menekan tombol "Cetak Struk".
          </span>
        </div>
      </div>
    </VCard>

    <!-- Main Table Card -->
    <VCard class="rounded-xl border elevation-1">
      <VCardText class="pa-5 d-flex justify-space-between align-center flex-wrap gap-4">
        <VTextField
          v-model="search"
          placeholder="Cari profil printer..."
          prepend-inner-icon="ri-search-line"
          density="compact"
          variant="outlined"
          rounded="lg"
          clearable
          hide-details
          style="max-width: 280px;"
        />

        <div class="d-flex align-center gap-2 text-caption text-medium-emphasis">
          <VIcon icon="ri-printer-cloud-line" size="18" />
          <span>{{ items.length }} Profil Tersedia</span>
        </div>
      </VCardText>

      <VDivider />

      <VDataTable
        :headers="headers"
        :items="filteredItems"
        :loading="loading"
        class="text-no-wrap"
      >
        <!-- Nama Profil -->
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-3 py-2">
            <VAvatar color="primary" variant="tonal" size="36" rounded="lg">
              <VIcon icon="ri-printer-line" size="18" />
            </VAvatar>
            <div>
              <span class="font-weight-bold text-subtitle-2 text-high-emphasis d-block">{{ item.name }}</span>
              <span class="text-caption text-medium-emphasis">ID Profil: #{{ item.id }}</span>
            </div>
          </div>
        </template>

        <!-- Lebar Kertas -->
        <template #item.width="{ item }">
          <VChip size="small" color="info" variant="tonal" class="font-weight-bold">
            <VIcon icon="ri-ruler-line" size="13" class="me-1" />
            {{ item.width }}
          </VChip>
        </template>

        <!-- Margin -->
        <template #item.margin="{ item }">
          <span class="text-caption font-weight-medium">
            T: {{ item.margin_top || 0 }} | B: {{ item.margin_bottom || 0 }} | L: {{ item.margin_left || 0 }} | R: {{ item.margin_right || 0 }} mm
          </span>
        </template>

        <!-- Status -->
        <template #item.is_active="{ item }">
          <VChip
            :color="item.is_active ? 'success' : 'secondary'"
            size="small"
            variant="elevated"
            class="font-weight-bold"
          >
            {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
          </VChip>
        </template>

        <!-- Default POS -->
        <template #item.is_default="{ item }">
          <VChip v-if="item.is_default" color="primary" size="small" variant="elevated" class="font-weight-bold">
            <VIcon icon="ri-check-double-line" size="13" class="me-1" />
            Default POS
          </VChip>
          <VBtn
            v-else
            size="x-small"
            variant="tonal"
            color="primary"
            class="font-weight-bold text-none"
            @click="setDefault(item)"
          >
            Jadikan Default
          </VBtn>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex align-center justify-end gap-1">
            <IconBtn size="small" color="primary" variant="tonal" title="Edit Profil" @click="openEdit(item)">
              <VIcon icon="ri-edit-line" size="18" />
            </IconBtn>
            <IconBtn size="small" color="error" variant="tonal" title="Hapus Profil" @click="openDelete(item)">
              <VIcon icon="ri-delete-bin-line" size="18" />
            </IconBtn>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <!-- Dialog Add/Edit -->
    <VDialog v-model="dialog" max-width="620px">
      <VCard class="rounded-2xl pa-6">
        <VCardItem class="pa-0 mb-4">
          <div class="d-flex align-center gap-3">
            <VAvatar color="primary" variant="tonal" size="48" rounded="xl">
              <VIcon icon="ri-printer-line" size="26" />
            </VAvatar>
            <div>
              <h3 class="text-h6 font-weight-bold mb-0">
                {{ isEdit ? 'Edit Pengaturan Struk' : 'Tambah Profil Printer Struk' }}
              </h3>
              <p class="text-caption text-medium-emphasis mb-0">
                Atur ukuran kertas thermal dan margin batas cetak nota kasir.
              </p>
            </div>
          </div>
        </VCardItem>

        <VCardText class="pa-0 mb-4">
          <!-- Preset Chips -->
          <div class="mb-4">
            <label class="text-caption font-weight-bold text-medium-emphasis mb-1 d-block">
              Template Cepat:
            </label>
            <div class="d-flex flex-wrap gap-2">
              <VChip size="small" color="primary" variant="tonal" class="cursor-pointer font-weight-medium" @click="applyPreset('58mm')">
                Thermal 58mm (Mini)
              </VChip>
              <VChip size="small" color="success" variant="tonal" class="cursor-pointer font-weight-medium" @click="applyPreset('80mm')">
                Thermal 80mm (Standar)
              </VChip>
              <VChip size="small" color="info" variant="tonal" class="cursor-pointer font-weight-medium" @click="applyPreset('a5')">
                Kertas A5 (Faktur)
              </VChip>
            </div>
          </div>

          <VRow>
            <VCol cols="12" md="6">
              <label class="text-caption font-weight-bold text-high-emphasis mb-1 d-block">Nama Profil</label>
              <VTextField
                v-model="editedItem.name"
                placeholder="Contoh: Thermal Kasir 58mm"
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
                placeholder="Contoh: 58mm atau 80mm"
                variant="outlined"
                density="comfortable"
                rounded="lg"
                hide-details
              />
            </VCol>

            <VCol cols="12">
              <label class="text-caption font-weight-bold text-high-emphasis mb-2 d-block">Margin Kertas (mm)</label>
              <VRow>
                <VCol cols="6" sm="3">
                  <VTextField v-model="editedItem.margin_top" label="Atas (Top)" type="number" variant="outlined" density="compact" rounded="lg" hide-details />
                </VCol>
                <VCol cols="6" sm="3">
                  <VTextField v-model="editedItem.margin_bottom" label="Bawah (Bottom)" type="number" variant="outlined" density="compact" rounded="lg" hide-details />
                </VCol>
                <VCol cols="6" sm="3">
                  <VTextField v-model="editedItem.margin_left" label="Kiri (Left)" type="number" variant="outlined" density="compact" rounded="lg" hide-details />
                </VCol>
                <VCol cols="6" sm="3">
                  <VTextField v-model="editedItem.margin_right" label="Kanan (Right)" type="number" variant="outlined" density="compact" rounded="lg" hide-details />
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
                label="Jadikan Default POS"
                color="primary"
                hide-details
              />
            </VCol>
          </VRow>
        </VCardText>

        <VCardActions class="pa-0 d-flex justify-end gap-2">
          <VBtn variant="tonal" color="secondary" @click="close">Batal</VBtn>
          <VBtn color="primary" class="font-weight-bold" :loading="loading" @click="save">
            Simpan Pengaturan
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Dialog Delete -->
    <VDialog v-model="dialogDelete" max-width="440px">
      <VCard class="rounded-2xl pa-6 text-center">
        <VAvatar color="error" variant="tonal" size="64" class="mx-auto mb-4">
          <VIcon icon="ri-delete-bin-line" size="36" />
        </VAvatar>
        <h3 class="text-h6 font-weight-bold mb-2">
          Hapus Profil Printer?
        </h3>
        <p class="text-body-2 text-medium-emphasis mb-5">
          Apakah Anda yakin ingin menghapus pengaturan struk <strong>{{ itemToDelete?.name }}</strong>?
        </p>
        <div class="d-flex justify-center gap-2">
          <VBtn variant="tonal" color="secondary" @click="closeDelete">Batal</VBtn>
          <VBtn color="error" class="font-weight-bold" :loading="loading" @click="confirmDelete">
            Ya, Hapus
          </VBtn>
        </div>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.bg-primary-lighten-5 {
  background-color: rgba(var(--v-theme-primary), 0.08);
}
</style>
