<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for tenant promotion rules, freebie engine, and order price snapshots.
     */
    public function up(): void
    {
        // 1. Promotion Rules Table (Section 7 of SRS)
        if (!Schema::hasTable('promotion_rules')) {
            Schema::create('promotion_rules', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('description', 255)->nullable();
                $table->string('rule_type', 50)->default('cart_threshold'); // cart_threshold, product_discount, category_discount, quantity_bogo, freebie_gift, customer_tier
                $table->string('discount_type', 30)->default('percentage'); // percentage, fixed, freebie
                $table->decimal('discount_value', 12, 2)->default(0.00);
                $table->decimal('min_cart_value', 12, 2)->nullable();
                $table->unsignedInteger('min_quantity')->default(1);
                $table->unsignedBigInteger('freebie_product_id')->nullable();
                $table->string('freebie_product_name', 150)->nullable();
                $table->unsignedInteger('freebie_quantity')->default(1);
                $table->string('applies_to', 50)->default('all'); // all, specific_products, specific_categories, specific_tier
                $table->json('applicable_product_ids')->nullable();
                $table->json('applicable_category_ids')->nullable();
                $table->boolean('coupon_code_required')->default(false);
                $table->string('coupon_code', 50)->nullable()->index();
                $table->boolean('is_stackable')->default(false);
                $table->integer('priority')->default(10); // higher runs first
                $table->decimal('max_discount_amount', 12, 2)->nullable();
                $table->timestamp('start_date')->nullable();
                $table->timestamp('end_date')->nullable();
                $table->unsignedInteger('usage_limit_total')->nullable();
                $table->unsignedInteger('used_count')->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->string('banner_message', 255)->nullable();
                $table->timestamps();
            });
        }

        // 2. Promotion Redemptions Table
        if (!Schema::hasTable('promotion_redemptions')) {
            Schema::create('promotion_redemptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('promotion_rule_id')->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->decimal('discount_amount', 12, 2)->default(0.00);
                $table->unsignedBigInteger('freebie_product_id')->nullable();
                $table->timestamp('created_at')->useCurrent()->index();

                $table->foreign('promotion_rule_id')->references('id')->on('promotion_rules')->onDelete('cascade');
            });
        }

        // 3. Order Price Snapshots Table (Immutable financial snapshot, Section 8 & 9 of SRS)
        if (!Schema::hasTable('order_price_snapshots')) {
            Schema::create('order_price_snapshots', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->index(); // bill_id
                $table->decimal('subtotal', 14, 2);
                $table->decimal('discount_amount', 14, 2)->default(0.00);
                $table->decimal('tax_amount', 14, 2)->default(0.00);
                $table->decimal('shipping_amount', 14, 2)->default(0.00);
                $table->decimal('wallet_amount', 14, 2)->default(0.00);
                $table->decimal('grand_total', 14, 2);
                $table->json('rules_applied')->nullable();
                $table->json('freebies_allocated')->nullable();
                $table->json('line_items_snapshot')->nullable();
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_price_snapshots');
        Schema::dropIfExists('promotion_redemptions');
        Schema::dropIfExists('promotion_rules');
    }
};
