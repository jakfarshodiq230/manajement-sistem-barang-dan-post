<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeductionType;
use Illuminate\Http\Request;

class DeductionTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = DeductionType::query();

        if ($request->has('q')) {
            $search = $request->q;
            $query->where('name', 'like', "%{$search}%");
        }

        $itemsPerPage = $request->get('itemsPerPage', 15);
        if ($itemsPerPage == -1) {
            return response()->json($query->get());
        }

        $deductions = $query->paginate($itemsPerPage);
        return response()->json($deductions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:potongan,tunjangan',
            'amount' => 'required|numeric|min:0',
            'is_percentage' => 'boolean',
            'status' => 'required|in:Aktif,Nonaktif',
            'description' => 'nullable|string',
        ]);

        $deduction = DeductionType::create($validated);
        return response()->json($deduction, 201);
    }

    public function show(DeductionType $deductionType)
    {
        return response()->json($deductionType);
    }

    public function update(Request $request, DeductionType $deductionType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:potongan,tunjangan',
            'amount' => 'required|numeric|min:0',
            'is_percentage' => 'boolean',
            'status' => 'required|in:Aktif,Nonaktif',
            'description' => 'nullable|string',
        ]);

        $deductionType->update($validated);
        return response()->json($deductionType);
    }

    public function destroy(DeductionType $deductionType)
    {
        $deductionType->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
