<?php

declare(strict_types=1);

use App\Models\ExchangeRate;
use App\Models\Invoice;
use App\Models\User;
use App\Services\RevenueService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = app(RevenueService::class);
    $this->user = User::factory()->create();

    // Create exchange rates for USD
    ExchangeRate::factory()->create([
        'currency_code' => 'USD',
        'rate' => 1.0,
        'effective_date' => Carbon::now()->subDays(400)->format('Y-m-d'),
    ]);
});

describe('Chart Window Expansion', function () {
    test('MTD on day 1 expands to 30 days with integer daysInRange', function () {
        Carbon::setTestNow(Carbon::parse('2026-02-01 12:00:00'));

        $result = $this->service->getRevenueChart('MTD', $this->user, 'USD');

        expect($result['effectiveWindow']['wasExpanded'])->toBeTrue();
        expect($result['effectiveWindow']['daysInRange'])->toBe(30);
        expect($result['effectiveWindow']['daysInRange'])->toBeInt();
        expect($result['effectiveWindow']['expansionReason'])->toContain('Month has only');
        expect($result['effectiveWindow']['expansionReason'])->toContain('days');
        expect($result['effectiveWindow']['expansionReason'])->not->toContain('.');
    });

    test('MTD on day 7 (threshold) expands to 30 days', function () {
        Carbon::setTestNow(Carbon::parse('2026-02-07 00:00:00'));

        $result = $this->service->getRevenueChart('MTD', $this->user, 'USD');

        expect($result['effectiveWindow']['wasExpanded'])->toBeTrue();
        expect($result['effectiveWindow']['daysInRange'])->toBe(30);
        expect($result['effectiveWindow']['expansionReason'])->toContain('Month has only 7 days');
    });

    test('MTD on day 8 does NOT expand', function () {
        Carbon::setTestNow(Carbon::parse('2026-02-08 12:00:00'));

        $result = $this->service->getRevenueChart('MTD', $this->user, 'USD');

        expect($result['effectiveWindow']['wasExpanded'])->toBeFalse();
        expect($result['effectiveWindow']['daysInRange'])->toBe(8);
        expect($result['effectiveWindow']['expansionReason'])->toBeNull();
    });

    test('QTD on day 1 expands to 90 days', function () {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $result = $this->service->getRevenueChart('QTD', $this->user, 'USD');

        expect($result['effectiveWindow']['wasExpanded'])->toBeTrue();
        expect($result['effectiveWindow']['daysInRange'])->toBe(90);
        expect($result['effectiveWindow']['daysInRange'])->toBeInt();
        expect($result['effectiveWindow']['expansionReason'])->toContain('Quarter has only');
    });

    test('QTD on day 14 (threshold) expands to 90 days', function () {
        Carbon::setTestNow(Carbon::parse('2026-01-14 00:00:00'));

        $result = $this->service->getRevenueChart('QTD', $this->user, 'USD');

        expect($result['effectiveWindow']['wasExpanded'])->toBeTrue();
        expect($result['effectiveWindow']['daysInRange'])->toBe(90);
        expect($result['effectiveWindow']['expansionReason'])->toContain('Quarter has only 14 days');
    });

    test('QTD on day 15 does NOT expand', function () {
        Carbon::setTestNow(Carbon::parse('2026-01-15 12:00:00'));

        $result = $this->service->getRevenueChart('QTD', $this->user, 'USD');

        expect($result['effectiveWindow']['wasExpanded'])->toBeFalse();
        expect($result['effectiveWindow']['daysInRange'])->toBe(15);
        expect($result['effectiveWindow']['expansionReason'])->toBeNull();
    });

    test('YTD on day 1 expands to 365 days', function () {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $result = $this->service->getRevenueChart('YTD', $this->user, 'USD');

        expect($result['effectiveWindow']['wasExpanded'])->toBeTrue();
        expect($result['effectiveWindow']['daysInRange'])->toBe(365);
        expect($result['effectiveWindow']['daysInRange'])->toBeInt();
        expect($result['effectiveWindow']['expansionReason'])->toContain('Year has only');
    });

    test('YTD on day 30 (threshold) expands to 365 days', function () {
        Carbon::setTestNow(Carbon::parse('2026-01-30 00:00:00'));

        $result = $this->service->getRevenueChart('YTD', $this->user, 'USD');

        expect($result['effectiveWindow']['wasExpanded'])->toBeTrue();
        expect($result['effectiveWindow']['daysInRange'])->toBe(365);
        expect($result['effectiveWindow']['expansionReason'])->toContain('Year has only 30 days');
    });

    test('YTD on day 31 does NOT expand', function () {
        Carbon::setTestNow(Carbon::parse('2026-01-31 12:00:00'));

        $result = $this->service->getRevenueChart('YTD', $this->user, 'USD');

        expect($result['effectiveWindow']['wasExpanded'])->toBeFalse();
        expect($result['effectiveWindow']['daysInRange'])->toBe(31);
        expect($result['effectiveWindow']['expansionReason'])->toBeNull();
    });

    test('1W period never expands', function () {
        Carbon::setTestNow(Carbon::parse('2026-02-01 12:00:00'));

        $result = $this->service->getRevenueChart('1W', $this->user, 'USD');

        expect($result['effectiveWindow']['wasExpanded'])->toBeFalse();
        expect($result['effectiveWindow']['daysInRange'])->toBe(7);
        expect($result['effectiveWindow']['expansionReason'])->toBeNull();
    });

    test('daysInRange is always an integer even with fractional diffInDays', function () {
        // Test at different times of day to ensure ceil() works correctly
        Carbon::setTestNow(Carbon::parse('2026-02-01 23:59:59'));

        $result = $this->service->getRevenueChart('MTD', $this->user, 'USD');

        expect($result['effectiveWindow']['daysInRange'])->toBeInt();
        expect($result['effectiveWindow']['daysInRange'])->toBe(30);
    });
});

