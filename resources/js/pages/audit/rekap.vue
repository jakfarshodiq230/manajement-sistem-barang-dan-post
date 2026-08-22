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
  { title: 'Bulan',               key: 'bulan',            width: 120 },
  { title: 'Transaksi',           key: 'jumlah_transaksi', align: 'center' },
  { title: 'Omset Penjualan',     key: 'omset',            align: 'end' },
  { title: 'Modal (COGS)',        key: 'modal_cogs',       align: 'end' },
  { title: 'Beban Kas Kecil',     key: 'beban_operasional',align: 'end' },
  { title: 'Laba Bersih Riil',    key: 'laba_bersih',      align: 'end' },
  { title: 'Margin',              key: 'margin',           align: 'center' },
  { title: 'Selisih Kas',         key: 'selisih_kas',      align: 'end' },
  { title: 'Stok Masuk',          key: 'stok_masuk',       align: 'center' },
  { title: 'Stok Keluar',         key: 'stok_keluar',      align: 'center' },
  { title: 'Kumulatif Laba',      key: 'kumulatif_laba',   align: 'end' },
  { title: 'Aksi',                key: 'actions',          sortable: false, width: 60, align: 'center' },
]

// Detail daily headers
const detailHeaders = [
  { title: 'Tanggal',            key: 'tanggal' },
  { title: 'Hari',               key: 'hari' },
  { title: 'Transaksi',          key: 'jumlah_transaksi', align: 'center' },
  { title: 'Omset Penjualan',    key: 'omset',            align: 'end' },
  { title: 'Beban Kas Kecil',    key: 'beban_operasional',align: 'end' },
  { title: 'Laba Bersih',        key: 'laba',             align: 'end' },
  { title: 'Stok Masuk',         key: 'stok_masuk',       align: 'center' },
  { title: 'Stok Keluar',        key: 'stok_keluar',      align: 'center' },
  { title: 'Selisih Kas',        key: 'selisih_kas',      align: 'end' },
]

const marginCalc = row => {
  if (row.omset === null || row.omset === 0) return null
  
  return ((row.laba_bersih / row.omset) * 100).toFixed(1)
}
</script>

