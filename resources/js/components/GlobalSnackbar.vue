<script setup>
import { useSnackbarStore } from '@/stores/snackbar'

const snackbarStore = useSnackbarStore()
</script>

<template>
  <VSnackbar
    v-model="snackbarStore.isVisible"
    :color="snackbarStore.color"
    :timeout="snackbarStore.timeout"
    location="top end"
    variant="flat"
    class="v-snackbar-global"
  >
    <div class="d-flex align-center gap-2">
      <VIcon
        v-if="snackbarStore.color === 'success'"
        icon="ri-checkbox-circle-line"
      />
      <VIcon
        v-else-if="snackbarStore.color === 'error'"
        icon="ri-error-warning-line"
      />
      <VIcon
        v-else-if="snackbarStore.color === 'warning'"
        icon="ri-alert-line"
      />
      <VIcon
        v-else
        icon="ri-information-line"
      />
      
      <span class="text-body-1 text-white font-weight-medium">{{ snackbarStore.message }}</span>
    </div>

    <template #actions>
      <IconBtn
        color="white"
        @click="snackbarStore.hide()"
      >
        <VIcon icon="ri-close-line" />
      </IconBtn>
    </template>
  </VSnackbar>
</template>

<style lang="scss">
.v-snackbar-global {
  z-index: 9999 !important;
}
</style>
