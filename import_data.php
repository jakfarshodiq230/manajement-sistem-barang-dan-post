<?php
ini_set("memory_limit", "2G");

use App\Models\Category;
use App\Models\Product;
use App\Models\Branch;
use App\Models\ProductBranch;
use App\Models\ProductBatch;
use Carbon\Carbon;

$files = [
    [
        "file" => "diesel.json",
        "map" => [
            "sku" => "ID_SKU",
            "cat" => "KATEGORI BARANG",
            "name" => "NAMA BARANG",
            "type" => "TYPE",
            "brand" => "MERK",
            "unit" => "SATUAN",
            "cost" => "MODAL",
            "price1" => "HARGA JUAL",
            "price2" => "HARGA CABANG 1",
            "price4" => "HARGA CABANG 2",
            "qty" => "QTY"
        ]
    ],
    [
        "file" => "sparepart.json",
        "map" => [
            "sku" => "Part Number",
            "name" => "Nama Barang ",
            "type" => "Type Mobil",
            "brand" => "Merk",
            "cost" => "MODAL",
            "qty" => "QTY",
            "qty_alt" => "JUMLAH",
            "date" => "TANGGAL MASUK"
        ]
    ],
    [
        "file" => "unit.json",
        "map" => [
            "sku" => "ID_SKU",
            "cat" => "KATEGORI UNIT",
            "name" => "NAMA UNIT",
            "type" => "TYPE",
            "brand" => "MERK",
            "cost" => "HARGA SATUAN", 
            "price1" => "HARGA JUAL",
            "price2" => "HARGA CABANG/UNIT",
            "qty" => "QTY",
            "date" => "TANGGAL MASUK"
        ]
    ]
];

// Helper to safely parse and cap large numbers
function safeFloat($val) {
    if (!is_numeric($val)) return 0;
    $val = floatval($val);
    if ($val > 9999999999.99) return 9999999999.99;
    return $val;
}

DB::beginTransaction();
try {
    foreach ($files as $config) {
        if (!file_exists($config["file"])) continue;
        echo "Processing " . $config["file"] . "...\n";
        $data = json_decode(file_get_contents($config["file"]), true);
        $map = $config["map"];
        
        $count = 0;
        foreach ($data as $row) {
            $sku = trim((string)($row[$map["sku"]] ?? ""));
            if (empty($sku)) continue;
            
            // Category
            $catId = null;
            $catName = trim((string)($row[$map["cat"] ?? ""] ?? ""));
            if (!empty($catName) && $catName !== "nan") {
                $category = Category::firstOrCreate(["name" => $catName]);
                $catId = $category->id;
            }
            
            // Product
            $product = Product::firstOrNew(["sku" => $sku]);
            $product->name = trim((string)($row[$map["name"]] ?? $product->name ?? "Unknown"));
            if (!empty($map["type"]) && !empty($row[$map["type"]]) && $row[$map["type"]] !== "nan") $product->type = trim((string)$row[$map["type"]]);
            if (!empty($map["brand"]) && !empty($row[$map["brand"]]) && $row[$map["brand"]] !== "nan") $product->brand = trim((string)$row[$map["brand"]]);
            if (!empty($map["unit"]) && !empty($row[$map["unit"]]) && $row[$map["unit"]] !== "nan") $product->unit = trim((string)$row[$map["unit"]]);
            if ($catId) $product->category_id = $catId;
            $product->save();
            
            // Prices
            $cost = isset($map["cost"]) ? safeFloat($row[$map["cost"]]) : 0;
            
            // Branch 1
            if (isset($map["price1"]) && is_numeric($row[$map["price1"]])) {
                $p1 = safeFloat($row[$map["price1"]]);
                $pb1 = ProductBranch::firstOrCreate(
                    ["product_id" => $product->id, "branch_id" => 1],
                    ["price" => $p1, "cost_price" => $cost, "stock" => 0]
                );
                // Qty for Branch 1
                $qty = 0;
                if (isset($map["qty"]) && is_numeric($row[$map["qty"]])) $qty = intval($row[$map["qty"]]);
                else if (isset($map["qty_alt"]) && is_numeric($row[$map["qty_alt"]])) $qty = intval($row[$map["qty_alt"]]);
                
                if ($qty > 0) {
                    $entryDate = isset($map["date"]) && !empty($row[$map["date"]]) && $row[$map["date"]] !== "nan" ? Carbon::parse($row[$map["date"]]) : now();
                    
                    // Add batch if not exists to avoid duplicates if run multiple times
                    $batchExists = ProductBatch::where("product_branch_id", $pb1->id)
                        ->where("entry_date", $entryDate->toDateString())
                        ->where("cost_price", $cost)
                        ->first();
                        
                    if (!$batchExists) {
                        ProductBatch::create([
                            "product_branch_id" => $pb1->id,
                            "qty" => $qty,
                            "cost_price" => $cost,
                            "entry_date" => $entryDate->toDateString()
                        ]);
                        $pb1->stock += $qty;
                        $pb1->save();
                    }
                }
            }
            
            // Branch 2
            if (isset($map["price2"]) && is_numeric($row[$map["price2"]])) {
                $p2 = safeFloat($row[$map["price2"]]);
                ProductBranch::firstOrCreate(
                    ["product_id" => $product->id, "branch_id" => 2],
                    ["price" => $p2, "cost_price" => $cost, "stock" => 0]
                );
            }
            
            // Branch 4
            if (isset($map["price4"]) && is_numeric($row[$map["price4"]])) {
                $p4 = safeFloat($row[$map["price4"]]);
                ProductBranch::firstOrCreate(
                    ["product_id" => $product->id, "branch_id" => 4],
                    ["price" => $p4, "cost_price" => $cost, "stock" => 0]
                );
            }
            
            $count++;
            if ($count % 500 == 0) echo "  - Processed $count records\n";
        }
        echo "Finished {$config["file"]} - Total: $count records\n\n";
    }
    DB::commit();
    echo "Import completed successfully!\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n" . $e->getFile() . ":" . $e->getLine() . "\n";
}

