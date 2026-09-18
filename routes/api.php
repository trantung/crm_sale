<?php

use App\Http\Controllers\Api\LeadIntakeController;
use App\Http\Middleware\VerifyIntakeApiKey;
use Illuminate\Support\Facades\Route;

Route::middleware([VerifyIntakeApiKey::class, 'throttle:60,1'])->group(function () {
    Route::post('/v1/leads/intake', [LeadIntakeController::class, 'store'])->name('api.leads.intake');
});
