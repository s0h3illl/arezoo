<?php

namespace Database\Seeders;

use App\Enums\ContributionVisibility;
use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;
use Illuminate\Database\Seeder;

class ContributionSeeder extends Seeder
{
    /**
     * Give most wishes a handful of paid contributions, leaving the rest untouched.
     *
     * Funding levels fall out of the amounts rather than being aimed for: a cheap
     * wish sails past its price while an expensive one stays short. Overshooting
     * is deliberate — see ADR-0004.
     */
    public function run(): void
    {
        $users = User::all();

        Wish::all()->each(function (Wish $wish) use ($users): void {
            $others = $users->reject(
                fn (User $user): bool => $user->getKey() === $wish->user_id
            );

            // 25% chance to skip — leaves roughly 3 in 4 wishes funded.
            if ($others->isEmpty() || fake()->boolean(25)) {
                return;
            }

            $contributors = $others->random(
                fake()->numberBetween(1, min(6, $others->count()))
            );

            foreach ($contributors as $contributor) {
                Contribution::factory()
                    ->for($wish)
                    ->for($contributor, 'contributor')
                    ->paid()
                    ->create([
                        'amount' => fake()->numberBetween(100, 400) * 1_000,
                        'visibility' => fake()->randomElement(ContributionVisibility::cases()),
                        'message' => fake()->boolean(40) ? fake()->sentence() : null,
                    ]);
            }
        });
    }
}
