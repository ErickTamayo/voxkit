<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UpdateMeMutation
{
    /**
     * Update the authenticated user's profile.
     */
    public function __invoke($root, array $args): User
    {
        /** @var User $user */
        $user = Auth::user();

        // Only update fillable fields
        $user->fill($args);
        $user->save();

        return $user;
    }
}
