<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OwnerController extends Controller
{
    public function index(Request $request)
    {
        $query = Owner::with('children', 'branches', 'parent');
        
        $search = $request->query('search');
        $itemsPerPage = $request->query('itemsPerPage', 15);
        $page = $request->query('page', 1);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        $query->orderBy('name', 'asc');

        if ($request->has('itemsPerPage')) {
            if ($itemsPerPage == -1) {
                $owners = $query->get();
                $paginated = null;
            } else {
                $paginated = $query->paginate($itemsPerPage, ['*'], 'page', $page);
                $owners = $paginated->items();
            }

            $response = [
                'data' => $owners,
            ];

            if ($paginated) {
                $response['current_page'] = $paginated->currentPage();
                $response['last_page'] = $paginated->lastPage();
                $response['per_page'] = $paginated->perPage();
                $response['total'] = $paginated->total();
            }

            return response()->json($response);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->hasRole('Super Admin') && !$user->can('Manajemen Owner Create') && !$user->can('Owner Create') && !$user->can('create owners')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'parent_id' => 'nullable|exists:owners,id',
            'status' => 'nullable|string|in:Aktif,Nonaktif',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'qris_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->only(['name', 'email', 'phone', 'address', 'parent_id']);
        $data['status'] = $request->status ?? 'Aktif';

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        if ($request->hasFile('qris_image')) {
            $data['qris_image'] = $request->file('qris_image')->store('qris', 'public');
        }

        $owner = Owner::create($data);

        return response()->json(['message' => 'Owner created successfully', 'owner' => $owner], 201);
    }

    public function show(Owner $owner)
    {
        $owner->load('children', 'branches', 'parent');
        return response()->json($owner);
    }

    public function update(Request $request, Owner $owner)
    {
        $user = $request->user();
        if ($user && !$user->hasRole('Super Admin') && !$user->can('Manajemen Owner Write') && !$user->can('Owner Write') && !$user->can('write owners')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'parent_id' => 'nullable|exists:owners,id',
            'status' => 'nullable|string|in:Aktif,Nonaktif',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'qris_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->only(['name', 'email', 'phone', 'address', 'parent_id', 'status']);

        if ($request->hasFile('logo')) {
            // Delete old logo
            if ($owner->logo && Storage::disk('public')->exists($owner->logo)) {
                Storage::disk('public')->delete($owner->logo);
            }
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        } elseif ($request->logo === null && $request->has('logo')) {
             // Handle clear logo if explicitly passed as null
             if ($owner->logo && Storage::disk('public')->exists($owner->logo)) {
                Storage::disk('public')->delete($owner->logo);
             }
             $data['logo'] = null;
        }

        if ($request->hasFile('qris_image')) {
            if ($owner->qris_image && Storage::disk('public')->exists($owner->qris_image)) {
                Storage::disk('public')->delete($owner->qris_image);
            }
            $data['qris_image'] = $request->file('qris_image')->store('qris', 'public');
        } elseif ($request->qris_image === null && $request->has('qris_image')) {
             if ($owner->qris_image && Storage::disk('public')->exists($owner->qris_image)) {
                Storage::disk('public')->delete($owner->qris_image);
             }
             $data['qris_image'] = null;
        }

        $owner->update($data);

        return response()->json(['message' => 'Owner updated successfully', 'owner' => $owner]);
    }

    public function destroy(Owner $owner)
    {
        $user = request()->user();
        if ($user && !$user->hasRole('Super Admin') && !$user->can('Manajemen Owner Delete') && !$user->can('Owner Delete') && !$user->can('delete owners')) {
            abort(403, 'Unauthorized action.');
        }

        $owner->delete();
        return response()->json(['message' => 'Owner deleted successfully']);
    }
}
