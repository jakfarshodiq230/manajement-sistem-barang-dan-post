<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Module;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\DB;

class InitModulesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:init-modules';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize missing modules and permissions securely';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting module initialization...');

        // 1. Fix Permissions
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
        $dev = Role::where('name', 'Developer')->orWhere('name', 'dev')->first();
        if ($dev) {
            $permission = Permission::firstOrCreate(['name' => 'Kasir (POS) Create']);
            if (!$dev->hasPermissionTo('Kasir (POS) Create')) {
                $dev->givePermissionTo($permission);
            }
        }
        $this->info('Fixed basic permissions.');

        $owner = Role::where('name', 'Owner')->first();

        // 2. Init Dashboard
        $parentDash = Module::firstOrCreate(['name' => 'Dashboards', 'slug' => 'dashboards'], ['sequence' => 1]);
        $dashModules = [
            ['name' => 'Dashboard Analytics', 'slug' => 'dashboards/analytics'],
            ['name' => 'Dashboard Penjualan', 'slug' => 'dashboards/penjualan'],
            ['name' => 'Dashboard Barang', 'slug' => 'dashboards/barang'],
            ['name' => 'Dashboard Keuntungan', 'slug' => 'dashboards/keuntungan'],
            ['name' => 'Dashboard Audit', 'slug' => 'dashboards/audit'],
        ];

        foreach ($dashModules as $mod) {
            $child = Module::where('name', $mod['name'])->orWhere('slug', $mod['slug'])->first();
            if ($child) {
                $child->update(['name' => $mod['name'], 'slug' => $mod['slug'], 'parent_id' => $parentDash->id]);
            } else {
                $child = Module::create(['name' => $mod['name'], 'slug' => $mod['slug'], 'parent_id' => $parentDash->id, 'sequence' => 1]);
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
        $this->info('Dashboard modules initialized.');


        // 3. Init Rekap
        $parentAudit = Module::firstOrCreate(
            ['name' => 'Audit & Laporan', 'slug' => 'audit-laporan'],
            ['sequence' => 2]
        );
        $auditModules = [
            ['name' => 'Closing Harian', 'slug' => 'audit/closing-harian'],
            ['name' => 'Stock Opname',   'slug' => 'audit/stock-opname'],
            ['name' => 'Rekap Tahunan',  'slug' => 'audit/rekap'],
            ['name' => 'Analisis Stok',  'slug' => 'laporan/stok-aging'],
        ];

        foreach ($auditModules as $mod) {
            $child = Module::where('slug', $mod['slug'])->first();
            if ($child) {
                $child->update(['name' => $mod['name'], 'parent_id' => $parentAudit->id]);
            } else {
                $child = Module::create(['name' => $mod['name'], 'slug' => $mod['slug'], 'parent_id' => $parentAudit->id, 'sequence' => 1]);
            }
            $permName = $mod['name'] . ' Read';
            $permission = Permission::firstOrCreate(['name' => $permName], ['module_id' => $child->id]);
            $permission->update(['module_id' => $child->id]);
            if ($dev) $dev->givePermissionTo($permission);
            if ($owner) $owner->givePermissionTo($permission);
        }
        $this->info('Audit/Rekap modules initialized.');

        // 4. Init Piutang
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
        $this->info('Piutang modules initialized.');

        // 5. Init Mutasi
        $operasional = Module::firstOrCreate(
            ['name' => 'Operasional & Transaksi', 'slug' => 'transaksi-main'],
            ['sequence' => 3]
        );
        $mutasiModule = Module::where('slug', 'mutasi-stok')->first();
        if ($mutasiModule) {
            $mutasiModule->update(['name' => 'Mutasi Stok Antar Cabang', 'parent_id' => $operasional->id]);
        } else {
            $mutasiModule = Module::create(['name' => 'Mutasi Stok Antar Cabang', 'slug' => 'mutasi-stok', 'parent_id' => $operasional->id, 'sequence' => 3]);
        }
        $mutasiPerms = ['Mutasi Stok Create', 'Mutasi Stok Read', 'Mutasi Stok Approve'];
        foreach ($mutasiPerms as $perm) {
            $permission = Permission::firstOrCreate(['name' => $perm], ['module_id' => $mutasiModule->id]);
            $permission->update(['module_id' => $mutasiModule->id]);
            if ($dev) $dev->givePermissionTo($permission);
            if ($owner) $owner->givePermissionTo($permission);
        }
        $this->info('Mutasi Stok modules initialized.');

        $this->info('All modules and permissions successfully registered.');
    }
}
