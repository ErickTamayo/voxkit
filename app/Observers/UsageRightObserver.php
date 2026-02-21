<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\UsageRight;
use App\Services\ActivityService;

class UsageRightObserver
{
    public function __construct(
        private readonly ActivityService $activityService
    ) {}

    public function saved(UsageRight $usageRight): void
    {
        $this->activityService->syncUsageRight($usageRight);
    }

    public function deleted(UsageRight $usageRight): void
    {
        $this->activityService->syncUsageRight($usageRight);
    }

    public function restored(UsageRight $usageRight): void
    {
        $this->activityService->syncUsageRight($usageRight);
    }
}
