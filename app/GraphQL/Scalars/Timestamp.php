<?php

declare(strict_types=1);

namespace App\GraphQL\Scalars;

use GraphQL\Error\Error;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Type\Definition\ScalarType;

class Timestamp extends ScalarType
{
    public ?string $description = 'Unix timestamp (milliseconds since Unix epoch) representing a UTC datetime. Always stored and transmitted as UTC.';

    /**
     * Serialize to output (DB -> GraphQL response).
     * Convert Carbon datetime to Unix timestamp.
     */
    public function serialize($value): int
    {
        if ($value instanceof \Carbon\Carbon) {
            return $value->timestamp * 1000;
        }

        if ($value instanceof \DateTime) {
            return $value->getTimestamp() * 1000;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        throw new Error("Cannot serialize value as Timestamp: {$value}");
    }

    /**
     * Parse input value (GraphQL input -> PHP).
     * Convert Unix timestamp to Carbon instance for Eloquent.
     */
    public function parseValue($value): \Carbon\Carbon
    {
        if (! is_numeric($value)) {
            throw new Error("Timestamp must be a Unix timestamp integer, got: {$value}");
        }

        return \Carbon\Carbon::createFromTimestamp((int) $value / 1000, 'UTC');
    }

    /**
     * Parse literal value from GraphQL query.
     */
    public function parseLiteral($valueNode, ?array $variables = null): \Carbon\Carbon
    {
        if (! $valueNode instanceof IntValueNode) {
            throw new Error('Timestamp must be an integer');
        }

        return \Carbon\Carbon::createFromTimestamp((int) $valueNode->value / 1000, 'UTC');
    }
}
