<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'min_order_value',
        'max_discount',
        'free_gift_name',
        'free_gift_min_value',
        'usage_limit',
        'used_count',
        'is_active',
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'discount_value' => 'float',
        'min_order_value' => 'float',
        'max_discount' => 'float',
        'free_gift_min_value' => 'float',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Scope for currently active coupons
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')->orWhereRaw('used_count < usage_limit');
            });
    }

    /**
     * Check if coupon applies to a given subtotal
     */
    public function isValidForSubtotal(float $subtotal): bool
    {
        if (!$this->is_active) return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) return false;
        if ($subtotal < $this->min_order_value) return false;

        return true;
    }

    /**
     * Calculate discount amount for a given subtotal
     */
    public function calculateDiscount(float $subtotal): float
    {
        if (!$this->isValidForSubtotal($subtotal)) {
            return 0.0;
        }

        if ($this->discount_type === 'percent') {
            $discount = ($subtotal * $this->discount_value) / 100;
        } else {
            $discount = min($this->discount_value, $subtotal);
        }

        if ($this->max_discount !== null && $this->max_discount > 0) {
            $discount = min($discount, $this->max_discount);
        }

        return round($discount, 2);
    }
}
