<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\GiftCard;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GiftCardController extends Controller
{
    /**
     * Display a listing of gift cards with filtering and statistics.
     */
    public function index(Request $request)
    {
        $query = GiftCard::with(['redeemedByCustomer', 'creator']);

        // Search by Code, Notes, or Customer
        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('code', 'like', "%{$s}%")
                  ->orWhere('notes', 'like', "%{$s}%")
                  ->orWhereHas('redeemedByCustomer', function ($cq) use ($s) {
                      $cq->where('name', 'like', "%{$s}%")
                         ->orWhere('phone', 'like', "%{$s}%");
                  });
            });
        }

        // Filter by Status
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'locked') {
                $query->where('locked_until', '>', now());
            } elseif ($status === 'expired') {
                $query->where('status', 'expired')
                      ->orWhere(function ($q) {
                          $q->whereNotNull('expires_at')
                            ->where('expires_at', '<=', now());
                      });
            } else {
                $query->where('status', $status);
            }
        }

        // Auto-seed starter gift cards if none exist
        if (GiftCard::count() === 0) {
            self::seedDefaultCards();
        }

        $giftCards = $query->latest()->paginate(15)->withQueryString();

        // Statistics
        $totalCards = GiftCard::count();
        $activeCards = GiftCard::where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count();
        $activeBalance = GiftCard::where('status', 'active')->sum('current_balance');
        $redeemedCount = GiftCard::where('status', 'redeemed')->count();
        $redeemedTotal = GiftCard::where('status', 'redeemed')->sum('initial_amount');
        $lockedCount = GiftCard::whereNotNull('locked_until')->where('locked_until', '>', now())->count();

        // Customers for optional assignment / dropdown
        $customers = Customer::orderBy('name')->take(100)->get(['id', 'name', 'phone']);

        return view('tenant.gift_cards.index', compact(
            'giftCards',
            'totalCards',
            'activeCards',
            'activeBalance',
            'redeemedCount',
            'redeemedTotal',
            'lockedCount',
            'customers'
        ));
    }

    /**
     * Store a newly created gift card.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'           => 'nullable|string|max:30|unique:gift_cards,code',
            'initial_amount' => 'required|numeric|min:1',
            'expires_at'     => 'nullable|date',
            'notes'          => 'nullable|string|max:500',
        ]);

        $code = !empty($validated['code']) 
            ? strtoupper(trim($validated['code'])) 
            : GiftCard::generateCode();

        $amount = (float) $validated['initial_amount'];

        GiftCard::create([
            'code'               => $code,
            'pin'                => null,
            'initial_amount'     => $amount,
            'current_balance'    => $amount,
            'currency'           => 'INR',
            'status'             => 'active',
            'expires_at'         => $validated['expires_at'] ?? null,
            'created_by_user_id' => auth()->id(),
            'notes'              => $validated['notes'] ?? null,
        ]);

        return redirect()->route('tenant.gift-cards.index')->with('success', "Gift Card '{$code}' issued successfully with balance ₹" . number_format($amount, 2));
    }

    /**
     * Bulk generate batches of promotional / retail gift cards.
     */
    public function bulkGenerate(Request $request)
    {
        $validated = $request->validate([
            'quantity'   => 'required|integer|min:1|max:100',
            'amount'     => 'required|numeric|min:1',
            'prefix'     => 'nullable|string|max:8',
            'expires_at' => 'nullable|date',
            'notes'      => 'nullable|string|max:255',
        ]);

        $quantity = (int) $validated['quantity'];
        $amount = (float) $validated['amount'];
        $prefix = !empty($validated['prefix']) ? strtoupper(trim($validated['prefix'])) : 'GC';
        $expiresAt = $validated['expires_at'] ?? null;
        $notes = $validated['notes'] ?? 'Bulk batch generated on ' . now()->toFormattedDateString();

        DB::transaction(function () use ($quantity, $amount, $prefix, $expiresAt, $notes) {
            for ($i = 0; $i < $quantity; $i++) {
                do {
                    $code = $prefix . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
                } while (GiftCard::where('code', $code)->exists());

                GiftCard::create([
                    'code'               => $code,
                    'pin'                => null,
                    'initial_amount'     => $amount,
                    'current_balance'    => $amount,
                    'currency'           => 'INR',
                    'status'             => 'active',
                    'expires_at'         => $expiresAt,
                    'created_by_user_id' => auth()->id(),
                    'notes'              => $notes,
                ]);
            }
        });

        return redirect()->route('tenant.gift-cards.index')->with('success', "Batch of {$quantity} gift cards generated successfully (₹" . number_format($amount, 2) . " each).");
    }

    /**
     * Toggle active / disabled status of a gift card.
     */
    public function toggle(GiftCard $giftCard)
    {
        if ($giftCard->status === 'redeemed') {
            return back()->with('error', 'Cannot toggle a fully redeemed gift card.');
        }

        $newStatus = ($giftCard->status === 'active') ? 'disabled' : 'active';
        $giftCard->update(['status' => $newStatus]);

        $label = $newStatus === 'active' ? 'enabled' : 'disabled';
        return back()->with('success', "Gift Card '{$giftCard->code}' has been {$label}.");
    }

    /**
     * Unlock a card that was temporarily locked due to 5 wrong PIN attempts.
     */
    public function unlock(GiftCard $giftCard)
    {
        $giftCard->update([
            'failed_attempts' => 0,
            'locked_until'    => null,
        ]);

        return back()->with('success', "Gift Card '{$giftCard->code}' has been unlocked. Failed attempts reset to 0.");
    }

    /**
     * Delete a gift card.
     */
    public function destroy(GiftCard $giftCard)
    {
        $code = $giftCard->code;
        $giftCard->delete();

        return back()->with('success', "Gift Card '{$code}' deleted successfully.");
    }

    /**
     * Check balance & status for POS cashier / quick verification.
     */
    public function checkBalance(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
        ]);

        $code = strtoupper(trim($validated['code']));

        $giftCard = GiftCard::where('code', $code)->first();

        if (!$giftCard) {
            return response()->json(['success' => false, 'message' => 'Gift card not found.'], 404);
        }

        if ($giftCard->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => "Gift card is {$giftCard->status}.",
            ], 422);
        }

        if ($giftCard->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'This gift card has expired.',
            ], 422);
        }

        return response()->json([
            'success'         => true,
            'code'            => $giftCard->code,
            'current_balance' => (float) $giftCard->current_balance,
            'initial_amount'  => (float) $giftCard->initial_amount,
            'currency'        => $giftCard->currency,
            'expires_at'      => $giftCard->expires_at ? $giftCard->expires_at->toFormattedDateString() : 'Never',
        ]);
    }

    /**
     * Instant POS Gift Card redemption: credits customer wallet or applies to POS order.
     */
    public function redeemPos(Request $request, WalletService $walletService)
    {
        $validated = $request->validate([
            'customer_phone' => 'required|string',
            'code'           => 'required|string',
        ]);

        $customer = Customer::firstOrCreate(
            ['phone' => $validated['customer_phone']],
            ['name' => $request->input('customer_name', 'Customer')]
        );

        $result = $walletService->redeemGiftCard($customer, $validated['code']);

        if ($result['success']) {
            $customer->refresh();
            return response()->json([
                'success'        => true,
                'message'        => $result['message'],
                'credited'       => $result['amount_credited'] ?? 0,
                'wallet_balance' => (float) ($customer->wallet?->balance ?? $customer->wallet_balance ?? 0),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to redeem gift card.',
        ], 422);
    }

    /**
     * Seed default starter gift cards.
     */
    public static function seedDefaultCards(): void
    {
        $cards = [
            [
                'code'            => 'GC-WELCOME-500',
                'pin'             => null,
                'initial_amount'  => 500,
                'current_balance' => 500,
                'currency'        => 'INR',
                'status'          => 'active',
                'expires_at'      => now()->addYear(),
                'notes'           => 'Welcome Gift Voucher - Active for all storefront & POS orders',
            ],
            [
                'code'            => 'GC-FESTIVE-1000',
                'pin'             => null,
                'initial_amount'  => 1000,
                'current_balance' => 1000,
                'currency'        => 'INR',
                'status'          => 'active',
                'expires_at'      => now()->addYear(),
                'notes'           => 'Festive Season Reward Card',
            ],
            [
                'code'            => 'GC-VIP-2500',
                'pin'             => null,
                'initial_amount'  => 2500,
                'current_balance' => 2500,
                'currency'        => 'INR',
                'status'          => 'active',
                'expires_at'      => now()->addYear(),
                'notes'           => 'Exclusive VIP Customer Voucher',
            ],
        ];

        foreach ($cards as $card) {
            GiftCard::firstOrCreate(['code' => $card['code']], $card);
        }
    }
}

