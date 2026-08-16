<script setup>
import { computed } from 'vue'
import dayjs from 'dayjs'

const props = defineProps({
  sale: {
    type: Object,
    required: false,
    default: null,
  },
  branch: {
    type: Object,
    required: false,
    default: null,
  },
  cashierName: {
    type: String,
    required: false,
    default: '',
  },
  setting: {
    type: Object,
    required: false,
    default: null,
  }
})

const formatCurrency = val => {
  return new Intl.NumberFormat('id-ID').format(val || 0)
}

const receiptStyle = computed(() => {
  if (!props.setting) {
    return {
      width: '58mm',
      padding: '0mm 0mm 0mm 0mm'
    }
  }

  const s = props.setting
  return {
    width: s.width || '58mm',
    padding: `${s.margin_top || 0}mm ${s.margin_right || 0}mm ${s.margin_bottom || 0}mm ${s.margin_left || 0}mm`
  }
})

const formatDate = date => {
  if (!date) return ''
  return dayjs(date).format('DD/MM/YYYY HH:mm')
}

const formatDateOnly = date => {
  if (!date) return ''
  return dayjs(date).format('DD MMMM YYYY')
}

// Terbilang Rupiah
const terbilang = (angka) => {
  const bilangan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas']
  let res = ''
  angka = parseInt(angka)
  if (angka === 0) return 'nol'
  if (angka < 12) res = ' ' + bilangan[angka]
  else if (angka < 20) res = terbilang(angka - 10) + ' belas'
  else if (angka < 100) res = terbilang(Math.floor(angka / 10)) + ' puluh' + terbilang(angka % 10)
  else if (angka < 200) res = ' seratus' + terbilang(angka - 100)
  else if (angka < 1000) res = terbilang(Math.floor(angka / 100)) + ' ratus' + terbilang(angka % 100)
  else if (angka < 2000) res = ' seribu' + terbilang(angka - 1000)
  else if (angka < 1000000) res = terbilang(Math.floor(angka / 1000)) + ' ribu' + terbilang(angka % 1000)
  else if (angka < 1000000000) res = terbilang(Math.floor(angka / 1000000)) + ' juta' + terbilang(angka % 1000000)
  else if (angka < 1000000000000) res = terbilang(Math.floor(angka / 1000000000)) + ' miliar' + terbilang(angka % 1000000000)
  else if (angka < 1000000000000000) res = terbilang(Math.floor(angka / 1000000000000)) + ' triliun' + terbilang(angka % 1000000000000)
  return res.trim()
}

const terbilangStr = computed(() => {
  if (!props.sale) return ''
  let text = terbilang(props.sale.grand_total)
  return text.charAt(0).toUpperCase() + text.slice(1) + ' rupiah'
})

const isKwitansiMode = computed(() => {
  const widthInt = parseInt((props.setting?.width || '58mm').replace(/\D/g,''))
  return widthInt > 100 // Jika lebar lebih dari 100mm, gunakan format Kuitansi
})
</script>

