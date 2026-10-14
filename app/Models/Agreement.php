<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Agreement extends Model
{
    use HasFactory;

    protected $fillable = [
        'agreement_number',
        'title',
        'category',
        'party_type',
        'customer_id',
        'supplier_id',
        'party_name',
        'party_phone',
        'party_email',
        'party_address',
        'start_date',
        'end_date',
        'status',
        'total_value',
        'payment_terms',
        'content',
        'witness_name',
        'signed_at',
        'notes',
        'created_by',
        'document_checksum',
        'digital_signature',
        'signed_ip',
        'signed_user_agent',
        'attachment_path',
        'attachment_hash',
        'is_encrypted',
        'encryption_algorithm',
    ];

    /**
     * Automatic Encryption at Rest (AES-256-CBC) using SafeEncrypted Cast
     * (OWASP A02: Cryptographic Failures & SRS Section 14 & 18).
     */
    protected $casts = [
        'start_date'           => 'date',
        'end_date'             => 'date',
        'signed_at'            => 'datetime',
        'total_value'          => 'decimal:2',
        'is_encrypted'         => 'boolean',
        'content'              => \App\Casts\SafeEncrypted::class,
        'payment_terms'        => \App\Casts\SafeEncrypted::class,
        'notes'                => \App\Casts\SafeEncrypted::class,
        'party_address'        => \App\Casts\SafeEncrypted::class,
        'digital_signature'    => \App\Casts\SafeEncrypted::class,
        'witness_name'         => \App\Casts\SafeEncrypted::class,
    ];

    /**
     * Model Boot Event to guarantee cryptographic integrity hashes on save
     */
    protected static function booted()
    {
        static::saving(function (Agreement $agreement) {
            $agreement->is_encrypted = true;
            $agreement->encryption_algorithm = 'AES-256-CBC';
            $agreement->document_checksum = $agreement->calculateChecksum();
        });
    }

    /**
     * Calculate SHA-256 Cryptographic Checksum across core agreement contract attributes
     */
    public function calculateChecksum(): string
    {
        $payload = implode('|', [
            (string) $this->agreement_number,
            (string) $this->title,
            (string) $this->party_name,
            (string) $this->party_email,
            (string) $this->content,
            (string) $this->payment_terms,
            (string) $this->total_value,
            (string) ($this->signed_at ? $this->signed_at->toIso8601String() : ''),
        ]);

        return hash('sha256', $payload);
    }

    /**
     * Verify document integrity against tampering
     */
    public function verifyIntegrity(): bool
    {
        if (empty($this->document_checksum)) {
            return false;
        }

        return hash_equals($this->document_checksum, $this->calculateChecksum());
    }

    /**
     * Generate an HMAC-SHA256 Time-Limited Temporary Signed URL (SRS Section 14 & 18: Never expose permanent public URLs)
     */
    public function generateSecureTemporaryUrl(int $minutes = 30): string
    {
        return URL::temporarySignedRoute(
            'tenant.agreements.secure-view',
            now()->addMinutes($minutes),
            ['agreement' => $this->id]
        );
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator()
    {
        return $this->belongsTo(tenantuser::class, 'created_by');
    }

    public static function generateAgreementNumber(): string
    {
        $yearMonth = date('Ym');
        $last = self::where('agreement_number', 'LIKE', "AGR-{$yearMonth}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($last) {
            $parts = explode('-', $last->agreement_number);
            $seq = intval(end($parts)) + 1;
        } else {
            $seq = 1;
        }

        return 'AGR-' . $yearMonth . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'active' => ['bg' => 'bg-emerald-50 dark:bg-emerald-950/40', 'text' => 'text-emerald-600 dark:text-emerald-400', 'border' => 'border-emerald-200 dark:border-emerald-800'],
            'draft' => ['bg' => 'bg-amber-50 dark:bg-amber-950/40', 'text' => 'text-amber-600 dark:text-amber-400', 'border' => 'border-amber-200 dark:border-amber-800'],
            'expired' => ['bg' => 'bg-rose-50 dark:bg-rose-950/40', 'text' => 'text-rose-600 dark:text-rose-400', 'border' => 'border-rose-200 dark:border-rose-800'],
            'terminated' => ['bg' => 'bg-slate-100 dark:bg-slate-800', 'text' => 'text-slate-600 dark:text-slate-400', 'border' => 'border-slate-300 dark:border-slate-700'],
            'completed' => ['bg' => 'bg-blue-50 dark:bg-blue-950/40', 'text' => 'text-blue-600 dark:text-blue-400', 'border' => 'border-blue-200 dark:border-blue-800'],
            default => ['bg' => 'bg-slate-100 dark:bg-slate-800', 'text' => 'text-slate-600 dark:text-slate-400', 'border' => 'border-slate-300 dark:border-slate-700'],
        };
    }
}
