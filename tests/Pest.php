<?php

use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function signInAsAdmin(): User
{
    $admin = User::factory()->admin()->create();

    test()->actingAs($admin);

    return $admin;
}

function ownerWithAvailable(int $amount): User
{
    $owner = User::factory()->create();

    Contribution::factory()->settledAt(now()->subWeek())->create([
        'wish_id' => Wish::factory()->create(['user_id' => $owner->id])->id,
        'amount' => $amount,
    ]);

    return $owner;
}

/**
 * What the finance page reports as available to its owner.
 */
function availableFor(User $owner): int
{
    $available = 0;

    test()->actingAs($owner)->get(route('dashboard.finance'))->assertInertia(
        function (AssertableInertia $page) use (&$available) {
            $available = $page->toArray()['props']['balance']['available'];
        }
    );

    return $available;
}
