<script setup>
import { ref, computed } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import { useAbility } from '@casl/vue'

const props = defineProps({
  documentId: {
    type: [Number, String],
    required: true,
  },
  documentType: {
    type: String,
    required: true,

    // 'purchase_order', 'goods_receipt', 'return_transaction', 'sale'
  },
  approvalStatus: {
    type: String,
    default: 'pending', // pending, validated, approved
  },
  documentStatus: {
    type: String,
    default: '', // pending, completed, cancelled
  },
})

const emit = defineEmits(['status-updated'])

const snackbar = useSnackbarStore()
const ability = useAbility()

const isDownloading = ref(false)
const isSubmitting = ref(false)
const isValidating = ref(false)
const isApproving = ref(false)
const isRejecting = ref(false)
const isRejectDialogVisible = ref(false)
const rejectionReason = ref('')
const isPdfDialogVisible = ref(false)
const pdfBlobUrl = ref('')

const isPinDialogVisible = ref(false)
const pin = ref('')
const isVerifyingPin = ref(false)
const pinErrorMsg = ref('')

const moduleName = computed(() => {
  switch (props.documentType) {
  case 'purchase_order': return 'Purchase Order'
  case 'goods_receipt': return 'Penerimaan Gudang'
  case 'return_transaction': return 'Retur Barang'
  case 'sale': return 'Penjualan'
  default: return 'Documents'
  }
})

const canValidate = computed(() => ability.can('validate', moduleName.value) || ability.can('manage', 'all'))
const canApprove = computed(() => ability.can('approve', moduleName.value) || ability.can('manage', 'all'))
const canSubmit = computed(() => ability.can('create', moduleName.value) || ability.can('manage', 'all'))

const isActionDisabled = computed(() => {
  return props.documentStatus?.toLowerCase() === 'completed' || props.documentStatus?.toLowerCase() === 'cancelled'
})

const downloadPdf = async () => {
  isDownloading.value = true
  try {
    const response = await $api(`/apps/documents/${props.documentType}/${props.documentId}/pdf`, {
      responseType: 'blob',
    })
    
    // Create a blob URL from the response
    const blob = new Blob([response], { type: 'application/pdf' })

    pdfBlobUrl.value = URL.createObjectURL(blob)
    
    // Open the popup modal
    isPdfDialogVisible.value = true
    snackbar.show('PDF berhasil dimuat', 'success')
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal memuat PDF', 'error')
  } finally {
    isDownloading.value = false
  }
}

const closePdfDialog = () => {
  isPdfDialogVisible.value = false
  if (pdfBlobUrl.value) {
    URL.revokeObjectURL(pdfBlobUrl.value)
    pdfBlobUrl.value = ''
  }
}

const submitDocument = async () => {
  isSubmitting.value = true
  try {
    const response = await $api(`/apps/documents/${props.documentType}/${props.documentId}/submit`, {
      method: 'POST',
    })

    snackbar.show('Dokumen berhasil diajukan untuk validasi', 'success')
    emit('status-updated', 'pending')
  } catch (error) {
    snackbar.show('Gagal mengajukan dokumen', 'error')
  } finally {
    isSubmitting.value = false
  }
}

const validateDocument = async () => {
  isValidating.value = true
  try {
    const response = await $api(`/apps/documents/${props.documentType}/${props.documentId}/validate`, {
      method: 'POST',
    })

    snackbar.show('Dokumen berhasil divalidasi', 'success')
    emit('status-updated', 'validated')
  } catch (error) {
    snackbar.show('Gagal memvalidasi dokumen', 'error')
  } finally {
    isValidating.value = false
  }
}

const openPinDialog = () => {
  pin.value = ''
  pinErrorMsg.value = ''
  isPinDialogVisible.value = true
}

const verifyPinAndApprove = async () => {
  if (!pin.value) {
    pinErrorMsg.value = 'PIN wajib diisi'
    
    return
  }
  
  isVerifyingPin.value = true
  pinErrorMsg.value = ''
  
  try {
    await $api('/apps/verify-pin', {
      method: 'POST',
      body: {
        pin: pin.value,
      },
    })
    
    // PIN verified, proceed to approve
    isPinDialogVisible.value = false
    await executeApproveDocument()
  } catch (error) {
    pinErrorMsg.value = error.data?.message || error.message || 'PIN salah atau otorisasi gagal'
  } finally {
    isVerifyingPin.value = false
  }
}

const executeApproveDocument = async () => {
  isApproving.value = true
  try {
    const response = await $api(`/apps/documents/${props.documentType}/${props.documentId}/approve`, {
      method: 'POST',
    })

    snackbar.show('Dokumen berhasil disetujui', 'success')
    emit('status-updated', 'approved')
  } catch (error) {
    snackbar.show('Gagal menyetujui dokumen', 'error')
  } finally {
    isApproving.value = false
  }
}

const openRejectDialog = () => {
  rejectionReason.value = ''
  isRejectDialogVisible.value = true
}

