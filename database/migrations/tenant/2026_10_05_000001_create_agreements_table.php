<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->string('agreement_number')->unique();
            $table->string('title');
            $table->string('category')->default('General'); // Customer, Vendor, Employee, Rental, Service, General
            $table->string('party_type')->default('customer'); // customer, supplier, employee, other
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('party_name');
            $table->string('party_phone')->nullable();
            $table->string('party_email')->nullable();
            $table->text('party_address')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status')->default('draft'); // draft, active, expired, terminated, completed
            $table->decimal('total_value', 15, 2)->default(0);
            $table->text('payment_terms')->nullable();
            $table->longText('content')->nullable();
            $table->string('witness_name')->nullable();
            $table->dateTime('signed_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index('status');
            $table->index('party_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agreements');
    }
};
