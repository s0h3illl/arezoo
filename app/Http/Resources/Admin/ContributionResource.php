<?php

namespace App\Http\Resources\Admin;

use App\Enums\ContributionStatus;
use App\Models\Contribution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * A contribution as the admin panel reads it — one row of the list.
 *
 * Deliberately narrow, and deliberately blind to visibility: a hidden or
 * owner-only contribution's contributor is included exactly like a public
 * one's, because visibility governs what a user sees, never what moderation
 * can see.
 *
 * @mixin Contribution
 */
class ContributionResource extends JsonResource
{
    /**
     * A single contribution is handed to Inertia as the prop itself, not as an
     * envelope. See `UserResource::$wrap` for why.
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array{id: int, wish: array{id: int, title: string}, contributor: array{id: int, name: string}, amount: int, status: ContributionStatus, settled_at: ?Carbon, payment: array{id: int, transaction_id: ?string, reference_id: ?string}}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wish' => [
                'id' => $this->wish->id,
                'title' => $this->wish->title,
            ],
            'contributor' => [
                'id' => $this->contributor->id,
                'name' => $this->contributor->name,
            ],
            'amount' => $this->amount,
            'status' => $this->status,
            'settled_at' => $this->settled_at,
            /*
             * The gateway attempt behind the money — both identifiers, so a row
             * here can be matched against the gateway's own dashboard. Never
             * null: a contribution whose payment failed no longer exists
             * (ADR-0001), so the two are only ever seen together.
             */
            'payment' => [
                'id' => $this->payment->id,
                'transaction_id' => $this->payment->transaction_id,
                'reference_id' => $this->payment->reference_id,
            ],
        ];
    }
}
