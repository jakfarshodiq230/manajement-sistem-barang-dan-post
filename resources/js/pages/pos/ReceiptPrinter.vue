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
  },
  printFormat: {
    type: String,
    default: 'continuous_form', // 'continuous_form', 'thermal', 'kwitansi'
  }
})

const formatCurrency = val => {
  return new Intl.NumberFormat('id-ID').format(val || 0)
}

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

const print = () => {
  const printEl = document.getElementById('print-receipt-section')
  if (!printEl) {
    window.print()
    return
  }

  const iframe = document.createElement('iframe')
  iframe.style.position = 'fixed'
  iframe.style.right = '0'
  iframe.style.bottom = '0'
  iframe.style.width = '0'
  iframe.style.height = '0'
  iframe.style.border = '0'
  document.body.appendChild(iframe)

  const isContinuous = props.printFormat === 'continuous_form'
  const isThermal = props.printFormat === 'thermal'
  const isKwitansi = props.printFormat === 'kwitansi'

  let pageStyle = ''
  if (isContinuous) {
    pageStyle = `
      @page { size: auto; margin: 4mm 6mm; }
      body {
        font-family: 'Courier New', Courier, monospace;
        font-size: 11px;
        line-height: 1.25;
        width: 100%;
        max-width: 241mm;
        color: #000;
        background: #fff;
        padding: 4mm;
      }
      .dotmatrix-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 5px; }
      .dotmatrix-store-name { font-size: 14px; font-weight: bold; }
      .dotmatrix-store-sub { font-size: 10px; }
      .dotmatrix-title-box { text-align: center; }
      .dotmatrix-doc-title { font-size: 15px; font-weight: bold; text-decoration: underline; letter-spacing: 1px; }
      .dotmatrix-doc-sub { font-size: 10px; }
      .dotmatrix-meta table { font-size: 10px; border-collapse: collapse; }
      .dotmatrix-meta td { padding: 1px 3px; }
      .dotmatrix-divider-solid { border-top: 1px solid #000; margin: 4px 0; }
      .dotmatrix-divider-dashed { border-top: 1px dashed #000; margin: 4px 0; }
      .dotmatrix-table { width: 100%; border-collapse: collapse; font-size: 11px; }
      .dotmatrix-table th { border-bottom: 1px solid #000; border-top: 1px solid #000; padding: 3px 4px; font-weight: bold; }
      .dotmatrix-table td { padding: 2px 4px; }
      .dotmatrix-bottom { display: flex; justify-content: space-between; margin-top: 4px; }
      .dotmatrix-bottom-left { width: 55%; }
      .dotmatrix-terbilang { font-size: 10px; margin-bottom: 8px; font-style: italic; }
      .dotmatrix-signatures { display: flex; gap: 30px; margin-bottom: 6px; }
      .dotmatrix-sig-box { text-align: center; font-size: 10px; }
      .dotmatrix-sig-line { margin-top: 35px; font-weight: bold; }
      .dotmatrix-notice { font-size: 9px; font-style: italic; }
      .dotmatrix-bottom-right { width: 40%; }
      .dotmatrix-totals-table { width: 100%; font-size: 11px; border-collapse: collapse; }
      .dotmatrix-totals-table td { padding: 1px 4px; }
      .dotmatrix-grand-total { font-weight: bold; font-size: 12px; border-top: 1px dashed #000; border-bottom: 1px dashed #000; }
      .font-bold { font-weight: bold; }
    `
  } else if (isThermal) {
    pageStyle = `
      @page { size: auto; margin: 0; }
      body {
        font-family: 'Courier New', Courier, monospace;
        font-size: 11px;
        line-height: 1.2;
        width: 58mm;
        color: #000;
        background: #fff;
        padding: 2mm;
      }
      .text-center { text-align: center; }
      .font-weight-bold { font-weight: bold; }
      .divider-dashed { border-top: 1px dashed #000; }
      .mb-1 { margin-bottom: 3px; }
      .mb-2 { margin-bottom: 6px; }
      .mb-3 { margin-bottom: 8px; }
      .mt-3 { margin-top: 8px; }
      .pb-1 { padding-bottom: 3px; }
      .pt-1 { padding-top: 3px; }
      table { width: 100%; border-collapse: collapse; font-size: 11px; }
      td { padding: 1px 2px; }
    `
  } else {
    pageStyle = `
      @page { size: auto; margin: 10mm; }
      body {
        font-family: Arial, sans-serif;
        color: #000;
        background: #fff;
        font-size: 12px;
        padding: 5mm;
      }
      .kwitansi-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
      .kwitansi-company { display: flex; align-items: center; gap: 10px; }
      .kwitansi-company-name { font-size: 18px; font-weight: bold; }
      .kwitansi-title { font-size: 22px; font-weight: bold; text-decoration: underline; letter-spacing: 2px; }
      .kwitansi-dashed-line { border-top: 1px dashed #000; margin: 10px 0; }
      .kwitansi-received-from table { width: 100%; font-size: 13px; margin-bottom: 10px; }
      .kwitansi-terbilang-box { background-color: #f0f0f0; border: 1px solid #ccc; padding: 6px 10px; font-size: 12px; margin-bottom: 12px; }
      .kwitansi-items-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 12px; }
      .kwitansi-items-table th, .kwitansi-items-table td { border: 1px solid #000; padding: 5px 8px; }
      .kwitansi-items-table th { background-color: #f0f0f0; }
      .kwitansi-footer { display: flex; justify-content: space-between; }
      .kwitansi-footer-left { width: 50%; }
      .kwitansi-footer-right { width: 40%; text-align: right; }
      .kwitansi-total-box { border: 2px solid #000; padding: 6px 10px; font-size: 14px; display: flex; justify-content: space-between; margin-bottom: 15px; }
      .kwitansi-signature { text-align: center; font-size: 12px; margin-top: 10px; }
    `
  }

  const doc = iframe.contentWindow.document
  doc.open()
  doc.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>Faktur / Struk - ${props.sale?.invoice_number || 'Receipt'}</title>
      <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        ${pageStyle}
      </style>
    </head>
    <body>
      ${printEl.innerHTML}
    </body>
    </html>
  `)
  doc.close()

  setTimeout(() => {
    iframe.contentWindow.focus()
    iframe.contentWindow.print()
    setTimeout(() => {
      document.body.removeChild(iframe)
    }, 1500)
  }, 250)
}

defineExpose({ print })
</script>

<template>
  <div
    v-if="sale"
    id="print-receipt-section"
    :class="{
      'format-continuous-form': props.printFormat === 'continuous_form',
      'format-thermal': props.printFormat === 'thermal',
      'format-kwitansi': props.printFormat === 'kwitansi',
    }"
  >
    <!-- 1. MODE CONTINUOUS FORM (DOT MATRIX PRINTER - EPSON LX/LQ 9.5" x 5.5") -->
    <div v-if="props.printFormat === 'continuous_form'" class="dotmatrix-wrapper">
      <div class="dotmatrix-header">
        <div class="dotmatrix-store">
          <div class="dotmatrix-store-name">{{ branch?.owner?.name || branch?.name || 'PT. DUMAI INVENTORI' }}</div>
          <div class="dotmatrix-store-sub">{{ branch?.address || 'Alamat Toko' }}</div>
          <div class="dotmatrix-store-sub">TELP: {{ branch?.contact || '-' }}</div>
        </div>
        <div class="dotmatrix-title-box">
          <div class="dotmatrix-doc-title">FAKTUR PENJUALAN</div>
          <div class="dotmatrix-doc-sub">(NOTA TOKO)</div>
        </div>
        <div class="dotmatrix-meta">
          <table>
            <tr>
              <td>NO. FAKTUR</td>
              <td>:</td>
              <td class="font-bold">{{ sale.invoice_number }}</td>
            </tr>
            <tr>
              <td>TANGGAL</td>
              <td>:</td>
              <td>{{ formatDate(sale.transaction_date) }}</td>
            </tr>
            <tr>
              <td>KASIR</td>
              <td>:</td>
              <td>{{ cashierName || 'KASIR' }}</td>
            </tr>
            <tr>
              <td>PELANGGAN</td>
              <td>:</td>
              <td>{{ sale.customer?.name || 'UMUM' }}</td>
            </tr>
          </table>
        </div>
      </div>

      <div class="dotmatrix-divider-solid"></div>

      <!-- Items Table -->
      <table class="dotmatrix-table">
        <thead>
          <tr>
            <th style="width: 30px; text-align: center;">NO</th>
            <th style="text-align: left;">KODE / NAMA BARANG</th>
            <th style="width: 80px; text-align: center;">QTY</th>
            <th style="width: 100px; text-align: right;">HARGA</th>
            <th style="width: 80px; text-align: right;">DISC</th>
            <th style="width: 120px; text-align: right;">JUMLAH</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, idx) in sale.items" :key="idx">
            <td style="text-align: center;">{{ idx + 1 }}</td>
            <td>
              <span class="font-bold">{{ item.product?.name || item.product_name }}</span>
              <span v-if="item.product?.sku" style="font-size: 10px;"> [{{ item.product.sku }}]</span>
            </td>
            <td style="text-align: center;">{{ item.quantity }} {{ item.unit || 'pcs' }}</td>
            <td style="text-align: right;">{{ formatCurrency(item.unit_price) }}</td>
            <td style="text-align: right;">{{ item.discount > 0 ? formatCurrency(item.discount) : '-' }}</td>
            <td style="text-align: right; font-weight: bold;">{{ formatCurrency(item.subtotal) }}</td>
          </tr>
        </tbody>
      </table>

      <div class="dotmatrix-divider-dashed"></div>

      <!-- Summary & Signatures -->
      <div class="dotmatrix-bottom">
        <div class="dotmatrix-bottom-left">
          <div class="dotmatrix-terbilang">
            Terbilang: <em># {{ terbilangStr }} #</em>
          </div>
          
          <div class="dotmatrix-signatures">
            <div class="dotmatrix-sig-box">
              <div>Tanda Terima,</div>
              <div class="dotmatrix-sig-line">( Pelanggan )</div>
            </div>
            <div class="dotmatrix-sig-box">
              <div>Hormat Kami,</div>
              <div class="dotmatrix-sig-line">( {{ cashierName || 'Kasir' }} )</div>
            </div>
          </div>
          
          <div class="dotmatrix-notice">
            * Perhatian: Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.
          </div>
        </div>

        <div class="dotmatrix-bottom-right">
          <table class="dotmatrix-totals-table">
            <tr>
              <td>SUBTOTAL</td>
              <td>:</td>
              <td style="text-align: right;">{{ formatCurrency(sale.total_amount) }}</td>
            </tr>
            <tr v-if="sale.discount > 0">
              <td>DISKON</td>
              <td>:</td>
              <td style="text-align: right;">-{{ formatCurrency(sale.discount) }}</td>
            </tr>
            <tr v-if="sale.tax_amount > 0">
              <td>PPN</td>
              <td>:</td>
              <td style="text-align: right;">{{ formatCurrency(sale.tax_amount) }}</td>
            </tr>
            <tr class="dotmatrix-grand-total">
              <td>GRAND TOTAL</td>
              <td>:</td>
              <td style="text-align: right;">Rp {{ formatCurrency(sale.grand_total) }}</td>
            </tr>
            <tr>
              <td>BAYAR (TUNAI)</td>
              <td>:</td>
              <td style="text-align: right;">Rp {{ formatCurrency(sale.paid_amount || sale.grand_total) }}</td>
            </tr>
            <tr>
              <td>KEMBALI</td>
              <td>:</td>
              <td style="text-align: right;">Rp {{ formatCurrency(sale.change_amount || 0) }}</td>
            </tr>
          </table>
        </div>
      </div>
    </div>

    <!-- 2. MODE THERMAL KASIR (58mm / 80mm) -->
    <div v-else-if="props.printFormat === 'thermal'" class="thermal-receipt-content">
      <!-- Header -->
      <div class="text-center mb-3">
        <h2 v-if="branch?.owner" class="font-weight-bold mb-1" style="font-size: 16px;">
          {{ branch.owner.name }}
        </h2>
        <h3 v-if="branch" class="mb-1" style="font-size: 14px; font-weight: normal;">
          {{ branch.name }}
        </h3>
        <p style="font-size: 11px; line-height: 1.2; margin-bottom: 0;">
          {{ branch?.address || '' }}
        </p>
        <p v-if="branch?.contact" style="font-size: 11px; margin-bottom: 0;">
          Telp: {{ branch.contact }}
        </p>
      </div>
      
      <div class="divider-dashed mb-2" />
      
      <!-- Meta -->
      <table style="width: 100%; font-size: 11px; line-height: 1.3;" class="mb-2">
        <tr>
          <td style="width: 45px;">Faktur</td>
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
      <table style="width: 100%; font-size: 11px; line-height: 1.3;" class="mb-2">
        <template v-for="item in sale.items" :key="item.id">
          <tr>
            <td colspan="3" class="pb-1">
              {{ item.product?.name || item.product_name }}
            </td>
          </tr>
          <tr>
            <td style="width: 45%;">
              {{ item.quantity }} x {{ formatCurrency(item.unit_price) }}
            </td>
            <td v-if="item.discount > 0" style="width: 15%;">
              -{{ formatCurrency(item.discount) }}
            </td>
            <td v-else style="width: 15%;" />
            <td style="width: 40%; text-align: right;" class="pb-1">
              {{ formatCurrency(item.subtotal) }}
            </td>
          </tr>
        </template>
      </table>
      
      <div class="divider-dashed mb-2" />
      
      <!-- Totals -->
      <table style="width: 100%; font-size: 11px; line-height: 1.3;" class="mb-3">
        <tr>
          <td>Subtotal</td>
          <td style="text-align: right;">{{ formatCurrency(sale.total_amount) }}</td>
        </tr>
        <tr v-if="sale.discount > 0">
          <td>Diskon</td>
          <td style="text-align: right;">-{{ formatCurrency(sale.discount) }}</td>
        </tr>
        <tr v-if="sale.tax_amount > 0">
          <td>Pajak</td>
          <td style="text-align: right;">{{ formatCurrency(sale.tax_amount) }}</td>
        </tr>
        <tr style="font-weight: bold; font-size: 12px;">
          <td class="pt-1">Total</td>
          <td style="text-align: right;" class="pt-1">{{ formatCurrency(sale.grand_total) }}</td>
        </tr>
        <tr>
          <td class="pt-1">Tunai / Bayar</td>
          <td style="text-align: right;" class="pt-1">{{ formatCurrency(sale.paid_amount || sale.grand_total) }}</td>
        </tr>
        <tr>
          <td>Kembali</td>
          <td style="text-align: right;">{{ formatCurrency(sale.change_amount || 0) }}</td>
        </tr>
      </table>
      
      <!-- Footer -->
      <div class="divider-dashed mb-2" />
      <div class="text-center mt-3" style="font-size: 11px;">
        <p class="mb-1 font-weight-bold">Terima Kasih</p>
        <p style="font-size: 10px; line-height: 1.1;">
          Barang yang sudah dibeli tidak dapat ditukar atau dikembalikan
        </p>
      </div>
    </div>

    <!-- 3. MODE KWITANSI FORMAL (A4 / Setengah Folio) -->
    <div v-else class="kwitansi-wrapper">
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
  </div>
</template>

<style>
/* Hide the receipt on screen by default */
#print-receipt-section {
  display: none;
}

@media print {
  body * {
    visibility: hidden;
  }
  
  #print-receipt-section, #print-receipt-section * {
    visibility: visible;
  }
  
  #print-receipt-section {
    display: block;
    position: absolute;
    left: 0;
    top: 0;
    margin: 0;
    padding: 0;
    color: #000 !important;
    background: #fff !important;
  }

  /* CONTINUOUS FORM / DOT MATRIX SPECIFIC CSS (9.5" x 5.5") */
  #print-receipt-section.format-continuous-form {
    width: 241mm !important;
    max-width: 241mm !important;
    padding: 5mm 8mm !important;
    font-family: 'Courier New', Courier, monospace !important;
    font-size: 11px !important;
    line-height: 1.2 !important;
  }

  #print-receipt-section.format-thermal {
    width: 58mm !important;
    padding: 2mm !important;
    font-family: 'Courier New', Courier, monospace !important;
    font-size: 11px !important;
  }

  #print-receipt-section.format-kwitansi {
    width: 100% !important;
    font-family: Arial, sans-serif !important;
  }

  @page {
    margin: 0;
    size: auto;
  }
}

/* CONTINUOUS FORM STYLING (DOT MATRIX) */
.dotmatrix-wrapper {
  width: 100%;
  color: #000;
  font-family: 'Courier New', Courier, monospace;
  font-size: 11px;
}

.dotmatrix-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 5px;
}

.dotmatrix-store-name {
  font-size: 14px;
  font-weight: bold;
  letter-spacing: 0.5px;
}

.dotmatrix-store-sub {
  font-size: 10px;
}

.dotmatrix-title-box {
  text-align: center;
}

.dotmatrix-doc-title {
  font-size: 15px;
  font-weight: bold;
  text-decoration: underline;
  letter-spacing: 1px;
}

.dotmatrix-doc-sub {
  font-size: 10px;
}

.dotmatrix-meta table {
  font-size: 10px;
  border-collapse: collapse;
}

.dotmatrix-meta td {
  padding: 1px 3px;
}

.dotmatrix-divider-solid {
  border-top: 1px solid #000;
  margin: 4px 0;
}

.dotmatrix-divider-dashed {
  border-top: 1px dashed #000;
  margin: 4px 0;
}

.dotmatrix-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 11px;
}

.dotmatrix-table th {
  border-bottom: 1px solid #000;
  border-top: 1px solid #000;
  padding: 3px 4px;
  font-weight: bold;
}

.dotmatrix-table td {
  padding: 2px 4px;
}

.dotmatrix-bottom {
  display: flex;
  justify-content: space-between;
  margin-top: 4px;
}

.dotmatrix-bottom-left {
  width: 55%;
}

.dotmatrix-terbilang {
  font-size: 10px;
  margin-bottom: 8px;
  font-style: italic;
}

.dotmatrix-signatures {
  display: flex;
  gap: 30px;
  margin-bottom: 6px;
}

.dotmatrix-sig-box {
  text-align: center;
  font-size: 10px;
}

.dotmatrix-sig-line {
  margin-top: 35px;
  font-weight: bold;
}

.dotmatrix-notice {
  font-size: 9px;
  font-style: italic;
}

.dotmatrix-bottom-right {
  width: 40%;
}

.dotmatrix-totals-table {
  width: 100%;
  font-size: 11px;
  border-collapse: collapse;
}

.dotmatrix-totals-table td {
  padding: 1px 4px;
}

.dotmatrix-grand-total {
  font-weight: bold;
  font-size: 12px;
  border-top: 1px dashed #000;
  border-bottom: 1px dashed #000;
}

.font-bold {
  font-weight: bold;
}

/* THERMAL STYLES */
.thermal-receipt-content {
  width: 100%;
  font-family: 'Courier New', Courier, monospace;
}

.divider-dashed {
  border-top: 1px dashed #000;
}

/* KWITANSI STYLES */
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
}

.kwitansi-company-address, .kwitansi-company-telp {
  font-size: 12px;
}

.kwitansi-title {
  font-size: 24px;
  font-weight: bold;
  text-decoration: underline;
  letter-spacing: 2px;
}

.kwitansi-meta table {
  font-size: 12px;
}

.kwitansi-meta td {
  padding: 2px 4px;
}

.kwitansi-dashed-line {
  border-top: 1px dashed #000;
  margin: 10px 0;
}

.kwitansi-received-from table {
  width: 100%;
  font-size: 13px;
  margin-bottom: 10px;
}

.kwitansi-received-from td {
  padding: 4px;
}

.kwitansi-terbilang-box {
  background-color: #f0f0f0;
  border: 1px solid #ccc;
  padding: 8px 12px;
  font-size: 13px;
  margin-bottom: 15px;
}

.kwitansi-items-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 15px;
  font-size: 12px;
}

.kwitansi-items-table th, .kwitansi-items-table td {
  border: 1px solid #000;
  padding: 6px 8px;
}

.kwitansi-items-table th {
  background-color: #f0f0f0;
}

.kwitansi-footer {
  display: flex;
  justify-content: space-between;
}

.kwitansi-footer-left {
  width: 50%;
}

.kwitansi-footer-left table {
  font-size: 12px;
  margin-bottom: 10px;
}

.kwitansi-footer-left td {
  padding: 2px;
}

.kwitansi-perhatian-box {
  font-size: 11px;
  font-style: italic;
}

.kwitansi-footer-right {
  width: 40%;
  text-align: right;
}

.kwitansi-total-box {
  border: 2px solid #000;
  padding: 6px 10px;
  font-size: 14px;
  display: flex;
  justify-content: space-between;
  margin-bottom: 20px;
}

.kwitansi-signature {
  text-align: center;
  font-size: 12px;
  margin-top: 10px;
}
</style>
