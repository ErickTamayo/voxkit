<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use Illuminate\Support\Facades\Route;

// Google OAuth token route (native, no session needed)
Route::post('/auth/google/token', [GoogleAuthController::class, 'authenticateToken']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/ping', [AuthController::class, 'ping']);
    Route::post('/media', [\App\Http\Controllers\MediaController::class, 'store']); // to check this implementation
});
