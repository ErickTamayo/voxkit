<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Job;
use App\Models\Platform;
use App\Models\User;

beforeEach(function () {
    config()->set('scout.driver', 'collection');
});

function searchQuery(): string
{
    return <<<'GRAPHQL'
    query Search($query: String, $types: [SearchEntityType!], $first: Int, $page: Int) {
      search(query: $query, types: $types, first: $first, page: $page) {
        data {
          entity {
            __typename
            ... on Contact {
              id
              user_id
              name
            }
            ... on Client {
              id
            }
            ... on Agent {
              id
            }
            ... on Job {
              id
              project_title
            }
            ... on Platform {
              id
              name
            }
          }
          matches {
            field
            text
            matchedText
            start
            end
            snippet
          }
        }
        paginatorInfo {
          currentPage
          perPage
          total
          lastPage
          hasMorePages
        }
      }
    }
    GRAPHQL;
}

test('search returns most recently updated entities when query is omitted', function () {
    $user = actingAsUser();
    $baseTime = now()->startOfSecond();

    Contact::factory()->create([
        'user_id' => $user->id,
        'name' => 'Browse Oldest',
        'updated_at' => $baseTime->copy()->subMinutes(10),
    ]);

    $middle = Contact::factory()->create([
        'user_id' => $user->id,
        'name' => 'Browse Middle',
        'updated_at' => $baseTime->copy()->subMinutes(5),
    ]);

    $newest = Contact::factory()->create([
        'user_id' => $user->id,
        'name' => 'Browse Newest',
        'updated_at' => $baseTime,
    ]);

    $response = $this->graphQL(searchQuery(), [
        'types' => ['CONTACT'],
        'first' => 2,
        'page' => 1,
    ]);

    $response->assertGraphQLErrorFree();

    $hits = collect($response->json('data.search.data'))
        ->filter(fn (array $row): bool => ($row['entity']['__typename'] ?? null) === 'Contact')
        ->values();

    expect($hits)->toHaveCount(2);
    expect($hits->pluck('entity.id')->all())->toBe([
        (string) $newest->id,
        (string) $middle->id,
    ]);
    expect(collect($hits[0]['matches']))->toBeEmpty();
    expect($response->json('data.search.paginatorInfo.currentPage'))->toBe(1);
    expect($response->json('data.search.paginatorInfo.perPage'))->toBe(2);
    expect($response->json('data.search.paginatorInfo.total'))->toBe(3);
    expect($response->json('data.search.paginatorInfo.lastPage'))->toBe(2);
    expect($response->json('data.search.paginatorInfo.hasMorePages'))->toBeTrue();
});

test('search is scoped to the authenticated user', function () {
    $owner = actingAsUser();
    $otherUser = User::factory()->create();

    $ownerContact = Contact::factory()->create([
        'user_id' => $owner->id,
        'name' => 'Scoped Contact Alpha',
    ]);

    Contact::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Scoped Contact Alpha',
    ]);

    $response = $this->graphQL(searchQuery(), [
        'query' => 'Scoped Contact Alpha',
        'first' => 20,
        'page' => 1,
    ]);

    $response->assertGraphQLErrorFree();

    $results = $response->json('data.search.data');

    expect($results)->toBeArray();
    $contactRows = collect($results)
        ->filter(fn (array $row): bool => ($row['entity']['__typename'] ?? null) === 'Contact')
        ->values();

    expect($contactRows->pluck('entity.id')->contains($ownerContact->id))->toBeTrue();
    expect($contactRows->every(fn (array $row): bool => ($row['entity']['user_id'] ?? null) === $owner->id))->toBeTrue();
});

