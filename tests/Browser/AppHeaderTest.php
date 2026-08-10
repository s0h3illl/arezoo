<?php

use App\Models\User;

test('the landing page fills the header with its own sections', function () {
    $page = visit(route('home', absolute: false));

    /*
     * Both labels appear again further down the page, so the assertion is pinned
     * to the nav the page handed the header — anywhere else it would pass on the
     * page's own headings and prove nothing.
     */
    $page->assertNoJavaScriptErrors()
        ->assertSeeIn('#page-nav', 'امکانات')
        ->assertSeeIn('#page-nav', 'سوالات');
});

test('a guest gets the login link and no profile button', function () {
    $page = visit(route('home', absolute: false));

    $page->assertNoJavaScriptErrors()
        ->assertSee('ورود')
        ->assertNotPresent('[aria-label="حساب کاربری"]');
});

test('a signed in user gets the profile button instead of the login link', function () {
    $this->actingAs(User::factory()->create());

    $page = visit(route('home', absolute: false));

    $page->assertNoJavaScriptErrors()
        ->assertPresent('[aria-label="حساب کاربری"]')
        ->assertDontSee('ورود');
});

test('a page with no sections of its own gets the way back home', function () {
    $page = visit(route('login', absolute: false));

    $page->assertNoJavaScriptErrors()
        ->assertNotPresent('#page-nav')
        ->assertPresent('[aria-label="صفحه اصلی"]');
});
