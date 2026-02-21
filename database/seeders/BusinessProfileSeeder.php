<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class BusinessProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $createdCount = 0;

        User::query()
            ->select('id')
            ->chunkById(200, function ($users) use (&$createdCount) {
                foreach ($users as $user) {
                    $profile = BusinessProfile::query()->firstOrCreate([
                        'user_id' => $user->id,
                    ]);

                    if ($profile->wasRecentlyCreated) {
                        $createdCount++;
                    }
                }
            });

        $this->command?->info("Ensured business profiles for all users ({$createdCount} created).");
    }
}
