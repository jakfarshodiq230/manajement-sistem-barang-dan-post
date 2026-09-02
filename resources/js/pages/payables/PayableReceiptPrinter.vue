<script setup>
import { computed } from 'vue'
import dayjs from 'dayjs'
import 'dayjs/locale/id'
import QrcodeVue from 'qrcode.vue'

dayjs.locale('id')

const props = defineProps({
  statement: {
    type: Object,
    required: false,
    default: null,
  },
  payment: {
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
  if (!props.payment?.amount) return ''
  let text = terbilang(props.payment.amount)
  return text.charAt(0).toUpperCase() + text.slice(1) + ' rupiah'
})

// Nama Petugas Kasir / Admin dari Database
const signerName = computed(() => {
  if (props.payment?.creator?.name) return props.payment.creator.name
  if (props.payment?.user?.name) return props.payment.user.name
  if (props.payment?.creator?.employee?.name) return props.payment.creator.employee.name
  if (props.statement?.creator?.name) return props.statement.creator.name
  
  try {
    const userData = JSON.parse(localStorage.getItem('userData') || '{}')
    if (userData?.name) return userData.name
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    if (user?.name) return user.name
  } catch (e) {}

  return props.branch?.owner?.name || 'Admin Toko'
})

// Nama Penerima / Supplier dari Database
const supplierSignerName = computed(() => {
  return props.statement?.supplier?.contact_person || props.statement?.supplier?.name || 'Pihak Supplier'
})

// Current Branch
const currentBranch = computed(() => {
  return props.branch || props.statement?.branch || {}
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
  if (!props.payment) return ''
  const payNo = props.payment.payment_number || 'KAS-KELUAR'
  const supp = props.statement?.supplier?.name || 'Supplier'
  const branchName = currentBranch.value.name || 'Cabang Utama'
  const payAmt = Number(props.payment.amount || 0).toLocaleString('id-ID')
  const remainAmt = Number(props.statement?.remaining_amount || 0).toLocaleString('id-ID')
  const payDate = formatDate(props.payment.payment_date || props.payment.created_at)

  return `VERIFIKASI KEABSAHAN TRANSAKSI MS.POS\n`
    + `====================================\n`
    + `Dokumen   : Kuitansi Pembayaran Hutang\n`
    + `No. Kuitansi: ${payNo}\n`
    + `Supplier  : ${supp}\n`
    + `Cabang    : ${branchName}\n`
    + `Tgl Bayar : ${payDate}\n`
    + `Jml Bayar : Rp ${payAmt}\n`
    + `Sisa Hutang: Rp ${remainAmt}\n`
    + `Metode    : ${props.payment.payment_method || 'Kas'}\n`
    + `Status    : PENGELUARAN KAS SAH & TERCATAT RESMI`
})

// 2. QR Code Tanda Tangan Digital Kasir / Admin (Identitas Penandatangan Lengkap)
const signerQrValue = computed(() => {
  const branchName = currentBranch.value.name || 'Cabang Utama'
  const timeStr = dayjs(props.payment?.created_at || new Date()).format('DD/MM/YYYY HH:mm:ss')

  return `TANDA TANGAN DIGITAL RESMI (DIGITAL SIGNATURE)\n`
    + `===============================================\n`
    + `Penandatangan : ${signerName.value}\n`
    + `Jabatan       : Petugas Keuangan / Kasir\n`
    + `Unit / Cabang : ${branchName}\n`
    + `Waktu TTD     : ${timeStr}\n`
    + `Keperluan     : Pengesahan Pembayaran Tagihan (${props.statement?.statement_number || '-'})\n`
    + `Status TTD    : TERTANDA DIGITAL SAH (VERIFIED)`
})

const print = () => {
  const printEl = document.getElementById('print-payable-receipt-section')
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
    v-if="payment && statement"
    id="print-payable-receipt-section"
  >
    <!-- 1. FORMAT STRUK THERMAL (58mm / 80mm) -->
    <div v-if="isThermal" class="thermal-container">
      <div class="text-center">
        <div class="font-bold" style="font-size: 13px;">{{ (currentBranch.owner?.name || currentBranch.name || 'TOKO').toUpperCase() }}</div>
        <div style="font-size: 11px;">{{ currentBranch.name }}</div>
        <div style="font-size: 9px;">{{ currentBranch.address || '-' }}</div>
        <div style="font-size: 9px;" v-if="currentBranch.phone">Telp: {{ currentBranch.phone }}</div>
      </div>

      <div class="divider-dashed"></div>

      <div class="text-center font-bold" style="font-size: 11px;">
        KUITANSI PEMBAYARAN HUTANG
      </div>

      <div class="divider-dashed"></div>

      <table class="meta-table">
        <tbody>
          <tr>
            <td>No. Bukti</td>
            <td>: {{ payment.payment_number }}</td>
          </tr>
          <tr>
            <td>Tanggal</td>
            <td>: {{ formatDateWithTime(payment.payment_date || payment.created_at) }}</td>
          </tr>
          <tr>
            <td>Supplier</td>
            <td>: <strong>{{ statement.supplier?.name }}</strong></td>
          </tr>
          <tr>
            <td>No. Tagihan</td>
            <td>: {{ statement.statement_number }}</td>
          </tr>
          <tr>
            <td>Metode</td>
            <td>: {{ payment.payment_method === 'bank_transfer' ? 'Transfer Bank' : 'Kas Tunai' }}</td>
          </tr>
          <tr v-if="payment.bank_account">
            <td>Bank</td>
            <td>: {{ payment.bank_account.bank_name }}</td>
          </tr>
        </tbody>
      </table>

      <div class="divider-dashed"></div>

      <table class="meta-table">
        <tbody>
          <tr>
            <td>Total Tagihan</td>
            <td class="text-right">Rp {{ formatCurrency(statement.total_amount) }}</td>
          </tr>
          <tr class="font-bold" style="font-size: 12px;">
            <td>DIBAYAR KALI INI</td>
            <td class="text-right">Rp {{ formatCurrency(payment.amount) }}</td>
          </tr>
          <tr>
            <td>Total Sudah Bayar</td>
            <td class="text-right">Rp {{ formatCurrency(statement.paid_amount) }}</td>
          </tr>
          <tr class="font-bold" style="color: #000;">
            <td>Sisa Hutang</td>
            <td class="text-right">Rp {{ formatCurrency(statement.remaining_amount) }}</td>
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
        <div>Kasir / Admin: <strong>{{ signerName }}</strong></div>
        <div style="margin-top: 3px;">*** Struk ini adalah bukti pembayaran sah ***</div>
      </div>
    </div>

    <!-- 2. FORMAT KUITANSI CONTINUOUS FORM / A5 (NON-THERMAL SESUAI ATURAN KERTAS DATABASE) -->
    <div v-else class="kwitansi-frame">
      <!-- Kop Dokumen -->
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
                NO: <strong>{{ payment.payment_number }}</strong>
              </div>
              <div class="kwitansi-meta" style="font-size: 9.5px; color: #333;">
                TGL: {{ formatDateOnly(payment.payment_date || payment.created_at) }}
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Garis Pemisah Solid -->
      <div class="divider-solid"></div>

      <!-- Isi Formulir Kuitansi -->
      <table class="form-table">
        <tbody>
          <tr>
            <td class="form-label">DIBAYARKAN KEPADA</td>
            <td class="form-sep">:</td>
            <td>
              <div class="form-fill-line">
                <strong style="font-size: 12px; text-transform: uppercase;">{{ statement.supplier?.name }}</strong>
                <span v-if="statement.supplier?.phone" style="font-size: 10.5px; color: #444; margin-left: 6px;">(Telp: {{ statement.supplier.phone }})</span>
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
                <span>Cicilan / Pelunasan Hutang Tagihan Supplier Periode <strong>{{ statement.period_month }}</strong> (No. Tagihan: <strong>{{ statement.statement_number }}</strong>)</span>
              </div>
            </td>
          </tr>
          <tr>
            <td class="form-label">METODE PEMBAYARAN</td>
            <td class="form-sep">:</td>
            <td>
              <div class="form-fill-line" style="font-size: 10.5px;">
                <strong>{{ payment.payment_method === 'bank_transfer' ? 'Transfer Bank' : 'Kas Tunai' }}</strong>
                <span v-if="payment.bank_account">
                  - {{ payment.bank_account.bank_name }} ({{ payment.bank_account.account_number }} a/n {{ payment.bank_account.account_name }})
                </span>
                <span v-if="payment.reference_number">
                  | Ref: {{ payment.reference_number }}
                </span>
                <span style="margin-left: 8px;">
                  | Total: Rp {{ formatCurrency(statement.total_amount) }} | Sisa: <strong :style="{ color: statement.remaining_amount > 0 ? '#b91c1c' : '#15803d' }">Rp {{ formatCurrency(statement.remaining_amount) }}</strong>
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
                <div class="nominal-value">RP. {{ formatCurrency(payment.amount) }}</div>
              </div>

              <!-- QR Bukti Keabsahan Transaksi -->
              <div style="margin-top: 6px; display: flex; align-items: center; gap: 6px; background: #fafafa; border: 1px solid #ccc; padding: 4px 6px; border-radius: 3px; max-width: 240px;">
                <div style="border: 1px solid #aaa; padding: 1px; background: #fff; display: inline-block;">
                  <QrcodeVue :value="docQrValue" :size="32" level="M" render-as="svg" />
                </div>
                <div style="font-size: 7.5px; color: #333; line-height: 1.2;">
                  <strong>BUKTI TRANSAKSI SAH</strong><br>
                  Scan untuk verifikasi pengeluaran kas
                </div>
              </div>
            </td>

            <!-- Kolom Kanan: Tempat/Tgl & Tanda Tangan -->
            <td style="width: 56%; vertical-align: top;">
              <div style="text-align: right; font-size: 10px; color: #333; margin-bottom: 4px;">
                {{ currentBranch.city || currentBranch.name?.replace('Cabang ', '') || 'Duri' }}, {{ formatDateOnly(payment.payment_date || payment.created_at) }}
              </div>
              
              <table class="ttd-table">
                <tbody>
                  <tr>
                    <td style="width: 50%;">
                      <div style="font-size: 10px; margin-bottom: 2px;">Penerima (Supplier),</div>
                      <div style="height: 36px; display: flex; align-items: center; justify-content: center;">
                        <span style="font-size: 8px; color: #999;">( Cap / TTD )</span>
                      </div>
                      <div class="ttd-name">( {{ supplierSignerName }} )</div>
                    </td>
                    <td style="width: 50%;">
                      <div style="font-size: 10px; margin-bottom: 2px;">Kasir / Admin,</div>
                      <div style="margin: 1px auto; display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 36px;">
                        <div style="border: 1px solid #ccc; padding: 1px; background: #fff; display: inline-block;">
                          <QrcodeVue :value="signerQrValue" :size="34" level="M" render-as="svg" />
                        </div>
                        <div class="badge-digital">[TERTANDA DIGITAL]</div>
                      </div>
                      <div class="ttd-name">
                        ( {{ signerName }} )
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
#print-payable-receipt-section {
  display: none;
}
</style>
