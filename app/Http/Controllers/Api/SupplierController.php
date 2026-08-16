<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $q = $request->query('q', '');
        $itemsPerPage = $request->query('itemsPerPage', 10);
        $all = $request->query('all', false);

        $query = \App\Models\Supplier::query();

        if ($q) {
            $query->where('name', 'like', "%{$q}%")
                  ->orWhere('contact_person', 'like', "%{$q}%");
        }
        
        $query->orderBy('created_at', 'desc');

        if ($all) {
            return response()->json($query->get());
        }

        return response()->json($query->paginate($itemsPerPage));
    }

    public function store(Request $request)
    {
        if (!request()->user()->can('Data Supplier Create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        
        $supplier = \App\Models\Supplier::create($request->all());
        return response()->json(['message' => 'Supplier berhasil ditambahkan', 'supplier' => $supplier], 201);
    }

    public function show(\App\Models\Supplier $supplier)
    {
        return response()->json($supplier);
    }

    public function update(Request $request, \App\Models\Supplier $supplier)
    {
        if (!request()->user()->can('Data Supplier Write')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        
        $supplier->update($request->all());
        return response()->json(['message' => 'Supplier berhasil diperbarui', 'supplier' => $supplier]);
    }

    public function destroy(\App\Models\Supplier $supplier)
    {
        if (!request()->user()->can('Data Supplier Delete')) {
            abort(403, 'Unauthorized action.');
        }

        $supplier->delete();
        return response()->json(['message' => 'Supplier berhasil dihapus']);
    }
}
