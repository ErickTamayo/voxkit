<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class InvoiceItemPolicy
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
        if (! method_exists($model, 'invoice')) {
            return false;
        }

        $invoice = $model->invoice()->first();
        if ($invoice === null) {
            return false;
        }

        $ownerId = $invoice->getAttribute('user_id');
        if ($ownerId === null) {
            return false;
        }

        return (string) $ownerId === (string) $user->id;
    }
}
