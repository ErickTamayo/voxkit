<?php

use App\Models\AuthCode;
use App\Models\User;
use App\Notifications\AuthCodeNotification;
use App\Services\AuthCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

const REQUEST_AUTHENTICATION_CODE_MUTATION = <<<'GRAPHQL'
mutation ($input: RequestAuthenticationCodeInput!) {
    requestAuthenticationCode(input: $input) {
        __typename
        ... on RequestAuthenticationCodeSuccess {
            message
        }
        ... on AuthenticationRateLimitError {
            message
            retry_after_seconds
        }
    }
}
GRAPHQL;

const AUTHENTICATE_WITH_CODE_MUTATION = <<<'GRAPHQL'
mutation ($input: AuthenticateWithCodeInput!) {
    authenticateWithCode(input: $input) {
        __typename
        ... on AuthenticateWithCodeSessionSuccess {
            message
        }
        ... on AuthenticateWithCodeTokenSuccess {
            message
            token
        }
        ... on AuthenticateWithCodeInvalidCodeError {
            message
        }
        ... on AuthenticationRateLimitError {
            message
            retry_after_seconds
        }
    }
}
GRAPHQL;

const LOGOUT_MUTATION = <<<'GRAPHQL'
mutation {
    logout {
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
    expect($response->json('data.requestAuthenticationCode.__typename'))->toBe('RequestAuthenticationCodeSuccess');

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
                'mode' => 'SESSION',
            ],
        ],
    ]);

    $response->assertSuccessful();
    expect($response->json('data.authenticateWithCode.__typename'))->toBe('AuthenticateWithCodeSessionSuccess');

    $user->refresh();
    expect($user->email_verified_at)->not->toBeNull();

    $meResponse = $this->postJson('/graphql', [
        'query' => ME_QUERY,
    ]);

    $meResponse->assertSuccessful();
    expect($meResponse->json('data.me.email'))->toBe($user->email);
});

it('defaults to session authentication when mode is omitted', function () {
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
    expect($response->json('data.authenticateWithCode.__typename'))->toBe('AuthenticateWithCodeSessionSuccess');

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
                'mode' => 'SESSION',
            ],
        ],
    ]);

    $firstAttempt->assertSuccessful();
    expect($firstAttempt->json('data.authenticateWithCode.__typename'))->toBe('AuthenticateWithCodeSessionSuccess');

    $secondAttempt = $this->postJson('/graphql', [
        'query' => AUTHENTICATE_WITH_CODE_MUTATION,
        'variables' => [
            'input' => [
                'email' => $user->email,
                'code' => $code,
                'mode' => 'SESSION',
            ],
        ],
    ]);

    $secondAttempt->assertSuccessful();
    expect($secondAttempt->json('data.authenticateWithCode.__typename'))->toBe('AuthenticateWithCodeInvalidCodeError');
    expect($secondAttempt->json('data.authenticateWithCode.message'))->toBe('Invalid or expired code.');
});

it('authenticates with a valid code and returns a bearer token', function () {
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
                'mode' => 'TOKEN',
                'device_name' => 'ios-simulator',
            ],
        ],
    ]);

    $response->assertSuccessful();
    expect($response->json('data.authenticateWithCode.__typename'))->toBe('AuthenticateWithCodeTokenSuccess');
    expect($response->json('data.authenticateWithCode.token'))->not->toBeNull();

    $user->refresh();
    expect($user->email_verified_at)->not->toBeNull();

    $token = $response->json('data.authenticateWithCode.token');
    $meResponse = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/graphql', [
            'query' => ME_QUERY,
        ]);

    $meResponse->assertSuccessful();
    expect($meResponse->json('data.me.email'))->toBe($user->email);
    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_type' => User::class,
        'tokenable_id' => $user->id,
        'name' => 'ios-simulator',
    ]);
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
    expect($firstResponse->json('data.requestAuthenticationCode.__typename'))->toBe('RequestAuthenticationCodeSuccess');

    $secondResponse = $this->postJson('/graphql', [
        'query' => REQUEST_AUTHENTICATION_CODE_MUTATION,
        'variables' => [
            'input' => [
                'email' => $email,
            ],
        ],
    ]);

    $secondResponse->assertSuccessful();
    expect($secondResponse->json('data.requestAuthenticationCode.__typename'))->toBe('AuthenticationRateLimitError');
    expect($secondResponse->json('data.requestAuthenticationCode.message'))->toContain('Too many attempts.');
});

it('disables auth code rate limits in local environment', function () {
    Notification::fake();

    $currentEnvironment = $this->app->environment();
    $this->app['env'] = 'local';

    try {
        $email = 'local-no-limit-'.uniqid().'@example.com';

        $firstResponse = $this->postJson('/graphql', [
            'query' => REQUEST_AUTHENTICATION_CODE_MUTATION,
            'variables' => [
                'input' => [
                    'email' => $email,
                ],
            ],
        ]);

        $firstResponse->assertSuccessful();
        expect($firstResponse->json('data.requestAuthenticationCode.__typename'))->toBe('RequestAuthenticationCodeSuccess');

        $secondResponse = $this->postJson('/graphql', [
            'query' => REQUEST_AUTHENTICATION_CODE_MUTATION,
            'variables' => [
                'input' => [
                    'email' => $email,
                ],
            ],
        ]);

        $secondResponse->assertSuccessful();
        expect($secondResponse->json('data.requestAuthenticationCode.__typename'))->toBe('RequestAuthenticationCodeSuccess');
        expect($secondResponse->json('data.requestAuthenticationCode.message'))->toBe('Authentication code sent.');
    } finally {
        $this->app['env'] = $currentEnvironment;
    }
});

it('logs out the authenticated session', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);
    $code = app(AuthCodeService::class)->issueCode($user, AuthCode::PURPOSE_AUTH);

    $authenticateResponse = $this->postJson('/graphql', [
        'query' => AUTHENTICATE_WITH_CODE_MUTATION,
        'variables' => [
            'input' => [
                'email' => $user->email,
                'code' => $code,
                'mode' => 'SESSION',
            ],
        ],
    ]);

    $authenticateResponse->assertSuccessful();
    expect($authenticateResponse->json('data.authenticateWithCode.__typename'))->toBe('AuthenticateWithCodeSessionSuccess');

    $logoutResponse = $this->postJson('/graphql', [
        'query' => LOGOUT_MUTATION,
    ]);

    $logoutResponse->assertSuccessful();
    expect($logoutResponse->json('data.logout.message'))->toBe('Logged out.');
    expect(Auth::guard('web')->check())->toBeFalse();
});

it('logs out and revokes the authenticated bearer token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-device')->plainTextToken;

    $logoutResponse = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/graphql', [
            'query' => LOGOUT_MUTATION,
        ]);

    $logoutResponse->assertSuccessful();
    expect($logoutResponse->json('data.logout.message'))->toBe('Logged out.');

    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_type' => User::class,
        'tokenable_id' => $user->id,
        'name' => 'test-device',
    ]);
});
