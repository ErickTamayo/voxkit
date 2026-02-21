<?php

declare(strict_types=1);

use App\Models\ExchangeRate;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Settings;
use App\Models\User;
use App\Services\RevenueService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->service = app(RevenueService::class);
    $this->user = User::factory()->create();
    Carbon::setTestNow('2024-01-15 12:00:00');
});

test('it calculates current revenue correctly', function () {
    // Setup rate: USD to CAD = 1.35
    ExchangeRate::factory()->create([
        'currency_code' => 'CAD',
        'rate' => 1.35,
        'effective_date' => '2024-01-10',
    ]);

    // Setup rate: USD to USD = 1.0
    ExchangeRate::factory()->create([
        'currency_code' => 'USD',
        'rate' => 1.0,
        'effective_date' => '2024-01-10',
    ]);

    // Invoice 1: 100 USD paid on Jan 10
    Invoice::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'paid',
        'total' => ['amount_cents' => 10000, 'currency' => 'USD'], // $100.00
        'paid_at' => '2024-01-10',
    ]);

    // Invoice 2: 100 CAD paid on Jan 10
    Invoice::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'paid',
        'total' => ['amount_cents' => 10000, 'currency' => 'CAD'], // $100.00 CAD
        'paid_at' => '2024-01-10',
    ]);

    // Get metrics in CAD
    $metrics = $this->service->getRevenueMetrics('MTD', $this->user, 'CAD');

    // Expected:
    // Invoice 1 (USD -> CAD): 10000 * (1.35 / 1.0) = 13500 cents
    // Invoice 2 (CAD -> CAD): 10000 * (1.35 / 1.35) = 10000 cents
    // Total: 23500 cents
    expect($metrics['metrics']['current']['total']['amount_cents'])->toBe(23500);
});

test('it calculates pipeline revenue for job types', function () {
    // 1. Flat Rate: $100
    Job::factory()->create([
        'user_id' => $this->user->id,
        'contracted_rate' => ['amount_cents' => 10000, 'currency' => 'USD'],
        'rate_type' => 'flat',
        'status' => 'in_progress',
    ]);

    // 2. Per Word: 500 words @ $0.10
    Job::factory()->create([
        'user_id' => $this->user->id,
        'contracted_rate' => ['amount_cents' => 10, 'currency' => 'USD'], // 10 cents per word
        'rate_type' => 'per_word',
        'word_count' => 500,
        'status' => 'in_progress',
    ]);

    // 3. Hourly: 2 hours @ $150.00
    Job::factory()->create([
        'user_id' => $this->user->id,
        'contracted_rate' => ['amount_cents' => 15000, 'currency' => 'USD'],
        'rate_type' => 'hourly',
        'estimated_hours' => 2.0,
        'status' => 'in_progress',
    ]);

    // 4. Per Finished Hour: $200 per PFH. 4500 words (~0.5 PFH)
    Job::factory()->create([
        'user_id' => $this->user->id,
        'contracted_rate' => ['amount_cents' => 20000, 'currency' => 'USD'],
        'rate_type' => 'per_finished_hour',
        'word_count' => 4500,
        'status' => 'in_progress',
    ]);

    // Total Expected in USD:
    // 1. 10000
    // 2. 5000
    // 3. 30000
    // 4. 10000
    // Total: 55000 cents ($550.00)

    ExchangeRate::factory()->create(['currency_code' => 'USD', 'rate' => 1.0, 'effective_date' => now()->format('Y-m-d')]);

    $metrics = $this->service->getRevenueMetrics('MTD', $this->user, 'USD');

    expect($metrics['metrics']['pipeline']['total']['amount_cents'])->toBe(55000);
});

test('it handles cross currency math correctly', function () {
    // Base: CAD (1.35)
    // Job: EUR (0.92)
    // Bridge: USD (1.0)

    ExchangeRate::factory()->create(['currency_code' => 'CAD', 'rate' => 1.35, 'effective_date' => '2024-01-10']);
    ExchangeRate::factory()->create(['currency_code' => 'EUR', 'rate' => 0.92, 'effective_date' => '2024-01-10']);

    // Job: 100 EUR
    Job::factory()->create([
        'user_id' => $this->user->id,
        'contracted_rate' => ['amount_cents' => 10000, 'currency' => 'EUR'],
        'rate_type' => 'flat',
        'status' => 'in_progress',
    ]);

    // Calculation: 10000 * (1.35 / 0.92) = 14673.91 -> 14674

    $metrics = $this->service->getRevenueMetrics('MTD', $this->user, 'CAD');

    // Allow variance of 1 cent due to rounding
    expect($metrics['metrics']['pipeline']['total']['amount_cents'])->toBeBetween(14673, 14675);
});

test('it calculates in-flight revenue from unpaid invoices and unbilled active jobs', function () {
    ExchangeRate::factory()->create(['currency_code' => 'USD', 'rate' => 1.0, 'effective_date' => '2024-01-10']);

    Job::factory()->create([
        'user_id' => $this->user->id,
        'contracted_rate' => ['amount_cents' => 15000, 'currency' => 'USD'],
        'rate_type' => 'flat',
        'status' => 'in_progress',
    ]);

    $jobWithInvoice = Job::factory()->create([
        'user_id' => $this->user->id,
        'contracted_rate' => ['amount_cents' => 30000, 'currency' => 'USD'],
        'rate_type' => 'flat',
        'status' => 'in_progress',
    ]);

    Invoice::factory()->create([
        'user_id' => $this->user->id,
        'job_id' => $jobWithInvoice->id,
        'status' => 'sent',
        'issued_at' => '2024-01-10',
        'total' => ['amount_cents' => 30000, 'currency' => 'USD'],
    ]);

    $metrics = $this->service->getRevenueMetrics('MTD', $this->user, 'USD');

    expect($metrics['metrics']['in_flight']['total']['amount_cents'])->toBe(45000);
});

