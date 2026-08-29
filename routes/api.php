<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/auth/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
Route::get('/katalog/{branch_id}', [\App\Http\Controllers\Api\KatalogController::class, 'getKatalog']);

Route::get('/fix-permissions', function () {
    app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    
    // Ensure dev role has Kasir (POS) Create permission
    $dev = \Spatie\Permission\Models\Role::where('name', 'Developer')->orWhere('name', 'dev')->first();
    if ($dev) {
        $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'Kasir (POS) Create']);
        if (!$dev->hasPermissionTo('Kasir (POS) Create')) {
            $dev->givePermissionTo($permission);
        }
    }
    return response()->json(['message' => 'Cache cleared and permissions synced for dev role!']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/apps/update-pin', [\App\Http\Controllers\Api\AuthController::class, 'updatePin']);
    Route::post('/apps/users/{id}/update-pin', [\App\Http\Controllers\Api\UserController::class, 'updatePin']);
    Route::post('/apps/users/{id}/assignments', [\App\Http\Controllers\Api\UserController::class, 'updateAssignments']);
    Route::post('/users/{id}/assignments', [\App\Http\Controllers\Api\UserController::class, 'updateAssignments']);
    Route::post('/apps/verify-pin', [\App\Http\Controllers\Api\AuthController::class, 'verifyPin']);
});

