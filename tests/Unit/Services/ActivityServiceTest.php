<?php

declare(strict_types=1);

use App\Enums\Enums\AuditionStatus;
use App\Enums\Enums\InvoiceStatus;
use App\Enums\Enums\JobStatus;
use App\Models\Activity;
use App\Models\Audition;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\UsageRight;
use App\Models\User;
use App\Services\ActivityService;
use Carbon\Carbon;
use Illuminate\Support\Str;

beforeEach(function (): void {
    config()->set('scout.driver', 'null');
    Carbon::setTestNow('2026-02-09 10:00:00');
    $this->service = app(ActivityService::class);
    $this->user = User::factory()->create();
});

afterEach(function (): void {
    Carbon::setTestNow();
});

test('creates audition activity when condition is met', function (): void {
    $audition = Audition::factory()->create([
        'user_id' => $this->user->id,
        'status' => AuditionStatus::RECEIVED,
        'response_deadline' => now()->addHours(12),
    ]);

    $this->service->syncAudition($audition);

    /** @var Activity $action */
    $action = Activity::query()
        ->where('user_id', $this->user->id)
        ->where('targetable_type', Audition::class)
        ->where('targetable_id', $audition->id)
        ->where('trigger', 'audition_response_due')
        ->firstOrFail();

    expect($action->action)->toBeNull();
    expect($action->targetable_version)->toBe($audition->fresh()->current_version);
});

test('archives audition activity when condition becomes false without changing stored version', function (): void {
    $audition = Audition::factory()->create([
        'user_id' => $this->user->id,
        'status' => AuditionStatus::RECEIVED,
        'response_deadline' => now()->addHours(12),
    ]);

    $this->service->syncAudition($audition);

    $action = Activity::query()->firstOrFail();
    $storedVersion = $action->targetable_version;

    $audition->update([
        'status' => AuditionStatus::WON,
    ]);

    $this->service->syncAudition($audition->fresh());

    $action->refresh();

    expect($action->action)->toBe('archived');
    expect($action->snoozed_until)->toBeNull();
    expect($action->targetable_version)->toBe($storedVersion);
});

test('ignores archived actions even when trigger condition remains true', function (): void {
    $audition = Audition::factory()->create([
        'user_id' => $this->user->id,
        'status' => AuditionStatus::WON,
        'response_deadline' => now()->addHours(12),
    ]);

    expect(Activity::query()->count())->toBe(0);

    $action = Activity::factory()->create([
        'user_id' => $this->user->id,
        'targetable_type' => Audition::class,
        'targetable_id' => $audition->id,
        'trigger' => 'audition_response_due',
        'action' => 'archived',
        'targetable_version' => 11,
    ]);

    $updatedAt = $action->updated_at;

    $audition->update([
        'status' => AuditionStatus::RECEIVED,
        'project_title' => 'Updated title to bump version',
    ]);

    $this->service->syncAudition($audition->fresh());

    $action->refresh();

    expect(Activity::query()->count())->toBe(1);
    expect($action->action)->toBe('archived');
    expect($action->targetable_version)->toBe(11);
    expect($action->updated_at->toDateTimeString())->toBe($updatedAt->toDateTimeString());
});

test('preserves snoozed action state when trigger condition is still true', function (): void {
    $audition = Audition::factory()->create([
        'user_id' => $this->user->id,
        'status' => AuditionStatus::RECEIVED,
        'response_deadline' => now()->addHours(12),
    ]);

    $action = Activity::factory()->create([
        'user_id' => $this->user->id,
        'targetable_type' => Audition::class,
        'targetable_id' => $audition->id,
        'trigger' => 'audition_response_due',
        'action' => 'snoozed',
        'snoozed_until' => now()->addDays(2),
        'targetable_version' => 7,
    ]);

    $previousSnooze = $action->snoozed_until;

    $audition->update([
        'project_title' => 'Another update',
    ]);

    $this->service->syncAudition($audition->fresh());

    $action->refresh();

    expect($action->action)->toBe('snoozed');
    expect($action->snoozed_until?->toDateTimeString())->toBe($previousSnooze?->toDateTimeString());
    expect($action->targetable_version)->toBe(7);
});

