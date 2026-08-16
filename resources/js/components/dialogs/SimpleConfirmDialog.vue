<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  title: {
    type: String,
    default: 'Konfirmasi',
  },
  message: {
    type: String,
    required: true,
  },
  confirmText: {
    type: String,
    default: 'Ya, Lanjutkan',
  },
  cancelText: {
    type: String,
    default: 'Batal',
  },
  color: {
    type: String,
    default: 'error',
  },
})

const emit = defineEmits([
  'update:isDialogVisible',
  'confirm',
])

const updateModelValue = val => {
  emit('update:isDialogVisible', val)
}

const onConfirm = () => {
  emit('confirm', true)
  updateModelValue(false)
}

const onCancel = () => {
  emit('confirm', false)
  updateModelValue(false)
}
</script>

<template>
  <VDialog
    max-width="500"
    :model-value="props.isDialogVisible"
    @update:model-value="updateModelValue"
  >
    <VCard class="text-center px-6 py-6">
      <VCardText>
        <VBtn
          icon
          variant="outlined"
          :color="props.color"
          class="my-4"
          size="x-large"
        >
          <span class="text-4xl">!</span>
        </VBtn>

        <h5 class="text-h5 font-weight-medium mb-3">
          {{ props.title }}
        </h5>
        
        <p class="text-body-1 mb-0">
          {{ props.message }}
        </p>
      </VCardText>

      <VCardText class="d-flex align-center justify-center gap-4 mt-2">
        <VBtn
          :color="props.color"
          variant="elevated"
          @click="onConfirm"
        >
          {{ props.confirmText }}
        </VBtn>

        <VBtn
          color="secondary"
          variant="outlined"
          @click="onCancel"
        >
          {{ props.cancelText }}
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>
