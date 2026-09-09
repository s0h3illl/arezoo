<?php

namespace App\Http\Controllers;

use App\Actions\CalculateBalance;
use App\Http\Resources\WithdrawalResource;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    public function __invoke(): Response
    {
        $owner = auth()->user();

        return Inertia::render('dashboard/Finance', [
            'balance' => CalculateBalance::for($owner)->toArray(),
            'hold_hours' => (int) config('withdrawals.hold_hours'),
            'fee' => (int) config('withdrawals.fee'),
            'minimum' => (int) config('withdrawals.minimum'),
            'withdrawals' => WithdrawalResource::collection(
                $owner->withdrawals()
                    ->orderByDesc('requested_at')
                    ->get()
            )->resolve(),
        ]);
    }
}
