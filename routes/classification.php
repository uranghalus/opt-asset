<?php

use App\Enums\Permission;
use App\Http\Controllers\Classification\AssetCategoryController;
use App\Http\Controllers\Classification\AssetClusterController;
use App\Http\Controllers\Classification\AssetGroupController;
use App\Http\Controllers\Classification\AssetSubClusterController;
use App\Http\Controllers\Classification\ItemController;
use Illuminate\Support\Facades\Route;

// The classification chain is the first production consumer of the RBAC
// machinery: every route runs behind the tenant context AND the
// permission:classifications.manage middleware — the permission string is
// passed as a plain string at the Gate boundary (a BackedEnum directly
// triggers a TypeError inside spatie).
Route::middleware(['auth', 'verified', 'tenant', 'permission:'.Permission::ClassificationsManage->value])
    ->prefix('classifications')
    ->name('classifications.')
    ->group(function (): void {
        Route::get('groups', [AssetGroupController::class, 'index'])->name('groups.index');
        Route::get('groups/create', [AssetGroupController::class, 'create'])->name('groups.create');
        Route::post('groups', [AssetGroupController::class, 'store'])->name('groups.store');
        Route::get('groups/{group}/edit', [AssetGroupController::class, 'edit'])->name('groups.edit');
        Route::put('groups/{group}', [AssetGroupController::class, 'update'])->name('groups.update');
        Route::delete('groups/{group}', [AssetGroupController::class, 'destroy'])->name('groups.destroy');

        Route::get('categories', [AssetCategoryController::class, 'index'])->name('categories.index');
        Route::get('categories/create', [AssetCategoryController::class, 'create'])->name('categories.create');
        Route::post('categories', [AssetCategoryController::class, 'store'])->name('categories.store');
        Route::get('categories/{category}/edit', [AssetCategoryController::class, 'edit'])->name('categories.edit');
        Route::put('categories/{category}', [AssetCategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [AssetCategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('clusters', [AssetClusterController::class, 'index'])->name('clusters.index');
        Route::get('clusters/create', [AssetClusterController::class, 'create'])->name('clusters.create');
        Route::post('clusters', [AssetClusterController::class, 'store'])->name('clusters.store');
        Route::get('clusters/{cluster}/edit', [AssetClusterController::class, 'edit'])->name('clusters.edit');
        Route::put('clusters/{cluster}', [AssetClusterController::class, 'update'])->name('clusters.update');
        Route::delete('clusters/{cluster}', [AssetClusterController::class, 'destroy'])->name('clusters.destroy');

        Route::get('sub-clusters', [AssetSubClusterController::class, 'index'])->name('sub-clusters.index');
        Route::get('sub-clusters/create', [AssetSubClusterController::class, 'create'])->name('sub-clusters.create');
        Route::post('sub-clusters', [AssetSubClusterController::class, 'store'])->name('sub-clusters.store');
        Route::get('sub-clusters/{subCluster}/edit', [AssetSubClusterController::class, 'edit'])->name('sub-clusters.edit');
        Route::put('sub-clusters/{subCluster}', [AssetSubClusterController::class, 'update'])->name('sub-clusters.update');
        Route::delete('sub-clusters/{subCluster}', [AssetSubClusterController::class, 'destroy'])->name('sub-clusters.destroy');

        Route::get('items', [ItemController::class, 'index'])->name('items.index');
        Route::get('items/create', [ItemController::class, 'create'])->name('items.create');
        Route::post('items', [ItemController::class, 'store'])->name('items.store');
        Route::get('items/{item}/edit', [ItemController::class, 'edit'])->name('items.edit');
        Route::put('items/{item}', [ItemController::class, 'update'])->name('items.update');
        Route::delete('items/{item}', [ItemController::class, 'destroy'])->name('items.destroy');
    });
