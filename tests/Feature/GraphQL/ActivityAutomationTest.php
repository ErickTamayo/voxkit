<?php

declare(strict_types=1);

use App\Enums\Enums\JobStatus;
use App\Models\Activity;
use App\Models\Audition;
use App\Models\Job;
use App\Models\Platform;
use App\Models\User;
use Carbon\Carbon;

const CREATE_AUDITION_FOR_INBOX_MUTATION = <<<'GRAPHQL'
mutation CreateAudition($input: CreateAuditionInput!) {
    createAudition(input: $input) {
        id
        status
    }
}
GRAPHQL;

const UPDATE_AUDITION_FOR_INBOX_MUTATION = <<<'GRAPHQL'
mutation UpdateAudition($id: ULID!, $input: UpdateAuditionInput!) {
    updateAudition(id: $id, input: $input) {
        id
        status
    }
}
GRAPHQL;

beforeEach(function (): void {
    config()->set('scout.driver', 'null');
    Carbon::setTestNow('2026-02-09 10:00:00');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

test('audition graphQL mutations create and archive activities via observers', function (): void {
    $user = actingAsUser();
    $platform = Platform::factory()->create(['user_id' => $user->id]);

    $createResponse = $this->graphQL(CREATE_AUDITION_FOR_INBOX_MUTATION, [
        'input' => [
            'sourceable_type' => Platform::class,
            'sourceable_id' => $platform->id,
            'project_title' => 'Observer audition',
            'category' => 'COMMERCIAL',
            'rate_type' => 'FLAT',
            'status' => 'RECEIVED',
            'response_deadline' => now()->addHours(12)->timestamp * 1000,
        ],
    ]);

    $createResponse->assertGraphQLErrorFree();

    $auditionId = $createResponse->json('data.createAudition.id');

    $this->assertDatabaseHas('activities', [
        'user_id' => $user->id,
        'targetable_type' => Audition::class,
        'targetable_id' => $auditionId,
        'trigger' => 'audition_response_due',
        'action' => null,
    ]);

    $updateResponse = $this->graphQL(UPDATE_AUDITION_FOR_INBOX_MUTATION, [
        'id' => $auditionId,
        'input' => [
            'status' => 'WON',
        ],
    ]);

    $updateResponse->assertGraphQLErrorFree();

    $this->assertDatabaseHas('activities', [
        'user_id' => $user->id,
        'targetable_type' => Audition::class,
        'targetable_id' => $auditionId,
        'trigger' => 'audition_response_due',
        'action' => 'archived',
    ]);
});

test('sync activities command archives expired time-window actions and never reopens archived ones', function (): void {
    $user = User::factory()->create();
    $user->settings->update([
        'activity_job_session_upcoming_hours' => 1,
    ]);

    $job = Job::factory()->create([
        'user_id' => $user->id,
        'status' => JobStatus::BOOKED,
        'session_date' => now()->addMinutes(30),
    ]);

    $this->artisan('app:sync-activities', ['--user' => $user->id])
        ->assertExitCode(0);

    $this->assertDatabaseHas('activities', [
        'user_id' => $user->id,
        'targetable_type' => Job::class,
        'targetable_id' => $job->id,
        'trigger' => 'job_session_upcoming',
        'action' => null,
    ]);

    Carbon::setTestNow(now()->addHours(2));

    $this->artisan('app:sync-activities', ['--user' => $user->id])
        ->assertExitCode(0);

    $this->assertDatabaseHas('activities', [
        'user_id' => $user->id,
        'targetable_type' => Job::class,
        'targetable_id' => $job->id,
        'trigger' => 'job_session_upcoming',
        'action' => 'archived',
    ]);

    $job->update([
        'session_date' => now()->addMinutes(15),
    ]);

    $this->artisan('app:sync-activities', ['--user' => $user->id])
        ->assertExitCode(0);

    expect(Activity::query()
        ->where('user_id', $user->id)
        ->where('targetable_type', Job::class)
        ->where('targetable_id', $job->id)
        ->where('trigger', 'job_session_upcoming')
        ->count())->toBe(1);

    $action = Activity::query()
        ->where('user_id', $user->id)
        ->where('targetable_type', Job::class)
        ->where('targetable_id', $job->id)
        ->where('trigger', 'job_session_upcoming')
        ->firstOrFail();

    expect($action->action)->toBe('archived');
});
