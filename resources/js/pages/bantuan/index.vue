<script setup>
import { ref, computed } from 'vue'

definePage({
  meta: {
    public: true,
  },
})

const searchQuery = ref('')
const activeTab = ref('alur')
const selectedCategory = ref('all')
const activePanel = ref([0, 1, 2])

// Filter Categories
const workflowCategories = [
  { id: 'all', title: 'Semua Alur', icon: 'ri-apps-2-line' },
  { id: 'master', title: '1. Master Data', icon: 'ri-database-2-line' },
  { id: 'gudang', title: '2. Pengadaan & Gudang', icon: 'ri-truck-line' },
  { id: 'pos', title: '3. Kasir & Transaksi', icon: 'ri-shopping-cart-2-line' },
  { id: 'piutang_retur', title: '4. Piutang & Retur', icon: 'ri-exchange-dollar-line' },
  { id: 'opname', title: '5. Opname & Audit Stok', icon: 'ri-archive-stack-line' },
  { id: 'laporan', title: '6. Laporan & Keuangan', icon: 'ri-file-chart-line' },
]

// 1. Alur Sistem Lengkap & Step-by-Step
const systemWorkflows = [
  {
    id: 'wf-master',
    category: 'master',
    icon: 'ri-database-2-line',
    color: 'primary',
    title: 'Tahap 1: Inisialisasi Master Data & Katalog Barang',
    subtitle: 'Langkah awal mempersiapkan data kategori, master produk SKU, supplier, dan pelanggan sebelum transaksi dimulai.',
    steps: [
      {
        no: 1,
        title: 'Buat Kategori Barang & Satuan',
        desc: 'Buka menu Kategori Barang. Buat kategori utama (misal: Sembako, Minuman, Alat Tulis) dan tentukan satuan produk standar (Pcs, Box, Kg). Kategori mempermudah filter laporan dan audit stock opname.',
        link: '/kategori-barang',
        linkText: 'Buka Kategori Barang',
      },
      {
        no: 2,
        title: 'Daftarkan Master Data Produk & Barcode SKU',
        desc: 'Buka Master Data Produk. Masukkan nama barang, SKU/Barcode unik, kategori, satuan, metode stok (FIFO/FEFO/LIFO), harga beli acuan, dan harga jual standar serta batas Minimum Nego.',
        link: '/master-data-produk',
        linkText: 'Buka Master Produk',
      },
      {
        no: 3,
        title: 'Input Data Supplier & Data Pelanggan',
        desc: 'Daftarkan data vendor pemasok untuk pemesanan barang (PO), serta daftarkan pelanggan tetap lengkap dengan batas limit kredit piutang (Credit Limit) dan jatuh tempo.',
        link: '/suppliers',
        linkText: 'Buka Supplier & Pelanggan',
      },
    ],
    tips: 'Gunakan scanner barcode laser saat mendaftarkan produk agar SKU barcode sesuai dengan fisik barang di toko.',
  },
  {
    id: 'wf-gudang',
    category: 'gudang',
    icon: 'ri-truck-line',
    color: 'info',
    title: 'Tahap 2: Pengadaan, Penerimaan Barang & Mutasi Antar Cabang',
    subtitle: 'Alur pasokan logistik dari pesanan pembelian (PO) ke vendor, penerimaan batch gudang, hingga distribusi stok ke cabang.',
    steps: [
      {
        no: 1,
        title: 'Penerimaan Barang & Catat Nomor Batch',
        desc: 'Buka menu Penerimaan Barang. Saat barang tiba dari supplier, catat nomor faktur supplier, nomor batch, tanggal kadaluarsa (Expired Date), harga beli aktual (HPP), dan jumlah fisik yang masuk.',
        link: '/penerimaan-barang',
        linkText: 'Buka Penerimaan Barang',
      },
      {
        no: 2,
        title: 'Mutasi / Transfer Stok Antar Cabang',
        desc: 'Buka menu Mutasi Stok. Pilih cabang asal dan cabang tujuan, pilih item barang serta batch yang akan dikirim, lalu terbitkan Surat Jalan Mutasi.',
        link: '/mutasi-stok',
        linkText: 'Buka Mutasi Stok',
      },
      {
        no: 3,
        title: 'Konfirmasi Penerimaan Cabang Tujuan',
        desc: 'Admin cabang tujuan memverifikasi barang fisik yang diterima dan melakukan konfirmasi (Approve). Stok otomatis bertambah di cabang penerima dan berkurang di cabang pengirim.',
        link: '/mutasi-stok',
        linkText: 'Verifikasi Mutasi',
      },
    ],
    tips: 'Untuk barang makanan/obat-obatan, pastikan tanggal kadaluarsa diinput teliti agar sistem FEFO otomatis memprioritaskan stok yang mendekati expired.',
  },
  {
    id: 'wf-pos',
    category: 'pos',
    icon: 'ri-shopping-cart-2-line',
    color: 'success',
    title: 'Tahap 3: Operasional Kasir & Transaksi Penjualan (POS)',
    subtitle: 'Panduan lengkap kasir dari buka shift kas awal, scan transaksi, diskon/nego, pembayaran, hingga cetak struk.',
    steps: [
      {
        no: 1,
        title: 'Buka Shift Kasir & Saldo Kas Awal',
        desc: 'Buka menu Kasir POS. Masukkan nominal uang modal kas awal di laci (cash drawer) sebelum melayani pelanggan pertama.',
        link: '/pos',
        linkText: 'Buka Kasir POS',
      },
      {
        no: 2,
        title: 'Pindai Barcode / Pilih Produk & Pelanggan',
        desc: 'Ketik nama barang atau scan barcode menggunakan scanner (tekan F2 untuk fokus cari). Pilih pelanggan jika transaksi atas nama langganan atau pilih Pelanggan Umum.',
        link: '/pos',
        linkText: 'Ke Halaman Kasir',
      },
      {
        no: 3,
        title: 'Nego Harga & Otorisasi PIN Supervisor',
        desc: 'Kasir dapat mengubah harga item pada keranjang. Jika harga di bawah batas Minimum Nego yang ditentukan Owner, sistem akan meminta input PIN Supervisor.',
        link: '/pos',
        linkText: 'Lihat POS',
      },
      {
        no: 4,
        title: 'Proses Pembayaran & Cetak Struk',
        desc: 'Pilih metode pembayaran: Tunai (Gunakan tombol pecahan uang pas/cepat), Transfer Bank, QRIS, atau Piutang/Tempo. Setelah bayar, cetak struk thermal 58mm/80mm.',
        link: '/pos',
        linkText: 'Buka Kasir POS',
      },
    ],
    tips: 'Gunakan tombol pintasan keyboard: F2 (Cari Produk), F4 (Pilih Pelanggan), F8 (Checkout Bayar), dan F9 (Uang Pas).',
  },
  {
    id: 'wf-piutang',
    category: 'piutang_retur',
    icon: 'ri-exchange-dollar-line',
    color: 'warning',
    title: 'Tahap 4: Pengelolaan Buku Piutang & Retur Barang',
    subtitle: 'Pencatatan tagihan pelanggan tempo, pelunasan bertahap, dan pengembalian barang rusak/salah kirim.',
    steps: [
      {
        no: 1,
        title: 'Monitoring Buku Piutang Pelanggan',
        desc: 'Buka menu Piutang. Pantau daftar tagihan pelanggan yang belum lunas, tanggal jatuh tempo, dan riwayat cicilan pembayaran.',
        link: '/receivables',
        linkText: 'Buka Piutang',
      },
      {
        no: 2,
        title: 'Pencatatan Pembayaran Cicilan / Pelunasan',
        desc: 'Klik tombol Bayar Piutang pada pelanggan bersangkutan. Masukkan nominal yang disetorkan (Tunai/Transfer), sistem otomatis mengurangi sisa saldo piutang dan mencatat kas masuk.',
        link: '/receivables',
        linkText: 'Kelola Piutang',
      },
      {
        no: 3,
        title: 'Proses Retur Penjualan / Retur Pembelian',
        desc: 'Buka menu Retur. Masukkan nomor faktur/invoice penjualan, pilih barang yang diretur, dan cantumkan alasan. Stok akan otomatis dikembalikan dan saldo kas/piutang disesuaikan.',
        link: '/retur',
        linkText: 'Buka Menu Retur',
      },
    ],
    tips: 'Sistem akan otomatis memberikan peringatan jika transaksi penjualan kredit pelanggan telah melebihi batas limit piutang.',
  },
  {
    id: 'wf-opname',
    category: 'opname',
    icon: 'ri-archive-stack-line',
    color: 'error',
    title: 'Tahap 5: Audit Stok & Stock Opname Berkala (Cycle Counting)',
    subtitle: 'Prosedur rekonsiliasi jumlah fisik barang di gudang dengan catatan sistem untuk mendeteksi selisih dan barang rusak.',
    steps: [
      {
        no: 1,
        title: 'Buka Sesi Stock Opname',
        desc: 'Buka menu Audit & Laporan > Stock Opname. Klik Buat Opname Baru, tentukan cabang dan kategori barang yang akan dihitung.',
        link: '/audit/stock-opname',
        linkText: 'Buka Stock Opname',
      },
      {
        no: 2,
        title: 'Penghitungan Fisik & Input Stok Aktual',
        desc: 'Petugas gudang menghitung jumlah fisik barang di rak dan memasukkan angka riil ke dalam sistem opname.',
        link: '/audit/stock-opname',
        linkText: 'Input Hasil Opname',
      },
      {
        no: 3,
        title: 'Analisis Selisih & Persetujuan (Approval)',
        desc: 'Sistem menghitung selisih fisik vs sistem beserta nilai kerugian rupiahnya. Owner atau Manager meninjau dan menyetujui (Approve) untuk memperbarui stok buku secara otomatis.',
        link: '/audit/stock-opname',
        linkText: 'Rekonsiliasi Stok',
      },
    ],
    tips: 'Lakukan opname secara berkala (misal: mingguan per kategori) agar selisih barang cepat terdeteksi tanpa mengganggu transaksi toko.',
  },
  {
    id: 'wf-laporan',
    category: 'laporan',
    icon: 'ri-file-chart-line',
    color: 'secondary',
    title: 'Tahap 6: Laporan Finansial, Laba Rugi & Tutup Buku Harian',
    subtitle: 'Pemantauan omzet penjualan, HPP, laba bersih, arus kas, dan ekspor laporan resmi.',
    steps: [
      {
        no: 1,
        title: 'Laporan Penjualan & Rekap Kasir',
        desc: 'Buka menu Laporan Penjualan. Tinjau total transaksi harian, produk terlaris (Best Seller), metode pembayaran yang digunakan, dan rekap per kasir.',
        link: '/laporan',
        linkText: 'Buka Laporan Penjualan',
      },
      {
        no: 2,
        title: 'Laporan Laba Rugi & HPP Akurat',
        desc: 'Buka Laporan Laba Rugi. Sistem secara otomatis menghitung Pendapatan Penjualan dikurangi HPP (Harga Pokok Penjualan berbasis FIFO/FEFO) untuk menghasilkan Laba Kotor dan Laba Bersih.',
        link: '/laporan',
        linkText: 'Buka Laba Rugi',
      },
      {
        no: 3,
        title: 'Ekspor Berkas Excel & Cetak PDF',
        desc: 'Gunakan tombol Ekspor Excel (.xlsx) atau Cetak PDF di setiap halaman laporan untuk keperluan pembukuan akuntansi dan arsip laporan owner.',
        link: '/laporan',
        linkText: 'Ekspor Laporan',
      },
    ],
    tips: 'Filter tanggal dapat disesuaikan untuk melihat performa Harian, Mingguan, Bulanan, maupun Tahunan.',
  },
]

