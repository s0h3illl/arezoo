<?php

namespace App\Http\Resources\Admin;

use App\Models\Wish;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * A wish as the admin panel reads it — one row of the dashboard's glance at
 * what has just been published.
 *
 * Deliberately narrow, and deliberately without the amount the wish has
 * received: that is a per-wish aggregate, and a column nobody asked for is not
 * worth either a query per row or an aggregate join across the table.
 *
 * @mixin Wish
 */
class WishResource extends JsonResource
{
    /**
     * A single wish is handed to Inertia as the prop itself, not as an
     * envelope. See `UserResource::$wrap` for why.
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array{id: int, title: string, owner: array{id: int, name: string}, price: int, created_at: ?Carbon}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'owner' => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
            ],
            'price' => $this->price,
            'created_at' => $this->created_at,
        ];
    }
}
