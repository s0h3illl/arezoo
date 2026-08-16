<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Http\Resources\WishResource;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * How many cards one page of the grid holds.
     *
     * Divisible by two, three and four, so no breakpoint ends on a short row.
     */
    private const int WISHES_PER_PAGE = 12;

    public function __invoke(Request $request, User $user): Response
    {
        return Inertia::render('Profile', [
            'user' => new UserResource($user),
            'wishes' => Inertia::scroll(fn () => WishResource::collection(
                $user->wishes()
                    ->withReceivedTotal()
                    ->paginate(self::WISHES_PER_PAGE)
            )),
        ]);
    }
}
