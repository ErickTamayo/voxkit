<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class SyncActivitiesCommand extends Command
{
    protected $signature = 'app:sync-activities {--user= : Sync a single user by ULID}';

    protected $description = 'Sync activities from entity status/date conditions';

    public function handle(ActivityService $activityService): int
    {
        $userId = $this->option('user');

        $query = User::query()->orderBy('id');
        if (is_string($userId) && $userId !== '') {
            $query->where('id', $userId);
        }

        $processed = 0;

        $query->chunk(100, function ($users) use ($activityService, &$processed): void {
            foreach ($users as $user) {
                $activityService->syncForUser($user);
                $processed++;
            }
        });

        $this->components->info("Synced activities for {$processed} user(s).");

        return SymfonyCommand::SUCCESS;
    }
}
