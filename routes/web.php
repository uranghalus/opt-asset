<?php

use App\Http\Controllers\SamlController;
use App\Http\Controllers\TenantSwitchController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('saml.redirect');
    }

    // Route by membership (T01c): platform admins belong in the platform
    // area — sending them to 'dashboard' would hit the `tenant` middleware
    // and 403 — while members land on the dashboard.
    return auth()->user()->isPlatformAdmin()
        ? redirect()->route('platform.tenants.index')
        : redirect()->route('dashboard');
})->name('home');

Route::prefix('saml')->group(function () {
    Route::get('redirect', [SamlController::class, 'redirect'])->name('saml.redirect');
    Route::match(['get', 'post'], 'acs', [SamlController::class, 'acs'])->name('saml.acs');
    Route::get('sls', [SamlController::class, 'sls'])->name('saml.sls');
    Route::get('logout', [SamlController::class, 'sls'])->name('saml.logout');
    Route::get('metadata', [SamlController::class, 'metadata'])->name('saml.metadata');
});

Route::post('logout', function () {
    auth()->logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('home');
})->name('logout')->middleware('auth');

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

// Tenant switching (T01c/T01d) sits OUTSIDE the `tenant` middleware:
// switching is how a superadmin first ACQUIRES a context, so the request
// must not be redirected for lacking one. Target validation (membership
// or superadmin, active tenant) and the audit row happen in the controller.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('tenant/switch', [TenantSwitchController::class, 'store'])
        ->name('tenant.switch');
});

require __DIR__.'/settings.php';
