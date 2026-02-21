<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Invoice>
     */
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issuedAt = fake()->dateTimeBetween('-90 days', 'now');
        $dueAt = (clone $issuedAt)->modify('+30 days');
        $subtotal = fake()->numberBetween(1000, 500000);
        $taxRate = fake()->optional()->randomFloat(4, 0, 0.2);
        $taxAmount = $taxRate === null ? null : (int) round($subtotal * $taxRate);
        $total = $subtotal + ($taxAmount ?? 0);

        $currency = fake()->currencyCode();

        return [
            'user_id' => User::factory(),
            'job_id' => fake()->boolean(60) ? Job::factory() : null,
            'client_id' => Contact::factory(),
            'invoice_number' => strtoupper(Str::random(10)),
            'issued_at' => $issuedAt,
            'due_at' => $dueAt,
            'subtotal' => [
                'amount_cents' => $subtotal,
                'currency' => $currency,
            ],
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount !== null ? [
                'amount_cents' => $taxAmount,
                'currency' => $currency,
            ] : null,
            'total' => [
                'amount_cents' => $total,
                'currency' => $currency,
            ],
            'status' => fake()->randomElement(['draft', 'sent', 'paid', 'overdue', 'cancelled']),
            'paid_at' => fake()->boolean(40) ? fake()->dateTimeBetween($issuedAt, 'now') : null,
        ];
    }
}
