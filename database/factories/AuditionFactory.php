<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Audition;
use App\Models\Contact;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Audition>
 */
class AuditionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Audition>
     */
    protected $model = Audition::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $usePlatform = fake()->boolean();

        return [
            'user_id' => User::factory(),
            'sourceable_type' => $usePlatform ? Platform::class : Contact::class,
            'sourceable_id' => $usePlatform ? Platform::factory() : Contact::factory(),
            'source_reference' => fake()->optional()->uuid(),
            'project_title' => fake()->sentence(3),
            'brand_name' => fake()->optional()->company(),
            'character_name' => fake()->optional()->firstName(),
            'category' => fake()->randomElement([
                'commercial',
                'animation',
                'video_game',
                'audiobook',
                'elearning',
                'corporate',
                'documentary',
                'narration',
                'promo',
                'trailer',
                'radio_imaging',
                'ivr',
                'explainer',
                'podcast',
                'dubbing',
                'announcement',
                'meditation',
                'tv_series',
                'film',
                'coaching',
                'other',
            ]),
            'word_count' => fake()->optional()->numberBetween(50, 1000),
            'budget_min' => fake()->optional()->numberBetween(5_000, 50_000),
            'budget_max' => fake()->optional()->numberBetween(50_000, 200_000),
            'quoted_rate' => fake()->optional()->numberBetween(5_000, 200_000),
            'rate_type' => fake()->randomElement([
                'flat',
                'hourly',
                'per_finished_hour',
                'per_word',
                'per_line',
                'buyout',
            ]),
            'response_deadline' => fake()->optional()->dateTimeBetween('now', '+2 weeks'),
            'project_deadline' => fake()->optional()->dateTimeBetween('+2 weeks', '+2 months'),
            'status' => fake()->randomElement([
                'received',
                'preparing',
                'submitted',
                'shortlisted',
                'callback',
                'won',
                'lost',
                'expired',
            ]),
        ];
    }
}
