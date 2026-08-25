<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { VForm } from 'vuetify/components/VForm'
import { useSnackbarStore } from '@/stores/snackbar'

const props = defineProps({
  rolePermissions: {
    type: Object,
    required: false,
    default: () => ({
      name: '',
      permissions: [],
    }),
  },
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
})

const emit = defineEmits([
  'update:isDialogVisible',
  'update:rolePermissions',
])

const snackbar = useSnackbarStore()

const permissions = ref([])
const searchQuery = ref('')
const role = ref('')
const isLoading = ref(false)
const refPermissionForm = ref()

const availableActions = [
  { key: 'read', label: 'Lihat', color: 'primary', icon: 'ri-eye-line' },
  { key: 'create', label: 'Tambah', color: 'success', icon: 'ri-add-line' },
  { key: 'write', label: 'Ubah', color: 'warning', icon: 'ri-edit-line' },
  { key: 'delete', label: 'Hapus', color: 'error', icon: 'ri-delete-bin-line' },
  { key: 'validate', label: 'Validasi', color: 'info', icon: 'ri-checkbox-circle-line' },
  { key: 'approve', label: 'Approval', color: 'secondary', icon: 'ri-check-double-line' },
  { key: 'export', label: 'Ekspor', color: 'info', icon: 'ri-file-download-line' },
  { key: 'import', label: 'Impor', color: 'info', icon: 'ri-file-upload-line' },
  { key: 'pin', label: 'PIN Otorisasi', color: 'error', icon: 'ri-key-2-line' },
]

const fetchModules = async () => {
  try {
    const data = await $api('/apps/modules?all=true')
    const dData = data.data || data

    permissions.value = dData.map(module => {
      const permObj = {
        name: module.name,
        slug: module.slug || module.name.toLowerCase().replace(/\s+/g, '-'),
      }

      availableActions.forEach(action => {
        permObj[action.key] = false
      })
      
      return permObj
    })

    applyRolePermissions()
  } catch (error) {
    console.error('Failed to fetch modules:', error)
  }
}

onMounted(() => {
  fetchModules()
})

const applyRolePermissions = () => {
  if (props.rolePermissions && props.rolePermissions.name) {
    role.value = props.rolePermissions.name
    
    const rolePerms = (props.rolePermissions.permissions || []).map(p => p.name || p)
    
    permissions.value.forEach(permission => {
      availableActions.forEach(action => {
        const actionCap = action.key === 'pin' ? 'PIN' : (action.key.charAt(0).toUpperCase() + action.key.slice(1))
        const actionCapAlt = action.key.charAt(0).toUpperCase() + action.key.slice(1)
        const fullPermName = `${permission.name} ${actionCap}`
        const fullPermNameAlt = `${permission.name} ${actionCapAlt}`
        permission[action.key] = rolePerms.includes(fullPermName) || rolePerms.includes(fullPermNameAlt)
      })
    })
  } else {
    role.value = ''
    permissions.value.forEach(permission => {
      availableActions.forEach(action => {
        permission[action.key] = false
      })
    })
  }
}

watch(() => props.rolePermissions, () => {
  applyRolePermissions()
}, { deep: true })

const totalCheckedCount = computed(() => {
  let counter = 0
  permissions.value.forEach(p => {
    availableActions.forEach(a => {
      if (p[a.key]) counter++
    })
  })
  return counter
})

const totalPossibleCount = computed(() => permissions.value.length * availableActions.length)

const isAllSelected = computed(() => {
  return totalPossibleCount.value > 0 && totalCheckedCount.value === totalPossibleCount.value
})

const selectAllGlobal = val => {
  permissions.value.forEach(p => {
    availableActions.forEach(a => {
      p[a.key] = val
    })
  })
}

const toggleRowAll = (permission, val) => {
  availableActions.forEach(a => {
    permission[a.key] = val
  })
}

const isRowAllSelected = permission => {
  return availableActions.every(a => permission[a.key])
}

const isRowSomeSelected = permission => {
  const count = availableActions.filter(a => permission[a.key]).length
  return count > 0 && count < availableActions.length
}

// Presets
const applyPreset = presetType => {
  selectAllGlobal(false)
  if (presetType === 'super') {
    selectAllGlobal(true)
  } else if (presetType === 'kasir') {
    permissions.value.forEach(p => {
      const name = p.name.toLowerCase()
      if (name.includes('pos') || name.includes('kasir') || name.includes('transaksi') || name.includes('katalog')) {
        p.read = true
        p.create = true
        p.export = true
      }
      if (name.includes('pelanggan') || name.includes('produk')) {
        p.read = true
      }
    })
  } else if (presetType === 'gudang') {
    permissions.value.forEach(p => {
      const name = p.name.toLowerCase()
      if (name.includes('stok') || name.includes('penerimaan') || name.includes('mutasi') || name.includes('gudang') || name.includes('opname')) {
        p.read = true
        p.create = true
        p.write = true
        p.validate = true
        p.export = true
      }
      if (name.includes('produk') || name.includes('kategori') || name.includes('supplier')) {
        p.read = true
      }
    })
  }
  snackbar.show(`Preset izin ${presetType.toUpperCase()} berhasil diterapkan`, 'info')
}

