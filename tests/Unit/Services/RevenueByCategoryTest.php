<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\User;
use App\Services\RevenueService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = app(RevenueService::class);
    $this->user = User::factory()->create();
    Carbon::setTestNow('2024-01-15 12:00:00');
});

test('it attributes paid revenue to job category', function () {
    $client = Client::factory()->create();
    $contact = Contact::factory()->create([
        'user_id' => $this->user->id,
        'contactable_type' => Client::class,
        'contactable_id' => $client->id,
        'name' => 'Direct Client',
    ]);

    $job = Job::factory()->create([
        'user_id' => $this->user->id,
        'client_id' => $contact->id,
        'category' => 'commercial',
        'status' => 'in_progress',
    ]);

    Invoice::factory()->create([
        'user_id' => $this->user->id,
        'job_id' => $job->id,
        'client_id' => $contact->id,
        'status' => 'paid',
        'paid_at' => '2024-01-10',
        'total' => ['amount_cents' => 12000, 'currency' => 'USD'],
    ]);

    $result = $this->service->getRevenueByCategory('MTD', $this->user, 'USD', 10);

    $entry = collect($result['categories'])
        ->first(fn (array $row): bool => $row['category'] === 'commercial');

    expect($entry)->not()->toBeNull();
    expect($entry['paid']['amount_cents'])->toBe(12000);
});

test('it uses unknown when invoices have no job', function () {
    $client = Client::factory()->create();
    $contact = Contact::factory()->create([
        'user_id' => $this->user->id,
        'contactable_type' => Client::class,
        'contactable_id' => $client->id,
        'name' => 'Standalone Client',
    ]);

    Invoice::factory()->create([
        'user_id' => $this->user->id,
        'job_id' => null,
        'client_id' => $contact->id,
        'status' => 'paid',
        'paid_at' => '2024-01-10',
        'total' => ['amount_cents' => 8000, 'currency' => 'USD'],
    ]);

    $result = $this->service->getRevenueByCategory('MTD', $this->user, 'USD', 10);

    $entry = collect($result['categories'])
        ->first(fn (array $row): bool => $row['category'] === 'unknown');

    expect($entry)->not()->toBeNull();
    expect($entry['paid']['amount_cents'])->toBe(8000);
});

test('it filters zero totals and respects take limit', function () {
    $categories = ['commercial', 'animation', 'promo', 'corporate'];

    foreach ($categories as $index => $category) {
        $client = Client::factory()->create();
        $contact = Contact::factory()->create([
            'user_id' => $this->user->id,
            'contactable_type' => Client::class,
            'contactable_id' => $client->id,
            'name' => "Client {$index}",
        ]);

        $job = Job::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $contact->id,
            'category' => $category,
            'status' => 'in_progress',
        ]);

        if ($index < 3) {
            Invoice::factory()->create([
                'user_id' => $this->user->id,
                'job_id' => $job->id,
                'client_id' => $contact->id,
                'status' => 'paid',
                'paid_at' => '2024-01-10',
                'total' => [
                    'amount_cents' => 10000 + ($index * 1000),
                    'currency' => 'USD',
                ],
            ]);
        }
    }

    $result = $this->service->getRevenueByCategory('MTD', $this->user, 'USD', 2);

    expect($result['categories'])->toHaveCount(2);

    foreach ($result['categories'] as $row) {
        $total = $row['paid']['amount_cents'] + $row['in_flight']['amount_cents'];
        expect($total)->toBeGreaterThan(0);
    }
});
