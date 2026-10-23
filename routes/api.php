<?php

use App\Http\Controllers\Api\DeviceApiController;
use Illuminate\Support\Facades\Route;

// ESP32 endpoints. Auth: "Authorization: Bearer <device token>" (see docs/API.md)
Route::prefix('v1')->middleware(['device', 'throttle:device-readings'])->group(function () {
    Route::post('/readings', [DeviceApiController::class, 'store'])->name('api.readings.store');
    Route::post('/heartbeat', [DeviceApiController::class, 'heartbeat'])->name('api.heartbeat');
});
