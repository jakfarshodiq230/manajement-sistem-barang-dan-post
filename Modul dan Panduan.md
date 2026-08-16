# BUKU PANDUAN PENGGUNAAN SISTEM MANAJEMEN BARANG

Sistem Manajemen Barang ini dirancang untuk menangani operasional bisnis secara menyeluruh mulai dari pusat hingga cabang. Struktur panduan ini disesuaikan persis dengan susunan menu navigasi pada sistem Anda.

---

## 1. MAIN (DASHBOARDS)
Modul ini adalah pusat kontrol pemantauan performa bisnis secara *real-time*.
* **Dashboard Analytics**: Ringkasan utama mengenai performa bisnis harian, jumlah transaksi, dan wawasan umum.
* **Dashboard Barang**: Pantauan khusus pergerakan stok, jumlah barang yang tersedia, dan metrik inventori.
* **Dashboard Audit**: Pantauan khusus untuk mengecek riwayat selisih barang, hasil stock opname, dan keamanan sistem.
* **Dashboard Penjualan**: Laporan grafis mengenai tren omset penjualan, barang terlaris, dan histori transaksi.
* **Dashboard Keuntungan**: Analisis perbandingan antara Harga Pokok Penjualan (HPP/Modal) vs Harga Jual untuk mengukur margin laba kotor/bersih. HPP dihitung secara otomatis menggunakan metode manajemen stok yang dikonfigurasi pada sistem (**FIFO**, **LIFO**, **FEFO**, atau **Average**) berdasarkan histori harga beli.

---

## 2. MANAJEMEN (MASTER DATA)
Modul ini digunakan oleh manajemen/admin untuk mendaftarkan data pokok bisnis sebelum bisa digunakan pada transaksi operasional.
* **Produk**: Mendaftarkan barang jualan. Anda bisa mengatur nama, merek, barcode (serta mencetak label A4), *status retur*, dan *tipe pajak (Include PPN/Exclude PPN)*.
* **Kategori Barang**: Mengelompokkan barang agar lebih rapi (contoh: Sembako, Elektronik).
* **Data Supplier**: Daftar pemasok (*supplier*) tempat Anda membeli barang grosir/kulakan.
* **Cabang**: Mengatur lokasi fisik bisnis Anda, baik itu bertipe **Toko (Store)** maupun **Gudang (Warehouse)**.
* **Manajemen Owner**: Mendaftarkan dan mengelola profil para pemilik bisnis/investor.
* **Data Pelanggan**: *Database* pelanggan Anda (digunakan terutama saat pelanggan berutang/pembayaran tempo di kasir).
* **Manajemen Karyawan**: Mengatur data profil staf yang bekerja di perusahaan.

---

## 3. OPERASIONAL (TRANSAKSI)
Modul harian yang digunakan oleh staf gudang dan kasir untuk perputaran barang.
* **Purchase Order**: Membuat surat pesanan pembelian barang ke *Supplier*.
* **Penerimaan Gudang**: Mencatat barang fisik yang baru tiba dari *Supplier* agar stok di sistem otomatis bertambah.
* **Inventori Cabang**: Panel bagi tiap cabang untuk melihat stok fisik mereka, serta fitur **Mutasi/Transfer Barang** antar cabang (misal dari Gudang ke Toko).
* **Kasir (POS)**: Aplikasi Point of Sale untuk melayani pembeli. Sistem menerapkan pemotongan stok otomatis sesuai dengan metode yang digunakan (**FIFO** untuk barang awal masuk, **LIFO** untuk barang terakhir masuk, atau **FEFO** untuk barang yang mendekati masa kadaluarsa). Otomatis menghitung diskon dan pajak (Include/Exclude PPN), mendukung banyak metode pembayaran (Tunai, Transfer, QRIS, Tempo), dan mencetak struk thermal.
* **Penjualan**: Rekap histori daftar struk/transaksi yang sudah sukses terjadi di kasir.
* **Data Piutang**: Memantau daftar pelanggan yang mengambil barang secara **tempo/utang**, serta mencatat pembayaran cicilannya.
* **Retur Barang**: Fasilitas untuk mengembalikan barang (baik ke supplier maupun dari pelanggan). Barang yang di-setting "Tidak Bisa Diretur" pada Master Produk akan ditolak oleh sistem.

---

## 4. LAPORAN (AUDIT & LAPORAN)
Modul pembukuan dan rekam jejak untuk menjaga keamanan aset perusahaan.
* **Stok Global**: Pemantauan jumlah total stok gabungan dari semua toko dan gudang.
* **Closing Harian**: Proses tutup buku kasir (pengecekan uang fisik vs uang di sistem) setiap pergantian *shift* atau tutup toko.
* **Riwayat Stok**: Kartu stok (Buku Besar) yang mencatat mendetail pergerakan setiap item per detik (Kapan barang masuk, terjual, hilang, dsb). Riwayat ini secara detail mencatat *batch* penerimaan, tanggal masuk, dan *expired date* untuk memastikan keakuratan prinsip **FIFO, LIFO, FEFO**, maupun **Average Cost**.
* **Stock Opname**: Audit fisik rutin. Pusat membuat jadwal, lalu tiap cabang menghitung fisik di lokasinya. Sistem lalu mengeluarkan laporan **selisih (discrepancy)**.
* **Rekap Tahunan**: Laporan agregat yang merangkum performa bisnis dalam kurun waktu 1 tahun.
* **Stok Saat Ini**: Cuplikan (*snapshot*) sisa barang secara *real-time* di waktu tersebut.
* **Pengguna**: Laporan aktivitas pengguna/karyawan.
* **Fast/Slow Moving**: Algoritma cerdas yang mendeteksi mana barang yang paling cepat laku (Fast) agar bisa disetok ulang, dan mana barang yang lama menumpuk di gudang (Slow) agar bisa diobral.

