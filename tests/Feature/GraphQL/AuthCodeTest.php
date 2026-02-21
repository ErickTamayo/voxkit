<?php

declare(strict_types=1);

use App\Models\AuthCode;
use App\Models\User;
use App\Notifications\AuthCodeNotification;
use App\Services\AuthCodeService;
use Illuminate\Support\Facades\Notification;

const SIGN_UP_MUTATION = <<<'GRAPHQL'
mutation ($input: SignUpAndRequestAuthenticationTokenInput!) {
    signUpAndRequestAuthenticationToken(input: $input) {
        __typename
        ... on SignUpAndRequestAuthenticationTokenOkResponse {
            ok
        }
        ... on SignUpAndRequestAuthenticationTokenErrorResponse {
            message
        }
    }
}
GRAPHQL;

const REQUEST_AUTH_TOKEN_MUTATION = <<<'GRAPHQL'
mutation ($input: RequestAuthenticationCodeInput!) {
    requestAuthenticationCode(input: $input) {
        __typename
        ... on RequestAuthenticationCodeOkResponse {
            ok
        }
        ... on RequestAuthenticationCodeErrorResponse {
            message
        }
    }
}
GRAPHQL;

const AUTHENTICATE_WITH_TOKEN_MUTATION = <<<'GRAPHQL'
mutation ($input: AuthenticateWithCodeInput!) {
    authenticateWithCode(input: $input) {
        __typename
        ... on AuthenticateWithCodeOkResponse {
            ok
        }
        ... on AuthenticateWithCodeTokenResponse {
            token
        }
        ... on AuthenticateWithCodeErrorResponse {
            message
        }
    }
}
GRAPHQL;

const REVOKE_TOKEN_MUTATION = <<<'GRAPHQL'
mutation {
    revokeToken {
        __typename
        ... on RevokeTokenOkResponse {
            ok
        }
        ... on RevokeTokenErrorResponse {
            message
        }
    }
}
GRAPHQL;

const AUTH_ME_QUERY = <<<'GRAPHQL'
query {
    me {
        id
        email
    }
}
GRAPHQL;

test('sign up sends code and returns ok', function () {
    Notification::fake();

    $response = $this->graphQL(SIGN_UP_MUTATION, [
        'input' => [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ],
    ]);

    $response->assertGraphQLErrorFree();
    expect($response->json('data.signUpAndRequestAuthenticationToken.__typename'))->toBe('SignUpAndRequestAuthenticationTokenOkResponse');
    expect($response->json('data.signUpAndRequestAuthenticationToken.ok'))->toBeTrue();

    $user = User::query()->where('email', 'ada@example.com')->first();
    expect($user)->not->toBeNull();

    Notification::assertSentTo($user, AuthCodeNotification::class);
});

test('register request with existing email stays silent and does not update name', function () {
    Notification::fake();

    $user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'existing@example.com',
    ]);

    $response = $this->graphQL(SIGN_UP_MUTATION, [
        'input' => [
            'name' => 'New Name',
            'email' => 'existing@example.com',
        ],
    ]);

    $response->assertGraphQLErrorFree();
    expect($response->json('data.signUpAndRequestAuthenticationToken.__typename'))->toBe('SignUpAndRequestAuthenticationTokenOkResponse');

    $user->refresh();
    expect($user->name)->toBe('Original Name');

    Notification::assertSentTo($user, AuthCodeNotification::class);
});

test('authenticate request hides unknown email', function () {
    Notification::fake();

    $response = $this->graphQL(REQUEST_AUTH_TOKEN_MUTATION, [
        'input' => [
            'email' => 'missing@example.com',
        ],
    ]);

    $response->assertGraphQLErrorFree();
    expect($response->json('data.requestAuthenticationCode.__typename'))->toBe('RequestAuthenticationCodeOkResponse');

    Notification::assertNothingSent();
});

