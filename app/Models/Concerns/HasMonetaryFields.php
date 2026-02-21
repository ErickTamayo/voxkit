<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * Trait for models with monetary fields.
 *
 * Models using this trait should define:
 * - monetaryFields(): array - list of field base names (e.g., ['amount', 'contracted_rate'])
 *
 * For each field, the model should have:
 * - {field}_cents (bigInteger)
 * - {field}_currency (string, 3)
 *
 * Exception: Invoice uses shared 'currency' column for all amount fields.
 */
trait HasMonetaryFields
{
    /**
     * Get the list of monetary field names.
     * Override in model if needed.
     */
    public function monetaryFields(): array
    {
        return [];
    }

    /**
     * Get the currency column name for a given field.
     * Override in models like Invoice that share a currency column.
     */
    public function getCurrencyColumn(string $field): string
    {
        return "{$field}_currency";
    }

    /**
     * Get the monetary value structure for a field.
     */
    public function getMonetaryValue(string $field): array
    {
        $currencyColumn = $this->getCurrencyColumn($field);

        return [
            'cents' => $this->{"{$field}_cents"},
            'currency' => $this->{$currencyColumn},
        ];
    }

    /**
     * Get full MonetaryAmount structure with original and converted values.
     */
    public function getMonetaryAmount(string $field, ?ExchangeRateService $exchangeService = null): ?array
    {
        $exchangeService = $exchangeService ?? app(ExchangeRateService::class);

        $original = $this->getMonetaryValue($field);
        if ($original['cents'] === null) {
            return null;
        }
        if ($original['currency'] === null) {
            throw new InvalidArgumentException("Missing currency for monetary field {$field}.");
        }

        $originalMoney = [
            'currency' => $original['currency'],
            'amount_cents' => $original['cents'],
            'precision' => 'EXACT',
        ];

        // Get target currency from authenticated user's settings
        $user = Auth::user();
        $targetCurrency = $user?->settings?->currency ?? 'USD';

        // Convert to user's base currency
        $result = $exchangeService->convertWithMetadata(
            $original['cents'],
            $original['currency'],
            $targetCurrency
        );

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
