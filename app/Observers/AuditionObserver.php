<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Audition;
use App\Services\ActivityService;
use Illuminate\Support\Facades\Cache;

class AuditionObserver
{
    public function __construct(
        private readonly ActivityService $activityService
    ) {}

    public function saved(Audition $audition): void
    {
        $this->clearCache();
        $this->activityService->syncAudition($audition);
    }

    public function deleted(Audition $audition): void
    {
        $this->clearCache();
        $this->activityService->syncAudition($audition);
    }

    public function restored(Audition $audition): void
    {
        $this->clearCache();
        $this->activityService->syncAudition($audition);
    }

    protected function clearCache(): void
    {
        Cache::tags(['audition_metrics', 'audition_chart'])->flush();
    }
}
