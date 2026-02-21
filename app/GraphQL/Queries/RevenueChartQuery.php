<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Services\RevenueService;
use Illuminate\Support\Facades\Auth;

class RevenueChartQuery
{
    public function __construct(
        protected RevenueService $revenueService
    ) {}

    public function __invoke($_, array $args): array
    {
        return $this->revenueService->getRevenueChart(
            $args['period'],
            Auth::user(),
            $args['baseCurrency'] ?? null
        );
    }
}
