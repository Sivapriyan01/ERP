<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashbackEscrow extends Model
{
    protected $table = 'cashback_escrow';

    protected $fillable = [
        'wallet_id',
        'customer_id',
        'order_id',
        'amount',
        'status', // escrow, released, cancelled
        'release_eligible_at',
        'released_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'release_eligible_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
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
