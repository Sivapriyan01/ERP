<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * Display listing of coupons
     */
    public function index(Request $request)
    {
        $query = Coupon::query();

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('code', 'like', "%{$s}%")
                  ->orWhere('name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->input('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $coupons = $query->latest()->paginate(15)->withQueryString();

        // Seed default Derma Co style coupons if none exist yet
        if (Coupon::count() === 0) {
            Coupon::create([
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
            ]);

            Coupon::create([
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
            ]);

            Coupon::create([
                'code' => 'DERMA20',
                'name' => 'Welcome Derma 20%',
                'description' => 'Flat 20% discount on all active orders above ₹499.',
                'discount_type' => 'percent',
                'discount_value' => 20,
                'min_order_value' => 499,
                'max_discount' => 400,
                'is_active' => true,
            ]);

            Coupon::create([
                'code' => 'PREPAID5',
                'name' => 'Extra 5% Prepaid Discount',
                'description' => 'Instant 5% discount on UPI & Online prepaid payments.',
                'discount_type' => 'percent',
                'discount_value' => 5,
                'min_order_value' => 0,
                'max_discount' => 200,
                'is_active' => true,
            ]);

            $coupons = Coupon::latest()->paginate(15);
        }

        $totalCoupons = Coupon::count();
        $activeCoupons = Coupon::where('is_active', true)->count();
        $totalRedemptions = Coupon::sum('used_count');

        $settings = [
            'website_coupon_modal_title' => \App\Models\Setting::get('website_coupon_modal_title', 'Apply Coupon & Offers'),
            'website_coupon_modal_subtitle' => \App\Models\Setting::get('website_coupon_modal_subtitle', 'The Derma Co Verified Promo Codes'),
            'website_free_shipping_threshold' => \App\Models\Setting::get('website_free_shipping_threshold', '999'),
            'website_free_gift_threshold' => \App\Models\Setting::get('website_free_gift_threshold', '899'),
            'website_free_gift_name' => \App\Models\Setting::get('website_free_gift_name', 'Free 10% Niacinamide Serum Mini 5ml'),
            'website_free_gift_image' => \App\Models\Setting::get('website_free_gift_image', '/gift-icon.png'),
            'website_prepaid_discount_percent' => \App\Models\Setting::get('website_prepaid_discount_percent', '5'),
        ];

        return view('tenant.coupons.index', compact('coupons', 'totalCoupons', 'activeCoupons', 'totalRedemptions', 'settings'));
    }

    /**
     * Store new coupon
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:coupons,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percent,fixed',
            'discount_value' => 'required|numeric|min:0.01',
            'min_order_value' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'free_gift_name' => 'nullable|string|max:255',
            'free_gift_min_value' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['min_order_value'] = $validated['min_order_value'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);

        Coupon::create($validated);

        return redirect()->route('tenant.coupons.index')->with('success', "Coupon '{$validated['code']}' created successfully.");
    }

    /**
     * Update existing coupon dynamically
     */
    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:coupons,code,' . $coupon->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percent,fixed',
            'discount_value' => 'required|numeric|min:0.01',
            'min_order_value' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'free_gift_name' => 'nullable|string|max:255',
            'free_gift_min_value' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
            'is_active' => 'nullable',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['min_order_value'] = $validated['min_order_value'] ?? 0;
        $validated['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : $coupon->is_active;

        $coupon->update($validated);

        return redirect()->route('tenant.coupons.index')->with('success', "Coupon '{$coupon->code}' updated successfully.");
    }

    /**
     * Update storefront promotional modal & discount settings
     */
    public function updateSettings(Request $request)
    {
        $keys = [
            'website_coupon_modal_title',
            'website_coupon_modal_subtitle',
            'website_free_shipping_threshold',
            'website_free_gift_threshold',
            'website_free_gift_name',
            'website_prepaid_discount_percent',
        ];

        foreach ($keys as $key) {
            if ($request->has($key)) {
                \App\Models\Setting::set($key, (string) $request->input($key, ''), 'website');
            }
        }

        if ($request->hasFile('website_free_gift_image')) {
            $path = $request->file('website_free_gift_image')->store('website/gifts', 'public');
            \App\Models\Setting::set('website_free_gift_image', '/storage/' . $path, 'website');
        }

        return redirect()->route('tenant.coupons.index')->with('success', 'Storefront coupon modal and offer settings updated successfully.');
    }

    /**
     * Toggle coupon active state
     */
    public function toggle(Coupon $coupon)
    {
        $coupon->update(['is_active' => !$coupon->is_active]);
        $status = $coupon->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Coupon '{$coupon->code}' has been {$status}.");
    }

    /**
     * Delete coupon
     */
    public function destroy(Coupon $coupon)
    {
        $code = $coupon->code;
        $coupon->delete();
        return back()->with('success', "Coupon '{$code}' deleted successfully.");
    }
}
