<?php

use App\Enums\ContributionStatus;
use App\Enums\PaymentStatus;
use App\Models\Contribution;

test('settling a contribution marks it paid, verifies its payment, and records when it settled', function () {
    $contribution = Contribution::factory()->pending()->create();

    $contribution->markPaid('ref-123');
    $contribution->refresh();

    expect($contribution->status)->toBe(ContributionStatus::Paid)
        ->and($contribution->settled_at)->not->toBeNull()
        ->and($contribution->payment->fresh()->status)->toBe(PaymentStatus::Verified)
        ->and($contribution->payment->fresh()->reference_id)->toBe('ref-123');
});

test('a pending contribution has no settlement time', function () {
    $contribution = Contribution::factory()->pending()->create();

    expect($contribution->settled_at)->toBeNull();
});

test('a paid contribution has a settlement time', function () {
    $contribution = Contribution::factory()->paid()->create();

    expect($contribution->settled_at)->not->toBeNull();
});

test('the factory can settle a contribution at a chosen time', function () {
    $when = now()->subDays(5);

    $contribution = Contribution::factory()->settledAt($when)->create();

    expect($contribution->status)->toBe(ContributionStatus::Paid)
        ->and($contribution->settled_at->timestamp)->toBe($when->timestamp);
});
