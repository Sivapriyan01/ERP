<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Wallet;
use App\Models\WalletLedger;
use App\Models\WalletCredit;
use App\Models\GiftCard;
use App\Models\CashbackEscrow;
use App\Models\RewardPoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class WalletService
{
    /**
     * Get or initialize customer wallet
     */
    public function getOrCreateWallet(Customer $customer): Wallet
    {
        return Wallet::firstOrCreate(
            ['customer_id' => $customer->id],
            [
                'currency' => 'INR',
                'balance' => 0.00,
                'pending_cashback' => 0.00,
                'is_active' => true,
            ]
        );
    }

    /**
     * Credit customer's wallet with an immutable ledger entry and FIFO credit chunk.
     * Inflows allowed: gift_card, cashback, reward_conversion, refund, promo, adjustment.
     */
    public function credit(
        Customer $customer,
        float $amount,
        string $source = 'promo',
        ?string $description = null,
        ?Carbon $expiresAt = null,
        ?string $refType = null,
        ?string $refId = null
    ): WalletLedger {
        if ($amount <= 0) {
            throw new Exception("Credit amount must be greater than zero.");
        }

        return DB::transaction(function () use ($customer, $amount, $source, $description, $expiresAt, $refType, $refId) {
            $wallet = Wallet::where('customer_id', $customer->id)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = $this->getOrCreateWallet($customer);
                $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
            }

            $newBalance = round($wallet->balance + $amount, 2);
            $txId = 'WTX-' . strtoupper(Str::random(10));

            // 1. Create Immutable Ledger Record
            $ledger = WalletLedger::create([
                'wallet_id' => $wallet->id,
                'customer_id' => $customer->id,
                'transaction_id' => $txId,
                'type' => in_array($source, ['gift_card', 'cashback', 'reward_conversion', 'refund', 'adjustment']) ? $source : 'adjustment',
                'direction' => 'credit',
                'amount' => $amount,
                'running_balance' => $newBalance,
                'reference_type' => $refType,
                'reference_id' => $refId,
                'description' => $description ?: "Credit of ₹" . number_format($amount, 2) . " via " . ucfirst(str_replace('_', ' ', $source)),
                'status' => 'completed',
                'created_at' => now(),
            ]);

            // 2. Create FIFO Wallet Credit Chunk
            WalletCredit::create([
                'wallet_id' => $wallet->id,
                'customer_id' => $customer->id,
                'ledger_id' => $ledger->id,
                'source' => $source,
                'amount' => $amount,
                'remaining_amount' => $amount,
                'expires_at' => $expiresAt,
                'status' => 'available',
            ]);

            // 3. Update Wallet Balance
            $wallet->balance = $newBalance;
            $wallet->save();

            // 4. Keep customer summary column synced
            $customer->wallet_balance = $newBalance;
            $customer->save();

            return $ledger;
        });
    }

    /**
     * Debit customer's wallet using strict FIFO consumption against unexpired credits.
     * Prevents double spend using row-level locking.
     */
    public function debitFifo(
        Customer $customer,
        float $amount,
        string $description = "Order payment",
        ?string $refType = 'order',
        ?string $refId = null
    ): WalletLedger {
        if ($amount <= 0) {
            throw new Exception("Debit amount must be greater than zero.");
        }

        return DB::transaction(function () use ($customer, $amount, $description, $refType, $refId) {
            $wallet = Wallet::where('customer_id', $customer->id)->lockForUpdate()->first();
            if (!$wallet || $wallet->balance < $amount) {
                $available = $wallet ? $wallet->balance : 0.00;
                throw new Exception("Insufficient wallet balance. Available: ₹" . number_format($available, 2));
            }

            // Fetch available unexpired credits in FIFO order: earliest expiry first, then oldest ID first
            $credits = WalletCredit::where('wallet_id', $wallet->id)
                ->where('status', 'available')
                ->where('remaining_amount', '>', 0)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->orderByRaw('CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END, expires_at ASC')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $totalAvailableCredits = $credits->sum('remaining_amount');
            if ($totalAvailableCredits < $amount) {
                throw new Exception("Available unexpired credits (₹" . number_format($totalAvailableCredits, 2) . ") are less than requested ₹" . number_format($amount, 2));
            }

            $remainingToDebit = $amount;
            foreach ($credits as $credit) {
                if ($remainingToDebit <= 0) break;

                if ($credit->remaining_amount <= $remainingToDebit) {
                    $deducted = $credit->remaining_amount;
                    $credit->remaining_amount = 0.00;
                    $credit->status = 'consumed';
                    $credit->save();
                    $remainingToDebit = round($remainingToDebit - $deducted, 2);
                } else {
                    $credit->remaining_amount = round($credit->remaining_amount - $remainingToDebit, 2);
                    $credit->status = 'available';
                    $credit->save();
                    $remainingToDebit = 0.00;
                }
            }

            $newBalance = round($wallet->balance - $amount, 2);
            $txId = 'WTX-' . strtoupper(Str::random(10));

            // Create Immutable Ledger Record
            $ledger = WalletLedger::create([
                'wallet_id' => $wallet->id,
                'customer_id' => $customer->id,
                'transaction_id' => $txId,
                'type' => 'spend',
                'direction' => 'debit',
                'amount' => $amount,
                'running_balance' => $newBalance,
                'reference_type' => $refType,
                'reference_id' => $refId,
                'description' => $description,
                'status' => 'completed',
                'created_at' => now(),
            ]);

            // Update Wallet Balance
            $wallet->balance = $newBalance;
            $wallet->save();

            // Sync Customer Table
            $customer->wallet_balance = $newBalance;
            $customer->save();

            return $ledger;
        });
    }

    /**
     * Redeem a Gift Card (Code based)
     */
    public function redeemGiftCard(Customer $customer, string $code, ?string $pin = null): array
    {
        $code = strtoupper(trim($code));

        return DB::transaction(function () use ($customer, $code) {
            $giftCard = GiftCard::where('code', $code)->lockForUpdate()->first();

            if (!$giftCard) {
                return ['success' => false, 'message' => 'Invalid gift card code.'];
            }

            if ($giftCard->status !== 'active' || $giftCard->current_balance <= 0) {
                return ['success' => false, 'message' => "This gift card is {$giftCard->status} or already redeemed."];
            }

            if ($giftCard->expires_at && $giftCard->expires_at->isPast()) {
                $giftCard->status = 'expired';
                $giftCard->save();
                return ['success' => false, 'message' => 'This gift card has expired.'];
            }

            // Reset failed attempts upon success
            $giftCard->failed_attempts = 0;
            $giftCard->locked_until = null;

            $redeemAmount = $giftCard->current_balance;

            // Credit the customer's wallet
            $ledger = $this->credit(
                $customer,
                $redeemAmount,
                'gift_card',
                "Redeemed Gift Card {$giftCard->code}",
                $giftCard->expires_at,
                'gift_card',
                $giftCard->code
            );

            // Mark Gift Card Redeemed
            $giftCard->current_balance = 0.00;
            $giftCard->status = 'redeemed';
            $giftCard->redeemed_by_customer_id = $customer->id;
            $giftCard->redeemed_at = now();
            $giftCard->save();

            return [
                'success' => true,
                'message' => "Gift Card redeemed successfully! ₹" . number_format($redeemAmount, 2) . " credited to your wallet.",
                'amount' => $redeemAmount,
                'new_balance' => $ledger->running_balance,
            ];
        });
    }

    /**
     * Put post-purchase cashback into escrow until return/refund window expires.
     */
    public function holdCashback(Customer $customer, int $orderId, float $amount, int $returnDays = 7): CashbackEscrow
    {
        $wallet = $this->getOrCreateWallet($customer);

        $escrow = CashbackEscrow::create([
            'wallet_id' => $wallet->id,
            'customer_id' => $customer->id,
            'order_id' => $orderId,
            'amount' => $amount,
            'status' => 'escrow',
            'release_eligible_at' => now()->addDays($returnDays),
        ]);

        $wallet->pending_cashback = round($wallet->pending_cashback + $amount, 2);
        $wallet->save();

        return $escrow;
    }

    /**
     * Release mature cashback from escrow into available wallet balance (worker job).
     */
    public function releaseCashbackEscrow(): int
    {
        $matures = CashbackEscrow::where('status', 'escrow')
            ->where('release_eligible_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($matures as $escrow) {
            DB::transaction(function () use ($escrow, &$count) {
                $customer = Customer::find($escrow->customer_id);
                if ($customer) {
                    $this->credit(
                        $customer,
                        $escrow->amount,
                        'cashback',
                        "Cashback released for Order #{$escrow->order_id}",
                        now()->addMonths(6), // Cashback valid for 6 months
                        'order',
                        (string)$escrow->order_id
                    );

                    $wallet = Wallet::find($escrow->wallet_id);
                    if ($wallet) {
                        $wallet->pending_cashback = max(0.00, round($wallet->pending_cashback - $escrow->amount, 2));
                        $wallet->save();
                    }

                    $escrow->status = 'released';
                    $escrow->released_at = now();
                    $escrow->save();
                    $count++;
                }
            });
        }
        return $count;
    }

    /**
     * Expire unused credits past their expiry date (worker job).
     */
    public function expireCredits(): int
    {
        $expiredCredits = WalletCredit::where('status', 'available')
            ->where('remaining_amount', '>', 0)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($expiredCredits as $credit) {
            DB::transaction(function () use ($credit, &$count) {
                $expiredAmount = $credit->remaining_amount;
                $credit->remaining_amount = 0.00;
                $credit->status = 'expired';
                $credit->save();

                $wallet = Wallet::find($credit->wallet_id);
                $customer = Customer::find($credit->customer_id);

                if ($wallet && $customer) {
                    $newBalance = max(0.00, round($wallet->balance - $expiredAmount, 2));
                    $wallet->balance = $newBalance;
                    $wallet->save();

                    $customer->wallet_balance = $newBalance;
                    $customer->save();

                    // Create ledger audit
                    WalletLedger::create([
                        'wallet_id' => $wallet->id,
                        'customer_id' => $customer->id,
                        'transaction_id' => 'WEXP-' . strtoupper(Str::random(10)),
                        'type' => 'expiry',
                        'direction' => 'debit',
                        'amount' => $expiredAmount,
                        'running_balance' => $newBalance,
                        'reference_type' => 'wallet_credit',
                        'reference_id' => (string)$credit->id,
                        'description' => "Expired unused credit of ₹" . number_format($expiredAmount, 2),
                        'status' => 'completed',
                        'created_at' => now(),
                    ]);

                    $count++;
                }
            });
        }
        return $count;
    }

    /**
     * Add reward points to customer
     */
    public function addRewardPoints(Customer $customer, float $points, string $type = 'purchase_reward', ?string $desc = null, ?string $refId = null): RewardPoint
    {
        return DB::transaction(function () use ($customer, $points, $type, $desc, $refId) {
            $ledger = RewardPoint::create([
                'customer_id' => $customer->id,
                'points' => $points,
                'direction' => 'credit',
                'type' => $type,
                'reference_id' => $refId,
                'description' => $desc ?: "Earned {$points} reward points",
                'created_at' => now(),
            ]);

            $customer->points = round(($customer->points ?? 0) + $points, 2);
            $customer->save();

            return $ledger;
        });
    }

    /**
     * Convert customer reward points to wallet credit
     * Default rule: 10 points = ₹1.00 (e.g. 500 points = ₹50.00)
     */
    public function convertRewardsToWallet(Customer $customer, float $points, float $rate = 0.1): array
    {
        if ($points <= 0) {
            return ['success' => false, 'message' => 'Points to convert must be greater than zero.'];
        }

        $currentPoints = $customer->points ?? 0;
        if ($currentPoints < $points) {
            return ['success' => false, 'message' => "Insufficient points. You have {$currentPoints} points."];
        }

        $walletCreditAmount = round($points * $rate, 2);
        if ($walletCreditAmount <= 0) {
            return ['success' => false, 'message' => 'Converted amount is too small. Minimum 10 points required.'];
        }

        return DB::transaction(function () use ($customer, $points, $walletCreditAmount) {
            // Deduct Points
            RewardPoint::create([
                'customer_id' => $customer->id,
                'points' => $points,
                'direction' => 'debit',
                'type' => 'converted_to_wallet',
                'reference_id' => null,
                'description' => "Converted {$points} points into ₹" . number_format($walletCreditAmount, 2) . " wallet credit",
                'created_at' => now(),
            ]);

            $customer->points = max(0, round(($customer->points ?? 0) - $points, 2));
            $customer->save();

            // Credit Wallet with 90-day expiry
            $ledger = $this->credit(
                $customer,
                $walletCreditAmount,
                'reward_conversion',
                "Converted {$points} reward points",
                now()->addDays(90),
                'rewards',
                'conversion'
            );

            return [
                'success' => true,
                'message' => "Successfully converted {$points} points to ₹" . number_format($walletCreditAmount, 2) . " in your wallet!",
                'credited_amount' => $walletCreditAmount,
                'new_points_balance' => $customer->points,
                'new_wallet_balance' => $ledger->running_balance,
            ];
        });
    }
}
