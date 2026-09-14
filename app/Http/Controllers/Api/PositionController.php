<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Position::query();
        if ($request->has('q')) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }
        if ($request->has('itemsPerPage')) {
            $itemsPerPage = $request->itemsPerPage;
            return response()->json($query->paginate($itemsPerPage === '-1' ? 1000 : $itemsPerPage));
        }
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'level' => 'integer',
            'base_salary' => 'numeric',
            'default_allowance' => 'nullable|numeric',
            'default_deduction' => 'nullable|numeric',
            'description' => 'nullable|string',
            'status' => 'string',
        ]);
        
        $position = Position::create($validated);
        return response()->json(['message' => 'Jabatan berhasil ditambahkan', 'data' => $position]);
    }

    public function show(string $id)
    {
        return response()->json(Position::findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'level' => 'integer',
            'base_salary' => 'numeric',
            'default_allowance' => 'nullable|numeric',
            'default_deduction' => 'nullable|numeric',
            'description' => 'nullable|string',
            'status' => 'string',
        ]);
        
        $position = Position::findOrFail($id);
        $position->update($validated);
        return response()->json(['message' => 'Jabatan berhasil diupdate', 'data' => $position]);
    }

    public function destroy(string $id)
    {
        $position = Position::findOrFail($id);
        $position->delete();
        return response()->json(['message' => 'Jabatan berhasil dihapus']);
    }
}
