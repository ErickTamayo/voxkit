<?php

declare(strict_types=1);

use App\Services\ChartBuilder;
use Carbon\Carbon;

beforeEach(function () {
    $this->builder = new ChartBuilder;
    Carbon::setTestNow('2024-06-15 12:00:00');
});

describe('toBucketKey', function () {
    test('it generates daily bucket keys correctly', function () {
        $date = Carbon::parse('2024-06-15 14:30:00');

        $key = $this->builder->toBucketKey($date, 'day');

        expect($key)->toBe('2024-06-15');
    });

    test('it generates weekly bucket keys aligned to week start', function () {
        // June 15, 2024 is a Saturday
        // Week starts on Monday (June 10)
        $date = Carbon::parse('2024-06-15 14:30:00');

        $key = $this->builder->toBucketKey($date, 'week');

        expect($key)->toBe('2024-06-10'); // Monday of that week
    });

    test('it generates monthly bucket keys aligned to month start', function () {
        $date = Carbon::parse('2024-06-15 14:30:00');

        $key = $this->builder->toBucketKey($date, 'month');

        expect($key)->toBe('2024-06-01');
    });

    test('it defaults to daily bucket key for unknown grouping', function () {
        $date = Carbon::parse('2024-06-15 14:30:00');

        $key = $this->builder->toBucketKey($date, 'unknown');

        expect($key)->toBe('2024-06-15');
    });

    test('it handles dates at start of week correctly', function () {
        // June 10, 2024 is a Monday (start of week)
        $date = Carbon::parse('2024-06-10 00:00:00');

        $key = $this->builder->toBucketKey($date, 'week');

        expect($key)->toBe('2024-06-10');
    });

    test('it handles dates at end of week correctly', function () {
        // June 16, 2024 is a Sunday (end of week)
        $date = Carbon::parse('2024-06-16 23:59:59');

        $key = $this->builder->toBucketKey($date, 'week');

        expect($key)->toBe('2024-06-10'); // Same Monday
    });
});

