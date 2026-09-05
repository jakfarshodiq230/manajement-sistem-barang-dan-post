<script setup>
import { ref, watch, computed } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  adjustmentId: {
    type: Number,
    default: null,
  },
})

const emit = defineEmits([
  'update:isDialogVisible',
  'close',
  'applied',
])

const snackbar = useSnackbarStore()
const isLoading = ref(false)
const isApplying = ref(false)
const isCancelling = ref(false)
const isDownloadingPdf = ref(false)
const adjustment = ref(null)

const formatCurrency = val => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val || 0)
}

const formatDate = dateStr => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}

// Summary Metrics
const totalItems = computed(() => adjustment.value?.items?.length || 0)

const totalValueDiff = computed(() => {
  if (!adjustment.value?.items) return 0
  return adjustment.value.items.reduce((acc, i) => acc + (Number(i.new_price) - Number(i.old_price)), 0)
})

const increasedCount = computed(() => {
  if (!adjustment.value?.items) return 0
  return adjustment.value.items.filter(i => Number(i.new_price) > Number(i.old_price)).length
})

const decreasedCount = computed(() => {
  if (!adjustment.value?.items) return 0
  return adjustment.value.items.filter(i => Number(i.new_price) < Number(i.old_price)).length
})

const fetchDetail = async () => {
  if (!props.adjustmentId) return
  isLoading.value = true
  try {
    const res = await $api(`/apps/price-adjustments/${props.adjustmentId}`)
    adjustment.value = res.data
  } catch (e) {
    console.error(e)
    snackbar.showSnackbar('Gagal memuat rincian dokumen penyesuaian harga', 'error')
  } finally {
    isLoading.value = false
  }
}

watch(() => props.adjustmentId, id => {
  if (id && props.isDialogVisible) {
    fetchDetail()
  }
})

watch(() => props.isDialogVisible, val => {
  if (val && props.adjustmentId) {
    fetchDetail()
  }
})

const handleClose = () => {
  emit('update:isDialogVisible', false)
  emit('close')
}

// Download PDF
const downloadPdf = async () => {
  if (!props.adjustmentId) return
  isDownloadingPdf.value = true
  try {
    const token = useCookie('accessToken').value
    const res = await fetch(`/api/apps/price-adjustments/${props.adjustmentId}/export-pdf`, {
      headers: {
        'Authorization': `Bearer ${token || ''}`,
        'Accept': 'application/pdf',
      },
    })

    if (!res.ok) {
      const errData = await res.json().catch(() => ({}))
      throw new Error(errData.message || 'Gagal mengunduh SK Penetapan Harga PDF')
    }

    const blob = await res.blob()
    const url = window.URL.createObjectURL(blob)

    // Open PDF in new tab for direct viewing/printing
    window.open(url, '_blank')

    // Also trigger file download
    const a = document.createElement('a')
    a.href = url
    a.download = `SK_Penetapan_Harga_${adjustment.value?.adjustment_number || props.adjustmentId}.pdf`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)

    setTimeout(() => window.URL.revokeObjectURL(url), 10000)
  } catch (err) {
    console.error(err)
    snackbar.showSnackbar(err.message || 'Gagal mencetak dokumen PDF', 'error')
  } finally {
    isDownloadingPdf.value = false
  }
}

// Approve & Apply
const applyAdjustment = async () => {
  if (!props.adjustmentId) return
  isApplying.value = true
  try {
    const res = await $api(`/apps/price-adjustments/${props.adjustmentId}/apply`, {
      method: 'POST',
    })

    if (res.success) {
      snackbar.showSnackbar('Penyesuaian harga berhasil disahkan dan diterapkan serentak ke kasir!', 'success')
      await fetchDetail()
      emit('applied')
    }
  } catch (err) {
    console.error(err)
    snackbar.showSnackbar(err?.response?._data?.message || 'Gagal mengesahkan penyesuaian harga', 'error')
  } finally {
    isApplying.value = false
  }
}