// 2. Tanya Jawab Populer (FAQ) & Solusi Kendala
const faqs = [
  {
    id: 1,
    category: 'pos',
    question: 'Printer thermal struk kasir tidak mencetak atau kertas keluar polos/kosong?',
    answer: '1. Pastikan kabel USB/Bluetooth printer terhubung dengan baik ke komputer.\n2. Cek apakah gulungan kertas thermal tidak terbalik (sisi mengkilap harus menghadap head pemanas printer).\n3. Buka menu Pengaturan Struk di sistem untuk melakukan uji coba cetak (Test Print).\n4. Pastikan driver printer thermal (seperti POS-58 atau POS-80) sudah terpasang dan diset sebagai default printer.',
  },
  {
    id: 2,
    category: 'pos',
    question: 'Bagaimana cara kasir memberikan harga nego atau diskon kepada pelanggan?',
    answer: 'Pada halaman Kasir POS, klik kolom harga pada item di keranjang belanja. Masukkan harga nego yang disepakati. Sistem akan memvalidasi apakah harga tersebut berada di atas batas Minimum Nego yang diizinkan oleh Owner. Jika di bawah batas nego, sistem akan meminta otorisasi PIN Supervisor.',
  },
  {
    id: 3,
    category: 'pos',
    question: 'Bagaimana cara melakukan retur barang dari transaksi pelanggan?',
    answer: 'Buka menu Retur > Retur Penjualan. Klik tombol "Tambah Retur", masukkan nomor invoice/struk penjualan yang ingin diretur, pilih item barang yang dikembalikan serta alasannya (rusak/cacat/salah barang). Sistem otomatis mengembalikan stok barang ke gudang/toko dan menyesuaikan nominal kas/piutang.',
  },
  {
    id: 4,
    category: 'gudang',
    question: 'Apa perbedaan metode pengeluaran stok FIFO, FEFO, dan LIFO di sistem?',
    answer: '• FIFO (First In First Out): Barang yang pertama kali masuk gudang akan otomatis dikeluarkan pertama kali saat transaksi kasir.\n• FEFO (First Expired First Out): Barang dengan tanggal kadaluarsa paling dekat akan diprioritaskan keluar terlebih dahulu.\n• LIFO (Last In First Out): Barang yang terakhir masuk dikeluarkan pertama kali.\nMetode ini diatur per produk pada Master Data Produk.',
  },
  {
    id: 5,
    category: 'opname',
    question: 'Bagaimana cara melakukan penyesuaian saat ada selisih stok (Stock Opname)?',
    answer: 'Buka menu Audit & Laporan > Stock Opname. Buat sesi opname baru, pilih kategori atau seluruh barang, lalu masukkan jumlah stok fisik yang dihitung di gudang. Sistem akan menampilkan selisih (surplus/minus) beserta nilai rupiahnya dan melakukan penyesuaian otomatis setelah disetujui (Approved).',
  },
  {
    id: 6,
    category: 'master',
    question: 'Bagaimana jika lupa PIN Supervisor untuk otorisasi diskon/void transaksi?',
    answer: 'Owner atau Super Admin dapat mereset PIN Supervisor melalui menu Pengaturan > Karyawan & Pengguna. Pilih akun karyawan/manager yang bersangkutan, lalu ubah PIN otorisasi pada tab Keamanan Akun.',
  },
  {
    id: 7,
    category: 'master',
    question: 'Bagaimana cara mengganti cabang aktif saat login multi-cabang?',
    answer: 'Jika akun Anda memiliki hak akses multi-cabang (seperti Owner atau Super Admin), klik dropdown nama cabang di pojok kanan atas navbar, lalu pilih cabang yang ingin Anda kelola. Data stok, kasir, dan laporan akan otomatis beralih ke cabang tersebut.',
  },
  {
    id: 8,
    category: 'laporan',
    question: 'Bagaimana cara mengekspor Laporan Laba Rugi dan Neraca ke format Excel atau PDF?',
    answer: 'Buka menu Laporan > Laporan Laba Rugi atau Laporan Penjualan. Tentukan rentang tanggal dan filter cabang yang diinginkan, lalu klik tombol "Ekspor Excel (.xlsx)" atau "Cetak PDF" di sudut kanan atas tabel laporan.',
  },
]

