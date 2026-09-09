<?php

namespace Database\Factories;

use App\Enums\WithdrawalStatus;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Withdrawal>
 */
class WithdrawalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => fake()->numberBetween(20, 500) * 1_000,
            'fee' => (int) config('withdrawals.fee'),
            'sheba' => 'IR062960000000100324200001',
            'status' => WithdrawalStatus::Requested,
            'note' => '',
            'requested_at' => now(),
            'decided_at' => null,
        ];
    }

    public function requested(): static
    {
        return $this->state([
            'status' => WithdrawalStatus::Requested,
            'decided_at' => null,
        ]);
    }

    public function accepted(): static
    {
        return $this->state([
            'status' => WithdrawalStatus::Accepted,
            'decided_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state([
            'status' => WithdrawalStatus::Paid,
            'decided_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => WithdrawalStatus::Rejected,
            'decided_at' => now(),
        ]);
    }
}
