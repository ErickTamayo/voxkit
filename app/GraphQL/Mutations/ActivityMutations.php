<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\Activity;

class ActivityMutations
{
    /**
     * Snooze an activity for the authenticated user.
     */
    public function snooze($root, array $args): Activity
    {
        /** @var Activity $action */
        $action = Activity::query()->findOrFail($args['id']);

        $action->action = 'snoozed';
        $action->snoozed_until = $args['snoozed_until'];
        $action->save();

        return $action;
    }

    /**
     * Archive an activity for the authenticated user.
     */
    public function archive($root, array $args): Activity
    {
        /** @var Activity $action */
        $action = Activity::query()->findOrFail($args['id']);

        $action->action = 'archived';
        $action->snoozed_until = null;
        $action->save();

        return $action;
    }
}