<template>
  <section class="pa-4">
    <!-- ── Header / Filter ───────────────────────── -->
    <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
      <div>
        <h2 class="text-h4 mb-1 font-weight-bold">
          📒 Laporan Rekap Tahunan
        </h2>
        <p class="text-body-2 mb-0 text-medium-emphasis">
          Rekapitulasi omzet, modal HPP, beban kas kecil operasional, laba bersih riil, dan mutasi stok bulanan.
        </p>
      </div>
      
      <div class="d-flex align-center gap-3">
        <VSelect
          v-model="selectedYear"
          :items="yearOptions"
          label="Tahun Laporan"
          density="compact"
          variant="outlined"
          style="min-width: 140px; max-width: 180px"
          hide-details
        />
        <VBtn
          color="error"
          variant="tonal"
          prepend-icon="ri-file-pdf-line"
          :loading="isPdfLoading"
          @click="downloadPdf"
        >
          Download PDF
        </VBtn>
      </div>
    </div>

    <!-- ── Summary Cards ─────────────────────────── -->
    <VRow class="mb-6">
      <VCol cols="12" sm="6" md="2" lg="2">
        <VCard elevation="2" class="pa-3 rounded-xl border h-100" :loading="isLoading">
          <p class="text-caption text-medium-emphasis mb-1 font-weight-bold">
            TOTAL TRANSAKSI
          </p>
          <h4 class="text-h5 font-weight-bold">
            {{ fmtNum(rekapData.summary.total_transaksi) }}
          </h4>
          <span class="text-caption text-disabled">Struk Selesai</span>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3" lg="3">
        <VCard elevation="2" class="pa-3 rounded-xl border border-s-lg border-primary h-100" :loading="isLoading">
          <p class="text-caption text-primary mb-1 font-weight-bold">
            TOTAL OMSET PENJUALAN
          </p>
          <h4 class="text-h5 font-weight-bold text-primary">
            {{ fmt(rekapData.summary.total_omset) }}
          </h4>
          <span class="text-caption text-disabled">Gross Revenue</span>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="2" lg="2">
        <VCard elevation="2" class="pa-3 rounded-xl border border-s-lg border-error h-100" :loading="isLoading">
          <p class="text-caption text-error mb-1 font-weight-bold">
            BEBAN KAS KECIL
          </p>
          <h4 class="text-h5 font-weight-bold text-error">
            {{ fmt(rekapData.summary.total_beban) }}
          </h4>
          <span class="text-caption text-disabled">Biaya Operasional</span>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3" lg="3">
        <VCard elevation="2" class="pa-3 rounded-xl border border-s-lg border-success h-100" :loading="isLoading">
          <p class="text-caption text-success mb-1 font-weight-bold">
            LABA BERSIH RIIL
          </p>
          <h4 class="text-h5 font-weight-bold text-success">
            {{ fmt(rekapData.summary.total_laba) }}
          </h4>
          <span class="text-caption text-disabled">Omzet &minus; HPP &minus; Kas Kecil</span>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="2" lg="2">
        <VCard elevation="2" class="pa-3 rounded-xl border border-s-lg border-info h-100" :loading="isLoading">
          <p class="text-caption text-info mb-1 font-weight-bold">
            MARGIN BERSIH
          </p>
          <h4 class="text-h5 font-weight-bold text-info">
            {{ rekapData.summary.margin ?? 0 }}%
          </h4>
          <span class="text-caption text-disabled">Rasio Profitabilitas</span>
        </VCard>
      </VCol>
    </VRow>

    <!-- ── Main Table (12 bulan) ──────────────────── -->
    <VRow class="mb-6">
      <VCol cols="12">
        <VCard class="rounded-xl border elevation-2">
          <VCardItem class="pb-2">
            <template #prepend>
              <VAvatar color="primary" variant="tonal" size="38" class="me-2" rounded="lg">
                <VIcon icon="ri-calendar-event-line" size="22" />
              </VAvatar>
            </template>
            <VCardTitle class="text-h6 font-weight-bold">Rincian Performa Keuangan Bulanan &mdash; Tahun {{ selectedYear }}</VCardTitle>
            <VCardSubtitle>Klik tombol mata (👁) pada baris bulan untuk melihat rincian transaksi harian</VCardSubtitle>
          </VCardItem>
          <VDivider />

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
            hover
            class="text-no-wrap rekap-table"
          >
            <!-- Bulan -->
            <template #item.bulan="{ item }">
              <div class="d-flex align-center gap-2">
                <VChip
                  v-if="item.is_current"
                  color="primary"
                  size="x-small"
                  variant="flat"
                  class="font-weight-bold"
                >
                  Bulan Ini
                </VChip>
                <span :class="item.is_future ? 'text-medium-emphasis' : 'font-weight-bold'">
                  {{ item.bulan }}
                </span>
              </div>
            </template>

            <!-- Transaksi -->
            <template #item.jumlah_transaksi="{ item }">
              <span :class="item.is_future ? 'text-medium-emphasis' : 'font-weight-medium'">
                {{ item.is_future ? '—' : fmtNum(item.jumlah_transaksi) }}
              </span>
            </template>

            <!-- Omset -->
            <template #item.omset="{ item }">
              <span class="font-weight-medium text-primary">
                {{ item.is_future ? '—' : fmt(item.omset) }}
              </span>
            </template>

            <!-- Modal COGS -->
            <template #item.modal_cogs="{ item }">
              <span class="text-medium-emphasis">
                {{ item.is_future ? '—' : fmt(item.modal_cogs) }}
              </span>
            </template>

            <!-- Beban Kas Kecil -->
            <template #item.beban_operasional="{ item }">
              <span v-if="!item.is_future" class="text-error font-weight-medium">
                {{ item.beban_operasional > 0 ? ('- ' + fmt(item.beban_operasional)) : 'Rp 0' }}
              </span>
              <span v-else class="text-medium-emphasis">—</span>
            </template>

            <!-- Laba Bersih -->
            <template #item.laba_bersih="{ item }">
              <span :class="labaColor(item.laba_bersih)" class="font-weight-bold">
                {{ item.is_future ? '—' : fmt(item.laba_bersih) }}
              </span>
            </template>

            <!-- Margin -->
            <template #item.margin="{ item }">
              <VChip
                v-if="!item.is_future && marginCalc(item) !== null"
                :color="parseFloat(marginCalc(item)) >= 20 ? 'success' : parseFloat(marginCalc(item)) >= 10 ? 'warning' : 'error'"
                size="x-small"
                variant="tonal"
                class="font-weight-bold"
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
              <span class="text-success font-weight-medium">
                {{ item.is_future ? '—' : ('+' + fmtNum(item.stok_masuk)) }}
              </span>
            </template>

            <!-- Stok Keluar -->
            <template #item.stok_keluar="{ item }">
              <span class="text-error font-weight-medium">
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
                variant="tonal"
                color="primary"
                @click="openDetail(item)"
              />
            </template>

            <!-- Footer / Total Row -->
            <template #bottom>
              <div class="pa-4 border-t d-flex flex-wrap align-center justify-space-between gap-4 text-body-2 bg-var-theme-surface">
                <div><strong>Total Transaksi:</strong> {{ fmtNum(rekapData.summary.total_transaksi) }} Struk</div>
                <div><strong>Total Omset:</strong> <span class="text-primary font-weight-bold">{{ fmt(rekapData.summary.total_omset) }}</span></div>
                <div><strong>Total Beban Kas Kecil:</strong> <span class="text-error font-weight-bold">{{ fmt(rekapData.summary.total_beban) }}</span></div>
                <div><strong>Total Laba Bersih Riil:</strong>
                  <span :class="labaColor(rekapData.summary.total_laba)" class="font-weight-bold"> {{ fmt(rekapData.summary.total_laba) }}</span>
                </div>
                <div><strong>Margin Rata-rata:</strong> <span class="text-info font-weight-bold">{{ rekapData.summary.margin ?? 0 }}%</span></div>
              </div>
            </template>
          </VDataTableServer>
        </VCard>
      </VCol>
    </VRow>

    <!-- ── Catatan Rumus ──────────────────────────── -->
    <VRow>
      <VCol cols="12">
        <VCard class="rounded-xl border elevation-1" title="📐 Standar Perhitungan Akuntansi Rekap Keuangan">
          <VCardText>
            <VAlert
              color="info"
              variant="tonal"
              icon="ri-information-line"
              class="mb-4"
            >
              Semua angka di halaman ini dihitung secara riil: <strong>Laba Bersih Riil = Omzet Penjualan &minus; HPP (Modal Barang Terjual) &minus; Beban Operasional Kas Kecil</strong>. Transaksi yang masuk hanya status <strong>Selesai (Completed)</strong>.
            </VAlert>

            <VRow class="g-3">
              <VCol cols="12" md="4">
                <div class="pa-3 bg-var-theme-surface rounded-lg border h-100">
                  <div class="text-subtitle-2 font-weight-bold text-primary mb-1">1. Omset & HPP Modal</div>
                  <div class="text-caption text-medium-emphasis">
                    Omset adalah nilai bruto penjualan. HPP adalah modal dasar dari batch stok barang yang keluar saat kasir melakukan transaksi.
                  </div>
                </div>
              </VCol>

              <VCol cols="12" md="4">
                <div class="pa-3 bg-var-theme-surface rounded-lg border h-100">
                  <div class="text-subtitle-2 font-weight-bold text-error mb-1">2. Beban Kas Kecil (Petty Cash)</div>
                  <div class="text-caption text-medium-emphasis">
                    Akumulasi biaya rutin harian toko (Token PLN, Galon Minum, Bensin Antar Barang, ATK Thermal, dan Lembur Karyawan).
                  </div>
                </div>
              </VCol>

              <VCol cols="12" md="4">
                <div class="pa-3 bg-var-theme-surface rounded-lg border h-100">
                  <div class="text-subtitle-2 font-weight-bold text-success mb-1">3. Laba Bersih Riil (Net Profit)</div>
                  <div class="text-caption text-medium-emphasis">
                    Keuntungan bersih akhir yang siap dialokasikan sebagai laba ditahan atau pembagian keuntungan cabang kepada owner.
                  </div>
                </div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- ── Detail Dialog (Harian per Bulan) ─────────── -->
    <VDialog
      v-model="detailDialog"
      max-width="950"
    >
      <VCard class="rounded-xl">
        <VCardTitle class="bg-primary text-white pa-4 d-flex align-center justify-space-between">
          <span class="font-weight-bold">Detail Harian &mdash; {{ detailMonth?.bulan }} {{ selectedYear }}</span>
          <VBtn icon="ri-close-line" variant="text" size="small" @click="detailDialog = false" />
        </VCardTitle>

        <VCardText class="pa-4">
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
              <span class="text-primary font-weight-medium">
                {{ item.is_future ? '—' : fmt(item.omset) }}
              </span>
            </template>
            <template #item.beban_operasional="{ item }">
              <span v-if="!item.is_future" class="text-error font-weight-medium">
                {{ item.beban_operasional > 0 ? ('- ' + fmt(item.beban_operasional)) : 'Rp 0' }}
              </span>
              <span v-else class="text-medium-emphasis">—</span>
            </template>
            <template #item.laba="{ item }">
              <span :class="labaColor(item.laba)" class="font-weight-bold">
                {{ item.is_future ? '—' : fmt(item.laba) }}
              </span>
            </template>
            <template #item.stok_masuk="{ item }">
              <span class="text-success font-weight-medium">
                {{ item.is_future ? '—' : ('+' + fmtNum(item.stok_masuk)) }}
              </span>
            </template>
            <template #item.stok_keluar="{ item }">
              <span class="text-error font-weight-medium">
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
              <div class="text-center py-6 text-disabled">
                Tidak ada transaksi tercatat.
              </div>
            </template>
          </VDataTableServer>
        </VCardText>
        <VCardActions class="px-4 pb-4">
          <VSpacer />
          <VBtn
            variant="tonal"
            @click="detailDialog = false"
          >
            Tutup
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
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
