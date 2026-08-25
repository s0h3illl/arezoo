<?php

namespace App\Models;

use Database\Factories\WishFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $description
 * @property string|null $thumbnail
 * @property string|null $purchase_link
 * @property int $price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $owner
 * @property-read Collection<int, Contribution> $contributions
 * @property-read int|null $received_total
 */
class Wish extends Model
{
    /** @use HasFactory<WishFactory> */
    use HasFactory;

    /**
     * `contributions.wish_id` cascades, and balances are derived from exactly
     * those rows (ADR-0005), so a real delete would shrink money an owner had
     * already earned. A soft delete never fires the foreign key, leaving it to
     * `forceDelete()` — the full-purge path of ADR-0003.
     */
    use SoftDeletes;

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
     * The contributions to this wish whose money actually landed.
     *
     * One relation holds the definition of what counts, so the figure a single
     * wish reports and the figure a whole grid of them reports are the same
     * figure rather than two spellings of it.
     *
     * @return HasMany<Contribution, $this>
     */
    public function paidContributions(): HasMany
    {
        return $this->contributions()->paid();
    }

    /**
     * The amount this wish has actually received.
     *
     * The total is deliberately uncapped — a wish is allowed to receive more
     * than its price.
     */
    public function receivedTotal(): int
    {
        return (int) $this->paidContributions()->sum('amount');
    }

    /**
     * Read the same amount as `receivedTotal()`, for every wish in one query.
     *
     * A grid of cards each showing what it has received would otherwise cost a
     * query per card, so the sum rides along with the rows as `received_total`.
     * It is null where nothing has been paid, which is a sum over no rows rather
     * than a missing figure.
     *
     * @param  Builder<Wish>  $query
     */
    #[Scope]
    protected function withReceivedTotal(Builder $query): void
    {
        $query->withSum('paidContributions as received_total', 'amount');
    }

    public function loadReceivedTotal(): static
    {
        return $this->loadSum('paidContributions as received_total', 'amount');
    }

    /**
     * How many people have paid towards this wish.
     *
     * Someone who gives more than once still counts as one contributor.
     */
    public function contributorCount(): int
    {
        return $this->paidContributions()->distinct()->count('contributor_id');
    }
}
