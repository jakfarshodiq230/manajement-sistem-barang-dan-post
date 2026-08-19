<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import SidebarModuleGroup from './SidebarModuleGroup.vue'
import AddNewModuleDrawer from './AddNewModuleDrawer.vue'
import SimpleConfirmDialog from '@/components/dialogs/SimpleConfirmDialog.vue'
import { useSnackbarStore } from '@/stores/snackbar'
import { dragAndDrop } from '@formkit/drag-and-drop/vue'
import { animations, handleEnd } from '@formkit/drag-and-drop'

definePage({
  meta: {
    public: true,
  },
})

const modules = ref([])
const roles = ref([])
const isAddNewModuleDrawerVisible = ref(false)

const search = ref('')
const selectedGroupNodeId = ref(null) 
const selectedModuleDetail = ref(null) 

const isConfirmDeleteDialogVisible = ref(false)
const moduleToDelete = ref(null)

const snackbar = useSnackbarStore()

const fetchModules = async () => {
  try {
    const data = await $api('/apps/modules?all=true')
    modules.value = data.data || data
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal memuat modul', 'error')
  }
}

const fetchRoles = async () => {
  try {
    const data = await $api('/apps/roles')
    roles.value = data.data || data
  } catch (error) {
    console.error(error)
  }
}

onMounted(() => {
  fetchModules()
  fetchRoles()
})

const stats = computed(() => {
  const total = modules.value.length
  const active = modules.value.filter(m => m.status === 'Aktif' || !m.status).length
  const roleCount = roles.value.length
  const groupCount = new Set(modules.value.filter(m => m.category).map(m => m.category)).size
  
  return {
    total,
    active,
    roleCount,
    groupCount,
  }
})

// Build Tree for left column
const moduleTree = computed(() => {
  const groupsMap = {}
  const groupsArray = []
  
  const topLevel = modules.value.filter(m => !m.parent_id)
  
  let filteredTopLevel = topLevel
  if (search.value) {
    filteredTopLevel = topLevel.filter(m => m.name.toLowerCase().includes(search.value.toLowerCase()))
  }
  
  filteredTopLevel.forEach(m => {
    let cat = m.category || 'Modul Utama'
    if (!groupsMap[cat]) {
      groupsMap[cat] = { category: cat, modules: [] }
      groupsArray.push(groupsMap[cat])
    }
    
    let children = modules.value.filter(child => child.parent_id === m.id)
    children.sort((a, b) => (a.sequence || 0) - (b.sequence || 0))
    
    groupsMap[cat].modules.push({ ...m, children })
  })
  
  groupsArray.forEach(group => {
    group.modules.sort((a, b) => (a.sequence || 0) - (b.sequence || 0))
  })
  
  return groupsArray
})

const refCategoryList = ref()
const localCategories = ref([])

watch(moduleTree, newVal => {
  localCategories.value = JSON.parse(JSON.stringify(newVal))
}, { immediate: true })

onMounted(() => {
  if (refCategoryList.value) {
    dragAndDrop({
      parent: refCategoryList,
      values: localCategories,
      dragHandle: '.category-drag-handle',
      plugins: [animations()],
      handleEnd: data => {
        handleEnd(data)
        saveGlobalOrder()
      },
    })
  }
})

const selectNode = node => {
  selectedGroupNodeId.value = node.id
}

const openAddDrawer = () => {
  selectedModuleDetail.value = null
  isAddNewModuleDrawerVisible.value = true
}

const openEditDrawer = module => {
  selectedModuleDetail.value = JSON.parse(JSON.stringify(module))
  isAddNewModuleDrawerVisible.value = true
}

const saveModule = async moduleData => {
  try {
    if (selectedModuleDetail.value && selectedModuleDetail.value.id) {
      await $api(`/apps/modules/${selectedModuleDetail.value.id}`, {
        method: 'PUT',
        body: moduleData,
      })
      snackbar.show('Modul berhasil diperbarui', 'success')
    } else {
      await $api('/apps/modules', {
        method: 'POST',
        body: moduleData,
      })
      snackbar.show('Modul baru ditambahkan', 'success')
    }
    fetchModules()
    isAddNewModuleDrawerVisible.value = false
  } catch (error) {
    console.error(error)
    snackbar.show('Terjadi kesalahan saat menyimpan modul', 'error')
  }
}

