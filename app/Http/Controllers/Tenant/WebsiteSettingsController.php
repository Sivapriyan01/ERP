<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class WebsiteSettingsController extends Controller
{
    /**
     * Display the website settings form
     */
    public function index()
    {
        $settings = [
            // MSG91 & SMS Gateway Settings
            'msg91_widget_id'          => Setting::get('msg91_widget_id', config('services.msg91.template_id', env('MSG91_TEMPLATE_ID', '36697164476b323432353839'))),
            'msg91_token_auth'         => Setting::get('msg91_token_auth', config('services.msg91.auth_key', env('MSG91_AUTH_KEY', '572040TDOpHdLN6aab6e64P1'))),
            'msg91_auth_key'           => Setting::get('msg91_auth_key', config('services.msg91.auth_key', env('MSG91_AUTH_KEY', ''))),
            'msg91_template_id'        => Setting::get('msg91_template_id', config('services.msg91.template_id', env('MSG91_TEMPLATE_ID', ''))),
            'msg91_dlt_sender_id'      => Setting::get('msg91_dlt_sender_id', env('MSG91_SENDER_ID', '')),
            'msg91_otp_expiry'         => Setting::get('msg91_otp_expiry', '10'),
            'msg91_otp_length'         => Setting::get('msg91_otp_length', '4'),
            'website_otp_required'     => Setting::get('website_otp_required', '1'),

            // Customer Wallet & Loyalty Rewards Program Settings
            'wallet_enabled'           => Setting::get('wallet_enabled', '1'),
            'wallet_cashback_percent'  => Setting::get('wallet_cashback_percent', '5'),
            'wallet_cashback_hold_days'=> Setting::get('wallet_cashback_hold_days', '7'),
            'wallet_max_usage_percent' => Setting::get('wallet_max_usage_percent', '100'),
            'rewards_conversion_rate'  => Setting::get('rewards_conversion_rate', '0.1'),
            'rewards_coins_per_rupee'  => (string) ((float) Setting::get('rewards_conversion_rate', '0.1') > 0 ? round(1 / (float) Setting::get('rewards_conversion_rate', '0.1'), 1) : 10),
            'rewards_earn_per_100'     => Setting::get('rewards_earn_per_100', '10'),
            'rewards_min_conversion_points' => Setting::get('rewards_min_conversion_points', '10'),

            // Storefront Identity & Policies
            'website_store_name'        => Setting::get('website_store_name', 'Square Store'),
            'website_support_phone'     => Setting::get('website_support_phone', '6383272563'),
            'website_support_email'     => Setting::get('website_support_email', 'support@squarestore.in'),
            'website_free_delivery_min' => Setting::get('website_free_delivery_min', '999'),
            'website_shipping_fee'      => Setting::get('website_shipping_fee', '99'),
            'website_discount_percent'  => Setting::get('website_discount_percent', '0'),
            'website_announcement'      => Setting::get('website_announcement', '🎉 FREE Delivery on orders above ₹999! Verified Authentic Stock.'),
            'website_primary_color'     => Setting::get('website_primary_color', '#10b981'),
            'website_accent_color'      => Setting::get('website_accent_color', '#059669'),
            'website_font_family'       => Setting::get('website_font_family', 'Inter'),
            'website_hero_title'        => Setting::get('website_hero_title', 'Seamless Shopping. Instant ERP Fulfillment.'),
            'website_hero_subtitle'     => Setting::get('website_hero_subtitle', 'Browse authentic products with live warehouse stock sync, instant GST-compliant invoicing, and automated courier dispatch.'),
            'website_hero_badge'        => Setting::get('website_hero_badge', 'Official ERP Connected Store'),
            'website_business_type'     => Setting::get('website_business_type', 'Clothing Store'),
            'website_positioning'       => Setting::get('website_positioning', 'sustainable fashion'),
            'website_palette_name'      => Setting::get('website_palette_name', 'Sage'),
            'website_layout_style'      => Setting::get('website_layout_style', 'split'),
            'website_border_radius'     => Setting::get('website_border_radius', '16px'),
            'website_header_style'      => Setting::get('website_header_style', 'regular'),
            'website_shadow_style'      => Setting::get('website_shadow_style', 'small'),

            // Derma Co Promotional & Coupon Drawer Settings
            'website_coupon_modal_title'       => Setting::get('website_coupon_modal_title', 'Apply Coupon & Offers'),
            'website_coupon_modal_subtitle'    => Setting::get('website_coupon_modal_subtitle', 'The Derma Co Verified Promo Codes'),
            'website_free_shipping_threshold'  => Setting::get('website_free_shipping_threshold', '999'),
            'website_free_gift_threshold'      => Setting::get('website_free_gift_threshold', '899'),
            'website_free_gift_name'           => Setting::get('website_free_gift_name', 'Free 10% Niacinamide Serum Mini 5ml'),
            'website_prepaid_discount_percent' => Setting::get('website_prepaid_discount_percent', '5'),

            // Gift Cards & Digital Vouchers Settings
            'website_gift_card_enabled'              => Setting::get('website_gift_card_enabled', '1'),
            'website_gift_card_checkout_redeem'     => Setting::get('website_gift_card_checkout_redeem', '1'),
            'website_gift_card_pos_redeem'          => Setting::get('website_gift_card_pos_redeem', '1'),
            'website_gift_card_default_validity_days'=> Setting::get('website_gift_card_default_validity_days', '365'),
            'website_gift_card_min_purchase'        => Setting::get('website_gift_card_min_purchase', '100'),
            'website_gift_card_max_purchase'        => Setting::get('website_gift_card_max_purchase', '10000'),
        ];

        // Auto-seed starter coupons if empty
        if (\App\Models\Coupon::count() === 0) {
            $this->seedStarterCoupons();
        }

        // Auto-seed starter gift cards if empty
        if (\App\Models\GiftCard::count() === 0) {
            $this->seedStarterGiftCards();
        }

        $coupons = \App\Models\Coupon::latest()->get();
        $giftCards = \App\Models\GiftCard::with('redeemedByCustomer')->latest()->take(6)->get();
        $giftCardStats = [
            'total'   => \App\Models\GiftCard::count(),
            'active'  => \App\Models\GiftCard::where('status', 'active')->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count(),
            'balance' => (float) \App\Models\GiftCard::where('status', 'active')->sum('current_balance'),
            'redeemed'=> \App\Models\GiftCard::where('status', 'redeemed')->count(),
        ];

        return view('tenant.website_settings.index', compact('settings', 'coupons', 'giftCards', 'giftCardStats'));
    }

    /**
     * Update website settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            // SMS & OTP
            'msg91_widget_id'          => 'nullable|string|max:100',
            'msg91_token_auth'         => 'nullable|string|max:100',
            'msg91_auth_key'           => 'nullable|string|max:100',
            'msg91_template_id'        => 'nullable|string|max:100',
            'msg91_dlt_sender_id'      => 'nullable|string|max:20',
            'msg91_otp_expiry'         => 'nullable|integer|min:1|max:60',
            'msg91_otp_length'         => 'nullable|integer|in:4,6',
            'website_otp_required'     => 'nullable|in:0,1',

            // Customer Wallet & Loyalty Rewards
            'wallet_enabled'           => 'nullable|in:0,1',
            'wallet_cashback_percent'  => 'nullable|numeric|min:0|max:100',
            'wallet_cashback_hold_days'=> 'nullable|integer|min:0|max:365',
            'wallet_max_usage_percent' => 'nullable|numeric|min:0|max:100',
            'rewards_coins_per_rupee'  => 'nullable|numeric|min:0.1|max:1000',
            'rewards_conversion_rate'  => 'nullable|numeric|min:0.0001|max:10',
            'rewards_earn_per_100'     => 'nullable|numeric|min:0|max:1000',
            'rewards_min_conversion_points' => 'nullable|integer|min:1|max:10000',

            // Storefront Identity & Policies
            'website_store_name'        => 'nullable|string|max:150',
            'website_support_phone'     => 'nullable|string|max:20',
            'website_support_email'     => 'nullable|email|max:150',
            'website_free_delivery_min' => 'nullable|numeric|min:0',
            'website_shipping_fee'      => 'nullable|numeric|min:0',
            'website_discount_percent'  => 'nullable|numeric|min:0|max:100',
            'website_announcement'      => 'nullable|string|max:255',
            'website_primary_color'     => 'nullable|string|max:20',
            'website_accent_color'      => 'nullable|string|max:20',
            'website_font_family'       => 'nullable|string|max:50',
            'website_hero_title'        => 'nullable|string|max:255',
            'website_hero_subtitle'     => 'nullable|string|max:500',
            'website_hero_badge'        => 'nullable|string|max:100',
            'website_card_style'        => 'nullable|string|max:50',
            'website_business_type'     => 'nullable|string|max:100',
            'website_positioning'       => 'nullable|string|max:100',
            'website_palette_name'      => 'nullable|string|max:50',
            'website_layout_style'      => 'nullable|string|max:50',
            'website_border_radius'     => 'nullable|string|max:20',
            'website_header_style'      => 'nullable|string|max:50',
            'website_shadow_style'      => 'nullable|string|max:50',

            // Derma Co Promotional
            'website_coupon_modal_title'       => 'nullable|string|max:255',
            'website_coupon_modal_subtitle'    => 'nullable|string|max:255',
            'website_free_shipping_threshold'  => 'nullable|numeric|min:0',
            'website_free_gift_threshold'      => 'nullable|numeric|min:0',
            'website_free_gift_name'           => 'nullable|string|max:255',
            'website_prepaid_discount_percent' => 'nullable|numeric|min:0|max:100',

            // Gift Cards & Vouchers
            'website_gift_card_enabled'              => 'nullable|in:0,1',
            'website_gift_card_checkout_redeem'     => 'nullable|in:0,1',
            'website_gift_card_pos_redeem'          => 'nullable|in:0,1',
            'website_gift_card_default_validity_days'=> 'nullable|integer|min:1|max:3650',
            'website_gift_card_min_purchase'        => 'nullable|numeric|min:0',
            'website_gift_card_max_purchase'        => 'nullable|numeric|min:0',
        ]);

        // Auto-calculate rewards_conversion_rate from coins_per_rupee if provided
        if ($request->filled('rewards_coins_per_rupee') && floatval($request->rewards_coins_per_rupee) > 0) {
            $validated['rewards_conversion_rate'] = (string) round(1 / floatval($request->rewards_coins_per_rupee), 4);
        }

        foreach ($validated as $key => $value) {
            Setting::set($key, (string) ($value ?? ''), 'website');
        }

        // Checkbox handling for boolean flags (1 if checked, 0 if unchecked)
        $isOtpRequired = $request->has('website_otp_required') && ($request->input('website_otp_required') === '1' || $request->input('website_otp_required') === 1);
        Setting::set('website_otp_required', $isOtpRequired ? '1' : '0', 'website');

        $isWalletEnabled = $request->has('wallet_enabled') && ($request->input('wallet_enabled') === '1' || $request->input('wallet_enabled') === 1);
        Setting::set('wallet_enabled', $isWalletEnabled ? '1' : '0', 'website');

        $isGiftCardEnabled = $request->has('website_gift_card_enabled') && ($request->input('website_gift_card_enabled') === '1' || $request->input('website_gift_card_enabled') === 1);
        Setting::set('website_gift_card_enabled', $isGiftCardEnabled ? '1' : '0', 'website');

        $isGiftCardCheckout = $request->has('website_gift_card_checkout_redeem') && ($request->input('website_gift_card_checkout_redeem') === '1' || $request->input('website_gift_card_checkout_redeem') === 1);
        Setting::set('website_gift_card_checkout_redeem', $isGiftCardCheckout ? '1' : '0', 'website');

        $isGiftCardPos = $request->has('website_gift_card_pos_redeem') && ($request->input('website_gift_card_pos_redeem') === '1' || $request->input('website_gift_card_pos_redeem') === 1);
        Setting::set('website_gift_card_pos_redeem', $isGiftCardPos ? '1' : '0', 'website');

        return redirect()->route('tenant.website-settings.index')
            ->with('success', 'Website settings, Promo coupons, and Gift Card configurations updated successfully!');
    }

    /**
     * Live test MSG91 SMS dispatch from Admin UI
     */
    public function testSms(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'auth_key' => 'nullable|string',
            'template_id' => 'nullable|string',
            'sender_id' => 'nullable|string',
        ]);

        $phone = preg_replace('/[^0-9]/', '', $request->input('phone'));
        if (strlen($phone) < 10) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid 10-digit mobile number.',
            ], 422);
        }

        $authKey = $request->input('auth_key') ?: Setting::get('msg91_auth_key', env('MSG91_AUTH_KEY', ''));
        $templateId = $request->input('template_id') ?: Setting::get('msg91_template_id', env('MSG91_TEMPLATE_ID', ''));
        $senderId = $request->input('sender_id') ?: Setting::get('msg91_dlt_sender_id', env('MSG91_SENDER_ID', ''));

        if (empty($authKey)) {
            return response()->json([
                'success' => false,
                'message' => 'MSG91 Auth Key is missing. Please enter your Auth Key in settings.',
            ], 422);
        }

        try {
            $msg91 = new \App\Services\Msg91Service($authKey, $templateId);
            $testOtp = (string) rand(1000, 9999);
            $result = $msg91->sendOtp($phone, $testOtp);

            return response()->json([
                'success'  => $result['success'],
                'message'  => $result['message'],
                'test_otp' => $testOtp,
                'phone'    => $phone,
                'data'     => $result['data'] ?? [],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exception occurred while dispatching test SMS: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Seed starter coupons if none exist.
     */
    public function seedStarterCoupons(): void
    {
        $coupons = [
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
            ],
            [
                'code' => 'DERMA20',
                'name' => 'Welcome Derma 20%',
                'description' => 'Flat 20% discount on all active orders above ₹499.',
                'discount_type' => 'percent',
                'discount_value' => 20,
                'min_order_value' => 499,
                'max_discount' => 400,
                'is_active' => true,
            ],
            [
                'code' => 'PREPAID5',
                'name' => 'Extra 5% Prepaid Discount',
                'description' => 'Instant 5% discount on UPI & Online prepaid payments.',
                'discount_type' => 'percent',
                'discount_value' => 5,
                'min_order_value' => 0,
                'max_discount' => 200,
                'is_active' => true,
            ],
        ];

        foreach ($coupons as $data) {
            \App\Models\Coupon::firstOrCreate(['code' => $data['code']], $data);
        }
    }

    /**
     * Seed starter gift cards if none exist.
     */
    public function seedStarterGiftCards(): void
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

        foreach ($cards as $data) {
            \App\Models\GiftCard::firstOrCreate(['code' => $data['code']], $data);
        }
    }

    /**
     * Action to manually seed sample coupons and gift cards on demand.
     */
    public function seedSamples(Request $request)
    {
        $type = $request->input('type', 'all');

        if ($type === 'coupons' || $type === 'all') {
            $this->seedStarterCoupons();
        }

        if ($type === 'gift_cards' || $type === 'all') {
            $this->seedStarterGiftCards();
        }

        $section = $type === 'coupons' ? 'coupons' : ($type === 'gift_cards' ? 'gift_cards' : 'coupons');

        return redirect()->route('tenant.website-settings.index', ['section' => $section])
            ->with('success', 'Starter demo coupons and gift cards have been seeded successfully.');
    }
}