test('search can be filtered by type', function () {
    $user = actingAsUser();

    $client = Client::factory()->create();
    Contact::factory()->create([
        'user_id' => $user->id,
        'contactable_type' => Client::class,
        'contactable_id' => $client->id,
        'name' => 'Type Filter Needle',
    ]);

    $agent = Agent::factory()->create();
    Contact::factory()->create([
        'user_id' => $user->id,
        'contactable_type' => Agent::class,
        'contactable_id' => $agent->id,
        'name' => 'Type Filter Needle',
    ]);

    $response = $this->graphQL(searchQuery(), [
        'query' => 'Type Filter Needle',
        'types' => ['CLIENT'],
        'first' => 20,
        'page' => 1,
    ]);

    $response->assertGraphQLErrorFree();

    $typenames = collect($response->json('data.search.data'))->pluck('entity.__typename')->all();
    $matches = collect($response->json('data.search.data.0.matches'));

    expect($typenames)->toBeArray();
    expect($typenames)->not->toBeEmpty();
    expect(collect($typenames)->unique()->all())->toBe(['Client']);
    expect($matches->pluck('field')->contains('contact.name'))->toBeTrue();
    expect($matches->pluck('field')->contains('contact__name'))->toBeFalse();
    expect($matches->pluck('field')->contains('contact_name'))->toBeFalse();
    expect($matches->pluck('field')->contains('name'))->toBeFalse();
});

test('search normalizes relation field matches with dot notation', function () {
    $user = actingAsUser();

    $client = Contact::factory()->create([
        'user_id' => $user->id,
        'name' => 'Signal Ridge Media',
    ]);

    $agentRecord = Agent::factory()->create();
    $agent = Contact::factory()->create([
        'user_id' => $user->id,
        'contactable_type' => Agent::class,
        'contactable_id' => $agentRecord->id,
        'name' => 'Acme Talent Agency',
    ]);

    Job::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'agent_id' => $agent->id,
        'project_title' => 'National Campaign',
        'brand_name' => 'Signal Brand',
        'character_name' => 'Narrator',
    ]);

    $response = $this->graphQL(searchQuery(), [
        'query' => 'acme',
        'types' => ['JOB'],
        'first' => 20,
        'page' => 1,
    ]);

    $response->assertGraphQLErrorFree();

    $jobHit = collect($response->json('data.search.data'))
        ->first(fn (array $row): bool => ($row['entity']['__typename'] ?? null) === 'Job');

    expect($jobHit)->not->toBeNull();

    $fields = collect($jobHit['matches'])->pluck('field');
    expect($fields->contains('agent.name'))->toBeTrue();
    expect($fields->contains('agent__name'))->toBeFalse();
    expect($fields->contains('agent_name'))->toBeFalse();
    expect($fields->contains('character.name'))->toBeFalse();
});

test('search without types can return multiple entity types', function () {
    $user = actingAsUser();

    Contact::factory()->create([
        'user_id' => $user->id,
        'name' => 'Cross Entity Needle',
    ]);

    Platform::factory()->create([
        'user_id' => $user->id,
        'name' => 'Cross Entity Needle',
    ]);

    $response = $this->graphQL(searchQuery(), [
        'query' => 'Cross Entity Needle',
        'first' => 20,
        'page' => 1,
    ]);

    $response->assertGraphQLErrorFree();

    $typenames = collect($response->json('data.search.data'))->pluck('entity.__typename')->unique()->all();

    expect($typenames)->toContain('Contact');
    expect($typenames)->toContain('Platform');
});

test('search returns paginator info', function () {
    $user = actingAsUser();

    Contact::factory()->count(3)->create([
        'user_id' => $user->id,
        'name' => 'Paginated Needle',
    ]);

    $response = $this->graphQL(searchQuery(), [
        'query' => 'Paginated Needle',
        'types' => ['CONTACT'],
        'first' => 2,
        'page' => 1,
    ]);

    $response->assertGraphQLErrorFree();

    expect($response->json('data.search.paginatorInfo.currentPage'))->toBe(1);
    expect($response->json('data.search.paginatorInfo.perPage'))->toBe(2);
    expect($response->json('data.search.paginatorInfo.total'))->toBe(3);
    expect($response->json('data.search.paginatorInfo.lastPage'))->toBe(2);
    expect($response->json('data.search.paginatorInfo.hasMorePages'))->toBeTrue();
});

