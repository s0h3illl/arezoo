<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWishRequest;
use App\Http\Requests\UpdateWishRequest;
use App\Http\Resources\ContributionResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\WishResource;
use App\Models\Wish;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class WishController extends Controller
{
    private const int CONTRIBUTIONS_PER_PAGE = 5;

    public function store(StoreWishRequest $request): RedirectResponse
    {
        $owner = auth()->user();

        $owner->wishes()->create([
            ...$request->safe()->only(['title', 'description', 'purchase_link', 'price']),
            'thumbnail' => $request->file('thumbnail')?->store('wishes', 'public'),
        ]);

        return to_route('profile', $owner->username);
    }

    public function show(Wish $wish): Response
    {
        $wish->loadReceivedTotal();
        $wish->load('owner');

        return Inertia::render('WishDetail', [
            'wish' => new WishResource($wish),
            'owner' => new UserResource($wish->owner),
            'contributions' => Inertia::scroll(fn () => ContributionResource::collection(
                $wish->paidContributions()
                    ->with(['contributor', 'wish'])
                    ->orderByDesc('settled_at')
                    ->paginate(self::CONTRIBUTIONS_PER_PAGE)
            )),
        ]);
    }

    public function update(UpdateWishRequest $request, Wish $wish): RedirectResponse
    {
        $wish->update([
            ...$request->safe()->only(['title', 'description', 'purchase_link', 'price']),
            ...$this->thumbnailChange($request, $wish),
        ]);

        return to_route('profile', $request->user()->username);
    }

    /**
     * @return array<string, string|null>
     *
     * @throws RuntimeException when the replacement cannot be written to the disk.
     */
    private function thumbnailChange(UpdateWishRequest $request, Wish $wish): array
    {
        $replacement = $request->file('thumbnail');
        $isRemoving = $request->boolean('remove_thumbnail');

        if ($replacement === null && ! $isRemoving) {
            return [];
        }

        $stored = $replacement?->store('wishes', 'public');

        if ($stored === false) {
            throw new RuntimeException('Unable to store the replacement wish cover.');
        }

        if ($wish->thumbnail !== null) {
            Storage::disk('public')->delete($wish->thumbnail);
        }

        return ['thumbnail' => $stored];
    }

    public function destroy(Request $request, Wish $wish): RedirectResponse
    {
        $wish->delete();

        return to_route('profile', $request->user()->username);
    }
}