// Filtered permissions by search
const filteredPermissions = computed(() => {
  if (!searchQuery.value) return permissions.value
  const q = searchQuery.value.toLowerCase().trim()
  return permissions.value.filter(p => p.name.toLowerCase().includes(q))
})

const onSubmit = async () => {
  if (!role.value.trim()) {
    snackbar.show('Nama peran wajib diisi', 'error')
    return
  }

  isLoading.value = true
  const payload = {
    name: role.value,
    permissions: permissions.value.flatMap(p => {
      const perms = []
      availableActions.forEach(action => {
        if (p[action.key]) {
          const actionCapitalized = action.key.charAt(0).toUpperCase() + action.key.slice(1)
          perms.push(`${p.name} ${actionCapitalized}`)
        }
      })
      return perms
    }),
  }

  try {
    if (props.rolePermissions?.id) {
      await $api(`/apps/roles/${props.rolePermissions.id}`, { method: 'PUT', body: payload })
    } else {
      await $api('/apps/roles', { method: 'POST', body: payload })
    }
    
    snackbar.show('Hak akses peran berhasil disimpan!', 'success')
    emit('update:rolePermissions', payload)
    emit('update:isDialogVisible', false)
    
    // Refresh page / roles
    window.location.reload()
  } catch (e) {
    console.error(e)
    const msg = e.response?._data?.message || 'Gagal menyimpan hak akses peran'
    snackbar.show(msg, 'error')
  } finally {
    isLoading.value = false
  }
}

const onReset = () => {
  emit('update:isDialogVisible', false)
}
</script>

