<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExchangeRateService
{
    protected string $appId;

    protected string $baseUrl = 'https://openexchangerates.org/api';

    protected string $storagePath = 'exchange-rates';

    const DEFAULT_SYNC_DAYS = 10;

    const FULL_SYNC_DAYS = 365;

    public function __construct()
    {
        $this->appId = config('services.open_exchange_rates.app_id');
    }

    /**
     * @param  array<int, Carbon|string>  $forceRefreshDates
     */
    public function syncRates(bool $fullSync = false, array $forceRefreshDates = []): void
    {
        $startDate = $this->determineStartDate($fullSync);
        $today = Carbon::today();
        $forceRefreshDateStrings = $this->normalizeForceRefreshDates($forceRefreshDates);

        $currentDate = $startDate->copy();

        while ($currentDate->lte($today)) {
            $dateString = $currentDate->format('Y-m-d');
            $this->processDate(
                $currentDate,
                $dateString === $today->format('Y-m-d'),
                in_array($dateString, $forceRefreshDateStrings, true)
            );
            $currentDate->addDay();
        }
    }

    protected function determineStartDate(bool $fullSync = false): Carbon
    {
        if ($fullSync) {
            return Carbon::today()->subDays(self::FULL_SYNC_DAYS);
        }

        // Ensure storage directory exists
        if (! Storage::exists($this->storagePath)) {
            Storage::makeDirectory($this->storagePath);
        }

        $files = Storage::files($this->storagePath);

        if (empty($files)) {
            return Carbon::today()->subDays(self::DEFAULT_SYNC_DAYS);
        }

        // Extract dates from filenames (YYYY-MM-DD.json)
        $dates = [];
        foreach ($files as $file) {
            if (preg_match('/(\d{4}-\d{2}-\d{2})\.json$/', $file, $matches)) {
                $dates[] = $matches[1];
            }
        }

        if (empty($dates)) {
            return Carbon::today()->subDays(self::DEFAULT_SYNC_DAYS);
        }

        sort($dates);
        $lastDate = end($dates);

        return Carbon::parse($lastDate)->addDay();
    }

    protected function processDate(Carbon $date, bool $isToday, bool $forceRefresh = false): void
    {
        $dateString = $date->format('Y-m-d');
        $filename = "{$this->storagePath}/{$dateString}.json";

        // Check if we already have this date cached (skip if it's today as we might want to refresh,
        // but for historical data we skip if exists)
        if (! $isToday && ! $forceRefresh && Storage::exists($filename)) {
            $data = json_decode(Storage::get($filename), true);
            $this->storeRates($data, $dateString);

            return;
        }

        // Fetch from API
        $endpoint = $isToday ? 'latest.json' : "historical/{$dateString}.json";
        $response = Http::get("{$this->baseUrl}/{$endpoint}", [
            'app_id' => $this->appId,
        ]);

        if ($response->successful()) {
            $data = $response->json();

            // Validate data
            if (isset($data['rates']) && is_array($data['rates'])) {
                // Save to storage
                Storage::put($filename, json_encode($data, JSON_PRETTY_PRINT));

                // Store in DB
                $this->storeRates($data, $dateString);

                Log::info("Synced exchange rates for {$dateString}");
            } else {
                Log::error("Invalid exchange rate data received for {$dateString}");
            }
        } else {
            Log::error("Failed to fetch exchange rates for {$dateString}: ".$response->body());
        }
    }

    protected function storeRates(array $data, string $dateString): void
    {
        $baseCurrency = $data['base'] ?? 'USD';
        $rates = $data['rates'] ?? [];

        foreach ($rates as $currency => $rate) {
            ExchangeRate::updateOrCreate(
                [
                    'currency_code' => $currency,
                    'effective_date' => $dateString.' 00:00:00',
                ],
                [
                    'rate' => $rate,
                    'base_currency' => $baseCurrency,
                ]
            );
        }
    }

    /**
     * Convert an amount from one currency to another.
     *
     * @param  int|float  $amount  Amount in cents (or float)
     * @param  string  $fromCurrency  Source currency code
     * @param  string  $toCurrency  Target currency code
     * @param  Carbon|null  $date  Date for conversion rate (defaults to today)
     * @return int Converted amount in cents
     */
    public function convert(int|float $amount, string $fromCurrency, string $toCurrency, ?Carbon $date = null): int
    {
        if ($fromCurrency === $toCurrency) {
            return (int) round($amount);
        }

        $date = $date ?? Carbon::today();

        // USD is the base currency for all rates
        if ($fromCurrency === 'USD') {
            $fromRate = 1.0;
        } else {
            $fromRate = $this->getRate($fromCurrency, $date);
        }

        if ($toCurrency === 'USD') {
            $toRate = 1.0;
        } else {
            $toRate = $this->getRate($toCurrency, $date);
        }

        if ($fromRate <= 0) {
            return (int) round($amount);
        }

        // Convert to USD first: Amount / FromRate
        // Then to Target: USD_Amount * ToRate
        $converted = ($amount / $fromRate) * $toRate;

        return (int) round($converted);
    }

    protected function getRate(string $currency, Carbon $date): float
    {
        $cacheKey = "exchange_rate:{$currency}:{$date->format('Y-m-d')}";

        return Cache::remember($cacheKey, 3600, function () use ($currency, $date) {
            $rate = ExchangeRate::where('currency_code', $currency)
                ->where('effective_date', '<=', $date->format('Y-m-d 23:59:59'))
                ->orderBy('effective_date', 'desc')
                ->value('rate');

            return $rate ? (float) $rate : 1.0;
        });
    }

    /**
     * Get the rate for a specific currency, also indicating if it was an exact match.
     *
     * @return array{rate: float, is_exact: bool}
     */
    protected function getRateWithAccuracy(string $currency, Carbon $date): array
    {
        $cacheKey = "exchange_rate_accuracy:{$currency}:{$date->format('Y-m-d')}";

        return Cache::remember($cacheKey, 3600, function () use ($currency, $date) {
            $record = ExchangeRate::where('currency_code', $currency)
                ->where('effective_date', '<=', $date->format('Y-m-d 23:59:59'))
                ->orderBy('effective_date', 'desc')
                ->first(['rate', 'effective_date']);

            if (! $record) {
                return ['rate' => 1.0, 'is_exact' => false];
            }

            $isExact = Carbon::parse($record->effective_date)->isSameDay($date);

            return ['rate' => (float) $record->rate, 'is_exact' => $isExact];
        });
    }

    /**
     * @param  array<int, Carbon|string>  $forceRefreshDates
     * @return array<int, string>
     */
    protected function normalizeForceRefreshDates(array $forceRefreshDates): array
    {
        $dates = [];

        foreach ($forceRefreshDates as $date) {
            if ($date instanceof Carbon) {
                $dates[] = $date->format('Y-m-d');

                continue;
            }

            if (is_string($date) && $date !== '') {
                $dates[] = Carbon::parse($date)->format('Y-m-d');
            }
        }

        return array_values(array_unique($dates));
    }

    /**
     * Convert an amount and return metadata about the conversion accuracy.
     *
     * @return array{amount: int, precision: string}
     */
    public function convertWithMetadata(int|float $amount, string $fromCurrency, string $toCurrency, ?Carbon $date = null): array
    {
        if ($fromCurrency === $toCurrency) {
            return [
                'amount' => (int) round($amount),
                'precision' => 'EXACT',
            ];
        }

        $date = $date ?? Carbon::today();
        $isAccurate = true;

        if ($fromCurrency === 'USD') {
            $fromRate = 1.0;
        } else {
            $result = $this->getRateWithAccuracy($fromCurrency, $date);
            $fromRate = $result['rate'];
            if (! $result['is_exact']) {
                $isAccurate = false;
            }
        }

        if ($toCurrency === 'USD') {
            $toRate = 1.0;
        } else {
            $result = $this->getRateWithAccuracy($toCurrency, $date);
            $toRate = $result['rate'];
            if (! $result['is_exact']) {
                $isAccurate = false;
            }
        }

        if ($fromRate <= 0) {
            return [
                'amount' => (int) round($amount),
                'precision' => 'ESTIMATED',
            ];
        }

        $converted = ($amount / $fromRate) * $toRate;

        // Today's rates are never finalized, so always mark as ESTIMATED
        $isToday = $date->isToday();
        $precision = ($isAccurate && ! $isToday) ? 'EXACT' : 'ESTIMATED';

        return [
            'amount' => (int) round($converted),
            'precision' => $precision,
        ];
    }
}
