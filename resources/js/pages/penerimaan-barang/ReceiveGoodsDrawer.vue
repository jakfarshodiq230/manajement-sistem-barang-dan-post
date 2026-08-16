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
  // Clear old previews to avoid memory leaks
  photoPreviews.value.forEach(p => URL.revokeObjectURL(p))
  photoPreviews.value = []
  
  if (newPhotos) {
    // Convert FileList to Array if necessary
    const filesArray = Array.isArray(newPhotos) ? newPhotos : Array.from(newPhotos)

    filesArray.forEach(file => {
      // In some Vuetify versions, the file might be wrapped in an object
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

watch([() => props.selectedPo, () => props.selectedGr], ([newPo, newGr]) => {
  if (newGr) {
    // Edit Mode
    date.value = newGr.date ? newGr.date.substring(0, 10) : new Date().toISOString().substr(0, 10)
    notes.value = newGr.notes || ''
    
    // We need to match with PO items to know max qty
    // newGr.purchase_order has the items
    if (newGr.purchase_order && newGr.purchase_order.items) {
      items.value = newGr.purchase_order.items.map(poItem => {
        // Find if this PO item was received in GR
        const grItem = newGr.items?.find(i => i.purchase_order_item_id === poItem.id)
        
        return {
          purchase_order_item_id: poItem.id,
          product_id: poItem.product_id,
          product_name: poItem.product?.name || 'Produk',
          ordered_qty: poItem.qty,
          qty_received: grItem ? grItem.qty_received : 0,
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
      ordered_qty: poItem.qty,
      qty_received: poItem.qty, // default to receiving everything
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
    :width="600"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <AppDrawerHeaderSection
      :title="isEditMode ? 'Edit Penerimaan Fisik' : 'Verifikasi Fisik Penerimaan'"
      @cancel="closeNavigationDrawer"
    />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <div class="mb-6 bg-var-theme-background pa-4 rounded">
            <div class="text-sm">
              Nomor PO: <span class="font-weight-bold">{{ displayPo?.po_number }}</span>
            </div>
            <div class="text-sm">
              Cabang Tujuan: <span class="font-weight-bold">{{ displayPo?.branch?.name }}</span>
            </div>
            <div class="text-sm">
              Supplier: <span class="font-weight-bold">{{ displayPo?.supplier?.name }}</span>
            </div>
          </div>

          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <VCol cols="12">
                <VTextField
                  v-model="date"
                  type="date"
                  :rules="[v => !!v || 'Tanggal wajib diisi']"
                  label="Tanggal Terima Fisik"
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="notes"
                  label="Catatan Penerimaan"
                  rows="2"
                  placeholder="Misal: Kardus sedikit sobek, dll."
                />
              </VCol>

              <VCol cols="12">
                <VFileInput
                  v-model="photos"
                  multiple
                  chips
                  show-size
                  accept="image/*"
                  label="Bukti Foto Barang (Wajib)"
                  prepend-icon=""
                  prepend-inner-icon="ri-image-add-line"
                  :rules="isEditMode ? [] : [v => (v && v.length > 0) || 'Minimal 1 foto bukti wajib diunggah']"
                  hint="Anda bisa memilih lebih dari 1 foto"
                  persistent-hint
                />
                
                <!-- Preview Foto yang akan diupload -->
                <div
                  v-if="photoPreviews.length > 0"
                  class="mt-4"
                >
                  <div class="text-caption font-weight-bold mb-2">
                    Pratinjau Foto yang Dipilih:
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
                </div>

                <!-- Preview Foto yang sudah ada di database (saat edit) -->
                <div
                  v-if="isEditMode && selectedGr?.photos && selectedGr.photos.length > 0"
                  class="mt-4"
                >
                  <div class="text-caption font-weight-bold mb-2">
                    Foto Tersimpan Sebelumnya:
                  </div>
                  <div class="d-flex flex-wrap gap-2">
                    <a 
                      v-for="(photo, index) in selectedGr.photos" 
                      :key="index"
                      :href="'/storage/' + photo"
                      target="_blank"
                      class="d-inline-block border rounded overflow-hidden"
                      style="width: 60px; height: 60px; opacity: 0.8;"
                    >
                      <img
                        :src="'/storage/' + photo"
                        alt="Tersimpan"
                        style="width: 100%; height: 100%; object-fit: cover;"
                      >
                    </a>
                  </div>
                  <div class="mt-2 text-caption text-info">
                    <i>Catatan: Mengunggah foto baru akan menambahkan foto di atas, bukan menghapusnya.</i>
                  </div>
                </div>
              </VCol>

              <VCol cols="12">
                <VDivider class="my-4" />
                <h6 class="text-h6 font-weight-medium mb-4">
                  Ceklis Barang Datang
                </h6>

                <VTable
                  density="compact"
                  class="text-no-wrap"
                >
                  <thead>
                    <tr>
                      <th class="text-left">
                        Nama Barang
                      </th>
                      <th class="text-center">
                        Dipesan
                      </th>
                      <th
                        class="text-center"
                        style="width: 150px"
                      >
                        Diterima (Fisik)
                      </th>
                      <th
                        class="text-center"
                        style="width: 150px"
                      >
                        Kedaluwarsa (Opt)
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="(item, index) in items"
                      :key="index"
                    >
                      <td>{{ item.product_name }}</td>
                      <td class="text-center">
                        {{ item.ordered_qty }}
                      </td>
                      <td>
                        <VTextField
                          v-model="item.qty_received"
                          type="number"
                          density="compact"
                          hide-details
                          min="0"
                          :max="item.ordered_qty"
                        />
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
              </VCol>

              <VCol
                cols="12"
                class="mt-4"
              >
                <VAlert
                  v-if="isEditMode"
                  color="error"
                  variant="tonal"
                  class="mb-4"
                >
                  PERHATIAN: Mengedit riwayat penerimaan akan merubah ulang (rollback & re-apply) riwayat stok dan jumlah stok saat ini di cabang!
                </VAlert>
                <VAlert
                  v-else
                  color="warning"
                  variant="tonal"
                  class="mb-4"
                >
                  Pastikan jumlah fisik sudah benar. Setelah disimpan, stok di cabang akan bertambah secara otomatis dan status PO akan selesai.
                </VAlert>
                <VBtn
                  type="submit"
                  class="me-3"
                >
                  {{ isEditMode ? 'Simpan Revisi' : 'Simpan & Masukkan Stok' }}
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
