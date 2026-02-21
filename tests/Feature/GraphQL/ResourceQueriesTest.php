<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\Attachment;
use App\Models\Audition;
use App\Models\BusinessProfile;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\ExpenseDefinition;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Job;
use App\Models\Platform;
use App\Models\UsageRight;
use App\Models\User;

const DEFAULT_SELECTION = 'id user_id';

function graphqlFindQuery(string $field, string $selection = DEFAULT_SELECTION): string
{
    $selection = trim($selection);

    return <<<GRAPHQL
    query (
        \$id: ULID!
    ) {
        {$field}(id: \$id) {
            {$selection}
        }
    }
    GRAPHQL;
}

function graphqlPaginatorQuery(string $field, string $selection = DEFAULT_SELECTION): string
{
    $selection = trim($selection);

    return <<<GRAPHQL
    query {
        {$field}(first: 50) {
            data {
                {$selection}
            }
        }
    }
    GRAPHQL;
}

dataset('guardedFindQueries', function () {
    return [
        'attachment' => [[
            'field' => 'attachment',
            'selection' => DEFAULT_SELECTION,
            'assertUserId' => true,
            'factory' => function (User $user): Attachment {
                $job = Job::factory()->for($user)->create();

                return Attachment::factory()->for($user)->create([
                    'attachable_type' => Job::class,
                    'attachable_id' => $job->id,
                ]);
            },
        ]],
        'audition' => [[
            'field' => 'audition',
            'selection' => DEFAULT_SELECTION,
            'assertUserId' => true,
            'factory' => fn (User $user): Audition => Audition::factory()->create(['user_id' => $user->id]),
        ]],
        'businessProfile' => [[
            'field' => 'businessProfile',
            'selection' => DEFAULT_SELECTION,
            'assertUserId' => true,
            'factory' => fn (User $user): BusinessProfile => $user->businessProfile()->firstOrFail(),
        ]],
        'contact' => [[
            'field' => 'contact',
            'selection' => DEFAULT_SELECTION,
            'assertUserId' => true,
            'factory' => fn (User $user): Contact => Contact::factory()->create(['user_id' => $user->id]),
        ]],
        'expense' => [[
            'field' => 'expense',
            'selection' => DEFAULT_SELECTION,
            'assertUserId' => true,
            'factory' => fn (User $user): Expense => Expense::factory()->create(['user_id' => $user->id]),
        ]],
        'expenseDefinition' => [[
            'field' => 'expenseDefinition',
            'selection' => DEFAULT_SELECTION,
            'assertUserId' => true,
            'factory' => fn (User $user): ExpenseDefinition => ExpenseDefinition::factory()->create(['user_id' => $user->id]),
        ]],
        'invoice' => [[
            'field' => 'invoice',
            'selection' => DEFAULT_SELECTION,
            'assertUserId' => true,
            'factory' => fn (User $user): Invoice => Invoice::factory()->create(['user_id' => $user->id]),
        ]],
        'job' => [[
            'field' => 'job',
            'selection' => DEFAULT_SELECTION,
            'assertUserId' => true,
            'factory' => fn (User $user): Job => Job::factory()->create(['user_id' => $user->id]),
        ]],
        'platform' => [[
            'field' => 'platform',
            'selection' => DEFAULT_SELECTION,
            'assertUserId' => true,
            'factory' => fn (User $user): Platform => Platform::factory()->create(['user_id' => $user->id]),
        ]],
        'agent' => [[
            'field' => 'agent',
            'selection' => 'id',
            'assertUserId' => false,
            'factory' => function (User $user): Agent {
                $agent = Agent::factory()->create();
                Contact::factory()->create([
                    'user_id' => $user->id,
                    'contactable_type' => Agent::class,
                    'contactable_id' => $agent->id,
                ]);

                return $agent;
            },
        ]],
        'client' => [[
            'field' => 'client',
            'selection' => 'id',
            'assertUserId' => false,
            'factory' => function (User $user): Client {
                $client = Client::factory()->create();
                Contact::factory()->create([
                    'user_id' => $user->id,
                    'contactable_type' => Client::class,
                    'contactable_id' => $client->id,
                ]);

                return $client;
            },
        ]],
        'usageRight' => [[
            'field' => 'usageRight',
            'selection' => 'id',
            'assertUserId' => false,
            'factory' => function (User $user): UsageRight {
                $job = Job::factory()->create(['user_id' => $user->id]);

                return UsageRight::factory()->create([
                    'usable_type' => Job::class,
                    'usable_id' => $job->id,
                ]);
            },
        ]],
        'invoiceItem' => [[
            'field' => 'invoiceItem',
            'selection' => 'id',
            'assertUserId' => false,
            'factory' => function (User $user): InvoiceItem {
                $invoice = Invoice::factory()->create(['user_id' => $user->id]);

                return InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);
            },
        ]],
    ];
});