Route::middleware(['auth:sanctum', \App\Http\Middleware\SetBranchPermission::class])->prefix('apps')->group(function () {
    Route::get('/dashboards/analytics', [\App\Http\Controllers\Api\DashboardController::class, 'analytics']);
    Route::get('/dashboards/sales-analytics', [\App\Http\Controllers\Api\DashboardController::class, 'salesAnalytics']);
    
    // Global Stock Report
    Route::get('/reports/global-stock', [\App\Http\Controllers\Api\GlobalStockController::class, 'index']);

    Route::get('/dashboards/inventory', [\App\Http\Controllers\Api\DashboardController::class, 'inventory']);
    Route::get('/dashboards/profit', [\App\Http\Controllers\Api\DashboardController::class, 'profit']);
    Route::get('/dashboards/audit', [\App\Http\Controllers\Api\DashboardController::class, 'audit']);
    
    // Audit Modules
    Route::get('cash-reconciliations/monitoring', [\App\Http\Controllers\Api\CashReconciliationController::class, 'monitoring']);
    Route::get('cash-reconciliations/required-date', [\App\Http\Controllers\Api\CashReconciliationController::class, 'getRequiredDate']);
    Route::get('cash-reconciliations/preview', [\App\Http\Controllers\Api\CashReconciliationController::class, 'preview']);
    Route::apiResource('cash-reconciliations', \App\Http\Controllers\Api\CashReconciliationController::class);
    
    Route::put('stock-opnames/batch/{batchId}', [\App\Http\Controllers\Api\StockOpnameController::class, 'updateBatch']);
    Route::delete('stock-opnames/batch/{batchId}', [\App\Http\Controllers\Api\StockOpnameController::class, 'destroyBatch']);
    Route::get('stock-opnames/{id}/items', [\App\Http\Controllers\Api\StockOpnameController::class, 'items']);
    Route::apiResource('stock-opnames', \App\Http\Controllers\Api\StockOpnameController::class)->except(['destroy']);
    Route::put('stock-opnames/{id}/items/{itemId}', [\App\Http\Controllers\Api\StockOpnameController::class, 'updateItem']);
    Route::post('stock-opnames/{id}/submit', [\App\Http\Controllers\Api\StockOpnameController::class, 'submit']);
    Route::post('stock-opnames/{id}/revision', [\App\Http\Controllers\Api\StockOpnameController::class, 'revision']);
    Route::post('stock-opnames/{id}/approve', [\App\Http\Controllers\Api\StockOpnameController::class, 'approve']);

    // Rekap Tahunan & Bulanan
    Route::get('/rekap/tahunan/pdf', [\App\Http\Controllers\Api\RekapController::class, 'exportPdfTahunan']);
    Route::get('/rekap/tahunan', [\App\Http\Controllers\Api\RekapController::class, 'tahunan']);
    Route::get('/rekap/bulanan', [\App\Http\Controllers\Api\RekapController::class, 'bulanan']);

    // Users & Assignments
    Route::post('users/{id}/assignments', [\App\Http\Controllers\Api\UserController::class, 'updateAssignments']);
    Route::apiResource('users', \App\Http\Controllers\Api\UserController::class);
    Route::apiResource('roles', \App\Http\Controllers\Api\RoleController::class);
    Route::apiResource('permissions', \App\Http\Controllers\Api\PermissionController::class);
    Route::get('modules/navigation', [\App\Http\Controllers\Api\ModuleController::class, 'navigation']);
    Route::post('modules/reorder', [\App\Http\Controllers\Api\ModuleController::class, 'reorder']);
    Route::apiResource('modules', \App\Http\Controllers\Api\ModuleController::class);
    Route::apiResource('owners', \App\Http\Controllers\Api\OwnerController::class);
    // Branches endpoints
    Route::apiResource('branches', \App\Http\Controllers\Api\BranchController::class);
    // Employees endpoints
    Route::apiResource('employees', \App\Http\Controllers\Api\EmployeeController::class);
    // Categories endpoints
    Route::post('categories/import', [\App\Http\Controllers\Api\CategoryController::class, 'import']);
    Route::apiResource('categories', \App\Http\Controllers\Api\CategoryController::class);
    // Suppliers endpoints
    Route::apiResource('suppliers', \App\Http\Controllers\Api\SupplierController::class);
    // Purchase Orders endpoints
    Route::apiResource('purchase-orders', \App\Http\Controllers\Api\PurchaseOrderController::class);
    // Goods Receipts endpoints
    Route::post('goods-receipts/{id}/approve', [\App\Http\Controllers\Api\GoodsReceiptController::class, 'approve']);
    Route::post('goods-receipts/{id}/reject', [\App\Http\Controllers\Api\GoodsReceiptController::class, 'reject']);
    Route::apiResource('goods-receipts', \App\Http\Controllers\Api\GoodsReceiptController::class);
    
    // Customers and Receivables
    Route::apiResource('customers', \App\Http\Controllers\CustomerController::class);
    Route::apiResource('receivables', \App\Http\Controllers\ReceivableController::class)->except(['store', 'update']);
    Route::post('receivables/{receivable}/pay', [\App\Http\Controllers\ReceivableController::class, 'pay']);
    Route::post('receivables/{receivable}/send-email', [\App\Http\Controllers\ReceivableController::class, 'sendEmail']);
    Route::get('receivables/{receivable}/email-logs', [\App\Http\Controllers\ReceivableController::class, 'emailLogs']);
    Route::post('email-logs/{id}/retry', [\App\Http\Controllers\ReceivableController::class, 'retryEmail']);
    
    Route::apiResource('receipt-settings', \App\Http\Controllers\Api\ReceiptSettingController::class);

    // Sales endpoints
    Route::apiResource('sales', \App\Http\Controllers\Api\SaleController::class);
    // Returns endpoints
    Route::post('returns/{id}/approve', [\App\Http\Controllers\Api\ReturnController::class, 'approve']);
    Route::post('returns/{id}/receive-replacement', [\App\Http\Controllers\Api\ReturnController::class, 'receiveReplacement']);
    Route::apiResource('returns', \App\Http\Controllers\Api\ReturnController::class);

    // Supplier Credits endpoints (Potong Hutang / Saldo Retur Supplier)
    Route::apiResource('supplier-credits', \App\Http\Controllers\Api\SupplierCreditController::class)->only(['index', 'show']);

    // Accounts Payable (Buku Hutang Supplier & Rekap Tagihan Bulanan)
    Route::get('payables/invoices', [\App\Http\Controllers\Api\PayableController::class, 'invoices']);
    Route::get('payables/periods', [\App\Http\Controllers\Api\PayableController::class, 'periods']);
    Route::apiResource('payables', \App\Http\Controllers\Api\PayableController::class);
    Route::post('payables/{id}/pay', [\App\Http\Controllers\Api\PayableController::class, 'recordPayment']);
    Route::delete('payables/{id}/payments/{paymentId}', [\App\Http\Controllers\Api\PayableController::class, 'voidPayment']);

    // Bank Accounts (Master Rekening Bank & Saldo Penerimaan)
    Route::apiResource('bank-accounts', \App\Http\Controllers\Api\BankAccountController::class);

    // Products endpoints
    Route::post('products/import', [\App\Http\Controllers\Api\ProductController::class, 'import']);
    Route::apiResource('products', \App\Http\Controllers\Api\ProductController::class);
    Route::post('product-branches/import', [\App\Http\Controllers\Api\ProductBranchController::class, 'import']);
    Route::apiResource('product-branches', \App\Http\Controllers\Api\ProductBranchController::class);
    Route::put('product-batches/{batchId}', [\App\Http\Controllers\Api\ProductBranchController::class, 'updateBatchPrice']);
    Route::get('product-batches/detail/{batchId}', [\App\Http\Controllers\Api\ProductBranchController::class, 'batchDetail']);
    Route::get('pos/scan-batch/{batchId}', [\App\Http\Controllers\Api\ProductBranchController::class, 'scanBatch']);
    // Cash Shifts (Shift Kasir)
    Route::get('cash-shifts/current', [\App\Http\Controllers\Api\CashShiftController::class, 'current']);
    Route::post('cash-shifts/open', [\App\Http\Controllers\Api\CashShiftController::class, 'open']);
    Route::post('cash-shifts/close', [\App\Http\Controllers\Api\CashShiftController::class, 'close']);
    Route::get('cash-shifts', [\App\Http\Controllers\Api\CashShiftController::class, 'index']);

    // POS Held Bills (Simpan Transaksi Sementara)
    Route::get('pos-held-bills', [\App\Http\Controllers\Api\PosHeldBillController::class, 'index']);
    Route::post('pos-held-bills', [\App\Http\Controllers\Api\PosHeldBillController::class, 'store']);
    Route::delete('pos-held-bills/{id}', [\App\Http\Controllers\Api\PosHeldBillController::class, 'destroy']);

    // Petty Cash (Kas Kecil Cabang)
    Route::apiResource('petty-cashes', \App\Http\Controllers\Api\PettyCashController::class);

    // Branch Capitals (Manajemen Modal & Pengembalian Modal Cabang)
    Route::get('branch-capitals/summary', [\App\Http\Controllers\Api\BranchCapitalController::class, 'summary']);
    Route::post('branch-capitals/send-summary-email', [\App\Http\Controllers\Api\BranchCapitalController::class, 'sendSummaryEmail']);
    Route::post('branch-capitals/{id}/send-email', [\App\Http\Controllers\Api\BranchCapitalController::class, 'sendEmail']);
    Route::get('branch-capitals/{id}/email-logs', [\App\Http\Controllers\Api\BranchCapitalController::class, 'emailLogs']);
    Route::post('branch-capitals/{id}/approve', [\App\Http\Controllers\Api\BranchCapitalController::class, 'approve']);
    Route::post('branch-capitals/{id}/reject', [\App\Http\Controllers\Api\BranchCapitalController::class, 'reject']);
    Route::post('branch-capitals/{id}/void', [\App\Http\Controllers\Api\BranchCapitalController::class, 'void']);
    Route::post('branch-capitals/{id}', [\App\Http\Controllers\Api\BranchCapitalController::class, 'update']);
    Route::apiResource('branch-capitals', \App\Http\Controllers\Api\BranchCapitalController::class);

    // Security, IP Tracking & Anti-Hacker Logs
    Route::get('security/logs', [\App\Http\Controllers\Api\SecurityController::class, 'index']);
    Route::get('security/summary', [\App\Http\Controllers\Api\SecurityController::class, 'summary']);
    Route::get('security/blocked-ips', [\App\Http\Controllers\Api\SecurityController::class, 'getBlockedIps']);
    Route::post('security/block-ip', [\App\Http\Controllers\Api\SecurityController::class, 'blockIp']);
    Route::post('security/unblock-ip', [\App\Http\Controllers\Api\SecurityController::class, 'unblockIp']);
    Route::delete('security/logs/clear', [\App\Http\Controllers\Api\SecurityController::class, 'clearOldLogs']);

    Route::get('stock-transfers/status-counts', [\App\Http\Controllers\Api\StockTransferController::class, 'statusCounts']);
    Route::apiResource('stock-transfers', \App\Http\Controllers\Api\StockTransferController::class)->except(['update', 'destroy']);
    Route::get('stock-transfers/{id}/delivery-note', [\App\Http\Controllers\Api\StockTransferController::class, 'deliveryNote']);
    Route::post('stock-transfers/{id}/prepare', [\App\Http\Controllers\Api\StockTransferController::class, 'prepare']);
    Route::post('stock-transfers/{id}/pickup', [\App\Http\Controllers\Api\StockTransferController::class, 'pickup']);
    Route::post('stock-transfers/{id}/receive', [\App\Http\Controllers\Api\StockTransferController::class, 'receive']);
    Route::post('stock-transfers/{id}/approve', [\App\Http\Controllers\Api\StockTransferController::class, 'approve']);
    Route::post('stock-transfers/{id}/reject', [\App\Http\Controllers\Api\StockTransferController::class, 'reject']);
    Route::post('stock-transfers/{id}/cancel', [\App\Http\Controllers\Api\StockTransferController::class, 'cancel']);
    // Switch active role and branch
    Route::post('switch-role', function (\Illuminate\Http\Request $request) {
        $user = $request->user() ?: auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $roleId = $request->input('role_id');
        $branchId = $request->input('branch_id');
        
        // Verify the user actually has this role
        $hasRole = \DB::table('model_has_roles')
            ->where('model_type', get_class($user))
            ->where('model_id', $user->id)
            ->where('role_id', $roleId)
            ->exists();

        if (!$hasRole) {
            return response()->json(['message' => 'Unauthorized: role not assigned to this user'], 403);
        }

        $user->update([
            'active_role_id' => $roleId,
            'branch_id'      => $branchId ?: null,
        ]);
        $role = \Spatie\Permission\Models\Role::find($roleId);

        $abilityRules = [];
        if ($role) {
            $permissions = $role->permissions->pluck('name');
            if ($permissions->contains('manage all') || $permissions->contains('all') || $permissions->contains('*')) {
                $abilityRules[] = ['action' => 'manage', 'subject' => 'all'];
            } else {
                foreach ($permissions as $perm) {
                    $parts = explode(' ', $perm);
                    if (count($parts) >= 2) {
                        $action = strtolower(array_pop($parts));
                        $subject = implode(' ', $parts);
                        $abilityRules[] = ['action' => $action, 'subject' => $subject];
                    } else {
                        $abilityRules[] = ['action' => strtolower($perm), 'subject' => 'all'];
                    }
                }
            }
        }

        if (empty($abilityRules)) {
            $abilityRules[] = ['action' => 'read', 'subject' => 'Auth'];
        }

        $activeBranch = $user->branch_id ? \App\Models\Branch::find($user->branch_id) : null;

        return response()->json([
            'message'            => 'Role switched successfully',
            'active_role'        => $role ? $role->name : null,
            'active_branch_id'   => $user->branch_id,
            'active_branch_name' => $activeBranch ? $activeBranch->name : 'Semua Cabang (Global)',
            'userAbilityRules'   => $abilityRules,
        ]);
    });
});

