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
  'update:is-drawer-open',
  'close',
  'cancel',
  'saveMovement',
])

const isFormValid = ref(false)
const refForm = ref()
const quantity = ref(1)
const unit_cost = ref(0)
const notes = ref('')

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  emit('update:is-drawer-open', false)
  emit('close')
  emit('cancel')
  nextTick(() => {
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
  emit('update:is-drawer-open', val)
  if (!val) {
    emit('close')
    emit('cancel')
  }
}
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

const projectedTotalStock = computed(() => {
  const current = Number(props.selectedBranchProduct?.stock) || 0
  const add = Number(quantity.value) || 0
  return current + add
})
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="$vuetify.display.xs ? '100%' : ($vuetify.display.smAndDown ? '92vw' : 480)"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- Header -->
    <div class="d-flex align-center justify-space-between px-6 py-5 border-b bg-gradient-header">
      <div class="d-flex align-center gap-3">
        <VAvatar
          size="42"
          color="primary"
          variant="tonal"
          class="rounded-lg"
        >
          <VIcon icon="ri-inbox-archive-line" size="24" />
        </VAvatar>
        <div>
          <h5 class="text-h6 font-weight-bold mb-0">
            Inbound Stok Barang Masuk
          </h5>
          <span class="text-caption text-medium-emphasis">
            Pencatatan penambahan stok fisik cabang
          </span>
        </div>
      </div>
      <VBtn
        icon="ri-close-line"
        variant="tonal"
        color="secondary"
        size="small"
        type="button"
        @click.stop="closeNavigationDrawer"
      />
    </div>

    <PerfectScrollbar :options="{ wheelPropagation: false }" style="height: calc(100vh - 75px);">
      <VCard flat class="pa-6">
        <!-- Product & Branch Info Card -->
        <div
          v-if="props.selectedBranchProduct"
          class="pa-4 mb-5 rounded-xl border bg-var-theme-surface shadow-xs"
        >
          <div class="d-flex align-center gap-3 mb-2">
            <VAvatar
              size="38"
              color="primary"
              variant="tonal"
              rounded="lg"
            >
              <VIcon icon="ri-box-3-line" size="20" />
            </VAvatar>
            <div>
              <div class="font-weight-bold text-body-1 text-high-emphasis">
                {{ props.selectedBranchProduct.product?.name }}
              </div>
              <div class="text-caption font-mono text-medium-emphasis">
                SKU: {{ props.selectedBranchProduct.product?.sku || '-' }} | Cabang: <strong>{{ props.selectedBranchProduct.branch?.name }}</strong>
              </div>
            </div>
          </div>

          <div class="d-flex align-center justify-space-between pt-2 border-t mt-2">
            <span class="text-caption text-medium-emphasis">Stok Saat Ini:</span>
            <VChip size="small" color="primary" variant="tonal" class="font-weight-bold">
              {{ props.selectedBranchProduct.stock }} {{ props.selectedBranchProduct.product?.unit || 'Unit' }}
            </VChip>
          </div>
        </div>

        <!-- Form -->
        <VForm
          ref="refForm"
          v-model="isFormValid"
          @submit.prevent="onSubmit"
        >
          <VRow dense>
            <VCol cols="12" class="mb-2">
              <VTextField
                v-model.number="quantity"
                :rules="[v => !!v || 'Jumlah kuantitas wajib diisi', v => v > 0 || 'Jumlah minimal 1']"
                label="Jumlah Barang Masuk (Qty)"
                type="number"
                min="1"
                placeholder="10"
                density="comfortable"
                variant="outlined"
                prepend-inner-icon="ri-add-circle-line"
                :suffix="props.selectedBranchProduct?.product?.unit || 'Pcs'"
              />
            </VCol>

            <!-- Projected Stock Banner -->
            <VCol cols="12" v-if="quantity > 0" class="mb-3">
              <div class="pa-3 bg-primary-lighten-5 border border-primary-subtle rounded-lg d-flex align-center justify-space-between">
                <span class="text-caption text-primary font-weight-medium">Estimasi Stok Akhir:</span>
                <span class="text-subtitle-2 font-weight-bold text-primary">
                  {{ projectedTotalStock }} {{ props.selectedBranchProduct?.product?.unit || 'Unit' }}
                </span>
              </div>
            </VCol>

            <VCol cols="12" class="mb-2">
              <VTextField
                :model-value="formatInputRupiah(unit_cost)"
                label="Harga Modal Satuan Masuk (HPP) (Rp)"
                type="text"
                placeholder="0"
                density="comfortable"
                variant="outlined"
                prepend-inner-icon="ri-money-dollar-circle-line"
                prefix="Rp"
                hint="Memperbarui harga modal dasar cabang jika diisi"
                persistent-hint
                @update:model-value="val => unit_cost = parseInputRupiah(val)"
              />
            </VCol>

            <VCol cols="12" class="mb-4">
              <VTextarea
                v-model="notes"
                label="Catatan / Nomor Dokumen Pembelian"
                placeholder="Misal: Penerimaan tambahan dari Supplier A (Nota #123)"
                rows="3"
                density="comfortable"
                variant="outlined"
                prepend-inner-icon="ri-file-text-line"
              />
            </VCol>

            <!-- Action Bar -->
            <VCol cols="12">
              <div class="d-flex align-center gap-3 pt-2">
                <VBtn
                  type="submit"
                  color="primary"
                  size="large"
                  prepend-icon="ri-download-2-line"
                  class="font-weight-bold flex-grow-1 rounded-lg shadow-sm"
                >
                  Simpan Inbound Stok
                </VBtn>
                <VBtn
                  type="button"
                  variant="outlined"
                  color="secondary"
                  size="large"
                  class="rounded-lg px-5"
                  @click.stop="closeNavigationDrawer"
                >
                  Batal
                </VBtn>
              </div>
            </VCol>
          </VRow>
        </VForm>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>

<style scoped>
.bg-gradient-header {
  background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.05) 0%, rgba(var(--v-theme-surface), 1) 100%);
}
.bg-primary-lighten-5 {
  background-color: rgba(var(--v-theme-primary), 0.06) !important;
}
.border-primary-subtle {
  border-color: rgba(var(--v-theme-primary), 0.25) !important;
}
</style>
