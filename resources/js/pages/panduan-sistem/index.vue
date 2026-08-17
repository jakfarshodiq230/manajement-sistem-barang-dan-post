<script setup>
import { ref } from 'vue'

const panel = ref([0])
</script>

<template>
  <div>
    <VRow>
      <VCol cols="12">
        <h2 class="text-h4 mb-4">Panduan Sistem Manajement Barang & POS</h2>
        <p class="text-body-1 mb-6">
          Selamat datang di halaman Panduan Sistem. Panduan ini dirancang untuk membantu Anda memahami seluruh alur kerja aplikasi, mulai dari pengelolaan Master Data, Transaksi Kasir, hingga Pelaporan dan Pengaturan Sistem. Klik pada masing-masing bab di bawah ini untuk membaca lebih lanjut.
        </p>

        <VExpansionPanels v-model="panel" multiple>
          <!-- Bab 1: Master Data -->
          <VExpansionPanel>
            <VExpansionPanelTitle class="text-h6 font-weight-bold">
              Bab 1: Master Data (Kategori, Produk & Supplier)
            </VExpansionPanelTitle>
            <VExpansionPanelText class="pt-4">
              <p><strong>Master Data</strong> adalah fondasi dari sistem ini. Sebelum Anda bisa melakukan transaksi atau pembelian barang, Anda wajib mengisi Master Data terlebih dahulu.</p>
              <ul class="ms-5 mb-4">
                <li class="mb-2"><strong>Kategori Barang:</strong> Gunakan menu ini untuk mengelompokkan barang (misalnya: Elektronik, Pakaian, Makanan).</li>
                <li class="mb-2"><strong>Produk:</strong> Di sinilah Anda mendaftarkan item barang baru. Setiap produk wajib memiliki Barcode/SKU, nama, harga beli, dan harga jual.</li>
                <li class="mb-2"><strong>Data Supplier:</strong> Daftarkan vendor atau pemasok barang Anda di sini agar memudahkan pelacakan saat melakukan <em>Purchase Order</em> (PO).</li>
                <li class="mb-2"><strong>Data Pelanggan:</strong> Daftarkan data pelanggan tetap Anda, terutama mereka yang sering berhutang (agar bisa dilacak di menu Piutang).</li>
              </ul>
            </VExpansionPanelText>
          </VExpansionPanel>

          <!-- Bab 2: Manajemen Gudang & Stok -->
          <VExpansionPanel>
            <VExpansionPanelTitle class="text-h6 font-weight-bold">
              Bab 2: Manajemen Gudang & Stok
            </VExpansionPanelTitle>
            <VExpansionPanelText class="pt-4">
              <p>Menu ini digunakan untuk mengatur arus keluar masuk barang, mulai dari pembelian ke Supplier hingga distribusi antar Cabang.</p>
              <ul class="ms-5 mb-4">
                <li class="mb-2"><strong>Purchase Order (PO):</strong> Buat dokumen pemesanan barang ke Supplier. Status PO dapat dilacak hingga barang tiba.</li>
                <li class="mb-2"><strong>Penerimaan Gudang:</strong> Setelah barang dari PO tiba, catat penerimaannya di sini. Stok global akan otomatis bertambah!</li>
                <li class="mb-2"><strong>Mutasi Stok:</strong> Gunakan menu ini untuk memindahkan/mengirim barang dari Gudang Pusat ke Cabang, atau antar Cabang.</li>
                <li class="mb-2"><strong>Inventori Cabang:</strong> Memantau stok secara spesifik di tiap-tiap cabang secara <em>real-time</em>.</li>
              </ul>
            </VExpansionPanelText>
          </VExpansionPanel>

          <!-- Bab 3: Transaksi & Kasir (POS) -->
          <VExpansionPanel>
            <VExpansionPanelTitle class="text-h6 font-weight-bold">
              Bab 3: Transaksi & Kasir (POS)
            </VExpansionPanelTitle>
            <VExpansionPanelText class="pt-4">
              <p>Fasilitas Point of Sale (POS) digunakan langsung oleh Kasir untuk melayani pelanggan di lapangan.</p>
              <ul class="ms-5 mb-4">
                <li class="mb-2"><strong>Kasir (POS):</strong> Layar kasir yang dioptimalkan untuk kecepatan. Ketik nama barang atau <em>scan barcode</em> untuk memasukkan barang ke keranjang.</li>
                <li class="mb-2"><strong>Metode Pembayaran:</strong> Kasir mendukung Tunai, Transfer Bank, dan QRIS.</li>
                <li class="mb-2"><strong>Transaksi & Penjualan:</strong> Seluruh nota transaksi terekam di menu ini. Anda juga bisa mencetak ulang kuitansi dari sini.</li>
                <li class="mb-2"><strong>Retur Barang:</strong> Jika ada pelanggan yang mengembalikan barang, proses pengembalian (dan pengembalian dana/stok) dicatat di menu Retur.</li>
                <li class="mb-2"><strong>Data Piutang:</strong> Jika pelanggan berhutang (Kasbon), datanya akan masuk ke sini. Anda dapat memantau Sisa Hutang dan mencatat Pembayaran Cicilan (Pay).</li>
              </ul>
            </VExpansionPanelText>
          </VExpansionPanel>

          <!-- Bab 4: Laporan & Audit -->
          <VExpansionPanel>
            <VExpansionPanelTitle class="text-h6 font-weight-bold">
              Bab 4: Laporan & Audit
            </VExpansionPanelTitle>
            <VExpansionPanelText class="pt-4">
              <p>Menu ini khusus untuk para Manajer dan Pemilik (Owner) untuk memantau kesehatan dan performa bisnis.</p>
              <ul class="ms-5 mb-4">
                <li class="mb-2"><strong>Closing Harian:</strong> Rutinitas wajib Kasir untuk menyetorkan laporan pendapatan dan mencocokkan uang di laci (*cash drawer*) pada akhir *shift*.</li>
                <li class="mb-2"><strong>Stok Global & Stok Saat Ini:</strong> Laporan komprehensif seluruh nilai aset stok Anda.</li>
                <li class="mb-2"><strong>Fast/Slow Moving:</strong> Analisis AI/Sistem untuk mendeteksi barang mana yang paling laris (Fast) dan mana yang kurang diminati (Slow) agar Anda tidak salah menyetok barang.</li>
                <li class="mb-2"><strong>Riwayat Stok:</strong> Melacak setiap pergerakan sehelai barang (kapan masuk, kapan keluar, kapan terjual).</li>
              </ul>
            </VExpansionPanelText>
          </VExpansionPanel>

          <!-- Bab 5: Pengaturan Akses & Keamanan -->
          <VExpansionPanel>
            <VExpansionPanelTitle class="text-h6 font-weight-bold">
              Bab 5: Pengaturan Sistem & Karyawan
            </VExpansionPanelTitle>
            <VExpansionPanelText class="pt-4">
              <p>Area khusus Developer/Super Admin untuk mengatur sistem.</p>
              <ul class="ms-5 mb-4">
                <li class="mb-2"><strong>Pengguna & Roles:</strong> Tambah user baru, berikan <em>Role</em> (Kasir, Admin, dll) dan tentukan hak akses masing-masing lewat <em>Permissions</em>.</li>
                <li class="mb-2"><strong>Manajemen Cabang:</strong> Tambah, edit, atau tutup operasional cabang tertentu.</li>
                <li class="mb-2"><strong>Pengaturan Struk:</strong> Mengubah tulisan <em>Header</em>, <em>Footer</em>, dan catatan khusus yang akan dicetak di kertas struk pelanggan.</li>
                <li class="mb-2"><strong>Manajemen Karyawan (HRIS):</strong> Mengelola data kepegawaian internal Anda.</li>
                <li class="mb-2"><strong>PIN Kasir (Security):</strong> Setiap kasir bisa membuat PIN 6 angka di halaman profilnya untuk otorisasi khusus (seperti membatalkan pesanan atau nego harga khusus).</li>
              </ul>
            </VExpansionPanelText>
          </VExpansionPanel>

        </VExpansionPanels>
      </VCol>
    </VRow>
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Panduan Sistem
</route>
