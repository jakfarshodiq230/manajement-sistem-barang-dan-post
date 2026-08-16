<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import JsBarcode from 'jsbarcode'
import html2pdf from 'html2pdf.js'

const route = useRoute()
const batchId = route.query.batch_id
const qty = route.query.qty ? parseInt(route.query.qty) : 1

const batchData = ref(null)
const isLoading = ref(true)
const isDownloading = ref(false)

const formatDate = dateStr => {
  if (!dateStr) return '-'
  
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric',
  })
}

const formatRupiah = value => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value)
}

onMounted(async () => {
  if (batchId) {
    try {
      const res = await $api(`/apps/product-batches/detail/${batchId}`)
      batchData.value = res
      
      // Delay to render barcodes
      setTimeout(() => {
        const barcodeElements = document.querySelectorAll('.barcode-svg')
        barcodeElements.forEach(el => {
          JsBarcode(el, `BATCH-${batchData.value.id}`, {
            format: "CODE128",
            width: 1,
            height: 25,
            displayValue: false, 
            margin: 0
          })
        })
      }, 500)
    } catch (error) {
      console.error(error)
      alert('Gagal memuat data batch')
    }
  }
  isLoading.value = false
})

const downloadPdf = async () => {
  isDownloading.value = true
  const element = document.getElementById('print-area')
  if (!element) return

  const opt = {
    margin:       10,
    filename:     `Label-Batch-${batchData.value?.id || 'Unknown'}.pdf`,
    image:        { type: 'jpeg', quality: 0.98 },
    html2canvas:  { scale: 2 },
    jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
    pagebreak:    { mode: ['avoid-all', 'css', 'legacy'] }
  }

  await html2pdf().set(opt).from(element).save()
  isDownloading.value = false
}
</script>

<template>
  <div
    v-if="isLoading"
    class="d-flex justify-center align-center h-100vh"
  >
    <VProgressCircular indeterminate />
  </div>
  
  <div v-else-if="batchData" class="pa-4 bg-var-theme-background min-h-screen">
    <!-- Toolbar -->
    <div class="d-flex justify-space-between align-center mb-4 pb-4 border-b">
      <div>
        <h4 class="text-h4">Label Barcode</h4>
        <p class="text-body-2 text-disabled mb-0">Total {{ qty }} label akan diunduh</p>
      </div>
      
      <VBtn
        prepend-icon="ri-download-2-line"
        color="primary"
        @click="downloadPdf"
        :loading="isDownloading"
      >
        Download PDF
      </VBtn>
    </div>

    <!-- Print Area -->
    <div id="print-area" class="print-container">
      <div
        v-for="i in qty"
        :key="i"
        class="label-wrapper"
      >
        <div class="label-content">
          <div class="text-center font-weight-bold mb-1 title-text">
            {{ batchData.product_branch?.product?.name || '-' }}
          </div>
          
          <div class="barcode-container mt-1">
            <svg class="barcode-svg"></svg>
          </div>
          
          <div class="info-grid mt-1">
            <div class="info-row">
              <span class="label">Harga:</span>
              <span class="val font-weight-bold">{{ formatRupiah(batchData.product_branch?.price || 0) }}</span>
            </div>
            <div class="info-row">
              <span class="label">SKU:</span>
              <span class="val">{{ batchData.product_branch?.product?.sku || '-' }}</span>
            </div>
            <div class="info-row">
              <span class="label">Exp:</span>
              <span class="val">{{ formatDate(batchData.expiration_date) }}</span>
            </div>
            <div
              class="info-row text-center mt-1 font-weight-bold"
              style="font-size: 8px !important;"
            >
              BATCH-{{ batchData.id }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.print-container {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-start;
  align-content: flex-start;
  gap: 10px;
  background-color: transparent;
  width: 190mm; /* A4 width (210mm) - margins (20mm) */
  margin: 0 auto;
}

.label-wrapper {
  width: 40mm;
  height: 30mm;
  background: white;
  padding: 1.5mm;
  box-sizing: border-box;
  overflow: hidden;
  display: flex;
  justify-content: center;
  align-items: center;
  border: 1px dashed #ccc;
  break-inside: avoid;
  page-break-inside: avoid;
}

.label-content {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: center;
  gap: 2px;
}

.title-text {
  font-size: 9px;
  line-height: 1.1;
  text-align: center;
  width: 100%;
  padding-bottom: 2px;
}

.barcode-container {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
}

.barcode-svg {
  width: 100%;
  max-height: 30px;
}

.info-grid {
  width: 100%;
  display: flex;
  flex-direction: column;
  font-size: 8px;
  line-height: 1.1;
}

.info-row {
  display: flex;
  justify-content: space-between;
  width: 100%;
}

.label {
  color: #333;
}
.val {
  color: #000;
}
</style>

<route lang="yaml">
meta:
  layout: blank
  action: read
  subject: Inventori Cabang
</route>
