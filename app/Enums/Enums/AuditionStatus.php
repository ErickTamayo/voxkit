<?php

declare(strict_types=1);

namespace App\Enums\Enums;

enum AuditionStatus: string
{
    case RECEIVED = 'received';
    case PREPARING = 'preparing';
    case SUBMITTED = 'submitted';
    case SHORTLISTED = 'shortlisted';
    case CALLBACK = 'callback';
    case WON = 'won';
    case LOST = 'lost';
    case EXPIRED = 'expired';
}
