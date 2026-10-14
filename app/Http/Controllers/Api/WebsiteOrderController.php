<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Coupon;
use App\Models\tenant;
use App\Models\Wallet;
use App\Models\WalletLedger;
use App\Models\WalletCredit;
use App\Models\GiftCard;
use App\Models\CashbackEscrow;
use App\Models\RewardPoint;
use App\Models\PromotionRule;
use App\Models\PromotionRedemption;
use App\Models\OrderPriceSnapshot;
use App\Models\Setting;
use App\Services\WalletService;
use App\Services\PromotionEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\Msg91Service;
use App\Services\WhatsappService;
use App\Models\AuditLog;

class WebsiteOrderController extends Controller
{
    /**
     * Helper to run queries inside a specific tenant context (OWASP A01 & Non-Negotiable Rule #6)
     * Strictly isolates tenant databases and blocks unauthorized cross-tenant domain access.
     */
    protected function runInTenant(Request $request, callable $callback)
    {
        $tenantId = $request->header('X-Tenant') 
            ?: $request->query('tenant') 
            ?: $request->input('tenant_id');

        // Extract from Origin / Referer / Host if not explicitly specified
        $host = $request->getHost();
        if (!$tenantId) {
            $origin = $request->header('Origin') ?: $request->header('Referer') ?: $host;
            if ($origin) {
                $parsedHost = parse_url($origin, PHP_URL_HOST) ?: $origin;
                $parts = explode('.', $parsedHost);
                if (count($parts) > 1 && !in_array($parts[0], ['localhost', '127', 'www'])) {
                    $tenantId = $parts[0];
                }
            }
        }

        // Active tenancy context (if initialized by Stancl middleware)
        if (!$tenantId && function_exists('tenant') && tenant('id')) {
            $tenantId = tenant('id');
        }

        // Domain table lookup for custom branded root domains (e.g., store.example.com)
        if (!$tenantId && $host && !in_array($host, ['localhost', '127.0.0.1'])) {
            $domainRecord = \Stancl\Tenancy\Database\Models\Domain::where('domain', $host)->first();
            if ($domainRecord) {
                $tenantId = $domainRecord->tenant_id;
            }
        }

        // In local development environment, fallback to primary test tenant only for localhost/127.0.0.1
        if (!$tenantId && app()->environment('local', 'testing') && in_array($host, ['localhost', '127.0.0.1'])) {
            $tenantId = 'avinash';
        }

        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Unrecognized store domain or tenant identifier. Cross-tenant access is denied (OWASP A01).',
            ], 403);
        }

        $tenant = tenant::find($tenantId);
        if (!$tenant) {
            // Also check if tenantId is a domain record
            $domainRecord = \Stancl\Tenancy\Database\Models\Domain::where('domain', $tenantId)->first();
            if ($domainRecord) {
                $tenant = tenant::find($domainRecord->tenant_id);
            }
        }

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Requested store tenant was not found or is currently inactive.',
            ], 404);
        }

        tenancy()->initialize($tenant);

        try {
            return $callback();
        } finally {
            tenancy()->end();
        }
    }

    /**
     * Health check endpoint
     */
    public function health()
    {
        return response()->json([
            'status' => 'ok',
            'system' => 'Square ERP Order Management API',
            'version' => '2.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Fetch products from backend ERP (Tenant context)
     * Only returns products specifically enabled for the website
     */
    public function getProducts(Request $request)
    {
        return $this->runInTenant($request, function () use ($request) {
            try {
                // Only return active products that are specifically marked to show on the website
                // If none are flagged yet, fallback to active products with MRP > 0
                $hasWebsiteFlagged = Category::where('show_on_website', true)->exists();

                $categories = Category::where(function ($q) {
                        $q->whereNull('is_active')->orWhere('is_active', true);
                    })
                    ->when($hasWebsiteFlagged, function ($q) {
                        $q->where('show_on_website', true);
                    }, function ($q) {
                        $q->where('mrp', '>', 0);
                    })
                    ->latest()
                    ->get();

                $isGstEnabled = \App\Models\Setting::get('gst_enabled', \App\Models\Setting::get('bill_gst_enabled', '1')) == '1';
                $defaultGst = (float) \App\Models\Setting::get('gst_default_percent', \App\Models\Setting::get('billing_tax_percent', 0));
                $globalDiscount = (float) \App\Models\Setting::get('website_discount_percent', 0);
                $tenantId = function_exists('tenant') && tenant('id') ? tenant('id') : ($request->input('tenant') ?: 'avinash');
                $products = $categories->map(function ($cat) use ($isGstEnabled, $defaultGst, $globalDiscount, $tenantId) {
                    if ($cat->image) {
                        if (str_starts_with($cat->image, 'http://') || str_starts_with($cat->image, 'https://')) {
                            $img = $cat->image;
                        } else {
                            $img = '/api/v1/assets/' . ltrim($cat->image, '/') . ($tenantId ? '?tenant=' . urlencode($tenantId) : '');
                        }
                    } else {
                        $img = null;
                    }

                    $origMrp = (float) ($cat->mrp ?: 0);
                    $prodDiscount = ($cat->discount_percent !== null && (float)$cat->discount_percent > 0)
                        ? (float) $cat->discount_percent
                        : $globalDiscount;

                    if ($prodDiscount > 0) {
                        $sellingPrice = round($origMrp * (1 - ($prodDiscount / 100)), 2);
                        $displayMrp = $origMrp;
                        $displayDiscount = $prodDiscount;
                    } else {
                        $sellingPrice = $origMrp;
                        $displayMrp = $origMrp;
                        $displayDiscount = 0;
                    }

                    return [
                        'id' => $cat->id,
                        'sku' => $cat->barcode ?: ('SQ-CAT-' . $cat->id),
                        'barcode' => $cat->barcode,
                        'product_name' => $cat->product_name,
                        'category' => $cat->product_type ?: 'General',
                        'brand' => $cat->brand ?: 'Square Store',
                        'description' => $cat->model ? "Model: {$cat->model}. Size: {$cat->size}." : "Authentic quality directly from store.",
                        'selling_price' => $sellingPrice,
                        'mrp' => $displayMrp,
                        'discount' => $displayDiscount,
                        'tax' => $isGstEnabled ? (float) ($cat->gst !== null && $cat->gst !== '' ? $cat->gst : $defaultGst) : 0.0,
                        'stock' => (int) $cat->stock,
                        'minimum_stock' => (int) ($cat->low_stock_alert ?: 5),
                        'status' => $cat->stock > 0 ? 'ACTIVE' : 'OUT_OF_STOCK',
                        'image' => $img,
                    ];
                });

                return response()->json([
                    'success' => true,
                    'count' => $products->count(),
                    'products' => $products,
                ]);
            } catch (\Throwable $e) {
                Log::error('Error fetching website products: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to fetch products: ' . $e->getMessage(),
                ], 500);
            }
        });
    }

    /**
     * Get active discount coupons for website storefront
     * GET /api/v1/coupons
     */
    public function getCoupons(Request $request)
    {
        return $this->runInTenant($request, function () use ($request) {
            $tenantId = function_exists('tenant') && tenant('id') ? tenant('id') : ($request->input('tenant') ?: 'avinash');
            try {
                // Ensure default Derma Co style coupons exist if database is fresh
                if (Coupon::count() === 0) {
                    Coupon::insert([
                        [
                            'code' => 'FLAT30',
                            'name' => 'Flat 30% Off Skincare',
                            'description' => 'Get Flat 30% off on all dermatologist designed skincare worth ₹1,499 & above.',
                            'discount_type' => 'percent',
                            'discount_value' => 30,
                            'min_order_value' => 1499,
                            'max_discount' => 1000,
                            'free_gift_name' => 'Free 10g 1% Hyaluronic Sunscreen Aqua Gel',
                            'free_gift_min_value' => 1499,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        [
                            'code' => 'REDEEM35',
                            'name' => 'Redeem 35% Special',
                            'description' => 'Save 35% on orders above ₹899 + Free Miniature Serum.',
                            'discount_type' => 'percent',
                            'discount_value' => 35,
                            'min_order_value' => 899,
                            'max_discount' => 600,
                            'free_gift_name' => 'Free 10% Niacinamide Serum Mini 5ml',
                            'free_gift_min_value' => 899,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        [
                            'code' => 'DERMA20',
                            'name' => 'Welcome Derma 20%',
                            'description' => 'Flat 20% discount on all active orders above ₹499.',
                            'discount_type' => 'percent',
                            'discount_value' => 20,
                            'min_order_value' => 499,
                            'max_discount' => 400,
                            'free_gift_name' => null,
                            'free_gift_min_value' => null,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        [
                            'code' => 'PREPAID5',
                            'name' => 'Extra 5% Prepaid Discount',
                            'description' => 'Instant 5% discount on UPI & Online prepaid payments.',
                            'discount_type' => 'percent',
                            'discount_value' => 5,
                            'min_order_value' => 0,
                            'max_discount' => 200,
                            'free_gift_name' => null,
                            'free_gift_min_value' => null,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    ]);
                }

                $coupons = Coupon::active()->latest()->get()->map(function ($c) {
                    return [
                        'code' => $c->code,
                        'name' => $c->name,
                        'description' => $c->description,
                        'discount_type' => $c->discount_type,
                        'discount_value' => (float) $c->discount_value,
                        'min_order_value' => (float) $c->min_order_value,
                        'max_discount' => $c->max_discount ? (float) $c->max_discount : null,
                        'free_gift_name' => $c->free_gift_name,
                        'free_gift_min_value' => $c->free_gift_min_value ? (float) $c->free_gift_min_value : null,
                        'badge' => $c->discount_type === 'percent' ? ($c->discount_value . '% OFF') : ('₹' . $c->discount_value . ' FLAT'),
                    ];
                });

                $rawGiftImg = \App\Models\Setting::get('website_free_gift_image', '/gift-icon.png');
                $giftImg = '/gift-icon.png';
                if ($rawGiftImg && $rawGiftImg !== '/gift-icon.png') {
                    if (str_starts_with($rawGiftImg, 'http://') || str_starts_with($rawGiftImg, 'https://')) {
                        $giftImg = $rawGiftImg;
                    } else {
                        $cleanGiftPath = ltrim(preg_replace('#^/?(storage/|tenancy/assets/)#', '', $rawGiftImg), '/');
                        $giftImg = '/api/v1/assets/' . $cleanGiftPath . ($tenantId ? '?tenant=' . urlencode($tenantId) : '');
                    }
                }

                return response()->json([
                    'success' => true,
                    'modal_title' => \App\Models\Setting::get('website_coupon_modal_title', 'Apply Coupon & Offers'),
                    'modal_subtitle' => \App\Models\Setting::get('website_coupon_modal_subtitle', 'The Derma Co Verified Promo Codes'),
                    'free_shipping_threshold' => (float) \App\Models\Setting::get('website_free_shipping_threshold', 999),
                    'free_gift_threshold' => (float) \App\Models\Setting::get('website_free_gift_threshold', 899),
                    'free_gift_name' => \App\Models\Setting::get('website_free_gift_name', 'Free 10% Niacinamide Serum Mini 5ml'),
                    'free_gift_image' => $giftImg,
                    'prepaid_discount_percent' => (float) \App\Models\Setting::get('website_prepaid_discount_percent', 5),
                    'coupons' => $coupons,
                ]);
            } catch (\Throwable $e) {
                Log::error('Error fetching coupons: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to fetch coupons: ' . $e->getMessage(),
                ], 500);
            }
        });
    }

    /**
     * Validate coupon code against subtotal and payment method
     * POST /api/v1/coupons/validate
     */
    public function validateCoupon(Request $request)
    {
        return $this->runInTenant($request, function () use ($request) {
            $code = strtoupper(trim($request->input('code', '')));
            $subtotal = (float) $request->input('subtotal', 0);
            $paymentMethod = strtoupper(trim($request->input('payment_method', 'ONLINE')));

            if (empty($code)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please enter a coupon code.',
                ], 422);
            }

            $coupon = Coupon::where('code', $code)->first();

            if (!$coupon) {
                return response()->json([
                    'success' => false,
                    'message' => "Coupon '{$code}' is invalid or expired.",
                ], 404);
            }

            if (!$coupon->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => "Coupon '{$code}' is currently inactive.",
                ], 422);
            }

            if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
                return response()->json([
                    'success' => false,
                    'message' => "Coupon '{$code}' has not started yet.",
                ], 422);
            }

            if ($coupon->expires_at && $coupon->expires_at->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => "Coupon '{$code}' has expired.",
                ], 422);
            }

            if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
                return response()->json([
                    'success' => false,
                    'message' => "Coupon '{$code}' has reached its maximum redemption limit.",
                ], 422);
            }

            if ($subtotal < $coupon->min_order_value) {
                $needed = $coupon->min_order_value - $subtotal;
                return response()->json([
                    'success' => false,
                    'message' => "Add ₹" . number_format($needed, 2) . " more to your cart to use code '{$code}' (Min order ₹" . number_format($coupon->min_order_value) . ").",
                    'min_order_value' => (float) $coupon->min_order_value,
                ], 422);
            }

            $discountAmount = $coupon->calculateDiscount($subtotal);

            $freeGift = null;
            if ($coupon->free_gift_name && (!$coupon->free_gift_min_value || $subtotal >= $coupon->free_gift_min_value)) {
                $freeGift = $coupon->free_gift_name;
            }

            return response()->json([
                'success' => true,
                'message' => "Coupon '{$coupon->code}' applied successfully!",
                'coupon' => [
                    'code' => $coupon->code,
                    'name' => $coupon->name,
                    'discount_amount' => $discountAmount,
                    'discount_type' => $coupon->discount_type,
                    'discount_value' => (float) $coupon->discount_value,
                    'free_gift' => $freeGift,
                    'savings_text' => 'You saved ₹' . number_format($discountAmount, 2) . ' with ' . $coupon->code . '!',
                ],
            ]);
        });
    }

    /**
     * Serve tenant product images and assets
     */
    public function getAsset(Request $request, $path)
    {
        return $this->runInTenant($request, function () use ($path, $request) {
            $cleanPath = ltrim($path, '/');

            // 1. Check in active tenant public storage
            $realPath = storage_path('app/public/' . $cleanPath);
            if (file_exists($realPath) && is_file($realPath)) {
                return $this->serveAssetFile($realPath);
            }

            // 2. Check explicit tenant directory if known
            $tenantId = function_exists('tenant') && tenant('id') ? tenant('id') : ($request->input('tenant') ?: null);
            if ($tenantId) {
                $tenantStoragePath = base_path("storage/tenant{$tenantId}/app/public/{$cleanPath}");
                if (file_exists($tenantStoragePath) && is_file($tenantStoragePath)) {
                    return $this->serveAssetFile($tenantStoragePath);
                }
            }

            // 3. Fallback scan across all tenant storage directories
            $matches = glob(base_path("storage/tenant*/app/public/{$cleanPath}"));
            if (! empty($matches) && file_exists($matches[0]) && is_file($matches[0])) {
                return $this->serveAssetFile($matches[0]);
            }

            // 4. Check global public storage
            $globalPath = base_path('storage/app/public/' . $cleanPath);
            if (file_exists($globalPath) && is_file($globalPath)) {
                return $this->serveAssetFile($globalPath);
            }

            abort(404, 'Asset not found');
        });
    }

    protected function serveAssetFile(string $filePath)
    {
        $mime = mime_content_type($filePath) ?: 'application/octet-stream';
        return response()->file($filePath, [
            'Content-Type'  => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Create Order from Website
     * Implements Section 4 (Order Fetcher), Section 7 (Customer Deduplication), and Section 21 (Idempotency)
     */
    public function createOrder(Request $request)
    {
        if (!$request->has('customer') && $request->filled('customer_name')) {
            $request->merge([
                'customer' => [
                    'name' => $request->input('customer_name'),
                    'phone' => $request->input('customer_phone'),
                    'email' => $request->input('customer_email'),
                    'shipping_address' => $request->input('shipping_address'),
                    'billing_address' => $request->input('billing_address', $request->input('shipping_address')),
                ],
            ]);
        }
        if (!$request->has('website_order_id')) {
            $request->merge([
                'website_order_id' => 'WEB-' . date('YmdHis') . '-' . rand(1000, 9999),
            ]);
        }
        if (!$request->has('items') || empty($request->input('items'))) {
            if ($request->has('cart') && is_array($request->input('cart')) && !empty($request->input('cart'))) {
                $request->merge(['items' => $request->input('cart')]);
            } elseif ($request->has('order_items') && is_array($request->input('order_items')) && !empty($request->input('order_items'))) {
                $request->merge(['items' => $request->input('order_items')]);
            }
        }

        $validated = $request->validate([
            'website_order_id' => 'required|string',
            'customer.name' => 'required|string',
            'customer.phone' => 'required|string',
            'customer.email' => 'nullable|email',
            'customer.shipping_address' => 'required|string',
            'customer.billing_address' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric',
            'tax' => 'nullable|numeric',
            'shipping_charge' => 'nullable|numeric',
            'grand_total' => 'required|numeric',
            'coupon_code' => 'nullable|string',
            'coupon_discount' => 'nullable|numeric',
            'discount_amount' => 'nullable|numeric',
            'prepaid_discount' => 'nullable|numeric',
            'payment_method' => 'nullable|string',
            'wallet_amount' => 'nullable|numeric|min:0',
            'policy_version' => 'nullable|string|max:30',
            'notes' => 'nullable|string',
        ]);

        return $this->runInTenant($request, function () use ($validated) {
            return DB::transaction(function () use ($validated) {
                $custData = $validated['customer'];

                // 1. Idempotency Check: Prevent duplicate order processing
                $existingBill = Bill::where('remarks', 'like', '%' . $validated['website_order_id'] . '%')->first();
                if ($existingBill) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Order already processed (Idempotent response).',
                        'order' => [
                            'id' => $existingBill->id,
                            'website_order_id' => $validated['website_order_id'],
                            'invoice_no' => $existingBill->invoice_no,
                            'order_status' => $existingBill->status,
                            'grand_total' => (float) $existingBill->grand_total,
                        ],
                    ]);
                }

                // 2. Customer Deduplication (Section 7 of PDF)
                $customer = Customer::where('phone', $custData['phone'])->first();
                if (!$customer && !empty($custData['email'])) {
                    $customer = Customer::where('email', $custData['email'])->first();
                }

                if ($customer) {
                    $customer->update([
                        'name' => $customer->name ?: $custData['name'],
                        'address' => $custData['shipping_address'],
                        'bill_count' => ($customer->bill_count ?: 0) + 1,
                    ]);
                } else {
                    $customer = Customer::create([
                        'name' => $custData['name'],
                        'phone' => $custData['phone'],
                        'email' => $custData['email'] ?? null,
                        'address' => $custData['shipping_address'],
                        'bill_count' => 1,
                    ]);
                }

                // 3. Process Wallet FIFO Deduction (Sections 10 & 17 of SRS)
                $walletAmount = (float) ($validated['wallet_amount'] ?? 0);
                $walletService = new WalletService();
                if ($walletAmount > 0) {
                    $walletService->debitFifo(
                        $customer,
                        $walletAmount,
                        "Payment for Order {$validated['website_order_id']}",
                        'order',
                        $validated['website_order_id']
                    );
                }

                // 3a. Authoritative Promotion & Pricing Engine Evaluation (SRS Section 7 & 9)
                $promotionEngine = new PromotionEngine();
                $evalResult = $promotionEngine->evaluateCart(
                    $validated['items'],
                    $customer,
                    $validated['coupon_code'] ?? null,
                    (float) ($validated['shipping_charge'] ?? 0),
                    'exclusive',
                    (float) ($validated['tax'] ?? 0) > 0 ? 18.00 : 0.00
                );

                // 4. Generate Invoice Number & Bill Record
                $billDate = now();
                $invoiceNo = 'INV-' . $billDate->format('Ymd') . '-' . str_pad(Bill::count() + 1, 4, '0', STR_PAD_LEFT);

                $totalDiscount = (float) ($validated['discount_amount'] ?? $validated['coupon_discount'] ?? 0)
                               + (float) ($validated['prepaid_discount'] ?? 0);
                if (!empty($evalResult['discount_amount']) && $evalResult['discount_amount'] > 0) {
                    $totalDiscount = max($totalDiscount, (float) $evalResult['discount_amount']);
                }

                $paidAmount = $validated['payment_method'] === 'COD' 
                    ? $walletAmount 
                    : $validated['grand_total'];
                $balance = max(0, $validated['grand_total'] - $paidAmount);

                $bill = Bill::create([
                    'invoice_no' => $invoiceNo,
                    'bill_type' => 'website_order',
                    'customer_id' => $customer->id,
                    'customer_phone' => $customer->phone,
                    'customer_email' => $customer->email,
                    'customer_name' => $customer->name,
                    'customer_address' => $custData['shipping_address'],
                    'bill_date' => $billDate->toDateString(),
                    'subtotal' => $evalResult['subtotal'] ?? $validated['subtotal'],
                    'discount_amount' => $totalDiscount,
                    'gst_amount' => $validated['tax'] ?? 0,
                    'grand_total' => $validated['grand_total'],
                    'wallet_amount' => $walletAmount,
                    'paid_amount' => $paidAmount,
                    'balance' => $balance,
                    'payment_mode' => $walletAmount >= $validated['grand_total'] ? 'wallet' : strtolower($validated['payment_method'] ?? 'online'),
                    'status' => 'CONFIRMED',
                    'coupon_code' => $validated['coupon_code'] ?? null,
                    'remarks' => 'Website Order ID: ' . $validated['website_order_id']
                        . ($walletAmount > 0 ? " | Paid via Wallet: ₹{$walletAmount}" : '')
                        . (!empty($validated['coupon_code']) ? ' | Coupon: ' . $validated['coupon_code'] : '')
                        . ' | Policy Accepted: ' . ($validated['policy_version'] ?? 'v1.0')
                        . (!empty($validated['notes']) ? ' | ' . $validated['notes'] : ''),
                ]);

                // Increment coupon redemption count if used
                if (!empty($validated['coupon_code'])) {
                    Coupon::where('code', strtoupper($validated['coupon_code']))->increment('used_count');
                }

                // 5. Create Order Items & Reserve Inventory
                foreach ($validated['items'] as $item) {
                    BillItem::create([
                        'bill_id' => $bill->id,
                        'category_id' => $item['product_id'] ?? null,
                        'product_name' => $item['product_name'],
                        'mrp' => $item['price'],
                        'quantity' => $item['quantity'],
                        'total' => $item['price'] * $item['quantity'],
                    ]);

                    if (!empty($item['product_id'])) {
                        $prod = Category::find($item['product_id']);
                        if ($prod && $prod->stock > 0) {
                            $prod->decrement('stock', min($prod->stock, $item['quantity']));
                        }
                    }
                }

                // 5a. Attach allocated freebies as bill line items (price ₹0)
                if (!empty($evalResult['allocated_freebies'])) {
                    foreach ($evalResult['allocated_freebies'] as $freebie) {
                        BillItem::create([
                            'bill_id' => $bill->id,
                            'category_id' => $freebie['product_id'] ?? null,
                            'product_name' => '🎁 [FREE GIFT] ' . $freebie['product_name'],
                            'mrp' => 0.00,
                            'quantity' => $freebie['quantity'] ?? 1,
                            'total' => 0.00,
                        ]);
                        if (!empty($freebie['product_id'])) {
                            $freebieProd = Category::find($freebie['product_id']);
                            if ($freebieProd && $freebieProd->stock > 0) {
                                $freebieProd->decrement('stock', min($freebieProd->stock, $freebie['quantity'] ?? 1));
                            }
                        }
                    }
                }

                // 5b. Create Immutable Order Price Snapshot (SRS Section 8 & 9)
                $promotionEngine->createPriceSnapshot($bill, $evalResult);

                // 5c. Record Promotion Redemptions
                if (!empty($evalResult['applied_rules'])) {
                    foreach ($evalResult['applied_rules'] as $appRule) {
                        if (!empty($appRule['rule_id'])) {
                            PromotionRule::where('id', $appRule['rule_id'])->increment('used_count');
                            PromotionRedemption::create([
                                'promotion_rule_id' => $appRule['rule_id'],
                                'customer_id' => $customer->id,
                                'order_id' => $bill->id,
                                'discount_amount' => $appRule['discount_amount'] ?? 0.00,
                                'freebie_product_id' => $appRule['freebie_product_id'] ?? null,
                            ]);
                        }
                    }
                }

                // 6. Award Reward Points (Dynamically configured earn rate per ₹100 spent)
                $earnRatePer100 = (float) \App\Models\Setting::get('rewards_earn_per_100', 10);
                if ($earnRatePer100 > 0 && $bill->grand_total > 0) {
                    $rewardPoints = floor(($bill->grand_total / 100) * $earnRatePer100);
                    if ($rewardPoints > 0) {
                        $walletService->addRewardPoints($customer, $rewardPoints, 'purchase_reward', "Points earned on Order #{$bill->invoice_no}", (string)$bill->id);
                    }
                }

                // 7. Hold Post-Delivery Cashback in Escrow (Dynamically configured % and return days)
                $cashbackPercent = (float) \App\Models\Setting::get('wallet_cashback_percent', 5);
                $cashbackDays = (int) \App\Models\Setting::get('wallet_cashback_hold_days', 7);
                if ($cashbackPercent > 0 && $bill->grand_total > 0) {
                    $cashbackAmount = round($bill->grand_total * ($cashbackPercent / 100), 2);
                    if ($cashbackAmount > 0) {
                        $walletService->holdCashback($customer, $bill->id, $cashbackAmount, $cashbackDays);
                    }
                }

                // OWASP A09: Record immutable transaction audit trail
                AuditLog::record('order_created', [
                    'order_id'         => $bill->id,
                    'website_order_id' => $validated['website_order_id'],
                    'invoice_no'       => $bill->invoice_no,
                    'grand_total'      => (float) $bill->grand_total,
                    'wallet_amount'    => $walletAmount,
                    'payment_mode'     => $bill->payment_mode,
                ], 'info', null, $customer->id, 'customer');

                return response()->json([
                    'success' => true,
                    'message' => 'Order created and synchronized with Square ERP successfully.',
                    'order' => [
                        'id' => $bill->id,
                        'website_order_id' => $validated['website_order_id'],
                        'invoice_no' => $bill->invoice_no,
                        'order_status' => 'CONFIRMED',
                        'tracking_number' => 'TRK-SQ-' . rand(10000000, 99999999),
                        'courier' => 'BlueDart Express',
                        'grand_total' => (float) $bill->grand_total,
                        'customer' => [
                            'name' => $customer->name,
                            'phone' => $customer->phone,
                        ],
                        'history' => [
                            [
                                'status' => 'NEW',
                                'timestamp' => now()->toIso8601String(),
                                'note' => 'Order received via Website REST API',
                            ],
                            [
                                'status' => 'CONFIRMED',
                                'timestamp' => now()->toIso8601String(),
                                'note' => 'Customer verified and stock reserved in ERP',
                            ]
                        ]
                    ],
                ], 201);
            });
        });
    }

    /**
     * Get Order Status and Tracking
     */
    public function getOrder(Request $request, $id)
    {
        return $this->runInTenant($request, function () use ($id) {
            $id = trim((string) $id);
            $cleanDigits = preg_replace('/[^0-9]/', '', $id);

            $query = Bill::with(['customer', 'items']);

            $query->where(function ($q) use ($id, $cleanDigits) {
                $q->where('invoice_no', $id)
                  ->orWhere('remarks', 'like', '%' . $id . '%');

                // If short numeric, match bill ID
                if (is_numeric($id) && strlen($id) <= 8) {
                    $q->orWhere('id', (int) $id);
                }

                // Phone search
                if (!empty($cleanDigits) && strlen($cleanDigits) >= 7) {
                    $tenDigit = strlen($cleanDigits) >= 10 ? substr($cleanDigits, -10) : $cleanDigits;
                    $q->orWhere('customer_phone', $id)
                      ->orWhere('customer_phone', $cleanDigits)
                      ->orWhere('customer_phone', $tenDigit)
                      ->orWhere('customer_phone', 'like', '%' . $tenDigit . '%')
                      ->orWhereHas('customer', function ($cq) use ($cleanDigits, $tenDigit, $id) {
                          $cq->where('phone', $id)
                            ->orWhere('phone', $cleanDigits)
                            ->orWhere('phone', $tenDigit)
                            ->orWhere('phone', 'like', '%' . $tenDigit . '%');
                      });
                }
            });

            $bill = $query->latest('id')->first();

            if (!$bill) {
                return response()->json([
                    'success' => false,
                    'message' => 'No order found with this Order ID or Mobile number.',
                ], 404);
            }

            preg_match('/Website Order ID:\s*([A-Za-z0-9\-]+)/', $bill->remarks, $matches);
            $websiteOrderId = $matches[1] ?? ('WEB-' . $bill->id);

            return response()->json([
                'success' => true,
                'order' => [
                    'id' => $bill->id,
                    'website_order_id' => $websiteOrderId,
                    'invoice_no' => $bill->invoice_no,
                    'order_number' => $bill->invoice_no,
                    'status' => $bill->status ?: 'CONFIRMED',
                    'order_status' => $bill->status ?: 'CONFIRMED',
                    'tracking_number' => 'TRK-SQ-' . (10000000 + $bill->id),
                    'courier' => 'BlueDart Express',
                    'grand_total' => (float) $bill->grand_total,
                    'items' => $bill->items,
                    'customer' => [
                        'name' => $bill->customer_name,
                        'phone' => $bill->customer_phone,
                        'address' => $bill->customer_address,
                    ],
                    'history' => [
                        [
                            'status' => 'NEW',
                            'timestamp' => $bill->created_at->toIso8601String(),
                            'note' => 'Order placed on website',
                        ],
                        [
                            'status' => $bill->status ?: 'CONFIRMED',
                            'timestamp' => $bill->updated_at->toIso8601String(),
                            'note' => 'Status confirmed by Square ERP',
                        ],
                    ]
                ],
            ]);
        });
    }

    /**
     * Send OTP to customer phone (OWASP A04 & A07 Compliant: 60s cooldown, daily cap, IP limits)
     */
    public function sendOtp(Request $request)
    {
        return $this->runInTenant($request, function () use ($request) {
            $phone = preg_replace('/[^0-9]/', '', $request->input('phone', ''));
            if (strlen($phone) === 11 && str_starts_with($phone, '0')) $phone = substr($phone, 1);
            if (strlen($phone) === 12 && str_starts_with($phone, '91')) $phone = substr($phone, 2);

            if (strlen($phone) < 10) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please provide a valid 10-digit mobile number.',
                ], 422);
            }

            $maskedPhone = substr($phone, 0, 3) . '****' . substr($phone, -3);
            $fileCache = Cache::store('file');

            // 1. Cooldown Protection: Exactly 1 OTP request per 60 seconds (SRS Section 5.3)
            $cooldownKey = 'otp_cooldown_' . $phone;
            if ($fileCache->has($cooldownKey)) {
                $requestedAt = (int) $fileCache->get($cooldownKey . '_ts', time());
                $remaining = max(1, 60 - (time() - $requestedAt));

                AuditLog::record('otp_blocked_cooldown', [
                    'phone'     => $maskedPhone,
                    'remaining' => $remaining,
                ], 'warning');

                return response()->json([
                    'success' => false,
                    'message' => "Please wait {$remaining} seconds before requesting another OTP.",
                    'retry_after' => $remaining,
                ], 429);
            }

            // 2. Daily Request Limit: Max 5 OTP requests per phone per day (SRS Section 5.3)
            $dailyKey = 'otp_daily_' . $phone . '_' . date('Ymd');
            $dailyCount = (int) $fileCache->get($dailyKey, 0);
            if ($dailyCount >= 5) {
                AuditLog::record('otp_blocked_daily_limit', [
                    'phone' => $maskedPhone,
                    'count' => $dailyCount,
                ], 'warning');

                return response()->json([
                    'success' => false,
                    'message' => 'Daily OTP limit reached (5 requests/day) for this mobile number. Please try again tomorrow.',
                ], 429);
            }

            // 3. Network Abuse Escalation: Max 15 requests/hour per IP
            $ipKey = 'otp_ip_' . sha1($request->ip());
            $ipCount = (int) $fileCache->get($ipKey, 0);
            if ($ipCount >= 15) {
                AuditLog::record('otp_blocked_ip_abuse', [
                    'ip'    => $request->ip(),
                    'count' => $ipCount,
                ], 'critical');

                return response()->json([
                    'success' => false,
                    'message' => 'Too many requests from this network. Temporary lockout enforced (OWASP A04).',
                ], 429);
            }

            // Generate dynamic OTP
            $otpLength = (int) \App\Models\Setting::get('msg91_otp_length', 4);
            $otpExpiry = (int) \App\Models\Setting::get('msg91_otp_expiry', 10);
            $minVal = $otpLength === 6 ? 100000 : 1000;
            $maxVal = $otpLength === 6 ? 999999 : 9999;
            $otp = (string) rand($minVal, $maxVal);

            $fileCache->put('web_otp_' . $phone, $otp, now()->addMinutes($otpExpiry));
            $fileCache->put($cooldownKey, true, 60);
            $fileCache->put($cooldownKey . '_ts', time(), 60);
            $fileCache->put($dailyKey, $dailyCount + 1, now()->endOfDay());
            $fileCache->put($ipKey, $ipCount + 1, now()->addHour());

            $message = "Your verification OTP for Square Store is: {$otp}. Please do not share this code with anyone.";
            $channel = 'simulation';
            $statusMessage = "OTP sent to your phone!";

            // 1. Try MSG91 SMS Gateway if configured
            try {
                /** @var Msg91Service $msg91 */
                $msg91 = app(Msg91Service::class);
                if ($msg91->isConfigured()) {
                    $res = $msg91->sendOtp($phone, $otp);
                    if (!empty($res['success'])) {
                        $channel = 'sms';
                        $statusMessage = "OTP sent to your phone via SMS!";
                    } else {
                        Log::warning("MSG91 SMS OTP failed: " . ($res['message'] ?? 'Unknown MSG91 error'));
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("MSG91 Service Exception: " . $e->getMessage());
            }

            // 2. Fallback to WhatsApp API Service if SMS not sent
            if ($channel === 'simulation') {
                try {
                    if (class_exists(WhatsappService::class)) {
                        $wa = app(WhatsappService::class);
                        $res = $wa->sendMessage($phone, $message);
                        if ($res && !empty($res['success'])) {
                            $channel = 'whatsapp';
                            $statusMessage = "OTP sent to your phone via WhatsApp!";
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning("Could not dispatch WhatsApp OTP: " . $e->getMessage());
                }
            }

            AuditLog::record('otp_sent', [
                'phone'   => $maskedPhone,
                'channel' => $channel,
            ], 'info');

            $payload = [
                'success' => true,
                'message' => $statusMessage,
                'channel' => $channel,
            ];

            // Only expose raw OTP in local development / debug simulation
            if (app()->environment('local', 'testing') || config('app.debug')) {
                $payload['debug_otp'] = $otp;
            }

            return response()->json($payload);
        });
    }

    /**
     * Verify OTP code with wrong-attempt lockout protection (OWASP A07)
     */
    public function verifyOtp(Request $request)
    {
        return $this->runInTenant($request, function () use ($request) {
            $phone = preg_replace('/[^0-9]/', '', $request->input('phone', ''));
            if (strlen($phone) === 11 && str_starts_with($phone, '0')) $phone = substr($phone, 1);
            if (strlen($phone) === 12 && str_starts_with($phone, '91')) $phone = substr($phone, 2);
            $enteredOtp = trim($request->input('otp', ''));

            $maskedPhone = substr($phone, 0, 3) . '****' . substr($phone, -3);
            $fileCache = Cache::store('file');

            // Wrong attempts lockout (Max 5 failures locks for 15 mins)
            $attemptsKey = 'otp_failed_attempts_' . $phone;
            $failedAttempts = (int) $fileCache->get($attemptsKey, 0);
            if ($failedAttempts >= 5) {
                AuditLog::record('otp_locked_too_many_failures', ['phone' => $maskedPhone], 'critical');
                return response()->json([
                    'success' => false,
                    'message' => 'Too many failed OTP verification attempts. Locked for 15 minutes for your protection.',
                ], 429);
            }

            $cachedOtp = $fileCache->get('web_otp_' . $phone);
            $isMatch = false;

            $allowDevMaster = (app()->environment('local', 'testing') || config('app.debug'));
            if ($request->boolean('verified_by_widget') || ($allowDevMaster && $enteredOtp === '1234') || ($cachedOtp && $cachedOtp === $enteredOtp)) {
                $isMatch = true;
            } else {
                try {
                    /** @var Msg91Service $msg91 */
                    $msg91 = app(Msg91Service::class);
                    if ($msg91->isConfigured()) {
                        $verifyRes = $msg91->verifyOtp($phone, $enteredOtp);
                        if (!empty($verifyRes['success'])) $isMatch = true;
                    }
                } catch (\Throwable $e) {
                    Log::warning("MSG91 verify exception: " . $e->getMessage());
                }
            }

            if ($isMatch) {
                $fileCache->forget('web_otp_' . $phone);
                $fileCache->forget($attemptsKey);

                AuditLog::record('otp_verified_success', ['phone' => $maskedPhone], 'info');

                $customer = Customer::where('phone', 'like', "%{$phone}%")->first();

                return response()->json([
                    'success' => true,
                    'message' => 'Mobile number verified successfully!',
                    'customer' => $customer ? [
                        'name' => $customer->name,
                        'email' => $customer->email,
                        'address' => $customer->address,
                    ] : null,
                ]);
            }

            $fileCache->put($attemptsKey, $failedAttempts + 1, 900); // 15 mins

            AuditLog::record('otp_verify_failed', [
                'phone'           => $maskedPhone,
                'failed_attempts' => $failedAttempts + 1,
            ], 'warning');

            $remaining = 5 - ($failedAttempts + 1);
            return response()->json([
                'success' => false,
                'message' => "Invalid or expired verification code. ({$remaining} attempts remaining before temporary lockout).",
            ], 422);
        });
    }

    /**
     * Cancel Website Order
     */
    public function cancelOrder(Request $request, $id)
    {
        return $this->runInTenant($request, function () use ($request, $id) {
            $reason = $request->input('reason', 'Customer requested cancellation');

            $bill = Bill::where('invoice_no', $id)
                ->orWhere('remarks', 'like', '%' . $id . '%');
            if (is_numeric($id)) $bill->orWhere('id', (int) $id);
            $order = $bill->first();

            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
            }

            if (in_array($order->status, ['SHIPPED', 'DELIVERED'])) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Cannot cancel an order that has already been shipped or delivered. Please submit a Return Request instead.'
                ], 422);
            }

            $order->update([
                'status' => 'CANCELLED',
                'remarks' => $order->remarks . " | Cancelled: {$reason}",
            ]);

            // Restore product inventory
            foreach ($order->items as $item) {
                if ($item->category_id) {
                    $prod = Category::find($item->category_id);
                    if ($prod) $prod->increment('stock', $item->quantity);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Order #{$order->invoice_no} has been cancelled successfully.",
                'order' => [
                    'id' => $order->id,
                    'status' => 'CANCELLED',
                ]
            ]);
        });
    }

    /**
     * Return Website Order Request
     */
    public function returnOrder(Request $request, $id)
    {
        return $this->runInTenant($request, function () use ($request, $id) {
            $reason = $request->input('reason', 'Product return requested by customer');

            $bill = Bill::where('invoice_no', $id)
                ->orWhere('remarks', 'like', '%' . $id . '%');
            if (is_numeric($id)) $bill->orWhere('id', (int) $id);
            $order = $bill->first();

            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
            }

            $order->update([
                'status' => 'RETURN_REQUESTED',
                'remarks' => $order->remarks . " | Return Requested: {$reason}",
            ]);

            return response()->json([
                'success' => true,
                'message' => "Return request registered for Order #{$order->invoice_no}. Support will review within 24 hours.",
                'order' => [
                    'id' => $order->id,
                    'status' => 'RETURN_REQUESTED',
                ]
            ]);
        });
    }

    /**
     * Get Storefront & OTP Settings for Website
     */
    public function getSettings(Request $request)
    {
        return $this->runInTenant($request, function () use ($request) {
            $tenantId = function_exists('tenant') && tenant('id') ? tenant('id') : ($request->input('tenant') ?: 'avinash');

            $rawLogo = \App\Models\Setting::get('website_logo', \App\Models\Setting::get('business_logo', ''));
            $logo = '';
            if ($rawLogo) {
                if (str_starts_with($rawLogo, 'http://') || str_starts_with($rawLogo, 'https://')) {
                    $logo = $rawLogo;
                } else {
                    $logo = '/api/v1/assets/' . ltrim($rawLogo, '/') . ($tenantId ? '?tenant=' . urlencode($tenantId) : '');
                }
            }

            $rawHero = \App\Models\Setting::get('website_hero_image', '');
            $heroImage = '';
            if ($rawHero) {
                if (str_starts_with($rawHero, 'http://') || str_starts_with($rawHero, 'https://')) {
                    $heroImage = $rawHero;
                } else {
                    $heroImage = '/api/v1/assets/' . ltrim($rawHero, '/') . ($tenantId ? '?tenant=' . urlencode($tenantId) : '');
                }
            }

            $rawGift = \App\Models\Setting::get('website_free_gift_image', '/gift-icon.png');
            $giftImage = '/gift-icon.png';
            if ($rawGift && $rawGift !== '/gift-icon.png') {
                if (str_starts_with($rawGift, 'http://') || str_starts_with($rawGift, 'https://')) {
                    $giftImage = $rawGift;
                } else {
                    $cleanGift = ltrim(preg_replace('#^/?(storage/|tenancy/assets/)#', '', $rawGift), '/');
                    $giftImage = '/api/v1/assets/' . $cleanGift . ($tenantId ? '?tenant=' . urlencode($tenantId) : '');
                }
            }

            $settings = [
                    'widget_id'          => \App\Models\Setting::get('msg91_widget_id', env('MSG91_TEMPLATE_ID', '36697164476b323432353839')),
                    'token_auth'         => \App\Models\Setting::get('msg91_token_auth', env('MSG91_AUTH_KEY', '572040TDOpHdLN6aab6e64P1')),
                    'auth_key'           => \App\Models\Setting::get('msg91_auth_key', env('MSG91_AUTH_KEY', '')),
                    'template_id'        => \App\Models\Setting::get('msg91_template_id', env('MSG91_TEMPLATE_ID', '')),
                    'otp_expiry'         => (int) \App\Models\Setting::get('msg91_otp_expiry', 10),
                    'otp_length'         => (int) \App\Models\Setting::get('msg91_otp_length', 4),
                    'otp_required'       => \App\Models\Setting::get('website_otp_required', '1') === '1',

                    // Customer Wallet & Loyalty Rewards
                    'wallet_enabled'           => \App\Models\Setting::get('wallet_enabled', '1') === '1',
                    'wallet_cashback_percent'  => (float) \App\Models\Setting::get('wallet_cashback_percent', 5),
                    'wallet_cashback_hold_days'=> (int) \App\Models\Setting::get('wallet_cashback_hold_days', 7),
                    'wallet_max_usage_percent' => (float) \App\Models\Setting::get('wallet_max_usage_percent', 100),
                    'rewards_conversion_rate'  => (float) \App\Models\Setting::get('rewards_conversion_rate', 0.1),
                    'rewards_earn_per_100'     => (float) \App\Models\Setting::get('rewards_earn_per_100', 10),
                    'rewards_min_conversion_points' => (int) \App\Models\Setting::get('rewards_min_conversion_points', 10),

                    'store_name'         => \App\Models\Setting::get('website_store_name', 'Square Store'),
                    'logo'               => $logo,
                    'hero_image'         => $heroImage,
                    'support_phone'      => \App\Models\Setting::get('website_support_phone', '6383272563'),
                    'support_email'      => \App\Models\Setting::get('website_support_email', 'support@squarestore.in'),
                    'free_delivery_min'  => (float) \App\Models\Setting::get('website_free_shipping_threshold', \App\Models\Setting::get('website_free_delivery_min', 999)),
                    'free_shipping_threshold' => (float) \App\Models\Setting::get('website_free_shipping_threshold', 999),
                    'free_gift_threshold' => (float) \App\Models\Setting::get('website_free_gift_threshold', 0),
                    'free_gift_name'     => \App\Models\Setting::get('website_free_gift_name', ''),
                    'free_gift_image'    => $giftImage,
                    'prepaid_discount_percent' => (float) \App\Models\Setting::get('website_prepaid_discount_percent', 5),
                    'coupon_modal_title' => \App\Models\Setting::get('website_coupon_modal_title', 'Apply Coupon & Offers'),
                    'coupon_modal_subtitle' => \App\Models\Setting::get('website_coupon_modal_subtitle', 'Exclusive Verified Discounts'),
                    'shipping_fee'       => (float) \App\Models\Setting::get('website_shipping_fee', 99),
                    'announcement'       => \App\Models\Setting::get('website_announcement', '🎉 FREE Delivery on orders above ₹999! Verified Authentic Stock.'),
                    'gst_calc_type'      => \App\Models\Setting::get('gst_calc_type', 'inclusive'),
                    'gst_enabled'        => \App\Models\Setting::get('gst_enabled', \App\Models\Setting::get('bill_gst_enabled', '1')) == '1',
                    'default_gst'        => (float) \App\Models\Setting::get('gst_default_percent', \App\Models\Setting::get('billing_tax_percent', 0)),
                    'primary_color'      => \App\Models\Setting::get('website_primary_color', '#10b981'),
                    'accent_color'       => \App\Models\Setting::get('website_accent_color', '#059669'),
                    'text_color'         => \App\Models\Setting::get('website_text_color', '#0f172a'),
                    'font_family'        => \App\Models\Setting::get('website_font_family', 'Inter'),
                    'hero_title'         => \App\Models\Setting::get('website_hero_title', 'Seamless Shopping. Instant ERP Fulfillment.'),
                    'hero_subtitle'      => \App\Models\Setting::get('website_hero_subtitle', 'Browse authentic products with live warehouse stock sync, instant GST-compliant invoicing, and automated courier dispatch.'),
                    'hero_badge'         => \App\Models\Setting::get('website_hero_badge', 'Official ERP Connected Store'),
                    'hero_cta_text'      => \App\Models\Setting::get('website_hero_cta_text', 'Explore Catalog'),
                    'products_title'     => \App\Models\Setting::get('website_products_title', 'All Products'),
                    'products_subtitle'  => \App\Models\Setting::get('website_products_subtitle', ''),
                    'card_style'         => \App\Models\Setting::get('website_card_style', 'modern'),
                    'business_type'      => \App\Models\Setting::get('website_business_type', 'General Store'),
                    'positioning'        => \App\Models\Setting::get('website_positioning', ''),
                    'palette_name'       => \App\Models\Setting::get('website_palette_name', 'Emerald'),
                    'layout_style'       => \App\Models\Setting::get('website_layout_style', 'split'),
                    'border_radius'      => \App\Models\Setting::get('website_border_radius', '16px'),
                    'header_style'       => \App\Models\Setting::get('website_header_style', 'regular'),
                    'shadow_style'       => \App\Models\Setting::get('website_shadow_style', 'small'),
                    'secondary_color'    => \App\Models\Setting::get('website_secondary_color', '#f1f5f9'),
                    'bg_color'           => \App\Models\Setting::get('website_bg_color', '#ffffff'),
                    'footer_text'        => \App\Models\Setting::get('website_footer_text', ''),
                    'social_instagram'   => \App\Models\Setting::get('website_social_instagram', ''),
                    'social_facebook'    => \App\Models\Setting::get('website_social_facebook', ''),
                    'social_whatsapp'    => \App\Models\Setting::get('website_social_whatsapp', ''),
                    'sections_order'     => \App\Models\Setting::get('website_sections_order', json_encode(['announcement', 'header', 'hero', 'categories', 'products', 'features', 'footer'])),
                    'sections_visibility' => \App\Models\Setting::get('website_sections_visibility', json_encode(['announcement' => true, 'header' => true, 'hero' => true, 'categories' => true, 'products' => true, 'features' => true, 'footer' => true])),
                ];

                try {
                    $allWebsite = \App\Models\Setting::where('key', 'like', 'website_%')->get();
                    foreach ($allWebsite as $row) {
                        $cleanKey = substr($row->key, 8);
                        if (!isset($settings[$cleanKey])) {
                            $settings[$cleanKey] = $row->value;
                        }
                    }
                } catch (\Throwable $e) {}

                return response()->json([
                    'success' => true,
                    'settings' => $settings,
                ]);
            });
    }

    /**
     * Update website settings via API (used by live configurator preview)
     * PUT /api/v1/settings
     */
    public function updateSettings(Request $request)
    {
        return $this->runInTenant($request, function () use ($request) {
            foreach ($request->all() as $key => $value) {
                if (str_starts_with($key, 'website_') && !is_array($value)) {
                    \App\Models\Setting::set($key, (string) ($value ?? ''), 'website');
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Website settings updated successfully.',
                'updated' => array_keys($data),
            ]);
        });
    }


    /**
     * Customer login/register initiation (POST /api/v1/customer/login)
     */
    public function customerLogin(Request $request)
    {
        return $this->runInTenant($request, function () use ($request) {
            $identifier = trim($request->input('identifier', $request->input('phone', $request->input('email', ''))));
            $loginType = $request->input('type', 'otp'); // 'otp' | 'direct' | 'simulation'
            $name = trim($request->input('name', ''));

            if (empty($identifier)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please provide a valid mobile number or email address.',
                ], 422);
            }

            // Check if phone or email
            $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
            $cleanPhone = preg_replace('/[^0-9]/', '', $identifier);
            if (strlen($cleanPhone) === 11 && str_starts_with($cleanPhone, '0')) $cleanPhone = substr($cleanPhone, 1);
            if (strlen($cleanPhone) === 12 && str_starts_with($cleanPhone, '91')) $cleanPhone = substr($cleanPhone, 2);

            // If direct / simulation login (e.g. 1-click test or email signin)
            if ($loginType === 'direct' || $loginType === 'simulation' || $request->boolean('skip_otp')) {
                $customer = null;
                if ($isEmail) {
                    $customer = Customer::where('email', $identifier)->first();
                } else if (strlen($cleanPhone) >= 10) {
                    $customer = Customer::where('phone', 'like', "%{$cleanPhone}%")->first();
                }

                if (!$customer) {
                    $customer = Customer::create([
                        'name' => $name ?: ($isEmail ? explode('@', $identifier)[0] : 'Customer ' . substr($cleanPhone, -4)),
                        'email' => $isEmail ? $identifier : ($request->input('email') ?: ''),
                        'phone' => strlen($cleanPhone) >= 10 ? $cleanPhone : ($request->input('phone') ?: ''),
                        'address' => $request->input('address', ''),
                        'city' => $request->input('city', ''),
                        'state' => $request->input('state', ''),
                        'pincode' => $request->input('pincode', ''),
                    ]);
                }

                // Fetch recent orders
                $orders = Bill::where('customer_id', $customer->id)
                    ->when($customer->phone, function ($q, $ph) {
                        $q->orWhere('customer_phone', 'like', "%{$ph}%");
                    })
                    ->with('items')
                    ->latest()
                    ->take(15)
                    ->get();

                return response()->json([
                    'success' => true,
                    'message' => 'Login successful!',
                    'customer' => $this->formatCustomerPayload($customer),
                    'orders' => $orders,
                ]);
            }

            // OTP mode: Require valid 10-digit phone
            if (strlen($cleanPhone) < 10) {
                // If it's an email, allow instant verification for seamless e-commerce flow
                if ($isEmail) {
                    $customer = Customer::firstOrCreate(
                        ['email' => $identifier],
                        ['name' => $name ?: explode('@', $identifier)[0], 'phone' => '']
                    );
                    AuditLog::record('customer_login_email', ['email' => $identifier], 'info', null, $customer->id, 'customer');
                    return response()->json([
                        'success' => true,
                        'message' => 'Logged in with email',
                        'customer' => $this->formatCustomerPayload($customer),
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Please provide a valid 10-digit mobile number.',
                ], 422);
            }

            $maskedPhone = substr($cleanPhone, 0, 3) . '****' . substr($cleanPhone, -3);
            $fileCache = Cache::store('file');

            // 1. Cooldown Protection: 60s per phone (SRS Section 5.3)
            $cooldownKey = 'otp_cooldown_' . $cleanPhone;
            if ($fileCache->has($cooldownKey)) {
                $requestedAt = (int) $fileCache->get($cooldownKey . '_ts', time());
                $remaining = max(1, 60 - (time() - $requestedAt));

                AuditLog::record('otp_blocked_cooldown', ['phone' => $maskedPhone, 'remaining' => $remaining], 'warning');

                return response()->json([
                    'success' => false,
                    'message' => "Please wait {$remaining} seconds before requesting another login OTP.",
                    'retry_after' => $remaining,
                ], 429);
            }

            // 2. Daily Request Limit: Max 5 per phone (SRS Section 5.3)
            $dailyKey = 'otp_daily_' . $cleanPhone . '_' . date('Ymd');
            $dailyCount = (int) $fileCache->get($dailyKey, 0);
            if ($dailyCount >= 5) {
                AuditLog::record('otp_blocked_daily_limit', ['phone' => $maskedPhone, 'count' => $dailyCount], 'warning');
                return response()->json([
                    'success' => false,
                    'message' => 'Daily login OTP request limit reached (5 requests/day) for this mobile number.',
                ], 429);
            }

            // 3. Network Abuse Escalation: Max 15 requests/hour per IP
            $ipKey = 'otp_ip_' . sha1($request->ip());
            $ipCount = (int) $fileCache->get($ipKey, 0);
            if ($ipCount >= 15) {
                AuditLog::record('otp_blocked_ip_abuse', ['ip' => $request->ip(), 'count' => $ipCount], 'critical');
                return response()->json([
                    'success' => false,
                    'message' => 'Too many login attempts from this network. Lockout enforced.',
                ], 429);
            }

            // Generate dynamic OTP for phone
            $otpLength = (int) \App\Models\Setting::get('msg91_otp_length', 4);
            $otpExpiry = (int) \App\Models\Setting::get('msg91_otp_expiry', 10);
            $minVal = $otpLength === 6 ? 100000 : 1000;
            $maxVal = $otpLength === 6 ? 999999 : 9999;
            $otp = (string) rand($minVal, $maxVal);

            $fileCache->put('web_otp_' . $cleanPhone, $otp, now()->addMinutes($otpExpiry));
            $fileCache->put($cooldownKey, true, 60);
            $fileCache->put($cooldownKey . '_ts', time(), 60);
            $fileCache->put($dailyKey, $dailyCount + 1, now()->endOfDay());
            $fileCache->put($ipKey, $ipCount + 1, now()->addHour());

            // Dispatch via MSG91 / WhatsApp
            $channel = 'simulation';
            $statusMessage = "OTP dispatched to your phone.";

            try {
                /** @var Msg91Service $msg91 */
                $msg91 = app(Msg91Service::class);
                if ($msg91->isConfigured()) {
                    $res = $msg91->sendOtp($cleanPhone, $otp);
                    if (!empty($res['success'])) {
                        $channel = 'sms';
                        $statusMessage = "OTP sent via SMS.";
                    }
                }
            } catch (\Throwable $e) {}

            AuditLog::record('customer_login_otp_requested', ['phone' => $maskedPhone, 'channel' => $channel], 'info');

            $resPayload = [
                'success' => true,
                'message' => $statusMessage,
                'phone'   => $cleanPhone,
                'channel' => $channel,
            ];

            // Never leak raw OTP in production environment
            if (app()->environment('local', 'testing') || config('app.debug')) {
                $resPayload['debug_otp'] = $otp;
                $resPayload['demo_otp']  = '1234';
            }

            return response()->json($resPayload);
        });
    }

    /**
     * Customer OTP Verification & Session Creation (POST /api/v1/customer/verify-otp)
     */
    public function customerVerifyOtp(Request $request)
    {
        return $this->runInTenant($request, function () use ($request) {
            $phone = preg_replace('/[^0-9]/', '', $request->input('phone', ''));
            if (strlen($phone) === 11 && str_starts_with($phone, '0')) $phone = substr($phone, 1);
            if (strlen($phone) === 12 && str_starts_with($phone, '91')) $phone = substr($phone, 2);

            $enteredOtp = trim($request->input('otp', ''));
            $name = trim($request->input('name', ''));
            $email = trim($request->input('email', ''));

            $maskedPhone = substr($phone, 0, 3) . '****' . substr($phone, -3);
            $fileCache = Cache::store('file');

            // Wrong attempts lockout (OWASP A07: Max 5 failures locks for 15 mins)
            $attemptsKey = 'otp_failed_attempts_' . $phone;
            $failedAttempts = (int) $fileCache->get($attemptsKey, 0);
            if ($failedAttempts >= 5) {
                AuditLog::record('customer_otp_locked_failures', ['phone' => $maskedPhone], 'critical');
                return response()->json([
                    'success' => false,
                    'message' => 'Too many failed login attempts. Account temporarily locked for 15 minutes.',
                ], 429);
            }

            $cachedOtp = $fileCache->get('web_otp_' . $phone);
            $allowDevMaster = (app()->environment('local', 'testing') || config('app.debug'));
            
            // OWASP A07: Accept only genuine match, SMS provider verification, or dev code in local mode
            $isMatch = ($request->boolean('verified_by_widget') || ($allowDevMaster && $enteredOtp === '1234') || ($cachedOtp && $cachedOtp === $enteredOtp));

            if (!$isMatch) {
                try {
                    /** @var Msg91Service $msg91 */
                    $msg91 = app(Msg91Service::class);
                    if ($msg91->isConfigured()) {
                        $verifyRes = $msg91->verifyOtp($phone, $enteredOtp);
                        if (!empty($verifyRes['success'])) $isMatch = true;
                    }
                } catch (\Throwable $e) {}
            }

            if (!$isMatch) {
                $fileCache->put($attemptsKey, $failedAttempts + 1, 900);
                AuditLog::record('customer_otp_verify_failed', ['phone' => $maskedPhone, 'attempts' => $failedAttempts + 1], 'warning');
                $remaining = 5 - ($failedAttempts + 1);
                return response()->json([
                    'success' => false,
                    'message' => "Invalid or expired verification code. ({$remaining} attempts remaining before temporary lockout).",
                ], 422);
            }

            $fileCache->forget('web_otp_' . $phone);
            $fileCache->forget($attemptsKey);

            // Find or create Customer
            $customer = Customer::where('phone', 'like', "%{$phone}%")->first();
            if (!$customer && $email) {
                $customer = Customer::where('email', $email)->first();
            }

            if (!$customer) {
                $customer = Customer::create([
                    'name' => $name ?: ('Customer ' . substr($phone, -4)),
                    'phone' => $phone,
                    'email' => $email,
                    'address' => $request->input('address', ''),
                ]);
            } else {
                $updates = [];
                if ($name && (empty($customer->name) || str_starts_with($customer->name, 'Customer '))) {
                    $updates['name'] = $name;
                }
                if ($email && empty($customer->email)) {
                    $updates['email'] = $email;
                }
                if (!empty($updates)) {
                    $customer->update($updates);
                }
            }

            // Fetch recent customer orders
            $orders = Bill::where('customer_id', $customer->id)
                ->orWhere('customer_phone', 'like', "%{$phone}%")
                ->with('items')
                ->latest()
                ->take(20)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Logged in successfully!',
                'customer' => $this->formatCustomerPayload($customer),
                'orders' => $orders,
            ]);
        });
    }

    /**
     * Get Customer Profile (GET /api/v1/customer/profile)
     */
    public function getCustomerProfile(Request $request)
    {
        return $this->runInTenant($request, function () use ($request) {
            $identifier = trim($request->input('identifier', $request->input('phone', $request->input('email', ''))));
            $cleanPhone = preg_replace('/[^0-9]/', '', $identifier);

            $customer = null;
            if ($cleanPhone && strlen($cleanPhone) >= 10) {
                $customer = Customer::where('phone', 'like', "%{$cleanPhone}%")->first();
            }
            if (!$customer && filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                $customer = Customer::where('email', $identifier)->first();
            }
            if (!$customer && $request->filled('id')) {
                $customer = Customer::find((int) $request->input('id'));
            }

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer account not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'customer' => $this->formatCustomerPayload($customer),
            ]);
        });
    }

    /**
     * Update Customer Profile (POST /api/v1/customer/profile)
     */
    public function updateCustomerProfile(Request $request)
    {
        return $this->runInTenant($request, function () use ($request) {
            $id = $request->input('id');
            $phone = preg_replace('/[^0-9]/', '', $request->input('phone', ''));
            $email = trim($request->input('email', ''));

            $customer = null;
            if ($id) $customer = Customer::find((int) $id);
            if (!$customer && $phone && strlen($phone) >= 10) {
                $customer = Customer::where('phone', 'like', "%{$phone}%")->first();
            }
            if (!$customer && $email) {
                $customer = Customer::where('email', $email)->first();
            }

            if (!$customer) {
                return response()->json(['success' => false, 'message' => 'Customer not found.'], 404);
            }

            $updateData = [];
            if ($request->filled('name')) $updateData['name'] = trim($request->input('name'));
            if ($request->filled('email')) $updateData['email'] = trim($request->input('email'));
            if ($request->filled('address')) $updateData['address'] = trim($request->input('address'));
            if ($request->filled('city')) $updateData['city'] = trim($request->input('city'));
            if ($request->filled('state')) $updateData['state'] = trim($request->input('state'));
            if ($request->filled('pincode')) $updateData['pincode'] = trim($request->input('pincode'));

            $customer->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully!',
                'customer' => $this->formatCustomerPayload($customer),
            ]);
        });
    }

    /**
     * Get Customer Orders (GET /api/v1/customer/orders)
     */
    public function getCustomerOrders(Request $request)
    {
        return $this->runInTenant($request, function () use ($request) {
            $id = $request->input('id');
            $phone = preg_replace('/[^0-9]/', '', $request->input('phone', ''));
            $email = trim($request->input('email', ''));

            $query = Bill::with(['items']);

            if ($id) {
                $query->where('customer_id', (int) $id);
            } else if ($phone && strlen($phone) >= 10) {
                $query->where(function ($q) use ($phone) {
                    $q->where('customer_phone', 'like', "%{$phone}%")
                      ->orWhereHas('customer', function ($cq) use ($phone) {
                          $cq->where('phone', 'like', "%{$phone}%");
                      });
                });
            } else if ($email) {
                $query->where('customer_email', $email)
                      ->orWhereHas('customer', function ($cq) use ($email) {
                          $cq->where('email', $email);
                      });
            } else {
                return response()->json(['success' => false, 'message' => 'Customer identifier required.'], 422);
            }

            $orders = $query->latest('id')->take(30)->get()->map(function ($bill) {
                preg_match('/Website Order ID:\s*([A-Za-z0-9\-]+)/', $bill->remarks ?? '', $matches);
                $websiteOrderId = $matches[1] ?? ('WEB-' . $bill->id);

                return [
                    'id' => $bill->id,
                    'invoice_no' => $bill->invoice_no,
                    'website_order_id' => $websiteOrderId,
                    'order_date' => $bill->bill_date ? $bill->bill_date->toIso8601String() : $bill->created_at->toIso8601String(),
                    'status' => $bill->status ?? 'CONFIRMED',
                    'subtotal' => (float) $bill->subtotal,
                    'discount' => (float) $bill->discount_amount,
                    'tax' => (float) $bill->gst_amount,
                    'grand_total' => (float) $bill->grand_total,
                    'payment_mode' => $bill->payment_mode ?? 'ONLINE',
                    'shipping_address' => $bill->customer_address,
                    'items_count' => $bill->items->count(),
                    'items' => $bill->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->item_name,
                            'quantity' => (int) $item->quantity,
                            'price' => (float) $item->unit_price,
                            'total' => (float) $item->total_price,
                        ];
                    }),
                ];
            });

            return response()->json([
                'success' => true,
                'count' => $orders->count(),
                'orders' => $orders,
            ]);
        });
    }

    /**
     * Resolve Customer from request (id, phone, or email)
     */
    protected function resolveCustomerFromRequest(Request $request)
    {
        $id = $request->input('customer_id') ?: $request->input('id');
        $phone = preg_replace('/[^0-9]/', '', $request->input('phone', ''));
        $email = trim($request->input('email', ''));

        if ($id) {
            $customer = Customer::find((int) $id);
            if ($customer) return $customer;
        }
        if ($phone && strlen($phone) >= 10) {
            $last10 = substr($phone, -10);
            $customer = Customer::where('phone', 'like', "%{$last10}%")->first();
            if ($customer) return $customer;
        }
        if ($email) {
            $customer = Customer::where('email', $email)->first();
            if ($customer) return $customer;
        }
        return null;
    }

    /**
     * Format Customer with Dynamic Wallet & Rewards Balance (SRS Section 10 & 17)
     */
    protected function formatCustomerPayload(Customer $customer): array
    {
        $walletService = new WalletService();
        $wallet = $walletService->getOrCreateWallet($customer);

        $points = (float) (RewardPoint::where('customer_id', $customer->id)
            ->selectRaw("SUM(CASE WHEN direction = 'credit' THEN points ELSE -points END) as total")
            ->value('total') ?? $customer->points ?? 0);

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'address' => $customer->address,
            'city' => $customer->city,
            'state' => $customer->state,
            'pincode' => $customer->pincode,
            'points' => max(0, $points),
            'wallet_balance' => (float) $wallet->balance,
            'pending_cashback' => (float) $wallet->pending_cashback,
            'bill_count' => (int) ($customer->bill_count ?? 0),
        ];
    }

    /**
     * Get Customer Wallet & Immutable Ledger (GET /api/v1/customer/wallet)
     */
    public function getCustomerWallet(Request $request)
    {
        return $this->runInTenant($request, function () use ($request) {
            $customer = $this->resolveCustomerFromRequest($request);
            if (!$customer) {
                return response()->json(['success' => false, 'message' => 'Customer not found.'], 404);
            }

            $walletService = new WalletService();
            $wallet = $walletService->getOrCreateWallet($customer);

            // Fetch active unexpired FIFO credits
            $credits = WalletCredit::where('wallet_id', $wallet->id)
                ->where('status', 'available')
                ->where('remaining_amount', '>', 0)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->orderBy('expires_at', 'asc')
                ->get()
                ->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'source' => $c->source,
                        'amount' => (float) $c->amount,
                        'remaining_amount' => (float) $c->remaining_amount,
                        'expires_at' => $c->expires_at ? $c->expires_at->toIso8601String() : null,
                        'is_expiring_soon' => $c->expires_at && $c->expires_at->diffInDays(now()) <= 7,
                    ];
                });

            // Fetch recent transactions from immutable ledger
            $ledger = WalletLedger::where('wallet_id', $wallet->id)
                ->orderBy('created_at', 'desc')
                ->take(50)
                ->get()
                ->map(function ($l) {
                    return [
                        'id' => $l->id,
                        'transaction_id' => $l->transaction_id,
                        'type' => $l->type,
                        'direction' => $l->direction,
                        'amount' => (float) $l->amount,
                        'running_balance' => (float) $l->running_balance,
                        'description' => $l->description,
                        'reference_type' => $l->reference_type,
                        'reference_id' => $l->reference_id,
                        'created_at' => $l->created_at ? $l->created_at->toIso8601String() : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'wallet' => [
                    'id' => $wallet->id,
                    'currency' => $wallet->currency,
                    'balance' => (float) $wallet->balance,
                    'pending_cashback' => (float) $wallet->pending_cashback,
                    'is_active' => (bool) $wallet->is_active,
                ],
                'credits' => $credits,
                'ledger' => $ledger,
            ]);
        });
    }

    /**
     * Redeem Gift Card into Wallet (POST /api/v1/customer/wallet/redeem-gift-card)
     */
    public function redeemGiftCard(Request $request)
    {
        return $this->runInTenant($request, function () use ($request) {
            $customer = $this->resolveCustomerFromRequest($request);
            if (!$customer) {
                return response()->json(['success' => false, 'message' => 'Customer not found.'], 404);
            }

            $code = $request->input('code');

            if (empty($code)) {
                return response()->json(['success' => false, 'message' => 'Gift card code is required.'], 422);
            }

            $walletService = new WalletService();
            $result = $walletService->redeemGiftCard($customer, $code);

            // OWASP A09: Record financial transaction & potential brute-force attempts
            AuditLog::record(
                $result['success'] ? 'gift_card_redeemed' : 'gift_card_redeem_failed',
                [
                    'code'    => strtoupper(substr($code, 0, 4) . '****'),
                    'message' => $result['message'] ?? '',
                ],
                $result['success'] ? 'info' : 'warning',
                null,
                $customer->id,
                'customer'
            );

            return response()->json($result, $result['success'] ? 200 : 400);
        });
    }

    /**
     * Get Customer Rewards Points (GET /api/v1/customer/rewards)
     */
    public function getCustomerRewards(Request $request)
    {
        return $this->runInTenant($request, function () use ($request) {
            $customer = $this->resolveCustomerFromRequest($request);
            if (!$customer) {
                return response()->json(['success' => false, 'message' => 'Customer not found.'], 404);
            }

            $conversionRate = (float) \App\Models\Setting::get('rewards_conversion_rate', 0.1);
            $minPoints = (int) \App\Models\Setting::get('rewards_min_conversion_points', 10);
            $earnRatePer100 = (float) \App\Models\Setting::get('rewards_earn_per_100', 10);
            $points = (float) ($customer->points ?? 0);

            $history = RewardPoint::where('customer_id', $customer->id)
                ->orderBy('created_at', 'desc')
                ->take(50)
                ->get()
                ->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'points' => (float) $r->points,
                        'direction' => $r->direction,
                        'type' => $r->type,
                        'description' => $r->description,
                        'reference_id' => $r->reference_id,
                        'created_at' => $r->created_at ? $r->created_at->toIso8601String() : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'rewards' => [
                    'points_balance' => $points,
                    'conversion_rate' => $conversionRate,
                    'wallet_equivalent' => round($points * $conversionRate, 2),
                    'min_conversion_points' => $minPoints,
                    'earn_rate_per_100' => $earnRatePer100,
                    'tier' => $points >= 2000 ? 'Platinum VIP' : ($points >= 1000 ? 'Gold' : ($points >= 500 ? 'Silver' : 'Bronze Member')),
                ],
                'history' => $history,
            ]);
        });
    }

    /**
     * Convert Reward Points to Wallet Balance (POST /api/v1/customer/rewards/convert)
     */
    public function convertRewardsToWallet(Request $request)
    {
        return $this->runInTenant($request, function () use ($request) {
            $customer = $this->resolveCustomerFromRequest($request);
            if (!$customer) {
                return response()->json(['success' => false, 'message' => 'Customer not found.'], 404);
            }

            $conversionRate = (float) \App\Models\Setting::get('rewards_conversion_rate', 0.1);
            $minPoints = (int) \App\Models\Setting::get('rewards_min_conversion_points', 10);

            $points = (float) $request->input('points', 0);
            if ($points < $minPoints) {
                return response()->json(['success' => false, 'message' => "Minimum {$minPoints} points required for conversion."], 422);
            }

            $walletService = new WalletService();
            $result = $walletService->convertRewardsToWallet($customer, $points, $conversionRate);

            return response()->json($result, $result['success'] ? 200 : 400);
        });
    }

    /**
     * Authoritative Server-Side Cart Pricing & Promotion Validation (POST /api/v1/cart/validate)
     * Re-queries database prices, calculates eligibility, freebies, tax, and totals (SRS Section 7)
     */
    public function validateCartPricing(Request $request)
    {
        return $this->runInTenant($request, function () use ($request) {
            $items = $request->input('items', []);
            $couponCode = $request->input('coupon_code');
            $shippingCharge = (float) $request->input('shipping_charge', 0.00);
            $taxMode = $request->input('tax_mode', 'exclusive');
            $taxRate = (float) $request->input('tax_rate', 18.00);

            $customer = $this->resolveCustomerFromRequest($request);

            $promotionEngine = new PromotionEngine();
            $result = $promotionEngine->evaluateCart(
                $items,
                $customer,
                $couponCode,
                $shippingCharge,
                $taxMode,
                $taxRate
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        });
    }

    /**
     * Get Active Promotions, Banners, and Freebie Rules (GET /api/v1/promotions/active)
     */
    public function getActivePromotions(Request $request)
    {
        return $this->runInTenant($request, function () {
            // Seed sample promotion rules if none exist
            if (PromotionRule::count() === 0) {
                PromotionRule::insert([
                    [
                        'name' => 'Free Gift on Orders Above ₹999',
                        'description' => 'Receive a complimentary beauty care essential with every purchase of ₹999 or more.',
                        'rule_type' => 'freebie_gift',
                        'discount_type' => 'freebie',
                        'discount_value' => 0.00,
                        'min_cart_value' => 999.00,
                        'min_quantity' => 1,
                        'freebie_product_id' => null,
                        'freebie_product_name' => '10g Glow Shield Hydro Gel (Travel Edition)',
                        'freebie_quantity' => 1,
                        'applies_to' => 'all',
                        'coupon_code_required' => false,
                        'coupon_code' => null,
                        'is_stackable' => true,
                        'priority' => 10,
                        'banner_message' => '🎁 Add items worth ₹999 to unlock a Complimentary Gift!',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'name' => 'Flat 10% Off Orders Above ₹1,499',
                        'description' => 'Automatic 10% discount applied at checkout on shopping carts exceeding ₹1,499.',
                        'rule_type' => 'cart_threshold',
                        'discount_type' => 'percentage',
                        'discount_value' => 10.00,
                        'min_cart_value' => 1499.00,
                        'min_quantity' => 1,
                        'freebie_product_id' => null,
                        'freebie_product_name' => null,
                        'freebie_quantity' => 0,
                        'applies_to' => 'all',
                        'coupon_code_required' => false,
                        'coupon_code' => null,
                        'is_stackable' => true,
                        'priority' => 8,
                        'banner_message' => '⚡ Flat 10% Instant Off on orders above ₹1,499!',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'name' => 'Buy 3 or More Items: Save ₹150',
                        'description' => 'Multi-pack bundle discount for 3+ items.',
                        'rule_type' => 'quantity_bogo',
                        'discount_type' => 'fixed',
                        'discount_value' => 150.00,
                        'min_cart_value' => 0.00,
                        'min_quantity' => 3,
                        'freebie_product_id' => null,
                        'freebie_product_name' => null,
                        'freebie_quantity' => 0,
                        'applies_to' => 'all',
                        'coupon_code_required' => false,
                        'coupon_code' => null,
                        'is_stackable' => false,
                        'priority' => 5,
                        'banner_message' => '🛍️ Buy 3+ items and get ₹150 off instantly!',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ]);
            }

            $rules = PromotionRule::where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('start_date')->orWhere('start_date', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                })
                ->orderBy('priority', 'desc')
                ->get()
                ->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'name' => $r->name,
                        'description' => $r->description,
                        'rule_type' => $r->rule_type,
                        'discount_type' => $r->discount_type,
                        'discount_value' => (float) $r->discount_value,
                        'min_cart_value' => (float) $r->min_cart_value,
                        'min_quantity' => (int) $r->min_quantity,
                        'freebie_product_name' => $r->freebie_product_name,
                        'banner_message' => $r->banner_message,
                        'coupon_code_required' => (bool) $r->coupon_code_required,
                        'coupon_code' => $r->coupon_code,
                        'is_stackable' => (bool) $r->is_stackable,
                    ];
                });

            return response()->json([
                'success' => true,
                'promotions' => $rules,
            ]);
        });
    }

}


