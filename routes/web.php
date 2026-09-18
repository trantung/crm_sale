<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
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

    Route::get('leads/import', [LeadController::class, 'importForm'])->name('leads.import');
    Route::post('leads/import', [LeadController::class, 'importStore'])->name('leads.import.store');
    Route::post('leads/{lead}/stage', [LeadController::class, 'changeStage'])->name('leads.stage')->whereNumber('lead');
    Route::post('leads/{lead}/assign', [LeadController::class, 'assign'])->name('leads.assign')->whereNumber('lead');
    Route::post('leads/{lead}/activities', [LeadController::class, 'storeActivity'])->name('leads.activities.store')->whereNumber('lead');
    Route::post('leads/{lead}/call', [LeadController::class, 'logCall'])->name('leads.call')->whereNumber('lead');
    Route::resource('leads', LeadController::class)->whereNumber('lead');

    Route::middleware('role:admin')->group(function () {
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::resource('users', UserController::class)->except(['show']);
    });
});

require __DIR__.'/auth.php';
