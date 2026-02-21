<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\BusinessProfile;
use App\Models\Settings;
use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        Settings::create([
            'user_id' => $user->id,
        ]);

        BusinessProfile::create([
            'user_id' => $user->id,
        ]);
    }
}
