<script setup>
import { ref, watch } from 'vue'

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

const emit = defineEmits(['update:isDialogVisible', 'refreshData'])

const batches = ref([])
const isLoading = ref(false)

const formatInputRupiah = value => {
  if (value === null || value === undefined || value === '') return ''
  const digits = String(value).replace(/\D/g, '')
  if (!digits) return ''
  
  return new Intl.NumberFormat('id-ID').format(digits)
}

const parseInputRupiah = value => {
  if (value === null || value === undefined || value === '') return 0
  const digits = String(value).replace(/\D/g, '')
  
  return Number(digits) || 0
}

const fetchBatches = async () => {
  if (!props.selectedData?.id) return
  
  isLoading.value = true
  try {
    const res = await $api(`/apps/product-branches/${props.selectedData.id}`)


    // Fetch productBatches from the product branch
    batches.value = res.product_batches || []
  } catch (error) {
    console.error(error)
  } finally {
    isLoading.value = false
  }
}

watch(() => props.isDialogVisible, newVal => {
  if (newVal) {
    fetchBatches()
  } else {
    batches.value = []
  }
})

const saveBatchPrice = async batch => {
  if (batch.isSaving) return
  
  batch.isSaving = true
  try {
    await $api(`/apps/product-batches/${batch.id}`, {
      method: 'PUT',
      body: {
        price: batch.price,
        min_nego_price: batch.min_nego_price,
      },
    })
    
    // Show success snackbar ideally, but since we don't have it injected easily here, we can just change icon state momentarily
    batch.saved = true
    setTimeout(() => { batch.saved = false }, 2000)
    
    emit('refreshData')
  } catch (error) {
    console.error(error)
    alert(error.data?.message || 'Gagal menyimpan harga batch')
  } finally {
    batch.isSaving = false
  }
}

const closeDialog = () => {
  emit('update:isDialogVisible', false)
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    max-width="800"
    @update:model-value="(val) => emit('update:isDialogVisible', val)"
  >
    <VCard title="Kelola Harga Batch" :loading="isLoading">
      <VCardText>
        <p class="mb-4 text-body-1">
          Atur harga jual dan batas nego untuk masing-masing batch dari produk 
          <strong>{{ props.selectedData?.product?.name }}</strong> di cabang 
          <strong>{{ props.selectedData?.branch?.name }}</strong>.
        </p>

        <VProgressLinear
          v-if="isLoading"
          indeterminate
          color="primary"
          height="2"
          class="mb-3"
        />

        <div
          v-if="isLoading"
          class="d-flex justify-center align-center pa-6 text-medium-emphasis"
        >
          <VProgressCircular
            indeterminate
            color="primary"
            size="28"
            class="me-2"
          />
          <span>Memuat data batch produk...</span>
        </div>
        
        <VTable
          v-else-if="batches.length > 0"
          class="text-no-wrap"
        >
          <thead>
            <tr>
              <th>ID BATCH</th>
              <th>SISA STOK</th>
              <th>HARGA MODAL</th>
              <th>HARGA JUAL</th>
              <th>BATAS NEGO</th>
              <th>SIMPAN</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="batch in batches"
              :key="batch.id"
            >
              <td>#{{ batch.id }}</td>
              <td>
                <VChip
                  size="small"
                  :color="batch.qty > 0 ? 'success' : 'error'"
                >
                  {{ batch.qty }}
                </VChip>
              </td>
              <td>Rp {{ formatInputRupiah(batch.cost_price) }}</td>
              <td>
                <VTextField
                  :model-value="formatInputRupiah(batch.price)"
                  type="text"
                  density="compact"
                  hide-details
                  style="min-width: 120px"
                  @update:model-value="val => batch.price = parseInputRupiah(val)"
                />
              </td>
              <td>
                <VTextField
                  :model-value="formatInputRupiah(batch.min_nego_price)"
                  type="text"
                  density="compact"
                  hide-details
                  style="min-width: 120px"
                  @update:model-value="val => batch.min_nego_price = parseInputRupiah(val)"
                />
              </td>
              <td>
                <VBtn
                  :color="batch.saved ? 'success' : 'primary'"
                  size="small"
                  :loading="batch.isSaving"
                  :icon="batch.saved ? 'ri-check-line' : 'ri-save-line'"
                  @click="saveBatchPrice(batch)"
                />
              </td>
            </tr>
          </tbody>
        </VTable>
        <VAlert
          v-else
          type="info"
          variant="tonal"
          class="mt-4"
        >
          Tidak ada riwayat batch untuk produk ini.
        </VAlert>
      </VCardText>
      
      <VCardActions class="px-4 pb-4">
        <VSpacer />
        <VBtn
          color="secondary"
          variant="tonal"
          @click="closeDialog"
        >
          Tutup
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
