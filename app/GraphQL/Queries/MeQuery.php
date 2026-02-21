<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class MeQuery
{
    /**
     * Get the authenticated user.
     */
    public function __invoke(): User
    {
        /** @var User */
        return Auth::user();
    }
}
