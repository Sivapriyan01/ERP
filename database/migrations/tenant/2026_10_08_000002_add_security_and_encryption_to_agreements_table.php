<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to add cryptographic integrity, digital signatures,
     * and secure storage fields to agreements table (OWASP & SRS Section 14 & 18).
     */
    public function up(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            if (!Schema::hasColumn('agreements', 'document_checksum')) {
                $table->string('document_checksum', 64)->nullable()->index()->after('witness_name');
            }
            if (!Schema::hasColumn('agreements', 'digital_signature')) {
                $table->longText('digital_signature')->nullable()->after('document_checksum');
            }
            if (!Schema::hasColumn('agreements', 'signed_ip')) {
                $table->string('signed_ip', 45)->nullable()->after('digital_signature');
            }
            if (!Schema::hasColumn('agreements', 'signed_user_agent')) {
                $table->text('signed_user_agent')->nullable()->after('signed_ip');
            }
            if (!Schema::hasColumn('agreements', 'attachment_path')) {
                $table->string('attachment_path', 500)->nullable()->after('signed_user_agent');
            }
            if (!Schema::hasColumn('agreements', 'attachment_hash')) {
                $table->string('attachment_hash', 64)->nullable()->after('attachment_path');
            }
            if (!Schema::hasColumn('agreements', 'is_encrypted')) {
                $table->boolean('is_encrypted')->default(true)->after('attachment_hash');
            }
            if (!Schema::hasColumn('agreements', 'encryption_algorithm')) {
                $table->string('encryption_algorithm', 30)->default('AES-256-CBC')->after('is_encrypted');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropColumn([
                'document_checksum',
                'digital_signature',
                'signed_ip',
                'signed_user_agent',
                'attachment_path',
                'attachment_hash',
                'is_encrypted',
                'encryption_algorithm',
            ]);
        });
    }
};
