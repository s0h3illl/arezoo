<?php

namespace Database\Factories;

use App\Enums\ContributionStatus;
use App\Enums\ContributionVisibility;
use App\Models\Contribution;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wish;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contribution>
 */
class ContributionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Each contribution gets a payment of its own, carrying the same amount —
     * the two are created together and never shared. Amounts are Toman, kept
     * within the wish factory's price range so funding levels stay plausible.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wish_id' => Wish::factory(),
            'contributor_id' => User::factory(),
            'amount' => fake()->numberBetween(5, 500) * 1_000,
            'payment_id' => fn (array $attributes) => Payment::factory()->state([
                'amount' => $attributes['amount'],
            ]),
            'message' => null,
            'visibility' => ContributionVisibility::Public,
            'status' => ContributionStatus::Pending,
        ];
    }

    /**
     * Indicate that the contributor has not returned from the gateway yet.
     */
    public function pending(): static
    {
        return $this->state([
            'status' => ContributionStatus::Pending,
        ]);
    }

    /**
     * Indicate that the money landed, paired with the verified payment that carried it.
     */
    public function paid(): static
    {
        return $this->state([
            'payment_id' => fn (array $attributes) => Payment::factory()->verified()->state([
                'amount' => $attributes['amount'],
            ]),
            'status' => ContributionStatus::Paid,
            'settled_at' => now(),
        ]);
    }

    /**
     * Indicate that the money landed at a specific moment.
     *
     * Lets a test place a contribution on either side of a time boundary —
     * the held/available boundary in ADR-0006, for instance — rather than
     * only ever settling "now".
     */
    public function settledAt(DateTimeInterface $when): static
    {
        return $this->paid()->state([
            'settled_at' => $when,
        ]);
    }
}
