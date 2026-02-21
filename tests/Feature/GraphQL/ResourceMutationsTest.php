<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\Attachment;
use App\Models\Audition;
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
use Illuminate\Support\Str;

const RESOURCE_MUTATION_SELECTION = 'id user_id';

dataset('resourceMutations', function () {
    return [
        'attachment' => [[
            'table' => 'attachments',
            'selection' => RESOURCE_MUTATION_SELECTION,
            'assertUserId' => true,
            'createMutation' => 'createAttachment',
            'createInputType' => 'CreateAttachmentInput',
            'updateMutation' => 'updateAttachment',
            'updateInputType' => 'UpdateAttachmentInput',
            'deleteMutation' => 'deleteAttachment',
            'factory' => function (User $user): Attachment {
                $job = Job::factory()->for($user)->create();

                return Attachment::factory()->for($user)->create([
                    'attachable_type' => Job::class,
                    'attachable_id' => $job->id,
                ]);
            },
            'createInput' => function (User $user): array {
                $job = Job::factory()->for($user)->create();

                return [
                    'attachable_type' => Job::class,
                    'attachable_id' => $job->id,
                    'filename' => 'attachment-'.Str::random(6).'.pdf',
                    'original_filename' => 'original.pdf',
                    'mime_type' => 'application/pdf',
                    'size' => 1024,
                    'disk' => 'local',
                    'path' => 'attachments/'.Str::random(10).'.pdf',
                    'category' => 'SCRIPT',
                ];
            },
            'createCheck' => 'filename',
            'updateInput' => fn (): array => ['filename' => 'updated-'.Str::random(6).'.pdf'],
            'updateCheck' => 'filename',
        ]],
        'audition' => [[
            'table' => 'auditions',
            'selection' => RESOURCE_MUTATION_SELECTION,
            'assertUserId' => true,
            'createMutation' => 'createAudition',
            'createInputType' => 'CreateAuditionInput',
            'updateMutation' => 'updateAudition',
            'updateInputType' => 'UpdateAuditionInput',
            'deleteMutation' => 'deleteAudition',
            'factory' => fn (User $user): Audition => Audition::factory()->create(['user_id' => $user->id]),
            'createInput' => function (User $user): array {
                $platform = Platform::factory()->create(['user_id' => $user->id]);

                return [
                    'sourceable_type' => Platform::class,
                    'sourceable_id' => $platform->id,
                    'project_title' => 'Audition '.Str::random(6),
                    'category' => 'COMMERCIAL',
                    'rate_type' => 'FLAT',
                    'status' => 'RECEIVED',
                ];
            },
            'createCheck' => 'project_title',
            'updateInput' => fn (): array => ['project_title' => 'Updated Audition '.Str::random(6)],
            'updateCheck' => 'project_title',
        ]],
        'contact' => [[
            'table' => 'contacts',
            'selection' => RESOURCE_MUTATION_SELECTION,
            'assertUserId' => true,
            'createMutation' => 'createContact',
            'createInputType' => 'CreateContactInput',
            'updateMutation' => 'updateContact',
            'updateInputType' => 'UpdateContactInput',
            'deleteMutation' => 'deleteContact',
            'factory' => fn (User $user): Contact => Contact::factory()->create(['user_id' => $user->id]),
            'createInput' => function (User $user): array {
                $client = Client::factory()->create();

                return [
                    'contactable_type' => Client::class,
                    'contactable_id' => $client->id,
                    'name' => 'Alex '.Str::random(6),
                ];
            },
            'createCheck' => 'name',
            'updateInput' => fn (): array => ['name' => 'Updated contact name'],
            'updateCheck' => 'name',
        ]],
        'agent' => [[
            'table' => 'agents',
            'selection' => 'id',
            'assertUserId' => false,
            'createMutation' => 'createAgent',
            'createInputType' => 'CreateAgentWithContactInput',
            'updateMutation' => 'updateAgent',
            'updateInputType' => 'UpdateAgentWithContactInput',
            'deleteMutation' => 'deleteAgent',
            'factory' => function (User $user): Agent {
                $agent = Agent::factory()->create();
                Contact::factory()->create([
                    'user_id' => $user->id,
                    'contactable_type' => Agent::class,
                    'contactable_id' => $agent->id,
                ]);

                return $agent;
            },
            'createInput' => fn (User $user): array => [
                'contact' => [
                    'name' => 'Agent Contact '.Str::random(6),
                ],
                'agency_name' => 'Agency '.Str::random(6),
            ],
            'createCheck' => 'agency_name',
            'updateInput' => fn (): array => ['agency_name' => 'Updated Agency '.Str::random(6)],
            'updateCheck' => 'agency_name',
        ]],
        'client' => [[
            'table' => 'clients',
            'selection' => 'id',
            'assertUserId' => false,
            'createMutation' => 'createClient',
            'createInputType' => 'CreateClientInput',
            'updateMutation' => 'updateClient',
            'updateInputType' => 'UpdateClientInput',
            'deleteMutation' => 'deleteClient',
            'factory' => function (User $user): Client {
                $client = Client::factory()->create();
                Contact::factory()->create([
                    'user_id' => $user->id,
                    'contactable_type' => Client::class,
                    'contactable_id' => $client->id,
                ]);

                return $client;
            },
            'createInput' => fn (User $user): array => [
                'type' => 'COMPANY',
                'industry' => 'Gaming '.Str::random(4),
            ],
            'createCheck' => 'industry',
            'updateInput' => fn (): array => ['industry' => 'Updated Industry '.Str::random(4)],
            'updateCheck' => 'industry',
        ]],
        'platform' => [[
            'table' => 'platforms',
            'selection' => RESOURCE_MUTATION_SELECTION,
            'assertUserId' => true,
            'createMutation' => 'createPlatform',
            'createInputType' => 'CreatePlatformInput',
            'updateMutation' => 'updatePlatform',
            'updateInputType' => 'UpdatePlatformInput',
            'deleteMutation' => 'deletePlatform',
            'factory' => fn (User $user): Platform => Platform::factory()->create(['user_id' => $user->id]),
            'createInput' => fn (User $user): array => ['name' => 'Platform '.Str::random(6)],
            'createCheck' => 'name',
            'updateInput' => fn (): array => ['name' => 'Updated Platform '.Str::random(6)],
            'updateCheck' => 'name',
        ]],
        'job' => [[
            'table' => 'jobs',
            'selection' => RESOURCE_MUTATION_SELECTION,
            'assertUserId' => true,
            'createMutation' => 'createJob',
            'createInputType' => 'CreateJobInput',
            'updateMutation' => 'updateJob',
            'updateInputType' => 'UpdateJobInput',
            'deleteMutation' => 'deleteJob',
            'factory' => fn (User $user): Job => Job::factory()->create(['user_id' => $user->id]),
            'createInput' => function (User $user): array {
                $client = Contact::factory()->create(['user_id' => $user->id]);

                return [
                    'client' => [
                        'id' => $client->id,
                    ],
                    'project_title' => 'VO Job '.Str::random(6),
                    'category' => 'COMMERCIAL',
                    'contracted_rate' => ['amount_cents' => 125000, 'currency' => 'USD'],
                    'rate_type' => 'FLAT',
                    'status' => 'BOOKED',
                ];
            },
            'createCheck' => 'project_title',
            'updateInput' => fn (): array => ['project_title' => 'Updated Job '.Str::random(6)],
            'updateCheck' => 'project_title',
        ]],
        'usageRight' => [[
            'table' => 'usage_rights',
            'selection' => 'id',
            'assertUserId' => false,
            'createMutation' => 'createUsageRight',
            'createInputType' => 'CreateUsageRightInput',
            'updateMutation' => 'updateUsageRight',
            'updateInputType' => 'UpdateUsageRightInput',
            'deleteMutation' => 'deleteUsageRight',
            'factory' => function (User $user): UsageRight {
                $job = Job::factory()->create(['user_id' => $user->id]);

                return UsageRight::factory()->create([
                    'usable_type' => Job::class,
                    'usable_id' => $job->id,
                ]);
            },
            'createInput' => function (User $user): array {
                $job = Job::factory()->create(['user_id' => $user->id]);

                return [
                    'usable_type' => Job::class,
                    'usable_id' => $job->id,
                    'type' => 'BROADCAST',
                    'media_types' => ['TV'],
                    'geographic_scope' => 'NATIONAL',
                    'duration_type' => 'FIXED',
                    'duration_months' => 12,
                ];
            },
            'createCheck' => 'duration_months',
            'updateInput' => fn (): array => ['duration_months' => 24],
            'updateCheck' => 'duration_months',
        ]],
        'invoice' => [[
            'table' => 'invoices',
            'selection' => RESOURCE_MUTATION_SELECTION,
            'assertUserId' => true,
            'createMutation' => 'createInvoice',
            'createInputType' => 'CreateInvoiceInput',
            'updateMutation' => 'updateInvoice',
            'updateInputType' => 'UpdateInvoiceInput',
            'deleteMutation' => 'deleteInvoice',
            'factory' => fn (User $user): Invoice => Invoice::factory()->create(['user_id' => $user->id]),
            'createInput' => function (User $user): array {
                $client = Contact::factory()->create(['user_id' => $user->id]);

                return [
                    'client_id' => $client->id,
                    'invoice_number' => 'INV-'.Str::upper(Str::random(6)),
                    'issued_at' => now()->timestamp * 1000,
                    'due_at' => now()->addDays(30)->timestamp * 1000,
                    'subtotal' => ['amount_cents' => 250000, 'currency' => 'USD'],
                    'tax_rate' => 0.1,
                    'tax_amount' => ['amount_cents' => 25000, 'currency' => 'USD'],
                    'total' => ['amount_cents' => 275000, 'currency' => 'USD'],
                    'status' => 'DRAFT',
                ];
            },
            'createCheck' => 'invoice_number',
            'updateInput' => fn (): array => ['invoice_number' => 'INV-'.Str::upper(Str::random(6))],
            'updateCheck' => 'invoice_number',
        ]],
        'invoiceItem' => [[
            'table' => 'invoice_items',
            'selection' => 'id',
            'assertUserId' => false,
            'createMutation' => 'createInvoiceItem',
            'createInputType' => 'CreateInvoiceItemInput',
            'updateMutation' => 'updateInvoiceItem',
            'updateInputType' => 'UpdateInvoiceItemInput',
            'deleteMutation' => 'deleteInvoiceItem',
            'factory' => function (User $user): InvoiceItem {
                $invoice = Invoice::factory()->create(['user_id' => $user->id]);

                return InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);
            },
            'createInput' => function (User $user): array {
                $invoice = Invoice::factory()->create(['user_id' => $user->id]);

                return [
                    'invoice_id' => $invoice->id,
                    'description' => 'Session fee',
                    'quantity' => 1,
                    'unit_price' => 150000,
                    'amount' => 150000,
                ];
            },
            'createCheck' => 'description',
            'updateInput' => fn (): array => ['description' => 'Updated line item'],
            'updateCheck' => 'description',
        ]],
        'expenseDefinition' => [[
            'table' => 'expense_definitions',
            'selection' => RESOURCE_MUTATION_SELECTION,
            'assertUserId' => true,
            'createMutation' => 'createExpenseDefinition',
            'createInputType' => 'CreateExpenseDefinitionInput',
            'updateMutation' => 'updateExpenseDefinition',
            'updateInputType' => 'UpdateExpenseDefinitionInput',
            'deleteMutation' => 'deleteExpenseDefinition',
            'factory' => fn (User $user): ExpenseDefinition => ExpenseDefinition::factory()->create(['user_id' => $user->id]),
            'createInput' => fn (User $user): array => [
                'name' => 'Subscription '.Str::random(6),
                'amount' => ['amount_cents' => 4500, 'currency' => 'USD'],
                'category' => 'SOFTWARE',
                'recurrence' => 'MONTHLY',
                'starts_at' => now()->timestamp * 1000,
            ],
            'createCheck' => 'name',
            'updateInput' => fn (): array => ['name' => 'Updated Subscription '.Str::random(6)],
            'updateCheck' => 'name',
        ]],
        'expense' => [[
            'table' => 'expenses',
            'selection' => RESOURCE_MUTATION_SELECTION,
            'assertUserId' => true,
            'createMutation' => 'createExpense',
            'createInputType' => 'CreateExpenseInput',
            'updateMutation' => 'updateExpense',
            'updateInputType' => 'UpdateExpenseInput',
            'deleteMutation' => 'deleteExpense',
            'factory' => fn (User $user): Expense => Expense::factory()->create(['user_id' => $user->id]),
            'createInput' => fn (User $user): array => [
                'description' => 'Studio rental',
                'amount' => ['amount_cents' => 12500, 'currency' => 'USD'],
                'category' => 'STUDIO',
                'date' => now()->timestamp * 1000,
            ],
            'createCheck' => 'description',
            'updateInput' => fn (): array => ['description' => 'Updated expense description'],
            'updateCheck' => 'description',
        ]],
    ];
});

