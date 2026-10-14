<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('discount_type')->default('percent'); // 'percent' or 'fixed'
                $table->decimal('discount_value', 10, 2);
                $table->decimal('min_order_value', 10, 2)->default(0);
                $table->decimal('max_discount', 10, 2)->nullable();
                $table->string('free_gift_name')->nullable();
                $table->decimal('free_gift_min_value', 10, 2)->nullable();
                $table->integer('usage_limit')->nullable();
                $table->integer('used_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('bills') && !Schema::hasColumn('bills', 'coupon_code')) {
            Schema::table('bills', function (Blueprint $table) {
                $table->string('coupon_code')->nullable()->after('remarks');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
        if (Schema::hasTable('bills') && Schema::hasColumn('bills', 'coupon_code')) {
            Schema::table('bills', function (Blueprint $table) {
                $table->dropColumn('coupon_code');
            });
        }
    }
};
