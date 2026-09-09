<?php

use App\Enums\ContributionVisibility;
use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;
use App\Models\Withdrawal;
use Inertia\Testing\AssertableInertia;

test('a guest is sent from the finance page to sign in', function () {
    $this->get(route('dashboard.finance'))->assertRedirect(route('login'));
});

test('an unverified user is sent from the finance page to the verification notice', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('dashboard.finance'))
        ->assertRedirect(route('verification.notice'));
});

test('a verified user reaches the finance page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard.finance'))
        ->assertOk();
});

test('the total is the available part plus the held part', function () {
    $sara = User::factory()->create();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);

    Contribution::factory()->settledAt(now()->subDays(5))->create([
        'wish_id' => $wish->id,
        'amount' => 300_000,
    ]);
    Contribution::factory()->settledAt(now()->subHour())->create([
        'wish_id' => $wish->id,
        'amount' => 120_000,
    ]);

    $this->actingAs($sara)->get(route('dashboard.finance'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('balance.available', 300_000)
            ->where('balance.held', 120_000)
            ->where('balance.total', 420_000)
            ->etc()
    );
});

test('held money becomes available once the hold period passes, with nothing to claim', function () {
    $sara = User::factory()->create();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);

    Contribution::factory()->settledAt(now())->create([
        'wish_id' => $wish->id,
        'amount' => 90_000,
    ]);

    $this->travel(config('withdrawals.hold_hours') + 1)->hours();

    $this->actingAs($sara)->get(route('dashboard.finance'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('balance.available', 90_000)
            ->where('balance.held', 0)
            ->etc()
    );
});

test('a contribution that has not settled counts towards neither figure', function () {
    $sara = User::factory()->create();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);

    Contribution::factory()->pending()->create([
        'wish_id' => $wish->id,
        'amount' => 400_000,
    ]);

    $this->actingAs($sara)->get(route('dashboard.finance'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('balance.total', 0)
            ->where('balance.available', 0)
            ->where('balance.held', 0)
            ->etc()
    );
});

test('a contribution to somebody elses wish never counts', function () {
    $sara = User::factory()->create();

    Contribution::factory()->settledAt(now()->subWeek())->create([
        'wish_id' => Wish::factory()->create()->id,
        'amount' => 800_000,
    ]);
    Contribution::factory()->settledAt(now())->create([
        'wish_id' => Wish::factory()->create()->id,
        'amount' => 600_000,
    ]);

    $this->actingAs($sara)->get(route('dashboard.finance'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('balance.total', 0)->etc()
    );
});

test("a deleted wish's contributions still count toward the balance", function () {
    $sara = User::factory()->create();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);

    Contribution::factory()->settledAt(now()->subWeek())->create([
        'wish_id' => $wish->id,
        'amount' => 250_000,
    ]);

    $wish->delete();

    $this->actingAs($sara)->get(route('dashboard.finance'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('balance.available', 250_000)->etc()
    );
});

test('visibility never affects the balance', function (ContributionVisibility $visibility) {
    $sara = User::factory()->create();

    Contribution::factory()->settledAt(now()->subWeek())->create([
        'wish_id' => Wish::factory()->create(['user_id' => $sara->id])->id,
        'visibility' => $visibility,
        'amount' => 130_000,
    ]);

    $this->actingAs($sara)->get(route('dashboard.finance'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('balance.available', 130_000)->etc()
    );
})->with([
    'public' => ContributionVisibility::Public,
    'owner-only' => ContributionVisibility::OwnerOnly,
    'hidden' => ContributionVisibility::Hidden,
]);

test('an owner with no contributions is shown a zero rather than nothing', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard.finance'))
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('balance.total', 0)
                ->where('balance.available', 0)
                ->where('balance.held', 0)
        );
});

