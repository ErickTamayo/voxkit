<?php

use App\Models\User;
use App\Services\GoogleOAuthService;
use Laravel\Socialite\Two\User as SocialiteUser;

function createMockSocialiteUser(array $data): SocialiteUser
{
    $user = new SocialiteUser;
    $user->id = $data['id'];
    $user->email = $data['email'];
    $user->name = $data['name'];
    $user->token = $data['token'] ?? 'mock_access_token';
    $user->refreshToken = $data['refreshToken'] ?? null;

    return $user;
}

beforeEach(function () {
    $this->service = new GoogleOAuthService;
});

test('finds existing user by google id', function () {
    // Create user with google_id
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'google_id' => '123456789',
        'name' => 'Test User',
    ]);

    // Create mock Socialite user
    $googleUser = createMockSocialiteUser([
        'id' => '123456789',
        'email' => 'test@example.com',
        'name' => 'Test User Updated',
    ]);

    $result = $this->service->authenticateWithSocialite($googleUser);

    expect($result->id)->toBe($user->id);
    expect($result->google_id)->toBe('123456789');
    // Service doesn't update name when finding by google_id
    expect($result->name)->toBe('Test User');
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('links google account to existing email', function () {
    // Create user without google_id
    $user = User::factory()->create([
        'email' => 'existing@example.com',
        'google_id' => null,
        'name' => 'Existing User',
        'email_verified_at' => null,
    ]);

    expect($user->google_id)->toBeNull();
    expect($user->email_verified_at)->toBeNull();

    // Create mock Socialite user with same email
    $googleUser = createMockSocialiteUser([
        'id' => '987654321',
        'email' => 'existing@example.com',
        'name' => 'Existing User',
    ]);

    $result = $this->service->authenticateWithSocialite($googleUser);

    expect($result->id)->toBe($user->id);
    expect($result->google_id)->toBe('987654321');

    // Check email_verified_at is set
    $freshUser = User::find($user->id);
    expect($freshUser->email_verified_at)->not->toBeNull();
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('creates new user when no match found', function () {
    $googleUser = createMockSocialiteUser([
        'id' => '111222333',
        'email' => 'newuser@example.com',
        'name' => 'New User',
    ]);

    $result = $this->service->authenticateWithSocialite($googleUser);

    expect($result->email)->toBe('newuser@example.com');
    expect($result->google_id)->toBe('111222333');
    expect($result->name)->toBe('New User');
    expect($result->email_verified_at)->not->toBeNull();
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('updates google token for existing user', function () {
    $user = User::factory()->create([
        'google_id' => '555666777',
        'google_token' => 'old_token',
    ]);

    $googleUser = createMockSocialiteUser([
        'id' => '555666777',
        'email' => $user->email,
        'name' => $user->name,
        'token' => 'new_token',
    ]);

    $result = $this->service->authenticateWithSocialite($googleUser);

    expect($result->google_token)->toBe('new_token');
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('verify id token throws exception on invalid token', function () {
    // Use reflection to test the protected method
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('verifyIdToken');
    $method->setAccessible(true);

    expect(fn () => $method->invoke($this->service, 'invalid_token_12345'))
        ->toThrow(\Exception::class);
})->uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);
