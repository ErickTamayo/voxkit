<?php

declare(strict_types=1);

use App\GraphQL\Queries\SearchQuery;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    Mockery::close();
});

function emptySearchPayload(): array
{
    return [
        'data' => [],
        'paginatorInfo' => [
            'currentPage' => 1,
            'perPage' => 20,
            'total' => 0,
            'lastPage' => 1,
            'hasMorePages' => false,
        ],
    ];
}

test('search query resolver trims query and clamps pagination args', function () {
    $user = User::factory()->create();
    Auth::shouldReceive('user')->once()->andReturn($user);

    $service = Mockery::mock(SearchService::class);
    $service->shouldReceive('search')
        ->once()
        ->withArgs(function (User $resolvedUser, string $query, mixed $types, int $perPage, int $page) use ($user): bool {
            return $resolvedUser->is($user)
                && $query === 'acme'
                && $types === ['JOB']
                && $perPage === 50
                && $page === 1;
        })
        ->andReturn(emptySearchPayload());

    $resolver = new SearchQuery($service);
    $result = $resolver(null, [
        'query' => '   acme   ',
        'types' => ['JOB'],
        'first' => 999,
        'page' => -9,
    ]);

    expect($result)->toBe(emptySearchPayload());
});

test('search query resolver applies defaults when optional args are omitted', function () {
    $user = User::factory()->create();
    Auth::shouldReceive('user')->once()->andReturn($user);

    $service = Mockery::mock(SearchService::class);
    $service->shouldReceive('search')
        ->once()
        ->withArgs(function (User $resolvedUser, string $query, mixed $types, int $perPage, int $page) use ($user): bool {
            return $resolvedUser->is($user)
                && $query === 'needle'
                && $types === []
                && $perPage === 20
                && $page === 1;
        })
        ->andReturn(emptySearchPayload());

    $resolver = new SearchQuery($service);
    $result = $resolver(null, [
        'query' => 'needle',
    ]);

    expect($result)->toBe(emptySearchPayload());
});

test('search query resolver uses empty query when query arg is omitted', function () {
    $user = User::factory()->create();
    Auth::shouldReceive('user')->once()->andReturn($user);

    $service = Mockery::mock(SearchService::class);
    $service->shouldReceive('search')
        ->once()
        ->withArgs(function (User $resolvedUser, string $query, mixed $types, int $perPage, int $page) use ($user): bool {
            return $resolvedUser->is($user)
                && $query === ''
                && $types === ['CONTACT']
                && $perPage === 5
                && $page === 2;
        })
        ->andReturn(emptySearchPayload());

    $resolver = new SearchQuery($service);
    $result = $resolver(null, [
        'types' => ['CONTACT'],
        'first' => 5,
        'page' => 2,
    ]);

    expect($result)->toBe(emptySearchPayload());
});