test('verify token code returns token and sets email verified', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $code = app(AuthCodeService::class)->issueCode($user, AuthCode::PURPOSE_AUTH);

    $response = $this->graphQL(AUTHENTICATE_WITH_TOKEN_MUTATION, [
        'input' => [
            'email' => $user->email,
            'code' => $code,
            'mode' => 'TOKEN',
            'device_name' => 'ios',
        ],
    ]);

    $response->assertGraphQLErrorFree();
    expect($response->json('data.authenticateWithCode.__typename'))->toBe('AuthenticateWithCodeTokenResponse');
    expect($response->json('data.authenticateWithCode.token'))->not->toBeNull();

    $user->refresh();
    expect($user->email_verified_at)->not->toBeNull();

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
    ]);
});

test('verify token code cannot be reused', function () {
    $user = User::factory()->create();
    $code = app(AuthCodeService::class)->issueCode($user, AuthCode::PURPOSE_AUTH);

    $this->graphQL(AUTHENTICATE_WITH_TOKEN_MUTATION, [
        'input' => [
            'email' => $user->email,
            'code' => $code,
            'mode' => 'TOKEN',
        ],
    ]);

    $response = $this->graphQL(AUTHENTICATE_WITH_TOKEN_MUTATION, [
        'input' => [
            'email' => $user->email,
            'code' => $code,
            'mode' => 'TOKEN',
        ],
    ]);

    $response->assertGraphQLErrorFree();
    expect($response->json('data.authenticateWithCode.__typename'))->toBe('AuthenticateWithCodeErrorResponse');
});

