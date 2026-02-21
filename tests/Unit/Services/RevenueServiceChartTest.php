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
    $this->baseCurrency = 'USD';

    // Set a fixed 'Now' to make tests deterministic
    // let's say 'Now' is Wednesday, June 15th, 2024
    Carbon::setTestNow(Carbon::parse('2024-06-15 12:00:00'));

    // Setup generic exchange rate
    ExchangeRate::factory()->create(['currency_code' => 'USD', 'rate' => 1.0, 'effective_date' => '2024-01-01']);
});

test('1W chart returns 7 daily points including today', function () {
    // 1W = Last 6 days + Today = 7 days.
    // Period: June 9 to June 15

    $result = $this->service->getRevenueChart('1W', $this->user, $this->baseCurrency);
    $chart = $result['chart'];

    expect($chart)->toHaveCount(7);
    expect($chart[0]['timestamp'])->toBe(Carbon::parse('2024-06-09')->startOfDay()->timestamp * 1000);
    expect($chart[6]['timestamp'])->toBe(Carbon::parse('2024-06-15')->startOfDay()->timestamp * 1000);
});

test('MTD chart returns daily points from start of month', function () {
    // MTD = June 1 to June 15 = 15 days

    $result = $this->service->getRevenueChart('MTD', $this->user, $this->baseCurrency);
    $chart = $result['chart'];

    expect($chart)->toHaveCount(15);
    expect($chart[0]['timestamp'])->toBe(Carbon::parse('2024-06-01')->startOfDay()->timestamp * 1000);
    expect($chart[14]['timestamp'])->toBe(Carbon::parse('2024-06-15')->startOfDay()->timestamp * 1000);
});

test('QTD chart returns weekly points', function () {
    // QTD = Start of Quarter (April 1st) to June 15th.

    // April 1 2024 is a Monday.
    // If we group by week, we expect timestamps to be Mondays (start of week).

    $result = $this->service->getRevenueChart('QTD', $this->user, $this->baseCurrency);
    $chart = $result['chart'];

    // Just verify structure for now, the count depends on exact weeks
    expect($chart[0]['timestamp'])->toBe(Carbon::parse('2024-04-01')->startOfDay()->timestamp * 1000); // Monday
    // Ensure interval is roughly right (~11 weeks)
    expect(count($chart))->toBeGreaterThan(10);
});

test('YTD chart returns monthly points', function () {
    // YTD = Jan 1 to June 15.
    // Group by Month -> Jan, Feb, Mar, Apr, May, June = 6 points.

    $result = $this->service->getRevenueChart('YTD', $this->user, $this->baseCurrency);
    $chart = $result['chart'];

    expect($chart)->toHaveCount(6);
    expect($chart[0]['timestamp'])->toBe(Carbon::parse('2024-01-01')->startOfDay()->timestamp * 1000); // Normalizes to start of month
    expect($chart[5]['timestamp'])->toBe(Carbon::parse('2024-06-01')->startOfDay()->timestamp * 1000);
});

test('Chart correctly buckets revenue on specific days', function () {
    // Create invoices on specific days
    Invoice::factory()->create([
        'user_id' => $this->user->id,
        'total' => ['amount_cents' => 10000, 'currency' => 'USD'],
        'status' => 'paid',
        'paid_at' => '2024-06-10 10:00:00', // Monday
    ]);

    Invoice::factory()->create([
        'user_id' => $this->user->id,
        'total' => ['amount_cents' => 20000, 'currency' => 'USD'],
        'status' => 'paid',
        'paid_at' => '2024-06-10 15:00:00', // Monday (Same day)
    ]);

    Invoice::factory()->create([
        'user_id' => $this->user->id,
        'total' => ['amount_cents' => 5000, 'currency' => 'USD'],
        'status' => 'paid',
        'paid_at' => '2024-06-12 10:00:00', // Wednesday
    ]);

    $result = $this->service->getRevenueChart('1W', $this->user, $this->baseCurrency);
    $chart = collect($result['chart']);

    // June 10 should have 30000
    $june10Timestamp = Carbon::parse('2024-06-10')->startOfDay()->timestamp * 1000;
    $june10 = $chart->firstWhere('timestamp', $june10Timestamp);
    expect($june10['value'])->toBe(30000);

    // June 12 should have 5000
    $june12Timestamp = Carbon::parse('2024-06-12')->startOfDay()->timestamp * 1000;
    $june12 = $chart->firstWhere('timestamp', $june12Timestamp);
    expect($june12['value'])->toBe(5000);

    // June 11 (Gap) should have 0
    $june11Timestamp = Carbon::parse('2024-06-11')->startOfDay()->timestamp * 1000;
    $june11 = $chart->firstWhere('timestamp', $june11Timestamp);
    expect($june11['value'])->toBe(0);
});

