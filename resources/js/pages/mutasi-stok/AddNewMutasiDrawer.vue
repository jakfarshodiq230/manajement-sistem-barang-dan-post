<script setup>
import { ref, nextTick } from 'vue'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { useSnackbarStore } from '@/stores/snackbar'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  branches: {
    type: Array,
    default: () => [],
  },
  masterProducts: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'saveData',
])

const isFormValid = ref(false)
const refForm = ref()
const source_branch_id = ref(null)
const destination_branch_id = ref(null)
const notes = ref('')
const snackbar = useSnackbarStore()

const items = ref([
  { product_id: null, qty: 1 },
])

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
    items.value = [{ product_id: null, qty: 1 }]
  })
}

const addItem = () => {
  items.value.push({ product_id: null, qty: 1 })
}

const removeItem = index => {
  if (items.value.length > 1) {
    items.value.splice(index, 1)
  }
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      if (source_branch_id.value === destination_branch_id.value) {
        snackbar.show('Cabang asal dan tujuan tidak boleh sama!', 'warning')
        return
      }

      if (items.value.some(i => !i.product_id || i.qty < 1)) {
        snackbar.show('Mohon lengkapi semua baris barang (Produk dan Qty harus lebih dari 0).', 'warning')
        return
      }

      emit('saveData', {
        source_branch_id: source_branch_id.value,
        destination_branch_id: destination_branch_id.value,
        notes: notes.value,
        items: items.value,
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
    :width="700"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <AppDrawerHeaderSection
      title="Buat Mutasi Stok Baru"
      @cancel="closeNavigationDrawer"
    />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <VCol cols="12" md="6">
                <VAutocomplete
                  v-model="source_branch_id"
                  :rules="[v => !!v || 'Cabang asal wajib dipilih']"
                  :items="props.branches"
                  item-title="name"
                  item-value="id"
                  label="Cabang Asal (Sumber Barang)"
                />
              </VCol>

              <VCol cols="12" md="6">
                <VAutocomplete
                  v-model="destination_branch_id"
                  :rules="[v => !!v || 'Cabang tujuan wajib dipilih']"
                  :items="props.branches"
                  item-title="name"
                  item-value="id"
                  label="Cabang Tujuan"
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="notes"
                  label="Catatan Mutasi"
                  rows="2"
                  placeholder="Instruksi pengiriman, alasan mutasi, dll."
                />
              </VCol>

              <VCol cols="12">
                <VDivider class="my-4" />
                <div class="d-flex justify-space-between align-center mb-4">
                  <h6 class="text-h6 font-weight-medium">
                    Daftar Barang Mutasi
                  </h6>
                  <VBtn
                    size="small"
                    variant="tonal"
                    prepend-icon="ri-add-line"
                    @click="addItem"
                  >
                    Tambah Baris
                  </VBtn>
                </div>

                <div
                  v-for="(item, index) in items"
                  :key="index"
                  class="d-flex align-center gap-4 mb-4"
                >
                  <div class="flex-grow-1">
                    <VAutocomplete
                      v-model="item.product_id"
                      :items="props.masterProducts"
                      :item-title="prod => prod.sku ? `[${prod.sku}] ${prod.name}` : prod.name"
                      item-value="id"
                      label="Cari Produk (Nama / SKU)"
                      density="compact"
                      clearable
                    />
                  </div>
                  <div style="width: 120px;">
                    <VTextField
                      v-model="item.qty"
                      type="number"
                      label="Qty Mutasi"
                      density="compact"
                      :rules="[v => v > 0 || 'Minimal 1']"
                    />
                  </div>
                  <div>
                    <IconBtn
                      size="small"
                      color="error"
                      :disabled="items.length === 1"
                      @click="removeItem(index)"
                    >
                      <VIcon icon="ri-close-line" />
                    </IconBtn>
                  </div>
                </div>
              </VCol>

              <VCol cols="12" class="mt-4">
                <VBtn type="submit" class="me-3">
                  Buat Pengajuan
                </VBtn>
                <VBtn
                  type="reset"
                  variant="outlined"
                  color="secondary"
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
