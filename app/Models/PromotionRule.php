<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionRule extends Model
{
    protected $fillable = [
        'name',
        'description',
        'rule_type', // cart_threshold, product_discount, category_discount, quantity_bogo, freebie_gift, customer_tier
        'discount_type', // percentage, fixed, freebie
        'discount_value',
        'min_cart_value',
        'min_quantity',
        'freebie_product_id',
        'freebie_product_name',
        'freebie_quantity',
        'applies_to',
        'applicable_product_ids',
        'applicable_category_ids',
        'coupon_code_required',
        'coupon_code',
        'is_stackable',
        'priority',
        'max_discount_amount',
        'start_date',
        'end_date',
        'usage_limit_total',
        'used_count',
        'is_active',
        'banner_message',
    ];

    protected $casts = [
        'discount_value' => 'float',
        'min_cart_value' => 'float',
        'min_quantity' => 'integer',
        'freebie_quantity' => 'integer',
        'applicable_product_ids' => 'array',
        'applicable_category_ids' => 'array',
        'coupon_code_required' => 'boolean',
        'is_stackable' => 'boolean',
        'priority' => 'integer',
        'max_discount_amount' => 'float',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'usage_limit_total' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
    ];

    public function freebieProduct()
    {
        return $this->belongsTo(Category::class, 'freebie_product_id');
    }

    public function redemptions()
    {
        return $this->hasMany(PromotionRedemption::class);
    }
}
