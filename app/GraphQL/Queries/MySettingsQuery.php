<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\Settings;
use Illuminate\Support\Facades\Auth;

class MySettingsQuery
{
    /**
     * Get the authenticated user's settings.
     */
    public function __invoke(): ?Settings
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return $user->settings;
    }
}
