<?php

declare(strict_types=1);

namespace App\Enums\Enums;

enum JobStatus: string
{
    case BOOKED = 'booked';
    case IN_PROGRESS = 'in_progress';
    case DELIVERED = 'delivered';
    case REVISION = 'revision';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
