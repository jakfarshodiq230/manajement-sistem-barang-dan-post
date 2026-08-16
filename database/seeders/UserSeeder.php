<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create a default branch because Spatie permissions is using 'teams' (branch_id)
        $branch = \App\Models\Branch::firstOrCreate(['id' => 1], [
            'name' => 'Kantor Pusat',
        ]);
        
        // Set the active team ID for spatie permissions
        setPermissionsTeamId($branch->id);

        $password = Hash::make('12345');

        // 1. Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => $password,
            ]
        );
        $superAdmin->assignRole('Super Admin');

        // Dev Admin (added for user)
        $devAdmin = User::firstOrCreate(
            ['email' => 'dev@example.com'],
            [
                'name' => 'Developer',
                'password' => $password,
            ]
        );
        $devAdmin->assignRole('Dev');

        // 2. Admin Pusat
        $adminPusat = User::firstOrCreate(
            ['email' => 'adminpusat@example.com'],
            [
                'name' => 'Admin Pusat',
                'password' => $password,
            ]
        );
        $adminPusat->assignRole('Admin Pusat');

        // 3. Admin Cabang
        $adminCabang = User::firstOrCreate(
            ['email' => 'admincabang@example.com'],
            [
                'name' => 'Admin Cabang',
                'password' => $password,
            ]
        );
        $adminCabang->assignRole('Admin Cabang');

        // 4. Kasir
        $kasir = User::firstOrCreate(
            ['email' => 'kasir@example.com'],
            [
                'name' => 'Kasir',
                'password' => $password,
            ]
        );
        $kasir->assignRole('Kasir');

        // 5. Auditor
        $auditor = User::firstOrCreate(
            ['email' => 'auditor@example.com'],
            [
                'name' => 'Auditor',
                'password' => $password,
            ]
        );
        $auditor->assignRole('Auditor');
    }
}
