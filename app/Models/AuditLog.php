<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'customer_id',
        'user_type',
        'event',
        'severity',
        'ip_address',
        'user_agent',
        'url',
        'payload',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Record an audit or security event in an append-oriented manner (OWASP A09 & SRS Section 18)
     */
    public static function record(
        string $event,
        array $payload = [],
        string $severity = 'info',
        ?int $userId = null,
        ?int $customerId = null,
        ?string $userType = null
    ): self {
        $ip = Request::ip();
        $ua = Request::userAgent();
        $url = Request::fullUrl();

        return self::create([
            'user_id'     => $userId ?: (auth('tenant')->id() ?? auth('web')->id() ?? null),
            'customer_id' => $customerId,
            'user_type'   => $userType ?: (auth('tenant')->check() ? 'staff' : (auth('web')->check() ? 'superadmin' : ($customerId ? 'customer' : 'guest'))),
            'event'       => $event,
            'severity'    => $severity,
            'ip_address'  => $ip,
            'user_agent'  => $ua ? substr($ua, 0, 500) : null,
            'url'         => $url ? substr($url, 0, 500) : null,
            'payload'     => $payload,
            'created_at'  => now(),
        ]);
    }
}
