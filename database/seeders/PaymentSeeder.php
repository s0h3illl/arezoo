<?php

namespace Database\Seeders;

use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Round out the payments table with attempts that never became a contribution.
     *
     * ContributionSeeder already seeded a Verified payment for every paid
     * contribution. What's missing is the other two outcomes: an attempt still
     * waiting on the gateway (Pending), and one the gateway turned down
     * (Failed) — whose contribution row was deleted per ADR-0001, leaving only
     * this payment behind. Both are standalone rows with no contribution.
     */
    public function run(): void
    {
        Payment::factory()
            ->count(fake()->numberBetween(5, 12))
            ->pending()
            ->create();

        Payment::factory()
            ->count(fake()->numberBetween(15, 30))
            ->failed()
            ->create();
    }
}