test('verify token code deactivates after too many failed attempts', function () {
    $user = User::factory()->create();
    $code = app(AuthCodeService::class)->issueCode($user, AuthCode::PURPOSE_AUTH);
    $invalidCode = $code === '000000' ? '000001' : '000000';

    foreach (range(1, 5) as $_) {
        $response = $this->graphQL(AUTHENTICATE_WITH_TOKEN_MUTATION, [
            'input' => [
                'email' => $user->email,
                'code' => $invalidCode,
                'mode' => 'TOKEN',
            ],
        ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.authenticateWithCode.__typename'))->toBe('AuthenticateWithCodeErrorResponse');
    }

    $authCode = AuthCode::query()
        ->where('user_id', $user->id)
        ->where('purpose', AuthCode::PURPOSE_AUTH)
        ->latest('created_at')
        ->first();

    expect($authCode)->not->toBeNull();
    expect($authCode->attempts)->toBe(5);
    expect($authCode->used_at)->not->toBeNull();

    $response = $this->graphQL(AUTHENTICATE_WITH_TOKEN_MUTATION, [
        'input' => [
            'email' => $user->email,
            'code' => $code,
            'mode' => 'TOKEN',
        ],
    ]);

    $response->assertGraphQLErrorFree();
    expect($response->json('data.authenticateWithCode.__typename'))->toBe('AuthenticateWithCodeErrorResponse');
});

test('authenticate with code is rate limited after repeated failures', function () {
    $email = 'verify-throttle-'.uniqid().'@example.com';

    foreach (range(1, 8) as $_) {
        $response = $this->graphQL(AUTHENTICATE_WITH_TOKEN_MUTATION, [
            'input' => [
                'email' => $email,
                'code' => '000000',
                'mode' => 'TOKEN',
            ],
        ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.authenticateWithCode.__typename'))->toBe('AuthenticateWithCodeErrorResponse');
        expect($response->json('data.authenticateWithCode.message'))->toBe('Invalid or expired code.');
    }

    $response = $this->graphQL(AUTHENTICATE_WITH_TOKEN_MUTATION, [
        'input' => [
            'email' => $email,
            'code' => '000000',
            'mode' => 'TOKEN',
        ],
    ]);

    $response->assertGraphQLErrorFree();
    expect($response->json('data.authenticateWithCode.__typename'))->toBe('AuthenticateWithCodeErrorResponse');
    expect($response->json('data.authenticateWithCode.message'))->toContain('Too many attempts.');
});

test('authenticate with code is rate limited across multiple ips for the same email', function () {
    $email = 'verify-email-limit-'.uniqid().'@example.com';

    foreach (range(1, 8) as $index) {
        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => "10.10.0.{$index}"])
            ->graphQL(AUTHENTICATE_WITH_TOKEN_MUTATION, [
                'input' => [
                    'email' => $email,
                    'code' => '000000',
                    'mode' => 'TOKEN',
                ],
            ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.authenticateWithCode.__typename'))->toBe('AuthenticateWithCodeErrorResponse');
        expect($response->json('data.authenticateWithCode.message'))->toBe('Invalid or expired code.');
    }

    $response = $this
        ->withServerVariables(['REMOTE_ADDR' => '10.10.0.200'])
        ->graphQL(AUTHENTICATE_WITH_TOKEN_MUTATION, [
            'input' => [
                'email' => $email,
                'code' => '000000',
                'mode' => 'TOKEN',
            ],
        ]);

    $response->assertGraphQLErrorFree();
    expect($response->json('data.authenticateWithCode.__typename'))->toBe('AuthenticateWithCodeErrorResponse');
    expect($response->json('data.authenticateWithCode.message'))->toContain('Too many attempts.');
});

test('verify token code fails when expired', function () {
    $user = User::factory()->create();
    $code = '123456';

    AuthCode::create([
        'user_id' => $user->id,
        'purpose' => AuthCode::PURPOSE_AUTH,
        'code_hash' => hash('sha256', $code),
        'expires_at' => now()->subMinute(),
    ]);

    $response = $this->graphQL(AUTHENTICATE_WITH_TOKEN_MUTATION, [
        'input' => [
            'email' => $user->email,
            'code' => $code,
            'mode' => 'TOKEN',
        ],
    ]);

    $response->assertGraphQLErrorFree();
    expect($response->json('data.authenticateWithCode.__typename'))->toBe('AuthenticateWithCodeErrorResponse');
});

test('verify session code establishes session', function () {
    $user = User::factory()->create();
    $code = app(AuthCodeService::class)->issueCode($user, AuthCode::PURPOSE_AUTH);

    $response = $this->graphQL(AUTHENTICATE_WITH_TOKEN_MUTATION, [
        'input' => [
            'email' => $user->email,
            'code' => $code,
            'mode' => 'SESSION',
        ],
    ]);

    $response->assertGraphQLErrorFree();
    expect($response->json('data.authenticateWithCode.__typename'))->toBe('AuthenticateWithCodeOkResponse');

    $meResponse = $this->graphQL(AUTH_ME_QUERY);
    $meResponse->assertGraphQLErrorFree();
    expect($meResponse->json('data.me.email'))->toBe($user->email);
});

test('request authentication code is rate limited after too many requests', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'request-throttle-'.uniqid().'@example.com',
    ]);

    foreach (range(1, 5) as $_) {
        $response = $this->graphQL(REQUEST_AUTH_TOKEN_MUTATION, [
            'input' => [
                'email' => $user->email,
            ],
        ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.requestAuthenticationCode.__typename'))->toBe('RequestAuthenticationCodeOkResponse');

        if ($_ < 5) {
            $this->travel(61)->seconds();
        }
    }

    $response = $this->graphQL(REQUEST_AUTH_TOKEN_MUTATION, [
        'input' => [
            'email' => $user->email,
        ],
    ]);

    $response->assertGraphQLErrorFree();
    expect($response->json('data.requestAuthenticationCode.__typename'))->toBe('RequestAuthenticationCodeErrorResponse');
    expect($response->json('data.requestAuthenticationCode.message'))->toContain('Too many attempts.');

    $this->travelBack();
});

test('request authentication code enforces resend cooldown', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'request-cooldown-'.uniqid().'@example.com',
    ]);

    $firstResponse = $this->graphQL(REQUEST_AUTH_TOKEN_MUTATION, [
        'input' => [
            'email' => $user->email,
        ],
    ]);

    $firstResponse->assertGraphQLErrorFree();
    expect($firstResponse->json('data.requestAuthenticationCode.__typename'))->toBe('RequestAuthenticationCodeOkResponse');

    $secondResponse = $this->graphQL(REQUEST_AUTH_TOKEN_MUTATION, [
        'input' => [
            'email' => $user->email,
        ],
    ]);

    $secondResponse->assertGraphQLErrorFree();
    expect($secondResponse->json('data.requestAuthenticationCode.__typename'))->toBe('RequestAuthenticationCodeErrorResponse');
    expect($secondResponse->json('data.requestAuthenticationCode.message'))->toContain('Too many attempts.');
});

