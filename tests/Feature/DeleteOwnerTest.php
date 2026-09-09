<?php

use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;
use Illuminate\Database\QueryException;

test('a wish may not belong to an owner who does not exist', function () {
    expect(fn () => Wish::factory()->create(['user_id' => 404]))
        ->toThrow(QueryException::class);
});

test('deleting an owner removes the wishes they own', function () {
    $sara = User::factory()->create();
    Wish::factory()->count(2)->create(['user_id' => $sara->id]);

    $sara->delete();

    expect(Wish::withTrashed()->where('user_id', $sara->id)->count())->toBe(0);
});

test('deleting an owner removes a wish they had already soft deleted', function () {
    $sara = User::factory()->create();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);
    $wish->delete();

    $sara->delete();

    expect(Wish::withTrashed()->whereKey($wish->id)->exists())->toBeFalse();
});

test('deleting an owner leaves everybody elses wishes alone', function () {
    $sara = User::factory()->create();
    Wish::factory()->create(['user_id' => $sara->id]);
    $reza = User::factory()->create();
    $theirs = Wish::factory()->create(['user_id' => $reza->id]);

    $sara->delete();

    expect(Wish::whereKey($theirs->id)->exists())->toBeTrue();
});

test('deleting an owner takes the contributions towards their wishes with them', function () {
    $sara = User::factory()->create();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);
    Contribution::factory()->paid()->count(2)->create(['wish_id' => $wish->id]);

    $sara->delete();

    expect(Contribution::where('wish_id', $wish->id)->count())->toBe(0);
});

test('an owner who also gave to somebody else loses their own wishes and leaves that contribution behind', function () {
    $sara = User::factory()->create();
    $hers = Wish::factory()->create(['user_id' => $sara->id]);

    $reza = User::factory()->create();
    $his = Wish::factory()->create(['user_id' => $reza->id]);
    $contribution = Contribution::factory()->paid()->create([
        'wish_id' => $his->id,
        'contributor_id' => $sara->id,
        'amount' => 90_000,
    ]);

    $sara->delete();

    $contribution->refresh();

    expect(Wish::withTrashed()->whereKey($hers->id)->exists())->toBeFalse()
        ->and($contribution->contributor_id)->toBeNull()
        ->and($contribution->amount)->toBe(90_000)
        ->and($his->receivedTotal())->toBe(90_000);
});
