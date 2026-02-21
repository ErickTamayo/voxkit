<?php

declare(strict_types=1);

use App\Models\ExchangeRate;
use App\Services\ExchangeRateService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('it helps determine start date when no cache exists', function () {
    $service = new ExchangeRateService;

    // We access the protected method via reflection for testing if needed,
    // or we implicitly test it via syncRates behavior.
    // For this test, let's test the public syncRates method with mocks.

    // Mock today as 2026-01-30
    Carbon::setTestNow('2026-01-30');

    // Expect calls for 11 days (10 days ago + today)
    // 2026-01-20 to 2026-01-30

    Http::fake([
        'openexchangerates.org/*' => Http::response([
            'base' => 'USD',
            'rates' => ['EUR' => 0.92, 'GBP' => 0.79],
        ], 200),
    ]);

    $service->syncRates();

    // Verify 11 files are created
    $files = Storage::files('exchange-rates');
    expect(count($files))->toBe(11);

    // Verify DB records
    expect(ExchangeRate::count())->toBe(22); // 11 days * 2 currencies

    // Verify specific file exists
    expect(Storage::exists('exchange-rates/2026-01-30.json'))->toBeTrue();
});

test('it resumes from last cached date', function () {
    Carbon::setTestNow('2026-01-30');

    // Simulate existing cache for yesterday
    Storage::makeDirectory('exchange-rates');
    Storage::put('exchange-rates/2026-01-29.json', json_encode(['rates' => ['EUR' => 0.88]]));

    Http::fake([
        'openexchangerates.org/*' => Http::response([
            'base' => 'USD',
            'rates' => ['EUR' => 0.90],
        ], 200),
        // Should NOT call historical/2026-01-29.json or older
    ]);

    $service = new ExchangeRateService;
    $service->syncRates();

    // Verify new file created for today
    expect(Storage::exists('exchange-rates/2026-01-30.json'))->toBeTrue();

    // Verify HTTP call count - should only be 1 for today
    Http::assertSentCount(1);
});

test('it handles failed API requests gracefully', function () {
    Carbon::setTestNow('2026-01-30');

    Http::fake([
        'openexchangerates.org/*' => Http::response([], 500),
    ]);

    $service = new ExchangeRateService;
    $service->syncRates();

    // No files should be created for failed requests
    expect(Storage::files('exchange-rates'))->toBeEmpty();
    expect(ExchangeRate::count())->toBe(0);
});