test('the hold period is read from configuration rather than written into the code', function () {
    expect(config('withdrawals.hold_hours'))->toBe(72);

    config(['withdrawals.hold_hours' => 1]);

    $sara = User::factory()->create();

    Contribution::factory()->settledAt(now()->subHours(2))->create([
        'wish_id' => Wish::factory()->create(['user_id' => $sara->id])->id,
        'amount' => 60_000,
    ]);

    $this->actingAs($sara)->get(route('dashboard.finance'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('balance.available', 60_000)
            ->where('balance.held', 0)
            ->etc()
    );
});

test('every withdrawal the owner has made is listed', function () {
    $sara = User::factory()->create();

    Withdrawal::factory()->paid()->create([
        'user_id' => $sara->id,
        'amount' => 100_000,
        'requested_at' => now()->subWeek(),
    ]);
    $newest = Withdrawal::factory()->rejected()->create([
        'user_id' => $sara->id,
        'amount' => 250_000,
        'requested_at' => now()->subDay(),
    ]);

    $this->actingAs($sara)->get(route('dashboard.finance'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('withdrawals', 2)
            ->where('withdrawals.0.id', $newest->id)
            ->where('withdrawals.1.amount', 100_000)
            ->etc()
    );
});

test('each withdrawal row shows the amount, fee, sheba, state and date it was requested', function () {
    $sara = User::factory()->create();

    Withdrawal::factory()->create([
        'user_id' => $sara->id,
        'amount' => 180_000,
        'fee' => 12_500,
        'sheba' => 'IR820540102680020817909002',
    ]);

    $this->actingAs($sara)->get(route('dashboard.finance'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('withdrawals.0.amount', 180_000)
            ->where('withdrawals.0.fee', 12_500)
            ->where('withdrawals.0.sheba', 'IR820540102680020817909002')
            ->where('withdrawals.0.status', 'requested')
            ->has('withdrawals.0.requested_at')
            ->etc()
    );
});

test('the owner sees the state and the decision date once an admin has decided a request', function () {
    $sara = User::factory()->create();
    $withdrawal = Withdrawal::factory()->accepted()->create([
        'user_id' => $sara->id,
        'decided_at' => now()->subHour(),
    ]);

    $this->actingAs($sara)->get(route('dashboard.finance'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('withdrawals.0.status', 'accepted')
            ->whereNot('withdrawals.0.decided_at', null)
            ->etc()
    );
});

test('the owner sees the rejected state and its date on their finance page', function () {
    $sara = User::factory()->create();
    $decidedAt = now()->subHours(3)->startOfSecond();
    Withdrawal::factory()->rejected()->create([
        'user_id' => $sara->id,
        'decided_at' => $decidedAt,
    ]);

    $this->actingAs($sara)->get(route('dashboard.finance'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('withdrawals.0.status', 'rejected')
            ->where('withdrawals.0.decided_at', $decidedAt->toJson())
            ->etc()
    );
});

test('the owner reads the note an admin left beside the request it belongs to', function () {
    $sara = User::factory()->create();
    Withdrawal::factory()->rejected()->create([
        'user_id' => $sara->id,
        'note' => 'شماره شبایی که دادی مال بانک دیگه‌ایه.',
    ]);

    $this->actingAs($sara)->get(route('dashboard.finance'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('withdrawals.0.note', 'شماره شبایی که دادی مال بانک دیگه‌ایه.')
            ->etc()
    );
});

test('a withdrawal nobody has written a note on carries an empty note', function () {
    $sara = User::factory()->create();
    Withdrawal::factory()->create(['user_id' => $sara->id]);

    $this->actingAs($sara)->get(route('dashboard.finance'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('withdrawals.0.note', '')->etc()
    );
});

test('one owner cannot read the notes on another owner\'s withdrawals', function () {
    $sara = User::factory()->create();
    Withdrawal::factory()->rejected()->create([
        'user_id' => User::factory()->create()->id,
        'note' => 'شبات درست نیست.',
    ]);

    $this->actingAs($sara)->get(route('dashboard.finance'))->assertInertia(
        fn (AssertableInertia $page) => $page->has('withdrawals', 0)
    );
});

test('an owner cannot write a note through the request form', function () {
    $sara = ownerWithAvailable(500_000);

    $this->actingAs($sara)->post(route('dashboard.withdrawals.store'), [
        'amount' => 100_000,
        'sheba' => 'IR820540102680020817909002',
        'note' => 'خودم نوشتم',
    ])->assertRedirect();

    expect(Withdrawal::query()->sole()->note)->toBe('');
});
