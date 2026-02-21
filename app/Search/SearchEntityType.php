<?php

declare(strict_types=1);

namespace App\Search;

enum SearchEntityType: string
{
    case CONTACT = 'CONTACT';
    case CLIENT = 'CLIENT';
    case AGENT = 'AGENT';
    case JOB = 'JOB';
    case INVOICE = 'INVOICE';
    case AUDITION = 'AUDITION';
    case EXPENSE = 'EXPENSE';
    case PLATFORM = 'PLATFORM';
    case NOTE = 'NOTE';
}
