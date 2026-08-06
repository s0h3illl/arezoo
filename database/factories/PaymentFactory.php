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

    /**
     * Indicate that the attempt reached the gateway and never came back.
     *
     * The state `definition()` already produces, named so a test can say which
     * of the three endings it means rather than relying on the default.
     */
    public function pending(): static
    {
        return $this->state([
            'reference_id' => null,
            'status' => PaymentStatus::Pending,
        ]);
    }

    /**
     * Indicate that the gateway confirmed the attempt and handed back a reference.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'reference_id' => (string) fake()->unique()->numberBetween(1_000_000, 9_999_999),
            'status' => PaymentStatus::Verified,
        ]);
    }

    /**
     * Indicate that the attempt failed and left no contribution behind.
     *
     * The gateway never handed back a reference, so there is none to record —
     * the transaction identifier is all a user's complaint can be matched
     * against. Created on its own, this is the orphan attempt of ADR-0001.
     */
    public function failed(): static
    {
        return $this->state([
            'reference_id' => null,
            'status' => PaymentStatus::Failed,
        ]);
    }
}
