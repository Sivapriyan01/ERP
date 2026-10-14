<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for tenant wallet, ledger, gift cards, cashback escrow & rewards.
     */
    public function up(): void
    {
        // 1. Wallets Table
        if (!Schema::hasTable('wallets')) {
            Schema::create('wallets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->index();
                $table->string('currency', 10)->default('INR');
                $table->decimal('balance', 14, 2)->default(0.00);
                $table->decimal('pending_cashback', 14, 2)->default(0.00);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            });
        }

        // 2. Wallet Ledger Table (Immutable Financial Audit Trail)
        if (!Schema::hasTable('wallet_ledger')) {
            Schema::create('wallet_ledger', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('wallet_id')->index();
                $table->unsignedBigInteger('customer_id')->index();
                $table->string('transaction_id', 40)->unique();
                $table->enum('type', ['gift_card', 'cashback', 'spend', 'refund', 'expiry', 'adjustment', 'reward_conversion'])->index();
                $table->enum('direction', ['credit', 'debit'])->index();
                $table->decimal('amount', 14, 2);
                $table->decimal('running_balance', 14, 2);
                $table->string('reference_type', 50)->nullable()->index();
                $table->string('reference_id', 100)->nullable()->index();
                $table->text('description')->nullable();
                $table->enum('status', ['completed', 'pending', 'reversed'])->default('completed');
                $table->timestamp('created_at')->useCurrent()->index();

                $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
                $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            });
        }

        // 3. Wallet Credits Table (for FIFO Credit Expiry Tracking)
        if (!Schema::hasTable('wallet_credits')) {
            Schema::create('wallet_credits', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('wallet_id')->index();
                $table->unsignedBigInteger('customer_id')->index();
                $table->unsignedBigInteger('ledger_id')->nullable()->index();
                $table->string('source', 50)->default('promo'); // gift_card, cashback, reward, promo, refund
                $table->decimal('amount', 14, 2);
                $table->decimal('remaining_amount', 14, 2);
                $table->timestamp('expires_at')->nullable()->index();
                $table->enum('status', ['available', 'partially_spent', 'consumed', 'expired', 'reversed'])->default('available')->index();
                $table->timestamps();

                $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
                $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            });
        }

        // 4. Gift Cards Table (Code + PIN, Wrong Attempt Lockout)
        if (!Schema::hasTable('gift_cards')) {
            Schema::create('gift_cards', function (Blueprint $table) {
                $table->id();
                $table->string('code', 30)->unique()->index();
                $table->string('pin', 60)->nullable(); // Optional PIN
                $table->decimal('initial_amount', 14, 2);
                $table->decimal('current_balance', 14, 2);
                $table->string('currency', 10)->default('INR');
                $table->enum('status', ['active', 'redeemed', 'expired', 'disabled'])->default('active')->index();
                $table->unsignedInteger('failed_attempts')->default(0);
                $table->timestamp('locked_until')->nullable();
                $table->timestamp('expires_at')->nullable()->index();
                $table->unsignedBigInteger('redeemed_by_customer_id')->nullable()->index();
                $table->timestamp('redeemed_at')->nullable();
                $table->unsignedBigInteger('created_by_user_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 5. Cashback Escrow Table (Held Until Return/Refund Window Closes)
        if (!Schema::hasTable('cashback_escrow')) {
            Schema::create('cashback_escrow', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('wallet_id')->index();
                $table->unsignedBigInteger('customer_id')->index();
                $table->unsignedBigInteger('order_id')->index(); // bill_id
                $table->decimal('amount', 14, 2);
                $table->enum('status', ['escrow', 'released', 'cancelled'])->default('escrow')->index();
                $table->timestamp('release_eligible_at')->index();
                $table->timestamp('released_at')->nullable();
                $table->timestamps();

                $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
                $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            });
        }

        // 6. Reward Points Ledger Table
        if (!Schema::hasTable('reward_points_ledger')) {
            Schema::create('reward_points_ledger', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->index();
                $table->decimal('points', 12, 2);
                $table->enum('direction', ['credit', 'debit'])->index();
                $table->string('type', 50)->index(); // purchase_reward, referral, signup_bonus, converted_to_wallet, expired
                $table->string('reference_id', 100)->nullable();
                $table->text('description')->nullable();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamp('created_at')->useCurrent()->index();

                $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_points_ledger');
        Schema::dropIfExists('cashback_escrow');
        Schema::dropIfExists('gift_cards');
        Schema::dropIfExists('wallet_credits');
        Schema::dropIfExists('wallet_ledger');
        Schema::dropIfExists('wallets');
    }
};
