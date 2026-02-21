<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Contact>
     */
    protected $model = Contact::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'contactable_type' => Client::class,
            'contactable_id' => Client::factory(),
            'name' => fake()->name(),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'phone_ext' => fake()->optional()->numerify('###'),
            'address_street' => fake()->optional()->streetAddress(),
            'address_city' => fake()->optional()->city(),
            'address_state' => fake()->optional()->state(),
            'address_country' => fake()->optional()->countryCode(),
            'address_postal' => fake()->optional()->postcode(),
            'last_contacted_at' => fake()->optional()->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
