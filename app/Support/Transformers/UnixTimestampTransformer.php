<?php

declare(strict_types=1);

namespace App\Support\Transformers;

use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\Transformation\TransformationContext;
use Spatie\LaravelData\Transformers\Transformer;

class UnixTimestampTransformer implements Transformer
{
    public function transform(DataProperty $property, mixed $value, TransformationContext $context): int
    {
        // Return milliseconds (timestamp * 1000) for consistency with JavaScript Date.now()
        return $value->getTimestamp() * 1000;
    }
}