<template>
  <div
    v-if="sale"
    id="print-receipt-section"
    :style="receiptStyle"
  >
    <!-- MODE KUITANSI FORMAL -->
    <div v-if="isKwitansiMode" class="kwitansi-wrapper">
      <div class="kwitansi-header">
        <div class="kwitansi-company">
          <img v-if="branch?.owner?.logo" :src="'/storage/' + branch.owner.logo" alt="Logo" class="kwitansi-logo">
          <div class="kwitansi-company-info">
            <div class="kwitansi-company-name">{{ branch?.owner?.name || 'NAMA PERUSAHAAN' }}</div>
            <div class="kwitansi-company-address">{{ branch?.address || 'Alamat Perusahaan' }}</div>
            <div class="kwitansi-company-telp">TELP. {{ branch?.contact || '-' }}</div>
          </div>
        </div>
        
        <div class="kwitansi-title">
          KWITANSI
        </div>
        
        <div class="kwitansi-meta">
          <table>
            <tr>
              <td>Tgl Kwitansi</td>
              <td>: {{ formatDateOnly(sale.transaction_date) }}</td>
            </tr>
            <tr>
              <td>Faktur No</td>
              <td>: {{ sale.invoice_number }}</td>
            </tr>
            <tr>
              <td>No Pelanggan</td>
              <td>: {{ sale.customer?.code || '-' }}</td>
            </tr>
          </table>
        </div>
      </div>
      
      <div class="kwitansi-dashed-line"></div>
      
      <div class="kwitansi-received-from">
        <table>
          <tr>
            <td style="width: 140px;">Telah terima dari</td>
            <td>: {{ sale.customer?.name || 'Pelanggan Umum' }}</td>
          </tr>
          <tr>
            <td>Sejumlah uang</td>
            <td>: {{ formatCurrency(sale.grand_total) }}</td>
          </tr>
        </table>
        
        <div class="kwitansi-terbilang-box">
          <span style="font-style: italic;">{{ terbilangStr }}</span>
        </div>
      </div>
      
      <table class="kwitansi-items-table">
        <thead>
          <tr>
            <th style="width: 50px;">NO</th>
            <th>K E T E R A N G A N</th>
            <th style="width: 150px; text-align: right;">JUMLAH</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in sale.items" :key="index">
            <td style="text-align: center;">{{ index + 1 }}</td>
            <td>{{ item.product?.name || item.product_name }} <br><small>{{ item.quantity }} x {{ formatCurrency(item.unit_price) }}</small></td>
            <td style="text-align: right;">{{ formatCurrency(item.subtotal) }}</td>
          </tr>
        </tbody>
      </table>
      
      <div class="kwitansi-footer">
        <div class="kwitansi-footer-left">
          <table>
            <tr>
              <td style="width: 100px;">Total</td>
              <td>: {{ formatCurrency(sale.total_amount) }}</td>
            </tr>
            <tr v-if="sale.discount > 0">
              <td>Diskon</td>
              <td>: {{ formatCurrency(sale.discount) }}</td>
            </tr>
            <tr>
              <td>Grand Total</td>
              <td>: {{ formatCurrency(sale.grand_total) }}</td>
            </tr>
            <tr>
              <td>Status</td>
              <td>: {{ sale.payment_status === 'paid' ? 'Lunas' : 'Belum Lunas' }}</td>
            </tr>
          </table>
          
          <div class="kwitansi-perhatian-box">
            <strong>Perhatian :</strong>
            <p>Barang yang sudah dibeli tidak dapat ditukar atau dikembalikan.</p>
          </div>
        </div>
        
        <div class="kwitansi-footer-right">
          <div class="kwitansi-total-box">
            <span>T O T A L :</span>
            <span style="font-weight: bold;">{{ formatCurrency(sale.grand_total) }}</span>
          </div>
          
          <div class="kwitansi-signature">
            <div style="margin-bottom: 50px;">{{ formatDateOnly(new Date()) }}</div>
            <div style="font-weight: bold; text-decoration: underline;">{{ cashierName || 'Kasir' }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODE THERMAL KASIR (Default) -->
    <div v-else class="receipt-content">
      <!-- Header -->
      <div class="text-center mb-3">
        <img v-if="branch?.owner?.logo" :src="'/storage/' + branch.owner.logo" alt="Logo" style="max-height: 50px; margin-bottom: 5px;">
        <h2
          v-if="branch?.owner"
          class="font-weight-bold mb-1"
          style="font-size: 16px;"
        >
          {{ branch.owner.name }}
        </h2>
        <h3
          v-if="branch"
          class="mb-1"
          style="font-size: 14px; font-weight: normal;"
        >
          {{ branch.name }}
        </h3>
        <p style="font-size: 11px; line-height: 1.2; margin-bottom: 0;">
          {{ branch?.address || '' }}
        </p>
        <p
          v-if="branch?.contact"
          style="font-size: 11px; margin-bottom: 0;"
        >
          Telp: {{ branch.contact }}
        </p>
      </div>
      
      <div class="divider-dashed mb-2" />
      
      <!-- Meta -->
      <table
        style="width: 100%; font-size: 11px; line-height: 1.3;"
        class="mb-2"
      >
        <tr>
          <td style="width: 40px;">
            Faktur
          </td>
          <td>:</td>
          <td>{{ sale.invoice_number }}</td>
        </tr>
        <tr>
          <td>Waktu</td>
          <td>:</td>
          <td>{{ formatDate(sale.transaction_date) }}</td>
        </tr>
        <tr>
          <td>Kasir</td>
          <td>:</td>
          <td>{{ cashierName || 'Kasir' }}</td>
        </tr>
        <tr>
          <td>Plgn</td>
          <td>:</td>
          <td>{{ sale.customer?.name || 'Umum' }}</td>
        </tr>
      </table>
      
      <div class="divider-dashed mb-2" />
      
      <!-- Items -->
      <table
        style="width: 100%; font-size: 11px; line-height: 1.3;"
        class="mb-2"
      >
        <template
          v-for="item in sale.items"
          :key="item.id"
        >
          <tr>
            <td
              colspan="3"
              class="pb-1"
            >
              {{ item.product?.name || item.product_name }}
            </td>
          </tr>
          <tr>
            <td style="width: 40%;">
              {{ item.quantity }} x {{ formatCurrency(item.unit_price) }}
            </td>
            <td
              v-if="item.discount > 0"
              style="width: 20%;"
            >
              -{{ formatCurrency(item.discount) }}
            </td>
            <td
              v-else
              style="width: 20%;"
            />
            <td
              style="width: 40%; text-align: right;"
              class="pb-1"
            >
              {{ formatCurrency(item.subtotal) }}
            </td>
          </tr>
        </template>
      </table>
      
      <div class="divider-dashed mb-2" />
      
      <!-- Totals -->
      <table
        style="width: 100%; font-size: 11px; line-height: 1.3;"
        class="mb-3"
      >
        <tr>
          <td>Subtotal</td>
          <td style="text-align: right;">
            {{ formatCurrency(sale.total_amount) }}
          </td>
        </tr>
        <tr v-if="sale.discount > 0">
          <td>Diskon</td>
          <td style="text-align: right;">
            -{{ formatCurrency(sale.discount) }}
          </td>
        </tr>
        <tr v-if="sale.tax_amount > 0">
          <td>Pajak</td>
          <td style="text-align: right;">
            {{ formatCurrency(sale.tax_amount) }}
          </td>
        </tr>
        <tr style="font-weight: bold; font-size: 12px;">
          <td class="pt-1">
            Total
          </td>
          <td
            style="text-align: right;"
            class="pt-1"
          >
            {{ formatCurrency(sale.grand_total) }}
          </td>
        </tr>
        <tr>
          <td class="pt-1">
            Tunai / Bayar
          </td>
          <td
            style="text-align: right;"
            class="pt-1"
          >
            {{ formatCurrency(sale.paid_amount || sale.grand_total) }}
          </td>
        </tr>
        <tr>
          <td>Kembali</td>
          <td style="text-align: right;">
            {{ formatCurrency(sale.change_amount || 0) }}
          </td>
        </tr>
      </table>
      
      <!-- Footer -->
      <div class="divider-dashed mb-2" />
      
      <div
        class="text-center mt-4"
        style="font-size: 11px;"
      >
        <p class="mb-1 font-weight-bold">
          Terima Kasih
        </p>
        <p style="font-size: 10px; line-height: 1.1;">
          Barang yang sudah dibeli tidak dapat ditukar atau dikembalikan
        </p>
      </div>
    </div>
  </div>
</template>

<style>
/* Hide the receipt on screen by default */
#print-receipt-section {
  display: none;
}

