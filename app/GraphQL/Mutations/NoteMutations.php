<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\Activity;
use App\Models\Audition;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\ExpenseDefinition;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Note;
use App\Models\Platform;
use App\Models\UsageRight;
use Illuminate\Support\Facades\Auth;

class NoteMutations
{
    /**
     * Add a note to an entity.
     */
    public function add($root, array $args): Note
    {
        $notableId = $args['notable_id'];
        $content = $args['content'];
        $userId = Auth::id();

        // Try to find the entity across all notable types
        $notableEntity = $this->findNotableEntity($notableId, $userId);

        if (! $notableEntity) {
            throw new \Exception("Entity with ID {$notableId} not found or does not belong to user");
        }

        return Note::create([
            'user_id' => $userId,
            'notable_type' => get_class($notableEntity),
            'notable_id' => $notableId,
            'content' => $content,
        ]);
    }

    /**
     * Find the notable entity by ID across all notable types.
     */
    private function findNotableEntity(string $id, string $userId)
    {
        // Types with direct user_id column
        $directUserTypes = [
            Audition::class,
            Job::class,
            Contact::class,
            Invoice::class,
            Expense::class,
            ExpenseDefinition::class,
            Activity::class,
            Platform::class,
        ];

        foreach ($directUserTypes as $type) {
            $entity = $type::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if ($entity) {
                return $entity;
            }
        }

        // UsageRight has indirect user_id through polymorphic usable relation
        $usageRight = UsageRight::where('id', $id)->first();
        if ($usageRight && $usageRight->usable && $usageRight->usable->user_id === $userId) {
            return $usageRight;
        }

        return null;
    }
}
