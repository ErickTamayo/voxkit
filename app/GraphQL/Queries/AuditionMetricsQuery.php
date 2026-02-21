<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Services\AuditionService;
use Illuminate\Support\Facades\Auth;

class AuditionMetricsQuery
{
    public function __construct(
        protected AuditionService $auditionService
    ) {}

    public function __invoke($_, array $args): array
    {
        return $this->auditionService->getAuditionMetrics(
            $args['period'],
            Auth::user()
        );
    }
}