@media print {
  /* Hide EVERYTHING else when printing */
  body * {
    visibility: hidden;
  }
  
  /* Show only the receipt and its children */
  #print-receipt-section, #print-receipt-section * {
    visibility: visible;
  }
  
  #print-receipt-section {
    display: block;
    position: absolute;
    left: 0;
    top: 0;
    width: var(--printer-width, 58mm);
    margin: 0;
    padding: 10px;
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
    size: auto;
  }

  /* KWITANSI SPECIFIC STYLES */
  .kwitansi-wrapper {
    width: 100%;
    font-family: Arial, sans-serif;
    color: #000;
  }

  .kwitansi-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
  }

  .kwitansi-company {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .kwitansi-logo {
    width: 60px;
    height: auto;
    object-fit: contain;
  }

  .kwitansi-company-name {
    font-size: 18px;
    font-weight: bold;
    text-transform: uppercase;
  }

  .kwitansi-company-address, .kwitansi-company-telp {
    font-size: 12px;
  }

  .kwitansi-title {
    font-size: 24px;
    font-weight: bold;
    letter-spacing: 2px;
    margin-top: 15px;
  }

  .kwitansi-meta table {
    font-size: 12px;
  }

  .kwitansi-meta td {
    padding: 2px 5px;
  }

  .kwitansi-dashed-line {
    border-top: 2px solid #000;
    border-bottom: 1px solid #000;
    height: 3px;
    margin-bottom: 15px;
  }

  .kwitansi-received-from {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
  }

  .kwitansi-received-from table {
    font-size: 13px;
  }

  .kwitansi-terbilang-box {
    border: 1px dashed #000;
    border-radius: 20px;
    padding: 10px 20px;
    font-size: 14px;
    min-width: 300px;
    text-align: center;
    background-color: #f9f9f9;
    -webkit-print-color-adjust: exact;
  }

  .kwitansi-items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 15px;
    font-size: 13px;
  }

  .kwitansi-items-table th {
    border-top: 1px solid #000;
    border-bottom: 1px solid #000;
    padding: 8px 5px;
    text-align: left;
  }

  .kwitansi-items-table td {
    padding: 8px 5px;
  }

  .kwitansi-footer {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
  }

  .kwitansi-footer-left table {
    font-size: 12px;
    margin-bottom: 10px;
  }

  .kwitansi-perhatian-box {
    border: 1px solid #000;
    padding: 10px;
    width: 250px;
    font-size: 11px;
  }

  .kwitansi-total-box {
    border-top: 1px solid #000;
    border-bottom: 1px solid #000;
    padding: 5px 0;
    font-size: 16px;
    display: flex;
    justify-content: space-between;
    width: 250px;
    margin-bottom: 20px;
  }

  .kwitansi-signature {
    text-align: right;
    font-size: 13px;
    margin-top: 20px;
  }
}
</style>
