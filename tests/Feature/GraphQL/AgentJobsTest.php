<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\Contact;
use App\Models\Job;
use App\Models\User;

const GET_AGENT_JOBS_QUERY = <<<'GRAPHQL'
query GetAgentJobs($agentId: ULID!, $first: Int) {
    agent(id: $agentId) {
        jobs(first: $first) {
            data {
                id
                project_title
            }
            paginatorInfo {
                total
                count
                currentPage
                hasMorePages
            }
        }
    }
}
GRAPHQL;

const GET_AGENT_JOBS_FILTERED_QUERY = <<<'GRAPHQL'
query GetAgentCompletedJobs($agentId: ULID!) {
    agent(id: $agentId) {
        jobs(where: { column: STATUS, operator: EQ, value: "completed" }) {
            paginatorInfo {
                total
            }
        }
    }
}
GRAPHQL;

const GET_AGENT_JOBS_SORTED_QUERY = <<<'GRAPHQL'
query GetAgentJobsSorted($agentId: ULID!) {
    agent(id: $agentId) {
        jobs(orderBy: [{ column: CREATED_AT, order: DESC }], first: 5) {
            data {
                project_title
            }
        }
    }
}
GRAPHQL;

const GET_AGENT_JOBS_SIMPLE_QUERY = <<<'GRAPHQL'
query GetAgentJobs($agentId: ULID!) {
    agent(id: $agentId) {
        jobs {
            paginatorInfo {
                total
            }
        }
    }
}
GRAPHQL;

describe('Agent Jobs Pagination', function () {
    test('returns paginated jobs for agent', function () {
        $user = actingAsUser();
        $agent = Agent::factory()->create();
        $contact = Contact::factory()->create([
            'user_id' => $user->id,
            'contactable_type' => Agent::class,
            'contactable_id' => $agent->id,
        ]);

        Job::factory()->count(15)->create([
            'user_id' => $user->id,
            'agent_id' => $contact->id,
        ]);

        $response = $this->graphQL(GET_AGENT_JOBS_QUERY, [
            'agentId' => $agent->id,
            'first' => 10,
        ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.agent.jobs.paginatorInfo.total'))->toBe(15);
        expect($response->json('data.agent.jobs.paginatorInfo.count'))->toBe(10);
        expect($response->json('data.agent.jobs.paginatorInfo.currentPage'))->toBe(1);
        expect($response->json('data.agent.jobs.paginatorInfo.hasMorePages'))->toBeTrue();
    });
});

describe('Agent Jobs Filtering', function () {
    test('filters jobs by status', function () {
        $user = actingAsUser();
        $agent = Agent::factory()->create();
        $contact = Contact::factory()->create([
            'user_id' => $user->id,
            'contactable_type' => Agent::class,
            'contactable_id' => $agent->id,
        ]);

        Job::factory()->count(3)->create([
            'user_id' => $user->id,
            'agent_id' => $contact->id,
            'status' => 'completed',
        ]);

        Job::factory()->count(2)->create([
            'user_id' => $user->id,
            'agent_id' => $contact->id,
            'status' => 'in_progress',
        ]);

        $response = $this->graphQL(GET_AGENT_JOBS_FILTERED_QUERY, [
            'agentId' => $agent->id,
        ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.agent.jobs.paginatorInfo.total'))->toBe(3);
    });
});

describe('Agent Jobs Sorting', function () {
    test('orders jobs by created_at desc', function () {
        $user = actingAsUser();
        $agent = Agent::factory()->create();
        $contact = Contact::factory()->create([
            'user_id' => $user->id,
            'contactable_type' => Agent::class,
            'contactable_id' => $agent->id,
        ]);

        Job::factory()->create([
            'user_id' => $user->id,
            'agent_id' => $contact->id,
            'created_at' => now()->subDays(5),
            'project_title' => 'Old Job',
        ]);

        Job::factory()->create([
            'user_id' => $user->id,
            'agent_id' => $contact->id,
            'created_at' => now(),
            'project_title' => 'New Job',
        ]);

        $response = $this->graphQL(GET_AGENT_JOBS_SORTED_QUERY, [
            'agentId' => $agent->id,
        ]);

        $response->assertGraphQLErrorFree();
        $jobs = $response->json('data.agent.jobs.data');
        expect($jobs[0]['project_title'])->toBe('New Job');
        expect($jobs[1]['project_title'])->toBe('Old Job');
    });
});

describe('Agent Jobs Authorization', function () {
    test('only returns jobs for authenticated user', function () {
        $user1 = actingAsUser();
        $user2 = User::factory()->create();

        $agent = Agent::factory()->create();
        $contact1 = Contact::factory()->create([
            'user_id' => $user1->id,
            'contactable_type' => Agent::class,
            'contactable_id' => $agent->id,
        ]);

        Job::factory()->count(3)->create([
            'user_id' => $user1->id,
            'agent_id' => $contact1->id,
        ]);

        $contact2 = Contact::factory()->create([
            'user_id' => $user2->id,
            'contactable_type' => Agent::class,
            'contactable_id' => $agent->id,
        ]);
        Job::factory()->count(5)->create([
            'user_id' => $user2->id,
            'agent_id' => $contact2->id,
        ]);

        $response = $this->graphQL(GET_AGENT_JOBS_SIMPLE_QUERY, [
            'agentId' => $agent->id,
        ]);

        $response->assertGraphQLErrorFree();
        expect($response->json('data.agent.jobs.paginatorInfo.total'))->toBe(3);
    });
});