// 3. Pintasan Keyboard POS
const shortcuts = [
  { key: 'F2', desc: 'Fokus ke kolom pencarian produk / scan barcode' },
  { key: 'F4', desc: 'Pilih data pelanggan dari daftar' },
  { key: 'F8', desc: 'Buka modal pembayaran (Checkout)' },
  { key: 'F9', desc: 'Pilih nominal Uang Pas secara instan' },
  { key: 'Esc', desc: 'Tutup modal aktif / batalkan aksi' },
  { key: 'Enter', desc: 'Konfirmasi pembayaran & simpan transaksi' },
]

// Filtering Logic
const filteredWorkflows = computed(() => {
  return systemWorkflows.filter(wf => {
    const matchCategory = selectedCategory.value === 'all' || wf.category === selectedCategory.value
    const q = searchQuery.value.toLowerCase().trim()
    if (!q) return matchCategory

    const matchTitle = wf.title.toLowerCase().includes(q)
    const matchSubtitle = wf.subtitle.toLowerCase().includes(q)
    const matchTips = wf.tips?.toLowerCase().includes(q)
    const matchSteps = wf.steps.some(s => s.title.toLowerCase().includes(q) || s.desc.toLowerCase().includes(q))

    return (matchTitle || matchSubtitle || matchTips || matchSteps) && (selectedCategory.value === 'all' || wf.category === selectedCategory.value)
  })
})

