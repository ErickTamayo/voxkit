<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\Agent;
use App\Services\AgentMutationService;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final class AgentMutations
{
    public function __construct(
        private readonly AgentMutationService $agentMutationService
    ) {}

    /**
     * Create a new agent and its primary contact.
     */
    public function create(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Agent
    {
        return $this->agentMutationService->create($args['input'], $context->user()->id);
    }

    /**
     * Update an existing agent and/or its primary contact.
     */
    public function update(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): ?Agent
    {
        return $this->agentMutationService->update($args['id'], $args['input']);
    }
}
