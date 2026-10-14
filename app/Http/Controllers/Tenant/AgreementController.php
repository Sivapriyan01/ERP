<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Setting;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class AgreementController extends Controller
{
    public function index(Request $request)
    {
        $stats = [
            'total'       => Agreement::count(),
            'active'      => Agreement::where('status', 'active')->count(),
            'draft'       => Agreement::where('status', 'draft')->count(),
            'expired'     => Agreement::where('status', 'expired')->count(),
            'total_value' => Agreement::sum('total_value'),
        ];

        $query = Agreement::with(['customer', 'supplier']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('agreement_number', 'ILIKE', "%{$search}%")
                  ->orWhere('title', 'ILIKE', "%{$search}%")
                  ->orWhere('party_name', 'ILIKE', "%{$search}%")
                  ->orWhere('party_phone', 'ILIKE', "%{$search}%");
            });
        }

        $agreements = $query->latest()->paginate(15)->withQueryString();

        $customers = Customer::select('id', 'name', 'phone', 'email', 'address')->orderBy('name')->get();
        $suppliers = Supplier::select('id', 'name', 'phone', 'email', 'address')->orderBy('name')->get();

        return view('tenant.agreements.index', compact('agreements', 'stats', 'customers', 'suppliers'));
    }

    /**
     * Store a new cryptographically secured & encrypted agreement (AES-256-CBC)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'             => 'required|string|max:255',
            'category'          => 'required|string|max:100',
            'party_type'        => 'required|string|in:customer,supplier,employee,other',
            'customer_id'       => 'nullable|integer',
            'supplier_id'       => 'nullable|integer',
            'party_name'        => 'required|string|max:255',
            'party_phone'       => 'nullable|string|max:50',
            'party_email'       => 'nullable|email|max:255',
            'party_address'     => 'nullable|string',
            'start_date'        => 'required|date',
            'end_date'          => 'nullable|date|after_or_equal:start_date',
            'status'            => 'required|string|in:draft,active,expired,terminated,completed',
            'total_value'       => 'nullable|numeric|min:0',
            'payment_terms'     => 'nullable|string',
            'content'           => 'nullable|string',
            'witness_name'      => 'nullable|string|max:255',
            'notes'             => 'nullable|string',
            'digital_signature' => 'nullable|string',
            'attachment'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // Max 10MB
        ]);

        $validated['agreement_number'] = Agreement::generateAgreementNumber();
        $validated['total_value']      = $validated['total_value'] ?? 0;
        $validated['created_by']       = auth('tenant')->id();
        $validated['is_encrypted']     = true;
        $validated['encryption_algorithm'] = 'AES-256-CBC';

        // Capture digital signature & sign metadata if signed upon creation
        if (!empty($validated['digital_signature']) || $validated['status'] === 'active') {
            $validated['signed_at']         = now();
            $validated['signed_ip']         = $request->ip();
            $validated['signed_user_agent'] = substr((string) $request->userAgent(), 0, 500);
        }

        // Handle physical signed document upload with military-grade encryption at rest
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            
            // OWASP A03 / A08: Validate file content & disallow executables
            $disallowedExtensions = ['php', 'phtml', 'exe', 'sh', 'bat', 'js', 'py', 'cmd', 'html', 'htm'];
            $ext = strtolower($file->getClientOriginalExtension());
            if (in_array($ext, $disallowedExtensions)) {
                return back()->withErrors(['attachment' => 'Disallowed file type detected for security.'])->withInput();
            }

            $rawContents = file_get_contents($file->getRealPath());
            $attachmentHash = hash('sha256', $rawContents);

            // Store in private tenant vault encrypted at rest (AES-256)
            $tenantId = function_exists('tenant') && tenant('id') ? tenant('id') : 'primary';
            $safeFileName = 'agr_' . Str::random(32) . '.enc';
            $path = "tenants/{$tenantId}/agreements/{$safeFileName}";

            // Encrypt raw document bytes before writing to storage
            $encryptedPayload = Crypt::encrypt($rawContents);
            Storage::disk('local')->put($path, $encryptedPayload);

            $validated['attachment_path'] = $path;
            $validated['attachment_hash'] = $attachmentHash;
        }

        unset($validated['attachment']);
        $agreement = Agreement::create($validated);

        // OWASP A09: Record immutable audit trail for agreement creation
        AuditLog::record('agreement_created', [
            'agreement_number' => $agreement->agreement_number,
            'title'            => $agreement->title,
            'party_name'       => $agreement->party_name,
            'checksum'         => $agreement->document_checksum,
            'has_signature'    => !empty($agreement->digital_signature),
            'has_attachment'   => !empty($agreement->attachment_path),
        ], 'info');

        return redirect()->route('tenant.agreements.index')
            ->with('success', "Agreement {$agreement->agreement_number} created and encrypted successfully.");
    }

    public function show(Agreement $agreement)
    {
        $agreement->load(['customer', 'supplier', 'creator']);

        AuditLog::record('agreement_viewed', [
            'agreement_number' => $agreement->agreement_number,
            'integrity_valid'  => $agreement->verifyIntegrity(),
        ], 'info');

        return response()->json($agreement);
    }

    public function update(Request $request, Agreement $agreement)
    {
        $validated = $request->validate([
            'title'             => 'required|string|max:255',
            'category'          => 'required|string|max:100',
            'party_type'        => 'required|string|in:customer,supplier,employee,other',
            'customer_id'       => 'nullable|integer',
            'supplier_id'       => 'nullable|integer',
            'party_name'        => 'required|string|max:255',
            'party_phone'       => 'nullable|string|max:50',
            'party_email'       => 'nullable|email|max:255',
            'party_address'     => 'nullable|string',
            'start_date'        => 'required|date',
            'end_date'          => 'nullable|date|after_or_equal:start_date',
            'status'            => 'required|string|in:draft,active,expired,terminated,completed',
            'total_value'       => 'nullable|numeric|min:0',
            'payment_terms'     => 'nullable|string',
            'content'           => 'nullable|string',
            'witness_name'      => 'nullable|string|max:255',
            'notes'             => 'nullable|string',
            'digital_signature' => 'nullable|string',
            'attachment'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $validated['total_value'] = $validated['total_value'] ?? 0;
        if ($validated['status'] === 'active' && !$agreement->signed_at) {
            $validated['signed_at']         = now();
            $validated['signed_ip']         = $request->ip();
            $validated['signed_user_agent'] = substr((string) $request->userAgent(), 0, 500);
        }

        // Handle attachment replacement with encryption at rest
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            
            $disallowedExtensions = ['php', 'phtml', 'exe', 'sh', 'bat', 'js', 'py', 'cmd', 'html', 'htm'];
            $ext = strtolower($file->getClientOriginalExtension());
            if (in_array($ext, $disallowedExtensions)) {
                return back()->withErrors(['attachment' => 'Disallowed file type detected for security.'])->withInput();
            }

            $rawContents = file_get_contents($file->getRealPath());
            $attachmentHash = hash('sha256', $rawContents);

            // Clean up previous attachment if exists
            if (!empty($agreement->attachment_path) && Storage::disk('local')->exists($agreement->attachment_path)) {
                Storage::disk('local')->delete($agreement->attachment_path);
            }

            $tenantId = function_exists('tenant') && tenant('id') ? tenant('id') : 'primary';
            $safeFileName = 'agr_' . Str::random(32) . '.enc';
            $path = "tenants/{$tenantId}/agreements/{$safeFileName}";

            $encryptedPayload = Crypt::encrypt($rawContents);
            Storage::disk('local')->put($path, $encryptedPayload);

            $validated['attachment_path'] = $path;
            $validated['attachment_hash'] = $attachmentHash;
        }

        unset($validated['attachment']);
        $agreement->update($validated);

        AuditLog::record('agreement_updated', [
            'agreement_number' => $agreement->agreement_number,
            'checksum'         => $agreement->document_checksum,
        ], 'info');

        return redirect()->route('tenant.agreements.index')
            ->with('success', "Agreement {$agreement->agreement_number} updated and encrypted successfully.");
    }

    /**
     * Submit a digital touch/mouse signature to legally sign the agreement (SRS Section 14)
     */
    public function sign(Request $request, Agreement $agreement)
    {
        $request->validate([
            'signature' => 'required|string', // Base64 data URL from signature canvas
        ]);

        $agreement->digital_signature   = $request->input('signature');
        $agreement->signed_at           = now();
        $agreement->signed_ip           = $request->ip();
        $agreement->signed_user_agent   = substr((string) $request->userAgent(), 0, 500);
        $agreement->status              = 'active';
        $agreement->save();

        AuditLog::record('agreement_digitally_signed', [
            'agreement_number' => $agreement->agreement_number,
            'signed_ip'        => $agreement->signed_ip,
            'checksum'         => $agreement->document_checksum,
        ], 'info');

        return response()->json([
            'success'   => true,
            'message'   => "Agreement {$agreement->agreement_number} has been digitally signed and cryptographically sealed.",
            'checksum'  => $agreement->document_checksum,
            'signed_at' => $agreement->signed_at->toIso8601String(),
        ]);
    }

    /**
     * Generate a Time-Limited HMAC-SHA256 Signed Temporary URL (SRS Section 14 & 18: Never expose permanent public URLs)
     */
    public function generateShareLink(Request $request, Agreement $agreement)
    {
        $minutes = (int) $request->input('duration', 30);
        $minutes = max(5, min(1440, $minutes)); // 5 mins to 24 hours max

        $secureUrl = $agreement->generateSecureTemporaryUrl($minutes);

        AuditLog::record('agreement_share_link_created', [
            'agreement_number'   => $agreement->agreement_number,
            'expires_in_minutes' => $minutes,
        ], 'info');

        return response()->json([
            'success'            => true,
            'secure_url'         => $secureUrl,
            'expires_in_minutes' => $minutes,
            'checksum'           => $agreement->document_checksum,
            'message'            => "Temporary encrypted link generated. Link expires automatically in {$minutes} minutes.",
        ]);
    }

    /**
     * Secure Temporary View with Cryptographic Checksum Validation (Requires Valid HMAC Signature)
     */
    public function secureView(Request $request, Agreement $agreement)
    {
        // Enforce cryptographic HMAC URL signature validation (OWASP A01 & SRS Section 14 & 18)
        if (!$request->hasValidSignature()) {
            AuditLog::record('agreement_invalid_link_accessed', [
                'agreement_id' => $agreement->id,
                'ip'           => $request->ip(),
            ], 'warning');

            abort(403, 'This agreement link is invalid or has expired for security.');
        }

        $agreement->load(['customer', 'supplier']);
        $settings = Setting::pluck('value', 'key')->all();
        $isIntegrityValid = $agreement->verifyIntegrity();

        AuditLog::record('agreement_secure_link_viewed', [
            'agreement_number' => $agreement->agreement_number,
            'integrity_valid'  => $isIntegrityValid,
            'ip'               => $request->ip(),
        ], 'info');

        return view('tenant.agreements.print', [
            'agreement'        => $agreement,
            'settings'         => $settings,
            'isSecureSigned'   => true,
            'isIntegrityValid' => $isIntegrityValid,
        ]);
    }

    /**
     * Securely stream private attached agreement documents with on-the-fly decryption & integrity verification
     */
    public function downloadAttachment(Request $request, Agreement $agreement)
    {
        if (empty($agreement->attachment_path) || !Storage::disk('local')->exists($agreement->attachment_path)) {
            abort(404, 'Attachment not found.');
        }

        $storedData = Storage::disk('local')->get($agreement->attachment_path);
        
        // Decrypt ciphertext from storage vault
        try {
            $rawDecrypted = Crypt::decrypt($storedData);
        } catch (\Throwable $e) {
            $rawDecrypted = $storedData; // Legacy unencrypted fallback
        }

        // Cryptographic integrity check against tamper (SHA-256)
        if (!empty($agreement->attachment_hash)) {
            $actualHash = hash('sha256', $rawDecrypted);
            if (!hash_equals($agreement->attachment_hash, $actualHash)) {
                AuditLog::record('agreement_attachment_tamper_detected', [
                    'agreement_number' => $agreement->agreement_number,
                    'expected_hash'    => $agreement->attachment_hash,
                    'actual_hash'      => $actualHash,
                ], 'critical');

                abort(403, 'Document integrity violation detected: Attachment checksum does not match cryptographic seal.');
            }
        }

        AuditLog::record('agreement_attachment_downloaded', [
            'agreement_number' => $agreement->agreement_number,
            'attachment_hash'  => $agreement->attachment_hash,
        ], 'info');

        return response($rawDecrypted, 200, [
            'Content-Type'              => 'application/pdf',
            'Content-Disposition'       => 'inline; filename="' . $agreement->agreement_number . '_signed_document.pdf"',
            'X-Content-Type-Options'    => 'nosniff',
            'Cache-Control'             => 'private, no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Retrieve security audit trail and encryption proof for this agreement
     */
    public function auditTrail(Agreement $agreement)
    {
        $logs = AuditLog::where('metadata->agreement_number', $agreement->agreement_number)
            ->orWhere('metadata->agreement_id', $agreement->id)
            ->latest()
            ->take(20)
            ->get();

        return response()->json([
            'agreement_number' => $agreement->agreement_number,
            'checksum'         => $agreement->document_checksum,
            'is_encrypted'     => (bool) $agreement->is_encrypted,
            'algorithm'        => $agreement->encryption_algorithm ?? 'AES-256-CBC',
            'integrity_valid'  => $agreement->verifyIntegrity(),
            'signed_at'        => $agreement->signed_at ? $agreement->signed_at->toIso8601String() : null,
            'signed_ip'        => $agreement->signed_ip,
            'has_signature'    => !empty($agreement->digital_signature),
            'has_attachment'   => !empty($agreement->attachment_path),
            'logs'             => $logs,
        ]);
    }

    public function updateStatus(Request $request, Agreement $agreement)
    {
        $request->validate([
            'status' => 'required|string|in:draft,active,expired,terminated,completed'
        ]);

        $update = ['status' => $request->status];
        if ($request->status === 'active' && !$agreement->signed_at) {
            $update['signed_at']         = now();
            $update['signed_ip']         = $request->ip();
            $update['signed_user_agent'] = substr((string) $request->userAgent(), 0, 500);
        }

        $agreement->update($update);

        AuditLog::record('agreement_status_changed', [
            'agreement_number' => $agreement->agreement_number,
            'new_status'       => $request->status,
        ], 'info');

        return response()->json([
            'success' => true,
            'message' => "Agreement status updated to {$request->status}."
        ]);
    }

    public function destroy(Agreement $agreement)
    {
        $num = $agreement->agreement_number;

        AuditLog::record('agreement_deleted', [
            'agreement_number' => $num,
        ], 'warning');

        $agreement->delete();

        return redirect()->route('tenant.agreements.index')->with('success', "Agreement {$num} deleted.");
    }

    public function print(Agreement $agreement)
    {
        $settings = Setting::pluck('value', 'key')->all();
        $agreement->load(['customer', 'supplier']);

        AuditLog::record('agreement_printed', [
            'agreement_number' => $agreement->agreement_number,
        ], 'info');

        return view('tenant.agreements.print', compact('agreement', 'settings'));
    }
}
