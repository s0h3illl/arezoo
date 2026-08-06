<?php

namespace App\Http\Resources\Admin;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * A gateway attempt as the admin panel reads it — one row of the list.
 *
 * The contribution is nullable by design, not by accident: a failed attempt
 * outlives the contribution it was created alongside (ADR-0001), and an attempt
 * with nothing behind it is the case this screen exists for.
 *
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * A single payment is handed to Inertia as the prop itself, not as an
     * envelope. See `UserResource::$wrap` for why.
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array{id: int, transaction_id: ?string, reference_id: ?string, amount: int, status: PaymentStatus, created_at: ?Carbon, contribution: ?array{id: int, wish: array{id: int, title: string}, contributor: array{id: int, name: string}}}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'reference_id' => $this->reference_id,
            'amount' => $this->amount,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'contribution' => $this->contribution === null ? null : [
                'id' => $this->contribution->id,
                'wish' => [
                    'id' => $this->contribution->wish->id,
                    'title' => $this->contribution->wish->title,
                ],
                'contributor' => [
                    'id' => $this->contribution->contributor->id,
                    'name' => $this->contribution->contributor->name,
                ],
            ],
        ];
    }
}
