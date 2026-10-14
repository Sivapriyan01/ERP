<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPriceSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'wallet_amount',
        'grand_total',
        'rules_applied',
        'freebies_allocated',
        'line_items_snapshot',
        'created_at',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'discount_amount' => 'float',
        'tax_amount' => 'float',
        'shipping_amount' => 'float',
        'wallet_amount' => 'float',
        'grand_total' => 'float',
        'rules_applied' => 'array',
        'freebies_allocated' => 'array',
        'line_items_snapshot' => 'array',
        'created_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Bill::class, 'order_id');
    }
}
