<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Search\SearchableEntities;
use App\Search\SearchEntityType;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Typesense\Client as TypesenseClient;
use Typesense\Exceptions\ObjectNotFound;

class SearchService
{
    /**
     * @return array{
     *   data: list<array{
     *     entity: Model,
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
    public function search(User $user, string $query, mixed $rawTypes, int $perPage, int $page): array
    {
        $requestedTypes = $this->resolveTypes($rawTypes);
        $driver = (string) config('scout.driver');
        $isBrowseQuery = $query === '';

        Log::debug('Search query received.', [
            'query' => $query,
            'user_id' => (string) $user->id,
            'driver' => $driver,
            'is_browse_query' => $isBrowseQuery,
            'types' => array_map(static fn (SearchEntityType $type): string => $type->value, $requestedTypes),
            'page' => $page,
            'per_page' => $perPage,
        ]);

        if ($driver === 'typesense') {
            return $this->searchViaTypesense(
                query: $query,
                userId: (string) $user->id,
                perPage: $perPage,
                page: $page,
                types: $requestedTypes,
                isBrowseQuery: $isBrowseQuery,
            );
        }

        Log::warning('Search query is not using Typesense driver. Falling back to local search.', [
            'driver' => $driver,
        ]);

        return $this->searchViaScoutFallback(
            query: $query,
            userId: (string) $user->id,
            perPage: $perPage,
            page: $page,
            types: $requestedTypes,
            isBrowseQuery: $isBrowseQuery,
        );
    }

    /**
     * @param  list<SearchEntityType>  $types
     * @return array{
     *   data: list<array{
     *     entity: Model,
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
    protected function searchViaTypesense(
        string $query,
        string $userId,
        int $perPage,
        int $page,
        array $types,
        bool $isBrowseQuery,
    ): array {
        $normalizedQuery = $isBrowseQuery ? '*' : $query;
        $entityTypes = $this->entityTypeFilters($types);
        $collectionName = SearchableEntities::collectionName();

        $filters = ["user_id:={$userId}"];
        if ($entityTypes !== []) {
            $filters[] = 'entity_type:['.implode(',', $entityTypes).']';
        }

        $options = [
            'q' => $normalizedQuery,
            'query_by' => SearchableEntities::typesenseQueryBy(),
            'filter_by' => implode(' && ', $filters),
            'sort_by' => $isBrowseQuery
                ? 'updated_at:desc,entity_id:asc'
                : '_text_match:desc,updated_at:desc,entity_id:asc',
            'page' => $page,
            'per_page' => $perPage,
        ];

        if (! $isBrowseQuery) {
            $options['highlight_fields'] = SearchableEntities::typesenseHighlightFields();
        }

        Log::debug('Executing Typesense search.', [
            'collection' => $collectionName,
            'options' => $options,
        ]);

        try {
            /** @var array{hits?: array<int, array<string, mixed>>, found?: int} $raw */
            $raw = $this->typesenseClient()
                ->getCollections()
                ->{$collectionName}
                ->getDocuments()
                ->search($options);
        } catch (ObjectNotFound $exception) {
            Log::warning('Typesense collection not found during search.', [
                'collection' => $collectionName,
                'reason' => $exception->getMessage(),
                'available_collections' => $this->availableTypesenseCollections(),
            ]);

            return $this->emptyResult($page, $perPage);
        } catch (\Throwable $exception) {
            Log::warning('Typesense search failed.', [
                'collection' => $collectionName,
                'reason' => $exception->getMessage(),
            ]);

            return $this->emptyResult($page, $perPage);
        }

        $rawHits = array_values(array_filter(
            $raw['hits'] ?? [],
            static fn (mixed $hit): bool => is_array($hit)
        ));

        $documents = array_values(array_filter(array_map(
            static fn (array $hit): ?array => isset($hit['document']) && is_array($hit['document']) ? $hit['document'] : null,
            $rawHits
        )));

        $modelsByDocumentKey = $this->hydrateDocumentsByKey($documents, $userId);

        $resultItems = [];
        foreach ($rawHits as $hit) {
            $document = isset($hit['document']) && is_array($hit['document']) ? $hit['document'] : null;
            if (! is_array($document)) {
                continue;
            }

            $documentKey = $this->documentKey($document);
            if ($documentKey === null) {
                continue;
            }

            $model = $modelsByDocumentKey[$documentKey] ?? null;
            if (! $model instanceof Model) {
                continue;
            }

            $resultItems[] = [
                'entity' => $model,
                'matches' => $isBrowseQuery ? [] : $this->matchesFromTypesenseHit($hit, $document, $query),
            ];
        }

        $total = (int) ($raw['found'] ?? 0);

        Log::debug('Typesense search response mapped.', [
            'found' => $total,
            'raw_hits_count' => count($rawHits),
            'documents_count' => count($documents),
            'hydrated_models_count' => count($resultItems),
        ]);

        return [
            'data' => $resultItems,
            'paginatorInfo' => $this->paginatorInfo($page, $perPage, $total),
        ];
    }

    /**
     * @param  list<SearchEntityType>  $types
     * @return array{
     *   data: list<array{
     *     entity: Model,
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
    protected function searchViaScoutFallback(
        string $query,
        string $userId,
        int $perPage,
        int $page,
        array $types,
        bool $isBrowseQuery,
    ): array {
        $modelClasses = $this->modelClassesForTypes($types);
        $results = collect();

        Log::debug('Executing fallback search path.', [
            'model_classes' => $modelClasses,
            'needle' => mb_strtolower($query),
        ]);

        foreach ($modelClasses as $modelClass) {
            /** @var EloquentCollection<int, Model> $scoped */
            $scoped = SearchableEntities::userScopedQuery($modelClass, $userId)->get();

            foreach ($scoped as $model) {
                $matches = $isBrowseQuery
                    ? []
                    : $this->fallbackMatchesFromDocument($model->toSearchableArray(), $query);

                if (! $isBrowseQuery && $matches === []) {
                    continue;
                }

                $results->push([
                    'entity' => $model,
                    'matches' => $matches,
                ]);
            }
        }

        /** @var Collection<int, array{entity: Model, matches: array<int, array<string, int|string>>}> $sorted */
        $sorted = $results
            ->sort(fn (array $left, array $right): int => $this->compareResultItems($left, $right))
            ->values();

        $total = $sorted->count();
        $offset = ($page - 1) * $perPage;
        $paged = $sorted->slice($offset, $perPage)->values()->all();

        return [
            'data' => $paged,
            'paginatorInfo' => $this->paginatorInfo($page, $perPage, $total),
        ];
    }

    /**
     * @param  array<string, mixed>  $document
     * @return list<array{field:string,text:string,matchedText:string,start:int,end:int,snippet:string}>
     */
    protected function fallbackMatchesFromDocument(array $document, string $query): array
    {
        $matches = [];
        $needle = mb_strtolower($query);
        $seen = [];

        foreach (SearchableEntities::explainableTextFields() as $field) {
            $text = $this->stringValue($document[$field] ?? null);
            if ($text === '') {
                continue;
            }

            $start = mb_stripos($text, $needle);
            if ($start === false) {
                continue;
            }

            $matchLength = max(mb_strlen($query), 1);
            $startIndex = (int) $start;
            $endIndex = $startIndex + $matchLength;
            $matchedText = mb_substr($text, $startIndex, $matchLength);
            $presentedField = $this->presentedMatchField($field);
            $dedupeKey = strtolower("{$presentedField}:{$startIndex}:{$endIndex}:{$matchedText}");

            if (isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;

            $matches[] = [
                'field' => $presentedField,
                'text' => $text,
                'matchedText' => $matchedText,
                'start' => $startIndex,
                'end' => $endIndex,
                'snippet' => $this->buildSnippet($text, $startIndex, $matchLength),
            ];
        }

        return $matches;
    }

    /**
     * @param  array<string, mixed>  $hit
     * @param  array<string, mixed>  $document
     * @return list<array{field:string,text:string,matchedText:string,start:int,end:int,snippet:string}>
     */
    protected function matchesFromTypesenseHit(array $hit, array $document, string $query): array
    {
        $matches = [];
        $seen = [];

        foreach ($this->normalizedHighlights($hit) as $highlight) {
            $sourceField = $highlight['field'];

            if (! $this->isExplainableMatchField($sourceField)) {
                continue;
            }

            $text = $this->stringValue($document[$sourceField] ?? null);

            if ($sourceField === '' || $text === '') {
                continue;
            }

            $presentedField = $this->presentedMatchField($sourceField);
            ['matchedText' => $matchedText, 'start' => $start, 'end' => $end] = $this->resolveBestMatch(
                text: $text,
                highlight: $highlight,
                query: $query,
            );

            if ($matchedText === '' || $start < 0 || $end < 0) {
                continue;
            }

            $dedupeKey = strtolower("{$presentedField}:{$start}:{$end}:{$matchedText}");
            if (isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;

            $matches[] = [
                'field' => $presentedField,
                'text' => $text,
                'matchedText' => $matchedText,
                'start' => $start,
                'end' => $end,
                'snippet' => $this->resolveSnippet($highlight['snippet'], $text, $start, $end),
            ];
        }

        if ($matches !== []) {
            return $matches;
        }

        return $this->fallbackMatchesFromDocument($document, $query);
    }

    protected function isExplainableMatchField(string $field): bool
    {
        return in_array($field, SearchableEntities::explainableTextFields(), true);
    }

    protected function presentedMatchField(string $field): string
    {
        return str_replace('__', '.', $field);
    }

    /**
     * @param  array<string, mixed>  $hit
     * @return list<array{field:string,snippet:string,matched_tokens:list<string>}>
     */
    protected function normalizedHighlights(array $hit): array
    {
        $highlights = [];

        if (isset($hit['highlights']) && is_array($hit['highlights'])) {
            foreach ($hit['highlights'] as $highlight) {
                if (! is_array($highlight)) {
                    continue;
                }

                $field = (string) ($highlight['field'] ?? '');
                if ($field === '') {
                    continue;
                }

                $highlights[] = [
                    'field' => $field,
                    'snippet' => (string) ($highlight['snippet'] ?? ''),
                    'matched_tokens' => $this->normalizeMatchedTokens($highlight['matched_tokens'] ?? []),
                ];
            }
        }

        if (isset($hit['highlight']) && is_array($hit['highlight'])) {
            foreach ($hit['highlight'] as $field => $highlight) {
                if (! is_string($field) || $field === '') {
                    continue;
                }

                if (is_array($highlight)) {
                    $highlights[] = [
                        'field' => $field,
                        'snippet' => (string) ($highlight['snippet'] ?? ''),
                        'matched_tokens' => $this->normalizeMatchedTokens($highlight['matched_tokens'] ?? []),
                    ];

                    continue;
                }

                if (is_string($highlight)) {
                    $highlights[] = [
                        'field' => $field,
                        'snippet' => $highlight,
                        'matched_tokens' => [],
                    ];
                }
            }
        }

        return array_values(array_reduce($highlights, function (array $carry, array $highlight): array {
            $key = $highlight['field'].'|'.$highlight['snippet'].'|'.implode('|', $highlight['matched_tokens']);
            $carry[$key] = $highlight;

            return $carry;
        }, []));
    }

    /**
     * @return list<string>
     */
    protected function normalizeMatchedTokens(mixed $tokens): array
    {
        if (! is_array($tokens)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $token): string => trim((string) $token),
            $tokens
        )));
    }

    /**
     * @param  array{field:string,snippet:string,matched_tokens:list<string>}  $highlight
     */
    protected function resolveHighlightedText(array $highlight): string
    {
        $matchedToken = $highlight['matched_tokens'][0] ?? '';
        if ($matchedToken !== '') {
            return $matchedToken;
        }

        if (preg_match('/<mark>(.*?)<\/mark>/i', $highlight['snippet'], $match) === 1) {
            return trim(strip_tags($match[1]));
        }

        return '';
    }

    /**
     * @param  array{field:string,snippet:string,matched_tokens:list<string>}  $highlight
     * @return array{matchedText:string,start:int,end:int}
     */
    protected function resolveBestMatch(string $text, array $highlight, string $query): array
    {
        foreach ($this->queryTokens($query) as $queryToken) {
            [$start, $end] = $this->matchBounds($text, $queryToken);
            if ($start >= 0 && $end >= 0) {
                return [
                    'matchedText' => mb_substr($text, $start, max($end - $start, 1)),
                    'start' => $start,
                    'end' => $end,
                ];
            }
        }

        foreach ($highlight['matched_tokens'] as $matchedToken) {
            [$start, $end] = $this->matchBounds($text, $matchedToken);
            if ($start >= 0 && $end >= 0) {
                return [
                    'matchedText' => mb_substr($text, $start, max($end - $start, 1)),
                    'start' => $start,
                    'end' => $end,
                ];
            }
        }

        $highlightedText = $this->resolveHighlightedText($highlight);
        [$start, $end] = $this->matchBounds($text, $highlightedText);

        if ($start >= 0 && $end >= 0 && $highlightedText !== '') {
            return [
                'matchedText' => mb_substr($text, $start, max($end - $start, 1)),
                'start' => $start,
                'end' => $end,
            ];
        }

        return [
            'matchedText' => '',
            'start' => -1,
            'end' => -1,
        ];
    }

    /**
     * @return list<string>
     */
    protected function queryTokens(string $query): array
    {
        $parts = preg_split('/\s+/u', trim($query)) ?: [];

        return array_values(array_filter(array_map(
            static fn (string $part): string => trim($part),
            $parts
        )));
    }

    /**
     * @return array{0:int,1:int}
     */
    protected function matchBounds(string $text, string $matchedText): array
    {
        if ($matchedText === '') {
            return [-1, -1];
        }

        $start = mb_stripos($text, $matchedText);
        if ($start === false) {
            return [-1, -1];
        }

        $startIndex = (int) $start;
        $endIndex = $startIndex + mb_strlen($matchedText);

        return [$startIndex, $endIndex];
    }

    protected function resolveSnippet(string $highlightSnippet, string $text, int $start, int $end): string
    {
        $snippet = trim(strip_tags($highlightSnippet));
        if ($snippet !== '') {
            return $snippet;
        }

        if ($start < 0 || $end < 0) {
            return $text;
        }

        return $this->buildSnippet($text, $start, max($end - $start, 1));
    }

    protected function buildSnippet(string $text, int $start, int $length): string
    {
        $radius = 40;
        $textLength = mb_strlen($text);

        $snippetStart = max(0, $start - $radius);
        $snippetLength = min($textLength - $snippetStart, $length + ($radius * 2));
        $snippet = mb_substr($text, $snippetStart, $snippetLength);

        if ($snippetStart > 0) {
            $snippet = '...'.$snippet;
        }

        if (($snippetStart + $snippetLength) < $textLength) {
            $snippet .= '...';
        }

        return $snippet;
    }

    protected function compareResultItems(array $left, array $right): int
    {
        $leftModel = $left['entity'];
        $rightModel = $right['entity'];

        if (! $leftModel instanceof Model || ! $rightModel instanceof Model) {
            return 0;
        }

        $leftUpdatedAt = (int) ($leftModel->updated_at?->getTimestamp() ?? 0);
        $rightUpdatedAt = (int) ($rightModel->updated_at?->getTimestamp() ?? 0);

        if ($leftUpdatedAt !== $rightUpdatedAt) {
            return $rightUpdatedAt <=> $leftUpdatedAt;
        }

        return strcmp((string) $leftModel->getKey(), (string) $rightModel->getKey());
    }

    protected function stringValue(mixed $value): string
    {
        if (is_string($value)) {
            return trim($value);
        }

        if (is_int($value) || is_float($value) || is_bool($value)) {
            return trim((string) $value);
        }

        return '';
    }

    /**
     * @param  list<array<string, mixed>>  $documents
     * @return array<string, Model>
     */
    protected function hydrateDocumentsByKey(array $documents, string $userId): array
    {
        $typeMap = SearchableEntities::modelByEntityType();
        $idsByType = [];

        foreach ($documents as $document) {
            $documentKey = $this->documentKey($document);
            if ($documentKey === null) {
                continue;
            }

            [$entityType, $entityId] = explode(':', $documentKey, 2);
            if (! isset($typeMap[$entityType])) {
                continue;
            }

            $idsByType[$entityType][] = $entityId;
        }

        $modelsByKey = [];
        $loadedByType = [];

        foreach ($idsByType as $entityType => $ids) {
            $modelClass = $typeMap[$entityType];

            $models = SearchableEntities::userScopedQuery($modelClass, $userId)
                ->whereKey(array_values(array_unique($ids)))
                ->get();

            $loadedByType[$entityType] = $models->count();

            foreach ($models as $model) {
                $modelsByKey[$entityType.':'.(string) $model->getKey()] = $model;
            }
        }

        Log::debug('Hydrated Typesense documents to models.', [
            'ids_by_type' => array_map(static fn (array $ids): int => count($ids), $idsByType),
            'loaded_by_type' => $loadedByType,
            'ordered_count' => count($modelsByKey),
        ]);

        return $modelsByKey;
    }

    /**
     * @param  array<string, mixed>  $document
     */
    protected function documentKey(array $document): ?string
    {
        $entityType = strtolower((string) ($document['entity_type'] ?? ''));
        $entityId = (string) ($document['entity_id'] ?? '');

        if ($entityType === '' || $entityId === '') {
            return null;
        }

        return "{$entityType}:{$entityId}";
    }

    /**
     * @return list<string>
     */
    protected function availableTypesenseCollections(): array
    {
        try {
            /** @var array<int, array{name?: string}> $collections */
            $collections = $this->typesenseClient()->getCollections()->retrieve();

            return array_values(array_filter(array_map(
                static fn (array $collection): ?string => $collection['name'] ?? null,
                $collections
            )));
        } catch (\Throwable $exception) {
            Log::warning('Unable to list Typesense collections after search failure.', [
                'reason' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @return list<SearchEntityType>
     */
    protected function resolveTypes(mixed $argsTypes): array
    {
        if (! is_array($argsTypes)) {
            return [];
        }

        $types = [];

        foreach ($argsTypes as $value) {
            $type = SearchEntityType::tryFrom((string) $value);
            if (! $type instanceof SearchEntityType) {
                continue;
            }

            $types[$type->value] = $type;
        }

        return array_values($types);
    }

    /**
     * @param  list<SearchEntityType>  $types
     * @return list<string>
     */
    protected function entityTypeFilters(array $types): array
    {
        if ($types === []) {
            return [];
        }

        return array_values(array_unique(array_map(
            static fn (SearchEntityType $type): string => strtolower($type->value),
            $types
        )));
    }

    /**
     * @param  list<SearchEntityType>  $types
     * @return list<class-string<Model>>
     */
    protected function modelClassesForTypes(array $types): array
    {
        if ($types === []) {
            return SearchableEntities::allModelClasses();
        }

        $models = [];
        foreach ($types as $type) {
            $models = array_merge($models, SearchableEntities::modelsForType($type));
        }

        return array_values(array_unique($models));
    }

    /**
     * @return array{currentPage:int,perPage:int,total:int,lastPage:int,hasMorePages:bool}
     */
    protected function paginatorInfo(int $page, int $perPage, int $total): array
    {
        $lastPage = max(1, (int) ceil($total / $perPage));

        return [
            'currentPage' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'lastPage' => $lastPage,
            'hasMorePages' => $page < $lastPage,
        ];
    }

    /**
     * @return array{
     *   data: list<array{
     *     entity: Model,
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
    protected function emptyResult(int $page, int $perPage): array
    {
        return [
            'data' => [],
            'paginatorInfo' => $this->paginatorInfo($page, $perPage, 0),
        ];
    }

    protected function typesenseClient(): TypesenseClient
    {
        /** @var array<string, mixed> $settings */
        $settings = config('scout.typesense.client-settings', []);

        $apiKey = (string) ($settings['api_key'] ?? '');
        $maskedApiKey = $apiKey === ''
            ? ''
            : str_repeat('*', max(strlen($apiKey) - 4, 0)).substr($apiKey, -4);

        Log::debug('Resolved Typesense client settings.', [
            'api_key' => $maskedApiKey,
            'nodes' => $settings['nodes'] ?? [],
            'nearest_node' => $settings['nearest_node'] ?? null,
            'connection_timeout_seconds' => $settings['connection_timeout_seconds'] ?? null,
            'healthcheck_interval_seconds' => $settings['healthcheck_interval_seconds'] ?? null,
            'num_retries' => $settings['num_retries'] ?? null,
            'retry_interval_seconds' => $settings['retry_interval_seconds'] ?? null,
        ]);

        return new TypesenseClient($settings);
    }
}