dataset('paginatorQueries', function () {
    return [
        'attachments' => [[
            'field' => 'attachments',
            'factory' => function (User $user): Attachment {
                $job = Job::factory()->for($user)->create();

                return Attachment::factory()->for($user)->create([
                    'attachable_type' => Job::class,
                    'attachable_id' => $job->id,
                ]);
            },
        ]],
        'auditions' => [[
            'field' => 'auditions',
            'factory' => fn (User $user): Audition => Audition::factory()->create(['user_id' => $user->id]),
        ]],
        'businessProfiles' => [[
            'field' => 'businessProfiles',
            'factory' => fn (User $user): BusinessProfile => $user->businessProfile()->firstOrFail(),
        ]],
        'contacts' => [[
            'field' => 'contacts',
            'factory' => fn (User $user): Contact => Contact::factory()->create(['user_id' => $user->id]),
        ]],
        'expenses' => [[
            'field' => 'expenses',
            'factory' => fn (User $user): Expense => Expense::factory()->create(['user_id' => $user->id]),
        ]],
        'expenseDefinitions' => [[
            'field' => 'expenseDefinitions',
            'factory' => fn (User $user): ExpenseDefinition => ExpenseDefinition::factory()->create(['user_id' => $user->id]),
        ]],
        'invoices' => [[
            'field' => 'invoices',
            'factory' => fn (User $user): Invoice => Invoice::factory()->create(['user_id' => $user->id]),
        ]],
        'jobs' => [[
            'field' => 'jobs',
            'factory' => fn (User $user): Job => Job::factory()->create(['user_id' => $user->id]),
        ]],
        'platforms' => [[
            'field' => 'platforms',
            'factory' => fn (User $user): Platform => Platform::factory()->create(['user_id' => $user->id]),
        ]],
    ];
});

test('guarded find queries return records for authenticated users', function (array $resource) {
    $user = actingAsUser();
    $model = $resource['factory']($user);

    $response = $this->graphQL(graphqlFindQuery($resource['field'], $resource['selection']), [
        'id' => $model->id,
    ]);

    $response->assertGraphQLErrorFree();
    expect($response->json("data.{$resource['field']}.id"))->toBe($model->id);

    if ($resource['assertUserId']) {
        expect($response->json("data.{$resource['field']}.user_id"))->toBe($user->id);
    }
})->with('guardedFindQueries');

test('guarded find queries require authentication', function (array $resource) {
    $user = User::factory()->create();
    $model = $resource['factory']($user);

    $response = $this->graphQL(graphqlFindQuery($resource['field'], $resource['selection']), [
        'id' => $model->id,
    ]);

    $response->assertGraphQLErrorMessage('Unauthenticated.');
})->with('guardedFindQueries');

test('paginator queries only return authenticated user records', function (array $resource) {
    $user = actingAsUser();
    $otherUser = User::factory()->create();

    $resource['factory']($user);
    $resource['factory']($user);
    $resource['factory']($otherUser);

    $response = $this->graphQL(graphqlPaginatorQuery($resource['field']));

    $response->assertGraphQLErrorFree();

    $data = collect($response->json("data.{$resource['field']}.data"));
    expect($data)->not()->toBeEmpty();
    expect($data->pluck('user_id')->unique()->values()->all())->toBe([$user->id]);
})->with('paginatorQueries');
