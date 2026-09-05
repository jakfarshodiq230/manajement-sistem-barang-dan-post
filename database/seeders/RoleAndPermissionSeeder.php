<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ============================================================
        // 1. CREATE MODULES
        // ============================================================

        // --- 1. DASHBOARDS (parent) ---
        $mDashboard = Module::updateOrCreate(['slug' => 'dashboards'], [
            'name' => 'Dashboards', 'sequence' => 1, 'category' => 'Utama', 'status' => 'Aktif', 'icon' => 'ri-home-smile-line', 'parent_id' => null
        ]);
        $mDashboardAnalytics = Module::updateOrCreate(['slug' => 'dashboards/analytics'], [
            'name' => 'Dashboard Analytics', 'parent_id' => $mDashboard->id, 'sequence' => 1, 'status' => 'Aktif', 'icon' => 'ri-bar-chart-box-line'
        ]);
        $mDashboardPenjualan = Module::updateOrCreate(['slug' => 'dashboards/penjualan'], [
            'name' => 'Dashboard Penjualan', 'parent_id' => $mDashboard->id, 'sequence' => 2, 'status' => 'Aktif', 'icon' => 'ri-shopping-cart-2-line'
        ]);
        $mDashboardBarang = Module::updateOrCreate(['slug' => 'dashboards/barang'], [
            'name' => 'Dashboard Barang', 'parent_id' => $mDashboard->id, 'sequence' => 3, 'status' => 'Aktif', 'icon' => 'ri-box-3-line'
        ]);
        $mDashboardKeuntungan = Module::updateOrCreate(['slug' => 'dashboards/keuntungan'], [
            'name' => 'Dashboard Keuntungan', 'parent_id' => $mDashboard->id, 'sequence' => 4, 'status' => 'Aktif', 'icon' => 'ri-money-dollar-circle-line'
        ]);
        $mDashboardAudit = Module::updateOrCreate(['slug' => 'dashboards/audit'], [
            'name' => 'Dashboard Audit', 'parent_id' => $mDashboard->id, 'sequence' => 5, 'status' => 'Aktif', 'icon' => 'ri-shield-check-line'
        ]);

        // --- 2. MASTER DATA ---
        $mMasterData = Module::updateOrCreate(['slug' => 'master-data'], [
            'name' => 'Master Data', 'sequence' => 2, 'category' => 'Master Data', 'status' => 'Aktif', 'icon' => 'ri-database-2-line', 'parent_id' => null
        ]);
        $mProduk = Module::updateOrCreate(['slug' => 'master-data-produk'], [
            'name' => 'Data Produk & Barang', 'parent_id' => $mMasterData->id, 'sequence' => 1, 'status' => 'Aktif', 'icon' => 'ri-box-3-line'
        ]);
        $mKategoriBarang = Module::updateOrCreate(['slug' => 'kategori-barang'], [
            'name' => 'Kategori Barang', 'parent_id' => $mMasterData->id, 'sequence' => 2, 'status' => 'Aktif', 'icon' => 'ri-list-check'
        ]);
        $mDataSupplier = Module::updateOrCreate(['slug' => 'suppliers'], [
            'name' => 'Data Supplier', 'parent_id' => $mMasterData->id, 'sequence' => 3, 'status' => 'Aktif', 'icon' => 'ri-truck-line'
        ]);
        $mDataCustomer = Module::updateOrCreate(['slug' => 'customers'], [
            'name' => 'Data Pelanggan', 'parent_id' => $mMasterData->id, 'sequence' => 4, 'status' => 'Aktif', 'icon' => 'ri-user-smile-line'
        ]);
        $mCabang = Module::updateOrCreate(['slug' => 'apps/branches'], [
            'name' => 'Data Cabang & Toko', 'parent_id' => $mMasterData->id, 'sequence' => 5, 'status' => 'Aktif', 'icon' => 'ri-store-3-line'
        ]);
        $mManajemenOwner = Module::updateOrCreate(['slug' => 'apps/owners'], [
            'name' => 'Manajemen Owner', 'parent_id' => $mMasterData->id, 'sequence' => 6, 'status' => 'Aktif', 'icon' => 'ri-user-star-line'
        ]);
        $mManajemenKaryawan = Module::updateOrCreate(['slug' => 'apps/employees'], [
            'name' => 'Manajemen Karyawan', 'parent_id' => $mMasterData->id, 'sequence' => 7, 'status' => 'Aktif', 'icon' => 'ri-user-2-line'
        ]);
        $mRekeningBank = Module::updateOrCreate(['slug' => 'bank-accounts'], [
            'name' => 'Rekening Bank & Kas', 'parent_id' => $mMasterData->id, 'sequence' => 8, 'status' => 'Aktif', 'icon' => 'ri-bank-card-line'
        ]);
        $mPriceAdjustments = Module::updateOrCreate(['slug' => 'price-adjustments'], [
            'name' => 'Penyesuaian Harga Periode', 'parent_id' => $mMasterData->id, 'sequence' => 9, 'status' => 'Aktif', 'icon' => 'ri-price-tag-3-line'
        ]);

        // --- 3. OPERASIONAL & TRANSAKSI ---
        $mTransaksi = Module::updateOrCreate(['slug' => 'transaksi-main'], [
            'name' => 'Operasional & Transaksi', 'sequence' => 3, 'category' => 'Operasional', 'status' => 'Aktif', 'icon' => 'ri-shopping-bag-3-line', 'parent_id' => null
        ]);
        $mKasirPOS = Module::updateOrCreate(['slug' => 'pos'], [
            'name' => 'Kasir (POS)', 'parent_id' => $mTransaksi->id, 'sequence' => 1, 'status' => 'Aktif', 'icon' => 'ri-bank-card-line'
        ]);
        $mPenjualan = Module::updateOrCreate(['slug' => 'transaksi'], [
            'name' => 'Riwayat Penjualan', 'parent_id' => $mTransaksi->id, 'sequence' => 2, 'status' => 'Aktif', 'icon' => 'ri-exchange-dollar-line'
        ]);
        $mPurchaseOrder = Module::updateOrCreate(['slug' => 'purchase-orders'], [
            'name' => 'Purchase Order (PO)', 'parent_id' => $mTransaksi->id, 'sequence' => 3, 'status' => 'Aktif', 'icon' => 'ri-file-list-3-line'
        ]);
        $mPenerimaanGudang = Module::updateOrCreate(['slug' => 'penerimaan-barang'], [
            'name' => 'Penerimaan Gudang', 'parent_id' => $mTransaksi->id, 'sequence' => 4, 'status' => 'Aktif', 'icon' => 'ri-download-2-line'
        ]);
        $mInventoriCabang = Module::updateOrCreate(['slug' => 'inventori-cabang'], [
            'name' => 'Inventori Cabang', 'parent_id' => $mTransaksi->id, 'sequence' => 5, 'status' => 'Aktif', 'icon' => 'ri-store-2-line'
        ]);
        $mMutasiStok = Module::updateOrCreate(['slug' => 'mutasi-stok'], [
            'name' => 'Mutasi Stok Antar Cabang', 'parent_id' => $mTransaksi->id, 'sequence' => 6, 'status' => 'Aktif', 'icon' => 'ri-arrow-left-right-line'
        ]);
        $mReturBarang = Module::updateOrCreate(['slug' => 'retur'], [
            'name' => 'Retur Barang', 'parent_id' => $mTransaksi->id, 'sequence' => 7, 'status' => 'Aktif', 'icon' => 'ri-arrow-go-back-line'
        ]);

        // --- 4. KEUANGAN & KAS ---
        $mKeuangan = Module::updateOrCreate(['slug' => 'keuangan-main'], [
            'name' => 'Keuangan & Kas', 'sequence' => 4, 'category' => 'Keuangan', 'status' => 'Aktif', 'icon' => 'ri-wallet-3-line', 'parent_id' => null
        ]);
        $mPusatKeuangan = Module::updateOrCreate(['slug' => 'keuangan'], [
            'name' => 'Pusat Keuangan & Kas', 'parent_id' => $mKeuangan->id, 'sequence' => 1, 'status' => 'Aktif', 'icon' => 'ri-dashboard-line'
        ]);
        $mKasKecil = Module::updateOrCreate(['slug' => 'kas-kecil'], [
            'name' => 'Kas Kecil (Petty Cash)', 'parent_id' => $mKeuangan->id, 'sequence' => 2, 'status' => 'Aktif', 'icon' => 'ri-wallet-3-line'
        ]);
        $mPiutang = Module::updateOrCreate(['slug' => 'receivables'], [
            'name' => 'Buku Piutang Pelanggan', 'parent_id' => $mKeuangan->id, 'sequence' => 3, 'status' => 'Aktif', 'icon' => 'ri-hand-coin-line'
        ]);
        $mHutang = Module::updateOrCreate(['slug' => 'payables'], [
            'name' => 'Buku Hutang Supplier', 'parent_id' => $mKeuangan->id, 'sequence' => 4, 'status' => 'Aktif', 'icon' => 'ri-money-dollar-circle-line'
        ]);
        $mBranchCapitals = Module::updateOrCreate(['slug' => 'apps/branch-capitals'], [
            'name' => 'Modal & ROI Cabang', 'parent_id' => $mKeuangan->id, 'sequence' => 5, 'status' => 'Aktif', 'icon' => 'ri-funds-line'
        ]);

        // --- 5. AKUNTANSI & PEMBUKUAN ---
        $mAkuntansi = Module::updateOrCreate(['slug' => 'akuntansi-main'], [
            'name' => 'Akuntansi', 'sequence' => 5, 'category' => 'Akuntansi', 'status' => 'Aktif', 'icon' => 'ri-book-read-line', 'parent_id' => null
        ]);
        $mPusatAkuntansi = Module::updateOrCreate(['slug' => 'akuntansi'], [
            'name' => 'Pusat Akuntansi', 'parent_id' => $mAkuntansi->id, 'sequence' => 1, 'status' => 'Aktif', 'icon' => 'ri-dashboard-line'
        ]);
        $mCOA = Module::updateOrCreate(['slug' => 'akuntansi/coa'], [
            'name' => 'Bagan Akun (COA)', 'parent_id' => $mAkuntansi->id, 'sequence' => 2, 'status' => 'Aktif', 'icon' => 'ri-node-tree'
        ]);
        $mJurnalUmum = Module::updateOrCreate(['slug' => 'akuntansi/jurnal'], [
            'name' => 'Jurnal Umum', 'parent_id' => $mAkuntansi->id, 'sequence' => 3, 'status' => 'Aktif', 'icon' => 'ri-file-list-3-line'
        ]);
        $mBukuBesar = Module::updateOrCreate(['slug' => 'akuntansi/buku-besar'], [
            'name' => 'Buku Besar', 'parent_id' => $mAkuntansi->id, 'sequence' => 4, 'status' => 'Aktif', 'icon' => 'ri-book-2-line'
        ]);
        $mLaporanNeraca = Module::updateOrCreate(['slug' => 'akuntansi/neraca'], [
            'name' => 'Neraca & Laporan Keuangan', 'parent_id' => $mAkuntansi->id, 'sequence' => 5, 'status' => 'Aktif', 'icon' => 'ri-scales-3-line'
        ]);

        // --- 6. AUDIT & LAPORAN ---
        $mAuditLaporan = Module::updateOrCreate(['slug' => 'audit-laporan'], [
            'name' => 'Audit & Laporan', 'sequence' => 6, 'category' => 'Laporan', 'status' => 'Aktif', 'icon' => 'ri-file-chart-line', 'parent_id' => null
        ]);
        $mPusatLaporan = Module::updateOrCreate(['slug' => 'laporan'], [
            'name' => 'Pusat Laporan', 'parent_id' => $mAuditLaporan->id, 'sequence' => 1, 'status' => 'Aktif', 'icon' => 'ri-folder-chart-line'
        ]);
        $mStokGlobal = Module::updateOrCreate(['slug' => 'laporan/stok-global'], [
            'name' => 'Stok Global Multi-Cabang', 'parent_id' => $mAuditLaporan->id, 'sequence' => 2, 'status' => 'Aktif', 'icon' => 'ri-archive-line'
        ]);
        $mStokAging = Module::updateOrCreate(['slug' => 'laporan/stok-aging'], [
            'name' => 'Analisis Stok & Usia (FEFO)', 'parent_id' => $mAuditLaporan->id, 'sequence' => 3, 'status' => 'Aktif', 'icon' => 'ri-bar-chart-grouped-line'
        ]);
        $mRiwayatStok = Module::updateOrCreate(['slug' => 'audit-laporan/riwayat-stok'], [
            'name' => 'Riwayat Kartu Stok', 'parent_id' => $mAuditLaporan->id, 'sequence' => 4, 'status' => 'Aktif', 'icon' => 'ri-history-line'
        ]);
        $mStockOpname = Module::updateOrCreate(['slug' => 'audit/stock-opname'], [
            'name' => 'Stock Opname', 'parent_id' => $mAuditLaporan->id, 'sequence' => 5, 'status' => 'Aktif', 'icon' => 'ri-file-list-3-line'
        ]);
        $mClosingHarian = Module::updateOrCreate(['slug' => 'audit/closing-harian'], [
            'name' => 'Closing Shift Kasir', 'parent_id' => $mAuditLaporan->id, 'sequence' => 6, 'status' => 'Aktif', 'icon' => 'ri-calculator-line'
        ]);
        $mRekapTahunan = Module::updateOrCreate(['slug' => 'audit/rekap'], [
            'name' => 'Rekap Keuangan & Laba Rugi', 'parent_id' => $mAuditLaporan->id, 'sequence' => 7, 'status' => 'Aktif', 'icon' => 'ri-file-chart-line'
        ]);

        // --- 7. PENGATURAN ---
        $mPengaturan = Module::updateOrCreate(['slug' => 'pengaturan'], [
            'name' => 'Pengaturan', 'sequence' => 7, 'category' => 'Sistem', 'status' => 'Aktif', 'icon' => 'ri-settings-3-line', 'parent_id' => null
        ]);
        $mPengguna = Module::updateOrCreate(['slug' => 'apps/pengaturan-pengguna'], [
            'name' => 'Pengguna & PIN Kasir', 'parent_id' => $mPengaturan->id, 'sequence' => 1, 'status' => 'Aktif', 'icon' => 'ri-user-settings-line'
        ]);
        $mRoles = Module::updateOrCreate(['slug' => 'apps/roles'], [
            'name' => 'Roles & Akses', 'parent_id' => $mPengaturan->id, 'sequence' => 2, 'status' => 'Aktif', 'icon' => 'ri-admin-line'
        ]);
        $mPermissions = Module::updateOrCreate(['slug' => 'apps/permissions'], [
            'name' => 'Hak Akses (Permissions)', 'parent_id' => $mPengaturan->id, 'sequence' => 3, 'status' => 'Aktif', 'icon' => 'ri-shield-keyhole-line'
        ]);
        $mModules = Module::updateOrCreate(['slug' => 'apps/modules'], [
            'name' => 'Manajemen Modul', 'parent_id' => $mPengaturan->id, 'sequence' => 4, 'status' => 'Aktif', 'icon' => 'ri-function-line'
        ]);
        $mSecurityLogs = Module::updateOrCreate(['slug' => 'security-logs'], [
            'name' => 'Log Keamanan & Akses IP', 'parent_id' => $mPengaturan->id, 'sequence' => 5, 'status' => 'Aktif', 'icon' => 'ri-shield-line'
        ]);
        $mPengaturanStruk = Module::updateOrCreate(['slug' => 'pengaturan-struk'], [
            'name' => 'Format Struk Kasir', 'parent_id' => $mPengaturan->id, 'sequence' => 6, 'status' => 'Aktif', 'icon' => 'ri-printer-line'
        ]);
        $mPanduanSistem = Module::updateOrCreate(['slug' => 'panduan-sistem'], [
            'name' => 'Panduan Sistem', 'parent_id' => $mPengaturan->id, 'sequence' => 7, 'status' => 'Aktif', 'icon' => 'ri-book-open-line'
        ]);

        // ============================================================
        // 2. CREATE PERMISSIONS (per module × 8 actions)
        // ============================================================
        $actions = ['Read', 'Write', 'Create', 'Delete', 'Approve', 'Export', 'Import', 'Validate'];

        $modulePermissions = [
            // Dashboard
            'Dashboards'                  => $mDashboard->id,
            'Dashboard Analytics'         => $mDashboardAnalytics->id,
            'Dashboard Penjualan'         => $mDashboardPenjualan->id,
            'Dashboard Barang'            => $mDashboardBarang->id,
            'Dashboard Keuntungan'        => $mDashboardKeuntungan->id,
            'Dashboard Audit'             => $mDashboardAudit->id,
            // Master Data
            'Master Data'                 => $mMasterData->id,
            'Data Produk & Barang'        => $mProduk->id,
            'Kategori Barang'             => $mKategoriBarang->id,
            'Data Supplier'               => $mDataSupplier->id,
            'Data Pelanggan'              => $mDataCustomer->id,
            'Data Cabang & Toko'          => $mCabang->id,
            'Manajemen Owner'             => $mManajemenOwner->id,
            'Manajemen Karyawan'          => $mManajemenKaryawan->id,
            'Rekening Bank & Kas'         => $mRekeningBank->id,
            'Penyesuaian Harga Periode'   => $mPriceAdjustments->id,
            // Transaksi
            'Operasional & Transaksi'     => $mTransaksi->id,
            'Kasir (POS)'                 => $mKasirPOS->id,
            'Riwayat Penjualan'           => $mPenjualan->id,
            'Purchase Order (PO)'         => $mPurchaseOrder->id,
            'Penerimaan Gudang'           => $mPenerimaanGudang->id,
            'Inventori Cabang'            => $mInventoriCabang->id,
            'Mutasi Stok Antar Cabang'    => $mMutasiStok->id,
            'Retur Barang'                => $mReturBarang->id,
            // Keuangan
            'Keuangan & Kas'              => $mKeuangan->id,
            'Pusat Keuangan & Kas'        => $mPusatKeuangan->id,
            'Kas Kecil (Petty Cash)'      => $mKasKecil->id,
            'Buku Piutang Pelanggan'      => $mPiutang->id,
            'Buku Hutang Supplier'        => $mHutang->id,
            'Modal & ROI Cabang'          => $mBranchCapitals->id,
            // Akuntansi
            'Akuntansi'                   => $mAkuntansi->id,
            'Pusat Akuntansi'             => $mPusatAkuntansi->id,
            'Bagan Akun (COA)'            => $mCOA->id,
            'Jurnal Umum'                 => $mJurnalUmum->id,
            'Buku Besar'                  => $mBukuBesar->id,
            'Neraca & Laporan Keuangan'   => $mLaporanNeraca->id,
            // Audit & Laporan
            'Audit & Laporan'             => $mAuditLaporan->id,
            'Pusat Laporan'               => $mPusatLaporan->id,
            'Stok Global Multi-Cabang'    => $mStokGlobal->id,
            'Analisis Stok & Usia (FEFO)' => $mStokAging->id,
            'Riwayat Kartu Stok'          => $mRiwayatStok->id,
            'Stock Opname'                => $mStockOpname->id,
            'Closing Shift Kasir'         => $mClosingHarian->id,
            'Rekap Keuangan & Laba Rugi'  => $mRekapTahunan->id,
            // Pengaturan
            'Pengaturan'                  => $mPengaturan->id,
            'Pengguna & PIN Kasir'        => $mPengguna->id,
            'Roles & Akses'               => $mRoles->id,
            'Hak Akses (Permissions)'     => $mPermissions->id,
            'Manajemen Modul'             => $mModules->id,
            'Log Keamanan & Akses IP'     => $mSecurityLogs->id,
            'Format Struk Kasir'          => $mPengaturanStruk->id,
            'Panduan Sistem'              => $mPanduanSistem->id,
        ];

        foreach ($modulePermissions as $moduleName => $moduleId) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(
                    ['name' => "$moduleName $action", 'guard_name' => 'web'],
                    ['module_id' => $moduleId]
                );
            }
        }

        // Special permissions (legacy)
        $legacyPerms = [
            ['name' => 'view dashboard',   'module_id' => $mDashboard->id],
            ['name' => 'view transactions', 'module_id' => $mTransaksi->id],
            ['name' => 'create transactions', 'module_id' => $mTransaksi->id],
            ['name' => 'approve Documents', 'module_id' => $mTransaksi->id],
            ['name' => 'validate Documents', 'module_id' => $mTransaksi->id],
            ['name' => 'approve level 1', 'module_id' => $mTransaksi->id],
            ['name' => 'approve level 2', 'module_id' => $mTransaksi->id],
            ['name' => 'manage users',    'module_id' => $mPengguna->id],
            ['name' => 'manage branches', 'module_id' => $mPengguna->id], // map to pengguna for now
            ['name' => 'manage products', 'module_id' => $mProduk->id],
            ['name' => 'view audit logs', 'module_id' => $mDashboardAudit->id],
        ];
        foreach ($legacyPerms as $p) {
            Permission::firstOrCreate(['name' => $p['name'], 'guard_name' => 'web'], ['module_id' => $p['module_id']]);
        }

        // ============================================================
        // 3. CREATE ROLES & ASSIGN PERMISSIONS
        // ============================================================

        // 1. Super Admin — gets ALL permissions
        $roleSuperAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $roleSuperAdmin->syncPermissions(Permission::all());

        // 1.5 Dev — gets ALL permissions
        $roleDev = Role::firstOrCreate(['name' => 'Dev', 'guard_name' => 'web']);
        $roleDev->syncPermissions(Permission::all());

        // 2. Admin Pusat — nearly full access except Modules management
        $roleAdminPusat = Role::firstOrCreate(['name' => 'Admin Pusat', 'guard_name' => 'web']);
        $roleAdminPusat->syncPermissions(Permission::whereNotIn('name', [
            'Modules Write', 'Modules Create', 'Modules Delete',
            'Roles Write', 'Roles Create', 'Roles Delete',
            'Permissions Write', 'Permissions Create', 'Permissions Delete',
        ])->get());

        // 3. Admin Cabang — manage their branch transactions
        $roleAdminCabang = Role::firstOrCreate(['name' => 'Admin Cabang', 'guard_name' => 'web']);
        $adminCabangPerms = collect([
            'Dashboards Read', 'Dashboard Analytics Read', 'Dashboard Penjualan Read',
            'Dashboard Barang Read', 'Dashboard Keuntungan Read',
            'Master Data Read', 'Data Produk & Barang Read', 'Data Produk & Barang Write', 'Data Produk & Barang Create',
            'Kategori Barang Read', 'Data Supplier Read', 'Data Pelanggan Read', 'Data Pelanggan Write', 'Data Pelanggan Create',
            'Manajemen Karyawan Read', 'Manajemen Karyawan Write', 'Manajemen Karyawan Create',
            'Rekening Bank & Kas Read', 'Penyesuaian Harga Periode Read', 'Penyesuaian Harga Periode Write', 'Penyesuaian Harga Periode Create', 'Penyesuaian Harga Periode Approve',
            'Operasional & Transaksi Read', 'Operasional & Transaksi Write', 'Operasional & Transaksi Create', 'Operasional & Transaksi Approve',
            'Inventori Cabang Read', 'Inventori Cabang Write', 'Inventori Cabang Create',
            'Purchase Order (PO) Read', 'Purchase Order (PO) Create', 'Purchase Order (PO) Write', 'Purchase Order (PO) Approve',
            'Penerimaan Gudang Read', 'Penerimaan Gudang Create', 'Penerimaan Gudang Write', 'Penerimaan Gudang Approve',
            'Riwayat Penjualan Read', 'Riwayat Penjualan Export',
            'Retur Barang Read', 'Retur Barang Create',
            'Kasir (POS) Read', 'Kasir (POS) Create',
            'Mutasi Stok Antar Cabang Read', 'Mutasi Stok Antar Cabang Create', 'Mutasi Stok Antar Cabang Write', 'Mutasi Stok Antar Cabang Approve', 'Mutasi Stok Antar Cabang Validate',
            'Keuangan & Kas Read', 'Pusat Keuangan & Kas Read', 'Kas Kecil (Petty Cash) Read', 'Kas Kecil (Petty Cash) Create', 'Kas Kecil (Petty Cash) Write',
            'Buku Piutang Pelanggan Read', 'Buku Piutang Pelanggan Write', 'Buku Piutang Pelanggan Create',
            'Buku Hutang Supplier Read', 'Modal & ROI Cabang Read', 'Modal & ROI Cabang Create',
            'Audit & Laporan Read', 'Pusat Laporan Read',
            'Closing Shift Kasir Read', 'Closing Shift Kasir Create', 'Closing Shift Kasir Write',
            'Stock Opname Read', 'Stock Opname Create', 'Stock Opname Write',
            'Stok Global Multi-Cabang Read', 'Analisis Stok & Usia (FEFO) Read', 'Riwayat Kartu Stok Read', 'Rekap Keuangan & Laba Rugi Read',
            'Pengaturan Read', 'Pengguna & PIN Kasir Read',
            'view dashboard', 'view transactions', 'create transactions', 'approve level 1',
        ]);
        $roleAdminCabang->syncPermissions(Permission::whereIn('name', $adminCabangPerms)->get());

        // 4. Kasir — POS, sales, kas kecil, and piutang payments
        $roleKasir = Role::firstOrCreate(['name' => 'Kasir', 'guard_name' => 'web']);
        $kasirPerms = collect([
            'Dashboards Read', 'Dashboard Analytics Read',
            'Kasir (POS) Read', 'Kasir (POS) Create', 'Kasir (POS) Write',
            'Riwayat Penjualan Read',
            'Inventori Cabang Read',
            'Rekening Bank & Kas Read',
            'Kas Kecil (Petty Cash) Read', 'Kas Kecil (Petty Cash) Create',
            'Buku Piutang Pelanggan Read', 'Buku Piutang Pelanggan Write',
            'Closing Shift Kasir Read', 'Closing Shift Kasir Create',
            'view dashboard', 'view transactions', 'create transactions',
        ]);
        $roleKasir->syncPermissions(Permission::whereIn('name', $kasirPerms)->get());

        // 5. Auditor — read-only audit and financial access
        $roleAuditor = Role::firstOrCreate(['name' => 'Auditor', 'guard_name' => 'web']);
        $auditorPerms = collect([
            'Dashboards Read', 'Dashboard Analytics Read', 'Dashboard Penjualan Read',
            'Dashboard Barang Read', 'Dashboard Keuntungan Read', 'Dashboard Audit Read',
            'Audit & Laporan Read', 'Pusat Laporan Read',
            'Closing Shift Kasir Read', 'Stock Opname Read', 'Rekap Keuangan & Laba Rugi Read',
            'Stok Global Multi-Cabang Read', 'Analisis Stok & Usia (FEFO) Read', 'Riwayat Kartu Stok Read',
            'Riwayat Penjualan Read', 'Purchase Order (PO) Read', 'Penerimaan Gudang Read',
            'Rekening Bank & Kas Read', 'Penyesuaian Harga Periode Read', 'Kas Kecil (Petty Cash) Read', 'Buku Piutang Pelanggan Read', 'Buku Hutang Supplier Read', 'Modal & ROI Cabang Read',
            'Akuntansi Read', 'Pusat Akuntansi Read', 'Bagan Akun (COA) Read', 'Jurnal Umum Read', 'Buku Besar Read', 'Neraca & Laporan Keuangan Read',
            'view dashboard', 'view transactions', 'view audit logs',
        ]);
        $roleAuditor->syncPermissions(Permission::whereIn('name', $auditorPerms)->get());

        $this->command->info('Roles & Permissions seeded: ' . Permission::count() . ' permissions, ' . Role::count() . ' roles.');
    }
}