---

## 5. SISTEM (PENGATURAN)
Modul teknis tingkat dewa (hanya untuk Super Admin/IT) guna mengunci keamanan sistem.
* **Roles**: Pembuatan peran jabatan. Contoh: Role 'Kasir', 'Kepala Gudang', atau 'Admin Pusat'.
* **Permissions**: Mengatur detail hak akses yang sangat spesifik (Misal: apakah Kasir boleh melihat harga modal? Apakah Kepala Toko boleh menghapus transaksi?).
* **Modules**: Pengaturan struktur modul dan menu navigasi aplikasi.
* **Konfigurasi Bisnis**: Pengaturan inti sistem, di mana Super Admin dapat memilih metode aliran barang dan perhitungan HPP yang akan diterapkan di seluruh sistem (**FIFO, LIFO, FEFO,** atau **Average**).

---
*Catatan: Pada hampir setiap tabel data di sistem ini, Anda dapat menekan tombol Export untuk mendownload data dalam bentuk **Excel (.xlsx)** maupun **PDF**.*

---

## 6. ALUR PENGGUNAAN SISTEM (WORKFLOW)
Bagi pengguna baru yang baru menggunakan sistem ini, berikut adalah urutan langkah (SOP) dari nol hingga sistem siap beroperasi:

### Tahap 1: Pengaturan Sistem (Setup Awal)
1. Masuk ke **SISTEM** > **Konfigurasi Bisnis**: Tetapkan metode pemotongan stok (FIFO/LIFO/FEFO/Average) dan pengaturan pajak.
2. Masuk ke **SISTEM** > **Roles & Permissions**: Buat hak akses sesuai struktur organisasi (contoh: Super Admin, Kasir, Kepala Gudang).
3. Masuk ke **MANAJEMEN** > **Cabang**: Daftarkan lokasi fisik, baik yang berfungsi sebagai Gudang Pusat maupun Toko/Cabang.

### Tahap 2: Input Data Pokok (Master Data)
1. Masuk ke **MANAJEMEN** > **Manajemen Karyawan**: Daftarkan staf Anda dan berikan mereka akun serta *role* yang sudah dibuat.
2. Masuk ke **MANAJEMEN** > **Data Supplier**: Masukkan daftar pemasok atau *vendor* kulakan.
3. Masuk ke **MANAJEMEN** > **Kategori Barang**: Buat kelompok untuk merapikan rak (contoh: Makanan, Minuman, Elektronik).
4. Masuk ke **MANAJEMEN** > **Produk**: Daftarkan semua *item* yang akan dijual (Barcode, Nama, Harga Jual, Tipe Pajak). 
*(Penting: Saat mendaftarkan produk, Anda tidak menginput jumlah stok di sini. Stok fisik masuk melalui menu Penerimaan Gudang).*

### Tahap 3: Pengadaan & Saldo Awal Stok
1. Masuk ke **OPERASIONAL** > **Purchase Order (PO)**: Buat draf pemesanan barang ke supplier.
2. Masuk ke **OPERASIONAL** > **Penerimaan Gudang**: Ketika barang dari supplier sudah tiba di lokasi, lakukan penerimaan di sini. **Proses inilah yang memunculkan stok di dalam sistem**.
3. Masuk ke **OPERASIONAL** > **Inventori Cabang**: Lakukan mutasi/transfer stok dari Gudang Pusat ke Toko Cabang agar kasir di toko memiliki persediaan fisik untuk dijual.

### Tahap 4: Transaksi Penjualan
1. Kasir cabang masuk ke **OPERASIONAL** > **Kasir (POS)**.
2. Lakukan transaksi dengan men-scan *barcode* produk atau mencari nama barang. Selesaikan pembayaran (Tunai/QRIS/Tempo). Stok akan langsung terpotong otomatis sesuai metode yang dikonfigurasi (misal: FIFO).
3. Jika pelanggan mengambil barang secara tempo, status tagihan bisa dipantau dan dibayar cicilannya lewat menu **Data Piutang**.

### Tahap 5: Laporan & Audit
1. **Closing**: Di akhir *shift*, kasir wajib melakukan **Closing Harian** di menu Laporan untuk menyetorkan uang fisik yang ada di laci.
2. **Pantau Kinerja**: Pemilik/Manajer dapat memantau hasil hari itu langsung melalui menu **MAIN** (Dashboard Penjualan & Keuntungan).
3. **Audit**: Secara berkala (misal sebulan sekali), gunakan fitur **Stock Opname** pada menu Laporan untuk mencocokkan stok fisik vs stok di komputer.