test('Chart correctly buckets revenue into Weeks for QTD', function () {
    // QTD Start: April 1 (Monday)

    // Invoice on April 3rd (Wednesday) -> Should fall into Week of April 1st
    Invoice::factory()->create([
        'user_id' => $this->user->id,
        'total' => ['amount_cents' => 10000, 'currency' => 'USD'],
        'status' => 'paid',
        'paid_at' => '2024-04-03',
    ]);

    // Invoice on April 10th (Wednesday) -> Should fall into Week of April 8th
    Invoice::factory()->create([
        'user_id' => $this->user->id,
        'total' => ['amount_cents' => 20000, 'currency' => 'USD'],
        'status' => 'paid',
        'paid_at' => '2024-04-10',
    ]);

    $result = $this->service->getRevenueChart('QTD', $this->user, $this->baseCurrency);
    $chart = collect($result['chart']);

    $week1Timestamp = Carbon::parse('2024-04-01')->startOfDay()->timestamp * 1000;
    $week1 = $chart->firstWhere('timestamp', $week1Timestamp);
    expect($week1['value'])->toBe(10000);
});

test('QTD chart handles Quarter starting on Tuesday (Q4 2024)', function () {
    // Q4 2024 starts Oct 1 (Tuesday).
    // "Now" is Nov 15.
    Carbon::setTestNow(Carbon::parse('2024-11-15 12:00:00'));

    // Invoice on Oct 1 (Tuesday)
    Invoice::factory()->create([
        'user_id' => $this->user->id,
        'total' => ['amount_cents' => 10000, 'currency' => 'USD'],
        'status' => 'paid',
        'paid_at' => '2024-10-01 10:00:00',
    ]);

    $result = $this->service->getRevenueChart('QTD', $this->user, $this->baseCurrency);
    $chart = collect($result['chart']);

    // Oct 1 is Tuesday. Start of that week is Sep 30 (Monday).
    // We expect the bucket timestamp to be Sep 30.

    $pointTimestamp = Carbon::parse('2024-09-30')->startOfDay()->timestamp * 1000;
    $point = $chart->firstWhere('timestamp', $pointTimestamp);
    expect($point)->not->toBeNull();
    expect($point['value'])->toBe(10000);
});

test('Chart gap filling provides continuous timeline', function () {
    // 1W Chart.
    // "Now" is June 15.
    // Range: June 9 - June 15.
    // Invoice on June 9 and June 15.
    // Middle days (10,11,12,13,14) should exist with 0 value.

    Carbon::setTestNow(Carbon::parse('2024-06-15 12:00:00'));

    Invoice::factory()->create(['user_id' => $this->user->id, 'total' => ['amount_cents' => 100, 'currency' => 'USD'], 'status' => 'paid', 'paid_at' => '2024-06-09']);
    Invoice::factory()->create(['user_id' => $this->user->id, 'total' => ['amount_cents' => 100, 'currency' => 'USD'], 'status' => 'paid', 'paid_at' => '2024-06-15']);

    $result = $this->service->getRevenueChart('1W', $this->user, $this->baseCurrency);
    $chart = collect($result['chart']);

    expect($chart)->toHaveCount(7);

    $dates = ['2024-06-09', '2024-06-10', '2024-06-11', '2024-06-12', '2024-06-13', '2024-06-14', '2024-06-15'];

    foreach ($dates as $date) {
        $timestamp = Carbon::parse($date)->startOfDay()->timestamp * 1000;
        $point = $chart->firstWhere('timestamp', $timestamp);
        expect($point)->not->toBeNull();
        if ($date === '2024-06-09' || $date === '2024-06-15') {
            expect($point['value'])->toBe(100);
        } else {
            expect($point['value'])->toBe(0);
        }
    }
});
