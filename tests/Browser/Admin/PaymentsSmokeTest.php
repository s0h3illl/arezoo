<?php

use App\Models\Contribution;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wish;

test('the admin payments list renders without javascript errors', function () {
    $this->actingAs(User::factory()->admin()->create());
    $wish = Wish::factory()->create(['title' => 'دوچرخه']);
    Contribution::factory()->for($wish)->paid()->create();
    Payment::factory()->failed()->create(['transaction_id' => '4821990']);

    $page = visit(route('admin.payments.index', absolute: false));

    // The Persian copy only exists once Vue mounts, so seeing it is what stops
    // a missing or broken bundle from passing the error assertion vacuously.
    $page->assertNoJavaScriptErrors()
        ->assertSee('پرداخت‌ها')
        ->assertSee('4821990')
        ->assertSee('دوچرخه');
});

/*
| The attempt that left nothing behind is the whole reason for the screen — it
| has to read as an attempt with no contribution, not as a blank cell.
*/
test('a failed attempt says it left no contribution behind', function () {
    $this->actingAs(User::factory()->admin()->create());
    Payment::factory()->failed()->create(['amount' => 85_000]);

    $page = visit(route('admin.payments.index', absolute: false));

    $page->assertNoJavaScriptErrors()
        ->assertSee('ناموفق')
        ->assertSee('مشارکتی ازش نمونده')
        ->assertSee('تومان');
});

/*
| An attempt still waiting on the gateway may yet land, so it must not read as
| one the gateway turned down. Both words on screen at once is the assertion.
*/
test('a pending attempt reads differently from a failed one', function () {
    $this->actingAs(User::factory()->admin()->create());
    Payment::factory()->pending()->create();
    Payment::factory()->failed()->create();

    $page = visit(route('admin.payments.index', absolute: false));

    $page->assertNoJavaScriptErrors()
        ->assertSee('در انتظار درگاه')
        ->assertSee('ناموفق');
});

test('an empty list explains itself rather than showing a blank table', function () {
    $this->actingAs(User::factory()->admin()->create());

    $page = visit(route('admin.payments.index', absolute: false));

    $page->assertNoJavaScriptErrors()
        ->assertSee('هنوز پرداختی ثبت نشده');
});

/*
| The way an admin answers "I paid and nothing happened": type the identifier
| the user was given, and see the attempt behind it.
*/
test('an admin finds a failed attempt by typing its transaction identifier', function () {
    $this->actingAs(User::factory()->admin()->create());
    Payment::factory()->failed()->create(['transaction_id' => '4821990']);
    Payment::factory()->failed()->create(['transaction_id' => '1000001']);

    $page = visit(route('admin.payments.index', absolute: false));

    // `assertDontSee` is the assertion that carries this test: the other row is
    // on screen until the debounced search lands, so it can only pass after the
    // round trip. Asserting the wanted row is *there* would pass either way.
    $page->fill('#search', '4821990')
        ->assertDontSee('1000001')
        ->assertNoJavaScriptErrors()
        ->assertSee('4821990');
});
