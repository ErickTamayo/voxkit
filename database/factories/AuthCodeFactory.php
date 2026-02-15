<?php

namespace Database\Factories;

use App\Models\AuthCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuthCode>
 */
class AuthCodeFactory extends Factory
{
    protected $model = AuthCode::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'purpose' => AuthCode::PURPOSE_AUTH,
            'code_hash' => hash('sha256', '123456'),
            'expires_at' => now()->addMinutes(10),
            'used_at' => null,
            'attempts' => 0,
        ];
    }
}
