<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class PositionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Position::query();

            if ($request->search) {
                $query->where('name', 'like', "%{$request->search}%");
            }

            if ($request->has('itemsPerPage') && $request->itemsPerPage == -1) {
                $positions = $query->orderBy('name')->get();
                return response()->json([
                    'data' => $positions,
                    'total' => $positions->count()
                ]);
            }

            $perPage = $request->itemsPerPage ?? 10;
            $positions = $query->orderBy('name')->paginate($perPage);

            return response()->json([
                'data' => $positions->items(),
                'total' => $positions->total(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil data jabatan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'base_salary' => 'nullable|numeric',
            'default_allowance' => 'nullable|numeric',
            'default_deduction' => 'nullable|numeric',
            'description' => 'nullable|string',
            'status' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();
            $position = Position::create($validated);
            DB::commit();

            return response()->json([
                'message' => 'Jabatan berhasil ditambahkan',
                'data' => $position
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menambahkan jabatan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $position = Position::find($id);
        if (!$position) {
            return response()->json(['message' => 'Jabatan tidak ditemukan'], 404);
        }
        return response()->json(['data' => $position]);
    }

    public function update(Request $request, $id)
    {
        $position = Position::find($id);
        if (!$position) {
            return response()->json(['message' => 'Jabatan tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'base_salary' => 'nullable|numeric',
            'default_allowance' => 'nullable|numeric',
            'default_deduction' => 'nullable|numeric',
            'description' => 'nullable|string',
            'status' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();
            $position->update($validated);
            DB::commit();

            return response()->json([
                'message' => 'Jabatan berhasil diupdate',
                'data' => $position
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal mengupdate jabatan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $position = Position::find($id);
        if (!$position) {
            return response()->json(['message' => 'Jabatan tidak ditemukan'], 404);
        }

        try {
            DB::beginTransaction();
            $position->delete();
            DB::commit();

            return response()->json([
                'message' => 'Jabatan berhasil dihapus'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menghapus jabatan: ' . $e->getMessage()
            ], 500);
        }
    }
}
