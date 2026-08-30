<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  selectedData: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['update:isDialogVisible'])

const selectedBatch = ref(null)
const quantityToPrint = ref(1)

const batches = computed(() => {
  if (!props.selectedData || !props.selectedData.product_batches) return []
  
  return props.selectedData.product_batches.filter(b => b.qty > 0)
})

const closeDialog = () => {
  emit('update:isDialogVisible', false)
  selectedBatch.value = null
  quantityToPrint.value = 1
}

const printLabels = () => {
  if (!selectedBatch.value) {
    alert('Pilih batch terlebih dahulu!')
    
    return
  }
  
  if (quantityToPrint.value < 1) {
    alert('Jumlah cetak minimal 1')
    
    return
  }

  // Buka jendela baru khusus print
  const url = `/apps/print-label?batch_id=${selectedBatch.value}&qty=${quantityToPrint.value}`

  window.open(url, '_blank', 'width=800,height=600')
  closeDialog()
}

// Helper to format date
const formatDate = dateStr => {
  if (!dateStr) return '-'
  
  return new Date(dateStr).toLocaleDateString('id-ID')
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    :fullscreen="$vuetify.display.xs"
    max-width="500"
    @update:model-value="val => emit('update:isDialogVisible', val)"
  >
    <VCard>
      <VCardItem class="pb-2">
        <VCardTitle class="text-h5">
          Cetak Label QR (Stiker)
        </VCardTitle>
        <VCardSubtitle>Produk: {{ props.selectedData?.product?.name }}</VCardSubtitle>
      </VCardItem>

      <VCardText class="pt-4">
        <VAlert
          v-if="batches.length === 0"
          color="warning"
          variant="tonal"
          class="mb-4"
        >
          Belum ada stok (batch) yang tersedia untuk produk ini di cabang ini.
        </VAlert>

        <VForm
          v-else
          @submit.prevent="printLabels"
        >
          <VRow>
            <VCol cols="12">
              <VSelect
                v-model="selectedBatch"
                :items="batches"
                item-value="id"
                item-title="id"
                label="Pilih Stok / Batch"
              >
                <template #selection="{ item }">
                  Batch #{{ item.raw.id }} (Exp: {{ formatDate(item.raw.expiration_date) }} - Qty: {{ item.raw.qty }})
                </template>
                <template #item="{ props: itemProps, item }">
                  <VListItem
                    v-bind="itemProps"
                    :title="`Batch #${item.raw.id} - Sisa: ${item.raw.qty} pcs`"
                  >
                    <template #subtitle>
                      Tgl Masuk: {{ formatDate(item.raw.entry_date) }} | Exp: <strong class="text-error">{{ formatDate(item.raw.expiration_date) }}</strong>
                    </template>
                  </VListItem>
                </template>
              </VSelect>
            </VCol>
            
            <VCol cols="12">
              <VTextField
                v-model.number="quantityToPrint"
                type="number"
                label="Jumlah Label yang Dicetak"
                min="1"
                :max="1000"
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap">
        <VBtn
          color="secondary"
          variant="tonal"
          @click="closeDialog"
        >
          Batal
        </VBtn>
        <VBtn
          color="primary"
          :disabled="!selectedBatch || batches.length === 0"
          prepend-icon="ri-printer-line"
          @click="printLabels"
        >
          Cetak Sekarang
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>
