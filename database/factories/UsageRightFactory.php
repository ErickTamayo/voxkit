<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Job;
use App\Models\UsageRight;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsageRight>
 */
class UsageRightFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<UsageRight>
     */
    protected $model = UsageRight::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usable_type' => Job::class,
            'usable_id' => Job::factory(),
            'type' => fake()->randomElement(['broadcast', 'non_broadcast']),
            'media_types' => fake()->randomElements([
                'tv',
                'radio',
                'digital',
                'social_media',
                'streaming',
                'cinema',
                'print',
                'outdoor',
                'internal',
                'podcast',
                'video_game',
                'all_media',
            ], fake()->numberBetween(1, 4)),
            'geographic_scope' => fake()->randomElement(['local', 'regional', 'national', 'multi_national', 'worldwide']),
            'duration_type' => fake()->randomElement(['fixed', 'perpetual']),
            'duration_months' => fake()->optional()->numberBetween(1, 36),
            'start_date' => fake()->optional()->date(),
            'expiration_date' => fake()->optional()->date(),
            'exclusivity' => fake()->boolean(30),
            'exclusivity_category' => fake()->optional()->word(),
            'ai_rights_granted' => fake()->boolean(20),
            'renewal_reminder_sent' => false,
        ];
    }
}
