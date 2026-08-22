<script setup>
import { ref, computed } from 'vue'

definePage({
  meta: {
    public: true,
  },
})

const searchQuery = ref('')
const activeCategory = ref('all')
const activeTab = ref('flow')
const activePanel = ref(Array.from({ length: 15 }, (_, i) => i))

const setCategory = catId => {
  activeCategory.value = catId
  activePanel.value = Array.from({ length: 15 }, (_, i) => i)
}

const categories = [
  { id: 'all', title: 'Semua Panduan', icon: 'ri-apps-2-line' },
  { id: 'master', title: '1. Master Data', icon: 'ri-database-2-line' },
  { id: 'gudang', title: '2. Gudang & Logistik', icon: 'ri-truck-line' },
  { id: 'pos', title: '3. Kasir & Transaksi POS', icon: 'ri-shopping-cart-2-line' },
  { id: 'retur_piutang', title: '4. Retur & Piutang', icon: 'ri-exchange-dollar-line' },
  { id: 'audit_laporan', title: '5. Audit & Opname', icon: 'ri-archive-stack-line' },
  { id: 'keuangan', title: '6. Keuangan & Laporan', icon: 'ri-file-chart-line' },
  { id: 'security', title: '7. Keamanan & RBAC', icon: 'ri-shield-keyhole-line' },
]

// Visual Architecture Flow Steps
const visualFlowSteps = [
  {
    step: 1,
    title: 'Master Data & Inisialisasi',
    icon: 'ri-database-2-line',
    color: 'primary',
    desc: 'Daftarkan kategori, produk, barcode SKU, supplier, dan pelanggan terdaftar.',
    route: '/master-data-produk',
  },
  {
    step: 2,
    title: 'Pengadaan & Gudang',
    icon: 'ri-truck-line',
    color: 'info',
    desc: 'Penerimaan barang per nomor batch/expired dan mutasi transfer antar cabang.',
    route: '/penerimaan-barang',
  },
  {
    step: 3,
    title: 'Kasir POS & Penjualan',
    icon: 'ri-shopping-cart-2-line',
    color: 'success',
    desc: 'Scan barcode, diskon/nego (PIN Supervisor), multi-pembayaran & cetak struk.',
    route: '/pos',
  },
  {
    step: 4,
    title: 'Buku Piutang & Retur',
    icon: 'ri-exchange-dollar-line',
    color: 'warning',
    desc: 'Pelacakan nota tempo kasbon, cicilan, dan retur pengembalian barang.',
    route: '/receivables',
  },
  {
    step: 5,
    title: 'Audit & Stock Opname',
    icon: 'ri-archive-stack-line',
    color: 'error',
    desc: 'Cycle counting mingguan, analisis FIFO/FEFO, dan rekonsiliasi selisih stok.',
    route: '/audit/stock-opname',
  },
  {
    step: 6,
    title: 'Laba Rugi & Rekap Kasir',
    icon: 'ri-file-chart-line',
    color: 'secondary',
    desc: 'Closing harian kasir, kalkulasi HPP riil, neraca, dan cetak PDF tahunan.',
    route: '/audit/rekap',
  },
]

