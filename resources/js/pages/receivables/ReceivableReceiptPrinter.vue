<script setup>
import { computed } from 'vue'
import dayjs from 'dayjs'
import 'dayjs/locale/id'
import QrcodeVue from 'qrcode.vue'

dayjs.locale('id')

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
  setting: {
    type: Object,
    required: false,
    default: null,
  },
  printFormat: {
    type: String,
    default: 'continuous_form', // 'continuous_form', 'kwitansi', 'thermal'
  },
})

const formatCurrency = val => {
  return new Intl.NumberFormat('id-ID').format(Math.round(val || 0))
}

const formatDate = date => {
  if (!date) return '-'
  return dayjs(date).format('DD-MM-YYYY')
}

const formatDateWithTime = date => {
  if (!date) return '-'
  return dayjs(date).format('DD-MM-YYYY HH:mm')
}

const formatDateOnly = date => {
  if (!date) return '-'
  return dayjs(date).format('DD MMMM YYYY')
}

// Sisa Piutang Setelah Pembayaran Ini
const remainingBalance = computed(() => {
  if (!props.receivable) return 0
  const due = Number(props.receivable.amount_due) || 0
  const paid = Number(props.receivable.amount_paid) || 0
  return Math.max(0, due - paid)
})

// Terbilang Rupiah Helper
const terbilang = (nominal) => {
  const angka = Math.floor(Math.abs(Number(nominal) || 0))
  if (angka === 0) return 'nol'

  const satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas']

  function convert(n) {
    if (n < 12) return satuan[n]
    if (n < 20) return convert(n - 10) + ' belas'
    if (n < 100) return convert(Math.floor(n / 10)) + ' puluh' + (n % 10 > 0 ? ' ' + convert(n % 10) : '')
    if (n < 200) return 'seratus' + (n - 100 > 0 ? ' ' + convert(n - 100) : '')
    if (n < 1000) return convert(Math.floor(n / 100)) + ' ratus' + (n % 100 > 0 ? ' ' + convert(n % 100) : '')
    if (n < 2000) return 'seribu' + (n - 1000 > 0 ? ' ' + convert(n - 1000) : '')
    if (n < 1000000) return convert(Math.floor(n / 1000)) + ' ribu' + (n % 1000 > 0 ? ' ' + convert(n % 1000) : '')
    if (n < 1000000000) return convert(Math.floor(n / 1000000)) + ' juta' + (n % 1000000 > 0 ? ' ' + convert(n % 1000000) : '')
    if (n < 1000000000000) return convert(Math.floor(n / 1000000000)) + ' miliar' + (n % 1000000000 > 0 ? ' ' + convert(n % 1000000000) : '')
    if (n < 1000000000000000) return convert(Math.floor(n / 1000000000000)) + ' triliun' + (n % 1000000000000 > 0 ? ' ' + convert(n % 1000000000000) : '')
    return ''
  }

  return convert(angka).replace(/\s+/g, ' ').trim()
}

const terbilangStr = computed(() => {
  if (!props.lastPayment?.amount) return ''
  let text = terbilang(props.lastPayment.amount)
  return text.charAt(0).toUpperCase() + text.slice(1) + ' rupiah'
})

// Nama Kasir / Petugas
const cashierName = computed(() => {
  if (props.lastPayment?.user?.name) return props.lastPayment.user.name
  try {
    const userData = JSON.parse(localStorage.getItem('userData') || '{}')
    if (userData?.name) return userData.name
  } catch (e) {}
  return 'Petugas Kasir'
})

// Nama Pelanggan
const customerName = computed(() => {
  return props.receivable?.customer?.name || 'Pelanggan Toko'
})

// Current Branch
const currentBranch = computed(() => {
  return props.branch || props.receivable?.sale?.branch || {}
})

// Check Paper Formats from database
const isThermal = computed(() => {
  if (props.printFormat === 'thermal') return true
  const name = props.setting?.name?.toLowerCase() || ''
  const width = props.setting?.width || ''
  return name.includes('thermal') || width.includes('58mm') || width.includes('80mm')
})

const isContinuous11 = computed(() => {
  const name = props.setting?.name?.toLowerCase() || ''
  return name.includes('11 inch') || name.includes('11')
})

const isA5 = computed(() => {
  const name = props.setting?.name?.toLowerCase() || ''
  const width = props.setting?.width || ''
  return name.includes('a5') || width.includes('210mm')
})

