<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\AuditLog;
use Symfony\Component\HttpFoundation\Response;

class ApiSecurityRateLimiter
{
    protected RateLimiter $limiter;

    public function __construct()
    {
        $fileCache = Cache::store('file');
        $this->limiter = new RateLimiter($fileCache);
    }

    /**
     * Handle rate limiting for security-sensitive API endpoints (OWASP A04 & SRS Section 18)
     */
    public function handle(Request $request, Closure $next, string $action = 'default', int $maxAttempts = 15, int $decaySeconds = 60): Response
    {
        $ip = $request->ip() ?: '0.0.0.0';
        $key = "api_rate:{$action}:" . sha1($ip);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($key);

            // OWASP A09: Record Security Rate-Limit Violation to Audit Logs
            try {
                if (class_exists(AuditLog::class)) {
                    AuditLog::record(
                        'rate_limit_exceeded',
                        [
                            'action'      => $action,
                            'max_allowed' => $maxAttempts,
                            'retry_after' => $retryAfter,
                            'ip'          => $ip,
                        ],
                        'warning'
                    );
                }
            } catch (\Throwable $e) {
                // Silently bypass logging errors to prevent breaking response
            }

            return response()->json([
                'success' => false,
                'message' => "Too many requests for {$action}. Please wait {$retryAfter} seconds before retrying.",
                'retry_after' => $retryAfter,
            ], 429);
        }

        $this->limiter->hit($key, $decaySeconds);

        return $next($request);
    }
}
