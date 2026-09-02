<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import GoodsReceiptPrinter from '../GoodsReceiptPrinter.vue'

const route = useRoute()
const grId = route.params.id

const goodsReceipt = ref(null)
const receiptSettings = ref([])
const activeReceiptSetting = computed(() => {
  if (receiptSettings.value.length === 0) return null
  return receiptSettings.value.find(s => s.is_default) || receiptSettings.value[0]
})
const isLoading = ref(true)
const errorMsg = ref('')
const printerRef = ref(null)

const fetchReceipt = async () => {
  try {
    const [data, settingsRes] = await Promise.all([
      $api(`/apps/goods-receipts/${grId}`),
      $api('/apps/receipt-settings'),
    ])

    goodsReceipt.value = data.data || data
    receiptSettings.value = settingsRes.data || settingsRes || []
    
    // Auto-trigger print when data is loaded
    setTimeout(() => {
      if (printerRef.value?.print) {
        printerRef.value.print()
      } else {
        window.print()
      }
    }, 400)
  } catch (error) {
    console.error(error)
    errorMsg.value = 'Gagal memuat data Bukti Penerimaan / Faktur.'
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchReceipt()
})
</script>

<template>
  <div class="pa-4">
    <div v-if="isLoading" class="text-center py-12">
      Sedang memuat data cetak faktur penerimaan...
    </div>
    <div v-else-if="errorMsg" class="text-center py-12 text-error">
      {{ errorMsg }}
    </div>
    <div v-else>
      <GoodsReceiptPrinter
        ref="printerRef"
        :goods-receipt="goodsReceipt"
        :branch="goodsReceipt?.purchase_order?.branch || goodsReceipt?.purchaseOrder?.branch"
        :setting="activeReceiptSetting"
        :print-format="activeReceiptSetting?.name?.toLowerCase().includes('thermal') ? 'thermal' : 'kwitansi'"
      />
    </div>
  </div>
</template>

<route lang="yaml">
meta:
  layout: blank
  action: read
  subject: Penerimaan Gudang
</route>
