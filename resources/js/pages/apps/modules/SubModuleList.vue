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
])

const refSubList = ref()
const localChildren = ref(props.childrenList)

dragAndDrop({
  parent: refSubList,
  values: localChildren,
  group: 'level2',
  draggable: child => child.classList.contains('tree-grid-level2'),
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
    class="tree-list-container"
  >
    <li
      v-for="(sub, index) in localChildren"
      :key="sub.id"
      class="tree-grid-level2"
    >
      <!-- Level 2 Row -->
      <div class="tree-grid-row bg-surface">
        <!-- Column: Name -->
        <div class="d-flex align-center tree-line-container">
          <div
            class="tree-line"
            :class="[{ 'is-last': index === localChildren.length - 1 }]"
          />
          
          <VIcon
            icon="ri-draggable"
            size="20"
            class="tree-grid-drag-handle ml-8"
          />
          <VIcon
            :icon="sub.icon || 'ri-circle-line'"
            size="18"
            class="mr-2 text-secondary"
          />
          <span class="text-body-2 text-high-emphasis">{{ sub.name }}</span>
        </div>
        
        <!-- Column: Slug -->
        <div class="text-body-2 text-disabled text-truncate">
          {{ sub.slug || '-' }}
        </div>
        
        <!-- Column: Icon Text -->
        <div class="text-body-2 text-disabled text-truncate">
          {{ sub.icon || '-' }}
        </div>
        
        <!-- Column: Sequence -->
        <div class="text-body-2 text-center">
          {{ sub.sequence }}
        </div>
        
        <!-- Column: Actions -->
        <div class="text-center">
          <IconBtn
            size="small"
            @click.stop="emit('editModule', sub)"
          >
            <VIcon icon="ri-pencil-line" />
          </IconBtn>
          <IconBtn
            size="small"
            color="error"
            @click.stop="emit('deleteModule', sub.id)"
          >
            <VIcon icon="ri-delete-bin-line" />
          </IconBtn>
        </div>
      </div>
    </li>
  </ul>
</template>

<style lang="scss">
.tree-grid-level2 {
  background: rgb(var(--v-theme-surface));
  position: relative;
}

.tree-line-container {
  position: relative;
}

.tree-line {
  position: absolute;
  left: 20px;
  top: -24px; /* Extends up to connect to parent */
  bottom: 0;
  width: 20px;
  border-left: 2px solid rgba(var(--v-theme-on-surface), 0.15);
  
  /* Horizontal line to the item */
  &::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    width: 16px;
    border-top: 2px solid rgba(var(--v-theme-on-surface), 0.15);
  }
}

/* If it's the last child, the vertical line shouldn't go past the middle */
.tree-line.is-last {
  bottom: 50%;
}
</style>
