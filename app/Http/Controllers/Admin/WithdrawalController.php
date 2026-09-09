<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WithdrawalDecision;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateWithdrawalRequest;
use App\Http\Resources\Admin\WithdrawalResource;
use App\Models\Withdrawal;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WithdrawalController extends Controller
{
    public function index(): Response
    {
        $withdrawals = Withdrawal::query()
            ->latest()
            ->with('owner')
            ->paginate(20);

        return Inertia::render('admin/withdrawals/Index', [
            'withdrawals' => WithdrawalResource::collection($withdrawals),
        ]);
    }

    public function update(UpdateWithdrawalRequest $request, Withdrawal $withdrawal): RedirectResponse
    {
        match ($request->decision()) {
            WithdrawalDecision::Accept => $withdrawal->accept(),
            WithdrawalDecision::Pay => $withdrawal->markPaid(),
        };

        return back();
    }
}