function graphqlCreateMutation(string $name, string $inputType, string $selection = RESOURCE_MUTATION_SELECTION): string
{
    $selection = trim($selection);

    return <<<GRAPHQL
    mutation (
        \$input: {$inputType}!
    ) {
        {$name}(input: \$input) {
            {$selection}
        }
    }
    GRAPHQL;
}

function graphqlUpdateMutation(string $name, string $inputType): string
{
    return <<<GRAPHQL
    mutation (
        \$id: ULID!
        \$input: {$inputType}!
    ) {
        {$name}(id: \$id, input: \$input) {
            id
        }
    }
    GRAPHQL;
}

function graphqlDeleteMutation(string $name): string
{
    return <<<GRAPHQL
    mutation (
        \$id: ULID!
    ) {
        {$name}(id: \$id) {
            id
        }
    }
    GRAPHQL;
}

test('resource create mutations store records and inject user id', function (array $resource) {
    $user = actingAsUser();
    $input = $resource['createInput']($user);

    $response = $this->graphQL(
        graphqlCreateMutation($resource['createMutation'], $resource['createInputType'], $resource['selection']),
        ['input' => $input],
    );

    $response->assertGraphQLErrorFree();

    $created = $response->json("data.{$resource['createMutation']}");
    expect($created)->not()->toBeNull();

    if ($resource['assertUserId']) {
        expect($created['user_id'])->toBe($user->id);
    }

    $assertData = [
        'id' => $created['id'],
        $resource['createCheck'] => $input[$resource['createCheck']],
    ];

    if ($resource['assertUserId']) {
        $assertData['user_id'] = $user->id;
    }

    $this->assertDatabaseHas($resource['table'], $assertData);
})->with('resourceMutations');

