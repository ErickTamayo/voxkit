<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Enums\AuditionStatus;
use App\Models\Audition;
use App\Models\User;
use App\Support\Period;
use App\Support\Stats;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AuditionService
{
    public function __construct(
        protected ChartBuilder $chartBuilder
    ) {}

    /**
     * Calculate audition metrics with strict period boundaries.
     *
     * MTD = exactly from month start to today
     * QTD = exactly from quarter start to today
     * No time window expansion.
     */
    public function getAuditionMetrics(string $period, User $user): array
    {
        $cacheTag = 'audition_metrics';
        $cacheKey = "audition_metrics_{$user->id}_{$period}";

        return Cache::tags([$cacheTag])->remember($cacheKey, 300, function () use ($period, $user) {
            return $this->calculateMetrics($period, $user);
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
    public function getAuditionChart(string $period, User $user): array
    {
        $cacheTag = 'audition_chart';
        $cacheKey = "audition_chart_{$user->id}_{$period}";

        return Cache::tags([$cacheTag])->remember($cacheKey, 300, function () use ($period, $user) {
            return $this->calculateChart($period, $user);
        });
    }

    /**
     * Calculate metrics with strict period boundaries (no expansion).
     */
    protected function calculateMetrics(string $period, User $user): array
    {
        // 1. Parse STRICT period boundaries
        $range = Period::parse($period);
        $previousRange = Period::getPreviousPeriod($range);

        // 2. Calculate current period stats
        $currentStats = $this->calculateCurrentPeriodStats($user, $range);

        // 3. Calculate comparison period stats
        $previousStats = $this->calculateCurrentPeriodStats($user, $previousRange);

        // 4. Calculate booking rate for current period
        $bookingRate = $this->calculateBookingRate($user, $range);

        // 5. Return structured response
        return [
            'period' => [
                'start' => $range['start']->timestamp * 1000,
                'end' => $range['end']->timestamp * 1000,
            ],
            'metrics' => [
                'current' => [
                    'total' => $currentStats['total'],
                    'trend_percentage' => Stats::calculateTrend($currentStats['total'], $previousStats['total']),
                    'comparison_total' => $previousStats['total'],
                ],
                'booking_rate' => $bookingRate,
            ],
        ];
    }

    /**
     * Calculate chart data with intelligent time window expansion.
     */
    protected function calculateChart(string $period, User $user): array
    {
        $now = Carbon::now();

        // 1. Determine EFFECTIVE time window (with expansion)
        $windowData = Period::determineChartWindow($period, $now);
        $range = $windowData['range'];

        // 2. Determine grouping interval based on requested period
        $groupBy = Period::determineChartGrouping($period);

        // 3. Calculate chart buckets (chunk auditions by submitted_at)
        $buckets = [];
        Audition::query()
            ->where('user_id', $user->id)
            ->whereNotNull('submitted_at')
            ->whereBetween('submitted_at', [$range['start'], $range['end']])
            ->chunk(1000, function ($auditions) use (&$buckets, $groupBy) {
                foreach ($auditions as $audition) {
                    $bucketKey = $this->chartBuilder->toBucketKey($audition->submitted_at, $groupBy);

                    if (! isset($buckets[$bucketKey])) {
                        $buckets[$bucketKey] = 0;
                    }
                    $buckets[$bucketKey]++;
                }
            });

        // 4. Fill gaps and return
        $chart = $this->chartBuilder->fillGaps($buckets, $range, $groupBy);

        return [
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
     * Calculate total auditions submitted in a period.
     */
    protected function calculateCurrentPeriodStats(User $user, array $range): array
    {
        $total = Audition::query()
            ->where('user_id', $user->id)
            ->whereNotNull('submitted_at')
            ->whereBetween('submitted_at', [$range['start'], $range['end']])
            ->count();

        return ['total' => $total];
    }

    /**
     * Calculate booking rate: percentage of WON auditions vs total auditions in period.
     * Returns 0.0 if no auditions exist to avoid division by zero.
     */
    protected function calculateBookingRate(User $user, array $range): float
    {
        $totalAuditions = Audition::query()
            ->where('user_id', $user->id)
            ->whereNotNull('submitted_at')
            ->whereBetween('submitted_at', [$range['start'], $range['end']])
            ->count();

        if ($totalAuditions === 0) {
            return 0.0;
        }

        $wonAuditions = Audition::query()
            ->where('user_id', $user->id)
            ->where('status', AuditionStatus::WON)
            ->whereNotNull('submitted_at')
            ->whereBetween('submitted_at', [$range['start'], $range['end']])
            ->count();

        return round(($wonAuditions / $totalAuditions) * 100, 1);
    }
}
