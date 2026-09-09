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
     * @return array{id: int, state: 'visible'|'anonymous'|'deleted', contributor: array{name: string, avatar: ?string}, message: ?string, wish: array{title: string, deleted: bool}, amount: int, settled_at: ?string}
     */
    public function toArray(Request $request): array
    {
        $state = $this->contributorState();

        return [
            'id' => $this->id,
            'state' => $state,
            'contributor' => [
                'name' => $this->contributorName($state),
                'avatar' => $state === 'visible' ? $this->contributor->avatarUrl() : null,
            ],
            'message' => $this->message,
            'wish' => [
                'title' => $this->wish->title,
                'deleted' => $this->wish->trashed(),
            ],
            'amount' => $this->amount,
            'settled_at' => $this->settled_at?->diffForHumans(),
        ];
    }

    /**
     * @return 'visible'|'anonymous'|'deleted'
     */
    private function contributorState(): string
    {
        if ($this->contributor === null) {
            return 'deleted';
        }

        return $this->isVisible() ? 'visible' : 'anonymous';
    }

    /**
     * @param  'visible'|'anonymous'|'deleted'  $state
     */
    private function contributorName(string $state): string
    {
        return match ($state) {
            'visible' => $this->contributor->name,
            'deleted' => (string) __('contributions.deleted'),
            'anonymous' => (string) __('contributions.anonymous'),
        };
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
}