test('resource update mutations persist changes', function (array $resource) {
    $user = actingAsUser();
    $model = $resource['factory']($user);
    $input = $resource['updateInput']();

    $response = $this->graphQL(graphqlUpdateMutation($resource['updateMutation'], $resource['updateInputType']), [
        'id' => $model->id,
        'input' => $input,
    ]);

    $response->assertGraphQLErrorFree();

    $this->assertDatabaseHas($resource['table'], [
        'id' => $model->id,
        $resource['updateCheck'] => $input[$resource['updateCheck']],
    ]);
})->with('resourceMutations');

test('resource update mutations return null for non-owners', function (array $resource) {
    $owner = User::factory()->create();
    $model = $resource['factory']($owner);
    $originalValue = $model->getAttribute($resource['updateCheck']);

    actingAsUser();

    $response = $this->graphQL(graphqlUpdateMutation($resource['updateMutation'], $resource['updateInputType']), [
        'id' => $model->id,
        'input' => $resource['updateInput'](),
    ]);

    $response->assertGraphQLErrorFree();
    expect($response->json("data.{$resource['updateMutation']}"))->toBeNull();

    $model->refresh();
    expect($model->getAttribute($resource['updateCheck']))->toBe($originalValue);
})->with('resourceMutations');

test('resource delete mutations soft delete records', function (array $resource) {
    $user = actingAsUser();
    $model = $resource['factory']($user);

    $response = $this->graphQL(graphqlDeleteMutation($resource['deleteMutation']), [
        'id' => $model->id,
    ]);

    $response->assertGraphQLErrorFree();
    expect($response->json("data.{$resource['deleteMutation']}.id"))->toBe($model->id);

    $this->assertSoftDeleted($resource['table'], [
        'id' => $model->id,
    ]);
})->with('resourceMutations');

