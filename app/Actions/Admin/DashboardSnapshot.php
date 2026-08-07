<?php

namespace App\Actions\Admin;

use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;
use Illuminate\Support\Facades\Cache;

/**
 * A once-a-day reading of the platform's headline numbers, for the dashboard's
 * three tiles.
 *
 * The three totals and the moment they were taken are computed and cached
 * together as one array, so the moment is true of all three — never of
 * whichever total a later recalculation happened to touch. Nothing in the
 * app's write paths invalidates this cache; the reading simply ages out a day
 * after it was taken, and the next request behind it takes a fresh one.
 */
class DashboardSnapshot
{
    private const string CACHE_KEY = 'admin.dashboard.snapshot';

    /**
     * The current snapshot, taking a fresh one if a day has passed since the
     * cached one was calculated.
     *
     * @return array{users_count: int, wishes_count: int, raised_amount: int, computed_at: string}
     */
    public static function current(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addDay(), function (): array {
            return [
                'users_count' => User::query()->excludingAdmins()->count(),
                'wishes_count' => Wish::query()->count(),
                'raised_amount' => (int) Contribution::query()->paid()->sum('amount'),
                // A string, not a Carbon instance: config/cache.php disables
                // unserialising arbitrary classes from cache, so an object
                // stored here would come back broken.
                'computed_at' => now()->toIso8601String(),
            ];
        });
    }
}
