<?php

use App\Models\AuthCode;
use App\Models\User;
use App\Notifications\AuthCodeNotification;
use App\Services\AuthCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

const REQUEST_AUTHENTICATION_CODE_MUTATION = <<<'GRAPHQL'
mutation ($input: RequestAuthenticationCodeInput!) {
    requestAuthenticationCode(input: $input) {
        ok
        message
    }
}
GRAPHQL;

const AUTHENTICATE_WITH_CODE_MUTATION = <<<'GRAPHQL'
mutation ($input: AuthenticateWithCodeInput!) {
    authenticateWithCode(input: $input) {
        ok
        message
    }
}
GRAPHQL;

const LOGOUT_MUTATION = <<<'GRAPHQL'
mutation {
    logout {
        ok
        message
    }
}
GRAPHQL;

const ME_QUERY = <<<'GRAPHQL'
query {
    me {
        id
        email
    }
}
GRAPHQL;

it('requests an authentication code and creates the user when missing', function () {
    Notification::fake();

    $response = $this->postJson('/graphql', [
        'query' => REQUEST_AUTHENTICATION_CODE_MUTATION,
        'variables' => [
            'input' => [
                'email' => 'new-user@example.com',
            ],
        ],
    ]);

    $response->assertSuccessful();
    expect($response->json('data.requestAuthenticationCode.ok'))->toBeTrue();

    $user = User::query()->where('email', 'new-user@example.com')->first();
    expect($user)->not->toBeNull();

    Notification::assertSentTo($user, AuthCodeNotification::class);
    $this->assertDatabaseHas('auth_codes', [
        'user_id' => $user->id,
        'purpose' => AuthCode::PURPOSE_AUTH,
    ]);
});

it('authenticates with a valid code and establishes a session', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $code = app(AuthCodeService::class)->issueCode($user, AuthCode::PURPOSE_AUTH);

    $response = $this->postJson('/graphql', [
        'query' => AUTHENTICATE_WITH_CODE_MUTATION,
        'variables' => [
            'input' => [
                'email' => $user->email,
                'code' => $code,
            ],
        ],
    ]);

    $response->assertSuccessful();
    expect($response->json('data.authenticateWithCode.ok'))->toBeTrue();

    $user->refresh();
    expect($user->email_verified_at)->not->toBeNull();

    $meResponse = $this->postJson('/graphql', [
        'query' => ME_QUERY,
    ]);

    $meResponse->assertSuccessful();
    expect($meResponse->json('data.me.email'))->toBe($user->email);
});

it('does not allow reusing a consumed code', function () {
    $user = User::factory()->create();
    $code = app(AuthCodeService::class)->issueCode($user, AuthCode::PURPOSE_AUTH);

    $firstAttempt = $this->postJson('/graphql', [
        'query' => AUTHENTICATE_WITH_CODE_MUTATION,
        'variables' => [
            'input' => [
                'email' => $user->email,
                'code' => $code,
            ],
        ],
    ]);

    $firstAttempt->assertSuccessful();
    expect($firstAttempt->json('data.authenticateWithCode.ok'))->toBeTrue();

    $secondAttempt = $this->postJson('/graphql', [
        'query' => AUTHENTICATE_WITH_CODE_MUTATION,
        'variables' => [
            'input' => [
                'email' => $user->email,
                'code' => $code,
            ],
        ],
    ]);

    $secondAttempt->assertSuccessful();
    expect($secondAttempt->json('data.authenticateWithCode.ok'))->toBeFalse();
    expect($secondAttempt->json('data.authenticateWithCode.message'))->toBe('Invalid or expired code.');
});

it('enforces request cooldown between auth code requests', function () {
    Notification::fake();

    $email = 'cooldown-'.uniqid().'@example.com';

    $firstResponse = $this->postJson('/graphql', [
        'query' => REQUEST_AUTHENTICATION_CODE_MUTATION,
        'variables' => [
            'input' => [
                'email' => $email,
            ],
        ],
    ]);

    $firstResponse->assertSuccessful();
    expect($firstResponse->json('data.requestAuthenticationCode.ok'))->toBeTrue();

    $secondResponse = $this->postJson('/graphql', [
        'query' => REQUEST_AUTHENTICATION_CODE_MUTATION,
        'variables' => [
            'input' => [
                'email' => $email,
            ],
        ],
    ]);

    $secondResponse->assertSuccessful();
    expect($secondResponse->json('data.requestAuthenticationCode.ok'))->toBeFalse();
    expect($secondResponse->json('data.requestAuthenticationCode.message'))->toContain('Too many attempts.');
});

it('logs out the authenticated session', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $logoutResponse = $this->postJson('/graphql', [
        'query' => LOGOUT_MUTATION,
    ]);

    $logoutResponse->assertSuccessful();
    expect($logoutResponse->json('data.logout.ok'))->toBeTrue();

    $meResponse = $this->postJson('/graphql', [
        'query' => ME_QUERY,
    ]);

    $meResponse->assertSuccessful();
    expect($meResponse->json('data.me'))->toBeNull();
    expect($meResponse->json('errors.0.message'))->toBe('Unauthenticated.');
});
