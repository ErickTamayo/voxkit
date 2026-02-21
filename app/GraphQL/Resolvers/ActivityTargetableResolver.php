<?php

declare(strict_types=1);

namespace App\GraphQL\Resolvers;

use App\Models\Activity;
use AvocetShores\LaravelRewind\Exceptions\LaravelRewindException;
use AvocetShores\LaravelRewind\Facades\Rewind;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class ActivityTargetableResolver
{
    public function resolve($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): ?Model
    {
        if (! $root instanceof Activity) {
            return null;
        }

        $targetable = $root->targetable;
        if (! $targetable) {
            return null;
        }

        $version = $root->targetable_version;
        if (! $version) {
            return $targetable;
        }

        try {
            $attributes = Rewind::getVersionAttributes($targetable, (int) $version);
        } catch (LaravelRewindException $exception) {
            Log::warning('Activity targetable version lookup failed; falling back to live model.', [
                'activity_id' => $root->getKey(),
                'targetable_type' => $root->targetable_type,
                'targetable_id' => $root->targetable_id,
                'targetable_version' => $version,
                'reason' => $exception->getMessage(),
            ]);

            return $targetable;
        }

        $versioned = $targetable->replicate();
        $versioned->forceFill($attributes);
        $versioned->setAttribute($targetable->getKeyName(), $targetable->getKey());
        $versioned->exists = true;

        return $versioned;
    }
}
