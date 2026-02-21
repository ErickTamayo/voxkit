<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

/**
 * Cast for GraphQL MoneyInput payloads.
 *
 * Usage:
 *  'amount' => Money::class
 *  'tax_amount' => Money::class . ':nullable'
 */
class Money implements CastsAttributes
{
    private bool $nullable = false;

    public function __construct(string ...$arguments)
    {
        $this->nullable = in_array('nullable', $arguments, true);
    }

    public function get($model, string $key, $value, array $attributes): ?array
    {
        $centsKey = "{$key}_cents";
        $currencyKey = $this->currencyColumn($model, $key);

        $cents = $attributes[$centsKey] ?? null;
        $currency = $attributes[$currencyKey] ?? null;

        if ($cents === null && $currency === null) {
            return null;
        }

        if ($cents === null || $currency === null) {
            throw new InvalidArgumentException("Inconsistent monetary data for {$key}.");
        }

        return [
            'amount_cents' => (int) $cents,
            'currency' => $currency,
        ];
    }

    public function set($model, string $key, $value, array $attributes): array
    {
        $centsKey = "{$key}_cents";
        $currencyKey = $this->currencyColumn($model, $key);

        if ($value === null) {
            if (! $this->nullable) {
                throw new InvalidArgumentException("{$key} is required.");
            }

            return [
                $centsKey => null,
                $currencyKey => null,
            ];
        }

        if (! is_array($value)) {
            throw new InvalidArgumentException("{$key} must be a MoneyInput array.");
        }

        if (! array_key_exists('amount_cents', $value) || ! array_key_exists('currency', $value)) {
            throw new InvalidArgumentException("{$key} requires amount_cents and currency.");
        }

        $amount = $value['amount_cents'];
        $currency = $value['currency'];

        if (! is_int($amount)) {
            throw new InvalidArgumentException("{$key}.amount_cents must be an integer.");
        }

        if (! is_string($currency) || $currency === '') {
            throw new InvalidArgumentException("{$key}.currency must be a non-empty string.");
        }

        return [
            $centsKey => $amount,
            $currencyKey => $currency,
        ];
    }

    private function currencyColumn(object $model, string $field): string
    {
        if (method_exists($model, 'getCurrencyColumn')) {
            return $model->getCurrencyColumn($field);
        }

        return "{$field}_currency";
    }
}
