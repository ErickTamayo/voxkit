<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

class UserData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public ?int $email_verified_at,
        public ?int $created_at,
        public ?int $updated_at,
    ) {}
}
