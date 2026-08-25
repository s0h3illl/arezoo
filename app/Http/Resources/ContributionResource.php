<?php

namespace App\Http\Resources;

use App\Enums\ContributionVisibility;
use App\Models\Contribution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @mixin Contribution
 */
class ContributionResource extends JsonResource
{
    /**
     * @var string|null
     */
    public static $wrap = null;

    /**
     * @return array{id: int, name: string, avatar: ?string, amount: int, settled_at: ?Carbon}
     */
    public function toArray(Request $request): array
    {
        $visible = $this->isVisible();

        return [
            'id' => $this->id,
            'name' => $visible ? $this->contributor->name : $this->maskedName(),
            'avatar' => $visible ? $this->contributor->avatar : null,
            'amount' => $this->amount,
            'settled_at' => $this->settled_at,
        ];
    }

    private function isVisible(): bool
    {
        if ($this->visibility === ContributionVisibility::Public) {
            return true;
        }

        if ($this->visibility === ContributionVisibility::OwnerOnly) {
           return $this->isOwner();
        }

        return false;
    }

    private function isOwner(): bool
    {
        if (auth()->guest()) {
            return false;
        }

        return auth()->user()->id === $this->wish->user_id;
    }

    private function maskedName(): string
    {
        return (string) __('contributions.anonymous');
    }
}
