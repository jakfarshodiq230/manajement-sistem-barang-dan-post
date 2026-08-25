<script setup>
import { computed } from 'vue'
import dayjs from 'dayjs'

const props = defineProps({
  receivable: {
    type: Object,
    required: false,
    default: null,
  },
  lastPayment: {
    type: Object,
    required: false,
    default: null,
  },
  branch: {
    type: Object,
    required: false,
    default: null,
  },
})

const formatCurrency = val => {
  return new Intl.NumberFormat('id-ID').format(val || 0)
}

const formatDate = date => {
  if (!date) return ''
  
  return dayjs(date).format('DD/MM/YYYY HH:mm')
}

const remainingBalance = computed(() => {
  if (!props.receivable) return 0
  
  return Number(props.receivable.amount_due) - Number(props.receivable.amount_paid)
})
</script>

<template>
  <div
    v-if="receivable && lastPayment"
    id="print-receivable-receipt"
    class="receipt-wrapper"
  >
    <div class="receipt-content">
      <!-- Header -->
      <div class="text-center mb-3">
        <h2
          v-if="branch?.owner"
          class="font-weight-bold mb-1"
          style="font-size: 18px;"
        >
          {{ branch.owner.name }}
        </h2>
        <h3
          class="font-weight-bold mb-1"
          :style="branch?.owner ? 'font-size: 14px;' : 'font-size: 18px;'"
        >
          {{ branch?.name || 'Toko' }}
        </h3>
        <div
          class="text-caption"
          style="line-height: 1.2;"
        >
          {{ branch?.address || 'Alamat Cabang' }}
        </div>
        <div
          v-if="branch?.phone"
          class="text-caption"
        >
          Telp: {{ branch?.phone }}
        </div>
      </div>
      
      <div class="divider-dashed mb-2" />
      
      <div
        class="text-center mb-2 font-weight-bold"
        style="font-size: 14px;"
      >
        BUKTI PEMBAYARAN CICILAN
      </div>

      <div class="divider-dashed mb-2" />
      
      <!-- Info -->
      <div
        class="mb-2 text-caption"
        style="line-height: 1.2; font-size: 12px;"
      >
        <div class="d-flex mb-1">
          <div style="width: 55px;">
            No. Nota
          </div>
          <div class="px-1">
            :
          </div>
          <div
            class="flex-grow-1"
            style="font-size: 10px; line-height: 1.5; white-space: nowrap;"
          >
            {{ receivable.sale?.invoice_number }}
          </div>
        </div>
        <div class="d-flex mb-1">
          <div style="width: 55px;">
            Tgl Bayar
          </div>
          <div class="px-1">
            :
          </div>
          <div class="flex-grow-1">
            {{ formatDate(lastPayment.payment_date || lastPayment.created_at) }}
          </div>
        </div>
        <div class="d-flex mb-1">
          <div style="width: 55px;">
            Kasir
          </div>
          <div class="px-1">
            :
          </div>
          <div class="flex-grow-1">
            {{ lastPayment.user?.name || '-' }}
          </div>
        </div>
        <div class="d-flex mb-1">
          <div style="width: 55px;">
            Pelanggan
          </div>
          <div class="px-1">
            :
          </div>
          <div class="flex-grow-1 font-weight-bold">
            {{ receivable.customer?.name || '-' }}
          </div>
        </div>
      </div>
      
      <div class="divider-dashed mb-2" />
      
      <!-- Items -->
      <div class="mb-2">
        <div
          class="font-weight-bold text-center mb-1"
          style="font-size: 12px;"
        >
          Barang yang Dibeli (Utang)
        </div>
        <div
          v-for="item in receivable.sale?.items"
          :key="item.id"
          class="mb-1 text-caption"
          style="line-height: 1.2; font-size: 12px;"
        >
          <div class="font-weight-bold">
            {{ item.product_branch?.product?.name || 'Produk' }}
          </div>
          <div class="d-flex justify-space-between">
            <span>{{ item.qty }} x {{ formatCurrency(item.price) }}</span>
            <span>{{ formatCurrency(item.subtotal) }}</span>
          </div>
        </div>
      </div>
      
      <div class="divider-dashed mb-2" />
      
      <!-- Totals & Installment details -->
      <table
        class="w-100 text-caption font-weight-bold mb-2"
        style="line-height: 1.2; font-size: 12px;"
      >
        <tbody>
          <tr>
            <td>Total Utang Awal</td>
            <td class="text-right">
              {{ formatCurrency(receivable.amount_due) }}
            </td>
          </tr>
          <tr>
            <td>Total Dibayar</td>
            <td class="text-right">
              {{ formatCurrency(receivable.amount_paid) }}
            </td>
          </tr>
          <tr style="font-size: 14px; margin-top: 5px;">
            <td class="pt-2 text-error">
              Sisa Utang
            </td>
            <td class="text-right pt-2 text-error">
              {{ formatCurrency(remainingBalance) }}
            </td>
          </tr>
        </tbody>
      </table>

      <div class="divider-dashed mb-2" />

      <!-- Installment History -->
      <div
        v-if="receivable.payments?.length > 0"
        class="mb-2"
      >
        <div
          class="font-weight-bold text-center mb-1"
          style="font-size: 12px;"
        >
          Riwayat Cicilan
        </div>
        <table
          class="w-100 text-caption"
          style="line-height: 1.2; font-size: 10px;"
        >
          <tbody>
            <tr
              v-for="(payment, index) in receivable.payments"
              :key="payment.id"
            >
              <td style="width: 20px;">
                {{ index + 1 }}.
              </td>
              <td>{{ formatDate(payment.payment_date).split(' ')[0] }}</td>
              <td class="text-right">
                {{ formatCurrency(payment.amount) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <div class="divider-dashed mb-2" />

      <!-- Payment this time -->
      <div
        class="mb-1 text-center font-weight-bold"
        style="font-size: 12px;"
      >
        PEMBAYARAN KALI INI
      </div>
      <table
        class="w-100 text-caption mb-3"
        style="line-height: 1.2; font-size: 12px;"
      >
        <tbody>
          <tr>
            <td>Metode</td>
            <td class="text-right text-uppercase">
              {{ lastPayment.payment_method }}
            </td>
          </tr>
          <tr
            class="font-weight-bold"
            style="font-size: 14px;"
          >
            <td>Nominal Bayar</td>
            <td class="text-right">
              {{ formatCurrency(lastPayment.amount) }}
            </td>
          </tr>
        </tbody>
      </table>
      
      <div
        class="text-center mt-4"
        style="font-size: 11px;"
      >
        <p class="mb-1 font-weight-bold">
          Terima Kasih
        </p>
        <p style="font-size: 10px; line-height: 1.1;">
          Struk ini adalah bukti pembayaran cicilan yang sah
        </p>
      </div>
    </div>
  </div>
</template>

<style>
/* Hide the receipt on screen by default */
#print-receivable-receipt {
  display: none;
}

@media print {
  /* Hide EVERYTHING else when printing */
  body * {
    visibility: hidden;
  }
  
  /* Show only the receipt and its children */
  #print-receivable-receipt, #print-receivable-receipt * {
    visibility: visible;
  }
  
  #print-receivable-receipt {
    display: block;
    position: absolute;
    left: 0;
    top: 0;
    width: 58mm; /* Thermal printer standard width */
    margin: 0;
    padding: 0;
    color: #000;
    background: #fff;
    font-family: 'Courier New', Courier, monospace, sans-serif;
  }

  .divider-dashed {
    border-top: 1px dashed #000;
  }

  /* Force background to be transparent and remove margins */
  @page {
    margin: 0;
  }
  
  html, body {
    margin: 0 !important;
    padding: 0 !important;
    background-color: white !important;
  }
}

.receipt-content {
  width: 100%;
  padding: 2mm 5mm 5mm 2mm; /* Small padding so it doesn't clip on edges */
}
</style>
