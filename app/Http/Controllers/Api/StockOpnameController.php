<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\ProductBranch;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockOpnameController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('group_by_batch')) {
            $query = StockOpname::with('creator:id,name')
                ->select('batch_id', 'audit_date', 'notes', 'created_by', DB::raw('MAX(created_at) as created_at'), DB::raw('COUNT(id) as total_branches'))
                ->whereNotNull('batch_id')
                ->groupBy('batch_id', 'audit_date', 'notes', 'created_by')
                ->orderByRaw('MAX(created_at) DESC');

            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where('notes', 'like', "%{$search}%");
            }
            
            return response()->json($query->paginate(10));
        }

        $query = StockOpname::with(['branch:id,name', 'creator:id,name'])->orderBy('audit_date', 'desc');

        if ($request->has('batch_id')) {
            $query->where('batch_id', $request->query('batch_id'));
        }

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->query('branch_id'));
        }
        
        $user = $request->user();
        if ($user && $user->active_role_id) {
            $role = \Spatie\Permission\Models\Role::find($user->active_role_id);
            if (!$role || !$role->hasPermissionTo('Cabang Read')) {
                $assignment = DB::table('model_has_roles')
                    ->where('model_id', $user->id)
                    ->where('model_type', get_class($user))
                    ->where('role_id', $user->active_role_id)
                    ->first();
                if ($assignment && $assignment->branch_id) {
                    $query->where('branch_id', $assignment->branch_id);
                }
            }
        }

        return response()->json($query->paginate(10));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'audit_date' => 'required|date',
            'notes' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $batchId = (string) \Illuminate\Support\Str::uuid();
        
        $branchQuery = \App\Models\Branch::where('status', 'Aktif');
        if (!empty($validated['branch_id'])) {
            $branchQuery->where('id', $validated['branch_id']);
        }
        $branches = $branchQuery->get();

        if ($branches->isEmpty()) {
            return response()->json(['message' => 'Tidak ada cabang aktif yang ditemukan.'], 400);
        }

        $creatorId = $request->user()->id;

        DB::beginTransaction();
        try {
            foreach ($branches as $branch) {
                $stockOpname = StockOpname::create([
                    'batch_id' => $batchId,
                    'branch_id' => $branch->id,
                    'created_by' => $creatorId,
                    'audit_date' => $validated['audit_date'],
                    'status' => 'draft',
                    'notes' => $validated['notes'] ?? '',
                ]);

                $endDate = \Carbon\Carbon::parse($validated['audit_date'])->endOfDay();

                // Cari audit terakhir yang approved
                $lastAudit = StockOpname::where('branch_id', $branch->id)
                    ->where('status', 'approved')
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                $startDate = $lastAudit ? $lastAudit->created_at : '2000-01-01 00:00:00';

                $salesData = DB::table('sale_items')
                    ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                    ->join('product_branches', 'sale_items.product_branch_id', '=', 'product_branches.id')
                    ->where('sales.branch_id', $branch->id)
                    ->whereBetween('sales.created_at', [$startDate, $endDate])
                    ->where('sales.status', 'completed')
                    ->select('product_branches.product_id', DB::raw('SUM(sale_items.qty) as total_sold'))
                    ->groupBy('product_branches.product_id')
                    ->pluck('total_sold', 'product_id');

                $invQuery = ProductBranch::where('branch_id', $branch->id);
                if (!empty($validated['category_id'])) {
                    $invQuery->whereHas('product', function($q) use ($validated) {
                        $q->where('category_id', $validated['category_id']);
                    });
                }

                $invQuery->chunkById(500, function ($inventories) use ($stockOpname, $salesData) {
                    $items = [];
                    foreach ($inventories as $inv) {
                        $items[] = [
                            'stock_opname_id' => $stockOpname->id,
                            'product_id' => $inv->product_id,
                            'system_qty' => $inv->stock,
                            'physical_qty' => null,
                            'damaged_qty' => null,
                            'sold_qty' => $salesData[$inv->product_id] ?? 0,
                            'variance' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    if (!empty($items)) {
                        StockOpnameItem::insert($items);
                    }
                });

                $branchAdminIds = DB::table('model_has_roles')
                    ->where('branch_id', $branch->id)
                    ->pluck('model_id');
                $branchAdmins = \App\Models\User::whereIn('id', $branchAdminIds)->get();
                if ($branchAdmins->isNotEmpty()) {
                    \Illuminate\Support\Facades\Notification::send($branchAdmins, new \App\Notifications\StockOpnameCreated('Audit Stock Opname', 'Jadwal audit fisik baru (' . $validated['audit_date'] . ') telah dibuat untuk cabang Anda.', '/audit/stock-opname'));
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal membuat sesi stock opname: '.$e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Sesi Stock Opname berhasil dibuat.',
        ], 201);
    }

    public function updateBatch(Request $request, $batchId)
    {
        $validated = $request->validate([
            'audit_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $opnames = StockOpname::where('batch_id', $batchId)->get();
        if ($opnames->isEmpty()) {
            return response()->json(['message' => 'Batch not found'], 404);
        }

        // Check if any is approved or submitted (cannot edit if so)
        $hasProgress = $opnames->contains(function ($opname) {
            return in_array($opname->status, ['completed', 'approved']);
        });

        if ($hasProgress) {
            return response()->json(['message' => 'Tidak dapat diubah karena sebagian cabang sudah mengirimkan laporan atau sudah disetujui.'], 400);
        }

        StockOpname::where('batch_id', $batchId)->update([
            'audit_date' => $validated['audit_date'],
            'notes' => $validated['notes'] ?? '',
        ]);

        return response()->json(['message' => 'Batch updated successfully.']);
    }

    public function destroyBatch($batchId)
    {
        $opnames = StockOpname::where('batch_id', $batchId)->get();
        if ($opnames->isEmpty()) {
            return response()->json(['message' => 'Batch not found'], 404);
        }

        $hasProgress = $opnames->contains(function ($opname) {
            return in_array($opname->status, ['completed', 'approved']);
        });

        if ($hasProgress) {
            return response()->json(['message' => 'Tidak dapat dihapus karena sebagian cabang sudah mengirimkan laporan atau sudah disetujui.'], 400);
        }

        StockOpname::where('batch_id', $batchId)->delete();

        return response()->json(['message' => 'Batch deleted successfully.']);
    }

    public function show($id)
    {
        $stockOpname = StockOpname::with(['branch', 'creator'])->findOrFail($id);
        return response()->json($stockOpname);
    }

    public function items(Request $request, $id)
    {
        $query = StockOpnameItem::with('product')->where('stock_opname_id', $id);
        
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('product', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 20);
        return response()->json($query->paginate($perPage));
    }

    public function updateItem(Request $request, $id, $itemId)
    {
        $stockOpname = StockOpname::findOrFail($id);
        if ($stockOpname->status === 'approved') {
            return response()->json(['message' => 'Cannot modify approved stock opname.'], 400);
        }

        $validated = $request->validate([
            'physical_qty' => 'required|integer|min:0',
            'damaged_qty' => 'nullable|integer|min:0',
            'reason' => 'nullable|string',
        ]);

        $item = StockOpnameItem::where('stock_opname_id', $id)->findOrFail($itemId);
        $item->physical_qty = $validated['physical_qty'];
        $item->damaged_qty = $validated['damaged_qty'] ?? 0;
        $totalPhysical = $item->physical_qty + $item->damaged_qty;
        $item->variance = $totalPhysical - $item->system_qty;
        $item->reason = $validated['reason'];
        $item->save();

        if ($stockOpname->status === 'draft') {
            $stockOpname->status = 'in_progress';
            $stockOpname->save();
        }

        return response()->json(['message' => 'Item updated', 'data' => $item]);
    }

    public function submit(Request $request, $id)
    {
        $stockOpname = StockOpname::findOrFail($id);
        
        if (!in_array($stockOpname->status, ['draft', 'in_progress'])) {
            return response()->json(['message' => 'Status dokumen saat ini tidak dapat di-submit.'], 400);
        }

        $pin = $request->input('pin');
        if (!$pin) {
            return response()->json(['message' => 'PIN otorisasi Kepala Cabang dibutuhkan untuk mengirim laporan!'], 400);
        }

        // Verifikasi PIN via database RBAC permission
        $managers = \App\Models\User::all()->filter(function($u) {
            return $u->can('Stock Opname Approve') || $u->can('Stock Opname Validate') || $u->can('Stock Opname PIN') || $u->can('manage all');
        });

        $authorized = false;
        foreach ($managers as $manager) {
            if ($manager->pos_pin && ($manager->pos_pin === $pin || \Illuminate\Support\Facades\Hash::check($pin, $manager->pos_pin))) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            return response()->json(['message' => 'PIN otorisasi Kepala Cabang tidak valid!'], 403);
        }

        $stockOpname->status = 'completed';
        $stockOpname->save();

        return response()->json(['message' => 'Stock Opname berhasil di-submit untuk review.']);
    }

    public function revision(Request $request, $id)
    {
        $stockOpname = StockOpname::findOrFail($id);
        
        if ($stockOpname->status !== 'completed') {
            return response()->json(['message' => 'Hanya dokumen berstatus Menunggu Review yang dapat dikembalikan.'], 400);
        }

        $validated = $request->validate([
            'notes' => 'required|string',
        ]);

        $stockOpname->status = 'in_progress';
        $stockOpname->notes = $stockOpname->notes ? $stockOpname->notes . "\n[Revisi]: " . $validated['notes'] : "[Revisi]: " . $validated['notes'];
        $stockOpname->save();

        return response()->json(['message' => 'Dokumen dikembalikan ke cabang untuk revisi.']);
    }

    public function approve(Request $request, $id)
    {
        $stockOpname = StockOpname::with('items')->findOrFail($id);
        
        if ($stockOpname->status !== 'completed') {
            return response()->json(['message' => 'Hanya dokumen berstatus Menunggu Review (Completed) yang dapat di-approve.'], 400);
        }

        DB::beginTransaction();
        try {
            foreach ($stockOpname->items as $item) {
                if ($item->physical_qty !== null && $item->variance !== 0) {
                    $inventory = ProductBranch::where('branch_id', $stockOpname->branch_id)
                        ->where('product_id', $item->product_id)
                        ->first();
                    
                    if ($inventory) {
                        $inventory->stock = $item->physical_qty;
                        $inventory->save();

                        // Log movement
                        DB::table('stock_movements')->insert([
                            'product_branch_id' => $inventory->id,
                            'type' => 'adjustment',
                            'quantity' => $item->variance,
                            'reference_type' => 'App\Models\StockOpname',
                            'reference_id' => $stockOpname->id,
                            'notes' => 'Stock Opname Audit Adjustment',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            $stockOpname->status = 'approved';
            $stockOpname->save();

            DB::commit();
            return response()->json(['message' => 'Stock Opname approved and inventory updated.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to approve: ' . $e->getMessage()], 500);
        }
    }
}
