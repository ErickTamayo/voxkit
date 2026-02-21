<?php

declare(strict_types=1);

use App\Http\Controllers\GoogleAuthController;
use Illuminate\Support\Facades\Route;

if (app()->environment('local')) {
    Route::view('/graphql-voyager', 'graphql.voyager')->name('graphql.voyager');
}

// Google OAuth routes (web-based, use sessions)
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);

Route::view('/{any?}', 'app')->where('any', '^(?!graphql$|graphql-voyager$).*');
