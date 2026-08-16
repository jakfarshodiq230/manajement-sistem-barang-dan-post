<script setup>
import {
  animations,
  handleEnd,
  performTransfer,
} from '@formkit/drag-and-drop'
import { dragAndDrop } from '@formkit/drag-and-drop/vue'
import SubModuleList from './SubModuleList.vue'
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
})

const emit = defineEmits([
  'editModule',
  'deleteModule',
  'updateItemsState',
  'updateSubItemsState',
])

const refLevel1List = ref()
const localModules = ref(props.modulesList)

dragAndDrop({
  parent: refLevel1List,
  values: localModules,
  group: 'level1',
  draggable: child => child.classList.contains('tree-grid-level1'),
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
  <div class="tree-grid-category-group">
    <!-- Category Header Row -->
    <div class="tree-grid-row bg-var-theme-background font-weight-bold">
      <div
        style="grid-column: 1 / -1;"
        class="d-flex align-center gap-2"
      >
        <VIcon
          icon="ri-folder-2-line"
          size="20"
          class="text-primary"
        />
        <span class="text-primary">{{ category || 'Tanpa Kategori' }}</span>
      </div>
    </div>

    <!-- Level 1 List (Draggable Container) -->
    <ul
      ref="refLevel1List"
      class="tree-list-container"
    >
      <li
        v-for="module in localModules"
        :key="module.id"
        class="tree-grid-level1"
      >
        <!-- Level 1 Row -->
        <div class="tree-grid-row bg-surface">
          <!-- Column: Name -->
          <div class="d-flex align-center">
            <VIcon
              icon="ri-draggable"
              size="20"
              class="tree-grid-drag-handle"
            />
            <VIcon
              :icon="module.icon || 'ri-circle-line'"
              size="20"
              class="mr-2 text-secondary"
            />
            <span class="text-body-1 font-weight-semibold">{{ module.name }}</span>
          </div>
          
          <!-- Column: Slug -->
          <div class="text-body-2 text-disabled text-truncate">
            {{ module.slug || '-' }}
          </div>
          
          <!-- Column: Icon Text -->
          <div class="text-body-2 text-disabled text-truncate">
            {{ module.icon || '-' }}
          </div>
          
          <!-- Column: Sequence -->
          <div class="text-body-2 text-center">
            {{ module.sequence }}
          </div>
          
          <!-- Column: Actions -->
          <div class="text-center">
            <IconBtn
              size="small"
              @click.stop="emit('editModule', module)"
            >
              <VIcon icon="ri-pencil-line" />
            </IconBtn>
            <IconBtn
              size="small"
              color="error"
              @click.stop="emit('deleteModule', module.id)"
            >
              <VIcon icon="ri-delete-bin-line" />
            </IconBtn>
          </div>
        </div>

        <!-- Level 2 List -->
        <SubModuleList 
          v-if="module.children && module.children.length > 0"
          :parent-id="module.id"
          :children-list="module.children"
          @edit-module="m => emit('editModule', m)"
          @delete-module="id => emit('deleteModule', id)"
          @update-sub-items-state="data => emit('updateSubItemsState', data)"
        />
      </li>
    </ul>
  </div>
</template>

<style lang="scss">
.tree-grid-category-group {
  margin-bottom: 16px;
}
.tree-list-container {
  list-style: none;
  padding: 0;
  margin: 0;
}
.tree-grid-level1 {
  background: rgb(var(--v-theme-surface));
}
</style>
