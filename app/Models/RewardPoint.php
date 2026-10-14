<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardPoint extends Model
{
    protected $table = 'reward_points_ledger';
    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'points',
        'direction', // credit, debit
        'type', // purchase_reward, referral, signup_bonus, converted_to_wallet, expired
        'reference_id',
        'description',
        'expires_at',
        'created_at',
    ];

    protected $casts = [
        'points' => 'float',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