describe('fillGaps', function () {
    test('it fills daily gaps correctly', function () {
        // Buckets with data for June 10 and June 12 (missing June 11)
        $buckets = [
            '2024-06-10' => 10000,
            '2024-06-12' => 15000,
        ];

        $range = [
            'start' => Carbon::parse('2024-06-10'),
            'end' => Carbon::parse('2024-06-12'),
        ];

        $result = $this->builder->fillGaps($buckets, $range, 'day');

        expect($result)->toHaveCount(3);
        expect($result[0]['value'])->toBe(10000);
        expect($result[1]['value'])->toBe(0); // June 11 filled with 0
        expect($result[2]['value'])->toBe(15000);
    });

    test('it fills weekly gaps correctly', function () {
        // Buckets for weeks starting June 3 and June 17 (missing June 10)
        $buckets = [
            '2024-06-03' => 20000,
            '2024-06-17' => 25000,
        ];

        $range = [
            'start' => Carbon::parse('2024-06-03'),
            'end' => Carbon::parse('2024-06-20'),
        ];

        $result = $this->builder->fillGaps($buckets, $range, 'week');

        expect($result)->toHaveCount(3);
        expect($result[0]['value'])->toBe(20000);
        expect($result[1]['value'])->toBe(0); // Week of June 10 filled with 0
        expect($result[2]['value'])->toBe(25000);
    });

    test('it fills monthly gaps correctly', function () {
        // Buckets for January and March (missing February)
        $buckets = [
            '2024-01-01' => 30000,
            '2024-03-01' => 35000,
        ];

        $range = [
            'start' => Carbon::parse('2024-01-01'),
            'end' => Carbon::parse('2024-03-15'),
        ];

        $result = $this->builder->fillGaps($buckets, $range, 'month');

        expect($result)->toHaveCount(3);
        expect($result[0]['value'])->toBe(30000);
        expect($result[1]['value'])->toBe(0); // February filled with 0
        expect($result[2]['value'])->toBe(35000);
    });

    test('it returns timestamps in UTC milliseconds', function () {
        $buckets = ['2024-06-10' => 10000];

        $range = [
            'start' => Carbon::parse('2024-06-10'),
            'end' => Carbon::parse('2024-06-10'),
        ];

        $result = $this->builder->fillGaps($buckets, $range, 'day');

        $expectedTimestamp = Carbon::parse('2024-06-10')->startOfDay()->timestamp * 1000;
        expect($result[0]['timestamp'])->toBe($expectedTimestamp);
    });

    test('it handles empty buckets by filling all with zeros', function () {
        $buckets = [];

        $range = [
            'start' => Carbon::parse('2024-06-10'),
            'end' => Carbon::parse('2024-06-12'),
        ];

        $result = $this->builder->fillGaps($buckets, $range, 'day');

        expect($result)->toHaveCount(3);
        expect($result[0]['value'])->toBe(0);
        expect($result[1]['value'])->toBe(0);
        expect($result[2]['value'])->toBe(0);
    });

    test('it aligns weekly grouping to week start', function () {
        // Range doesn't start on Monday
        $buckets = ['2024-06-10' => 10000]; // Monday

        $range = [
            'start' => Carbon::parse('2024-06-12'), // Wednesday
            'end' => Carbon::parse('2024-06-15'),
        ];

        $result = $this->builder->fillGaps($buckets, $range, 'week');

        // Should align to Monday June 10
        $expectedTimestamp = Carbon::parse('2024-06-10')->startOfWeek()->startOfDay()->timestamp * 1000;
        expect($result[0]['timestamp'])->toBe($expectedTimestamp);
    });

    test('it deduplicates timestamps correctly', function () {
        // Create scenario where same timestamp might be generated twice
        $buckets = [
            '2024-06-01' => 5000,
            '2024-06-01' => 10000, // Duplicate key (will be overwritten in array)
        ];

        $range = [
            'start' => Carbon::parse('2024-06-01'),
            'end' => Carbon::parse('2024-06-01'),
        ];

        $result = $this->builder->fillGaps($buckets, $range, 'day');

        expect($result)->toHaveCount(1);
        expect($result[0]['value'])->toBe(10000); // Last value wins
    });

    test('it handles single day range', function () {
        $buckets = ['2024-06-15' => 10000];

        $range = [
            'start' => Carbon::parse('2024-06-15'),
            'end' => Carbon::parse('2024-06-15'),
        ];

        $result = $this->builder->fillGaps($buckets, $range, 'day');

        expect($result)->toHaveCount(1);
        expect($result[0]['value'])->toBe(10000);
    });

    test('it handles cross-year date ranges', function () {
        $buckets = [
            '2023-12-01' => 10000,
            '2024-01-01' => 15000,
        ];

        $range = [
            'start' => Carbon::parse('2023-12-01'),
            'end' => Carbon::parse('2024-01-15'),
        ];

        $result = $this->builder->fillGaps($buckets, $range, 'month');

        expect($result)->toHaveCount(2);
        expect($result[0]['value'])->toBe(10000); // December
        expect($result[1]['value'])->toBe(15000); // January
    });

    test('it maintains chronological order', function () {
        $buckets = [
            '2024-06-15' => 15000,
            '2024-06-10' => 10000,
            '2024-06-12' => 12000,
        ];

        $range = [
            'start' => Carbon::parse('2024-06-10'),
            'end' => Carbon::parse('2024-06-15'),
        ];

        $result = $this->builder->fillGaps($buckets, $range, 'day');

        expect($result)->toHaveCount(6);

        // Verify chronological order
        for ($i = 0; $i < count($result) - 1; $i++) {
            expect($result[$i]['timestamp'])->toBeLessThan($result[$i + 1]['timestamp']);
        }
    });

    test('it handles leap year dates correctly', function () {
        // 2024 is a leap year
        $buckets = [
            '2024-02-28' => 10000,
            '2024-03-01' => 15000,
        ];

        $range = [
            'start' => Carbon::parse('2024-02-28'),
            'end' => Carbon::parse('2024-03-01'),
        ];

        $result = $this->builder->fillGaps($buckets, $range, 'day');

        expect($result)->toHaveCount(3);
        expect($result[1]['value'])->toBe(0); // Feb 29 should be included
    });

    test('it handles weekly ranges spanning multiple weeks', function () {
        $buckets = [
            '2024-06-03' => 10000, // Week 1
            '2024-06-10' => 15000, // Week 2
            '2024-06-17' => 20000, // Week 3
        ];

        $range = [
            'start' => Carbon::parse('2024-06-03'),
            'end' => Carbon::parse('2024-06-20'),
        ];

        $result = $this->builder->fillGaps($buckets, $range, 'week');

        expect($result)->toHaveCount(3);

        // Verify all values are present
        expect($result[0]['value'])->toBe(10000);
        expect($result[1]['value'])->toBe(15000);
        expect($result[2]['value'])->toBe(20000);
    });

    test('it returns correct structure with timestamp and value fields', function () {
        $buckets = ['2024-06-10' => 10000];

        $range = [
            'start' => Carbon::parse('2024-06-10'),
            'end' => Carbon::parse('2024-06-10'),
        ];

        $result = $this->builder->fillGaps($buckets, $range, 'day');

        expect($result[0])->toHaveKeys(['timestamp', 'value']);
        expect($result[0]['timestamp'])->toBeInt();
        expect($result[0]['value'])->toBeInt();
    });
});

