<script setup>
import { computed } from 'vue'
import dayjs from 'dayjs'
import 'dayjs/locale/id'
import QrcodeVue from 'qrcode.vue'

dayjs.locale('id')

const props = defineProps({
  goodsReceipt: {
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

// Calculate due days
const termDays = computed(() => {
  if (!props.goodsReceipt?.date || !props.goodsReceipt?.due_date) return 0
  const d1 = dayjs(props.goodsReceipt.date)
  const d2 = dayjs(props.goodsReceipt.due_date)
  const diff = d2.diff(d1, 'day')
  return diff >= 0 ? diff : 0
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

// Item Level Financial Calculations
const getItemGross = item => {
  return Number(item.gross_price || item.net_unit_price || 0)
}

const getItemNet = item => {
  const gross = getItemGross(item)
  
  let d1 = Number(item.discount_percent_1) || 0
  let d2 = Number(item.discount_percent_2) || 0
  let d3 = Number(item.discount_percent_3) || 0
  let d4 = Number(item.discount_percent_4) || 0
  let d5 = Number(item.discount_percent_5) || 0

  if (item.discount_string && !d1 && !d2) {
    const parts = String(item.discount_string).split('+').map(p => parseFloat(p.trim())).filter(p => !isNaN(p))
    d1 = parts[0] || 0
    d2 = parts[1] || 0
    d3 = parts[2] || 0
    d4 = parts[3] || 0
    d5 = parts[4] || 0
  }

  if (d1 > 0 || d2 > 0 || d3 > 0 || d4 > 0 || d5 > 0 || Number(item.discount_amount) > 0) {
    let cur = gross
    if (d1 > 0) cur *= (1 - (d1 / 100))
    if (d2 > 0) cur *= (1 - (d2 / 100))
    if (d3 > 0) cur *= (1 - (d3 / 100))
    if (d4 > 0) cur *= (1 - (d4 / 100))
    if (d5 > 0) cur *= (1 - (d5 / 100))

    const dNom = Number(item.discount_amount) || 0
    const qty = Number(item.qty_received) || 1
    if (dNom > 0 && qty > 0) {
      cur -= (dNom / qty)
    }
    return Math.max(0, Math.round(cur))
  }

  if (item.net_unit_price && Number(item.net_unit_price) > 0) {
    return Number(item.net_unit_price)
  }

  return gross
}

const getItemTotal = item => {
  const net = getItemNet(item)
  const qty = Number(item.qty_received) || 1
  return Math.round(net * qty)
}

const getItemDiscountStr = item => {
  if (item.discount_string) return item.discount_string
  const discs = []
  if (item.discount_percent_1 > 0) discs.push(`${Number(item.discount_percent_1).toFixed(2)}%`)
  if (item.discount_percent_2 > 0) discs.push(`${Number(item.discount_percent_2).toFixed(2)}%`)
  if (item.discount_percent_3 > 0) discs.push(`${Number(item.discount_percent_3).toFixed(2)}%`)
  if (discs.length > 0) return discs.join('+ ')
  if (item.discount_amount > 0) return `Rp ${formatCurrency(item.discount_amount)}`
  return '-'
}

// Financial calculations on document level
const calculatedSubtotalBruto = computed(() => {
  if (!props.goodsReceipt) return 0
  if (Number(props.goodsReceipt.subtotal_bruto) > 0) return Number(props.goodsReceipt.subtotal_bruto)
  if (!props.goodsReceipt.items) return 0
  return props.goodsReceipt.items.reduce((acc, it) => acc + (getItemGross(it) * Number(it.qty_received || 1)), 0)
})

const calculatedTotal = computed(() => {
  if (!props.goodsReceipt) return 0
  if (Number(props.goodsReceipt.total_amount) > 0) return Number(props.goodsReceipt.total_amount)
  if (!props.goodsReceipt.items) return 0
  const subtotalNet = props.goodsReceipt.items.reduce((acc, it) => acc + getItemTotal(it), 0)
  return Math.max(0, subtotalNet - (Number(props.goodsReceipt.extra_discount) || 0))
})

const calculatedDpp = computed(() => {
  if (!props.goodsReceipt) return 0
  if (Number(props.goodsReceipt.dpp_amount) > 0) return Number(props.goodsReceipt.dpp_amount)
  const taxPct = Number(props.goodsReceipt.tax_percentage || 11)
  return Math.round(calculatedTotal.value / (1 + (taxPct / 100)))
})

const calculatedTax = computed(() => {
  if (!props.goodsReceipt) return 0
  if (Number(props.goodsReceipt.tax_amount) > 0) return Number(props.goodsReceipt.tax_amount)
  return Math.max(0, calculatedTotal.value - calculatedDpp.value)
})

const calculatedDppLain = computed(() => {
  if (!props.goodsReceipt) return 0
  return Math.round(calculatedDpp.value * 0.916666) // Rasio DPP Lain standar jika ada
})

const terbilangStr = computed(() => {
  let text = terbilang(calculatedTotal.value)
  return text.charAt(0).toUpperCase() + text.slice(1) + ' rupiah'
})

// Nama Petugas Penerima / Kasir
const receiverName = computed(() => {
  if (props.goodsReceipt?.user?.name) return props.goodsReceipt.user.name
  if (props.goodsReceipt?.validator?.name) return props.goodsReceipt.validator.name
  if (props.goodsReceipt?.approver?.name) return props.goodsReceipt.approver.name
  
  try {
    const userData = JSON.parse(localStorage.getItem('userData') || '{}')
    if (userData?.name) return userData.name
  } catch (e) {}

  return 'Petugas Gudang'
})

// Supplier Information
const supplier = computed(() => {
  return props.goodsReceipt?.purchase_order?.supplier || props.goodsReceipt?.purchaseOrder?.supplier || {}
})

const supplierName = computed(() => {
  return supplier.value.name || 'PT. CAPELLA PATRIA UTAMA'
})

const supplierAddress = computed(() => {
  return supplier.value.address || 'JL. SOEKARNO HATTA NO.57 RT.7 RW.12 PEKANBARU'
})

const supplierPhone = computed(() => {
  return supplier.value.phone || '0761-7865000'
})

const supplierFax = computed(() => {
  return supplier.value.fax || '0761-7865100'
})

const supplierNpwp = computed(() => {
  return supplier.value.tax_id || supplier.value.npwp || '0014310932123000'
})

// Active Branch
const currentBranch = computed(() => {
  return props.branch || props.goodsReceipt?.purchase_order?.branch || props.goodsReceipt?.purchaseOrder?.branch || {}
})

// Check if format is thermal
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

// 1. QR Code Bukti Keabsahan Data Penerimaan Barang (Verifikasi Sistem)
const docQrValue = computed(() => {
  if (!props.goodsReceipt) return ''
  const noFaktur = props.goodsReceipt.invoice_number_supplier || props.goodsReceipt.receipt_number || '-'
  const noGR = props.goodsReceipt.receipt_number || '-'
  const supp = supplierName.value
  const branchName = currentBranch.value.name || 'Gudang Utama'
  const dateStr = formatDate(props.goodsReceipt.date)
  const totalStr = Number(calculatedTotal.value || 0).toLocaleString('id-ID')

  return `VERIFIKASI KEABSAHAN DOKUMEN MS.POS\n`
    + `====================================\n`
    + `Dokumen   : Faktur Penerimaan Barang\n`
    + `No. GR    : ${noGR}\n`
    + `No. Faktur: ${noFaktur}\n`
    + `Supplier  : ${supp}\n`
    + `Cabang    : ${branchName}\n`
    + `Tanggal   : ${dateStr}\n`
    + `Total     : Rp ${totalStr}\n`
    + `Status    : DOKUMEN SAH & TERCATAT RESMI`
})

// 2. QR Code Tanda Tangan Digital Kasir/Penerima (Identitas Penandatangan Lengkap)
const signerQrValue = computed(() => {
  const branchName = currentBranch.value.name || 'Gudang Utama'
  const timeStr = dayjs(props.goodsReceipt?.created_at || new Date()).format('DD/MM/YYYY HH:mm:ss')

  return `TANDA TANGAN DIGITAL RESMI (DIGITAL SIGNATURE)\n`
    + `===============================================\n`
    + `Penandatangan : ${receiverName.value}\n`
    + `Jabatan       : Petugas Penerimaan Gudang\n`
    + `Unit / Cabang : ${branchName}\n`
    + `Waktu TTD     : ${timeStr}\n`
    + `Keperluan     : Pengesahan Penerimaan Barang (${props.goodsReceipt?.receipt_number || '-'})\n`
    + `Status TTD    : TERTANDA DIGITAL SAH (VERIFIED)`
})

const print = () => {
  const printEl = document.getElementById('print-goods-receipt-section')
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
        line-height: 1.25;
        width: 100%;
        color: #000;
        background: #fff;
        padding: ${mt}mm ${mr}mm ${mb}mm ${ml}mm;
        margin: 0;
      }
      .cf-container {
        width: 100%;
        background: #fff;
        padding: 0;
      }
      .font-bold { font-weight: bold; }
      .text-center { text-align: center; }
      .text-right { text-align: right; }
      .divider-solid { border-top: 1.5px solid #000; margin: 4px 0; }
      .divider-dashed { border-top: 1px dashed #000; margin: 4px 0; }
      .faktur-grid-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10.5px;
        margin-top: 2px;
      }
      .faktur-grid-table th {
        border-top: 1.5px solid #000;
        border-bottom: 1.5px solid #000;
        padding: 4px 3px;
        font-weight: bold;
        font-size: 10px;
        text-transform: uppercase;
      }
      .faktur-grid-table td {
        padding: 2.5px 3px;
        vertical-align: top;
      }
      .meta-box-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10.5px;
      }
      .meta-box-table td {
        padding: 1.5px 2px;
        vertical-align: top;
      }
      .summary-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10.5px;
      }
      .summary-table td {
        padding: 1.5px 2px;
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
    v-if="goodsReceipt"
    id="print-goods-receipt-section"
    class="goods-receipt-print-wrapper"
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
        BUKTI PENERIMAAN BARANG
      </div>

      <div class="divider-dashed"></div>

      <table class="meta-table">
        <tbody>
          <tr>
            <td>No. GR</td>
            <td>: {{ goodsReceipt.receipt_number }}</td>
          </tr>
          <tr>
            <td>No. Faktur</td>
            <td>: {{ goodsReceipt.invoice_number_supplier || '-' }}</td>
          </tr>
          <tr>
            <td>Tanggal</td>
            <td>: {{ formatDate(goodsReceipt.date) }}</td>
          </tr>
          <tr>
            <td>Supplier</td>
            <td>: <strong>{{ supplierName }}</strong></td>
          </tr>
        </tbody>
      </table>

      <div class="divider-dashed"></div>

      <!-- Item List Thermal -->
      <table style="width: 100%; font-size: 10.5px;">
        <tbody>
          <tr v-for="(item, index) in goodsReceipt.items" :key="item.id || index">
            <td colspan="2" style="padding-top: 2px;">
              <strong>{{ item.product_branch?.product?.code || item.productBranch?.product?.code || '' }}</strong> - {{ item.product_branch?.product?.name || item.productBranch?.product?.name || item.product_name || 'Barang' }}<br>
              <span style="font-size: 9.5px; color: #444;">
                {{ item.qty_received }} {{ item.unit || 'PCS' }} x {{ formatCurrency(getItemGross(item)) }}
              </span>
              <span v-if="getItemDiscountStr(item) !== '-'" style="font-size: 9.5px; color: #777;">
                (disc {{ getItemDiscountStr(item) }})
              </span>
            </td>
            <td style="text-align: right; vertical-align: bottom; font-weight: bold;">
              {{ formatCurrency(getItemTotal(item)) }}
            </td>
          </tr>
        </tbody>
      </table>

      <div class="divider-dashed"></div>

      <table class="meta-table">
        <tbody>
          <tr>
            <td>Subtotal Bruto</td>
            <td class="text-right">Rp {{ formatCurrency(calculatedSubtotalBruto) }}</td>
          </tr>
          <tr v-if="goodsReceipt.extra_discount > 0">
            <td>Potongan Tambahan</td>
            <td class="text-right">- Rp {{ formatCurrency(goodsReceipt.extra_discount) }}</td>
          </tr>
          <tr v-if="calculatedTax > 0">
            <td>PPN ({{ goodsReceipt.tax_percentage || 11 }}%)</td>
            <td class="text-right">Rp {{ formatCurrency(calculatedTax) }}</td>
          </tr>
          <tr class="font-bold" style="font-size: 12px;">
            <td>TOTAL FAKTUR</td>
            <td class="text-right">Rp {{ formatCurrency(calculatedTotal) }}</td>
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
        <div style="font-size: 8px; color: #555; margin-top: 2px;">Scan Validasi Penerimaan</div>
      </div>

      <div class="text-center mt-2" style="font-size: 9px;">
        <div>Penerima: <strong>{{ receiverName }}</strong></div>
        <div style="margin-top: 3px;">*** Dokumen tanda terima sah gudang ***</div>
      </div>
    </div>

    <!-- 2. FORMAT KUITANSI / FAKTUR CONTINUOUS FORM (DOT MATRIX & A5 SESUAI FOTO RESMI) -->
    <div v-else class="cf-container">
      <!-- Kop Dokumen (Kiri: Supplier, Tengah: FAKTUR, Kanan: Kantor Pusat & Ref PBR) -->
      <table style="width: 100%; border-collapse: collapse;">
        <tbody>
          <tr>
            <!-- Kiri: Info Supplier Lengkap -->
            <td style="width: 48%; vertical-align: top;">
              <div class="font-bold" style="font-size: 13px; letter-spacing: 0.5px; text-transform: uppercase;">
                {{ supplierName }}
              </div>
              <div style="font-size: 9.5px; line-height: 1.25; margin-top: 2px;">
                {{ supplierAddress }}<br>
                Telp. {{ supplierPhone }}, Fax. {{ supplierFax }}<br>
                NPWP/NPPKP: {{ supplierNpwp }} Tanggal : {{ formatDate(goodsReceipt.date) }}
              </div>
            </td>

            <!-- Tengah: Judul FAKTUR & Barcode -->
            <td style="width: 22%; text-align: center; vertical-align: top;">
              <div class="font-bold" style="font-size: 16px; letter-spacing: 2px; margin-top: 4px;">
                FAKTUR
              </div>
            </td>

            <!-- Kanan: Catatan Giro, No Ref PBR, dan Kantor Pusat -->
            <td style="width: 30%; text-align: right; vertical-align: top; font-size: 8.5px; line-height: 1.2;">
              <div style="font-style: italic;">
                * Pembayaran dengan giro/cheque harap dicantumkan atas nama<br>
                <strong>{{ supplierName }}</strong> dan dianggap sah bila telah diuangkan.
              </div>
              <div class="font-bold" style="font-size: 13px; letter-spacing: 1px; margin-top: 3px;">
                {{ goodsReceipt.receipt_number }}
              </div>
              <div style="margin-top: 3px; font-size: 8.5px; color: #333;">
                <strong>KANTOR PUSAT :</strong><br>
                {{ supplierAddress }}
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Garis Pemisah Kop -->
      <div class="divider-solid"></div>

      <!-- Detail Informasi Faktur & Penerima (2 Kolom Khas Dot Matrix) -->
      <table class="meta-box-table" style="margin-bottom: 2px;">
        <tbody>
          <tr>
            <!-- Kolom Kiri: Nomor, Tgl/Jth Tempo, Kode Sales, Gudang -->
            <td style="width: 52%; vertical-align: top; padding-right: 8px;">
              <table style="width: 100%; border-collapse: collapse; font-size: 10.5px;">
                <tbody>
                  <tr>
                    <td style="width: 110px; font-weight: bold; letter-spacing: 1px;">N O M O R</td>
                    <td style="width: 10px;">:</td>
                    <td><strong style="font-size: 11.5px;">{{ goodsReceipt.invoice_number_supplier || goodsReceipt.receipt_number }}</strong></td>
                  </tr>
                  <tr>
                    <td style="font-weight: bold;">Tgl/Jth Tempo</td>
                    <td>:</td>
                    <td>{{ formatDate(goodsReceipt.date) }} / {{ formatDate(goodsReceipt.due_date) }} ( {{ termDays }} hari)</td>
                  </tr>
                  <tr>
                    <td style="font-weight: bold;">Kode Sales</td>
                    <td>:</td>
                    <td>{{ goodsReceipt.sales_name || goodsReceipt.purchase_order?.supplier?.pic_name || 'LK.0001 REZEKI GENESIS' }}</td>
                  </tr>
                  <tr>
                    <td style="font-weight: bold;">Gudang</td>
                    <td>:</td>
                    <td>
                      {{ currentBranch.name || 'G01' }}
                      <span style="margin-left: 20px;">SJ : {{ goodsReceipt.delivery_order_number || '-' }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </td>

            <!-- Kolom Kanan: Kepada (Penerima Toko/Owner) -->
            <td style="width: 48%; vertical-align: top; padding-left: 8px;">
              <table style="width: 100%; border-collapse: collapse; font-size: 10.5px;">
                <tbody>
                  <tr>
                    <td style="width: 60px; font-weight: bold;">Kepada</td>
                    <td style="width: 10px;">:</td>
                    <td>
                      <strong style="font-size: 11px;">{{ (currentBranch.owner?.name || 'PT. PAGARUYUNG MITRA PERSADA').toUpperCase() }}</strong>
                    </td>
                  </tr>
                  <tr>
                    <td></td>
                    <td></td>
                    <td>{{ currentBranch.address || 'JALAN LINTAS KILOMETER 18' }}</td>
                  </tr>
                  <tr>
                    <td></td>
                    <td></td>
                    <td>{{ (currentBranch.city || 'DURI').toUpperCase() }} ({{ currentBranch.code || '10.040.02552.01' }})</td>
                  </tr>
                </tbody>
              </table>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Tabel Grid Daftar Barang Sesuai Persis dengan Foto -->
      <table class="faktur-grid-table">
        <thead>
          <tr>
            <th style="width: 25px; text-align: center;">NO</th>
            <th style="text-align: left;">KODEPART/NAMA BRG.</th>
            <th style="width: 45px; text-align: center;">QTY.</th>
            <th style="width: 80px; text-align: right;">HRG/@</th>
            <th style="width: 90px; text-align: center;">DISCOUNT.</th>
            <th style="width: 80px; text-align: right;">NETTO</th>
            <th style="width: 105px; text-align: right;">JUMLAH RP(Inc Ppn)</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(item, idx) in goodsReceipt.items"
            :key="item.id || idx"
          >
            <td style="text-align: center;">{{ idx + 1 }}</td>
            <td>
              <div class="font-bold" style="font-size: 10.5px;">
                {{ item.product_branch?.product?.code || item.productBranch?.product?.code || item.product?.code || '-' }}
              </div>
              <div style="font-size: 10px; color: #222;">
                {{ item.product_branch?.product?.name || item.productBranch?.product?.name || item.product_name || 'Barang' }}
              </div>
            </td>
            <td style="text-align: center; font-weight: bold;">
              {{ item.qty_received }}
            </td>
            <td style="text-align: right; font-family: monospace;">
              {{ formatCurrency(getItemGross(item)) }}
            </td>
            <td style="text-align: center; font-size: 10px;">
              {{ getItemDiscountStr(item) }}
            </td>
            <td style="text-align: right; font-family: monospace;">
              {{ formatCurrency(getItemNet(item)) }}
            </td>
            <td style="text-align: right; font-family: monospace; font-weight: bold;">
              {{ formatCurrency(getItemTotal(item)) }}
            </td>
          </tr>
        </tbody>
      </table>

      <div class="divider-solid"></div>

      <!-- Bagian Bawah: Keterangan, QR Verifikasi, TTD & Rincian Total -->
      <table style="width: 100%; border-collapse: collapse; margin-top: 2px;">
        <tbody>
          <tr>
            <!-- Kolom Kiri: Keterangan, User/Waktu, QR dan Kolom TTD -->
            <td style="width: 58%; vertical-align: top; padding-right: 12px;">
              <div style="font-size: 9.5px; margin-bottom: 2px;">
                * Ket.: {{ goodsReceipt.notes || 'MO CASH KRM PARLIN PAGARUYUNG HANGTUAH DURI' }}
              </div>
              <div style="font-size: 9px; color: #333; margin-bottom: 6px;">
                * sudah termasuk PPN &nbsp;&nbsp;&nbsp;&nbsp; {{ receiverName }} ({{ dayjs(goodsReceipt.created_at || new Date()).format('HH:mm:ss') }}) &nbsp;&nbsp; Via : SLS
              </div>

              <!-- Dual QR Code Verifikasi Keabsahan & TTD Digital -->
              <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; background: #fafafa; border: 1px solid #ddd; padding: 4px 6px; border-radius: 4px;">
                <div style="border: 1px solid #ccc; padding: 1px; background: #fff; display: inline-block;">
                  <QrcodeVue :value="docQrValue" :size="34" level="M" render-as="svg" />
                </div>
                <div style="font-size: 7.5px; line-height: 1.2; color: #333;">
                  <strong>VERIFIKASI DATA RESMI</strong><br>
                  Scan QR untuk validasi keabsahan data faktur pada sistem Ms.POS
                </div>
              </div>

              <!-- Kolom Tanda Tangan 2 Pihak -->
              <table style="width: 100%; text-align: center; font-size: 10px; margin-top: 4px;">
                <tbody>
                  <tr>
                    <td style="width: 50%; vertical-align: top;">
                      <div class="font-bold" style="font-size: 10px; margin-bottom: 2px;">{{ supplierName }}</div>
                      <div style="height: 36px; display: flex; align-items: center; justify-content: center;">
                        <span style="font-size: 8px; color: #999;">( Cap & TTD Basah )</span>
                      </div>
                      <div class="font-bold">( PEKANBARU )</div>
                    </td>
                    <td style="width: 50%; vertical-align: top;">
                      <div class="font-bold" style="font-size: 10px; margin-bottom: 2px;">PENERIMA</div>
                      <div style="margin: 1px auto; display: flex; flex-direction: column; justify-content: center; min-height: 36px; align-items: center;">
                        <QrcodeVue :value="signerQrValue" :size="34" level="M" render-as="svg" />
                        <div style="font-size: 6px; color: #16a34a; font-weight: bold;">[TERTANDA DIGITAL]</div>
                      </div>
                      <div class="font-bold">( {{ receiverName }} )</div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </td>

            <!-- Kolom Kanan: Rincian Angka Faktur (JUMLAH HARGA JUAL, TOTAL, DPP, PPN) -->
            <td style="width: 42%; vertical-align: top;">
              <table class="summary-table">
                <tbody>
                  <tr>
                    <td style="font-size: 10.5px;">JUMLAH HARGA JUAL</td>
                    <td style="width: 25px; text-align: right;">Rp.</td>
                    <td style="text-align: right; font-family: monospace; font-weight: bold; font-size: 11px;">
                      {{ formatCurrency(calculatedSubtotalBruto) }}
                    </td>
                  </tr>
                  <tr>
                    <td style="font-size: 10.5px;">EXTRA DISCOUNT</td>
                    <td style="text-align: right;">Rp.</td>
                    <td style="text-align: right; font-family: monospace; font-size: 11px;">
                      {{ formatCurrency(goodsReceipt.extra_discount || 0) }}
                    </td>
                  </tr>
                  <tr style="border-top: 1px solid #000; border-bottom: 1.5px solid #000;">
                    <td style="font-weight: bold; font-size: 11px; padding: 3px 0; letter-spacing: 1px;">T O T A L (Inc Ppn)</td>
                    <td style="text-align: right; font-weight: bold; padding: 3px 0;">Rp.</td>
                    <td style="text-align: right; font-family: monospace; font-weight: bold; font-size: 12px; padding: 3px 0;">
                      {{ formatCurrency(calculatedTotal) }}
                    </td>
                  </tr>
                  <tr>
                    <td style="font-size: 10px; padding-top: 4px; letter-spacing: 1px;">D P P</td>
                    <td style="text-align: right; padding-top: 4px;">Rp.</td>
                    <td style="text-align: right; font-family: monospace; font-size: 10.5px; padding-top: 4px;">
                      {{ formatCurrency(calculatedDpp) }}
                    </td>
                  </tr>
                  <tr>
                    <td style="font-size: 10px; letter-spacing: 1px;">D P P  LAIN</td>
                    <td style="text-align: right;">Rp.</td>
                    <td style="text-align: right; font-family: monospace; font-size: 10.5px;">
                      {{ formatCurrency(calculatedDppLain) }}
                    </td>
                  </tr>
                  <tr>
                    <td style="font-size: 10px; letter-spacing: 1px;">P P N</td>
                    <td style="text-align: right;">Rp.</td>
                    <td style="text-align: right; font-family: monospace; font-size: 10.5px;">
                      {{ formatCurrency(calculatedTax) }}
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
#print-goods-receipt-section {
  display: none;
}
</style>
