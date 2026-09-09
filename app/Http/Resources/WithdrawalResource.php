<?php

namespace App\Http\Resources;

use App\Enums\WithdrawalStatus;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @mixin Withdrawal
 */
class WithdrawalResource extends JsonResource
{
    /**
     * @var string|null
     */
    public static $wrap = null;

    /**
     * @return array{id: int, amount: int, fee: int, sheba: string, status: WithdrawalStatus, note: string, requested_at: Carbon, decided_at: ?Carbon}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'fee' => $this->fee,
            'sheba' => $this->sheba,
            'status' => $this->status,
            'note' => $this->note,
            'requested_at' => $this->requested_at,
            'decided_at' => $this->decided_at,
        ];
    }
}
