<?php

use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;

test('admin contributions list renders without javascript errors', function () {
    $this->actingAs(User::factory()->admin()->create());
    $wish = Wish::factory()->create(['title' => 'دوچرخه']);
    Contribution::factory()->for($wish)->paid()->create();
    Contribution::factory()->pending()->create();

    $page = visit(route('admin.contributions.index', absolute: false));

    // The Persian copy only exists once Vue mounts, so seeing it is what stops
    // a missing or broken bundle from passing the error assertion vacuously.
    $page->assertNoJavaScriptErrors()
        ->assertSee('مشارکت‌ها')
        ->assertSee('دوچرخه');
});

test('an empty list explains itself rather than showing a blank table', function () {
    $this->actingAs(User::factory()->admin()->create());

    $page = visit(route('admin.contributions.index', absolute: false));

    $page->assertNoJavaScriptErrors()
        ->assertSee('هنوز مشارکتی ثبت نشده');
});
