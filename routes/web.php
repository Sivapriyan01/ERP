<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\SuperAdminLoginController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\TenantController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Central Domain Redirects (Fix for 404/Collision with tenant routes)
foreach (['localhost', '127.0.0.1', 'square.in'] as $domain) {
    Route::domain($domain)->get('/', function () {
        return redirect()->route('superadmin.login');
    });
    Route::domain($domain)->get('/login', function () {
        return redirect()->route('superadmin.login');
    });
    Route::domain($domain)->get('/dashboard', function () {
        return redirect()->route('superadmin.dashboard');
    });
}



Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::group([
    'middleware' => ['web'],
], function () {
    require __DIR__.'/auth.php';

    // routes/web.php — Central Routes (Super Admin)

    /*
    |--------------------------------------------------------------------------
    | Central Web Routes — Super Admin only
    | Accessed via main domain: localhost or square.in
    |--------------------------------------------------------------------------
    */

    // Redirect root to super admin login
    Route::get('/', function () {
        return redirect()->route('superadmin.login');
    });

    // Super Admin Auth
    Route::prefix('superadmin')->name('superadmin.')->group(function () {

        // Guest routes (not logged in)
        Route::middleware('guest:web')->group(function () {
            Route::get('/login',  [SuperAdminLoginController::class, 'showLoginForm'])
                 ->name('login');
            Route::post('/login', [SuperAdminLoginController::class, 'login'])
                 ->name('login.post');
        });

        // Authenticated routes
        Route::middleware(['auth:web', 'superadmin'])->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])
                 ->name('dashboard');

            // Tenant management
            Route::resource('tenants', TenantController::class);
            Route::post('tenants/{tenant}/toggle-status', [TenantController::class, 'toggleStatus'])
                 ->name('tenants.toggle-status');
            Route::get('tenants/{tenant}/backup', [TenantController::class, 'backup'])
                 ->name('tenants.backup');
            Route::post('tenants/{tenant}/restore', [TenantController::class, 'restore'])
                 ->name('tenants.restore');

            // Settings
            Route::get('/settings', [\App\Http\Controllers\SuperAdmin\SettingsController::class, 'index'])
                 ->name('settings.index');
            Route::post('/settings', [\App\Http\Controllers\SuperAdmin\SettingsController::class, 'update'])
                 ->name('settings.update');
        });

        Route::post('/logout', [SuperAdminLoginController::class, 'logout'])
             ->name('logout')
             ->middleware('auth:web');
    });
});

// Central fallback for tenant assets on central domains or unauthenticated storefront requests
Route::get('/tenancy/assets/{path}', function (\Illuminate\Http\Request $request, $path) {
    $cleanPath = ltrim($path, '/');
    $tenantId = $request->input('tenant') ?: ($request->header('X-Tenant') ?: null);
    if (! $tenantId) {
        $host = $request->getHost();
        $parts = explode('.', $host);
        if (count($parts) > 1 && ! in_array($parts[0], ['localhost', '127', 'www'])) {
            $tenantId = $parts[0];
        }
    }
    if ($tenantId) {
        $tenantPath = base_path("storage/tenant{$tenantId}/app/public/{$cleanPath}");
        if (file_exists($tenantPath) && is_file($tenantPath)) {
            $mime = mime_content_type($tenantPath) ?: 'application/octet-stream';
            return response()->file($tenantPath, ['Content-Type' => $mime, 'Cache-Control' => 'public, max-age=86400']);
        }
    }
    $matches = glob(base_path("storage/tenant*/app/public/{$cleanPath}"));
    if (! empty($matches) && file_exists($matches[0]) && is_file($matches[0])) {
        $mime = mime_content_type($matches[0]) ?: 'application/octet-stream';
        return response()->file($matches[0], ['Content-Type' => $mime, 'Cache-Control' => 'public, max-age=86400']);
    }
    abort(404);
})->where('path', '.*');