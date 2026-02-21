<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\Audition;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Job;
use Illuminate\Support\Str;

const UPDATE_JOB_MUTATION = <<<'GRAPHQL'
mutation UpdateJob($id: ULID!, $input: UpdateJobInput!) {
    updateJob(id: $id, input: $input) {
        id
        audition_id
        client_id
        agent_id
        project_title
    }
}
GRAPHQL;

test('updateJob accepts relationship ids inside relation objects', function () {
    $user = actingAsUser();

    $initialAudition = Audition::factory()->create(['user_id' => $user->id]);
    $targetAudition = Audition::factory()->create(['user_id' => $user->id]);

    $initialClient = Client::factory()->create();
    $initialClientContact = Contact::factory()->create([
        'user_id' => $user->id,
        'contactable_type' => Client::class,
        'contactable_id' => $initialClient->id,
    ]);

    $targetClient = Client::factory()->create();
    $targetClientContact = Contact::factory()->create([
        'user_id' => $user->id,
        'contactable_type' => Client::class,
        'contactable_id' => $targetClient->id,
    ]);

    $initialAgent = Agent::factory()->create();
    $initialAgentContact = Contact::factory()->create([
        'user_id' => $user->id,
        'contactable_type' => Agent::class,
        'contactable_id' => $initialAgent->id,
    ]);

    $targetAgent = Agent::factory()->create();
    $targetAgentContact = Contact::factory()->create([
        'user_id' => $user->id,
        'contactable_type' => Agent::class,
        'contactable_id' => $targetAgent->id,
    ]);

    $job = Job::factory()->create([
        'user_id' => $user->id,
        'audition_id' => $initialAudition->id,
        'client_id' => $initialClientContact->id,
        'agent_id' => $initialAgentContact->id,
    ]);

    $input = [
        'audition' => ['id' => $targetAudition->id],
        'client' => ['id' => $targetClientContact->id],
        'agent' => ['id' => $targetAgentContact->id],
    ];

    $response = $this->graphQL(UPDATE_JOB_MUTATION, [
        'id' => $job->id,
        'input' => $input,
    ]);

    $response->assertGraphQLErrorFree();
    expect($response->json('data.updateJob.audition_id'))->toBe($targetAudition->id);
    expect($response->json('data.updateJob.client_id'))->toBe($targetClientContact->id);
    expect($response->json('data.updateJob.agent_id'))->toBe($targetAgentContact->id);
});

test('updateJob creates client and agent relationships in a single mutation', function () {
    $user = actingAsUser();

    $initialClient = Client::factory()->create();
    $initialClientContact = Contact::factory()->create([
        'user_id' => $user->id,
        'contactable_type' => Client::class,
        'contactable_id' => $initialClient->id,
    ]);

    $job = Job::factory()->create([
        'user_id' => $user->id,
        'client_id' => $initialClientContact->id,
        'agent_id' => null,
    ]);

    $input = [
        'client' => [
            'create' => [
                'contact' => [
                    'name' => 'New Client Contact',
                    'email' => 'client@newco.test',
                    'contactable' => [
                        'client' => [
                            'type' => 'COMPANY',
                            'industry' => 'Advertising',
                        ],
                    ],
                ],
            ],
        ],
        'agent' => [
            'create' => [
                'contact' => [
                    'name' => 'New Agent Contact',
                    'email' => 'agent@newvoices.test',
                    'contactable' => [
                        'agent' => [
                            'agency_name' => 'New Voices Agency',
                        ],
                    ],
                ],
            ],
        ],
        'project_title' => 'Updated with nested relations',
    ];

    $response = $this->graphQL(UPDATE_JOB_MUTATION, [
        'id' => $job->id,
        'input' => $input,
    ]);

    $response->assertGraphQLErrorFree();

    $updatedJob = Job::query()->findOrFail($job->id);
    $clientContact = Contact::query()->findOrFail($updatedJob->client_id);
    $agentContact = Contact::query()->findOrFail($updatedJob->agent_id);

    expect($updatedJob->project_title)->toBe('Updated with nested relations');

    $this->assertDatabaseHas('contacts', [
        'id' => $clientContact->id,
        'user_id' => $user->id,
        'contactable_type' => Client::class,
        'name' => 'New Client Contact',
    ]);

    $this->assertDatabaseHas('contacts', [
        'id' => $agentContact->id,
        'user_id' => $user->id,
        'contactable_type' => Agent::class,
        'name' => 'New Agent Contact',
    ]);
});

test('updateJob can clear optional relationships by setting relation objects to null', function () {
    $user = actingAsUser();

    $audition = Audition::factory()->create(['user_id' => $user->id]);

    $client = Client::factory()->create();
    $clientContact = Contact::factory()->create([
        'user_id' => $user->id,
        'contactable_type' => Client::class,
        'contactable_id' => $client->id,
    ]);

    $agent = Agent::factory()->create();
    $agentContact = Contact::factory()->create([
        'user_id' => $user->id,
        'contactable_type' => Agent::class,
        'contactable_id' => $agent->id,
    ]);

    $job = Job::factory()->create([
        'user_id' => $user->id,
        'audition_id' => $audition->id,
        'client_id' => $clientContact->id,
        'agent_id' => $agentContact->id,
    ]);

    $response = $this->graphQL(UPDATE_JOB_MUTATION, [
        'id' => $job->id,
        'input' => [
            'audition' => null,
            'agent' => null,
        ],
    ]);

    $response->assertGraphQLErrorFree();
    expect($response->json('data.updateJob.audition_id'))->toBeNull();
    expect($response->json('data.updateJob.agent_id'))->toBeNull();

    $this->assertDatabaseHas('jobs', [
        'id' => $job->id,
        'audition_id' => null,
        'agent_id' => null,
    ]);
});

test('updateJob rolls back nested creates when a relationship fails validation', function () {
    $user = actingAsUser();

    $initialClient = Client::factory()->create();
    $initialClientContact = Contact::factory()->create([
        'user_id' => $user->id,
        'contactable_type' => Client::class,
        'contactable_id' => $initialClient->id,
    ]);

    $job = Job::factory()->create([
        'user_id' => $user->id,
        'client_id' => $initialClientContact->id,
    ]);

    $clientCount = Client::query()->count();
    $agentCount = Agent::query()->count();
    $contactCount = Contact::query()->count();

    $response = $this->graphQL(UPDATE_JOB_MUTATION, [
        'id' => $job->id,
        'input' => [
            'client' => [
                'create' => [
                    'contact' => [
                        'name' => 'Transient Client',
                        'contactable' => [
                            'client' => [
                                'type' => 'INDIVIDUAL',
                            ],
                        ],
                    ],
                ],
            ],
            'agent' => [
                'id' => (string) Str::ulid(),
            ],
        ],
    ]);

    $response->assertJsonStructure(['errors' => [['message']]]);
    expect($response->json('data.updateJob'))->toBeNull();
    expect(Client::query()->count())->toBe($clientCount);
    expect(Agent::query()->count())->toBe($agentCount);
    expect(Contact::query()->count())->toBe($contactCount);
    expect($job->fresh()->client_id)->toBe($initialClientContact->id);
});
