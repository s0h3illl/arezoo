<?php

use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;

test('admin dashboard renders without javascript errors', function () {
    $this->actingAs(User::factory()->admin()->create());
    Contribution::factory()->paid()->create(['amount' => 250_000]);
    Wish::factory()->create(['title' => 'دوچرخه‌ی کوهستان']);

    $page = visit(route('admin.dashboard', absolute: false));

    // The Persian copy only exists once Vue mounts, so seeing it is what stops
    // a missing or broken bundle from passing the error assertion vacuously.
    // The money figure proves the tile went through the app's Toman formatting
    // rather than printing a bare number.
    $page->assertNoJavaScriptErrors()
        ->assertSee('داشبورد')
        /*
         * Anchored to the tiles rather than the page. «آرزوها» now titles the
         * table below as well, so an unanchored assertion would be satisfied by
         * the heading and the wishes tile could disappear undetected.
         */
        ->assertSeeIn('#platform-totals', 'کاربران')
        ->assertSeeIn('#platform-totals', 'آرزوها')
        ->assertSeeIn('#platform-totals', 'مجموع جمع‌آوری‌شده')
        ->assertSeeIn('#platform-totals', 'تومان')
        // The wishes table, headers and row alike — a row proves the loop ran,
        // and the title proves the props reached it.
        ->assertSeeIn('#latest-wishes', 'صاحب آرزو')
        ->assertSeeIn('#latest-wishes', 'زمان انتشار')
        ->assertSeeIn('#latest-wishes', 'دوچرخه‌ی کوهستان');
});

test('the wishes table explains itself when no wish has been published', function () {
    $this->actingAs(User::factory()->admin()->create());

    $page = visit(route('admin.dashboard', absolute: false));

    $page->assertNoJavaScriptErrors()
        ->assertSeeIn('#latest-wishes', 'هنوز آرزویی منتشر نشده')
        ->assertDontSee('صاحب آرزو');
});
