<?php

use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;
use App\Models\Withdrawal;

test('an owner can visit the page without any error', function () {
    $sara = User::factory()->create();

    Contribution::factory()->settledAt(now())->create([
        'wish_id' => Wish::factory()->create(['user_id' => $sara->id])->id,
        'amount' => 200_000,
    ]);

    $this->actingAs($sara);

    visit(route('dashboard.finance', absolute: false))
        ->assertNoJavaScriptErrors()
        ->assertSee('۲۰۰٬۰۰۰ تومان');
});

test('an owner who has earned nothing is told so rather than shown a blank page', function () {
    $this->actingAs(User::factory()->create());

    visit(route('dashboard.finance', absolute: false))
        ->assertSee('۰ تومان')
        ->assertPresent('@no-earnings');
});

test('an owner sees the requests they have already made', function () {
    $sara = User::factory()->create();

    Contribution::factory()->settledAt(now()->subWeek())->create([
        'wish_id' => Wish::factory()->create(['user_id' => $sara->id])->id,
        'amount' => 500_000,
    ]);

    Withdrawal::factory()->requested()->create([
        'user_id' => $sara->id,
        'amount' => 150_000,
        'sheba' => 'IR820540102680020817909002',
    ]);

    $this->actingAs($sara);

    visit(route('dashboard.finance', absolute: false))
        ->assertNoJavaScriptErrors()
        ->assertPresent('@withdrawal-row')
        ->assertSee('IR820540102680020817909002')
        ->assertSee('در انتظار بررسی')
        ->assertPresent('@open-request-notice');
});

test('an owner who has withdrawn everything sees their history rather than the never-earned note', function () {
    $sara = User::factory()->create();

    Contribution::factory()->settledAt(now()->subWeek())->create([
        'wish_id' => Wish::factory()->create(['user_id' => $sara->id])->id,
        'amount' => 200_000,
    ]);

    Withdrawal::factory()->paid()->create([
        'user_id' => $sara->id,
        'amount' => 200_000,
    ]);

    $this->actingAs($sara);

    visit(route('dashboard.finance', absolute: false))
        ->assertNoJavaScriptErrors()
        ->assertNotPresent('@no-earnings')
        ->assertPresent('@withdrawal-row');
});

test('the owner reads the note on the request it belongs to', function () {
    $sara = User::factory()->create();
    $message = 'message from admin';

    Withdrawal::factory()->rejected()->create([
        'user_id' => $sara->id,
        'note' => $message,
    ]);

    $this->actingAs($sara);

    visit(route('dashboard.finance', absolute: false))
        ->assertNoJavaScriptErrors()
        ->assertSee($message);
});

test('a request nobody wrote a note on renders no note element at all', function () {
    $sara = User::factory()->create();

    Withdrawal::factory()->rejected()->create(['user_id' => $sara->id]);

    $this->actingAs($sara);

    visit(route('dashboard.finance', absolute: false))
        ->assertPresent('@withdrawal-row')
        ->assertNotPresent('@withdrawal-note');
});