const filteredFaqs = computed(() => {
  return faqs.filter(faq => {
    const matchCategory = selectedCategory.value === 'all' || faq.category === selectedCategory.value
    const q = searchQuery.value.toLowerCase().trim()
    if (!q) return matchCategory

    const matchQuestion = faq.question.toLowerCase().includes(q)
    const matchAnswer = faq.answer.toLowerCase().includes(q)

    return (matchQuestion || matchAnswer) && (selectedCategory.value === 'all' || faq.category === selectedCategory.value)
  })
})
</script>

<template>
  <div class="bantuan-page">
    <!-- Hero Banner & Real-Time Search -->
    <div class="hero-bantuan rounded-2xl pa-8 pa-md-12 mb-8 text-center position-relative overflow-hidden elevation-3">
      <div class="hero-content position-relative z-index-2 max-w-750 mx-auto">
        <VChip color="primary" variant="elevated" size="small" class="font-weight-bold mb-3">
          <VIcon icon="ri-book-open-line" size="14" class="me-1" />
          Pusat Bantuan & Panduan Alur Sistem
        </VChip>

        <h1 class="text-h3 font-weight-extrabold text-white mb-3">
          Panduan Operasional & Solusi Kendala
        </h1>

        <p class="text-body-1 text-white-80 mb-6">
          Pelajari alur kerja sistem dari Master Data, Gudang, Kasir POS, Piutang, hingga Laporan Keuangan, atau cari solusi instan.
        </p>

        <!-- Live Instant Search Input -->
        <VCard elevation="4" class="search-card rounded-xl pa-2 max-w-650 mx-auto">
          <VTextField
            v-model="searchQuery"
            placeholder="Cari alur kerja, modul, atau kata kunci (misal: kasir, printer, opname, FIFO, retur, PIN)..."
            prepend-inner-icon="ri-search-line"
            variant="plain"
            density="comfortable"
            hide-details
            clearable
            class="px-2"
          />
        </VCard>
      </div>
    </div>

    <!-- Quick Contact Channels (WhatsApp, Hotline, Email) -->
    <VRow class="mb-8">
      <VCol cols="12" sm="6" md="4">
        <VCard class="h-100 pa-5 rounded-xl border elevation-1 contact-card" hover>
          <div class="d-flex align-center gap-4 mb-3">
            <VAvatar color="success" variant="tonal" size="52" rounded="lg">
              <VIcon icon="ri-whatsapp-line" size="28" />
            </VAvatar>
            <div>
              <h3 class="text-subtitle-1 font-weight-bold mb-0">Chat WhatsApp</h3>
              <span class="text-caption text-success font-weight-medium">Respon Cepat (08:00 - 22:00)</span>
            </div>
          </div>
          <p class="text-body-2 text-medium-emphasis mb-4">
            Konsultasi langsung dengan tim teknis kami untuk kendala kasir dan operasional harian.
          </p>
          <VBtn
            block
            color="success"
            variant="tonal"
            prepend-icon="ri-whatsapp-line"
            href="https://wa.me/6281234567890?text=Halo%20Tim%20Support%20Ms.POS,%20saya%20butuh%20bantuan%20terkait%20sistem"
            target="_blank"
            class="font-weight-bold"
          >
            Hubungi via WhatsApp
          </VBtn>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="4">
        <VCard class="h-100 pa-5 rounded-xl border elevation-1 contact-card" hover>
          <div class="d-flex align-center gap-4 mb-3">
            <VAvatar color="primary" variant="tonal" size="52" rounded="lg">
              <VIcon icon="ri-phone-line" size="28" />
            </VAvatar>
            <div>
              <h3 class="text-subtitle-1 font-weight-bold mb-0">Call Center & Hotline</h3>
              <span class="text-caption text-primary font-weight-medium">(0761) 8899-234</span>
            </div>
          </div>
          <p class="text-body-2 text-medium-emphasis mb-4">
            Layanan telepon untuk kendala jaringan, printer, atau kendala POS darurat.
          </p>
          <VBtn
            block
            color="primary"
            variant="tonal"
            prepend-icon="ri-phone-fill"
            href="tel:07618899234"
            class="font-weight-bold"
          >
            Panggil Layanan Suara
          </VBtn>
        </VCard>
      </VCol>

      <VCol cols="12" sm="12" md="4">
        <VCard class="h-100 pa-5 rounded-xl border elevation-1 contact-card" hover>
          <div class="d-flex align-center gap-4 mb-3">
            <VAvatar color="info" variant="tonal" size="52" rounded="lg">
              <VIcon icon="ri-mail-send-line" size="28" />
            </VAvatar>
            <div>
              <h3 class="text-subtitle-1 font-weight-bold mb-0">Email Bantuan Resmi</h3>
              <span class="text-caption text-info font-weight-medium">support@ptdumai.com</span>
            </div>
          </div>
          <p class="text-body-2 text-medium-emphasis mb-4">
            Kirimkan berkas audit, permintaan penyesuaian database, atau kendala non-darurat.
          </p>
          <VBtn
            block
            color="info"
            variant="tonal"
            prepend-icon="ri-mail-line"
            href="mailto:support@ptdumai.com?subject=Bantuan%20Sistem%20Ms.POS"
            class="font-weight-bold"
          >
            Kirim Pesan Email
          </VBtn>
        </VCard>
      </VCol>
    </VRow>

    <!-- Main Navigation Tabs: Alur Sistem, FAQ, Pintasan Keyboard -->
    <VCard class="rounded-xl border elevation-1 mb-6">
      <VTabs
        v-model="activeTab"
        color="primary"
        grow
        class="border-b"
      >
        <VTab value="alur" class="font-weight-bold">
          <VIcon icon="ri-flow-chart" size="20" class="me-2" />
          Alur Lengkap Sistem (Step-by-Step)
        </VTab>
        <VTab value="faq" class="font-weight-bold">
          <VIcon icon="ri-questionnaire-line" size="20" class="me-2" />
          Tanya Jawab & Solusi Kendala (FAQ)
        </VTab>
        <VTab value="shortcut" class="font-weight-bold">
          <VIcon icon="ri-keyboard-line" size="20" class="me-2" />
          Pintasan Keyboard Kasir (Shortcuts)
        </VTab>
      </VTabs>

      <!-- Category Filter Pills -->
      <div v-if="activeTab !== 'shortcut'" class="pa-4 bg-var-theme-background border-b d-flex flex-wrap gap-2 align-center">
        <span class="text-caption font-weight-bold text-medium-emphasis me-2">Kategori Modul:</span>
        <VChip
          v-for="cat in workflowCategories"
          :key="cat.id"
          :color="selectedCategory === cat.id ? 'primary' : 'default'"
          :variant="selectedCategory === cat.id ? 'elevated' : 'tonal'"
          class="cursor-pointer font-weight-medium"
          size="small"
          @click="selectedCategory = cat.id"
        >
          <VIcon :icon="cat.icon" size="14" class="me-1" />
          {{ cat.title }}
        </VChip>
      </div>

      <VCardText class="pa-6">
        <!-- TAB 1: ALUR SISTEM LENGKAP -->
        <div v-if="activeTab === 'alur'">
          <div v-if="filteredWorkflows.length > 0" class="d-flex flex-column gap-6">
            <VCard
              v-for="wf in filteredWorkflows"
              :key="wf.id"
              class="border rounded-xl pa-5 pa-md-6 elevation-1"
            >
              <!-- Workflow Header -->
              <div class="d-flex align-center gap-3 mb-4">
                <VAvatar :color="wf.color" variant="tonal" size="48" rounded="lg">
                  <VIcon :icon="wf.icon" size="26" />
                </VAvatar>
                <div>
                  <h3 class="text-h6 font-weight-bold mb-1">
                    {{ wf.title }}
                  </h3>
                  <p class="text-body-2 text-medium-emphasis mb-0">
                    {{ wf.subtitle }}
                  </p>
                </div>
              </div>

              <VDivider class="mb-5" />

              <!-- Step Cards Grid -->
              <VRow>
                <VCol
                  v-for="step in wf.steps"
                  :key="step.no"
                  cols="12"
                  md="4"
                >
                  <div class="h-100 pa-4 rounded-lg bg-var-theme-background border d-flex flex-column justify-space-between">
                    <div>
                      <div class="d-flex align-center gap-2 mb-2">
                        <VChip size="x-small" :color="wf.color" variant="elevated" class="font-weight-bold">
                          Langkah {{ step.no }}
                        </VChip>
                        <h4 class="text-subtitle-2 font-weight-bold text-high-emphasis">
                          {{ step.title }}
                        </h4>
                      </div>
                      <p class="text-caption text-medium-emphasis mb-3" style="line-height: 1.5;">
                        {{ step.desc }}
                      </p>
                    </div>

                    <VBtn
                      v-if="step.link"
                      size="small"
                      variant="tonal"
                      :color="wf.color"
                      :to="step.link"
                      class="mt-auto font-weight-bold text-none"
                      append-icon="ri-arrow-right-line"
                    >
                      {{ step.linkText }}
                    </VBtn>
                  </div>
                </VCol>
              </VRow>

              <!-- Workflow Tip -->
              <div v-if="wf.tips" class="mt-4 pa-3 rounded-lg bg-primary-lighten-5 border d-flex align-center gap-2 text-caption">
                <VIcon icon="ri-lightbulb-line" color="primary" size="18" />
                <span class="text-high-emphasis"><strong>Tips Praktis:</strong> {{ wf.tips }}</span>
              </div>
            </VCard>
          </div>

          <!-- Empty Workflows -->
          <div v-else class="text-center py-12 text-disabled">
            <VIcon icon="ri-search-line" size="48" class="mb-2" />
            <p class="text-body-1 font-weight-medium">Tidak ada alur kerja yang cocok dengan kata kunci "{{ searchQuery }}".</p>
            <VBtn size="small" variant="tonal" color="primary" @click="searchQuery = ''; selectedCategory = 'all'">
              Reset Pencarian
            </VBtn>
          </div>
        </div>

        <!-- TAB 2: TANYA JAWAB (FAQ) -->
        <div v-else-if="activeTab === 'faq'">
          <div v-if="filteredFaqs.length > 0">
            <VExpansionPanels
              v-model="activePanel"
              variant="accordion"
              class="faq-panels"
            >
              <VExpansionPanel
                v-for="faq in filteredFaqs"
                :key="faq.id"
                :value="faq.id"
                class="border rounded-lg mb-3 overflow-hidden"
              >
                <VExpansionPanelTitle class="font-weight-bold text-body-1 py-4">
                  <div class="d-flex align-center gap-2">
                    <VIcon icon="ri-question-line" size="18" color="primary" />
                    <span>{{ faq.question }}</span>
                  </div>
                </VExpansionPanelTitle>
                <VExpansionPanelText class="text-body-2 text-medium-emphasis pt-2 pb-4">
                  <div style="white-space: pre-line; line-height: 1.6;">
                    {{ faq.answer }}
                  </div>
                </VExpansionPanelText>
              </VExpansionPanel>
            </VExpansionPanels>
          </div>

          <!-- Empty FAQs -->
          <div v-else class="text-center py-12 text-disabled">
            <VIcon icon="ri-search-line" size="48" class="mb-2" />
            <p class="text-body-1 font-weight-medium">Tidak ada FAQ yang cocok dengan kata kunci "{{ searchQuery }}".</p>
            <VBtn size="small" variant="tonal" color="primary" @click="searchQuery = ''; selectedCategory = 'all'">
              Reset Pencarian
            </VBtn>
          </div>
        </div>

        <!-- TAB 3: PINTASAN KEYBOARD (SHORTCUTS) -->
        <div v-else-if="activeTab === 'shortcut'">
          <div class="mb-4">
            <h3 class="text-h6 font-weight-bold mb-1">
              Daftar Pintasan Keyboard Layar Kasir POS
            </h3>
            <p class="text-body-2 text-medium-emphasis">
              Gunakan tombol keyboard di bawah ini untuk mempercepat transaksi kasir tanpa perlu menggunakan mouse.
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
                <div class="shortcut-badge d-flex align-center justify-center font-weight-extrabold text-primary bg-surface elevation-1 border rounded-lg">
                  {{ sc.key }}
                </div>
                <div>
                  <div class="text-subtitle-2 font-weight-bold text-high-emphasis mb-0">{{ sc.key }}</div>
                  <div class="text-caption text-medium-emphasis">{{ sc.desc }}</div>
                </div>
              </div>
            </VCol>
          </VRow>
        </div>
      </VCardText>
    </VCard>
  </div>
</template>

<style scoped>
.hero-bantuan {
  background: linear-gradient(135deg, #1e1b4b 0%, #312e81 45%, #4338ca 100%);
}

.hero-content {
  z-index: 2;
}

.text-white-80 {
  color: rgba(255, 255, 255, 0.82) !important;
}

.max-w-750 {
  max-width: 750px;
}

.max-w-650 {
  max-width: 650px;
}

.contact-card {
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.contact-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px -4px rgba(0, 0, 0, 0.08) !important;
}

.bg-primary-lighten-5 {
  background-color: rgba(var(--v-theme-primary), 0.08);
}

.shortcut-badge {
  width: 48px;
  height: 44px;
  font-size: 16px;
  letter-spacing: 0.5px;
}
</style>
