<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * List every user on the platform, other than the admins.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->excludingAdmins()
            ->when($search !== '', fn (Builder $query) => $query->matching($search))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/users/Index', [
            'users' => UserResource::collection($users),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show one user in full, so judging a report does not take a query.
     */
    public function show(User $user): Response
    {
        return Inertia::render('admin/users/Show', [
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Block or unblock the user, or update who they are.
     *
     * `is_blocked` is assigned rather than filled: barring someone from the app
     * is a moderation decision, not an attribute of theirs a form may carry in.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        if ($request->isBlockAction()) {
            $user->is_blocked = $request->boolean('is_blocked');
        } else {
            $user->fill($request->safe()->only(['name', 'email']));

            /* An address nobody has proven is an unverified address, however it changed. */
            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }
        }

        $user->save();

        return back();
    }
}
