<?php

namespace App\Actions;

use App\DTOs\Balance;
use App\Models\Contribution;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CalculateBalance
{
    public static function for(User $user): Balance
    {
        $boundary = now()->subHours(self::holdHours());

        return new Balance(
            available: (int) self::earnings($user)
                ->where('contributions.settled_at', '<', $boundary)
                ->sum('contributions.amount') - self::reserved($user),
            held: (int) self::earnings($user)
                ->where('contributions.settled_at', '>=', $boundary)
                ->sum('contributions.amount'),
        );
    }

    /**
     * @return Builder<Contribution>
     */
    private static function earnings(User $user): Builder
    {
        return Contribution::query()
            ->join('wishes', 'wishes.id', '=', 'contributions.wish_id')
            ->where('wishes.user_id', $user->id)
            ->paid();
    }

    private static function reserved(User $user): int
    {
        return (int) $user->withdrawals()->reserving()->sum('amount');
    }

    private static function holdHours(): int
    {
        return (int) config('withdrawals.hold_hours');
    }
}
