<script setup>
import { ref, watch, nextTick } from 'vue'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  selectedBranchProduct: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'saveMovement',
])

const isFormValid = ref(false)
const refForm = ref()
const quantity = ref(1)
const unit_cost = ref(0)
const notes = ref('')

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
    quantity.value = 1
    unit_cost.value = 0
  })
}

watch(() => props.selectedBranchProduct, newVal => {
  if (newVal) {
    unit_cost.value = newVal.cost_price || 0
  }
}, { immediate: true })

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      emit('saveMovement', {
        product_branch_id: props.selectedBranchProduct.id,
        type: 'in',
        quantity: quantity.value,
        unit_cost: unit_cost.value,
        notes: notes.value,
      })
      closeNavigationDrawer()
    }
  })
}

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
}
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="400"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- Header -->
    <AppDrawerHeaderSection
      title="Inbound Stok (Barang Masuk)"
      @cancel="closeNavigationDrawer"
    />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <div
            v-if="props.selectedBranchProduct"
            class="mb-4"
          >
            <h6 class="text-h6 font-weight-medium mb-1">
              {{ props.selectedBranchProduct.product?.name }}
            </h6>
            <div class="d-flex align-center gap-2">
              <VChip
                size="small"
                color="primary"
              >
                Cabang: {{ props.selectedBranchProduct.branch?.name }}
              </VChip>
              <VChip
                size="small"
                color="success"
              >
                Stok Saat Ini: {{ props.selectedBranchProduct.stock }}
              </VChip>
            </div>
          </div>
          <VDivider class="mb-4" />

          <!-- Form -->
          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <VCol cols="12">
                <VTextField
                  v-model="quantity"
                  :rules="[v => !!v || 'Kuantitas wajib diisi', v => v > 0 || 'Kuantitas harus lebih dari 0']"
                  label="Jumlah Barang Masuk (Qty)"
                  type="number"
                  placeholder="10"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="unit_cost"
                  label="Harga Modal Satuan (Rp)"
                  type="number"
                  placeholder="0"
                  hint="Otomatis memperbarui harga modal dasar jika diisi"
                  persistent-hint
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="notes"
                  label="Catatan / Keterangan Pembelian"
                  placeholder="Misal: Pembelian dari Supplier A (Nota #123)"
                  rows="3"
                />
              </VCol>

              <VCol cols="12">
                <VBtn
                  type="submit"
                  class="me-3"
                >
                  Simpan Inbound
                </VBtn>
                <VBtn
                  type="reset"
                  variant="outlined"
                  color="error"
                  @click="closeNavigationDrawer"
                >
                  Batal
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>
