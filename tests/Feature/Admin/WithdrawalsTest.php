<?php

use App\Enums\WithdrawalStatus;
use App\Models\User;
use App\Models\Withdrawal;
use Inertia\Testing\AssertableInertia;

test('an admin sees every withdrawal on the queue', function () {
    signInAsAdmin();
    Withdrawal::factory()->count(3)->create();

    $this->get(route('admin.withdrawals.index'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('admin/withdrawals/Index')
            ->has('withdrawals.data', 3)
    );
});

test('the list is paginated, following the contributions shape', function () {
    signInAsAdmin();
    Withdrawal::factory()->count(25)->create();

    $this->get(route('admin.withdrawals.index'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('withdrawals.data', 20)
            ->where('withdrawals.meta.total', 25)
    );

    $this->get(route('admin.withdrawals.index', ['page' => 2]))->assertInertia(
        fn (AssertableInertia $page) => $page->has('withdrawals.data', 5)
    );
});

test('a withdrawal owned by an admin appears in the queue like any other', function () {
    signInAsAdmin();
    $staff = User::factory()->admin()->create(['name' => 'مدیر']);
    Withdrawal::factory()->for($staff, 'owner')->create();

    $this->get(route('admin.withdrawals.index'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('withdrawals.data', 1)
            ->where('withdrawals.data.0.owner.name', 'مدیر')
    );
});

test('each row carries the owner, the amount, the fee, the transfer amount, and the sheba', function () {
    signInAsAdmin();
    $owner = User::factory()->create(['name' => 'سارا احمدی']);
    Withdrawal::factory()->for($owner, 'owner')->create([
        'amount' => 200_000,
        'fee' => 12_500,
        'sheba' => 'IR062960000000100324200001',
    ]);

    $this->get(route('admin.withdrawals.index'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('withdrawals.data.0.owner.name', 'سارا احمدی')
            ->where('withdrawals.data.0.amount', 200_000)
            ->where('withdrawals.data.0.fee', 12_500)
            ->where('withdrawals.data.0.transfer', 187_500)
            ->where('withdrawals.data.0.sheba', 'IR062960000000100324200001')
    );
});

test('the fee and transfer amount come from the withdrawal, never from live config', function () {
    signInAsAdmin();
    Withdrawal::factory()->create(['amount' => 100_000, 'fee' => 5_000]);

    config()->set('withdrawals.fee', 99_999);

    $this->get(route('admin.withdrawals.index'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('withdrawals.data.0.fee', 5_000)
            ->where('withdrawals.data.0.transfer', 95_000)
    );
});

test('an admin can accept a requested withdrawal, and the time is recorded', function () {
    signInAsAdmin();
    $withdrawal = Withdrawal::factory()->requested()->create();

    $this->patch(route('admin.withdrawals.update', $withdrawal), ['action' => 'accept'])
        ->assertRedirect();

    $withdrawal->refresh();
    expect($withdrawal->status)->toBe(WithdrawalStatus::Accepted)
        ->and($withdrawal->decided_at)->not->toBeNull();
});

test('an admin can mark an accepted withdrawal paid, and the time is recorded', function () {
    signInAsAdmin();
    $withdrawal = Withdrawal::factory()->accepted()->create();

    $this->patch(route('admin.withdrawals.update', $withdrawal), ['action' => 'pay'])
        ->assertRedirect();

    $withdrawal->refresh();
    expect($withdrawal->status)->toBe(WithdrawalStatus::Paid)
        ->and($withdrawal->decided_at)->not->toBeNull();
});

test('marking paid a withdrawal that has not been accepted is refused', function () {
    signInAsAdmin();
    $withdrawal = Withdrawal::factory()->requested()->create();

    $this->patch(route('admin.withdrawals.update', $withdrawal), ['action' => 'pay'])
        ->assertForbidden();

    expect($withdrawal->refresh()->status)->toBe(WithdrawalStatus::Requested);
});

test('a paid withdrawal cannot be changed again by any route', function (string $action) {
    signInAsAdmin();
    $withdrawal = Withdrawal::factory()->paid()->create();

    $this->patch(route('admin.withdrawals.update', $withdrawal), ['action' => $action])
        ->assertForbidden();

    expect($withdrawal->refresh()->status)->toBe(WithdrawalStatus::Paid);
})->with(['accept', 'pay']);

test('an unknown action is rejected', function () {
    signInAsAdmin();
    $withdrawal = Withdrawal::factory()->requested()->create();

    $this->patch(route('admin.withdrawals.update', $withdrawal), ['action' => 'delete'])
        ->assertSessionHasErrors('action');
});

test('accepting reserves the money exactly as requested and paid do — it never changes a balance', function () {
    $owner = ownerWithAvailable(500_000);
    $withdrawal = Withdrawal::factory()->for($owner, 'owner')->requested()->create(['amount' => 200_000]);

    expect(availableFor($owner))->toBe(300_000);

    signInAsAdmin();
    $this->patch(route('admin.withdrawals.update', $withdrawal), ['action' => 'accept'])
        ->assertRedirect();

    expect(availableFor($owner))->toBe(300_000);
});

test('a non-admin cannot reach the queue', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.withdrawals.index'))
        ->assertNotFound();
});

test('a non-admin cannot act on a request', function () {
    $withdrawal = Withdrawal::factory()->requested()->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.withdrawals.update', $withdrawal), ['action' => 'accept'])
        ->assertNotFound();

    expect($withdrawal->refresh()->status)->toBe(WithdrawalStatus::Requested);
});

test('a guest gets the same 404 from the queue and its actions', function () {
    $withdrawal = Withdrawal::factory()->requested()->create();

    $this->get(route('admin.withdrawals.index'))->assertNotFound();
    $this->patch(route('admin.withdrawals.update', $withdrawal), ['action' => 'accept'])
        ->assertNotFound();
});

test('an admin can reject a requested withdrawal, and the time is recorded', function () {
    signInAsAdmin();
    $withdrawal = Withdrawal::factory()->requested()->create();

    $this->patch(route('admin.withdrawals.update', $withdrawal), ['action' => 'reject'])
        ->assertRedirect();

    $withdrawal->refresh();
    expect($withdrawal->status)->toBe(WithdrawalStatus::Rejected)
        ->and($withdrawal->decided_at)->not->toBeNull();
});

test('an admin can reject an accepted withdrawal, and the time is recorded', function () {
    signInAsAdmin();
    $withdrawal = Withdrawal::factory()->accepted()->create(['decided_at' => now()->subDay()]);

    $this->patch(route('admin.withdrawals.update', $withdrawal), ['action' => 'reject'])
        ->assertRedirect();

    $withdrawal->refresh();
    expect($withdrawal->status)->toBe(WithdrawalStatus::Rejected)
        ->and($withdrawal->decided_at->isToday())->toBeTrue();
});

test('rejecting returns the amount to the available balance immediately', function () {
    $owner = ownerWithAvailable(500_000);
    $withdrawal = Withdrawal::factory()->for($owner, 'owner')->requested()->create(['amount' => 200_000]);

    expect(availableFor($owner))->toBe(300_000);

    signInAsAdmin();
    $this->patch(route('admin.withdrawals.update', $withdrawal), ['action' => 'reject'])
        ->assertRedirect();

    expect(availableFor($owner))->toBe(500_000);
});

test('after a rejection the owner can request the same money again', function () {
    $owner = ownerWithAvailable(500_000);
    $withdrawal = Withdrawal::factory()->for($owner, 'owner')->requested()->create(['amount' => 500_000]);

    signInAsAdmin();
    $this->patch(route('admin.withdrawals.update', $withdrawal), ['action' => 'reject']);

    $this->actingAs($owner)->post(route('dashboard.withdrawals.store'), [
        'amount' => 500_000,
        'sheba' => 'IR062960000000100324200001',
    ])->assertRedirect();

    expect(Withdrawal::query()->reserving()->where('user_id', $owner->id)->sum('amount'))->toBe(500_000);
});

test('a rejected withdrawal cannot be changed again by any route', function (string $action) {
    signInAsAdmin();
    $withdrawal = Withdrawal::factory()->rejected()->create();

    $this->patch(route('admin.withdrawals.update', $withdrawal), ['action' => $action])
        ->assertForbidden();

    expect($withdrawal->refresh()->status)->toBe(WithdrawalStatus::Rejected);
})->with(['accept', 'pay', 'reject']);

test('a paid withdrawal cannot be rejected', function () {
    signInAsAdmin();
    $withdrawal = Withdrawal::factory()->paid()->create();

    $this->patch(route('admin.withdrawals.update', $withdrawal), ['action' => 'reject'])
        ->assertForbidden();

    expect($withdrawal->refresh()->status)->toBe(WithdrawalStatus::Paid);
});

test('a non-admin cannot reject a withdrawal, and a guest gets the same 404', function () {
    $withdrawal = Withdrawal::factory()->requested()->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.withdrawals.update', $withdrawal), ['action' => 'reject'])
        ->assertNotFound();

    auth()->logout();

    $this->patch(route('admin.withdrawals.update', $withdrawal), ['action' => 'reject'])
        ->assertNotFound();

    expect($withdrawal->refresh()->status)->toBe(WithdrawalStatus::Requested);
});

test('an admin can write a note on a withdrawal in any state', function (string $state) {
    signInAsAdmin();
    $withdrawal = Withdrawal::factory()->{$state}()->create();

    $this->put(route('admin.withdrawals.note.update', $withdrawal), ['note' => 'شبات درست نیست.'])
        ->assertRedirect();

    expect($withdrawal->refresh()->note)->toBe('شبات درست نیست.');
})->with(['requested', 'accepted', 'paid', 'rejected']);

test('a note can be rewritten', function () {
    signInAsAdmin();
    $withdrawal = Withdrawal::factory()->rejected()->create(['note' => 'اولی']);

    $this->put(route('admin.withdrawals.note.update', $withdrawal), ['note' => 'دومی'])
        ->assertRedirect();

    expect($withdrawal->refresh()->note)->toBe('دومی');
});

test('a note is optional and may be emptied', function () {
    signInAsAdmin();
    $withdrawal = Withdrawal::factory()->rejected()->create(['note' => 'اولی']);

    $this->put(route('admin.withdrawals.note.update', $withdrawal), ['note' => ''])
        ->assertRedirect();

    expect($withdrawal->refresh()->note)->toBe('');
});

test('saving a note changes nothing else about the withdrawal', function () {
    signInAsAdmin();
    $withdrawal = Withdrawal::factory()->accepted()->create([
        'amount' => 200_000,
        'fee' => 12_500,
        'sheba' => 'IR062960000000100324200001',
        'requested_at' => now()->subWeek(),
        'decided_at' => now()->subDay(),
    ]);

    $this->put(route('admin.withdrawals.note.update', $withdrawal), [
        'note' => 'در حال بررسی',
        'amount' => 900_000,
        'fee' => 0,
        'sheba' => 'IR820540102680020817909002',
        'status' => 'paid',
    ])->assertRedirect();

    $fresh = $withdrawal->fresh();
    expect($fresh->amount)->toBe(200_000)
        ->and($fresh->fee)->toBe(12_500)
        ->and($fresh->sheba)->toBe('IR062960000000100324200001')
        ->and($fresh->status)->toBe(WithdrawalStatus::Accepted)
        ->and($fresh->requested_at->equalTo($withdrawal->requested_at))->toBeTrue()
        ->and($fresh->decided_at->equalTo($withdrawal->decided_at))->toBeTrue();
});

test('the note reaches the queue row', function () {
    signInAsAdmin();
    Withdrawal::factory()->create(['note' => 'شبات درست نیست.']);

    $this->get(route('admin.withdrawals.index'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('withdrawals.data.0.note', 'شبات درست نیست.')
    );
});

test('a non-admin cannot write a note', function () {
    $withdrawal = Withdrawal::factory()->create(['note' => '']);

    $this->actingAs(User::factory()->create())
        ->put(route('admin.withdrawals.note.update', $withdrawal), ['note' => 'خودم نوشتم'])
        ->assertNotFound();

    expect($withdrawal->refresh()->note)->toBe('');
});

test('a guest gets the same 404 when writing a note', function () {
    $withdrawal = Withdrawal::factory()->create(['note' => '']);

    $this->put(route('admin.withdrawals.note.update', $withdrawal), ['note' => 'خودم نوشتم'])
        ->assertNotFound();

    expect($withdrawal->refresh()->note)->toBe('');
});

test('the owner of a withdrawal cannot write its note', function () {
    $owner = User::factory()->create();
    $withdrawal = Withdrawal::factory()->for($owner, 'owner')->create(['note' => '']);

    $this->actingAs($owner)
        ->put(route('admin.withdrawals.note.update', $withdrawal), ['note' => 'خودم نوشتم'])
        ->assertNotFound();

    expect($withdrawal->refresh()->note)->toBe('');
});

test('a note longer than the column is meant to hold is refused', function () {
    signInAsAdmin();
    $withdrawal = Withdrawal::factory()->create(['note' => '']);

    $this->put(route('admin.withdrawals.note.update', $withdrawal), ['note' => str_repeat('ا', 2_001)])
        ->assertSessionHasErrors('note');

    expect($withdrawal->refresh()->note)->toBe('');
});

test('a request carrying no note at all is refused rather than emptying one', function () {
    signInAsAdmin();
    $withdrawal = Withdrawal::factory()->create(['note' => 'اولی']);

    $this->put(route('admin.withdrawals.note.update', $withdrawal))
        ->assertSessionHasErrors('note');

    expect($withdrawal->refresh()->note)->toBe('اولی');
});