// 1. QR Code Bukti Keabsahan Transaksi / Dokumen
const docQrValue = computed(() => {
  if (!props.lastPayment) return ''
  const inv = props.receivable?.sale?.invoice_number || 'PIUTANG'
  const cust = customerName.value
  const branchName = currentBranch.value.name || 'Cabang Utama'
  const payAmt = Number(props.lastPayment.amount || 0).toLocaleString('id-ID')
  const remainAmt = Number(remainingBalance.value || 0).toLocaleString('id-ID')
  const payDate = formatDate(props.lastPayment.payment_date || props.lastPayment.created_at)

  return `VERIFIKASI KEABSAHAN TRANSAKSI MS.POS\n`
    + `====================================\n`
    + `Dokumen   : Kuitansi Pembayaran Piutang\n`
    + `No. Nota  : ${inv}\n`
    + `Pelanggan : ${cust}\n`
    + `Cabang    : ${branchName}\n`
    + `Tgl Bayar : ${payDate}\n`
    + `Jml Bayar : Rp ${payAmt}\n`
    + `Sisa Bon  : Rp ${remainAmt}\n`
    + `Metode    : ${props.lastPayment.payment_method || 'Kas'}\n`
    + `Status    : PEMBAYARAN SAH & TERCATAT RESMI`
})

// 2. QR Code Tanda Tangan Digital Kasir (Identitas Penandatangan Lengkap)
const signerQrValue = computed(() => {
  const branchName = currentBranch.value.name || 'Cabang Utama'
  const timeStr = dayjs(props.lastPayment?.created_at || new Date()).format('DD/MM/YYYY HH:mm:ss')

  return `TANDA TANGAN DIGITAL RESMI (DIGITAL SIGNATURE)\n`
    + `===============================================\n`
    + `Penandatangan : ${cashierName.value}\n`
    + `Jabatan       : Petugas Kasir / Finance\n`
    + `Unit / Cabang : ${branchName}\n`
    + `Waktu TTD     : ${timeStr}\n`
    + `Keperluan     : Pengesahan Pembayaran Piutang (${props.receivable?.sale?.invoice_number || '-'})\n`
    + `Status TTD    : TERTANDA DIGITAL SAH (VERIFIED)`
})

