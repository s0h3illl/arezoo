<?php

use App\Models\User;
use App\Models\Withdrawal;

test('the admin withdrawals queue renders without any errors', function () {
    $this->actingAs(User::factory()->admin()->create());
    $owner = User::factory()->create(['name' => 'Sara Ahmadi']);
    Withdrawal::factory()->for($owner, 'owner')->requested()->create([
        'sheba' => 'IR062960000000100324200001',
    ]);
    Withdrawal::factory()->accepted()->create();
    Withdrawal::factory()->paid()->create();

    $page = visit(route('admin.withdrawals.index', absolute: false));

    $page->assertNoJavaScriptErrors();
});
