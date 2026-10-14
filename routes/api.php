<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WebsiteOrderController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ── Square ERP Website Order Management APIs (v1) ────────────────
Route::prefix('v1')->group(function () {
    Route::get('/health',              [WebsiteOrderController::class, 'health']);
    Route::get('/products',            [WebsiteOrderController::class, 'getProducts']);
    Route::post('/orders',             [WebsiteOrderController::class, 'createOrder'])->middleware('api.rate:checkout,10,60');
    Route::get('/orders/{id}',         [WebsiteOrderController::class, 'getOrder']);
    Route::post('/orders/{id}/cancel', [WebsiteOrderController::class, 'cancelOrder']);
    Route::post('/orders/{id}/return', [WebsiteOrderController::class, 'returnOrder']);
    Route::post('/otp/send',           [WebsiteOrderController::class, 'sendOtp'])->middleware('api.rate:otp_request,10,60');
    Route::post('/otp/verify',         [WebsiteOrderController::class, 'verifyOtp'])->middleware('api.rate:otp_verify,15,60');
    Route::get('/coupons',             [WebsiteOrderController::class, 'getCoupons']);
    Route::post('/coupons/validate',    [WebsiteOrderController::class, 'validateCoupon'])->middleware('api.rate:coupon_validate,15,60');
    Route::get('/settings',            [WebsiteOrderController::class, 'getSettings']);
    Route::put('/settings',            [WebsiteOrderController::class, 'updateSettings']);  // Live preview/configurator
    Route::get('/assets/{path}',       [WebsiteOrderController::class, 'getAsset'])->where('path', '.*'); // Serve tenant product & store assets

    // ── Customer Auth & Account Management ───────────────────────────
    Route::post('/customer/login',       [WebsiteOrderController::class, 'customerLogin'])->middleware('api.rate:customer_login,15,60');
    Route::post('/customer/verify-otp',  [WebsiteOrderController::class, 'customerVerifyOtp'])->middleware('api.rate:customer_verify,15,60');
    Route::get('/customer/profile',      [WebsiteOrderController::class, 'getCustomerProfile']);
    Route::post('/customer/profile',     [WebsiteOrderController::class, 'updateCustomerProfile']);
    Route::get('/customer/orders',       [WebsiteOrderController::class, 'getCustomerOrders']);

    // ── Customer Wallet & Rewards (Sections 10 & 17 of SRS) ────────────
    Route::get('/customer/wallet',                  [WebsiteOrderController::class, 'getCustomerWallet']);
    Route::post('/customer/wallet/redeem-gift-card',[WebsiteOrderController::class, 'redeemGiftCard'])->middleware('api.rate:gift_card_redeem,10,60');
    Route::get('/customer/rewards',                 [WebsiteOrderController::class, 'getCustomerRewards']);
    Route::post('/customer/rewards/convert',        [WebsiteOrderController::class, 'convertRewardsToWallet']);

    // ── Promotions, Freebies & Price Rules (Section 7 of SRS) ─────────
    Route::post('/cart/validate',                   [WebsiteOrderController::class, 'validateCartPricing']);
    Route::get('/promotions/active',                [WebsiteOrderController::class, 'getActivePromotions']);

});


