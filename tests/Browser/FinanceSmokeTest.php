<?php

use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;

test('an owner can visit the finance page without any error', function () {
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
        ->assertNoJavaScriptErrors()
        ->assertSee('۰ تومان')
        ->assertPresent('@no-earnings');
});
