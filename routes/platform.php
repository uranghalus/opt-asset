<?php

use App\Http\Controllers\Platform\BusinessUnitController;
use App\Http\Middleware\EnsurePlatformAdmin;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', EnsurePlatformAdmin::class])
    ->prefix('platform/business-units')
    ->name('platform.business-units.')
    ->group(function (): void {
        Route::get('/', [BusinessUnitController::class, 'index'])->name('index');
        Route::get('create', [BusinessUnitController::class, 'create'])->name('create');
        Route::post('/', [BusinessUnitController::class, 'store'])->name('store');
        Route::get('{tenant}/edit', [BusinessUnitController::class, 'edit'])->name('edit');
        Route::put('{tenant}', [BusinessUnitController::class, 'update'])->name('update');
        Route::patch('{tenant}/status', [BusinessUnitController::class, 'transition'])->name('transition');
    });
