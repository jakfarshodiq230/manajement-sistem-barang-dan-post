<script setup>
import { ref, watch, nextTick, computed } from 'vue'
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
  selectedGr: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'saveData',
])

const isFormValid = ref(false)
const refForm = ref()
const date = ref(new Date().toISOString().substr(0, 10))
const notes = ref('')
const items = ref([])
const photos = ref([])
const photoPreviews = ref([])

const isEditMode = computed(() => !!props.selectedGr)

// Generate previews when photos change
watch(() => photos.value, newPhotos => {
  photoPreviews.value.forEach(p => URL.revokeObjectURL(p))
  photoPreviews.value = []
  
  if (newPhotos) {
    const filesArray = Array.isArray(newPhotos) ? newPhotos : Array.from(newPhotos)

    filesArray.forEach(file => {
      const actualFile = (file && file.file) ? file.file : file
      if (actualFile instanceof File || actualFile instanceof Blob) {
        try {
          photoPreviews.value.push(URL.createObjectURL(actualFile))
        } catch(e) {
          console.error("Failed to create object URL for file", e)
        }
      }
    })
  }
}, { deep: true })

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
    items.value = []
    photos.value = []
    photoPreviews.value.forEach(p => URL.revokeObjectURL(p))
    photoPreviews.value = []
  })
}

const formatCurrency = val => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val || 0)
}

watch([() => props.selectedPo, () => props.selectedGr], ([newPo, newGr]) => {
  if (newGr) {
    // Edit Mode
    date.value = newGr.date ? newGr.date.substring(0, 10) : new Date().toISOString().substr(0, 10)
    notes.value = newGr.notes || ''
    
    if (newGr.purchase_order && newGr.purchase_order.items) {
      items.value = newGr.purchase_order.items.map(poItem => {
        const grItem = newGr.items?.find(i => i.purchase_order_item_id === poItem.id)
        
        return {
          purchase_order_item_id: poItem.id,
          product_id: poItem.product_id,
          product_name: poItem.product?.name || 'Produk',
          sku: poItem.product?.sku || '-',
          unit_name: poItem.unit_name || 'pcs',
          conversion_qty: poItem.conversion_qty || 1,
          gross_price: poItem.gross_price || poItem.unit_cost || 0,
          discount_percent_1: poItem.discount_percent_1 || 0,
          discount_percent_2: poItem.discount_percent_2 || 0,
          discount_amount: poItem.discount_amount || 0,
          final_cost_per_piece: poItem.final_cost_per_piece || poItem.unit_cost || 0,
          ordered_qty: poItem.qty,
          qty_received: grItem ? grItem.qty_received : 0,
          price: grItem?.price || 0,
          min_nego_price: grItem?.min_nego_price || 0,
          expiration_date: grItem && grItem.expiration_date ? grItem.expiration_date : '',
        }
      })
    }
  } else if (newPo && newPo.items) {
    // Create Mode
    date.value = new Date().toISOString().substr(0, 10)
    notes.value = ''
    items.value = newPo.items.map(poItem => ({
      purchase_order_item_id: poItem.id,
      product_id: poItem.product_id,
      product_name: poItem.product?.name || 'Produk',
      sku: poItem.product?.sku || '-',
      unit_name: poItem.unit_name || 'pcs',
      conversion_qty: poItem.conversion_qty || 1,
      gross_price: poItem.gross_price || poItem.unit_cost || 0,
      discount_percent_1: poItem.discount_percent_1 || 0,
      discount_percent_2: poItem.discount_percent_2 || 0,
      discount_amount: poItem.discount_amount || 0,
      final_cost_per_piece: poItem.final_cost_per_piece || poItem.unit_cost || 0,
      ordered_qty: poItem.qty,
      qty_received: poItem.qty,
      price: 0,
      min_nego_price: 0,
      expiration_date: '',
    }))
  } else {
    items.value = []
  }
}, { immediate: true })

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      if (items.value.every(i => i.qty_received === 0)) {
        alert('Anda belum menerima barang apapun (Qty Received = 0 semua).')
        return
      }
      
      if (!isEditMode.value && (!photos.value || photos.value.length === 0)) {
        alert('Anda wajib mengunggah bukti foto penerimaan barang.')
        return
      }

      const formData = new FormData()
      if (isEditMode.value) formData.append('id', props.selectedGr.id)
      formData.append('purchase_order_id', isEditMode.value ? props.selectedGr.purchase_order_id : props.selectedPo.id)
      formData.append('invoice_number_supplier', displayPo.value?.invoice_number_supplier || '')
      formData.append('tax_type', displayPo.value?.tax_type || 'include')
      formData.append('tax_percentage', displayPo.value?.tax_percentage || 11)
      formData.append('extra_discount', displayPo.value?.extra_discount || 0)
      formData.append('date', date.value)
      formData.append('notes', notes.value || '')
      formData.append('items', JSON.stringify(items.value))
      
      if (photos.value) {
        const filesArray = Array.isArray(photos.value) ? photos.value : Array.from(photos.value)

        filesArray.forEach(file => {
          const actualFile = (file && file.file) ? file.file : file
          formData.append('photos[]', actualFile)
        })
      }

      emit('saveData', formData)
      closeNavigationDrawer()
    }
  })
}

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
}

