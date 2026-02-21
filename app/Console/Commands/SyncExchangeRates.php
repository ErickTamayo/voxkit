<?php

namespace App\Console\Commands;

use App\Services\ExchangeRateService;
use Illuminate\Console\Command;

class SyncExchangeRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-exchange-rates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync daily exchange rates from OpenExchangeRates';

    /**
     * Execute the console command.
     */
    public function handle(ExchangeRateService $service)
    {
        $this->info('Starting exchange rate sync...');

        try {
            $service->syncRates();
            $this->info('Exchange rates synced successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to sync exchange rates: '.$e->getMessage());

            return 1;
        }
    }
}
