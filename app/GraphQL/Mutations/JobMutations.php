<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\Job;
use App\Services\JobCreationService;
use App\Services\JobUpdateService;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final class JobMutations
{
    public function __construct(
        private readonly JobCreationService $jobCreationService,
        private readonly JobUpdateService $jobUpdateService
    ) {}

    /**
     * Create a new job with MoneyInput handling.
     */
    public function create(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Job
    {
        return $this->jobCreationService->create($args['input'], $context->user()->id);
    }

    /**
     * Update a job with MoneyInput handling.
     */
    public function update(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): ?Job
    {
        return $this->jobUpdateService->update($args['id'], $args['input'], $context->user()->id);
    }

    /**
     * Archive a job for the authenticated user.
     */
    public function archive(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): ?Job
    {
        $job = Job::where('id', $args['id'])
            ->where('user_id', $context->user()->id)
            ->first();

        if (! $job) {
            return null;
        }

        $job->archived_at = now();
        $job->save();

        return $job->fresh();
    }

    /**
     * Unarchive a job for the authenticated user.
     */
    public function unarchive(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): ?Job
    {
        $job = Job::where('id', $args['id'])
            ->where('user_id', $context->user()->id)
            ->first();

        if (! $job) {
            return null;
        }

        $job->archived_at = null;
        $job->save();

        return $job->fresh();
    }
}
