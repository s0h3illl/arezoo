<?php

namespace App\Models;

use App\Enums\ContributionStatus;
use App\Enums\ContributionVisibility;
use App\Enums\PaymentStatus;
use Database\Factories\ContributionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Money a contributor has put towards a wish.
 *
 * A contribution is provisional until its payment verifies: a failed attempt
 * deletes the row rather than marking it failed. See ADR-0001.
 *
 * @property int $id
 * @property int $wish_id
 * @property int $contributor_id
 * @property int $payment_id
 * @property int $amount
 * @property string|null $message
 * @property ContributionVisibility $visibility
 * @property ContributionStatus $status
 * @property Carbon|null $settled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Wish $wish
 * @property-read User $contributor
 * @property-read Payment $payment
 */
class Contribution extends Model
{
    /** @use HasFactory<ContributionFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'visibility' => ContributionVisibility::class,
            'status' => ContributionStatus::class,
            'settled_at' => 'datetime',
        ];
    }

    /**
     * The wish this contribution was made towards, deleted or not.
     *
     * A contribution outlives its wish (ADR-0003), so the relation has to reach
     * a trashed one rather than resolving to null.
     *
     * @return BelongsTo<Wish, $this>
     */
    public function wish(): BelongsTo
    {
        return $this->belongsTo(Wish::class)->withTrashed();
    }

    /**
     * The user who made this contribution.
     *
     * @return BelongsTo<User, $this>
     */
    public function contributor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contributor_id');
    }

    /**
     * The gateway attempt this contribution was created alongside.
     *
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Limit the query to contributions whose money actually landed.
     *
     * @param  Builder<$this>  $query
     */
    public function scopePaid(Builder $query): void
    {
        $query->where('status', ContributionStatus::Paid);
    }

    /**
     * Count this contribution towards its wish, on the gateway's word.
     *
     * The contribution, its payment, and its settlement time move together so
     * a crash can never leave one settled without the others. `created_at`
     * marks when the gateway attempt began, not when it verified, which is
     * why settlement needs a timestamp of its own (ADR-0005).
     */
    public function markPaid(string $referenceId): void
    {
        DB::transaction(function () use ($referenceId): void {
            $this->payment->update([
                'reference_id' => $referenceId,
                'status' => PaymentStatus::Verified,
            ]);

            $this->update([
                'status' => ContributionStatus::Paid,
                'settled_at' => now(),
            ]);
        });
    }

    /**
     * Drop this contribution while keeping the failed attempt on record.
     *
     * The payment deliberately outlives the contribution it was created
     * alongside, so a failure stays auditable. See ADR-0001.
     */
    public function abandon(): void
    {
        DB::transaction(function (): void {
            $this->delete();

            $this->payment->update(['status' => PaymentStatus::Failed]);
        });
    }
}
