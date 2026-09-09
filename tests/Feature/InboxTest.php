<?php

use App\Enums\ContributionVisibility;
use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;
use Inertia\Testing\AssertableInertia;

test('a guest is sent from the inbox to sign in', function () {
    $this->get(route('dashboard.messages'))->assertRedirect(route('login'));
});

test('an unverified user is sent from the inbox to the verification notice', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('dashboard.messages'))
        ->assertRedirect(route('verification.notice'));
});

test('a verified user reaches the inbox', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard.messages'))
        ->assertOk();
});

test('the inbox carries every paid message on the readers own wishes, at all three visibilities', function () {
    $sara = User::factory()->create();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);

    foreach (ContributionVisibility::cases() as $visibility) {
        Contribution::factory()->paid()->create([
            'wish_id' => $wish->id,
            'visibility' => $visibility,
            'message' => "a note at {$visibility->value}",
        ]);
    }

    $this->actingAs($sara)->get(route('dashboard.messages'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('messages.meta.total', 3)
    );
});

test('the inbox leaves out a message on somebody elses wish, an unpaid one, and a contribution with no message', function () {
    $sara = User::factory()->create();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);

    Contribution::factory()->paid()->create([
        'wish_id' => Wish::factory()->create()->id,
        'message' => 'written on another owners wish',
    ]);

    Contribution::factory()->pending()->create([
        'wish_id' => $wish->id,
        'message' => 'the money never landed',
    ]);

    Contribution::factory()->paid()->create([
        'wish_id' => $wish->id,
        'message' => null,
    ]);

    $this->actingAs($sara)->get(route('dashboard.messages'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('messages.meta.total', 0)
    );
});

