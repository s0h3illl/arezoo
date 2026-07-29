<?php

namespace Database\Seeders;

use App\Enums\ContributionVisibility;
use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;
use Database\Factories\ContributionFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class ContributionSeeder extends Seeder
{
    /**
     * The share of a wish's price that ends up paid, picked per wish.
     *
     * A zero leaves the wish untouched, and a share above one lets it
     * overshoot its price — which the domain deliberately allows, see ADR-0004.
     *
     * @var list<float>
     */
    protected array $fundingShares = [0.0, 0.0, 0.2, 0.45, 0.8, 1.0, 1.3];

    /**
     * Seed each wish with a plausible funding history.
     */
    public function run(): void
    {
        $users = User::all();

        Wish::all()->each(function (Wish $wish) use ($users): void {
            $contributors = $users->reject(
                fn (User $user): bool => $user->getKey() === $wish->user_id
            );

            if ($contributors->isEmpty()) {
                return;
            }

            $this->fund($wish, $contributors);
        });
    }

    /**
     * Pay a share of the wish's price, spread across a handful of contributors.
     *
     * @param  Collection<int, User>  $contributors
     */
    protected function fund(Wish $wish, Collection $contributors): void
    {
        $share = fake()->randomElement($this->fundingShares);

        if ($share <= 0) {
            return;
        }

        $target = (int) round($wish->price * $share / 1_000) * 1_000;

        $chosen = $contributors->random(fake()->numberBetween(
            1, min(4, $contributors->count(), max(1, intdiv($target, 1_000)))
        ));

        foreach ($this->split($target, $chosen->count()) as $index => $amount) {
            $this->contribute($wish, $chosen[$index], $amount)->paid()->create();
        }

        if (fake()->boolean(25)) {
            $this->contribute($wish, $contributors->random())->create();
        }
    }

    /**
     * Start a contribution towards a wish, with a believable message and visibility.
     *
     * Leaving the amount out lets the factory pick one from its own Toman range.
     */
    protected function contribute(Wish $wish, User $contributor, ?int $amount = null): ContributionFactory
    {
        $state = [
            'message' => fake()->boolean(40) ? fake()->sentence() : null,
            'visibility' => fake()->randomElement([
                ContributionVisibility::Public,
                ContributionVisibility::Public,
                ContributionVisibility::OwnerOnly,
                ContributionVisibility::Hidden,
            ]),
        ];

        if ($amount !== null) {
            $state['amount'] = $amount;
        }

        return Contribution::factory()
            ->for($wish)
            ->for($contributor, 'contributor')
            ->state($state);
    }

    /**
     * Split a total into whole-thousand Toman amounts, one per contributor.
     *
     * @return list<int>
     */
    protected function split(int $total, int $count): array
    {
        $amounts = [];
        $remaining = $total;

        for ($left = $count; $left > 1; $left--) {
            $amount = max(1_000, (int) round($remaining / $left / 1_000) * 1_000);

            $amounts[] = $amount;
            $remaining -= $amount;
        }

        $amounts[] = max(1_000, $remaining);

        return $amounts;
    }
}