const print = () => {
  const printEl = document.getElementById('print-receivable-receipt')
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

  let pageStyle = ''
  if (isThermal.value) {
    const widthVal = props.setting?.width || '80mm'
    const mt = props.setting?.margin_top ?? 0
    const mb = props.setting?.margin_bottom ?? 0
    const ml = props.setting?.margin_left ?? 0
    const mr = props.setting?.margin_right ?? 0
    pageStyle = `
      @page {
        size: ${widthVal} auto;
        margin: 0mm !important;
      }
      @media print {
        @page { margin: 0mm !important; }
        html, body { margin: 0mm !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      }
      * { box-sizing: border-box; }
      body {
        font-family: 'Courier New', Courier, monospace;
        font-size: 11px;
        line-height: 1.2;
        width: ${widthVal};
        color: #000;
        background: #fff;
        padding: ${mt || 2}mm ${mr || 3}mm ${mb || 2}mm ${ml || 3}mm;
        margin: 0 auto;
      }
      .text-center { text-align: center; }
      .text-right { text-align: right; }
      .font-bold { font-weight: bold; }
      .divider-dashed { border-top: 1px dashed #000; margin: 5px 0; }
      .meta-table { width: 100%; border-collapse: collapse; font-size: 11px; }
      .meta-table td { padding: 1px 0; }
    `
  } else {
    // Non-thermal: Continuous Form (9.5 x 5.5 Inch / 9.5 x 11 Inch) / A5 driven by database setting
    const mt = props.setting?.margin_top ?? 4
    const mb = props.setting?.margin_bottom ?? 4
    const ml = props.setting?.margin_left ?? 6
    const mr = props.setting?.margin_right ?? 6
    const pageSize = isContinuous11.value ? '241mm 280mm' : (isA5.value ? '210mm 148mm' : '241mm 140mm')

    pageStyle = `
      @page {
        size: ${pageSize};
        margin: 0mm !important;
      }
      @media print {
        @page { margin: 0mm !important; }
        html, body { margin: 0mm !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      }
      * { box-sizing: border-box; }
      body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif, 'Courier New', monospace;
        font-size: 11px;
        line-height: 1.3;
        width: 100%;
        color: #000;
        background: #fff;
        padding: ${mt}mm ${mr}mm ${mb}mm ${ml}mm;
        margin: 0;
      }
      .kwitansi-frame {
        border: 1.5px solid #000;
        border-radius: 4px;
        padding: 8px 12px;
        background: #fff;
      }
      .header-table {
        width: 100%;
        border-collapse: collapse;
      }
      .store-name {
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        color: #000;
        letter-spacing: 0.5px;
      }
      .store-address {
        font-size: 9.5px;
        color: #333;
        margin-top: 1px;
      }
      .kwitansi-title {
        font-size: 16px;
        font-weight: 900;
        letter-spacing: 2px;
        color: #000;
        text-align: right;
        margin: 0;
      }
      .kwitansi-meta {
        font-size: 10.5px;
        color: #000;
        text-align: right;
        margin-top: 2px;
        font-family: monospace;
      }
      .divider-solid {
        border-bottom: 1.5px solid #000;
        margin: 6px 0;
      }
      .form-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 4px;
        font-size: 11px;
      }
      .form-table td {
        vertical-align: top;
      }
      .form-label {
        width: 170px;
        font-weight: bold;
        color: #000;
        white-space: nowrap;
      }
      .form-sep {
        width: 10px;
        text-align: center;
        font-weight: bold;
      }
      .form-fill-line {
        border-bottom: 1px dotted #555;
        padding-bottom: 1px;
        width: 100%;
        display: block;
      }
      .terbilang-box {
        background: #f8fafc;
        border: 1px dashed #666;
        padding: 4px 8px;
        font-style: italic;
        font-weight: bold;
        font-size: 11px;
        color: #000;
        border-radius: 3px;
        display: block;
      }
      .footer-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 6px;
      }
      .nominal-box {
        border: 1.5px solid #000;
        background: #f8fafc;
        padding: 4px 10px;
        display: inline-block;
        border-radius: 3px;
      }
      .nominal-label {
        font-size: 9px;
        font-weight: bold;
        color: #333;
        letter-spacing: 0.5px;
      }
      .nominal-value {
        font-size: 15px;
        font-weight: 800;
        font-family: monospace, sans-serif;
        color: #000;
      }
      .ttd-table {
        width: 100%;
        text-align: center;
        border-collapse: collapse;
      }
      .ttd-table td {
        vertical-align: top;
        padding: 0 6px;
      }
      .ttd-name {
        font-weight: bold;
        font-size: 10px;
        color: #000;
      }
      .badge-digital {
        font-size: 6px;
        color: #16a34a;
        font-weight: bold;
        margin-top: 1px;
      }
    `
  }

  const doc = iframe.contentWindow.document
  doc.open()
  doc.write(`
    <!DOCTYPE html>
    <html>
      <head>
        <title></title>
        <style>
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
    }, 1000)
  }, 400)
}

defineExpose({
  print,
})
</script>

<template>
  <div
    v-if="receivable && lastPayment"
    id="print-receivable-receipt"
    class="receivable-print-wrapper"
  >
    <!-- 1. FORMAT STRUK THERMAL (58mm / 80mm) -->
    <div v-if="isThermal" class="thermal-container">
      <div class="text-center">
        <div class="font-bold" style="font-size: 13px;">
          {{ (currentBranch.owner?.name || currentBranch.name || 'PT. DUMAI BERKAH ABADI').toUpperCase() }}
        </div>
        <div style="font-size: 11px;">
          {{ currentBranch.name || 'Cabang Toko' }}
        </div>
        <div style="font-size: 9px;">
          {{ currentBranch.address || '-' }}
        </div>
        <div style="font-size: 9px;" v-if="currentBranch.phone">
          Telp: {{ currentBranch.phone }}
        </div>
      </div>

      <div class="divider-dashed"></div>

      <div class="text-center font-bold" style="font-size: 11px;">
        KUITANSI PENERIMAAN PIUTANG
      </div>

      <div class="divider-dashed"></div>

      <table class="meta-table">
        <tbody>
          <tr>
            <td>No. Nota</td>
            <td>: {{ receivable.sale?.invoice_number }}</td>
          </tr>
          <tr>
            <td>Tanggal</td>
            <td>: {{ formatDateWithTime(lastPayment.payment_date || lastPayment.created_at) }}</td>
          </tr>
          <tr>
            <td>Pelanggan</td>
            <td>: <strong>{{ customerName }}</strong></td>
          </tr>
          <tr>
            <td>Metode</td>
            <td>: {{ lastPayment.payment_method === 'bank_transfer' ? 'Transfer Bank' : (lastPayment.payment_method === 'qris' ? 'QRIS' : 'Kas Tunai') }}</td>
          </tr>
        </tbody>
      </table>

      <div class="divider-dashed"></div>

      <table class="meta-table">
        <tbody>
          <tr>
            <td>Total Piutang</td>
            <td class="text-right">Rp {{ formatCurrency(receivable.amount_due) }}</td>
          </tr>
          <tr class="font-bold" style="font-size: 12px;">
            <td>DIBAYAR KALI INI</td>
            <td class="text-right">Rp {{ formatCurrency(lastPayment.amount) }}</td>
          </tr>
          <tr>
            <td>Total Sudah Bayar</td>
            <td class="text-right">Rp {{ formatCurrency(receivable.amount_paid) }}</td>
          </tr>
          <tr class="font-bold" style="color: #000;">
            <td>Sisa Piutang</td>
            <td class="text-right">Rp {{ formatCurrency(remainingBalance) }}</td>
          </tr>
        </tbody>
      </table>

      <div class="divider-dashed"></div>

      <div style="font-size: 9px; font-style: italic; margin-bottom: 6px;">
        Terbilang: {{ terbilangStr }}
      </div>

      <!-- Thermal QR Barcode -->
      <div style="text-align: center; margin: 6px auto;">
        <QrcodeVue :value="signerQrValue" :size="65" level="M" render-as="svg" />
        <div style="font-size: 8px; color: #555; margin-top: 2px;">Scan Validasi Pembayaran</div>
      </div>

      <div class="text-center mt-2" style="font-size: 9px;">
        <div>Kasir: <strong>{{ cashierName }}</strong></div>
        <div style="margin-top: 3px;">*** Bukti Pembayaran Sah ***</div>
      </div>
    </div>

    <!-- 2. FORMAT KUITANSI CONTINUOUS FORM / A5 (NON-THERMAL SESUAI ATURAN KERTAS DATABASE) -->
    <div v-else class="kwitansi-frame">
      <!-- Kop Usaha & Judul Kwitansi -->
      <table class="header-table">
        <tbody>
          <tr>
            <td style="width: 58%; vertical-align: top;">
              <div class="store-name">
                {{ currentBranch.owner?.name || 'PT. PAGARUYUNG MITRA PERSADA' }}
              </div>
              <div class="store-address">
                <strong>{{ currentBranch.name || 'Cabang Utama' }}</strong> - {{ currentBranch.address || 'Jalan Lintas Kilometer 18' }}
              </div>
              <div class="store-address" v-if="currentBranch.phone">
                Telp: {{ currentBranch.phone }}
              </div>
            </td>
            <td style="width: 42%; vertical-align: top; text-align: right;">
              <div class="kwitansi-title">
                K U I T A N S I
              </div>
              <div class="kwitansi-meta">
                NO: <strong>{{ receivable.sale?.invoice_number }}</strong>
              </div>
              <div class="kwitansi-meta" style="font-size: 9.5px; color: #333;">
                TGL: {{ formatDateOnly(lastPayment.payment_date || lastPayment.created_at) }}
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Garis Pemisah Solid -->
      <div class="divider-solid"></div>

      <!-- Isi Formulir Kwitansi -->
      <table class="form-table">
        <tbody>
          <tr>
            <td class="form-label">TELAH DITERIMA DARI</td>
            <td class="form-sep">:</td>
            <td>
              <div class="form-fill-line">
                <strong style="font-size: 12px; text-transform: uppercase;">{{ customerName }}</strong>
                <span v-if="receivable.customer?.phone" style="font-size: 10.5px; color: #444; margin-left: 6px;">(Telp: {{ receivable.customer.phone }})</span>
              </div>
            </td>
          </tr>
          <tr>
            <td class="form-label">SEJUMLAH UANG</td>
            <td class="form-sep">:</td>
            <td>
              <div class="terbilang-box">
                *** {{ terbilangStr }} ***
              </div>
            </td>
          </tr>
          <tr>
            <td class="form-label">UNTUK PEMBAYARAN</td>
            <td class="form-sep">:</td>
            <td>
              <div class="form-fill-line">
                <span>Cicilan / Pelunasan Piutang Transaksi Bon No: <strong>{{ receivable.sale?.invoice_number }}</strong> (Tgl Bon: {{ formatDate(receivable.sale?.date || receivable.created_at) }})</span>
              </div>
            </td>
          </tr>
          <tr>
            <td class="form-label">METODE PEMBAYARAN</td>
            <td class="form-sep">:</td>
            <td>
              <div class="form-fill-line" style="font-size: 10.5px;">
                <strong>{{ lastPayment.payment_method === 'bank_transfer' || lastPayment.payment_method === 'transfer' ? 'Transfer Bank' : (lastPayment.payment_method === 'qris' ? 'QRIS' : 'Kas Tunai') }}</strong>
                <span v-if="lastPayment.bank_account || lastPayment.bank_name">
                  - {{ lastPayment.bank_account ? `${lastPayment.bank_account.bank_name} (${lastPayment.bank_account.account_number} a/n ${lastPayment.bank_account.account_name})` : lastPayment.bank_name }}
                </span>
                <span style="margin-left: 8px;">
                  | Total: Rp {{ formatCurrency(receivable.amount_due) }} | Sisa: <strong :style="{ color: remainingBalance > 0 ? '#b91c1c' : '#15803d' }">Rp {{ formatCurrency(remainingBalance) }}</strong>
                </span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="divider-solid"></div>

      <!-- Bagian Bawah: Box Nominal, QR Verifikasi, dan Tanda Tangan -->
      <table class="footer-table">
        <tbody>
          <tr>
            <!-- Kolom Kiri: Nominal Rp & QR Bukti Transaksi -->
            <td style="width: 44%; vertical-align: top;">
              <div class="nominal-box">
                <div class="nominal-label">JUMLAH DIBAYAR:</div>
                <div class="nominal-value">RP. {{ formatCurrency(lastPayment.amount) }}</div>
              </div>

              <!-- QR Bukti Keabsahan Transaksi -->
              <div style="margin-top: 6px; display: flex; align-items: center; gap: 6px; background: #f8fafc; border: 1px solid #ccc; padding: 4px 6px; border-radius: 3px; max-width: 240px;">
                <div style="border: 1px solid #aaa; padding: 1px; background: #fff; display: inline-block;">
                  <QrcodeVue :value="docQrValue" :size="32" level="M" render-as="svg" />
                </div>
                <div style="font-size: 7.5px; color: #333; line-height: 1.2;">
                  <strong>BUKTI TRANSAKSI SAH</strong><br>
                  Scan untuk verifikasi penerimaan piutang
                </div>
              </div>
            </td>

            <!-- Kolom Kanan: Tempat/Tgl & Tanda Tangan -->
            <td style="width: 56%; vertical-align: top;">
              <div style="text-align: right; font-size: 10px; color: #333; margin-bottom: 4px;">
                {{ currentBranch.city || currentBranch.name?.replace('Cabang ', '') || 'Duri' }}, {{ formatDateOnly(lastPayment.payment_date || lastPayment.created_at) }}
              </div>
              
              <table class="ttd-table">
                <tbody>
                  <tr>
                    <td style="width: 50%;">
                      <div style="font-size: 10px; margin-bottom: 2px;">Pelanggan (Penyetor),</div>
                      <div class="ttd-space" style="height: 36px; display: flex; align-items: center; justify-content: center;">
                        <span style="font-size: 8px; color: #999;">( Tanda Tangan )</span>
                      </div>
                      <div class="ttd-name">( {{ customerName }} )</div>
                    </td>
                    <td style="width: 50%;">
                      <div style="font-size: 10px; margin-bottom: 2px;">Kasir / Penerima,</div>
                      <div style="margin: 1px auto; display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 36px;">
                        <div style="border: 1px solid #ccc; padding: 1px; background: #fff; display: inline-block;">
                          <QrcodeVue :value="signerQrValue" :size="34" level="M" render-as="svg" />
                        </div>
                        <div class="badge-digital">[TERTANDA DIGITAL]</div>
                      </div>
                      <div class="ttd-name">
                        ( {{ cashierName }} )
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style>
/* Sembunyikan container dari layar utama */
#print-receivable-receipt {
  display: none;
}
</style>
