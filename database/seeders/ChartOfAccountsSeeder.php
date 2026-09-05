<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\AccountSetting;

class ChartOfAccountsSeeder extends Seeder
{
    public function run()
    {
        $accounts = [
            // ==========================================
            // 1. ASET / AKTIVA (ASSETS)
            // ==========================================
            // Aset Lancar (Current Assets)
            ['code' => '1100', 'name' => 'Aset Lancar', 'type' => 'asset', 'category' => 'current_asset', 'normal_balance' => 'debit', 'parent_code' => null, 'is_system' => true],
            ['code' => '1101', 'name' => 'Kas Laci Kasir (Cash on Hand)', 'type' => 'asset', 'category' => 'current_asset', 'normal_balance' => 'debit', 'parent_code' => '1100'],
            ['code' => '1102', 'name' => 'Kas di Bank (Bank Accounts)', 'type' => 'asset', 'category' => 'current_asset', 'normal_balance' => 'debit', 'parent_code' => '1100'],
            ['code' => '1103', 'name' => 'Piutang Usaha (Accounts Receivable)', 'type' => 'asset', 'category' => 'current_asset', 'normal_balance' => 'debit', 'parent_code' => '1100'],
            ['code' => '1104', 'name' => 'Persediaan Barang Dagang (Inventory)', 'type' => 'asset', 'category' => 'current_asset', 'normal_balance' => 'debit', 'parent_code' => '1100'],
            ['code' => '1105', 'name' => 'Biaya Dibayar di Muka (Prepaid Expenses)', 'type' => 'asset', 'category' => 'current_asset', 'normal_balance' => 'debit', 'parent_code' => '1100'],
            ['code' => '1106', 'name' => 'Perlengkapan Toko (Store Supplies)', 'type' => 'asset', 'category' => 'current_asset', 'normal_balance' => 'debit', 'parent_code' => '1100'],

            // Aset Tetap (Fixed Assets)
            ['code' => '1200', 'name' => 'Aset Tetap', 'type' => 'asset', 'category' => 'fixed_asset', 'normal_balance' => 'debit', 'parent_code' => null, 'is_system' => true],
            ['code' => '1201', 'name' => 'Peralatan & Mesin Toko', 'type' => 'asset', 'category' => 'fixed_asset', 'normal_balance' => 'debit', 'parent_code' => '1200'],
            ['code' => '1202', 'name' => 'Kendaraan Operasional', 'type' => 'asset', 'category' => 'fixed_asset', 'normal_balance' => 'debit', 'parent_code' => '1200'],
            ['code' => '1203', 'name' => 'Bangunan & Renovasi Toko', 'type' => 'asset', 'category' => 'fixed_asset', 'normal_balance' => 'debit', 'parent_code' => '1200'],
            ['code' => '1299', 'name' => 'Akumulasi Penyusutan Aset Tetap', 'type' => 'asset', 'category' => 'contra_asset', 'normal_balance' => 'credit', 'parent_code' => '1200'],

            // ==========================================
            // 2. KEWAJIBAN / HUTANG (LIABILITIES)
            // ==========================================
            // Kewajiban Jangka Pendek (Current Liabilities)
            ['code' => '2100', 'name' => 'Kewajiban Lancar', 'type' => 'liability', 'category' => 'current_liability', 'normal_balance' => 'credit', 'parent_code' => null, 'is_system' => true],
            ['code' => '2101', 'name' => 'Hutang Usaha / Dagang (Accounts Payable)', 'type' => 'liability', 'category' => 'current_liability', 'normal_balance' => 'credit', 'parent_code' => '2100'],
            ['code' => '2102', 'name' => 'Hutang Gaji Karyawan', 'type' => 'liability', 'category' => 'current_liability', 'normal_balance' => 'credit', 'parent_code' => '2100'],
            ['code' => '2103', 'name' => 'Hutang Pajak (PPN/PPh)', 'type' => 'liability', 'category' => 'current_liability', 'normal_balance' => 'credit', 'parent_code' => '2100'],
            ['code' => '2104', 'name' => 'Hutang Biaya Operasional Lainnya', 'type' => 'liability', 'category' => 'current_liability', 'normal_balance' => 'credit', 'parent_code' => '2100'],

            // Kewajiban Jangka Panjang (Long-Term Liabilities)
            ['code' => '2200', 'name' => 'Kewajiban Jangka Panjang', 'type' => 'liability', 'category' => 'long_term_liability', 'normal_balance' => 'credit', 'parent_code' => null, 'is_system' => true],
            ['code' => '2201', 'name' => 'Hutang Bank Jangka Panjang', 'type' => 'liability', 'category' => 'long_term_liability', 'normal_balance' => 'credit', 'parent_code' => '2200'],

            // ==========================================
            // 3. EKUITAS / MODAL (EQUITY)
            // ==========================================
            ['code' => '3100', 'name' => 'Ekuitas Pemilik', 'type' => 'equity', 'category' => 'equity', 'normal_balance' => 'credit', 'parent_code' => null, 'is_system' => true],
            ['code' => '3101', 'name' => 'Modal Disetor Pemilik (Owner Capital)', 'type' => 'equity', 'category' => 'equity', 'normal_balance' => 'credit', 'parent_code' => '3100'],
            ['code' => '3102', 'name' => 'Prive / Pengembalian Modal / ROI Owner', 'type' => 'equity', 'category' => 'equity', 'normal_balance' => 'debit', 'parent_code' => '3100'],
            ['code' => '3103', 'name' => 'Laba Ditahan (Retained Earnings)', 'type' => 'equity', 'category' => 'equity', 'normal_balance' => 'credit', 'parent_code' => '3100', 'is_system' => true],
            ['code' => '3104', 'name' => 'Laba / Rugi Periode Berjalan', 'type' => 'equity', 'category' => 'equity', 'normal_balance' => 'credit', 'parent_code' => '3100', 'is_system' => true],

            // ==========================================
            // 4. PENDAPATAN (REVENUE)
            // ==========================================
            ['code' => '4100', 'name' => 'Pendapatan Usaha', 'type' => 'revenue', 'category' => 'operating_revenue', 'normal_balance' => 'credit', 'parent_code' => null, 'is_system' => true],
            ['code' => '4101', 'name' => 'Pendapatan Penjualan Toko (Sales)', 'type' => 'revenue', 'category' => 'operating_revenue', 'normal_balance' => 'credit', 'parent_code' => '4100'],
            ['code' => '4102', 'name' => 'Retur & Potongan Penjualan', 'type' => 'revenue', 'category' => 'operating_revenue', 'normal_balance' => 'debit', 'parent_code' => '4100'],
            ['code' => '4201', 'name' => 'Pendapatan Lain-lain (Non-Operasional)', 'type' => 'revenue', 'category' => 'other_income', 'normal_balance' => 'credit', 'parent_code' => null],

            // ==========================================
            // 5. BEBAN POKOK PENJUALAN (COGS)
            // ==========================================
            ['code' => '5100', 'name' => 'Beban Pokok Penjualan', 'type' => 'cogs', 'category' => 'cogs', 'normal_balance' => 'debit', 'parent_code' => null, 'is_system' => true],
            ['code' => '5101', 'name' => 'Harga Pokok Penjualan (HPP Barang Dagang)', 'type' => 'cogs', 'category' => 'cogs', 'normal_balance' => 'debit', 'parent_code' => '5100'],
            ['code' => '5102', 'name' => 'Beban Selisih Stok Opname / Rusak', 'type' => 'cogs', 'category' => 'cogs', 'normal_balance' => 'debit', 'parent_code' => '5100'],

            // ==========================================
            // 6. BEBAN OPERASIONAL (EXPENSES)
            // ==========================================
            ['code' => '6100', 'name' => 'Beban Operasional & Umum', 'type' => 'expense', 'category' => 'operating_expense', 'normal_balance' => 'debit', 'parent_code' => null, 'is_system' => true],
            ['code' => '6101', 'name' => 'Beban Kas Kecil Operasional Harian', 'type' => 'expense', 'category' => 'operating_expense', 'normal_balance' => 'debit', 'parent_code' => '6100'],
            ['code' => '6102', 'name' => 'Beban Gaji & Upah Karyawan', 'type' => 'expense', 'category' => 'operating_expense', 'normal_balance' => 'debit', 'parent_code' => '6100'],
            ['code' => '6103', 'name' => 'Beban Listrik, Air & Internet', 'type' => 'expense', 'category' => 'operating_expense', 'normal_balance' => 'debit', 'parent_code' => '6100'],
            ['code' => '6104', 'name' => 'Beban Sewa Gedung / Ruko', 'type' => 'expense', 'category' => 'operating_expense', 'normal_balance' => 'debit', 'parent_code' => '6100'],
            ['code' => '6105', 'name' => 'Beban Perlengkapan & ATK Toko', 'type' => 'expense', 'category' => 'operating_expense', 'normal_balance' => 'debit', 'parent_code' => '6100'],
            ['code' => '6106', 'name' => 'Beban Transportasi & Logistik', 'type' => 'expense', 'category' => 'operating_expense', 'normal_balance' => 'debit', 'parent_code' => '6100'],
            ['code' => '6107', 'name' => 'Beban Penyusutan Aset Tetap', 'type' => 'expense', 'category' => 'operating_expense', 'normal_balance' => 'debit', 'parent_code' => '6100'],
            ['code' => '6201', 'name' => 'Beban Bunga & Administrasi Bank', 'type' => 'expense', 'category' => 'other_expense', 'normal_balance' => 'debit', 'parent_code' => null],
        ];

        // 1. Seed Parent accounts first
        foreach ($accounts as $accData) {
            $parentCode = $accData['parent_code'] ?? null;
            $parentId = null;

            if ($parentCode) {
                $parentAcc = Account::where('code', $parentCode)->first();
                $parentId = $parentAcc ? $parentAcc->id : null;
            }

            Account::updateOrCreate(
                ['code' => $accData['code']],
                [
                    'name' => $accData['name'],
                    'type' => $accData['type'],
                    'category' => $accData['category'],
                    'normal_balance' => $accData['normal_balance'],
                    'parent_id' => $parentId,
                    'is_system' => $accData['is_system'] ?? false,
                    'is_active' => true,
                    'opening_balance' => 0,
                ]
            );
        }

        // 2. Setup Default Account Mappings in account_settings
        $mappings = [
            'default_cash' => '1101',
            'default_bank' => '1102',
            'default_ar' => '1103',
            'default_inventory' => '1104',
            'default_ap' => '2101',
            'default_capital' => '3101',
            'default_retained_earnings' => '3103',
            'default_sales' => '4101',
            'default_cogs' => '5101',
            'default_expense' => '6101',
        ];

        foreach ($mappings as $key => $code) {
            $acc = Account::where('code', $code)->first();
            if ($acc) {
                AccountSetting::updateOrCreate(
                    ['branch_id' => null, 'setting_key' => $key],
                    ['account_id' => $acc->id]
                );
            }
        }
    }
}
