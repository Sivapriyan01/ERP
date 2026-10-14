<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = [
        'customer_id',
        'currency',
        'balance',
        'pending_cashback',
        'is_active',
    ];

    protected $casts = [
        'balance' => 'float',
        'pending_cashback' => 'float',
        'is_active' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function ledger()
    {
        return $this->hasMany(WalletLedger::class)->orderBy('created_at', 'desc');
    }

    public function credits()
    {
        return $this->hasMany(WalletCredit::class);
    }

    public function activeCredits()
    {
        return $this->hasMany(WalletCredit::class)
            ->where('status', 'available')
            ->where('remaining_amount', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderBy('expires_at', 'asc') // FIFO: earliest expiring first
            ->orderBy('id', 'asc');
    }

    public function escrow()
    {
        return $this->hasMany(CashbackEscrow::class);
    }
}
