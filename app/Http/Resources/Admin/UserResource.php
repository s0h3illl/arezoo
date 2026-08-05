<?php

namespace App\Http\Resources\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * The one row the admin users list renders, mirrored by `AdminUserRow` in
 * `resources/js/types/user.ts`.
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * A single user is handed to Inertia as the prop itself, not as an envelope.
     *
     * Inertia resolves every `Responsable` prop through `toResponse()`, which is
     * where the default `data` wrapper is applied — a page given one of these
     * would read `user.data.name`. The collection keeps its own `data` key, which
     * is declared by `ResourceCollection` and is untouched by this.
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * Deliberately narrow: the panel is admin-only, so this is not a leak, but a
     * column added to `users` later should not silently ride along to the frontend.
     *
     * @return array{id: int, name: string, email: string, email_verified_at: ?Carbon, is_blocked: bool}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'is_blocked' => $this->is_blocked,
        ];
    }
}
