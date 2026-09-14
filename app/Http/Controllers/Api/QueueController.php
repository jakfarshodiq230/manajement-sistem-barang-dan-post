<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class QueueController extends Controller
{
    public function getStats()
    {
        $pending = Queue::size();
        $failed = DB::table('failed_jobs')->count();

        return response()->json([
            'pending' => $pending,
            'failed' => $failed
        ]);
    }

    public function getFailedJobs(Request $request)
    {
        $perPage = $request->input('itemsPerPage', 10);
        
        $jobs = DB::table('failed_jobs')->orderBy('id', 'desc')->paginate($perPage);
        
        return response()->json([
            'jobs' => $jobs->items(),
            'total' => $jobs->total()
        ]);
    }

    public function retryJob($id)
    {
        try {
            Artisan::call('queue:retry', ['id' => [$id]]);
            return response()->json(['message' => 'Tugas berhasil di-retry!']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal me-retry tugas.', 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteJob($id)
    {
        try {
            DB::table('failed_jobs')->where('id', $id)->delete();
            return response()->json(['message' => 'Tugas gagal berhasil dihapus!']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus tugas.', 'error' => $e->getMessage()], 500);
        }
    }
}
