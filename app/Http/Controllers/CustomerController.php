<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q', '') ?: $request->query('search', '');
        $isActive = $request->query('is_active');
        $itemsPerPage = $request->query('itemsPerPage', 10);
        $all = $request->query('all', false);

        $query = Customer::query();

        if ($q) {
            $query->where(function($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('company_name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($isActive !== null && $isActive !== 'all' && $isActive !== '') {
            $boolActive = ($isActive === '1' || $isActive === 1 || $isActive === 'true' || $isActive === true);
            $query->where('is_active', $boolActive);
        }
        
        $query->orderBy('name', 'asc');

        if ($all) {
            return response()->json($query->get());
        }

        $totalAll = Customer::count();
        $totalActive = Customer::where('is_active', true)->count();
        $totalWithLimit = Customer::where('credit_limit', '>', 0)->count();

        $paginator = $query->paginate($itemsPerPage);

        return response()->json([
            'data' => $paginator->items(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'summary' => [
                'total' => $totalAll,
                'active' => $totalActive,
                'with_limit' => $totalWithLimit,
            ],
        ]);
    }

    public function store(Request $request)
    {
        if (!request()->user()->can('Data Pelanggan Create')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'nik' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $customer = Customer::create($validated);

        return response()->json([
            'message' => 'Customer created successfully.',
            'customer' => $customer
        ], 201);
    }

    public function show(Customer $customer)
    {
        $customer->load('receivables.payments');
        return response()->json($customer);
    }

    public function update(Request $request, Customer $customer)
    {
        if (!request()->user()->can('Data Pelanggan Update') && !request()->user()->can('Data Pelanggan Write')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'nik' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $customer->update($validated);

        return response()->json([
            'message' => 'Customer updated successfully.',
            'customer' => $customer
        ]);
    }

    public function destroy(Customer $customer)
    {
        if (!request()->user()->can('Data Pelanggan Delete')) {
            abort(403, 'Unauthorized action.');
        }

        $customer->delete();
        return response()->json(['message' => 'Customer deleted successfully.']);
    }
}
