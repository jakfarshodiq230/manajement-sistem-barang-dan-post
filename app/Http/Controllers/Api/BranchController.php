<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isGlobal = false;
        $userBranchIds = [];

        if ($user) {
            if ($user->can('manage all') || $user->can('all') || $user->can('*') || $user->can('Cabang Create')) {
                $isGlobal = true;
            }

            $assignments = \DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->where('model_type', get_class($user))
                ->pluck('branch_id');

            if ($assignments->contains(null) && $assignments->count() > 0) {
                $isGlobal = true;
            }

            $userBranchIds = $assignments->filter()->unique()->values()->toArray();
            if (empty($userBranchIds) && $user->branch_id) {
                $userBranchIds = [$user->branch_id];
            }
        }

        // For user assignment management or global branch list
        if ($request->has('all') && ($request->all == 'true' || $request->all == '1')) {
            return response()->json(Branch::select('id', 'name', 'type', 'address', 'status')->get());
        }

        // For the dropdowns (only id and name)
        if ($request->has('simple')) {
            $query = Branch::select('id', 'name');
            if (!$isGlobal && !empty($userBranchIds)) {
                $query->whereIn('id', $userBranchIds);
            }
            return response()->json($query->get());
        }

        // For the main data table
        $query = Branch::with('owner');
        if (!$isGlobal && !empty($userBranchIds)) {
            $query->whereIn('id', $userBranchIds);
        }

        $search = $request->query('search');
        $itemsPerPage = $request->query('itemsPerPage', 15);
        $page = $request->query('page', 1);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        $query->orderBy('name', 'asc');

        if ($request->has('itemsPerPage')) {
            if ($itemsPerPage == -1) {
                $branches = $query->get();
                $paginated = null;
            } else {
                $paginated = $query->paginate($itemsPerPage, ['*'], 'page', $page);
                $branches = $paginated->items();
            }

            $response = [
                'data' => $branches,
            ];

            if ($paginated) {
                $response['current_page'] = $paginated->currentPage();
                $response['last_page'] = $paginated->lastPage();
                $response['per_page'] = $paginated->perPage();
                $response['total'] = $paginated->total();
            }

            return response()->json($response);
        }

        $branches = $query->get();
        return response()->json($branches);
    }

    public function store(Request $request)
    {
        if (!request()->user()->can('Cabang Create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|in:store,warehouse',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'owner_id' => 'nullable|exists:owners,id',
            'status' => 'nullable|string|in:Aktif,Tutup',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->only(['name', 'type', 'email', 'phone', 'address', 'owner_id']);
        $data['status'] = $request->status ?? 'Aktif';

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $branch = Branch::create($data);

        return response()->json(['message' => 'Cabang berhasil dibuat', 'branch' => $branch], 201);
    }

    public function show(Branch $branch)
    {
        $branch->load('owner');
        return response()->json($branch);
    }

    public function update(Request $request, Branch $branch)
    {
        if (!request()->user()->can('Cabang Write')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|in:store,warehouse',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'owner_id' => 'nullable|exists:owners,id',
            'status' => 'nullable|string|in:Aktif,Tutup',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->only(['name', 'type', 'email', 'phone', 'address', 'owner_id', 'status']);

        if ($request->hasFile('logo')) {
            // Delete old logo
            if ($branch->logo && Storage::disk('public')->exists($branch->logo)) {
                Storage::disk('public')->delete($branch->logo);
            }
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        } elseif ($request->logo === null && $request->has('logo')) {
             // Handle clear logo if explicitly passed as null
             if ($branch->logo && Storage::disk('public')->exists($branch->logo)) {
                Storage::disk('public')->delete($branch->logo);
             }
             $data['logo'] = null;
        }

        $branch->update($data);

        return response()->json(['message' => 'Cabang berhasil diupdate', 'branch' => $branch]);
    }

    public function destroy(Branch $branch)
    {
        if (!request()->user()->can('Cabang Delete')) {
            abort(403, 'Unauthorized action.');
        }

        // Instead of deleting, just soft-close it if the user calls DELETE.
        // Or return an error suggesting to close it.
        $branch->update(['status' => 'Tutup']);
        return response()->json(['message' => 'Cabang tidak dihapus, melainkan ditutup (Soft Close) untuk menjaga integritas data.']);
    }
}
