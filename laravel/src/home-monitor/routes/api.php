<?php

use App\Http\Controllers\{DeviceController, DeviceDataController, DeviceParameterController, DeviceTypeController, LocationController};
use App\Http\Controllers\UserController;
use App\Http\Controllers\RedirectController;
use Illuminate\Support\Facades\Route;

// Login/Register routes create tokens
Route::post('/users/login', [UserController::class, 'login'])
    ->name('users.login')
    ->middleware('throttle:5,1');
Route::post('/users/register', [UserController::class, 'create'])
    ->name('users.register')
    ->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    // User routes
    Route::name('users.me.')->prefix('users/me')->group(function () {
        Route::get('/', [UserController::class, 'show'])->name('show');
        Route::post('/logout', [UserController::class, 'logout'])->name('logout');
        Route::post('/logout-other-tokens', [UserController::class, 'logoutOtherTokens'])->name('logout-other-tokens');
    });

    // DeviceType routes
    Route::name('device-types.')->prefix('device-types')->group(function () {
        Route::get('/', [DeviceTypeController::class, 'index'])->name('index');
        Route::post('/', [DeviceTypeController::class, 'store'])->name('store');
        Route::get('/{id}', [DeviceTypeController::class, 'show'])->name('show');
        Route::put('/{id}', [DeviceTypeController::class, 'update'])->name('update');
        Route::delete('/{id}', [DeviceTypeController::class, 'destroy'])->name('destroy');
    });

    // DeviceData routes
    Route::name('device-parameters.data.')->prefix('device-parameters/{paramId}/data')->group(function () {
        Route::get('/', [DeviceDataController::class, 'bucket'])->name('bucket');
        Route::post('/', [DeviceDataController::class, 'store'])->name('store');
    });
    Route::post('/device-parameters/heartbeat', [DeviceDataController::class, 'heartbeat'])->name('device-parameters.heartbeat');

    // DeviceParameter routes
    Route::name('device-parameters.')->prefix('device-parameters')->group(function () {
        Route::get('/', [DeviceParameterController::class, 'index'])->name('index');
        Route::post('/', [DeviceParameterController::class, 'store'])->name('store');
        Route::get('/{id}', [DeviceParameterController::class, 'show'])->name('show');
        Route::put('/{id}', [DeviceParameterController::class, 'update'])->name('update');
        Route::delete('/{id}', [DeviceParameterController::class, 'destroy'])->name('destroy');
    });

    // Device routes
    Route::name('devices.')->prefix('devices')->group(function () {
        Route::get('/', [DeviceController::class, 'index'])->name('index');
        Route::post('/', [DeviceController::class, 'store'])->name('store');
        Route::get('/{id}', [DeviceController::class, 'show'])->name('show');
        Route::put('/{id}', [DeviceController::class, 'update'])->name('update');
        Route::delete('/{id}', [DeviceController::class, 'destroy'])->name('destroy');
    });

    // Location routes
    Route::name('locations.')->prefix('locations')->group(function () {
        Route::get('/', [LocationController::class, 'index'])->name('index');
        Route::post('/', [LocationController::class, 'store'])->name('store');
        Route::get('/{id}', [LocationController::class, 'show'])->name('show');
        Route::put('/{id}', [LocationController::class, 'update'])->name('update');
        Route::delete('/{id}', [LocationController::class, 'destroy'])->name('destroy');
    });

    Route::fallback([RedirectController::class, 'unknownApiRoute']);
});
