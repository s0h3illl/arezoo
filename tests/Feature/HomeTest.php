<?php

use App\Models\Faq;
use Inertia\Testing\AssertableInertia;

test('a user reaches the landing page', function () {
    $response = $this->get(route('home'));

    $response->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page->component('Home')
    );
});

test('every question in the table is passed to the page', function () {
    $faqs = Faq::factory()->count(7)->create();

    $response = $this->get(route('home'));

    $response->assertOk()->assertInertia(function (AssertableInertia $page) use ($faqs) {
        $page->has('faqs', 7);

        foreach ($faqs as $index => $faq) {
            $page->where("faqs.{$index}.id", $faq->id);
        }
    });
});

test('a question carries the question and its answer', function () {
    $faq = Faq::factory()->create([
        'title' => 'استفاده از آرزو چقدر هزینه داره؟',
        'body' => 'ساختن لیست و فرستادن لینکش رایگانه.',
    ]);

    $this->get(route('home'))->assertInertia(
        fn (AssertableInertia $page) => $page->has(
            'faqs.0',
            fn (AssertableInertia $question) => $question
                ->where('title', 'استفاده از آرزو چقدر هزینه داره؟')
                ->where('body', 'ساختن لیست و فرستادن لینکش رایگانه.')
                ->etc()
        )
    );
});
