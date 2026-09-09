<?php

namespace App\Http\Controllers;

use App\Actions\RequestWithdrawal;
use App\Http\Requests\StoreWithdrawalRequest;
use Illuminate\Http\RedirectResponse;

class WithdrawalController extends Controller
{
    public function store(StoreWithdrawalRequest $request): RedirectResponse
    {
        RequestWithdrawal::for(
            auth()->user(),
            (int) $request->validated('amount'),
            (string) $request->validated('sheba'),
        );

        return to_route('dashboard.finance');
    }
}
