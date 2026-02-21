<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Search\SearchableEntities;

trait HasSearchDocument
{
    /**
     * Get the index name for the model.
     */
    public function searchableAs(): string
    {
        return SearchableEntities::collectionName();
    }

    /**
     * Convert the model instance to an array for Scout indexing.
     *
     * @return array<string, int|string>
     */
    public function toSearchableArray(): array
    {
        $this->prepareSearchRelations();

        $entityType = $this->searchEntityType();
        $entityTerms = SearchableEntities::entityTerms($entityType);
        $documentFields = $this->flattenSearchDocumentFields($this->searchDocumentFields());
        $indexedFields = array_filter(
            $documentFields,
            static fn (string $value): bool => trim($value) !== ''
        );

        $textParts = [
            $entityTerms,
            ...array_values($indexedFields),
        ];

        return [
            'id' => $this->getScoutKey(),
            'entity_id' => (string) $this->getKey(),
            'user_id' => $this->searchUserIdForIndex(),
            'entity_type' => $entityType,
            'entity_terms' => $entityTerms,
            ...$indexedFields,
            'searchable_text' => trim(implode(' ', array_filter(
                $textParts,
                static fn (?string $value): bool => is_string($value) && trim($value) !== '',
            ))),
            'created_at' => (int) $this->created_at?->getTimestamp(),
            'updated_at' => (int) $this->updated_at?->getTimestamp(),
        ];
    }

    /**
     * @return array<string, string|int|float|bool|array<string, mixed>|null>
     */
    abstract protected function searchDocumentFields(): array;

    /**
     * @param  array<string, string|int|float|bool|array<string, mixed>|null>  $fields
     * @return array<string, string>
     */
    protected function flattenSearchDocumentFields(array $fields, string $prefix = ''): array
    {
        $flattened = [];

        foreach ($fields as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            $key = trim($key);
            if ($key === '') {
                continue;
            }

            $normalizedKey = str_replace('.', '__', $key);
            $path = $prefix === '' ? $normalizedKey : "{$prefix}__{$normalizedKey}";

            if (is_array($value)) {
                $flattened = [
                    ...$flattened,
                    ...$this->flattenSearchDocumentFields($value, $path),
                ];

                continue;
            }

            $stringValue = $this->searchDocumentValueToString($value);
            if ($stringValue === null) {
                continue;
            }

            $flattened[$path] = $stringValue;
        }

        return $flattened;
    }

    protected function searchDocumentValueToString(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value) || is_bool($value)) {
            return (string) $value;
        }

        return null;
    }

    abstract protected function searchEntityType(): string;

    protected function searchScoutKeyPrefix(): string
    {
        return $this->searchEntityType();
    }

    protected function searchUserIdForIndex(): string
    {
        return (string) $this->user_id;
    }

    protected function prepareSearchRelations(): void
    {
        $relations = $this->searchRelationsForIndex();
        if ($relations === []) {
            return;
        }

        $this->loadMissing($relations);
    }

    /**
     * @return list<string>
     */
    protected function searchRelationsForIndex(): array
    {
        return [];
    }

    public function getScoutKey(): mixed
    {
        return $this->searchScoutKeyPrefix().':'.$this->getKey();
    }
}
