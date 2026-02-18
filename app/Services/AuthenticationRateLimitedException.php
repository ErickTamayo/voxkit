<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class AuthenticationRateLimitedException extends RuntimeException
{
    public function __construct(
        public readonly int $retryAfterSeconds
    ) {
        parent::__construct('Too many attempts.');
    }
}
