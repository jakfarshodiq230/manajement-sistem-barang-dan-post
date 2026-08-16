<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = \App\Models\Category::query();
        
        $search = $request->query('search');
        $itemsPerPage = $request->query('itemsPerPage', 15);
        $page = $request->query('page', 1);

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }
        
        $query->orderBy('name', 'asc');

        if ($itemsPerPage == -1) {
            $categories = $query->get();
            $paginated = null;
        } else {
            $paginated = $query->paginate($itemsPerPage, ['*'], 'page', $page);
            $categories = $paginated->items();
        }

        $response = [
            'data' => $categories,
        ];

        if ($paginated) {
            $response['current_page'] = $paginated->currentPage();
            $response['last_page'] = $paginated->lastPage();
            $response['per_page'] = $paginated->perPage();
            $response['total'] = $paginated->total();
        }

        return response()->json($response);
    }

    public function store(Request $request)
    {
        if (!request()->user()->can('Kategori Barang Create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string']);
        $category = \App\Models\Category::create($request->all());
        return response()->json(['message' => 'Kategori berhasil ditambahkan', 'category' => $category], 201);
    }

    public function show(\App\Models\Category $category)
    {
        return response()->json($category);
    }

    public function update(Request $request, \App\Models\Category $category)
    {
        if (!request()->user()->can('Kategori Barang Write')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string']);
        $category->update($request->all());
        return response()->json(['message' => 'Kategori berhasil diperbarui', 'category' => $category]);
    }

    public function destroy(\App\Models\Category $category)
    {
        if (!request()->user()->can('Kategori Barang Delete')) {
            abort(403, 'Unauthorized action.');
        }

        $category->delete();
        return response()->json(['message' => 'Kategori berhasil dihapus']);
    }

    public function import(Request $request)
    {
        if (!request()->user()->can('Kategori Barang Create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), "r");
        
        $header = true;
        $count = 0;
        
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($header) {
                $header = false;
                continue; // Skip header row
            }
            
            // Format: Nama Kategori, Deskripsi
            if (isset($row[0]) && trim($row[0]) !== '') {
                \App\Models\Category::create([
                    'name' => trim($row[0]),
                    'description' => isset($row[1]) ? trim($row[1]) : null,
                ]);
                $count++;
            }
        }
        
        fclose($handle);
        return response()->json(['message' => "$count Kategori berhasil diimpor"]);
    }
}
