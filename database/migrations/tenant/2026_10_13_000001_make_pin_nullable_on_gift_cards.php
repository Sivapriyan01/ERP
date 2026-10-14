<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gift_cards')) {
            try {
                DB::statement('ALTER TABLE gift_cards ALTER COLUMN pin DROP NOT NULL');
            } catch (\Throwable $e) {
                Schema::table('gift_cards', function (Blueprint $table) {
                    $table->string('pin', 60)->nullable()->change();
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('gift_cards')) {
            try {
                DB::statement('ALTER TABLE gift_cards ALTER COLUMN pin SET NOT NULL');
            } catch (\Throwable $e) {
                // Ignore rollback failure if null values exist
            }
        }
    }
};
