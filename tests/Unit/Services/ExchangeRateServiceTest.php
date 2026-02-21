<?php

namespace Tests\Unit\Services;

use App\Models\ExchangeRate;
use App\Services\ExchangeRateService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExchangeRateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ExchangeRateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExchangeRateService;
    }

    public function test_it_returns_original_amount_if_currencies_match()
    {
        $amount = 1000;
        $result = $this->service->convert($amount, 'USD', 'USD');
        $this->assertEquals(1000, $result);
    }

    public function test_it_converts_usd_to_foreign_currency()
    {
        ExchangeRate::factory()->create([
            'currency_code' => 'CAD',
            'rate' => 1.35,
            'base_currency' => 'USD',
            'effective_date' => Carbon::today(),
        ]);

        $amount = 1000;
        $result = $this->service->convert($amount, 'USD', 'CAD');
        $this->assertEquals(1350, $result);
    }

    public function test_it_converts_foreign_currency_to_usd()
    {
        ExchangeRate::factory()->create([
            'currency_code' => 'CAD',
            'rate' => 1.35,
            'base_currency' => 'USD',
            'effective_date' => Carbon::today(),
        ]);

        $amount = 1350;
        $result = $this->service->convert($amount, 'CAD', 'USD');
        $this->assertEquals(1000, $result);
    }

    public function test_it_converts_between_two_foreign_currencies()
    {
        ExchangeRate::factory()->create([
            'currency_code' => 'CAD',
            'rate' => 1.35,
            'base_currency' => 'USD',
            'effective_date' => Carbon::today(),
        ]);

        ExchangeRate::factory()->create([
            'currency_code' => 'EUR',
            'rate' => 0.90,
            'base_currency' => 'USD',
            'effective_date' => Carbon::today(),
        ]);

        $amount = 1350; // 1350 CAD = 1000 USD = 900 EUR
        $result = $this->service->convert($amount, 'CAD', 'EUR');
        $this->assertEquals(900, $result);
    }

    public function test_it_finds_nearest_rate_by_date()
    {
        ExchangeRate::factory()->create([
            'currency_code' => 'CAD',
            'rate' => 1.30,
            'effective_date' => Carbon::yesterday(),
        ]);

        ExchangeRate::factory()->create([
            'currency_code' => 'CAD',
            'rate' => 1.40,
            'effective_date' => Carbon::tomorrow(),
        ]);

        $result = $this->service->convert(1000, 'USD', 'CAD', Carbon::today());
        $this->assertEquals(1300, $result);
    }
}
