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
  { id: 'modal_roi', title: '7. Modal & ROI Cabang', icon: 'ri-hand-coin-line' },
]

// 1. Alur Sistem Lengkap & Step-by-Step
const systemWorkflows = [
  {
    id: 'wf-master',
    category: 'master',
    icon: 'ri-database-2-line',
    color: 'primary',
    title: 'Tahap 1: Inisialisasi Master Data & Inventori Cabang (3 Tingkat Harga & Pajak)',
    subtitle: 'Langkah awal mempersiapkan kategori, master produk SKU, struktur harga jual/nego, supplier, dan pelanggan.',
    steps: [
      {
        no: 1,
        title: 'Buat Kategori Barang & Satuan',
        desc: 'Buka menu Kategori Barang. Buat kategori utama (misal: Sembako, Aki Baterai, Elektronik) dan tentukan satuan produk standar (Pcs, Dus, Box, Karton). Kategori mempermudah filter laporan dan audit stock opname.',
        link: '/kategori-barang',
        linkText: 'Buka Kategori Barang',
      },
      {
        no: 2,
        title: 'Daftarkan Master Data Produk & Barcode SKU',
        desc: 'Buka Master Data Produk. Masukkan nama barang, SKU/Barcode unik, kategori, satuan, metode stok (FIFO/FEFO/LIFO), dan isi konversi kemasan (misal: 1 Dus = 24 Pcs).',
        link: '/master-data-produk',
        linkText: 'Buka Master Produk',
      },
      {
        no: 3,
        title: 'Atur 3 Tingkat Harga & Kalkulator Markup di Inventori Cabang',
        desc: 'Buka menu Inventori & Harga Cabang. Setiap produk memiliki 3 lapis harga:\n• 1. Harga Modal (HPP Real): Otomatis mencakup diskon supplier bertingkat dan PPN Masukan 11%.\n• 2. Harga Jual Normal: Gunakan tombol kalkulasi markup cepat (+20%, +25%, +30%, +35%, +40%).\n• 3. Harga Nego Minimum: Batas harga terendah kasir (+10%, +15%, +20% Modal).',
        link: '/inventori-cabang',
        linkText: 'Buka Inventori Cabang',
      },
      {
        no: 4,
        title: 'Input Data Supplier & Data Pelanggan',
        desc: 'Daftarkan vendor pemasok untuk PO dan pelanggan tetap lengkap dengan limit kredit piutang (Credit Limit) dan jatuh tempo.',
        link: '/suppliers',
        linkText: 'Buka Supplier & Pelanggan',
      },
    ],
    tips: 'Gunakan tombol kalkulasi markup cepat di panel Inventori Cabang agar harga jual dan batas tawar kasir langsung terhitung otomatis.',
  },
  {
    id: 'wf-gudang',
    category: 'gudang',
    icon: 'ri-truck-line',
    color: 'info',
    title: 'Tahap 2: Pengadaan (PO Multi-Diskon D1..D5), Penerimaan Gudang & Mutasi',
    subtitle: 'Alur pasokan dari Purchase Order dengan diskon bertingkat hingga 5 level, kalkulasi HPP real, hingga distribusi cabang.',
    steps: [
      {
        no: 1,
        title: 'Buat PO dengan Multi-Column Diskon (D1 s/d D5 + Potongan Rp)',
        desc: 'Buka menu Purchase Order. Masukkan No. Faktur Supplier, pilih Supplier & Cabang. Input baris barang dengan diskon bertingkat D1..D5 atau ketik langsung di kolom Format Cepat misal 10+5+2+2+1. Sistem menghitung DPP Netto secara compound.',
        link: '/purchase-orders',
        linkText: 'Buka Purchase Order',
      },
      {
        no: 2,
        title: 'Penerimaan Barang & Pencatatan Nomor Batch',
        desc: 'Saat barang tiba, buka menu Penerimaan Barang. Cocokkan fisik dengan faktur, masukkan Nomor Batch dan Expired Date. HPP Real per pcs otomatis terupdate di Master Produk dan stok bertambah (+).',
        link: '/penerimaan-barang',
        linkText: 'Buka Penerimaan Barang',
      },
      {
        no: 3,
        title: 'Mutasi Stok Antar Cabang & Surat Jalan Digital QR Code',
        desc: 'Buka menu Mutasi Stok untuk mentransfer barang antar cabang. Dilengkapi Surat Jalan cetak/PDF dan Tanda Tangan Digital 3 Pihak dengan QR Code Verifikasi Keamanan.',
        link: '/mutasi-stok',
        linkText: 'Buka Mutasi Stok',
      },
    ],
    tips: 'Untuk barang makanan/obat/baterai, input tanggal kadaluarsa dengan teliti agar sistem FEFO memprioritaskan stok yang mendekati expired terlebih dahulu.',
  },
  {
    id: 'wf-pos',
    category: 'pos',
    icon: 'ri-shopping-cart-2-line',
    color: 'success',
    title: 'Tahap 3: Operasional Kasir (POS), Diskon Total & Otorisasi Nego',
    subtitle: 'Panduan kasir dari buka shift kas, scan transaksi, diskon total bon, otorisasi nego batas bawah, hingga cetak struk.',
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
        desc: 'Ketik nama barang atau scan barcode (F2). Pilih pelanggan jika transaksi langganan/tempo (F4) atau pelanggan umum.',
        link: '/pos',
        linkText: 'Ke Halaman Kasir',
      },
      {
        no: 3,
        title: 'Input Diskon Total Bon & Tawar Menawar (Nego)',
        desc: 'Kasir dapat menginput nominal Diskon Total langsung pada ringkasan keranjang. Jika kasir memberi harga nego di bawah Harga Nego Minimum, sistem meminta input PIN Supervisor (Master PIN: 123456).',
        link: '/pos',
        linkText: 'Lihat POS',
      },
      {
        no: 4,
        title: 'Proses Pembayaran, Cetak Struk & Hold Transaksi',
        desc: 'Pilih metode bayar: Tunai (F9 untuk uang pas), Transfer Bank, QRIS, atau Tempo/Piutang. Tekan F6 untuk menahan transaksi (Hold Bill) jika pelanggan ingin mengambil barang tambahan, dan F7 untuk membuka kembali daftar transaksi tertahan (Held Bills).',
        link: '/pos',
        linkText: 'Buka Kasir POS',
      },
    ],
    tips: 'Gunakan tombol pintasan keyboard: F1/F2 (Cari/Scan), F3/F4 (Pelanggan), F6 (Hold Bon), F7 (Bon Ditahan), F8 (Checkout), F9 (Uang Pas), dan Enter (Konfirmasi Bayar).',
  },
  {
    id: 'wf-piutang',
    category: 'piutang_retur',
    icon: 'ri-exchange-dollar-line',
    color: 'warning',
    title: 'Tahap 4: Pengelolaan Buku Piutang, Notifikasi Email & Retur Barang',
    subtitle: 'Pencatatan tagihan pelanggan tempo, pengiriman invoice & kwitansi email otomatis, serta retur barang.',
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
        title: 'Kirim Surat Tagihan Faktur (Invoice) ke Email Pelanggan',
        desc: 'Klik tombol "Kirim Email Tagihan" pada drawer detail piutang. Sistem menyusun rincian tagihan faktur penjualan resmi dan mengirimkannya langsung ke email pelanggan.',
        link: '/receivables',
        linkText: 'Buka Piutang',
      },
      {
        no: 3,
        title: 'Pencatatan Pembayaran Cicilan & Email Kwitansi Otomatis',
        desc: 'Klik tombol Bayar Piutang. Masukkan nominal yang disetorkan (Tunai/Transfer). Sistem otomatis memotong saldo sisa piutang, mencatat kas masuk, dan mengirimkan Kwitansi Tanda Terima Resmi ke email pelanggan.',
        link: '/receivables',
        linkText: 'Kelola Piutang',
      },
      {
        no: 4,
        title: 'Pengingat Jatuh Tempo Harian & Audit Trail Log Email',
        desc: 'Scheduler otomatis harian mengirimkan pengingat H-3 dan tagihan menunggak ke email pelanggan. Seluruh aktivitas email tercatat pada log dengan fitur Kirim Ulang (Retry).',
      },
      {
        no: 5,
        title: 'Proses Retur Penjualan / Retur Pembelian',
        desc: 'Buka menu Retur. Masukkan nomor faktur/invoice penjualan, pilih barang yang diretur, dan cantumkan alasan. Stok akan otomatis dikembalikan dan saldo kas/piutang disesuaikan.',
        link: '/retur',
        linkText: 'Buka Menu Retur',
      },
    ],
    tips: 'Gunakan filter "Jatuh Tempo" di Buku Piutang dan tombol Kirim Email Tagihan untuk mempercepat penagihan piutang pelanggan.',
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
  {
    id: 'wf-modal-roi',
    category: 'modal_roi',
    icon: 'ri-hand-coin-line',
    color: 'warning',
    title: 'Tahap 7: Manajemen Modal, Permohonan Dana (PDF) & Pengembalian ROI Cabang',
    subtitle: 'Alur komprehensif investasi modal Owner, pengajuan dana toko dengan proposal PDF, otorisasi transfer, dan pengembalian dividen/laba closing shift.',
    steps: [
      {
        no: 1,
        title: 'Injeksi Modal Langsung (Owner → Cabang)',
        desc: 'Owner / Kantor Pusat menginput penyertaan modal awal toko atau penambahan modal kerja secara langsung. Dilengkapi format mata uang Rupiah otomatis, pilihan rekening bank, dan upload bukti transfer dana.',
        link: '/apps/branch-capitals',
        linkText: 'Buka Modal Cabang',
      },
      {
        no: 2,
        title: 'Pengajuan Permintaan Modal Tambahan & Upload Proposal PDF',
        desc: 'Kepala Toko / Admin Cabang mengajukan permohonan modal kerja (tambahan stok, perbaikan aset toko, kas darurat operasional) disertai justifikasi dan lampiran dokumen resmi Proposal / RAB dalam format PDF atau gambar.',
        link: '/apps/branch-capitals',
        linkText: 'Ajukan Permintaan Modal',
      },
      {
        no: 3,
        title: 'Verifikasi & Otorisasi Penyaluran Dana oleh Owner (Approval Workflow)',
        desc: 'Owner menelaah proposal PDF pemohon. Jika disetujui, Owner mengklik tombol Approve serta melampirkan bukti transfer penyaluran dana. Jika ditolak, Owner mengisi alasan penolakan secara resmi.',
        link: '/apps/branch-capitals',
        linkText: 'Approval Modal',
      },
      {
        no: 4,
        title: 'Setoran Pengembalian Modal & Cicilan Closing Harian ke Owner',
        desc: 'Cabang toko menyetorkan surplus laba harian closing shift atau cicilan pengembalian modal ke rekening Owner. Setoran modal tunai hari itu otomatis terintegrasi ke Audit Closing Harian (/audit/closing-harian) sebagai pengurang kas fisik laci kasir.',
        link: '/apps/branch-capitals',
        linkText: 'Setor Pengembalian Modal',
      },
      {
        no: 5,
        title: 'Executive KPI Dashboard & Monitoring Payback Progress (% ROI)',
        desc: 'Owner memantau Total Modal Diberikan, Total Modal Dikembalikan, Sisa Modal Tertanam (Outstanding Capital), dan persentase pengembalian modal (% ROI) secara real-time per cabang.',
        link: '/apps/branch-capitals',
        linkText: 'Lihat KPI Modal',
      },
    ],
    tips: 'Setiap pengajuan permohonan modal dapat melampirkan berkas PDF Proposal agar peruntukan anggaran dapat diaudit dan disetujui dengan cepat.',
  },
]

