<?php

use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;

test('an owners money is untouched when one of their contributors deletes their account', function () {
    $sara = User::factory()->create();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);

    $reza = User::factory()->create();
    Contribution::factory()->paid()->create([
        'wish_id' => $wish->id,
        'contributor_id' => $reza->id,
        'amount' => 120_000,
    ]);
    Contribution::factory()->paid()->create([
        'wish_id' => $wish->id,
        'amount' => 80_000,
    ]);

    expect($wish->receivedTotal())->toBe(200_000)
        ->and($wish->contributorCount())->toBe(2);

    $reza->delete();

    expect($wish->receivedTotal())->toBe(200_000)
        ->and($wish->contributorCount())->toBe(2);
});

test('a departed contributor who gave twice can no longer be told from two of them', function () {
    $sara = User::factory()->create();
    $wish = Wish::factory()->create(['user_id' => $sara->id]);

    $reza = User::factory()->create();
    Contribution::factory()->paid()->count(2)->create([
        'wish_id' => $wish->id,
        'contributor_id' => $reza->id,
        'amount' => 50_000,
    ]);

    expect($wish->contributorCount())->toBe(1);

    $reza->delete();

    expect($wish->contributorCount())->toBe(2)
        ->and($wish->receivedTotal())->toBe(100_000);
});

test('deleting a contributor leaves every contribution they made in place, with no contributor', function () {
    $reza = User::factory()->create();
    $contributions = Contribution::factory()->paid()->count(2)->create([
        'contributor_id' => $reza->id,
        'message' => 'happy birthday',
    ]);

    $reza->delete();

    foreach ($contributions as $contribution) {
        $contribution->refresh();

        expect($contribution->contributor_id)->toBeNull()
            ->and($contribution->contributor)->toBeNull()
            ->and($contribution->message)->toBe('happy birthday')
            ->and($contribution->amount)->toBe($contribution->payment->amount)
            ->and($contribution->settled_at)->not->toBeNull();
    }
});
