<?php

use App\Models\User;

test('admin dashboard renders without javascript errors', function () {
    $this->actingAs(User::factory()->admin()->create());

    $page = visit(route('admin.dashboard', absolute: false));

    // The Persian copy only exists once Vue mounts, so seeing it is what stops
    // a missing or broken bundle from passing the error assertion vacuously.
    $page->assertNoJavaScriptErrors()->assertSee('داشبورد');
});
