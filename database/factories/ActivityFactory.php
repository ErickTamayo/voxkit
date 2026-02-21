<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'targetable_type' => null, // Set in seeder/test
            'targetable_id' => null, // Set in seeder/test
            'trigger' => fake()->randomElement([
                'audition_response_due',
                'job_session_upcoming',
                'job_delivery_due',
                'job_revision_requested',
                'invoice_due_soon',
                'invoice_overdue',
                'usage_rights_expiring',
            ]),
            'action' => fake()->optional()->randomElement(['snoozed', 'archived']),
            'snoozed_until' => null,
        ];
    }
}
