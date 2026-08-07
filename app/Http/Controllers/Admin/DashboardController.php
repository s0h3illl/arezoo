<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\DashboardSnapshot;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\WishResource;
use App\Models\Wish;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * How many of the newest wishes the dashboard shows. A glance, not a list:
     * there is no wishes section to page through to.
     */
    private const int LATEST_WISHES_LIMIT = 5;

    /**
     * Show the admin panel's landing screen: a once-a-day snapshot of the
     * platform's headline numbers, and a live look at what has just been
     * published.
     */
    public function __invoke(): Response
    {
        return Inertia::render('admin/Dashboard', [
            'snapshot' => DashboardSnapshot::current(),
            /*
             * Resolved to a plain array rather than handed over as a resource
             * collection: the `data` envelope a collection responds with earns
             * its place next to pagination's `meta` and `links`, and there is
             * neither here. Five rows, so the page reads `latest_wishes[0]`.
             */
            'latest_wishes' => WishResource::collection($this->latestWishes())->resolve(),
        ]);
    }

    /**
     * The newest wishes, read fresh on every visit so the screen always holds
     * one thing that is certainly current, however old the totals above it are.
     *
     * The identifier breaks ties: wishes created in the same instant — a
     * seeder's batch, or two people publishing at once — would otherwise come
     * back in whatever order the database chose, and the same five rows could
     * reorder between two refreshes with nothing changed.
     *
     * @return Collection<int, Wish>
     */
    private function latestWishes(): Collection
    {
        return Wish::query()
            ->with('owner')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::LATEST_WISHES_LIMIT)
            ->get();
    }
}
