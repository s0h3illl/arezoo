<?php

use App\Models\Faq;

test('the landing page renders without javascript errors', function () {
    $faq = Faq::factory()->create(['title' => 'استفاده از آرزو چقدر هزینه داره؟']);

    $page = visit(route('home', absolute: false));

    $page->assertNoJavaScriptErrors()
        ->assertSee($faq->title);
});