test('resource delete mutations return null for non-owners', function (array $resource) {
    $owner = User::factory()->create();
    $model = $resource['factory']($owner);

    actingAsUser();

    $response = $this->graphQL(graphqlDeleteMutation($resource['deleteMutation']), [
        'id' => $model->id,
    ]);

    $response->assertGraphQLErrorFree();
    expect($response->json("data.{$resource['deleteMutation']}"))->toBeNull();

    $this->assertNotSoftDeleted($resource['table'], [
        'id' => $model->id,
    ]);
})->with('resourceMutations');

test('resource mutations require authentication', function (array $resource) {
    $input = $resource['createInput'](User::factory()->create());

    $createResponse = $this->graphQL(
        graphqlCreateMutation($resource['createMutation'], $resource['createInputType'], $resource['selection']),
        ['input' => $input],
    );

    $createResponse->assertGraphQLErrorMessage('Unauthenticated.');

    $user = User::factory()->create();
    $model = $resource['factory']($user);

    $updateResponse = $this->graphQL(graphqlUpdateMutation($resource['updateMutation'], $resource['updateInputType']), [
        'id' => $model->id,
        'input' => $resource['updateInput'](),
    ]);

    $updateResponse->assertGraphQLErrorMessage('Unauthenticated.');

    $deleteResponse = $this->graphQL(graphqlDeleteMutation($resource['deleteMutation']), [
        'id' => $model->id,
    ]);

    $deleteResponse->assertGraphQLErrorMessage('Unauthenticated.');
})->with('resourceMutations');
