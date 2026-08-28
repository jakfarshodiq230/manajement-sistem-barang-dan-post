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
  const num = typeof value === 'number' ? value : Number(String(value).replace(/[^0-9.-]+/g, ''))
  if (isNaN(num)) return ''
  return new Intl.NumberFormat('id-ID').format(Math.round(num))
}

const parseInputRupiah = value => {
  if (!value) return 0
  const clean = String(value).replace(/[^0-9]/g, '')
  return clean ? Number(clean) : 0
}

const formatCurrency = value => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value || 0)
}

const fetchBatches = async () => {
  if (!props.selectedData?.id) return
  
  isLoading.value = true
  try {
    const res = await $api(`/apps/product-branches/${props.selectedData.id}`)
    const loadedBatches = res.product_batches || []
    
    batches.value = loadedBatches.map(b => ({
      ...b,
      cost_price_display: formatInputRupiah(b.cost_price || 0),
      price_display: formatInputRupiah(b.price || 0),
      min_nego_price_display: formatInputRupiah(b.min_nego_price || 0),
      isSaving: false,
      saved: false,
    }))
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

const onCostPriceInput = (val, batch) => {
  const num = parseInputRupiah(val)
  batch.cost_price = num
  batch.cost_price_display = num ? formatInputRupiah(num) : ''
}

const onPriceInput = (val, batch) => {
  const num = parseInputRupiah(val)
  batch.price = num
  batch.price_display = num ? formatInputRupiah(num) : ''
}

const onMinNegoPriceInput = (val, batch) => {
  const num = parseInputRupiah(val)
  batch.min_nego_price = num
  batch.min_nego_price_display = num ? formatInputRupiah(num) : ''
}

const applyMarkup = (batch, percent) => {
  const cost = Number(batch.cost_price) || 0
  if (cost > 0) {
    batch.price = Math.round(cost * (1 + percent / 100))
    batch.price_display = formatInputRupiah(batch.price)
    batch.min_nego_price = Math.round(cost * 1.10)
    batch.min_nego_price_display = formatInputRupiah(batch.min_nego_price)
  }
}

const applyBatchToAll = sourceBatch => {
  batches.value.forEach(b => {
    b.cost_price = sourceBatch.cost_price
    b.cost_price_display = formatInputRupiah(sourceBatch.cost_price)
    b.price = sourceBatch.price
    b.price_display = formatInputRupiah(sourceBatch.price)
    b.min_nego_price = sourceBatch.min_nego_price
    b.min_nego_price_display = formatInputRupiah(sourceBatch.min_nego_price)
  })
}

const saveBatchPrice = async (batch, applyToAll = false) => {
  if (batch.isSaving) return
  
  batch.isSaving = true
  try {
    await $api(`/apps/product-batches/${batch.id}`, {
      method: 'PUT',
      body: {
        cost_price: batch.cost_price,
        price: batch.price,
        min_nego_price: batch.min_nego_price,
        apply_to_all_batches: applyToAll,
      },
    })
    
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

const isSavingAll = ref(false)
const saveAllBatches = async () => {
  if (isSavingAll.value || batches.value.length === 0) return
  isSavingAll.value = true
  try {
    for (const batch of batches.value) {
      await $api(`/apps/product-batches/${batch.id}`, {
        method: 'PUT',
        body: {
          cost_price: batch.cost_price,
          price: batch.price,
          min_nego_price: batch.min_nego_price,
        },
      })
      batch.saved = true
    }
    setTimeout(() => { batches.value.forEach(b => b.saved = false) }, 2000)
    emit('refreshData')
  } catch (error) {
    console.error(error)
    alert('Gagal menyimpan beberapa batch: ' + (error.data?.message || error.message))
  } finally {
    isSavingAll.value = false
  }
}

const closeDialog = () => {
  emit('update:isDialogVisible', false)
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    max-width="960"
    scrollable
    @update:model-value="(val) => emit('update:isDialogVisible', val)"
  >
    <VCard class="rounded-xl overflow-hidden shadow-lg d-flex flex-column" style="max-height: 90vh;" :loading="isLoading">
      <!-- Header -->
      <div class="px-6 py-5 border-b bg-gradient-header d-flex justify-space-between align-center flex-shrink-0">
        <div class="d-flex align-center gap-3">
          <VAvatar color="primary" variant="tonal" size="44" class="rounded-lg">
            <VIcon icon="ri-qr-code-line" size="24" />
          </VAvatar>
          <div>
            <h5 class="text-h6 font-weight-bold mb-0">
              Detail Batch, Kode SCC Aki & Harga Inventori
            </h5>
            <span class="text-caption text-medium-emphasis">
              Produk: <strong>{{ props.selectedData?.product?.name }}</strong> | Cabang: <strong>{{ props.selectedData?.branch?.name }}</strong>
            </span>
          </div>
        </div>
        <VBtn
          icon="ri-close-line"
          variant="text"
          size="small"
          color="secondary"
          @click="closeDialog"
        />
      </div>

      <VCardText class="pa-6 overflow-y-auto" style="max-height: calc(90vh - 130px);">
        <div class="mb-4 pa-4 rounded-xl border bg-var-theme-surface shadow-xs">
          <VRow dense align="center">
            <VCol cols="12" sm="4">
              <div class="text-caption text-medium-emphasis">Total Stok Gabungan:</div>
              <div class="text-h6 font-weight-bold text-primary">
                {{ props.selectedData?.stock || 0 }} {{ props.selectedData?.product?.unit || 'Unit' }}
              </div>
            </VCol>
            <VCol cols="12" sm="4">
              <div class="text-caption text-medium-emphasis">Jumlah Batch Tercatat:</div>
              <div class="text-h6 font-weight-bold text-success">
                {{ batches.length }} Batch Fisik
              </div>
            </VCol>
            <VCol cols="12" sm="4">
              <div class="text-caption text-medium-emphasis">Metode Alur Stok:</div>
              <div class="font-weight-bold text-uppercase text-info">
                {{ props.selectedData?.product?.stock_method || 'FIFO (First In First Out)' }}
              </div>
            </VCol>
          </VRow>
        </div>

        <VAlert type="info" variant="tonal" density="compact" class="mb-4 text-caption rounded-lg py-2">
          <strong>Edit Harga per Batch:</strong> Anda dapat menyesuaikan <strong>HPP Real (Modal)</strong>, <strong>Harga Jual (POS)</strong>, dan <strong>Min. Nego Kasir</strong> untuk setiap batch stok secara fleksibel atau menyamakan seluruh batch sekaligus.
        </VAlert>

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
          <span>Memuat rincian data batch dan serial SCC...</span>
        </div>
        
        <div
          v-else-if="batches.length > 0"
          class="border rounded-xl overflow-hidden"
        >
          <table class="w-100 table-receipt">
            <thead>
              <tr class="bg-grey-100 text-left">
                <th class="pa-3 text-xs text-center" style="width: 40px;">NO</th>
                <th class="pa-3 text-xs">KODE SCC & NO. BATCH</th>
                <th class="pa-3 text-xs">TGL MASUK & EXP</th>
                <th class="pa-3 text-xs text-center" style="width: 75px;">SISA STOK</th>
                <th class="pa-3 text-xs" style="width: 150px;">HPP REAL (MODAL)</th>
                <th class="pa-3 text-xs" style="width: 170px;">HARGA JUAL (POS)</th>
                <th class="pa-3 text-xs" style="width: 150px;">MIN. NEGO (KASIR)</th>
                <th class="pa-3 text-xs text-center" style="width: 80px;">AKSI</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(batch, idx) in batches"
                :key="batch.id"
                class="border-b"
              >
                <td class="pa-2 text-xs text-center font-weight-bold">{{ idx + 1 }}</td>
                <td class="pa-2 text-xs">
                  <div v-if="batch.scc_code" class="font-weight-bold text-primary d-flex align-center gap-1">
                    <VIcon icon="ri-qr-code-line" size="14" />
                    <code>{{ batch.scc_code }}</code>
                  </div>
                  <div v-if="batch.batch_number" class="text-caption text-medium-emphasis">
                    Batch: <code>{{ batch.batch_number }}</code>
                  </div>
                  <div v-if="!batch.scc_code && !batch.batch_number" class="text-caption text-disabled">
                    Batch Default #{{ batch.id }}
                  </div>
                </td>
                <td class="pa-2 text-xs">
                  <div class="text-caption">Masuk: {{ batch.entry_date ? batch.entry_date.substring(0, 10) : '-' }}</div>
                  <div v-if="batch.expiration_date" class="text-caption text-warning font-weight-medium">
                    Exp: {{ batch.expiration_date.substring(0, 10) }}
                  </div>
                </td>
                <td class="pa-2 text-xs text-center">
                  <VChip
                    size="small"
                    :color="batch.qty > 0 ? 'success' : 'error'"
                    class="font-weight-bold"
                  >
                    {{ batch.qty }}
                  </VChip>
                </td>
                <td class="pa-2 text-xs">
                  <VTextField
                    :model-value="batch.cost_price_display"
                    density="compact"
                    variant="outlined"
                    prefix="Rp"
                    hide-details
                    placeholder="0"
                    class="font-mono text-error font-weight-bold"
                    @update:model-value="val => onCostPriceInput(val, batch)"
                  />
                </td>
                <td class="pa-2 text-xs">
                  <VTextField
                    :model-value="batch.price_display"
                    density="compact"
                    variant="outlined"
                    prefix="Rp"
                    hide-details
                    placeholder="0"
                    class="font-mono text-success font-weight-bold"
                    @update:model-value="val => onPriceInput(val, batch)"
                  />
                  <div class="d-flex align-center gap-1 mt-1">
                    <span class="text-caption text-disabled" style="font-size: 9px;">Preset:</span>
                    <VBtn size="x-small" variant="tonal" color="primary" class="px-1" style="font-size: 9px; height: 18px; min-width: 28px;" @click="applyMarkup(batch, 15)">+15%</VBtn>
                    <VBtn size="x-small" variant="tonal" color="primary" class="px-1" style="font-size: 9px; height: 18px; min-width: 28px;" @click="applyMarkup(batch, 20)">+20%</VBtn>
                    <VBtn size="x-small" variant="tonal" color="primary" class="px-1" style="font-size: 9px; height: 18px; min-width: 28px;" @click="applyMarkup(batch, 25)">+25%</VBtn>
                  </div>
                </td>
                <td class="pa-2 text-xs">
                  <VTextField
                    :model-value="batch.min_nego_price_display"
                    density="compact"
                    variant="outlined"
                    prefix="Rp"
                    hide-details
                    placeholder="0"
                    class="font-mono text-warning font-weight-bold"
                    @update:model-value="val => onMinNegoPriceInput(val, batch)"
                  />
                  <div v-if="batch.price > 0 && batch.cost_price > 0" class="text-caption text-success mt-1" style="font-size: 10px;">
                    Laba: +{{ formatCurrency(batch.price - batch.cost_price) }}
                  </div>
                </td>
                <td class="pa-2 text-xs text-center">
                  <div class="d-flex align-center justify-center gap-1">
                    <VBtn
                      icon
                      size="small"
                      :color="batch.saved ? 'success' : 'primary'"
                      :loading="batch.isSaving"
                      variant="tonal"
                      title="Simpan Batch Ini"
                      @click="saveBatchPrice(batch, false)"
                    >
                      <VIcon :icon="batch.saved ? 'ri-check-line' : 'ri-save-line'" size="16" />
                    </VBtn>
                    <VBtn
                      v-if="batches.length > 1"
                      icon
                      size="small"
                      color="info"
                      variant="text"
                      title="Salin Harga Batch Ini ke Semua Batch Lain"
                      @click="applyBatchToAll(batch)"
                    >
                      <VIcon icon="ri-file-copy-line" size="16" />
                    </VBtn>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div
          v-else
          class="pa-8 text-center border rounded-xl bg-grey-50 text-medium-emphasis"
        >
          <VIcon icon="ri-inbox-archive-line" size="36" class="mb-2 text-disabled" />
          <div class="font-weight-medium">Belum ada data batch fisik untuk produk ini.</div>
          <div class="text-caption">Data batch dan nomor SCC akan otomatis terbentuk saat barang diterima dari Supplier melalui menu <strong>Penerimaan Barang</strong>.</div>
        </div>
      </VCardText>
      
      <VCardActions class="px-6 py-4 border-t d-flex justify-space-between align-center bg-grey-50 flex-wrap gap-2 flex-shrink-0">
        <div class="d-flex align-center gap-2">
          <VBtn
            v-if="batches.length > 0"
            color="primary"
            variant="flat"
            prepend-icon="ri-save-3-line"
            :loading="isSavingAll"
            @click="saveAllBatches"
          >
            Simpan Semua Batch
          </VBtn>
        </div>
        <VBtn
          color="secondary"
          variant="outlined"
          @click="closeDialog"
        >
          Tutup
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.bg-gradient-header {
  background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.05) 0%, rgba(var(--v-theme-surface), 1) 100%);
}
.table-receipt {
  border-collapse: collapse;
}
.table-receipt th {
  font-weight: 600;
}
</style>
