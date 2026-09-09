<?php

namespace App\Http\Resources\Admin;

use App\Enums\WithdrawalStatus;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * A withdrawal as the admin queue reads it — one row carrying everything needed
 * to make the transfer by hand. The owner is plain text: no row links to a user
 * detail view.
 *
 * @mixin Withdrawal
 */
class WithdrawalResource extends JsonResource
{
    /**
     * @var string|null
     */
    public static $wrap = null;

    /**
     * @return array{id: int, owner: array{name: string}, amount: int, fee: int, transfer: int, sheba: string, status: WithdrawalStatus, requested_at: Carbon, decided_at: ?Carbon}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'owner' => [
                'name' => $this->owner->name,
            ],
            'amount' => $this->amount,
            'fee' => $this->fee,
            'transfer' => $this->amount - $this->fee,
            'sheba' => $this->sheba,
            'status' => $this->status,
            'requested_at' => $this->requested_at,
            'decided_at' => $this->decided_at,
        ];
    }
}
