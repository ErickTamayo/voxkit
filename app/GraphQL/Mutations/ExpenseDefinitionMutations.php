<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\ExpenseDefinition;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final class ExpenseDefinitionMutations
{
    /**
     * Create a new expense definition with MoneyInput handling.
     */
    public function create(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): ExpenseDefinition
    {
        $data = $args['input'];
        $data['user_id'] = $context->user()->id;

        return ExpenseDefinition::create($data);
    }

    /**
     * Update an expense definition with MoneyInput handling.
     */
    public function update(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): ?ExpenseDefinition
    {
        $definition = ExpenseDefinition::where('id', $args['id'])
            ->where('user_id', $context->user()->id)
            ->first();

        if (! $definition) {
            return null;
        }

        $data = $args['input'];

        $definition->update($data);

        return $definition->fresh();
    }
}
