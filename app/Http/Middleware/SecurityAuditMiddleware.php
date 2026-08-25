<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\SecurityAccessLog;
use App\Models\BlockedIp;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SecurityAuditMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $this->getClientIp($request);

        // 1. Check if IP is in Blacklist
        $blocked = BlockedIp::where('ip_address', $ip)
            ->where(function ($q) {
                $q->whereNull('blocked_until')
                  ->orWhere('blocked_until', '>', Carbon::now());
            })
            ->first();

        if ($blocked) {
            $blocked->increment('attempts_count');

            // Log the blocked attempt
            try {
                SecurityAccessLog::create([
                    'user_id'          => Auth::id() ?? null,
                    'ip_address'       => $ip,
                    'user_agent'       => $request->header('User-Agent'),
                    'device_type'      => $this->getDeviceType($request->header('User-Agent')),
                    'operating_system' => $this->getOperatingSystem($request->header('User-Agent')),
                    'browser'          => $this->getBrowser($request->header('User-Agent')),
                    'event_type'       => 'blocked_ip_access',
                    'endpoint'         => substr($request->fullUrl(), 0, 500),
                    'method'           => $request->method(),
                    'status_code'      => 403,
                    'payload'          => json_encode(['blocked_reason' => $blocked->reason]),
                    'risk_level'       => 'critical',
                    'threat_tags'      => ['banned_ip_attempt', 'auto_blocked'],
                    'is_blocked'       => true,
                ]);
            } catch (\Throwable $e) {
                // Ignore log errors
            }

            return response()->json([
                'message' => 'Akses dari alamat IP Anda (' . $ip . ') telah diblokir oleh sistem keamanan karena aktivitas mencurigakan. Hubungi Administrator.',
                'blocked' => true,
            ], 403);
        }

        // 2. Pre-execution threat detection (Payload & User Agent scanning)
        $threatAnalysis = $this->analyzeThreats($request);

        // Proceed with request
        $response = $next($request);

        // 3. Post-execution logging (Only for relevant API & Auth paths, ignore assets)
        $path = $request->path();
        if ($this->shouldLogRequest($path, $request)) {
            $this->logSecurityEvent($request, $response, $ip, $threatAnalysis);
        }

        return $response;
    }

    /**
     * Extract real client IP
     */
    protected function getClientIp(Request $request): string
    {
        return $request->header('CF-Connecting-IP')
            ?: $request->header('X-Real-IP')
            ?: explode(',', $request->header('X-Forwarded-For', ''))[0]
            ?: $request->ip()
            ?: '127.0.0.1';
    }

    /**
     * Analyze request for attack patterns
     */
    protected function analyzeThreats(Request $request): array
    {
        $tags = [];
        $risk = 'low';

        $ua = strtolower((string) $request->header('User-Agent'));
        $rawQuery = strtolower((string) $request->getQueryString());
        $rawBody = strtolower(json_encode($request->except(['password', 'password_confirmation', 'pin', 'pos_pin', 'token'])));

        // A. Scanner & Hacker Tool Signatures
        $hackerTools = ['sqlmap', 'nikto', 'nmap', 'masscan', 'wpscan', 'gobuster', 'dirbuster', 'acunetix', 'havij', 'burpcollaborator', 'hydra', 'metasploit'];
        foreach ($hackerTools as $tool) {
            if (str_contains($ua, $tool)) {
                $tags[] = 'hacker_tool_' . $tool;
                $risk = 'critical';
            }
        }

        // B. SQL Injection Signatures
        $sqliPatterns = ['union select', 'select * from', "' or 1=1", '" or 1=1', 'sleep(', 'benchmark(', 'information_schema', 'into outfile', 'load_file('];
        foreach ($sqliPatterns as $pattern) {
            if (str_contains($rawQuery, $pattern) || str_contains($rawBody, $pattern)) {
                $tags[] = 'sql_injection_attempt';
                $risk = 'critical';
            }
        }

        // C. XSS Signatures
        $xssPatterns = ['<script', 'javascript:', 'onload=', 'onerror=', 'document.cookie', '<iframe', 'alert(', 'eval('];
        foreach ($xssPatterns as $pattern) {
            if (str_contains($rawQuery, $pattern) || str_contains($rawBody, $pattern)) {
                $tags[] = 'xss_attempt';
                $risk = 'critical';
            }
        }

        // D. Path Traversal
        $traversalPatterns = ['../', '..\\', '/etc/passwd', 'win.ini', 'boot.ini'];
        foreach ($traversalPatterns as $pattern) {
            if (str_contains($rawQuery, $pattern) || str_contains($rawBody, $pattern)) {
                $tags[] = 'path_traversal_attempt';
                $risk = 'critical';
            }
        }

        return [
            'risk' => $risk,
            'tags' => array_unique($tags),
        ];
    }

    /**
     * Check if request should be recorded
     */
    protected function shouldLogRequest(string $path, Request $request): bool
    {
        // Ignore static assets
        if (preg_match('/\.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|map)$/i', $path)) {
            return false;
        }

        if (str_starts_with($path, '@vite') || str_starts_with($path, 'build/')) {
            return false;
        }

        // Log all API requests, Auth routes, or non-GET requests
        return str_starts_with($path, 'api/') || $request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('DELETE');
    }

    /**
     * Log the security event
     */
    protected function logSecurityEvent(Request $request, Response $response, string $ip, array $threatAnalysis): void
    {
        try {
            $statusCode = $response->getStatusCode();
            $path = $request->path();
            $method = $request->method();
            $risk = $threatAnalysis['risk'];
            $tags = $threatAnalysis['tags'];
            $eventType = 'http_request';

            // Detect Login Events
            if (str_contains($path, 'auth/login') || str_contains($path, 'login')) {
                if ($statusCode >= 200 && $statusCode < 300) {
                    $eventType = 'login_success';
                    $tags[] = 'auth_success';
                } else {
                    $eventType = 'login_failed';
                    $tags[] = 'auth_failed';

                    // Check for Brute Force (>= 5 failed attempts in last 5 mins from same IP)
                    $recentFails = SecurityAccessLog::where('ip_address', $ip)
                        ->where('event_type', 'login_failed')
                        ->where('created_at', '>=', Carbon::now()->subMinutes(5))
                        ->count();

                    if ($recentFails >= 4) {
                        $eventType = 'brute_force_attempt';
                        $risk = 'critical';
                        $tags[] = 'brute_force_attack';
                    } elseif ($recentFails >= 2) {
                        $risk = 'high';
                        $tags[] = 'multiple_login_failures';
                    } else {
                        $risk = 'medium';
                    }
                }
            } elseif ($statusCode === 401 || $statusCode === 403) {
                $eventType = 'unauthorized_access';
                if ($risk === 'low') $risk = 'medium';
                $tags[] = 'unauthorized';
            } elseif (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
                $eventType = 'data_mutation';
            }

            // Sanitize payload
            $sanitizedPayload = $this->sanitizePayload($request);

            SecurityAccessLog::create([
                'user_id'          => Auth::id() ?? null,
                'ip_address'       => $ip,
                'user_agent'       => substr((string) $request->header('User-Agent'), 0, 500),
                'device_type'      => $this->getDeviceType($request->header('User-Agent')),
                'operating_system' => $this->getOperatingSystem($request->header('User-Agent')),
                'browser'          => $this->getBrowser($request->header('User-Agent')),
                'event_type'       => $eventType,
                'endpoint'         => substr($request->fullUrl(), 0, 500),
                'method'           => $method,
                'status_code'      => $statusCode,
                'payload'          => !empty($sanitizedPayload) ? json_encode($sanitizedPayload) : null,
                'risk_level'       => $risk,
                'threat_tags'      => !empty($tags) ? array_values(array_unique($tags)) : null,
                'is_blocked'       => false,
            ]);

            // Dispatch emergency notification alert to Owner & Super Admin on Critical Threats
            if ($risk === 'critical') {
                \App\Services\NotificationService::notifyOwnerAndAdmins(
                    '🚨 Alert Ancaman Keamanan Sistem',
                    "Terdeteksi percobaan serangan ({$eventType}) dari IP {$ip} pada {$path}.",
                    '/dashboards/security',
                    'error',
                    'ri-shield-flash-line'
                );
            }
        } catch (\Throwable $e) {
            // Silently ignore logging failures to not disrupt core requests
        }
    }

    /**
     * Remove sensitive parameters from payload
     */
    protected function sanitizePayload(Request $request): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'pin', 'pos_pin', 'current_password', 'token', 'secret', 'api_key', 'auth_token'];
        $input = $request->except($sensitiveKeys);

        // Replace sensitive keys with masked string if present in nested arrays
        foreach ($sensitiveKeys as $key) {
            if ($request->has($key)) {
                $input[$key] = '********';
            }
        }

        return $input;
    }

    protected function getDeviceType(?string $ua): string
    {
        if (!$ua) return 'Desktop';
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $ua)) return 'Tablet';
        if (preg_match('/(mobile|ipod|iphone|blackberry|opera mini|iemobile|wpdesktop)/i', $ua)) return 'Mobile';
        if (preg_match('/(bot|crawler|spider|curl|wget|sqlmap|nikto)/i', $ua)) return 'Bot / Scanner';
        return 'Desktop';
    }

    protected function getOperatingSystem(?string $ua): string
    {
        if (!$ua) return 'Unknown OS';
        if (preg_match('/windows|win32/i', $ua)) return 'Windows';
        if (preg_match('/macintosh|mac os x/i', $ua)) return 'Mac OS';
        if (preg_match('/linux/i', $ua)) return 'Linux';
        if (preg_match('/android/i', $ua)) return 'Android';
        if (preg_match('/iphone|ipad|ipod/i', $ua)) return 'iOS';
        return 'Unknown OS';
    }

    protected function getBrowser(?string $ua): string
    {
        if (!$ua) return 'Unknown Browser';
        if (preg_match('/chrome|crios|crmo/i', $ua)) return 'Chrome';
        if (preg_match('/firefox|fxios/i', $ua)) return 'Firefox';
        if (preg_match('/safari/i', $ua)) return 'Safari';
        if (preg_match('/opera|opr\//i', $ua)) return 'Opera';
        if (preg_match('/edg/i', $ua)) return 'Edge';
        if (preg_match('/postman/i', $ua)) return 'Postman Client';
        if (preg_match('/curl/i', $ua)) return 'cURL CLI';
        if (preg_match('/sqlmap/i', $ua)) return 'SQLMap Attack Tool';
        if (preg_match('/python/i', $ua)) return 'Python Script';
        return 'Unknown Browser';
    }
}
