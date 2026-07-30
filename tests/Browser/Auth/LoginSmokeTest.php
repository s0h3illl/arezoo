<?php

test('the login page loads without javascript errors', function () {
    $page = visit(route('login', absolute: false));

    $page->assertSee('ورود')->assertNoJavaScriptErrors();
});
