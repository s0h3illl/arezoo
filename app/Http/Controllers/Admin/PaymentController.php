<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\PaymentResource;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    /**
     * List every gateway attempt, newest first, including the ones that failed
     * and left no contribution behind. Read-only: there is no store, update, or
     * destroy here.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $payments = Payment::query()
            ->when($search !== '', fn (Builder $query) => $query->matching($search))
            ->with(['contribution.wish', 'contribution.contributor'])
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/payments/Index', [
            'payments' => PaymentResource::collection($payments),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }
}