// Detailed Module Guides
const guides = [
  {
    id: 'master-data',
    category: 'master',
    icon: 'ri-database-2-line',
    color: 'primary',
    title: '1. Master Data (Kategori, Produk, Barcode SKU, Supplier & Pelanggan)',
    subtitle: 'Fondasi utama katalog inventaris dan entitas bisnis sebelum operasional dimulai',
    steps: [
      {
        title: 'Kategori Barang & Satuan Produk',
        desc: 'Buka menu Master Data > Kategori Barang. Buat kategori utama (Sembako, Makanan, Minuman, Elektronik, dll) dan tentukan satuan barang (Pcs, Box, Kg). Kategori mempermudah filtering laporan penjualan dan pengelompokan Stock Opname.',
        link: '/kategori-barang',
        linkText: 'Buka Kategori Barang',
      },
      {
        title: 'Katalog Produk, Barcode & Metode Stok',
        desc: 'Daftarkan setiap item produk dengan Barcode unik, SKU, Nama Barang, Merk, Satuan, Kategori, Metode Stok (FIFO/FEFO/LIFO), Harga Beli Acuan, Harga Jual standar, dan batas Minimum Nego. Sistem otomatis membuat barcode yang bisa langsung discan di kasir.',
        link: '/master-data-produk',
        linkText: 'Buka Master Produk',
      },
      {
        title: 'Data Supplier & Pelanggan (Credit Limit)',
        desc: 'Catat vendor pemasok (untuk Purchase Order) dan data pelanggan tetap. Pada pelanggan, tentukan batas limit kredit (Plafon Piutang) dan termin jatuh tempo.',
        link: '/suppliers',
        linkText: 'Buka Data Supplier',
      },
    ],
    tips: 'Pastikan barcode produk tidak duplikat agar proses scanning kasir dan opname gudang berjalan 100% akurat.',
  },
  {
    id: 'pengadaan-gudang',
    category: 'gudang',
    icon: 'ri-truck-line',
    color: 'info',
    title: '2. Pengadaan Barang, Penerimaan Gudang & Mutasi Antar Cabang',
    subtitle: 'Alur logistik dari pesanan ke supplier hingga distribusi stok ke cabang toko',
    steps: [
      {
        title: 'Pembuatan Purchase Order (PO)',
        desc: 'Pilih Supplier, tentukan cabang tujuan pengiriman, masukkan daftar barang yang dipesan beserta kuantitas dan harga beli kesepakatan.',
        link: '/purchase-orders',
        linkText: 'Buka Purchase Order',
      },
      {
        title: 'Penerimaan Barang & Pencatatan Batch (Goods Receipt)',
        desc: 'Saat barang fisik tiba, buka menu Penerimaan Barang. Cocokkan kuantitas fisik dengan faktur supplier. Masukkan Nomor Batch dan Tanggal Kadaluarsa (Expired Date). Stok cabang otomatis bertambah (+).',
        link: '/penerimaan-barang',
        linkText: 'Buka Penerimaan Barang',
      },
      {
        title: 'Mutasi / Transfer Stok Antar Cabang (Stock Transfer)',
        desc: 'Kirim barang dari Gudang Pusat ke Cabang Toko (atau antar cabang) melalui menu Mutasi Stok. Pilih cabang asal, cabang tujuan, dan kuantitas. Stok cabang asal otomatis berkurang (-) dan cabang tujuan bertambah (+).',
        link: '/mutasi-stok',
        linkText: 'Buka Mutasi Stok',
      },
    ],
    tips: 'Setiap penerimaan barang otomatis membentuk identitas Batch untuk mendukung valuasi HPP akurat dan metode FEFO/FIFO.',
  },
  {
    id: 'kasir-pos',
    category: 'pos',
    icon: 'ri-shopping-cart-2-line',
    color: 'success',
    title: '3. Operasional Transaksi Kasir (Point of Sale / POS)',
    subtitle: 'Proses checkout cepat, scan barcode, diskon/nego, multi-pembayaran, dan cetak struk',
    steps: [
      {
        title: 'Buka Shift Kasir & Saldo Kas Awal',
        desc: 'Buka menu Kasir POS. Masukkan nominal uang modal kas kecil di laci (cash drawer) sebelum melayani pelanggan pertama.',
        link: '/pos',
        linkText: 'Buka Layar Kasir POS',
      },
      {
        title: 'Pindai Barcode / Cari Produk & Pelanggan',
        desc: 'Kasir cukup men-scan barcode barang menggunakan scanner barcode USB/Bluetooth, atau mengetik nama/SKU (tekan F2). Pilih pelanggan umum (*Walk-in*) atau pelanggan terdaftar.',
        link: '/pos',
        linkText: 'Ke Halaman Kasir',
      },
      {
        title: 'Nego Harga & Otorisasi PIN Supervisor',
        desc: 'Kasir dapat mengubah harga item pada keranjang. Jika harga berada di bawah batas Minimum Nego yang ditentukan Owner, sistem meminta input PIN Supervisor.',
      },
      {
        title: 'Metode Pembayaran (Cash, Transfer, QRIS, Tempo) & Cetak Struk',
        desc: 'Pilih metode bayar. Jika Tunai, gunakan tombol pecahan uang pas/cepat. Jika Tempo, pilih Pelanggan Terdaftar. Setelah pembayaran selesai, printer thermal otomatis mencetak struk belanja.',
        link: '/transaksi',
        linkText: 'Buka Riwayat Transaksi',
      },
    ],
    tips: 'Gunakan tombol pintasan keyboard kasir: F2 (Cari Produk), F4 (Pilih Pelanggan), F8 (Checkout Bayar), dan F9 (Uang Pas).',
  },
  {
    id: 'retur-barang',
    category: 'retur_piutang',
    icon: 'ri-arrow-go-back-line',
    color: 'error',
    title: '4. Manajemen Retur Barang (Penjualan & Pembelian)',
    subtitle: 'SOP pengembalian barang rusak/cacat dan penyesuaian otomatis stok inventaris',
    steps: [
      {
        title: 'Retur Penjualan (Customer Return): Stok Bertambah (+)',
        desc: 'Digunakan saat konsumen mengembalikan barang ke toko (rusak/salah beli). Masukkan nomor invoice penjualan, pilih item yang diretur, dan isi alasan retur. Setelah disetujui, stok fisik cabang otomatis bertambah (+).',
        link: '/retur',
        linkText: 'Buka Menu Retur',
      },
      {
        title: 'Retur Pembelian (Supplier Return): Stok Berkurang (-)',
        desc: 'Digunakan saat toko/gudang mengembalikan barang rusak/cacat ke Supplier. Sistem otomatis memotong stok fisik keluar (-) dan menyesuaikan tagihan utang ke supplier.',
      },
      {
        title: 'Otorisasi & Approval Supervisor',
        desc: 'Setiap retur yang diajukan dalam status Draft/Pending wajib di-approve oleh Supervisor/Manager sebelum mutasi fisik dieksekusi oleh sistem.',
      },
    ],
    tips: 'Cetak tanda terima retur sebagai bukti sah penyerahan barang yang dikembalikan.',
  },
  {
    id: 'buku-piutang',
    category: 'retur_piutang',
    icon: 'ri-wallet-3-line',
    color: 'warning',
    title: '5. Buku Piutang Usaha & Pembayaran Cicilan (Receivables)',
    subtitle: 'Pelacakan nota tempo kasbon, umur piutang jatuh tempo, dan kwitansi angsuran',
    steps: [
      {
        title: 'Monitoring Buku Piutang Pelanggan',
        desc: 'Seluruh transaksi kasir yang menggunakan metode Tempo otomatis masuk ke Buku Piutang Usaha. Dilengkapi kartu ringkasan: Total Piutang Aktif, Sisa Belum Bayar, Total Tertagih, dan Piutang Jatuh Tempo.',
        link: '/receivables',
        linkText: 'Buka Buku Piutang',
      },
      {
        title: 'Pencatatan Pembayaran Cicilan (Installment)',
        desc: 'Klik tombol Bayar Piutang pada pelanggan terkait. Masukkan nominal uang yang disetorkan (bisa cicil bertahap atau langsung lunas). Sistem otomatis memotong saldo sisa piutang.',
      },
      {
        title: 'Cetak Kwitansi Pembayaran Resmi',
        desc: 'Setelah pembayaran cicilan disimpan, kasir dapat langsung mencetak Kwitansi Pembayaran Resmi sebagai bukti sah penerimaan uang.',
      },
    ],
    tips: 'Gunakan filter "Jatuh Tempo" untuk memprioritaskan penagihan piutang yang sudah melewati batas waktu.',
  },
  {
    id: 'stock-opname',
    category: 'audit_laporan',
    icon: 'ri-archive-stack-line',
    color: 'primary',
    title: '6. Stock Opname & Cycle Counting (Audit Fisik vs Sistem)',
    subtitle: 'Metodologi audit parsial per kategori dan cabang tanpa menghentikan operasional toko',
    steps: [
      {
        title: 'Buka Sesi Stock Opname / Cycle Counting',
        desc: 'Buka menu Stock Opname. Pilih Cabang dan Kategori Barang yang ingin diaudit (misal: hanya Kategori Minuman). Sistem akan mengunci snapshot stok saat itu.',
        link: '/audit/stock-opname',
        linkText: 'Buka Stock Opname',
      },
      {
        title: 'Penghitungan Fisik & Input Angka Riil',
        desc: 'Petugas gudang menghitung barang fisik di rak dan menginput angka riil ke sistem. Sistem otomatis menghitung Selisih (Variance) = Fisik - Sistem.',
      },
      {
        title: 'Penyesuaian Stok Otomatis (Adjustment Approval)',
        desc: 'Setelah hasil audit disetujui (Approved) oleh Owner/Manager, sistem otomatis membuat log penyesuaian stok agar data buku sama persis dengan stok riil.',
      },
    ],
    tips: 'Gunakan metode Cycle Counting (audit berkala per kategori) agar proses opname ribuan SKU selesai cepat tanpa harus tutup toko.',
  },
  {
    id: 'closing-harian',
    category: 'keuangan',
    icon: 'ri-safe-2-line',
    color: 'success',
    title: '7. Closing Harian Kasir & Penguncian Transaksi (Transaction Lock)',
    subtitle: 'Rekonsiliasi uang fisik di laci kasir dan pencegahan kecurangan kas',
    steps: [
      {
        title: 'Hitung Kas Fisik di Laci Kasir',
        desc: 'Pada akhir shift/hari, kasir menghitung seluruh uang tunai fisik yang ada di laci kasir (uang kertas + uang logam).',
        link: '/audit/closing-harian',
        linkText: 'Buka Closing Harian',
      },
      {
        title: 'Input Form Closing & Rekonsiliasi Selisih',
        desc: 'Masukkan jumlah Uang Fisik. Sistem otomatis membandingkan dengan Uang Sistem (Penjualan Tunai + DP + Cicilan Piutang) dan menampilkan Selisih Kas.',
      },
      {
        title: 'Penguncian Tanggal (Lock System)',
        desc: 'Setelah closing disetujui (Approved), sistem otomatis mengunci tanggal tersebut sehingga tidak ada manipulasi transaksi di tanggal yang sudah ditutup.',
      },
    ],
    tips: 'Kasir wajib mengisi kolom catatan keterangan jika terdapat selisih uang.',
  },
  {
    id: 'rekap-keuangan',
    category: 'keuangan',
    icon: 'ri-file-chart-line',
    color: 'warning',
    title: '8. Laporan Laba Rugi, HPP FIFO/FEFO & Rekap Tahunan PDF',
    subtitle: 'Laporan keuangan ringkasan omzet, HPP modal aktual, laba bersih, dan arus kas',
    steps: [
      {
        title: 'Laporan Penjualan & Laba Rugi Real-Time',
        desc: 'Buka menu Laporan. Sistem secara otomatis menghitung Pendapatan Penjualan dikurangi HPP (berbasis batch FIFO/FEFO) untuk menghasilkan Laba Kotor dan Laba Bersih.',
        link: '/laporan',
        linkText: 'Buka Laporan',
      },
      {
        title: 'Buku Rekapitulasi Tahunan (12 Bulan)',
        desc: 'Buka menu Rekap Tahunan untuk melihat performa 12 bulan: Total Omzet, Beban HPP Modal, Laba Bersih, Margin %, dan Volume Barang Masuk/Keluar.',
        link: '/audit/rekap',
        linkText: 'Buka Rekap Tahunan',
      },
      {
        title: 'Ekspor Dokumen Excel (.xlsx) & Cetak PDF',
        desc: 'Klik tombol Ekspor Excel atau Cetak PDF untuk mengunduh laporan keuangan formal yang siap dipresentasikan ke Owner atau akuntan.',
      },
    ],
    tips: 'Semua angka laba bersih dihitung otomatis berdasarkan HPP aktual saat batch barang dibeli dari supplier.',
  },
  {
    id: 'security-rbac',
    category: 'security',
    icon: 'ri-shield-keyhole-line',
    color: 'primary',
    title: '9. Manajemen Hak Akses (RBAC), Supervisor PIN & Audit Log',
    subtitle: 'Pengaturan otorisasi karyawan dan pelacakan jejak rekam audit forensik',
    steps: [
      {
        title: 'Kelola Pengguna & Hak Akses (RBAC)',
        desc: 'Buka menu Manajemen Pengguna. Daftarkan akun karyawan, tentukan peran (Super Admin, Kasir, Admin Gudang), dan atur hak akses modul.',
        link: '/apps/employees',
        linkText: 'Buka Manajemen Karyawan',
      },
      {
        title: 'PIN Otorisasi Supervisor',
        desc: 'Atur 6-digit PIN keamanan pada profil pengguna. PIN ini wajib diinput saat melakukan tindakan berisiko tinggi (void nota, hapus piutang, diskon di bawah batas nego).',
      },
      {
        title: 'Jejak Audit Keamanan (Audit Log)',
        desc: 'Buka Dashboard Audit untuk melacak seluruh histori aktivitas: siapa yang mengedit data, kapan waktu terjadinya, dan nilai sebelum vs sesudah diedit.',
        link: '/dashboards/audit',
        linkText: 'Buka Dashboard Audit',
      },
    ],
    tips: 'Ganti PIN supervisor secara berkala untuk menjaga kerahasiaan otorisasi tindakan kritis.',
  },
]

