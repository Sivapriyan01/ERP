<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletLedger extends Model
{
    protected $table = 'wallet_ledger';
    public $timestamps = false; // We use immutable created_at

    protected $fillable = [
        'wallet_id',
        'customer_id',
        'transaction_id',
        'type', // gift_card, cashback, spend, refund, expiry, adjustment, reward_conversion
        'direction', // credit, debit
        'amount',
        'running_balance',
        'reference_type',
        'reference_id',
        'description',
        'status',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'running_balance' => 'float',
        'created_at' => 'datetime',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
