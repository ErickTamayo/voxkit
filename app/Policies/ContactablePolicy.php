<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ContactablePolicy
{
    public function view(User $user, Model $model): bool
    {
        return $this->owns($user, $model);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->owns($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->owns($user, $model);
    }

    private function owns(User $user, Model $model): bool
    {
        if (! method_exists($model, 'contact')) {
            return false;
        }

        $contact = $model->contact()->first();
        if ($contact === null) {
            return false;
        }

        return (string) $contact->getAttribute('user_id') === (string) $user->id;
    }
}
