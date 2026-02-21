<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Job;
use App\Services\ActivityService;
use Illuminate\Support\Facades\Cache;

class JobObserver
{
    public function __construct(
        private readonly ActivityService $activityService
    ) {}

    /**
     * Handle the Job "saved" event.
     */
    public function saved(Job $job): void
    {
        $this->clearRevenueCache($job->user_id);
        $this->activityService->syncJob($job);
    }

    /**
     * Handle the Job "deleted" event.
     */
    public function deleted(Job $job): void
    {
        $this->clearRevenueCache($job->user_id);
        $this->activityService->syncJob($job);
    }

    /**
     * Handle the Job "restored" event.
     */
    public function restored(Job $job): void
    {
        $this->clearRevenueCache($job->user_id);
        $this->activityService->syncJob($job);
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
