<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\User;
use App\Services\SearchService;
use Illuminate\Support\Facades\Auth;

class SearchQuery
{
    public function __construct(
        private readonly SearchService $searchService,
    ) {}

    /**
     * @param  array<string, mixed>  $args
     * @return array{
     *   data: list<array{
     *     entity: \Illuminate\Database\Eloquent\Model,
     *     matches: list<array{
     *       field: string,
     *       text: string,
     *       matchedText: string,
     *       start: int,
     *       end: int,
     *       snippet: string
     *     }>
     *   }>,
     *   paginatorInfo: array{
     *     currentPage: int,
     *     perPage: int,
     *     total: int,
     *     lastPage: int,
     *     hasMorePages: bool
     *   }
     * }
     */
    public function __invoke($_, array $args): array
    {
        $query = trim((string) ($args['query'] ?? ''));
        $perPage = max(1, min((int) ($args['first'] ?? 20), 50));
        $page = max(1, (int) ($args['page'] ?? 1));

        /** @var User $user */
        $user = Auth::user();

        return $this->searchService->search(
            user: $user,
            query: $query,
            rawTypes: $args['types'] ?? [],
            perPage: $perPage,
            page: $page,
        );
    }
}
