<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ExchangeRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $service = new \App\Services\ExchangeRateService;
        $today = Carbon::today();

        $service->syncRates(true, [
            $today,
            $today->copy()->subDay(),
        ]);
    }
}
