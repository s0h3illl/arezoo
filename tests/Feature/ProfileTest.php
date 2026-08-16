<?php

use App\Http\Resources\WishResource;
use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

/*
| A profile is a page built to be sent to people, so the first cases are all about
| who the URL answers to. The grid it carries follows below.
*/

test('a profile resolves by username', function () {
    User::factory()->create(['username' => 'sara', 'name' => 'سارا احمدی']);

    $response = $this->get(route('profile', 'sara'));

    $response->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('Profile')
            ->where('user.name', 'سارا احمدی')
            ->where('user.username', 'sara')
    );
});

test('a guest reads a profile without being sent to sign in', function () {
    User::factory()->create(['username' => 'sara']);

    $this->assertGuest();
    $this->get(route('profile', 'sara'))->assertOk();
});

/*
| One username, however it is typed. Two people cannot hold names differing only
| in capitalisation, so a link that shouts still leads to the one profile.
*/
test('a username in the URL matches whatever its capitalisation', function () {
    User::factory()->create(['username' => 'sara']);

    $this->get(route('profile', 'SARA'))->assertOk();
});

test('a username nobody holds answers with the shared error page', function () {
    $response = $this->get(route('profile', 'nobody'));

    $response->assertNotFound()->assertInertia(
        fn (AssertableInertia $page) => $page->component('Error')->where('status', 404)
    );
});

/*
| Blocking is a real removal from the app, not a cosmetic one — so the profile is
| unreachable by everyone, including its own owner.
*/
test('a blocked user has no profile', function () {
    User::factory()->blocked()->create(['username' => 'sara']);

    $this->get(route('profile', 'sara'))->assertNotFound();
});

/*
| ADR-0008 keeps admins out of the panel's user routes. It says nothing about the
| public site, where an admin reads and publishes wishes like anybody else.
*/
test("an admin's profile reads like anyone else's", function () {
    User::factory()->admin()->create(['username' => 'sara']);

    $this->get(route('profile', 'sara'))->assertOk();
});

/*
| The public URL shape must not leak into the panel. Its binding still takes an id
| and still leaves out admins, so a username there is simply an unknown user.
*/
test('the panel still resolves a user by primary key, and never by username', function () {
    $this->actingAs(User::factory()->admin()->create());
    $user = User::factory()->create(['username' => 'sara']);

    $this->get(route('admin.users.show', $user->id))->assertOk();
    $this->get(route('admin.users.show', 'sara'))->assertNotFound();
});