const confirmDeleteModule = id => {
  moduleToDelete.value = id
  isConfirmDeleteDialogVisible.value = true
}

const executeDeleteModule = async isConfirmed => {
  if (!isConfirmed) return
  
  try {
    await $api(`/apps/modules/${moduleToDelete.value}`, { method: 'DELETE' })
    snackbar.show('Modul berhasil dihapus', 'success')
    fetchModules()
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal menghapus modul', 'error')
  } finally {
    moduleToDelete.value = null
  }
}

const saveGlobalOrder = async () => {
  let globalSequence = 0
  const payload = []

  localCategories.value.forEach(group => {
    group.modules.forEach(mod => {
      payload.push({
        id: mod.id,
        sequence: globalSequence++,
        category: group.category || null,
        parent_id: mod.parent_id || null,
      })
    })
  })

  try {
    await $api('/apps/modules/reorder', {
      method: 'POST',
      body: { modules: payload },
    })
    snackbar.show('Urutan modul berhasil disimpan', 'success', 2000)

    const data = await $api('/apps/modules?all=true')
    modules.value = data.data || data
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal menyimpan urutan', 'error')
    fetchModules()
  }
}

const updateItemsState = async ({ category, items }) => {
  items.forEach(item => {
    localCategories.value.forEach(g => {
      if (g.category !== category) {
        g.modules = g.modules.filter(m => m.id !== item.id)
      }
    })
  })

  const groupIndex = localCategories.value.findIndex(g => g.category === category)
  if (groupIndex !== -1) {
    localCategories.value[groupIndex].modules = items
  } else {
    localCategories.value.push({ category, modules: items })
  }
  
  saveGlobalOrder()
}

const updateSubItemsState = async ({ parentId, items }) => {
  const payload = items.map((item, index) => ({
    id: item.id,
    sequence: index,
    category: item.category || null,
    parent_id: parentId,
  }))

  try {
    await $api('/apps/modules/reorder', {
      method: 'POST',
      body: { modules: payload },
    })
    snackbar.show('Urutan sub-modul tersimpan', 'success', 2000)
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal menyimpan urutan', 'error')
    fetchModules()
  }
}
</script>

