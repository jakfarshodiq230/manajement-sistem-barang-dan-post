<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\Supplier;
use App\Models\Owner;
use App\Models\Employee;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\StockMovement;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ReturnTransaction;
use App\Models\ReturnItem;
use App\Models\CashReconciliation;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class RealDataSeeder extends Seeder
{
    public function run()
    {
        // Start date for master data (January)
        $masterDate = Carbon::create(2026, 1, 10, 8, 0, 0);

        // 1. Branches
        $branchPusat = Branch::firstOrCreate(['id' => 1], [
            'name' => 'Cabang Pusat Jakarta',
            'address' => 'Jl. Jenderal Sudirman No. 1, Jakarta Pusat',
            'phone' => '021-1234567',
            'created_at' => $masterDate,
            'updated_at' => $masterDate,
        ]);
        $branchBandung = Branch::firstOrCreate(['id' => 2], [
            'name' => 'Cabang Bandung',
            'address' => 'Jl. Asia Afrika No. 100, Bandung',
            'phone' => '022-7654321',
            'created_at' => $masterDate->copy()->addDays(15),
            'updated_at' => $masterDate->copy()->addDays(15),
        ]);

        // 2. Categories
        $catDate = $masterDate->copy()->addDays(20);
        $catElektronik = Category::firstOrCreate(['name' => 'Elektronik'], ['description' => 'Barang elektronik dan gadget', 'created_at' => $catDate, 'updated_at' => $catDate]);
        $catPakaian = Category::firstOrCreate(['name' => 'Pakaian'], ['description' => 'Pakaian pria dan wanita', 'created_at' => $catDate->copy()->addDays(2), 'updated_at' => $catDate->copy()->addDays(2)]);
        $catMakanan = Category::firstOrCreate(['name' => 'Makanan & Minuman'], ['description' => 'Makanan ringan dan minuman kemasan', 'created_at' => $catDate->copy()->addDays(5), 'updated_at' => $catDate->copy()->addDays(5)]);

        // 3. Suppliers
        $supDate = $catDate->copy()->addDays(10);
        $supSamsung = Supplier::firstOrCreate(['name' => 'PT. Samsung Electronics'], ['contact_person' => 'Budi', 'phone' => '08111222333', 'address' => 'Jakarta', 'created_at' => $supDate, 'updated_at' => $supDate]);
        $supPakaian = Supplier::firstOrCreate(['name' => 'CV. Pakaian Jaya'], ['contact_person' => 'Agus', 'phone' => '08222333444', 'address' => 'Bandung', 'created_at' => $supDate->copy()->addDays(3), 'updated_at' => $supDate->copy()->addDays(3)]);
        $supIndofood = Supplier::firstOrCreate(['name' => 'PT. Indofood Sukses Makmur'], ['contact_person' => 'Sari', 'phone' => '08333444555', 'address' => 'Jakarta', 'created_at' => $supDate->copy()->addDays(6), 'updated_at' => $supDate->copy()->addDays(6)]);

        // 4. Products
        $prodDate = $supDate->copy()->addDays(10);
        $pLaptop = Product::firstOrCreate(['sku' => 'ELK-001'], [
            'name' => 'Laptop Asus ROG Zephyrus G14',
            'category_id' => $catElektronik->id,
            'description' => 'Laptop gaming asus ROG Zephyrus G14 dengan Ryzen 9.',
            'created_at' => $prodDate, 'updated_at' => $prodDate
        ]);
        $pKemeja = Product::firstOrCreate(['sku' => 'PAK-001'], [
            'name' => 'Kemeja Flanel Pria Lengan Panjang',
            'category_id' => $catPakaian->id,
            'description' => 'Kemeja flanel pria ukuran L warna merah hitam.',
            'created_at' => $prodDate->copy()->addDays(2), 'updated_at' => $prodDate->copy()->addDays(2)
        ]);
        $pIndomie = Product::firstOrCreate(['sku' => 'MKN-001'], [
            'name' => 'Indomie Goreng Spesial',
            'category_id' => $catMakanan->id,
            'description' => 'Mie instant goreng indomie rasa original.',
            'created_at' => $prodDate->copy()->addDays(4), 'updated_at' => $prodDate->copy()->addDays(4)
        ]);

        // Product Branches (Pricing & Stock)
        $pbLaptopJkt = ProductBranch::firstOrCreate(['product_id' => $pLaptop->id, 'branch_id' => $branchPusat->id], [
            'cost_price' => 15000000,
            'price' => 17500000,
            'stock' => 10,
        ]);
        $pbLaptopBdg = ProductBranch::firstOrCreate(['product_id' => $pLaptop->id, 'branch_id' => $branchBandung->id], [
            'cost_price' => 15000000,
            'price' => 18000000, // Sedikit lebih mahal di Bandung
            'stock' => 5,
        ]);
        $pbKemejaJkt = ProductBranch::firstOrCreate(['product_id' => $pKemeja->id, 'branch_id' => $branchPusat->id], [
            'cost_price' => 100000,
            'price' => 150000,
            'stock' => 50,
        ]);
        $pbIndomieJkt = ProductBranch::firstOrCreate(['product_id' => $pIndomie->id, 'branch_id' => $branchPusat->id], [
            'cost_price' => 2500,
            'price' => 3500,
            'stock' => 500,
        ]);
        $pbIndomieBdg = ProductBranch::firstOrCreate(['product_id' => $pIndomie->id, 'branch_id' => $branchBandung->id], [
            'cost_price' => 2500,
            'price' => 3500,
            'stock' => 200,
        ]);

        // 5. Users
        $password = Hash::make('password123');

        // Owner (Super Admin)
        $userDate = $masterDate->copy()->subDays(5);
        setPermissionsTeamId($branchPusat->id); // Owner can be in any team, Super Admin role bypasses
        $owner = User::firstOrCreate(['email' => 'owner@example.com'], [
            'name' => 'Bapak Shodiq (Owner)',
            'password' => $password,
            'created_at' => $userDate, 'updated_at' => $userDate
        ]);
        if (!$owner->hasRole('Super Admin')) {
            $owner->assignRole('Super Admin');
        }
        
        $ownerModel = Owner::firstOrCreate(['email' => 'owner@example.com'], [
            'name' => 'Bapak Shodiq (Owner)',
            'phone' => '08123456789',
            'address' => 'Jl. Owner Pusat No. 1, Jakarta',
            'status' => 'active',
            'created_at' => $userDate, 'updated_at' => $userDate
        ]);

        // Link Owner to branches if necessary
        $branchPusat->update(['owner_id' => $ownerModel->id]);
        $branchBandung->update(['owner_id' => $ownerModel->id]);

        // Admin Pusat (Employee)
        $adminJkt = User::firstOrCreate(['email' => 'admin.jkt@example.com'], [
            'name' => 'Rina (Admin Pusat)',
            'password' => $password,
            'created_at' => $userDate->copy()->addDays(10), 'updated_at' => $userDate->copy()->addDays(10)
        ]);
        if (!$adminJkt->hasRole('Admin Pusat')) {
            $adminJkt->assignRole('Admin Pusat');
        }
        
        Employee::firstOrCreate(['user_id' => $adminJkt->id], [
            'name' => 'Rina (Admin Pusat)',
            'nik' => 'EMP-001',
            'phone' => '08212345678',
            'email' => 'admin.jkt@example.com',
            'branch_id' => $branchPusat->id,
            'joined_date' => $userDate->copy()->addDays(10),
            'status' => 'active',
            'created_at' => $userDate->copy()->addDays(10), 'updated_at' => $userDate->copy()->addDays(10)
        ]);

        // Admin Cabang Bandung (Employee)
        setPermissionsTeamId($branchBandung->id);
        $adminBdg = User::firstOrCreate(['email' => 'admin.bdg@example.com'], [
            'name' => 'Asep (Admin Bandung)',
            'password' => $password,
            'created_at' => $userDate->copy()->addDays(15), 'updated_at' => $userDate->copy()->addDays(15)
        ]);
        if (!$adminBdg->hasRole('Admin Cabang')) {
            $adminBdg->assignRole('Admin Cabang');
        }
        
        Employee::firstOrCreate(['user_id' => $adminBdg->id], [
            'name' => 'Asep (Admin Bandung)',
            'nik' => 'EMP-002',
            'phone' => '08312345678',
            'email' => 'admin.bdg@example.com',
            'branch_id' => $branchBandung->id,
            'joined_date' => $userDate->copy()->addDays(15),
            'status' => 'active',
            'created_at' => $userDate->copy()->addDays(15), 'updated_at' => $userDate->copy()->addDays(15)
        ]);

        // Kasir Bandung (Employee)
        $kasirBdg = User::firstOrCreate(['email' => 'kasir.bdg@example.com'], [
            'name' => 'Siti (Kasir Bandung)',
            'password' => $password,
            'created_at' => $userDate->copy()->addDays(16), 'updated_at' => $userDate->copy()->addDays(16)
        ]);
        if (!$kasirBdg->hasRole('Kasir')) {
            $kasirBdg->assignRole('Kasir');
        }
        
        Employee::firstOrCreate(['user_id' => $kasirBdg->id], [
            'name' => 'Siti (Kasir Bandung)',
            'nik' => 'EMP-003',
            'phone' => '08412345678',
            'email' => 'kasir.bdg@example.com',
            'branch_id' => $branchBandung->id,
            'joined_date' => $userDate->copy()->addDays(16),
            'status' => 'active',
            'created_at' => $userDate->copy()->addDays(16), 'updated_at' => $userDate->copy()->addDays(16)
        ]);

        // 6. Generate Transactions (Jan to current date)
        $startDate = Carbon::create(2026, 1, 1);
        $endDate = Carbon::now();
        
        $currentDate = $startDate->copy();
        
        $poCounter = 1;
        $grCounter = 1;
        $invCounter = 1;
        $retCounter = 1;

        while ($currentDate->lte($endDate)) {
            // Weekly Restock (Every Monday)
            if ($currentDate->isMonday()) {
                // PO Pusat -> Supplier Samsung (Laptops)
                $po1 = PurchaseOrder::create([
                    'po_number' => 'PO-' . $currentDate->format('ymd') . str_pad($poCounter++, 4, '0', STR_PAD_LEFT),
                    'branch_id' => $branchPusat->id,
                    'supplier_id' => $supSamsung->id,
                    'user_id' => $adminJkt->id,
                    'date' => $currentDate->copy()->setHour(9),
                    'status' => 'completed',
                    'approval_status' => 'approved',
                    'total_amount' => 150000000,
                    'notes' => 'Weekly restock laptop',
                    'created_at' => $currentDate->copy()->setHour(9),
                    'updated_at' => $currentDate->copy()->setHour(9),
                ]);
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po1->id,
                    'product_id' => $pLaptop->id,
                    'qty' => 10,
                    'unit_cost' => 15000000,
                    'total_price' => 150000000,
                ]);

                // GR Pusat (1 day later)
                $grDate = $currentDate->copy()->addDay()->setHour(10);
                if ($grDate->lte($endDate)) {
                    $gr1 = GoodsReceipt::create([
                        'receipt_number' => 'GR-' . $grDate->format('ymd') . str_pad($grCounter++, 4, '0', STR_PAD_LEFT),
                        'purchase_order_id' => $po1->id,
                        'user_id' => $adminJkt->id,
                        'date' => $grDate,
                        'approval_status' => 'approved',
                        'notes' => 'Received weekly restock',
                        'created_at' => $grDate,
                        'updated_at' => $grDate,
                    ]);
                    GoodsReceiptItem::create([
                        'goods_receipt_id' => $gr1->id,
                        'purchase_order_item_id' => $po1->items()->first()->id ?? 1,
                        'product_branch_id' => $pbLaptopJkt->id,
                        'qty_received' => 10,
                    ]);
                    StockMovement::create([
                        'product_branch_id' => $pbLaptopJkt->id,
                        'user_id' => $adminJkt->id,
                        'type' => 'in',
                        'quantity' => 10,
                        'unit_cost' => 15000000,
                        'reference_type' => GoodsReceipt::class,
                        'reference_id' => $gr1->id,
                        'notes' => 'Restock',
                        'created_at' => $grDate,
                        'updated_at' => $grDate,
                    ]);
                    
                    // Update Stock
                    $pbLaptopJkt->increment('stock', 10);
                }
            }

            // Daily Sales (Bandung)
            // Generate 1 to 3 sales per day
            $salesCount = rand(1, 3);
            for ($i = 0; $i < $salesCount; $i++) {
                $saleTime = $currentDate->copy()->setHour(rand(10, 20))->setMinute(rand(0, 59));
                
                // Randomly sell either Laptop, Kemeja, or Indomie
                $itemRand = rand(1, 100);
                if ($itemRand <= 10 && $pbLaptopBdg->stock > 0) { // 10% chance for laptop
                    $qty = 1;
                    $pb = $pbLaptopBdg;
                } elseif ($itemRand <= 40 && $pbKemejaJkt->stock > 0) { // 30% chance for Kemeja (wait Kemeja is Jkt, let's use Indomie for Bdg)
                    $qty = rand(5, 20);
                    $pb = $pbIndomieBdg;
                } else {
                    $qty = rand(1, 5);
                    $pb = $pbIndomieBdg;
                }

                if ($pb->stock >= $qty) {
                    $total = $qty * $pb->price;
                    
                    $sale = Sale::create([
                        'invoice_number' => 'INV-BDG-' . $currentDate->format('ymd') . str_pad($invCounter++, 4, '0', STR_PAD_LEFT),
                        'branch_id' => $branchBandung->id,
                        'user_id' => $kasirBdg->id,
                        'date' => $saleTime,
                        'status' => 'completed',
                        'total_amount' => $total,
                        'payment_method' => ['cash', 'qris', 'transfer'][rand(0, 2)],
                        'created_at' => $saleTime,
                        'updated_at' => $saleTime,
                    ]);

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_branch_id' => $pb->id,
                        'qty' => $qty,
                        'price' => $pb->price,
                        'cost_price' => $pb->cost_price,
                        'subtotal' => $total,
                    ]);

                    StockMovement::create([
                        'product_branch_id' => $pb->id,
                        'user_id' => $kasirBdg->id,
                        'type' => 'out',
                        'quantity' => $qty,
                        'unit_cost' => $pb->cost_price,
                        'reference_type' => Sale::class,
                        'reference_id' => $sale->id,
                        'notes' => 'Penjualan',
                        'created_at' => $saleTime,
                        'updated_at' => $saleTime,
                    ]);
                    
                    $pb->decrement('stock', $qty);
                    
                    // 5% chance to have a return for this sale
                    if (rand(1, 100) <= 5) {
                        $retTime = $saleTime->copy()->addHours(2);
                        $ret = ReturnTransaction::create([
                            'return_number' => 'RET-BDG-' . $currentDate->format('ymd') . str_pad($retCounter++, 4, '0', STR_PAD_LEFT),
                            'branch_id' => $branchBandung->id,
                            'user_id' => $kasirBdg->id,
                            'approved_by' => $adminBdg->id,
                            'reference_type' => 'sale',
                            'reference_id' => $sale->id,
                            'return_type' => 'pengembalian_uang',
                            'status' => 'completed',
                            'total_amount' => $total,
                            'notes' => 'Barang cacat, pelanggan minta kembali uang.',
                            'created_at' => $retTime,
                            'updated_at' => $retTime,
                        ]);

                        ReturnItem::create([
                            'return_transaction_id' => $ret->id,
                            'product_branch_id' => $pb->id,
                            'qty' => $qty,
                            'unit_price' => $pb->price,
                            'subtotal' => $total,
                        ]);

                        // Stock movement for return (inbound since customer returned it)
                        // But wait, if it's damaged it might go to damaged qty, but let's just use regular 'in' for simplicity here
                        StockMovement::create([
                            'product_branch_id' => $pb->id,
                            'user_id' => $kasirBdg->id,
                            'type' => 'in',
                            'quantity' => $qty,
                            'unit_cost' => $pb->cost_price,
                            'reference_type' => ReturnTransaction::class,
                            'reference_id' => $ret->id,
                            'notes' => 'Retur Penjualan',
                            'created_at' => $retTime,
                            'updated_at' => $retTime,
                        ]);
                        $pb->increment('stock', $qty);
                    }
                }
            }

            $currentDate->addDay();
        }
    }
}
