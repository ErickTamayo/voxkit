<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\Contact;

const CREATE_AGENT_MUTATION = <<<'GRAPHQL'
mutation CreateAgent($input: CreateAgentWithContactInput!) {
    createAgent(input: $input) {
        id
        agency_name
        commission_rate
        is_exclusive
        contact {
            id
            name
            email
            phone
            phone_ext
        }
    }
}
GRAPHQL;

const UPDATE_AGENT_MUTATION = <<<'GRAPHQL'
mutation UpdateAgent($id: ULID!, $input: UpdateAgentWithContactInput!) {
    updateAgent(id: $id, input: $input) {
        id
        agency_name
        commission_rate
        is_exclusive
        contact {
            id
            name
            email
            phone
            phone_ext
        }
    }
}
GRAPHQL;

test('createAgent creates agent and contact in one mutation', function () {
    $user = actingAsUser();

    $response = $this->graphQL(CREATE_AGENT_MUTATION, [
        'input' => [
            'contact' => [
                'name' => 'Jamie Agent',
                'email' => 'jamie@premier.test',
                'phone' => '+1 555-111-2222',
            ],
            'agency_name' => 'Premier Voices',
            'commission_rate' => 1200,
            'is_exclusive' => true,
        ],
    ]);

    $response->assertGraphQLErrorFree();

    $agentId = $response->json('data.createAgent.id');
    $contactId = $response->json('data.createAgent.contact.id');

    $this->assertDatabaseHas('agents', [
        'id' => $agentId,
        'agency_name' => 'Premier Voices',
        'commission_rate' => 1200,
        'is_exclusive' => 1,
    ]);

    $this->assertDatabaseHas('contacts', [
        'id' => $contactId,
        'user_id' => $user->id,
        'contactable_type' => Agent::class,
        'contactable_id' => $agentId,
        'name' => 'Jamie Agent',
        'email' => 'jamie@premier.test',
    ]);
});

test('createAgent rolls back agent create when contact validation fails', function () {
    actingAsUser();

    $agentCount = Agent::query()->count();
    $contactCount = Contact::query()->count();

    $response = $this->graphQL(CREATE_AGENT_MUTATION, [
        'input' => [
            'contact' => [
                'name' => '   ',
            ],
            'agency_name' => 'Should Rollback',
        ],
    ]);

    $response->assertJsonStructure(['errors' => [['message']]]);
    expect($response->json('data.createAgent'))->toBeNull();
    expect(Agent::query()->count())->toBe($agentCount);
    expect(Contact::query()->count())->toBe($contactCount);
});

test('updateAgent updates both agent and contact fields', function () {
    $user = actingAsUser();
    $agent = Agent::factory()->create([
        'agency_name' => 'Old Agency',
        'commission_rate' => 1000,
        'is_exclusive' => false,
    ]);
    $contact = Contact::factory()->create([
        'user_id' => $user->id,
        'contactable_type' => Agent::class,
        'contactable_id' => $agent->id,
        'name' => 'Old Name',
        'email' => 'old@agency.test',
        'phone' => '111',
        'phone_ext' => '10',
    ]);

    $response = $this->graphQL(UPDATE_AGENT_MUTATION, [
        'id' => $agent->id,
        'input' => [
            'contact' => [
                'name' => 'New Name',
                'email' => 'new@agency.test',
                'phone_ext' => '22',
            ],
            'agency_name' => 'New Agency',
            'commission_rate' => 1500,
            'is_exclusive' => true,
        ],
    ]);

    $response->assertGraphQLErrorFree();

    $this->assertDatabaseHas('agents', [
        'id' => $agent->id,
        'agency_name' => 'New Agency',
        'commission_rate' => 1500,
        'is_exclusive' => 1,
    ]);

    $this->assertDatabaseHas('contacts', [
        'id' => $contact->id,
        'name' => 'New Name',
        'email' => 'new@agency.test',
        'phone_ext' => '22',
    ]);
});
