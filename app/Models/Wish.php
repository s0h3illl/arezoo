<?php

namespace App\Models;

use Database\Factories\WishFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $description
 * @property string|null $thumbnail
 * @property string $purchase_link
 * @property int $price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $owner
 * @property-read Collection<int, Contribution> $contributions
 */
class Wish extends Model
{
    /** @use HasFactory<WishFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * The user this wish belongs to.
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Every contribution made towards this wish.
     *
     * @return HasMany<Contribution, $this>
     */
    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    /**
     * The amount this wish has actually received.
     *
     * Only paid contributions count, and the total is deliberately uncapped —
     * a wish is allowed to receive more than its price.
     */
    public function receivedTotal(): int
    {
        return (int) $this->contributions()->paid()->sum('amount');
    }

    /**
     * How many people have paid towards this wish.
     *
     * Someone who gives more than once still counts as one contributor.
     */
    public function contributorCount(): int
    {
        return $this->contributions()->paid()->distinct()->count('contributor_id');
    }
}
