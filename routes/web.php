<?php

use App\Http\Controllers\SamlController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('saml.redirect');
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

require __DIR__.'/settings.php';
