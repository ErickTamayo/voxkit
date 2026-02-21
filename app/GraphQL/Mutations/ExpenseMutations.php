<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\Expense;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final class ExpenseMutations
{
    /**
     * Create a new expense with MoneyInput handling.
     */
    public function create(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Expense
    {
        $data = $args['input'];
        $data['user_id'] = $context->user()->id;

        return Expense::create($data);
    }

    /**
     * Update an expense with MoneyInput handling.
     */
    public function update(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): ?Expense
    {
        $expense = Expense::where('id', $args['id'])
            ->where('user_id', $context->user()->id)
            ->first();

        if (! $expense) {
            return null;
        }

        $data = $args['input'];

        $expense->update($data);

        return $expense->fresh();
    }
}
