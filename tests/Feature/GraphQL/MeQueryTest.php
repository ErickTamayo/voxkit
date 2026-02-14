<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the currently authenticated user from the me query', function () {
    $authenticatedUser = User::factory()->create();
    User::factory()->create();

    $this->actingAs($authenticatedUser);

    $this->postJson('/graphql', [
        'query' => <<<'GRAPHQL'
            query {
                me {
                    id
                    name
                    email
                }
            }
        GRAPHQL,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.me.id', $authenticatedUser->id)
        ->assertJsonPath('data.me.name', $authenticatedUser->name)
        ->assertJsonPath('data.me.email', $authenticatedUser->email);
});

it('requires authentication for the me query', function () {
    $this->postJson('/graphql', [
        'query' => <<<'GRAPHQL'
            query {
                me {
                    id
                }
            }
        GRAPHQL,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.me', null)
        ->assertJsonPath('errors.0.message', 'Unauthenticated.');
});
