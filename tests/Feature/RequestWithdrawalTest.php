<?php

use App\Actions\RequestWithdrawal;
use App\Enums\WithdrawalStatus;
use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;

function sheba(): string
{
    return 'IR062960000000100324200001';
}

test('a guest cannot request a withdrawal', function () {
    $this->post(route('dashboard.withdrawals.store'), [
        'amount' => 50_000,
        'sheba' => sheba(),
    ])->assertRedirect(route('login'));

    expect(Withdrawal::count())->toBe(0);
});

test('an unverified user cannot request a withdrawal', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->post(route('dashboard.withdrawals.store'), [
            'amount' => 50_000,
            'sheba' => sheba(),
        ])
        ->assertRedirect(route('verification.notice'));

    expect(Withdrawal::count())->toBe(0);
});

test('an owner can request a withdrawal for an amount they choose', function () {
    $sara = ownerWithAvailable(500_000);

    $this->actingAs($sara)
        ->post(route('dashboard.withdrawals.store'), [
            'amount' => 200_000,
            'sheba' => sheba(),
        ])
        ->assertRedirect(route('dashboard.finance'));

    $withdrawal = Withdrawal::sole();

    expect($withdrawal->user_id)->toBe($sara->id)
        ->and($withdrawal->amount)->toBe(200_000)
        ->and($withdrawal->fee)->toBe(12_500)
        ->and($withdrawal->sheba)->toBe(sheba())
        ->and($withdrawal->status)->toBe(WithdrawalStatus::Requested)
        ->and($withdrawal->note)->toBe('')
        ->and($withdrawal->requested_at)->not->toBeNull()
        ->and($withdrawal->decided_at)->toBeNull();
});