test('syncs all job triggers and archives them once job is no longer actionable', function (): void {
    $job = Job::factory()->create([
        'user_id' => $this->user->id,
        'status' => JobStatus::REVISION,
        'session_date' => now()->addHours(6),
        'delivery_deadline' => now()->addHours(8),
    ]);

    $this->service->syncJob($job);

    expect(Activity::query()->where('targetable_id', $job->id)->count())->toBe(3);

    $job->update([
        'status' => JobStatus::COMPLETED,
    ]);

    $this->service->syncJob($job->fresh());

    $actions = Activity::query()
        ->where('targetable_type', Job::class)
        ->where('targetable_id', $job->id)
        ->get();

    expect($actions)->toHaveCount(3);
    foreach ($actions as $action) {
        expect($action->action)->toBe('archived');
    }
});

test('transitions invoice actions from due soon to overdue and keeps original version on archive', function (): void {
    $invoice = Invoice::factory()->create([
        'user_id' => $this->user->id,
        'status' => InvoiceStatus::SENT,
        'due_at' => now()->addDays(2),
    ]);

    $this->service->syncInvoice($invoice);

    /** @var Activity $dueSoon */
    $dueSoon = Activity::query()
        ->where('targetable_type', Invoice::class)
        ->where('targetable_id', $invoice->id)
        ->where('trigger', 'invoice_due_soon')
        ->firstOrFail();

    $storedVersion = $dueSoon->targetable_version;

    $invoice->update([
        'due_at' => now()->subDay(),
    ]);

    $this->service->syncInvoice($invoice->fresh());

    $dueSoon->refresh();

    $overdue = Activity::query()
        ->where('targetable_type', Invoice::class)
        ->where('targetable_id', $invoice->id)
        ->where('trigger', 'invoice_overdue')
        ->first();

    expect($dueSoon->action)->toBe('archived');
    expect($dueSoon->targetable_version)->toBe($storedVersion);
    expect($overdue)->not()->toBeNull();
    expect($overdue?->action)->toBeNull();
});

test('archives usage right actions when the owning job is deleted', function (): void {
    $job = Job::factory()->create([
        'user_id' => $this->user->id,
        'status' => JobStatus::IN_PROGRESS,
    ]);

    $usageRight = UsageRight::factory()->create([
        'usable_type' => Job::class,
        'usable_id' => $job->id,
        'expiration_date' => now()->addDays(5),
    ]);

    $this->service->syncUsageRight($usageRight);

    $job->delete();

    $this->service->syncUsageRight($usageRight->fresh());

    $action = Activity::query()
        ->where('targetable_type', UsageRight::class)
        ->where('targetable_id', $usageRight->id)
        ->where('trigger', 'usage_rights_expiring')
        ->first();

    expect($action)->not()->toBeNull();
    expect($action?->action)->toBe('archived');
});

test('syncForUser respects configurable thresholds from settings', function (): void {
    $this->user->settings->update([
        'activity_audition_response_due_hours' => 1,
    ]);

    $audition = Audition::factory()->create([
        'user_id' => $this->user->id,
        'status' => AuditionStatus::RECEIVED,
        'response_deadline' => now()->addHours(2),
    ]);

    $this->service->syncForUser($this->user->fresh());

    expect(Activity::query()
        ->where('targetable_type', Audition::class)
        ->where('targetable_id', $audition->id)
        ->count())->toBe(0);

    $this->user->settings->update([
        'activity_audition_response_due_hours' => 3,
    ]);

    $this->service->syncForUser($this->user->fresh());

    expect(Activity::query()
        ->where('targetable_type', Audition::class)
        ->where('targetable_id', $audition->id)
        ->where('trigger', 'audition_response_due')
        ->count())->toBe(1);
});

test('syncForUser archives active actions with missing targetables', function (): void {
    Activity::factory()->create([
        'user_id' => $this->user->id,
        'targetable_type' => Audition::class,
        'targetable_id' => (string) Str::ulid(),
        'trigger' => 'audition_response_due',
        'action' => null,
    ]);

    $this->service->syncForUser($this->user->fresh());

    $action = Activity::query()->firstOrFail();

    expect($action->action)->toBe('archived');
});
