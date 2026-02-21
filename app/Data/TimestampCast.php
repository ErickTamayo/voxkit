<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

/**
 * Cast Carbon timestamps to Unix milliseconds (integers).
 * Returns timestamps in milliseconds for consistency with JavaScript Date.now().
 */
class TimestampCast implements Cast
{
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): ?int
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp() * 1000; // milliseconds
        }

        return (int) $value;
    }
}
