<?php

namespace App\GraphQL\Resolvers;

use App\Services\ExchangeRateService;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class CurrencyResolver
{
    public function __construct(protected ExchangeRateService $service) {}

    public function resolve($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): ?array
    {
        $fieldName = $resolveInfo->fieldName;
        // Infer source field: total_in_base_currency -> total
        $sourceField = str_replace('_in_base_currency', '', $fieldName);

        // Access property on the root model (e.g. Invoice)
        $amount = $root->{$sourceField} ?? null;

        // If amount is null, and schema requires Money!, we might have an issue.
        // However, if the source field (e.g. tax_amount) is nullable, the schema field (tax_amount_in_base_currency)
        // should also be nullable. If it's non-null (total), amount should be there.
        if ($amount === null) {
            return null;
        }

        $sourceCurrency = $root->currency ?? 'USD';

        $user = $context->user();
        if (! $user) {
            // Fallback if no auth user (shouldn't happen with @guard, but safe default)
            return [
                'currency' => $sourceCurrency,
                'amount_cents' => (int) $amount,
                'precision' => 'EXACT',
            ];
        }

        // Ideally we should ensure settings are eager loaded or cached on the user model if widely used.
        $targetCurrency = $user->settings->currency ?? 'USD';

        $result = $this->service->convertWithMetadata((int) $amount, $sourceCurrency, $targetCurrency);

        return [
            'currency' => $targetCurrency,
            'amount_cents' => $result['amount'],
            'precision' => $result['precision'],
        ];
    }
}
