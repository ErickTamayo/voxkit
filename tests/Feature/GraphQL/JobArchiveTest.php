<?php

declare(strict_types=1);

use App\Models\Job;

const ARCHIVE_JOB_MUTATION = <<<'GRAPHQL'
mutation ArchiveJob($id: ULID!) {
    archiveJob(id: $id) {
        id
        archived_at
    }
}
GRAPHQL;

const UNARCHIVE_JOB_MUTATION = <<<'GRAPHQL'
mutation UnarchiveJob($id: ULID!) {
    unarchiveJob(id: $id) {
        id
        archived_at
    }
}
GRAPHQL;

describe('Job Archiving', function () {
    test('can archive a job', function () {
        $user = actingAsUser();
        $job = Job::factory()->create(['user_id' => $user->id]);

        $response = $this->graphQL(ARCHIVE_JOB_MUTATION, ['id' => $job->id]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.archiveJob.id'))->toBe($job->id);
        expect($response->json('data.archiveJob.archived_at'))->not->toBeNull();
        expect($job->fresh()->archived_at)->not->toBeNull();
    });

    test('can unarchive a job', function () {
        $user = actingAsUser();
        $job = Job::factory()->create([
            'user_id' => $user->id,
            'archived_at' => now(),
        ]);

        $response = $this->graphQL(UNARCHIVE_JOB_MUTATION, ['id' => $job->id]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.unarchiveJob.id'))->toBe($job->id);
        expect($response->json('data.unarchiveJob.archived_at'))->toBeNull();
        expect($job->fresh()->archived_at)->toBeNull();
    });
});
