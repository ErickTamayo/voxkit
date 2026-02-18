<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class InvalidAuthenticationCodeException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Invalid or expired code.');
    }
}
