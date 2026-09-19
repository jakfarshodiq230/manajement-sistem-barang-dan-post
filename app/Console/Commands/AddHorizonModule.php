<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AddHorizonModule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-horizon-module';
    protected $description = 'Add Horizon module to RBAC DB';

    public function handle()
    {
        \App\Models\Module::where('slug', 'horizon')->delete();

        $module = \App\Models\Module::updateOrCreate(
            ['name' => 'Manajemen Antrean'],
            [
                'slug' => 'manajemen-antrean',
                'icon' => 'ri-list-settings-line',
                'sequence' => 99,
                'category' => 'Settings',
                'status' => 'active',
                'description' => 'Queue Management System',
            ]
        );

        // Assign permission to Super Admin (assuming role ID 1 or name 'Super Admin')
        $role = \Spatie\Permission\Models\Role::findById(1);
        if ($role) {
            $permission = \Spatie\Permission\Models\Permission::updateOrCreate(['name' => 'read horizon']);
            $role->givePermissionTo($permission);
        }

        $this->info('Horizon module added successfully!');
    }
}
