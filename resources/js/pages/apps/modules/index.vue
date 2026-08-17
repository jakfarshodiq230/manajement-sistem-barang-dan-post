<script setup>
import { ref, computed, onMounted } from 'vue'
import SidebarModuleGroup from './SidebarModuleGroup.vue'
import AddNewModuleDrawer from './AddNewModuleDrawer.vue'
import SimpleConfirmDialog from '@/components/dialogs/SimpleConfirmDialog.vue'
import { useSnackbarStore } from '@/stores/snackbar'

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
  const active = modules.value.filter(m => m.status === 'Aktif').length
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
  
  // Also filter top-level by search
  let filteredTopLevel = topLevel
  if (search.value) {
    // Basic search filtering
    filteredTopLevel = topLevel.filter(m => m.name.toLowerCase().includes(search.value.toLowerCase()))
  }
  
  filteredTopLevel.forEach(m => {
    let cat = m.category || ''
    if (!groupsMap[cat]) {
      groupsMap[cat] = { category: cat, modules: [] }
      groupsArray.push(groupsMap[cat])
    }
    
    let children = modules.value.filter(child => child.parent_id === m.id)
    children.sort((a, b) => a.sequence - b.sequence)
    
    groupsMap[cat].modules.push({ ...m, children })
  })
  
  groupsArray.forEach(group => {
    group.modules.sort((a, b) => a.sequence - b.sequence)
  })
  
  return groupsArray
})

import { dragAndDrop } from '@formkit/drag-and-drop/vue'
import { animations, handleEnd } from '@formkit/drag-and-drop'

const refCategoryList = ref()
const localCategories = ref([])

watch(moduleTree, newVal => {
  localCategories.value = JSON.parse(JSON.stringify(newVal))
}, { immediate: true })

onMounted(() => {
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
  // Flatten all modules from localCategories into a single array with continuous sequence
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
    snackbar.show('Urutan modul tersimpan', 'success', 2000)


    // Silent fetch so we don't jank the UI too much, but ensures sync
    const data = await $api('/apps/modules?all=true')

    modules.value = data.data || data
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal menyimpan urutan', 'error')
    fetchModules()
  }
}

const updateItemsState = async ({ category, items }) => {
  // A category's items were dragged. 
  
  // Remove these items from any OTHER category to prevent duplication during cross-category drags
  items.forEach(item => {
    localCategories.value.forEach(g => {
      if (g.category !== category) {
        g.modules = g.modules.filter(m => m.id !== item.id)
      }
    })
  })

  // Update the localCategories array for this specific category
  const groupIndex = localCategories.value.findIndex(g => g.category === category)
  if (groupIndex !== -1) {
    localCategories.value[groupIndex].modules = items
  } else {
    // If it was dragged to a new empty space
    localCategories.value.push({ category, modules: items })
  }
  
  // Recalculate global order!
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
    <!-- Header with Buttons -->
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h4 class="text-h4 mb-1">
          Manajemen Modul
        </h4>
        <p class="text-body-1 text-disabled mb-0">
          Kelola struktur, akses, dan pengaturan modul aplikasi
        </p>
      </div>
      <div class="d-flex gap-3">
        <VBtn
          v-if="$can('create', 'Modules')"
          prepend-icon="ri-add-line"
          @click="openAddDrawer"
        >
          Tambah Modul
        </VBtn>
      </div>
    </div>

    <!-- Stats Cards -->
    <VRow class="mb-4">
      <VCol
        cols="12"
        md="3"
      >
        <VCard
          elevation="0"
          class="border"
        >
          <VCardText class="d-flex align-center gap-4">
            <VAvatar
              color="primary"
              variant="tonal"
              rounded
              size="54"
            >
              <VIcon
                icon="ri-apps-2-line"
                size="28"
              />
            </VAvatar>
            <div>
              <div class="text-subtitle-2 text-disabled">
                Total Modul
              </div>
              <div class="text-h4 font-weight-bold">
                {{ stats.total }}
              </div>
              <div class="text-caption text-disabled mt-1">
                Semua modul
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol
        cols="12"
        md="3"
      >
        <VCard
          elevation="0"
          class="border"
        >
          <VCardText class="d-flex align-center gap-4">
            <VAvatar
              color="success"
              variant="tonal"
              rounded
              size="54"
            >
              <VIcon
                icon="ri-checkbox-circle-line"
                size="28"
              />
            </VAvatar>
            <div>
              <div class="text-subtitle-2 text-disabled">
                Modul Aktif
              </div>
              <div class="text-h4 font-weight-bold">
                {{ stats.active }}
              </div>
              <div class="text-caption text-disabled mt-1">
                Modul sedang aktif
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol
        cols="12"
        md="3"
      >
        <VCard
          elevation="0"
          class="border"
        >
          <VCardText class="d-flex align-center gap-4">
            <VAvatar
              color="warning"
              variant="tonal"
              rounded
              size="54"
            >
              <VIcon
                icon="ri-group-line"
                size="28"
              />
            </VAvatar>
            <div>
              <div class="text-subtitle-2 text-disabled">
                Hak Akses
              </div>
              <div class="text-h4 font-weight-bold">
                {{ stats.roleCount }}
              </div>
              <div class="text-caption text-disabled mt-1">
                Role memiliki akses
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol
        cols="12"
        md="3"
      >
        <VCard
          elevation="0"
          class="border"
        >
          <VCardText class="d-flex align-center gap-4">
            <VAvatar
              color="info"
              variant="tonal"
              rounded
              size="54"
            >
              <VIcon
                icon="ri-folder-2-line"
                size="28"
              />
            </VAvatar>
            <div>
              <div class="text-subtitle-2 text-disabled">
                Grup Modul
              </div>
              <div class="text-h4 font-weight-bold">
                {{ stats.groupCount }}
              </div>
              <div class="text-caption text-disabled mt-1">
                Grup kategori modul
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- 1-Column Main Layout (Expanded Tree) -->
    <VRow justify="center">
      <VCol
        cols="12"
        md="12"
        lg="12"
      >
        <VCard
          elevation="0"
          class="border h-100"
        >
          <VCardText>
            <div class="d-flex justify-space-between align-center mb-6">
              <div>
                <h6 class="text-h6 mb-1">
                  Struktur Modul
                </h6>
                <div class="text-caption text-disabled">
                  Tree struktur modul aplikasi
                </div>
              </div>
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
                <div class="category-draggable-wrapper cursor-move">
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
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Slide-out Drawer for Add/Edit -->
    <AddNewModuleDrawer
      v-model:is-drawer-open="isAddNewModuleDrawerVisible"
      :selected-module="selectedModuleDetail"
      @module-data="saveModule"
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

<route lang="yaml">
meta:
  action: read
  subject: Modules
</route>
