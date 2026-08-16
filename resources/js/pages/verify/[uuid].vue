<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const uuid = route.params.uuid

const isLoading = ref(true)
const isValid = ref(false)
const errorMessage = ref('')
const docData = ref(null)

onMounted(async () => {
  try {
    const response = await fetch(`/api/verify-document/${uuid}`)
    const data = await response.json()
    
    if (response.ok && data.valid) {
      isValid.value = true
      docData.value = data
    } else {
      isValid.value = false
      errorMessage.value = data.message || 'Dokumen tidak ditemukan atau tidak valid.'
    }
  } catch (err) {
    isValid.value = false
    errorMessage.value = 'Terjadi kesalahan saat memverifikasi dokumen.'
  } finally {
    isLoading.value = false
  }
})

const getDocTypeName = type => {
  const types = {
    'purchase_order': 'Purchase Order',
    'goods_receipt': 'Penerimaan Gudang',
    'return_transaction': 'Retur / Ganti Barang',
    'sale': 'Struk Pembelian',
  }
  
  return types[type] || 'Dokumen'
}

const formatDate = dateString => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  }).format(date)
}
</script>

<template>
  <div class="verify-page-container d-flex align-center justify-center min-h-screen bg-grey-100 pa-4">
    <VCard
      class="pa-6 text-center elevation-3"
      max-width="500"
      width="100%"
    >
      <!-- Loading State -->
      <div
        v-if="isLoading"
        class="py-8"
      >
        <VProgressCircular
          indeterminate
          color="primary"
          size="64"
        />
        <h3 class="text-h6 mt-4">
          Memverifikasi Dokumen...
        </h3>
      </div>
      
      <!-- Valid Document State -->
      <div v-else-if="isValid">
        <VIcon
          icon="ri-checkbox-circle-fill"
          color="success"
          size="80"
          class="mb-4"
        />
        <h2 class="text-h5 font-weight-bold text-success mb-2">
          DOKUMEN VALID
        </h2>
        <p class="text-body-1 mb-6">
          Sistem mencatat dokumen ini asli dan sah.
        </p>
        
        <VDivider class="mb-6" />
        
        <VRow class="text-left mb-2">
          <VCol
            cols="5"
            class="font-weight-medium text-grey-600"
          >
            Jenis Dokumen
          </VCol>
          <VCol
            cols="7"
            class="font-weight-bold"
          >
            {{ getDocTypeName(docData.type) }}
          </VCol>
        </VRow>
        
        <VRow class="text-left mb-2">
          <VCol
            cols="5"
            class="font-weight-medium text-grey-600"
          >
            Nomor Ref
          </VCol>
          <VCol
            cols="7"
            class="font-weight-bold"
          >
            {{ docData.reference_number }}
          </VCol>
        </VRow>

        <VRow class="text-left mb-2">
          <VCol
            cols="5"
            class="font-weight-medium text-grey-600"
          >
            Dibuat Pada
          </VCol>
          <VCol cols="7">
            {{ formatDate(docData.created_at) }}
          </VCol>
        </VRow>
        
        <VDivider class="my-4" />
        
        <VRow class="text-left mb-2">
          <VCol
            cols="5"
            class="font-weight-medium text-grey-600"
          >
            Diperiksa Oleh
          </VCol>
          <VCol cols="7">
            <div class="font-weight-bold text-primary">
              {{ docData.validated_by || 'Belum' }}
            </div>
            <div
              v-if="docData.validated_at"
              class="text-caption text-grey-500"
            >
              {{ formatDate(docData.validated_at) }}
            </div>
          </VCol>
        </VRow>
        
        <VRow class="text-left mb-2">
          <VCol
            cols="5"
            class="font-weight-medium text-grey-600"
          >
            Disetujui Oleh
          </VCol>
          <VCol cols="7">
            <div class="font-weight-bold text-primary">
              {{ docData.approved_by || 'Belum' }}
            </div>
            <div
              v-if="docData.approved_at"
              class="text-caption text-grey-500"
            >
              {{ formatDate(docData.approved_at) }}
            </div>
          </VCol>
        </VRow>

        <VRow class="text-left mb-2 mt-4">
          <VCol
            cols="5"
            class="font-weight-medium text-grey-600"
          >
            Status Akhir
          </VCol>
          <VCol cols="7">
            <VChip
              :color="docData.status === 'approved' ? 'success' : (docData.status === 'validated' ? 'info' : 'warning')"
              size="small"
            >
              {{ docData.status.toUpperCase() }}
            </VChip>
          </VCol>
        </VRow>
      </div>
      
      <!-- Invalid Document State -->
      <div v-else>
        <VIcon
          icon="ri-close-circle-fill"
          color="error"
          size="80"
          class="mb-4"
        />
        <h2 class="text-h5 font-weight-bold text-error mb-2">
          TIDAK VALID
        </h2>
        <p class="text-body-1">
          {{ errorMessage }}
        </p>
        <p class="text-body-2 text-grey-600 mt-4">
          Peringatan: Dokumen ini tidak tercatat di dalam sistem kami atau mungkin telah dimanipulasi.
        </p>
      </div>
    </VCard>
  </div>
</template>

<style scoped>
.verify-page-container {
  min-height: 100vh;
  width: 100%;
}
</style>

<route lang="yaml">
meta:
  layout: blank
  action: read
  subject: Public
</route>
