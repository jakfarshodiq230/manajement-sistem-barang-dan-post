<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReceiptSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReceiptSettingController extends Controller
{
    public function index()
    {
        $settings = ReceiptSetting::orderBy('id', 'asc')->paginate(10);
        return response()->json($settings);
    }

    private function checkDevRole()
    {
        $user = auth()->user();
        if (!$user) return false;

        if ($user->active_role_id) {
            $role = \Spatie\Permission\Models\Role::find($user->active_role_id);
            return $role && strtolower($role->name) === 'dev';
        }

        // fallback to standard spatie hasRole if active_role_id isn't reliable
        return $user->hasRole('dev') || $user->hasRole('Dev');
    }

    public function store(Request $request)
    {
        if (!$this->checkDevRole()) {
            return response()->json(['message' => 'Unauthorized. Only Dev can perform this action.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'width' => 'required|string|max:50',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'margin_top' => 'nullable|integer',
            'margin_bottom' => 'nullable|integer',
            'margin_left' => 'nullable|integer',
            'margin_right' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->is_default) {
            ReceiptSetting::where('id', '>', 0)->update(['is_default' => false]);
        }

        $setting = ReceiptSetting::create($request->all());

        return response()->json([
            'message' => 'Pengaturan kuitansi berhasil ditambahkan',
            'setting' => $setting
        ], 201);
    }

    public function update(Request $request, $id)
    {
        if (!$this->checkDevRole()) {
            return response()->json(['message' => 'Unauthorized. Only Dev can perform this action.'], 403);
        }

        $setting = ReceiptSetting::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'width' => 'required|string|max:50',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'margin_top' => 'nullable|integer',
            'margin_bottom' => 'nullable|integer',
            'margin_left' => 'nullable|integer',
            'margin_right' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->is_default) {
            ReceiptSetting::where('id', '!=', $id)->update(['is_default' => false]);
        }

        $setting->update($request->all());

        return response()->json([
            'message' => 'Pengaturan kuitansi berhasil diperbarui',
            'setting' => $setting
        ]);
    }

    public function destroy($id)
    {
        if (!$this->checkDevRole()) {
            return response()->json(['message' => 'Unauthorized. Only Dev can perform this action.'], 403);
        }

        $setting = ReceiptSetting::findOrFail($id);
        $setting->delete();

        return response()->json([
            'message' => 'Pengaturan kuitansi berhasil dihapus'
        ]);
    }
}
