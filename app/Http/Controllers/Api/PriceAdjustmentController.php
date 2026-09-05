<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PriceAdjustment;
use App\Models\PriceAdjustmentItem;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\ProductBatch;
use App\Models\Branch;
use App\Models\Owner;
use App\Services\RedisCacheService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PriceAdjustmentController extends Controller
{
    /**
     * Display a listing of price adjustments.
     */
    public function index(Request $request)
    {
        $q = $request->query('q', '');
        $status = $request->query('status', '');
        $branchId = $request->query('branch_id', '');
        $startDate = $request->query('start_date', '');
        $endDate = $request->query('end_date', '');
        $itemsPerPage = (int) $request->query('itemsPerPage', 15);

        $query = PriceAdjustment::with(['branch', 'creator:id,name,email', 'approver:id,name,email'])
            ->orderBy('id', 'desc');

        if (!empty($q)) {
            $query->where(function ($w) use ($q) {
                $w->where('adjustment_number', 'like', "%{$q}%")
                  ->orWhere('title', 'like', "%{$q}%")
                  ->orWhere('reason', 'like', "%{$q}%");
            });
        }

        if (!empty($status) && $status !== 'all') {
            $query->where('status', $status);
        }

        if (!empty($branchId) && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        if (!empty($startDate)) {
            $query->where('effective_date', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $query->where('effective_date', '<=', $endDate);
        }

        $paginator = $query->paginate($itemsPerPage);

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ]);
    }

    /**
     * Store a newly created price adjustment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'effective_date' => 'required|date',
            'branch_id' => 'nullable|exists:branches,id',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.new_cost_price' => 'nullable|numeric|min:0',
            'items.*.new_price' => 'required|numeric|min:0',
            'items.*.new_min_nego_price' => 'nullable|numeric|min:0',
            'apply_immediately' => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $user = $request->user();
            $adjustmentNumber = PriceAdjustment::generateAdjustmentNumber();

            $adjustment = PriceAdjustment::create([
                'adjustment_number' => $adjustmentNumber,
                'branch_id' => $validated['branch_id'] ?? null,
                'title' => $validated['title'],
                'effective_date' => $validated['effective_date'],
                'reason' => $validated['reason'] ?? 'Penyesuaian Harga Berkala',
                'status' => 'draft',
                'total_items' => count($validated['items']),
                'created_by' => $user ? $user->id : null,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $itemData) {
                $productId = $itemData['product_id'];
                $branchId = $validated['branch_id'] ?? null;

                // Lookup current prices from product_branch or product
                $pb = null;
                if ($branchId) {
                    $pb = ProductBranch::where('branch_id', $branchId)->where('product_id', $productId)->first();
                } else {
                    $pb = ProductBranch::where('product_id', $productId)->first();
                }

                $oldCostPrice = $itemData['old_cost_price'] ?? ($pb ? (float)$pb->cost_price : 0);
                $oldPrice = $itemData['old_price'] ?? ($pb ? (float)$pb->price : 0);
                $oldMinNego = $itemData['old_min_nego_price'] ?? ($pb ? (float)$pb->min_nego_price : 0);

                PriceAdjustmentItem::create([
                    'price_adjustment_id' => $adjustment->id,
                    'product_id' => $productId,
                    'product_branch_id' => $pb ? $pb->id : null,
                    'old_cost_price' => $oldCostPrice,
                    'new_cost_price' => $itemData['new_cost_price'] ?? $oldCostPrice,
                    'old_price' => $oldPrice,
                    'new_price' => $itemData['new_price'],
                    'old_min_nego_price' => $oldMinNego,
                    'new_min_nego_price' => $itemData['new_min_nego_price'] ?? ($itemData['new_price'] * 0.95),
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            if (!empty($validated['apply_immediately']) && $validated['apply_immediately'] === true) {
                $this->executeApply($adjustment, $user);
            }

            return response()->json([
                'success' => true,
                'message' => 'Dokumen penyesuaian harga berhasil dibuat',
                'data' => $adjustment->load('items.product.category', 'branch'),
            ], 201);
        });
    }

    /**
     * Display the specified price adjustment.
     */
    public function show($id)
    {
        $adjustment = PriceAdjustment::with([
            'branch',
            'creator:id,name,email',
            'approver:id,name,email',
            'items.product.category',
            'items.productBranch.branch'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $adjustment,
        ]);
    }

    /**
     * Apply and approve the price adjustment.
     */
    public function apply(Request $request, $id)
    {
        $adjustment = PriceAdjustment::with('items')->findOrFail($id);

        if ($adjustment->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen penyesuaian harga ini sudah disetujui dan diterapkan sebelumnya.',
            ], 422);
        }

        if ($adjustment->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen yang telah dibatalkan tidak dapat diterapkan.',
            ], 422);
        }

        DB::transaction(function () use ($adjustment, $request) {
            $user = $request->user();
            $this->executeApply($adjustment, $user);
        });

        return response()->json([
            'success' => true,
            'message' => 'Penyesuaian harga berhasil disahkan dan diterapkan serentak ke katalog toko & kasir.',
            'data' => $adjustment->fresh(['branch', 'approver', 'items.product']),
        ]);
    }

    /**
     * Internal executor to apply prices to ProductBranch and ProductBatch.
     */
    protected function executeApply(PriceAdjustment $adjustment, $user)
    {
        $adjustment->update([
            'status' => 'approved',
            'approved_by' => $user ? $user->id : null,
            'approved_at' => now(),
        ]);

        foreach ($adjustment->items as $item) {
            $productId = $item->product_id;
            $newPrice = (float) $item->new_price;
            $newCostPrice = (float) $item->new_cost_price;
            $newMinNego = (float) $item->new_min_nego_price;

            $productBranchesQuery = ProductBranch::where('product_id', $productId);
            if ($adjustment->branch_id) {
                $productBranchesQuery->where('branch_id', $adjustment->branch_id);
            }
            $targetPbs = $productBranchesQuery->get();

            // If no ProductBranch exists yet for a specific branch, create it
            if ($targetPbs->isEmpty() && $adjustment->branch_id) {
                $createdPb = ProductBranch::create([
                    'branch_id' => $adjustment->branch_id,
                    'product_id' => $productId,
                    'stock' => 0,
                    'cost_price' => $newCostPrice,
                    'price' => $newPrice,
                    'min_nego_price' => $newMinNego,
                    'tax_percentage' => 0,
                ]);
                $targetPbs = collect([$createdPb]);
            }

            foreach ($targetPbs as $pb) {
                // Update ProductBranch master
                $pb->update([
                    'cost_price' => $newCostPrice > 0 ? $newCostPrice : $pb->cost_price,
                    'price' => $newPrice,
                    'min_nego_price' => $newMinNego > 0 ? $newMinNego : $pb->min_nego_price,
                ]);

                // Update active batches
                ProductBatch::where('product_branch_id', $pb->id)
                    ->where('qty', '>', 0)
                    ->update([
                        'price' => $newPrice,
                        'min_nego_price' => $newMinNego > 0 ? $newMinNego : $pb->min_nego_price,
                    ]);

                // Record audit log into price_histories
                PriceHistory::create([
                    'product_id' => $productId,
                    'product_branch_id' => $pb->id,
                    'branch_id' => $pb->branch_id,
                    'price_adjustment_id' => $adjustment->id,
                    'adjustment_number' => $adjustment->adjustment_number,
                    'old_cost_price' => $item->old_cost_price,
                    'new_cost_price' => $newCostPrice,
                    'old_price' => $item->old_price,
                    'new_price' => $newPrice,
                    'old_min_nego_price' => $item->old_min_nego_price,
                    'new_min_nego_price' => $newMinNego,
                    'effective_date' => $adjustment->effective_date,
                    'reason' => $adjustment->reason . ($adjustment->title ? " ({$adjustment->title})" : ""),
                    'user_id' => $user ? $user->id : null,
                ]);
            }
        }

        // Invalidate Redis POS catalog cache if applicable
        try {
            if ($adjustment->branch_id) {
                RedisCacheService::clearBranchProducts($adjustment->branch_id);
            } else {
                RedisCacheService::clearAllProducts();
            }
        } catch (\Throwable $t) {
            // Non-blocking
        }
    }

    /**
     * Cancel draft adjustment.
     */
    public function cancel(Request $request, $id)
    {
        $adjustment = PriceAdjustment::findOrFail($id);

        if ($adjustment->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen yang telah disetujui tidak dapat dibatalkan.',
            ], 422);
        }

        $adjustment->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Dokumen penyesuaian harga berhasil dibatalkan.',
        ]);
    }

    /**
     * Get price history audit trail.
     */
    public function history(Request $request)
    {
        $q = $request->query('q', '');
        $productId = $request->query('product_id', '');
        $branchId = $request->query('branch_id', '');
        $startDate = $request->query('start_date', '');
        $endDate = $request->query('end_date', '');
        $itemsPerPage = (int) $request->query('itemsPerPage', 15);

        $query = PriceHistory::with([
            'product.category',
            'branch:id,name,address',
            'user:id,name,email',
            'priceAdjustment:id,adjustment_number,title,status',
        ])->orderBy('id', 'desc');

        if (!empty($q)) {
            $query->where(function ($w) use ($q) {
                $w->where('adjustment_number', 'like', "%{$q}%")
                  ->orWhere('reason', 'like', "%{$q}%")
                  ->orWhereHas('product', function ($pq) use ($q) {
                      $pq->where('name', 'like', "%{$q}%")
                         ->orWhere('sku', 'like', "%{$q}%");
                  });
            });
        }

        if (!empty($productId) && $productId !== 'all') {
            $query->where('product_id', $productId);
        }

        if (!empty($branchId) && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        if (!empty($startDate)) {
            $query->where('effective_date', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $query->where('effective_date', '<=', $endDate);
        }

        $paginator = $query->paginate($itemsPerPage);

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ]);
    }

    /**
     * Export price adjustment document to PDF.
     */
    public function exportPdf(Request $request, $id)
    {
        $adjustment = PriceAdjustment::with([
            'branch',
            'creator',
            'approver',
            'items.product.category',
        ])->findOrFail($id);

        $owner = Owner::first();

        // Calculate summary statistics
        $totalItems = $adjustment->items->count();
        $totalPriceIncrease = 0;
        $totalItemsIncreased = 0;
        $totalItemsDecreased = 0;

        foreach ($adjustment->items as $item) {
            $diff = (float)$item->new_price - (float)$item->old_price;
            if ($diff > 0) $totalItemsIncreased++;
            elseif ($diff < 0) $totalItemsDecreased++;
            $totalPriceIncrease += $diff;
        }

        $pdf = Pdf::loadView('pdf.price_adjustment', [
            'adjustment' => $adjustment,
            'owner' => $owner,
            'totalItems' => $totalItems,
            'totalPriceIncrease' => $totalPriceIncrease,
            'totalItemsIncreased' => $totalItemsIncreased,
            'totalItemsDecreased' => $totalItemsDecreased,
            'printedAt' => now()->format('d/m/Y H:i:s'),
            'verificationUuid' => Str::uuid()->toString(),
        ])->setPaper('a4', 'portrait');

        $filename = "SK_Penetapan_Harga_{$adjustment->adjustment_number}.pdf";

        return $pdf->download($filename);
    }
}
