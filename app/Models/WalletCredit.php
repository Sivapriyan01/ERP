<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletCredit extends Model
{
    protected $fillable = [
        'wallet_id',
        'customer_id',
        'ledger_id',
        'source', // gift_card, cashback, reward, promo, refund
        'amount',
        'remaining_amount',
        'expires_at',
        'status', // available, partially_spent, consumed, expired, reversed
    ];

    protected $casts = [
        'amount' => 'float',
        'remaining_amount' => 'float',
        'expires_at' => 'datetime',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function ledger()
    {
        return $this->belongsTo(WalletLedger::class, 'ledger_id');
    }
}
