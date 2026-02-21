<?php

declare(strict_types=1);

namespace App\GraphQL\Resolvers;

use App\Models\Concerns\HasMonetaryFields;
use App\Services\ExchangeRateService;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class MonetaryAmountResolver
{
    public function __construct(protected ExchangeRateService $service) {}

    /**
     * Resolve a MonetaryAmount field.
     *
     * The field name should match a monetary field on the model
     * (e.g., "amount" resolves to amount_cents + amount_currency).
     */
    public function resolve($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): ?array
    {
        $fieldName = $resolveInfo->fieldName;

        // Check if model uses HasMonetaryFields trait
        if (method_exists($root, 'getMonetaryAmount')) {
            return $root->getMonetaryAmount($fieldName, $this->service);
        }

        // Fallback: manual resolution for models without the trait
        $centsColumn = "{$fieldName}_cents";
        $currencyColumn = method_exists($root, 'getCurrencyColumn')
          ? $root->getCurrencyColumn($fieldName)
          : "{$fieldName}_currency";

        $cents = $root->{$centsColumn} ?? null;
        if ($cents === null) {
            return null;
        }

        $currency = $root->{$currencyColumn} ?? 'USD';

        $originalMoney = [
            'currency' => $currency,
            'amount_cents' => $cents,
            'precision' => 'EXACT',
        ];

        $user = $context->user();
        $targetCurrency = $user?->settings?->currency ?? 'USD';

        $result = $this->service->convertWithMetadata($cents, $currency, $targetCurrency);

        $convertedMoney = [
            'currency' => $targetCurrency,
            'amount_cents' => $result['amount'],
            'precision' => $result['precision'],
        ];

        return [
            'original' => $originalMoney,
            'converted' => $convertedMoney,
        ];
    }
}
