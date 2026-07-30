<?php

test('login page renders without javascript errors', function () {
    $page = visit(route('login', absolute: false));

    // The Persian copy only exists once Vue mounts, so seeing it is what stops
    // a missing or broken bundle from passing the error assertion vacuously.
    $page->assertNoJavaScriptErrors()->assertSee('خوش برگشتی!');
});
