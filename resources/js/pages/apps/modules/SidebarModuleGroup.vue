<script setup>
import {
  animations,
  handleEnd,
  performTransfer,
} from '@formkit/drag-and-drop'
import { dragAndDrop } from '@formkit/drag-and-drop/vue'
import SidebarSubModuleList from './SidebarSubModuleList.vue'
import { ref, watch } from 'vue'

const props = defineProps({
  category: {
    type: String,
    required: true,
  },
  modulesList: {
    type: Array,
    required: true,
  },
  selectedGroupNodeId: {
    type: Number,
    default: null,
  },
})

const emit = defineEmits([
  'editModule',
  'deleteModule',
  'updateItemsState',
  'updateSubItemsState',
  'selectNode',
  'selectModuleDetail',
])

const refLevel1List = ref()
const localModules = ref(props.modulesList)

dragAndDrop({
  parent: refLevel1List,
  values: localModules,
  group: 'sidebarLevel1',
  draggable: child => child.classList.contains('sidebar-level1-li'),
  plugins: [animations()],
  performTransfer: (state, data) => {
    performTransfer(state, data)
    emit('updateItemsState', {
      category: props.category,
      items: localModules.value,
    })
  },
  handleEnd: data => {
    handleEnd(data)
    emit('updateItemsState', {
      category: props.category,
      items: localModules.value,
    })
  },
})

watch(() => props.modulesList, () => {
  localModules.value = props.modulesList
}, { immediate: true, deep: true })
</script>

<template>
  <!-- Category Root -->
  <div class="mb-2">
    <div class="d-flex align-center px-3 py-2 rounded">
      <div
        class="category-drag-handle d-flex align-center mr-1"
        style="cursor: grab;"
      >
        <VIcon
          icon="ri-draggable"
          size="20"
          class="text-disabled"
        />
      </div>
      <VIcon
        icon="ri-folder-2-line"
        size="20"
        class="mr-2 text-primary"
      />
      <span class="font-weight-semibold text-primary">{{ category || 'Kategori' }}</span>
    </div>

    <!-- Level 1 List -->
    <ul
      ref="refLevel1List"
      class="tree-children-container pl-0 mb-0"
    >
      <li
        v-for="module in localModules"
        :key="module.id"
        class="sidebar-level1-li"
      >
        <div
          class="tree-item d-flex align-center justify-space-between rounded mb-1 px-2 py-1"
          :class="{ 'bg-surface-variant': selectedGroupNodeId === module.id }"
          @click="emit('selectNode', module)"
        >
          <div class="d-flex align-center overflow-hidden">
            <VIcon
              icon="ri-draggable"
              size="16"
              class="mr-1 text-disabled drag-handle"
            />
            <VIcon
              :icon="module.icon || 'ri-layout-masonry-line'"
              size="16"
              class="mr-2"
            />
            <span class="text-body-2 text-truncate font-weight-medium">{{ module.name }}</span>
          </div>
          
          <div class="sidebar-actions d-flex align-center gap-1">
            <VBadge
              v-if="module.children && module.children.length > 0"
              :content="module.children.length"
              color="success"
              inline
              class="mr-1"
            />
            <IconBtn
              size="x-small"
              color="secondary"
              @click.stop="emit('editModule', module)"
            >
              <VIcon
                icon="ri-pencil-line"
                size="14"
              />
            </IconBtn>
            <IconBtn
              size="x-small"
              color="error"
              @click.stop="emit('deleteModule', module.id)"
            >
              <VIcon
                icon="ri-delete-bin-line"
                size="14"
              />
            </IconBtn>
          </div>
        </div>

        <!-- Level 2 List -->
        <SidebarSubModuleList
          v-if="module.children && module.children.length > 0"
          :parent-id="module.id"
          :children-list="module.children"
          @edit-module="m => emit('editModule', m)"
          @delete-module="id => emit('deleteModule', id)"
          @update-sub-items-state="data => emit('updateSubItemsState', data)"
          @select-module-detail="m => emit('selectModuleDetail', m)"
        />
      </li>
    </ul>
  </div>
</template>

<style lang="scss" scoped>
.sidebar-level1-li {
  list-style: none;
}
.tree-item {
  cursor: pointer;
  transition: background-color 0.2s;
  
  &:hover {
    background-color: rgba(var(--v-theme-on-surface), 0.04);
    
    .sidebar-actions .v-btn {
      opacity: 1 !important;
    }
  }

  .drag-handle {
    cursor: grab;
    &:active {
      cursor: grabbing;
    }
  }

  .sidebar-actions .v-btn {
    opacity: 0;
    transition: opacity 0.2s;
  }
}
</style>