test('search includes entity terms to match by model kind', function () {
    $user = actingAsUser();

    Platform::factory()->create([
        'user_id' => $user->id,
        'name' => 'Voices Dot Com',
    ]);

    $response = $this->graphQL(searchQuery(), [
        'query' => 'platform',
        'first' => 20,
        'page' => 1,
    ]);

    $response->assertGraphQLErrorFree();

    $platformHit = collect($response->json('data.search.data'))
        ->first(fn (array $row): bool => ($row['entity']['__typename'] ?? null) === 'Platform');

    expect($platformHit)->not->toBeNull();
    expect(collect($platformHit['matches'])->pluck('field')->contains('entity_terms'))->toBeTrue();
});

test('search returns field-level match metadata', function () {
    $user = actingAsUser();

    Contact::factory()->create([
        'user_id' => $user->id,
        'name' => 'Acme Advertising Agency',
    ]);

    $response = $this->graphQL(searchQuery(), [
        'query' => 'acme',
        'types' => ['CONTACT'],
        'first' => 20,
        'page' => 1,
    ]);

    $response->assertGraphQLErrorFree();

    $firstContactHit = collect($response->json('data.search.data'))
        ->first(fn (array $row): bool => ($row['entity']['__typename'] ?? null) === 'Contact');

    expect($firstContactHit)->not->toBeNull();

    $nameMatch = collect($firstContactHit['matches'])
        ->first(fn (array $match): bool => ($match['field'] ?? null) === 'name');

    expect($nameMatch)->not->toBeNull();
    expect(collect($firstContactHit['matches'])->pluck('field')->contains('searchable_text'))->toBeFalse();
    expect($nameMatch['text'])->toBe('Acme Advertising Agency');
    expect($nameMatch['start'])->toBe(0);
    expect($nameMatch['end'])->toBe(4);
    expect(mb_strtolower((string) $nameMatch['matchedText']))->toBe('acme');
});

test('search clamps first and page arguments', function () {
    $user = actingAsUser();

    Contact::factory()->count(60)->create([
        'user_id' => $user->id,
        'name' => 'Clamp Needle',
    ]);

    $response = $this->graphQL(searchQuery(), [
        'query' => 'Clamp Needle',
        'types' => ['CONTACT'],
        'first' => 999,
        'page' => -5,
    ]);

    $response->assertGraphQLErrorFree();

    expect($response->json('data.search.paginatorInfo.currentPage'))->toBe(1);
    expect($response->json('data.search.paginatorInfo.perPage'))->toBe(50);
    expect($response->json('data.search.paginatorInfo.total'))->toBe(60);
    expect($response->json('data.search.paginatorInfo.lastPage'))->toBe(2);
    expect($response->json('data.search.paginatorInfo.hasMorePages'))->toBeTrue();
    expect(count($response->json('data.search.data')))->toBe(50);
});

test('search keeps requested page when it is beyond last page', function () {
    $user = actingAsUser();

    Contact::factory()->count(3)->create([
        'user_id' => $user->id,
        'name' => 'Far Page Needle',
    ]);

    $response = $this->graphQL(searchQuery(), [
        'query' => 'Far Page Needle',
        'types' => ['CONTACT'],
        'first' => 2,
        'page' => 9,
    ]);

    $response->assertGraphQLErrorFree();

    expect($response->json('data.search.data'))->toBe([]);
    expect($response->json('data.search.paginatorInfo.currentPage'))->toBe(9);
    expect($response->json('data.search.paginatorInfo.perPage'))->toBe(2);
    expect($response->json('data.search.paginatorInfo.total'))->toBe(3);
    expect($response->json('data.search.paginatorInfo.lastPage'))->toBe(2);
    expect($response->json('data.search.paginatorInfo.hasMorePages'))->toBeFalse();
});