Route::middleware('auth:sanctum')->get('/user', function (\Illuminate\Http\Request $request) {
    $user = $request->user() ?: auth()->user();
    if (!$user) {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    $rawAssignments = \DB::table('model_has_roles as mhr')
        ->join('roles', 'mhr.role_id', '=', 'roles.id')
        ->leftJoin('branches', 'mhr.branch_id', '=', 'branches.id')
        ->where('mhr.model_type', get_class($user))
        ->where('mhr.model_id', $user->id)
        ->select(
            'branches.id as branch_id',
            \DB::raw('COALESCE(branches.name, "Semua Cabang (Global)") as branch_name'),
            'roles.id as role_id',
            'roles.name as role_name'
        )
        ->get();

    $assignments = collect();

    // If user has multiple assigned branches, add 'Semua Cabang yang Ditugaskan' as the primary option
    if ($rawAssignments->count() > 1) {
        $first = $rawAssignments->first();
        $assignments->push([
            'branch_id'   => null,
            'branch_name' => 'Semua Cabang yang Ditugaskan (Multi-Cabang)',
            'role_id'     => $first->role_id,
            'role_name'   => $first->role_name,
            'is_all'      => true,
        ]);
    }

    foreach ($rawAssignments as $a) {
        $assignments->push($a);
    }

    $directRoles = $user->roles->pluck('name')->toArray();
    $foundRole = $user->active_role_id ? \Spatie\Permission\Models\Role::find($user->active_role_id) : null;
    $firstAssig = $rawAssignments->first();
    $activeRole = $foundRole ? $foundRole->name : ($firstAssig ? $firstAssig->role_name : (!empty($directRoles) ? $directRoles[0] : 'User'));
    $activeBranch = $user->branch_id ? \App\Models\Branch::find($user->branch_id) : null;

    // Calculate userAbilityRules dynamically from Spatie permissions in database
    $assignedRoleIds = $rawAssignments->pluck('role_id')->unique()->filter();
    $userPermissions = collect();

    // 1. Load permissions from active role (if switched) or assigned roles
    if ($user->active_role_id) {
        $activeRoleModel = \Spatie\Permission\Models\Role::find($user->active_role_id);
        if ($activeRoleModel) {
            $userPermissions = $userPermissions->merge($activeRoleModel->permissions->pluck('name'));
        }
    } elseif ($assignedRoleIds->isNotEmpty()) {
        $rolePermissions = \Spatie\Permission\Models\Permission::join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->whereIn('role_has_permissions.role_id', $assignedRoleIds)
            ->pluck('permissions.name');
        $userPermissions = $userPermissions->merge($rolePermissions);
    }

    // 2. Merge direct user permissions from database
    $userPermissions = $userPermissions->merge($user->getAllPermissions()->pluck('name'))->unique();

    $abilityRules = [];

    // 3. Dynamic CASL Rule Generation (100% DB-driven, zero hardcoded role names)
    if ($userPermissions->contains('manage all') || $userPermissions->contains('all') || $userPermissions->contains('*')) {
        $abilityRules[] = ['action' => 'manage', 'subject' => 'all'];
    } else {
        foreach ($userPermissions as $permName) {
            $parts = explode(' ', trim($permName));
            if (count($parts) >= 2) {
                $action = strtolower(array_pop($parts));
                $subject = implode(' ', $parts);
                $abilityRules[] = ['action' => $action, 'subject' => $subject];

                // Aliases for compatibility with Vue route action checks
                if ($action === 'write' || $action === 'update') {
                    $abilityRules[] = ['action' => 'write', 'subject' => $subject];
                    $abilityRules[] = ['action' => 'update', 'subject' => $subject];
                    $abilityRules[] = ['action' => 'edit', 'subject' => $subject];
                }

                if (in_array(strtolower($subject), ['log keamanan', 'keamanan sistem', 'security logs', 'log keamanan & akses ip'])) {
                    $abilityRules[] = ['action' => $action, 'subject' => 'Log Keamanan'];
                    $abilityRules[] = ['action' => $action, 'subject' => 'Security Logs'];
                    $abilityRules[] = ['action' => $action, 'subject' => 'Keamanan Sistem'];
                    $abilityRules[] = ['action' => $action, 'subject' => 'Log Keamanan & Akses IP'];
                }
            } else {
                $abilityRules[] = ['action' => strtolower($permName), 'subject' => 'all'];
            }
        }
    }
    $abilityRules[] = ['action' => 'read', 'subject' => 'Auth'];

    return response()->json([
        'id'                 => $user->id,
        'fullName'           => $user->name,
        'username'           => strtolower(str_replace(' ', '', $user->name)),
        'email'              => $user->email,
        'role'               => $activeRole,
        'branch_id'          => $user->branch_id,
        'branch_name'        => $activeBranch ? $activeBranch->name : 'Semua Cabang (Global)',
        'active_branch_id'   => $user->branch_id,
        'active_branch_name' => $activeBranch ? $activeBranch->name : 'Semua Cabang (Global)',
        'assignments'        => $assignments,
        'avatar'             => '',
        'userAbilityRules'   => $abilityRules,
    ]);
});

use App\Models\Module;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

Route::get('/init-dashboard', function () {
    $parent = Module::firstOrCreate(['name' => 'Dashboard', 'slug' => 'dashboard'], ['sequence' => 1]);
    
    $modules = [
        ['name' => 'Dashboard Analytics', 'slug' => 'dashboards/analytics'],
        ['name' => 'Dashboard Penjualan', 'slug' => 'dashboards/penjualan'],
        ['name' => 'Dashboard Barang', 'slug' => 'dashboards/barang'],
        ['name' => 'Dashboard Keuntungan', 'slug' => 'dashboards/keuntungan'],
        ['name' => 'Dashboard Audit', 'slug' => 'dashboards/audit'],
    ];

    $dev = Role::where('name', 'Developer')->first();
    $owner = Role::where('name', 'Owner')->first();

    foreach ($modules as $mod) {
        $child = Module::where('name', $mod['name'])->orWhere('slug', $mod['slug'])->first();
        if ($child) {
            $child->update(['name' => $mod['name'], 'slug' => $mod['slug'], 'parent_id' => $parent->id]);
        } else {
            $child = Module::create(['name' => $mod['name'], 'slug' => $mod['slug'], 'parent_id' => $parent->id, 'sequence' => 1]);
        }

        $permName = $mod['name'] . ' Read';
        $permission = Permission::where('name', $permName)->first();
        if (!$permission) {
            $permission = Permission::create(['name' => $permName, 'module_id' => $child->id]);
        } else {
            $permission->update(['module_id' => $child->id]);
        }

        if ($dev) $dev->givePermissionTo($permission);
        if ($owner) $owner->givePermissionTo($permission);
    }

    return response()->json(['message' => '5 Dashboard sub-modules and permissions initialized successfully.']);
});

Route::get('/debug-modules', function() {
    \Illuminate\Support\Facades\DB::table('modules')->where('slug', 'dashboard-penjualan')->delete();
    return response()->json(\App\Models\Module::where('name', 'like', 'Dashboard%')->get(['id', 'name', 'slug']));
});

Route::get('/init-rekap', function () {
    $parent = Module::firstOrCreate(
        ['name' => 'Audit & Laporan', 'slug' => 'audit'],
        ['sequence' => 2]
    );

    $modules = [
        ['name' => 'Closing Harian', 'slug' => 'audit/closing-harian'],
        ['name' => 'Stock Opname',   'slug' => 'audit/stock-opname'],
        ['name' => 'Rekap Tahunan',  'slug' => 'audit/rekap'],
        ['name' => 'Analisis Stok',  'slug' => 'laporan/stok-aging'],
    ];

    $dev   = Role::where('name', 'Developer')->first();
    $owner = Role::where('name', 'Owner')->first();

    foreach ($modules as $mod) {
        $child = Module::where('slug', $mod['slug'])->first();
        if ($child) {
            $child->update(['name' => $mod['name'], 'parent_id' => $parent->id]);
        } else {
            $child = Module::create(['name' => $mod['name'], 'slug' => $mod['slug'], 'parent_id' => $parent->id, 'sequence' => 1]);
        }

        $permName   = $mod['name'] . ' Read';
        $permission = Permission::firstOrCreate(['name' => $permName], ['module_id' => $child->id]);
        $permission->update(['module_id' => $child->id]);

        if ($dev)   $dev->givePermissionTo($permission);
        if ($owner) $owner->givePermissionTo($permission);
    }

    return response()->json(['message' => 'Audit modules & Rekap Tahunan registered successfully.']);
});

Route::get('/init-piutang', function () {
    $dev   = Role::where('name', 'Developer')->first();
    $owner = Role::where('name', 'Owner')->first();
    
    // Register Master Data -> Pelanggan
    $masterData = Module::firstOrCreate(
        ['name' => 'Master Data', 'slug' => 'master-data'],
        ['sequence' => 3]
    );

    $customerModule = Module::where('slug', 'customers')->first();
    if ($customerModule) {
        $customerModule->update(['name' => 'Data Pelanggan', 'parent_id' => $masterData->id]);
    } else {
        $customerModule = Module::create(['name' => 'Data Pelanggan', 'slug' => 'customers', 'parent_id' => $masterData->id, 'sequence' => 5]);
    }

    $customerPerms = ['Data Pelanggan Create', 'Data Pelanggan Read', 'Data Pelanggan Update', 'Data Pelanggan Delete'];
    foreach ($customerPerms as $perm) {
        $permission = Permission::firstOrCreate(['name' => $perm], ['module_id' => $customerModule->id]);
        $permission->update(['module_id' => $customerModule->id]);
        if ($dev) $dev->givePermissionTo($permission);
        if ($owner) $owner->givePermissionTo($permission);
    }

    // Register Modul Piutang
    $receivableModule = Module::where('slug', 'receivables')->first();
    if ($receivableModule) {
        $receivableModule->update(['name' => 'Data Piutang', 'parent_id' => null]);
    } else {
        $receivableModule = Module::create(['name' => 'Data Piutang', 'slug' => 'receivables', 'parent_id' => null, 'sequence' => 4]);
    }

    $receivablePerms = ['Data Piutang Read', 'Data Piutang Pay'];
    foreach ($receivablePerms as $perm) {
        $permission = Permission::firstOrCreate(['name' => $perm], ['module_id' => $receivableModule->id]);
        $permission->update(['module_id' => $receivableModule->id]);
        if ($dev) $dev->givePermissionTo($permission);
        if ($owner) $owner->givePermissionTo($permission);
    }

    return response()->json(['message' => 'Piutang & Customer modules registered and permissions assigned successfully.']);
});

// Document Validation & PDF Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/apps/documents/{type}/{id}/verify', [App\Http\Controllers\Api\DocumentVerificationController::class, 'verify']);
    Route::post('/apps/documents/{type}/{id}/submit', [App\Http\Controllers\Api\DocumentVerificationController::class, 'submitDocument']);
    Route::post('/apps/documents/{type}/{id}/validate', [\App\Http\Controllers\Api\DocumentVerificationController::class, 'validateDocument']);
    Route::post('/apps/documents/{type}/{id}/approve', [\App\Http\Controllers\Api\DocumentVerificationController::class, 'approveDocument']);
    Route::post('/apps/documents/{type}/{id}/reject', [\App\Http\Controllers\Api\DocumentVerificationController::class, 'rejectDocument']);
    Route::get('/apps/documents/{type}/{id}/pdf', [\App\Http\Controllers\Api\DocumentPdfController::class, 'download']);

    // Notifications
    Route::get('/apps/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::post('/apps/notifications/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::delete('/apps/notifications/{id}', [\App\Http\Controllers\Api\NotificationController::class, 'destroy']);
    
    // Reports
    Route::get('/apps/reports/stock-history', [\App\Http\Controllers\Api\ReportController::class, 'stockHistory']);
    Route::get('/apps/reports/current-stock', [\App\Http\Controllers\Api\ReportController::class, 'currentStock']);
    Route::get('/apps/reports/fast-slow-moving', [\App\Http\Controllers\Api\ReportController::class, 'fastSlowMoving']);
    Route::get('/apps/reports/stock-aging', [\App\Http\Controllers\Api\ReportController::class, 'stockAging']);
});
// Public verification
Route::get('/verify-document/{uuid}', [\App\Http\Controllers\Api\DocumentVerificationController::class, 'verify']);

