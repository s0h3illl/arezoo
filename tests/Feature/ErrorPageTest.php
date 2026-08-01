<?php

use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Inertia\Testing\AssertableInertia;

/*
| Inertia answers by rendering the root Blade view, so a response Laravel rendered
| itself — a stack trace, a stock status page — carries a plain string instead.
| That is what the `View` assertions below are reading.
*/

test('a missing page renders the shared error page', function () {
    // A 404 never reaches a route, so the `web` group never starts a session. The
    // shared props have to survive that or the hook trades one error for another.
    $response = $this->get('/no-such-page');

    $response->assertNotFound()->assertInertia(
        fn (AssertableInertia $page) => $page->component('Error')->where('status', 404)
    );
});

test('a forbidden page renders the shared error page', function () {
    Route::middleware('web')->get('/forbidden-fixture', fn () => abort(403));

    $response = $this->get('/forbidden-fixture');

    $response->assertForbidden()->assertInertia(
        fn (AssertableInertia $page) => $page->component('Error')->where('status', 403)
    );
});

test('a server error renders the shared error page when debug mode is off', function () {
    config(['app.debug' => false]);
    Route::middleware('web')->get('/boom-fixture', fn () => throw new RuntimeException('boom'));

    $response = $this->get('/boom-fixture');

    $response->assertServerError()->assertInertia(
        fn (AssertableInertia $page) => $page->component('Error')->where('status', 500)
    );
});

test('debug mode keeps the stack trace instead of the error page', function () {
    config(['app.debug' => true]);
    Route::middleware('web')->get('/boom-fixture', fn () => throw new RuntimeException('boom'));

    $response = $this->get('/boom-fixture');

    $response->assertServerError();
    expect($response->baseResponse->original)->not->toBeInstanceOf(View::class);
});

test('statuses without their own copy fall through to Laravel', function () {
    Route::middleware('web')->get('/gone-fixture', fn () => abort(410));

    $response = $this->get('/gone-fixture');

    $response->assertStatus(410);
    expect($response->baseResponse->original)->not->toBeInstanceOf(View::class);
});
