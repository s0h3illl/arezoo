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
    Withdrawal::factory()->rejected()->create();

    $page = visit(route('admin.withdrawals.index', absolute: false));

    $page->assertNoJavaScriptErrors();
});

test('a row carries a reject action while it is requested or accepted', function (string $state) {
    $this->actingAs(User::factory()->admin()->create());
    Withdrawal::factory()->{$state}()->create();

    visit(route('admin.withdrawals.index', absolute: false))
        ->assertPresent('@reject');
})->with(['requested', 'accepted']);

test('a rejected withdrawal shows no accept, mark-paid, or reject action', function () {
    $this->actingAs(User::factory()->admin()->create());
    Withdrawal::factory()->rejected()->create();

    visit(route('admin.withdrawals.index', absolute: false))
        ->assertMissing('@accept')
        ->assertMissing('@mark-paid')
        ->assertMissing('@reject');
});
