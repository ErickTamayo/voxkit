<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\Audition;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Job;
use Illuminate\Support\Str;

const CREATE_JOB_MUTATION = <<<'GRAPHQL'
mutation CreateJob($input: CreateJobInput!) {
    createJob(input: $input) {
        id
        audition_id
        client_id
        agent_id
        project_title
    }
}
GRAPHQL;

test('createJob accepts relationship ids inside relation objects', function () {
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

    $input = [
        'audition' => ['id' => $audition->id],
        'client' => ['id' => $clientContact->id],
        'agent' => ['id' => $agentContact->id],
        'project_title' => 'Relationship ID Job',
        'category' => 'COMMERCIAL',
        'contracted_rate' => ['amount_cents' => 125000, 'currency' => 'USD'],
        'rate_type' => 'FLAT',
        'status' => 'BOOKED',
    ];

    $response = $this->graphQL(CREATE_JOB_MUTATION, ['input' => $input]);

    $response->assertGraphQLErrorFree();
    expect($response->json('data.createJob.audition_id'))->toBe($audition->id);
    expect($response->json('data.createJob.client_id'))->toBe($clientContact->id);
    expect($response->json('data.createJob.agent_id'))->toBe($agentContact->id);
});

test('createJob creates client and agent relationships in a single mutation', function () {
    $user = actingAsUser();

    $input = [
        'client' => [
            'create' => [
                'contact' => [
                    'name' => 'Acme Studios',
                    'email' => 'casting@acme.test',
                    'contactable' => [
                        'client' => [
                            'type' => 'COMPANY',
                            'industry' => 'Gaming',
                            'payment_terms' => 'Net 30',
                        ],
                    ],
                ],
            ],
        ],
        'agent' => [
            'create' => [
                'contact' => [
                    'name' => 'Jamie Agent',
                    'email' => 'jamie@premier.test',
                    'contactable' => [
                        'agent' => [
                            'agency_name' => 'Premier Voices',
                            'commission_rate' => 1200,
                            'is_exclusive' => true,
                        ],
                    ],
                ],
            ],
        ],
        'project_title' => 'Nested Relationship Job',
        'category' => 'COMMERCIAL',
        'contracted_rate' => ['amount_cents' => 200000, 'currency' => 'USD'],
        'rate_type' => 'FLAT',
        'status' => 'BOOKED',
    ];

    $response = $this->graphQL(CREATE_JOB_MUTATION, ['input' => $input]);

    $response->assertGraphQLErrorFree();

    $job = Job::query()->findOrFail($response->json('data.createJob.id'));
    $clientContact = Contact::query()->findOrFail($job->client_id);
    $agentContact = Contact::query()->findOrFail($job->agent_id);

    $this->assertDatabaseHas('contacts', [
        'id' => $clientContact->id,
        'user_id' => $user->id,
        'contactable_type' => Client::class,
        'name' => 'Acme Studios',
    ]);

    $this->assertDatabaseHas('contacts', [
        'id' => $agentContact->id,
        'user_id' => $user->id,
        'contactable_type' => Agent::class,
        'name' => 'Jamie Agent',
    ]);

    $this->assertDatabaseHas('clients', [
        'id' => $clientContact->contactable_id,
        'type' => 'company',
        'industry' => 'Gaming',
        'payment_terms' => 'Net 30',
    ]);

    $this->assertDatabaseHas('agents', [
        'id' => $agentContact->contactable_id,
        'agency_name' => 'Premier Voices',
        'commission_rate' => 1200,
        'is_exclusive' => 1,
    ]);
});

test('createJob rolls back nested creates when a relationship fails validation', function () {
    actingAsUser();

    $jobCount = Job::query()->count();
    $clientCount = Client::query()->count();
    $agentCount = Agent::query()->count();
    $contactCount = Contact::query()->count();

    $input = [
        'client' => [
            'create' => [
                'contact' => [
                    'name' => 'Direct Client',
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
        'project_title' => 'Rollback Job',
        'category' => 'COMMERCIAL',
        'contracted_rate' => ['amount_cents' => 50000, 'currency' => 'USD'],
        'rate_type' => 'FLAT',
        'status' => 'BOOKED',
    ];

    $response = $this->graphQL(CREATE_JOB_MUTATION, ['input' => $input]);

    $response->assertJsonStructure(['errors' => [['message']]]);
    expect($response->json('data.createJob'))->toBeNull();
    expect(Job::query()->count())->toBe($jobCount);
    expect(Client::query()->count())->toBe($clientCount);
    expect(Agent::query()->count())->toBe($agentCount);
    expect(Contact::query()->count())->toBe($contactCount);
});
