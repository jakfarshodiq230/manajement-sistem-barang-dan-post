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
  { id: 'modal_roi', title: '7. Modal & ROI Cabang', icon: 'ri-hand-coin-line' },
  { id: 'security', title: '8. Keamanan & RBAC', icon: 'ri-shield-keyhole-line' },
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
  {
    step: 7,
    title: 'Modal & ROI Cabang',
    icon: 'ri-hand-coin-line',
    color: 'primary',
    desc: 'Injeksi modal, pengajuan proposal PDF, approval setoran laba & KPI ROI %.',
    route: '/apps/branch-capitals',
  },
]

// Detailed Module Guides
const guides = [
  {
    id: 'master-data',
    category: 'master',
    icon: 'ri-database-2-line',
    color: 'primary',
    title: '1. Master Data & Inventori Cabang (Struktur 3 Tingkat Harga, Multi-Batch & Pajak POS)',
    subtitle: 'Fondasi utama katalog inventaris, penetapan harga per batch, batas nego kasir, dan perlakuan PPN',
    steps: [
      {
        title: 'Kategori Barang & Satuan Produk',
        desc: 'Buka menu Master Data > Kategori Barang. Buat kategori utama (Sembako, Aki & Baterai, Elektronik, dll) dan tentukan satuan barang (Pcs, Dus, Box, Karton). Kategori mempermudah filtering laporan penjualan dan pengelompokan Stock Opname.',
        link: '/kategori-barang',
        linkText: 'Buka Kategori Barang',
      },
      {
        title: 'Katalog Produk & Barcode SKU',
        desc: 'Daftarkan setiap item produk dengan Barcode unik, SKU, Nama Barang, Merk, Satuan, Kategori, Metode Stok (FIFO/FEFO/LIFO), dan Isi Konversi (misal 1 Dus = 24 Pcs). Sistem otomatis membuat barcode yang siap discan di kasir.',
        link: '/master-data-produk',
        linkText: 'Buka Master Produk',
      },
      {
        title: 'Penetapan Struktur 3 Tingkat Harga & Kelola Multi-Batch',
        desc: 'Buka menu Inventori Cabang. Setiap produk dan batch fisik memiliki 3 lapis harga:\n• 1. Harga Modal (HPP Real): Modal bersih per unit yang otomatis mencakup diskon supplier dan PPN Masukan 11%.\n• 2. Harga Jual Normal: Harga pricelist kasir. Gunakan tombol pintas kalkulasi cepat Markup (+15%, +20%, +25%, +30%) dari modal.\n• 3. Harga Nego Minimum: Batas harga terendah tawar-menawar kasir.\n• Multi-Batch FIFO: Jika barang memiliki banyak batch dengan modal berbeda, POS otomatis memakai harga Batch Aktif (FIFO/FEFO). Owner dapat menyesuaikan harga setiap batch atau menyamakan seluruh batch melalui tombol "Kelola Batch".',
        link: '/inventori-cabang',
        linkText: 'Buka Inventori Cabang',
      },
      {
        title: 'Pengaturan Pajak Penjualan POS (PPN Keluaran Kasir)',
        desc: 'Di menu Inventori Cabang, tentukan apakah kasir membebankan PPN tambahan ke pembeli:\n• Harga Final (Netto / 0%): Toko menjual harga bersih tanpa membebankan pajak tambahan di struk.\n• + PPN 11%: Kasir akan menambahkan 11% PPN pada struk transaksi ke konsumen akhir.',
        link: '/inventori-cabang',
        linkText: 'Atur Pajak POS Cabang',
      },
      {
        title: 'Data Supplier & Pelanggan (Credit Limit & Tempo)',
        desc: 'Catat vendor pemasok (untuk Purchase Order) dan data pelanggan tetap. Pada pelanggan, tentukan batas limit kredit (Plafon Piutang) dan termin jatuh tempo.',
        link: '/suppliers',
        linkText: 'Buka Data Supplier',
      },
    ],
    tips: 'Gunakan tombol "Kelola Batch" di Inventori Cabang untuk mengatur HPP Real, Harga Jual POS, dan Batas Nego untuk masing-masing batch pengiriman supplier.',
  },
  {
    id: 'pengadaan-gudang',
    category: 'gudang',
    icon: 'ri-truck-line',
    color: 'info',
    title: '2. Pengadaan Barang (PO), Penerimaan Gudang & Kalkulasi HPP Real',
    subtitle: 'Alur pesanan pembelian (PO) fisik, verifikasi faktur gudang, diskon bertingkat D1..D5, kode SCC aki, dan mutasi stok',
    steps: [
      {
        title: 'Pembuatan Purchase Order (PO) ke Supplier',
        desc: 'Buka menu Purchase Order. Pilih Supplier, Cabang Tujuan, Metode Pembayaran (Tunai / Lunas atau Kredit / Tempo), dan Tanggal Jatuh Tempo. Masukkan daftar barang yang dipesan beserta kuantitas fisik (Qty & Satuan). Form PO difokuskan pada jumlah kuantitas pesanan, sedangkan harga faktur aktual diverifikasi di Gudang.',
        link: '/purchase-orders',
        linkText: 'Buka Purchase Order',
      },
      {
        title: 'Penerimaan Barang (Goods Receipt), Diskon D1..D5 & HPP Real',
        desc: 'Saat barang fisik dan faktur tiba di gudang/toko, buka menu Penerimaan Barang. Cocokkan fisik barang, masukkan harga faktur aktual, diskon bertingkat (D1 s/d D5), dan perlakuan PPN (Include/Exclude/Non-PPN). Sistem otomatis menghitung Live HPP Modal per Pcs dan mencatat batch baru.',
        link: '/penerimaan-barang',
        linkText: 'Buka Penerimaan Barang',
      },
      {
        title: 'Pencatatan Nomor Batch, Expired Date & Kode SCC Aki',
        desc: 'Untuk produk aki/baterai, masukkan nomor serial SCC unik per unit. Untuk produk makanan/obat, tentukan tanggal kadaluarsa (Expired Date) agar sistem FEFO bekerja otomatis.',
      },
      {
        title: 'Penanganan Kenaikan Harga Supplier & Retur Barang Masuk',
        desc: 'Jika harga faktur supplier naik dari kesepakatan awal, sistem otomatis menampilkan notifikasi kenaikan harga. Jika barang rusak/ditolak, pilih kompensasi: Tukar Barang, Potong Hutang Berjalan, atau Pengembalian Uang.',
      },
      {
        title: 'Mutasi / Transfer Stok Antar Cabang (Surat Jalan Digital & QR Code)',
        desc: 'Kirim barang antar cabang melalui menu Mutasi Stok. Dilengkapi Surat Jalan resmi dengan Tanda Tangan Digital 3 Pihak (Pengirim, Driver, Penerima) dan QR Code Verifikasi Keamanan.',
        link: '/mutasi-stok',
        linkText: 'Buka Mutasi Stok',
      },
    ],
    tips: 'Setiap penerimaan barang otomatis membentuk batch fisik baru lengkap dengan HPP Real di Inventori Cabang.',
  },
  {
    id: 'kasir-pos',
    category: 'pos',
    icon: 'ri-shopping-cart-2-line',
    color: 'success',
    title: '3. Operasional Transaksi Kasir (POS), Diskon Total & Otorisasi Nego',
    subtitle: 'Proses checkout cepat, scan barcode, diskon total faktur, otorisasi PIN supervisor, dan cetak struk',
    steps: [
      {
        title: 'Buka Shift Kasir & Saldo Kas Awal',
        desc: 'Buka menu Kasir POS. Masukkan nominal uang modal kas kecil di laci (cash drawer) sebelum melayani pelanggan pertama.',
        link: '/pos',
        linkText: 'Buka Layar Kasir POS',
      },
      {
        title: 'Pindai Barcode / Cari Produk & Pelanggan',
        desc: 'Kasir cukup men-scan barcode barang menggunakan scanner barcode USB/Bluetooth, atau mengetik nama/SKU (tekan F2). Sistem otomatis menarik harga dari Batch Aktif (FIFO/FEFO). Pilih pelanggan umum (*Walk-in*) atau pelanggan terdaftar (tekan F4).',
        link: '/pos',
        linkText: 'Ke Halaman Kasir',
      },
      {
        title: 'Diskon Total Bon Belanja (Bebas Input)',
        desc: 'Pada ringkasan keranjang belanja kasir, kolom Diskon Total dapat langsung diisi nominal potongan harga khusus faktur (misal Rp 20.000). Total tagihan bersih otomatis terpotong.',
      },
      {
        title: 'Tawar-Menawar (Nego) & Otorisasi PIN Supervisor',
        desc: 'Kasir dapat mengedit harga jual item di keranjang:\n• Jika harga tawar masih di atas atau sama dengan Harga Nego Minimum, kasir bisa langsung memproses transaksi.\n• Jika harga tawar berada di bawah Harga Nego Minimum, sistem otomatis mengunci dan meminta input PIN Otorisasi Supervisor/Owner (Master PIN: 123456).',
      },
      {
        title: 'Multi-Pembayaran (Cash, Transfer, QRIS, Tempo) & Cetak Struk',
        desc: 'Pilih metode bayar. Jika Tunai, gunakan tombol pecahan uang pas/cepat (F9). Jika Tempo, pilih Pelanggan Terdaftar & isi tanggal jatuh tempo. Printer thermal otomatis mencetak struk belanja.',
        link: '/transaksi',
        linkText: 'Buka Riwayat Transaksi',
      },
    ],
    tips: 'Gunakan tombol pintasan keyboard kasir: F1/F2 (Cari/Scan), F3/F4 (Pelanggan), F6 (Hold Tahan Bon), F7 (Daftar Bon Ditahan), F8 (Checkout), F9 (Uang Pas), dan Enter (Konfirmasi Bayar).',
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
        title: 'Retur Pembelian (Supplier Return): Penyesuaian Hutang / Uang',
        desc: 'Digunakan saat toko/gudang mengembalikan barang rusak/cacat ke Supplier. Sistem menyediakan pilihan kompensasi: Tukar Barang, Potong Hutang Berjalan Bulan Selanjutnya, atau Pengembalian Uang Tunai/Transfer.',
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
    title: '5. Buku Piutang Usaha, Pengiriman Email Tagihan & Kwitansi Otomatis',
    subtitle: 'Pelacakan nota tempo kasbon, pengiriman invoice ke email pelanggan, kwitansi cicilan otomatis, dan pengingat jatuh tempo',
    steps: [
      {
        title: 'Monitoring Buku Piutang Pelanggan',
        desc: 'Seluruh transaksi kasir yang menggunakan metode Tempo otomatis masuk ke Buku Piutang Usaha. Dilengkapi kartu ringkasan: Total Piutang Aktif, Sisa Belum Bayar, Total Tertagih, dan Piutang Jatuh Tempo.',
        link: '/receivables',
        linkText: 'Buka Buku Piutang',
      },
      {
        title: 'Kirim Surat Tagihan Faktur Resmi ke Email Pelanggan',
        desc: 'Klik tombol Kirim Email Tagihan pada drawer detail piutang. Sistem otomatis menyusun email HTML elegan berisi rincian faktur penjualan, daftar barang, termin jatuh tempo, dan rincian rekening pembayaran toko.',
      },
      {
        title: 'Pencatatan Pembayaran Cicilan & Kwitansi Otomatis',
        desc: 'Klik tombol Bayar Piutang pada pelanggan terkait. Masukkan nominal cicilan atau pelunasan. Sistem otomatis mencatat kas masuk, memotong sisa piutang, mencetak struk kasir, serta mengirimkan Kwitansi Tanda Terima Resmi ke email pelanggan secara otomatis.',
      },
      {
        title: 'Pengingat Otomatis Jatuh Tempo Harian (Automated Scheduler)',
        desc: 'Sistem menjalankan scheduler otomatis harian (receivables:send-reminders) yang mengecek piutang H-3 jatuh tempo dan piutang menunggak (overdue) untuk mengirimkan surat pengingat santun ke email pelanggan.',
      },
      {
        title: 'Audit Trail Riwayat Log Pengiriman Email & Fitur Kirim Ulang (Retry)',
        desc: 'Setiap pengiriman email tercatat rapi pada tabel email_logs lengkap dengan status (Terkirim / Gagal / Pending) dan pesan error jika SMTP bermasalah. Kasir/Admin dapat menekan tombol [ 🔄 Kirim Ulang ] seketika.',
      },
    ],
    tips: 'Gunakan fitur Kirim Email Tagihan agar pelanggan menerima rincian invoice formal langsung di smartphone mereka.',
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
        title: 'Input Form Closing & Rekonsiliasi Selisih Lengkap',
        desc: 'Masukkan jumlah Uang Fisik riil di laci. Sistem secara real-time menampilkan estimasi kas sistem: (Penjualan Tunai + DP Kas + Pelunasan Piutang + Injeksi Modal Masuk) dikurangi (Cicilan/Setoran Modal ke Owner + Pengeluaran Kas Kecil). Selisih kas (Variance) otomatis terhitung.',
      },
      {
        title: 'Penguncian Tanggal (Lock System)',
        desc: 'Setelah closing disetujui (Final/Completed), sistem otomatis mengunci tanggal tersebut sehingga tidak ada manipulasi transaksi di tanggal yang sudah ditutup.',
      },
    ],
    tips: 'Pastikan seluruh setoran cicilan modal ke Owner dan kas kecil hari tersebut sudah dicatat agar perhitungan kas laci kasir sinkron 100%.',
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
    title: '9. Manajemen Hak Akses Murni Database (RBAC) & Penugasan Multi-Cabang',
    subtitle: 'Pengaturan otorisasi granular Spatie RBAC, penugasan peran per toko, dan PIN keamanan supervisor',
    steps: [
      {
        title: 'Kelola Pengguna & Hak Akses Database (RBAC)',
        desc: 'Buka menu Pengaturan Pengguna. Daftarkan akun karyawan dan atur izin akses (Permissions) secara granular per modul (Create, Read, Write, Delete, Approve, Export, Import, PIN). Tidak ada peran yang dikunci secara kaku (hardcoded), seluruhnya dikelola dinamis melalui database.',
        link: '/apps/pengaturan-pengguna',
        linkText: 'Buka Pengaturan Pengguna',
      },
      {
        title: 'Penugasan Multi-Cabang & Peran Ganda (Branch Assignments)',
        desc: 'Satu pengguna dapat ditugaskan pada beberapa cabang sekaligus dengan peran berbeda (misal: Admin di Toko A, namun Kasir di Toko B). Fitur Switch Role di profil memungkinkan pengguna berganti konteks toko secara instan.',
      },
      {
        title: 'PIN Otorisasi Supervisor Dinamis',
        desc: 'Setiap otorisasi tindakan berisiko tinggi (diskon di bawah batas nego, void nota, penghapusan piutang) diverifikasi melalui PIN 6-digit pengguna yang memiliki hak akses approval dari database.',
      },
      {
        title: 'Jejak Audit Keamanan (Audit Trail Log)',
        desc: 'Buka Dashboard Audit untuk melacak seluruh histori aktivitas: siapa yang mengedit data, kapan waktu terjadinya, dan nilai sebelum vs sesudah diedit.',
        link: '/dashboards/audit',
        linkText: 'Buka Dashboard Audit',
      },
    ],
    tips: 'Ganti PIN supervisor secara berkala untuk menjaga kerahasiaan otorisasi tindakan kritis.',
  },
  {
    id: 'modal-roi',
    category: 'modal_roi',
    icon: 'ri-hand-coin-line',
    color: 'warning',
    title: '10. Manajemen Modal, Notifikasi Email Owner & Pengembalian ROI Cabang',
    subtitle: 'Alur terpadu penyertaan modal Owner, pengajuan dana proposal PDF, setoran laba, dan pengiriman laporan rekap ke email Owner',
    steps: [
      {
        title: 'Injeksi Modal Langsung (Owner → Cabang)',
        desc: 'Digunakan oleh Owner / Kantor Pusat untuk menyuntikkan modal awal pendirian toko atau penambahan modal kerja secara langsung. Lengkap dengan nominal Rupiah terformat otomatis, pilihan rekening bank, dan lampiran bukti transfer dana.',
        link: '/apps/branch-capitals',
        linkText: 'Buka Modal & ROI Cabang',
      },
      {
        title: 'Pengajuan Permintaan Modal Tambahan & Dokumen Proposal PDF',
        desc: 'Kepala Toko / Admin Cabang dapat mengajukan permohonan dana tambahan lengkap dengan alasan justifikasi dan lampiran dokumen resmi Proposal / RAB format PDF.',
        link: '/apps/branch-capitals',
        linkText: 'Ajukan Permintaan Modal',
      },
      {
        title: 'Verifikasi & Otorisasi Penyaluran Dana oleh Owner (Approval Workflow)',
        desc: 'Permohonan berstatus Pending masuk ke dashboard Owner. Owner dapat membaca dokumen PDF proposal, menyetujui (Approve) dengan mengunggah struk transfer penyaluran, atau menolak (Reject) dengan alasan resmi.',
      },
      {
        title: 'Setoran Pengembalian Modal & Notifikasi Email Otomatis ke Owner',
        desc: 'Cabang menyetorkan surplus laba closing shift / cicilan modal ke rekening Owner. Sistem otomatis mengirimkan Notifikasi Email Setoran Masuk ke inbox Owner lengkap dengan rincian mutasi kas.',
      },
      {
        title: 'Kirim Rekapitulasi Portofolio Modal & ROI ke Email Owner',
        desc: 'Klik tombol "Kirim Rekap Modal ke Email Owner" di dashboard eksekutif untuk mengirimkan ringkasan portofolio konsolidasi permodalan, saldo modal tertanam, dan tingkat pengembalian (% ROI) langsung ke email Owner.',
      },
      {
        title: 'Executive KPI Dashboard & Monitoring Payback Progress (% ROI)',
        desc: 'Pantau secara real-time: Total Modal Diberikan, Total Modal Dikembalikan, Sisa Modal Tertanam (Outstanding), serta progress bar Payback ROI (%) per cabang maupun konsolidasi seluruh toko.',
      },
    ],
    tips: 'Gunakan fitur Kirim Rekap Modal ke Email Owner untuk mengirimkan laporan mingguan atau bulanan secara praktis.',
  },
  {
    id: 'notifikasi-redis',
    category: 'security',
    icon: 'ri-notification-3-line',
    color: 'warning',
    title: '11. Notifikasi Real-Time (Cabang & Jabatan) & Akselerasi Redis',
    subtitle: 'Notifikasi otomatis per cabang/jabatan untuk approval modal, selisih closing kasir, mutasi stok, dan ancaman keamanan',
    steps: [
      {
        title: 'Notifikasi Terarah Berbasis Cabang (Branch-Scoped)',
        desc: 'Notifikasi transaksi operasional (injeksi modal cabang, mutasi stok masuk, piutang jatuh tempo) otomatis dikirimkan hanya kepada staf dan kasir yang bertugas di cabang terkait.',
      },
      {
        title: 'Notifikasi Terarah Berbasis Jabatan (Role-Scoped)',
        desc: 'Pengajuan permohonan modal, selisih kas closing harian, dan alert ancaman hacker dikirimkan langsung ke lonceng notifikasi Owner, Super Admin, dan Auditor.',
      },
      {
        title: 'Akselerasi In-Memory Caching & Background Queue Redis',
        desc: 'Katalog produk POS dan pengecekan blacklist IP dicache di RAM dengan respon 0ms. Tugas berat diproses di background tanpa membuat kasir menunggu.',
      },
    ],
    tips: 'Klik langsung item notifikasi di lonceng navbar untuk membuka dokumen transaksi atau approval terkait secara instan.',
  },
]

// Keyboard Shortcuts
const shortcuts = [
  { key: 'F1 / F2', name: 'Cari Produk / Scan Barcode', desc: 'Fokus instan ke kolom pencarian produk atau scan barcode scanner' },
  { key: 'F3 / F4', name: 'Pilih / Tambah Pelanggan', desc: 'Membuka drawer pelanggan terdaftar untuk transaksi kredit/tempo/kasbon' },
  { key: 'F6', name: 'Hold / Tahan Transaksi', desc: 'Menahan keranjang transaksi pelanggan saat ini ke antrean pending' },
  { key: 'F7', name: 'Daftar Transaksi Ditahan', desc: 'Membuka popup antrean transaksi tertahan untuk dimuat kembali (Resume)' },
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