<template>
  <section class="module-dashboard">
    <!-- Header Banner -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-4 mb-6">
      <div>
        <div class="d-flex align-center gap-2 mb-1">
          <VChip color="primary" variant="tonal" size="small" class="font-weight-bold">
            <VIcon icon="ri-apps-2-line" size="14" class="me-1" />
            ARSITEKTUR APLIKASI
          </VChip>
        </div>
        <h1 class="text-h4 font-weight-extrabold text-high-emphasis mb-1">
          Manajemen Struktur Modul
        </h1>
        <p class="text-body-1 text-medium-emphasis mb-0">
          Kelola hierarki menu navigasi, kategori modul, dan integrasi hak akses sistem.
        </p>
      </div>

      <div class="d-flex gap-3">
        <VBtn
          color="primary"
          class="font-weight-bold text-none"
          prepend-icon="ri-add-line"
          @click="openAddDrawer"
        >
          Tambah Modul Baru
        </VBtn>
      </div>
    </div>

    <!-- Stats Cards -->
    <VRow class="mb-6">
      <VCol cols="12" sm="6" md="3">
        <VCard class="pa-5 rounded-xl border elevation-1">
          <div class="d-flex align-center gap-4">
            <VAvatar color="primary" variant="tonal" size="52" rounded="lg">
              <VIcon icon="ri-apps-2-line" size="28" />
            </VAvatar>
            <div>
              <h4 class="text-h5 font-weight-bold mb-0">{{ stats.total }}</h4>
              <span class="text-caption text-medium-emphasis">Total Seluruh Modul</span>
            </div>
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard class="pa-5 rounded-xl border elevation-1">
          <div class="d-flex align-center gap-4">
            <VAvatar color="success" variant="tonal" size="52" rounded="lg">
              <VIcon icon="ri-checkbox-circle-line" size="28" />
            </VAvatar>
            <div>
              <h4 class="text-h5 font-weight-bold mb-0">{{ stats.active }}</h4>
              <span class="text-caption text-medium-emphasis">Modul Berstatus Aktif</span>
            </div>
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard class="pa-5 rounded-xl border elevation-1">
          <div class="d-flex align-center gap-4">
            <VAvatar color="warning" variant="tonal" size="52" rounded="lg">
              <VIcon icon="ri-shield-user-line" size="28" />
            </VAvatar>
            <div>
              <h4 class="text-h5 font-weight-bold mb-0">{{ stats.roleCount }}</h4>
              <span class="text-caption text-medium-emphasis">Peran Terhubung (Roles)</span>
            </div>
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard class="pa-5 rounded-xl border elevation-1">
          <div class="d-flex align-center gap-4">
            <VAvatar color="info" variant="tonal" size="52" rounded="lg">
              <VIcon icon="ri-folder-2-line" size="28" />
            </VAvatar>
            <div>
              <h4 class="text-h5 font-weight-bold mb-0">{{ stats.groupCount }}</h4>
              <span class="text-caption text-medium-emphasis">Kategori Grup Modul</span>
            </div>
          </div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Main Tree Card -->
    <VCard class="rounded-xl border elevation-1">
      <VCardText class="pa-6">
        <div class="d-flex justify-space-between align-center flex-wrap gap-4 mb-6">
          <div>
            <h3 class="text-h6 font-weight-bold mb-1">
              Hierarki & Urutan Modul (Drag & Drop)
            </h3>
            <p class="text-caption text-medium-emphasis mb-0">
              Tarik dan lepaskan kartu modul untuk mengatur posisi urutan menu di sidebar secara instan.
            </p>
          </div>

          <VTextField
            v-model="search"
            placeholder="Cari nama modul..."
            prepend-inner-icon="ri-search-line"
            density="compact"
            variant="outlined"
            rounded="lg"
            clearable
            hide-details
            style="max-width: 260px;"
          />
        </div>

        <!-- Custom Interactive Tree List -->
        <div
          ref="refCategoryList"
          class="module-tree-list"
        >
          <template
            v-for="group in localCategories"
            :key="group.category"
          >
            <div class="category-draggable-wrapper cursor-move mb-4">
              <SidebarModuleGroup
                v-if="group.modules.length > 0"
                :category="group.category"
                :modules-list="group.modules"
                :selected-group-node-id="selectedGroupNodeId"
                @edit-module="openEditDrawer"
                @delete-module="confirmDeleteModule"
                @update-items-state="updateItemsState"
                @update-sub-items-state="updateSubItemsState"
                @select-node="selectNode"
                @select-module-detail="openEditDrawer"
              />
            </div>
          </template>

          <div v-if="localCategories.length === 0" class="text-center py-12 text-medium-emphasis">
            <VIcon icon="ri-folder-open-line" size="48" class="mb-2" />
            <p class="text-body-1 font-weight-medium">Tidak ada modul yang ditemukan.</p>
          </div>
        </div>
      </VCardText>
    </VCard>

    <!-- Slide-out Drawer for Add/Edit -->
    <AddNewModuleDrawer
      v-model:is-drawer-open="isAddNewModuleDrawerVisible"
      :selected-module="selectedModuleDetail"
      @module-data="saveModule"
    />

    <SimpleConfirmDialog
      v-model:is-dialog-visible="isConfirmDeleteDialogVisible"
      title="Hapus Modul Sistem"
      message="Apakah Anda yakin ingin menghapus modul ini? Seluruh sub-modul dan izin terkait akan ikut terhapus."
      confirm-text="Ya, Hapus"
      cancel-text="Batal"
      @confirm="executeDeleteModule"
    />
  </section>
</template>

<style lang="scss">
.module-dashboard {
  .tree-children-container {
    position: relative;
    margin-left: 12px;
    padding-left: 12px;
    border-left: 1px dashed rgba(var(--v-theme-on-surface), 0.2);
    
    .tree-item {
      position: relative;
      &::before {
        content: '';
        position: absolute;
        left: -12px;
        top: 50%;
        width: 12px;
        border-top: 1px dashed rgba(var(--v-theme-on-surface), 0.2);
      }
    }
  }

  .tree-subchildren-container {
    position: relative;
    margin-left: 24px;
    padding-left: 12px;
    border-left: 1px dashed rgba(var(--v-theme-on-surface), 0.2);
    
    .tree-sub-item {
      position: relative;
      &::before {
        content: '';
        position: absolute;
        left: -12px;
        top: 50%;
        width: 12px;
        border-top: 1px dashed rgba(var(--v-theme-on-surface), 0.2);
      }
    }
  }
}
</style>
