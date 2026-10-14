<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftCard extends Model
{
    protected $fillable = [
        'code',
        'pin',
        'initial_amount',
        'current_balance',
        'currency',
        'status', // active, redeemed, expired, disabled
        'failed_attempts',
        'locked_until',
        'expires_at',
        'redeemed_by_customer_id',
        'redeemed_at',
        'created_by_user_id',
        'notes',
    ];

    protected $casts = [
        'initial_amount' => 'float',
        'current_balance' => 'float',
        'failed_attempts' => 'integer',
        'locked_until' => 'datetime',
        'expires_at' => 'datetime',
        'redeemed_at' => 'datetime',
    ];

    public function redeemedByCustomer()
    {
        return $this->belongsTo(Customer::class, 'redeemed_by_customer_id');
    }

    public function creator()
    {
        return $this->belongsTo(TenantUser::class, 'created_by_user_id');
    }

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public static function generateCode(): string
    {
        do {
            $code = 'GC-' . strtoupper(\Illuminate\Support\Str::random(4)) . '-' . strtoupper(\Illuminate\Support\Str::random(4)) . '-' . strtoupper(\Illuminate\Support\Str::random(4));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public static function generatePin(): string
    {
        return (string) random_int(1000, 9999);
    }
}
