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

        // --- DASHBOARD (parent) ---
        $mDashboard = Module::firstOrCreate(['slug' => 'dashboards'], [
            'name' => 'Dashboards', 'sequence' => 1, 'category' => 'Main', 'status' => 'Aktif', 'icon' => 'ri-home-smile-line'
        ]);
        $mDashboardAnalytics = Module::firstOrCreate(['slug' => 'dashboards/analytics'], [
            'name' => 'Dashboard Analytics', 'parent_id' => $mDashboard->id, 'sequence' => 1, 'status' => 'Aktif', 'icon' => 'ri-bar-chart-box-line'
        ]);
        $mDashboardPenjualan = Module::firstOrCreate(['slug' => 'dashboards/penjualan'], [
            'name' => 'Dashboard Penjualan', 'parent_id' => $mDashboard->id, 'sequence' => 2, 'status' => 'Aktif', 'icon' => 'ri-shopping-cart-2-line'
        ]);
        $mDashboardBarang = Module::firstOrCreate(['slug' => 'dashboards/barang'], [
            'name' => 'Dashboard Barang', 'parent_id' => $mDashboard->id, 'sequence' => 3, 'status' => 'Aktif', 'icon' => 'ri-box-3-line'
        ]);
        $mDashboardKeuntungan = Module::firstOrCreate(['slug' => 'dashboards/keuntungan'], [
            'name' => 'Dashboard Keuntungan', 'parent_id' => $mDashboard->id, 'sequence' => 4, 'status' => 'Aktif', 'icon' => 'ri-money-dollar-circle-line'
        ]);
        $mDashboardAudit = Module::firstOrCreate(['slug' => 'dashboards/audit'], [
            'name' => 'Dashboard Audit', 'parent_id' => $mDashboard->id, 'sequence' => 5, 'status' => 'Aktif', 'icon' => 'ri-shield-check-line'
        ]);

        // --- MASTER DATA ---
        $mMasterData = Module::firstOrCreate(['slug' => 'master-data'], [
            'name' => 'Master Data', 'sequence' => 2, 'category' => 'Manajemen', 'status' => 'Aktif', 'icon' => 'ri-database-2-line'
        ]);
        $mProduk = Module::firstOrCreate(['slug' => 'master-data-produk'], [
            'name' => 'Produk', 'parent_id' => $mMasterData->id, 'sequence' => 1, 'status' => 'Aktif', 'icon' => 'ri-box-3-line'
        ]);
        $mKategoriBarang = Module::firstOrCreate(['slug' => 'kategori-barang'], [
            'name' => 'Kategori Barang', 'parent_id' => $mMasterData->id, 'sequence' => 2, 'status' => 'Aktif', 'icon' => 'ri-list-check'
        ]);
        $mDataSupplier = Module::firstOrCreate(['slug' => 'suppliers'], [
            'name' => 'Data Supplier', 'parent_id' => $mMasterData->id, 'sequence' => 3, 'status' => 'Aktif', 'icon' => 'ri-truck-line'
        ]);
        $mCabang = Module::firstOrCreate(['slug' => 'apps/branches'], [
            'name' => 'Cabang', 'parent_id' => $mMasterData->id, 'sequence' => 4, 'status' => 'Aktif', 'icon' => 'ri-store-3-line'
        ]);
        $mManajemenOwner = Module::firstOrCreate(['slug' => 'apps/owners'], [
            'name' => 'Manajemen Owner', 'parent_id' => $mMasterData->id, 'sequence' => 5, 'status' => 'Aktif', 'icon' => 'ri-user-star-line'
        ]);
        $mManajemenKaryawan = Module::firstOrCreate(['slug' => 'apps/employees'], [
            'name' => 'Manajemen Karyawan', 'parent_id' => $mMasterData->id, 'sequence' => 6, 'status' => 'Aktif', 'icon' => 'ri-user-2-line'
        ]);
        $mRekeningBank = Module::firstOrCreate(['slug' => 'bank-accounts'], [
            'name' => 'Rekening Bank', 'parent_id' => $mMasterData->id, 'sequence' => 7, 'status' => 'Aktif', 'icon' => 'ri-bank-card-line'
        ]);

        // --- TRANSAKSI & OPERASIONAL ---
        $mTransaksi = Module::firstOrCreate(['slug' => 'transaksi-main'], [
            'name' => 'Transaksi', 'sequence' => 3, 'category' => 'Operasional', 'status' => 'Aktif', 'icon' => 'ri-shopping-cart-line'
        ]);
        $mPurchaseOrder = Module::firstOrCreate(['slug' => 'purchase-orders'], [
            'name' => 'Purchase Order', 'parent_id' => $mTransaksi->id, 'sequence' => 1, 'status' => 'Aktif', 'icon' => 'ri-file-list-3-line'
        ]);
        $mPenerimaanGudang = Module::firstOrCreate(['slug' => 'penerimaan-barang'], [
            'name' => 'Penerimaan Gudang', 'parent_id' => $mTransaksi->id, 'sequence' => 2, 'status' => 'Aktif', 'icon' => 'ri-download-2-line'
        ]);
        $mInventoriCabang = Module::firstOrCreate(['slug' => 'inventori-cabang'], [
            'name' => 'Inventori Cabang', 'parent_id' => $mTransaksi->id, 'sequence' => 3, 'status' => 'Aktif', 'icon' => 'ri-store-2-line'
        ]);
        $mKasirPOS = Module::firstOrCreate(['slug' => 'pos'], [
            'name' => 'Kasir (POS)', 'parent_id' => $mTransaksi->id, 'sequence' => 4, 'status' => 'Aktif', 'icon' => 'ri-bank-card-line'
        ]);
        $mPenjualan = Module::firstOrCreate(['slug' => 'transaksi'], [
            'name' => 'Penjualan', 'parent_id' => $mTransaksi->id, 'sequence' => 5, 'status' => 'Aktif', 'icon' => 'ri-exchange-dollar-line'
        ]);
        $mReturBarang = Module::firstOrCreate(['slug' => 'retur'], [
            'name' => 'Retur Barang', 'parent_id' => $mTransaksi->id, 'sequence' => 6, 'status' => 'Aktif', 'icon' => 'ri-arrow-go-back-line'
        ]);
        $mMutasiStok = Module::firstOrCreate(['slug' => 'mutasi-stok'], [
            'name' => 'Mutasi Stok', 'parent_id' => $mTransaksi->id, 'sequence' => 7, 'status' => 'Aktif', 'icon' => 'ri-arrow-left-right-line'
        ]);

        // --- KEUANGAN & KAS ---
        $mKeuangan = Module::firstOrCreate(['slug' => 'keuangan'], [
            'name' => 'Keuangan', 'sequence' => 4, 'category' => 'Keuangan', 'status' => 'Aktif', 'icon' => 'ri-wallet-3-line'
        ]);
        $mKasKecil = Module::firstOrCreate(['slug' => 'kas-kecil'], [
            'name' => 'Kas Kecil', 'parent_id' => $mKeuangan->id, 'sequence' => 1, 'status' => 'Aktif', 'icon' => 'ri-wallet-3-line'
        ]);
        $mPiutang = Module::firstOrCreate(['slug' => 'receivables'], [
            'name' => 'Buku Piutang', 'parent_id' => $mKeuangan->id, 'sequence' => 2, 'status' => 'Aktif', 'icon' => 'ri-hand-coin-line'
        ]);
        $mHutang = Module::firstOrCreate(['slug' => 'payables'], [
            'name' => 'Hutang Pembelian', 'parent_id' => $mKeuangan->id, 'sequence' => 3, 'status' => 'Aktif', 'icon' => 'ri-money-dollar-circle-line'
        ]);
        $mBranchCapitals = Module::firstOrCreate(['slug' => 'apps/branch-capitals'], [
            'name' => 'Setoran Modal & ROI', 'parent_id' => $mKeuangan->id, 'sequence' => 4, 'status' => 'Aktif', 'icon' => 'ri-funds-line'
        ]);

        // --- AUDIT & LAPORAN ---
        $mAuditLaporan = Module::firstOrCreate(['slug' => 'audit-laporan'], [
            'name' => 'Audit & Laporan', 'sequence' => 5, 'category' => 'Laporan', 'status' => 'Aktif', 'icon' => 'ri-file-chart-line'
        ]);
        $mPusatLaporan = Module::firstOrCreate(['slug' => 'laporan'], [
            'name' => 'Pusat Laporan', 'parent_id' => $mAuditLaporan->id, 'sequence' => 1, 'status' => 'Aktif', 'icon' => 'ri-file-chart-line'
        ]);
        $mClosingHarian = Module::firstOrCreate(['slug' => 'audit/closing-harian'], [
            'name' => 'Closing Harian', 'parent_id' => $mAuditLaporan->id, 'sequence' => 2, 'status' => 'Aktif', 'icon' => 'ri-file-text-line'
        ]);
        $mStockOpname = Module::firstOrCreate(['slug' => 'audit/stock-opname'], [
            'name' => 'Stock Opname', 'parent_id' => $mAuditLaporan->id, 'sequence' => 3, 'status' => 'Aktif', 'icon' => 'ri-file-list-3-line'
        ]);
        $mRekapTahunan = Module::firstOrCreate(['slug' => 'audit/rekap'], [
            'name' => 'Rekap Tahunan', 'parent_id' => $mAuditLaporan->id, 'sequence' => 4, 'status' => 'Aktif', 'icon' => 'ri-book-2-line'
        ]);
        $mRiwayatStok = Module::firstOrCreate(['slug' => 'audit-laporan/riwayat-stok'], [
            'name' => 'Riwayat Stok', 'parent_id' => $mAuditLaporan->id, 'sequence' => 5, 'status' => 'Aktif', 'icon' => 'ri-history-line'
        ]);
        $mStokSaatIni = Module::firstOrCreate(['slug' => 'audit-laporan/stok-saat-ini'], [
            'name' => 'Stok Saat Ini', 'parent_id' => $mAuditLaporan->id, 'sequence' => 6, 'status' => 'Aktif', 'icon' => 'ri-archive-line'
        ]);
        $mFastSlowMoving = Module::firstOrCreate(['slug' => 'audit-laporan/fast-slow-moving'], [
            'name' => 'Fast/Slow Moving', 'parent_id' => $mAuditLaporan->id, 'sequence' => 7, 'status' => 'Aktif', 'icon' => 'ri-bar-chart-grouped-line'
        ]);

        // --- PENGATURAN ---
        $mPengaturan = Module::firstOrCreate(['slug' => 'pengaturan'], [
            'name' => 'Pengaturan', 'sequence' => 6, 'category' => 'Sistem', 'status' => 'Aktif', 'icon' => 'ri-settings-3-line'
        ]);
        $mPengguna = Module::firstOrCreate(['slug' => 'pengaturan-pengguna'], [
            'name' => 'Pengguna', 'parent_id' => $mPengaturan->id, 'sequence' => 1, 'status' => 'Aktif', 'icon' => 'ri-user-settings-line'
        ]);
        $mRoles = Module::firstOrCreate(['slug' => 'apps/roles'], [
            'name' => 'Roles', 'parent_id' => $mPengaturan->id, 'sequence' => 2, 'status' => 'Aktif', 'icon' => 'ri-admin-line'
        ]);
        $mPermissions = Module::firstOrCreate(['slug' => 'apps/permissions'], [
            'name' => 'Permissions', 'parent_id' => $mPengaturan->id, 'sequence' => 3, 'status' => 'Aktif', 'icon' => 'ri-shield-keyhole-line'
        ]);
        $mModules = Module::firstOrCreate(['slug' => 'apps/modules'], [
            'name' => 'Modules', 'parent_id' => $mPengaturan->id, 'sequence' => 4, 'status' => 'Aktif', 'icon' => 'ri-function-line'
        ]);

        // ============================================================
        // 2. CREATE PERMISSIONS (per module × 8 actions)
        // ============================================================
        $actions = ['Read', 'Write', 'Create', 'Delete', 'Approve', 'Export', 'Import', 'Validate'];

        $modulePermissions = [
            // Dashboard
            'Dashboards'           => $mDashboard->id,
            'Dashboard Analytics'  => $mDashboardAnalytics->id,
            'Dashboard Penjualan'  => $mDashboardPenjualan->id,
            'Dashboard Barang'     => $mDashboardBarang->id,
            'Dashboard Keuntungan' => $mDashboardKeuntungan->id,
            'Dashboard Audit'      => $mDashboardAudit->id,
            // Master Data
            'Master Data'          => $mMasterData->id,
            'Produk'               => $mProduk->id,
            'Kategori Barang'      => $mKategoriBarang->id,
            'Data Supplier'        => $mDataSupplier->id,
            'Cabang'               => $mCabang->id,
            'Manajemen Owner'      => $mManajemenOwner->id,
            'Manajemen Karyawan'   => $mManajemenKaryawan->id,
            'Rekening Bank'        => $mRekeningBank->id,
            // Transaksi
            'Transaksi'            => $mTransaksi->id,
            'Purchase Order'       => $mPurchaseOrder->id,
            'Penerimaan Gudang'    => $mPenerimaanGudang->id,
            'Inventori Cabang'     => $mInventoriCabang->id,
            'Kasir (POS)'          => $mKasirPOS->id,
            'Penjualan'            => $mPenjualan->id,
            'Retur Barang'         => $mReturBarang->id,
            'Mutasi Stok'          => $mMutasiStok->id,
            // Keuangan
            'Keuangan'             => $mKeuangan->id,
            'Kas Kecil'            => $mKasKecil->id,
            'Buku Piutang'         => $mPiutang->id,
            'Hutang Pembelian'     => $mHutang->id,
            'Setoran Modal & ROI'  => $mBranchCapitals->id,
            // Audit & Laporan
            'Audit & Laporan'      => $mAuditLaporan->id,
            'Pusat Laporan'        => $mPusatLaporan->id,
            'Closing Harian'       => $mClosingHarian->id,
            'Stock Opname'         => $mStockOpname->id,
            'Rekap Tahunan'        => $mRekapTahunan->id,
            'Riwayat Stok'         => $mRiwayatStok->id,
            'Stok Saat Ini'        => $mStokSaatIni->id,
            'Fast/Slow Moving'     => $mFastSlowMoving->id,
            // Pengaturan
            'Pengaturan'           => $mPengaturan->id,
            'Pengguna'             => $mPengguna->id,
            'Roles'                => $mRoles->id,
            'Permissions'          => $mPermissions->id,
            'Modules'              => $mModules->id,
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
            'Master Data Read', 'Produk Read', 'Produk Write', 'Produk Create',
            'Kategori Barang Read', 'Data Supplier Read',
            'Manajemen Karyawan Read', 'Manajemen Karyawan Write', 'Manajemen Karyawan Create',
            'Rekening Bank Read',
            'Transaksi Read', 'Transaksi Write', 'Transaksi Create', 'Transaksi Approve',
            'Inventori Cabang Read', 'Inventori Cabang Write', 'Inventori Cabang Create',
            'Purchase Order Read', 'Purchase Order Create', 'Purchase Order Write', 'Purchase Order Approve',
            'Penerimaan Gudang Read', 'Penerimaan Gudang Create', 'Penerimaan Gudang Write', 'Penerimaan Gudang Approve',
            'Penjualan Read', 'Penjualan Export',
            'Retur Barang Read', 'Retur Barang Create',
            'Kasir (POS) Read', 'Kasir (POS) Create',
            'Mutasi Stok Read', 'Mutasi Stok Create', 'Mutasi Stok Write', 'Mutasi Stok Approve', 'Mutasi Stok Validate',
            'Keuangan Read', 'Kas Kecil Read', 'Kas Kecil Create', 'Kas Kecil Write',
            'Buku Piutang Read', 'Buku Piutang Write', 'Buku Piutang Create',
            'Hutang Pembelian Read', 'Setoran Modal & ROI Read', 'Setoran Modal & ROI Create',
            'Audit & Laporan Read', 'Audit Read', 'Pusat Laporan Read',
            'Closing Harian Read', 'Closing Harian Create', 'Closing Harian Write',
            'Stock Opname Read', 'Stock Opname Create', 'Stock Opname Write',
            'Riwayat Stok Read', 'Stok Saat Ini Read', 'Fast/Slow Moving Read',
            'Pengaturan Read', 'Pengguna Read',
            'view dashboard', 'view transactions', 'create transactions', 'approve level 1',
        ]);
        $roleAdminCabang->syncPermissions(Permission::whereIn('name', $adminCabangPerms)->get());

        // 4. Kasir — POS, sales, kas kecil, and piutang payments
        $roleKasir = Role::firstOrCreate(['name' => 'Kasir', 'guard_name' => 'web']);
        $kasirPerms = collect([
            'Dashboards Read', 'Dashboard Analytics Read',
            'Kasir (POS) Read', 'Kasir (POS) Create', 'Kasir (POS) Write',
            'Penjualan Read',
            'Inventori Cabang Read',
            'Rekening Bank Read',
            'Kas Kecil Read', 'Kas Kecil Create',
            'Buku Piutang Read', 'Buku Piutang Write',
            'Closing Harian Read', 'Closing Harian Create',
            'view dashboard', 'view transactions', 'create transactions',
        ]);
        $roleKasir->syncPermissions(Permission::whereIn('name', $kasirPerms)->get());

        // 5. Auditor — read-only audit and financial access
        $roleAuditor = Role::firstOrCreate(['name' => 'Auditor', 'guard_name' => 'web']);
        $auditorPerms = collect([
            'Dashboards Read', 'Dashboard Analytics Read', 'Dashboard Penjualan Read',
            'Dashboard Barang Read', 'Dashboard Keuntungan Read', 'Dashboard Audit Read',
            'Audit Read', 'Audit & Laporan Read', 'Pusat Laporan Read',
            'Closing Harian Read', 'Stock Opname Read', 'Rekap Tahunan Read',
            'Riwayat Stok Read', 'Stok Saat Ini Read', 'Fast/Slow Moving Read',
            'Penjualan Read', 'Purchase Order Read', 'Penerimaan Gudang Read',
            'Rekening Bank Read', 'Kas Kecil Read', 'Buku Piutang Read', 'Hutang Pembelian Read', 'Setoran Modal & ROI Read',
            'view dashboard', 'view transactions', 'view audit logs',
        ]);
        $roleAuditor->syncPermissions(Permission::whereIn('name', $auditorPerms)->get());

        $this->command->info('Roles & Permissions seeded: ' . Permission::count() . ' permissions, ' . Role::count() . ' roles.');
    }
}