// Cancel Document
const cancelAdjustment = async () => {
  if (!props.adjustmentId) return
  isCancelling.value = true
  try {
    const res = await $api(`/apps/price-adjustments/${props.adjustmentId}/cancel`, {
      method: 'POST',
    })

    if (res.success) {
      snackbar.showSnackbar('Dokumen penyesuaian harga berhasil dibatalkan', 'info')
      await fetchDetail()
      emit('applied')
    }
  } catch (err) {
    console.error(err)
    snackbar.showSnackbar('Gagal membatalkan dokumen', 'error')
  } finally {
    isCancelling.value = false
  }
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    max-width="950"
    scrollable
    @update:model-value="val => emit('update:isDialogVisible', val)"
  >
    <VCard class="rounded-lg">
      <!-- Dialog Header -->
      <VCardTitle class="d-flex align-center justify-space-between pa-5 border-b">
        <div class="d-flex align-center gap-3">
          <VAvatar color="primary" variant="tonal" rounded="lg" size="42">
            <VIcon icon="ri-price-tag-3-line" size="24" />
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-bold">
              {{ adjustment?.title || 'Rincian Penyesuaian Harga' }}
            </div>
            <div class="text-caption font-mono text-medium-emphasis">
              No. Dokumen: {{ adjustment?.adjustment_number }}
            </div>
          </div>
        </div>

        <div class="d-flex align-center gap-2">
          <VChip
            v-if="adjustment"
            size="small"
            class="font-weight-bold text-uppercase"
            :color="adjustment.status === 'approved' ? 'success' : (adjustment.status === 'draft' ? 'warning' : 'secondary')"
          >
            {{ adjustment.status === 'approved' ? 'DISETUJUI & BERLAKU' : (adjustment.status === 'draft' ? 'DRAFT USULAN' : 'DIBATALKAN') }}
          </VChip>
          <VBtn icon="ri-close-line" variant="text" color="default" @click="handleClose" />
        </div>
      </VCardTitle>

      <VCardText class="pa-5">
        <div v-if="isLoading" class="text-center py-10">
          <VProgressCircular indeterminate color="primary" />
          <div class="text-caption mt-2">Memuat dokumen penyesuaian harga...</div>
        </div>

        <template v-else-if="adjustment">
          <!-- Metadata Card -->
          <VCard elevation="0" class="border rounded-lg pa-4 mb-4 bg-var-theme-surface">
            <VRow dense>
              <VCol cols="12" md="4">
                <div class="text-caption text-medium-emphasis">Tanggal Berlaku Efektif</div>
                <div class="font-weight-bold text-body-2">
                  {{ formatDate(adjustment.effective_date) }}
                </div>
              </VCol>

              <VCol cols="12" md="4">
                <div class="text-caption text-medium-emphasis">Target Cabang Toko</div>
                <div class="font-weight-bold text-body-2">
                  {{ adjustment.branch ? adjustment.branch.name : 'Semua Cabang Toko (Pusat & Cabang)' }}
                </div>
              </VCol>

              <VCol cols="12" md="4">
                <div class="text-caption text-medium-emphasis">Alasan Penyesuaian</div>
                <div class="font-weight-bold text-body-2 text-primary">
                  {{ adjustment.reason || '-' }}
                </div>
              </VCol>

              <VCol cols="12" md="6" class="mt-2">
                <div class="text-caption text-medium-emphasis">Dibuat Oleh</div>
                <div class="text-body-2 font-weight-medium">
                  {{ adjustment.creator?.name || 'Admin' }} ({{ formatDate(adjustment.created_at) }})
                </div>
              </VCol>

              <VCol cols="12" md="6" class="mt-2">
                <div class="text-caption text-medium-emphasis">Disahkan & Disetujui Oleh</div>
                <div class="text-body-2 font-weight-medium">
                  <span v-if="adjustment.approved_by" class="text-success font-weight-bold">
                    {{ adjustment.approver?.name || 'Owner' }} ({{ formatDate(adjustment.approved_at) }})
                  </span>
                  <span v-else class="text-warning">
                    Menunggu Persetujuan Owner / Manajemen
                  </span>
                </div>
              </VCol>

              <VCol v-if="adjustment.notes" cols="12" class="mt-2">
                <div class="text-caption text-medium-emphasis">Catatan Memo</div>
                <div class="text-body-2 fst-italic">{{ adjustment.notes }}</div>
              </VCol>
            </VRow>
          </VCard>

          <!-- KPI Summary Cards -->
          <VRow class="mb-4" dense>
            <VCol cols="12" sm="3">
              <VCard elevation="0" class="border rounded-lg pa-3 text-center">
                <div class="text-caption text-medium-emphasis font-weight-bold text-uppercase">Total Produk</div>
                <div class="text-h6 font-weight-bold">{{ totalItems }} SKU</div>
              </VCard>
            </VCol>

            <VCol cols="12" sm="3">
              <VCard elevation="0" class="border rounded-lg pa-3 text-center">
                <div class="text-caption text-success font-weight-bold text-uppercase">Produk Naik</div>
                <div class="text-h6 font-weight-bold text-success">+{{ increasedCount }} Item</div>
              </VCard>
            </VCol>

            <VCol cols="12" sm="3">
              <VCard elevation="0" class="border rounded-lg pa-3 text-center">
                <div class="text-caption text-medium-emphasis font-weight-bold text-uppercase">Produk Turun / Tetap</div>
                <div class="text-h6 font-weight-bold">{{ decreasedCount }} Item</div>
              </VCard>
            </VCol>

            <VCol cols="12" sm="3">
              <VCard elevation="0" class="border rounded-lg pa-3 text-center">
                <div class="text-caption text-primary font-weight-bold text-uppercase">Total Selisih Nilai</div>
                <div
                  class="text-h6 font-weight-bold font-mono"
                  :class="totalValueDiff >= 0 ? 'text-success' : 'text-error'"
                >
                  {{ totalValueDiff >= 0 ? '+' : '' }}{{ formatCurrency(totalValueDiff) }}
                </div>
              </VCard>
            </VCol>
          </VRow>

          <!-- Item Table -->
          <VCard elevation="0" class="border rounded-lg">
            <VTable density="compact" class="text-no-wrap" hover>
              <thead>
                <tr>
                  <th style="inline-size: 40px;">No</th>
                  <th>Produk & SKU</th>
                  <th class="text-end">HPP Modal</th>
                  <th class="text-end">Harga Lama</th>
                  <th class="text-end">Harga Baru</th>
                  <th class="text-end">Selisih Kenaikan</th>
                  <th class="text-end">Harga Min. Nego</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, idx) in adjustment.items" :key="item.id">
                  <td>{{ idx + 1 }}</td>
                  <td>
                    <div class="font-weight-bold text-body-2">{{ item.product?.name || '-' }}</div>
                    <div class="text-caption font-mono text-medium-emphasis">
                      {{ item.product?.sku || '-' }} • {{ item.product?.category?.name || 'Umum' }}
                    </div>
                  </td>
                  <td class="text-end font-mono text-medium-emphasis">
                    {{ formatCurrency(item.new_cost_price) }}
                  </td>
                  <td class="text-end font-mono text-medium-emphasis">
                    {{ formatCurrency(item.old_price) }}
                  </td>
                  <td class="text-end font-mono font-weight-bold text-primary">
                    {{ formatCurrency(item.new_price) }}
                  </td>
                  <td class="text-end font-mono">
                    <VChip
                      size="x-small"
                      class="font-weight-bold"
                      :color="item.new_price > item.old_price ? 'success' : (item.new_price < item.old_price ? 'error' : 'secondary')"
                    >
                      {{ item.new_price > item.old_price ? '+' : '' }}{{ formatCurrency(item.new_price - item.old_price) }}
                    </VChip>
                  </td>
                  <td class="text-end font-mono text-medium-emphasis">
                    {{ formatCurrency(item.new_min_nego_price) }}
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCard>
        </template>
      </VCardText>

      <!-- Dialog Footer -->
      <VCardActions class="pa-4 border-t d-flex align-center justify-space-between bg-var-theme-surface">
        <VBtn
          color="secondary"
          variant="tonal"
          prepend-icon="ri-file-pdf-2-line"
          :loading="isDownloadingPdf"
          @click="downloadPdf"
        >
          Cetak PDF Resmi
        </VBtn>

        <div class="d-flex align-center gap-2">
          <VBtn
            v-if="adjustment?.status === 'draft'"
            color="error"
            variant="text"
            :loading="isCancelling"
            @click="cancelAdjustment"
          >
            Batalkan Dokumen
          </VBtn>

          <VBtn
            v-if="adjustment?.status === 'draft'"
            color="success"
            prepend-icon="ri-check-double-line"
            :loading="isApplying"
            @click="applyAdjustment"
          >
            Setujui & Terapkan Harga ke Kasir
          </VBtn>

          <VBtn variant="tonal" color="secondary" @click="handleClose">
            Tutup
          </VBtn>
        </div>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