const rejectDocument = async () => {
  if (!rejectionReason.value) {
    snackbar.show('Alasan penolakan wajib diisi', 'error')
    
    return
  }
  
  isRejecting.value = true
  try {
    const response = await $api(`/apps/documents/${props.documentType}/${props.documentId}/reject`, {
      method: 'POST',
      body: { reason: rejectionReason.value },
    })

    snackbar.show('Dokumen berhasil ditolak', 'success')
    emit('status-updated', 'rejected')
    isRejectDialogVisible.value = false
  } catch (error) {
    snackbar.show('Gagal menolak dokumen', 'error')
  } finally {
    isRejecting.value = false
  }
}
</script>

<template>
  <div class="d-inline-flex flex-wrap gap-2">
    <!-- Submit Button (For Drafts) -->
    <VBtn
      v-if="canSubmit && approvalStatus === 'draft'"
      color="primary"
      prepend-icon="ri-send-plane-line"
      :loading="isSubmitting"
      :disabled="isActionDisabled"
      @click="submitDocument"
    >
      Ajukan Validasi
    </VBtn>

    <!-- Validation Button -->
    <VBtn
      v-if="canValidate && approvalStatus === 'pending'"
      color="info"
      variant="tonal"
      prepend-icon="ri-check-double-line"
      :loading="isValidating"
      :disabled="isActionDisabled"
      @click="validateDocument"
    >
      Validasi
    </VBtn>

    <!-- Approve Button -->
    <VBtn
      v-if="canApprove && (approvalStatus === 'pending' || approvalStatus === 'validated')"
      color="success"
      prepend-icon="ri-checkbox-circle-line"
      :loading="isApproving"
      :disabled="isActionDisabled"
      @click="openPinDialog"
    >
      Setujui
    </VBtn>

    <!-- Reject Button -->
    <VBtn
      v-if="(canValidate || canApprove) && (approvalStatus === 'pending' || approvalStatus === 'validated')"
      color="error"
      variant="tonal"
      prepend-icon="ri-close-circle-line"
      :disabled="isActionDisabled"
      @click="openRejectDialog"
    >
      Tolak
    </VBtn>

    <!-- Print PDF Button -->
    <VBtn
      v-if="approvalStatus === 'approved'"
      color="primary"
      variant="outlined"
      prepend-icon="ri-printer-line"
      :loading="isDownloading"
      @click="downloadPdf"
    >
      Cetak PDF
    </VBtn>

    <!-- Reject Dialog -->
    <VDialog
      v-model="isRejectDialogVisible"
      max-width="500"
    >
      <VCard>
        <VCardTitle class="pt-6 px-6">
          Alasan Penolakan
        </VCardTitle>
        
        <VCardText class="px-6 pb-6">
          <VTextarea
            v-model="rejectionReason"
            label="Masukkan Alasan"
            placeholder="Tuliskan mengapa dokumen ini ditolak..."
            auto-grow
            rows="3"
          />
        </VCardText>

        <VCardActions class="px-6 pb-6 d-flex justify-end gap-3">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isRejectDialogVisible = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="error"
            variant="elevated"
            :loading="isRejecting"
            @click="rejectDocument"
          >
            Tolak Dokumen
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- PIN Dialog for Approval -->
    <VDialog
      v-model="isPinDialogVisible"
      max-width="400"
      persistent
    >
      <VCard>
        <VCardItem class="bg-primary text-white pa-4">
          <VCardTitle class="d-flex align-center text-white">
            <VIcon
              icon="ri-lock-password-line"
              size="24"
              class="me-2"
            />
            Otorisasi Persetujuan
          </VCardTitle>
        </VCardItem>

        <VCardText class="pt-6">
          <p class="mb-6 text-body-1">
            Masukkan PIN rahasia Anda untuk memberikan persetujuan pada dokumen ini.
          </p>

          <VTextField
            v-model="pin"
            type="password"
            label="PIN Rahasia"
            placeholder="••••••"
            :error-messages="pinErrorMsg"
            autocomplete="off"
            @keyup.enter="verifyPinAndApprove"
          />
        </VCardText>

        <VCardActions class="pa-4 pt-0 justify-end">
          <VBtn
            color="secondary"
            variant="outlined"
            :disabled="isVerifyingPin"
            @click="isPinDialogVisible = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="primary"
            variant="elevated"
            :loading="isVerifyingPin"
            @click="verifyPinAndApprove"
          >
            Setujui Dokumen
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- PDF Viewer Dialog -->
    <VDialog
      v-model="isPdfDialogVisible"
      max-width="900"
      @update:model-value="(val) => { if(!val) closePdfDialog() }"
    >
      <VCard>
        <VCardTitle class="d-flex justify-space-between align-center px-6 pt-6 pb-4">
          <span>Pratinjau PDF</span>
          <VBtn
            icon
            variant="text"
            size="small"
            @click="closePdfDialog"
          >
            <VIcon icon="ri-close-line" />
          </VBtn>
        </VCardTitle>
        
        <VCardText class="px-6 pb-6 pt-0">
          <iframe
            v-if="pdfBlobUrl"
            :src="pdfBlobUrl"
            width="100%"
            height="600px"
            style="border: none; border-radius: 8px;"
          />
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>
