<script setup>
import { ref, watch, nextTick } from 'vue'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  selectedPo: {
    type: Object,
    default: null,
  },
  branches: {
    type: Array,
    default: () => [],
  },
  suppliers: {
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
const branch_id = ref(null)
const supplier_id = ref(null)
const date = ref(new Date().toISOString().substr(0, 10))
const notes = ref('')

const items = ref([
  { product_id: null, qty: 1, unit_cost: 0 },
])

watch(() => props.selectedPo, newVal => {
  if (newVal) {
    branch_id.value = newVal.branch_id
    supplier_id.value = newVal.supplier_id
    date.value = newVal.date || newVal.created_at?.substr(0, 10)
    notes.value = newVal.notes || ''
    
    if (newVal.items && newVal.items.length) {
      items.value = newVal.items.map(item => ({
        product_id: item.product_id,
        qty: item.qty,
        unit_cost: item.unit_cost,
      }))
    }
  } else {
    branch_id.value = null
    supplier_id.value = null
    date.value = new Date().toISOString().substr(0, 10)
    notes.value = ''
    items.value = [{ product_id: null, qty: 1, unit_cost: 0 }]
  }
}, { immediate: true })

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
    items.value = [{ product_id: null, qty: 1, unit_cost: 0 }]
  })
}

const addItem = () => {
  items.value.push({ product_id: null, qty: 1, unit_cost: 0 })
}

const removeItem = index => {
  if (items.value.length > 1) {
    items.value.splice(index, 1)
  }
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      // Validate items
      if (items.value.some(i => !i.product_id || i.qty < 1 || i.unit_cost < 0)) {
        alert('Mohon lengkapi semua baris barang dengan benar (Produk, Qty > 0, Harga Modal >= 0).')
        
        return
      }

      emit('saveData', {
        id: props.selectedPo?.id,
        branch_id: branch_id.value,
        supplier_id: supplier_id.value,
        date: date.value,
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

const formatNumber = value => {
  if (!value) return ''
  const number = value.toString().replace(/\D/g, '')
  
  return new Intl.NumberFormat('id-ID').format(number)
}

const parseNumber = value => {
  if (!value) return 0
  const number = value.toString().replace(/\D/g, '')
  
  return number ? parseInt(number, 10) : 0
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
      :title="props.selectedPo ? 'Edit Purchase Order' : 'Buat Purchase Order Baru'"
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
              <VCol cols="6">
                <VAutocomplete
                  v-model="branch_id"
                  :rules="[v => !!v || 'Cabang wajib dipilih']"
                  :items="props.branches"
                  item-title="name"
                  item-value="id"
                  label="Pilih Cabang (Tujuan Barang)"
                />
              </VCol>

              <VCol cols="6">
                <VAutocomplete
                  v-model="supplier_id"
                  :rules="[v => !!v || 'Supplier wajib dipilih']"
                  :items="props.suppliers"
                  item-title="name"
                  item-value="id"
                  label="Pilih Supplier"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="date"
                  type="date"
                  :rules="[v => !!v || 'Tanggal wajib diisi']"
                  label="Tanggal PO"
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="notes"
                  label="Catatan PO"
                  rows="2"
                  placeholder="Instruksi pengiriman, dll."
                />
              </VCol>

              <VCol cols="12">
                <VDivider class="my-4" />
                <div class="d-flex justify-space-between align-center mb-4">
                  <h6 class="text-h6 font-weight-medium">
                    Daftar Barang (Item PO)
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
                  <div style="width: 100px;">
                    <VTextField
                      v-model="item.qty"
                      type="number"
                      label="Qty"
                      density="compact"
                    />
                  </div>
                  <div style="width: 180px;">
                    <VTextField
                      :model-value="formatNumber(item.unit_cost)"
                      prefix="Rp"
                      label="Harga Modal Satuan"
                      density="compact"
                      @update:model-value="val => item.unit_cost = parseNumber(val)"
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

              <VCol
                cols="12"
                class="mt-4"
              >
                <VBtn
                  type="submit"
                  class="me-3"
                >
                  {{ props.selectedPo ? 'Simpan Perubahan' : 'Buat PO' }}
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
