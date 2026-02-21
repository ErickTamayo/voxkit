<?php

declare(strict_types=1);

namespace App\GraphQL\Scalars;

use GraphQL\Error\Error;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;

class Json extends ScalarType
{
    public ?string $description = 'Arbitrary JSON value.';

    /**
     * Serialize to output (DB -> GraphQL response).
     *
     * @param  mixed  $value
     */
    public function serialize($value): mixed
    {
        return $value;
    }

    /**
     * Parse input value (GraphQL input -> PHP).
     *
     * @param  mixed  $value
     */
    public function parseValue($value): mixed
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        throw new Error('JSON value must be an object, array, or valid JSON string.');
    }

    /**
     * Parse literal value from GraphQL query.
     */
    public function parseLiteral($valueNode, ?array $variables = null): mixed
    {
        if (! $valueNode instanceof StringValueNode) {
            throw new Error('JSON literal must be a string.');
        }

        $decoded = json_decode($valueNode->value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Error('Invalid JSON literal.');
        }

        return $decoded;
    }
}
