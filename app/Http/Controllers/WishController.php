<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWishRequest;
use App\Models\Wish;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WishController extends Controller
{
    /**
     * Publish a wish to the signed-in user's own profile.
     *
     * The wish is created through the owner's relation, so it belongs to whoever
     * the session says is here rather than to anything the body claims.
     *
     * The response is a redirect to that profile, and the new wish is simply in
     * the refreshed props. Nothing is inserted optimistically: the server is the
     * truth, so the grid cannot disagree with the database.
     */
    public function store(StoreWishRequest $request): RedirectResponse
    {
        $owner = auth()->user();

        $owner->wishes()->create([
            ...$request->safe()->only(['title', 'description', 'purchase_link', 'price']),
            'thumbnail' => $request->file('thumbnail')?->store('wishes', 'public'),
        ]);

        return to_route('profile', $owner->username);
    }

    public function destroy(Request $request, Wish $wish): RedirectResponse
    {
        $wish->delete();

        return to_route('profile', $request->user()->username);
    }
}
