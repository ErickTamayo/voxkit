<?php

declare(strict_types=1);

use App\Models\Contact;

test('createJob maps MoneyInput into cents and currency columns', function () {
    $user = actingAsUser();
    $client = Contact::factory()->create(['user_id' => $user->id]);

    $input = [
        'client' => [
            'id' => $client->id,
        ],
        'project_title' => 'Money Input Job',
        'category' => 'COMMERCIAL',
        'contracted_rate' => [
            'amount_cents' => 125000,
            'currency' => 'USD',
        ],
        'rate_type' => 'FLAT',
        'status' => 'BOOKED',
    ];

    $response = $this->graphQL(<<<'GRAPHQL'
    mutation ($input: CreateJobInput!) {
        createJob(input: $input) {
            id
        }
    }
    GRAPHQL, ['input' => $input]);

    $response->assertGraphQLErrorFree();

    $jobId = $response->json('data.createJob.id');

    $this->assertDatabaseHas('jobs', [
        'id' => $jobId,
        'user_id' => $user->id,
        'contracted_rate_cents' => 125000,
        'contracted_rate_currency' => 'USD',
    ]);
});
