<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuthCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuthCode>
 */
class AuthCodeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = AuthCode::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        return [
            'user_id' => User::factory(),
            'purpose' => AuthCode::PURPOSE_AUTH,
            'code_hash' => hash('sha256', $code),
            'expires_at' => now()->addMinutes(10),
            'used_at' => null,
        ];
    }
}
