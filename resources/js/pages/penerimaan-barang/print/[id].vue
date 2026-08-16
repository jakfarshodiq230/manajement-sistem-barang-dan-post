<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const grId = route.params.id

const goodsReceipt = ref(null)
const isLoading = ref(true)
const errorMsg = ref('')

const fetchReceipt = async () => {
  try {
    const data = await $api(`/apps/goods-receipts/${grId}`)

    goodsReceipt.value = data
    
    // Auto-trigger print when data is loaded
    setTimeout(() => {
      window.print()
    }, 500)
  } catch (error) {
    console.error(error)
    errorMsg.value = 'Gagal memuat data Bukti Penerimaan.'
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchReceipt()
})

const formatNumber = val => {
  if (!val) return '0'
  
  return new Intl.NumberFormat('id-ID').format(val)
}
</script>

<template>
  <div class="print-container pa-8">
    <div
      v-if="isLoading"
      class="text-center py-12"
    >
      Sedang memuat data cetak...
    </div>
    
    <div
      v-else-if="errorMsg"
      class="text-center py-12 text-error"
    >
      {{ errorMsg }}
    </div>
    
    <div
      v-else
      class="receipt-content"
    >
      <!-- Kop Surat / Header Bukti -->
      <div class="d-flex justify-space-between align-start mb-6 border-b pb-4">
        <div>
          <h2 class="text-h4 font-weight-bold mb-1">
            BUKTI PENERIMAAN BARANG
          </h2>
          <div class="text-subtitle-1 text-grey-800">
            No. Penerimaan: {{ goodsReceipt.receipt_number }}
          </div>
          <div class="text-subtitle-2 text-grey-600">
            No. Referensi PO: {{ goodsReceipt.purchase_order?.po_number }}
          </div>
        </div>
        <div class="text-right">
          <div class="text-h5 font-weight-bold text-primary mb-1">
            GUDANG PUSAT
          </div>
          <div class="text-body-2">
            Tgl. Cetak: {{ new Date().toLocaleDateString('id-ID') }}
          </div>
        </div>
      </div>
      
      <!-- Info Dokumen -->
      <VRow class="mb-6">
        <VCol cols="6">
          <div class="text-caption font-weight-bold text-grey-600">
            DITERIMA TANGGAL
          </div>
          <div class="text-body-1">
            {{ goodsReceipt.date ? new Date(goodsReceipt.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-' }}
          </div>
        </VCol>
        <VCol cols="6">
          <div class="text-caption font-weight-bold text-grey-600">
            CABANG PEMESAN (PO)
          </div>
          <div class="text-body-1">
            {{ goodsReceipt.purchase_order?.branch?.name || '-' }}
          </div>
        </VCol>
        <VCol cols="6">
          <div class="text-caption font-weight-bold text-grey-600">
            SUPPLIER
          </div>
          <div class="text-body-1">
            {{ goodsReceipt.purchase_order?.supplier?.name || '-' }}
          </div>
        </VCol>
        <VCol cols="6">
          <div class="text-caption font-weight-bold text-grey-600">
            DITERIMA OLEH
          </div>
          <div class="text-body-1">
            {{ goodsReceipt.user?.name || '-' }}
          </div>
        </VCol>
      </VRow>
      
      <!-- Tabel Barang -->
      <table class="w-100 receipt-table mb-6">
        <thead>
          <tr>
            <th
              class="text-left"
              style="width: 50px;"
            >
              NO
            </th>
            <th class="text-left">
              NAMA BARANG
            </th>
            <th
              class="text-right"
              style="width: 150px;"
            >
              QTY DIPESAN
            </th>
            <th
              class="text-right"
              style="width: 150px;"
            >
              QTY DITERIMA
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(item, index) in goodsReceipt.items"
            :key="item.id"
          >
            <td class="text-center">
              {{ index + 1 }}
            </td>
            <td>{{ item.product_branch?.product?.name || 'Item' }}</td>
            <td class="text-right">
              {{ formatNumber(goodsReceipt.purchase_order?.items?.find(i => i.product_id === item.product_branch?.product_id)?.qty || 0) }}
            </td>
            <td class="text-right font-weight-bold">
              {{ formatNumber(item.qty_received) }}
            </td>
          </tr>
        </tbody>
      </table>
      
      <!-- Catatan -->
      <div
        v-if="goodsReceipt.notes"
        class="mb-12"
      >
        <div class="text-caption font-weight-bold text-grey-600">
          CATATAN PENERIMAAN:
        </div>
        <div class="text-body-2 pa-3 bg-grey-100 rounded">
          {{ goodsReceipt.notes }}
        </div>
      </div>
      
      <!-- Tanda Tangan -->
      <VRow class="text-center mt-12 pt-8">
        <VCol cols="4">
          <div class="mb-16">
            Pengirim / Ekspedisi
          </div>
          <div class="font-weight-bold">
            ( .................................... )
          </div>
        </VCol>
        <VCol cols="4" />
        <VCol cols="4">
          <div class="mb-16">
            Penerima Gudang
          </div>
          <div class="font-weight-bold text-decoration-underline">
            {{ goodsReceipt.user?.name || '( .................................... )' }}
          </div>
          <div class="text-caption">
            NIP: {{ goodsReceipt.user?.employee?.nik || '-' }}
          </div>
        </VCol>
      </VRow>
    </div>
  </div>
</template>

<route lang="yaml">
meta:
  layout: blank
  action: read
  subject: Penerimaan Gudang
</route>

<style scoped>
/* Styling khusus untuk cetak */
.print-container {
  background-color: white;
  min-height: 100vh;
  color: black;
  font-family: 'Inter', sans-serif;
}

.receipt-table {
  border-collapse: collapse;
}

.receipt-table th,
.receipt-table td {
  border: 1px solid #e0e0e0;
  padding: 12px;
}

.receipt-table th {
  background-color: #f5f5f5;
  font-weight: bold;
}

/* Menyembunyikan elemen bawaan browser saat diprint */
@media print {
  @page { margin: 15mm; }
  body {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
}
</style>
