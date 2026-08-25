<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'

definePage({
  meta: {
    layout: 'blank',
    public: true,
  },
})

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
    'purchase_order': 'Purchase Order (PO)',
    'goods_receipt': 'Penerimaan Barang Gudang (GR)',
    'return_transaction': 'Retur / Ganti Barang',
    'sale': 'Struk Penjualan Kasir',
    'stock_transfer': 'Surat Jalan Mutasi Antar Cabang',
  }
  
  return types[type] || 'Dokumen Inventori'
}

const formatDate = dateString => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  }).format(date)
}

const getTransferStatusBadge = status => {
  switch (status) {
    case 'pending':
      return { text: '1. Request Diajukan', color: 'warning' }
    case 'ready_for_pickup':
    case 'approved':
      return { text: '2. Siap Dijemput', color: 'info' }
    case 'in_transit':
      return { text: '3. Dibawa Kurir (In-Transit)', color: 'purple' }
    case 'completed':
      return { text: '4. Selesai (Diterima Toko)', color: 'success' }
    case 'rejected':
      return { text: 'Ditolak', color: 'error' }
    case 'cancelled':
      return { text: 'Dibatalkan', color: 'secondary' }
    default:
      return { text: status, color: 'primary' }
  }
}
</script>

<template>
  <div class="verify-page-container d-flex align-center justify-center min-h-screen bg-grey-100 pa-4">
    <!-- Loading State -->
    <VCard
      v-if="isLoading"
      class="pa-8 text-center elevation-3"
      max-width="500"
      width="100%"
    >
      <VProgressCircular
        indeterminate
        color="primary"
        size="64"
      />
      <h3 class="text-h6 mt-4">
        Memverifikasi Dokumen & TTD Digital...
      </h3>
    </VCard>
    
    <!-- Valid Stock Transfer Verification Card -->
    <VCard
      v-else-if="isValid && docData && docData.type === 'stock_transfer'"
      class="pa-6 elevation-4 rounded-xl"
      max-width="760"
      width="100%"
    >
      <!-- Header -->
      <div class="text-center mb-6">
        <div class="d-inline-flex align-center justify-center pa-3 bg-success-lighten-5 rounded-circle mb-3">
          <VIcon
            icon="ri-shield-check-fill"
            color="success"
            size="54"
          />
        </div>
        <h2 class="text-h5 font-weight-bold text-success mb-1">
          DOKUMEN MUTASI VALID & TERVERIFIKASI
        </h2>
        <p class="text-caption text-medium-emphasis mb-2">
          Surat Jalan resmi tercatat dalam sistem terpusat PT. Dumai Manajemen Barang
        </p>
        <VChip
          :color="getTransferStatusBadge(docData.status).color"
          size="small"
          variant="elevated"
          class="font-weight-bold"
        >
          Status: {{ getTransferStatusBadge(docData.status).text }}
        </VChip>
      </div>

      <VDivider class="mb-4" />

      <!-- Route & Document Info -->
      <div class="bg-grey-50 pa-4 rounded-lg border mb-4">
        <div class="d-flex justify-space-between align-center mb-3">
          <div>
            <div class="text-caption text-medium-emphasis">No. Referensi Dokumen:</div>
            <div class="font-weight-bold text-h6 text-primary">{{ docData.reference_number }}</div>
          </div>
          <div class="text-right">
            <div class="text-caption text-medium-emphasis">Waktu Pengajuan:</div>
            <div class="font-weight-medium text-body-2">{{ formatDate(docData.created_at) }}</div>
          </div>
        </div>

        <VRow dense>
          <VCol cols="12" sm="6">
            <div class="pa-2 border rounded bg-white">
              <div class="text-caption text-error font-weight-bold">CABANG ASAL (PENGIRIM):</div>
              <div class="font-weight-bold text-body-2">{{ docData.source_branch }}</div>
            </div>
          </VCol>
          <VCol cols="12" sm="6">
            <div class="pa-2 border rounded bg-white">
              <div class="text-caption text-success font-weight-bold">CABANG TUJUAN (PENERIMA):</div>
              <div class="font-weight-bold text-body-2">{{ docData.destination_branch }}</div>
            </div>
          </VCol>
        </VRow>
      </div>

      <!-- 3-Party Digital Validation Cards -->
      <h6 class="text-subtitle-1 font-weight-bold mb-3 d-flex align-center gap-2">
        <VIcon icon="ri-fingerprint-line" size="20" color="primary" />
        Data Validasi & Tanda Tangan Digital 3 Pihak
      </h6>

      <VRow dense class="mb-4">
        <!-- 1. Cabang Asal -->
        <VCol cols="12" md="4">
          <VCard variant="outlined" class="pa-3 h-100 border-primary bg-primary-lighten-5">
            <div class="d-flex align-center gap-2 mb-2">
              <VIcon icon="ri-box-3-line" color="primary" size="20" />
              <span class="font-weight-bold text-caption text-primary">1. Pengirim (Cabang Asal)</span>
            </div>
            <div class="text-caption">
              <div>Petugas: <strong>{{ docData.prepared_by || docData.created_by || '-' }}</strong></div>
              <div>Waktu: {{ formatDate(docData.prepared_at || docData.created_at) }}</div>
              <VChip size="x-small" color="primary" class="mt-2">
                Stok Asal Terpotong & Disiapkan
              </VChip>
            </div>
          </VCard>
        </VCol>

        <!-- 2. Kurir Penjemput -->
        <VCol cols="12" md="4">
          <VCard
            variant="outlined"
            class="pa-3 h-100"
            :class="docData.picked_up_by_name ? 'border-purple bg-purple-lighten-5' : 'border-dashed bg-grey-50 opacity-60'"
          >
            <div class="d-flex align-center gap-2 mb-2">
              <VIcon icon="ri-truck-line" :color="docData.picked_up_by_name ? 'purple' : 'grey'" size="20" />
              <span class="font-weight-bold text-caption" :class="docData.picked_up_by_name ? 'text-purple' : 'text-grey'">
                2. Kurir / Penjemput
              </span>
            </div>
            <div v-if="docData.picked_up_by_name" class="text-caption">
              <div>Nama: <strong>{{ docData.picked_up_by_name }}</strong></div>
              <div>Waktu: {{ formatDate(docData.picked_up_at) }}</div>
              <div v-if="docData.pickup_notes" class="mt-1 font-italic">
                "{{ docData.pickup_notes }}"
              </div>
              <VChip size="x-small" color="purple" class="mt-2">
                Divalidasi & Dibawa Kurir
              </VChip>
            </div>
            <div v-else class="text-caption text-medium-emphasis">
              Belum divalidasi kurir penjemput
            </div>
          </VCard>
        </VCol>

        <!-- 3. Toko Penerima -->
        <VCol cols="12" md="4">
          <VCard
            variant="outlined"
            class="pa-3 h-100"
            :class="docData.received_by ? 'border-success bg-success-lighten-5' : 'border-dashed bg-grey-50 opacity-60'"
          >
            <div class="d-flex align-center gap-2 mb-2">
              <VIcon icon="ri-checkbox-circle-line" :color="docData.received_by ? 'success' : 'grey'" size="20" />
              <span class="font-weight-bold text-caption" :class="docData.received_by ? 'text-success' : 'text-grey'">
                3. Penerima (Toko Tujuan)
              </span>
            </div>
            <div v-if="docData.received_by" class="text-caption">
              <div>Penerima: <strong>{{ docData.received_by }}</strong></div>
              <div>Waktu: {{ formatDate(docData.received_at) }}</div>
              <div v-if="docData.receive_notes" class="mt-1 font-italic">
                "{{ docData.receive_notes }}"
              </div>
              <VChip size="x-small" color="success" class="mt-2">
                Diterima & Masuk Stok
              </VChip>
            </div>
            <div v-else class="text-caption text-medium-emphasis">
              Menunggu barang tiba di toko tujuan
            </div>
          </VCard>
        </VCol>
      </VRow>

      <!-- Items Breakdown -->
      <div v-if="docData.items && docData.items.length">
        <h6 class="text-subtitle-2 font-weight-bold mb-2">
          Rincian Barang Mutasi:
        </h6>
        <div class="border rounded overflow-hidden mb-4">
          <VTable density="compact">
            <thead>
              <tr class="bg-grey-100">
                <th class="font-weight-bold">Barang & SKU</th>
                <th class="text-center font-weight-bold" style="width: 80px;">Minta</th>
                <th class="text-center font-weight-bold" style="width: 80px;">Disiapkan</th>
                <th class="text-center font-weight-bold" style="width: 80px;">Dijemput</th>
                <th class="text-center font-weight-bold" style="width: 80px;">Diterima</th>
                <th class="font-weight-bold" style="width: 90px;">Kondisi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(it, idx) in docData.items" :key="idx">
                <td>
                  <div class="font-weight-bold text-caption">{{ it.name }}</div>
                  <div class="text-caption text-medium-emphasis font-mono"><code>{{ it.sku }}</code></div>
                </td>
                <td class="text-center font-weight-bold">{{ it.qty_requested }}</td>
                <td class="text-center font-weight-bold text-primary">{{ it.qty_prepared }}</td>
                <td class="text-center font-weight-bold text-purple">{{ it.qty_picked }}</td>
                <td class="text-center font-weight-bold text-success">{{ it.qty_received ?? '-' }}</td>
                <td>
                  <VChip v-if="it.receive_condition === 'good'" size="x-small" color="success" variant="tonal">Baik</VChip>
                  <VChip v-else-if="it.receive_condition === 'damaged'" size="x-small" color="error" variant="tonal">Rusak</VChip>
                  <VChip v-else-if="it.receive_condition === 'missing'" size="x-small" color="warning" variant="tonal">Hilang</VChip>
                  <span v-else class="text-caption text-disabled">-</span>
                </td>
              </tr>
            </tbody>
          </VTable>
        </div>
      </div>
    </VCard>
    
    <!-- Standard Document Verification Card (PO, GR, Sale) -->
    <VCard
      v-else-if="isValid && docData"
      class="pa-6 text-center elevation-3 rounded-xl"
      max-width="520"
      width="100%"
    >
      <VIcon
        icon="ri-checkbox-circle-fill"
        color="success"
        size="72"
        class="mb-3"
      />
      <h2 class="text-h5 font-weight-bold text-success mb-1">
        DOKUMEN VALID & SAH
      </h2>
      <p class="text-body-2 text-medium-emphasis mb-4">
        Dokumen tercatat resmi dan terverifikasi dalam sistem.
      </p>
      
      <VDivider class="mb-4" />
      
      <VRow class="text-left mb-2">
        <VCol cols="5" class="font-weight-medium text-grey-600">Jenis Dokumen</VCol>
        <VCol cols="7" class="font-weight-bold">{{ getDocTypeName(docData?.type) }}</VCol>
      </VRow>
      
      <VRow class="text-left mb-2">
        <VCol cols="5" class="font-weight-medium text-grey-600">Nomor Ref</VCol>
        <VCol cols="7" class="font-weight-bold text-primary">{{ docData.reference_number }}</VCol>
      </VRow>

      <VRow class="text-left mb-2">
        <VCol cols="5" class="font-weight-medium text-grey-600">Dibuat Pada</VCol>
        <VCol cols="7">{{ formatDate(docData.created_at) }}</VCol>
      </VRow>
      
      <VDivider class="my-3" />
      
      <VRow class="text-left mb-2">
        <VCol cols="5" class="font-weight-medium text-grey-600">Diperiksa Oleh</VCol>
        <VCol cols="7">
          <div class="font-weight-bold text-primary">{{ docData.validated_by || 'Belum' }}</div>
          <div v-if="docData.validated_at" class="text-caption text-grey-500">{{ formatDate(docData.validated_at) }}</div>
        </VCol>
      </VRow>
      
      <VRow class="text-left mb-2">
        <VCol cols="5" class="font-weight-medium text-grey-600">Disetujui Oleh</VCol>
        <VCol cols="7">
          <div class="font-weight-bold text-primary">{{ docData.approved_by || 'Belum' }}</div>
          <div v-if="docData.approved_at" class="text-caption text-grey-500">{{ formatDate(docData.approved_at) }}</div>
        </VCol>
      </VRow>

      <VRow class="text-left mb-2 mt-3">
        <VCol cols="5" class="font-weight-medium text-grey-600">Status Akhir</VCol>
        <VCol cols="7">
          <VChip
            :color="docData.status === 'approved' || docData.status === 'completed' ? 'success' : 'warning'"
            size="small"
          >
            {{ (docData.status || 'PENDING').toUpperCase() }}
          </VChip>
        </VCol>
      </VRow>
    </VCard>
    
    <!-- Invalid Document State -->
    <VCard
      v-else
      class="pa-6 text-center elevation-3 rounded-xl"
      max-width="500"
      width="100%"
    >
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
  public: true
</route>
