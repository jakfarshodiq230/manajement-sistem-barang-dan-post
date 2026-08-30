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
  'update:is-drawer-open',
  'close',
  'cancel',
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

const extractArray = val => {
  if (Array.isArray(val)) return val
  if (val && Array.isArray(val.data)) return val.data
  return []
}

const fetchTransactions = async () => {
  if (!branchId.value) return
  isLoading.value = true
  try {
    const endpoint = referenceType.value === 'sale' ? '/apps/transactions' : '/apps/purchase-orders'
    const res = await $api(endpoint, {
      query: {
        branch_id: branchId.value,
        itemsPerPage: -1,
      },
    })
    availableTransactions.value = extractArray(res)
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal memuat transaksi referensi', 'error')
  } finally {
    isLoading.value = false
  }
}

watch(referenceId, async newVal => {
  if (!newVal) {
    transactionItems.value = []
    return
  }
  
  isLoading.value = true
  try {
    const endpoint = referenceType.value === 'sale' ? `/apps/transactions/${newVal}` : `/apps/purchase-orders/${newVal}`
    const res = await $api(endpoint)
    const data = res.data || res

    const rawItems = data.items || data.details || []
    transactionItems.value = rawItems.map(item => {
      const p = item.product || item.product_branch?.product || {}
      return {
        product_id: p.id || item.product_id,
        product_branch_id: item.product_branch_id || p.id,
        name: p.name || item.product_name || 'Produk',
        product_name: p.name || item.product_name || 'Produk',
        sku: p.sku || '',
        unit_price: item.price || item.unit_price || item.unit_cost || 0,
        max_qty: item.quantity || item.qty || 1,
        qty: 1,
        selected: false,
        is_returnable: p.is_returnable ?? true,
        condition: 'good',
        reason: '',
      }
    })
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal memuat detail item transaksi', 'error')
  } finally {
    isLoading.value = false
  }
})

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  emit('update:is-drawer-open', false)
  emit('close')
  emit('cancel')
  resetForm()
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      const selectedItems = transactionItems.value
        .filter(i => i.selected && i.qty > 0)
        .map(i => ({
          product_id: i.product_id,
          product_branch_id: i.product_branch_id,
          qty: Number(i.qty),
          unit_price: Number(i.unit_price),
          reason: i.reason || '',
        }))

      if (selectedItems.length === 0) {
        snackbar.show('Pilih minimal 1 barang yang dicentang untuk diretur', 'warning')
        return
      }

      emit('returnData', {
        branch_id: branchId.value,
        reference_type: referenceType.value,
        reference_id: referenceId.value,
        return_type: returnType.value,
        notes: notes.value,
        items: selectedItems,
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
    resetForm()
  }
}

function resetForm() {
  branchId.value = null
  referenceType.value = 'sale'
  returnType.value = 'pengembalian_uang'
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
    :width="$vuetify.display.xs ? '100%' : ($vuetify.display.smAndDown ? '92vw' : 620)"
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
          <VIcon icon="ri-refund-2-line" size="24" />
        </VAvatar>
        <div>
          <h5 class="text-h6 font-weight-bold mb-0">
            Buat Dokumen Retur Barang
          </h5>
          <span class="text-caption text-medium-emphasis">
            Pengembalian retur penjualan customer / pembelian supplier
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
                <VSelect
                  v-model="returnType"
                  :items="referenceType === 'purchase' ? [
                    { title: 'Tukar Barang (Barang Diganti Fisik oleh Supplier)', value: 'tukar_barang' },
                    { title: 'Pengembalian Dana / Potong Hutang Berjalan Bulan Selanjutnya', value: 'potong_hutang' }
                  ] : [
                    { title: 'Pengembalian Uang ke Pelanggan', value: 'pengembalian_uang' },
                    { title: 'Tukar Barang', value: 'tukar_barang' }
                  ]"
                  item-title="title"
                  item-value="value"
                  label="Metode Penanganan Retur"
                  persistent-hint
                  :hint="referenceType === 'purchase' && returnType === 'potong_hutang' ? 'Dana retur akan otomatis diterbitkan sebagai Saldo Kredit untuk memotong tagihan PO supplier berikutnya.' : ''"
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="notes"
                  label="Alasan Retur / Kerusakan"
                  placeholder="Deskripsi kerusakan fisik, bocor, atau alasan pengembalian ke supplier"
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
                  type="button"
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
