<?php

namespace App\Http\Resources;

use App\Models\Wish;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use LogicException;

/**
 * A wish as the application reads it — every column of the `wishes` table.
 *
 * One resource for the whole model rather than one per screen. A screen that
 * wants less narrows what it declares where it uses it, so a field added to the
 * table reaches every page at once instead of being threaded through a new
 * shape each time. The profile grid renders five of these; see `WishCard.vue`,
 * whose prop is a `Pick<Wish, …>` of exactly those.
 *
 * `received` is read from the `received_total` the grid's one aggregate brings
 * along — see `Wish::withReceivedTotal()`. It is uncapped (ADR-0004), so a wish
 * may report more than its price.
 *
 * @mixin Wish
 */
class WishResource extends JsonResource
{
    /**
     * A single wish is handed to Inertia as the prop itself, not as an envelope.
     * See `App\Http\Resources\Admin\UserResource::$wrap` for why.
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array{id: int, user_id: int, title: string, description: string, thumbnail: ?string, purchase_link: string, price: int, received: int, created_at: ?Carbon, updated_at: ?Carbon}
     *
     * @throws LogicException when the grid's aggregate was never loaded.
     */
    public function toArray(Request $request): array
    {
        /*
         * A sum over no paid contributions is null, and so is an aggregate that
         * was never loaded — the two are told apart by whether the column is
         * there at all. Without this, a caller who forgot the scope would
         * publish every wish's money as zero and look perfectly healthy.
         */
        if (! array_key_exists('received_total', $this->getAttributes())) {
            throw new LogicException(
                'A wish was serialised without what it has received. Load the query with `Wish::withReceivedTotal()`.'
            );
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'description' => $this->description,
            // A URL rather than a storage path, so the card renders a cover
            // without knowing where the disk keeps it. Null stays null: the card
            // draws its own placeholder rather than fetching nothing.
            'thumbnail' => $this->thumbnail === null
                ? null
                : Storage::disk('public')->url($this->thumbnail),
            'purchase_link' => $this->purchase_link,
            'price' => $this->price,
            'received' => (int) $this->received_total,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
