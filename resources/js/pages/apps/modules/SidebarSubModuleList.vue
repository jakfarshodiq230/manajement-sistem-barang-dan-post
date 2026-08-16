<script setup>
import {
  animations,
  handleEnd,
  performTransfer,
} from '@formkit/drag-and-drop'
import { dragAndDrop } from '@formkit/drag-and-drop/vue'
import { ref, watch } from 'vue'

const props = defineProps({
  parentId: {
    type: Number,
    required: true,
  },
  childrenList: {
    type: Array,
    required: true,
  },
})

const emit = defineEmits([
  'editModule',
  'deleteModule',
  'updateSubItemsState',
  'selectModuleDetail',
])

const refSubList = ref()
const localChildren = ref(props.childrenList)

dragAndDrop({
  parent: refSubList,
  values: localChildren,
  group: 'sidebarLevel2',
  draggable: child => child.classList.contains('sidebar-sub-item-li'),
  plugins: [animations()],
  performTransfer: (state, data) => {
    performTransfer(state, data)
    emit('updateSubItemsState', {
      parentId: props.parentId,
      items: localChildren.value,
    })
  },
  handleEnd: data => {
    handleEnd(data)
    emit('updateSubItemsState', {
      parentId: props.parentId,
      items: localChildren.value,
    })
  },
})

watch(() => props.childrenList, () => {
  localChildren.value = props.childrenList
}, { immediate: true, deep: true })
</script>

<template>
  <ul
    ref="refSubList"
    class="tree-subchildren-container pl-0 mb-0"
  >
    <li
      v-for="sub in localChildren"
      :key="sub.id"
      class="sidebar-sub-item-li"
    >
      <div
        class="tree-sub-item d-flex align-center justify-space-between rounded mb-1 px-2 py-1"
        @click="emit('selectModuleDetail', sub)"
      >
        <div class="d-flex align-center overflow-hidden">
          <VIcon
            icon="ri-draggable"
            size="14"
            class="mr-1 text-disabled drag-handle"
          />
          <VIcon
            :icon="sub.icon || 'ri-circle-line'"
            size="14"
            class="mr-2 text-disabled"
          />
          <span class="text-body-2 text-truncate">{{ sub.name }}</span>
        </div>
        <div class="sidebar-actions d-flex gap-1">
          <IconBtn
            size="x-small"
            color="secondary"
            @click.stop="emit('editModule', sub)"
          >
            <VIcon
              icon="ri-pencil-line"
              size="14"
            />
          </IconBtn>
          <IconBtn
            size="x-small"
            color="error"
            @click.stop="emit('deleteModule', sub.id)"
          >
            <VIcon
              icon="ri-delete-bin-line"
              size="14"
            />
          </IconBtn>
        </div>
      </div>
    </li>
  </ul>
</template>

<style lang="scss" scoped>
.sidebar-sub-item-li {
  list-style: none;
}
.tree-sub-item {
  cursor: pointer;
  transition: background-color 0.2s;
  
  &:hover {
    background-color: rgba(var(--v-theme-on-surface), 0.04);
    
    .sidebar-actions {
      opacity: 1;
    }
  }

  .drag-handle {
    cursor: grab;
    &:active {
      cursor: grabbing;
    }
  }

  .sidebar-actions {
    opacity: 0;
    transition: opacity 0.2s;
  }
}
</style>
