<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for tenant audit and security logs (OWASP A09 & SRS Section 17 & 18).
     */
    public function up(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('user_type', 50)->default('guest')->index(); // staff, customer, system, guest
                $table->string('event', 100)->index(); // otp_requested, login_failed, price_override, etc.
                $table->string('severity', 20)->default('info')->index(); // info, warning, critical
                $table->string('ip_address', 45)->nullable()->index();
                $table->text('user_agent')->nullable();
                $table->string('url', 500)->nullable();
                $table->json('payload')->nullable();
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