<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 980"
    :model-value="props.isDialogVisible"
    scrollable
    @update:model-value="onReset"
  >
    <VCard class="rounded-2xl overflow-hidden elevation-10">
      <!-- Header -->
      <VCardItem class="pa-5 bg-surface border-b">
        <div class="d-flex align-center justify-space-between w-100">
          <div class="d-flex align-center gap-3">
            <VAvatar color="primary" variant="tonal" size="48" rounded="xl">
              <VIcon icon="ri-shield-keyhole-line" size="26" />
            </VAvatar>
            <div>
              <h3 class="text-h6 font-weight-bold mb-0">
                {{ props.rolePermissions?.name ? `Edit Hak Akses: ${props.rolePermissions.name}` : 'Tambah Peran Baru' }}
              </h3>
              <p class="text-caption text-medium-emphasis mb-0">
                Tentukan hak otorisasi modul untuk peran ini dalam sistem POS & inventaris.
              </p>
            </div>
          </div>

          <DialogCloseBtn variant="text" size="small" @click="onReset" />
        </div>
      </VCardItem>

      <!-- Body Content -->
      <VCardText class="pa-6 overflow-y-auto" style="max-height: 70vh;">
        <VForm ref="refPermissionForm" @submit.prevent="onSubmit">
          <!-- Role Name & Presets -->
          <VRow class="mb-4">
            <VCol cols="12" md="6">
              <label class="text-caption font-weight-bold text-high-emphasis mb-1 d-block">
                Nama Peran (Role Name) <span class="text-error">*</span>
              </label>
              <VTextField
                v-model="role"
                placeholder="Contoh: Kasir Senior, Admin Gudang, Supervisor"
                prepend-inner-icon="ri-shield-user-line"
                variant="outlined"
                density="comfortable"
                rounded="lg"
                hide-details="auto"
                :disabled="props.rolePermissions?.name === 'Super Admin'"
              />
            </VCol>

            <!-- Quick Preset Chips -->
            <VCol cols="12" md="6">
              <label class="text-caption font-weight-bold text-medium-emphasis mb-1 d-block">
                Template Preset Cepat:
              </label>
              <div class="d-flex flex-wrap gap-2 pt-1">
                <VChip
                  size="small"
                  color="primary"
                  variant="tonal"
                  class="cursor-pointer font-weight-medium"
                  @click="applyPreset('super')"
                >
                  <VIcon icon="ri-vip-crown-line" size="13" class="me-1" />
                  Semua Izin (Super)
                </VChip>
                <VChip
                  size="small"
                  color="success"
                  variant="tonal"
                  class="cursor-pointer font-weight-medium"
                  @click="applyPreset('kasir')"
                >
                  <VIcon icon="ri-shopping-cart-2-line" size="13" class="me-1" />
                  Preset Kasir POS
                </VChip>
                <VChip
                  size="small"
                  color="warning"
                  variant="tonal"
                  class="cursor-pointer font-weight-medium"
                  @click="applyPreset('gudang')"
                >
                  <VIcon icon="ri-archive-stack-line" size="13" class="me-1" />
                  Preset Gudang & Stok
                </VChip>
              </div>
            </VCol>
          </VRow>

          <VDivider class="mb-4" />

          <!-- Permissions Toolbar -->
          <div class="d-flex align-center justify-space-between flex-wrap gap-3 mb-4">
            <div class="d-flex align-center gap-3">
              <h4 class="text-subtitle-1 font-weight-bold text-high-emphasis mb-0">
                Matriks Hak Akses Modul
              </h4>
              <VChip size="small" color="primary" variant="elevated" class="font-weight-bold">
                {{ totalCheckedCount }} / {{ totalPossibleCount }} Izin Aktif
              </VChip>
            </div>

            <div class="d-flex align-center gap-2">
              <VTextField
                v-model="searchQuery"
                placeholder="Cari modul..."
                prepend-inner-icon="ri-search-line"
                density="compact"
                variant="outlined"
                rounded="lg"
                hide-details
                style="width: 220px;"
                clearable
              />

              <VBtn
                size="small"
                :color="isAllSelected ? 'error' : 'primary'"
                variant="tonal"
                class="font-weight-bold text-none"
                @click="selectAllGlobal(!isAllSelected)"
              >
                {{ isAllSelected ? 'Batal Pilih Semua' : 'Pilih Semua Izin' }}
              </VBtn>
            </div>
          </div>

          <!-- Permissions Matrix Table -->
          <VCard class="border rounded-xl overflow-hidden mb-2" :loading="isLoading">
            <VProgressLinear v-if="isLoading" indeterminate color="primary" height="2" />
            <VTable class="permissions-matrix-table text-no-wrap" density="comfortable">
              <thead>
                <tr class="bg-var-theme-background">
                  <th class="font-weight-bold text-high-emphasis" style="min-width: 220px;">
                    Nama Modul Sistem
                  </th>
                  <th class="text-center" style="width: 70px;">
                    Pilih Baris
                  </th>
                  <th
                    v-for="action in availableActions"
                    :key="action.key"
                    class="text-center font-weight-bold text-caption text-high-emphasis"
                  >
                    <div class="d-flex flex-column align-center">
                      <VIcon :icon="action.icon" size="14" :color="action.color" class="mb-0" />
                      <span>{{ action.label }}</span>
                    </div>
                  </th>
                </tr>
              </thead>

              <tbody>
                <tr v-if="isLoading">
                  <td :colspan="2 + availableActions.length" class="text-center py-6 text-medium-emphasis">
                    <VProgressCircular indeterminate color="primary" size="24" class="me-2" />
                    <span>Memuat daftar modul hak akses...</span>
                  </td>
                </tr>
                <tr v-else-if="filteredPermissions.length === 0">
                  <td :colspan="2 + availableActions.length" class="text-center py-6 text-medium-emphasis">
                    Modul tidak ditemukan
                  </td>
                </tr>
                <tr
                  v-for="p in filteredPermissions"
                  :key="p.name"
                  class="permission-row"
                >
                  <!-- Module Name -->
                  <td>
                    <div class="d-flex align-center gap-2">
                      <VIcon icon="ri-folder-keyhole-line" size="18" color="primary" />
                      <span class="font-weight-bold text-subtitle-2">{{ p.name }}</span>
                    </div>
                  </td>

                  <!-- Row Select All -->
                  <td class="text-center">
                    <VCheckbox
                      :model-value="isRowAllSelected(p)"
                      :indeterminate="isRowSomeSelected(p)"
                      density="compact"
                      hide-details
                      color="primary"
                      class="d-inline-flex justify-center"
                      @update:model-value="val => toggleRowAll(p, val)"
                    />
                  </td>

                  <!-- Action Checkboxes -->
                  <td
                    v-for="action in availableActions"
                    :key="action.key"
                    class="text-center"
                  >
                    <VCheckbox
                      v-model="p[action.key]"
                      density="compact"
                      hide-details
                      :color="action.color"
                      class="d-inline-flex justify-center"
                    />
                  </td>
                </tr>

                <tr v-if="filteredPermissions.length === 0">
                  <td :colspan="availableActions.length + 2" class="text-center py-6 text-medium-emphasis">
                    Tidak ada modul yang cocok dengan pencarian "{{ searchQuery }}".
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCard>
        </VForm>
      </VCardText>

      <VDivider />

      <!-- Footer Actions -->
      <VCardActions class="pa-4 bg-surface d-flex justify-end gap-2">
        <VBtn
          variant="tonal"
          color="secondary"
          class="font-weight-medium"
          @click="onReset"
        >
          Batal
        </VBtn>

        <VBtn
          color="primary"
          :loading="isLoading"
          class="font-weight-bold px-6"
          prepend-icon="ri-save-line"
          @click="onSubmit"
        >
          Simpan Hak Akses Peran
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.permissions-matrix-table th {
  padding: 10px 12px !important;
  font-size: 12px;
}

.permissions-matrix-table td {
  padding: 8px 12px !important;
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.permission-row:hover {
  background-color: rgba(var(--v-theme-primary), 0.03);
}
</style>
