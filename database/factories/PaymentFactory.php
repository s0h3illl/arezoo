<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => fake()->numberBetween(5, 500) * 1_000,
            'transaction_id' => (string) fake()->unique()->numberBetween(1_000_000, 9_999_999),
            'reference_id' => null,
            'status' => PaymentStatus::Pending,
        ];
    }
}