Route::get('/init-mutasi', function () {
    $dev   = \Spatie\Permission\Models\Role::where('name', 'Developer')->first();
    $owner = \Spatie\Permission\Models\Role::where('name', 'Owner')->first();
    
    // Register Modul Mutasi Stok di bawah Operasional
    $operasional = \App\Models\Module::firstOrCreate(
        ['name' => 'Operasional', 'slug' => 'operasional'],
        ['sequence' => 3]
    );

    $mutasiModule = \App\Models\Module::where('slug', 'stock-transfers')->first();
    if ($mutasiModule) {
        $mutasiModule->update(['name' => 'Mutasi Stok', 'parent_id' => $operasional->id]);
    } else {
        $mutasiModule = \App\Models\Module::create(['name' => 'Mutasi Stok', 'slug' => 'stock-transfers', 'parent_id' => $operasional->id, 'sequence' => 3]);
    }

    $mutasiPerms = ['Mutasi Stok Create', 'Mutasi Stok Read', 'Mutasi Stok Approve'];
    foreach ($mutasiPerms as $perm) {
        $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $perm], ['module_id' => $mutasiModule->id]);
        $permission->update(['module_id' => $mutasiModule->id]);
        if ($dev) $dev->givePermissionTo($permission);
        if ($owner) $owner->givePermissionTo($permission);
    }

    return response()->json(['message' => 'Mutasi Stok module registered and permissions assigned successfully.']);
});
