<?php

test('admin dashboard renders without javascript errors', function () {
    $page = visit('/admin');

    $page->assertNoJavaScriptErrors();
});
