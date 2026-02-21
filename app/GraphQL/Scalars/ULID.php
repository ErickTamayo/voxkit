<?php

declare(strict_types=1);

namespace App\GraphQL\Scalars;

use GraphQL\Error\Error;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

class ULID extends ScalarType
{
    public ?string $description = 'ULID scalar type for Universally Unique Lexicographically Sortable Identifiers';

    /**
     * Serialize the ULID to a string for output.
     */
    public function serialize($value): string
    {
        return (string) $value;
    }

    /**
     * Parse incoming value from variable.
     */
    public function parseValue($value): string
    {
        if (! is_string($value)) {
            throw new Error('ULID must be a string, got: '.gettype($value));
        }

        if (! SymfonyUlid::isValid($value)) {
            throw new Error("Invalid ULID: {$value}");
        }

        return $value;
    }

    /**
     * Parse literal value from AST.
     */
    public function parseLiteral($valueNode, ?array $variables = null): string
    {
        if (! $valueNode instanceof StringValueNode) {
            throw new Error('ULID must be a string');
        }

        if (! SymfonyUlid::isValid($valueNode->value)) {
            throw new Error("Invalid ULID: {$valueNode->value}");
        }

        return $valueNode->value;
    }
}
