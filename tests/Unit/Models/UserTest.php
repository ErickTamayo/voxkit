<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;

it('persists oauth fields', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'google_id' => '123456789',
        'google_token' => 'test_token',
        'google_refresh_token' => 'refresh_token',
        'apple_id' => 'apple-123',
        'apple_token' => 'apple_token',
        'apple_refresh_token' => 'apple_refresh',
    ]);

    expect($user->google_id)->toBe('123456789');
    expect($user->google_token)->toBe('test_token');
    expect($user->google_refresh_token)->toBe('refresh_token');
    expect($user->apple_id)->toBe('apple-123');
    expect($user->apple_token)->toBe('apple_token');
    expect($user->apple_refresh_token)->toBe('apple_refresh');
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('hides oauth tokens from json', function () {
    $user = User::factory()->create([
        'google_token' => 'secret_token',
        'google_refresh_token' => 'secret_refresh',
        'apple_token' => 'secret_apple',
        'apple_refresh_token' => 'secret_apple_refresh',
    ]);

    $json = $user->toArray();

    expect($json)->not->toHaveKey('google_token');
    expect($json)->not->toHaveKey('google_refresh_token');
    expect($json)->not->toHaveKey('apple_token');
    expect($json)->not->toHaveKey('apple_refresh_token');
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('encrypts oauth tokens', function () {
    $plainGoogleToken = 'my_plain_access_token_12345';
    $plainGoogleRefreshToken = 'my_plain_refresh_token_67890';
    $plainAppleToken = 'my_plain_apple_access_token_12345';
    $plainAppleRefreshToken = 'my_plain_apple_refresh_token_67890';

    $user = User::factory()->create([
        'google_token' => $plainGoogleToken,
        'google_refresh_token' => $plainGoogleRefreshToken,
        'apple_token' => $plainAppleToken,
        'apple_refresh_token' => $plainAppleRefreshToken,
    ]);

    $rawUser = DB::table('users')->where('id', $user->id)->first();

    expect($rawUser->google_token)->not->toBe($plainGoogleToken);
    expect($rawUser->google_refresh_token)->not->toBe($plainGoogleRefreshToken);
    expect($rawUser->apple_token)->not->toBe($plainAppleToken);
    expect($rawUser->apple_refresh_token)->not->toBe($plainAppleRefreshToken);

    expect($user->google_token)->toBe($plainGoogleToken);
    expect($user->google_refresh_token)->toBe($plainGoogleRefreshToken);
    expect($user->apple_token)->toBe($plainAppleToken);
    expect($user->apple_refresh_token)->toBe($plainAppleRefreshToken);
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);