test('a message outlives the wish it was written for', function () {
    $sara = User::factory()->create();
    $wish = Wish::factory()->create(['user_id' => $sara->id, 'title' => 'a bike']);
    Contribution::factory()->paid()->create(['wish_id' => $wish->id, 'message' => 'happy birthday']);
    $wish->delete();

    $this->actingAs($sara)->get(route('dashboard.messages'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('messages.data.0.wish.title', 'a bike')
    );
});

test('a hidden contributor is masked, and the amount and the message still show', function () {
    $sara = User::factory()->create();
    $wish = Wish::factory()->create(['user_id' => $sara->id, 'title' => 'a bike']);
    Contribution::factory()->paid()->create([
        'wish_id' => $wish->id,
        'contributor_id' => User::factory()->create(['name' => 'Reza Ahmadi'])->id,
        'visibility' => ContributionVisibility::Hidden,
        'message' => 'happy birthday',
        'amount' => 120_000,
    ]);

    $this->actingAs($sara)->get(route('dashboard.messages'))->assertInertia(
        fn (AssertableInertia $page) => $page->has(
            'messages.data.0',
            fn (AssertableInertia $row) => $row
                ->where('state', 'anonymous')
                ->where('contributor.name', __('contributions.anonymous'))
                ->where('contributor.avatar', null)
                ->missing('contributor.id')
                ->where('message', 'happy birthday')
                ->where('wish.title', 'a bike')
                ->where('amount', 120_000)
                ->has('id')
                ->has('settled_at')
        )
    );
});

test('a message from a contributor who deleted their account reads as deleted, at every visibility', function (ContributionVisibility $visibility) {
    $sara = User::factory()->create();
    $wish = Wish::factory()->create(['user_id' => $sara->id, 'title' => 'a bike']);
    $reza = User::factory()->create(['name' => 'Reza Ahmadi']);
    Contribution::factory()->paid()->create([
        'wish_id' => $wish->id,
        'contributor_id' => $reza->id,
        'visibility' => $visibility,
        'message' => 'happy birthday',
        'amount' => 120_000,
    ]);

    $reza->delete();

    $this->actingAs($sara)->get(route('dashboard.messages'))->assertInertia(
        fn (AssertableInertia $page) => $page->has(
            'messages.data.0',
            fn (AssertableInertia $row) => $row
                ->where('state', 'deleted')
                ->where('contributor.name', __('contributions.deleted'))
                ->where('contributor.avatar', null)
                ->where('message', 'happy birthday')
                ->where('wish.title', 'a bike')
                ->where('amount', 120_000)
                ->has('id')
                ->has('settled_at')
        )
    );
})->with([
    'public' => ContributionVisibility::Public,
    'owner-only' => ContributionVisibility::OwnerOnly,
    'hidden' => ContributionVisibility::Hidden,
]);

test('a public and an owner-only contributor are both named to the owner', function (ContributionVisibility $visibility) {
    $sara = User::factory()->create();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);
    Contribution::factory()->paid()->create([
        'wish_id' => $wish->id,
        'contributor_id' => User::factory()->create(['name' => 'Reza Ahmadi'])->id,
        'visibility' => $visibility,
        'message' => 'happy birthday',
    ]);

    $this->actingAs($sara)->get(route('dashboard.messages'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('messages.data.0.state', 'visible')
            ->where('messages.data.0.contributor.name', 'Reza Ahmadi')
    );
})->with([
    'public' => ContributionVisibility::Public,
    'owner-only' => ContributionVisibility::OwnerOnly,
]);

test('the inbox is ordered by when the money landed, newest first', function () {
    $sara = User::factory()->create();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);

    $oldest = Contribution::factory()->settledAt(now()->subWeek())->create([
        'wish_id' => $wish->id,
        'message' => 'the oldest',
    ]);
    $newest = Contribution::factory()->settledAt(now())->create([
        'wish_id' => $wish->id,
        'message' => 'the newest',
    ]);
    $middle = Contribution::factory()->settledAt(now()->subDay())->create([
        'wish_id' => $wish->id,
        'message' => 'the one in between',
    ]);

    $this->actingAs($sara)->get(route('dashboard.messages'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('messages.data.0.id', $newest->id)
            ->where('messages.data.1.id', $middle->id)
            ->where('messages.data.2.id', $oldest->id)
            ->etc()
    );
});

test('a row carries the id of the contribution rather than the id of its wish', function () {
    $sara = User::factory()->create();
    Wish::factory()->count(2)->create(['user_id' => $sara->id]);
    $wish = Wish::factory()->create(['user_id' => $sara->id]);
    $contribution = Contribution::factory()->paid()->create([
        'wish_id' => $wish->id,
        'message' => 'a note',
    ]);

    expect($contribution->id)->not->toBe($wish->id);

    $this->actingAs($sara)->get(route('dashboard.messages'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('messages.data.0.id', $contribution->id)->etc()
    );
});

test('the inbox loads fifteen messages at a time', function () {
    $sara = User::factory()->create();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);
    Contribution::factory()->paid()->count(16)->create([
        'wish_id' => $wish->id,
        'message' => 'a note',
    ]);

    $this->actingAs($sara)->get(route('dashboard.messages'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->count('messages.data', 15)
            ->where('messages.meta.total', 16)
            ->etc()
    );
});

test('a message says its wish is deleted, and says nothing of the sort while the wish is alive', function () {
    $sara = User::factory()->create();
    $alive = Wish::factory()->create(['user_id' => $sara->id]);
    $gone = Wish::factory()->create(['user_id' => $sara->id]);

    Contribution::factory()->settledAt(now()->subDay())->create(['wish_id' => $alive->id, 'message' => 'a note']);
    $onGone = Contribution::factory()->settledAt(now())->create(['wish_id' => $gone->id, 'message' => 'a note']);

    $gone->delete();

    $this->actingAs($sara)->get(route('dashboard.messages'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('messages.meta.total', 2)
            ->where('messages.data.0.id', $onGone->id)
            ->where('messages.data.0.wish.deleted', true)
            ->where('messages.data.1.wish.deleted', false)
            ->etc()
    );
});
