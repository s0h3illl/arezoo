<?php

namespace App\Http\Controllers;

use App\Actions\CalculateBalance;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('dashboard/Finance', [
            'balance' => CalculateBalance::for(auth()->user())->toArray(),
            'hold_hours' => (int) config('withdrawals.hold_hours'),
        ]);
    }
}