describe('edge cases', function () {
    test('it handles very large amounts without overflow', function () {
        $buckets = ['2024-06-10' => PHP_INT_MAX - 1000];

        $range = [
            'start' => Carbon::parse('2024-06-10'),
            'end' => Carbon::parse('2024-06-10'),
        ];

        $result = $this->builder->fillGaps($buckets, $range, 'day');

        expect($result[0]['value'])->toBe(PHP_INT_MAX - 1000);
    });

    test('it handles zero amounts correctly', function () {
        $buckets = ['2024-06-10' => 0];

        $range = [
            'start' => Carbon::parse('2024-06-10'),
            'end' => Carbon::parse('2024-06-10'),
        ];

        $result = $this->builder->fillGaps($buckets, $range, 'day');

        expect($result[0]['value'])->toBe(0);
    });

    test('it handles negative amounts correctly', function () {
        // For refunds or adjustments
        $buckets = ['2024-06-10' => -5000];

        $range = [
            'start' => Carbon::parse('2024-06-10'),
            'end' => Carbon::parse('2024-06-10'),
        ];

        $result = $this->builder->fillGaps($buckets, $range, 'day');

        expect($result[0]['value'])->toBe(-5000);
    });

    test('it handles end of month correctly', function () {
        $buckets = [
            '2024-01-01' => 10000,
            '2024-02-01' => 15000,
        ];

        $range = [
            'start' => Carbon::parse('2024-01-31'),
            'end' => Carbon::parse('2024-02-01'),
        ];

        $result = $this->builder->fillGaps($buckets, $range, 'day');

        expect($result)->toHaveCount(2);
        expect($result[0]['value'])->toBe(0); // Jan 31
        expect($result[1]['value'])->toBe(15000); // Feb 1
    });

    test('it handles year boundaries in monthly grouping', function () {
        $buckets = [
            '2023-12-01' => 10000,
            '2024-01-01' => 15000,
            '2024-02-01' => 20000,
        ];

        $range = [
            'start' => Carbon::parse('2023-12-01'),
            'end' => Carbon::parse('2024-02-15'),
        ];

        $result = $this->builder->fillGaps($buckets, $range, 'month');

        expect($result)->toHaveCount(3);
        expect($result[0]['value'])->toBe(10000);
        expect($result[1]['value'])->toBe(15000);
        expect($result[2]['value'])->toBe(20000);
    });
});

describe('performance', function () {
    test('it handles large date ranges efficiently', function () {
        // Year-long daily range (365 days)
        $buckets = [];

        // Add some scattered data points
        for ($i = 0; $i < 365; $i += 30) {
            $date = Carbon::parse('2024-01-01')->addDays($i);
            $buckets[$date->format('Y-m-d')] = 10000;
        }

        $range = [
            'start' => Carbon::parse('2024-01-01'),
            'end' => Carbon::parse('2024-12-31'),
        ];

        $startTime = microtime(true);
        $result = $this->builder->fillGaps($buckets, $range, 'day');
        $duration = microtime(true) - $startTime;

        expect($result)->toHaveCount(366); // 2024 is a leap year
        expect($duration)->toBeLessThan(1.0); // Should complete in under 1 second
    });
});
