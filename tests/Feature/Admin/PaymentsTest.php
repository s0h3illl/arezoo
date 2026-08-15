<?php

use App\Enums\PaymentStatus;
use App\Models\Contribution;
use App\Models\Payment;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

/*
| The screen exists for the payment a user made that left nothing behind: the
| contribution was deleted when the attempt failed (ADR-0001), so the payment
| is the only record that the attempt ever happened.
*/
test('an admin sees a gateway attempt that failed and left no contribution behind', function () {
    $this->actingAs(User::factory()->admin()->create());
    $payment = Payment::factory()->failed()->create();

    $this->get(route('admin.payments.index'))->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('admin/payments/Index')
            ->where('payments.data.0.id', $payment->id)
            ->where('payments.data.0.contribution', null)
    );
});

/*
| The same as on the contributions screen: a deleted wish must not take the
| payment record down with it. See ADR-0003.
*/
test('an attempt shows its wish even once that wish is deleted', function () {
    $this->actingAs(User::factory()->admin()->create());
    $contribution = Contribution::factory()->paid()->create();
    $contribution->wish->delete();

    $this->get(route('admin.payments.index'))->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('payments.data.0.contribution.wish.id', $contribution->wish_id)
            ->where('payments.data.0.contribution.wish.title', $contribution->wish->title)
    );
});

/*
| A user asking about a payment has one thing to hand: the identifier the
| gateway showed them. It has to be enough to find the record, and they have no
| idea how the attempt ended — that is what they are asking.
*/
test('an attempt is found by its transaction identifier, however it ended', function (string $ending) {
    $this->actingAs(User::factory()->admin()->create());
    $wanted = Payment::factory()->{$ending}()->create(['transaction_id' => '4821990']);
    Payment::factory()->{$ending}()->create(['transaction_id' => '1000001']);

    $this->get(route('admin.payments.index', ['search' => '4821990']))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('payments.data', 1)
            ->where('payments.data.0.id', $wanted->id)
            ->where('filters.search', '4821990')
    );
})->with([
    'failed' => 'failed',
    'verified' => 'verified',
    'pending' => 'pending',
]);

/*
| A bare `%` reaches the database as a LIKE wildcard unless it is escaped, which
| would turn the narrowest possible search into "show me every attempt".
*/
test('a wildcard in the search term is searched for, not obeyed', function () {
    $this->actingAs(User::factory()->admin()->create());
    $wanted = Payment::factory()->failed()->create(['transaction_id' => '100%25']);
    Payment::factory()->count(3)->failed()->create();

    $this->get(route('admin.payments.index', ['search' => '%']))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('payments.data', 1)
            ->where('payments.data.0.id', $wanted->id)
    );
});

/*
| An attempt that reached the gateway and never came back is not the same
| answer as one the gateway turned down — the first may still land.
*/
test('a pending attempt is distinguishable from a failed one', function () {
    $this->actingAs(User::factory()->admin()->create());
    Payment::factory()->failed()->create(['transaction_id' => 'failed-one']);
    Payment::factory()->create(['transaction_id' => 'pending-one']);

    $this->get(route('admin.payments.index'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('payments.data.0.status', PaymentStatus::Pending)
            ->where('payments.data.1.status', PaymentStatus::Failed)
    );
});

test('the list is paginated, newest first', function () {
    $this->actingAs(User::factory()->admin()->create());
    Payment::factory()->count(25)->failed()->create();
    $newest = Payment::factory()->failed()->create();

    $this->get(route('admin.payments.index'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('payments.data', 20)
            ->where('payments.meta.total', 26)
            ->where('payments.data.0.id', $newest->id)
    );

    $this->get(route('admin.payments.index', ['page' => 2]))->assertInertia(
        fn (AssertableInertia $page) => $page->has('payments.data', 6)
    );
});

/*
| A 403 would confirm the section exists, and a login redirect would too. Everyone
| who isn't an admin gets the answer a made-up URL would give.
*/
test('an authenticated user who is not an admin gets a 404', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.payments.index'))->assertNotFound();
});

test('a guest visitor gets a 404', function () {
    $this->get(route('admin.payments.index'))->assertNotFound();
});