// 2. Tanya Jawab Populer (FAQ) & Solusi Kendala
const faqs = [
  {
    id: 1,
    category: 'gudang',
    question: 'Bagaimana cara menghitung HPP modal jika supplier memberikan diskon bertingkat (misal: 10% + 5% + 2%) dan PPN 11%?',
    answer: 'Pada menu Purchase Order (PO), sistem menghitung harga bersih secara compound:\n• Netto Kemasan = Harga Bruto x (1 - D1%) x (1 - D2%) x (1 - D3%) x (1 - D4%) x (1 - D5%) - Diskon Rp.\n• Anda juga dapat mengetik langsung di kolom Format Cepat (misal: "10+5+2+2+1").\n• Live HPP Modal per Pcs dihitung: Subtotal Faktur / (Qty Beli x Isi Satuan). Angka modal ini otomatis menjadi HPP Real di Master Produk dan POS Kasir.',
  },
  {
    id: 2,
    category: 'master',
    question: 'Kenapa di tabel Inventori Cabang tertulis "Harga Final (Netto)" padahal di struk supplier ada PPN?',
    answer: 'PPN 11% dari supplier (PPN Masukan) SUDAH MELEKAT & MASUK KE DALAM HARGA MODAL (HPP Real). Kolom "Pajak Penjualan POS" di Inventori Cabang adalah pengaturan apakah kasir toko ingin menambahkan PPN 11% lagi di struk kasir ke pembeli akhir (+ PPN 11%) atau harga jual toko sudah bersih/final ke pembeli (Harga Final Netto / 0%).',
  },
  {
    id: 3,
    category: 'pos',
    question: 'Bagaimana cara kasir memberikan Diskon Total pada struk bon belanja di POS?',
    answer: 'Pada layar POS kasir, tepat di atas nominal TOTAL terdapat kolom "Diskon Total". Kasir dapat langsung mengetik nominal potongan harga (misal Rp 25.000). Total tagihan bersih otomatis terpotong seketika.',
  },
  {
    id: 4,
    category: 'master',
    question: 'Bagaimana cara menentukan Harga Jual Normal dan Harga Nego Minimum dari modal?',
    answer: 'Buka menu Inventori & Harga Cabang, klik Edit pada produk:\n1. Harga Jual Normal: Klik tombol pintas markup (+20%, +25%, +30%, +35%, +40%) dari modal.\n2. Harga Nego Minimum: Klik tombol pintas margin minimal (+10%, +15%, +20% Modal). Jika saat tawar-menawar pembeli menawar di bawah batas ini, kasir wajib meminta otorisasi PIN Supervisor (Master PIN: 123456).',
  },
  {
    id: 5,
    category: 'pos',
    question: 'Printer thermal struk kasir tidak mencetak atau kertas keluar polos/kosong?',
    answer: '1. Pastikan kabel USB/Bluetooth printer terhubung dengan baik ke komputer.\n2. Cek apakah gulungan kertas thermal tidak terbalik (sisi mengkilap harus menghadap head pemanas printer).\n3. Buka menu Pengaturan Struk di sistem untuk melakukan uji coba cetak (Test Print).\n4. Pastikan driver printer thermal (seperti POS-58 atau POS-80) sudah terpasang dan diset sebagai default printer.',
  },
  {
    id: 6,
    category: 'gudang',
    question: 'Apa perbedaan metode pengeluaran stok FIFO, FEFO, dan LIFO di sistem?',
    answer: '• FIFO (First In First Out): Barang yang pertama kali masuk gudang akan otomatis dikeluarkan pertama kali saat transaksi kasir.\n• FEFO (First Expired First Out): Barang dengan tanggal kadaluarsa paling dekat akan diprioritaskan keluar terlebih dahulu.\n• LIFO (Last In First Out): Barang yang terakhir masuk dikeluarkan pertama kali.\nMetode ini diatur per produk pada Master Data Produk.',
  },
  {
    id: 7,
    category: 'opname',
    question: 'Bagaimana cara melakukan penyesuaian saat ada selisih stok (Stock Opname)?',
    answer: 'Buka menu Audit & Laporan > Stock Opname. Buat sesi opname baru, pilih kategori atau seluruh barang, lalu masukkan jumlah stok fisik yang dihitung di gudang. Sistem akan menampilkan selisih (surplus/minus) beserta nilai rupiahnya dan melakukan penyesuaian otomatis setelah disetujui (Approved).',
  },
  {
    id: 8,
    category: 'master',
    question: 'Bagaimana jika lupa PIN Supervisor untuk otorisasi diskon/void transaksi?',
    answer: 'Owner atau Super Admin dapat mereset PIN Supervisor melalui menu Pengaturan > Daftar Pengguna. Master PIN default sistem adalah 123456 jika belum diubah.',
  },
  {
    id: 9,
    category: 'master',
    question: 'Bagaimana cara mengganti peran atau cabang aktif saat login multi-cabang?',
    answer: 'Klik ikon avatar profil Anda di pojok kanan atas, lalu pilih menu "Ganti Peran". Sistem akan menampilkan daftar jabatan dan cabang yang ditugaskan kepada Anda. Anda dapat memilih "Semua Cabang yang Ditugaskan (Multi-Cabang)" untuk melihat rekapitulasi seluruh cabang, atau memilih salah satu cabang spesifik (misal: Cabang Bandung) untuk fokus pada operasional cabang tersebut.',
  },
  {
    id: 10,
    category: 'laporan',
    question: 'Bagaimana cara mengekspor Laporan Laba Rugi, Rekap Tahunan, dan Neraca ke format Excel atau PDF?',
    answer: 'Buka menu Laporan > Laporan Laba Rugi, Rekapitulasi Tahunan, atau Laporan Penjualan. Tentukan tahun/bulan dan filter cabang yang diinginkan, lalu klik tombol "Ekspor Excel (.xlsx)" atau "Cetak PDF". Laporan PDF otomatis menyajikan rekap finansial, HPP COGS, beban kas kecil, selisih audit kasir, mutasi stok, hingga rincian harian secara instan.',
  },
  {
    id: 11,
    category: 'modal_roi',
    question: 'Bagaimana cara cabang mengajukan permohonan modal tambahan ke Owner beserta dokumen Proposal PDF?',
    answer: 'Buka menu Modal & ROI Cabang, lalu klik tombol "Ajukan Permintaan Modal". Pilih cabang pemohon, kategori kebutuhan (misal: Permintaan Tambahan Stok / Renovasi), nominal yang diajukan, alasan kebutuhan, serta unggah dokumen resmi Proposal/RAB format PDF atau gambar. Permohonan akan berstatus Pending dan langsung muncul di dashboard Owner.',
  },
  {
    id: 12,
    category: 'modal_roi',
    question: 'Bagaimana proses Owner menyetujui (Approve) atau membatalkan (Void) transaksi modal?',
    answer: 'Owner dapat mengklik tombol "Approve" pada transaksi pending untuk menyetujui permohonan dan melampirkan bukti transfer bank penyaluran dana. Jika ada kesalahan input atau mutasi bank keliru, Owner dapat menggunakan tombol "Batalkan Persetujuan (Void)" dengan menyertakan alasan pembatalan resmi sehingga saldo modal cabang terkoreksi kembali secara otomatis.',
  },
  {
    id: 13,
    category: 'modal_roi',
    question: 'Bagaimana cara cabang mengembalikan modal dari laba shift kasir toko?',
    answer: 'Klik tombol "Setor Pengembalian Modal", pilih kategori (Setoran Laba Closing Shift / Cicilan Pengembalian Modal), masukkan nominal, tanggal setor, bank tujuan Owner, dan lampirkan struk bukti transfer. Setelah Owner menyetujui (Approve), sisa modal tertanam cabang akan berkurang dan persentase payback (% ROI) akan meningkat.',
  },
  {
    id: 14,
    category: 'pos',
    question: 'Bagaimana cara kerja fitur Tahan Transaksi (Hold Bill) dan Membuka Kembali (Resume) saat antrean kasir ramai?',
    answer: 'Saat melayani pembeli yang ingin mengambil barang tambahan di rak toko:\n1. Tekan tombol keyboard F6 atau klik tombol "Tahan (F6)" di POS untuk menyimpan keranjang belanja ke antrean tertahan (Held Bills).\n2. Kasir dapat langsung melayani transaksi pembeli berikutnya tanpa kehilangan item pembeli sebelumnya.\n3. Tekan tombol F7 atau klik tombol "Ditahan (X)" di bagian atas kasir untuk membuka daftar antrean, lalu klik "Ambil Kembali" (Resume) untuk melanjutkan transaksi pembeli pertama.',
  },
  {
    id: 15,
    category: 'piutang_retur',
    question: 'Bagaimana cara mengirim Surat Tagihan Piutang dan Kwitansi Cicilan ke Email Pelanggan?',
    answer: '1. Buka menu Buku Piutang (/receivables), klik "Bayar / Detail" pada transaksi terkait.\n2. Klik tombol "Kirim Email Tagihan", masukkan/konfirmasi alamat email pelanggan, lalu klik "Kirim Sekarang".\n3. Saat kasir mencatat cicilan piutang, sistem otomatis mengirimkan Kwitansi Tanda Terima Resmi ke email pelanggan secara otomatis.',
  },
  {
    id: '16',
    category: 'modal_roi',
    question: 'Bagaimana cara Owner menerima laporan email otomatis untuk setoran modal dan rekapitulasi portofolio ROI?',
    answer: '1. Setiap kali cabang mencatat Setor Pengembalian Modal, sistem otomatis mengirimkan email notifikasi mutasi ke inbox email Owner.\n2. Owner/Direksi juga dapat mengklik tombol "Kirim Rekap Modal ke Email Owner" di dashboard Modal & ROI Cabang untuk mendapatkan laporan portofolio konsolidasi permodalan dan tingkat pengembalian (ROI) secara berkala.',
  },
  {
    id: '17',
    category: 'piutang_retur',
    question: 'Bagaimana cara memantau status pengiriman email dan melakukan Kirim Ulang (Retry) jika email gagal terkirim?',
    answer: 'Pada drawer detail transaksi di Buku Piutang atau Modal Cabang, terdapat tabel "Riwayat Log Pengiriman Email" (email_logs). Jika status email "Gagal" (misal: koneksi SMTP down), sistem menampilkan pesan error dan menyediakan tombol [ 🔄 Kirim Ulang ] (Retry) yang bisa diklik langsung kapan saja.',
  },
]

// 3. Pintasan Keyboard POS
const shortcuts = [
  { key: 'F1 / F2', desc: 'Fokus instan ke kolom pencarian produk atau scan barcode scanner' },
  { key: 'F3 / F4', desc: 'Buka drawer input / daftar pelanggan (Walk-In maupun Pelanggan Terdaftar)' },
  { key: 'F6', desc: 'Hold / Tahan transaksi saat ini ke antrean pending (Held Bills)' },
  { key: 'F7', desc: 'Buka popup antrean transaksi ditahan untuk diambil kembali (Resume)' },
  { key: 'F8', desc: 'Buka pop-up pembayaran kasir (Checkout) saat keranjang belanja terisi' },
  { key: 'F9', desc: 'Pilih nominal Uang Pas sesuai total tagihan nota belanja secara instan' },
  { key: 'Esc', desc: 'Tutup pop-up pembayaran / modal aktif / batalkan aksi seketika' },
  { key: 'Enter', desc: 'Konfirmasi pembayaran, simpan transaksi kasir, dan cetak struk thermal' },
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