const displayPo = computed(() => isEditMode.value ? props.selectedGr?.purchase_order : props.selectedPo)
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="780"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <AppDrawerHeaderSection
      :title="isEditMode ? 'Edit Penerimaan Fisik' : 'Verifikasi Fisik Penerimaan Barang'"
      @cancel="closeNavigationDrawer"
    />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText class="pa-6">
          <!-- PO & Supplier Info Card -->
          <div class="mb-6 bg-var-theme-background pa-4 rounded-lg border">
            <VRow dense>
              <VCol cols="12" sm="6">
                <div class="text-caption text-medium-emphasis">Nomor PO:</div>
                <div class="font-weight-bold text-primary">{{ displayPo?.po_number }}</div>
              </VCol>
              <VCol cols="12" sm="6">
                <div class="text-caption text-medium-emphasis">No. Faktur Supplier:</div>
                <div class="font-weight-bold">{{ displayPo?.invoice_number_supplier || '-' }}</div>
              </VCol>
              <VCol cols="12" sm="6">
                <div class="text-caption text-medium-emphasis">Cabang Tujuan:</div>
                <div class="font-weight-bold">{{ displayPo?.branch?.name || '-' }}</div>
              </VCol>
              <VCol cols="12" sm="6">
                <div class="text-caption text-medium-emphasis">Supplier:</div>
                <div class="font-weight-bold">{{ displayPo?.supplier?.name || '-' }}</div>
              </VCol>
            </VRow>
          </div>

          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="date"
                  type="date"
                  :rules="[v => !!v || 'Tanggal wajib diisi']"
                  label="Tanggal Terima Fisik"
                  density="compact"
                />
              </VCol>

              <VCol cols="12" md="6">
                <VFileInput
                  v-model="photos"
                  multiple
                  chips
                  show-size
                  accept="image/*"
                  label="Bukti Foto Barang (Wajib)"
                  prepend-icon=""
                  prepend-inner-icon="ri-camera-line"
                  density="compact"
                  :rules="isEditMode ? [] : [v => (v && v.length > 0) || 'Minimal 1 foto bukti wajib diunggah']"
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="notes"
                  label="Catatan Penerimaan Gudang"
                  rows="2"
                  placeholder="Misal: Kardus tersegel utuh, barang telah dicek fisik..."
                  density="compact"
                  hide-details
                />
              </VCol>

              <!-- Preview Foto -->
              <VCol v-if="photoPreviews.length > 0" cols="12">
                <div class="text-caption font-weight-bold mb-2">
                  Pratinjau Foto Bukti Penerimaan:
                </div>
                <div class="d-flex flex-wrap gap-2">
                  <div 
                    v-for="(preview, index) in photoPreviews" 
                    :key="index"
                    class="border rounded overflow-hidden position-relative"
                    style="width: 80px; height: 80px;"
                  >
                    <img
                      :src="preview"
                      alt="Preview"
                      style="width: 100%; height: 100%; object-fit: cover;"
                    >
                    <div
                      class="position-absolute bg-primary text-white text-caption px-1 rounded"
                      style="top: 2px; left: 2px; line-height: 1.2;"
                    >
                      {{ index + 1 }}
                    </div>
                  </div>
                </div>
              </VCol>

              <VCol cols="12">
                <VDivider class="my-4" />
                <h6 class="text-subtitle-1 font-weight-bold mb-3 d-flex align-center gap-2">
                  <VIcon icon="ri-box-3-line" size="20" color="primary" />
                  Ceklis & Verifikasi Fisik Barang Datang
                </h6>

                <div class="border rounded overflow-hidden">
                  <VTable density="compact">
                    <thead>
                      <tr class="bg-grey-100">
                        <th class="font-weight-bold">Nama Barang</th>
                        <th class="text-center font-weight-bold" style="width: 110px;">Dipesan</th>
                        <th class="text-center font-weight-bold" style="width: 130px;">Diterima Fisik</th>
                        <th class="text-center font-weight-bold" style="width: 130px;">HPP / Pcs</th>
                        <th class="font-weight-bold" style="width: 150px;">Kedaluwarsa</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="(item, index) in items"
                        :key="index"
                      >
                        <td>
                          <div class="font-weight-bold">{{ item.product_name }}</div>
                          <div class="text-caption text-medium-emphasis">
                            SKU: <code>{{ item.sku }}</code> | Satuan: <strong>{{ item.unit_name }}</strong> (isi {{ item.conversion_qty }} pcs)
                          </div>
                        </td>
                        <td class="text-center font-weight-bold text-medium-emphasis">
                          {{ item.ordered_qty }} {{ item.unit_name }}
                        </td>
                        <td>
                          <VTextField
                            v-model.number="item.qty_received"
                            type="number"
                            density="compact"
                            hide-details
                            min="0"
                            :max="item.ordered_qty"
                            class="text-center font-weight-bold"
                          />
                        </td>
                        <td class="text-center">
                          <VChip color="success" size="small" variant="tonal">
                            {{ formatCurrency(item.final_cost_per_piece) }}
                          </VChip>
                        </td>
                        <td>
                          <VTextField
                            v-model="item.expiration_date"
                            type="date"
                            density="compact"
                            hide-details
                          />
                        </td>
                      </tr>
                    </tbody>
                  </VTable>
                </div>
              </VCol>

              <VCol cols="12" class="mt-4">
                <VAlert
                  v-if="isEditMode"
                  color="error"
                  variant="tonal"
                  class="mb-4"
                >
                  PERHATIAN: Mengedit riwayat penerimaan akan merevisi ulang stok dan HPP barang di cabang terkait.
                </VAlert>
                <VAlert
                  v-else
                  color="info"
                  variant="tonal"
                  class="mb-4"
                >
                  Setelah disimpan sebagai draft dan disetujui supervisor/owner, stok di cabang akan resmi bertambah dan HPP otomatis diperbarui sesuai faktur supplier.
                </VAlert>
                <div class="d-flex gap-2">
                  <VBtn
                    type="submit"
                    color="primary"
                    prepend-icon="ri-save-line"
                    class="font-weight-bold"
                  >
                    {{ isEditMode ? 'Simpan Revisi' : 'Simpan Penerimaan Barang' }}
                  </VBtn>
                  <VBtn
                    type="reset"
                    variant="outlined"
                    color="secondary"
                    @click="closeNavigationDrawer"
                  >
                    Batal
                  </VBtn>
                </div>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>
