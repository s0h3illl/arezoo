<?php

use App\Models\Contribution;
use App\Models\User;

test('admin dashboard renders without javascript errors', function () {
    $this->actingAs(User::factory()->admin()->create());
    Contribution::factory()->paid()->create(['amount' => 250_000]);

    $page = visit(route('admin.dashboard', absolute: false));

    // The Persian copy only exists once Vue mounts, so seeing it is what stops
    // a missing or broken bundle from passing the error assertion vacuously.
    // The money figure proves the tile went through the app's Toman formatting
    // rather than printing a bare number.
    $page->assertNoJavaScriptErrors()
        ->assertSee('داشبورد')
        ->assertSee('کاربران')
        ->assertSee('آرزوها')
        ->assertSee('مجموع جمع‌آوری‌شده')
        ->assertSee('تومان');
});
