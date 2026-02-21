<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\Job;
use App\Models\User;
use App\Search\SearchableEntities;
use App\Services\SearchService;
use Typesense\ApiCall;
use Typesense\Client as TypesenseClient;
use Typesense\Collections;
use Typesense\Exceptions\ObjectNotFound;

beforeEach(function () {
    Mockery::close();
});

class FakeTypesenseDocuments
{
    /**
     * @var array<string, mixed>
     */
    public array $lastOptions = [];

    /**
     * @param  array<string, mixed>  $response
     */
    public function __construct(
        private array $response = [],
        private ?Throwable $exception = null,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function search(array $options): array
    {
        $this->lastOptions = $options;

        if ($this->exception instanceof Throwable) {
            throw $this->exception;
        }

        return $this->response;
    }
}

class SearchServiceWithFakeTypesense extends SearchService
{
    public function __construct(private readonly TypesenseClient $client) {}

    protected function typesenseClient(): TypesenseClient
    {
        return $this->client;
    }
}

function makeTypesenseSearchService(FakeTypesenseDocuments $documents, array $availableCollections = []): SearchService
{
    $apiCall = Mockery::mock(ApiCall::class);
    $apiCall->shouldReceive('get')
        ->andReturnUsing(function (string $endPoint, array $params) use ($documents, $availableCollections): array {
            if ($endPoint === '/collections') {
                return $availableCollections;
            }

            if (str_ends_with($endPoint, '/documents/search')) {
                return $documents->search($params);
            }

            return [];
        });

    $collections = new Collections($apiCall);

    $client = Mockery::mock(TypesenseClient::class);
    $client->shouldReceive('getCollections')->andReturn($collections);

    return new SearchServiceWithFakeTypesense($client);
}

test('search service uses typesense branch and maps hits to hydrated models', function () {
    config()->set('scout.driver', 'collection');

    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'user_id' => $user->id,
        'name' => 'Acme Advertising Agency',
    ]);

    $documents = new FakeTypesenseDocuments([
        'found' => 1,
        'hits' => [
            [
                'document' => [
                    'entity_type' => 'contact',
                    'entity_id' => (string) $contact->id,
                    'user_id' => (string) $user->id,
                    'name' => 'Acme Advertising Agency',
                    'entity_terms' => 'contact contacts',
                ],
                'highlights' => [
                    [
                        'field' => 'name',
                        'snippet' => '<mark>Acme</mark> Advertising Agency',
                        'matched_tokens' => ['Acme'],
                    ],
                ],
            ],
        ],
    ]);

    config()->set('scout.driver', 'typesense');

    $service = makeTypesenseSearchService($documents);
    $result = $service->search($user, 'acme', ['CONTACT'], 10, 1);

    expect($result['paginatorInfo']['total'])->toBe(1);
    expect($result['data'])->toHaveCount(1);
    expect($result['data'][0]['entity']->is($contact))->toBeTrue();
    expect(collect($result['data'][0]['matches'])->pluck('field')->contains('name'))->toBeTrue();
    expect($documents->lastOptions['query_by'])->toBe(SearchableEntities::typesenseQueryBy());
    expect($documents->lastOptions['highlight_fields'])->toBe(SearchableEntities::typesenseHighlightFields());
    expect($documents->lastOptions['filter_by'])->toContain("user_id:={$user->id}");
    expect($documents->lastOptions['filter_by'])->toContain('entity_type:[contact]');
});

test('search service treats blank query as browse mode in typesense', function () {
    config()->set('scout.driver', 'collection');

    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'user_id' => $user->id,
        'name' => 'Recent Browse Result',
    ]);

    $documents = new FakeTypesenseDocuments([
        'found' => 1,
        'hits' => [
            [
                'document' => [
                    'entity_type' => 'contact',
                    'entity_id' => (string) $contact->id,
                    'user_id' => (string) $user->id,
                    'name' => 'Recent Browse Result',
                ],
            ],
        ],
    ]);

    config()->set('scout.driver', 'typesense');

    $service = makeTypesenseSearchService($documents);
    $result = $service->search($user, '', ['CONTACT'], 10, 3);

    expect($result['data'])->toHaveCount(1);
    expect($result['data'][0]['entity']->is($contact))->toBeTrue();
    expect($result['data'][0]['matches'])->toBe([]);
    expect($result['paginatorInfo']['currentPage'])->toBe(3);
    expect($result['paginatorInfo']['perPage'])->toBe(10);
    expect($result['paginatorInfo']['total'])->toBe(1);
    expect($documents->lastOptions['q'])->toBe('*');
    expect($documents->lastOptions['sort_by'])->toBe('updated_at:desc,entity_id:asc');
    expect($documents->lastOptions['query_by'])->toBe(SearchableEntities::typesenseQueryBy());
    expect($documents->lastOptions)->not->toHaveKey('highlight_fields');
});

test('search service treats blank query as browse mode in fallback driver', function () {
    config()->set('scout.driver', 'collection');

    $user = User::factory()->create();
    $baseTime = now()->startOfSecond();

    Contact::factory()->create([
        'user_id' => $user->id,
        'name' => 'Browse Older',
        'updated_at' => $baseTime->copy()->subMinutes(3),
    ]);

    $newer = Contact::factory()->create([
        'user_id' => $user->id,
        'name' => 'Browse Newer',
        'updated_at' => $baseTime,
    ]);

    $service = app(SearchService::class);
    $result = $service->search($user, '', ['CONTACT'], 1, 1);

    expect($result['paginatorInfo']['total'])->toBe(2);
    expect($result['paginatorInfo']['lastPage'])->toBe(2);
    expect($result['data'])->toHaveCount(1);
    expect($result['data'][0]['entity']->is($newer))->toBeTrue();
    expect($result['data'][0]['matches'])->toBe([]);
});

