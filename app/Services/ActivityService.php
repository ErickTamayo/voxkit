<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Enums\AuditionStatus;
use App\Enums\Enums\InvoiceStatus;
use App\Enums\Enums\JobStatus;
use App\Models\Activity;
use App\Models\Audition;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Settings;
use App\Models\UsageRight;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class ActivityService
{
    private const ACTION_ARCHIVED = 'archived';

    private const TRIGGER_AUDITION_RESPONSE_DUE = 'audition_response_due';

    private const TRIGGER_JOB_SESSION_UPCOMING = 'job_session_upcoming';

    private const TRIGGER_JOB_DELIVERY_DUE = 'job_delivery_due';

    private const TRIGGER_JOB_REVISION_REQUESTED = 'job_revision_requested';

    private const TRIGGER_INVOICE_DUE_SOON = 'invoice_due_soon';

    private const TRIGGER_INVOICE_OVERDUE = 'invoice_overdue';

    private const TRIGGER_USAGE_RIGHTS_EXPIRING = 'usage_rights_expiring';

    private const DEFAULT_AUDITION_RESPONSE_DUE_HOURS = 48;

    private const DEFAULT_JOB_SESSION_UPCOMING_HOURS = 24;

    private const DEFAULT_JOB_DELIVERY_DUE_HOURS = 24;

    private const DEFAULT_INVOICE_DUE_SOON_DAYS = 7;

    private const DEFAULT_USAGE_RIGHTS_EXPIRING_DAYS = 30;

    /**
     * Sync all activities for a single user.
     */
    public function syncForUser(User $user): void
    {
        $settings = $this->settingsForUser($user->id);

        Audition::query()
            ->where('user_id', $user->id)
            ->get()
            ->each(function (Audition $audition) use ($settings): void {
                $this->syncAuditionWithSettings($audition, $settings);
            });

        Job::query()
            ->where('user_id', $user->id)
            ->get()
            ->each(function (Job $job) use ($settings): void {
                $this->syncJobWithSettings($job, $settings);
            });

        Invoice::query()
            ->where('user_id', $user->id)
            ->get()
            ->each(function (Invoice $invoice) use ($settings): void {
                $this->syncInvoiceWithSettings($invoice, $settings);
            });

        UsageRight::query()
            ->where(function ($query) use ($user): void {
                $query->where(function ($auditionUsageRightQuery) use ($user): void {
                    $auditionUsageRightQuery
                        ->where('usable_type', Audition::class)
                        ->whereIn('usable_id', Audition::query()
                            ->withTrashed()
                            ->where('user_id', $user->id)
                            ->select('id'));
                })->orWhere(function ($jobUsageRightQuery) use ($user): void {
                    $jobUsageRightQuery
                        ->where('usable_type', Job::class)
                        ->whereIn('usable_id', Job::query()
                            ->withTrashed()
                            ->where('user_id', $user->id)
                            ->select('id'));
                });
            })
            ->get()
            ->each(function (UsageRight $usageRight) use ($settings, $user): void {
                $this->syncUsageRightWithSettings($usageRight, $settings, $user->id);
            });

        $this->archiveOrphanedActionsForUser($user->id);
    }

    /**
     * Sync activities for a single audition.
     */
    public function syncAudition(Audition $audition): void
    {
        $this->syncAuditionWithSettings($audition, $this->settingsForUser($audition->user_id));
    }

    /**
     * Sync activities for a single job.
     */
    public function syncJob(Job $job): void
    {
        $this->syncJobWithSettings($job, $this->settingsForUser($job->user_id));
    }

    /**
     * Sync activities for a single invoice.
     */
    public function syncInvoice(Invoice $invoice): void
    {
        $this->syncInvoiceWithSettings($invoice, $this->settingsForUser($invoice->user_id));
    }

    /**
     * Sync activities for a single usage right.
     */
    public function syncUsageRight(UsageRight $usageRight): void
    {
        $userId = $this->resolveUsageRightOwnerUserId($usageRight);
        if ($userId === null) {
            $this->archiveActionsForTargetable($usageRight, [
                self::TRIGGER_USAGE_RIGHTS_EXPIRING,
            ]);

            return;
        }

        $this->syncUsageRightWithSettings($usageRight, $this->settingsForUser($userId), $userId);
    }

    private function syncAuditionWithSettings(Audition $audition, Settings $settings): void
    {
        $shouldTrigger = $this->shouldTriggerAuditionResponseDue(
            $audition,
            $this->auditionResponseDueHours($settings)
        );

        $this->syncTrigger(
            userId: $audition->user_id,
            targetable: $audition,
            trigger: self::TRIGGER_AUDITION_RESPONSE_DUE,
            shouldTrigger: $shouldTrigger
        );
    }

    private function syncJobWithSettings(Job $job, Settings $settings): void
    {
        $this->syncTrigger(
            userId: $job->user_id,
            targetable: $job,
            trigger: self::TRIGGER_JOB_SESSION_UPCOMING,
            shouldTrigger: $this->shouldTriggerJobSessionUpcoming(
                $job,
                $this->jobSessionUpcomingHours($settings)
            )
        );

        $this->syncTrigger(
            userId: $job->user_id,
            targetable: $job,
            trigger: self::TRIGGER_JOB_DELIVERY_DUE,
            shouldTrigger: $this->shouldTriggerJobDeliveryDue(
                $job,
                $this->jobDeliveryDueHours($settings)
            )
        );

        $this->syncTrigger(
            userId: $job->user_id,
            targetable: $job,
            trigger: self::TRIGGER_JOB_REVISION_REQUESTED,
            shouldTrigger: $this->shouldTriggerJobRevisionRequested($job)
        );
    }

    private function syncInvoiceWithSettings(Invoice $invoice, Settings $settings): void
    {
        $this->syncTrigger(
            userId: $invoice->user_id,
            targetable: $invoice,
            trigger: self::TRIGGER_INVOICE_DUE_SOON,
            shouldTrigger: $this->shouldTriggerInvoiceDueSoon(
                $invoice,
                $this->invoiceDueSoonDays($settings)
            )
        );

        $this->syncTrigger(
            userId: $invoice->user_id,
            targetable: $invoice,
            trigger: self::TRIGGER_INVOICE_OVERDUE,
            shouldTrigger: $this->shouldTriggerInvoiceOverdue($invoice)
        );
    }

    private function syncUsageRightWithSettings(UsageRight $usageRight, Settings $settings, string $userId): void
    {
        $this->syncTrigger(
            userId: $userId,
            targetable: $usageRight,
            trigger: self::TRIGGER_USAGE_RIGHTS_EXPIRING,
            shouldTrigger: $this->shouldTriggerUsageRightsExpiring(
                $usageRight,
                $this->usageRightsExpiringDays($settings),
                $this->usageRightOwnerIsActive($usageRight)
            )
        );
    }

    private function syncTrigger(string $userId, Model $targetable, string $trigger, bool $shouldTrigger): void
    {
        /** @var Activity|null $action */
        $action = Activity::query()
            ->where('user_id', $userId)
            ->where('targetable_type', $targetable::class)
            ->where('targetable_id', (string) $targetable->getKey())
            ->where('trigger', $trigger)
            ->latest('created_at')
            ->first();

        if (! $action) {
            if (! $shouldTrigger) {
                return;
            }

            Activity::create([
                'user_id' => $userId,
                'targetable_type' => $targetable::class,
                'targetable_id' => (string) $targetable->getKey(),
                'targetable_version' => $this->targetableVersion($targetable),
                'trigger' => $trigger,
                'action' => null,
                'snoozed_until' => null,
            ]);

            return;
        }

        // Archived actions are terminal and should not be modified.
        if ($action->action === self::ACTION_ARCHIVED) {
            return;
        }

        if ($shouldTrigger) {
            return;
        }

        $this->archiveAction($action);
    }

    private function archiveAction(Activity $action): void
    {
        if ($action->action === self::ACTION_ARCHIVED) {
            return;
        }

        $action->action = self::ACTION_ARCHIVED;
        $action->snoozed_until = null;
        $action->save();
    }

    /**
     * @param  list<string>|null  $triggers
     */
    private function archiveActionsForTargetable(Model $targetable, ?array $triggers = null): void
    {
        $query = Activity::query()
            ->where('targetable_type', $targetable::class)
            ->where('targetable_id', (string) $targetable->getKey());

        if ($triggers !== null) {
            $query->whereIn('trigger', $triggers);
        }

        $query->where(function ($query): void {
            $query
                ->whereNull('action')
                ->orWhere('action', '!=', self::ACTION_ARCHIVED);
        });

        $query->get()->each(function (Activity $action): void {
            $this->archiveAction($action);
        });
    }

    private function archiveOrphanedActionsForUser(string $userId): void
    {
        Activity::query()
            ->where('user_id', $userId)
            ->where(function ($query): void {
                $query
                    ->whereNull('action')
                    ->orWhere('action', '!=', self::ACTION_ARCHIVED);
            })
            ->with('targetable')
            ->get()
            ->each(function (Activity $action): void {
                $targetable = $action->targetable;
                if ($targetable === null) {
                    $this->archiveAction($action);

                    return;
                }

                if (method_exists($targetable, 'trashed') && $targetable->trashed()) {
                    $this->archiveAction($action);
                }
            });
    }

    private function shouldTriggerAuditionResponseDue(Audition $audition, int $hours): bool
    {
        if ($audition->trashed() || ! in_array($audition->status, [
            AuditionStatus::RECEIVED,
            AuditionStatus::PREPARING,
            AuditionStatus::SUBMITTED,
            AuditionStatus::SHORTLISTED,
            AuditionStatus::CALLBACK,
        ], true)) {
            return false;
        }

        return $this->isWithinHoursWindow($audition->response_deadline, $hours);
    }

    private function shouldTriggerJobSessionUpcoming(Job $job, int $hours): bool
    {
        if ($job->trashed() || ! in_array($job->status, [
            JobStatus::BOOKED,
            JobStatus::IN_PROGRESS,
            JobStatus::REVISION,
        ], true)) {
            return false;
        }

        return $this->isWithinHoursWindow($job->session_date, $hours);
    }

    private function shouldTriggerJobDeliveryDue(Job $job, int $hours): bool
    {
        if ($job->trashed() || ! in_array($job->status, [
            JobStatus::BOOKED,
            JobStatus::IN_PROGRESS,
            JobStatus::REVISION,
        ], true)) {
            return false;
        }

        return $this->isWithinHoursWindow($job->delivery_deadline, $hours);
    }

    private function shouldTriggerJobRevisionRequested(Job $job): bool
    {
        if ($job->trashed()) {
            return false;
        }

        return $job->status === JobStatus::REVISION;
    }

    private function shouldTriggerInvoiceDueSoon(Invoice $invoice, int $days): bool
    {
        if ($invoice->trashed() || ! in_array($invoice->status, [
            InvoiceStatus::DRAFT,
            InvoiceStatus::SENT,
        ], true)) {
            return false;
        }

        return $this->isWithinDaysWindow($invoice->due_at, $days);
    }

    private function shouldTriggerInvoiceOverdue(Invoice $invoice): bool
    {
        if ($invoice->trashed() || ! in_array($invoice->status, [
            InvoiceStatus::DRAFT,
            InvoiceStatus::SENT,
            InvoiceStatus::OVERDUE,
        ], true)) {
            return false;
        }

        return $this->isPastDueDate($invoice->due_at);
    }

    private function shouldTriggerUsageRightsExpiring(UsageRight $usageRight, int $days, bool $ownerIsActive): bool
    {
        if ($usageRight->trashed() || ! $ownerIsActive) {
            return false;
        }

        return $this->isWithinDaysWindow($usageRight->expiration_date, $days);
    }

    private function isWithinHoursWindow(?CarbonInterface $value, int $hours): bool
    {
        if ($value === null) {
            return false;
        }

        $windowHours = max(0, $hours);
        $now = Carbon::now();
        $windowEnd = $now->copy()->addHours($windowHours);

        return $value->betweenIncluded($now, $windowEnd);
    }

    private function isWithinDaysWindow(?CarbonInterface $value, int $days): bool
    {
        if ($value === null) {
            return false;
        }

        $windowDays = max(0, $days);
        $windowStart = Carbon::now()->startOfDay();
        $windowEnd = Carbon::now()->addDays($windowDays)->endOfDay();

        return $value->betweenIncluded($windowStart, $windowEnd);
    }

    private function isPastDueDate(?CarbonInterface $value): bool
    {
        if ($value === null) {
            return false;
        }

        return $value->lt(Carbon::now()->startOfDay());
    }

    private function targetableVersion(Model $targetable): ?int
    {
        $version = $targetable->getAttribute('current_version');
        if (! is_numeric($version)) {
            return null;
        }

        return (int) $version;
    }

    private function settingsForUser(string $userId): Settings
    {
        $settings = Settings::query()
            ->where('user_id', $userId)
            ->first();

        if ($settings) {
            return $settings;
        }

        $fallback = new Settings;
        $fallback->forceFill([
            'user_id' => $userId,
            'activity_audition_response_due_hours' => self::DEFAULT_AUDITION_RESPONSE_DUE_HOURS,
            'activity_job_session_upcoming_hours' => self::DEFAULT_JOB_SESSION_UPCOMING_HOURS,
            'activity_job_delivery_due_hours' => self::DEFAULT_JOB_DELIVERY_DUE_HOURS,
            'activity_invoice_due_soon_days' => self::DEFAULT_INVOICE_DUE_SOON_DAYS,
            'activity_usage_rights_expiring_days' => self::DEFAULT_USAGE_RIGHTS_EXPIRING_DAYS,
        ]);

        return $fallback;
    }

    private function auditionResponseDueHours(Settings $settings): int
    {
        return $this->normalizeThreshold(
            $settings->activity_audition_response_due_hours,
            self::DEFAULT_AUDITION_RESPONSE_DUE_HOURS
        );
    }

    private function jobSessionUpcomingHours(Settings $settings): int
    {
        return $this->normalizeThreshold(
            $settings->activity_job_session_upcoming_hours,
            self::DEFAULT_JOB_SESSION_UPCOMING_HOURS
        );
    }

    private function jobDeliveryDueHours(Settings $settings): int
    {
        return $this->normalizeThreshold(
            $settings->activity_job_delivery_due_hours,
            self::DEFAULT_JOB_DELIVERY_DUE_HOURS
        );
    }

    private function invoiceDueSoonDays(Settings $settings): int
    {
        return $this->normalizeThreshold(
            $settings->activity_invoice_due_soon_days,
            self::DEFAULT_INVOICE_DUE_SOON_DAYS
        );
    }

    private function usageRightsExpiringDays(Settings $settings): int
    {
        return $this->normalizeThreshold(
            $settings->activity_usage_rights_expiring_days,
            self::DEFAULT_USAGE_RIGHTS_EXPIRING_DAYS
        );
    }

    private function normalizeThreshold(mixed $value, int $fallback): int
    {
        if (! is_numeric($value)) {
            return $fallback;
        }

        return max(0, (int) $value);
    }

    private function resolveUsageRightOwnerUserId(UsageRight $usageRight): ?string
    {
        if ($usageRight->usable_type === Audition::class) {
            $audition = Audition::query()
                ->withTrashed()
                ->find($usageRight->usable_id);

            return $audition?->user_id;
        }

        if ($usageRight->usable_type === Job::class) {
            $job = Job::query()
                ->withTrashed()
                ->find($usageRight->usable_id);

            return $job?->user_id;
        }

        return null;
    }

    private function usageRightOwnerIsActive(UsageRight $usageRight): bool
    {
        if ($usageRight->usable_type === Audition::class) {
            $audition = Audition::query()
                ->withTrashed()
                ->find($usageRight->usable_id);

            return $audition !== null && ! $audition->trashed();
        }

        if ($usageRight->usable_type === Job::class) {
            $job = Job::query()
                ->withTrashed()
                ->find($usageRight->usable_id);

            return $job !== null && ! $job->trashed();
        }

        return false;
    }
}
