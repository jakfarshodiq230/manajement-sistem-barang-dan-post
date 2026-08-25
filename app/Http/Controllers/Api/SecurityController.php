<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SecurityAccessLog;
use App\Models\BlockedIp;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SecurityController extends Controller
{
    /**
     * Get list of security access logs with filters and search
     */
    public function index(Request $request)
    {
        $query = SecurityAccessLog::with(['user:id,name,email']);

        // Search keyword
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                  ->orWhere('endpoint', 'like', "%{$search}%")
                  ->orWhere('browser', 'like', "%{$search}%")
                  ->orWhere('operating_system', 'like', "%{$search}%")
                  ->orWhere('event_type', 'like', "%{$search}%")
                  ->orWhere('payload', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by Risk Level
        if ($request->filled('risk_level') && $request->risk_level !== 'all') {
            $query->where('risk_level', $request->risk_level);
        }

        // Filter by Event Type
        if ($request->filled('event_type') && $request->event_type !== 'all') {
            $query->where('event_type', $request->event_type);
        }

        // Filter by Blocked Status
        if ($request->filled('is_blocked') && $request->is_blocked !== 'all') {
            $query->where('is_blocked', $request->is_blocked === 'true' || $request->is_blocked === '1');
        }

        // Filter by Date Range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay(),
            ]);
        }

        $perPage = (int) $request->input('per_page', 15);
        $logs = $query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($logs);
    }

    /**
     * Get Security & Threat KPI Summary
     */
    public function summary()
    {
        $now = Carbon::now();
        $twentyFourHoursAgo = Carbon::now()->subHours(24);

        $totalLogs24h = SecurityAccessLog::where('created_at', '>=', $twentyFourHoursAgo)->count();

        $threatsDetected24h = SecurityAccessLog::where('created_at', '>=', $twentyFourHoursAgo)
            ->whereIn('risk_level', ['high', 'critical'])
            ->count();

        $suspiciousIpsCount = SecurityAccessLog::where('created_at', '>=', $twentyFourHoursAgo)
            ->whereIn('risk_level', ['high', 'critical'])
            ->distinct('ip_address')
            ->count('ip_address');

        $totalBlockedIps = BlockedIp::count();

        // Recent Critical / High Threats
        $recentThreats = SecurityAccessLog::with(['user:id,name'])
            ->whereIn('risk_level', ['high', 'critical'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Top Attacking / Suspicious IPs
        $topAttackingIps = SecurityAccessLog::select('ip_address', DB::raw('COUNT(*) as total_events'), DB::raw('MAX(risk_level) as highest_risk'), DB::raw('MAX(created_at) as last_seen'))
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->whereIn('risk_level', ['high', 'critical'])
            ->groupBy('ip_address')
            ->orderBy('total_events', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'total_logs_24h'       => $totalLogs24h,
            'threats_detected_24h' => $threatsDetected24h,
            'suspicious_ips_count' => $suspiciousIpsCount,
            'total_blocked_ips'    => $totalBlockedIps,
            'recent_threats'       => $recentThreats,
            'top_attacking_ips'    => $topAttackingIps,
        ]);
    }

    /**
     * Get List of Blocked IPs
     */
    public function getBlockedIps()
    {
        $blockedIps = BlockedIp::with(['blocker:id,name'])->orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $blockedIps]);
    }

    /**
     * Block / Ban an IP Address
     */
    public function blockIp(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|string|max:45',
            'reason'     => 'nullable|string|max:255',
        ]);

        $ip = trim($request->ip_address);
        $reason = $request->input('reason', 'Diblokir oleh Administrator');

        $blocked = BlockedIp::updateOrCreate(
            ['ip_address' => $ip],
            [
                'reason'     => $reason,
                'blocked_by' => $request->user()?->id,
            ]
        );

        // Update existing logs for this IP to is_blocked = true
        SecurityAccessLog::where('ip_address', $ip)->update(['is_blocked' => true]);

        return response()->json([
            'message' => "Alamat IP {$ip} berhasil diblokir dari seluruh akses sistem.",
            'data'    => $blocked,
        ]);
    }

    /**
     * Unblock an IP Address
     */
    public function unblockIp(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|string',
        ]);

        $ip = trim($request->ip_address);
        BlockedIp::where('ip_address', $ip)->delete();

        return response()->json([
            'message' => "Blokir untuk alamat IP {$ip} berhasil dibuka.",
        ]);
    }

    /**
     * Clear Old Logs (> 30 days)
     */
    public function clearOldLogs()
    {
        $deleted = SecurityAccessLog::where('created_at', '<', Carbon::now()->subDays(30))->delete();

        return response()->json([
            'message' => "Berhasil membersihkan {$deleted} data log lama yang berusia lebih dari 30 hari.",
        ]);
    }
}
