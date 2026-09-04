<?php

namespace App\Http\Resources;

use App\Enums\ContributionVisibility;
use App\Models\Contribution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Contribution
 */
class MessageResource extends JsonResource
{
    /**
     * @var string|null
     */
    public static $wrap = null;

    /**
     * @return array{id: int, state: 'visible'|'anonymous', contributor: array{name: string, avatar: ?string}, message: ?string, wish: array{title: string}, amount: int, settled_at: ?string}
     */
    public function toArray(Request $request): array
    {
        $visible = $this->isVisible();

        return [
            'id' => $this->id,
            'state' => $visible ? 'visible' : 'anonymous',
            'contributor' => [
                'name' => $visible ? $this->contributor->name : $this->maskedName(),
                'avatar' => $visible ? $this->contributor->avatarUrl() : null,
            ],
            'message' => $this->message,
            'wish' => [
                'title' => $this->wish->title,
            ],
            'amount' => $this->amount,
            'settled_at' => $this->settled_at?->diffForHumans(),
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
