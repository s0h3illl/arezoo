<?php

namespace App\Actions;

use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestWithdrawal
{
    public static function for(User $user, int $amount, string $sheba): Withdrawal
    {
        return DB::transaction(function () use ($user, $amount, $sheba): Withdrawal {
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            if ($user->withdrawals()->open()->exists()) {
                throw ValidationException::withMessages([
                    'amount' => (string) __('withdrawals.already_open'),
                ]);
            }

            if ($amount > CalculateBalance::for($user)->available) {
                throw ValidationException::withMessages([
                    'amount' => (string) __('withdrawals.insufficient'),
                ]);
            }

            return $user->withdrawals()->create([
                'amount' => $amount,
                'fee' => (int) config('withdrawals.fee'),
                'sheba' => $sheba,
                'requested_at' => now(),
            ]);
        });
    }
}
