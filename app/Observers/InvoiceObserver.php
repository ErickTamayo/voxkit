<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Invoice;
use App\Services\ActivityService;
use Illuminate\Support\Facades\Cache;

class InvoiceObserver
{
    public function __construct(
        private readonly ActivityService $activityService
    ) {}

    /**
     * Handle the Invoice "saved" event (created or updated).
     */
    public function saved(Invoice $invoice): void
    {
        $this->clearRevenueCache($invoice->user_id);
        $this->activityService->syncInvoice($invoice);
    }

    /**
     * Handle the Invoice "deleted" event.
     */
    public function deleted(Invoice $invoice): void
    {
        $this->clearRevenueCache($invoice->user_id);
        $this->activityService->syncInvoice($invoice);
    }

    /**
     * Handle the Invoice "restored" event.
     */
    public function restored(Invoice $invoice): void
    {
        $this->clearRevenueCache($invoice->user_id);
        $this->activityService->syncInvoice($invoice);
    }

    /**
     * Clear the revenue cache for the user (all namespaces).
     */
    protected function clearRevenueCache(string $userId): void
    {
        // Clear ALL revenue cache namespaces
        try {
            Cache::tags(['revenue_stats'])->flush();
            Cache::tags(['revenue_metrics'])->flush();
            Cache::tags(['revenue_chart'])->flush();
        } catch (\BadMethodCallException $e) {
            // Fallback for drivers without tag support (like file/database)
            // Individual keys will expire naturally (5 min TTL)
        }
    }
}
