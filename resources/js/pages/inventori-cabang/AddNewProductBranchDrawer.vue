<script setup>
import { ref, watch, nextTick } from 'vue'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  selectedData: {
    type: Object,
    default: null,
  },
  masterProducts: {
    type: Array,
    default: () => [],
  },
  branchesList: {
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
const id = ref(null)
const product_id = ref(null)
const branch_id = ref(null)
const cost_price = ref(0)
const price = ref(0)
const min_nego_price = ref(0)
const tax_percentage = ref(0)
const other_fees = ref(0)

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
  })
}

watch(() => props.selectedData, newVal => {
  if (newVal) {
    id.value = newVal.id
    product_id.value = newVal.product_id
    branch_id.value = newVal.branch_id
    cost_price.value = Math.round(newVal.cost_price || 0)
    price.value = Math.round(newVal.price || 0)
    min_nego_price.value = Math.round(newVal.min_nego_price || 0)
    tax_percentage.value = newVal.tax_percentage || 0
    other_fees.value = Math.round(newVal.other_fees || 0)
  } else {
    id.value = null
    product_id.value = null
    branch_id.value = null
    cost_price.value = 0
    price.value = 0
    min_nego_price.value = 0
    tax_percentage.value = 0
    other_fees.value = 0
  }
}, { immediate: true })

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

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      emit('saveData', {
        id: id.value,
        product_id: product_id.value,
        branch_id: branch_id.value,
        cost_price: cost_price.value,
        price: price.value,
        min_nego_price: min_nego_price.value,
        tax_percentage: tax_percentage.value,
        other_fees: other_fees.value,
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
      :title="props.selectedData ? 'Edit Harga Cabang' : 'Daftarkan ke Cabang'"
      @cancel="closeNavigationDrawer"
    />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <!-- Form -->
          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <VCol cols="12">
                <VAutocomplete
                  v-model="product_id"
                  :rules="[v => !!v || 'Master produk wajib dipilih']"
                  :items="props.masterProducts"
                  item-title="name"
                  item-value="id"
                  label="Master Produk"
                  placeholder="Pilih Master Produk"
                  :disabled="!!props.selectedData"
                />
              </VCol>

              <VCol cols="12">
                <VAutocomplete
                  v-model="branch_id"
                  :rules="[v => !!v || 'Cabang wajib dipilih']"
                  :items="props.branchesList"
                  item-title="name"
                  item-value="id"
                  label="Cabang"
                  placeholder="Pilih Cabang"
                  :disabled="!!props.selectedData"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  :model-value="formatInputRupiah(cost_price)"
                  :rules="[v => !!v || 'Harga modal wajib diisi']"
                  label="Harga Modal (Rp)"
                  type="text"
                  placeholder="0"
                  @update:model-value="val => cost_price = parseInputRupiah(val)"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  :model-value="formatInputRupiah(price)"
                  :rules="[v => !!v || 'Harga jual wajib diisi']"
                  label="Harga Jual (Rp)"
                  type="text"
                  placeholder="0"
                  @update:model-value="val => price = parseInputRupiah(val)"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  :model-value="formatInputRupiah(min_nego_price)"
                  label="Harga Nego Minimum (Batas Bawah Kasir) (Rp)"
                  type="text"
                  placeholder="0"
                  persistent-hint
                  hint="Kasir tidak dapat menjual di bawah harga ini. Jika 0, maka disamakan dengan harga jual."
                  @update:model-value="val => min_nego_price = parseInputRupiah(val)"
                />
              </VCol>

              <VCol cols="6">
                <VTextField
                  v-model="tax_percentage"
                  label="Pajak PPN (%)"
                  type="number"
                  placeholder="0"
                  hint="Kosongkan jika tidak ada"
                />
              </VCol>

              <VCol cols="6">
                <VTextField
                  :model-value="formatInputRupiah(other_fees)"
                  label="Biaya Tambahan (Rp)"
                  type="text"
                  placeholder="0"
                  hint="Kosongkan jika tidak ada"
                  @update:model-value="val => other_fees = parseInputRupiah(val)"
                />
              </VCol>

              <VCol cols="12">
                <VBtn
                  type="submit"
                  class="me-3"
                >
                  Simpan
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