/*
| Who is reading. The owner gets controls nobody else does, and the page works
| that out by comparing the profile it is showing against the signed-in user it
| was shared — so these assert the two ids the answer is made of, rather than a
| flag. Nothing is protected by either: every write is authorised server-side.
*/
test('an owner reads a profile whose id matches their own', function () {
    $sara = User::factory()->create(['username' => 'sara']);
    $this->actingAs($sara);

    $this->get(route('profile', 'sara'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('user.id', $sara->id)
            ->where('auth.user.id', $sara->id)
    );
});

test('a signed-in visitor does not own the profile they are reading', function () {
    $sara = User::factory()->create(['username' => 'sara']);
    $reza = User::factory()->create(['username' => 'reza']);
    $this->actingAs($reza);

    $this->get(route('profile', 'sara'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('user.id', $sara->id)
            ->where('auth.user.id', $reza->id)
    );
});

test('a guest owns no profile', function () {
    User::factory()->create(['username' => 'sara']);

    $this->get(route('profile', 'sara'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('auth.user', null)
    );
});

/*
| The shared user prop used to be the whole model, which put every reader's own
| email, staff flag and timestamps into the HTML of every page in the app. This
| is the assertion that stops it coming back.
*/
test('the shared user carries nothing the frontend has no business seeing', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('home'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('auth.user', fn (AssertableInertia $user) => $user
                ->hasAll(['id', 'name', 'username', 'avatar', 'bio'])
                ->missingAll([
                    'email',
                    'email_verified_at',
                    'is_admin',
                    'is_blocked',
                    'created_at',
                    'updated_at',
                ])
            )
    );
});

test('a profile publishes nothing about its owner beyond the public shape', function () {
    User::factory()->create(['username' => 'sara']);

    $this->get(route('profile', 'sara'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('user', fn (AssertableInertia $user) => $user
                ->hasAll(['id', 'name', 'username', 'avatar', 'bio'])
                ->missingAll(['email', 'is_admin', 'is_blocked'])
            )
    );
});

test('a profile carries the wishes of its owner and of nobody else', function () {
    $sara = User::factory()->create(['username' => 'sara']);
    Wish::factory()->count(2)->create(['user_id' => $sara->id]);
    Wish::factory()->create(['title' => 'آرزوی یک نفر دیگر']);

    $this->get(route('profile', 'sara'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('wishes.data', 2)
            ->where('wishes.meta.total', 2)
    );
});

test('a card carries its cover, title, description, price and what it has received', function () {
    $sara = User::factory()->create(['username' => 'sara']);
    $wish = Wish::factory()->create([
        'user_id' => $sara->id,
        'title' => 'دوچرخه‌ی کوهستان',
        'description' => 'برای رفتن به کوه',
        'price' => 3_200_000,
    ]);

    $this->get(route('profile', 'sara'))->assertInertia(
        fn (AssertableInertia $page) => $page->has(
            'wishes.data.0',
            fn (AssertableInertia $card) => $card
                ->where('id', $wish->id)
                ->where('title', 'دوچرخه‌ی کوهستان')
                ->where('description', 'برای رفتن به کوه')
                ->where('thumbnail', Storage::disk('public')->url((string) $wish->thumbnail))
                ->where('price', 3_200_000)
                ->where('received', 0)
                ->etc()
        )
    );
});

test('only paid contributions count towards what a wish has received', function () {
    $sara = User::factory()->create(['username' => 'sara']);
    $wish = Wish::factory()->create(['user_id' => $sara->id, 'price' => 500_000]);
    Contribution::factory()->paid()->create(['wish_id' => $wish->id, 'amount' => 120_000]);
    Contribution::factory()->paid()->create(['wish_id' => $wish->id, 'amount' => 80_000]);
    Contribution::factory()->pending()->create(['wish_id' => $wish->id, 'amount' => 900_000]);

    $this->get(route('profile', 'sara'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('wishes.data.0.received', 200_000)
    );
});

/*
| Contributions are uncapped (ADR-0004), so the figure is allowed to pass the
| price. The card clamps its bar; the number it publishes stays the true one.
*/
test('a wish that received more than its price reports the whole of it', function () {
    $sara = User::factory()->create(['username' => 'sara']);
    $wish = Wish::factory()->create(['user_id' => $sara->id, 'price' => 500_000]);
    Contribution::factory()->paid()->create(['wish_id' => $wish->id, 'amount' => 700_000]);

    $this->get(route('profile', 'sara'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('wishes.data.0.price', 500_000)
            ->where('wishes.data.0.received', 700_000)
    );
});

test('serialising a wish without the grid aggregate fails rather than reporting nothing', function () {
    $wish = Wish::factory()->make();

    expect(fn () => WishResource::make($wish)->toArray(request()))
        ->toThrow(LogicException::class);
});

test('the grid hands over one page of wishes and a cursor to the next', function () {
    $sara = User::factory()->create(['username' => 'sara']);
    Wish::factory()->count(13)->create(['user_id' => $sara->id]);

    $response = $this->get(route('profile', 'sara'));

    $response->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('wishes.data', 12)
            ->where('wishes.meta.total', 13)
    );

    expect($response->viewData('page'))
        ->toHaveKey('scrollProps.wishes.nextPage', 2)
        ->toHaveKey('mergeProps', ['wishes.data']);
});
