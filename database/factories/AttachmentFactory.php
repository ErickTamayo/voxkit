<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Attachment>
     */
    protected $model = Attachment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'attachable_type' => Job::class,
            'attachable_id' => Job::factory(),
            'filename' => fake()->uuid().'.pdf',
            'original_filename' => fake()->words(2, true).'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(512, 5_000_000),
            'disk' => fake()->randomElement(['local', 's3']),
            'path' => fake()->filePath(),
            'category' => fake()->randomElement(['script', 'contract', 'other']),
            'metadata' => fake()->optional()->randomElement([
                ['source' => 'upload'],
                ['source' => 'import', 'version' => 1],
            ]),
        ];
    }
}
