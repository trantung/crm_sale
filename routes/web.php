<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadImportController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('leads/bulk-assign', [LeadController::class, 'bulkAssign'])->name('leads.bulk-assign');
    Route::post('leads/{lead}/stage', [LeadController::class, 'changeStage'])->name('leads.stage')->whereNumber('lead');
    Route::post('leads/{lead}/classify', [LeadController::class, 'classify'])->name('leads.classify')->whereNumber('lead');
    Route::post('leads/{lead}/assign', [LeadController::class, 'assign'])->name('leads.assign')->whereNumber('lead');
    Route::post('leads/{lead}/activities', [LeadController::class, 'storeActivity'])->name('leads.activities.store')->whereNumber('lead');
    Route::post('leads/{lead}/call', [LeadController::class, 'logCall'])->name('leads.call')->whereNumber('lead');
    Route::resource('leads', LeadController::class)->whereNumber('lead');

    Route::resource('products', ProductController::class)->except(['show']);
    Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'show']);

    Route::middleware('role:admin')->group(function () {
        Route::get('leads/import/sample', [LeadImportController::class, 'sample'])->name('leads.import.sample');
        Route::get('leads/import', [LeadImportController::class, 'create'])->name('leads.import');
        Route::post('leads/import', [LeadImportController::class, 'store'])->name('leads.import.store');
        Route::get('leads/import/preview', [LeadImportController::class, 'preview'])->name('leads.import.preview');
        Route::post('leads/import/preview', [LeadImportController::class, 'savePreview'])->name('leads.import.preview.save');
        Route::post('leads/import/commit', [LeadImportController::class, 'commit'])->name('leads.import.commit');
        Route::post('leads/import/cancel', [LeadImportController::class, 'cancel'])->name('leads.import.cancel');
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::resource('users', UserController::class)->except(['show']);
    });
});

require __DIR__.'/auth.php';
