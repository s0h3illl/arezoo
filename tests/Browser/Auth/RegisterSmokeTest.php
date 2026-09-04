<?php

test('register page renders without javascript errors', function () {
    $page = visit(route('register', absolute: false));

    $page->assertNoJavaScriptErrors()->assertSee('بزن بریم!');
});
