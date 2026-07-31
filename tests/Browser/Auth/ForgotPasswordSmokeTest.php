<?php

test('forgot password page renders without javascript errors', function () {
    $page = visit(route('password.request', absolute: false));

    // The Persian copy only exists once Vue mounts, so seeing it is what stops
    // a missing or broken bundle from passing the error assertion vacuously.
    $page->assertNoJavaScriptErrors()->assertSee('رمزت رو فراموش کردی؟');
});
