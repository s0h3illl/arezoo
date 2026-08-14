<?php

use App\Models\User;
use App\Models\Wish;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
| The first thing in this app that writes to the `wishes` table. Every wish on
| the platform before this was put there by a seeder, so these tests are the
| whole account of what a wish may be created from and by whom.
*/

test('an owner adds a wish with only a title and a price', function () {
    $sara = signIn();

    $response = $this->post(route('wishes.store'), wish());

    $response->assertRedirect(route('profile', 'sara'));

    expect(Wish::sole())
        ->title->toBe('دوچرخه‌ی کوهستان')
        ->price->toBe(3_200_000)
        ->user_id->toBe($sara->id)
        ->description->toBeNull()
        ->purchase_link->toBeNull()
        ->thumbnail->toBeNull();
});

test('the optional fields are kept when they are given', function () {
    signIn();

    $this->post(route('wishes.store'), wish([
        'description' => 'برای رفتن به کوه',
        'purchase_link' => 'https://example.com/bike',
    ]));

    expect(Wish::sole())
        ->description->toBe('برای رفتن به کوه')
        ->purchase_link->toBe('https://example.com/bike');
});

test('an optional field left blank is stored as nothing at all', function () {
    signIn();

    $this->post(route('wishes.store'), wish([
        'description' => '',
        'purchase_link' => '',
    ]));

    expect(Wish::sole())
        ->description->toBeNull()
        ->purchase_link->toBeNull();
});

test('a missing title is refused', function () {
    signIn();

    $response = $this->post(route('wishes.store'), wish(['title' => null]));

    $response->assertInvalid(['title']);
    expect(Wish::count())->toBe(0);
});

test('a missing price is refused', function () {
    signIn();

    $response = $this->post(route('wishes.store'), wish(['price' => null]));

    $response->assertInvalid(['price']);
    expect(Wish::count())->toBe(0);
});

it('validates every given field', function (array $payload) {
    signIn();

    $response = $this->post(route('wishes.store'), wish($payload));

    $response->assertInvalid(array_keys($payload));
    expect(Wish::count())->toBe(0);
})->with([
    'a title past 255 characters' => [['title' => str_repeat('ا', 256)]],
    'a description past 2,000 characters' => [['description' => str_repeat('ا', 2_001)]],
    'a link past 2,048 characters' => [['purchase_link' => 'https://example.com/'.str_repeat('a', 2_030)]],
    'a price past what the column holds' => [['price' => 4_294_967_296]],
    'a price of nothing at all' => [['price' => 0]],
]);

test('a product link that is not a link is refused', function () {
    signIn();

    $response = $this->post(route('wishes.store'), wish([
        'purchase_link' => 'not a link',
    ]));

    $response->assertInvalid(['purchase_link']);
    expect(Wish::count())->toBe(0);
});

test('a picture is stored on the public disk', function () {
    Storage::fake('public');
    signIn();

    $this->post(route('wishes.store'), wish([
        'thumbnail' => new UploadedFile(
            base_path('tests/fixtures/thumbnail.jpg'), 'bike.jpg', 'image/jpeg', test: true
        ),
    ]));

    $thumbnail = Wish::first()->thumbnail;

    Storage::disk('public')->assertExists((string) $thumbnail);
});

test('a file that is not a picture is refused', function () {
    Storage::fake('public');
    signIn();

    $response = $this->post(route('wishes.store'), wish([
        'thumbnail' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
    ]));

    $response->assertInvalid(['thumbnail']);
    expect(Wish::count())->toBe(0);
});

test('a picture larger than the maximum size is refused', function () {
    Storage::fake('public');
    signIn();

    $response = $this->post(route('wishes.store'), wish([
        'thumbnail' => UploadedFile::fake()->create('huge.jpg', 3_000, 'image/jpeg'),
    ]));

    $response->assertInvalid(['thumbnail']);
    expect(Wish::count())->toBe(0);
});

test('a guest may not add a wish, and is sent to sign in', function () {
    $response = $this->post(route('wishes.store'), wish());

    $response->assertRedirect(route('login'));
    expect(Wish::count())->toBe(0);
});

function signIn(): User
{
    $sara = User::factory()->create(['username' => 'sara']);

    test()->actingAs($sara);

    return $sara;
}

/**
 * The whole of a wish — title and price — with what the test examines swapped
 * in. A `null` override drops the field, which is how a missing one is written.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function wish(array $overrides = []): array
{
    return array_filter(
        array_merge([
            'title' => 'دوچرخه‌ی کوهستان',
            'price' => 3_200_000,
        ], $overrides),
        fn (mixed $value): bool => $value !== null,
    );
}
