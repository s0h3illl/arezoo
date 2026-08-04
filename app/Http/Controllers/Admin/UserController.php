<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * List every user on the platform.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->when($search !== '', fn (Builder $query) => $query->matching($search))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through($this->toRow(...));

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * The one row the list renders.
     *
     * Deliberately narrow: the panel is admin-only, so this is not a leak, but a
     * column added to `users` later should not silently ride along to the frontend.
     *
     * @return array{id: int, name: string, email: string, email_verified_at: ?Carbon, is_blocked: bool}
     */
    private function toRow(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'is_blocked' => $user->is_blocked,
        ];
    }
}
