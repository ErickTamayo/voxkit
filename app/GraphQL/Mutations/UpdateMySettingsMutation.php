<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\Settings;
use GraphQL\Error\Error;
use Illuminate\Support\Facades\Auth;

class UpdateMySettingsMutation
{
    /**
     * Update the authenticated user's settings.
     */
    public function __invoke($root, array $args): Settings
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $settings = $user->settings;

        if (! $settings) {
            throw new Error('Settings not found for the authenticated user');
        }

        // Only update fillable fields
        $settings->fill($args);
        $settings->save();

        return $settings;
    }
}
