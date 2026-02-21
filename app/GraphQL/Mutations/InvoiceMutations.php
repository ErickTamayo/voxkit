<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\Invoice;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final class InvoiceMutations
{
    /**
     * Create a new invoice with MoneyInput handling.
     */
    public function create(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Invoice
    {
        $data = $args['input'];
        $data['user_id'] = $context->user()->id;

        return Invoice::create($data);
    }

    /**
     * Update an invoice with MoneyInput handling.
     */
    public function update(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): ?Invoice
    {
        $invoice = Invoice::where('id', $args['id'])
            ->where('user_id', $context->user()->id)
            ->first();

        if (! $invoice) {
            return null;
        }

        $data = $args['input'];

        $invoice->update($data);

        return $invoice->fresh();
    }
}
