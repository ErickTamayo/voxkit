<?php

declare(strict_types=1);

use App\Models\Audition;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Platform;
use App\Models\User;
use App\Services\RevenueService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = app(RevenueService::class);
    $this->user = User::factory()->create();
    Carbon::setTestNow('2024-01-15 12:00:00');
});

test('it attributes paid revenue to audition sources first', function () {
    $platform = Platform::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Voices.com',
    ]);

    $audition = Audition::factory()->create([
        'user_id' => $this->user->id,
        'sourceable_type' => Platform::class,
        'sourceable_id' => $platform->id,
    ]);

    $client = Client::factory()->create();
    $clientContact = Contact::factory()->create([
        'user_id' => $this->user->id,
        'contactable_type' => Client::class,
        'contactable_id' => $client->id,
        'name' => 'Direct Client',
    ]);

    $job = Job::factory()->create([
        'user_id' => $this->user->id,
        'audition_id' => $audition->id,
        'client_id' => $clientContact->id,
        'status' => 'in_progress',
    ]);

    Invoice::factory()->create([
        'user_id' => $this->user->id,
        'job_id' => $job->id,
        'client_id' => $clientContact->id,
        'status' => 'paid',
        'paid_at' => '2024-01-10',
        'total' => ['amount_cents' => 10000, 'currency' => 'USD'],
    ]);

    $result = $this->service->getRevenueBySource('MTD', $this->user, 'USD', 10);

    $platformEntry = collect($result['sources'])
        ->first(fn (array $source): bool => $source['source_type'] === 'platform' && $source['source_name'] === 'Voices.com');

    expect($platformEntry)->not()->toBeNull();
    expect($platformEntry['paid']['amount_cents'])->toBe(10000);
    expect($platformEntry['in_flight']['amount_cents'])->toBe(0);
    expect($platformEntry['percentage_of_total'])->toBe(100.0);
});

test('it filters zero totals and respects take limit', function () {
    for ($i = 0; $i < 12; $i++) {
        $client = Client::factory()->create();
        $contact = Contact::factory()->create([
            'user_id' => $this->user->id,
            'contactable_type' => Client::class,
            'contactable_id' => $client->id,
            'name' => "Client {$i}",
        ]);

        if ($i % 2 === 0) {
            Invoice::factory()->create([
                'user_id' => $this->user->id,
                'job_id' => null,
                'client_id' => $contact->id,
                'status' => 'paid',
                'paid_at' => '2024-01-10',
                'total' => [
                    'amount_cents' => 10000 + ($i * 100),
                    'currency' => 'USD',
                ],
            ]);
        }
    }

    $result = $this->service->getRevenueBySource('MTD', $this->user, 'USD', 3);

    expect($result['sources'])->toHaveCount(3);

    foreach ($result['sources'] as $source) {
        $total = $source['paid']['amount_cents'] + $source['in_flight']['amount_cents'];
        expect($total)->toBeGreaterThan(0);
    }
});
