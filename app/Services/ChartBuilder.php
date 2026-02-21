<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Service for building time-series chart data.
 *
 * Handles bucketing data by time periods (day/week/month) and filling gaps
 * in the timeline with zero values for consistent chart visualization.
 */
class ChartBuilder
{
    /**
     * Convert a date to a bucket key based on the grouping interval.
     *
     * @param  string  $groupBy  'day', 'week', or 'month'
     */
    public function toBucketKey(Carbon $date, string $groupBy): string
    {
        return match ($groupBy) {
            'day' => $date->format('Y-m-d'),
            'week' => $date->copy()->startOfWeek()->format('Y-m-d'),
            'month' => $date->format('Y-m-01'),
            default => $date->format('Y-m-d'),
        };
    }

    /**
     * Fill gaps in chart data with zero values for missing dates.
     *
     * @param  array  $buckets  Associative array [bucketKey => value]
     * @param  array  $range  ['start' => Carbon, 'end' => Carbon]
     * @param  string  $groupBy  'day', 'week', or 'month'
     * @return array Chart points with structure [['timestamp' => int, 'value' => int], ...]
     */
    public function fillGaps(array $buckets, array $range, string $groupBy): array
    {
        $chart = [];
        $start = $range['start']->copy();
        $end = $range['end']->copy();

        $interval = match ($groupBy) {
            'day' => '1 day',
            'week' => '1 week',
            'month' => '1 month',
        };

        // For weekly grouping, align start to beginning of week
        if ($groupBy === 'week') {
            $start = $start->startOfWeek();
        }

        $periodRange = CarbonPeriod::create($start, $interval, $end);

        foreach ($periodRange as $date) {
            $bucketKey = $this->toBucketKey($date, $groupBy);

            // Convert date to UTC timestamp in milliseconds for the label
            $labelDate = match ($groupBy) {
                'day' => $date->copy()->startOfDay(),
                'week' => $date->copy()->startOfWeek()->startOfDay(),
                'month' => Carbon::parse($date->format('Y-m-01'))->startOfDay(),
            };

            $chart[] = [
                'timestamp' => $labelDate->timestamp * 1000, // Unix timestamp in milliseconds
                'value' => $buckets[$bucketKey] ?? 0,
            ];
        }

        // De-dupe if multiple iterations hit the same timestamp
        $deduped = [];
        foreach ($chart as $item) {
            $key = $item['timestamp'];
            if (! isset($deduped[$key])) {
                $deduped[$key] = $item;
            }
        }

        return array_values($deduped);
    }
}
