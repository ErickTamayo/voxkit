<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessProfile>
 */
class BusinessProfileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<BusinessProfile>
     */
    protected $model = BusinessProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->createQuietly();

        return [
            'user_id' => $user->id,
            'business_name' => fake()->optional()->company(),
            'address_street' => fake()->optional()->streetAddress(),
            'address_city' => fake()->optional()->city(),
            'address_state' => fake()->optional()->state(),
            'address_country' => fake()->optional()->countryCode(),
            'address_postal' => fake()->optional()->postcode(),
            'phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->companyEmail(),
            'payment_instructions' => fake()->optional()->paragraph(),
            'logo_path' => fake()->optional()->filePath(),
        ];
    }
}
