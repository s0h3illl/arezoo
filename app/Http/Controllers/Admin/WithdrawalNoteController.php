<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateWithdrawalNoteRequest;
use App\Models\Withdrawal;
use Illuminate\Http\RedirectResponse;

class WithdrawalNoteController extends Controller
{
    public function update(UpdateWithdrawalNoteRequest $request, Withdrawal $withdrawal): RedirectResponse
    {
        $withdrawal->update(['note' => $request->note()]);

        return back();
    }
}