describe('Chart Gap Filling', function () {
    test('fills all gaps for weekly grouping in QTD window', function () {
        Carbon::setTestNow(Carbon::parse('2026-01-20 12:00:00'));

        // Create invoices on specific dates
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'total' => ['amount_cents' => 10000, 'currency' => 'USD'],
            'status' => 'paid',
            'paid_at' => '2026-01-06', // Tuesday, buckets to 2026-01-05 (Monday)
        ]);

        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'total' => ['amount_cents' => 20000, 'currency' => 'USD'],
            'status' => 'paid',
            'paid_at' => '2026-01-13', // Tuesday, buckets to 2026-01-12 (Monday)
        ]);

        // QTD uses weekly grouping
        $result = $this->service->getRevenueChart('QTD', $this->user, 'USD');
        $chart = $result['chart'];

        // Should have entries for all weeks from start of quarter to now
        // Jan 1-20 = 3-4 weeks
        expect(count($chart))->toBeGreaterThanOrEqual(2);

        // Check that all chart points have the required structure
        foreach ($chart as $point) {
            expect($point)->toHaveKeys(['timestamp', 'value']);
            expect($point['timestamp'])->toBeInt();
            expect($point['value'])->toBeInt();
        }

        // Check that weeks with data have correct values (labels are timestamps)
        $week1Timestamp = Carbon::parse('2026-01-05')->startOfDay()->timestamp * 1000;
        $week2Timestamp = Carbon::parse('2026-01-12')->startOfDay()->timestamp * 1000;

        $weekWithInvoice1 = collect($chart)->firstWhere('timestamp', $week1Timestamp);
        $weekWithInvoice2 = collect($chart)->firstWhere('timestamp', $week2Timestamp);

        expect($weekWithInvoice1)->not->toBeNull();
        expect($weekWithInvoice1['value'])->toBe(10000);

        expect($weekWithInvoice2)->not->toBeNull();
        expect($weekWithInvoice2['value'])->toBe(20000);
    });

    test('fills all gaps for daily grouping', function () {
        Carbon::setTestNow(Carbon::parse('2026-02-08 12:00:00'));

        // MTD on day 8 should not expand, so we get 8 daily points
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'total' => ['amount_cents' => 10000, 'currency' => 'USD'],
            'status' => 'paid',
            'paid_at' => '2026-02-01',
        ]);

        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'total' => ['amount_cents' => 20000, 'currency' => 'USD'],
            'status' => 'paid',
            'paid_at' => '2026-02-05',
        ]);

        $result = $this->service->getRevenueChart('MTD', $this->user, 'USD');
        $chart = $result['chart'];

        // Should have exactly 8 days (Feb 1-8)
        expect(count($chart))->toBe(8);

        // Check first and last dates are timestamps
        expect($chart[0]['timestamp'])->toBeInt();
        expect($chart[7]['timestamp'])->toBeInt();

        // Verify the timestamps correspond to the correct dates
        $firstDate = Carbon::createFromTimestamp($chart[0]['timestamp'] / 1000);
        $lastDate = Carbon::createFromTimestamp($chart[7]['timestamp'] / 1000);
        expect($firstDate->format('Y-m-d'))->toBe('2026-02-01');
        expect($lastDate->format('Y-m-d'))->toBe('2026-02-08');

        // Days without invoices should have 0
        expect($chart[1]['value'])->toBe(0); // Feb 2
        expect($chart[2]['value'])->toBe(0); // Feb 3
    });

    test('weekly chart points are aligned to week starts', function () {
        Carbon::setTestNow(Carbon::parse('2026-01-10 12:00:00'));

        // QTD uses weekly grouping
        $result = $this->service->getRevenueChart('QTD', $this->user, 'USD');
        $chart = $result['chart'];

        // Each label should be a timestamp representing a Monday (week start)
        foreach ($chart as $point) {
            expect($point['timestamp'])->toBeInt(); // Should be a timestamp
            $date = Carbon::createFromTimestamp($point['timestamp'] / 1000);
            expect($date->dayOfWeek)->toBe(Carbon::MONDAY);
        }
    });

    test('no duplicate chart points after gap filling', function () {
        Carbon::setTestNow(Carbon::parse('2026-02-01 12:00:00'));

        $result = $this->service->getRevenueChart('MTD', $this->user, 'USD');
        $chart = $result['chart'];

        $timestamps = collect($chart)->pluck('timestamp')->toArray();
        $uniqueTimestamps = array_unique($timestamps);

        expect(count($timestamps))->toBe(count($uniqueTimestamps));
    });
});

describe('Chart Response Structure', function () {
    test('returns all required fields', function () {
        Carbon::setTestNow(Carbon::parse('2026-02-01 12:00:00'));

        $result = $this->service->getRevenueChart('MTD', $this->user, 'USD');

        expect($result)->toHaveKeys([
            'baseCurrency',
            'range',
            'effectiveWindow',
            'chart',
        ]);

        expect($result['range'])->toHaveKeys(['__typename', 'value']);
        expect($result['range']['__typename'])->toBe('CompactRangeValue');
        expect($result['range']['value'])->toBe('MTD');

        expect($result['effectiveWindow'])->toHaveKeys([
            'start',
            'end',
            'daysInRange',
            'wasExpanded',
            'expansionReason',
        ]);
    });

    test('timestamps are in milliseconds', function () {
        Carbon::setTestNow(Carbon::parse('2026-02-01 12:00:00'));

        $result = $this->service->getRevenueChart('MTD', $this->user, 'USD');

        // Timestamps should be in milliseconds (13+ digits)
        expect(strlen((string) $result['effectiveWindow']['start']))->toBeGreaterThanOrEqual(13);
        expect(strlen((string) $result['effectiveWindow']['end']))->toBeGreaterThanOrEqual(13);
    });
});
