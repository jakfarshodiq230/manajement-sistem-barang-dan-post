<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { VForm } from 'vuetify/components/VForm'
import { useSnackbarStore } from '@/stores/snackbar'
import { ref, watch } from 'vue'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  branches: {
    type: Array,
    required: true,
  },
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'returnData',
])

const isFormValid = ref(false)
const refForm = ref()

const branchId = ref(null)
const referenceType = ref('sale')
const returnType = ref('pengembalian_uang')

const referenceId = ref(null)
const notes = ref('')

const availableTransactions = ref([])
const transactionItems = ref([])

const isLoading = ref(false)
const snackbar = useSnackbarStore()

watch(referenceType, newVal => {
  if (newVal === 'sale') {
    returnType.value = 'pengembalian_uang'
  } else {
    returnType.value = 'tukar_barang'
  }
  referenceId.value = null
  transactionItems.value = []
  fetchTransactions()
})

watch(branchId, () => {
  fetchTransactions()
})

const fetchTransactions = async () => {
  if (!branchId.value) return
  
  isLoading.value = true
  try {
    let endpoint = referenceType.value === 'sale' ? '/apps/sales' : '/apps/purchase-orders'
    const data = await $api(`${endpoint}?branch_id=${branchId.value}`)
    
    // Filter completed transactions
    if (referenceType.value === 'sale') {
      availableTransactions.value = data
    } else {
      availableTransactions.value = data.filter(po => po.status === 'completed')
    }
  } catch (err) {
    console.error(err)
  } finally {
    isLoading.value = false
  }
}

watch(referenceId, async newVal => {
  transactionItems.value = []
  if (!newVal) return

  isLoading.value = true
  try {
    let endpoint = referenceType.value === 'sale' ? `/apps/sales/${newVal}` : `/apps/purchase-orders/${newVal}`
    const data = await $api(endpoint)
    
    if (data && data.items) {
      transactionItems.value = await Promise.all(data.items.map(async item => {
        const p_branch_id = item.product_branch_id || (item.product_id ? await getProductBranchId(item.product_id) : null)
        const product_obj = item.product_branch?.product || item.product || {}
        
        return {
          product_branch_id: p_branch_id,
          name: product_obj.name || 'Unknown',
          unit_price: item.price || item.unit_cost,
          max_qty: item.qty,
          qty: 1,
          selected: false,
          is_returnable: product_obj.is_returnable ?? true,
        }
      }))
    }
  } catch (err) {
    console.error(err)
  } finally {
    isLoading.value = false
  }
})

// Helper if purchase order items only have product_id, we need to map to product_branch_id
const getProductBranchId = async productId => {
  try {
    const data = await $api(`/apps/product-branches?branch_id=${branchId.value}`)
    const pb = data.find(p => p.product_id === productId)
    
    return pb ? pb.id : null
  } catch(e) {
    return null
  }
}

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  resetForm()
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      const itemsToReturn = transactionItems.value
        .filter(i => i.selected && i.qty > 0)
        .map(i => ({
          product_branch_id: i.product_branch_id,
          qty: Number(i.qty),
          unit_price: Number(i.unit_price),
        }))

      if (itemsToReturn.length === 0) {
        snackbar.show('Pilih minimal 1 barang untuk diretur', 'warning')
        
        return
      }

      emit('returnData', {
        branch_id: branchId.value,
        reference_type: referenceType.value,
        reference_id: referenceId.value,
        return_type: returnType.value,
        notes: notes.value,
        items: itemsToReturn,
      })
    }
  })
}

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
  if (!val) resetForm()
}

function resetForm() {
  branchId.value = null
  referenceId.value = null
  notes.value = ''
  transactionItems.value = []
  if (refForm.value) {
    refForm.value.resetValidation()
  }
}
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="500"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <AppDrawerHeaderSection
      title="Buat Retur Baru"
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
              <VCol cols="12">
                <VAutocomplete
                  v-model="branchId"
                  :items="branches"
                  item-title="name"
                  item-value="id"
                  label="Cabang"
                  :rules="[v => !!v || 'Cabang wajib diisi']"
                />
              </VCol>

              <VCol cols="12">
                <VSelect
                  v-model="referenceType"
                  :items="[
                    { title: 'Retur Penjualan (Customer)', value: 'sale' },
                    { title: 'Retur Pembelian (Supplier)', value: 'purchase' }
                  ]"
                  item-title="title"
                  item-value="value"
                  label="Jenis Transaksi Asal"
                />
              </VCol>

              <VCol cols="12">
                <VAutocomplete
                  v-model="referenceId"
                  :items="availableTransactions"
                  :item-title="referenceType === 'sale' ? 'invoice_number' : 'po_number'"
                  item-value="id"
                  label="Nomor Referensi"
                  :rules="[v => !!v || 'Referensi wajib dipilih']"
                  :loading="isLoading"
                  :disabled="!branchId"
                />
              </VCol>

              <VCol
                v-if="transactionItems.length > 0"
                cols="12"
              >
                <div class="text-subtitle-1 font-weight-medium mb-2">
                  Pilih Barang yang Diretur
                </div>
                <VList
                  lines="two"
                  class="border rounded"
                >
                  <VListItem
                    v-for="(item, index) in transactionItems"
                    :key="index"
                    :class="!item.is_returnable ? 'text-disabled bg-var-theme-background' : ''"
                  >
                    <template #prepend>
                      <VCheckboxBtn 
                        v-model="item.selected" 
                        :disabled="!item.is_returnable"
                      />
                    </template>
                    <VListItemTitle>{{ item.name }}</VListItemTitle>
                    <VListItemSubtitle>
                      Harga: Rp {{ item.unit_price }} | Max: {{ item.max_qty }}
                      <span v-if="!item.is_returnable" class="text-error ms-2 font-weight-bold">
                        (Tidak Bisa Diretur)
                      </span>
                    </VListItemSubtitle>
                    <template #append>
                      <VTextField
                        v-if="item.selected"
                        v-model="item.qty"
                        type="number"
                        min="1"
                        :max="item.max_qty"
                        density="compact"
                        style="width: 80px;"
                        hide-details
                      />
                    </template>
                  </VListItem>
                </VList>
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="notes"
                  label="Alasan Retur"
                  placeholder="Deskripsi kerusakan atau alasan pengembalian"
                  rows="2"
                  :rules="[v => !!v || 'Alasan retur wajib diisi']"
                />
              </VCol>

              <VCol cols="12">
                <VBtn
                  type="submit"
                  class="me-3"
                >
                  Ajukan Retur
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
