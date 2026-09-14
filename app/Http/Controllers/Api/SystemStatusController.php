<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemStatusController extends Controller
{
    public function checkPrerequisites(Request $request)
    {
        $missing = [];

        // Helper to get slug from modules (RBAC permission database)
        $getSlug = function($moduleName, $default) {
            $module = DB::table('modules')->where('name', $moduleName)->first();
            return $module ? '/' . $module->slug : $default;
        };

        // 1. Check Branches
        if (!DB::table('branches')->exists()) {
            $missing[] = [
                'module' => 'Manajemen Cabang',
                'description' => 'Toko/Cabang belum diatur.',
                'link' => $getSlug('Data Cabang & Toko', '/apps/branches')
            ];
        }

        // 2. Check Categories
        if (!DB::table('categories')->exists()) {
            $missing[] = [
                'module' => 'Kategori Barang',
                'description' => 'Belum ada kategori yang dibuat.',
                'link' => $getSlug('Kategori Barang', '/kategori-barang')
            ];
        }

        // 3. Check Bank Accounts
        if (!DB::table('bank_accounts')->exists()) {
            $missing[] = [
                'module' => 'Rekening Bank',
                'description' => 'Rekening kasir/bank belum diatur.',
                'link' => $getSlug('Rekening Bank & Kas', '/bank-accounts')
            ];
        }

        // 4. Check Owners
        if (!DB::table('owners')->exists()) {
            $missing[] = [
                'module' => 'Profil Usaha',
                'description' => 'Profil perusahaan kosong.',
                'link' => $getSlug('Manajemen Owner', '/apps/owners')
            ];
        }

        // 5. Check COA
        if (!DB::table('accounts')->exists()) {
            $missing[] = [
                'module' => 'Bagan Akun (COA)',
                'description' => 'Template Akuntansi belum diimpor.',
                'link' => $getSlug('Bagan Akun (COA)', '/akuntansi/coa')
            ];
        }

        // 6. Check User Branch Assignment
        $user = $request->user();
        if ($user) {
            $hasRoleBranch = DB::table('model_has_roles')
                ->where('model_type', get_class($user))
                ->where('model_id', $user->id)
                ->whereNotNull('branch_id')
                ->exists();
                
            $hasUserBranch = !empty($user->branch_id);
            
            $hasBranch = $hasRoleBranch || $hasUserBranch;
                
            if (!$hasBranch) {
                $missing[] = [
                    'module' => 'Penugasan Karyawan',
                    'description' => 'Akun Anda belum ditugaskan ke cabang manapun.',
                    'link' => $getSlug('Pengguna & PIN Kasir', '/apps/pengaturan-pengguna')
                ];
            }
        }

        return response()->json([
            'is_ready' => count($missing) === 0,
            'missing_modules' => $missing
        ]);
    }
}
