<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RedirectController;

Route::get('/', [RedirectController::class, 'index']);

Route::fallback([RedirectController::class, 'fallback']);
