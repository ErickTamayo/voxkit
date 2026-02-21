<?php

use App\Models\User;
use App\Services\GoogleOAuthService;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function () {
    Mockery::close();
});

test('authenticate token endpoint creates new user', function () {
    // Mock the GoogleOAuthService
    $mockService = Mockery::mock(GoogleOAuthService::class);
    $this->app->instance(GoogleOAuthService::class, $mockService);

    $user = User::factory()->make([
        'id' => 1,
        'email' => 'newuser@example.com',
        'google_id' => '123456789',
    ]);

    $mockService->shouldReceive('authenticateWithIdToken')
        ->once()
        ->with('valid_id_token_12345')
        ->andReturn($user);

    $response = $this->postJson('/api/auth/google/token', [
        'id_token' => 'valid_id_token_12345',
        'device_name' => 'test_device',
    ]);

    expect($response->status())->toBe(200);
    expect($response->json())->toHaveKey('token');
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('authenticate token endpoint links existing user', function () {
    $existingUser = User::factory()->create([
        'email' => 'existing@example.com',
        'google_id' => null,
    ]);

    $mockService = Mockery::mock(GoogleOAuthService::class);
    $this->app->instance(GoogleOAuthService::class, $mockService);

    $existingUser->google_id = '987654321';

    $mockService->shouldReceive('authenticateWithIdToken')
        ->once()
        ->andReturn($existingUser);

    $response = $this->postJson('/api/auth/google/token', [
        'id_token' => 'valid_id_token_67890',
    ]);

    expect($response->status())->toBe(200);
    expect($response->json())->toHaveKey('token');
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('authenticate token endpoint validates request', function () {
    $response = $this->postJson('/api/auth/google/token', [
        // Missing id_token
    ]);

    expect($response->status())->toBe(422);
    expect($response->json('errors'))->toHaveKey('id_token');
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('authenticate token endpoint handles invalid token', function () {
    $mockService = Mockery::mock(GoogleOAuthService::class);
    $this->app->instance(GoogleOAuthService::class, $mockService);

    $mockService->shouldReceive('authenticateWithIdToken')
        ->once()
        ->andThrow(new \RuntimeException('Invalid Google ID token'));

    $response = $this->postJson('/api/auth/google/token', [
        'id_token' => 'invalid_token',
    ]);

    expect($response->status())->toBe(401);
    expect($response->json('message'))->toBe('Google authentication failed');
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('redirect endpoint redirects to google', function () {
    $mockDriver = Mockery::mock();
    $mockDriver->shouldReceive('redirect')
        ->once()
        ->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

    Socialite::shouldReceive('driver')
        ->with('google')
        ->once()
        ->andReturn($mockDriver);

    $response = $this->get('/auth/google/redirect');

    expect($response->status())->toBeIn([301, 302, 303, 307, 308]);
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('callback endpoint creates session', function () {
    $googleUser = new SocialiteUser;
    $googleUser->id = '123456789';
    $googleUser->email = 'test@example.com';
    $googleUser->name = 'Test User';
    $googleUser->token = 'access_token';

    $mockDriver = Mockery::mock();
    $mockDriver->shouldReceive('user')
        ->once()
        ->andReturn($googleUser);

    Socialite::shouldReceive('driver')
        ->with('google')
        ->once()
        ->andReturn($mockDriver);

    $response = $this->get('/auth/google/callback?code=valid_code');

    expect($response->status())->toBeIn([301, 302, 303, 307, 308]);
    expect($response->headers->get('Location'))->toContain('/sign-in-callback?auth=success');
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('callback endpoint handles socialite error', function () {
    $mockDriver = Mockery::mock();
    $mockDriver->shouldReceive('user')
        ->once()
        ->andThrow(new \Exception('OAuth error from Google'));

    Socialite::shouldReceive('driver')
        ->with('google')
        ->once()
        ->andReturn($mockDriver);

    $response = $this->get('/auth/google/callback?code=invalid');

    expect($response->status())->toBeIn([301, 302, 303, 307, 308]);
    expect($response->headers->get('Location'))->toContain('/sign-in-callback?auth=error');
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('multiple devices get separate tokens', function () {
    $mockService = Mockery::mock(GoogleOAuthService::class);
    $this->app->instance(GoogleOAuthService::class, $mockService);

    $user = User::factory()->create([
        'google_id' => '555666777',
    ]);

    $mockService->shouldReceive('authenticateWithIdToken')
        ->twice()
        ->andReturn($user);

    // First device
    $response1 = $this->postJson('/api/auth/google/token', [
        'id_token' => 'token_1',
        'device_name' => 'iPhone',
    ]);

    // Second device
    $response2 = $this->postJson('/api/auth/google/token', [
        'id_token' => 'token_2',
        'device_name' => 'Android',
    ]);

    expect($response1->status())->toBe(200);
    expect($response2->status())->toBe(200);

    $token1 = $response1->json('token');
    $token2 = $response2->json('token');

    // Tokens should be different
    expect($token1)->not->toBe($token2);
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('google only user can authenticate without password', function () {
    $mockService = Mockery::mock(GoogleOAuthService::class);
    $this->app->instance(GoogleOAuthService::class, $mockService);

    $user = User::factory()->create([
        'email' => 'googleonly@example.com',
        'google_id' => '888999000',
    ]);

    $mockService->shouldReceive('authenticateWithIdToken')
        ->once()
        ->andReturn($user);

    $response = $this->postJson('/api/auth/google/token', [
        'id_token' => 'valid_token',
    ]);

    expect($response->status())->toBe(200);
    expect($response->json())->toHaveKey('token');
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);
