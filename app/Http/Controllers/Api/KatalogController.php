<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\ProductBranch;
use App\Models\Category;

class KatalogController extends Controller
{
    public function getKatalog(Request $request, $hash)
    {
        // Decode obscured ID
        $decoded = base64_decode($hash);
        if (strpos($decoded, 'store-') === 0) {
            $branch_id = str_replace('store-', '', $decoded);
        } else {
            $branch_id = $hash; // Fallback to raw ID if not obscured
        }

        $branch = Branch::where('status', 'Aktif')->findOrFail($branch_id);

        $query = ProductBranch::with(['product.category'])
            ->where('branch_id', $branch_id)
            ->whereHas('product', function($q) {
                $q->where('status', 'Aktif');
            });

        // Filter by category
        if ($request->has('category_id') && $request->category_id) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        // Search by product name
        if ($request->has('search') && $request->search) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        $products = $query->paginate(24);

        $categories = Category::all();

        return response()->json([
            'branch' => $branch,
            'categories' => $categories,
            'products' => $products
        ]);
    }
}
