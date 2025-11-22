<?php

use App\Http\Controllers\{DeviceController, DeviceDataController, DeviceParameterController, DeviceTypeController};
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Login/Register routes also create tokens
Route::post('/users/login', [UserController::class, 'login'])
    ->name('users.login')
    ->middleware('throttle:5,1');
Route::post('/users/register', [UserController::class, 'create'])
    ->name('users.register')
    ->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    // User routes
    Route::name('users.')->prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'show'])->name('show');
        Route::get('/logout', [UserController::class, 'logout'])->name('logout');
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
});