test('search service returns empty result when typesense collection is missing', function () {
    config()->set('scout.driver', 'typesense');

    $user = User::factory()->create();
    $documents = new FakeTypesenseDocuments(
        exception: new ObjectNotFound('Collection not found')
    );

    $service = makeTypesenseSearchService($documents, [
        ['name' => SearchableEntities::collectionName()],
    ]);

    $result = $service->search($user, 'acme', [], 10, 1);

    expect($result['data'])->toBe([]);
    expect($result['paginatorInfo']['total'])->toBe(0);
    expect($result['paginatorInfo']['lastPage'])->toBe(1);
});

test('search service returns empty result when typesense search throws', function () {
    config()->set('scout.driver', 'typesense');

    $user = User::factory()->create();
    $documents = new FakeTypesenseDocuments(
        exception: new RuntimeException('Typesense temporarily unavailable')
    );

    $service = makeTypesenseSearchService($documents);
    $result = $service->search($user, 'acme', [], 10, 1);

    expect($result['data'])->toBe([]);
    expect($result['paginatorInfo']['total'])->toBe(0);
});

test('search service normalizes relation fields and de-duplicates duplicate highlights', function () {
    config()->set('scout.driver', 'collection');

    $user = User::factory()->create();
    $client = Contact::factory()->create([
        'user_id' => $user->id,
        'name' => 'Acme Advertising Agency',
    ]);
    $job = Job::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'project_title' => 'National Campaign',
    ]);

    $documents = new FakeTypesenseDocuments([
        'found' => 1,
        'hits' => [
            [
                'document' => [
                    'entity_type' => 'job',
                    'entity_id' => (string) $job->id,
                    'user_id' => (string) $user->id,
                    'client__name' => 'Acme Advertising Agency',
                ],
                'highlights' => [
                    [
                        'field' => 'client__name',
                        'snippet' => 'Acme <mark>Advertising</mark> Agency',
                        'matched_tokens' => ['Advertising'],
                    ],
                ],
                'highlight' => [
                    'client__name' => [
                        'snippet' => 'Acme <mark>Advertising</mark> Agency',
                        'matched_tokens' => ['Advertising'],
                    ],
                    'searchable_text' => '<mark>Advertising</mark> Agency',
                ],
            ],
        ],
    ]);

    config()->set('scout.driver', 'typesense');

    $service = makeTypesenseSearchService($documents);
    $result = $service->search($user, 'advertising', ['JOB'], 10, 1);

    $fields = collect($result['data'][0]['matches'])->pluck('field');

    expect($fields->contains('client.name'))->toBeTrue();
    expect($fields->contains('client__name'))->toBeFalse();
    expect($fields->contains('searchable_text'))->toBeFalse();
    expect($fields->filter(fn (string $field): bool => $field === 'client.name')->count())->toBe(1);
});

test('search service supports legacy highlight string payload format', function () {
    config()->set('scout.driver', 'collection');

    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'user_id' => $user->id,
        'name' => 'Acme Advertising Agency',
    ]);

    $documents = new FakeTypesenseDocuments([
        'found' => 1,
        'hits' => [
            [
                'document' => [
                    'entity_type' => 'contact',
                    'entity_id' => (string) $contact->id,
                    'user_id' => (string) $user->id,
                    'name' => 'Acme Advertising Agency',
                ],
                'highlight' => [
                    'name' => '<mark>Acme</mark> Advertising Agency',
                ],
            ],
        ],
    ]);

    config()->set('scout.driver', 'typesense');

    $service = makeTypesenseSearchService($documents);
    $result = $service->search($user, 'acme', ['CONTACT'], 10, 1);

    $nameMatch = collect($result['data'][0]['matches'])
        ->first(fn (array $match): bool => ($match['field'] ?? null) === 'name');

    expect($nameMatch)->not->toBeNull();
    expect($nameMatch['start'])->toBe(0);
    expect($nameMatch['end'])->toBe(4);
    expect(mb_strtolower((string) $nameMatch['matchedText']))->toBe('acme');
});

test('search service fallback ordering is deterministic when timestamps tie', function () {
    config()->set('scout.driver', 'collection');

    $user = User::factory()->create();
    $updatedAt = now()->startOfSecond();

    $first = Contact::factory()->create([
        'user_id' => $user->id,
        'name' => 'Tie Break Needle',
        'updated_at' => $updatedAt,
    ]);

    $second = Contact::factory()->create([
        'user_id' => $user->id,
        'name' => 'Tie Break Needle',
        'updated_at' => $updatedAt,
    ]);

    $service = app(SearchService::class);
    $result = $service->search($user, 'tie break needle', ['CONTACT'], 20, 1);

    $returnedIds = array_values(array_map(
        static fn (array $item): string => (string) $item['entity']->id,
        $result['data']
    ));

    $expected = [(string) $first->id, (string) $second->id];
    sort($expected);

    expect($returnedIds)->toBe($expected);
});
