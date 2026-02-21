<?php

declare(strict_types=1);

use App\Enums\Enums\AuditionStatus;
use App\Models\Activity;
use App\Models\Audition;
use App\Models\User;

const INBOX_ACTIONS_QUERY = <<<'GRAPHQL'
query {
    activities(first: 20) {
        data {
            id
            user_id
            trigger
            targetable {
                __typename
                ... on Audition {
                    id
                    project_title
                    status
                }
            }
        }
    }
}
GRAPHQL;

describe('Activity Actions Query', function () {
    test('returns versioned targetable snapshot for activities', function () {
        $user = actingAsUser();

        $audition = Audition::factory()->create([
            'user_id' => $user->id,
            'project_title' => 'Initial Title',
            'status' => AuditionStatus::RECEIVED,
            'response_deadline' => null,
        ]);

        $audition->update([
            'project_title' => 'Middle Title',
            'status' => AuditionStatus::PREPARING,
        ]);

        $middleVersion = $audition->fresh()->current_version;

        $audition->update([
            'project_title' => 'Final Title',
            'status' => AuditionStatus::SUBMITTED,
        ]);

        $action = Activity::factory()->create([
            'user_id' => $user->id,
            'targetable_type' => Audition::class,
            'targetable_id' => $audition->id,
            'targetable_version' => $middleVersion,
            'trigger' => 'audition_response_due',
            'action' => null,
        ]);

        $response = $this->graphQL(INBOX_ACTIONS_QUERY);

        $response->assertGraphQLErrorFree();

        $items = $response->json('data.activities.data');
        expect($items)->toHaveCount(1);
        expect($items[0]['id'])->toBe($action->id);
        expect($items[0]['targetable']['__typename'])->toBe('Audition');
        expect($items[0]['targetable']['id'])->toBe($audition->id);
        expect($items[0]['targetable']['project_title'])->toBe('Middle Title');
        expect($items[0]['targetable']['status'])->toBe('PREPARING');
    });

    test('scopes activities to the authenticated user', function () {
        $user = actingAsUser();
        $otherUser = User::factory()->create();

        $audition = Audition::factory()->create([
            'user_id' => $user->id,
            'response_deadline' => null,
        ]);

        $otherAudition = Audition::factory()->create(['user_id' => $otherUser->id, 'response_deadline' => null]);

        Activity::factory()->create([
            'user_id' => $user->id,
            'targetable_type' => Audition::class,
            'targetable_id' => $audition->id,
            'trigger' => 'audition_response_due',
            'action' => null,
        ]);

        Activity::factory()->create([
            'user_id' => $otherUser->id,
            'targetable_type' => Audition::class,
            'targetable_id' => $otherAudition->id,
            'trigger' => 'audition_response_due',
            'action' => null,
        ]);

        $response = $this->graphQL(INBOX_ACTIONS_QUERY);

        $response->assertGraphQLErrorFree();

        $items = $response->json('data.activities.data');
        expect($items)->toHaveCount(1);
        expect($items[0]['user_id'])->toBe($user->id);
    });
});
