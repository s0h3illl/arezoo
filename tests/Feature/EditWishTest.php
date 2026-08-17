<?php

use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('an owner can edit their own wish', function () {
    $sara = signInAsEditor();
    $wish = Wish::factory()->create([
        'user_id' => $sara->id,
        'title' => 'دوچرخه',
        'price' => 500_000,
    ]);

    $response = $this->put(route('wishes.update', $wish), [
        'title' => 'دوچرخه کوهستان',
        'price' => 750_000,
    ]);

    $response->assertRedirect(route('profile', 'sara'));
    expect($wish->fresh())
        ->title->toBe('دوچرخه کوهستان')
        ->price->toBe(750_000);
});

test('the optional fields can be set on a wish that had none', function () {
    $sara = signInAsEditor();
    $wish = Wish::factory()->create([
        'user_id' => $sara->id,
        'description' => null,
        'purchase_link' => null,
    ]);

    $this->put(route('wishes.update', $wish), [
        'title' => $wish->title,
        'price' => $wish->price,
        'description' => 'test',
        'purchase_link' => 'https://example.com/bike',
    ]);

    expect($wish->fresh())
        ->description->toBe('test')
        ->purchase_link->toBe('https://example.com/bike');
});

test('the optional fields can be cleared again', function () {
    $sara = signInAsEditor();
    $wish = Wish::factory()->create([
        'user_id' => $sara->id,
        'description' => 'یه چیزی',
        'purchase_link' => 'https://example.com',
    ]);

    $this->put(route('wishes.update', $wish), [
        'title' => $wish->title,
        'price' => $wish->price,
        'description' => '',
        'purchase_link' => '',
    ]);

    expect($wish->fresh())
        ->description->toBeNull()
        ->purchase_link->toBeNull();
});

test('a wish cannot be edited into having no title', function () {
    $sara = signInAsEditor();
    $wish = Wish::factory()->create(['user_id' => $sara->id, 'title' => 'دوچرخه']);

    $response = $this->put(route('wishes.update', $wish), [
        'title' => '',
        'price' => $wish->price,
    ]);

    $response->assertInvalid(['title']);
    expect($wish->fresh()->title)->toBe('دوچرخه');
});

test('a wish cannot be edited into having no price', function () {
    $sara = signInAsEditor();
    $wish = Wish::factory()->create(['user_id' => $sara->id, 'price' => 500_000]);

    $response = $this->put(route('wishes.update', $wish), [
        'title' => $wish->title,
        'price' => '',
    ]);

    $response->assertInvalid(['price']);
    expect($wish->fresh()->price)->toBe(500_000);
});

test('a wish people have already paid towards edits just as freely', function () {
    $sara = signInAsEditor();
    $wish = Wish::factory()->create(['user_id' => $sara->id, 'price' => 500_000]);
    Contribution::factory()->paid()->count(2)->create(['wish_id' => $wish->id]);

    $this->put(route('wishes.update', $wish), [
        'title' => 'new title',
        'price' => 900_000,
    ])->assertRedirect(route('profile', 'sara'));

    expect($wish->fresh())->title->toBe('new title')->price->toBe(900_000);
});

test('a new cover replaces the old one and takes it off the disk', function () {
    Storage::fake('public');
    $sara = signInAsEditor();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);
    $old = $wish->thumbnail;

    $this->put(route('wishes.update', $wish), [
        'title' => $wish->title,
        'price' => $wish->price,
        'thumbnail' => new UploadedFile(
            base_path('tests/fixtures/wish-cover.jpg'), 'new.jpg', 'image/jpeg', test: true
        ),
    ]);

    $fresh = $wish->fresh();

    expect($fresh->thumbnail)->not->toBe($old);
    Storage::disk('public')->assertExists((string) $fresh->thumbnail);
    Storage::disk('public')->assertMissing((string) $old);
});

test('removing the cover nulls the column and takes the file off the disk', function () {
    Storage::fake('public');
    $sara = signInAsEditor();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);
    $old = $wish->thumbnail;

    $this->put(route('wishes.update', $wish), [
        'title' => $wish->title,
        'price' => $wish->price,
        'remove_thumbnail' => true,
    ]);

    expect($wish->fresh()->thumbnail)->toBeNull();
    Storage::disk('public')->assertMissing((string) $old);
});

test('an edit that says nothing about the cover leaves it alone', function () {
    Storage::fake('public');
    $sara = signInAsEditor();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);
    $old = $wish->thumbnail;

    $this->put(route('wishes.update', $wish), [
        'title' => 'اسم تازه',
        'price' => $wish->price,
    ]);

    expect($wish->fresh()->thumbnail)->toBe($old);
    Storage::disk('public')->assertExists((string) $old);
});

test('a cover sent alongside the remove flag wins', function () {
    Storage::fake('public');
    $sara = signInAsEditor();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);

    $this->put(route('wishes.update', $wish), [
        'title' => $wish->title,
        'price' => $wish->price,
        'remove_thumbnail' => true,
        'thumbnail' => new UploadedFile(
            base_path('tests/fixtures/wish-cover.jpg'), 'new.jpg', 'image/jpeg', test: true
        ),
    ]);

    expect($wish->fresh()->thumbnail)->not->toBeNull();
    Storage::disk('public')->assertExists((string) $wish->fresh()->thumbnail);
});

test('a cover must still be an image within the size cap', function () {
    Storage::fake('public');
    $sara = signInAsEditor();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);

    $response = $this->put(route('wishes.update', $wish), [
        'title' => $wish->title,
        'price' => $wish->price,
        'thumbnail' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
    ]);

    $response->assertInvalid(['thumbnail']);
});

test('a signed-in visitor may not edit a wish that is not theirs', function () {
    $wish = Wish::factory()->create(['title' => 'دوچرخه']);
    $this->actingAs(User::factory()->create(['username' => 'reza']));

    $this->put(route('wishes.update', $wish), [
        'title' => 'مال منه',
        'price' => 100_000,
    ])->assertForbidden();

    expect($wish->fresh()->title)->toBe('دوچرخه');
});

test('a guest may not edit a wish, and is sent to sign in', function () {
    $wish = Wish::factory()->create(['title' => 'دوچرخه']);

    $this->put(route('wishes.update', $wish), [
        'title' => 'مال منه',
        'price' => 100_000,
    ])->assertRedirect(route('login'));

    expect($wish->fresh()->title)->toBe('دوچرخه');
});

function signInAsEditor(): User
{
    $sara = User::factory()->create(['username' => 'sara']);

    test()->actingAs($sara);

    return $sara;
}