test('an owner can request their entire available balance in one action', function () {
    $sara = ownerWithAvailable(340_000);

    $this->actingAs($sara)
        ->post(route('dashboard.withdrawals.store'), [
            'amount' => 340_000,
            'sheba' => sheba(),
        ])
        ->assertSessionHasNoErrors();

    expect(Withdrawal::sole()->amount)->toBe(340_000);

    $this->get(route('dashboard.finance'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('balance.available', 0)->etc()
    );
});

test('a sheba is accepted only when its prefix, length and check digits are right', function (string $sheba, bool $isValid) {
    $sara = ownerWithAvailable(500_000);

    $response = $this->actingAs($sara)->post(route('dashboard.withdrawals.store'), [
        'amount' => 100_000,
        'sheba' => $sheba,
    ]);

    if ($isValid) {
        $response->assertSessionHasNoErrors();
        expect(Withdrawal::count())->toBe(1);
    } else {
        $response->assertSessionHasErrors('sheba');
        expect(Withdrawal::count())->toBe(0);
    }
})->with([
    'a real sheba' => [sheba(), true],
    'another real sheba' => ['IR820540102680020817909002', true],
    'a third real sheba' => ['IR580540105180021273113007', true],
    'one digit changed' => ['IR820540102680020817909003', false],
    'two digits transposed' => ['IR820540102680020817990002', false],
    'no IR prefix' => ['062960000000100324200001', false],
    'a lowercase prefix' => ['ir062960000000100324200001', false],
    'too short' => ['IR06296000000010032420000', false],
    'too long' => ['IR0629600000001003242000011', false],
    'letters in the body' => ['IR06296000000010032420000A', false],
    'spaces between groups' => ['IR06 2960 0000 0010 0324 2000 01', false],
    'empty' => ['', false],
]);

test('the fee is copied onto the withdrawal and a later config change never re-prices it', function () {
    $sara = ownerWithAvailable(500_000);

    $this->actingAs($sara)->post(route('dashboard.withdrawals.store'), [
        'amount' => 100_000,
        'sheba' => sheba(),
    ]);

    config(['withdrawals.fee' => 40_000]);

    expect(Withdrawal::sole()->fee)->toBe(12_500);

    $this->get(route('dashboard.finance'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('withdrawals.0.fee', 12_500)
            ->where('fee', 40_000)
            ->etc()
    );
});

test('the fee and the minimum request live in configuration', function () {
    expect(config('withdrawals.fee'))->toBe(12_500)
        ->and(config('withdrawals.minimum'))->toBe(15_000);
});

test('a request below the minimum is refused', function (int $amount) {
    $sara = ownerWithAvailable(500_000);

    $this->actingAs($sara)
        ->post(route('dashboard.withdrawals.store'), [
            'amount' => $amount,
            'sheba' => sheba(),
        ])
        ->assertSessionHasErrors(['amount' => __('withdrawals.below_minimum')]);

    expect(Withdrawal::count())->toBe(0);
})->with([
    'nothing' => 0,
    'one Toman' => 1,
    'the fee itself' => 12_500,
    'one Toman under the minimum' => 14_999,
]);

test('a request at exactly the minimum is accepted', function () {
    $sara = ownerWithAvailable(500_000);

    $this->actingAs($sara)
        ->post(route('dashboard.withdrawals.store'), [
            'amount' => 15_000,
            'sheba' => sheba(),
        ])
        ->assertSessionHasNoErrors();

    expect(Withdrawal::sole()->amount)->toBe(15_000);
});

test('a request above the available balance is refused as held money', function () {
    $sara = User::factory()->create();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);

    Contribution::factory()->settledAt(now()->subWeek())->create([
        'wish_id' => $wish->id,
        'amount' => 100_000,
    ]);
    Contribution::factory()->settledAt(now())->create([
        'wish_id' => $wish->id,
        'amount' => 900_000,
    ]);

    $this->actingAs($sara)
        ->post(route('dashboard.withdrawals.store'), [
            'amount' => 200_000,
            'sheba' => sheba(),
        ])
        ->assertSessionHasErrors(['amount' => __('withdrawals.insufficient')]);

    expect(Withdrawal::count())->toBe(0);
});

test('a requested withdrawal counts against the balance immediately', function () {
    $sara = ownerWithAvailable(500_000);

    $this->actingAs($sara)->post(route('dashboard.withdrawals.store'), [
        'amount' => 200_000,
        'sheba' => sheba(),
    ]);

    $this->get(route('dashboard.finance'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('balance.available', 300_000)
            ->where('balance.total', 300_000)
            ->etc()
    );
});

test('a second request while one is open is refused rather than queued', function (string $state) {
    $sara = ownerWithAvailable(500_000);

    Withdrawal::factory()->{$state}()->create([
        'user_id' => $sara->id,
        'amount' => 20_000,
    ]);

    $this->actingAs($sara)
        ->post(route('dashboard.withdrawals.store'), [
            'amount' => 50_000,
            'sheba' => sheba(),
        ])
        ->assertSessionHasErrors(['amount' => __('withdrawals.already_open')]);

    expect(Withdrawal::count())->toBe(1);
})->with(['requested', 'accepted']);

test('an accepted withdrawal reserves its amount exactly as a paid one does', function (string $state) {
    $sara = ownerWithAvailable(500_000);

    Withdrawal::factory()->{$state}()->create([
        'user_id' => $sara->id,
        'amount' => 120_000,
    ]);

    $this->actingAs($sara)->get(route('dashboard.finance'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('balance.available', 380_000)->etc()
    );
})->with(['requested', 'accepted', 'paid']);

test('a rejected withdrawal releases its money and lets the owner ask again', function () {
    $sara = ownerWithAvailable(500_000);

    Withdrawal::factory()->rejected()->create([
        'user_id' => $sara->id,
        'amount' => 120_000,
    ]);

    $this->actingAs($sara)->get(route('dashboard.finance'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('balance.available', 500_000)->etc()
    );

    $this->post(route('dashboard.withdrawals.store'), [
        'amount' => 500_000,
        'sheba' => sheba(),
    ])->assertSessionHasNoErrors();
});

test('one user cannot request a withdrawal against another users balance', function () {
    ownerWithAvailable(900_000);

    $reza = User::factory()->create();

    $this->actingAs($reza)
        ->post(route('dashboard.withdrawals.store'), [
            'amount' => 100_000,
            'sheba' => sheba(),
        ])
        ->assertSessionHasErrors(['amount' => __('withdrawals.insufficient')]);

    expect(Withdrawal::count())->toBe(0);
});

test('two requests for the same money cannot both succeed', function () {
    $sara = ownerWithAvailable(200_000);

    $refusal = null;

    Event::listen('eloquent.created: '.Withdrawal::class, function () use ($sara, &$refusal): void {
        if ($refusal !== null) {
            return;
        }

        try {
            $refusal = 'none';
            RequestWithdrawal::for($sara, 200_000, sheba());
        } catch (ValidationException $exception) {
            $refusal = $exception->getMessage();
        }
    });

    $this->actingAs($sara)
        ->post(route('dashboard.withdrawals.store'), [
            'amount' => 200_000,
            'sheba' => sheba(),
        ])
        ->assertSessionHasNoErrors();

    expect(Withdrawal::count())->toBe(1)
        ->and($refusal)->toContain(__('withdrawals.already_open'));

    $this->get(route('dashboard.finance'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('balance.available', 0)->etc()
    );
});
