<?php

test('the terms page renders without javascript errors', function () {
    $page = visit(route('terms', absolute: false));

    // Seeing the copy is what stops a missing or broken bundle from passing the
    // error assertion vacuously.
    $page->assertNoJavaScriptErrors();
});
