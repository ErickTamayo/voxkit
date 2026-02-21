<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\Carbon;

class Period
{
    public static function parse(string $period, ?Carbon $now = null): array
    {
        $now = $now ?? Carbon::now();

        return match ($period) {
            '1W' => ['start' => $now->copy()->subDays(6)->startOfDay(), 'end' => $now->copy()->endOfDay()],
            '4W' => ['start' => $now->copy()->subDays(27)->startOfDay(), 'end' => $now->copy()->endOfDay()],
            'MTD' => ['start' => $now->copy()->startOfMonth(), 'end' => $now->copy()->endOfDay()],
            'QTD' => ['start' => $now->copy()->startOfQuarter(), 'end' => $now->copy()->endOfDay()],
            'YTD' => ['start' => $now->copy()->startOfYear(), 'end' => $now->copy()->endOfDay()],
            '1Y' => ['start' => $now->copy()->subYear()->startOfDay(), 'end' => $now->copy()->endOfDay()],
            'ALL' => ['start' => Carbon::create(2000, 1, 1), 'end' => $now->copy()->endOfDay()],
            default => ['start' => $now->copy()->subDays(6)->startOfDay(), 'end' => $now->copy()->endOfDay()],
        };
    }

    public static function getPreviousPeriod(array $range): array
    {
        $start = $range['start']->copy();
        $end = $range['end']->copy();
        $diffInDays = $start->diffInDays($end) + 1;

        return [
            'start' => $start->subDays($diffInDays),
            'end' => $end->subDays($diffInDays),
        ];
    }

    public static function determineChartGrouping(string $period): string
    {
        return match ($period) {
            '1W', '4W', 'MTD' => 'day',
            'QTD' => 'week',
            '1Y', 'YTD', 'ALL' => 'month',
            default => 'day',
        };
    }

    public static function determineChartWindow(string $period, Carbon $now): array
    {
        $strictRange = self::parse($period, $now);
        $daysInPeriod = (int) ceil($strictRange['start']->diffInDays($now) + 1);

        $shouldExpand = match ($period) {
            'MTD' => $daysInPeriod <= 7,
            'QTD' => $daysInPeriod <= 14,
            'YTD' => $daysInPeriod <= 30,
            default => false,
        };

        if (! $shouldExpand) {
            return [
                'range' => $strictRange,
                'wasExpanded' => false,
                'reason' => null,
            ];
        }

        $expandedRange = match ($period) {
            'MTD' => ['start' => $now->copy()->subDays(29)->startOfDay(), 'end' => $now->copy()->endOfDay()],
            'QTD' => ['start' => $now->copy()->subDays(89)->startOfDay(), 'end' => $now->copy()->endOfDay()],
            'YTD' => ['start' => $now->copy()->subDays(364)->startOfDay(), 'end' => $now->copy()->endOfDay()],
            default => $strictRange,
        };

        return [
            'range' => $expandedRange,
            'wasExpanded' => true,
            'reason' => match ($period) {
                'MTD' => "Month has only {$daysInPeriod} days, showing 30-day window for better visualization",
                'QTD' => "Quarter has only {$daysInPeriod} days, showing 90-day window for better visualization",
                'YTD' => "Year has only {$daysInPeriod} days, showing 365-day window for better visualization",
                default => null,
            },
        ];
    }
}
