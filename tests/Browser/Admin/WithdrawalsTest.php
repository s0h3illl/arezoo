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

test('a row carries a note action in every state', function (string $state) {
    $this->actingAs(User::factory()->admin()->create());
    Withdrawal::factory()->{$state}()->create();

    visit(route('admin.withdrawals.index', absolute: false))
        ->assertPresent('@note');
})->with(['requested', 'accepted', 'paid', 'rejected']);

test('the note modal opens without any errors', function () {
    $this->actingAs(User::factory()->admin()->create());
    $withdrawal = Withdrawal::factory()->paid()->create(['note' => 'یادداشت قبلی']);

    visit(route('admin.withdrawals.index', absolute: false))
        ->click('@note')
        ->assertValue("#withdrawal-{$withdrawal->id}-note", 'یادداشت قبلی')
        ->assertPresent('@save-note')
        ->assertNoJavaScriptErrors();
});
