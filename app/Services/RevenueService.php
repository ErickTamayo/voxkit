<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Enums\InvoiceStatus;
use App\Models\Agent;
use App\Models\Audition;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Platform;
use App\Models\User;
use App\Support\Period;
use App\Support\Stats;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class RevenueService
{
    public function __construct(
        protected ExchangeRateService $exchangeRateService,
        protected ChartBuilder $chartBuilder
    ) {}

    /**
     * Calculate revenue metrics with strict period boundaries.
     *
     * MTD = exactly from month start to today
     * QTD = exactly from quarter start to today
     * No time window expansion.
     */
    public function getRevenueMetrics(
        string $period,
        User $user,
        ?string $baseCurrency = null
    ): array {
        $baseCurrency = $baseCurrency ?? $user->base_currency;

        $cacheTag = 'revenue_metrics';
        $cacheKey = "revenue_metrics_{$user->id}_{$period}_{$baseCurrency}";

        return Cache::tags([$cacheTag])->remember($cacheKey, 300, function () use ($period, $user, $baseCurrency) {
            return $this->calculateMetrics($period, $user, $baseCurrency);
        });
    }

    /**
     * Calculate chart data with intelligent time window expansion.
     *
     * Early x-TD periods are automatically expanded:
     * - MTD on day 1-7 of month → 30 days
     * - QTD on day 1-14 of quarter → 90 days
     * - YTD on day 1-30 of year → 365 days
     */
    public function getRevenueChart(
        string $period,
        User $user,
        ?string $baseCurrency = null
    ): array {
        $baseCurrency = $baseCurrency ?? $user->base_currency;

        $cacheTag = 'revenue_chart';
        $cacheKey = "revenue_chart_{$user->id}_{$period}_{$baseCurrency}";

        return Cache::tags([$cacheTag])->remember($cacheKey, 300, function () use ($period, $user, $baseCurrency) {
            return $this->calculateChart($period, $user, $baseCurrency);
        });
    }

    /**
     * Calculate revenue grouped by source.
     *
     * Paid revenue uses strict period boundaries.
     * In-flight revenue is a snapshot (unpaid invoices + unbilled active jobs).
     */
    public function getRevenueBySource(
        string $period,
        User $user,
        ?string $baseCurrency = null,
        int $take = 10
    ): array {
        $baseCurrency = $baseCurrency ?? $user->base_currency;
        $take = $this->normalizeTake($take);

        $cacheTag = 'revenue_by_source';
        $cacheKey = "revenue_by_source_{$user->id}_{$period}_{$baseCurrency}_{$take}";

        return Cache::tags([$cacheTag])->remember($cacheKey, 300, function () use ($period, $user, $baseCurrency, $take) {
            return $this->calculateRevenueBySource($period, $user, $baseCurrency, $take);
        });
    }

    /**
     * Calculate revenue grouped by project category.
     *
     * Paid revenue uses strict period boundaries.
     * In-flight revenue is a snapshot (unpaid invoices + unbilled active jobs).
     */
    public function getRevenueByCategory(
        string $period,
        User $user,
        ?string $baseCurrency = null,
        int $take = 10
    ): array {
        $baseCurrency = $baseCurrency ?? $user->base_currency;
        $take = $this->normalizeTake($take);

        $cacheTag = 'revenue_by_category';
        $cacheKey = "revenue_by_category_{$user->id}_{$period}_{$baseCurrency}_{$take}";

        return Cache::tags([$cacheTag])->remember($cacheKey, 300, function () use ($period, $user, $baseCurrency, $take) {
            return $this->calculateRevenueByCategory($period, $user, $baseCurrency, $take);
        });
    }

    protected function calculateJobValue(Job $job): int
    {
        $rate = $job->contracted_rate_cents;

        return match ($job->rate_type) {
            'flat', 'buyout', 'per_line' => $rate,
            'per_word' => $rate * ($job->word_count ?? 0),
            'hourly' => (int) round($rate * ($job->estimated_hours ?? $job->actual_hours ?? 0)),
            'per_finished_hour' => (int) round($rate * (($job->word_count ?? 0) / 9000)),
            default => $rate,
        };
    }

    /**
     * Calculate metrics with strict period boundaries (no expansion).
     */
    protected function calculateMetrics(string $period, User $user, string $baseCurrency): array
    {
        // 1. Parse STRICT period boundaries
        $range = Period::parse($period);
        $previousRange = Period::getPreviousPeriod($range);

        // 2. Calculate paid revenue
        $actualStats = $this->calculateActualRevenue($user, $range, $baseCurrency);

        // 3. Calculate comparison revenue
        $previousTotal = $this->calculateComparisonRevenue($user, $previousRange, $baseCurrency);

        // 4. Calculate pipeline revenue (active jobs)
        $pipelineStats = $this->calculatePipelineRevenue($user, $baseCurrency);

        // 5. Calculate in-flight revenue (unpaid invoices + unbilled active jobs)
        $inFlightStats = $this->calculateInFlightRevenue($user, $baseCurrency);

        // 6. Return structured response
        return [
            'baseCurrency' => $baseCurrency,
            'period' => [
                'start' => $range['start']->timestamp * 1000,
                'end' => $range['end']->timestamp * 1000,
            ],
            'metrics' => [
                'current' => [
                    'total' => [
                        'amount_cents' => $actualStats['total'],
                        'currency' => $baseCurrency,
                        'precision' => $actualStats['accurate'] ? 'EXACT' : 'ESTIMATED',
                    ],
                    'trend_percentage' => Stats::calculateTrend($actualStats['total'], $previousTotal['total']),
                    'comparison_total' => [
                        'amount_cents' => $previousTotal['total'],
                        'currency' => $baseCurrency,
                        'precision' => $previousTotal['accurate'] ? 'EXACT' : 'ESTIMATED',
                    ],
                    'precision' => $actualStats['accurate'] ? 'EXACT' : 'ESTIMATED',
                ],
                'pipeline' => [
                    'total' => [
                        'amount_cents' => $pipelineStats['total'],
                        'currency' => $baseCurrency,
                        'precision' => $pipelineStats['accurate'] ? 'EXACT' : 'ESTIMATED',
                    ],
                ],
                'in_flight' => [
                    'total' => [
                        'amount_cents' => $inFlightStats['total'],
                        'currency' => $baseCurrency,
                        'precision' => $inFlightStats['accurate'] ? 'EXACT' : 'ESTIMATED',
                    ],
                    'precision' => $inFlightStats['accurate'] ? 'EXACT' : 'ESTIMATED',
                ],
            ],
        ];
    }

    /**
     * Calculate chart data with intelligent time window expansion.
     */
    protected function calculateChart(string $period, User $user, string $baseCurrency): array
    {
        $now = Carbon::now();

        // 1. Determine EFFECTIVE time window (with expansion)
        $windowData = Period::determineChartWindow($period, $now);
        $range = $windowData['range'];

        // 2. Determine grouping interval based on requested period
        $groupBy = Period::determineChartGrouping($period);

        // 3. Calculate chart buckets (chunk invoices)
        $buckets = [];
        Invoice::query()
            ->where('user_id', $user->id)
            ->paid()
            ->whereBetween('paid_at', [$range['start'], $range['end']])
            ->chunk(1000, function ($invoices) use (&$buckets, $baseCurrency, $groupBy) {
                foreach ($invoices as $invoice) {
                    $dateStr = $invoice->paid_at->format('Y-m-d');

                    $result = $this->exchangeRateService->convertWithMetadata(
                        $invoice->total_cents,
                        $invoice->total_currency,
                        $baseCurrency,
                        $invoice->paid_at
                    );

                    $bucketKey = $this->chartBuilder->toBucketKey($invoice->paid_at, $groupBy);

                    if (! isset($buckets[$bucketKey])) {
                        $buckets[$bucketKey] = 0;
                    }
                    $buckets[$bucketKey] += $result['amount'];
                }
            });

        // 4. Fill gaps and return
        $chart = $this->chartBuilder->fillGaps($buckets, $range, $groupBy);

        return [
            'baseCurrency' => $baseCurrency,
            'range' => [
                '__typename' => 'CompactRangeValue',
                'value' => $period,
            ],
            'effectiveWindow' => [
                'start' => $range['start']->timestamp * 1000,
                'end' => $range['end']->timestamp * 1000,
                'daysInRange' => (int) ($range['start']->diffInDays($range['end']) + 1),
                'wasExpanded' => $windowData['wasExpanded'],
                'expansionReason' => $windowData['reason'],
            ],
            'chart' => $chart,
        ];
    }

    /**
     * Calculate revenue totals by source, including paid + in-flight.
     *
     * @return array{
     *     baseCurrency: string,
     *     period: array{start: int, end: int},
     *     sources: array<int, array<string, mixed>>
     * }
     */
    protected function calculateRevenueBySource(
        string $period,
        User $user,
        string $baseCurrency,
        int $take
    ): array {
        $range = Period::parse($period);

        $sources = $this->seedRevenueSources($user);

        $this->applyPaidRevenueBySource($sources, $user, $range, $baseCurrency);
        $this->applyInFlightRevenueBySource($sources, $user, $baseCurrency);

        $sources = $this->finalizeRevenueSources($sources, $baseCurrency, $take);

        return [
            'baseCurrency' => $baseCurrency,
            'period' => [
                'start' => $range['start']->timestamp * 1000,
                'end' => $range['end']->timestamp * 1000,
            ],
            'sources' => $sources,
        ];
    }

    /**
     * Calculate revenue totals by category, including paid + in-flight.
     *
     * @return array{
     *     baseCurrency: string,
     *     period: array{start: int, end: int},
     *     categories: array<int, array<string, mixed>>
     * }
     */
    protected function calculateRevenueByCategory(
        string $period,
        User $user,
        string $baseCurrency,
        int $take
    ): array {
        $range = Period::parse($period);

        $categories = [];

        $this->applyPaidRevenueByCategory($categories, $user, $range, $baseCurrency);
        $this->applyInFlightRevenueByCategory($categories, $user, $baseCurrency);

        $categories = $this->finalizeRevenueCategories($categories, $baseCurrency, $take);

        return [
            'baseCurrency' => $baseCurrency,
            'period' => [
                'start' => $range['start']->timestamp * 1000,
                'end' => $range['end']->timestamp * 1000,
            ],
            'categories' => $categories,
        ];
    }

    /**
     * Calculate actual revenue for a period.
     */
    protected function calculateActualRevenue(User $user, array $range, string $baseCurrency): array
    {
        $actualStats = ['total' => 0, 'accurate' => true];

        Invoice::query()
            ->where('user_id', $user->id)
            ->paid()
            ->whereBetween('paid_at', [$range['start'], $range['end']])
            ->chunk(1000, function ($invoices) use (&$actualStats, $baseCurrency) {
                foreach ($invoices as $invoice) {
                    $result = $this->exchangeRateService->convertWithMetadata(
                        $invoice->total_cents,
                        $invoice->total_currency,
                        $baseCurrency,
                        $invoice->paid_at
                    );

                    $actualStats['total'] += $result['amount'];
                    if ($result['precision'] === 'ESTIMATED') {
                        $actualStats['accurate'] = false;
                    }
                }
            });

        return $actualStats;
    }

    /**
     * Calculate comparison revenue for previous period.
     */
    protected function calculateComparisonRevenue(User $user, array $range, string $baseCurrency): array
    {
        return $this->calculateActualRevenue($user, $range, $baseCurrency);
    }

    /**
     * Calculate pipeline revenue from active jobs.
     */
    protected function calculatePipelineRevenue(User $user, string $baseCurrency): array
    {
        $pipelineStats = ['total' => 0, 'accurate' => true];
        $today = Carbon::now();

        Job::query()
            ->where('user_id', $user->id)
            ->active()
            ->chunk(1000, function ($jobs) use (&$pipelineStats, $baseCurrency, $today) {
                foreach ($jobs as $job) {
                    $value = $this->calculateJobValue($job);
                    $result = $this->exchangeRateService->convertWithMetadata(
                        $value,
                        $job->contracted_rate_currency,
                        $baseCurrency,
                        $today
                    );

                    $pipelineStats['total'] += $result['amount'];
                    if ($result['precision'] === 'ESTIMATED') {
                        $pipelineStats['accurate'] = false;
                    }
                }
            });

        return $pipelineStats;
    }

    /**
     * Calculate money in-flight from unpaid invoices and unbilled active jobs.
     */
    protected function calculateInFlightRevenue(User $user, string $baseCurrency): array
    {
        $inFlightStats = ['total' => 0, 'accurate' => true];
        $today = Carbon::now();

        // Unpaid invoices (DRAFT, SENT, OVERDUE)
        Invoice::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                InvoiceStatus::DRAFT,
                InvoiceStatus::SENT,
                InvoiceStatus::OVERDUE,
            ])
            ->chunk(1000, function ($invoices) use (&$inFlightStats, $baseCurrency, $today) {
                foreach ($invoices as $invoice) {
                    $date = $invoice->issued_at ?? $invoice->created_at ?? $today;
                    $result = $this->exchangeRateService->convertWithMetadata(
                        $invoice->total_cents,
                        $invoice->total_currency,
                        $baseCurrency,
                        $date
                    );

                    $inFlightStats['total'] += $result['amount'];
                    if ($result['precision'] === 'ESTIMATED') {
                        $inFlightStats['accurate'] = false;
                    }
                }
            });

        // Unbilled active jobs (active jobs with no invoices)
        Job::query()
            ->where('user_id', $user->id)
            ->active()
            ->whereDoesntHave('invoices')
            ->chunk(1000, function ($jobs) use (&$inFlightStats, $baseCurrency, $today) {
                foreach ($jobs as $job) {
                    $value = $this->calculateJobValue($job);
                    $result = $this->exchangeRateService->convertWithMetadata(
                        $value,
                        $job->contracted_rate_currency,
                        $baseCurrency,
                        $today
                    );

                    $inFlightStats['total'] += $result['amount'];
                    if ($result['precision'] === 'ESTIMATED') {
                        $inFlightStats['accurate'] = false;
                    }
                }
            });

        return $inFlightStats;
    }

    /**
     * Seed all available sources for the user (platforms, agents, direct clients).
     *
     * @return array<string, array{
     *     source_type: string,
     *     source_id: string|null,
     *     source_name: string,
     *     paid_total: int,
     *     in_flight_total: int,
     *     paid_exact: bool,
     *     in_flight_exact: bool
     * }>
     */
    protected function seedRevenueSources(User $user): array
    {
        $sources = [];

        Platform::query()
            ->where('user_id', $user->id)
            ->get(['id', 'name'])
            ->each(function (Platform $platform) use (&$sources): void {
                $this->initializeSource($sources, $this->sourceKey('platform', (string) $platform->id), [
                    'source_type' => 'platform',
                    'source_id' => (string) $platform->id,
                    'source_name' => $platform->name,
                ]);
            });

        Contact::query()
            ->where('user_id', $user->id)
            ->where('contactable_type', Agent::class)
            ->get(['id', 'name', 'contactable_type'])
            ->each(function (Contact $contact) use (&$sources): void {
                $this->initializeSource($sources, $this->sourceKey('agent', (string) $contact->id), [
                    'source_type' => 'agent',
                    'source_id' => (string) $contact->id,
                    'source_name' => $contact->name,
                ]);
            });

        Contact::query()
            ->where('user_id', $user->id)
            ->where('contactable_type', Client::class)
            ->get(['id', 'name', 'contactable_type'])
            ->each(function (Contact $contact) use (&$sources): void {
                $this->initializeSource($sources, $this->sourceKey('direct', (string) $contact->id), [
                    'source_type' => 'direct',
                    'source_id' => (string) $contact->id,
                    'source_name' => $contact->name,
                ]);
            });

        return $sources;
    }

    /**
     * Add paid revenue totals grouped by source.
     */
    protected function applyPaidRevenueBySource(
        array &$sources,
        User $user,
        array $range,
        string $baseCurrency
    ): void {
        Invoice::query()
            ->where('user_id', $user->id)
            ->paid()
            ->whereBetween('paid_at', [$range['start'], $range['end']])
            ->with([
                'job.audition.sourceable',
                'job.agent',
                'job.client',
                'client',
            ])
            ->chunk(1000, function ($invoices) use (&$sources, $baseCurrency): void {
                $today = Carbon::now();

                foreach ($invoices as $invoice) {
                    $date = $invoice->paid_at ?? $invoice->created_at ?? $today;
                    $result = $this->exchangeRateService->convertWithMetadata(
                        $invoice->total_cents,
                        $invoice->total_currency,
                        $baseCurrency,
                        $date
                    );

                    $sourceKey = $this->resolveRevenueSourceKey(
                        $sources,
                        $invoice->job,
                        $invoice->job?->audition,
                        $invoice->client
                    );

                    $this->addSourceAmount($sources, $sourceKey, 'paid', $result['amount'], $result['precision']);
                }
            });
    }

    /**
     * Add in-flight revenue totals grouped by source.
     *
     * In-flight = unpaid invoices + unbilled active jobs.
     */
    protected function applyInFlightRevenueBySource(
        array &$sources,
        User $user,
        string $baseCurrency
    ): void {
        $today = Carbon::now();

        Invoice::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                InvoiceStatus::DRAFT,
                InvoiceStatus::SENT,
                InvoiceStatus::OVERDUE,
            ])
            ->with([
                'job.audition.sourceable',
                'job.agent',
                'job.client',
                'client',
            ])
            ->chunk(1000, function ($invoices) use (&$sources, $baseCurrency, $today): void {
                foreach ($invoices as $invoice) {
                    $date = $invoice->issued_at ?? $invoice->created_at ?? $today;
                    $result = $this->exchangeRateService->convertWithMetadata(
                        $invoice->total_cents,
                        $invoice->total_currency,
                        $baseCurrency,
                        $date
                    );

                    $sourceKey = $this->resolveRevenueSourceKey(
                        $sources,
                        $invoice->job,
                        $invoice->job?->audition,
                        $invoice->client
                    );

                    $this->addSourceAmount($sources, $sourceKey, 'in_flight', $result['amount'], $result['precision']);
                }
            });

        Job::query()
            ->where('user_id', $user->id)
            ->active()
            ->whereDoesntHave('invoices')
            ->with(['audition.sourceable', 'agent', 'client'])
            ->chunk(1000, function ($jobs) use (&$sources, $baseCurrency, $today): void {
                foreach ($jobs as $job) {
                    $value = $this->calculateJobValue($job);
                    $result = $this->exchangeRateService->convertWithMetadata(
                        $value,
                        $job->contracted_rate_currency,
                        $baseCurrency,
                        $today
                    );

                    $sourceKey = $this->resolveRevenueSourceKey(
                        $sources,
                        $job,
                        $job->audition,
                        null
                    );

                    $this->addSourceAmount($sources, $sourceKey, 'in_flight', $result['amount'], $result['precision']);
                }
            });
    }

    /**
     * Add paid revenue totals grouped by category.
     */
    protected function applyPaidRevenueByCategory(
        array &$categories,
        User $user,
        array $range,
        string $baseCurrency
    ): void {
        Invoice::query()
            ->where('user_id', $user->id)
            ->paid()
            ->whereBetween('paid_at', [$range['start'], $range['end']])
            ->with('job')
            ->chunk(1000, function ($invoices) use (&$categories, $baseCurrency): void {
                $today = Carbon::now();

                foreach ($invoices as $invoice) {
                    $date = $invoice->paid_at ?? $invoice->created_at ?? $today;
                    $result = $this->exchangeRateService->convertWithMetadata(
                        $invoice->total_cents,
                        $invoice->total_currency,
                        $baseCurrency,
                        $date
                    );

                    $category = $invoice->job?->category ?? 'unknown';

                    $this->addCategoryAmount($categories, $category, 'paid', $result['amount'], $result['precision']);
                }
            });
    }

    /**
     * Add in-flight revenue totals grouped by category.
     *
     * In-flight = unpaid invoices + unbilled active jobs.
     */
    protected function applyInFlightRevenueByCategory(
        array &$categories,
        User $user,
        string $baseCurrency
    ): void {
        $today = Carbon::now();

        Invoice::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                InvoiceStatus::DRAFT,
                InvoiceStatus::SENT,
                InvoiceStatus::OVERDUE,
            ])
            ->with('job')
            ->chunk(1000, function ($invoices) use (&$categories, $baseCurrency, $today): void {
                foreach ($invoices as $invoice) {
                    $date = $invoice->issued_at ?? $invoice->created_at ?? $today;
                    $result = $this->exchangeRateService->convertWithMetadata(
                        $invoice->total_cents,
                        $invoice->total_currency,
                        $baseCurrency,
                        $date
                    );

                    $category = $invoice->job?->category ?? 'unknown';

                    $this->addCategoryAmount($categories, $category, 'in_flight', $result['amount'], $result['precision']);
                }
            });

        Job::query()
            ->where('user_id', $user->id)
            ->active()
            ->whereDoesntHave('invoices')
            ->chunk(1000, function ($jobs) use (&$categories, $baseCurrency, $today): void {
                foreach ($jobs as $job) {
                    $value = $this->calculateJobValue($job);
                    $result = $this->exchangeRateService->convertWithMetadata(
                        $value,
                        $job->contracted_rate_currency,
                        $baseCurrency,
                        $today
                    );

                    $category = $job->category ?? 'unknown';

                    $this->addCategoryAmount($categories, $category, 'in_flight', $result['amount'], $result['precision']);
                }
            });
    }

    /**
     * Resolve a source key using precedence rules.
     */
    protected function resolveRevenueSourceKey(
        array &$sources,
        ?Job $job,
        ?Audition $audition,
        ?Contact $fallbackClient
    ): string {
        if ($audition && $audition->sourceable) {
            $sourceable = $audition->sourceable;
            if ($sourceable instanceof Platform) {
                return $this->ensureSourceFromPlatform($sources, $sourceable);
            }

            if ($sourceable instanceof Contact) {
                return $this->ensureSourceFromContact($sources, $sourceable);
            }
        }

        if ($job?->agent) {
            return $this->ensureSourceFromContact($sources, $job->agent, 'agent');
        }

        if ($job?->client) {
            return $this->ensureSourceFromContact($sources, $job->client, 'direct');
        }

        if ($fallbackClient) {
            return $this->ensureSourceFromContact($sources, $fallbackClient, 'direct');
        }

        return $this->ensureUnknownSource($sources);
    }

    protected function ensureSourceFromPlatform(array &$sources, Platform $platform): string
    {
        $key = $this->sourceKey('platform', (string) $platform->id);
        $this->initializeSource($sources, $key, [
            'source_type' => 'platform',
            'source_id' => (string) $platform->id,
            'source_name' => $platform->name,
        ]);

        return $key;
    }

    protected function ensureSourceFromContact(
        array &$sources,
        Contact $contact,
        ?string $overrideType = null
    ): string {
        $type = $overrideType ?? ($contact->contactable_type === Agent::class ? 'agent' : 'direct');
        $key = $this->sourceKey($type, (string) $contact->id);

        $this->initializeSource($sources, $key, [
            'source_type' => $type,
            'source_id' => (string) $contact->id,
            'source_name' => $contact->name,
        ]);

        return $key;
    }

    protected function ensureUnknownSource(array &$sources): string
    {
        $key = $this->sourceKey('unknown', null);

        $this->initializeSource($sources, $key, [
            'source_type' => 'unknown',
            'source_id' => null,
            'source_name' => 'Unknown',
        ]);

        return $key;
    }

    protected function initializeSource(array &$sources, string $key, array $attributes): void
    {
        if (isset($sources[$key])) {
            return;
        }

        $sources[$key] = [
            'source_type' => $attributes['source_type'],
            'source_id' => $attributes['source_id'],
            'source_name' => $attributes['source_name'],
            'paid_total' => 0,
            'in_flight_total' => 0,
            'paid_exact' => true,
            'in_flight_exact' => true,
        ];
    }

    protected function sourceKey(string $type, ?string $id): string
    {
        return $id ? "{$type}:{$id}" : $type;
    }

    protected function addSourceAmount(
        array &$sources,
        string $key,
        string $bucket,
        int $amount,
        string $precision
    ): void {
        $totalKey = $bucket === 'paid' ? 'paid_total' : 'in_flight_total';
        $precisionKey = $bucket === 'paid' ? 'paid_exact' : 'in_flight_exact';

        $sources[$key][$totalKey] += $amount;

        if ($precision === 'ESTIMATED') {
            $sources[$key][$precisionKey] = false;
        }
    }

    protected function initializeCategory(array &$categories, string $category): void
    {
        if (isset($categories[$category])) {
            return;
        }

        $categories[$category] = [
            'category' => $category,
            'paid_total' => 0,
            'in_flight_total' => 0,
            'paid_exact' => true,
            'in_flight_exact' => true,
        ];
    }

    protected function addCategoryAmount(
        array &$categories,
        string $category,
        string $bucket,
        int $amount,
        string $precision
    ): void {
        $this->initializeCategory($categories, $category);

        $totalKey = $bucket === 'paid' ? 'paid_total' : 'in_flight_total';
        $precisionKey = $bucket === 'paid' ? 'paid_exact' : 'in_flight_exact';

        $categories[$category][$totalKey] += $amount;

        if ($precision === 'ESTIMATED') {
            $categories[$category][$precisionKey] = false;
        }
    }

    /**
     * Finalize and format sources for revenue-by-source charts.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function finalizeRevenueSources(array $sources, string $baseCurrency, int $take): array
    {
        $rows = array_values($sources);

        foreach ($rows as &$row) {
            $row['total'] = $row['paid_total'] + $row['in_flight_total'];
        }
        unset($row);

        usort($rows, function (array $a, array $b): int {
            if ($a['total'] === $b['total']) {
                return strcmp($a['source_name'], $b['source_name']);
            }

            return $b['total'] <=> $a['total'];
        });

        $rows = array_values(array_filter($rows, fn (array $row): bool => $row['total'] > 0));

        $rows = array_slice($rows, 0, $take);

        $finalTotal = array_sum(array_map(
            fn (array $row): int => $row['paid_total'] + $row['in_flight_total'],
            $rows
        ));

        return array_map(function (array $row) use ($baseCurrency, $finalTotal): array {
            $percentage = $finalTotal === 0 ? 0.0 : round((($row['paid_total'] + $row['in_flight_total']) / $finalTotal) * 100, 1);

            return [
                'source_type' => $row['source_type'],
                'source_name' => $row['source_name'],
                'paid' => [
                    'amount_cents' => $row['paid_total'],
                    'currency' => $baseCurrency,
                    'precision' => $row['paid_exact'] ? 'EXACT' : 'ESTIMATED',
                ],
                'in_flight' => [
                    'amount_cents' => $row['in_flight_total'],
                    'currency' => $baseCurrency,
                    'precision' => $row['in_flight_exact'] ? 'EXACT' : 'ESTIMATED',
                ],
                'percentage_of_total' => $percentage,
            ];
        }, $rows);
    }

    /**
     * Finalize and format categories for revenue-by-category charts.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function finalizeRevenueCategories(array $categories, string $baseCurrency, int $take): array
    {
        $rows = array_values($categories);

        foreach ($rows as &$row) {
            $row['total'] = $row['paid_total'] + $row['in_flight_total'];
        }
        unset($row);

        usort($rows, function (array $a, array $b): int {
            if ($a['total'] === $b['total']) {
                return strcmp($a['category'], $b['category']);
            }

            return $b['total'] <=> $a['total'];
        });

        $rows = array_values(array_filter($rows, fn (array $row): bool => $row['total'] > 0));
        $rows = array_slice($rows, 0, $take);

        $finalTotal = array_sum(array_map(
            fn (array $row): int => $row['paid_total'] + $row['in_flight_total'],
            $rows
        ));

        return array_map(function (array $row) use ($baseCurrency, $finalTotal): array {
            $percentage = $finalTotal === 0 ? 0.0 : round((($row['paid_total'] + $row['in_flight_total']) / $finalTotal) * 100, 1);

            return [
                'category' => $row['category'],
                'paid' => [
                    'amount_cents' => $row['paid_total'],
                    'currency' => $baseCurrency,
                    'precision' => $row['paid_exact'] ? 'EXACT' : 'ESTIMATED',
                ],
                'in_flight' => [
                    'amount_cents' => $row['in_flight_total'],
                    'currency' => $baseCurrency,
                    'precision' => $row['in_flight_exact'] ? 'EXACT' : 'ESTIMATED',
                ],
                'percentage_of_total' => $percentage,
            ];
        }, $rows);
    }

    protected function normalizeTake(int $take): int
    {
        if ($take <= 0) {
            return 10;
        }

        return min($take, 25);
    }
}
