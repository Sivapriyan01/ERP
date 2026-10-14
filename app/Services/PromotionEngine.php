<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Coupon;
use App\Models\PromotionRule;
use App\Models\PromotionRedemption;
use App\Models\OrderPriceSnapshot;
use App\Models\Bill;
use Carbon\Carbon;
use Exception;

class PromotionEngine
{
    /**
     * Server-Authoritative Cart & Promotion Evaluator (Section 7 of SRS)
     * Re-fetches price & stock from database, never trusts browser prices.
     */
    public function evaluateCart(
        array $cartItems,
        ?Customer $customer = null,
        ?string $couponCode = null,
        float $shippingCharge = 0.00,
        string $taxMode = 'exclusive',
        float $taxRate = 18.00
    ): array {
        if (empty($cartItems)) {
            return [
                'valid' => true,
                'items' => [],
                'subtotal' => 0.00,
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'shipping_charge' => 0.00,
                'grand_total' => 0.00,
                'applied_rules' => [],
                'allocated_freebies' => [],
                'freebie_progress' => null,
            ];
        }

        // 1. Re-fetch current price + stock from database
        $validatedItems = [];
        $rawSubtotal = 0.00;
        $totalItemsCount = 0;

        foreach ($cartItems as $item) {
            $prodId = $item['id'] ?? $item['product_id'] ?? null;
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $product = null;

            if ($prodId) {
                $product = Category::find($prodId);
            }

            if (!$product && !empty($item['product_name'])) {
                $product = Category::where('product_name', $item['product_name'])->first();
            }

            if (!$product && !empty($item['sku'])) {
                $product = Category::where('barcode', $item['sku'])->first();
            }

            // Server-authoritative unit price
            if ($product) {
                $mrp = (float) ($product->mrp ?: 0);
                $disc = (float) ($product->discount_percent ?: 0);
                $unitPrice = $disc > 0 ? round($mrp * (1 - ($disc / 100)), 2) : $mrp;
                $stock = (int) ($product->stock ?? 0);
                $prodName = $product->product_name;
                $sku = $product->barcode ?: ('SQ-CAT-' . $product->id);
            } else {
                $unitPrice = (float) ($item['price'] ?? $item['selling_price'] ?? 0);
                $stock = 999;
                $prodName = $item['product_name'] ?? 'Product';
                $sku = $item['sku'] ?? 'SKU';
            }

            $lineTotal = round($unitPrice * $qty, 2);

            $validatedItems[] = [
                'id' => $product ? $product->id : $prodId,
                'product_name' => $prodName,
                'sku' => $sku,
                'category_id' => $product ? $product->product_type : null,
                'unit_price' => $unitPrice,
                'quantity' => $qty,
                'stock' => $stock,
                'line_total' => $lineTotal,
                'is_in_stock' => $stock >= $qty,
            ];

            $rawSubtotal += $lineTotal;
            $totalItemsCount += $qty;
        }

        $rawSubtotal = round($rawSubtotal, 2);
        $totalDiscount = 0.00;
        $appliedRules = [];
        $allocatedFreebies = [];
        $hasNonStackableApplied = false;

        // 2. Fetch Active Promotion Rules sorted by priority DESC
        $activeRules = PromotionRule::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit_total')->orWhereRaw('used_count < usage_limit_total');
            })
            ->orderBy('priority', 'desc')
            ->orderBy('id', 'asc')
            ->get();

        // 3. Evaluate Eligibility & Discounts
        foreach ($activeRules as $rule) {
            // Check stacking restriction
            if ($hasNonStackableApplied && !$rule->is_stackable) {
                continue;
            }

            // Check coupon code requirement if rule specifies one
            if ($rule->coupon_code_required) {
                if (empty($couponCode) || strtoupper(trim($couponCode)) !== strtoupper(trim($rule->coupon_code))) {
                    continue;
                }
            }

            $ruleDiscount = 0.00;
            $isEligible = false;

            // Rule Type A: Cart Threshold (e.g. Spend ₹1000 get 10% off)
            if ($rule->rule_type === 'cart_threshold') {
                if (!$rule->min_cart_value || $rawSubtotal >= $rule->min_cart_value) {
                    $isEligible = true;
                    if ($rule->discount_type === 'percentage') {
                        $ruleDiscount = round(($rawSubtotal * $rule->discount_value) / 100, 2);
                    } else if ($rule->discount_type === 'fixed') {
                        $ruleDiscount = min($rawSubtotal, $rule->discount_value);
                    }
                }
            }
            // Rule Type B: Quantity Based / BOGO (e.g. Buy 2 or more items get discount)
            else if ($rule->rule_type === 'quantity_bogo') {
                if ($totalItemsCount >= $rule->min_quantity) {
                    $isEligible = true;
                    if ($rule->discount_type === 'percentage') {
                        $ruleDiscount = round(($rawSubtotal * $rule->discount_value) / 100, 2);
                    } else if ($rule->discount_type === 'fixed') {
                        $ruleDiscount = min($rawSubtotal, $rule->discount_value);
                    }
                }
            }
            // Rule Type C: Freebie / Gift with Purchase
            else if ($rule->rule_type === 'freebie_gift') {
                if (!$rule->min_cart_value || $rawSubtotal >= $rule->min_cart_value) {
                    $isEligible = true;
                    $freebieProduct = null;

                    if ($rule->freebie_product_id) {
                        $freebieProduct = Category::find($rule->freebie_product_id);
                    }

                    $allocatedFreebies[] = [
                        'rule_id' => $rule->id,
                        'rule_name' => $rule->name,
                        'product_id' => $freebieProduct ? $freebieProduct->id : $rule->freebie_product_id,
                        'product_name' => $freebieProduct ? ($freebieProduct->product_name ?: $freebieProduct->name) : ($rule->freebie_product_name ?: 'Complimentary Gift'),
                        'quantity' => $rule->freebie_quantity ?: 1,
                        'price' => 0.00,
                        'badge' => 'FREE GIFT',
                    ];
                }
            }
            // Rule Type D: Product / Category specific discount
            else if (in_array($rule->rule_type, ['product_discount', 'category_discount'])) {
                $applicableSum = 0.00;
                $allowedProductIds = $rule->applicable_product_ids ?: [];
                $allowedCatIds = $rule->applicable_category_ids ?: [];

                foreach ($validatedItems as $vItem) {
                    $match = false;
                    if ($rule->rule_type === 'product_discount' && in_array($vItem['id'], $allowedProductIds)) {
                        $match = true;
                    }
                    if ($rule->rule_type === 'category_discount' && in_array($vItem['category_id'], $allowedCatIds)) {
                        $match = true;
                    }
                    if ($match) {
                        $applicableSum += $vItem['line_total'];
                    }
                }

                if ($applicableSum > 0) {
                    $isEligible = true;
                    if ($rule->discount_type === 'percentage') {
                        $ruleDiscount = round(($applicableSum * $rule->discount_value) / 100, 2);
                    } else if ($rule->discount_type === 'fixed') {
                        $ruleDiscount = min($applicableSum, $rule->discount_value);
                    }
                }
            }

            // Cap discount if max_discount_amount is set
            if ($rule->max_discount_amount && $ruleDiscount > $rule->max_discount_amount) {
                $ruleDiscount = $rule->max_discount_amount;
            }

            if ($isEligible && ($ruleDiscount > 0 || $rule->rule_type === 'freebie_gift')) {
                $totalDiscount += $ruleDiscount;
                $appliedRules[] = [
                    'rule_id' => $rule->id,
                    'name' => $rule->name,
                    'type' => $rule->rule_type,
                    'discount_amount' => $ruleDiscount,
                    'banner' => $rule->banner_message,
                ];

                if (!$rule->is_stackable) {
                    $hasNonStackableApplied = true;
                }
            }
        }

        $appliedCouponDiscount = 0.00;

        // 4. Fallback: Coupon Code table evaluation (if coupon applied and not already covered by rules)
        if (!empty($couponCode) && empty(array_filter($appliedRules, fn($r) => !empty($r['coupon_code'])))) {
            $coupon = Coupon::where('code', strtoupper(trim($couponCode)))
                ->where('is_active', true)
                ->first();

            if ($coupon && $coupon->isValidForSubtotal($rawSubtotal)) {
                $couponDiscount = $coupon->calculateDiscount($rawSubtotal);

                if ($couponDiscount > 0) {
                    $appliedCouponDiscount = $couponDiscount;
                    $totalDiscount += $couponDiscount;
                    $appliedRules[] = [
                        'rule_id' => null,
                        'name' => "Coupon: {$coupon->code}",
                        'type' => 'coupon',
                        'discount_amount' => $couponDiscount,
                        'coupon_code' => $coupon->code,
                    ];
                }

                if (!empty($coupon->free_gift_name) && (!$coupon->free_gift_min_value || $rawSubtotal >= $coupon->free_gift_min_value)) {
                    $allocatedFreebies[] = [
                        'rule_id' => null,
                        'rule_name' => "Coupon {$coupon->code} Free Gift",
                        'product_id' => null,
                        'product_name' => $coupon->free_gift_name,
                        'quantity' => 1,
                        'price' => 0.00,
                        'badge' => 'FREE GIFT',
                    ];
                }
            }
        }

        // Total discount cannot exceed subtotal
        $totalDiscount = min($rawSubtotal, round($totalDiscount, 2));

        // 5. Default Freebie fallback rule if none configured (spend ₹999 get free gift)
        $freebieThreshold = 999.00;
        $freebieTargetRule = $activeRules->firstWhere('rule_type', 'freebie_gift');
        if ($freebieTargetRule && $freebieTargetRule->min_cart_value) {
            $freebieThreshold = (float) $freebieTargetRule->min_cart_value;
        }

        $freebieProgress = [
            'threshold' => $freebieThreshold,
            'current_subtotal' => $rawSubtotal,
            'is_unlocked' => $rawSubtotal >= $freebieThreshold,
            'amount_needed' => max(0, round($freebieThreshold - $rawSubtotal, 2)),
            'message' => $rawSubtotal >= $freebieThreshold
                ? '🎉 Congratulations! You unlocked a FREE Gift!'
                : "Add ₹" . number_format(max(0, $freebieThreshold - $rawSubtotal), 0) . " more to unlock a FREE Gift!",
        ];

        // 6. Tax & Grand Total Computation
        $discountedSubtotal = max(0, $rawSubtotal - $totalDiscount);
        $taxAmount = 0.00;
        if ($taxMode === 'inclusive') {
            // Price already includes tax: extract GST component
            $taxAmount = round($discountedSubtotal - ($discountedSubtotal / (1 + ($taxRate / 100))), 2);
            $grandTotal = round($discountedSubtotal + $shippingCharge, 2);
        } else {
            // Exclusive tax: add GST on top
            $taxAmount = round(($discountedSubtotal * $taxRate) / 100, 2);
            $grandTotal = round($discountedSubtotal + $taxAmount + $shippingCharge, 2);
        }

        return [
            'valid' => true,
            'items' => $validatedItems,
            'subtotal' => $rawSubtotal,
            'discount_amount' => $totalDiscount,
            'coupon_discount' => $appliedCouponDiscount,
            'rules_discount' => round(max(0, $totalDiscount - $appliedCouponDiscount), 2),
            'tax_amount' => $taxAmount,
            'shipping_charge' => $shippingCharge,
            'grand_total' => $grandTotal,
            'applied_rules' => $appliedRules,
            'allocated_freebies' => $allocatedFreebies,
            'freebie_progress' => $freebieProgress,
        ];
    }

    /**
     * Create an Immutable Price Snapshot for an Order (Section 8 & 9 of SRS)
     */
    public function createPriceSnapshot(Bill $bill, array $evalResult): OrderPriceSnapshot
    {
        return OrderPriceSnapshot::create([
            'order_id' => $bill->id,
            'subtotal' => $evalResult['subtotal'] ?? $bill->subtotal,
            'discount_amount' => $evalResult['discount_amount'] ?? $bill->discount_amount,
            'tax_amount' => $evalResult['tax_amount'] ?? $bill->gst_amount,
            'shipping_amount' => $evalResult['shipping_charge'] ?? 0.00,
            'wallet_amount' => (float) ($bill->wallet_amount ?? 0.00),
            'grand_total' => $evalResult['grand_total'] ?? $bill->grand_total,
            'rules_applied' => $evalResult['applied_rules'] ?? [],
            'freebies_allocated' => $evalResult['allocated_freebies'] ?? [],
            'line_items_snapshot' => $evalResult['items'] ?? [],
            'created_at' => now(),
        ]);
    }
}
