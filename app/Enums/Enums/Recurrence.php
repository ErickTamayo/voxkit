<?php

declare(strict_types=1);

namespace App\Enums\Enums;

enum Recurrence: string
{
    case ONE_OFF = 'one_off';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';
}