test('request authentication code is rate limited by ip across multiple emails', function () {
    foreach (range(1, 20) as $index) {
        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.25'])
            ->graphQL(REQUEST_AUTH_TOKEN_MUTATION, [
                'input' => [
                    'email' => "ip-limit-{$index}-".uniqid().'@example.com',
                ],
            ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.requestAuthenticationCode.__typename'))->toBe('RequestAuthenticationCodeOkResponse');
    }

    $blockedResponse = $this
        ->withServerVariables(['REMOTE_ADDR' => '203.0.113.25'])
        ->graphQL(REQUEST_AUTH_TOKEN_MUTATION, [
            'input' => [
                'email' => 'ip-limit-blocked-'.uniqid().'@example.com',
            ],
        ]);

    $blockedResponse->assertGraphQLErrorFree();
    expect($blockedResponse->json('data.requestAuthenticationCode.__typename'))->toBe('RequestAuthenticationCodeErrorResponse');
    expect($blockedResponse->json('data.requestAuthenticationCode.message'))->toContain('Too many attempts.');
});

test('sign up and request auth code is rate limited after too many requests', function () {
    Notification::fake();

    $email = 'signup-throttle-'.uniqid().'@example.com';

    foreach (range(1, 5) as $_) {
        $response = $this->graphQL(SIGN_UP_MUTATION, [
            'input' => [
                'name' => 'Rate Limited User',
                'email' => $email,
            ],
        ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.signUpAndRequestAuthenticationToken.__typename'))->toBe('SignUpAndRequestAuthenticationTokenOkResponse');

        if ($_ < 5) {
            $this->travel(61)->seconds();
        }
    }

    $response = $this->graphQL(SIGN_UP_MUTATION, [
        'input' => [
            'name' => 'Rate Limited User',
            'email' => $email,
        ],
    ]);

    $response->assertGraphQLErrorFree();
    expect($response->json('data.signUpAndRequestAuthenticationToken.__typename'))->toBe('SignUpAndRequestAuthenticationTokenErrorResponse');
    expect($response->json('data.signUpAndRequestAuthenticationToken.message'))->toContain('Too many attempts.');

    $this->travelBack();
});

test('register rejects disposable email domains', function () {
    Notification::fake();

    $storage = storage_path('framework/testing_disposable_domains.json');
    file_put_contents($storage, json_encode(['mailinator.com']));

    try {
        config([
            'disposable-email.storage' => $storage,
            'disposable-email.cache.enabled' => false,
        ]);

        $response = $this->graphQL(SIGN_UP_MUTATION, [
            'input' => [
                'name' => 'Temp User',
                'email' => 'temp@mailinator.com',
            ],
        ]);

        $response->assertGraphQLErrorMessage('Validation failed for the field [signUpAndRequestAuthenticationToken].');
    } finally {
        @unlink($storage);
    }

});

test('revoke token mutation revokes the current access token', function () {
    $user = User::factory()->create();
    $plainTextToken = $user->createToken('test_device')->plainTextToken;

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
    ]);

    $response = $this->postJson('/graphql', [
        'query' => REVOKE_TOKEN_MUTATION,
    ], [
        'Authorization' => 'Bearer '.$plainTextToken,
    ]);

    $response->assertOk();
    expect($response->json('data.revokeToken.__typename'))->toBe('RevokeTokenOkResponse');
    expect($response->json('data.revokeToken.ok'))->toBeTrue();

    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id' => $user->id,
    ]);
});