// Keyboard Shortcuts
const shortcuts = [
  { key: 'F1 / F2', name: 'Cari Produk / Scan Barcode', desc: 'Fokus instan ke kolom pencarian produk atau scan barcode scanner' },
  { key: 'F4', name: 'Pilih / Tambah Pelanggan', desc: 'Membuka drawer pelanggan terdaftar untuk transaksi kredit/tempo/kasbon' },
  { key: 'F8', name: 'Checkout / Bayar', desc: 'Membuka pop-up pembayaran kasir saat keranjang belanja terisi' },
  { key: 'F9', name: 'Uang Pas', desc: 'Memilih nominal bayar sesuai total tagihan nota secara instan' },
  { key: 'Esc', name: 'Batal / Reset', desc: 'Menutup dialog modal yang sedang aktif atau membatalkan aksi' },
  { key: 'Enter', name: 'Konfirmasi Bayar', desc: 'Menyimpan transaksi kasir dan mencetak struk thermal kasir' },
]

// Filtering
const filteredGuides = computed(() => {
  return guides.filter(guide => {
    const matchesCategory = activeCategory.value === 'all' || guide.category === activeCategory.value
    const q = searchQuery.value.toLowerCase().trim()
    if (!q) return matchesCategory

    const matchTitle = guide.title.toLowerCase().includes(q)
    const matchSubtitle = guide.subtitle.toLowerCase().includes(q)
    const matchTips = guide.tips?.toLowerCase().includes(q)
    const matchSteps = guide.steps.some(s => s.title.toLowerCase().includes(q) || s.desc.toLowerCase().includes(q))
    
    return (matchTitle || matchSubtitle || matchTips || matchSteps) && matchesCategory
  })
})
</script>

