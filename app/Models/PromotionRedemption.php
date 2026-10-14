<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionRedemption extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'promotion_rule_id',
        'customer_id',
        'order_id',
        'discount_amount',
        'freebie_product_id',
        'created_at',
    ];

    protected $casts = [
        'discount_amount' => 'float',
        'created_at' => 'datetime',
    ];

    public function rule()
    {
        return $this->belongsTo(PromotionRule::class, 'promotion_rule_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order()
    {
        return $this->belongsTo(Bill::class, 'order_id');
    }
}
