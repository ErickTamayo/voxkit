<?php

declare(strict_types=1);

const ME_QUERY = <<<'GRAPHQL'
query {
    me {
        id
        name
        email
    }
}
GRAPHQL;

const MY_SETTINGS_QUERY = <<<'GRAPHQL'
query {
    mySettings {
        id
        user_id
        timezone
        currency
        language
    }
}
GRAPHQL;

const UPDATE_ME_MUTATION = <<<'GRAPHQL'
mutation (
    $input: UpdateUserInput!
) {
    updateMe(input: $input) {
        id
        name
    }
}
GRAPHQL;

const UPDATE_MY_SETTINGS_MUTATION = <<<'GRAPHQL'
mutation (
    $input: UpdateSettingsInput!
) {
    updateMySettings(input: $input) {
        id
        timezone
        currency
        language
    }
}
GRAPHQL;

test('me query returns the authenticated user', function () {
    $user = actingAsUser();

    $response = $this->graphQL(ME_QUERY);

    $response->assertGraphQLErrorFree();
    expect($response->json('data.me.id'))->toBe($user->id);
    expect($response->json('data.me.email'))->toBe($user->email);
});

test('me query requires authentication', function () {
    $response = $this->graphQL(ME_QUERY);

    $response->assertGraphQLErrorMessage('Unauthenticated.');
});

test('mySettings query returns the authenticated user settings', function () {
    $user = actingAsUser();

    $response = $this->graphQL(MY_SETTINGS_QUERY);

    $response->assertGraphQLErrorFree();
    expect($response->json('data.mySettings.user_id'))->toBe($user->id);
});

test('mySettings query requires authentication', function () {
    $response = $this->graphQL(MY_SETTINGS_QUERY);

    $response->assertGraphQLErrorMessage('Unauthenticated.');
});

test('updateMe mutation updates the authenticated user', function () {
    $user = actingAsUser();

    $input = ['name' => 'Updated Name'];

    $response = $this->graphQL(UPDATE_ME_MUTATION, [
        'input' => $input,
    ]);

    $response->assertGraphQLErrorFree();
    expect($response->json('data.updateMe.name'))->toBe($input['name']);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => $input['name'],
    ]);
});

test('updateMe mutation requires authentication', function () {
    $response = $this->graphQL(UPDATE_ME_MUTATION, [
        'input' => ['name' => 'No Auth'],
    ]);

    $response->assertGraphQLErrorMessage('Unauthenticated.');
});

test('updateMySettings mutation updates the authenticated user settings', function () {
    $user = actingAsUser();

    $input = [
        'timezone' => 'America/New_York',
        'currency' => 'USD',
        'language' => 'en',
    ];

    $response = $this->graphQL(UPDATE_MY_SETTINGS_MUTATION, [
        'input' => $input,
    ]);

    $response->assertGraphQLErrorFree();
    expect($response->json('data.updateMySettings.timezone'))->toBe($input['timezone']);

    $this->assertDatabaseHas('settings', [
        'user_id' => $user->id,
        'timezone' => $input['timezone'],
        'currency' => $input['currency'],
        'language' => $input['language'],
    ]);
});

test('updateMySettings mutation requires authentication', function () {
    $response = $this->graphQL(UPDATE_MY_SETTINGS_MUTATION, [
        'input' => ['timezone' => 'No Auth'],
    ]);

    $response->assertGraphQLErrorMessage('Unauthenticated.');
});

test('updateMySettings mutation errors when settings are missing', function () {
    $user = actingAsUser();
    $user->settings()->delete();

    $response = $this->graphQL(UPDATE_MY_SETTINGS_MUTATION, [
        'input' => ['timezone' => 'Missing Settings'],
    ]);

    $response->assertGraphQLErrorMessage('Settings not found for the authenticated user');
});
