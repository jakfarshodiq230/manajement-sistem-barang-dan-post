<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { $api } from '@/utils/api'

// ─── State ────────────────────────────────────────────────────
const isLoading    = ref(true)
const isLoadingDetail = ref(false)
const selectedYear = ref(new Date().getFullYear())
const yearOptions  = ref([])
const rekapData    = ref({ summary: {}, months: [] })

const detailDialog   = ref(false)
const detailMonth    = ref(null)
const detailData     = ref([])

// Pagination (for VDataTableServer)
const itemsPerPage = ref(-1)
const page = ref(1)

// ─── Fetch tahunan ────────────────────────────────────────────
const fetchRekap = async () => {
  isLoading.value = true
  try {
    const res = await $api('/apps/rekap/tahunan', { query: { year: selectedYear.value } })
    if (res.success) {
      rekapData.value  = res.data
      yearOptions.value = res.data.years.reverse()
    }
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

// ─── Fetch detail bulanan ─────────────────────────────────────
const openDetail = async row => {
  if (row.is_future) return
  detailMonth.value    = row
  detailDialog.value   = true
  isLoadingDetail.value = true
  try {
    const res = await $api('/apps/rekap/bulanan', {
      query: { year: selectedYear.value, month: row.bulan_num },
    })

    if (res.success) detailData.value = res.data.days
  } catch (e) {
    console.error(e)
  } finally {
    isLoadingDetail.value = false
  }
}

const isPdfLoading = ref(false)

onMounted(fetchRekap)

const downloadPdf = async () => {
  if (isPdfLoading.value) return
  isPdfLoading.value = true
  try {
    const res = await $api('/apps/rekap/tahunan/pdf', {
      params: { year: selectedYear.value },
      responseType: 'blob',
    })
    
    // Create download link from blob
    const url = window.URL.createObjectURL(new Blob([res]))
    const link = document.createElement('a')

    link.href = url
    link.setAttribute('download', `Rekap_Tahunan_${selectedYear.value}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Error downloading PDF:', error)
  } finally {
    isPdfLoading.value = false
  }
}

watch(selectedYear, fetchRekap)

// ─── Helpers ──────────────────────────────────────────────────
const fmt = v => {
  if (v === null || v === undefined) return '—'
  
  return new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', minimumFractionDigits: 0,
  }).format(v)
}

const fmtNum = v => {
  if (v === null || v === undefined) return '—'
  
  return new Intl.NumberFormat('id-ID').format(v)
}

const labaColor = v => {
  if (v === null) return ''
  
  return v > 0 ? 'text-success' : v < 0 ? 'text-error' : 'text-medium-emphasis'
}

const rowClass = row => {
  if (row.is_future)  return 'opacity-40'
  if (row.is_current) return 'bg-primary-lighten-5 font-weight-bold'
  
  return ''
}

// Month table headers
const headers = [
  { title: 'Bulan',            key: 'bulan',            width: 130 },
  { title: 'Transaksi',        key: 'jumlah_transaksi', align: 'center' },
  { title: 'Omset',            key: 'omset',            align: 'end' },
  { title: 'Modal (COGS)',     key: 'modal_cogs',       align: 'end' },
  { title: 'Laba Bersih',      key: 'laba_bersih',      align: 'end' },
  { title: 'Margin',           key: 'margin',           align: 'center' },
  { title: 'Selisih Kas',      key: 'selisih_kas',      align: 'end' },
  { title: 'Stok Masuk',       key: 'stok_masuk',       align: 'center' },
  { title: 'Stok Keluar',      key: 'stok_keluar',      align: 'center' },
  { title: 'Kumulatif Laba',   key: 'kumulatif_laba',   align: 'end' },
  { title: '',                 key: 'actions',          sortable: false, width: 60 },
]

// Detail daily headers
const detailHeaders = [
  { title: 'Tanggal',    key: 'tanggal' },
  { title: 'Hari',       key: 'hari' },
  { title: 'Transaksi',  key: 'jumlah_transaksi', align: 'center' },
  { title: 'Omset',      key: 'omset',            align: 'end' },
  { title: 'Laba',       key: 'laba',             align: 'end' },
  { title: 'Stok Masuk', key: 'stok_masuk',       align: 'center' },
  { title: 'Stok Keluar', key: 'stok_keluar',      align: 'center' },
  { title: 'Selisih Kas', key: 'selisih_kas',      align: 'end' },
]

const marginCalc = row => {
  if (row.omset === null || row.omset === 0) return null
  
  return ((row.laba_bersih / row.omset) * 100).toFixed(1)
}
</script>

<template>
  <section>
    <!-- ── Header / Filter ───────────────────────── -->
    <div class="d-flex align-center justify-space-between mb-4 mt-2">
      <div>
        <h2 class="text-h4 mb-0 font-weight-bold">
          📒 Rekap Tahunan
        </h2>
        <p class="text-body-1 mb-0 text-disabled mt-1">
          Laporan rekap keuangan dan operasional per bulan, seperti buku bank
        </p>
      </div>
      
      <div class="d-flex align-center gap-3">
        <VSelect
          v-model="selectedYear"
          :items="yearOptions"
          label="Pilih Tahun"
          density="compact"
          variant="outlined"
          style="min-width: 130px; max-width: 160px"
          hide-details
        />
        <VBtn
          color="error"
          variant="tonal"
          prepend-icon="ri-file-pdf-line"
          :loading="isPdfLoading"
          @click="downloadPdf"
        >
          PDF
        </VBtn>
      </div>
    </div>
    <VRow>

    <!-- ── Summary Cards ─────────────────────────── -->
    <VCol
      v-if="!isLoading"
      cols="12"
      md="3"
    >
      <VCard>
        <VCardText>
          <p class="text-sm text-medium-emphasis mb-1">
            Total Transaksi
          </p>
          <h4 class="text-h4 font-weight-bold">
            {{ fmtNum(rekapData.summary.total_transaksi) }}
          </h4>
        </VCardText>
      </VCard>
    </VCol>
    <VCol
      v-if="!isLoading"
      cols="12"
      md="3"
    >
      <VCard>
        <VCardText>
          <p class="text-sm text-medium-emphasis mb-1">
            Total Omset
          </p>
          <h4 class="text-h4 font-weight-bold text-primary">
            {{ fmt(rekapData.summary.total_omset) }}
          </h4>
        </VCardText>
      </VCard>
    </VCol>
    <VCol
      v-if="!isLoading"
      cols="12"
      md="3"
    >
      <VCard>
        <VCardText>
          <p class="text-sm text-medium-emphasis mb-1">
            Laba Bersih
          </p>
          <h4 class="text-h4 font-weight-bold text-success">
            {{ fmt(rekapData.summary.total_laba) }}
          </h4>
        </VCardText>
      </VCard>
    </VCol>
    <VCol
      v-if="!isLoading"
      cols="12"
      md="3"
    >
      <VCard>
        <VCardText>
          <p class="text-sm text-medium-emphasis mb-1">
            Margin Rata-rata
          </p>
          <h4 class="text-h4 font-weight-bold text-info">
            {{ rekapData.summary.margin ?? 0 }}%
          </h4>
        </VCardText>
      </VCard>
    </VCol>

    <!-- ── Main Table (12 bulan) ──────────────────── -->
    <VCol cols="12">
      <VCard :title="`Rincian Per Bulan — Tahun ${selectedYear}`">
        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          v-model:page="page"
          :items-length="rekapData.months.length"
          :headers="headers"
          :items="rekapData.months"
          :loading="isLoading"
          item-value="bulan_num"
          :items-per-page="-1"
          hide-default-footer
          density="comfortable"
          class="text-no-wrap rekap-table"
        >
          <!-- Bulan -->
          <template #item.bulan="{ item }">
            <div class="d-flex align-center gap-2">
              <VBadge
                v-if="item.is_current"
                color="primary"
                content="Bulan Ini"
                inline
              />
              <span :class="item.is_future ? 'text-medium-emphasis' : 'font-weight-medium'">
                {{ item.bulan }}
              </span>
            </div>
          </template>

          <!-- Transaksi -->
          <template #item.jumlah_transaksi="{ item }">
            <span :class="item.is_future ? 'text-medium-emphasis' : ''">
              {{ item.is_future ? '—' : fmtNum(item.jumlah_transaksi) }}
            </span>
          </template>

          <!-- Omset -->
          <template #item.omset="{ item }">
            {{ item.is_future ? '—' : fmt(item.omset) }}
          </template>

          <!-- Modal -->
          <template #item.modal_cogs="{ item }">
            {{ item.is_future ? '—' : fmt(item.modal_cogs) }}
          </template>

          <!-- Laba Bersih -->
          <template #item.laba_bersih="{ item }">
            <span :class="labaColor(item.laba_bersih)">
              {{ item.is_future ? '—' : fmt(item.laba_bersih) }}
            </span>
          </template>

          <!-- Margin -->
          <template #item.margin="{ item }">
            <VChip
              v-if="!item.is_future && marginCalc(item) !== null"
              :color="parseFloat(marginCalc(item)) >= 20 ? 'success' : parseFloat(marginCalc(item)) >= 10 ? 'warning' : 'error'"
              size="x-small"
            >
              {{ marginCalc(item) }}%
            </VChip>
            <span
              v-else
              class="text-medium-emphasis"
            >—</span>
          </template>

          <!-- Selisih Kas -->
          <template #item.selisih_kas="{ item }">
            <span :class="labaColor(item.selisih_kas)">
              {{ item.is_future ? '—' : fmt(item.selisih_kas) }}
            </span>
          </template>

          <!-- Stok Masuk -->
          <template #item.stok_masuk="{ item }">
            <span class="text-success">
              {{ item.is_future ? '—' : ('+' + fmtNum(item.stok_masuk)) }}
            </span>
          </template>

          <!-- Stok Keluar -->
          <template #item.stok_keluar="{ item }">
            <span class="text-error">
              {{ item.is_future ? '—' : ('-' + fmtNum(item.stok_keluar)) }}
            </span>
          </template>

          <!-- Kumulatif Laba (YTD) -->
          <template #item.kumulatif_laba="{ item }">
            <span
              class="font-weight-bold"
              :class="labaColor(item.kumulatif_laba)"
            >
              {{ item.is_future ? '—' : fmt(item.kumulatif_laba) }}
            </span>
          </template>

          <!-- Actions -->
          <template #item.actions="{ item }">
            <VBtn
              v-if="!item.is_future"
              icon="ri-eye-line"
              size="small"
              variant="text"
              color="primary"
              @click="openDetail(item)"
            />
          </template>

          <!-- Footer / Total Row -->
          <template #bottom>
            <div class="pa-4 border-t d-flex flex-wrap gap-6 text-body-2">
              <span><strong>Total Transaksi:</strong> {{ fmtNum(rekapData.summary.total_transaksi) }}</span>
              <span><strong>Total Omset:</strong> {{ fmt(rekapData.summary.total_omset) }}</span>
              <span><strong>Total Laba:</strong>
                <span :class="labaColor(rekapData.summary.total_laba)"> {{ fmt(rekapData.summary.total_laba) }}</span>
              </span>
              <span><strong>Margin:</strong> {{ rekapData.summary.margin ?? 0 }}%</span>
            </div>
          </template>
        </VDataTableServer>
      </VCard>
    </VCol>

    <!-- ── Catatan Rumus ──────────────────────────── -->
    <VCol cols="12">
      <VCard title="📐 Catatan Rumus & Cara Perolehan Angka">
        <VCardText>
          <VAlert
            color="info"
            variant="tonal"
            icon="ri-information-line"
            class="mb-5"
          >
            Semua angka di halaman ini hanya dihitung dari transaksi penjualan dengan status
            <strong>Selesai (Completed)</strong>. Transaksi draf, tertunda, atau dibatalkan
            <strong>tidak</strong> dimasukkan ke dalam perhitungan.
          </VAlert>

          <VRow>
            <!-- Kolom 1 -->
            <VCol
              cols="12"
              md="6"
            >
              <p class="text-subtitle-2 font-weight-bold mb-2 text-primary">
                💰 Penjualan & Keuangan
              </p>
              <VList
                lines="two"
                density="compact"
              >
                <VListItem>
                  <template #title>
                    <strong>Omset</strong>
                  </template>
                  <template #subtitle>
                    = Jumlah <code>total_amount</code> dari semua transaksi yang selesai pada bulan tersebut.<br>
                    <span class="text-caption text-medium-emphasis">Ini adalah total harga jual kotor sebelum dikurangi modal.</span>
                  </template>
                </VListItem>
                <VDivider class="my-1" />
                <VListItem>
                  <template #title>
                    <strong>Modal / COGS</strong> (Harga Pokok Penjualan)
                  </template>
                  <template #subtitle>
                    = Jumlah <code>cost_price × qty</code> dari tabel <code>sale_items</code> untuk setiap transaksi selesai.<br>
                    <span class="text-caption text-medium-emphasis">Ini adalah total nilai beli barang yang berhasil terjual.</span>
                  </template>
                </VListItem>
                <VDivider class="my-1" />
                <VListItem>
                  <template #title>
                    <strong>Laba Bersih</strong>
                  </template>
                  <template #subtitle>
                    = <code>Omset</code> − <code>Modal/COGS</code><br>
                    <span class="text-caption text-medium-emphasis">Keuntungan kotor dari selisih harga jual dan harga modal.</span>
                  </template>
                </VListItem>
                <VDivider class="my-1" />
                <VListItem>
                  <template #title>
                    <strong>Margin (%)</strong>
                  </template>
                  <template #subtitle>
                    = <code>(Laba Bersih ÷ Omset) × 100%</code><br>
                    <span class="text-caption text-medium-emphasis">
                      🟢 ≥ 20% = Sangat baik &nbsp;|&nbsp; 🟡 ≥ 10% = Cukup &nbsp;|&nbsp; 🔴 &lt; 10% = Perlu perhatian
                    </span>
                  </template>
                </VListItem>
                <VDivider class="my-1" />
                <VListItem>
                  <template #title>
                    <strong>Kumulatif Laba (YTD)</strong>
                  </template>
                  <template #subtitle>
                    = Akumulasi total laba bersih dari bulan Januari hingga bulan yang bersangkutan.<br>
                    <span class="text-caption text-medium-emphasis">Bulan mendatang tidak ikut dihitung. Berguna untuk melihat tren laba sepanjang tahun (Year-to-Date).</span>
                  </template>
                </VListItem>
              </VList>
            </VCol>

            <!-- Kolom 2 -->
            <VCol
              cols="12"
              md="6"
            >
              <p class="text-subtitle-2 font-weight-bold mb-2 text-success">
                📦 Stok & Kas
              </p>
              <VList
                lines="two"
                density="compact"
              >
                <VListItem>
                  <template #title>
                    <strong>Stok Masuk (+)</strong>
                  </template>
                  <template #subtitle>
                    = Total <code>quantity</code> dari <code>stock_movements</code> dengan <code>type = 'in'</code> pada bulan tersebut.<br>
                    <span class="text-caption text-medium-emphasis">Mencakup penerimaan dari PO, transfer masuk, atau penambahan stok manual.</span>
                  </template>
                </VListItem>
                <VDivider class="my-1" />
                <VListItem>
                  <template #title>
                    <strong>Stok Keluar (-)</strong>
                  </template>
                  <template #subtitle>
                    = Total nilai absolut <code>|quantity|</code> dari <code>stock_movements</code> dengan tipe selain <code>'in'</code>.<br>
                    <span class="text-caption text-medium-emphasis">Mencakup penjualan, penyesuaian stok opname, retur, atau pengurangan manual.</span>
                  </template>
                </VListItem>
                <VDivider class="my-1" />
                <VListItem>
                  <template #title>
                    <strong>Selisih Kas</strong>
                  </template>
                  <template #subtitle>
                    = Jumlah kolom <code>variance</code> dari semua <code>cash_reconciliations</code> pada bulan tersebut.<br>
                    <span class="text-caption text-medium-emphasis">Positif = uang fisik lebih banyak dari sistem. Negatif = ada kekurangan kas.</span>
                  </template>
                </VListItem>
                <VDivider class="my-1" />
                <VListItem>
                  <template #title>
                    <strong>Bulan Mendatang</strong>
                  </template>
                  <template #subtitle>
                    Tampil redup dengan tanda <strong>—</strong> karena data belum tersedia.<br>
                    <span class="text-caption text-medium-emphasis">Rekap otomatis mengisi data begitu transaksi masuk di bulan tersebut.</span>
                  </template>
                </VListItem>
                <VDivider class="my-1" />
                <VListItem>
                  <template #title>
                    <strong>Detail Harian</strong> (ikon 👁)
                  </template>
                  <template #subtitle>
                    Klik ikon mata di baris bulan mana pun untuk melihat rincian per hari.<br>
                    <span class="text-caption text-medium-emphasis">Menampilkan breakdown transaksi, omset, laba, mutasi stok, dan selisih kas setiap hari dalam satu bulan.</span>
                  </template>
                </VListItem>
              </VList>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <!-- ── Detail Dialog (Harian per Bulan) ─────────── -->
  <VDialog
    v-model="detailDialog"
    max-width="900"
  >
    <VCard :title="`Detail Harian — ${detailMonth?.bulan} ${selectedYear}`">
      <VCardText>
        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          v-model:page="page"
          :items-length="detailData.length"
          :headers="detailHeaders"
          :items="detailData"
          :loading="isLoadingDetail"
          :items-per-page="-1"
          hide-default-footer
          density="compact"
          class="text-no-wrap"
        >
          <template #item.omset="{ item }">
            {{ item.is_future ? '—' : fmt(item.omset) }}
          </template>
          <template #item.laba="{ item }">
            <span :class="labaColor(item.laba)">
              {{ item.is_future ? '—' : fmt(item.laba) }}
            </span>
          </template>
          <template #item.stok_masuk="{ item }">
            <span class="text-success">
              {{ item.is_future ? '—' : ('+' + fmtNum(item.stok_masuk)) }}
            </span>
          </template>
          <template #item.stok_keluar="{ item }">
            <span class="text-error">
              {{ item.is_future ? '—' : ('-' + fmtNum(item.stok_keluar)) }}
            </span>
          </template>
          <template #item.selisih_kas="{ item }">
            <span :class="labaColor(item.selisih_kas)">
              {{ item.is_future ? '—' : fmt(item.selisih_kas) }}
            </span>
          </template>
          <template #item.jumlah_transaksi="{ item }">
            {{ item.is_future ? '—' : fmtNum(item.jumlah_transaksi) }}
          </template>
          <template #no-data>
            <div class="text-center py-6">
              Tidak ada data.
            </div>
          </template>
        </VDataTableServer>
      </VCardText>
      <VCardActions class="px-4 pb-4">
        <VSpacer />
        <VBtn
          variant="outlined"
          @click="detailDialog = false"
        >
          Tutup
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
  </VRow>
  </section>
</template>

<style scoped>
.rekap-table :deep(tr:hover td) {
  cursor: pointer;
}
</style>

<route lang="yaml">
meta:
  action: read
  subject: Rekap Tahunan
</route>