test('it calculates trend correctly', function () {
    ExchangeRate::factory()->create(['currency_code' => 'USD', 'rate' => 1.0, 'effective_date' => '2024-01-10']);
    ExchangeRate::factory()->create(['currency_code' => 'USD', 'rate' => 1.0, 'effective_date' => '2023-12-10']);

    // Current Month (Jan 2024): $200
    Invoice::factory()->create([
        'user_id' => $this->user->id,
        'total' => ['amount_cents' => 20000, 'currency' => 'USD'],
        'status' => 'paid',
        'paid_at' => '2024-01-10',
    ]);

    // Previous Month (Dec 2023): $100
    Invoice::factory()->create([
        'user_id' => $this->user->id,
        'total' => ['amount_cents' => 10000, 'currency' => 'USD'],
        'status' => 'paid',
        'paid_at' => '2023-12-10',
    ]);

    $metrics = $this->service->getRevenueMetrics('MTD', $this->user, 'USD');

    // (200 - 100) / 100 = 100% increase
    expect($metrics['metrics']['current']['trend_percentage'])->toBe(100.0);
});

test('it caches results and invalidates on update', function () {
    Cache::flush();

    // 1. Initial Call
    $this->service->getRevenueMetrics('MTD', $this->user, 'USD');

    $key = 'revenue_metrics_'.$this->user->id.'_MTD_USD';

    // Verify tags working
    Cache::tags(['revenue_metrics'])->put($key, ['fake' => 'data'], 300);
    expect(Cache::tags(['revenue_metrics'])->has($key))->toBeTrue();

    // Trigger Observer via Model Event
    Invoice::factory()->create(['user_id' => $this->user->id]);

    // Assert Cache Cleared
    expect(Cache::tags(['revenue_metrics'])->has($key))->toBeFalse();
});

test('it falls back gracefully missing exchange rate', function () {
    Job::factory()->create([
        'user_id' => $this->user->id,
        'contracted_rate' => ['amount_cents' => 10000, 'currency' => 'EUR'], // Different from base
        'rate_type' => 'flat',
        'status' => 'in_progress',
    ]);

    $metrics = $this->service->getRevenueMetrics('MTD', $this->user, 'USD');

    expect($metrics['metrics']['pipeline']['total']['amount_cents'])->toBe(10000);
});

test('it handles missing specific currency on a date where other rates exist', function () {
    // Jan 10: USD exists, CAD exists. EUR missing.
    ExchangeRate::factory()->create(['currency_code' => 'USD', 'rate' => 1.0, 'effective_date' => '2024-01-10']);
    ExchangeRate::factory()->create(['currency_code' => 'CAD', 'rate' => 1.35, 'effective_date' => '2024-01-10']);

    // Jan 09: EUR exists (0.92)
    ExchangeRate::factory()->create(['currency_code' => 'EUR', 'rate' => 0.92, 'effective_date' => '2024-01-09']);

    // Invoice: 100 EUR paid on Jan 10
    Invoice::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'paid',
        'total' => ['amount_cents' => 10000, 'currency' => 'EUR'],
        'paid_at' => '2024-01-10',
    ]);

    // Base: CAD
    // Logic: Look up Jan 10 for EUR -> Missing.
    // Fallback: Look up Jan 09 for EUR -> Found (0.92).
    // Calc: 10000 * (1.35 / 0.92) = 14673.91 -> 14674
    // If Logic Fail (defaults to 1.0): 10000 * (1.35 / 1.0) = 13500.

    $metrics = $this->service->getRevenueMetrics('MTD', $this->user, 'CAD');

    // Allow small variance
    expect($metrics['metrics']['current']['total']['amount_cents'])->toBeBetween(14670, 14680);
    expect($metrics['metrics']['current']['precision'])->toBe('ESTIMATED');
});

test('it flags revenue as accurate when exact keys exist', function () {
    ExchangeRate::factory()->create(['currency_code' => 'CAD', 'rate' => 1.35, 'effective_date' => '2024-01-10']);
    ExchangeRate::factory()->create(['currency_code' => 'USD', 'rate' => 1.0, 'effective_date' => '2024-01-10']);

    Invoice::factory()->create([
        'user_id' => $this->user->id,
        'total' => ['amount_cents' => 10000, 'currency' => 'CAD'],
        'paid_at' => '2024-01-10',
        'status' => 'paid',
    ]);

    $metrics = $this->service->getRevenueMetrics('MTD', $this->user, 'USD');
    expect($metrics['metrics']['current']['precision'])->toBe('EXACT');
});

test('it defaults to "USD" when baseCurrency is null and no user settings exist', function () {
    $metrics = $this->service->getRevenueMetrics('MTD', $this->user, null);

    expect($metrics['baseCurrency'])->toBe('USD');
});

test('it uses user settings currency when baseCurrency is null', function () {
    // Update the existing settings (created by UserObserver)
    $this->user->settings->update(['currency' => 'CAD']);
    $this->user->refresh();

    $metrics = $this->service->getRevenueMetrics('MTD', $this->user, null);

    expect($metrics['baseCurrency'])->toBe('CAD');
});