<template>
  <div class="pa-4">
    <!-- Hero Header Banner with Live Search -->
    <VCard elevation="2" class="mb-6 border rounded-2xl overflow-hidden hero-card">
      <VCardText class="pa-6 pa-md-8 text-white">
        <VRow align="center">
          <VCol cols="12" md="8">
            <div class="d-flex align-center gap-2 mb-2">
              <VChip color="white" size="small" variant="elevated" class="text-primary font-weight-bold">
                <VIcon icon="ri-book-open-line" size="14" class="me-1" />
                DOKUMENTASI RESMI
              </VChip>
              <span class="text-caption text-white-80 font-weight-medium">
                Standard Operating Procedure (SOP) POS & Logistik Terpadu
              </span>
            </div>
            
            <h1 class="text-h3 font-weight-extrabold text-white mb-2">
              Panduan Alur Sistem Terpadu
            </h1>
            
            <p class="text-body-1 text-white-80 mb-5 max-w-600">
              Pelajari alur kerja lengkap dari Master Data, Pengadaan Gudang, Kasir POS, Piutang, Audit Opname, hingga Laporan Keuangan Laba Rugi.
            </p>

            <!-- Search Input -->
            <VCard elevation="3" class="rounded-xl pa-1 max-w-550">
              <VTextField
                v-model="searchQuery"
                placeholder="Cari alur kerja atau SOP (contoh: kasir, opname, FIFO, retur, PIN, closing)..."
                prepend-inner-icon="ri-search-line"
                variant="plain"
                density="comfortable"
                hide-details
                clearable
                class="px-2"
              />
            </VCard>
          </VCol>

          <VCol cols="12" md="4" class="d-none d-md-flex justify-center">
            <div class="hero-icon-box d-flex align-center justify-center rounded-2xl pa-6">
              <VIcon icon="ri-flow-chart" size="84" color="white" />
            </div>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Navigation Tabs: Peta Alur Visual vs Dokumentasi Lengkap vs Pintasan Keyboard -->
    <VCard class="rounded-xl border elevation-1 mb-6">
      <VTabs
        v-model="activeTab"
        color="primary"
        grow
        class="border-b"
      >
        <VTab value="flow" class="font-weight-bold">
          <VIcon icon="ri-git-merge-line" size="20" class="me-2" />
          Peta Alur Kerja (Flowchart Ringkas)
        </VTab>
        <VTab value="sop" class="font-weight-bold">
          <VIcon icon="ri-file-list-3-line" size="20" class="me-2" />
          SOP & Panduan Terperinci per Modul
        </VTab>
        <VTab value="shortcuts" class="font-weight-bold">
          <VIcon icon="ri-keyboard-line" size="20" class="me-2" />
          Pintasan Keyboard Kasir
        </VTab>
      </VTabs>

      <!-- TAB 1: PETA ALUR KERJA VISUAL -->
      <div v-if="activeTab === 'flow'" class="pa-6">
        <div class="mb-6">
          <h3 class="text-h6 font-weight-bold mb-1">
            Diagram Alur Transaksi & Inventaris (End-to-End)
          </h3>
          <p class="text-body-2 text-medium-emphasis">
            Urutan standar operasional bisnis mulai dari inisialisasi master data hingga pembukuan laba rugi.
          </p>
        </div>

        <VRow>
          <VCol
            v-for="item in visualFlowSteps"
            :key="item.step"
            cols="12"
            sm="6"
            md="4"
          >
            <VCard class="h-100 pa-5 rounded-xl border bg-var-theme-background d-flex flex-column justify-space-between flow-step-card" hover>
              <div>
                <div class="d-flex align-center justify-space-between mb-3">
                  <VAvatar :color="item.color" variant="tonal" size="44" rounded="lg">
                    <VIcon :icon="item.icon" size="24" />
                  </VAvatar>
                  <VChip size="small" :color="item.color" variant="elevated" class="font-weight-bold">
                    Tahap {{ item.step }}
                  </VChip>
                </div>

                <h4 class="text-subtitle-1 font-weight-bold mb-1 text-high-emphasis">
                  {{ item.title }}
                </h4>

                <p class="text-body-2 text-medium-emphasis mb-4">
                  {{ item.desc }}
                </p>
              </div>

              <VBtn
                size="small"
                variant="tonal"
                :color="item.color"
                :to="item.route"
                class="font-weight-bold text-none mt-auto"
                append-icon="ri-arrow-right-line"
              >
                Buka Menu Terkait
              </VBtn>
            </VCard>
          </VCol>
        </VRow>
      </div>

      <!-- TAB 2: SOP & PANDUAN TERPERINCI -->
      <div v-else-if="activeTab === 'sop'" class="pa-6">
        <!-- Category Filter Pills -->
        <div class="d-flex flex-wrap gap-2 mb-6 align-center">
          <span class="text-caption font-weight-bold text-medium-emphasis me-2">Kategori:</span>
          <VChip
            v-for="cat in categories"
            :key="cat.id"
            :color="activeCategory === cat.id ? 'primary' : 'default'"
            :variant="activeCategory === cat.id ? 'elevated' : 'tonal'"
            class="cursor-pointer font-weight-medium"
            size="small"
            @click="setCategory(cat.id)"
          >
            <VIcon :icon="cat.icon" size="14" class="me-1" />
            {{ cat.title }}
          </VChip>
        </div>

        <!-- Empty State -->
        <div v-if="filteredGuides.length === 0" class="text-center py-12 text-disabled">
          <VAvatar color="secondary" variant="tonal" size="64" class="mb-3">
            <VIcon icon="ri-search-eye-line" size="32" />
          </VAvatar>
          <h3 class="text-h6 font-weight-bold">Tidak ada panduan yang cocok</h3>
          <p class="text-body-2 text-medium-emphasis">Coba gunakan kata kunci pencarian lain atau pilih kategori "Semua Panduan".</p>
          <VBtn color="primary" variant="tonal" size="small" @click="searchQuery = ''; activeCategory = 'all'">
            Reset Pencarian
          </VBtn>
        </div>

        <!-- Expansion Panels List -->
        <VExpansionPanels
          v-else
          v-model="activePanel"
          multiple
          class="custom-expansion-panels gap-4"
        >
          <VExpansionPanel
            v-for="guide in filteredGuides"
            :key="guide.id"
            elevation="1"
            class="border rounded-xl mb-4 overflow-hidden"
          >
            <VExpansionPanelTitle class="py-4 px-6">
              <div class="d-flex align-center gap-4">
                <VAvatar :color="guide.color" variant="tonal" size="44" rounded="lg">
                  <VIcon :icon="guide.icon" size="24" />
                </VAvatar>
                <div>
                  <div class="text-h6 font-weight-bold">{{ guide.title }}</div>
                  <div class="text-caption text-medium-emphasis">{{ guide.subtitle }}</div>
                </div>
              </div>
            </VExpansionPanelTitle>

            <VDivider />

            <VExpansionPanelText class="pa-6">
              <!-- Step by Step Timeline -->
              <div class="mb-6">
                <div class="text-subtitle-2 font-weight-bold text-primary mb-4 d-flex align-center gap-2">
                  <VIcon icon="ri-list-ordered" size="20" />
                  <span>Prosedur & Langkah Kerja:</span>
                </div>

                <VTimeline density="compact" side="end" truncate-line="both">
                  <VTimelineItem
                    v-for="(step, sIdx) in guide.steps"
                    :key="sIdx"
                    :dot-color="guide.color"
                    size="small"
                  >
                    <div class="d-flex flex-wrap align-center justify-space-between gap-2 mb-1">
                      <div class="font-weight-bold text-subtitle-2">{{ step.title }}</div>
                      <VBtn
                        v-if="step.link"
                        :to="step.link"
                        size="x-small"
                        :color="guide.color"
                        variant="tonal"
                        append-icon="ri-arrow-right-line"
                        class="text-none font-weight-bold"
                      >
                        {{ step.linkText || 'Buka Menu' }}
                      </VBtn>
                    </div>
                    <div class="text-body-2 text-medium-emphasis">
                      {{ step.desc }}
                    </div>
                  </VTimelineItem>
                </VTimeline>
              </div>

              <!-- Alert Tips -->
              <VAlert
                v-if="guide.tips"
                :color="guide.color"
                variant="tonal"
                density="comfortable"
                class="rounded-lg"
              >
                <template #prepend>
                  <VIcon icon="ri-lightbulb-line" size="22" />
                </template>
                <div class="text-caption">
                  <strong>Standar Sistem:</strong> {{ guide.tips }}
                </div>
              </VAlert>
            </VExpansionPanelText>
          </VExpansionPanel>
        </VExpansionPanels>
      </div>

      <!-- TAB 3: PINTASAN KEYBOARD KASIR -->
      <div v-else-if="activeTab === 'shortcuts'" class="pa-6">
        <div class="mb-5">
          <h3 class="text-h6 font-weight-bold mb-1">
            Daftar Pintasan Keyboard Layar Kasir POS
          </h3>
          <p class="text-body-2 text-medium-emphasis">
            Gunakan tombol fungsi keyboard berikut untuk mempercepat proses transaksi kasir di toko.
          </p>
        </div>

        <VRow>
          <VCol
            v-for="sc in shortcuts"
            :key="sc.key"
            cols="12"
            sm="6"
            md="4"
          >
            <div class="pa-4 rounded-xl border bg-var-theme-background d-flex align-center gap-3">
              <div class="shortcut-key d-flex align-center justify-center font-weight-extrabold text-primary bg-surface elevation-1 border rounded-lg">
                {{ sc.key }}
              </div>
              <div>
                <div class="text-subtitle-2 font-weight-bold text-high-emphasis mb-0">{{ sc.name }}</div>
                <div class="text-caption text-medium-emphasis">{{ sc.desc }}</div>
              </div>
            </div>
          </VCol>
        </VRow>
      </div>
    </VCard>
  </div>
</template>

<style scoped>
.hero-card {
  background: linear-gradient(135deg, #1e1b4b 0%, #312e81 45%, #4338ca 100%);
}

.hero-icon-box {
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.text-white-80 {
  color: rgba(255, 255, 255, 0.82) !important;
}

.max-w-600 {
  max-width: 600px;
}

.max-w-550 {
  max-width: 550px;
}

.flow-step-card {
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.flow-step-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 20px -4px rgba(0, 0, 0, 0.1) !important;
}

.shortcut-key {
  width: 48px;
  height: 44px;
  font-size: 16px;
  letter-spacing: 0.5px;
}

.custom-expansion-panels :deep(.v-expansion-panel-title__overlay) {
  opacity: 0;
}
.custom-expansion-panels :deep(.v-expansion-panel--active) {
  border-color: rgba(var(--v-theme-primary), 0.35) !important;
}
</style>
